<?php


require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot .'/local/enroll_by_profile/lib.php');

global $PAGE,$USER,$OUTPUT,$CFG;

require_login();
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');

$PAGE->requires->strings_for_js(
	array(
		'error0',
		'error1',
		'error2',
		'error3',
		'error4',
		'confirm_message_delete_rule' ,
		'content_title_contain' ,
		'content_title_equal_to' ,
		'content_title_between' 
		),
	'local_enroll_by_profile'
);
 

$PAGE->requires->css(new moodle_url('/local/enroll_by_profile/styles.css')); 

$PAGE->set_url('/local/enroll_by_profile/index.php');
$PAGE->set_title(get_string('page_title','local_enroll_by_profile'));

$PAGE->set_pagelayout('standard');

$reporturl = 'Home';
$PAGE->navbar->add($reporturl, new moodle_url('/my'));
$PAGE->navbar->add(get_string('page_title','local_enroll_by_profile'));

$context = context_system::instance();

require_capability('local/enroll_by_profile:view', $context);

if (!has_capability('local/enroll_by_profile:view', $context)) {
    print_error('enroll_by_profile','cantaccess','');
}

$PAGE->requires->js_call_amd('local_enroll_by_profile/script', 'init');

$renderable = new \local_enroll_by_profile\output\enrollbyprofile();
$renderer = $PAGE->get_renderer('local_enroll_by_profile');
$content = $renderer->render($renderable);

echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();