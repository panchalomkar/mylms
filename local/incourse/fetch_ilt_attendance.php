<?php
require_once('../../config.php');
require_login();

global $DB, $USER;

// The session ID from JS
$sessionid = required_param('cmid', PARAM_INT);

header('Content-Type: application/json');

try {
    // Get the latest signup status for this session and user
    $sql = "
        SELECT ss.statuscode
        FROM {ilt_signups} s
        JOIN {ilt_signups_status} ss
          ON ss.signupid = s.id
        WHERE s.sessionid = :sessionid
          AND s.userid = :userid
        ORDER BY ss.superceded ASC, ss.timecreated DESC
        LIMIT 1
    ";

    $status = $DB->get_record_sql($sql, [
        'sessionid' => $sessionid,
        'userid' => $USER->id
    ]);

    echo json_encode([
        'statuscode' => $status ? (int)$status->statuscode : null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error reading from database',
        'message' => $e->getMessage()
    ]);
}
