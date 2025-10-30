<?php
require_once('../../config.php');
require_login();

$id = required_param('id', PARAM_INT); // Course module ID

// Get course module and H5P record
$cm = get_coursemodule_from_id('h5pactivity', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$h5p = $DB->get_record('h5pactivity', ['id' => $cm->instance], '*', MUST_EXIST);

// 🔍 Fetch the actual stored H5P file from file storage
$fs = get_file_storage();
$file = $fs->get_area_files(
    $context->id,
    'mod_h5pactivity',
    'package',
    0,
    'itemid, filepath, filename',
    false
);

// Handle missing file
if (!$file) {
    echo json_encode([
        'status' => 'error',
        'message' => 'H5P file not found in storage.'
    ]);
    exit;
}

// Get the first file (the actual .h5p package)
$file = reset($file);

// ✅ Build the pluginfile URL correctly
$pluginfileurl = moodle_url::make_pluginfile_url(
    $file->get_contextid(),
    $file->get_component(),
    $file->get_filearea(),
    $file->get_itemid(),
    $file->get_filepath(),
    $file->get_filename()
)->out(false);

// ✅ Build the proper embed URL that Moodle expects
$embedurl = $CFG->wwwroot . '/h5p/embed.php?url=' . urlencode($pluginfileurl) .
             '&preventredirect=1&component=mod_h5pactivity';

$response = [
    'status' => 'success',
    'h5pname' => format_string($h5p->name),
    'embedurl' => $embedurl,
    'openinnewtab' => 0 // Set to 1 if you want new tab launch
];

header('Content-Type: application/json');
echo json_encode($response);
exit;
