<?php
// local/course_ai/complete_video.php
require_once('../../config.php');
require_login();
require_sesskey();

global $DB, $USER;

header('Content-Type: application/json; charset=utf-8');

$cmid = optional_param('cmid', 0, PARAM_INT);
if (!$cmid) {
    echo json_encode(['success' => false, 'error' => 'Missing cmid']);
    exit;
}

try {
    // Load course module and context; MUST_EXIST will throw if invalid
    $cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $course = get_course($cm->course);

    // Capability check - adjust for module type if needed
    if (!has_capability('mod/videofile:view', $context)) {
        echo json_encode(['success' => false, 'error' => 'No permission']);
        exit;
    }

    // Call the helper that sets completion for current user.
    require_once($CFG->dirroot . '/local/course_ai/lib.php');
    $ok = local_course_ai_mark_cm_complete($cmid, $USER->id);

    // OPTIONAL: add a simple log entry in your plugin table (uncomment if you created the table)
    /*
    $record = new stdClass();
    $record->course = $course->id;
    $record->cmid = $cmid;
    $record->userid = $USER->id;
    $record->timecreated = time();
    $DB->insert_record('local_course_ai_video_views', $record);
    */

    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to mark complete (update_state returned false)']);
    }
} catch (Exception $ex) {
    echo json_encode(['success' => false, 'error' => $ex->getMessage()]);
}
exit;
