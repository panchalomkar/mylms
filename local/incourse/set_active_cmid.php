<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', '', PARAM_INT);

if (!isset($_SESSION['incourse_active_cmid'])) {
    $_SESSION['incourse_active_cmid'] = [];
}

if ($cmid) {
    // Save
    $_SESSION['incourse_active_cmid'][$courseid] = $cmid;
    echo json_encode(['status' => 'saved', 'cmid' => $cmid]);
} else {
    // Get
    $saved = $_SESSION['incourse_active_cmid'][$courseid] ?? null;
    echo json_encode(['status' => 'ok', 'cmid' => $saved]);
}