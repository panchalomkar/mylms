<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/scorm/locallib.php');

header('Content-Type: application/json; charset=utf-8');
require_login();

$id = required_param('id', PARAM_INT); // Course module ID
$cm = get_coursemodule_from_id('scorm', $id, 0, false, MUST_EXIST);
$scorm = $DB->get_record('scorm', ['id' => $cm->instance], '*', MUST_EXIST);

// ✅ Find first SCO with valid launch file
$sco = $DB->get_record_select('scorm_scoes', 'scorm = ? AND launch <> ?', [$scorm->id, ''], '*', IGNORE_MULTIPLE);
if (!$sco) {
    $sco = $DB->get_record('scorm_scoes', ['scorm' => $scorm->id], '*', IGNORE_MULTIPLE);
}

if (!$sco) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No SCO found for this SCORM package.'
    ]);
    exit;
}

// Default attempt number
$attempt = 1;

// ✅ Generate SCORM player URL (no Moodle layout, popup-style view)
$launchurl = new moodle_url('/mod/scorm/player.php', [
    'a' => $scorm->id,
    'scoid' => $sco->id,
    'mode' => 'normal',
    'attempt' => $attempt,
    'display' => 'popup', // ✅ Ensures no Moodle header/footer UI
]);

// ✅ Detect if SCORM is configured to open in new tab (popup = 1)
$displaynewwindow = !empty($scorm->popup) && $scorm->popup == 1;

// ✅ Return clean JSON response
echo json_encode([
    'status' => 'success',
    'launchurl' => $launchurl->out(false),
    'openinnewtab' => $displaynewwindow,
    'scormname' => format_string($scorm->name)
]);
exit;
