<?php
require_once('../../../config.php');
$courseid = required_param('courseid', PARAM_INT);
$cohortid = optional_param('cohortid', 0, PARAM_INT);

$block = new \local_edwiserreports\blocks\courseprogressblock();
$params = (object)[
    'course' => $courseid,
    'cohort' => $cohortid,
    'group'  => 0,
    'tabledata' => false
];

$response = $block->get_data($params);

// Return JSON
echo json_encode([
    'average' => round($response->average,1),
    'totallearners' => $response->totallearners,
    'distribution' => $response->distribution
]);
exit;
