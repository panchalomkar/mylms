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
 * Attendance details page.
 *
 * Combines mod_ilt (ILT sessions) + mod_attendance (attendance activities)
 * into a single per-user attendance breakdown, matching the myprofile block totals.
 *
 * @package    local_mydashboard
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/mydashboard/lib.php');

require_login();

global $USER, $DB, $OUTPUT, $PAGE;

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/mydashboard/attendance.php'));
$PAGE->set_pagelayout('standard'); // Uses your theme layout, so the left panel + header render as-is.
$PAGE->set_title('Attendance');
$PAGE->set_heading('');

// Points awarded for a fully-attended session. Adjust to your gamification rules.
$pointspersession = 2;

// ---------------------------------------------------------------------------
// Row builder: turns a raw status key into the label / css class / points the
// template needs, and formats the date.
// ---------------------------------------------------------------------------
$makerow = function($name, $sessdate, $type, $statuskey) use ($pointspersession) {
    $map = [
        // key         => [label,            css class,    points earned]
        'present'    => ['Present',         'present',    $pointspersession],
        'selfmarked' => ['Self-marked',     'selfmarked', $pointspersession],
        'late'       => ['Late / Partial',  'late',       (int)floor($pointspersession / 2)],
        'absent'     => ['Absent',          'absent',     0],
        'nottaken'   => ['Not taken',       'nottaken',   0],
    ];
    if (!isset($map[$statuskey])) {
        $statuskey = 'nottaken';
    }
    list($label, $class, $earned) = $map[$statuskey];

    return [
        'name'         => $name !== '' ? $name : ucfirst($type),
        'datetime'     => $sessdate ? userdate($sessdate, '%d %b %Y &middot; %I:%M %p') : '&mdash;',
        'type'         => $type,
        'statuslabel'  => $label,
        'statusclass'  => $class,
        'pointsearned' => $earned,
        'pointsmax'    => $pointspersession,
        // Internal keys (ignored by the template, used for stats/sorting below).
        'statuskey'    => $statuskey,
        'sessdate'     => (int)$sessdate,
    ];
};

$rows = [];

// ===========================================================================
// 1) ILT sessions (mod_ilt).
// ---------------------------------------------------------------------------
// mod_ilt derives from mod_facetoface, so status codes map like facetoface:
//   100 = fully attended  -> Present
//    90 = partially att.   -> Late / Partial
//    80 = no show          -> Absent
//    70 / none             -> Not taken (booked, not yet marked)
//
// CONFIRM THESE 3 AGAINST YOUR SCHEMA (everything else your myprofile block
// already uses successfully):
//   - session -> activity FK column: {ilt_sessions}.ilt   (some builds: iltid)
//   - session date table/column:     {ilt_sessions_dates}.timestart
//   - activity name column:          {ilt}.name
// ===========================================================================
if ($DB->get_manager()->table_exists('ilt_signups')
        && $DB->get_manager()->table_exists('ilt_sessions')
        && $DB->get_manager()->table_exists('ilt')) {

    $hasdates   = $DB->get_manager()->table_exists('ilt_sessions_dates');
    $dateselect = $hasdates
        ? "(SELECT MIN(d.timestart) FROM {ilt_sessions_dates} d WHERE d.sessionid = sess.id)"
        : "0";

    $iltsql = "
        SELECT s.id AS signupid,
               sess.id AS sessionid,
               i.name AS activityname,
               ss.statuscode AS statuscode,
               {$dateselect} AS sessdate
          FROM {ilt_signups} s
          JOIN {ilt_sessions} sess ON sess.id = s.sessionid
          JOIN {ilt} i ON i.id = sess.ilt
     LEFT JOIN {ilt_signups_status} ss
            ON ss.signupid = s.id AND ss.superceded = 0
         WHERE s.userid = :userid";

    $iltrecords = $DB->get_records_sql($iltsql, ['userid' => $USER->id]);

    foreach ($iltrecords as $rec) {
        switch ((int)$rec->statuscode) {
            case 100: $statuskey = 'present';  break;
            case 90:  $statuskey = 'late';     break;
            case 80:  $statuskey = 'absent';   break;
            default:  $statuskey = 'nottaken'; break;
        }
        $rows[] = $makerow(
            format_string($rec->activityname),
            (int)$rec->sessdate,
            'ILT Session',
            $statuskey
        );
    }
}

