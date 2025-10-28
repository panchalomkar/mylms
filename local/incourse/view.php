<?php
require_once(__DIR__ . '/../../config.php');
$cmid = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
require_login($cm->course, false, $cm);

$modulecontext = context_module::instance($cm->id);
$PAGE->set_context($modulecontext);
$PAGE->set_url(new moodle_url('/local/incourse/view.php', ['id' => $cmid]));

ob_start();
include($CFG->dirroot . '/mod/' . $cm->modname . '/view.php');
$content = ob_get_clean();

// Return only the main content (for AJAX)
if (defined('AJAX_SCRIPT') || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo $content;
    exit;
}
echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();
