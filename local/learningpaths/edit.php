<?php
require_once("../../config.php");

// Security Validations.
require_login();
require_capability('local/learningpaths:managealllearningpaths', context_system::instance());

// Global variables.
global $PAGE, $CFG;

// Additional requirements.
require_once("lib.php");
require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPath.php");

// Learning path object definition.
$learningpath = new LearningPath(required_param('id', PARAM_INT));

$learningpathdata = $learningpath->data;
// Check learning path post data if exist.
$learningpath->check_forms_submit(optional_param('form', "", PARAM_TEXT));

// Set page information.
$PAGE->set_title(get_string('pluginname', 'local_learningpaths'));
$PAGE->set_heading(get_string('pluginname', 'local_learningpaths'));
$PAGE->requires->js_call_amd('local_learningpaths/save_botton', 'init');
// Require additional resources.
$PAGE->requires->css('/local/learningpaths/css/styles.css');
$PAGE->navbar->add(get_string('pluginname', 'local_learningpaths'), '/local/learningpaths/index.php');
//load lp properties
// Print header.
echo $OUTPUT->header();
//Print title
echo $OUTPUT->render_from_template('local_learningpaths/learningpath_edit','');
// Print form.
echo $learningpath->render_form();
 // Print footer.
echo $OUTPUT->footer();
