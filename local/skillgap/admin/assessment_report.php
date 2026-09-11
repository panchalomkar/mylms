<?php
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

require_login();
require_capability('local/skillgap:admin', context_system::instance());

global $PAGE, $DB, $CFG;

$assessmentid = required_param('assessmentid', PARAM_INT);

$PAGE->set_url(new moodle_url('/local/skillgap/admin/assessment_report.php', ['assessmentid' => $assessmentid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_skillgap') . ' - Assessment Report');
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('pluginname', 'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add(get_string('assessments', 'local_skillgap'), new moodle_url('/local/skillgap/admin/assessments.php'));
$PAGE->navbar->add('Competency Report');

$data     = \local_skillgap\local\helper::assessment_competency_report($assessmentid);
$renderer = $PAGE->get_renderer('local_skillgap');

echo $OUTPUT->header();
echo $renderer->render_assessment_report($data);
echo $OUTPUT->footer();
