<?php
// local/halloffame/pages/submit.php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

use local_halloffame\manager;

require_login();
$context = context_system::instance();
require_capability('local/halloffame:submit', $context);

if (!get_config('local_halloffame', 'enable_submissions')) {
    redirect(
        new moodle_url('/local/halloffame/pages/index.php'),
        get_string('accessdenied', 'local_halloffame'),
        null, \core\output\notification::NOTIFY_ERROR
    );
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/halloffame/pages/submit.php'));
$PAGE->set_title(get_string('uploadexternalcertificate', 'local_halloffame'));
$PAGE->set_heading(get_string('halloffame', 'local_halloffame'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/halloffame/styles.css'));
$PAGE->requires->js_call_amd('local_halloffame/main', 'init', [['tab' => 'achievements']]);

$PAGE->navbar->add(get_string('halloffame', 'local_halloffame'),
    new moodle_url('/local/halloffame/pages/index.php'));
$PAGE->navbar->add(get_string('submit', 'local_halloffame'));

global $USER;
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $title   = required_param('title',     PARAM_TEXT);
    $issuer  = optional_param('issuer',    '', PARAM_TEXT);
    $type    = optional_param('type',      '', PARAM_TEXT);
    $notes   = optional_param('notes',     '', PARAM_TEXT);
    $dateraw = optional_param('issuedate', '', PARAM_TEXT);

    $issuedate = ($dateraw !== '') ? ((int) strtotime($dateraw) ?: 0) : 0;
    $fileurl   = '';

    if (!empty($_FILES['certfile']['name']) && $_FILES['certfile']['error'] === UPLOAD_ERR_OK) {
        $error = manager::validate_upload($_FILES['certfile']);
        if ($error === '') {
            $itemid  = (int) ($USER->id * 10000 + (time() % 10000));
            $fileurl = manager::store_upload($_FILES['certfile'], 'certificates', $itemid);
            if ($fileurl === '') {
                $error = get_string('fileuploaderror', 'local_halloffame');
            }
        }
    }

    if ($error === '') {
        manager::submit_achievement(compact('title','issuer','issuedate','type','notes','fileurl'));
        $success = true;
    }
}

$types = [
    ['value' => '',                   'label' => '— Select Type —'],
    ['value' => 'Technical',          'label' => get_string('type_technical',  'local_halloffame')],
    ['value' => 'Project Management', 'label' => get_string('type_management', 'local_halloffame')],
    ['value' => 'Leadership',         'label' => get_string('type_leadership', 'local_halloffame')],
    ['value' => 'Compliance',         'label' => get_string('type_compliance', 'local_halloffame')],
    ['value' => 'Other',              'label' => get_string('type_other',      'local_halloffame')],
];

echo $OUTPUT->header();

if ($success) {
    echo $OUTPUT->notification(get_string('submissionsaved', 'local_halloffame'), 'success');
    echo html_writer::link(
        new moodle_url('/local/halloffame/pages/index.php', ['tab' => 'achievements']),
        '← ' . get_string('achievements', 'local_halloffame'),
        ['class' => 'btn hof-btn-primary']
    );
    echo html_writer::tag('span', ' ');
    echo html_writer::link(
        new moodle_url('/local/halloffame/pages/my_submissions.php'),
        get_string('mysubmissions', 'local_halloffame'),
        ['class' => 'btn hof-btn-secondary']
    );
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->render_from_template('local_halloffame/submit_form', [
    'sesskey' => sesskey(),
    'types'   => $types,
    'error'   => $error,
    'success' => false,
    'backurl' => (new moodle_url('/local/halloffame/pages/index.php'))->out(false),
]);

echo $OUTPUT->footer();
