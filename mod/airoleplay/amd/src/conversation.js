// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AI Roleplay - Main conversation UI controller.
 *
 * Manages: chat interface, voice recording, real-time transcript,
 * sentiment indicators, timer, turn management, evaluation results.
 *
 * @module     mod_airoleplay/conversation
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';

// ====================================================================
// CONVERSATION CONTROLLER
// ====================================================================

class ConversationController {

    constructor(config) {
        this.sessionId    = config.sessionId;
        this.sessionToken = config.sessionToken;
        this.cmid         = config.cmid;
        this.enableVoice  = config.enableVoice;
        this.enableHints  = config.enableHints;
        this.maxTurns     = config.maxTurns || 0;
        this.maxDuration  = config.maxDuration || 0;
        this.language     = config.language || 'en';
        this.persona      = config.persona || {};

        this.turnCount      = 0;
        this.isProcessing   = false;
        this.sessionActive  = true;
        this.startTime      = Date.now();
        this.timerInterval  = null;
        this.voiceRecorder  = null;
        this.audioContext   = null;
        this.mediaStream    = null;

        // DOM references.
        this.chatMessages   = document.getElementById('air-chat-messages');
        this.messageInput   = document.getElementById('air-message-input');
        this.sendBtn        = document.getElementById('air-send-btn');
        this.voiceBtn       = document.getElementById('air-voice-btn');
        this.endBtn         = document.getElementById('air-end-btn');
        this.timerEl        = document.getElementById('air-timer');
        this.turnCountEl    = document.getElementById('air-turn-count');
        this.sentimentEl    = document.getElementById('air-sentiment-indicator');
        this.confidenceMeter = document.getElementById('air-confidence-meter');
        this.typingIndicator = document.getElementById('air-typing-indicator');
        this.waveformEl     = document.getElementById('air-waveform');
        this.statusBar      = document.getElementById('air-status-bar');

        this._bindEvents();
        this._startTimer();
        this._showWelcomeMessage();
    }

    // ----------------------------------------------------------------
    // INITIALIZATION
    // ----------------------------------------------------------------

    _bindEvents() {
        // Send message on button click.
        this.sendBtn?.addEventListener('click', () => this.sendMessage());

        // Send on Enter (Shift+Enter for newline).
        this.messageInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Auto-resize textarea.
        this.messageInput?.addEventListener('input', () => {
            this.messageInput.style.height = 'auto';
            this.messageInput.style.height = Math.min(this.messageInput.scrollHeight, 120) + 'px';
        });

        // Voice button.
        this.voiceBtn?.addEventListener('click', () => this.toggleVoiceRecording());

        // End session.
        this.endBtn?.addEventListener('click', () => this.endSession());

        // Warn before leaving.
        window.addEventListener('beforeunload', (e) => {
            if (this.sessionActive && this.turnCount > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    _showWelcomeMessage() {
        if (!this.persona?.name) return;

        const intro = `Hi! I'm ${this.persona.name}. ${this._getPersonaIntro()}`;
        this._appendMessage('assistant', intro, {isOpener: true});
    }

    _getPersonaIntro() {
        const toneIntros = {
            'angry':      "Let's get straight to it.",
            'friendly':   "Happy to help today.",
            'formal':     "How may I assist you?",
            'skeptical':  "What is it you need?",
            'frustrated': "I hope this doesn't take long.",
            'neutral':    "What can I do for you?",
        };
        return toneIntros[this.persona?.tone] || "What can I do for you?";
    }

    // ----------------------------------------------------------------
    // CHAT
    // ----------------------------------------------------------------

    async sendMessage() {
        if (this.isProcessing || !this.sessionActive) return;

        const text = this.messageInput?.value?.trim();
        if (!text) return;

        this.messageInput.value = '';
        this.messageInput.style.height = 'auto';
        this._setProcessing(true);

        // Append user message immediately.
        this._appendMessage('user', text);

        try {
            const response = await this._callApi('mod_airoleplay_send_message', {
                session_id:    this.sessionId,
                session_token: this.sessionToken,
                message:       text,
                message_type:  'text',
            });

            // Update sentiment indicator.
            this._updateSentiment(response.sentiment, response.sentiment_score);

            // Show typing indicator briefly.
            this._showTyping();
            await this._delay(600);
            this._hideTyping();

            // Append AI response.
            this._appendMessage('assistant', response.ai_response);

            this.turnCount++;
            this._updateTurnCounter();

            // Check if session should end.
            if (response.should_end) {
                await this._promptSessionEnd('max_turns');
            }

        } catch (err) {
            this._appendSystemMessage('error', 'Failed to send message. Please try again.');
            console.error('Send message error:', err);
        } finally {
            this._setProcessing(false);
        }
    }

    // ----------------------------------------------------------------
    // VOICE RECORDING
    // ----------------------------------------------------------------

    async toggleVoiceRecording() {
        if (!this.enableVoice) return;

        if (this.voiceRecorder?.state === 'recording') {
            this._stopRecording();
        } else {
            await this._startRecording();
        }
    }

    async _startRecording() {
        try {
            this.mediaStream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    channelCount: 1,
                    sampleRate: 16000,
                    echoCancellation: true,
                    noiseSuppression: true,
                }
            });

            this.audioContext = new AudioContext({sampleRate: 16000});
            const source      = this.audioContext.createMediaStreamSource(this.mediaStream);

            // Set up waveform visualizer.
            this._startWaveform(source);

            const options = {mimeType: 'audio/webm;codecs=opus'};
            if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                options.mimeType = 'audio/ogg;codecs=opus';
            }

            this.audioChunks    = [];
            this.voiceRecorder  = new MediaRecorder(this.mediaStream, options);

            this.voiceRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) this.audioChunks.push(e.data);
            };

