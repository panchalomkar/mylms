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
global $CFG, $PAGE;
$PAGE->requires->jquery();

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
$f = optional_param('f', 0, PARAM_INT); // ilt Module ID.
$s = optional_param('s', 0, PARAM_INT); // ilt session ID.
$c = optional_param('c', 0, PARAM_INT); // Copy session.
$d = optional_param('d', 0, PARAM_INT); // Delete session.
$classroom = optional_param('classroom', null, PARAM_TEXT); // Delete session.
$resource = optional_param('sessionresource', null, PARAM_TEXT); // Delete session.
$confirm = optional_param('confirm', false, PARAM_BOOL); // Delete confirmation.

$nbdays = 1; // Default number to show.

$session = null;
if ($id && !$s) {
    if (!$cm = $DB->get_record('course_modules', array('id' => $id))) {
        print_error('error:incorrectcoursemoduleid', 'ilt');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('error:coursemisconfigured', 'ilt');
    }
    if (!$ilt = $DB->get_record('ilt', array('id' => $cm->instance))) {
        print_error('error:incorrectcoursemodule', 'ilt');
    }
} else if ($s) {
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
        print_error('error:incorrectcoursemoduleid', 'ilt');
    }

    $nbdays = count($session->sessiondates);
} else {
    if (!$ilt = $DB->get_record('ilt', array('id' => $f))) {
        print_error('error:incorrectiltid', 'ilt');
    }
    if (!$course = $DB->get_record('course', array('id' => $ilt->course))) {
        print_error('error:coursemisconfigured', 'ilt');
    }
    if (!$cm = get_coursemodule_from_instance('ilt', $ilt->id, $course->id)) {
        print_error('error:incorrectcoursemoduleid', 'ilt');
    }
}

require_course_login($course);
$errorstr = '';
$context = context_course::instance($course->id);
$modulecontext = context_module::instance($cm->id);
require_capability('mod/ilt:editsessions', $context);

$PAGE->set_cm($cm);
$PAGE->set_url('/mod/ilt/sessions.php', array('f' => $f));

$returnurl = "view.php?f=$ilt->id";

$editoroptions = array(
    'noclean'  => false,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $course->maxbytes,
    'context'  => $modulecontext,
);


// Handle deletions.
if ($d and $confirm) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    if (ilt_delete_session($session)) {

        // Logging and events trigger.
        $params = array(
            'context'  => $modulecontext,
            'objectid' => $session->id
        );
        $event = \mod_ilt\event\delete_session::create($params);
        $event->add_record_snapshot('ilt_sessions', $session);
        $event->add_record_snapshot('ilt', $ilt);
        $event->trigger();
    } else {

        // Logging and events trigger.
        $params = array(
            'context'  => $modulecontext,
            'objectid' => $session->id
        );
        $event = \mod_ilt\event\delete_session_failed::create($params);
        $event->add_record_snapshot('ilt_sessions', $session);
        $event->add_record_snapshot('ilt', $ilt);
        $event->trigger();
        print_error('error:couldnotdeletesession', 'ilt', $returnurl);
    }
    redirect($returnurl);
}

$customfields = ilt_get_session_customfields();

$sessionid = isset($session->id) ? $session->id : 0;

$details = new stdClass();
$details->id = isset($session) ? $session->id : 0;
$details->details = isset($session->details) ? $session->details : '';
$details->detailsformat = FORMAT_HTML;
$details = file_prepare_standard_editor($details, 'details', $editoroptions, $modulecontext, 'mod_ilt', 'session', $sessionid);

