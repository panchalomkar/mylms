<?php
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');
require_login();
require_capability('local/skillgap:takeassessment', context_system::instance());
$assessmentid = required_param('id', PARAM_INT);
$PAGE->set_url(new moodle_url('/local/skillgap/user/assessment.php', ['id' => $assessmentid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('assessments', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('pluginname',     'local_skillgap'), new moodle_url('/local/skillgap/user/dashboard.php'));
$PAGE->navbar->add(get_string('user_dashboard', 'local_skillgap'), new moodle_url('/local/skillgap/user/dashboard.php'));
$PAGE->navbar->add(get_string('assessments',    'local_skillgap'));
$PAGE->requires->js_call_amd('local_skillgap/skillgap', 'init');
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
if ($action === 'submit' && confirm_sesskey()) {
    $attemptid = required_param('attemptid', PARAM_INT);

    // Security: verify this attempt belongs to the current user and is still in-progress.
    $attempt = $DB->get_record('local_skillgap_attempt', ['id' => $attemptid], 'id,userid,assessmentid,status');
    if (!$attempt || (int)$attempt->userid !== (int)$USER->id) {
        throw new \moodle_exception('invalidattempt', 'local_skillgap', '', null,
            'Attempt does not belong to the current user.');
    }
    if ($attempt->status === 'completed') {
        redirect(new moodle_url('/local/skillgap/user/dashboard.php'),
            'This assessment has already been submitted.',
            null, \core\output\notification::NOTIFY_WARNING);
    }

    $DB->set_field('local_skillgap_attempt', 'status',        'completed', ['id' => $attemptid]);
    $DB->set_field('local_skillgap_attempt', 'timecompleted', time(),       ['id' => $attemptid]);
    local_skillgap_compute_scores($attemptid);
    redirect(new moodle_url('/local/skillgap/user/dashboard.php'),
        'Assessment submitted! Your scores have been recorded.',
        null, \core\output\notification::NOTIFY_SUCCESS);
}
$data     = \local_skillgap\local\helper::take_assessment_data($assessmentid, $USER->id);
$renderer = $PAGE->get_renderer('local_skillgap');
echo $OUTPUT->header();
echo $renderer->render_take_assessment($data);
echo $OUTPUT->footer();