            this.voiceRecorder.onstop = async () => {
                const blob = new Blob(this.audioChunks, {type: options.mimeType});
                await this._processAudioBlob(blob, options.mimeType);
            };

            this.voiceRecorder.start(250); // 250ms timeslices.
            this._setVoiceActive(true);

        } catch (err) {
            if (err.name === 'NotAllowedError') {
                Notification.alert('Microphone Access Required',
                    'Please allow microphone access to use voice input.');
            } else {
                Notification.alert('Voice Error', 'Could not start recording: ' + err.message);
            }
        }
    }

    _stopRecording() {
        if (this.voiceRecorder?.state === 'recording') {
            this.voiceRecorder.stop();
        }
        this.mediaStream?.getTracks().forEach(t => t.stop());
        this._stopWaveform();
        this._setVoiceActive(false);
    }

    async _processAudioBlob(blob, mimeType) {
        this._setProcessing(true);
        this._appendSystemMessage('info', '🎙️ Processing voice...');

        try {
            // Convert to base64 for API transmission.
            const base64 = await this._blobToBase64(blob);

            const response = await this._callApi('mod_airoleplay_transcribe_audio', {
                session_id:    this.sessionId,
                session_token: this.sessionToken,
                audio_data:    base64,
                audio_format:  mimeType,
                language:      this.language,
            });

            if (response.text?.trim()) {
                // Show transcript in input for review.
                this.messageInput.value = response.text.trim();
                this.messageInput.dispatchEvent(new Event('input'));

                // Auto-send after brief delay.
                await this._delay(300);
                await this.sendMessage();
            } else {
                this._appendSystemMessage('warning', 'Could not understand audio. Please try again.');
            }

        } catch (err) {
            this._appendSystemMessage('error', 'Voice processing failed. Please type your response.');
            console.error('Audio processing error:', err);
        } finally {
            this._setProcessing(false);
        }
    }

    // ----------------------------------------------------------------
    // WAVEFORM VISUALIZER
    // ----------------------------------------------------------------

    _startWaveform(source) {
        if (!this.waveformEl) return;

        this.analyser = this.audioContext.createAnalyser();
        this.analyser.fftSize = 256;
        source.connect(this.analyser);

        const bufferLength = this.analyser.frequencyBinCount;
        const dataArray    = new Uint8Array(bufferLength);
        const canvas       = this.waveformEl;
        const ctx          = canvas.getContext('2d');

        const draw = () => {
            if (!this._drawingWaveform) return;

            requestAnimationFrame(draw);
            this.analyser.getByteFrequencyData(dataArray);

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const barWidth = (canvas.width / bufferLength) * 2.5;
            let x = 0;

            for (let i = 0; i < bufferLength; i++) {
                const barHeight = (dataArray[i] / 255) * canvas.height;
                const hue = 220 + (i / bufferLength) * 60;
                ctx.fillStyle = `hsla(${hue}, 80%, 60%, 0.8)`;
                ctx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
                x += barWidth + 1;
            }
        };

        this._drawingWaveform = true;
        this.waveformEl.style.display = 'block';
        draw();
    }

    _stopWaveform() {
        this._drawingWaveform = false;
        if (this.waveformEl) {
            this.waveformEl.style.display = 'none';
        }
    }

    // ----------------------------------------------------------------
    // SESSION END
    // ----------------------------------------------------------------

    async endSession() {
        if (!this.sessionActive) return;

        const confirmed = await this._confirm(
            'End Session',
            'Are you sure you want to end this roleplay? Your session will be submitted for AI evaluation.'
        );
        if (!confirmed) return;

        this._setProcessing(true);
        this.sessionActive = false;
        clearInterval(this.timerInterval);

        try {
            await this._callApi('mod_airoleplay_end_session', {
                session_id:    this.sessionId,
                session_token: this.sessionToken,
            });

            // Redirect to results page.
            window.location.href = M.cfg.wwwroot + '/mod/airoleplay/results.php?session=' + this.sessionId;

        } catch (err) {
            this.sessionActive = true;
            this._setProcessing(false);
            Notification.alert('Error', 'Failed to end session. Please try again.');
        }
    }

    async _promptSessionEnd(reason) {
        const messages = {
            'max_turns': 'You have reached the maximum number of conversation turns.',
            'time_up':   'Time is up for this session.',
        };

        this._appendSystemMessage('info', messages[reason] || 'Session ending.');
        await this._delay(1500);
        await this.endSession();
    }

    // ----------------------------------------------------------------
    // DOM HELPERS
    // ----------------------------------------------------------------

    _appendMessage(role, content, options = {}) {
        if (!this.chatMessages) return;

        const wrapper = document.createElement('div');
        wrapper.className = `air-message air-message--${role}${options.isOpener ? ' air-message--opener' : ''}`;

        const avatar = document.createElement('div');
        avatar.className = 'air-message__avatar';

        if (role === 'assistant') {
            avatar.innerHTML = this.persona?.avatar_url
                ? `<img src="${this.persona.avatar_url}" alt="${this.persona.name}" />`
                : `<div class="air-avatar-placeholder">${(this.persona?.name || 'AI')[0]}</div>`;
        } else {
            avatar.innerHTML = `<div class="air-avatar-placeholder air-avatar-placeholder--user">You</div>`;
        }

        const bubble = document.createElement('div');
        bubble.className = 'air-message__bubble';
        bubble.innerHTML = this._formatMessageContent(content);

        const time = document.createElement('div');
        time.className = 'air-message__time';
        time.textContent = new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});

        wrapper.append(avatar, bubble, time);
        this.chatMessages.appendChild(wrapper);

        // Smooth scroll to bottom.
        requestAnimationFrame(() => {
            this.chatMessages.scrollTo({top: this.chatMessages.scrollHeight, behavior: 'smooth'});
        });

        // Animate in.
        wrapper.style.opacity = '0';
        wrapper.style.transform = 'translateY(10px)';
        requestAnimationFrame(() => {
            wrapper.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            wrapper.style.opacity    = '1';
            wrapper.style.transform  = 'translateY(0)';
        });
    }

    _appendSystemMessage(type, text) {
        if (!this.chatMessages) return;

        const el = document.createElement('div');
        el.className = `air-system-message air-system-message--${type}`;
        el.textContent = text;
        this.chatMessages.appendChild(el);
        this.chatMessages.scrollTop = this.chatMessages.scrollHeight;

        // Auto-remove info messages.
        if (type === 'info') {
            setTimeout(() => el.remove(), 3000);
        }
    }

    _formatMessageContent(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\n/g, '<br>');
    }

    _showTyping() {
        if (this.typingIndicator) {
            this.typingIndicator.style.display = 'flex';
            this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
        }
    }

    _hideTyping() {
        if (this.typingIndicator) {
            this.typingIndicator.style.display = 'none';
        }
    }

    _updateSentiment(sentiment, score) {
        if (!this.sentimentEl) return;

        const colors = {positive: '#22c55e', neutral: '#94a3b8', negative: '#ef4444'};
        const icons  = {positive: '😊', neutral: '😐', negative: '😟'};

        this.sentimentEl.style.color = colors[sentiment] || colors.neutral;
        this.sentimentEl.textContent = icons[sentiment]  || icons.neutral;
        this.sentimentEl.title       = `Sentiment: ${sentiment} (${Math.round(score * 100)}%)`;

        // Update confidence meter.
        if (this.confidenceMeter) {
            const pct = Math.round(score * 100);
            this.confidenceMeter.style.width = pct + '%';
            this.confidenceMeter.style.background = colors[sentiment];
        }
    }

    _updateTurnCounter() {
        if (!this.turnCountEl) return;
        this.turnCountEl.textContent = this.turnCount;

        // Warn if approaching max turns.
        if (this.maxTurns && this.turnCount >= this.maxTurns - 2) {
            this.turnCountEl.classList.add('air-turn-count--warning');
        }
    }

    _setProcessing(state) {
        this.isProcessing = state;

        if (this.sendBtn) {
            this.sendBtn.disabled = state;
            this.sendBtn.querySelector('.air-btn-text')?.classList.toggle('d-none', state);
            this.sendBtn.querySelector('.air-btn-spinner')?.classList.toggle('d-none', !state);
        }
        if (this.messageInput) {
            this.messageInput.disabled = state;
        }
    }

    _setVoiceActive(active) {
        this.voiceBtn?.classList.toggle('air-voice-btn--active', active);
        this.voiceBtn?.setAttribute('aria-label', active ? 'Stop recording' : 'Start voice input');

        if (active) {
            this.voiceBtn.innerHTML = '<i class="fa fa-stop"></i>';
        } else {
            this.voiceBtn.innerHTML = '<i class="fa fa-microphone"></i>';
        }
    }

    // ----------------------------------------------------------------
    // TIMER
    // ----------------------------------------------------------------

    _startTimer() {
        if (!this.timerEl) return;

        this.timerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
            this.timerEl.textContent = this._formatTime(elapsed);

            // Time limit warning.
            if (this.maxDuration) {
                const remaining = this.maxDuration - elapsed;
                if (remaining <= 60 && remaining > 0) {
                    this.timerEl.classList.add('air-timer--warning');
                }
                if (remaining <= 0) {
                    clearInterval(this.timerInterval);
                    this._promptSessionEnd('time_up');
                }
            }
        }, 1000);
    }

    _formatTime(seconds) {
        const m = Math.floor(seconds / 60).toString().padStart(2, '0');
        const s = (seconds % 60).toString().padStart(2, '0');
        return `${m}:${s}`;
    }

    // ----------------------------------------------------------------
    // UTILITIES
    // ----------------------------------------------------------------

    async _callApi(method, args) {
        const [result] = await Ajax.call([{methodname: method, args}]);
        return result;
    }

    _blobToBase64(blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result.split(',')[1]);
            reader.onerror   = reject;
            reader.readAsDataURL(blob);
        });
    }

    _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    _confirm(title, message) {
        return new Promise(resolve => {
            Notification.confirm(title, message, 'Confirm', 'Cancel',
                () => resolve(true), () => resolve(false));
        });
    }
}

