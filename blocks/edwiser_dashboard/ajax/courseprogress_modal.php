<?php
require_once('../../../config.php');
require_login();

use local_edwiserreports\blocks\courseprogressblock;
header('Content-Type: application/json');

$courseid = required_param('course', PARAM_INT);
$range    = required_param('range', PARAM_TEXT);

if (!$courseid || !$range) {
    echo json_encode(['rows' => [], 'error' => 'Missing course or range']);
    exit;
}
$courseid = required_param('course', PARAM_INT);
$range    = required_param('range', PARAM_TEXT);

$filter = (object)[
    'course' => $courseid,
    'cohort' => 0,
    'group'  => 0
];

$block = new courseprogressblock();
$table = $block->get_modal_table($filter, $range);

echo json_encode($table);
