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

$cm      = get_coursemodule_from_id('goone', $cmid, 0, false, MUST_EXIST);
$course  = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_capability('mod/goone:view', $context);

complete_user_login($USER);

// ---- Hand off to the real, unmodified goone view page ----
// win=1 tells view.php to use the 'embedded' page layout — strips the
// theme header/nav/footer/sidebar, leaving just the GO1 activity content.
// This is the exact same flag Moodle's own "open in new window" option uses.
chdir($CFG->dirroot . '/mod/goone');
$_GET['id']  = $cmid;
$_GET['win'] = 1;
require($CFG->dirroot . '/mod/goone/view.php');