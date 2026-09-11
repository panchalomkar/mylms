<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir  . '/filelib.php');
require_once($CFG->dirroot . '/webservice/lib.php');

$token = required_param('token', PARAM_ALPHANUM);
$cmid  = required_param('id', PARAM_INT);

$webservicelib      = new webservice();
$authenticationinfo = $webservicelib->authenticate_user($token);

$enabledfiledownload = (int)($authenticationinfo['service']->downloadfiles);
if (empty($enabledfiledownload)) {
    http_response_code(403);
    die('Web service file downloading must be enabled in external service settings.');
}

$cm      = get_coursemodule_from_id('airoleplay', $cmid, 0, false, MUST_EXIST);
$course  = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_capability('mod/airoleplay:view', $context);

// Establishes a genuine Moodle session for this user — same mechanism
// proven working for mod/goone/mobile/pluginfile.php. Avoids Moodle
// core's "only available via mobile app" restriction on autologin keys
// entirely, since we're not using that API at all.
complete_user_login($USER);

// Hand off to the real, unmodified airoleplay view page.
// win=1 strips the theme chrome if the plugin supports it (same
// pattern used for goone) — remove if airoleplay doesn't check this param.
chdir($CFG->dirroot . '/mod/airoleplay');
$_GET['id']  = $cmid;
$_GET['win'] = 1;
require($CFG->dirroot . '/mod/airoleplay/view.php');