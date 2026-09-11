<?php
// local/halloffame/pages/my_submissions.php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

require_login();
$context = context_system::instance();
require_capability('local/halloffame:submit', $context);

global $DB, $USER;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/halloffame/pages/my_submissions.php'));
$PAGE->set_title(get_string('mysubmissions', 'local_halloffame'));
$PAGE->set_heading(get_string('halloffame', 'local_halloffame'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/halloffame/styles.css'));
// Boot the AMD modules so the certificate popup works on this page too.
$PAGE->requires->js_call_amd('local_halloffame/main', 'init', [['tab' => 'mysubmissions']]);

$PAGE->navbar->add(get_string('halloffame', 'local_halloffame'),
    new moodle_url('/local/halloffame/pages/index.php'));
$PAGE->navbar->add(get_string('mysubmissions', 'local_halloffame'));

$recs = $DB->get_records('halloffame_submissions', ['userid' => $USER->id], 'timecreated DESC');

$statusmap = [
    'pending'  => ['label' => 'Pending Review', 'class' => 'hof-status-pending'],
    'approved' => ['label' => 'Approved ✓',     'class' => 'hof-status-approved'],
    'rejected' => ['label' => 'Rejected ✗',     'class' => 'hof-status-rejected'],
];

$rows = [];
foreach ($recs as $r) {
    $st      = $statusmap[$r->status] ?? ['label' => $r->status, 'class' => ''];
    $ext     = $r->fileurl ? strtolower(pathinfo($r->fileurl, PATHINFO_EXTENSION)) : '';
    $isimage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);

    // Pending submissions are still editable / deletable by the owner.
    $caneditdelete = ($r->status === 'pending');
    $editurl   = $caneditdelete
        ? (new moodle_url('/local/halloffame/pages/edit_submission.php',
            ['id' => $r->id]))->out(false)
        : '';
    $deleteurl = $caneditdelete
        ? (new moodle_url('/local/halloffame/pages/delete_submission.php',
            ['id' => $r->id, 'sesskey' => sesskey()]))->out(false)
        : '';

    $rows[] = [
        'id'            => (int) $r->id,
        'title'         => $r->title,
        'issuer'        => $r->issuer ?? '',
        'type'          => $r->type   ?? '',
        'timecreated'   => userdate($r->timecreated,
                            get_string('strftimedatetimeshort', 'langconfig')),
        'statuslabel'   => $st['label'],
        'statusclass'   => $st['class'],
        'fileurl'       => $r->fileurl ?? '',
        'fileisimage'   => $isimage,
        'caneditdelete' => $caneditdelete,
        'editurl'       => $editurl,
        'deleteurl'     => $deleteurl,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_halloffame/my_submissions', [
    'rows'      => $rows,
    'hasrows'   => !empty($rows),
    'submiturl' => (new moodle_url('/local/halloffame/pages/submit.php'))->out(false),
    'backurl'   => (new moodle_url('/local/halloffame/pages/index.php'))->out(false),
]);
echo $OUTPUT->footer();
