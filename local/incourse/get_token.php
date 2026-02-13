<?php
require_once('../../config.php');
require_once('lib.php');

global $CFG, $DB, $OUTPUT, $PAGE, $USER;

require_once($CFG->dirroot.'/mod/scorm/locallib.php');
require_once($CFG->dirroot.'/mod/scorm/datamodels/scorm_12lib.php');

$cmid = required_param('id', PARAM_INT);
$newwin = optional_param('win', 0, PARAM_INT) == 1;

if (!$cm = get_coursemodule_from_id('goone', $cmid, 0, true, MUST_EXIST)) {
    throw new moodle_exception(get_string('cmidincorrect', 'goone'));
}
if (!$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST)) {
    throw new moodle_exception(get_string('courseincorrect', 'goone'));
}
if (!$goone = $DB->get_record('goone', ['id' => $cm->instance], '*', MUST_EXIST)) {
    throw new moodle_exception(get_string('cmincorrect', 'goone'));
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/goone:view', $context);

$PAGE->set_url('/mod/goone/view.php', ['id' => $cm->id]);
$PAGE->set_title($goone->name);
$PAGE->requires->js_call_amd('mod_goone/viewer', 'init');

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$exiturl = course_get_url($course, $cm->sectionnum);
$strexit = get_string('exitactivity', 'scorm');
$exitlink = html_writer::link($exiturl, $strexit, ['title' => $strexit, 'class' => 'btn btn-default']);
$PAGE->set_button($exitlink);

if ($newwin) {
    $PAGE->set_pagelayout('embedded');
}
$isnewwin = $DB->get_field('goone', 'popup', ['id' => $goone->id]);
if ($isnewwin && !$newwin) {
    echo $OUTPUT->header();
    echo get_string('opennewwin', 'goone');
    $urltogo = new moodle_url('/mod/goone/view.php', ['id' => $cm->id]);
    $PAGE->requires->js_call_amd('mod_goone/viewer', 'newwindow', [$urltogo->__toString()]);
    echo $OUTPUT->footer();
    return;
}

// Load SCORM JS libraries
$PAGE->requires->js(new moodle_url('/lib/cookies.js'), true);
$PAGE->requires->js(new moodle_url('/mod/scorm/module.js'), true);
$PAGE->requires->js(new moodle_url('/mod/scorm/request.js'), true);

echo $OUTPUT->header();

// Inject SCORM datamodel
$data = ['datamodel' => goone_inject_datamodel()];
echo $OUTPUT->render_from_template('mod_goone/datamodel', $data);

// Initialize SCORM API if session state exists
$sstate = goone_session_state($goone->id, $cmid);
if (!empty($sstate)) {
    $PAGE->requires->js_init_call(
        'M.scorm_api.init', [
            $sstate->def,
            $sstate->cmiobj,
            $sstate->cmiint,
            $sstate->cmistring256,
            $sstate->cmistring4096,
            false, "0", "0",
            $CFG->wwwroot,
            sesskey(),
            "6",
            "1",
            $sstate->cmistate,
            $cmid,
            "GO1",
            false,
            true,
            "3"
        ]
    );
}

// ------------------- Generate Dynamic Go1 Token -------------------
$api_key = '309ht93hsggvc10ik7c25tfimhr'; // your Go1 API key
$redirect_url = $CFG->wwwroot . '/course/view.php?id=' . $course->id;

$payload = [
    'user_email'   => $USER->email,
    'first_name'   => $USER->firstname,
    'last_name'    => $USER->lastname,
    'activity_id'  => $goone->loid,
    'redirect_url' => $redirect_url
];

$ch = curl_init("https://api.mygo1.com/v1/oneTimeToken");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$api_key}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$token = null;
if ($http_code === 200 && $result) {
    $response = json_decode($result, true);
    $token = $response['token'] ?? null;
}

if (!$token) {
    throw new moodle_exception('Could not generate Go1 token');
}

// Render Go1 iframe dynamically
$data = [
    'token' => $token,
    'loid'  => $goone->loid,
    'url'   => "https://rap.mygo1.com/play/{$goone->loid}?oneTimeToken={$token}&redirectUrl=" . urlencode($redirect_url)
];
// echo $OUTPUT->render_from_template('mod_goone/view', $data);

// echo $OUTPUT->footer();
header('Content-Type: application/json');

$redirect_url = $CFG->wwwroot . '/course/view.php?id=' . $course->id;

$result = [
    'success' => $token ? true : false,
    'message' => $token ? 'Token generated' : 'Failed to generate Go1 token',
    'url'     => $token ? "https://rap.mygo1.com/play/{$goone->loid}?oneTimeToken={$token}&redirectUrl=" . urlencode($redirect_url) : ''
];

echo json_encode($result);
exit;
