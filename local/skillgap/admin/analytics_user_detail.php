<?php
/**
 * User Detail Report — per-user sub-competency breakdown (Image 3).
 * Route: /local/skillgap/admin/analytics_user_detail.php?compid=X&userid=Y
 */
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

require_login();
require_capability('local/skillgap:admin', context_system::instance());

$compid = required_param('compid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$PAGE->set_url(new moodle_url('/local/skillgap/admin/analytics_user_detail.php',
    ['compid' => $compid, 'userid' => $userid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_skillgap') . ' - ' . get_string('user_report', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('pluginname', 'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add(get_string('competency_analytics', 'local_skillgap'), new moodle_url('/local/skillgap/admin/analytics.php'));
$PAGE->navbar->add(get_string('reports', 'local_skillgap'), new moodle_url('/local/skillgap/admin/analytics_report.php', ['compid' => $compid]));
$PAGE->navbar->add(get_string('user_report', 'local_skillgap'));

$data     = \local_skillgap\local\helper::analytics_user_detail_data($compid, $userid);
$renderer = $PAGE->get_renderer('local_skillgap');

echo $OUTPUT->header();
echo $renderer->render_from_template('local_skillgap/analytics_user_detail', $data);
echo $OUTPUT->footer();
