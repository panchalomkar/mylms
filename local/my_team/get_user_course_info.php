<?php
require_once(__DIR__ . '/../../config.php');
require_login();

header('Content-Type: application/json');

$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

$user = $DB->get_record('user', ['id' => $userid], 'firstname, lastname', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $courseid], 'fullname', MUST_EXIST);

echo json_encode([
    'firstname' => $user->firstname,
    'lastname' => $user->lastname,
    'fullname' => $course->fullname
]);