$mform = new mod_ilt_session_form(null, compact('id', 'ilt', 'f', 's', 'c', 'nbdays', 'customfields', 'course', 'editoroptions'));

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) { // Form submitted.\

    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'ilt', $returnurl);
    }

    // Pre-process fields.
    if (empty($fromform->allowoverbook)) {
        $fromform->allowoverbook = 0;
    }
    if (empty($fromform->duration)) {
        $fromform->duration = 0;
    }
    if (empty($fromform->normalcost)) {
        $fromform->normalcost = 0;
    }
    if (empty($fromform->discountcost)) {
        $fromform->discountcost = 0;
    }

    $sessiondates = array();
    for ($i = 0; $i < $fromform->date_repeats; $i++) {
        if (!empty($fromform->datedelete[$i])) {
            continue; // Skip this date.
        }

        if (!empty($fromform->timestart[$i]) and !empty($fromform->timefinish[$i])) {
            $date = new stdClass();
            $date->timestart = $fromform->timestart[$i];
            $date->timefinish = $fromform->timefinish[$i];
            $sessiondates[] = $date;
        }
    }

    $todb = new stdClass();
    
    $todb->ilt = $ilt->id;
    
    $todb->datetimeknown = $fromform->datetimeknown;
    $todb->capacity = $fromform->sessioncapacity;
    $todb->allowoverbook = $fromform->allowoverbook;
    $todb->duration = $fromform->duration;
    $todb->normalcost = $fromform->normalcost;
    $todb->discountcost = $fromform->discountcost;
    if (has_capability('mod/ilt:configurecancellation', $context)) {
        $todb->allowcancellations = $fromform->allowcancellations;
    }
    /*
    * @Author VaibhavG
    * @package  #10: ILT Custom work - Assign multiple instructors in ILT Session
    * @desc added instructor drop down , Location text field & classroom text field values to db.
    * @date 12Dec2018
    * @start code
    */

        $todb->instructor = implode(',',$fromform->sessioninstructor);
        $todb->location = $fromform->sessionlocation;
        $todb->classroom = $classroom;
        $todb->bu = $fromform->sessioncostcenter;
        $todb->sessionname = $fromform->sessionname;
        $todb->resource = implode(',', (array)$resource);
       // print_r($todb->resource);exit;
    /*
    * @Author VaibhavG
    * @package #10: ILT Custom work - Assign multiple instructors in ILT Session
    * @desc added instructor drop down , Location text field & classroom text field values to db.
    * @date 12Dec2018
    * @End code
    */
    $sessionid = null;
    $transaction = $DB->start_delegated_transaction();

    $update = false;
    if (!$c and $session != null) {
        $update = true;
        $sessionid = $session->id;

        $todb->id = $session->id;
        if (!ilt_update_session($todb, $sessiondates)) {
            $transaction->force_transaction_rollback();

            // Logging and events trigger.
            $params = array(
                'context'  => $modulecontext,
                'objectid' => $session->id
            );
            $event = \mod_ilt\event\update_session_failed::create($params);
            $event->add_record_snapshot('ilt_sessions', $session);
            $event->add_record_snapshot('ilt', $ilt);
            $event->trigger();
            print_error('error:couldnotupdatesession', 'ilt', $returnurl);
        }

        // Remove old site-wide calendar entry.
        if (!ilt_remove_session_from_calendar($session, SITEID)) {
            $transaction->force_transaction_rollback();
            print_error('error:couldnotupdatecalendar', 'ilt', $returnurl);
        }
    } else {
        if (!$sessionid = ilt_add_session($todb, $sessiondates)) {
            $transaction->force_transaction_rollback();

            // Logging and events trigger.
            $params = array(
                'context'  => $modulecontext,
                'objectid' => $ilt->id
            );
            $event = \mod_ilt\event\add_session_failed::create($params);
            $event->add_record_snapshot('ilt', $ilt);
            $event->trigger();
            print_error('error:couldnotaddsession', 'ilt', $returnurl);
        }
    }

    foreach ($customfields as $field) {
        $fieldname = "custom_$field->shortname";
        if (!isset($fromform->$fieldname)) {
            $fromform->$fieldname = ''; // Need to be able to clear fields.
        }

        if (!ilt_save_customfield_value($field->id, $fromform->$fieldname, $sessionid, 'session')) {
            $transaction->force_transaction_rollback();
            print_error('error:couldnotsavecustomfield', 'ilt', $returnurl);
        }
    }

    // Save trainer roles.
    if (isset($fromform->trainerrole)) {
        ilt_update_trainers($sessionid, $fromform->trainerrole);
    }

    // Retrieve record that was just inserted/updated.
    if (!$session = ilt_get_session($sessionid)) {
        $transaction->force_transaction_rollback();
        print_error('error:couldnotfindsession', 'ilt', $returnurl);
    }

    // Update calendar entries.
    ilt_update_calendar_entries($session, $ilt);
    if ($update) {

        // Logging and events trigger.
        $params = array(
            'context'  => $modulecontext,
            'objectid' => $session->id
        );
        $event = \mod_ilt\event\update_session::create($params);
        $event->add_record_snapshot('ilt_sessions', $session);
        $event->add_record_snapshot('ilt', $ilt);
        $event->trigger();
    } else {

        // Logging and events trigger.
        $params = array(
            'context'  => $modulecontext,
            'objectid' => $session->id
        );
        $event = \mod_ilt\event\add_session::create($params);
        $event->add_record_snapshot('ilt_sessions', $session);
        $event->add_record_snapshot('ilt', $ilt);
        $event->trigger();
    }

    $transaction->allow_commit();

    $data = file_postupdate_standard_editor($fromform, 'details', $editoroptions, $modulecontext, 'mod_ilt', 'session', $session->id);
    $DB->set_field('ilt_sessions', 'details', $data->details, array('id' => $session->id));

    redirect($returnurl);
} else if ($session != null) { // Edit mode.

    // Set values for the form.
    $toform = new stdClass();
    $toform = file_prepare_standard_editor($details, 'details', $editoroptions, $modulecontext, 'mod_ilt', 'session', $session->id);

    $toform->datetimeknown = (1 == $session->datetimeknown);
    $toform->sessioncapacity = $session->sessioncapacity;
    $toform->allowoverbook = $session->allowoverbook;
    $toform->duration = $session->duration;
    $toform->normalcost = $session->normalcost;
    $toform->discountcost = $session->discountcost;
    if (has_capability('mod/ilt:configurecancellation', $context)) {
        $toform->allowcancellations = $session->allowcancellations;
    }
    /*
    * @Author VaibhavG
    * @package #10: ILT Custom work - Assign multiple instructors in ILT Session
    * @desc added instructor drop down , Location text field & classroom text field values to db.
    * @date 13Dec2018
    * @start code
    */
        $toform->sessioninstructor = implode(',',$session->sessioninstructor);
        $toform->sessionlocation = $session->sessionlocation;
        $toform->classroom = $session->classroom;
        $toform->bu = $session->sessioncostcenter;
        $toform->sessionname = $session->sessionname;
        $toform->sessionresource = $session->sessionresource;
    /*
    * @Author VaibhavG
    * @package #10: ILT Custom work - Assign multiple instructors in ILT Session
    * @desc added instructor drop down , Location text field & classroom text field values to db.
    * @date 13Dec2018
    * @End code
    */
    if ($session->sessiondates) {
        $i = 0;
        foreach ($session->sessiondates as $date) {
            $idfield = "sessiondateid[$i]";
            $timestartfield = "timestart[$i]";
            $timefinishfield = "timefinish[$i]";
            $toform->$idfield = $date->id;
            $toform->$timestartfield = $date->timestart;
            $toform->$timefinishfield = $date->timefinish;
            $i++;
        }
    }

    foreach ($customfields as $field) {
        $fieldname = "custom_$field->shortname";
        $toform->$fieldname = ilt_get_customfield_value($field, $session->id, 'session');
    }

    $mform->set_data($toform);
}

if ($c) {
    $heading = get_string('copyingsession', 'ilt', $ilt->name);
} else if ($d) {
    $heading = get_string('deletingsession', 'ilt', $ilt->name);
} else if ($id || $f) {
    $heading = get_string('addingsession', 'ilt', $ilt->name);
} else {
    $heading = get_string('editingsession', 'ilt', $ilt->name);
}

$pagetitle = format_string($ilt->name);


$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

echo $OUTPUT->box_start();
echo $OUTPUT->heading($heading);

if (!empty($errorstr)) {
    echo $OUTPUT->container(html_writer::tag('span', $errorstr, array('class' => 'errorstring')), array('class' => 'notifyproblem'));
}

if ($d) {
    $viewattendees = has_capability('mod/ilt:viewattendees', $context);
    ilt_print_session($session, $viewattendees);
    $optionsyes = array('sesskey' => sesskey(), 's' => $session->id, 'd' => 1, 'confirm' => 1);
    echo $OUTPUT->confirm(get_string('deletesessionconfirm', 'ilt', format_string($ilt->name)),
        new moodle_url('sessions.php', $optionsyes),
        new moodle_url($returnurl));
} else {
    $mform->display();
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
