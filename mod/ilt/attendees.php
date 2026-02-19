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
require_once($CFG->dirroot . '/mod/ilt/lib.php');

// Face-to-face session ID.
$s = required_param('s', PARAM_INT);

$takeattendance = optional_param('takeattendance', false, PARAM_BOOL); // Take attendance.
$cancelform = optional_param('cancelform', false, PARAM_BOOL); // Cancel request.
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT); // Face-to-face activity to return to.

// Load data.
if (!$session = ilt_get_session($s)) {
    print_error('error:incorrectcoursemodulesession', 'ilt');
}
if (!$ilt = $DB->get_record('ilt', array('id' => $session->ilt))) {
    print_error('error:incorrectiltid', 'ilt');
}
if (!$course = $DB->get_record('course', array('id' => $ilt->course))) {
    print_error('error:coursemisconfigured', 'ilt');
}
if (!$cm = get_coursemodule_from_instance('ilt', $ilt->id, $course->id)) {
    print_error('error:incorrectcoursemodule', 'ilt');
}

// Load attendees.
$attendees = ilt_get_attendees($session->id);

// Load cancellations.
$cancellations = ilt_get_cancellations($session->id);


/*
 * Capability checks to see if the current user can view this page
 *
 * This page is a bit of a special case in this respect as there are four uses for this page.
 *
 * 1) Viewing attendee list
 *   - Requires mod/ilt:viewattendees capability in the course
 *
 * 2) Viewing cancellation list
 *   - Requires mod/ilt:viewcancellations capability in the course
 *
 * 3) Taking attendance
 *   - Requires mod/ilt:takeattendance capabilities in the course
 */
$context = context_course::instance($course->id);
$contextmodule = context_module::instance($cm->id);
require_course_login($course);

// Actions the user can perform.
$canviewattendees = has_capability('mod/ilt:viewattendees', $context);
$cantakeattendance = has_capability('mod/ilt:takeattendance', $context);
$canviewcancellations = has_capability('mod/ilt:viewcancellations', $context);
$canviewsession = $canviewattendees || $cantakeattendance || $canviewcancellations;
$canapproverequests = false;

$requests = array();
$declines = array();

// If a user can take attendance, they can approve staff's booking requests.
if ($cantakeattendance) {
    $requests = ilt_get_requests($session->id);
}

// If requests found (but not in the middle of taking attendance), show requests table.
if ($requests && !$takeattendance) {
    $canapproverequests = true;
}

// Check the user is allowed to view this page.
if (!$canviewattendees && !$cantakeattendance && !$canapproverequests && !$canviewcancellations) {
    print_error('nopermissions', '', "{$CFG->wwwroot}/mod/ilt/view.php?id={$cm->id}", get_string('view'));
}

// Check user has permissions to take attendance.
if ($takeattendance && !$cantakeattendance) {
    print_error('nopermissions', '', '', get_capability_string('mod/ilt:takeattendance'));
}


/*
 * Handle submitted data
 */
if ($form = data_submitted()) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    $return = "{$CFG->wwwroot}/mod/ilt/attendees.php?s={$s}&backtoallsessions={$backtoallsessions}";

    if ($cancelform) {
        redirect($return);
    } else if (!empty($form->requests)) {

        // Approve requests.
        if ($canapproverequests && ilt_approve_requests($form)) {

            // Logging and events trigger.
            $params = array(
                'context'  => $contextmodule,
                'objectid' => $session->id
            );
            $event = \mod_ilt\event\approve_requests::create($params);
            $event->add_record_snapshot('ilt_sessions', $session);
            $event->add_record_snapshot('ilt', $ilt);
            $event->trigger();
        }

        redirect($return);
    } else if ($takeattendance) {
        if (ilt_take_attendance($form)) {

            // Logging and events trigger.
            $params = array(
                'context'  => $contextmodule,
                'objectid' => $session->id
            );
            $event = \mod_ilt\event\take_attendance::create($params);
            $event->add_record_snapshot('ilt_sessions', $session);
            $event->add_record_snapshot('ilt', $ilt);
            $event->trigger();
        } else {

            // Logging and events trigger.
            $params = array(
                'context'  => $contextmodule,
                'objectid' => $session->id
            );
            $event = \mod_ilt\event\take_attendance_failed::create($params);
            $event->add_record_snapshot('ilt_sessions', $session);
            $event->add_record_snapshot('ilt', $ilt);
            $event->trigger();
        }
        redirect($return.'&takeattendance=1');
    }
}

/*
 * Print page header
 */

// Logging and events trigger.
$params = array(
    'context'  => $contextmodule,
    'objectid' => $session->id
);
$event = \mod_ilt\event\attendees_viewed::create($params);
$event->add_record_snapshot('ilt_sessions', $session);
$event->add_record_snapshot('ilt', $ilt);
$event->trigger();

