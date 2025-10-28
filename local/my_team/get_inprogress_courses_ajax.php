<?php
require_once('../../config.php');
require_login();
require_once($CFG->libdir . '/completionlib.php');

header('Content-Type: application/json');

$userid = required_param('userid', PARAM_INT);
global $DB;

$data = [];

$enrolledcourses = enrol_get_users_courses($userid, true, 'id, fullname, visible');

foreach ($enrolledcourses as $course) {
    $courseid = $course->id;

    // Skip invisible courses
    if (!$course->visible) {
        continue;
    }

    // Check course completion is enabled
    $completion = new completion_info($course);
    if (!$completion->is_enabled()) {
        continue;
    }

    $modinfo = get_fast_modinfo($courseid, $userid);
    $cms = $modinfo->get_cms();

    $totalactivities = 0;
    $completedactivities = 0;

    foreach ($cms as $cm) {
        if (!$cm->uservisible || !$cm->completion || empty($cm->id) || empty($cm->course)) {
            continue;
        }

        $totalactivities++;

        // Use legacy method for older Moodle
        $cmcompletion = $completion->get_data($cm, true, $userid);

        if ($cmcompletion->completionstate == COMPLETION_COMPLETE ||
            $cmcompletion->completionstate == COMPLETION_COMPLETE_PASS) {
            $completedactivities++;
        }
    }

    $percentage = $totalactivities > 0 ? floor(($completedactivities / $totalactivities) * 100) : 0;

    $data[] = [
        'coursename' => format_string($course->fullname),
        'courseid' => $courseid,
        'percentage' => $percentage,
        'totalactivities' => $totalactivities,
        'completedactivities' => $completedactivities
    ];
}

echo json_encode($data);
