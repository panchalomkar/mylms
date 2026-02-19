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
 * Copyright (C) 2007-2011 Catalyst IT (http://www.catalyst.net.nz)
 * Copyright (C) 2011-2013 Totara LMS (http://www.totaralms.com)
 * Copyright (C) 2014 onwards Catalyst IT (http://www.catalyst-eu.net)
 *
 * @package    mod
 * @subpackage ilt
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @author     Alastair Munro <alastair.munro@totaralms.com>
 * @author     Aaron Barnes <aaron.barnes@totaralms.com>
 * @author     Francois Marier <francois@catalyst.net.nz>
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');

$s = required_param('s', PARAM_INT); // Facetoface session ID.
$confirm = optional_param('confirm', false, PARAM_BOOL);
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT);

if (!$session = ilt_get_session($s)) {
    print_error('error:incorrectcoursemodulesession', 'ilt');
}
if (!$session->allowcancellations) {
    print_error('error:cancellationsnotallowed', 'ilt');
}
if (!$ilt = $DB->get_record('ilt', array('id' => $session->ilt))) {
    print_error('error:incorrectiltid', 'ilt');
}
if (!$course = $DB->get_record('course', array('id' => $ilt->course))) {
    print_error('error:coursemisconfigured', 'ilt');
}
if (!$cm = get_coursemodule_from_instance("ilt", $ilt->id, $course->id)) {
    print_error('error:incorrectcoursemoduleid', 'ilt');
}

require_course_login($course);
$context = context_course::instance($course->id);
$contextmodule = context_module::instance($cm->id);
require_capability('mod/ilt:view', $context);

$returnurl = "$CFG->wwwroot/course/view.php?id=$course->id";
if ($backtoallsessions) {
    $returnurl = "$CFG->wwwroot/mod/ilt/view.php?f=$backtoallsessions";
}

$mform = new mod_ilt_cancelsignup_form(null, compact('s', 'backtoallsessions'));
if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) { // Form submitted.

    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'ilt', $returnurl);
    }

    $timemessage = 4;

    $errorstr = '';
    if (ilt_user_cancel($session, false, false, $errorstr, $fromform->cancelreason)) {

        // Logging and events trigger.
        $params = array(
            'context'  => $contextmodule,
            'objectid' => $session->id
        );
        $event = \mod_ilt\event\cancel_booking::create($params);
        $event->add_record_snapshot('ilt_sessions', $session);
        $event->add_record_snapshot('ilt', $ilt);
        $event->trigger();

        $message = get_string('bookingcancelled', 'ilt');

        if ($session->datetimeknown) {
            $error = ilt_send_cancellation_notice($ilt, $session, $USER->id);
            if (empty($error)) {
                if ($session->datetimeknown && $ilt->cancellationinstrmngr) {
                    $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') . get_string('cancellationsentmgr', 'ilt');
                } else {
                    $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') . get_string('cancellationsent', 'ilt');
                }
            } else {
                print_error($error, 'ilt');
            }
        }

        redirect($returnurl, $message, $timemessage);
    } else {

        // Logging and events trigger.
        $params = array(
            'context'  => $contextmodule,
            'objectid' => $session->id
        );
        $event = \mod_ilt\event\cancel_booking_failed::create($params);
        $event->add_record_snapshot('ilt_sessions', $session);
        $event->add_record_snapshot('ilt', $ilt);
        $event->trigger();

        redirect($returnurl, $errorstr, $timemessage);
    }

    redirect($returnurl);
}

$pagetitle = format_string($ilt->name);

$PAGE->set_cm($cm);
$PAGE->set_url('/mod/ilt/cancelsignup.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions, 'confirm' => $confirm));

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

$heading = get_string('cancelbookingfor', 'ilt', $ilt->name);

$viewattendees = has_capability('mod/ilt:viewattendees', $context);
$signedup = ilt_check_signup($ilt->id);

echo $OUTPUT->box_start();
echo $OUTPUT->heading($heading);

if ($signedup) {
    ilt_print_session($session, $viewattendees);
    $mform->display();
} else {
    print_error('notsignedup', 'ilt', $returnurl);
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