$pagetitle = format_string($ilt->name);

$PAGE->set_url('/mod/ilt/attendees.php', array('s' => $s));
$PAGE->set_context($context);
$PAGE->set_cm($cm);

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

/*
 * Print page content
 */

// If taking attendance, make sure the session has already started.
if ($takeattendance && $session->datetimeknown && !ilt_has_session_started($session, time())) {
    $link = "{$CFG->wwwroot}/mod/ilt/attendees.php?s={$session->id}";
    print_error('error:canttakeattendanceforunstartedsession', 'ilt', $link);
}

echo $OUTPUT->box_start();
echo $OUTPUT->heading(format_string($ilt->name));

if ($canviewsession) {
    echo ilt_print_session($session, true);
}

/*
 * Print attendees (if user able to view)
 */
if ($canviewattendees || $cantakeattendance) {
    if ($takeattendance) {
        $heading = get_string('takeattendance', 'ilt');
    } else {
        $heading = get_string('attendees', 'ilt');
    }

    echo $OUTPUT->heading($heading);

    if (empty($attendees)) {
        echo $OUTPUT->notification(get_string('nosignedupusers', 'ilt'));
    } else {
        if ($takeattendance) {
            $attendeesurl = new moodle_url('attendees.php', array('s' => $s, 'takeattendance' => '1'));
            echo html_writer::start_tag('form', array('action' => $attendeesurl, 'method' => 'post'));
            echo html_writer::tag('p', get_string('attendanceinstructions', 'ilt'));
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 's', 'value' => $s));
            echo html_writer::empty_tag('input', array('type' => 'hidden', ' name' => 'backtoallsessions',
                    'value' => $backtoallsessions)) . '</p>';

            // Prepare status options array.
            $statuses = ilt_statuses();
            $statusoptions = array();
            foreach ($statuses as $key => $value) {
                if ($key <= MDL_ILT_STATUS_BOOKED) {
                    continue;
                }

                $statusoptions[$key] = get_string('status_'.$value, 'ilt');
            }
        }

        $table = new html_table();
        $table->head = array(get_string('name'));
        $table->summary = get_string('attendeestablesummary', 'ilt');
        $table->align = array('left');
        $table->size = array('100%');

        if ($takeattendance) {
            $table->head[] = get_string('currentstatus', 'ilt');
            $table->align[] = 'center';
            $table->head[] = get_string('attendedsession', 'ilt');
            $table->align[] = 'center';
        } else {
            if (!get_config(null, 'ilt_hidecost')) {
                $table->head[] = get_string('cost', 'ilt');
                $table->align[] = 'center';
                if (!get_config(null, 'ilt_hidediscount')) {
                    $table->head[] = get_string('discountcode', 'ilt');
                    $table->align[] = 'center';
                }
            }

            $table->head[] = get_string('attendance', 'ilt');
            $table->align[] = 'center';
        }

        foreach ($attendees as $attendee) {
            $data = array();
            $attendeeurl = new moodle_url('/user/view.php', array('id' => $attendee->id, 'course' => $course->id));
            $data[] = html_writer::link($attendeeurl, format_string(fullname($attendee)));

            if ($takeattendance) {

                // Show current status.
                $data[] = get_string('status_'.ilt_get_status($attendee->statuscode), 'ilt');

                $optionid = 'submissionid_'.$attendee->submissionid;
                $status = $attendee->statuscode;
                $select = html_writer::select($statusoptions, $optionid, $status);
                $data[] = $select;
            } else {
                if (!get_config(null, 'ilt_hidecost')) {
                    $data[] = ilt_cost($attendee->id, $session->id, $session);
                    if (!get_config(null, 'ilt_hidediscount')) {
                        $data[] = $attendee->discountcode;
                    }
                }
                $data[] = str_replace(' ', '&nbsp;',
                    get_string('status_'.ilt_get_status($attendee->statuscode), 'ilt'));
            }
            $table->data[] = $data;
        }

        echo html_writer::table($table);

        if ($takeattendance) {
            echo html_writer::start_tag('p');
            echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('saveattendance', 'ilt'), 'class'=> 'btn btn-primary'));
            echo '&nbsp;' . html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'cancelform',
                    'value' => get_string('cancel'), 'class'=> 'btn btn-secondary') );
            echo html_writer::end_tag('p') . html_writer::end_tag('form');
        } else {

            // Actions.
            print html_writer::start_tag('p', array('class'=> 'listlink'));
            if ($cantakeattendance && $session->datetimeknown && ilt_has_session_started($session, time())) {

                // Take attendance.
                $attendanceurl = new moodle_url('attendees.php', array('s' => $session->id, 'takeattendance' => '1',
                    'backtoallsessions' => $backtoallsessions));
                echo html_writer::link($attendanceurl, get_string('takeattendance', 'ilt'), array('class'=> 'link')) . ' - ';
            }
        }
    }

    if (!$takeattendance) {
        if (has_capability('mod/ilt:addattendees', $context) ||
            has_capability('mod/ilt:removeattendees', $context)) {

            // Add/remove attendees.
            $editattendeeslink = new moodle_url('editattendees.php', array('s' => $session->id, 'backtoallsessions' => $backtoallsessions));
            echo html_writer::link($editattendeeslink, get_string('addremoveattendees', 'ilt'), array('class'=> 'link')) . ' - ';
        }
    }
}

