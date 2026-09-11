<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AI Roleplay - Activity view page.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/airoleplay/lib.php');

$id     = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'airoleplay');

require_login($course, true, $cm);

$context  = context_module::instance($cm->id);
$scenario = $DB->get_record('airoleplay', ['id' => $cm->instance], '*', MUST_EXIST);

require_capability('mod/airoleplay:view', $context);

// -----------------------------------------------------------------------
// Housekeeping: expire stale active sessions before we count anything.
// A session counts as "stale" if it's been active longer than its scenario's
// max_duration (or 1 hour, whichever is greater), AND has 0 learner messages.
// Empty stale sessions are deleted (they were never real attempts).
// Non-empty stale sessions are marked abandoned (preserved for audit).
// -----------------------------------------------------------------------
airoleplay_expire_stale_sessions($scenario, $USER->id);

// Log view event.
$event = \mod_airoleplay\event\course_module_viewed::create([
    'objectid' => $scenario->id,
    'context'  => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('airoleplay', $scenario);
$event->trigger();

// Update completion for view.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Handle start action.
if ($action === 'start') {
    require_sesskey();
    require_capability('mod/airoleplay:attempt', $context);

    // Check time limits.
    if ($scenario->timeopen && time() < $scenario->timeopen) {
        print_error('scenarionotopenyet', 'mod_airoleplay', '', userdate($scenario->timeopen));
    }
    if ($scenario->timeclose && time() > $scenario->timeclose) {
        print_error('scenarioclosed', 'mod_airoleplay', '', userdate($scenario->timeclose));
    }

    // Check attempt limits — only count attempts the learner actually committed
    // to (status != 'active'). Active rows are either in-progress or will be
    // cleaned up by airoleplay_expire_stale_sessions on the next view load.
    if ($scenario->max_attempts) {
        $used = $DB->count_records_select(
            'airoleplay_session',
            "airoleplay = :rid AND userid = :uid AND status <> 'active'",
            ['rid' => $scenario->id, 'uid' => $USER->id]
        );
        if ($used >= $scenario->max_attempts) {
            print_error('maxattemptsreached', 'mod_airoleplay', '', $scenario->max_attempts);
        }
    }

    $language = optional_param('language', $scenario->language, PARAM_TEXT);
    $type     = optional_param('type', $scenario->scenario_type, PARAM_ALPHA);

    // Reuse a recent active session instead of creating yet another attempt row.
    $resumable = airoleplay_find_resumable_session($scenario->id, $USER->id);
    if ($resumable) {
        $existing = $DB->get_record('airoleplay_session', ['id' => $resumable], 'id, session_token', MUST_EXIST);
        redirect(new moodle_url('/mod/airoleplay/conversation.php', [
            'session' => $existing->id,
            'id'      => $id,
            'token'   => $existing->session_token,
        ]));
    }

    // Create a new session.
    $session_token = bin2hex(random_bytes(32));
    $now = time();

    $session                = new stdClass();
    $session->airoleplay    = $scenario->id;
    $session->userid        = $USER->id;
    $session->attempt       = $DB->count_records_select(
        'airoleplay_session',
        "airoleplay = :rid AND userid = :uid AND status <> 'active'",
        ['rid' => $scenario->id, 'uid' => $USER->id]
    ) + 1;
    $session->session_token = $session_token;
    $session->status        = 'active';
    $session->scenario_type = $type;
    $session->language      = clean_param($language, PARAM_TEXT);
    $session->turn_count    = 0;
    $session->duration_secs = 0;
    $session->evaluated_by_ai = 0;
    $session->timestarted   = $now;
    $session->timemodified  = $now;

    $session->id = $DB->insert_record('airoleplay_session', $session);

    redirect(new moodle_url('/mod/airoleplay/conversation.php', [
	  'session' => $session->id,
          'id' => $id,
        'token'   => $session_token,
    ]));
}

// Page setup.
$PAGE->set_url('/mod/airoleplay/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($scenario->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/mod/airoleplay/styles.css');

// Load Chart.js (used by any inline chart rendering on this page).
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'));

// Gather data — only real attempts (completed, abandoned, OR active with at least 1 learner msg).
$attempts = $DB->get_records_sql(
    "SELECT s.id, s.attempt, s.status, s.score_overall, s.passed,
            s.duration_secs, s.timestarted, s.timecompleted,
            (SELECT COUNT(*) FROM {airoleplay_message} m
              WHERE m.session = s.id AND m.role = 'user') AS user_msg_count
       FROM {airoleplay_session} s
      WHERE s.airoleplay = :rid AND s.userid = :uid
      ORDER BY s.timestarted DESC",
    ['rid' => $scenario->id, 'uid' => $USER->id],
    0, 20
);

// Filter: hide active sessions with no learner messages — they aren't real attempts.
$real_attempts = [];
foreach ((array)$attempts as $a) {
    if ($a->status === 'active' && (int)$a->user_msg_count === 0) {
        continue;
    }
    $real_attempts[] = $a;
}

// Renumber 1..N (newest = highest), so the display reads naturally.
$total = count($real_attempts);
$attempts_data = [];
foreach ($real_attempts as $i => $a) {
    $display_attempt = $total - $i;  // since list is DESC, the first element is the newest = highest number
    $status_labels = [
        'active'    => 'In progress',
        'completed' => 'Completed',
        'abandoned' => 'Abandoned',
        'evaluated' => 'Evaluated',
    ];
    $attempts_data[] = [
        'id'           => (int)$a->id,
        'attempt'      => $display_attempt,
        'status'       => $a->status,
        'status_label' => $status_labels[$a->status] ?? ucfirst($a->status),
        'score'        => $a->score_overall ? round((float)$a->score_overall) : null,
        'passed'       => !empty($a->passed),
        'duration'     => $a->duration_secs ? gmdate('i:s', $a->duration_secs) : '--',
        'date'         => $a->timecompleted
                            ? userdate($a->timecompleted, get_string('strftimedate', 'langconfig'))
                            : userdate($a->timestarted, get_string('strftimedate', 'langconfig')),
        'can_view'     => $a->status === 'completed',
    ];
}

// Persona data.
$persona_data = null;
if (!empty($scenario->persona_id)) {
    $persona = $DB->get_record('airoleplay_persona', ['id' => $scenario->persona_id]);
    if ($persona) {
        $persona_data = [
            'name'       => format_string($persona->name),
            'type'       => $persona->persona_type,
            'tone'       => $persona->tone,
            'avatar_url' => $persona->avatar_url ?? '',
        ];
    }
}

$used_attempts = count($attempts_data);
$max_attempts  = (int)$scenario->max_attempts;
$attempts_left = $max_attempts > 0 ? max(0, $max_attempts - $used_attempts) : null;
$can_attempt   = has_capability('mod/airoleplay:attempt', $context)
    && ($attempts_left === null || $attempts_left > 0)
    && (empty($scenario->timeopen)  || time() >= $scenario->timeopen)
    && (empty($scenario->timeclose) || time() <= $scenario->timeclose);

$scores = [];
foreach ($attempts_data as $a) {
    if ($a['passed'] && $a['score'] !== null) {
        $scores[] = $a['score'];
    }
}
$best_score = $scores ? max($scores) : null;

$supported_languages = [
    'en' => 'English', 'hi' => 'Hindi', 'ta' => 'Tamil', 'te' => 'Telugu',
    'ar' => 'Arabic',  'fr' => 'French', 'de' => 'German', 'es' => 'Spanish',
];
$lang_options = [];
foreach ($supported_languages as $code => $name) {
    $lang_options[] = ['code' => $code, 'name' => $name, 'selected' => $code === $scenario->language];
}

$start_url = (new moodle_url('/mod/airoleplay/view.php', [
    'id'      => $id,
    'action'  => 'start',
    'sesskey' => sesskey(),
]))->out(false);

$template_data = [
    'scenario' => [
        'id'               => (int)$scenario->id,
        'name'             => format_string($scenario->name),
        'intro'            => format_module_intro('airoleplay', $scenario, $cm->id),
        'difficulty'       => $scenario->difficulty,
        'industry'         => $scenario->industry ?? '',
        'language'         => $scenario->language,
        'scenario_type'    => $scenario->scenario_type,
        'enable_voice'     => !empty($scenario->enable_voice),
        'max_attempts'     => $max_attempts,
        'max_duration'     => $scenario->max_duration ?? 0,
        'max_turns'        => $scenario->max_turns ?? 0,
        'pass_score'       => (int)$scenario->pass_score,
        'timeopen'         => $scenario->timeopen  ? userdate($scenario->timeopen)  : '',
        'timeclose'        => $scenario->timeclose ? userdate($scenario->timeclose) : '',
        'learning_objective' => $scenario->learning_objective ?? '',
    ],
    'persona'          => $persona_data,
    'attempts'         => $attempts_data,
    'has_attempts'     => !empty($attempts_data),
    'attempts_used'    => $used_attempts,
    'attempts_left'    => $attempts_left,
    'can_attempt'      => $can_attempt,
    'best_score'       => $best_score,
    'has_passed'       => !empty(array_filter($attempts_data, function($a) { return $a['passed']; })),
    'can_manage'       => has_capability('mod/airoleplay:managescenarios', $context),
    'can_view_reports' => has_capability('mod/airoleplay:viewreports', $context),
    'supported_languages' => $lang_options,
    'start_url'        => $start_url,
    'manage_url'       => (new moodle_url('/mod/airoleplay/view.php', ['id' => $id]))->out(false),
    'reports_url'      => (new moodle_url('/mod/airoleplay/report.php', ['id' => $id]))->out(false),
    'cmid'             => (int)$cm->id,
    'wwwroot'          => $CFG->wwwroot,
    'sesskey'          => sesskey(),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_airoleplay/view', $template_data);
echo $OUTPUT->footer();
