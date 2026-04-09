<?php
// local/halloffame/pages/manage_categories.php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

use local_halloffame\manager;

require_login();
$context = context_system::instance();
require_capability('local/halloffame:manageawards', $context);

global $DB;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/halloffame/pages/manage_categories.php'));
$PAGE->set_title(get_string('managecategories', 'local_halloffame'));
$PAGE->set_heading(get_string('halloffame', 'local_halloffame'));
$PAGE->set_pagelayout('admin');
$PAGE->requires->css(new moodle_url('/local/halloffame/styles.css'));

$PAGE->navbar->add(get_string('halloffame', 'local_halloffame'),
    new moodle_url('/local/halloffame/pages/index.php'));
$PAGE->navbar->add(get_string('managecategories', 'local_halloffame'));

$action = optional_param('action', '', PARAM_ALPHA);
$cid    = optional_param('cid',    0,  PARAM_INT);

// ── POST: add / rename ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $name = trim(required_param('catname', PARAM_TEXT));
    $desc = trim(optional_param('catdesc', '', PARAM_TEXT));
    if ($name !== '') {
        if ($action === 'rename' && $cid) {
            manager::save_category($name, $cid, $desc);
            redirect($PAGE->url, get_string('categorysaved', 'local_halloffame'),
                null, \core\output\notification::NOTIFY_SUCCESS);
        } elseif ($action === 'add') {
            if (!$DB->record_exists('halloffame_categories', ['name' => $name])) {
                manager::save_category($name, 0, $desc);
                redirect($PAGE->url, get_string('categorysaved', 'local_halloffame'),
                    null, \core\output\notification::NOTIFY_SUCCESS);
            }
        }
    }
}

// ── GET: delete ───────────────────────────────────────────────────────────────
if ($action === 'delete' && $cid && confirm_sesskey()) {
    $DB->delete_records('halloffame_categories', ['id' => $cid]);
    redirect($PAGE->url, 'Category deleted.', null, \core\output\notification::NOTIFY_WARNING);
}

$categories = manager::get_categories();

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_halloffame/manage_categories', [
    'sesskey'    => sesskey(),
    'categories' => array_values(array_map(function($c) {
        return ['id' => $c->id, 'name' => $c->name, 'description' => $c->description ?? ''];
    }, $categories)),
    'backurl'    => (new moodle_url('/local/halloffame/pages/admin.php'))->out(false),
    'pageurl'    => (new moodle_url('/local/halloffame/pages/manage_categories.php'))->out(false),
]);
echo $OUTPUT->footer();
