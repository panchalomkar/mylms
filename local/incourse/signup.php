<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$cmid = required_param('id', PARAM_INT);
$userid = $USER->id;

$results = [];

if ($DB->get_manager()->table_exists('ilt_signups') && $DB->get_manager()->table_exists('ilt_signups_status')) {
    // Fetch all signup + latest status for this user
    $sql = "
        SELECT s.id AS signupid, s.sessionid, ss.statuscode, ss.superceded
        FROM {ilt_signups} s
        JOIN {ilt_signups_status} ss ON ss.signupid = s.id
        WHERE s.userid = :userid
        AND ss.superceded = 0
    ";
    $records = $DB->get_records_sql($sql, ['userid' => $userid]);

    foreach ($records as $r) {
        $results[$r->sessionid] = (int)$r->statuscode;
    }
}

echo json_encode([
    'success' => true,
    'statuses' => $results
]);
die;
