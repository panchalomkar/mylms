<?php
require_once(__DIR__ . '/../../config.php');
require_login();

global $USER;

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id(false, $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);

require_once($CFG->libdir . '/completionlib.php');
$completion = new completion_info($course);

$data = $completion->get_data($cm, false, $USER->id);
$progress = (int) round(\core_completion\progress::get_course_progress_percentage($course, $USER->id));

echo json_encode([
    'completed' => (int)$data->completionstate,
    'progress'  => $progress,
    'cmid'      => $cmid,
]);