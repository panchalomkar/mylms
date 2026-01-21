<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

$action = required_param('action', PARAM_TEXT);
$data   = optional_param('data', '', PARAM_RAW);

$secret = get_config('local_edwiserreports', 'secret');
$lang   = current_language();

$url = $CFG->wwwroot . '/local/edwiserreports/request_handler.php';

$postdata = [
    'action' => $action,
    'secret' => $secret,
    'lang'   => $lang,
    'data'   => $data
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

$response = curl_exec($ch);
curl_close($ch);

header('Content-Type: application/json');
echo $response;
