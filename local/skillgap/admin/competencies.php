<?php
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

require_login();
require_capability('local/skillgap:admin', context_system::instance());

$deptid  = optional_param('deptid',  0, PARAM_INT);
$desigid = optional_param('desigid', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/skillgap/admin/competencies.php', ['deptid' => $deptid, 'desigid' => $desigid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('competencies', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('pluginname', 'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add(get_string('competencies', 'local_skillgap'));

$PAGE->requires->js_call_amd('local_skillgap/skillgap', 'init');

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
if ($action && confirm_sesskey()) {
    if ($action === 'save_comp') {
        $name        = required_param('name', PARAM_TEXT);
        $description = optional_param('description', '', PARAM_TEXT);
        $editid      = optional_param('editid', 0, PARAM_INT);
        if ($editid) {
            $DB->update_record('local_skillgap_comp',
                ['id' => $editid, 'name' => $name, 'description' => $description,
                 'timemodified' => time(), 'usermodified' => $USER->id]);
        } else {
            $DB->insert_record('local_skillgap_comp',
                ['deptid' => $deptid, 'desigid' => $desigid, 'name' => $name, 'description' => $description,
                 'timecreated' => time(), 'timemodified' => time(), 'usermodified' => $USER->id]);
        }
        redirect(new moodle_url('/local/skillgap/admin/competencies.php', ['deptid' => $deptid, 'desigid' => $desigid]),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'delete_comp') {
        $id = required_param('id', PARAM_INT);
        $DB->delete_records('local_skillgap_question',   ['compid' => $id]);
        $DB->delete_records('local_skillgap_subcomp',    ['compid' => $id]);
        $DB->delete_records('local_skillgap_comp_course',['compid' => $id]);
        $DB->delete_records('local_skillgap_comp',       ['id'     => $id]);
        redirect(new moodle_url('/local/skillgap/admin/competencies.php', ['deptid' => $deptid, 'desigid' => $desigid]),
            get_string('delete_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'save_sub') {
        $compid   = required_param('compid',   PARAM_INT);
        $subname  = required_param('subname',  PARAM_TEXT);
        $psid     = optional_param('parentsubid', null, PARAM_INT) ?: null;
        $DB->insert_record('local_skillgap_subcomp',
            ['compid' => $compid, 'name' => $subname, 'parentsubid' => $psid, 'timecreated' => time()]);
        redirect(new moodle_url('/local/skillgap/admin/competencies.php', ['deptid' => $deptid, 'desigid' => $desigid]),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'update_sub') {
        $subid   = required_param('subid',   PARAM_INT);
        $subname = required_param('subname', PARAM_TEXT);
        $DB->update_record('local_skillgap_subcomp',
            ['id' => $subid, 'name' => $subname]);
        redirect(new moodle_url('/local/skillgap/admin/competencies.php', ['deptid' => $deptid, 'desigid' => $desigid]),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'delete_sub') {
        $subid = required_param('subid', PARAM_INT);
        // Delete children (sub-sub) first.
        $DB->delete_records('local_skillgap_subcomp', ['parentsubid' => $subid]);
        $DB->delete_records('local_skillgap_subcomp', ['id' => $subid]);
        redirect(new moodle_url('/local/skillgap/admin/competencies.php', ['deptid' => $deptid, 'desigid' => $desigid]),
            get_string('delete_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'map_subcomp_course') {
        $subcompid = required_param('subcompid', PARAM_INT);
        $courseid  = required_param('courseid',  PARAM_INT);
        if (!$DB->record_exists('local_skillgap_subcomp_course', ['subcompid' => $subcompid, 'courseid' => $courseid])) {
            $DB->insert_record('local_skillgap_subcomp_course', ['subcompid' => $subcompid, 'courseid' => $courseid]);
        }
        redirect(new moodle_url('/local/skillgap/admin/competencies.php', ['deptid' => $deptid, 'desigid' => $desigid]),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'unmap_subcomp_course') {
        $subcompid = required_param('subcompid', PARAM_INT);
        $courseid  = required_param('courseid',  PARAM_INT);
        $DB->delete_records('local_skillgap_subcomp_course', ['subcompid' => $subcompid, 'courseid' => $courseid]);
        redirect(new moodle_url('/local/skillgap/admin/competencies.php', ['deptid' => $deptid, 'desigid' => $desigid]),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'map_course') {
        $compid   = required_param('compid',   PARAM_INT);
        $courseid = required_param('courseid', PARAM_INT);
        if (!$DB->record_exists('local_skillgap_comp_course', ['compid' => $compid, 'courseid' => $courseid])) {
            $DB->insert_record('local_skillgap_comp_course', ['compid' => $compid, 'courseid' => $courseid]);
        }
        redirect(new moodle_url('/local/skillgap/admin/competencies.php', ['deptid' => $deptid, 'desigid' => $desigid]),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'unmap_course') {
        $compid   = required_param('compid',   PARAM_INT);
        $courseid = required_param('courseid', PARAM_INT);
        $DB->delete_records('local_skillgap_comp_course', ['compid' => $compid, 'courseid' => $courseid]);
        redirect(new moodle_url('/local/skillgap/admin/competencies.php', ['deptid' => $deptid, 'desigid' => $desigid]),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'upload_csv_sub') {
        $compid  = required_param('compid', PARAM_INT);
        $csvfile = $_FILES['csvfile'] ?? null;
        if ($csvfile && $csvfile['error'] === UPLOAD_ERR_OK) {
            $rows  = array_map('str_getcsv', file($csvfile['tmp_name']));
            $count = 0;
            array_shift($rows);
            foreach ($rows as $row) {
                if (empty(trim($row[0] ?? ''))) { continue; }
                $subname    = clean_param(trim($row[0]), PARAM_TEXT);
                $subsubname = isset($row[1]) ? clean_param(trim($row[1]), PARAM_TEXT) : '';
                $subid      = null;
                if (!$DB->record_exists('local_skillgap_subcomp', ['compid' => $compid, 'name' => $subname])) {
                    $subid = $DB->insert_record('local_skillgap_subcomp',
                        ['compid' => $compid, 'name' => $subname, 'parentsubid' => null, 'timecreated' => time()]);
                    $count++;
                } else {
                    $subid = $DB->get_field('local_skillgap_subcomp', 'id', ['compid' => $compid, 'name' => $subname]);
                }
                if ($subsubname && $subid && !$DB->record_exists('local_skillgap_subcomp', ['compid' => $compid, 'name' => $subsubname])) {
                    $DB->insert_record('local_skillgap_subcomp',
                        ['compid' => $compid, 'name' => $subsubname, 'parentsubid' => $subid, 'timecreated' => time()]);
                    $count++;
                }
            }
            redirect(new moodle_url('/local/skillgap/admin/competencies.php', ['deptid' => $deptid, 'desigid' => $desigid]),
                get_string('csv_import_success', 'local_skillgap', $count), null, \core\output\notification::NOTIFY_SUCCESS);
        }
    }
}

$data     = \local_skillgap\local\helper::competencies_data($deptid, $desigid);
$renderer = $PAGE->get_renderer('local_skillgap');

echo $OUTPUT->header();
echo $renderer->render_competencies($data);
echo $OUTPUT->footer();
