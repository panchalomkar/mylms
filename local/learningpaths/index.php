<?php
require_once("../../config.php");

// Security Validations.
require_login();

// Validate capabilities.
$learningpathsmanager = has_capability('local/learningpaths:managealllearningpaths', context_system::instance());
$learningpathscompanymanager = has_capability('local/learningpaths:managecompanylearningpaths', context_system::instance());
if (!$learningpathsmanager && !$learningpathscompanymanager) {
    throw new moodle_exception('access_denied','local_learningpaths');
}
global $PAGE, $CFG, $OUTPUT;

// Require learningpath class.
require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPath.php");
require_once("{$CFG->dirroot}/local/learningpaths/lib.php");
// Learning path object definition and check forms submit to create new learningpaths.


// Page configurations.
$PAGE->set_title(get_string('pluginname', 'local_learningpaths'));
$PAGE->set_heading(get_string('pluginname', 'local_learningpaths'));
$PAGE->navbar->add(get_string('pluginname', 'local_learningpaths'), '/local/learningpaths/index.php');
// Load additional resources.
$PAGE->requires->css(new moodle_url("/local/learningpaths/css/styles.css"));
$PAGE->requires->js_call_amd('local_learningpaths/save_botton', 'init');
// Load the visual redesign layer (Moodle 5.0 UI refresh - presentation only, see custom-ui/README.md).
$PAGE->requires->css(new moodle_url("/local/learningpaths/custom-ui/css/learningpaths-redesign.css"));
$PAGE->requires->js(new moodle_url("/local/learningpaths/custom-ui/js/learningpaths-enhance.js"), true);
$learningpath = new LearningPath();
$learningpath->check_forms_submit(optional_param('form', "", PARAM_TEXT));
// Show header.
echo $OUTPUT->header();
$blinker = get_config('local_trial', 'local_trial_enable_blinker');
$templatecontext = array(
  'site_url' => $CFG->wwwroot,
  'learningpaths_view' => learningpaths_view(),
  'blinker' => $blinker
);
echo $OUTPUT->render_from_template('local_learningpaths/indexpage', $templatecontext);

// Show footer.
echo $OUTPUT->footer();
