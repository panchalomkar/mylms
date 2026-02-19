<?php
require_once('../../config.php');
require_login();
require_once($CFG->dirroot . '/local/course_ai/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/videofile/lib.php');

header('Content-Type: application/json'); // 

$courseid = required_param('courseid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);
require_sesskey();

$context = context_course::instance($courseid);
require_capability('moodle/course:manageactivities', $context);

$section = $DB->get_record('course_sections', ['id' => $sectionid, 'course' => $courseid], '*', MUST_EXIST);
$title = $section->name ?: 'Module ' . $section->section;


$result = regenerate_specific_section($courseid, $sectionid, $title); // Your function in lib.php

if (is_array($result) && !empty($result['success'])) {
    echo json_encode($result);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Regeneration failed ❌',
        'courseid' => $courseid,
        'sectionid' => $sectionid,
        'videourl' => $result['videourl'] ?? null // fallback null if not found
    ]);
}

exit;
