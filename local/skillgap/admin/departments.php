<?php
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

require_login();
require_capability('local/skillgap:admin', context_system::instance());

$PAGE->set_url(new moodle_url('/local/skillgap/admin/departments.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('departments', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('pluginname', 'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add(get_string('departments', 'local_skillgap'));

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
if ($action && confirm_sesskey()) {
    if ($action === 'sync') {
        local_skillgap_sync_departments();
        redirect(new moodle_url('/local/skillgap/admin/departments.php'),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'delete') {
        $id = required_param('id', PARAM_INT);
        $DB->delete_records('local_skillgap_dept', ['id' => $id]);
        redirect(new moodle_url('/local/skillgap/admin/departments.php'),
            get_string('delete_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

$data     = \local_skillgap\local\helper::departments_data();
$renderer = $PAGE->get_renderer('local_skillgap');

echo $OUTPUT->header();
echo $renderer->render_departments($data);
echo $OUTPUT->footer();