// ====================================================================
// RESULTS PAGE CONTROLLER - Charts and evaluation display
// ====================================================================

class ResultsController {

    constructor(config) {
        this.sessionId = config.sessionId;
        this.init();
    }

    async init() {
        try {
            const evaluation = await this._callApi('mod_airoleplay_get_evaluation', {
                session_id: this.sessionId,
            });

            if (!evaluation.evaluated) {
                // Poll until evaluation completes.
                this._pollForEvaluation();
                return;
            }

            this._renderResults(evaluation);
        } catch (err) {
            console.error('Results error:', err);
        }
    }

    async _pollForEvaluation() {
        const el = document.getElementById('air-evaluation-pending');
        if (el) el.style.display = 'block';

        let attempts = 0;
        const poll = setInterval(async () => {
            attempts++;
            if (attempts > 30) { // Max 5 minutes.
                clearInterval(poll);
                return;
            }

            try {
                const evaluation = await this._callApi('mod_airoleplay_get_evaluation', {
                    session_id: this.sessionId,
                });

                if (evaluation.evaluated) {
                    clearInterval(poll);
                    if (el) el.style.display = 'none';
                    this._renderResults(evaluation);
                }
            } catch (e) {
                // Continue polling.
            }
        }, 10000); // Poll every 10s.
    }

