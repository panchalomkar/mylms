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
?>
<style>
 /* =====================================================================
 * mod/ilt — "Attendees" page restyle (Figma match)
 * Add to mod/ilt/styles.css, then purge caches. Scoped to this page only.
 * ===================================================================== */

body#page-mod-ilt-attendees {
    --ilt-navy:   #0e2340;
    --ilt-orange: #f5a623;
    --ilt-green:  #1f9254;
    --ilt-red:    #d64545;
    --ilt-amber:  #c77f1a;
    --ilt-border: #e9edf2;
    --ilt-muted:  #6b7a8d;
    --ilt-ink:    #1f2d3d;
}

/* Keep breadcrumb, hide the theme's duplicate activity title/icon. */
body#page-mod-ilt-attendees #page-header .page-context-header,
body#page-mod-ilt-attendees #page-header .header-actions-container { display: none; }
body#page-mod-ilt-attendees #page-header { background: transparent; border: 0; box-shadow: none; margin-bottom: 0; }

body#page-mod-ilt-attendees div[role="main"] { background: transparent; box-shadow: none; padding: 0; }
body#page-mod-ilt-attendees #region-main .box.generalbox { border: 0; background: transparent; padding: 0 !important; }

/* ---------- Section header bars (the two <h2>s) ---------- */
body#page-mod-ilt-attendees #region-main .box.generalbox > h2 {
    background: var(--ilt-navy);
    color: #fff !important;
    font-size: 16px !important;
    font-weight: 700 !important;
    border-radius: 14px 14px 0 0;
    margin: 26px 0 0 !important;
    padding: 16px 22px !important;
}
body#page-mod-ilt-attendees #region-main .box.generalbox > h2:first-child { margin-top: 0 !important; }

/* ---------- Cards ---------- */
body#page-mod-ilt-attendees .table-responsive {
    background: #fff;
    border: 1px solid var(--ilt-border);
    border-top: 0;
    border-radius: 0 0 14px 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
    overflow: hidden;
    margin-bottom: 24px;
    padding: 20px;
}

/* ---------- Session details table (label / value) ---------- */
body#page-mod-ilt-attendees table.f2fsession {border-radius: 10px;
    overflow: hidden; width: 100%; margin: 0; border-collapse: collapse; }
body#page-mod-ilt-attendees table.f2fsession td.c0 {
    background: var(--ilt-navy);
    color: #fff !important;
    font-weight: 700;
    text-align: left !important;
    white-space: nowrap;
    width: 230px;
    padding: 16px 20px !important;
    border: 0;
}
body#page-mod-ilt-attendees table.f2fsession td.c0::before {
    content: "";
    display: inline-block;
    width: 17px; height: 17px;
    margin-right: 9px;
    vertical-align: -3px;
    background: center/contain no-repeat;
}
/* calendar icon on the first row (Session date/time) */
body#page-mod-ilt-attendees table.f2fsession tr:first-child td.c0::before {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23f5a623' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cpath d='M16 2v4M8 2v4M3 10h18'/%3E%3C/svg%3E");
}
/* users icon on the last row (Capacity) */
body#page-mod-ilt-attendees table.f2fsession tr.lastrow td.c0::before {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23f5a623' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='9' cy='7' r='4'/%3E%3Cpath d='M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'/%3E%3C/svg%3E");
}
body#page-mod-ilt-attendees table.f2fsession td.c1 {
    background: #fff;
    color: var(--ilt-ink);
    text-align: left !important;
    padding: 16px 20px !important;
    border: 0;
    border-bottom: 1px solid var(--ilt-border);
    font-size: 15px;
}
body#page-mod-ilt-attendees table.f2fsession tr:last-child td { border-bottom: 0; }

/* ---------- Attendees table ---------- */
body#page-mod-ilt-attendees table.generaltable.table {border-radius: 10px;
    overflow: hidden; width: 100%; margin: 0; border-collapse: collapse; }
