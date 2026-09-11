<?php
// local/halloffame/pages/delete_submission.php
// Delete a *pending* submission. Owner only (admins use the review queue's reject action).

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

require_login();
require_sesskey();

global $DB, $USER;

$id = required_param('id', PARAM_INT);

$context = context_system::instance();
require_capability('local/halloffame:submit', $context);

$sub = $DB->get_record('halloffame_submissions', ['id' => $id], '*', MUST_EXIST);

// Only the owner of a still-pending submission may delete it from this page.
if ((int)$sub->userid !== (int)$USER->id) {
    throw new moodle_exception('accessdenied', 'local_halloffame');
}
if ($sub->status !== 'pending') {
    throw new moodle_exception('cannotdeletenonpending', 'local_halloffame', '',
        null, 'Only pending submissions can be deleted by the submitter.');
}

\local_halloffame\manager::delete_submission($id);

redirect(
    new moodle_url('/local/halloffame/pages/my_submissions.php'),
    get_string('submissiondeleted', 'local_halloffame'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
