<?php
require_once(__DIR__ . '/../../config.php');
require_login();

header('Content-Type: application/json');

global $DB, $USER;

$action = optional_param('action', 'status', PARAM_ALPHA);

// ----- CANCEL a booking -----
if ($action === 'cancel') {
    require_sesskey();

    $sessionid = required_param('sessionid', PARAM_INT);

    $deleted = $DB->delete_records('ilt_signups', [
        'userid'    => $USER->id,
        'sessionid' => $sessionid
    ]);

    echo json_encode([
        'status'  => $deleted ? 'success' : 'error',
        'message' => $deleted ? 'Booking cancelled' : 'No booking found'
    ]);
    exit;
}

// ----- GET signup status (default) -----
$cmid = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('ilt', $cmid, 0, false, MUST_EXIST);
$iltid = $cm->instance;

$sessions = $DB->get_records('ilt_sessions', ['ilt' => $iltid]);

$result = [];
foreach ($sessions as $s) {
    $signedup = $DB->record_exists('ilt_signups', [
        'userid'    => $USER->id,
        'sessionid' => $s->id
    ]);

    $result[$s->id] = $signedup;
}

echo json_encode(['status' => 'success', 'signups' => $result]);