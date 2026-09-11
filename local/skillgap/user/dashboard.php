<?php
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');
require_login();
require_capability('local/skillgap:viewown', context_system::instance());
$PAGE->set_url(new moodle_url('/local/skillgap/user/dashboard.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('user_dashboard', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('pluginname',     'local_skillgap'), new moodle_url('/local/skillgap/user/dashboard.php'));
$PAGE->navbar->add(get_string('user_dashboard', 'local_skillgap'));
$PAGE->requires->js_call_amd('local_skillgap/skillgap', 'init');
$data     = \local_skillgap\local\helper::user_dashboard_data($USER->id);
$renderer = $PAGE->get_renderer('local_skillgap');
echo $OUTPUT->header();
echo $renderer->render_user_dashboard($data);
echo $OUTPUT->footer();
