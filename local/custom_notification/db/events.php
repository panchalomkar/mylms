<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local Course Progress Manager Plugin Events Observer.
 *
 * @package     local_custom_notification
 * @category    admin
 * @copyright   2019 wisdmlabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observerfile = '/local/custom_notification/classes/observer.php';
$observerclassname = '\local_custom_notification\observers\event_observer';

$observers = array(
    // Event observer for course completion notification.
    array(
        'eventname' => '\core\event\course_completed',
        'callback' => $observerclassname . '::course_completion_notification',
        'includefile' => $observerfile
    ),

    // Event observer for course module completion notification.
    array(
        'eventname' => '\core\event\course_module_completion_updated',
        'callback' => $observerclassname . '::course_module_completion_notification',
        'includefile' => $observerfile
    ),

    // Event observer for user enrolled notification.
    array(
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => $observerclassname . '::user_enrolled_notification',
        'includefile' => $observerfile
    ),

    // Event observer for user unenrolled notification.
    array(
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback' => $observerclassname . '::user_unenrolled_notification',
        'includefile' => $observerfile
    )
);
