<?php
// =========================================================
// FINAL: mod/pdfjsfolder/mobile/pluginfile.php
// Handles multiple PDFs in a pdfjsfolder
// 
// URL patterns:
//
// 1. List all files (JSON):
//    ?token=TOKEN&id=CMID&action=list
//
// 2. Get specific file by filename:
//    ?token=TOKEN&id=CMID&filename=doc.pdf
//
// 3. Get first PDF (default):
//    ?token=TOKEN&id=CMID
//
// Created by Omkar
// =========================================================

define('AJAX_SCRIPT', true);
define('NO_MOODLE_COOKIES', true);

require_once('../../../config.php');
require_once($CFG->libdir  . '/filelib.php');
require_once($CFG->dirroot . '/webservice/lib.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// ---- Params ----
$token    = required_param('token',    PARAM_ALPHANUM);
$cmid     = required_param('id',       PARAM_INT);
$filename = optional_param('filename', '',       PARAM_RAW);  // RAW to preserve spaces/special chars
$action   = optional_param('action',   '',       PARAM_ALPHA); // 'list' to get all files as JSON
$index    = optional_param('index',    0,        PARAM_INT);  // get file by index (0=first, 1=second...)

// ---- Authenticate ----
$webservicelib      = new webservice();
$authenticationinfo = $webservicelib->authenticate_user($token);

$enabledfiledownload = (int)($authenticationinfo['service']->downloadfiles);
if (empty($enabledfiledownload)) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    die(json_encode([
        'error'   => 'filedownloadnotenabled',
        'message' => 'Enable "Can download files" on your web service',
    ]));
}

// ---- Get course module ----
$cm = get_coursemodule_from_id('pdfjsfolder', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

if (!has_capability('mod/pdfjsfolder:view', $context)) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    die(json_encode(['error' => 'nopermission', 'message' => 'No permission']));
}

$pdfjsfolder = $DB->get_record('pdfjsfolder', ['id' => $cm->instance], '*', MUST_EXIST);

// ---- Get files — try ALL possible file areas ----
$fs         = get_file_storage();
$all_files  = [];
$file_areas = ['pdfs', 'content', 'intro', 'package'];

foreach ($file_areas as $area) {
    $found = $fs->get_area_files(
        $context->id,
        'mod_pdfjsfolder',
        $area,
        false,          // all itemids
        'filepath, filename ASC',
        false           // exclude directories
    );
    foreach ($found as $f) {
        $all_files[] = $f;
    }
}

// ---- Also try from DB directly using contextid ----
if (empty($all_files)) {
    // Get from files table directly
    $dbfiles = $DB->get_records_sql(
        "SELECT * FROM {files}
         WHERE contextid = ?
           AND component = 'mod_pdfjsfolder'
           AND filename != '.'
         ORDER BY filepath, filename ASC",
        [$context->id]
    );

    foreach ($dbfiles as $dbf) {
        $file = $fs->get_file(
            $dbf->contextid,
            $dbf->component,
            $dbf->filearea,
            $dbf->itemid,
            $dbf->filepath,
            $dbf->filename
        );
        if ($file) {
            $all_files[] = $file;
        }
    }
}

if (empty($all_files)) {
    header('Content-Type: application/json');
    die(json_encode([
        'error'     => 'nofiles',
        'message'   => 'No files found',
        'contextid' => $context->id,
        'cmid'      => $cmid,
        'debug'     => 'Check mdl_files WHERE contextid=' . $context->id,
    ]));
}

// =========================================================
// ACTION: list — return JSON list of all files
// =========================================================
if ($action === 'list') {
    header('Content-Type: application/json');

    $output = [
        'cmid'       => (int)$cmid,
        'name'       => $pdfjsfolder->name,
        'totalfiles' => count($all_files),
        'files'      => [],
    ];

    $i = 0;
    foreach ($all_files as $file) {
        $fname    = $file->get_filename();
        $mimetype = $file->get_mimetype();

        // Build base URL for each file
        $base_url = $CFG->wwwroot
            . '/mod/pdfjsfolder/mobile/pluginfile.php'
            . '?token=' . $token
            . '&id='    . $cmid;

        $output['files'][] = [
            'index'       => $i,
            'filename'    => $fname,
            'filesize'    => (int)$file->get_filesize(),
            'mimetype'    => $mimetype,
            'filepath'    => $file->get_filepath(),
            'filearea'    => $file->get_filearea(),
            'ispdf'       => ($mimetype === 'application/pdf'),
            'isimage'     => (strpos($mimetype, 'image/') === 0),

            // URL to serve this specific file
            'fileurl'     => $base_url . '&filename=' . urlencode($fname),

            // URL by index
            'indexurl'    => $base_url . '&index=' . $i,

            // Webservice pluginfile URL (standard Moodle)
            'wsurl'       => \moodle_url::make_webservice_pluginfile_url(
                                $file->get_contextid(),
                                $file->get_component(),
                                $file->get_filearea(),
                                $file->get_itemid(),
                                $file->get_filepath(),
                                $fname
                             )->out(false) . '?token=' . $token,
        ];
        $i++;
    }

    die(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// =========================================================
// SERVE FILE — by filename, index, or first PDF
// =========================================================
$targetfile = null;

// Option 1: by filename
if (!empty($filename)) {
    $filename_decoded = urldecode($filename);
    foreach ($all_files as $file) {
        if ($file->get_filename() === $filename_decoded ||
            $file->get_filename() === $filename) {
            $targetfile = $file;
            break;
        }
    }
}

// Option 2: by index
if (!$targetfile && $index > 0) {
    $arr = array_values($all_files);
    if (isset($arr[$index])) {
        $targetfile = $arr[$index];
    }
}

// Option 3: first PDF
if (!$targetfile) {
    foreach ($all_files as $file) {
        if ($file->get_mimetype() === 'application/pdf') {
            $targetfile = $file;
            break;
        }
    }
}

// Option 4: any first file
if (!$targetfile) {
    $targetfile = reset($all_files);
}

if (!$targetfile) {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: application/json');
    die(json_encode(['error' => 'filenotfound', 'message' => 'File not found: ' . $filename]));
}

// ---- Serve the file ----
$mimetype = $targetfile->get_mimetype();
$fname    = $targetfile->get_filename();
$filesize = $targetfile->get_filesize();

header('Content-Type: '        . $mimetype);
header('Content-Disposition: inline; filename="' . $fname . '"');
header('Content-Length: '      . $filesize);
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');

$targetfile->readfile();
die;