<?php
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');
require_login();
require_capability('local/skillgap:admin', context_system::instance());

$PAGE->set_url(new moodle_url('/local/skillgap/admin/promotion.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('promotion', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('pluginname', 'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add(get_string('promotion', 'local_skillgap'));

if (optional_param('action', '', PARAM_ALPHANUMEXT) === 'promote' && confirm_sesskey()) {
    $userid = required_param('userid', PARAM_INT);
    $user   = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

    // 1. Resolve current designation from profile.
    $current_desig = \local_skillgap\local\designation_service::get_user_designation($userid);

    if (empty($current_desig)) {
        redirect(
            new moodle_url('/local/skillgap/admin/promotion.php'),
            'Cannot promote ' . fullname($user) . ': user has no designation set in their profile.',
            null, \core\output\notification::NOTIFY_ERROR
        );
    }

    // 2. Find next designation using sort_order-based logic.
    $next_desig = \local_skillgap\local\designation_service::get_next_designation(
        $user->department, $current_desig
    );

    if (!$next_desig) {
        redirect(
            new moodle_url('/local/skillgap/admin/promotion.php'),
            'Cannot promote ' . fullname($user) . ': no next designation found. They may already be at the top level.',
            null, \core\output\notification::NOTIFY_ERROR
        );
    }

    // 3. Prevent duplicate promotion within 7 days.
    $last_promo = $DB->get_record_sql(
        "SELECT timepromoted FROM {local_skillgap_promotion}
          WHERE userid = :uid ORDER BY timepromoted DESC LIMIT 1",
        ['uid' => $userid]
    );
    if ($last_promo && (time() - (int)$last_promo->timepromoted < 7 * 86400)) {
        redirect(
            new moodle_url('/local/skillgap/admin/promotion.php'),
            fullname($user) . ' was already promoted recently. Please wait before promoting again.',
            null, \core\output\notification::NOTIFY_WARNING
        );
    }

    // 4. Resolve hierarchy IDs for logging.
    $from_rec = $DB->get_record('local_skillgap_desig_hierarchy',
        ['designation_name' => $current_desig, 'department' => $user->department],
        'id', IGNORE_MISSING);
    $to_rec   = $DB->get_record('local_skillgap_desig_hierarchy',
        ['designation_name' => $next_desig,    'department' => $user->department],
        'id', IGNORE_MISSING);

    // 5. Update user's profile field to new designation.
    \local_skillgap\local\designation_service::set_user_designation($userid, $next_desig);

    // 6. Record promotion log with real IDs.
    $DB->insert_record('local_skillgap_promotion', [
        'userid'        => $userid,
        'from_desigid'  => $from_rec ? (int)$from_rec->id : 0,
        'to_desigid'    => $to_rec   ? (int)$to_rec->id   : 0,
        'status'        => 'promoted',
        'promoted_by'   => $USER->id,
        'timecreated'   => time(),
        'timepromoted'  => time(),
    ]);

    // 7. Auto-assign pending assessments for the new designation:
    //    The dashboard dynamically filters by dept+desig, so after profile update
    //    the user will immediately see new designation's assessments on next login.
    //    If an explicit enrolment table were used, insert rows here.

    // 8. Send notification.
    local_skillgap_send_notification(
        $userid,
        'promoted',
        get_string('notify_promoted_subject', 'local_skillgap', $next_desig),
        get_string('notify_promoted_body',    'local_skillgap', $next_desig)
    );

    redirect(
        new moodle_url('/local/skillgap/admin/promotion.php'),
        get_string('promote_success', 'local_skillgap', fullname($user)) . ' → ' . $next_desig,
        null, \core\output\notification::NOTIFY_SUCCESS
    );
}

$data     = \local_skillgap\local\helper::promotion_data();
$renderer = $PAGE->get_renderer('local_skillgap');
echo $OUTPUT->header();
echo $renderer->render_promotion($data);
echo $OUTPUT->footer();
