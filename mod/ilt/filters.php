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

/* @Author VaibhavG
 * @desc include the ilt.js file to get classroom values according to it's location
 * @date 13Dec2018
 * Start code
 */
global $PAGE;
$PAGE->requires->js( new moodle_url('https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js'));
//$PAGE->requires->js(new moodle_url('/mod/ilt/js/ilt.js'));
$PAGE->requires->js_call_amd('mod_ilt/ilt', 'init');
/* @Author VaibhavG
 * End Code
 */

define('MAX_USERS_PER_PAGE', 5000);

$s                  = 11;//required_param('s', PARAM_INT); // Facetoface session ID.
$add                = optional_param('add', 0, PARAM_BOOL);
$remove             = optional_param('remove', 0, PARAM_BOOL);
$showall            = optional_param('showall', 0, PARAM_BOOL);
$searchtext         = optional_param('searchtext', '', PARAM_TEXT); // Search string.
$suppressemail      = optional_param('suppressemail', false, PARAM_BOOL); // Send email notifications.
$previoussearch     = optional_param('previoussearch', 0, PARAM_BOOL);
$backtoallsessions  = optional_param('backtoallsessions', 0, PARAM_INT); // Facetoface activity to go back to.
$user_type = 1; 

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

// Check essential permissions.
require_course_login($course);
$context = context_course::instance($course->id);
require_capability('mod/ilt:viewattendees', $context);

// Get some language strings.
$strsearch = get_string('search');
$strshowall = get_string('showall');
$strsearchresults = get_string('searchresults');
$strilts = get_string('modulenameplural', 'ilt');
$strilt = get_string('modulename', 'ilt');

$errors = array();
    /*
     * @Author VaibhavG
     * @package ILT task
     * @desc passed extra parameter courseid to the ilt_candidate_selector() constructor
     * @date 11Dec2018
     */
// Get the user_selector we will need.
if($user_type == 1)
{
    $potentialuserselector = new ilt_candidate_selector('addselect', array('sessionid' => $session->id, 'courseid' => $course->id));
}else{
    $potentialuserselector = new ilt_all_candidate_selector('addselect', array('sessionid' => $session->id, 'courseid' => $course->id));
}
$existinguserselector = new ilt_existing_selector('removeselect', array('sessionid' => $session->id));

// Process incoming user assignments.
if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    require_capability('mod/ilt:addattendees', $context);
    $userstoassign = $potentialuserselector->get_selected_users();
    
    if (!empty($userstoassign)) {
        foreach ($userstoassign as $adduser) {
            if (!$adduser = clean_param($adduser->id, PARAM_INT)) {
                continue; // Invalid userid.
            }                           

            // Make sure that the user is enroled in the course.
            if (!has_capability('moodle/course:view', $context, $adduser)) {
                $user = $DB->get_record('user', array('id' => $adduser));
                // Make sure that the user is enroled in the course.
                if (!is_enrolled($context, $user)) {
                    if (!enrol_try_internal_enrol($course->id, $user->id)) {
                        $errors[] = get_string('error:enrolmentfailed', 'ilt', fullname($user));
                        $errors[] = get_string('error:addattendee', 'ilt', fullname($user));
                        continue; // Don't sign the user up.
                    }
                }
            }

            $usernamefields = get_all_user_name_fields(true);
            if (ilt_get_session_user($ilt->id,$session->id, $adduser)) {
                $erruser = $DB->get_record('user', array('id' => $adduser), "id, {$usernamefields}");
                $errors[] = get_string('error:addalreadysignedupattendee', 'ilt', fullname($erruser));
            } else {
                if (!ilt_session_has_capacity($session, $context)) {
                    $errors[] = get_string('full', 'ilt');
                    break; // No point in trying to add other people.
                }
                // Check if we are waitlisting or booking.
                if ($session->datetimeknown) {
                    $status = MDL_ILT_STATUS_BOOKED;
                } else {
                    $status = MDL_ILT_STATUS_WAITLISTED;
                }
                if (!ilt_user_signup($session, $ilt, $course, '', MDL_ILT_BOTH,
                $status, $adduser, !$suppressemail)) {
                    $erruser = $DB->get_record('user', array('id' => $adduser), "id, {$usernamefields}");
                    $errors[] = get_string('error:addattendee', 'ilt', fullname($erruser));
                }
            }
        }
        $potentialuserselector->invalidate_selected_users();
        $existinguserselector->invalidate_selected_users();
    }
}

