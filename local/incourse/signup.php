<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$sessionid = required_param('s', PARAM_INT);
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/incourse/signup.php', ['s' => $sessionid]));
$PAGE->set_title('Sign Up for ILT Session');
$PAGE->set_heading('Sign Up for ILT Session');

require_once(__DIR__ . '/classes/form/signup_form.php');

$mform = new local_incourse_form_signup_form(null, ['sessionid' => $sessionid]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/ilt/view.php', ['f' => $backtoallsessions]));
} else if ($data = $mform->get_data()) {
    global $DB, $USER;

    // ✅ Make sure table exists
    if (!$DB->get_manager()->table_exists('ilt_signups')) {
        // Create table automatically if not present (for dev/test purposes)
        $table = new xmldb_table('ilt_signups');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $DB->get_manager()->create_table($table);
    }

    // ✅ Insert signup record
    $record = new stdClass();
    $record->sessionid = $sessionid;
    $record->userid = $USER->id;
    $record->timecreated = time();
    $DB->insert_record('ilt_signups', $record);

    redirect(new moodle_url('/mod/ilt/view.php', ['f' => $backtoallsessions]), '✅ You are signed up successfully!', 2);
}

echo $OUTPUT->header();
?>
<div class="p-6 bg-white rounded-xl shadow-md max-w-xl mx-auto mt-8">
    <div class="flex items-center justify-center gap-2 mb-4 text-[#003152]">
        <span class="material-icons text-3xl">how_to_reg</span>
        <h2 class="text-xl font-semibold">Sign Up for ILT Session</h2>
    </div>
    <div class="text-gray-600 mb-4 text-sm text-center">
        Please confirm your participation below.
    </div>
    <div class="mform-container">
        <?php $mform->display(); ?>
    </div>
</div>
<?php
echo $OUTPUT->footer();
