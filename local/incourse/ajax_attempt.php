<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$attemptid = required_param('attempt', PARAM_INT, PARAM_INT);
$cmid      = required_param('cmid', PARAM_INT, PARAM_INT);

$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
$PAGE->set_context($attemptobj->get_context());
$PAGE->set_pagelayout('embedded'); // no header/footer

$slots = $attemptobj->get_slots($attemptobj->get_currentpage());
$output = $PAGE->get_renderer('mod_quiz');

$data = $output->attempt_page($attemptobj, $attemptobj->get_currentpage(), $attemptobj->get_access_manager(time()), [], $slots, $cmid, $attemptobj->get_currentpage()+1);

echo json_encode(['html' => $data]);
