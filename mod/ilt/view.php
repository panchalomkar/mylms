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
require_once('renderer.php');

global $DB, $OUTPUT, $PAGE;

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
$f = optional_param('f', 0, PARAM_INT); // Facetoface ID.
$location = optional_param('location', '', PARAM_TEXT); // Location.
$download = optional_param('download', '', PARAM_ALPHA); // Download attendance.
$upage = optional_param('upage', 0, PARAM_INT); // upcoming table page
$ppage = optional_param('ppage', 0, PARAM_INT); // previous table page
$perpage = 5;
if ($id) {
    if (!$cm = $DB->get_record('course_modules', array('id' => $id))) {
        print_error('error:incorrectcoursemoduleid', 'ilt');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('error:coursemisconfigured', 'ilt');
    }
    if (!$ilt = $DB->get_record('ilt', array('id' => $cm->instance))) {
        print_error('error:incorrectcoursemodule', 'ilt');
    }
} else if ($f) {
    if (!$ilt = $DB->get_record('ilt', array('id' => $f))) {
        print_error('error:incorrectiltid', 'ilt');
    }
    if (!$course = $DB->get_record('course', array('id' => $ilt->course))) {
        print_error('error:coursemisconfigured', 'ilt');
    }
    if (!$cm = get_coursemodule_from_instance('ilt', $ilt->id, $course->id)) {
        print_error('error:incorrectcoursemoduleid', 'ilt');
    }
} else {
    print_error('error:mustspecifycoursemoduleilt', 'ilt');
}

$context = context_module::instance($cm->id);
//baseurl of the pagination
$pageparams = array('id' => $cm->id);
if($upage){
    $pageparams['upage'] = $upage;
}
if($ppage){
    $pageparams['ppage'] = $ppage;
}
if($ppage){
    $pageparams['location'] = $location;
}
$PAGE->set_url('/mod/ilt/view.php', $pageparams);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');

if (!empty($download)) {
    require_capability('mod/ilt:viewattendees', $context);
    ilt_download_attendance($ilt->name, $ilt->id, $location, $download);
    exit();
}

require_course_login($course, true, $cm);
require_capability('mod/ilt:view', $context);

// Logging and events trigger.
$params = array(
    'context'  => $context,
    'objectid' => $ilt->id
);
$event = \mod_ilt\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('ilt', $ilt);
$event->trigger();

$title = $course->shortname . ': ' . format_string($ilt->name);

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

$pagetitle = format_string($ilt->name);

$ILTrenderer = $PAGE->get_renderer('mod_ilt');

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();

if (empty($cm->visible) and !has_capability('mod/ilt:viewemptyactivities', $context)) {
    notice(get_string('activityiscurrentlyhidden'));
}
echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('allsessionsin', 'ilt', $ilt->name), 2,'all_session');

if ($ilt->intro) {
    echo $OUTPUT->box_start('generalbox', 'description');
    echo format_module_intro('ilt', $ilt, $cm->id);
    echo $OUTPUT->box_end();
} else {
    echo html_writer::empty_tag('br');
}
$locations = get_locations($ilt->id);
if (count($locations) > 2) {
    echo html_writer::start_tag('form', array('action' => 'view.php', 'method' => 'get', 'class' => 'formlocation'));
    echo html_writer::start_tag('div');
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'f', 'value' => $ilt->id));
    echo html_writer::select($locations, 'location', $location, '', array('onchange' => 'this.form.submit();'));
    echo html_writer::end_tag('div'). html_writer::end_tag('form');
}

print_session_list($course->id, $ilt->id, $location, $upage, $ppage, $perpage);

if (has_capability('mod/ilt:viewattendees', $context)) {
    echo $OUTPUT->heading(get_string('exportattendance', 'ilt'));
    echo html_writer::start_tag('form', array('action' => 'view.php', 'method' => 'get'));
    echo html_writer::start_tag('div');
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'f', 'value' => $ilt->id));
    echo get_string('format', 'ilt') . '&nbsp;';
    $formats = array('excel' => get_string('excelformat', 'ilt'),
                     'ods' => get_string('odsformat', 'ilt'));
    echo html_writer::select($formats, 'download', 'excel', '');
    echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('exporttofile', 'ilt'), 'class' => 'btn btn-secondary btn-square exporttofile'));
    echo html_writer::end_tag('div'). html_writer::end_tag('form');
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);