    _renderResults(evaluation) {
        // Score circle.
        this._renderScoreCircle(evaluation.score_overall, evaluation.passed);

        // Competency radar chart.
        if (evaluation.competencies?.length) {
            this._renderCompetencyRadar(evaluation.competencies);
        }

        // Competency bar chart.
        if (evaluation.competencies?.length) {
            this._renderCompetencyBars(evaluation.competencies);
        }

        // Summary text.
        const summaryEl = document.getElementById('air-summary-text');
        if (summaryEl) summaryEl.innerHTML = evaluation.ai_summary?.replace(/\n/g, '<br>') || '';

        // Strengths.
        this._renderList('air-strengths-list', evaluation.strengths || [], 'success');

        // Improvements.
        this._renderImprovements(evaluation.improvements || []);

        // Ideal responses.
        this._renderIdealResponses(evaluation.ideal_responses || []);

        // Reveal results section.
        document.getElementById('air-results-container')?.classList.remove('d-none');
    }

    _renderScoreCircle(score, passed) {
        const el = document.getElementById('air-score-circle');
        if (!el) return;

        const radius = 54;
        const circ   = 2 * Math.PI * radius;
        const pct    = Math.max(0, Math.min(100, score));
        const offset = circ - (pct / 100) * circ;
        const color  = passed ? '#22c55e' : score >= 50 ? '#f59e0b' : '#ef4444';

        el.innerHTML = `
          <svg width="140" height="140" viewBox="0 0 140 140">
            <circle cx="70" cy="70" r="${radius}" fill="none" stroke="#e2e8f0" stroke-width="10"/>
            <circle cx="70" cy="70" r="${radius}" fill="none" stroke="${color}" stroke-width="10"
                    stroke-dasharray="${circ}" stroke-dashoffset="${offset}"
                    stroke-linecap="round" transform="rotate(-90 70 70)"
                    style="transition: stroke-dashoffset 1.5s ease"/>
            <text x="70" y="65" text-anchor="middle" fill="${color}" font-size="28" font-weight="700">${Math.round(pct)}</text>
            <text x="70" y="85" text-anchor="middle" fill="#94a3b8" font-size="12">${passed ? '✓ PASSED' : '✗ FAILED'}</text>
          </svg>`;
    }

