<?php
require_once(__DIR__ . '/../../config.php');

$cmid = required_param('cmid', PARAM_INT);
require_login();

global $DB;

// Get course module
$cm = get_coursemodule_from_id('supervideo', $cmid, 0, false, MUST_EXIST);

// Get supervideo record
$sv = $DB->get_record('supervideo', ['id' => $cm->instance], '*', MUST_EXIST);

$videoUrl = $sv->videourl; // your stored URL
$videoId = '';

if (preg_match('/(?:youtu\.be\/|v=)([\w-]+)/', $videoUrl, $matches)) {
    $videoId = $matches[1]; // extract video ID
}

// Return JSON
echo json_encode([
    'videourl' => $videoId,
    'autoplay' => (int)$sv->autoplay,
    'showcontrols' => (int)$sv->showcontrols,
]);
exit;

exit;
