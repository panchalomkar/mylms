<?php
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

require_login();
require_capability('local/skillgap:admin', context_system::instance());

$PAGE->set_url(new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_skillgap') . ' — ' . get_string('admin_dashboard', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('pluginname', 'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add(get_string('admin_dashboard', 'local_skillgap'));

$PAGE->requires->js_call_amd('local_skillgap/skillgap', 'init');

$data     = \local_skillgap\local\helper::admin_dashboard_data();
$renderer = $PAGE->get_renderer('local_skillgap');

echo $OUTPUT->header();
echo $renderer->render_admin_dashboard($data);
echo $OUTPUT->footer();
