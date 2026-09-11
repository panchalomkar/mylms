<?php
/**
 * Designation hierarchy management.
 * Hierarchy is determined purely by sort_order (position 1 = top/highest rank).
 * No parent-child relationships — admins use Move Up / Move Down to define the ladder.
 */
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

use local_skillgap\local\designation_service;

require_login();
require_capability('local/skillgap:admin', context_system::instance());

$deptid = optional_param('deptid', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/skillgap/admin/designations.php', ['deptid' => $deptid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('designations', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('pluginname',   'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add(get_string('designations', 'local_skillgap'));
$PAGE->requires->js_call_amd('local_skillgap/skillgap', 'init');

$dept_name = '';
if ($deptid) {
    $dept_name = $DB->get_field('local_skillgap_dept', 'name', ['id' => $deptid]) ?: '';
    if (!$dept_name) { $deptid = 0; }
}

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
if ($action && confirm_sesskey()) {

    if ($action === 'sync' && $deptid) {
        $count = designation_service::sync_from_profiles($dept_name);
        redirect(new moodle_url('/local/skillgap/admin/designations.php', ['deptid' => $deptid]),
            "Synced {$count} new designation(s) from user profiles.",
            null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'move_up') {
        $hid  = required_param('hid', PARAM_INT);
        $flat = $deptid ? designation_service::get_hierarchy($dept_name) : [];
        $ids  = array_column($flat, 'id');
        $pos  = array_search($hid, $ids);
        if ($pos !== false && $pos > 0) {
            $swapped = $ids;
            [$swapped[$pos - 1], $swapped[$pos]] = [$swapped[$pos], $swapped[$pos - 1]];
            designation_service::reorder($swapped);
        }
        redirect(new moodle_url('/local/skillgap/admin/designations.php', ['deptid' => $deptid]),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'move_down') {
        $hid  = required_param('hid', PARAM_INT);
        $flat = $deptid ? designation_service::get_hierarchy($dept_name) : [];
        $ids  = array_column($flat, 'id');
        $pos  = array_search($hid, $ids);
        if ($pos !== false && $pos < count($ids) - 1) {
            $swapped = $ids;
            [$swapped[$pos], $swapped[$pos + 1]] = [$swapped[$pos + 1], $swapped[$pos]];
            designation_service::reorder($swapped);
        }
        redirect(new moodle_url('/local/skillgap/admin/designations.php', ['deptid' => $deptid]),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    // AJAX reorder (drag-drop from JS).
    if ($action === 'reorder') {
        $order_raw = required_param('order', PARAM_TEXT);
        $ids       = array_filter(array_map('intval', explode(',', $order_raw)));
        if (!empty($ids)) { designation_service::reorder($ids); }
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
        exit;
    }

    if ($action === 'delete_designation') {
        $hid = required_param('hid', PARAM_INT);
        $rec = $DB->get_record('local_skillgap_desig_hierarchy', ['id' => $hid]);
        if (!$rec) {
            redirect(new moodle_url('/local/skillgap/admin/designations.php', ['deptid' => $deptid]),
                'Designation not found.', null, \core\output\notification::NOTIFY_ERROR);
        }
        $desig_name = $rec->designation_name;

        // Dependency check: assessments or competencies using this designation?
        $in_assessment = $DB->count_records('local_skillgap_assessment', ['desigid' => $hid]);
        $in_comp       = $DB->count_records('local_skillgap_comp',       ['desigid' => $hid]);

        if ($in_assessment > 0 || $in_comp > 0) {
            $reason = [];
            if ($in_assessment > 0) { $reason[] = $in_assessment . ' assessment(s)'; }
            if ($in_comp > 0)       { $reason[] = $in_comp . ' competency/ies'; }
            redirect(new moodle_url('/local/skillgap/admin/designations.php', ['deptid' => $deptid]),
                'Cannot delete "' . $desig_name . '" — it is in use by: ' . implode(', ', $reason) . '.',
                null, \core\output\notification::NOTIFY_ERROR);
        }

        // Soft delete.
        $DB->set_field('local_skillgap_desig_hierarchy', 'deleted', 1, ['id' => $hid]);
        redirect(new moodle_url('/local/skillgap/admin/designations.php', ['deptid' => $deptid]),
            'Designation "' . $desig_name . '" deleted successfully.',
            null, \core\output\notification::NOTIFY_SUCCESS);
    }

} // end actions

// Build template data.
$depts     = $DB->get_records('local_skillgap_dept', null, 'name ASC', 'id,name');
$dept_opts = [['id' => '', 'name' => '-- Select Department --', 'selected' => !$deptid]];
foreach ($depts as $d) {
    $dept_opts[] = ['id' => $d->id, 'name' => $d->name, 'selected' => ($d->id == $deptid)];
}

$hierarchy_rows = [];
$profile_names  = [];
$available_names= [];

if ($deptid && $dept_name) {
    $profile_names   = designation_service::fetch_from_profiles($dept_name);
    $flat_hierarchy  = designation_service::get_hierarchy($dept_name);
    $stored_names    = array_column($flat_hierarchy, 'designation_name');
    $available_names = array_values(array_diff($profile_names, $stored_names));

    $total = count($flat_hierarchy);
    foreach ($flat_hierarchy as $idx => $h) {
        $hierarchy_rows[] = [
            'id'               => $h->id,
            'designation_name' => $h->designation_name,
            'sort_order'       => $h->sort_order,
            'position'         => $idx + 1,
            'is_top'           => $idx === 0,
            'can_move_up'      => $idx > 0,
            'can_move_down'    => $idx < $total - 1,
            'sesskey'          => sesskey(),
            'deptid'           => $deptid,
        ];
    }
}

$data = [
    'dept_options'    => $dept_opts,
    'selected_deptid' => $deptid,
    'dept_name'       => $dept_name,
    'has_dept'        => $deptid > 0,
    'hierarchy_rows'  => $hierarchy_rows,
    'has_rows'        => !empty($hierarchy_rows),
    'profile_count'   => count($profile_names),
    'profile_names'   => array_map(fn($n) => ['name' => $n], $profile_names),
    'has_unsynced'    => !empty($available_names),
    'unsynced_count'  => count($available_names),
    'available_names' => array_map(fn($n) => ['name' => $n], $available_names),
    'wwwroot'         => $CFG->wwwroot,
    'sesskey'         => sesskey(),
];

$renderer = $PAGE->get_renderer('local_skillgap');
echo $OUTPUT->header();
echo $renderer->render_designations($data);
echo $OUTPUT->footer();