// Process removing user assignments from session.
if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    require_capability('mod/ilt:removeattendees', $context);
    $userstoremove = $existinguserselector->get_selected_users();
    if (!empty($userstoremove)) {
        foreach ($userstoremove as $removeuser) {
            if (!$removeuser = clean_param($removeuser->id, PARAM_INT)) {
                continue; // Invalid userid.
            }

            if (ilt_user_cancel($session, $removeuser, true, $cancelerr)) {

                // Notify the user of the cancellation if the session hasn't started yet.
                $timenow = time();
                if (!$suppressemail and !ilt_has_session_started($session, $timenow)) {
                    ilt_send_cancellation_notice($ilt, $session, $removeuser);
                }
            } else {
                $errors[] = $cancelerr;
                $usernamefields = get_all_user_name_fields(true);
                $erruser = $DB->get_record('user', array('id' => $removeuser), "id, {$usernamefields}");
                $errors[] = get_string('error:removeattendee', 'ilt', fullname($erruser));
            }
        }
        $potentialuserselector->invalidate_selected_users();
        $existinguserselector->invalidate_selected_users();

        // Update attendees.
        ilt_update_attendees($session);
    }
}

// Main page.
$pagetitle = format_string($ilt->name);

$PAGE->set_cm($cm);
$PAGE->set_url('/mod/ilt/editattendees.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions));

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('addremoveattendees', 'ilt'));

// Create user_selector form.
$out = html_writer::start_tag('form', array('id' => 'assignform', 'method' => 'post', 'action' => $PAGE->url));
$out .= html_writer::start_tag('div',array('class'=>'modal fade', 'id'=>'exampleModal', 'tabindex'=>'-1', 'role'=>'dialog', 'aria-labelledby'=>'exampleModalLabel', 'aria-hidden'=>'true'));
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "previoussearch", 'value' => $previoussearch));
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "backtoallsessions", 'value' => $backtoallsessions));
$out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => "sesskey", 'value' => sesskey()));

$table = new html_table();
$table->attributes['class'] = "generaltable generalbox boxaligncenter";
$cells = array();
$content = html_writer::start_tag('p') . html_writer::tag('label', get_string('attendees', 'ilt'),
        array('for' => 'removeselect')) . html_writer::end_tag('p');
$content .= $existinguserselector->display(true);
$cell = new html_table_cell($content);
$cell->attributes['id'] = 'existingcell';
$cells[] = $cell;
$content = html_writer::tag('div', html_writer::empty_tag('input',
    array('type' => 'submit', 'id' => 'add', 'name' => 'add', 'title' => get_string('add'),
        'value' => $OUTPUT->larrow().' '.get_string('add'))), array('id' => 'addcontrols'));
$content .= html_writer::tag('div', html_writer::empty_tag('input',
    array('type' => 'submit', 'id' => 'remove', 'name' => 'remove', 'title' => get_string('remove'),
        'value' => $OUTPUT->rarrow().' '.get_string('remove'))), array('id' => 'removecontrols'));
$content .= html_writer::empty_tag('input',
    array('type' => 'hidden', 'id' => 'sessionid', 'name' => 'sessionid',
        'value' => $s));
$content .= html_writer::empty_tag('input',
    array('type' => 'hidden', 'id' => 'courseid', 'name' => 'courseid',
        'value' => $course->id));
    $sql_allowoverbook = 'SELECT allowoverbook
                        FROM
                            mdl_ilt_sessions 
                        WHERE
                            id = ?  
                        ';
    //getting allowoverbook flag and pass to JS using hidden field
    $record_allowoverbook = $DB->get_records_sql($sql_allowoverbook, array($s));
    foreach($record_allowoverbook as $allow)
        $allow->allowoverbook;
    
$content .= html_writer::empty_tag('input',
    array('type' => 'hidden', 'id' => 'allowoverbook', 'name' => 'allowoverbook',
        'value' => $allow->allowoverbook));

