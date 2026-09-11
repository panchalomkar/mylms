<?php
/**
 * Event observers for mod_airoleplay.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [

    // Trigger AI evaluation when session completes
    [
        'eventname'   => '\mod_airoleplay\event\session_completed',
        'callback'    => '\mod_airoleplay\event\observer::session_completed',
        'priority'    => 200,
        'internal'    => false,
    ],

    // Update assignment status when session changes
    [
        'eventname'   => '\mod_airoleplay\event\session_completed',
        'callback'    => '\local_airoleplay_assignments\event\observer::update_assignment_status',
        'priority'    => 100,
        'internal'    => false,
    ],

    // Trigger competency update on evaluation complete
    [
        'eventname'   => '\mod_airoleplay\event\evaluation_completed',
        'callback'    => '\local_airoleplay_competency\event\observer::update_competencies',
        'priority'    => 200,
        'internal'    => false,
    ],

    // Send coaching notification after evaluation
    [
        'eventname'   => '\mod_airoleplay\event\evaluation_completed',
        'callback'    => '\local_airoleplay_notifications\event\observer::send_coaching_notification',
        'priority'    => 100,
        'internal'    => false,
    ],

    // Trigger AI remediation assignment if failed
    [
        'eventname'   => '\mod_airoleplay\event\evaluation_completed',
        'callback'    => '\local_airoleplay_assignments\event\observer::check_remediation',
        'priority'    => 50,
        'internal'    => false,
    ],

    // Update grade in Moodle gradebook
    [
        'eventname'   => '\mod_airoleplay\event\evaluation_completed',
        'callback'    => '\mod_airoleplay\event\observer::update_gradebook',
        'priority'    => 300,
        'internal'    => false,
    ],

    // Update course completion when passed
    [
        'eventname'   => '\mod_airoleplay\event\session_passed',
        'callback'    => '\mod_airoleplay\event\observer::update_completion',
        'priority'    => 200,
        'internal'    => false,
    ],

    // Send overdue reminders via assignment engine
    [
        'eventname'   => '\core\event\user_loggedin',
        'callback'    => '\local_airoleplay_notifications\event\observer::check_pending_reminders',
        'priority'    => 50,
        'internal'    => false,
    ],

    // Invalidate user profile cache when custom fields change
    [
        'eventname'   => '\core\event\user_updated',
        'callback'    => '\mod_airoleplay\event\observer::invalidate_user_profile_cache',
        'priority'    => 100,
        'internal'    => false,
    ],
];