    _renderCompetencyRadar(competencies) {
        const canvas = document.getElementById('air-radar-chart');
        if (!canvas || !window.Chart) return;

        const labels = competencies.map(c => c.label);
        const scores = competencies.map(c => c.score);

        new window.Chart(canvas, {
            type: 'radar',
            data: {
                labels,
                datasets: [{
                    label: 'Your Performance',
                    data: scores,
                    backgroundColor: 'rgba(99, 102, 241, 0.15)',
                    borderColor:     'rgba(99, 102, 241, 0.8)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                }],
            },
            options: {
                scales: {r: {min: 0, max: 100, ticks: {stepSize: 25}}},
                plugins: {legend: {display: false}},
                animation: {duration: 1000},
            },
        });
    }

    _renderCompetencyBars(competencies) {
        const container = document.getElementById('air-competency-bars');
        if (!container) return;

        container.innerHTML = competencies.map(comp => {
            const pct   = Math.round(comp.score);
            const color = pct >= 80 ? '#22c55e' : pct >= 60 ? '#f59e0b' : '#ef4444';
            return `
              <div class="air-comp-bar mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="air-comp-label">${this._esc(comp.label)}</span>
                  <span class="air-comp-score" style="color:${color}">${pct}/100</span>
                </div>
                <div class="air-progress-track">
                  <div class="air-progress-fill" style="width:0%; background:${color}; transition: width 1s ease"
                       data-target="${pct}%"></div>
                </div>
                ${comp.ai_rationale ? `<p class="air-comp-rationale">${this._esc(comp.ai_rationale)}</p>` : ''}
              </div>`;
        }).join('');

        // Animate bars.
        requestAnimationFrame(() => {
            container.querySelectorAll('.air-progress-fill').forEach(bar => {
                bar.style.width = bar.dataset.target;
            });
        });
    }

    _renderList(containerId, items, type) {
        const el = document.getElementById(containerId);
        if (!el || !items.length) return;

        const icon = type === 'success' ? '✓' : '→';
        el.innerHTML = items.map(item =>
            `<li class="air-list-item air-list-item--${type}">
               <span class="air-list-icon">${icon}</span>
               <span>${this._esc(String(item))}</span>
             </li>`
        ).join('');
    }

    _renderImprovements(improvements) {
        const el = document.getElementById('air-improvements-list');
        if (!el || !improvements.length) return;

        el.innerHTML = improvements.map(imp => `
          <div class="air-improvement-card">
            <div class="air-improvement-area">⚡ ${this._esc(imp.area || '')}</div>
            ${imp.specific_gap ? `<p class="air-improvement-gap">${this._esc(imp.specific_gap)}</p>` : ''}
            ${imp.suggestion   ? `<p class="air-improvement-tip">💡 ${this._esc(imp.suggestion)}</p>` : ''}
          </div>`
        ).join('');
    }

    _renderIdealResponses(ideal) {
        const el = document.getElementById('air-ideal-responses');
        if (!el || !ideal.length) return;

        el.innerHTML = ideal.slice(0, 3).map(ir => `
          <div class="air-ideal-card">
            <div class="air-ideal-context">📍 ${this._esc(ir.context || '')}</div>
            <div class="air-ideal-comparison">
              <div class="air-ideal-said">
                <label>You said:</label>
                <p>${this._esc(ir.learner_said || '')}</p>
              </div>
              <div class="air-ideal-better">
                <label>Better response:</label>
                <p class="text-success">${this._esc(ir.better_response || '')}</p>
              </div>
            </div>
          </div>`
        ).join('');
    }

    _esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    async _callApi(method, args) {
        const [result] = await Ajax.call([{methodname: method, args}]);
        return result;
    }
}

// ====================================================================
// DASHBOARD CONTROLLER
// ====================================================================

class DashboardController {

    constructor(config) {
        this.role   = config.role || 'learner';
        this.period = config.period || 'month';
        this.cmid   = config.cmid || 0;
        this.init();
    }

    async init() {
        try {
            const data = await Ajax.call([{
                methodname: 'mod_airoleplay_get_dashboard_data',
                args: {
                    role:   this.role,
                    period: this.period,
                    cmid:   this.cmid,
                },
            }])[0];

            this._renderSummaryCards(data.summary_cards || []);
            this._renderScoreTrend(data.score_trend || []);
            this._renderRadar(data.competency_radar || []);
            this._renderRecentSessions(data.recent_sessions || []);
            this._renderLeaderboard(data.department_leaderboard || []);

            if (data.ai_cost_summary) {
                this._renderCostSummary(data.ai_cost_summary);
            }
        } catch (err) {
            console.error('Dashboard load error:', err);
        }
    }

    _renderSummaryCards(cards) {
        const container = document.getElementById('air-summary-cards');
        if (!container) return;

        container.innerHTML = cards.map(card => `
          <div class="air-stat-card air-stat-card--${card.color || 'primary'}">
            <div class="air-stat-icon">
              <i class="fa fa-${card.icon || 'chart-bar'}"></i>
            </div>
            <div class="air-stat-content">
              <div class="air-stat-value">${card.value}</div>
              <div class="air-stat-label">${card.label}</div>
              ${card.delta ? `<div class="air-stat-delta air-stat-delta--${card.trend}">${card.delta}</div>` : ''}
            </div>
          </div>`
        ).join('');
    }

    _renderScoreTrend(trend) {
        const canvas = document.getElementById('air-trend-chart');
        if (!canvas || !window.Chart || !trend.length) return;

        new window.Chart(canvas, {
            type: 'line',
            data: {
                labels:   trend.map(t => t.date),
                datasets: [{
                    label:           'Avg Score',
                    data:            trend.map(t => t.score),
                    borderColor:     'rgba(99, 102, 241, 0.9)',
                    backgroundColor: 'rgba(99, 102, 241, 0.08)',
                    tension:         0.4,
                    fill:            true,
                    pointRadius:     4,
                }],
            },
            options: {
                responsive: true,
                scales:  {y: {min: 0, max: 100}},
                plugins: {legend: {display: false}},
                animation: {duration: 800},
            },
        });
    }

    _renderRadar(radar) {
        const canvas = document.getElementById('air-dash-radar');
        if (!canvas || !window.Chart || !radar.length) return;

        new window.Chart(canvas, {
            type: 'radar',
            data: {
                labels:   radar.map(r => r.label),
                datasets: [{
                    label:           'Competency Scores',
                    data:            radar.map(r => r.score),
                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                    borderColor:     'rgba(16, 185, 129, 0.8)',
                    borderWidth: 2,
                }],
            },
            options: {
                scales: {r: {min: 0, max: 100}},
                plugins: {legend: {display: false}},
            },
        });
    }

    _renderRecentSessions(sessions) {
        const tbody = document.getElementById('air-recent-tbody');
        if (!tbody) return;

        tbody.innerHTML = sessions.map(s => {
            const date   = new Date(s.timecompleted * 1000).toLocaleDateString();
            const badge  = s.passed
                ? '<span class="badge bg-success">Passed</span>'
                : '<span class="badge bg-danger">Failed</span>';
            const score_color = s.score >= 80 ? 'text-success' : s.score >= 60 ? 'text-warning' : 'text-danger';
            return `<tr>
              <td>${s.scenario}</td>
              <td class="${score_color} fw-bold">${s.score}/100</td>
              <td>${badge}</td>
              <td>${date}</td>
              <td><a href="${M.cfg.wwwroot}/mod/airoleplay/results.php?session=${s.session_id}" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>`;
        }).join('') || '<tr><td colspan="5" class="text-center text-muted">No sessions yet.</td></tr>';
    }

    _renderLeaderboard(board) {
        const container = document.getElementById('air-leaderboard');
        if (!container || !board.length) return;

        container.innerHTML = board.slice(0, 10).map((entry, i) => `
          <div class="air-leader-row ${i < 3 ? 'air-leader-row--top' : ''}">
            <span class="air-leader-rank">${i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : entry.rank}</span>
            <span class="air-leader-name">${entry.name}</span>
            <span class="air-leader-score">${entry.score}/100</span>
            <span class="air-leader-count">${entry.count} sessions</span>
          </div>`
        ).join('');
    }

    _renderCostSummary(cost) {
        const el = document.getElementById('air-cost-summary');
        if (!el) return;

        el.innerHTML = `
          <div class="air-cost-total">Total: $${cost.total_cost.toFixed(4)} (${cost.total_tokens.toLocaleString()} tokens)</div>
          ${cost.by_provider.map(p => `
            <div class="air-cost-provider">
              <span>${p.provider}</span>
              <span>${p.tokens.toLocaleString()} tokens / $${p.cost.toFixed(4)}</span>
            </div>`).join('')}`;
    }
}

// ====================================================================
// MODULE EXPORTS
// ====================================================================

export const init = (config) => {
    switch (config.page) {
        case 'conversation':
            return new ConversationController(config);
        case 'results':
            return new ResultsController(config);
        case 'dashboard':
            return new DashboardController(config);
        default:
            console.warn('Unknown page type:', config.page);
    }
};

export default {init};
