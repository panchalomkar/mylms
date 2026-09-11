<?php
// This file is part of Moodle - http://moodle.org/
defined('MOODLE_INTERNAL') || die();

/**
 * RAP Mobile — Moodle event observers for FCM push notifications.
 *
 * Each observer maps one real Moodle event to one FCM push.
 * The notification_type values deliberately match the types used in
 * get_rapmobile_notifications() so the Flutter app can use the same
 * deep-link/navigation code for both pulled and pushed notifications.
 *
 * @package local_rapmobile
 */
$observers = [

    // =========================================================
    // ENROLMENT
    // =========================================================

    // User enrolled in a course → "New Course Assigned"
    [
        'eventname'   => '\core\event\user_enrolment_created',
        'callback'    => '\local_rapmobile\observers\push_observer::user_enrolled',
        'includefile' => null,
        'priority'    => 200,
        'internal'    => false,
    ],

    // User unenrolled from a course → "User Unenrolled"
    [
        'eventname'   => '\core\event\user_enrolment_deleted',
        'callback'    => '\local_rapmobile\observers\push_observer::user_unenrolled',
        'includefile' => null,
        'priority'    => 200,
        'internal'    => false,
    ],

    // =========================================================
    // COURSE COMPLETION
    // =========================================================

    // User completes a course → "Course Completed"
    [
        'eventname'   => '\core\event\course_completed',
        'callback'    => '\local_rapmobile\observers\push_observer::course_completed',
        'includefile' => null,
        'priority'    => 200,
        'internal'    => false,
    ],

    // =========================================================
    // ACTIVITY / MODULE COMPLETION
    // =========================================================

    // User completes a module/activity → "Module Completed"
    [
        'eventname'   => '\core\event\course_module_completion_updated',
        'callback'    => '\local_rapmobile\observers\push_observer::module_completed',
        'includefile' => null,
        'priority'    => 200,
        'internal'    => false,
    ],

    // =========================================================
    // ASSIGNMENT
    // =========================================================

    // Teacher grades an assignment → "Assignment Graded"
    [
        'eventname'   => '\mod_assign\event\submission_graded',
        'callback'    => '\local_rapmobile\observers\push_observer::assignment_graded',
        'includefile' => null,
        'priority'    => 200,
        'internal'    => false,
    ],

    // =========================================================
    // QUIZ
    // =========================================================

    // User submits a quiz attempt → "Quiz Submitted"
    [
        'eventname'   => '\mod_quiz\event\attempt_submitted',
        'callback'    => '\local_rapmobile\observers\push_observer::quiz_submitted',
        'includefile' => null,
        'priority'    => 200,
        'internal'    => false,
    ],

    // =========================================================
    // ILT (Instructor-Led Training)
    // =========================================================

    // ILT signup status changes → seat confirmed / waitlisted / cancelled
    // This fires whenever ilt_signups_status row is created/updated.
    // We hook into the generic DB event via a custom ILT event if it exists,
    // otherwise handle it in signup_ilt_session API call directly.
    // (See push_observer::ilt_status_changed for the dispatch logic.)
    [
        'eventname'   => '\local_rapmobile\event\ilt_signup_status_changed',
        'callback'    => '\local_rapmobile\observers\push_observer::ilt_status_changed',
        'includefile' => null,
        'priority'    => 200,
        'internal'    => false,
    ],

];
