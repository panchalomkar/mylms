<?php
// local/halloffame/pages/edit_award.php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

use local_halloffame\manager;
use local_halloffame\iomad_helper;
use local_halloffame\department_helper;

require_login();
$context = context_system::instance();
require_capability('local/halloffame:manageawards', $context);

global $DB, $USER;

$id = required_param('id', PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/halloffame/pages/edit_award.php', ['id' => $id]));
$PAGE->set_title(get_string('editaward', 'local_halloffame'));
$PAGE->set_heading(get_string('halloffame', 'local_halloffame'));
$PAGE->set_pagelayout('admin');
$PAGE->requires->css(new moodle_url('/local/halloffame/styles.css'));

$PAGE->navbar->add(get_string('halloffame', 'local_halloffame'),
    new moodle_url('/local/halloffame/pages/index.php'));
$PAGE->navbar->add(get_string('editaward', 'local_halloffame'));

// ── Load existing award ────────────────────────────────────────────────────────
$award = manager::get_award($id); // single-record fetch added to manager
if (!$award) {
    throw new moodle_exception('invalidrecord', 'local_halloffame');
}

// Guard: must be same company.
manager::assert_same_company($award->userid);

$companyid   = iomad_helper::get_current_companyid();
$companyname = iomad_helper::get_company_name($companyid);

// ── Handle POST ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $userid     = required_param('userid',     PARAM_INT);
    $title      = required_param('title',      PARAM_TEXT);
    $department = optional_param('department', '', PARAM_TEXT);
    $category   = optional_param('category',   '', PARAM_TEXT);
    $month      = optional_param('month',      (int) date('n'), PARAM_INT);
    $year       = optional_param('year',       (int) date('Y'), PARAM_INT);
    $message    = optional_param('message',    '', PARAM_TEXT);

    manager::assert_same_company($userid);

    // Handle optional new image upload.
    $imageurl = $award->image; // keep existing by default
    if (!empty($_FILES['achieverimage']['name']) &&
        $_FILES['achieverimage']['error'] === UPLOAD_ERR_OK) {
        $err = manager::validate_upload($_FILES['achieverimage']);
        if ($err === '') {
            $itemid   = (int)($userid * 10000 + (time() % 10000));
            $imageurl = manager::store_upload($_FILES['achieverimage'], 'awards_images', $itemid);
        }
    }

    if (empty($department) && $userid) {
        $department = department_helper::get_user_department($userid);
    }

    manager::update_award($id, [
        'userid'     => $userid,
        'title'      => $title,
        'department' => $department,
        'category'   => $category,
        'month'      => $month,
        'year'       => (int) $year,
        'message'    => $message,
        'image'      => $imageurl,
    ]);

    redirect(
        new moodle_url('/local/halloffame/pages/index.php', ['tab' => 'awards']),
        get_string('awardupdated', 'local_halloffame'),
        null, \core\output\notification::NOTIFY_SUCCESS
    );
}

// ── Build form data ────────────────────────────────────────────────────────────
$rawusers = iomad_helper::get_company_users_for_select();

// Ensure selected user exists
if (!array_key_exists($award->userid, $rawusers)) {
    $extrauser = \core_user::get_user($award->userid);
    if ($extrauser) {
        $rawusers[$extrauser->id] = $extrauser;
    }
}

$users = array_map(fn($u) => [
    'id'       => $u->id,
    'fullname' => fullname($u),
    'selected' => ((int)$u->id === (int)$award->userid),
], array_values($rawusers));

$depts = manager::get_departments();
// Mark currently selected department.
foreach ($depts as &$d) {
    $d['selected'] = (isset($d['value']) && $d['value'] === $award->department)
                  || (isset($d['label']) && $d['label'] === $award->department);
}
unset($d);

$months = [];
foreach (manager::months_list() as $num => $name) {
    $months[] = ['value' => $num, 'label' => $name, 'selected' => ($num === (int) $award->month)];
}

$cats = array_map(fn($c) => [
    'name'     => $c->name,
    'selected' => ($c->name === $award->category),
], manager::get_categories());

// Year range.
$years = [];
foreach (range((int) date('Y'), (int) date('Y') - 5) as $y) {
    $years[] = ['value' => $y, 'label' => $y, 'selected' => ($y === (int) $award->year)];
}

echo $OUTPUT->header();

if ($companyname) {
    echo $OUTPUT->notification(
        '<i class="fa fa-building"></i> Editing award for company: <strong>' .
        s($companyname) . '</strong>', 'info');
}

echo $OUTPUT->render_from_template('local_halloffame/edit_award_panel', [
    'awardid'     => $id,
    'sesskey'     => sesskey(),
    'users'       => $users,
    'title' => $award->title ?? '',
    'departments' => $depts,
    'selected' => ((int)$u->id === (int)$award->userid),
    'categories'  => $cats,
    'months'      => $months,
    'years'       => $years,
    'message'     => $award->message ?? '',
    'currentimage'=> $award->image   ?? '',
    'caturl'      => (new moodle_url('/local/halloffame/pages/manage_categories.php'))->out(false),
    'depturl'     => (new moodle_url('/local/halloffame/pages/manage_departments.php'))->out(false),
    'backurl'     => (new moodle_url('/local/halloffame/pages/index.php'))->out(false),
    'companyname' => $companyname,
    'hascompany'  => $companyname !== '',
]);

echo $OUTPUT->footer();
