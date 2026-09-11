<?php
/**
 * Team Analytics Visibility Mapping.
 * Allows admin to configure which designation can view team analytics of which designation.
 * Route: /local/skillgap/admin/analytics_visibility.php
 */
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

require_login();
require_capability('local/skillgap:admin', context_system::instance());

$PAGE->set_url(new moodle_url('/local/skillgap/admin/analytics_visibility.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Team Analytics Visibility Mapping');
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('pluginname', 'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add('Designations', new moodle_url('/local/skillgap/admin/designations.php'));
$PAGE->navbar->add('Team Analytics Visibility');

$action = optional_param('action', '', PARAM_ALPHANUMEXT);

if ($action && confirm_sesskey()) {
    if ($action === 'add_mapping') {
        $viewer_desig   = required_param('viewer_desig',   PARAM_TEXT);
        $viewer_dept    = required_param('viewer_dept',    PARAM_TEXT);
        // targets is now a JSON array of "desig||dept" strings
        $targets_json   = optional_param('targets_json',  '', PARAM_RAW);
        $targets        = json_decode($targets_json, true) ?: [];

        $added = 0;
        foreach ($targets as $target_str) {
            $parts        = explode('||', $target_str, 2);
            $target_desig = trim($parts[0] ?? '');
            $target_dept  = trim($parts[1] ?? '');
            if (empty($target_desig) || empty($target_dept)) { continue; }
            // Prevent duplicate mapping.
            if ($DB->record_exists('local_skillgap_analytics_vis', [
                'viewer_desig' => $viewer_desig,
                'viewer_dept'  => $viewer_dept,
                'target_desig' => $target_desig,
                'target_dept'  => $target_dept,
            ])) { continue; }

            $DB->insert_record('local_skillgap_analytics_vis', [
                'viewer_desig' => $viewer_desig,
                'viewer_dept'  => $viewer_dept,
                'target_desig' => $target_desig,
                'target_dept'  => $target_dept,
                'timecreated'  => time(),
                'createdby'    => $USER->id,
            ]);
            $added++;
        }
        redirect(new moodle_url('/local/skillgap/admin/analytics_visibility.php'),
            $added . ' visibility mapping(s) added.', null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'delete_mapping') {
        $id = required_param('id', PARAM_INT);
        $DB->delete_records('local_skillgap_analytics_vis', ['id' => $id]);
        redirect(new moodle_url('/local/skillgap/admin/analytics_visibility.php'),
            'Mapping removed.', null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Build data.
// Get all unique designations from desig_hierarchy (not deleted).
$all_desigs = $DB->get_records_sql(
    "SELECT dh.designation_name, d.name AS dept_name
       FROM {local_skillgap_desig_hierarchy} dh
       JOIN {local_skillgap_dept} d ON d.name = dh.department
      WHERE (dh.deleted IS NULL OR dh.deleted = 0)
      ORDER BY d.name ASC, dh.designation_name ASC"
);

$desig_opts = [];
foreach ($all_desigs as $dh) {
    $desig_opts[] = [
        'value' => $dh->designation_name . '||' . $dh->dept_name,
        'label' => $dh->dept_name . ' — ' . $dh->designation_name,
    ];
}

// Existing mappings.
$mappings = $DB->get_records('local_skillgap_analytics_vis', null, 'viewer_dept ASC, viewer_desig ASC');
$mapping_rows = [];
foreach ($mappings as $m) {
    $mapping_rows[] = [
        'id'           => $m->id,
        'viewer_desig' => $m->viewer_desig,
        'viewer_dept'  => $m->viewer_dept,
        'target_desig' => $m->target_desig,
        'target_dept'  => $m->target_dept,
        'sesskey'      => sesskey(),
    ];
}

$data = [
    'desig_opts'   => $desig_opts,
    'mapping_rows' => $mapping_rows,
    'has_mappings' => !empty($mapping_rows),
    'wwwroot'      => $CFG->wwwroot,
    'sesskey'      => sesskey(),
];

$renderer = $PAGE->get_renderer('local_skillgap');
echo $OUTPUT->header();
echo $renderer->render_from_template('local_skillgap/analytics_visibility', $data);
echo $OUTPUT->footer();
