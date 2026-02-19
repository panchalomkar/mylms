<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

use mod_quiz\quiz_settings;
use mod_quiz\quiz_attempt;

$cmid = required_param('cmid', PARAM_INT);
require_sesskey();

try {
    $quizobj = quiz_settings::create_for_cmid($cmid, $USER->id);

    if (!$quizobj->has_questions()) {
        throw new \moodle_exception('cannotstartnoquestions', 'quiz');
    }

    $accessmanager = $quizobj->get_access_manager(time());

    // Check if user has an in-progress attempt
    $attempts = $quizobj->get_user_attempts($USER->id, 'all');
    $inprogress = array_filter($attempts, function($a) {
        return $a->state == quiz_attempt::IN_PROGRESS || $a->state == quiz_attempt::OVERDUE;
    });
    if ($inprogress) {
        $attempt = reset($inprogress);
        echo json_encode(['attemptid' => $attempt->id]);
        exit;
    }

    // Otherwise, start a new attempt
    list($currentattemptid, $attemptnumber, $lastattempt) =
        quiz_validate_new_attempt($quizobj, $accessmanager, true, 0, true);

    if (!$currentattemptid || $lastattempt->state == quiz_attempt::NOT_STARTED) {
        $attempt = quiz_prepare_and_start_new_attempt($quizobj, $attemptnumber, $lastattempt);
    } else {
        $attempt = $lastattempt;
    }

    echo json_encode(['attemptid' => $attempt->id]);
    exit;

} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
