<?php
require_once('../../config.php');
require_login();

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir . '/gradelib.php');

// ✅ Question count
$question_count = $DB->count_records('quiz_slots', ['quizid' => $quiz->id]);

// ✅ Completed attempts
$finished_attempts = quiz_get_user_attempts($quiz->id, $USER->id, 'finished', true);
$finished_count = count($finished_attempts);

// ✅ Remaining attempts
$attempts_allowed = $quiz->attempts;
$attempts_remaining = ($attempts_allowed > 0)
    ? max(0, $attempts_allowed - $finished_count)
    : 'Unlimited';

// ✅ Quiz grade settings
$grade_item = $DB->get_record('grade_items', ['iteminstance' => $quiz->id, 'itemmodule' => 'quiz']);
$maxgrade   = $grade_item->grademax;
$grade_to_pass = $grade_item->gradepass*10;

// ✅ Get user latest grade
$grades = grade_get_grades($cm->course, 'mod', 'quiz', $quiz->id, $USER->id);
$usergrade = 0;

if (!empty($grades->items[0]->grades)) {
    $g = reset($grades->items[0]->grades);
    $usergrade = $g->grade ? round($g->grade, 2) : 0;
}

// ✅ Grading method text
$grademethodmap = [
    QUIZ_GRADEHIGHEST => "Highest Grade",
    QUIZ_GRADEAVERAGE => "Average Grade",
    QUIZ_ATTEMPTFIRST => "First Attempt",
    QUIZ_ATTEMPTLAST  => "Last Attempt",
];
$grademethod = $grademethodmap[$quiz->grademethod] ?? "Highest Grade";

// ✅ Output JSON
$data = [
    'name' => $quiz->name,
    'intro' => format_text($quiz->intro, $quiz->introformat),
    'timelimit' => $quiz->timelimit,
    'attempts' => $attempts_allowed > 0 ? $attempts_allowed : 'Unlimited',
    'attempts_remaining' => $attempts_remaining,
    'grade_to_pass' => $grade_to_pass,
    'question_count' => $question_count,
    'maxgrade' => $maxgrade,
    'usergrade' => $usergrade,     // ✅ NOW UI CAN SHOW "2 / 10"
    'grademethod' => $grademethod
];

echo json_encode($data);
