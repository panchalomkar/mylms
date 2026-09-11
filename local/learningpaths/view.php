<?php
require_once("../../config.php");

// Load moodle configurations, core, learning paths functions library and learning path clasess.
require_once("lib.php");
require_once("classes/objects/LearningPath.php");

// Security Validations.
require_login();

$stringman = get_string_manager();
$strings = $stringman->load_component_strings('local_learningpaths', 'en');
$PAGE->requires->strings_for_js(array_keys($strings), 'local_learningpaths');

try {
    // Validate capabilities.
    $learningpathsmanager = has_capability('local/learningpaths:managealllearningpaths', context_system::instance());
    $learningpathscompanymanager = has_capability('local/learningpaths:managecompanylearningpaths', context_system::instance());
    if (!$learningpathsmanager && !$learningpathscompanymanager) {
        throw new moodle_exception(get_string('access_denied'));
    }

    // Required global variables from moodle.
    global $PAGE, $CFG, $OUTPUT;

    $context = context_system::instance(); // or course context if needed
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('standard');

    // If form parameter exist, then check the submit.
    $formname = optional_param('form', "", PARAM_TEXT);

    // Learning path object definition. If learningpathid was sent them use it as id.
    $learningpathid = optional_param('learningpathid', 0, PARAM_INT);
    $learningpathid = ($learningpathid > 0) ? $learningpathid : optional_param('id', 0, PARAM_INT);

    // Required by Moodle 5.0 / Iomad before header() is called.
    $PAGE->set_url('/local/learningpaths/view.php', ['id' => $learningpathid]);

    $learningpath = new LearningPath($learningpathid);

    // Check post data. This function will do save of forms data.
    if (empty(!$formname)) {
        $learningpath->check_forms_submit($formname);
    }

    $PAGE->set_title(get_string('pluginname', 'local_learningpaths'));
    $PAGE->set_heading(get_string('pluginname', 'local_learningpaths'));
    $PAGE->navbar->add(get_string('pluginname', 'local_learningpaths'), '/local/learningpaths/index.php');

    if (!empty($learningpath->data->name)) {
        $PAGE->navbar->add(htmlspecialchars_decode($learningpath->data->name));
    } else {
        $PAGE->navbar->add(get_string('add_learningpath', 'local_learningpaths'));
    }

    // Including additional css and js files.
    $PAGE->requires->css('/local/learningpaths/css/styles.css');
    $PAGE->requires->css('/local/learningpaths/css/switchery.css');
    $PAGE->requires->css(new moodle_url("{$CFG->wwwroot}/blocks/lpd/styles.css"));
    $PAGE->requires->js_call_amd('local_learningpaths/learningpaths', 'lpactions');
    $PAGE->requires->js_call_amd('local_learningpaths/save_botton', 'init');

    // Load the visual redesign layer (Moodle 5.0 UI refresh - presentation only, see custom-ui/README.md).
    $PAGE->requires->css(new moodle_url("/local/learningpaths/custom-ui/css/learningpaths-redesign.css"));
    $PAGE->requires->js(new moodle_url("/local/learningpaths/custom-ui/js/learningpaths-enhance.js"), true);

    // Print common page header and title.
    echo $OUTPUT->header();

    $templatecontext = array(
        'site_url' => $CFG->wwwroot,
        'learningpathdataname' => !empty($learningpath->data->name)
            ? htmlspecialchars_decode($learningpath->data->name)
            : get_string('add_learningpath', 'local_learningpaths'),
        'learningpathrender_navigation_tabs' => $learningpath->render_navigation_tabs(),
        'learningpathrender_tabs' => $learningpath->render_tabs($learningpath->data)
    );
    echo $OUTPUT->render_from_template('local_learningpaths/view', $templatecontext);

    // Print common page footer.
    echo $OUTPUT->footer();

} catch (Exception $e) {
    throw $e;
}