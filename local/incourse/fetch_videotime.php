<?php
require_once(__DIR__ . '/../../config.php');

$cmid = required_param('cmid', PARAM_INT);
require_login();

global $DB, $CFG;

// Get course module
$cm = get_coursemodule_from_id('videotime', $cmid, 0, false, MUST_EXIST);

// Get videotime instance
$videotime = $DB->get_record('videotime', ['id' => $cm->instance], '*', IGNORE_MISSING);
if (!$videotime) {
    echo json_encode([
        'videourl' => '',
        'autoplay' => 0,
        'showcontrols' => 1,
        'debug' => 'Video record not found'
    ]);
    exit;
}

// Get the file from the correct component/filearea
$fs = get_file_storage();
$context = context_module::instance($cm->id);

// ⚡ Update to match your module's actual component/filearea
$files = $fs->get_area_files(
    $context->id,
    'videotimeplugin_videojs', // correct component
    'mediafile',               // correct filearea
    0,
    'itemid, filepath, filename',
    false
);


$videofileurl = '';
if (!empty($files)) {
    $file = reset($files);
    $videofileurl = file_encode_url(
        $CFG->wwwroot . '/pluginfile.php',
        '/' . $file->get_contextid() . 
        '/' . $file->get_component() . 
        '/' . $file->get_filearea() . 
        '/' . $file->get_itemid() .   // ⚠ Add this
        $file->get_filepath() . 
        $file->get_filename(),
        false
    );
}


// Fallback to vimeo_url if no file uploaded
elseif (!empty($videotime->vimeo_url)) {
    $videofileurl = $videotime->vimeo_url;
}

echo json_encode([
    'videourl' => $videofileurl,
    'autoplay' => 0,
    'showcontrols' => 1,
    'debug' => empty($videofileurl) ? 'No files found' : 'OK'
]);
