<?php
/**
 * Competency Analytics Dashboard.
 * Route: /local/skillgap/admin/analytics.php?compid=X
 */
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

require_login();
require_capability('local/skillgap:admin', context_system::instance());

$compid = optional_param('compid', 0, PARAM_INT);
$export = optional_param('export', '', PARAM_ALPHA);

$PAGE->set_url(new moodle_url('/local/skillgap/admin/analytics.php', ['compid' => $compid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Competency Analytics');
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('pluginname', 'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add('Competency Analytics');

// Handle CSV export.
if ($export === 'csv' && $compid) {
    $data = \local_skillgap\local\helper::competency_analytics_data($compid);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="competency_analytics_' . $compid . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['#', 'User', 'Email', 'Attempts', 'Score', 'Status']);
    foreach ($data['user_rows'] as $r) {
        fputcsv($out, [
            $r['num'], $r['fullname'], $r['email'],
            $r['attempts'], $r['score_label'], $r['status_label'],
        ]);
    }
    fclose($out);
    exit;
}

$data     = \local_skillgap\local\helper::competency_analytics_data($compid);
$renderer = $PAGE->get_renderer('local_skillgap');

echo $OUTPUT->header();
echo $renderer->render_from_template('local_skillgap/analytics', $data);
echo $OUTPUT->footer();
