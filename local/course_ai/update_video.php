<?php
require_once(__DIR__ . '/../../config.php');
require_login();
require_sesskey();

$courseid = required_param('courseid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);
$videourl = required_param('videourl', PARAM_RAW);
$time = time();

$response = ['status' => 'error', 'message' => 'Something went wrong'];

$params = ['courseid' => $courseid, 'sectionid' => $sectionid];
$record = $DB->get_record('course_section_video', $params);

if ($record) {
    $record->videourl = $videourl;
    $record->timemodified = $time;
    $DB->update_record('course_section_video', $record);
    $response = ['status' => 'success', 'message' => 'Video URL updated'];
} else {
    $new = new stdClass();
    $new->courseid = $courseid;
    $new->sectionid = $sectionid;
    $new->videourl = $videourl;
    $new->timecreated = $time;
    $new->timemodified = $time;
    $DB->insert_record('course_section_video', $new);
    $response = ['status' => 'success', 'message' => 'Video URL added'];
}

echo json_encode($response);
exit;