body#page-mod-ilt-attendees table.generaltable.table thead th {
    background: var(--ilt-navy);
    color: #fff;
    font-size: 12px !important;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
    border: 0;
    padding: 14px 20px;
}
body#page-mod-ilt-attendees table.generaltable.table thead th.c0{width: 50% !important;}
body#page-mod-ilt-attendees table.generaltable.table td.c0{width: 50% !important;}
body#page-mod-ilt-attendees table.generaltable.table tbody td {
    background: #fff;
    border: 0;
    border-bottom: 1px solid var(--ilt-border);
    padding: 16px 20px;
    font-size: 15px;
    color: var(--ilt-ink);
    vertical-align: middle;
}
body#page-mod-ilt-attendees table.generaltable.table tbody tr:last-child td { border-bottom: 0; }
body#page-mod-ilt-attendees table.generaltable.table td.c0,
body#page-mod-ilt-attendees table.generaltable.table td.c0 a { font-weight: 700; color: var(--ilt-navy); text-decoration: none; }

/* Name + email under it */
body#page-mod-ilt-attendees table.generaltable.table td.c0 .ilt-att-name { display: block; }
body#page-mod-ilt-attendees table.generaltable.table td.c0 .ilt-att-email {
    font-weight: 500;
    font-size: 13px;
    color: var(--ilt-muted);
    margin-top: 2px;
}

/* Attendance status pill (status is wrapped in <span class="ilt-att ilt-att-<key>">). */
body#page-mod-ilt-attendees table.generaltable.table td.c3 .ilt-att {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1;
    background: #e9edf1;                 /* "Not taken" grey — default */
    color: #667286;
}
body#page-mod-ilt-attendees .ilt-att-booked,
body#page-mod-ilt-attendees .ilt-att-fully_attended     { background: #d8f0e1 !important; color: #2f9e6b !important; }  /* green */
body#page-mod-ilt-attendees .ilt-att-partially_attended,
body#page-mod-ilt-attendees .ilt-att-waitlisted         { background: #f7ecd4 !important; color: #bf8b2f !important; }  /* Late / Partial amber */
/* "N registered" badge in the Attendees bar */
body#page-mod-ilt-attendees #region-main .box.generalbox > h2 .ilt-registered {
    float: right;
    background: var(--ilt-orange);
    color: var(--ilt-navy);
    font-size: 13px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 999px;
    text-transform: none;
    letter-spacing: 0;
}

/* "X booked of Y seats" in the Capacity cell */
body#page-mod-ilt-attendees table.f2fsession .ilt-cap-booked { font-weight: 700; color: var(--ilt-ink); }
body#page-mod-ilt-attendees table.f2fsession .ilt-cap-total  { color: var(--ilt-muted); }

/* ---------- Action links -> buttons (as a footer of the attendees card) ---------- */
body#page-mod-ilt-attendees .table-responsive:has(+ p.listlink) {
    border-radius: 0;
    border-bottom: 0;
    margin-bottom: 0;
    overflow: visible;
}
body#page-mod-ilt-attendees p.listlink {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff;
    border: 1px solid var(--ilt-border);
    border-top: 0;
    border-radius: 0 0 14px 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
    padding: 16px 22px;
    margin: 0 0 24px;
    font-size: 0;                 /* hides the " - " separators between links */
}
body#page-mod-ilt-attendees p.listlink a {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border-radius: 10px;
}
body#page-mod-ilt-attendees p.listlink a::before {
    content: "";
    width: 16px; height: 16px;
    background: center/contain no-repeat;
}
/* Take attendance -> navy button, clipboard icon */
body#page-mod-ilt-attendees p.listlink a[href*="takeattendance"] {
    background: var(--ilt-navy);
    color: #fff;
    padding: 10px 18px;
}
body#page-mod-ilt-attendees p.listlink a[href*="takeattendance"]::before {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M9 2h6a1 1 0 0 1 1 1v1H8V3a1 1 0 0 1 1-1z'/%3E%3Cpath d='M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2'/%3E%3Cpath d='m9 14 2 2 4-4'/%3E%3C/svg%3E");
}
/* Add / remove attendees -> white outline button, user-plus icon */
body#page-mod-ilt-attendees p.listlink a[href*="editattendees"] {
    background: #fff;
    color: var(--ilt-ink);
    border: 1px solid var(--ilt-border);
    padding: 9px 18px;
}
body#page-mod-ilt-attendees p.listlink a[href*="editattendees"]::before {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%230e2340' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='9' cy='7' r='4'/%3E%3Cline x1='19' y1='8' x2='19' y2='14'/%3E%3Cline x1='16' y1='11' x2='22' y2='11'/%3E%3C/svg%3E");
}
/* Go back -> plain orange link (no icon) */
body#page-mod-ilt-attendees p.listlink a.underline,
body#page-mod-ilt-attendees p.listlink a[href*="view.php"] {
    color: var(--ilt-orange);
    text-decoration: none;
    padding: 10px 4px;
    margin-left: auto;            /* push to the right like the Figma */
}
body#page-mod-ilt-attendees p.listlink a.underline::before,
body#page-mod-ilt-attendees p.listlink a[href*="view.php"]::before { display: none; }
/* take attendees */
/* =====================================================================
 * ADD-ON: "Take attendance" view. Scoped to the take-attendance form so
 * it never affects the normal attendees view. Append to the existing
 * <style> block — do not edit the existing rules.
 * ===================================================================== */

