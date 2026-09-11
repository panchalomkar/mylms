<?php
/**
 * Competency Report — per-competency user list (Image 2).
 * Route: /local/skillgap/admin/analytics_report.php?compid=X
 */
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

require_login();
require_capability('local/skillgap:admin', context_system::instance());

$compid = required_param('compid', PARAM_INT);
$export = optional_param('export', '', PARAM_ALPHA);

// CSV export.
if ($export === 'csv') {
    $data = \local_skillgap\local\helper::analytics_report_data($compid);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_comp_' . $compid . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['#', 'User', 'Email', 'Overall Score %', 'Status']);
    foreach ($data['user_rows'] as $r) {
        fputcsv($out, [$r['num'], $r['fullname'], $r['email'], $r['score_pct'] . '%', $r['status_label']]);
    }
    fclose($out);
    exit;
}

$PAGE->set_url(new moodle_url('/local/skillgap/admin/analytics_report.php', ['compid' => $compid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_skillgap') . ' - ' . get_string('analytics_report_title', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('pluginname', 'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add(get_string('competency_analytics', 'local_skillgap'), new moodle_url('/local/skillgap/admin/analytics.php'));
$PAGE->navbar->add(get_string('reports', 'local_skillgap'));

$data     = \local_skillgap\local\helper::analytics_report_data($compid);
$renderer = $PAGE->get_renderer('local_skillgap');

echo $OUTPUT->header();
echo $renderer->render_from_template('local_skillgap/analytics_report', $data);
echo $OUTPUT->footer();
