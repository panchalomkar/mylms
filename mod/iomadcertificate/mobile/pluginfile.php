<?php
define('AJAX_SCRIPT', true);
define('NO_MOODLE_COOKIES', true);

require_once('../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/pdflib.php');   // <-- ADDED: defines class pdf extends TCPDF
require_once($CFG->dirroot . '/webservice/lib.php');
require_once($CFG->dirroot . '/mod/iomadcertificate/lib.php');
require_once($CFG->dirroot . '/mod/iomadcertificate/locallib.php');

header('Access-Control-Allow-Origin: *');

$token   = required_param('token', PARAM_ALPHANUM);
$issueid = required_param('issueid', PARAM_INT);

$webservicelib = new webservice();
$authenticationinfo = $webservicelib->authenticate_user($token); // sets $USER

$enabledfiledownload = (int) ($authenticationinfo['service']->downloadfiles);
if (empty($enabledfiledownload)) {
    throw new webservice_access_exception('Web service file downloading must be enabled in external service settings');
}

$certrecord       = $DB->get_record('iomadcertificate_issues', ['id' => $issueid], '*', MUST_EXIST);
$iomadcertificate = $DB->get_record('iomadcertificate', ['id' => $certrecord->iomadcertificateid], '*', MUST_EXIST);
$cm      = get_coursemodule_from_instance('iomadcertificate', $iomadcertificate->id, 0, false, MUST_EXIST);
$course  = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_capability('mod/iomadcertificate:view', $context);

$canmanageiomadcertificate = has_capability('mod/iomadcertificate:manage', $context);
if ($USER->id != $certrecord->userid && !$canmanageiomadcertificate) {
    header('HTTP/1.1 403 Forbidden');
    die('You do not have permission to view this certificate.');
}

if ($iomadcertificate->requiredtime && !$canmanageiomadcertificate) {
    if (iomadcertificate_get_course_time($course->id) < ($iomadcertificate->requiredtime * 60)) {
        header('HTTP/1.1 403 Forbidden');
        die('Required course time not yet met.');
    }
}

$certuser = $DB->get_record('user', ['id' => $certrecord->userid], '*', MUST_EXIST);

require("$CFG->dirroot/mod/iomadcertificate/type/$iomadcertificate->iomadcertificatetype/certificate.php");

$filename     = iomadcertificate_get_iomadcertificate_filename($iomadcertificate, $cm, $course) . '.pdf';
$filecontents = $pdf->Output('', 'S');
send_file($filecontents, $filename, 0, 0, true, true, 'application/pdf');