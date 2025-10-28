<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot .'/lib/blocklib.php'); 
require_once($CFG->dirroot .'/local/lms_reports/reportlib.php'); 

global $PAGE,$USER,$CFG;

//Librerias para traer los datos de los reportes para el dashboard, en la variable $charts quedan alojados los datos necesarios para hacer las graficas
require_once(dirname(__FILE__).'/locallib.php');
require_login();
$data = new report_overviewstats();

$context = context_system::instance();
if (!has_capability('block/configurable_reports:viewreports', $context, $USER->id)){
	print_error("badpermissions",'block_configurable_reports');
} 


$countries          = $data->get_countries_data();
$lang               = $data->get_lang_data();
$coursescategory    = $data->get_coursescategory_data();
$onlineusers        = $data->get_online_users();
$totalusers         = $data->get_total_users();
$registeredtoday    = $data->get_registered_today();
$top_viewed         = $data->get_top_viewed();
$top_enrolled       = $data->get_top_enrolled();
$menu               = $data->get_menu_reports();


$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');

$PAGE->requires->css(new moodle_url('/local/lms_reports/css/lms_reports.css'));
$PAGE->requires->js_call_amd("local_lms_reports/script",'myfavoritereport');
$PAGE->requires->js_call_amd("local_lms_reports/script",'searchReport');
$PAGE->set_url('/local/lms_reports/index.php');
$PAGE->set_title('Reports');
$PAGE->set_pagelayout('reportnifty');


$courseid   = 1;
$importurl  = optional_param('importurl', '', PARAM_RAW);
$reportType = optional_param('type', '', PARAM_RAW);
$my_reports = optional_param('my_reports', '', PARAM_RAW);

if (! $course = $DB->get_record("course", array( "id" =>  $courseid)) ) {
    print_error("No such course id");
}

// Force user login in course (SITE or Course)
if ($course->id == SITEID){
    require_login();
    $context = context_system::instance();
} else {
    require_login($course->id);
    $context = context_course::instance($course->id);
}
$reporturl = 'Home';
$PAGE->navbar->add($reporturl, new moodle_url('/my'));
$PAGE->navbar->add(get_string('reports','local_lms_reports'));
echo $OUTPUT->header();
//echo $OUTPUT->navbar();
$options['myfavorites']=get_string('my_favorites', 'local_lms_reports');
$params  = array('type' => 'checkbox', 'name' => 'my_reports', 'id' => 'my_reports');
if(!empty($my_reports)){
	$params['checked']='checked';
}

echo <<<JS
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
JS;

include_once('dashboard.php'); 

echo $OUTPUT->footer();