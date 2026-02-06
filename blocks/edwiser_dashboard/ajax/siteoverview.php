<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

use block_edwiser_dashboard\helper;

$filter   = optional_param('filter', 'last7days', PARAM_ALPHA);
$cohortid = optional_param('cohortid', 0, PARAM_INT);

$data = helper::get_site_overview($filter, $cohortid);

// Ensure numeric arrays for Chart.js
$data['trendActiveUsers'] = array_values($data['trendActiveUsers']);
$data['trendEnrollments'] = array_values($data['trendEnrollments']);
$data['trendCompletions'] = array_values($data['trendCompletions']);
$data['trendDates']       = array_values($data['trendDates']);

header('Content-Type: application/json');
echo json_encode($data);
exit;
