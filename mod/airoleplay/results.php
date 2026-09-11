<?php
/**
 * AI Roleplay - Evaluation results page.
 *
 * Shows AI coaching feedback, competency breakdown,
 * ideal response examples, and learning recommendations.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/airoleplay/lib.php');

$session_id = required_param('session', PARAM_INT);

$session  = $DB->get_record('airoleplay_session', ['id' => $session_id], '*', MUST_EXIST);
$scenario = $DB->get_record('airoleplay', ['id' => $session->airoleplay], '*', MUST_EXIST);
$cm       = get_coursemodule_from_instance('airoleplay', $scenario->id, 0, false, MUST_EXIST);
$course   = get_course($cm->course);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Access check: own session OR viewallattempts cap.
if ($session->userid !== $USER->id) {
    require_capability('mod/airoleplay:viewallattempts', $context);
}

$PAGE->set_url('/mod/airoleplay/results.php', ['session' => $session_id]);
$PAGE->set_title(get_string('pluginname', 'mod_airoleplay') . ': ' . format_string($scenario->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/mod/airoleplay/styles.css');
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'));

// Note: the results page renders its charts, mood-o-meter, audio player and
// competency breakdown via the inline JS at the bottom of results.mustache,
// which polls mod_airoleplay_get_evaluation until the async evaluation finishes.
// We deliberately do NOT bootstrap the conversation AMD module here to avoid
// double-rendering the same widgets.

// Build template data from DB records.
$competencies = $DB->get_records(
    'airoleplay_competency_result',
    ['session' => $session_id],
    'score DESC'
);

$strengths      = json_decode($session->ai_strengths      ?? '[]', true) ?: [];
$improvements   = json_decode($session->ai_improvements   ?? '[]', true) ?: [];
$recommendations = json_decode($session->ai_recommendations ?? '[]', true) ?: [];
$ideal_responses = json_decode($session->ai_ideal_responses ?? '[]', true) ?: [];
$voice_analytics = json_decode($session->voice_analytics   ?? '{}', true) ?: [];

// Attempt navigation: get previous/next sessions for this user+scenario.
$all_sessions = $DB->get_records_select(
    'airoleplay_session',
    'airoleplay = :rid AND userid = :uid AND status = :s',
    ['rid' => $scenario->id, 'uid' => $session->userid, 's' => 'completed'],
    'attempt ASC',
    'id, attempt'
);
$session_list  = array_values((array)$all_sessions);
$current_index = array_search($session_id, array_column($session_list, 'id'));
$prev_session  = ($current_index > 0) ? $session_list[$current_index - 1] : null;
$next_session  = ($current_index < count($session_list) - 1) ? $session_list[$current_index + 1] : null;

$tpl = [
    'session' => [
        'id'              => $session->id,
        'attempt'         => $session->attempt,
        'status'          => $session->status,
        'score_overall'   => $session->score_overall ? round($session->score_overall) : null,
        'passed'          => (bool)$session->passed,
        'evaluated'       => (bool)$session->evaluated_by_ai,
        'turn_count'      => $session->turn_count,
        'duration'        => $session->duration_secs ? gmdate('i:s', $session->duration_secs) : '--',
        'date'            => $session->timecompleted ? userdate($session->timecompleted) : '',
        'pronunciation_score' => $session->pronunciation_score ? round($session->pronunciation_score) : null,
        'mood_score'      => isset($session->mood_score) ? (int)$session->mood_score : null,
        'mood_label'      => $session->mood_label ?? '',
        'audio_url'       => $session->session_audio_url ?? '',
    ],
    'has_recording' => !empty($session->session_audio_url),
    'has_mood'      => $session->mood_score !== null && $session->mood_score !== '',
    'mood'          => airoleplay_mood_template_data($session),
    'scenario' => [
        'id'    => $scenario->id,
        'name'  => format_string($scenario->name),
        'type'  => $scenario->scenario_type,
    ],
    'competencies' => array_values(array_map(fn($c) => [
        'key'          => $c->competency_key,
        'label'        => $c->competency_label,
        'score'        => round($c->score),
        'max_score'    => round($c->max_score),
        'ai_rationale' => $c->ai_rationale ?? '',
        'evidence'     => $c->evidence ?? '',
    ], (array)$competencies)),
    'has_competencies' => !empty($competencies),
    'strengths'         => array_values(array_map('strval', $strengths)),
    'improvements'      => $improvements,
    'recommendations'   => array_values(array_map('strval', $recommendations)),
    'ideal_responses'   => $ideal_responses,
    'ai_summary'        => $session->ai_summary ?? '',
    'voice_analytics'   => $voice_analytics,
    'has_voice'         => !empty($voice_analytics),
    'view_url'          => (new moodle_url('/mod/airoleplay/view.php', ['id' => $cm->id]))->out(false),
    'retry_url'         => (new moodle_url('/mod/airoleplay/view.php', ['id' => $cm->id]))->out(false),
    'prev_session_url'  => $prev_session ? (new moodle_url('/mod/airoleplay/results.php', ['session' => $prev_session->id]))->out(false) : null,
    'next_session_url'  => $next_session ? (new moodle_url('/mod/airoleplay/results.php', ['session' => $next_session->id]))->out(false) : null,
    'prev_attempt'      => $prev_session ? $prev_session->attempt : null,
    'next_attempt'      => $next_session ? $next_session->attempt : null,
    'can_retry'         => has_capability('mod/airoleplay:attempt', $context)
                           && (!$scenario->max_attempts || count($all_sessions) < $scenario->max_attempts),
    'is_own_session'    => $session->userid === $USER->id,
    'cmid'              => $cm->id,
    'ajaxurl'           => $CFG->wwwroot . '/lib/ajax/service.php?sesskey=' . sesskey(),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_airoleplay/results', $tpl);
echo $OUTPUT->footer();
