<?php
/**
 * Tabs Shown.
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */
require('../../config.php');
//Globalized required vars
global $CFG,$OUTPUT,$PAGE,$DB;
require_login();
$PAGE->set_pagelayout('default_plugins');
$PAGE->set_title(get_string('tenant','local_tenant_appearance'));
$PAGE->set_url('/local/tenant_appearance/tenant_appearance.php');
$PAGE->requires->css('/local/tenant_appearance/css/styles.css');
$PAGE->requires->css(new moodle_url('/local/tenant_appearance/bootstrap-colorpicker.min.css'));
$PAGE->navbar->add(get_string('pluginname', 'local_tenant_appearance'), '/local/tenant_appearance/tenant_appearance.php');

// Get all mform form here
require_once($CFG->dirroot . '/local/tenant_appearance/slideshow.php');
require_once($CFG->dirroot . '/local/tenant_appearance/logo.php');
require_once($CFG->dirroot . '/local/tenant_appearance/color.php');
require_once($CFG->dirroot . '/local/tenant_appearance/font.php');

//Return param in an array
$templatecontext=[
    'color' => $mformc->render(),
    'font' =>$mformf->render(),
    'logo' =>$mforma->render(),
    'slideshow' =>$mforms->render()
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_tenant_appearance/main',$templatecontext);
$PAGE->requires->js_call_amd('local_tenant_appearance/color', 'init');
echo  $OUTPUT->footer();

