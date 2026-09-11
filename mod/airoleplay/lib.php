<?php
defined('MOODLE_INTERNAL') || die();

function airoleplay_supports(string $feature): ?bool {
    switch ($feature) {
        case FEATURE_MOD_INTRO:            return true;
        case FEATURE_SHOW_DESCRIPTION:     return true;
        case FEATURE_GRADE_HAS_GRADE:      return true;
        case FEATURE_GRADE_OUTCOMES:       return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        case FEATURE_GROUPS:               return true;
        case FEATURE_GROUPINGS:            return true;
        case FEATURE_BACKUP_MOODLE2:       return true;
        case FEATURE_MOD_PURPOSE:          return MOD_PURPOSE_ASSESSMENT;
        default:                           return null;
    }
}

function airoleplay_add_instance(stdClass $data, $mform = null): int {
    global $DB;
    $data->timecreated  = time();
    $data->timemodified = time();

    // Fix: ensure gradepass is never NULL
    if (!isset($data->gradepass) || $data->gradepass === null || $data->gradepass === '') {
        $data->gradepass = 0;
    }

    if (!empty($data->mandatory_phrases_raw)) {
        $lines = array_filter(array_map('trim', explode("\n", $data->mandatory_phrases_raw)));
        $data->mandatory_phrases = json_encode(array_values($lines));
    }
    if (!empty($data->forbidden_phrases_raw)) {
        $lines = array_filter(array_map('trim', explode("\n", $data->forbidden_phrases_raw)));
        $data->forbidden_phrases = json_encode(array_values($lines));
    }
    
    $data->id = $DB->insert_record('airoleplay', $data);
    airoleplay_grade_item_update($data);

    // Pre-warm the competency cache so the first learner attempt doesn't pay
    // the AI inference latency. Wrapped so a transient AI failure here doesn't
    // block scenario creation.
    try {
        $scenario = $DB->get_record('airoleplay', ['id' => $data->id], '*');
        if ($scenario) {
            airoleplay_get_or_generate_competencies($scenario);
        }
    } catch (\Throwable $e) {
        debugging('airoleplay: competency pre-warm on create failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    return $data->id;
}

function airoleplay_update_instance(stdClass $data, $mform = null): bool {
    global $DB;
    $data->id           = $data->instance;
    $data->timemodified = time();

    // Add this:
    if (!isset($data->gradepass) || $data->gradepass === null || $data->gradepass === '') {
        $data->gradepass = 0;
    }

    if (!empty($data->mandatory_phrases_raw)) {

        $lines = array_filter(array_map('trim', explode("\n", $data->mandatory_phrases_raw)));
        $data->mandatory_phrases = json_encode(array_values($lines));
    }
    if (!empty($data->forbidden_phrases_raw)) {
        $lines = array_filter(array_map('trim', explode("\n", $data->forbidden_phrases_raw)));
        $data->forbidden_phrases = json_encode(array_values($lines));
    }

    // Invalidate cached competency rubric if the teacher changed something
    // material (name, learning objective, industry, difficulty, persona).
    // Next evaluation (or pre-warm below) will regenerate it.
    $existing = $DB->get_record('airoleplay', ['id' => $data->id], 'name, learning_objective, industry, difficulty, persona_id, evaluation_criteria');
    $material_changed = $existing && (
        ($existing->name ?? '')               !== ($data->name ?? '') ||
        ($existing->learning_objective ?? '') !== ($data->learning_objective ?? '') ||
        ($existing->industry ?? '')           !== ($data->industry ?? '') ||
        ($existing->difficulty ?? '')         !== ($data->difficulty ?? '') ||
        (int)($existing->persona_id ?? 0)     !== (int)($data->persona_id ?? 0)
    );
    if ($material_changed) {
        $data->evaluation_criteria = ''; // clears the cache; regenerated on next eval / pre-warm below
    }

    $DB->update_record('airoleplay', $data);
    airoleplay_grade_item_update($data);

    // Pre-warm the competency cache so the first learner attempt doesn't pay
    // the inference latency. Safe to call; it short-circuits if already cached.
    // Wrapped so a transient AI failure here doesn't block scenario save.
    try {
        $scenario = $DB->get_record('airoleplay', ['id' => $data->id], '*');
        if ($scenario && empty($scenario->evaluation_criteria)) {
            airoleplay_get_or_generate_competencies($scenario);
        }
    } catch (\Throwable $e) {
        debugging('airoleplay: competency pre-warm on update failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    return true;
}

function airoleplay_delete_instance(int $id): bool {
    global $DB;
    if (!$DB->record_exists('airoleplay', ['id' => $id])) {
        return false;
    }
    $sids = $DB->get_fieldset_select('airoleplay_session', 'id', 'airoleplay = :id', ['id' => $id]);
    if ($sids) {
        list($sql, $params) = $DB->get_in_or_equal($sids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('airoleplay_message', "session $sql", $params);
        $DB->delete_records_select('airoleplay_competency_result', "session $sql", $params);
        $DB->delete_records_select('airoleplay_ai_usage', "session $sql", $params);
    }
    $DB->delete_records('airoleplay_session', ['airoleplay' => $id]);
    $aids = $DB->get_fieldset_select('airoleplay_assignment', 'id', 'airoleplay = :id', ['id' => $id]);
    if ($aids) {
        list($sql, $params) = $DB->get_in_or_equal($aids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('airoleplay_assignment_user', "assignment $sql", $params);
    }
    $DB->delete_records('airoleplay_assignment', ['airoleplay' => $id]);
    $DB->delete_records('airoleplay', ['id' => $id]);
    airoleplay_grade_item_delete((object)['id' => $id, 'course' => 0]);
    return true;
}

function airoleplay_grade_item_update(stdClass $airoleplay, $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $params = [
        'itemname'  => $airoleplay->name,
        'idnumber'  => $airoleplay->cmidnumber ?? '',
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax'  => $airoleplay->grademax  ?? 100,
        'grademin'  => 0,
        'gradepass' => $airoleplay->gradepass ?? 70,
    ];
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    return grade_update('mod/airoleplay', $airoleplay->course ?? 0, 'mod', 'airoleplay',
        $airoleplay->id, 0, $grades, $params);
}

function airoleplay_grade_item_delete(stdClass $airoleplay): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update('mod/airoleplay', $airoleplay->course ?? 0, 'mod', 'airoleplay',
        $airoleplay->id, 0, null, ['deleted' => 1]);
}

function airoleplay_update_grades(stdClass $airoleplay, int $userid = 0, bool $nullifnone = true): void {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');
    if ($userid) {
        $val = $DB->get_field_sql(
            "SELECT MAX(grade) FROM {airoleplay_session}
              WHERE airoleplay=:rid AND userid=:uid AND status='completed' AND grade IS NOT NULL",
            ['rid' => $airoleplay->id, 'uid' => $userid]
        );
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = ($val !== false && $val !== null) ? (float)$val : null;
        airoleplay_grade_item_update($airoleplay, $grade);
    }
}

function airoleplay_get_completion_state(stdClass $course, stdClass $cm, int $userid, bool $type): bool {
    global $DB;
    $r = $DB->get_record('airoleplay', ['id' => $cm->instance], '*', MUST_EXIST);
    if ($r->completionsubmit) {
        return $DB->record_exists_select('airoleplay_session',
            "airoleplay=:rid AND userid=:uid AND passed=1 AND status='completed'",
            ['rid' => $r->id, 'uid' => $userid]);
    }
    return $type == COMPLETION_AND ? true : false;
}

function airoleplay_user_outline(stdClass $course, stdClass $user, stdClass $mod, stdClass $airoleplay): stdClass {
    global $DB;
    $result = new stdClass();
    $result->info = get_string('noattempts', 'mod_airoleplay');
    $result->time = 0;
    $sessions = $DB->get_records_select('airoleplay_session',
        "airoleplay=:rid AND userid=:uid AND status='completed'",
        ['rid' => $airoleplay->id, 'uid' => $user->id], 'timecompleted DESC', 'id,score_overall,timecompleted', 0, 5);
    if ($sessions) {
        $scores = array_column((array)$sessions, 'score_overall');
        $result->info = get_string('useroutline', 'mod_airoleplay',
            ['attempts' => count($sessions), 'best' => round((float)max($scores))]);
        $times = array_column((array)$sessions, 'timecompleted');
        $result->time = $times ? (int)max($times) : 0;
    }
    return $result;
}

/**
 * Serve plugin files. Handles:
 *   - intro            (module context)  — activity description attachments
 *   - avatar           (module context)  — persona avatars
 *   - session_audio    (module context)  — conversation recordings
 *   - message_audio    (module context)  — per-message AI TTS audio
 *
 * Access control:
 *   • Session owner can always play their own audio.
 *   • Users with mod/airoleplay:viewallattempts can play any session's audio
 *     (admin/manager report views).
 *
 * Note on signature: $course and $cm are deliberately untyped here because
 * Moodle 4.x sometimes passes null when serving files outside a real course
 * context. Adding `stdClass` type hints would cause TypeError fatals.
 */
function airoleplay_pluginfile($course, $cm, $context, string $filearea, array $args,
        bool $forcedownload, array $options = []): bool {
    global $DB, $USER;

    // All fileareas are served from module context.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // Public-ish module fileareas.
    if (in_array($filearea, ['intro', 'avatar'], true)) {
        require_login($course, true, $cm);
        $itemid   = array_shift($args);
        $filename = array_pop($args);
        $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_airoleplay', $filearea, $itemid, $filepath, $filename);
        if (!$file) { return false; }
        send_stored_file($file, 0, 0, $forcedownload, $options);
        return true;
    }

    // Audio fileareas — require auth + per-session ownership check.
    if (!in_array($filearea, ['session_audio', 'message_audio'], true)) {
        return false;
    }

    require_login($course, true, $cm);

    $itemid   = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    // Resolve the owning session for access control.
    if ($filearea === 'session_audio') {
        $session = $DB->get_record('airoleplay_session', ['id' => $itemid], '*', IGNORE_MISSING);
    } else { // message_audio: itemid is a message id.
        $msg = $DB->get_record('airoleplay_message', ['id' => $itemid], 'session', IGNORE_MISSING);
        $session = $msg ? $DB->get_record('airoleplay_session',
            ['id' => $msg->session], '*', IGNORE_MISSING) : null;
    }
    if (!$session) { return false; }

    $allowed = ((int)$session->userid === (int)$USER->id)
            || has_capability('mod/airoleplay:viewallattempts', $context);
    if (!$allowed) { return false; }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_airoleplay', $filearea, $itemid, $filepath, $filename);
    if (!$file) { return false; }

    // Audio caches for a day so repeat plays don't re-stream. Never force download.
    send_stored_file($file, 86400, 0, false, $options);
    return true;
}

function airoleplay_get_extra_capabilities(): array { return ['moodle/site:accessallgroups']; }
function airoleplay_get_view_actions(): array { return ['view', 'start session']; }
function airoleplay_get_post_actions(): array { return ['send message', 'end session']; }

/**
 * Expire active sessions that the user abandoned (closed the tab, etc.).
 *
 * - Empty sessions (no learner messages) older than the threshold are DELETED —
 *   they were never real attempts and should not count against the attempt cap.
 * - Non-empty sessions older than the threshold are marked 'abandoned' so they
 *   stay in the record for auditing but don't appear as "still active".
 *
 * Called from view.php on every page load — cheap, indexed by (airoleplay, userid).
 *
 * @param stdClass $scenario  The airoleplay activity instance.
 * @param int      $userid    User to clean up for.
 */
function airoleplay_expire_stale_sessions(stdClass $scenario, int $userid): void {
    global $DB;

    // Threshold: at least 1 hour, or 2x the scenario's max_duration if larger.
    $threshold = max(3600, 2 * (int)($scenario->max_duration ?? 0));
    $cutoff = time() - $threshold;

    $stale = $DB->get_records_select(
        'airoleplay_session',
        "airoleplay = :rid AND userid = :uid AND status = 'active' AND timestarted < :cutoff",
        ['rid' => $scenario->id, 'uid' => $userid, 'cutoff' => $cutoff],
        '',
        'id, turn_count, timestarted'
    );

    foreach ($stale as $s) {
        $msgcount = (int) $DB->count_records('airoleplay_message', ['session' => $s->id, 'role' => 'user']);

        if ($msgcount === 0) {
            // Never used — delete completely so it doesn't pollute the attempt count.
            $DB->delete_records('airoleplay_message', ['session' => $s->id]);
            $DB->delete_records('airoleplay_competency_result', ['session' => $s->id]);
            $DB->delete_records('airoleplay_session', ['id' => $s->id]);
        } else {
            // Has content — mark abandoned and preserve.
            $DB->update_record('airoleplay_session', (object) [
                'id'           => $s->id,
                'status'       => 'abandoned',
                'timemodified' => time(),
            ]);
        }
    }
}

/**
 * Build the template-friendly mood data for a session row.
 *
 * The "Mood-o-meter" UI is a speedometer dial showing the learner's emotional
 * tone over the call. We pre-compute the dial angle, the colour band, and a
 * matching emoji so templates remain dumb.
 *
 * mood_score is stored as an integer in -100..+100. We map it to:
 *   • dial angle:  -100 → -90°  (full left, "very negative")
 *                   +100 → +90°  (full right, "very positive")
 *   • colour:      red → amber → green
 *   • emoji + label band for the dial caption
 *
 * @param stdClass $session  airoleplay_session row
 * @return array
 */
function airoleplay_mood_template_data(stdClass $session): array {
    if ($session->mood_score === null || $session->mood_score === '') {
        return ['available' => false];
    }
    $score = max(-100, min(100, (int)$session->mood_score));

    // -100..+100 → -90°..+90°.
    $angle = ($score / 100) * 90;

    if ($score >= 60) {
        $emoji  = '😄';
        $colour = '#10b981'; // emerald
        $band   = 'Very Positive';
    } else if ($score >= 20) {
        $emoji  = '🙂';
        $colour = '#22c55e'; // green
        $band   = 'Positive';
    } else if ($score >= -20) {
        $emoji  = '😐';
        $colour = '#f59e0b'; // amber
        $band   = 'Neutral';
    } else if ($score >= -60) {
        $emoji  = '😟';
        $colour = '#f97316'; // orange
        $band   = 'Negative';
    } else {
        $emoji  = '😡';
        $colour = '#ef4444'; // red
        $band   = 'Very Negative';
    }

    // Pre-compute the dial's needle endpoint in SVG coordinates (160x90 dial).
    $rad   = deg2rad($angle - 90); // -90° rotation puts 0 at top in SVG.
    $cx    = 100;
    $cy    = 100;
    $r     = 70;
    $tipX  = round($cx + $r * cos($rad), 1);
    $tipY  = round($cy + $r * sin($rad), 1);

    return [
        'available' => true,
        'score'     => $score,
        'angle'     => round($angle, 1),
        'colour'    => $colour,
        'emoji'     => $emoji,
        'band'      => $band,
        'label'     => $session->mood_label ?: $band,
        'needle_x'  => $tipX,
        'needle_y'  => $tipY,
        // Pre-shifted percentage along the arc for use in CSS gradients.
        'arc_pct'   => round(($score + 100) / 2, 1),
    ];
}

/**
 * Find a still-usable active session for this user on this scenario, or null.
 * "Usable" = status active AND started within the last hour
 * (so opening the activity again resumes the just-started session rather than
 * creating a new attempt row each visit).
 *
 * @return int|null  Session id, or null if none.
 */
function airoleplay_find_resumable_session(int $scenarioid, int $userid): ?int {
    global $DB;
    $cutoff = time() - 3600;
    $rec = $DB->get_record_select(
        'airoleplay_session',
        "airoleplay = :rid AND userid = :uid AND status = 'active' AND timestarted >= :cutoff",
        ['rid' => $scenarioid, 'uid' => $userid, 'cutoff' => $cutoff],
        'id',
        IGNORE_MULTIPLE
    );
    return $rec ? (int) $rec->id : null;
}

/**
 * Get the canonical competency list for a scenario.
 *
 * Competencies are inferred ONCE per scenario by the AI (from its name,
 * learning objective, industry, difficulty, and persona) and cached in
 * `airoleplay.evaluation_criteria` as JSON. Every subsequent evaluation
 * scores against the *same* list, so two learners attempting the same
 * scenario get directly comparable radar charts.
 *
 * If the cache is empty/invalid, this function calls the AI to generate the
 * list and saves it before returning. If the AI call fails (no key, network
 * error, etc.), it falls back to a sensible default so evaluation never
 * blocks on this step.
 *
 * @param object $scenario  airoleplay row (must have id, name, learning_objective, etc.)
 * @return array  Each entry: ['key' => string, 'label' => string]
 */
function airoleplay_get_or_generate_competencies(object $scenario): array {
    global $DB, $CFG;

    // 1. Return cached list if present and well-formed.
    if (!empty($scenario->evaluation_criteria)) {
        $cached = json_decode($scenario->evaluation_criteria, true);
        if (is_array($cached) && !empty($cached)) {
            $valid = [];
            foreach ($cached as $row) {
                if (!empty($row['key']) && !empty($row['label'])) {
                    $valid[] = [
                        'key'   => (string)$row['key'],
                        'label' => (string)$row['label'],
                    ];
                }
            }
            if (count($valid) >= 3) {
                return $valid;
            }
        }
    }

    // 2. Build a focused prompt that infers competencies WITHOUT a transcript.
    require_once($CFG->dirroot . '/mod/airoleplay/externallib.php');

    $context_lines = ['Scenario name: ' . ($scenario->name ?? '(untitled)')];
    if (!empty($scenario->industry))           { $context_lines[] = 'Industry: '   . $scenario->industry; }
    if (!empty($scenario->difficulty))         { $context_lines[] = 'Difficulty: ' . $scenario->difficulty; }
    if (!empty($scenario->learning_objective)) { $context_lines[] = 'Learning objective: ' . $scenario->learning_objective; }
    if (!empty($scenario->system_instructions)) {
        $sysHint = mb_substr((string)$scenario->system_instructions, 0, 500);
        $context_lines[] = 'Scenario setup notes: ' . $sysHint;
    }
    if (!empty($scenario->persona_id)) {
        $persona = $DB->get_record('airoleplay_persona', ['id' => $scenario->persona_id], '*', IGNORE_MISSING);
        if ($persona) {
            $context_lines[] = 'AI partner persona: ' . ($persona->name ?? '')
                . (!empty($persona->persona_type) ? ' (' . $persona->persona_type . ')' : '')
                . (!empty($persona->tone) ? ', tone: ' . $persona->tone : '');
        }
    }

    $systemPrompt =
        "You design competency rubrics for corporate roleplay training. " .
        "Given a scenario description, list 5 or 6 competencies that a real trainer would assess. " .
        "Competencies must be SPECIFIC to the scenario — not generic soft skills like 'Communication'. " .
        "Return ONLY valid JSON with this exact shape:\n" .
        '{ "competencies": [' . "\n" .
        '    {"key": "<snake_case_id>", "label": "<Display Name, max 24 chars>"},' . "\n" .
        '    ... (5 or 6 entries)' . "\n" .
        '] }' . "\n\n" .
        "Examples (illustrative, pick what fits the scenario):\n" .
        "  • Conflict mediation → mediation_skill, active_listening, de-escalation, fairness, resolution_clarity, follow_through\n" .
        "  • Loan-approval / cross-sell → product_explanation, needs_discovery, cross_sell_attempt, compliance_disclosure, customer_reassurance, closing\n" .
        "  • Cold sales call → opener_strength, qualifying_questions, value_articulation, objection_handling, next_step_commitment\n" .
        "  • Customer complaint → empathy, problem_diagnosis, ownership, solution_offered, expectation_setting\n" .
        "Use snake_case for keys and Title Case for labels. Keep labels short (1-3 words, ≤24 chars) so they fit a radar chart.";

    $userPrompt = implode("\n", $context_lines);

    $prompt = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user',   'content' => $userPrompt],
    ];

    $competencies = null;
    try {
        $raw = \mod_airoleplay_ai_helper::chat($prompt, $scenario, [
            'max_tokens'      => 600,
            'temperature'     => 0.3,
            'response_format' => 'json_object',
        ]);
        $json = json_decode($raw, true);
        if (!is_array($json) && preg_match('/\{(?:[^{}]|(?R))*\}/s', $raw, $m)) {
            $json = json_decode($m[0], true);
        }
        if (is_array($json) && !empty($json['competencies']) && is_array($json['competencies'])) {
            $competencies = [];
            foreach ($json['competencies'] as $c) {
                $key   = isset($c['key'])   ? preg_replace('/[^a-z0-9_]/i', '_', strtolower(trim((string)$c['key'])))   : '';
                $label = isset($c['label']) ? trim((string)$c['label']) : '';
                if ($key === '' || $label === '') { continue; }
                if (strlen($label) > 50) { $label = substr($label, 0, 47) . '...'; }
                $competencies[] = ['key' => $key, 'label' => $label];
            }
            if (count($competencies) < 3) { $competencies = null; }
        }
    } catch (\Throwable $e) {
        debugging('airoleplay: competency inference failed for scenario '
            . ($scenario->id ?? '?') . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    // 3. Last-resort fallback — keep the legacy 6-competency list so evaluation
    //    still works if the AI call fails. The cache below means we'll retry
    //    next time the scenario is edited or re-saved.
    if (empty($competencies)) {
        $competencies = [
            ['key' => 'communication',     'label' => 'Communication'],
            ['key' => 'empathy',           'label' => 'Empathy'],
            ['key' => 'confidence',        'label' => 'Confidence'],
            ['key' => 'clarity',           'label' => 'Clarity'],
            ['key' => 'professionalism',   'label' => 'Professionalism'],
            ['key' => 'objection_handling','label' => 'Objection Handling'],
        ];
        // Don't cache the fallback — we want the next save to retry the AI call.
        return $competencies;
    }

    // 4. Cache the successful AI result so every future evaluation uses the same list.
    try {
        $DB->set_field('airoleplay', 'evaluation_criteria',
            json_encode($competencies), ['id' => $scenario->id]);
    } catch (\Throwable $e) {
        debugging('airoleplay: could not cache competencies on scenario '
            . $scenario->id . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    return $competencies;
}

/**
 * Clear the cached competency list for a scenario, so the next call to
 * airoleplay_get_or_generate_competencies() will re-infer them from the AI.
 * Use after a teacher edits the scenario name or learning objective.
 *
 * @param int $scenarioid
 */
function airoleplay_invalidate_competency_cache(int $scenarioid): void {
    global $DB;
    $DB->set_field('airoleplay', 'evaluation_criteria', '', ['id' => $scenarioid]);
}
