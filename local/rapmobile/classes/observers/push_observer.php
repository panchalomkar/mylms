<?php
// This file is part of Moodle - http://moodle.org/
namespace local_rapmobile\observers;

defined('MOODLE_INTERNAL') || die();

use local_rapmobile\firebase\fcm_service;

/**
 * push_observer
 *
 * Maps every Moodle event that your get_rapmobile_notifications() already
 * shows in the app to an immediate FCM push notification.
 *
 * IMPORTANT: notification_type values MUST match the types used in
 * get_rapmobile_notifications() so the Flutter app's existing deep-link
 * navigation code works without any changes on the app side.
 *
 * Notification types mapped here:
 *   new_course_assigned       → user enrolled in a course
 *   user_unenrolled           → user removed from a course
 *   course_completion         → user finishes a course
 *   course_module_completion  → user completes an activity
 *   assessment_due            → assignment graded by teacher
 *   assessment_due            → quiz attempt submitted
 *   ilt_confirmation          → ILT seat confirmed
 *   ilt_waitlisted            → ILT waitlisted
 *   ilt_cancellation          → ILT session cancelled
 *
 * @package local_rapmobile
 */
class push_observer {

    // ─────────────────────────────────────────────────────────────────────────
    // ENROLMENT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fires when: admin/teacher enrolls a user in a course.
     * Event: \core\event\user_enrolment_created
     * Matches: 'new_course_assigned' in get_rapmobile_notifications()
     */
    public static function user_enrolled(\core\event\user_enrolment_created $event): void {
        global $DB;

        $userid   = (int)$event->relateduserid;
        $courseid = (int)$event->courseid;

        if ($userid <= 0 || $courseid <= 0) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $courseid], 'fullname', IGNORE_MISSING);
        if (!$course) {
            return;
        }

        fcm_service::send_to_user(
            $userid,
            'New Course Assigned',
            'A new course "' . $course->fullname . '" has been assigned to you.',
            'new_course_assigned',
            $courseid,
            0
        );
    }

    /**
     * Fires when: user is removed/unenrolled from a course.
     * Event: \core\event\user_enrolment_deleted
     * Matches: 'user_unenrolled' in get_rapmobile_notifications()
     */
    public static function user_unenrolled(\core\event\user_enrolment_deleted $event): void {
        global $DB;

        $userid   = (int)$event->relateduserid;
        $courseid = (int)$event->courseid;

        if ($userid <= 0 || $courseid <= 0) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $courseid], 'fullname', IGNORE_MISSING);
        if (!$course) {
            return;
        }

        fcm_service::send_to_user(
            $userid,
            'Course Unenrolled',
            'You have been unenrolled from "' . $course->fullname . '".',
            'user_unenrolled',
            $courseid,
            0
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COURSE COMPLETION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fires when: user completes a full course.
     * Event: \core\event\course_completed
     * Matches: 'course_completion' in get_rapmobile_notifications()
     */
    public static function course_completed(\core\event\course_completed $event): void {
        global $DB;

        $userid   = (int)$event->relateduserid;
        $courseid = (int)$event->courseid;

        if ($userid <= 0 || $courseid <= 0) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $courseid], 'fullname', IGNORE_MISSING);
        if (!$course) {
            return;
        }

        fcm_service::send_to_user(
            $userid,
            'Course Completed! 🎉',
            'Congratulations! You completed "' . $course->fullname . '".',
            'course_completion',
            $courseid,
            0
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODULE / ACTIVITY COMPLETION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fires when: user completes any activity (SCORM, quiz, page, etc.)
     * Event: \core\event\course_module_completion_updated
     * Matches: 'course_module_completion' in get_rapmobile_notifications()
     */
    public static function module_completed(\core\event\course_module_completion_updated $event): void {
        global $DB;

        $userid = (int)$event->relateduserid;
        $cmid   = (int)$event->contextinstanceid;

        if ($userid <= 0 || $cmid <= 0) {
            return;
        }

        // Only fire on actual completion (state 1=complete, 2=complete with pass).
        // State 0 = incomplete — don't push for that.
        $completion = $DB->get_record(
            'course_modules_completion',
            ['userid' => $userid, 'coursemoduleid' => $cmid],
            'completionstate',
            IGNORE_MISSING
        );
        if (!$completion || (int)$completion->completionstate < 1) {
            return;
        }

        $cm = get_coursemodule_from_id('', $cmid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $cm->course], 'fullname', IGNORE_MISSING);
        if (!$course) {
            return;
        }

        fcm_service::send_to_user(
            $userid,
            'Activity Completed ✓',
            'You completed "' . $cm->name . '" in "' . $course->fullname . '".',
            'course_module_completion',
            (int)$cm->course,
            $cmid
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ASSIGNMENT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fires when: teacher grades a student's assignment submission.
     * Event: \mod_assign\event\submission_graded
     * Matches: 'assessment_due' type (reused for graded feedback)
     */
    public static function assignment_graded(\mod_assign\event\submission_graded $event): void {
        global $DB;

        // relateduserid = the student who was graded
        $userid = (int)$event->relateduserid;
        $cmid   = (int)$event->contextinstanceid;

        if ($userid <= 0 || $cmid <= 0) {
            return;
        }

        $cm = get_coursemodule_from_id('assign', $cmid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        $assign = $DB->get_record('assign', ['id' => $cm->instance], 'name', IGNORE_MISSING);
        if (!$assign) {
            return;
        }

        fcm_service::send_to_user(
            $userid,
            'Assignment Graded',
            'Your assignment "' . $assign->name . '" has been graded. Tap to view feedback.',
            'assessment_due',
            (int)$cm->course,
            $cmid
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // QUIZ
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fires when: user submits a quiz attempt.
     * Event: \mod_quiz\event\attempt_submitted
     * Matches: 'assessment_due' type (reused for quiz feedback)
     */
    public static function quiz_submitted(\mod_quiz\event\attempt_submitted $event): void {
        global $DB;

        $userid = (int)$event->userid;
        $cmid   = (int)$event->contextinstanceid;

        if ($userid <= 0 || $cmid <= 0) {
            return;
        }

        $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], 'name', IGNORE_MISSING);
        if (!$quiz) {
            return;
        }

        fcm_service::send_to_user(
            $userid,
            'Quiz Submitted',
            'Your quiz "' . $quiz->name . '" has been submitted successfully.',
            'assessment_due',
            (int)$cm->course,
            $cmid
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ILT — Instructor-Led Training
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fires when: ILT signup status changes (confirmed / waitlisted / cancelled).
     *
     * This is triggered by a custom event dispatched from signup_ilt_session
     * and cancel_ilt_signup in externallib.php (see HOW TO WIRE section below).
     *
     * Matches: 'ilt_confirmation', 'ilt_waitlisted', 'ilt_cancellation'
     *          in get_rapmobile_notifications()
     */
    public static function ilt_status_changed(\local_rapmobile\event\ilt_signup_status_changed $event): void {
        $data       = $event->get_data();
        $other      = $data['other'] ?? [];
        $userid     = (int)$data['relateduserid'];
        $courseid   = (int)($other['courseid'] ?? 0);
        $cmid       = (int)($other['cmid']     ?? 0);
        $statuscode = (int)($other['statuscode'] ?? 0);
        $iltname    = (string)($other['iltname'] ?? 'ILT Session');

        if ($userid <= 0) {
            return;
        }

        switch ($statuscode) {
            case 70: // Confirmed
                fcm_service::send_to_user(
                    $userid,
                    'ILT Seat Confirmed ✓',
                    'Your seat for "' . $iltname . '" has been confirmed.',
                    'ilt_confirmation',
                    $courseid,
                    $cmid
                );
                break;

            case 60: // Waitlisted
                fcm_service::send_to_user(
                    $userid,
                    'ILT Wait-listed',
                    'You are currently wait-listed for "' . $iltname . '".',
                    'ilt_waitlisted',
                    $courseid,
                    $cmid
                );
                break;

            case 10: // Cancelled
                fcm_service::send_to_user(
                    $userid,
                    'ILT Session Cancelled',
                    'Your ILT session "' . $iltname . '" has been cancelled.',
                    'ilt_cancellation',
                    $courseid,
                    $cmid
                );
                break;

            case 40: // Requested / pending approval
                fcm_service::send_to_user(
                    $userid,
                    'ILT Request Submitted',
                    'Your request for "' . $iltname . '" has been submitted and is awaiting approval.',
                    'ilt_request',
                    $courseid,
                    $cmid
                );
                break;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STATIC HELPER — call this directly from externallib.php for ILT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Direct push for ILT status change.
     *
     * Call this from signup_ilt_session() and cancel_ilt_signup() in
     * externallib.php right after inserting the ilt_signups_status row:
     *
     *   \local_rapmobile\observers\push_observer::push_ilt_status(
     *       $userid, $statuscode, $iltname, $courseid, $cmid
     *   );
     *
     * @param int    $userid
     * @param int    $statuscode  40=requested 70=confirmed 60=waitlisted 10=cancelled
     * @param string $iltname
     * @param int    $courseid
     * @param int    $cmid
     */
    public static function push_ilt_status(
        int    $userid,
        int    $statuscode,
        string $iltname,
        int    $courseid = 0,
        int    $cmid = 0
    ): void {
        switch ($statuscode) {
            case 70:
                fcm_service::send_to_user($userid,
                    'ILT Seat Confirmed ✓',
                    'Your seat for "' . $iltname . '" has been confirmed.',
                    'ilt_confirmation', $courseid, $cmid);
                break;
            case 60:
                fcm_service::send_to_user($userid,
                    'ILT Wait-listed',
                    'You are currently wait-listed for "' . $iltname . '".',
                    'ilt_waitlisted', $courseid, $cmid);
                break;
            case 10:
                fcm_service::send_to_user($userid,
                    'ILT Session Cancelled',
                    'Your ILT session "' . $iltname . '" has been cancelled.',
                    'ilt_cancellation', $courseid, $cmid);
                break;
            case 40:
                fcm_service::send_to_user($userid,
                    'ILT Request Submitted',
                    'Your request for "' . $iltname . '" has been submitted.',
                    'ilt_request', $courseid, $cmid);
                break;
        }
    }
}
