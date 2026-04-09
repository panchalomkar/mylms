<?php
// local/halloffame/pages/review.php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

use local_halloffame\manager;

require_login();
$context = context_system::instance();
require_capability('local/halloffame:approve', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/halloffame/pages/review.php'));
$PAGE->set_title(get_string('review', 'local_halloffame'));
$PAGE->set_heading(get_string('halloffame', 'local_halloffame'));
$PAGE->set_pagelayout('admin');
$PAGE->requires->css(new moodle_url('/local/halloffame/styles.css'));

$PAGE->navbar->add(get_string('halloffame', 'local_halloffame'),
    new moodle_url('/local/halloffame/pages/index.php'));
$PAGE->navbar->add(get_string('review', 'local_halloffame'));

// ── Handle approve / reject ───────────────────────────────────────────────────
$action = optional_param('action', '', PARAM_ALPHA);
$sid    = optional_param('sid',    0,  PARAM_INT);

if ($action && $sid && confirm_sesskey()) {
    if ($action === 'approve') {
        manager::approve_achievement($sid);
        redirect($PAGE->url,
            get_string('achievementapproved', 'local_halloffame'),
            null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'reject') {
        manager::reject_achievement($sid);
        redirect($PAGE->url,
            get_string('achievementrejected', 'local_halloffame'),
            null, \core\output\notification::NOTIFY_WARNING);
    }
}

echo $OUTPUT->header();

/** @var \local_halloffame\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_halloffame');
echo $renderer->render_review_queue();

echo html_writer::link(
    new moodle_url('/local/halloffame/pages/index.php'),
    '← ' . get_string('halloffame', 'local_halloffame'),
    ['class' => 'hof-back-link', 'style' => 'display:block;margin-top:24px']
);

echo $OUTPUT->footer();
