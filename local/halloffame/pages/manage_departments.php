<?php
// local/halloffame/pages/manage_departments.php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

use local_halloffame\manager;

require_login();
$context = context_system::instance();
require_capability('local/halloffame:manageawards', $context);

global $DB;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/halloffame/pages/manage_departments.php'));
$PAGE->set_title(get_string('managedepartments', 'local_halloffame'));
$PAGE->set_heading(get_string('halloffame', 'local_halloffame'));
$PAGE->set_pagelayout('admin');
$PAGE->requires->css(new moodle_url('/local/halloffame/styles.css'));

$PAGE->navbar->add(get_string('halloffame', 'local_halloffame'),
    new moodle_url('/local/halloffame/pages/index.php'));
$PAGE->navbar->add(get_string('managedepartments', 'local_halloffame'));

$action = optional_param('action', '', PARAM_ALPHA);
$did    = optional_param('did',    0,  PARAM_INT);

// ── POST: add / rename ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $name = trim(required_param('deptname', PARAM_TEXT));
    if ($name !== '') {
        if ($action === 'rename' && $did) {
            manager::save_department($name, $did);
            redirect($PAGE->url, get_string('departmentsaved', 'local_halloffame'),
                null, \core\output\notification::NOTIFY_SUCCESS);
        } elseif ($action === 'add') {
            if (!$DB->record_exists('halloffame_departments', ['name' => $name])) {
                manager::save_department($name);
                redirect($PAGE->url, get_string('departmentsaved', 'local_halloffame'),
                    null, \core\output\notification::NOTIFY_SUCCESS);
            }
        }
    }
}

// ── GET: delete ───────────────────────────────────────────────────────────────
if ($action === 'delete' && $did && confirm_sesskey()) {
    $DB->delete_records('halloffame_departments', ['id' => $did]);
    redirect($PAGE->url, 'Department deleted.', null, \core\output\notification::NOTIFY_WARNING);
}

$departments = manager::get_departments();

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_halloffame/manage_departments', [
    'sesskey'     => sesskey(),
    'departments' => array_values(array_map(fn($d) => ['id' => $d->id, 'name' => $d->name],
                                            $departments)),
    'backurl'     => (new moodle_url('/local/halloffame/pages/admin.php'))->out(false),
    'pageurl'     => (new moodle_url('/local/halloffame/pages/manage_departments.php'))->out(false),
]);
echo $OUTPUT->footer();