// ===========================================================================
// 2) Attendance activities (mod_attendance) across enrolled courses.
// ---------------------------------------------------------------------------
// Standard Moodle schema. Status via {attendance_log} + {attendance_statuses}:
//   acronym P / desc "Present..."  -> Present  (or Self-marked if takenby = self)
//   acronym L / "Late" / "Partial" -> Late / Partial
//   acronym A|E / "Absent|Excused" -> Absent
//   no log row                     -> Not taken
// ===========================================================================
if ($DB->get_manager()->table_exists('attendance_sessions')
        && $DB->get_manager()->table_exists('attendance')) {

    $attsql = "
        SELECT s.id AS sessionid,
               s.sessdate,
               s.description,
               a.name AS activityname,
               al.statusid,
               al.takenby,
               st.acronym,
               st.description AS statusdesc
          FROM {attendance_sessions} s
          JOIN {attendance} a ON a.id = s.attendanceid
          JOIN {course_modules} cm ON cm.instance = a.id
          JOIN {modules} md ON md.id = cm.module AND md.name = 'attendance'
          JOIN {enrol} e ON e.courseid = a.course
          JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = :userid1
     LEFT JOIN {attendance_log} al ON al.sessionid = s.id AND al.studentid = :userid2
     LEFT JOIN {attendance_statuses} st ON st.id = al.statusid
         WHERE cm.visible = 1
           AND e.status = 0
           AND ue.status = 0
           AND (s.groupid = 0 OR s.groupid IN (
                   SELECT gm.groupid FROM {groups_members} gm WHERE gm.userid = :userid3))";

    $attrecords = $DB->get_records_sql($attsql, [
        'userid1' => $USER->id,
        'userid2' => $USER->id,
        'userid3' => $USER->id,
    ]);

    foreach ($attrecords as $rec) {
        $statuskey = 'nottaken';
        if (!empty($rec->statusid)) {
            $acr  = strtoupper(trim($rec->acronym ?? ''));
            $desc = strtolower(trim($rec->statusdesc ?? ''));

            if ($acr === 'P' || strpos($desc, 'present') === 0) {
                // Self-marked when the student set their own log entry.
                $statuskey = ((int)$rec->takenby === (int)$USER->id) ? 'selfmarked' : 'present';
            } else if ($acr === 'L' || strpos($desc, 'late') !== false || strpos($desc, 'partial') !== false) {
                $statuskey = 'late';
            } else if ($acr === 'A' || $acr === 'E'
                    || strpos($desc, 'absent') === 0 || strpos($desc, 'excused') === 0) {
                $statuskey = 'absent';
            } else {
                // Any other explicit mark counts as attended.
                $statuskey = 'present';
            }
        }

        $name = !empty($rec->description) ? format_string($rec->description) : format_string($rec->activityname);
        $rows[] = $makerow($name, (int)$rec->sessdate, 'Attendance Activity', $statuskey);
    }
}

// ---------------------------------------------------------------------------
// Sort newest first, then compute the summary cards.
// ---------------------------------------------------------------------------
usort($rows, function($a, $b) {
    return $b['sessdate'] <=> $a['sessdate'];
});

$present = $selfmarked = $late = $absent = 0;
foreach ($rows as $r) {
    switch ($r['statuskey']) {
        case 'present':    $present++;    break;
        case 'selfmarked': $selfmarked++; break;
        case 'late':       $late++;       break;
        case 'absent':     $absent++;     break;
    }
}

$attended = $present + $selfmarked;      // Present + Self-marked count as attended.
$taken    = $attended + $late + $absent; // Sessions that have actually been marked.
$overall  = $taken ? (int)round($attended / $taken * 100) : 0;

// ---------------------------------------------------------------------------
// Pagination: 8 rows per page. Cards above stay based on the full result set.
// ---------------------------------------------------------------------------
$perpage   = 8;
$page      = optional_param('page', 0, PARAM_INT);
$rows      = array_values($rows);
$totalrows = count($rows);

// Clamp page in case of an out-of-range value in the URL.
$maxpage = $totalrows > 0 ? (int)ceil($totalrows / $perpage) - 1 : 0;
if ($page < 0) {
    $page = 0;
}
if ($page > $maxpage) {
    $page = $maxpage;
}

$pagedrows = array_slice($rows, $page * $perpage, $perpage);

// Build a custom pager (Moodle's paging_bar can't produce the "Showing X-Y of Z"
// text or the rounded numbered buttons, so we hand it to the template instead).
$baseurl    = new moodle_url('/local/mydashboard/attendance.php');
$totalpages = max(1, (int)ceil($totalrows / $perpage));
$maxbuttons = 6; // Visible numbered buttons, like the reference.

$windowstart = max(0, min($page - (int)floor($maxbuttons / 2), $totalpages - $maxbuttons));
$windowend   = min($totalpages - 1, $windowstart + $maxbuttons - 1);

$pages = [];
for ($p = $windowstart; $p <= $windowend; $p++) {
    $pages[] = [
        'num'    => $p + 1,
        'url'    => (new moodle_url($baseurl, ['page' => $p]))->out(false),
        'active' => ($p === $page),
    ];
}

$from = $totalrows ? ($page * $perpage) + 1 : 0;
$to   = min(($page + 1) * $perpage, $totalrows);

$pager = [
    'summary'      => "Showing {$from}\u{2013}{$to} of {$totalrows} entries", // en dash
    'prevurl'      => (new moodle_url($baseurl, ['page' => max(0, $page - 1)]))->out(false),
    'prevdisabled' => ($page <= 0),
    'nexturl'      => (new moodle_url($baseurl, ['page' => min($totalpages - 1, $page + 1)]))->out(false),
    'nextdisabled' => ($page >= $totalpages - 1),
    'pages'        => $pages,
];

$templatecontext = [
    'overall'       => $overall,
    'totalsessions' => $taken,
    'attended'      => $attended,
    'latepartial'   => $late,
    'notattended'   => $absent,
    'rows'          => $pagedrows,
    'hasrows'       => $totalrows > 0,
    'showpaging'    => $totalrows > $perpage,
    'pager'         => $pager,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_mydashboard/attendance', $templatecontext);
echo $OUTPUT->footer();