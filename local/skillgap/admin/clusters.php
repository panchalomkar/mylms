<?php
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');
require_login();
require_capability('local/skillgap:admin', context_system::instance());
$PAGE->set_url(new moodle_url('/local/skillgap/admin/clusters.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('clusters', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('pluginname', 'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add(get_string('clusters', 'local_skillgap'));
$PAGE->requires->js_call_amd('local_skillgap/skillgap', 'init');
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
if ($action && confirm_sesskey()) {
    if ($action === 'save') {
        $name    = required_param('name', PARAM_TEXT);
        $desc    = optional_param('description', '', PARAM_TEXT);
        $compids = optional_param('compids', '', PARAM_SEQUENCE);
        $clid    = $DB->insert_record('local_skillgap_cluster',
            ['name' => $name, 'description' => $desc, 'timecreated' => time(), 'usermodified' => $USER->id]);
        foreach (array_filter(array_map('intval', explode(',', $compids))) as $cid) {
            $DB->insert_record('local_skillgap_cluster_comp', ['clusterid' => $clid, 'compid' => $cid]);
        }
        redirect(new moodle_url('/local/skillgap/admin/clusters.php'),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'delete') {
        $id = required_param('id', PARAM_INT);
        $DB->delete_records('local_skillgap_cluster_comp', ['clusterid' => $id]);
        $DB->delete_records('local_skillgap_cluster',      ['id'        => $id]);
        redirect(new moodle_url('/local/skillgap/admin/clusters.php'),
            get_string('delete_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}
$data     = \local_skillgap\local\helper::clusters_data();
$renderer = $PAGE->get_renderer('local_skillgap');
echo $OUTPUT->header();
echo $renderer->render_clusters($data);
echo $OUTPUT->footer();
