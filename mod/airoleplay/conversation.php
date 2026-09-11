<?php
// This file is part of mod_airoleplay — voice-first conversation UI.

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$sessionid = optional_param('session', 0, PARAM_INT);

$cm = get_coursemodule_from_id('airoleplay', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('airoleplay', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
//require_capability('mod/airoleplay:participate', $context);
require_capability('mod/airoleplay:attempt', $context);
$PAGE->set_url('/mod/airoleplay/conversation.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');
$PAGE->add_body_class('airoleplay-conversation-page');

if (!$sessionid) {
    // Clean up any stale active sessions and try to resume a recent one
    // before creating a brand-new attempt row.
    airoleplay_expire_stale_sessions($instance, $USER->id);
    $sessionid = airoleplay_find_resumable_session((int) $instance->id, (int) $USER->id);
}

if (!$sessionid) {
    $now = time();
    // Only completed/abandoned attempts count toward the attempt number.
    $attempt = (int) $DB->count_records_select(
        'airoleplay_session',
        "airoleplay = :rid AND userid = :uid AND status <> 'active'",
        ['rid' => $instance->id, 'uid' => $USER->id]
    ) + 1;

    $session = (object) [
        'airoleplay'    => $instance->id,
        'userid'        => $USER->id,
        'attempt'       => $attempt,
        'session_token' => bin2hex(random_bytes(32)),
        'status'        => 'active',
        'scenario_type' => $instance->scenario_type ?? 'chat',
        'language'      => $instance->language ?? 'en',
        'turn_count'    => 0,
        'duration_secs' => 0,
        'evaluated_by_ai' => 0,
        'timestarted'   => $now,
        'timemodified'  => $now,
    ];
    // Optional cmid column for future-proofing (only set if present).
    if ($DB->get_manager()->field_exists('airoleplay_session', 'cmid')) {
        $session->cmid = $cm->id;
    }
    $sessionid = $DB->insert_record('airoleplay_session', $session);
}

$session = $DB->get_record('airoleplay_session', ['id' => $sessionid], '*', MUST_EXIST);
if ((int) $session->userid !== (int) $USER->id) {
    throw new moodle_exception('nopermissions', 'error');
}

// Load persona from airoleplay_persona table if linked.
$persona = null;
if (!empty($instance->persona_id)) {
    $persona = $DB->get_record('airoleplay_persona', ['id' => $instance->persona_id], '*', IGNORE_MISSING);
}

// Persona display name, falling back through scenario name → industry → generic.
$personaname = $persona && !empty($persona->name)
    ? $persona->name
    : (!empty($instance->name) ? format_string($instance->name) : 'AI Roleplay Partner');

// Build a meaningful subtitle: "industry · difficulty · tone".
$rolebits = [];
if (!empty($instance->industry))   { $rolebits[] = ucfirst($instance->industry); }
if (!empty($instance->difficulty)) { $rolebits[] = ucfirst($instance->difficulty); }
if ($persona && !empty($persona->tone) && $persona->tone !== 'neutral') {
    $rolebits[] = ucfirst($persona->tone);
}
$personarole = $rolebits ? implode(' · ', $rolebits) : 'Roleplay partner';

// Schema field names are snake_case. Convert max_duration (seconds) → minutes for the timer display.
// Fall back to 15 minutes if not set, not the previously-hardcoded 45.
$durationsecs = !empty($instance->max_duration) ? (int) $instance->max_duration : 15 * 60;
$durationmins = max(1, (int) ceil($durationsecs / 60));

// Voice/text feature toggles use the schema fields enable_voice / enable_transcript.
$voicemode = !isset($instance->enable_voice) || (int) $instance->enable_voice === 1;
$textmode  = true; // Always allow text input as a fallback — voice can fail for many reasons.

$language = !empty($instance->language) ? $instance->language : 'en';
$maxturns = !empty($instance->max_turns) ? (int) $instance->max_turns : 0;

$ajaxurl = $CFG->wwwroot . '/lib/ajax/service.php?sesskey=' . sesskey();
$endurl  = new moodle_url('/mod/airoleplay/view.php', ['id' => $cm->id]);

echo $OUTPUT->header();
?>

<style>
.airoleplay-app {
    --arp-bg: #0f1220;
    --arp-stage: linear-gradient(160deg, #1a1f3a 0%, #2d1b4e 55%, #1a2744 100%);
    --arp-accent: #7c5cff;
    --arp-accent2: #5eead4;
    --arp-text: #f1f5f9;
    --arp-muted: #94a3b8;
    --arp-danger: #f87171;
    font-family: "Segoe UI", system-ui, sans-serif;
    color: var(--arp-text);
    max-width: 1100px;
    margin: 0 auto;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,.35);
    background: var(--arp-bg);
}
.airoleplay-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    background: rgba(255,255,255,.04);
    border-bottom: 1px solid rgba(255,255,255,.08);
}
.airoleplay-topbar .meta { font-size: 12px; color: var(--arp-muted); }
.airoleplay-topbar .title { font-weight: 700; font-size: 16px; }
.airoleplay-topbar .actions { display: flex; gap: 12px; align-items: center; }
.airoleplay-topbar .timer { font-variant-numeric: tabular-nums; color: var(--arp-accent2); }
.airoleplay-topbar .btn-end {
    background: #dc2626;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 14px;
    cursor: pointer;
    font-weight: 600;
}
.airoleplay-stage-wrap {
    background: var(--arp-stage);
    padding: 28px 20px 20px;
    min-height: 340px;
    position: relative;
}
.airoleplay-agent {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}
.airoleplay-avatar-ring {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    padding: 5px;
    background: conic-gradient(from 0deg, var(--arp-accent), var(--arp-accent2), var(--arp-accent));
    transition: transform .3s, box-shadow .3s;
}
.airoleplay-avatar-ring.speaking {
    animation: arp-pulse 1.2s ease-in-out infinite;
    box-shadow: 0 0 40px rgba(124,92,255,.55);
}
@keyframes arp-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.04); }
}
.airoleplay-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: #1e293b center/cover no-repeat;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 4px solid #0f1220;
}
.airoleplay-avatar svg { width: 100%; height: 100%; }
.airoleplay-agent h2 { margin: 0; font-size: 22px; }
.airoleplay-agent .role { color: var(--arp-muted); font-size: 14px; }
.airoleplay-bubble {
    margin-top: 16px;
    max-width: 620px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 16px;
    padding: 14px 18px;
    font-size: 15px;
    line-height: 1.5;
    min-height: 48px;
}
.airoleplay-wave {
    display: flex;
    gap: 4px;
    height: 28px;
    align-items: flex-end;
    justify-content: center;
    margin-top: 8px;
    opacity: 0;
    transition: opacity .2s;
}
.airoleplay-wave.active { opacity: 1; }
.airoleplay-wave span {
    width: 4px;
    background: var(--arp-accent2);
    border-radius: 2px;
    animation: arp-bar .6s ease-in-out infinite alternate;
}
.airoleplay-wave span:nth-child(2) { animation-delay: .1s; }
.airoleplay-wave span:nth-child(3) { animation-delay: .2s; }
.airoleplay-wave span:nth-child(4) { animation-delay: .3s; }
.airoleplay-wave span:nth-child(5) { animation-delay: .4s; }
@keyframes arp-bar {
    from { height: 6px; }
    to { height: 22px; }
}
.airoleplay-chat {
    padding: 16px 20px;
    max-height: 220px;
    overflow-y: auto;
    background: #0c0f1a;
}
.airoleplay-msg {
    margin-bottom: 10px;
    display: flex;
    gap: 8px;
}
.airoleplay-msg.user { justify-content: flex-end; }
.airoleplay-msg .bubble {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 14px;
    line-height: 1.45;
}
.airoleplay-msg.assistant .bubble {
    background: rgba(124,92,255,.25);
    border: 1px solid rgba(124,92,255,.35);
}
.airoleplay-msg.user .bubble {
    background: rgba(94,234,212,.15);
    border: 1px solid rgba(94,234,212,.3);
}
.airoleplay-alert {
    margin: 10px 20px;
    padding: 10px 14px;
    border-radius: 10px;
    background: rgba(248,113,113,.15);
    border: 1px solid rgba(248,113,113,.4);
    color: #fecaca;
    font-size: 13px;
    display: none;
}
.airoleplay-alert.show { display: block; }
.airoleplay-controls {
    padding: 16px 20px 20px;
    background: #0c0f1a;
    border-top: 1px solid rgba(255,255,255,.06);
}
.airoleplay-hint {
    text-align: center;
    color: var(--arp-muted);
    font-size: 13px;
    margin-bottom: 12px;
}
.airoleplay-input-row {
    display: flex;
    gap: 10px;
    align-items: center;
}
.airoleplay-input-row input[type="text"] {
    flex: 1;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.06);
    color: var(--arp-text);
    padding: 12px 14px;
    font-size: 14px;
}
.airoleplay-mic {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, var(--arp-accent), #5b21b6);
    color: #fff;
    font-size: 26px;
    cursor: pointer;
    flex-shrink: 0;
    box-shadow: 0 8px 24px rgba(124,92,255,.45);
}
.airoleplay-mic.recording { background: linear-gradient(135deg, #dc2626, #991b1b); }
.airoleplay-send {
    border: none;
    background: rgba(255,255,255,.1);
    color: #fff;
    border-radius: 12px;
    padding: 12px 16px;
    cursor: pointer;
}
.airoleplay-status {
    text-align: center;
    font-size: 12px;
    color: var(--arp-muted);
    margin-top: 8px;
}
</style>

<div class="airoleplay-app" id="airoleplay-app"
     data-sessionid="<?php echo (int) $sessionid; ?>"
     data-ajaxurl="<?php echo s($ajaxurl); ?>"
     data-voice="<?php echo $voicemode ? '1' : '0'; ?>"
     data-text="<?php echo $textmode ? '1' : '0'; ?>"
     data-duration-secs="<?php echo (int) $durationsecs; ?>"
     data-language="<?php echo s($language); ?>"
     data-maxturns="<?php echo (int) $maxturns; ?>">

    <div class="airoleplay-topbar">
        <div>
            <div class="title" id="arp-persona-name"><?php echo s($personaname); ?></div>
            <div class="meta" id="arp-persona-role"><?php echo s($personarole); ?></div>
        </div>
        <div class="actions">
            <span class="meta">Turn <span id="arp-turn">0</span><?php if ($maxturns) { echo ' / ' . $maxturns; } ?></span>
            <span class="timer" id="arp-timer"><?php echo sprintf('%02d:%02d', $durationmins, 0); ?></span>
            <button type="button" class="btn-end" id="arp-end">End</button>
        </div>
    </div>

    <div class="airoleplay-stage-wrap">
        <div class="airoleplay-agent">
            <div class="airoleplay-avatar-ring" id="arp-avatar-ring">
                <div class="airoleplay-avatar" id="arp-avatar" aria-hidden="true">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="AI character">
                        <defs>
                            <linearGradient id="skin" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#e8b896"/>
                                <stop offset="100%" stop-color="#c68642"/>
                            </linearGradient>
                        </defs>
                        <rect width="200" height="200" fill="#1e293b"/>
                        <ellipse cx="100" cy="120" rx="72" ry="80" fill="url(#skin)"/>
                        <ellipse cx="100" cy="78" rx="58" ry="62" fill="url(#skin)"/>
                        <path d="M55 85 Q100 110 145 85" stroke="#5c3d2e" stroke-width="3" fill="none"/>
                        <circle cx="78" cy="72" r="6" fill="#2d1b0e"/>
                        <circle cx="122" cy="72" r="6" fill="#2d1b0e"/>
                        <rect x="62" y="155" width="76" height="50" rx="12" fill="#334155"/>
                        <rect x="48" y="168" width="28" height="60" rx="10" fill="#475569"/>
                        <rect x="124" y="168" width="28" height="60" rx="10" fill="#475569"/>
                    </svg>
                </div>
            </div>
            <h2 id="arp-agent-name"><?php echo s($personaname); ?></h2>
            <div class="role" id="arp-agent-role"><?php echo s($personarole); ?></div>
            <div class="airoleplay-bubble" id="arp-speech">Connecting to your roleplay partner…</div>
            <div class="airoleplay-wave" id="arp-wave">
                <span></span><span></span><span></span><span></span><span></span>
            </div>
        </div>
    </div>

    <div id="arp-error" class="airoleplay-alert" role="alert"></div>

    <div class="airoleplay-chat" id="arp-chat" aria-live="polite"></div>

    <div class="airoleplay-controls">
        <div class="airoleplay-hint">
            <strong><?php echo format_string($instance->name); ?></strong><br>
            <?php if ($voicemode) { ?>
                <span id="arp-mode-hint">Hands-free voice — the mic will listen automatically after your partner speaks. Click 🎙 to pause/resume.</span>
            <?php } else { ?>
                <span>Type your reply and press Enter.</span>
            <?php } ?>
        </div>
        <div class="airoleplay-input-row">
            <?php if ($textmode) { ?>
            <input type="text" id="arp-input" placeholder="Type a message (optional)…" autocomplete="off" />
            <button type="button" class="airoleplay-send" id="arp-send" title="Send">➤</button>
            <?php } ?>
            <?php if ($voicemode) { ?>
            <button type="button" class="airoleplay-mic" id="arp-mic" title="Hold to speak">🎙</button>
            <?php } ?>
        </div>
        <div class="airoleplay-status" id="arp-status">Starting session…</div>
    </div>
</div>

<script>
(function() {
    'use strict';

    const app = document.getElementById('airoleplay-app');
    const sessionId    = parseInt(app.dataset.sessionid, 10);
    const ajaxUrl      = app.dataset.ajaxurl;
    const voiceEnabled = app.dataset.voice === '1';
    const textEnabled  = app.dataset.text === '1';
    const durationSecs = parseInt(app.dataset.durationSecs, 10) || (15 * 60);
    const langCode     = app.dataset.language || 'en';
    const maxTurns     = parseInt(app.dataset.maxturns, 10) || 0;

    // Map Moodle language codes to BCP-47 locales for SpeechRecognition.
    const langMap = {
        en: 'en-IN', hi: 'hi-IN', ta: 'ta-IN', te: 'te-IN', kn: 'kn-IN',
        ml: 'ml-IN', mr: 'mr-IN', bn: 'bn-IN', gu: 'gu-IN', pa: 'pa-IN',
        ar: 'ar-SA', fr: 'fr-FR', de: 'de-DE', es: 'es-ES', ja: 'ja-JP',
        zh: 'zh-CN', pt: 'pt-BR', ru: 'ru-RU',
    };
    const speechLang = langMap[langCode] || 'en-IN';

    const el = {
        speech:      document.getElementById('arp-speech'),
        chat:        document.getElementById('arp-chat'),
        error:       document.getElementById('arp-error'),
        status:      document.getElementById('arp-status'),
        turn:        document.getElementById('arp-turn'),
        timer:       document.getElementById('arp-timer'),
        ring:        document.getElementById('arp-avatar-ring'),
        wave:        document.getElementById('arp-wave'),
        mic:         document.getElementById('arp-mic'),
        input:       document.getElementById('arp-input'),
        send:        document.getElementById('arp-send'),
        end:         document.getElementById('arp-end'),
        personaName: document.getElementById('arp-persona-name'),
        agentName:   document.getElementById('arp-agent-name'),
        personaRole: document.getElementById('arp-persona-role'),
        agentRole:   document.getElementById('arp-agent-role'),
    };

    // State.
    let turn          = 0;
    let secondsLeft   = durationSecs;
    let recognition   = null;
    let listening     = false;          // recognition.start() has been called and not yet ended
    let userPaused    = false;          // user clicked mic to pause hands-free mode
    let busy          = false;          // a server call is in flight
    let speakingNow   = false;          // TTS is playing
    let ending        = false;          // End session in progress — block all other actions
    let pendingText   = '';             // current interim transcript
    let silenceTimer  = null;           // timer that finalizes after silence
    const SILENCE_MS  = 1800;           // give user ~1.8s of silence before finalizing — generous

    function setStatus(msg) {
        el.status.textContent = msg;
    }

    function showError(msg) {
        el.error.textContent = msg;
        el.error.classList.add('show');
        // Auto-hide non-critical errors after a few seconds so they don't block the UI.
        clearTimeout(showError._t);
        showError._t = setTimeout(clearError, 6000);
    }

    function clearError() {
        el.error.classList.remove('show');
    }

    async function callWs(method, args) {
        const body = JSON.stringify([{ index: 0, methodname: method, args: args }]);
        const res = await fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: body
        });
        const data = await res.json();
        if (!Array.isArray(data) || !data[0]) {
            throw new Error('Empty response from server');
        }
        if (data[0].error) {
            const err = data[0].exception || data[0].error;
            throw new Error(typeof err === 'string' ? err : (err.message || JSON.stringify(err)));
        }
        return data[0].data;
    }

    function addMessage(role, text) {
        const row = document.createElement('div');
        row.className = 'airoleplay-msg ' + role;
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.textContent = text;
        row.appendChild(bubble);
        el.chat.appendChild(row);
        el.chat.scrollTop = el.chat.scrollHeight;
    }

    function setSpeaking(on) {
        speakingNow = on;
        if (el.ring) { el.ring.classList.toggle('speaking', on); }
        if (el.wave) { el.wave.classList.toggle('active', on); }
        // When the partner stops speaking, immediately reflect the next state in
        // the status line so it never gets stuck on "Partner is speaking…".
        if (!on && !busy && !ending) {
            if (userPaused) {
                setStatus('Paused — click 🎙 to resume.');
            } else if (voiceEnabled) {
                setStatus('Your turn — listening…');
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // CONVERSATION RECORDING
    //
    // We capture a single audio stream for the whole session by mixing the
    // learner's microphone with the AI's TTS playback, using Web Audio's
    // MediaStreamDestination. The resulting blob is uploaded on session end
    // so the recording is playable from the results & admin report pages.
    //
    // Falls back silently if MediaRecorder / getUserMedia is unsupported —
    // the rest of the conversation still works.
    // ─────────────────────────────────────────────────────────────────────
    let audioCtx           = null;
    let mixDestination     = null;   // MediaStreamAudioDestinationNode
    let micStream          = null;
    let mediaRecorder      = null;
    let recordedChunks     = [];
    let recordingMimeType  = 'audio/webm';

    async function initRecording() {
        if (!voiceEnabled) { return; }
        if (!window.MediaRecorder || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            // Browser doesn't support recording — that's OK, conversation still works.
            return;
        }
        try {
            micStream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (e) {
            // Permission denied or no mic — skip recording, don't block conversation.
            return;
        }

        const AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) { return; }
        audioCtx       = new AC();
        mixDestination = audioCtx.createMediaStreamDestination();

        // Pipe the mic into the mix.
        try {
            const micSource = audioCtx.createMediaStreamSource(micStream);
            micSource.connect(mixDestination);
        } catch (_) { /* ignore */ }

        // Choose the most widely supported mimetype. WebM/Opus is universal in
        // Chrome/Edge/Firefox; Safari uses MP4. We probe and pick what works.
        const candidates = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg'];
        for (let i = 0; i < candidates.length; i++) {
            if (MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(candidates[i])) {
                recordingMimeType = candidates[i];
                break;
            }
        }

        try {
            mediaRecorder = new MediaRecorder(mixDestination.stream, { mimeType: recordingMimeType });
            mediaRecorder.ondataavailable = function(ev) {
                if (ev.data && ev.data.size > 0) { recordedChunks.push(ev.data); }
            };
            // 5-second timeslice gives us periodic flush in case the page is
            // closed mid-session, without performance impact.
            mediaRecorder.start(5000);
        } catch (e) {
            // Codec unsupported — proceed silently without recording.
            mediaRecorder = null;
        }
    }

    /**
     * Route an HTMLAudioElement (AI TTS playback) into the mix so it's captured
     * in the recording alongside the learner's mic.
     */
    function pipeAudioToRecorder(audioEl) {
        if (!audioCtx || !mixDestination) { return; }
        // Browsers start the AudioContext suspended until a user gesture; if it's
        // still suspended the element can play silently and 'ended' may not fire.
        try {
            if (audioCtx.state === 'suspended' && audioCtx.resume) {
                audioCtx.resume();
            }
        } catch (_) {}
        try {
            const src = audioCtx.createMediaElementSource(audioEl);
            // Send to speakers (so the learner hears it) AND the recorder mix.
            src.connect(audioCtx.destination);
            src.connect(mixDestination);
        } catch (_) {
            // createMediaElementSource throws if called twice on the same element
            // — safe to ignore, the audio element still plays through normally.
        }
    }

    /**
     * Stop recording and return a Blob (or null if nothing was captured).
     */
    function stopRecording() {
        return new Promise(function(resolve) {
            if (!mediaRecorder || mediaRecorder.state === 'inactive') {
                resolve(null);
                return;
            }
            mediaRecorder.onstop = function() {
                try {
                    if (micStream) {
                        micStream.getTracks().forEach(function(t) { try { t.stop(); } catch (_) {} });
                    }
                } catch (_) {}
                if (!recordedChunks.length) { resolve(null); return; }
                resolve(new Blob(recordedChunks, { type: recordingMimeType }));
            };
            try { mediaRecorder.stop(); } catch (_) { resolve(null); }
        });
    }

    function blobToBase64(blob) {
        return new Promise(function(resolve, reject) {
            const reader = new FileReader();
            reader.onload  = function() {
                const result = reader.result || '';
                // Strip the "data:<mime>;base64," prefix the server doesn't need.
                const comma = result.indexOf(',');
                resolve(comma >= 0 ? result.substring(comma + 1) : result);
            };
            reader.onerror = function() { reject(reader.error); };
            reader.readAsDataURL(blob);
        });
    }

    async function uploadRecordingIfAny() {
        if (!mediaRecorder) { return; }
        try {
            const blob = await stopRecording();
            if (!blob || blob.size < 1024) { return; }
            const b64 = await blobToBase64(blob);
            await callWs('mod_airoleplay_upload_session_audio', {
                sessionid: sessionId,
                audio_b64: b64,
                mimetype:  recordingMimeType,
            });
        } catch (e) {
            // Best-effort: don't block end-of-session on upload failure.
            console.warn('mod_airoleplay: recording upload failed', e);
        }
    }

    // ----- Text-to-speech -------------------------------------------------
    //
    // Strategy: prefer server-rendered OpenAI TTS (neural voice, persona-aware
    // tone) when an audio_url is provided by send_message / get_opening_message.
    // Fall back to the browser's Web Speech API only when the server didn't
    // produce audio (e.g. no API key configured). The result is a consistent,
    // human-sounding voice across all browsers and devices.
    //
    function speak(text, audioUrl) {
        if (!voiceEnabled) { return Promise.resolve(); }

        // Pause recognition while the AI talks so the mic doesn't capture itself.
        stopListening();

        if (audioUrl) {
            return new Promise(function(resolve) {
                var settled = false;
                var safety  = null;

                // Guarantee we only ever finish once, always clearing the
                // speaking state and the safety timer. This is the key fix:
                // if the audio's 'ended' event never fires (which happens on
                // some browsers when the element is piped through Web Audio),
                // the safety timer below still releases the turn so the learner
                // can speak again without clicking the mic.
                function finish() {
                    if (settled) { return; }
                    settled = true;
                    if (safety) { clearTimeout(safety); }
                    setSpeaking(false);
                    resolve();
                }

                try {
                    const audio = new Audio();
                    audio.preload = 'auto';
                    // Mix the AI voice into the conversation recording.
                    pipeAudioToRecorder(audio);
                    audio.src = audioUrl;

                    audio.onplay = function() {
                        setSpeaking(true);
                        setStatus('Partner is speaking…');
                        // Safety net: never stay "speaking" longer than the
                        // audio's duration + 2s. If metadata is unavailable,
                        // fall back to a generous 60s ceiling.
                        var dur = (isFinite(audio.duration) && audio.duration > 0)
                                ? (audio.duration + 2) : 60;
                        if (safety) { clearTimeout(safety); }
                        safety = setTimeout(finish, dur * 1000);
                    };
                    audio.onloadedmetadata = function() {
                        // Re-arm the safety timer once we know the real duration.
                        if (settled) { return; }
                        if (isFinite(audio.duration) && audio.duration > 0) {
                            if (safety) { clearTimeout(safety); }
                            safety = setTimeout(finish, (audio.duration + 2) * 1000);
                        }
                    };
                    audio.onended = finish;
                    audio.onerror = function() {
                        if (settled) { return; }
                        settled = true;
                        if (safety) { clearTimeout(safety); }
                        setSpeaking(false);
                        // Fall back to Web Speech so the turn still gets spoken.
                        speakWithWebSpeech(text).then(resolve);
                    };

                    const p = audio.play();
                    if (p && typeof p.catch === 'function') {
                        p.catch(function() {
                            if (settled) { return; }
                            settled = true;
                            if (safety) { clearTimeout(safety); }
                            setSpeaking(false);
                            speakWithWebSpeech(text).then(resolve);
                        });
                    }
                    // Absolute fallback: if onplay never fires within 8s, give up
                    // on the audio element and use Web Speech.
                    setTimeout(function() {
                        if (!settled && !speakingNow) {
                            settled = true;
                            setSpeaking(false);
                            speakWithWebSpeech(text).then(resolve);
                        }
                    }, 8000);
                } catch (_) {
                    setSpeaking(false);
                    speakWithWebSpeech(text).then(resolve);
                }
            });
        }

        return speakWithWebSpeech(text);
    }

    function speakWithWebSpeech(text) {
        if (!window.speechSynthesis) { setSpeaking(false); return Promise.resolve(); }
        return new Promise(function(resolve) {
            var settled = false;
            function finish() {
                if (settled) { return; }
                settled = true;
                setSpeaking(false);
                resolve();
            }
            try { window.speechSynthesis.cancel(); } catch (_) {}
            const u = new SpeechSynthesisUtterance(text);
            u.lang = speechLang;
            u.rate  = 0.95;
            u.pitch = 1.0;
            u.onstart = function() { setSpeaking(true); setStatus('Partner is speaking…'); };
            u.onend   = finish;
            u.onerror = finish;
            window.speechSynthesis.speak(u);
            // Web Speech can silently fail to fire onend (a known browser bug),
            // so add a duration-based safety net (~80ms per word, min 4s).
            var words = (text || '').split(/\s+/).length;
            var est   = Math.max(4000, words * 380);
            setTimeout(finish, est);
        });
    }

    // ----- Server calls ---------------------------------------------------
    async function loadOpening() {
        setStatus('Loading opening message…');
        const data = await callWs('mod_airoleplay_get_opening_message', { sessionid: sessionId });
        if (data.persona) {
            el.personaName.textContent = data.persona;
            el.agentName.textContent   = data.persona;
        }
        if (data.persona_role) {
            el.personaRole.textContent = data.persona_role;
            el.agentRole.textContent   = data.persona_role;
        }
        el.speech.textContent = data.message;
        addMessage('assistant', data.message);
        await speak(data.message, data.audio_url || '');
        // Begin hands-free listening as soon as the AI finishes greeting.
        resumeListeningIfHandsfree();
    }

    async function sendUserMessage(text) {
        text = (text || '').trim();
        if (!text || busy || ending) { return; }
        busy = true;
        clearError();
        stopListening();
        setStatus('Thinking…');
        addMessage('user', text);
        if (el.input) { el.input.value = ''; }

        try {
            const data = await callWs('mod_airoleplay_send_message', {
                sessionid: sessionId,
                message:   text,
            });
            // If the user clicked End while the request was in flight, don't
            // play the reply or restart listening — just bail.
            if (ending) { return; }
            turn = data.turn || (turn + 1);
            el.turn.textContent = String(turn);
            el.speech.textContent = data.reply;
            addMessage('assistant', data.reply);
            await speak(data.reply, data.audio_url || '');

            if (ending) { return; }

            // Auto-end if scenario has a turn cap.
            if (maxTurns > 0 && turn >= maxTurns) {
                setStatus('Maximum turns reached. Wrapping up…');
                await endSession(true);
                return;
            }
        } catch (e) {
            if (!ending) { showError('Error: ' + e.message); }
        } finally {
            busy = false;
            if (!ending) { resumeListeningIfHandsfree(); }
        }
    }

    async function endSession(auto) {
        // Don't double-fire (button mash, second click while submitting).
        if (ending) { return; }

        // Confirm BEFORE we tear down state — preserves the user's session if they cancel.
        if (!auto && !confirm('End this roleplay session?')) { return; }

        ending = true;

        // Immediately disable the End button so the user sees feedback.
        if (el.end) {
            el.end.disabled = true;
            el.end.textContent = 'Ending…';
        }

        // Tear down everything that could re-enter sendUserMessage or block the page.
        userPaused = true;            // suppress hands-free auto-restart
        clearTimeout(silenceTimer);
        pendingText = '';
        try { if (recognition) { recognition.abort(); } } catch (_) {}
        try { if (window.speechSynthesis) { window.speechSynthesis.cancel(); } } catch (_) {}
        setSpeaking(false);

        setStatus('Saving recording…');

        // Best-effort: capture and upload the conversation audio before we
        // hand off to the evaluation. If this fails, we still complete the
        // session — the recording is a nice-to-have, not a hard requirement.
        try { await uploadRecordingIfAny(); } catch (_) {}

        setStatus('Submitting for evaluation…');

        try {
            const data = await callWs('mod_airoleplay_end_session', { sessionid: sessionId });
            if (data && data.redirecturl) {
                window.location.href = data.redirecturl;
                return; // stop here; page is navigating away
            }
            // No redirect URL — at least let the user know it ended and let them navigate.
            setStatus('Session ended.');
        } catch (e) {
            // Even if the server call fails, the user clearly wants out. Re-enable
            // the button so they can retry, but keep recognition torn down so they
            // can navigate away safely.
            ending = false;
            if (el.end) {
                el.end.disabled = false;
                el.end.textContent = 'End';
            }
            showError('Could not end session: ' + (e.message || 'server error') + '. You can leave the page or try again.');
        }
    }

    // ----- Speech recognition (continuous, hands-free) --------------------
    function initSpeech() {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SR) {
            if (voiceEnabled) {
                showError('Voice input requires Chrome or Edge. You can still type your replies.');
                // Also reveal the text input if it was hidden.
                if (el.input) { el.input.style.display = ''; }
            }
            return;
        }
        recognition = new SR();
        recognition.continuous     = true;   // don't auto-stop on first utterance
        recognition.interimResults = true;
        recognition.maxAlternatives = 1;
        recognition.lang = speechLang;

        recognition.onstart = function() {
            listening = true;
            el.mic && el.mic.classList.add('recording');
            if (!busy && !speakingNow) {
                setStatus('Listening… take your time.');
            }
        };

        recognition.onresult = function(ev) {
            // Build the running transcript from all results in this session.
            let interim = '';
            let finalChunk = '';
            for (let i = ev.resultIndex; i < ev.results.length; i++) {
                const r = ev.results[i];
                if (r.isFinal) {
                    finalChunk += r[0].transcript + ' ';
                } else {
                    interim += r[0].transcript;
                }
            }
            // Accumulate finals (so multi-sentence answers aren't lost between pauses).
            if (finalChunk) {
                pendingText = (pendingText + ' ' + finalChunk).trim();
            }
            // Show the live transcript in the input box for visual feedback.
            const live = (pendingText + ' ' + interim).trim();
            if (el.input) { el.input.value = live; }

            // Reset silence timer — only send after the user has paused for SILENCE_MS.
            clearTimeout(silenceTimer);
            silenceTimer = setTimeout(function() {
                if (ending) { return; }
                const toSend = pendingText.trim();
                if (toSend) {
                    pendingText = '';
                    sendUserMessage(toSend);
                }
            }, SILENCE_MS);
        };

        recognition.onerror = function(ev) {
            listening = false;
            el.mic && el.mic.classList.remove('recording');
            // Common, benign errors — just keep going.
            if (ev.error === 'no-speech' || ev.error === 'aborted') {
                return;
            }
            if (ev.error === 'not-allowed' || ev.error === 'service-not-allowed') {
                showError('Microphone access blocked. Click the camera/lock icon in the address bar to allow it.');
                userPaused = true; // don't auto-restart on a permission denial
                return;
            }
            showError('Microphone error: ' + ev.error + '. Retrying…');
        };

        recognition.onend = function() {
            listening = false;
            el.mic && el.mic.classList.remove('recording');
            if (ending) { return; }
            // If we have a final transcript that hasn't been sent yet, send it.
            if (pendingText.trim() && !busy) {
                const toSend = pendingText.trim();
                pendingText = '';
                sendUserMessage(toSend);
                return;
            }
            // Hands-free: auto-restart unless the user paused or AI is speaking.
            resumeListeningIfHandsfree();
        };
    }

    function startListening() {
        if (!recognition || listening || busy || speakingNow || ending) { return; }
        try {
            pendingText = '';
            recognition.start();
        } catch (e) {
            // Recognition can throw if start() is called too quickly after stop(). Retry briefly.
            setTimeout(function() {
                if (ending) { return; }
                try { recognition.start(); } catch (_) { /* give up silently */ }
            }, 250);
        }
    }

    function stopListening() {
        clearTimeout(silenceTimer);
        if (recognition && listening) {
            try { recognition.stop(); } catch (_) { /* ignore */ }
        }
    }

    function resumeListeningIfHandsfree() {
        if (ending || userPaused || !voiceEnabled || !recognition) { return; }
        // If the partner audio is still finishing, wait and retry rather than
        // give up — this is what makes hands-free reliably resume.
        if (speakingNow || busy) {
            setTimeout(resumeListeningIfHandsfree, 300);
            return;
        }
        // Small delay so we don't clip the start of the user's reply.
        setTimeout(startListening, 250);
    }

    // ----- Mic button = pause/resume hands-free mode ---------------------
    if (el.mic) {
        el.mic.addEventListener('click', function() {
            if (!recognition) {
                showError('Speech recognition is not available in this browser.');
                return;
            }
            clearError();
            if (listening) {
                userPaused = true;
                stopListening();
                setStatus('Paused — click 🎙 to resume.');
            } else {
                userPaused = false;
                setStatus('Listening… take your time.');
                startListening();
            }
        });
    }

    // ----- Text input fallback -------------------------------------------
    if (el.send && el.input) {
        el.send.addEventListener('click', function() {
            sendUserMessage(el.input.value);
        });
        el.input.addEventListener('keydown', function(ev) {
            if (ev.key === 'Enter' && !ev.shiftKey) {
                ev.preventDefault();
                sendUserMessage(el.input.value);
            }
        });
        // Typing in the box pauses hands-free listening so we don't double-send.
        el.input.addEventListener('focus', function() {
            userPaused = true;
            stopListening();
            setStatus('Paused while typing — click 🎙 to resume voice.');
        });
    }

    // ----- End button ----------------------------------------------------
    // Use both 'click' and 'pointerdown' so the end action fires even if some
    // focused element (mic state, autosuggest popup, etc.) absorbs the click.
    if (el.end) {
        el.end.addEventListener('click', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            endSession(false);
        });
    }

    // ----- Countdown -----------------------------------------------------
    const tickInterval = setInterval(function() {
        if (ending) { clearInterval(tickInterval); return; }
        if (secondsLeft <= 0) {
            clearInterval(tickInterval);
            setStatus('Time is up. Wrapping up…');
            endSession(true);
            return;
        }
        secondsLeft--;
        const m = Math.floor(secondsLeft / 60);
        const s = secondsLeft % 60;
        el.timer.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }, 1000);

    // ----- Boot ----------------------------------------------------------
    initSpeech();
    // Start recording — best-effort, runs in parallel with the conversation.
    initRecording();
    loadOpening().catch(function(e) {
        showError('Could not load opening: ' + e.message);
    });
})();
</script>

<?php
echo $OUTPUT->footer();