// Go back.
$url = new moodle_url('/course/view.php', array('id' => $course->id));
if ($backtoallsessions) {
    $url = new moodle_url('/mod/ilt/view.php', array('f' => $ilt->id, 'backtoallsessions' => $backtoallsessions));
}
echo html_writer::link($url, get_string('goback', 'ilt'), array('class'=> 'link underline')) . html_writer::end_tag('p');


/*
 * Print unapproved requests (if user able to view)
 */
if ($canapproverequests) {
    echo html_writer::empty_tag('br', array('id' => 'unapproved'));
    if (!$requests) {
        echo $OUTPUT->notification(get_string('noactionableunapprovedrequests', 'ilt'));
    } else {
        $canbookuser = (ilt_session_has_capacity($session, $contextmodule) || $session->allowoverbook);

        $OUTPUT->heading(get_string('unapprovedrequests', 'ilt'));

        if (!$canbookuser) {
            echo html_writer::tag('p', get_string('cannotapproveatcapacity', 'ilt'));
        }


        $action = new moodle_url('attendees.php', array('s' => $s));
        echo html_writer::start_tag('form', array('action' => $action->out(), 'method' => 'post'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 's', 'value' => $s));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'backtoallsessions',
                'value' => $backtoallsessions)) . html_writer::end_tag('p');

        $table = new html_table();
        $table->summary = get_string('requeststablesummary', 'ilt');
        $table->head = array(get_string('name'), get_string('timerequested', 'ilt'),
                get_string('decidelater', 'ilt'), get_string('decline', 'ilt'),
                get_string('approve', 'ilt'));
        $table->align = array('left', 'center', 'center', 'center', 'center');

        foreach ($requests as $attendee) {
            $data = array();
            $attendeelink = new moodle_url('/user/view.php', array('id' => $attendee->id, 'course' => $course->id));
            $data[] = html_writer::link($attendeelink, format_string(fullname($attendee)));
            $data[] = userdate($attendee->timerequested, get_string('strftimedatetime'));
            $data[] = html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'requests['.$attendee->id.']',
                'value' => '0', 'checked' => 'checked'));
            $data[] = html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'requests['.$attendee->id.']',
                'value' => '1'));
            $disabled = ($canbookuser) ? array() : array('disabled' => 'disabled');
            $data[] = html_writer::empty_tag('input', array_merge(array('type' => 'radio', 'name' => 'requests['.$attendee->id.']',
                'value' => '2'), $disabled));
            $table->data[] = $data;
        }

        echo html_writer::table($table);

        echo html_writer::tag('p', html_writer::empty_tag('input', array('type' => 'submit',
            'value' => get_string('updaterequests', 'ilt'))));
        echo html_writer::end_tag('form');
    }
}

/*
 * Print cancellations (if user able to view)
 */
if (!$takeattendance && $canviewcancellations && $cancellations) {

    echo html_writer::empty_tag('br');
    echo $OUTPUT->heading(get_string('cancellations', 'ilt'));

    $table = new html_table();
    $table->summary = get_string('cancellationstablesummary', 'ilt');
    $table->head = array(get_string('name'), get_string('timesignedup', 'ilt'),
                         get_string('timecancelled', 'ilt'), get_string('cancelreason', 'ilt'));
    $table->align = array('left', 'center', 'center');

    foreach ($cancellations as $attendee) {
        $data = array();
        $attendeelink = new moodle_url('/user/view.php', array('id' => $attendee->id, 'course' => $course->id));
        $data[] = html_writer::link($attendeelink, format_string(fullname($attendee)));
        $data[] = userdate($attendee->timesignedup, get_string('strftimedatetime'));
        $data[] = userdate($attendee->timecancelled, get_string('strftimedatetime'));
        $data[] = format_string($attendee->cancelreason);
        $table->data[] = $data;
    }
    echo html_writer::table($table);
}

/*
 * Print page footer
 */
echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
