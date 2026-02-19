<?php
require_once('../../../config.php');
require_login();

use local_edwiserreports\blocks\courseprogressblock;

header('Content-Type: application/json');

$courseid = required_param('courseid', PARAM_INT);

$block = new courseprogressblock();

$params = (object)[
    'course' => $courseid,
    'cohort' => 0,
    'group'  => 0
];

$response = $block->get_data($params);

if (empty($response->data)) {
    echo json_encode(['success' => false]);
    exit;
}

$ranges = [
    ['label' => '0–20%',   'range' => '0to20'],
    ['label' => '21–40%',  'range' => '21to40'],
    ['label' => '41–60%',  'range' => '41to60'],
    ['label' => '61–80%',  'range' => '61to80'],
    ['label' => '81–100%', 'range' => '81to100']
];

$distribution = [];
foreach ($ranges as $i => $range) {
    $distribution[] = [
        'label' => $range['label'],
        'value' => (int)($response->data[$i] ?? 0),
        'range' => $range['range']
    ];
}

echo json_encode([
    'success' => true,
    'average' => round($response->average, 1),
    'totallearners' => array_sum($response->data),
    'distribution' => $distribution
]);
