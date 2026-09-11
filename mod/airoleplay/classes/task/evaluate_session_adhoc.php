<?php
// This file is part of mod_airoleplay.

namespace mod_airoleplay\task;

defined('MOODLE_INTERNAL') || die();

class evaluate_session_adhoc extends \core\task\adhoc_task {

    /**
     * Safe trace helper. Standard mtrace() echoes directly to the output stream,
     * which is fine for cron but FATAL when this task is executed inline from a
     * web service call (the trace bytes corrupt the JSON response and the
     * browser reports `Unexpected token 'a', "airoleplay"... is not valid JSON`).
     *
     * Under CLI we behave like normal mtrace. Under a web request we log via
     * Moodle's debugging() channel so admins can still see the message in
     * developer debug mode without breaking the response.
     */
    private static function log(string $message): void {
        if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
            mtrace($message);
        } else {
            debugging($message, DEBUG_DEVELOPER);
        }
    }

    public function execute(): void {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/airoleplay/externallib.php');

        $data = $this->get_custom_data();
        // Tolerate both array and object forms (direct call vs queued).
        $sessionid = 0;
        if (is_array($data)) {
            $sessionid = (int) ($data['sessionid'] ?? 0);
        } else if (is_object($data)) {
            $sessionid = (int) ($data->sessionid ?? 0);
        }
        if (empty($sessionid)) {
            self::log('airoleplay: evaluate_session_adhoc called without sessionid');
            return;
        }

        $session = $DB->get_record('airoleplay_session', ['id' => $sessionid], '*', IGNORE_MISSING);
        if (!$session) {
            self::log('airoleplay: session ' . $sessionid . ' not found');
            return;
        }

        // Idempotency + concurrency guard. If this session has already been
        // evaluated, do nothing (the inline-trigger path and cron can both fire).
        // If another run started within the last 2 minutes, assume it's in
        // progress and skip to avoid duplicate OpenAI calls / duplicate rows.
        if (!empty($session->evaluated_by_ai)) {
            return;
        }
        $now = time();
        if (!empty($session->evaluation_time) && ($now - (int)$session->evaluation_time) < 120) {
            self::log('airoleplay: evaluation for session ' . $session->id . ' already in progress; skipping');
            return;
        }
        // Stamp the start time as an in-progress marker.
        $DB->set_field('airoleplay_session', 'evaluation_time', $now, ['id' => $session->id]);

        $scenario = $DB->get_record('airoleplay', ['id' => $session->airoleplay], '*', IGNORE_MISSING);
        if (!$scenario) {
            self::log('airoleplay: scenario for session ' . $session->id . ' not found');
            return;
        }

        $persona = null;
        if (!empty($scenario->persona_id)) {
            $persona = $DB->get_record('airoleplay_persona', ['id' => $scenario->persona_id], '*', IGNORE_MISSING);
        }

        $messages = $DB->get_records(
            'airoleplay_message',
            ['session' => $session->id],
            'timecreated ASC, id ASC'
        );
        if (empty($messages)) {
            self::log('airoleplay: session ' . $session->id . ' has no messages to evaluate; marking as evaluated with score 0');
            $DB->update_record('airoleplay_session', (object)[
                'id'              => $session->id,
                'score_overall'   => 0,
                'passed'          => 0,
                'ai_summary'      => 'No conversation to evaluate.',
                'evaluated_by_ai' => 1,
                'evaluation_time' => time(),
                'timemodified'    => time(),
            ]);
            return;
        }

        $transcript = '';
        $learnerturns = 0;
        foreach ($messages as $m) {
            $who = $m->role === 'assistant' ? 'PARTNER' : 'LEARNER';
            if ($m->role !== 'assistant') { $learnerturns++; }
            $transcript .= $who . ': ' . trim($m->content) . "\n";
        }

        // Mandatory phrases for the evaluator to check explicitly.
        $mandatory = [];
        if (!empty($scenario->mandatory_phrases)) {
            $decoded = json_decode($scenario->mandatory_phrases, true);
            if (is_array($decoded)) {
                $mandatory = $decoded;
            } else {
                $mandatory = array_values(array_filter(array_map('trim',
                    preg_split('/\r\n|\r|\n/', $scenario->mandatory_phrases))));
            }
        }

        // Get the FIXED competency list for this scenario. Same list for every
        // learner / every attempt on this scenario, so radar charts are directly
        // comparable. The list is inferred by the AI once (per scenario) and
        // cached on the airoleplay row in evaluation_criteria.
        require_once($CFG->dirroot . '/mod/airoleplay/lib.php');
        $rubric = airoleplay_get_or_generate_competencies($scenario);

        // Render the rubric for the prompt as a fixed scoring list.
        $rubric_lines = [];
        foreach ($rubric as $i => $c) {
            $rubric_lines[] = sprintf('  %d) key="%s", label="%s"',
                $i + 1, $c['key'], $c['label']);
        }
        $rubric_json_schema = [];
        foreach ($rubric as $c) {
            $rubric_json_schema[] = sprintf(
                '    {"key": "%s", "label": "%s", "score": <0-100>, "evidence": "<learner quote>", "rationale": "<why this score>"}',
                addslashes($c['key']), addslashes($c['label'])
            );
        }

        // Build a rich evaluation prompt.
        $systemPrompt =
            "You are an expert corporate-training coach evaluating a roleplay transcript.\n" .
            "Return ONLY valid JSON (no prose, no markdown fences) with this exact shape:\n" .
            "{\n" .
            '  "overall_score": <integer 0-100>,' . "\n" .
            '  "passed": <boolean>,' . "\n" .
            '  "summary": "<2-3 sentence overall judgment>",' . "\n" .
            '  "strengths": ["<short bullet>", ...],' . "\n" .
            '  "improvements": [{"area": "...", "specific_gap": "...", "suggestion": "..."}, ...],' . "\n" .
            '  "recommended_next_steps": ["<short bullet>", ...],' . "\n" .
            '  "competencies": [' . "\n" .
            implode(",\n", $rubric_json_schema) . "\n" .
            '  ],' . "\n" .
            '  "ideal_response_examples": [{"context": "...", "learner_said": "<EXACT quote from the transcript>", "better_response": "..."}, ...],' . "\n" .
            '  "sentiment_trend": "<one of: improving, steady, declining>",' . "\n" .
            '  "mood_score": <integer -100 to 100>,' . "\n" .
            '  "mood_label": "<one of: very-positive, positive, neutral, negative, very-negative>"' . "\n" .
            "}\n\n" .
            "CRITICAL — COMPETENCY RUBRIC (FIXED FOR THIS SCENARIO):\n" .
            "You MUST score the learner against EXACTLY these " . count($rubric) . " competencies — " .
            "use the keys and labels verbatim, in this exact order, do NOT add, remove, or rename any:\n" .
            implode("\n", $rubric_lines) . "\n\n" .
            "Each competency score must be a whole number 0-100, calibrated as: 0-39 poor, 40-59 below standard, 60-74 acceptable, 75-89 strong, 90-100 exceptional. " .
            "Cite at least one specific learner quote in each competency 'evidence' field. " .
            "overall_score should be the weighted average across competencies, rounded.\n" .
            "mood_score reflects the LEARNER's overall emotional tone during the call: " .
            "+100 = enthusiastic/highly positive; +50 = friendly/confident; 0 = neutral; " .
            "-50 = tense/frustrated; -100 = openly hostile. Pick mood_label to match the band.\n" .
            "For ideal_response_examples, learner_said MUST be a verbatim quote from a LEARNER line in the transcript — " .
            "never invent dialogue. Pick the 2-3 moments where a better response would have made the biggest impact.";

        $userPromptParts = [];
        $userPromptParts[] = 'Scenario: ' . ($scenario->name ?? '(untitled)');
        if (!empty($scenario->industry))   { $userPromptParts[] = 'Industry: ' . $scenario->industry; }
        if (!empty($scenario->difficulty)) { $userPromptParts[] = 'Difficulty: ' . $scenario->difficulty; }
        if (!empty($scenario->learning_objective)) {
            $userPromptParts[] = 'Learning objective: ' . $scenario->learning_objective;
        }
        if ($persona) {
            $userPromptParts[] = 'The AI partner played: ' . $persona->name
                . (!empty($persona->persona_type) ? ' (' . $persona->persona_type . ')' : '')
                . (!empty($persona->tone) ? ', tone: ' . $persona->tone : '');
        }
        $userPromptParts[] = 'Pass threshold: ' . ($scenario->pass_score ?? 70) . '%';
        if ($mandatory) {
            $userPromptParts[] = 'Required points the learner should have covered: ' . implode('; ', $mandatory)
                . ". For each missed point, deduct from the most relevant competencies, and call it out in 'improvements'.";
        }
        if ($learnerturns < 2) {
            $userPromptParts[] = "Note: the learner only contributed {$learnerturns} turn(s) — score conservatively due to insufficient evidence.";
        }
        $userPromptParts[] = "\n--- TRANSCRIPT ---\n" . $transcript;

        $prompt = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => implode("\n", $userPromptParts)],
        ];

        try {
            $raw = \mod_airoleplay_ai_helper::chat($prompt, $scenario, [
                'max_tokens'      => 2500,         // big enough for full competency JSON
                'temperature'     => 0.2,          // deterministic scoring
                'response_format' => 'json_object', // OpenAI guarantees valid JSON
            ]);

            // Try strict JSON parse first.
            $json = json_decode($raw, true);
            if (!is_array($json)) {
                // Fall back to extracting the largest JSON object substring.
                if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $raw, $m)) {
                    $json = json_decode($m[0], true);
                }
            }
            if (!is_array($json)) {
                throw new \Exception('AI returned non-JSON evaluation. Raw start: ' . substr($raw, 0, 300));
            }

            // Normalize numeric and array fields.
            $json['overall_score'] = isset($json['overall_score']) ? max(0, min(100, (float) $json['overall_score'])) : 0;
            if (!isset($json['passed'])) {
                $json['passed'] = ($json['overall_score'] >= (float)($scenario->pass_score ?? 70));
            }
            foreach (['strengths', 'improvements', 'recommended_next_steps', 'competencies', 'ideal_response_examples'] as $k) {
                if (!isset($json[$k]) || !is_array($json[$k])) { $json[$k] = []; }
            }

            // ENFORCE THE FIXED RUBRIC. If the AI hallucinated extra keys, drop
            // them. If it forgot any, insert a placeholder so the radar always
            // shows the same axes for this scenario across every learner.
            $by_key = [];
            foreach ($json['competencies'] as $c) {
                if (!empty($c['key'])) { $by_key[(string)$c['key']] = $c; }
            }
            $enforced = [];
            foreach ($rubric as $r) {
                $k = $r['key'];
                if (isset($by_key[$k])) {
                    $row = $by_key[$k];
                    // Force the canonical label so display stays consistent.
                    $row['label'] = $r['label'];
                    $row['key']   = $k;
                    $enforced[] = $row;
                } else {
                    // AI skipped this competency — record a placeholder.
                    $enforced[] = [
                        'key'       => $k,
                        'label'     => $r['label'],
                        'score'     => 0,
                        'evidence'  => '',
                        'rationale' => 'AI did not score this competency for this attempt.',
                    ];
                }
            }
            $json['competencies'] = $enforced;
            // Recompute overall_score from the enforced list (average of the
            // rubric scores), so it stays internally consistent.
            if (!empty($enforced)) {
                $sum = 0; $n = 0;
                foreach ($enforced as $c) {
                    $sum += max(0, min(100, (float)($c['score'] ?? 0)));
                    $n++;
                }
                if ($n > 0) {
                    $json['overall_score'] = (int) round($sum / $n);
                    $json['passed'] = ($json['overall_score'] >= (float)($scenario->pass_score ?? 70));
                }
            }

            // Mood: clamp to [-100, 100] and backfill label if missing.
            $mood = isset($json['mood_score']) ? (int) round((float)$json['mood_score']) : null;
            if ($mood === null) {
                // Derive a rough mood from sentiment_trend as a fallback.
                $trend = strtolower($json['sentiment_trend'] ?? 'steady');
                $mood = $trend === 'improving' ? 40 : ($trend === 'declining' ? -40 : 0);
            }
            $mood = max(-100, min(100, $mood));
            $json['mood_score'] = $mood;
            if (empty($json['mood_label'])) {
                $json['mood_label'] = $mood >= 60 ? 'very-positive'
                                   : ($mood >= 20 ? 'positive'
                                   : ($mood >= -20 ? 'neutral'
                                   : ($mood >= -60 ? 'negative' : 'very-negative')));
            }

            // Backfill learner_said by matching against real learner turns when
            // the AI returned an empty or fabricated quote. Critical so the UI
            // never shows "You said:" with no content.
            $learner_msgs = array_values(array_filter($messages, fn($m) => $m->role !== 'assistant'));
            $learner_texts = array_map(fn($m) => trim($m->content), $learner_msgs);
            foreach ($json['ideal_response_examples'] as &$ex) {
                $said = trim((string)($ex['learner_said'] ?? ''));
                if ($said === '' || !self::is_verbatim_or_substring($said, $learner_texts)) {
                    // Find the most relevant learner turn — match by the context
                    // hint when available, otherwise pick the longest unused turn.
                    $best = self::pick_best_quote($ex['context'] ?? '', $learner_texts);
                    if ($best !== null) {
                        $ex['learner_said'] = $best;
                    }
                }
            }
            unset($ex);

            // Persist via canonical session manager (writes session + competency_result rows).
            $apply_extras = [
                'mood_score' => $mood,
                'mood_label' => $json['mood_label'],
            ];
            if (class_exists('\\mod_airoleplay\\session\\session_manager')) {
                $manager = new \mod_airoleplay\session\session_manager();
                $manager->apply_evaluation((int) $session->id, $json, $scenario);
                // Update mood fields separately to keep apply_evaluation untouched
                // by additive feature flags.
                $DB->update_record('airoleplay_session', (object)[
                    'id'         => $session->id,
                    'mood_score' => $mood,
                    'mood_label' => $json['mood_label'],
                ]);
            } else {
                // Minimal fallback if class missing.
                $update = (object) [
                    'id'                 => $session->id,
                    'score_overall'      => (float) $json['overall_score'],
                    'passed'             => !empty($json['passed']) ? 1 : 0,
                    'ai_summary'         => $json['summary']                              ?? '',
                    'ai_strengths'       => json_encode($json['strengths']               ?? []),
                    'ai_improvements'    => json_encode($json['improvements']            ?? []),
                    'ai_recommendations' => json_encode($json['recommended_next_steps']  ?? []),
                    'ai_ideal_responses' => json_encode($json['ideal_response_examples'] ?? []),
                    'mood_score'         => $mood,
                    'mood_label'         => $json['mood_label'],
                    'scores_detail'      => json_encode($json),
                    'evaluated_by_ai'    => 1,
                    'evaluation_time'    => time(),
                    'timemodified'       => time(),
                ];
                $DB->update_record('airoleplay_session', $update);
            }

            self::log('airoleplay: evaluated session ' . $session->id . ' — score ' . $json['overall_score'] . ', mood ' . $mood);
        } catch (\Throwable $e) {
            self::log('airoleplay: evaluation failed for session ' . $session->id . ': ' . $e->getMessage());
            // Keep `evaluated_by_ai = 0` so the results page shows a clear retry
            // affordance instead of an empty "evaluated" state. Store the error
            // message in ai_summary so the UI / admin can see why it failed.
            // The 2-minute in-progress guard at the top of execute() prevents a
            // tight retry loop.
            $DB->update_record('airoleplay_session', (object)[
                'id'              => $session->id,
                'ai_summary'      => 'Evaluation failed: ' . $e->getMessage(),
                'evaluation_time' => time(),
                'timemodified'    => time(),
            ]);
        }
    }

    /**
     * True if $candidate matches any learner turn verbatim or as a substring
     * (case-insensitive). Lets us accept the AI's exact-or-near quotes.
     */
    private static function is_verbatim_or_substring(string $candidate, array $haystack): bool {
        $needle = strtolower(trim($candidate));
        if ($needle === '') { return false; }
        foreach ($haystack as $hay) {
            $h = strtolower($hay);
            if ($h === $needle || strpos($h, $needle) !== false || strpos($needle, $h) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Score learner turns by overlap with the context hint and return the best
     * quote. If nothing matches, return the longest learner turn (since the AI
     * likely meant a meaningful exchange).
     */
    private static function pick_best_quote(string $context, array $learner_texts): ?string {
        if (empty($learner_texts)) { return null; }

        $ctx_words = array_filter(array_map('strtolower',
            preg_split('/\W+/', $context) ?: []),
            fn($w) => strlen($w) > 3);

        $best = null; $bestScore = -1;
        foreach ($learner_texts as $text) {
            if (trim($text) === '') { continue; }
            $score = 0;
            $lowered = strtolower($text);
            foreach ($ctx_words as $w) {
                if (strpos($lowered, $w) !== false) { $score += 2; }
            }
            $score += min(20, strlen($text) / 10); // mild length bonus
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $text;
            }
        }
        // Truncate to a sensible length for display.
        if ($best !== null && strlen($best) > 280) {
            $best = substr($best, 0, 277) . '…';
        }
        return $best;
    }
}