/* Current-status pill (this column is c1 in take-attendance) */
body#page-mod-ilt-attendees table.generaltable.table td.c1 .ilt-att {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1;
    background: #e9edf1;
    color: #667286;
}

/* Wrap the whole take-attendance form as the white card body under the navy bar */
body#page-mod-ilt-attendees form[action*="takeattendance"] {
    background: #fff;
    border: 1px solid var(--ilt-border);
    border-top: 0;
    border-radius: 0 0 14px 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
    padding: 18px 22px;
    margin-bottom: 8px;
}
/* the inner table-responsive should not be a second card */
body#page-mod-ilt-attendees form[action*="takeattendance"] .table-responsive {
    background: transparent;
    border: 0;
    box-shadow: none;
    border-radius: 0;
    padding: 0;
    margin: 14px 0;
    overflow: visible;
}
body#page-mod-ilt-attendees form[action*="takeattendance"] table.generaltable.table {
    border: 1px solid var(--ilt-border);
    border-radius: 12px;
    overflow: hidden;
}
/* "Attended session" dropdown */
body#page-mod-ilt-attendees form[action*="takeattendance"] td.c2 select.form-select {
    border: 1px solid var(--ilt-border);
    border-radius: 10px;
    padding: 8px 30px;
}

/* Button row */
body#page-mod-ilt-attendees form[action*="takeattendance"] .ilt-ta-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 14px 0 0;
    padding: 0;
}
/* Save -> navy with orange save icon */
body#page-mod-ilt-attendees form[action*="takeattendance"] .ilt-ta-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--ilt-navy);
    border: 0;
    color: #fff;
    font-weight: 600;
    border-radius: 10px;
    padding: 11px 20px;
    cursor: pointer;
}
body#page-mod-ilt-attendees form[action*="takeattendance"] .ilt-ta-save::before {
    content: "";
    width: 16px; height: 16px;
    background: center/contain no-repeat;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23f5a623' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z'/%3E%3Cpolyline points='17 21 17 13 7 13 7 21'/%3E%3Cpolyline points='7 3 7 8 15 8'/%3E%3C/svg%3E");
}
/* Cancel -> white outline */
body#page-mod-ilt-attendees form[action*="takeattendance"] .ilt-ta-cancel {
    background: #fff;
    border: 1px solid var(--ilt-border);
    color: var(--ilt-ink);
    font-weight: 600;
    border-radius: 10px;
    padding: 11px 20px;
    cursor: pointer;
}

/* Go back (standalone link after the form) -> orange */
body#page-mod-ilt-attendees .box.generalbox > a.link.underline[href*="view.php"] {
    color: var(--ilt-orange);
    text-decoration: none;
    font-weight: 600;
    display: inline-block;
    margin: 8px 0 24px;
}
</style>
<?php
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

                                // Show current status as a pill.
                $cskey = ilt_get_status($attendee->statuscode);
                $data[] = html_writer::span(get_string('status_'.$cskey, 'ilt'), 'ilt-att ilt-att-'.$cskey);

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
                                $statuskey = ilt_get_status($attendee->statuscode);
                $data[] = html_writer::span(
                    get_string('status_'.$statuskey, 'ilt'),
                    'ilt-att ilt-att-'.$statuskey);
            }
            $table->data[] = $data;
        }

        echo html_writer::table($table);

        if ($takeattendance) {
                       echo html_writer::start_tag('p', array('class' => 'ilt-ta-actions'));
            echo html_writer::tag('button', get_string('saveattendance', 'ilt'),
                array('type' => 'submit', 'class' => 'ilt-ta-save'));
            echo html_writer::tag('button', get_string('cancel'),
                array('type' => 'submit', 'name' => 'cancelform', 'value' => '1', 'class' => 'ilt-ta-cancel'));
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
