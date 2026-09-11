<?php
/**
 * AI Roleplay — per-user detailed report (admin / manager view).
 *
 * Consolidates a single learner's performance on a scenario:
 *   • overall rating + best/average scores
 *   • per-attempt table with score, mood, duration, recording playback
 *   • latest competency profile (radar + bars)
 *   • aggregated strengths and areas to improve across attempts
 *
 * Access: requires mod/airoleplay:viewallattempts on the activity.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/airoleplay/lib.php');

$cmid   = required_param('id',     PARAM_INT);
$userid = required_param('userid', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'airoleplay');
require_login($course, true, $cm);

$context  = context_module::instance($cm->id);
$scenario = $DB->get_record('airoleplay', ['id' => $cm->instance], '*', MUST_EXIST);

require_capability('mod/airoleplay:viewallattempts', $context);

$learner = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$PAGE->set_url('/mod/airoleplay/user_report.php', ['id' => $cmid, 'userid' => $userid]);
$PAGE->set_title(get_string('pluginname', 'mod_airoleplay') . ': ' . fullname($learner));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->requires->css('/mod/airoleplay/styles.css');
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'));

// ---------------------------------------------------------------------------
// Gather all the learner's completed attempts on this scenario.
// ---------------------------------------------------------------------------
$sessions = $DB->get_records_select(
    'airoleplay_session',
    "airoleplay = :rid AND userid = :uid AND status IN ('completed','abandoned')",
    ['rid' => $scenario->id, 'uid' => $userid],
    'attempt ASC, timecompleted ASC'
);

$attempts_tpl = [];
$scores       = [];
$best_session = null;
$latest_session = null;

foreach ($sessions as $s) {
    if ($s->score_overall !== null && $s->score_overall !== '') {
        $scores[] = (float)$s->score_overall;
        if ($best_session === null || (float)$s->score_overall > (float)$best_session->score_overall) {
            $best_session = $s;
        }
    }
    if ($latest_session === null || (int)$s->timecompleted >= (int)$latest_session->timecompleted) {
        $latest_session = $s;
    }

    $mood = airoleplay_mood_template_data($s);

    $attempts_tpl[] = [
        'session_id'  => $s->id,
        'attempt'     => $s->attempt,
        'score'       => ($s->score_overall !== null && $s->score_overall !== '') ? round((float)$s->score_overall) : null,
        'passed'      => (bool)$s->passed,
        'status'      => $s->status,
        'status_label'=> $s->status === 'completed' ? 'Completed' : 'Abandoned',
        'turns'       => (int)$s->turn_count,
        'duration'    => $s->duration_secs ? gmdate('i:s', $s->duration_secs) : '--',
        'date'        => $s->timecompleted ? userdate($s->timecompleted, get_string('strftimedatetimeshort', 'langconfig')) : '',
        'has_recording' => !empty($s->session_audio_url),
        'audio_url'   => $s->session_audio_url ?? '',
        'mood_emoji'  => $mood['available'] ? $mood['emoji'] : '',
        'mood_band'   => $mood['available'] ? $mood['band']  : '',
        'results_url' => (new moodle_url('/mod/airoleplay/results.php', ['session' => $s->id]))->out(false),
    ];
}

// ---------------------------------------------------------------------------
// Aggregate stats.
// ---------------------------------------------------------------------------
$num_attempts = count($sessions);
$avg_score    = $scores ? round(array_sum($scores) / count($scores), 1) : null;
$best_score   = $scores ? round(max($scores)) : null;

// Overall rating out of 5 stars from best score (most generous, reflects ability).
$rating_basis = $best_score !== null ? $best_score : ($avg_score ?? 0);
$stars        = max(0, min(5, round($rating_basis / 20 * 2) / 2)); // nearest 0.5
$full_stars   = (int)floor($stars);
$half_star    = ($stars - $full_stars) >= 0.5;
$empty_stars  = 5 - $full_stars - ($half_star ? 1 : 0);

// ---------------------------------------------------------------------------
// Latest competency profile (from the best attempt for a fair picture).
// ---------------------------------------------------------------------------
$profile_session = $best_session ?: $latest_session;
$competencies = [];
if ($profile_session) {
    $rows = $DB->get_records('airoleplay_competency_result',
        ['session' => $profile_session->id], 'score DESC');
    foreach ($rows as $r) {
        $competencies[] = [
            'label'     => $r->competency_label,
            'score'     => round((float)$r->score),
            'rationale' => $r->ai_rationale ?? '',
        ];
    }
}

// ---------------------------------------------------------------------------
// Aggregated strengths & improvements across all attempts (deduped).
// ---------------------------------------------------------------------------
$all_strengths    = [];
$all_improvements = [];
foreach ($sessions as $s) {
    foreach ((array)json_decode($s->ai_strengths ?? '[]', true) as $str) {
        $key = strtolower(trim((string)$str));
        if ($key !== '') { $all_strengths[$key] = (string)$str; }
    }
    foreach ((array)json_decode($s->ai_improvements ?? '[]', true) as $imp) {
        $area = is_array($imp) ? ($imp['area'] ?? '') : (string)$imp;
        $key  = strtolower(trim((string)$area));
        if ($key !== '') {
            $all_improvements[$key] = is_array($imp)
                ? ['area' => $imp['area'] ?? '', 'suggestion' => $imp['suggestion'] ?? '']
                : ['area' => (string)$imp, 'suggestion' => ''];
        }
    }
}

$tpl = [
    'learner_name'  => fullname($learner),
    'learner_email' => $learner->email,
    'scenario_name' => format_string($scenario->name),
    'num_attempts'  => $num_attempts,
    'avg_score'     => $avg_score,
    'best_score'    => $best_score,
    'has_scores'    => !empty($scores),
    'pass_score'    => round((float)$scenario->pass_score),
    'passed_ever'   => $best_score !== null && $best_score >= (float)$scenario->pass_score,
    'rating'        => $stars,
    'stars_full'    => array_fill(0, $full_stars, 1),
    'has_half_star' => $half_star,
    'stars_empty'   => $empty_stars > 0 ? array_fill(0, $empty_stars, 1) : [],
    'attempts'      => $attempts_tpl,
    'has_attempts'  => !empty($attempts_tpl),
    'competencies'  => $competencies,
    'has_competencies' => !empty($competencies),
    'competency_json'  => json_encode(array_map(fn($c) => [
        'label' => $c['label'], 'score' => $c['score'],
    ], $competencies)),
    'strengths'     => array_values($all_strengths),
    'has_strengths' => !empty($all_strengths),
    'improvements'  => array_values($all_improvements),
    'has_improvements' => !empty($all_improvements),
    'back_url'      => (new moodle_url('/mod/airoleplay/report.php', ['id' => $cmid]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_airoleplay/user_report', $tpl);
echo $OUTPUT->footer();
