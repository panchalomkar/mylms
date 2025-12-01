<?php
require_once('../../config.php');
require_login();

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir . '/gradelib.php');


// ===================================================
// 1️⃣  QUESTION COUNT
// ===================================================
$question_count = $DB->count_records('quiz_slots', ['quizid' => $quiz->id]);


// ===================================================
// 2️⃣  ATTEMPTS (FINISHED + IN-PROGRESS)
// ===================================================

// Finished attempts
$finished_attempts = quiz_get_user_attempts(
    $quiz->id,
    $USER->id,
    'finished',
    true
);

$finished_count = count($finished_attempts);

// In-progress attempt → Moodle-approved detection
$inprogress_attempt = quiz_get_user_attempts(
    $quiz->id,
    $USER->id,
    'unfinished',
    true
);

$inprogress_attemptid = !empty($inprogress_attempt)
    ? $inprogress_attempt[0]->id
    : 0;


// Remaining attempts
$attempts_allowed = $quiz->attempts;
$attempts_remaining = ($attempts_allowed > 0)
    ? max(0, $attempts_allowed - $finished_count)
    : 'Unlimited';


// Build finished attempts list
$attempts_list = [];

foreach ($finished_attempts as $a) {

    $completed = userdate($a->timefinish);
    $marks = number_format($a->sumgrades, 2);

    $grade = quiz_rescale_grade($a->sumgrades, $quiz, false);
    $grade = number_format($grade, 2);

    $attempts_list[] = [
        'attemptid' => $a->id,
        'attemptnum' => $a->attempt,
        'completed' => $completed,
        'marks'     => $marks,
        'grade'     => $grade,
        'reviewurl' => (new moodle_url('/mod/quiz/review.php', ['attempt' => $a->id]))->out(false)
    ];
}


// NEXT attempt number
$next_attempt_number = $finished_count + 1;


// FIRST attempt ID
$firstattempt = $DB->get_record('quiz_attempts', [
    'quiz' => $quiz->id,
    'userid' => $USER->id,
    'attempt' => 1
]);

$first_attemptid = $firstattempt ? $firstattempt->id : 0;


// Highest grade
$highest_grade = !empty($attempts_list)
    ? max(array_column($attempts_list, 'grade'))
    : 0;


// ===================================================
// 3️⃣  GRADING SETTINGS
// ===================================================
$grade_item = $DB->get_record('grade_items', [
    'iteminstance' => $quiz->id,
    'itemmodule'   => 'quiz'
]);

$maxgrade      = $grade_item->grademax;
$grade_to_pass = $grade_item->gradepass * 10;


// Latest grade
$grades = grade_get_grades($cm->course, 'mod', 'quiz', $quiz->id, $USER->id);
$usergrade = 0;

if (!empty($grades->items[0]->grades)) {
    $g = reset($grades->items[0]->grades);
    $usergrade = $g->grade ? round($g->grade, 2) : 0;
}


// Grading method
$grademethodmap = [
    QUIZ_GRADEHIGHEST => "Highest Grade",
    QUIZ_GRADEAVERAGE => "Average Grade",
    QUIZ_ATTEMPTFIRST => "First Attempt",
    QUIZ_ATTEMPTLAST  => "Last Attempt",
];
$grademethod = $grademethodmap[$quiz->grademethod] ?? "Highest Grade";


// ===================================================
// 4️⃣  SAFE EXAM BROWSER STATUS
// ===================================================
$seb = $DB->get_record('quizaccess_seb_quizsettings', ['quizid' => $quiz->id]);
$seb_enabled = (!empty($seb) && $seb->requiresafeexambrowser == 1) ? 1 : 0;


// ===================================================
// 5️⃣  PROCTORING STATUS
// ===================================================
$proctor = $DB->get_record('quizaccess_proctoring', ['quizid' => $quiz->id]);
$proctoring_enabled = (!empty($proctor) && $proctor->proctoringrequired == 1) ? 1 : 0;


// ===================================================
// 6️⃣  FINAL JSON OUTPUT
// ===================================================
$data = [
    'name'                => $quiz->name,
    'intro'               => format_text($quiz->intro, $quiz->introformat),

    'courseid'            => $cm->course,

    // MOST IMPORTANT VALUES FOR UI
    'inprogress_attemptid'=> $inprogress_attemptid,
    'next_attempt'        => $next_attempt_number,
    'first_attemptid'     => $first_attemptid,

    'timelimit'           => $quiz->timelimit,
    'attempts'            => $attempts_allowed > 0 ? $attempts_allowed : 'Unlimited',
    'attempts_remaining'  => $attempts_remaining,
    'question_count'      => $question_count,

    'maxgrade'            => $maxgrade,
    'grade_to_pass'       => $grade_to_pass,
    'usergrade'           => $usergrade,
    'grademethod'         => $grademethod,

    'seb_enabled'         => $seb_enabled,
    'proctoring_enabled'  => $proctoring_enabled,

    'highest_grade'       => $highest_grade,
    'attempts_list'       => $attempts_list
];

echo json_encode($data);
