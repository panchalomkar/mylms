<?php
// Place this file at: C:\xampp\htdocs\lms5\theme\remui\quiz_result.php

use mod_quiz\quiz_attempt;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

require_login();
global $USER, $CFG;

$attemptid = required_param('attempt', PARAM_INT);
$cmid      = required_param('cmid',    PARAM_INT);

if (!confirm_sesskey()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid sesskey']);
    exit;
}

$attemptobj = quiz_attempt::create($attemptid);

if ($attemptobj->get_userid() != $USER->id) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$attempt   = $attemptobj->get_attempt();
$quiz      = $attemptobj->get_quiz();
$quizobj   = $attemptobj->get_quizobj();

$sumgrades = (float)($attempt->sumgrades ?? 0);
$maxgrades = (float)($quiz->sumgrades   ?? 0);
$quizgrade = (float)($quiz->grade       ?? 0);

// Rescale to quiz grade scale
$grade = ($maxgrades > 0)
    ? round(($sumgrades / $maxgrades) * $quizgrade, 2)
    : 0;

$scorepct = ($quizgrade > 0)
    ? round(($grade / $quizgrade) * 100, 2)
    : 0;

// Pass mark (if set)
$passmark = 0;
if (!empty($quiz->gradepass) && $quizgrade > 0) {
    $passmark = round(($quiz->gradepass / $quizgrade) * 100, 2);
}

$duration = ($attempt->timefinish > 0)
    ? ($attempt->timefinish - $attempt->timestart)
    : (time() - $attempt->timestart);

// Format grade float — strip unnecessary trailing zeros
function fmt_grade($val) {
    $rounded = round((float)$val, 2);
    return (floor($rounded) == $rounded)
        ? number_format($rounded, 0)
        : rtrim(number_format($rounded, 2, '.', ''), '0');
}

// Human-readable dates matching Moodle's userdate format
$dateformat = get_string('strftimedatetimeshort', 'langconfig');
$timestart  = $attempt->timestart  ? userdate($attempt->timestart,  $dateformat) : '--';
$timefinish = $attempt->timefinish ? userdate($attempt->timefinish, $dateformat) : '--';

// Re-attempt URL
$reattempturl = (new moodle_url('/mod/quiz/startattempt.php', [
    'cmid'    => $cmid,
    'sesskey' => sesskey(),
]))->out(false);

header('Content-Type: application/json');
echo json_encode([
    'score'        => $scorepct,
    'marks'        => fmt_grade($sumgrades),
    'totalmarks'   => fmt_grade($maxgrades),
    'grade'        => fmt_grade($grade),
    'maxgrade'     => fmt_grade($quizgrade),
    'passmark'     => $passmark,
    'timetaken'    => format_time($duration),
    'timestart'    => $timestart,
    'timefinish'   => $timefinish,
    'reviewurl'    => (new moodle_url('/mod/quiz/review.php', [
                          'attempt' => $attemptid,
                          'cmid'    => $cmid,
                      ]))->out(false),
    'reattempturl' => $reattempturl,
]);
exit;