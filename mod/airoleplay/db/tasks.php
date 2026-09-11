<?php
/**
 * Scheduled tasks for mod_airoleplay.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [

    [
        'classname' => '\mod_airoleplay\task\process_ai_evaluation_queue',
        'blocking'  => 0,
        'minute'    => '*/2',
        'hour'      => '*',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
        'disabled'  => 0,
    ],

    [
        'classname' => '\mod_airoleplay\task\send_assignment_reminders',
        'blocking'  => 0,
        'minute'    => '30',
        'hour'      => '8',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '1-5',
        'disabled'  => 0,
    ],

    [
        'classname' => '\mod_airoleplay\task\mark_overdue_assignments',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '1',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
        'disabled'  => 0,
    ],

    [
        'classname' => '\mod_airoleplay\task\generate_analytics_cache',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '3',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
        'disabled'  => 0,
    ],

    [
        'classname' => '\mod_airoleplay\task\cleanup_abandoned_sessions',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '2',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
        'disabled'  => 0,
    ],

    [
        'classname' => '\mod_airoleplay\task\sync_moodle_competencies',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '4',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
        'disabled'  => 0,
    ],

    [
        'classname' => '\mod_airoleplay\task\process_recurring_assignments',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '0',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
        'disabled'  => 0,
    ],
];
