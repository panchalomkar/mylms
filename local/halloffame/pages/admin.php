<?php
// local/halloffame/pages/admin.php — v2 IOMAD-aware
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

use local_halloffame\manager;
use local_halloffame\iomad_helper;
use local_halloffame\department_helper;

require_login();
$context = context_system::instance();
require_capability('local/halloffame:manageawards', $context);

global $DB, $USER;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/halloffame/pages/admin.php'));
$PAGE->set_title(get_string('adminpanel', 'local_halloffame'));
$PAGE->set_heading(get_string('halloffame', 'local_halloffame'));
$PAGE->set_pagelayout('admin');
$PAGE->requires->css(new moodle_url('/local/halloffame/styles.css'));

$PAGE->navbar->add(get_string('halloffame', 'local_halloffame'),
    new moodle_url('/local/halloffame/pages/index.php'));
$PAGE->navbar->add(get_string('adminpanel', 'local_halloffame'));

// ── IOMAD context ─────────────────────────────────────────────────────────────
$companyid   = iomad_helper::get_current_companyid();
$companyname = iomad_helper::get_company_name($companyid);

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $userid     = required_param('userid',     PARAM_INT);
    $title      = required_param('title',      PARAM_TEXT);
    $department = optional_param('department', '', PARAM_TEXT);
    $category   = optional_param('category',   '', PARAM_TEXT);
    $month      = optional_param('month',      (int) date('n'), PARAM_INT);
    $year       = optional_param('year',       (int) date('Y'), PARAM_INT);
    $message    = optional_param('message',    '', PARAM_TEXT);

    // Security: ensure selected user is in the same company.
    manager::assert_same_company($userid);

    // Upload achiever image.
    $imageurl = '';
    if (!empty($_FILES['achieverimage']['name']) &&
        $_FILES['achieverimage']['error'] === UPLOAD_ERR_OK) {
        $err = manager::validate_upload($_FILES['achieverimage']);
        if ($err === '') {
            $itemid   = (int)($userid * 10000 + (time() % 10000));
            $imageurl = manager::store_upload($_FILES['achieverimage'], 'awards_images', $itemid);
        }
    }

    // Auto-fill department from profile field if not provided.
    if (empty($department) && $userid) {
        $department = department_helper::get_user_department($userid);
    }

    manager::create_award(compact(
        'userid', 'title', 'department', 'category', 'month', 'year', 'message'
    ) + ['image' => $imageurl, 'companyid' => $companyid]);

    redirect(
        new moodle_url('/local/halloffame/pages/index.php', ['tab' => 'awards']),
        get_string('awardsaved', 'local_halloffame'),
        null, \core\output\notification::NOTIFY_SUCCESS
    );
}

// ── Form data — all scoped to current company ─────────────────────────────────

// IOMAD-scoped user list: only users in admin's company.
$rawusers = iomad_helper::get_company_users_for_select();
$users    = array_map(fn($u) => ['id' => $u->id, 'fullname' => fullname($u)],
                      array_values($rawusers));
$depts = manager::get_departments(); // returns value + label
$months = [];
foreach (manager::months_list() as $num => $name) {
    $months[] = ['value' => $num, 'label' => $name, 'selected' => ($num === (int) date('n'))];
}

echo $OUTPUT->header();

// Show IOMAD context notice.
if ($companyname) {
    echo $OUTPUT->notification(
        '<i class="fa fa-building"></i> Creating award for company: <strong>' .
        s($companyname) . '</strong>', 'info');
}

echo $OUTPUT->render_from_template('local_halloffame/admin_panel', [
    'sesskey'     => sesskey(),
    'users'       => $users,
    'departments' => $depts,
    'categories'  => array_map(fn($c) => ['name' => $c->name], manager::get_categories()),
    'months'      => $months,
    'caturl'      => (new moodle_url('/local/halloffame/pages/manage_categories.php'))->out(false),
    'depturl'     => (new moodle_url('/local/halloffame/pages/manage_departments.php'))->out(false),
    'backurl'     => (new moodle_url('/local/halloffame/pages/index.php'))->out(false),
    'companyname' => $companyname,
    'hascompany'  => $companyname !== '',
]);

echo $OUTPUT->footer();
