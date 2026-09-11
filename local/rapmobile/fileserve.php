<?php
// local/rapmobile/fileserve.php
// Generic ws-token file server — bypasses broken *_pluginfile() callbacks
// in mod_supervideo / mod_videotime, same rationale as pdfjsfolder's
// mobile/pluginfile.php.

define('NO_MOODLE_COOKIES', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/webservice/lib.php');

$token       = required_param('token', PARAM_ALPHANUM);
$contextid   = required_param('contextid', PARAM_INT);
$component   = required_param('component', PARAM_COMPONENT);
$filearea    = required_param('filearea', PARAM_AREA);
$itemid      = required_param('itemid', PARAM_INT);
$filename    = required_param('filename', PARAM_FILE);
$forcedown   = optional_param('forcedownload', 0, PARAM_BOOL);

// Validate the ws token the same way core webservice/pluginfile.php does.
$webservice = new webservice();
$authuser   = $webservice->authenticate_user($token); // throws on failure

$context = context::instance_by_id($contextid, MUST_EXIST);

// Must actually be enrolled/visible in that context.
if ($context->contextlevel == CONTEXT_MODULE) {
    list($course, $cm) = get_course_and_cm_from_cmid(
        $context->instanceid
    );
    require_login($course, false, $cm, false, true);
} else {
    require_login();
}

$fs   = get_file_storage();
$file = $fs->get_file($contextid, $component, $filearea, $itemid, '/', $filename);

if (!$file || $file->is_directory()) {
    send_file_not_found();
}

send_stored_file($file, 86400, 0, $forcedown);