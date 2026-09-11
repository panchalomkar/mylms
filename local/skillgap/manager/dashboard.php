<?php
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');
require_login();

// Allow access if: Moodle manager capability OR has team members via designation visibility mapping.
$has_cap  = has_capability('local/skillgap:managerviewteam', context_system::instance());
$has_team = count(local_skillgap_get_team($USER->id)) > 0;

if (!$has_cap && !$has_team) {
    throw new \moodle_exception('nopermissions', 'error', '', 'view team analytics');
}

$PAGE->set_url(new moodle_url('/local/skillgap/manager/dashboard.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('manager_dashboard', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('pluginname',        'local_skillgap'), new moodle_url('/local/skillgap/manager/dashboard.php'));
$PAGE->navbar->add(get_string('manager_dashboard', 'local_skillgap'));
$PAGE->requires->js_call_amd('local_skillgap/skillgap', 'init');
$filters = [
    'deptid'  => optional_param('deptid',  0, PARAM_INT),
    'desigid' => optional_param('desigid', 0, PARAM_INT),
    'compid'  => optional_param('compid',  0, PARAM_INT),
];
$data     = \local_skillgap\local\helper::manager_dashboard_data($USER->id, $filters);
$renderer = $PAGE->get_renderer('local_skillgap');
echo $OUTPUT->header();
echo $renderer->render_manager_dashboard($data);
echo $OUTPUT->footer();