function print_session_list($courseid, $iltid, $location, $upage = 0, $ppage = 0, $perpage = 5) {
    global $CFG, $USER, $DB, $OUTPUT, $PAGE;

    $ILTrenderer = $PAGE->get_renderer('mod_ilt');

    $timenow = time();

    $context = context_course::instance($courseid);
    $viewattendees = has_capability('mod/ilt:viewattendees', $context);
    $editsessions = has_capability('mod/ilt:editsessions', $context);

    $bookedsession = null;
    if ($submissions = ilt_get_user_submissions($iltid, $USER->id)) {
        $submission = array_shift($submissions);
        $bookedsession = $submission;
    }

    $customfields = ilt_get_session_customfields();
    
    $ustart = $upage * $perpage;  //calculate offset of the upcoming session  query type 
    list($upcomingarray, $upctotcount) = ilt_get_upcom_prev_sessions($iltid, $location, 'upcoming', $ustart, $perpage);

    $pstart = $ppage * $perpage; //calculate offset of the previous session  query type 
    list($previousarray, $prevtotcount) = ilt_get_upcom_prev_sessions($iltid, $location, 'previous', $pstart, $perpage);
    
    $upcomingtbdarray =array();
    if ($editsessions) {
        $addsessionlink = html_writer::link(
            new moodle_url('sessions.php', array('f' => $iltid)),
            html_writer::tag('i','', array( 'class' => "fa fa-calendar-plus-o", 'aria-hidden'=> "true")) . ' '.
            get_string('addsession', 'ilt'),
            array('class'=> 'addnewsession btn btn-success')
        );
        echo html_writer::tag('p', $addsessionlink,array("class"=>"Add_new_session"));
    }
    // Upcoming sessions.
    echo $OUTPUT->heading(get_string('upcomingsessions', 'ilt'),2,'prev_session');
    if (empty($upcomingarray) && empty($upcomingtbdarray)) {
        print_string('noupcoming', 'ilt');

    } else {
        $upcomingarray = array_merge($upcomingarray, $upcomingtbdarray);
        echo $ILTrenderer->print_session_list_table($customfields, $upcomingarray, $viewattendees, $editsessions);
        echo  $OUTPUT->paging_bar($upctotcount, $upage, $perpage, $PAGE->url, 'upage'); 
    }

   

    // Previous sessions.
    if (!empty($previousarray)) {
        echo $OUTPUT->heading(get_string('previoussessions', 'ilt'),2,'prev_session');
        echo $ILTrenderer->print_session_list_table($customfields, $previousarray, $viewattendees, $editsessions);
        echo  $OUTPUT->paging_bar($prevtotcount, $ppage, $perpage, $PAGE->url, 'ppage');
    }
}

/**
 * Get ilt locations
 *
 * @param   interger    $iltid
 * @return  array
 */
function get_locations($iltid) {
    global $CFG, $DB;

    $locationfieldid = $DB->get_field('ilt_session_field', 'id', array('shortname' => 'location'));
    if (!$locationfieldid) {
        return array();
    }

    $sql = "SELECT DISTINCT d.data AS location
              FROM {ilt} f
              JOIN {ilt_sessions} s ON s.ilt = f.id
              JOIN {ilt_session_data} d ON d.sessionid = s.id
             WHERE f.id = ? AND d.fieldid = ?";

    if ($records = $DB->get_records_sql($sql, array($iltid, $locationfieldid))) {
        $locationmenu[''] = get_string('alllocations', 'ilt');

        $i = 1;
        foreach ($records as $record) {
            $locationmenu[$record->location] = $record->location;
            $i++;
        }

        return $locationmenu;
    }

    return array();
}
