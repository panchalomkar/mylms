<?php
require_once('../../../config.php');
//@error_reporting(1023); // NOT FOR PRODUCTION SERVERS!
//@ini_set('display_errors', '1'); // NOT FOR PRODUCTION SERVERS!

global $PAGE,$USER,$CFG,$DB;

$context = context_system::instance();
if (!has_capability('block/configurable_reports:viewreports', $context)){
	print_error("badpermissions",'block_configurable_reports');
}

//admin_externalpage_setup('managereports');

$PAGE->set_url('/local/lms_reports/report_schedule/index.php');
$PAGE->requires->jquery();
$PAGE->requires->css("/blocks/configurable_reports/js/nifty/plugins/bootstrap-datepicker/bootstrap-datepicker.css");
$PAGE->requires->js_call_amd("local_lms_reports/bootstrap_wizard",'init');
$PAGE->requires->js_call_amd('local_lms_reports/bootstrap-datetimepicker', 'init');
$PAGE->requires->js_call_amd("local_lms_reports/script",'init');

$PAGE->requires->strings_for_js(array(
        'required_field_empty',
        'info_to_save_repo_schedule',
				'label_repo_schedule',
				'description_repo_schedule',
				'start_date_repo_schedule',
				'end_date_repo_schedule',
				'recipients_repo_schedule',
				'message_repo_schedule',
				'end_date_before_start_date',
				'star_date_before_today',
    ), 'local_lms_reports');


$PAGE->set_title('Schedule Reports');
$PAGE->set_pagelayout('reportnifty');

$PAGE->navbar->add(get_string('report'));

// Force user login in course (SITE or Course)
require_login();


echo $OUTPUT->header();

include_once('report_schedule_wizard.php');

echo $OUTPUT->footer();