$cell = new html_table_cell($content);
$cell->attributes['id'] = 'buttonscell';
$cells[] = $cell;
$content = html_writer::start_tag('p') . html_writer::tag('label',
        get_string('potentialattendees', 'ilt'), array('for' => 'addselect')) .html_writer::empty_tag('input', array('type' => 'radio', 'label' => get_string('iltcourseusers', 'ilt'), 'id' => 'id_courseusers', 'name' => 'users',
                'value' => 'Course', 'checked' => 'checked')).html_writer::tag('label',
        get_string('iltcourseusers', 'ilt'), array('for' => 'users')) .html_writer::empty_tag('input', array('type' => 'radio', 'label' => get_string('iltallusers', 'ilt'), 'id' => 'id_allusers', 'name' => 'users',
                'value' => 'All')) .html_writer::tag('label',
        get_string('iltallusers', 'ilt'), array('for' => 'users')) ;

//$content .= html_writer::link(new moodle_url($securewwwroot . '/mod/ilt/filters.php', array('classid' => $venuemanangement->id)),get_string('iltfilters','ilt')). html_writer::end_tag('p');
//$content .= html_writer::link(new moodle_url('/local/users/index.php'),get_string('iltfilters','ilt'),array('class' => ''));
$content .= '<a href="http://localhost/phoenix/public_html/mod/ilt/filters.php" class="btn btn-info btn-lg"  data-toggle="modal" data-target="#myModal">
                  <span class="fa fa-filter"></span> Filter 
            </a>';
$content .= $potentialuserselector->display(true);
$cell = new html_table_cell($content);
$cell->attributes['id'] = 'potentialcell';
$cells[] = $cell;
$table->data[] = new html_table_row($cells);
$content = html_writer::checkbox('suppressemail', 1, $suppressemail, get_string('suppressemail', 'ilt'),
    array('id' => 'suppressemail'));
$content .= $OUTPUT->help_icon('suppressemail', 'ilt');
$cell = new html_table_cell($content);
$cell->attributes['id'] = 'backcell';
$cell->attributes['colspan'] = '3';
$table->data[] = new html_table_row(array($cell));

$out .= html_writer::table($table);

// Get all signed up non-attendees.
$nonattendees = 0;
$usernamefields = get_all_user_name_fields(true, 'u');
$nonattendeesrs = $DB->get_recordset_sql(
     "SELECT
            u.id,
            {$usernamefields},
            u.email,
            ss.statuscode
        FROM
            {ilt_sessions} s
        JOIN
            {ilt_signups} su
         ON s.id = su.sessionid
        JOIN
            {ilt_signups_status} ss
         ON su.id = ss.signupid
        JOIN
            {user} u
         ON u.id = su.userid
        WHERE
            s.id = ?
        AND ss.superceded != 1
        AND ss.statuscode = ?
        ORDER BY
            u.lastname, u.firstname", array($session->id, MDL_ILT_STATUS_REQUESTED)
);

$table = new html_table();
$table->head = array(get_string('name'), get_string('email'), get_string('status'));

foreach ($nonattendeesrs as $user) {
    $data = array();
    $data[] = new html_table_cell(fullname($user));
    $data[] = new html_table_cell($user->email);
    $data[] = new html_table_cell(get_string('status_' . ilt_get_status($user->statuscode), 'ilt'));
    $row = new html_table_row($data);
    $table->data[] = $row;
    $nonattendees++;
}

$nonattendeesrs->close();
if ($nonattendees) {
    $out .= html_writer::empty_tag('br');
    $out .= $OUTPUT->heading(get_string('unapprovedrequests', 'ilt') . ' (' . $nonattendees . ')');
    $out .= html_writer::table($table);
}

$out .= html_writer::end_tag('div') . html_writer::end_tag('form');
echo $out;

if (!empty($errors)) {
    $msg = html_writer::start_tag('p');
    foreach ($errors as $e) {
        $msg .= $e . html_writer::empty_tag('br');
    }
    $msg .= html_writer::end_tag('p');
    echo $OUTPUT->box_start('center');
    echo $OUTPUT->notification($msg);
    echo $OUTPUT->box_end();
}

// Bottom of the page links.
echo html_writer::start_tag('p');
$url = new moodle_url('/mod/ilt/attendees.php', array('s' => $session->id, 'backtoallsessions' => $backtoallsessions));
echo html_writer::link($url, get_string('goback', 'ilt'));
echo html_writer::end_tag('p');
echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
