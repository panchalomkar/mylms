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
 * RAP Mobile API — Complete External Library
 * All functions for mobile app screens
 * Created by Omkar
 *
 * @package    local_rapmobile
 * @copyright  2024 RAP Tech Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir  . '/externallib.php');
require_once($CFG->dirroot . '/enrol/externallib.php');
require_once($CFG->dirroot . '/local/mydashboard/lib.php'); // reuse existing lib.php
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->libdir  . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');
class local_rapmobile_external extends external_api {

// notificaiton api
// =========================================================
// ░░ NOTIFICATIONS ░░
// =========================================================

public static function get_rapmobile_notifications_parameters() {
    return new external_function_parameters([
        'userid'          => new external_value(PARAM_INT, 'User ID'),
        'limit'           => new external_value(PARAM_INT, 'Max notifications to return', VALUE_DEFAULT, 50),
        'offset'          => new external_value(PARAM_INT, 'Pagination offset', VALUE_DEFAULT, 0),
        'mark_read_id'    => new external_value(PARAM_ALPHANUMEXT, 'Optional: notification_id to mark as read on this call', VALUE_DEFAULT, ''),
    ]);
}

public static function get_rapmobile_notifications($userid, $limit = 50, $offset = 0, $mark_read_id = '') {
    global $DB, $CFG;

    $params = self::validate_parameters(self::get_rapmobile_notifications_parameters(),
        ['userid' => $userid, 'limit' => $limit, 'offset' => $offset, 'mark_read_id' => $mark_read_id]);

    $limit  = max(1, min(200, $params['limit']));
    $offset = max(0, $params['offset']);
    $userid = $params['userid'];
    $now    = time();

    // ---- Mark-as-read side effect, if a notification_id was passed ----
    $readids = json_decode(get_user_preferences('rapmobile_read_notifications', '[]', $userid), true) ?: [];
    if (!empty($params['mark_read_id']) && !in_array($params['mark_read_id'], $readids, true)) {
        $readids[] = $params['mark_read_id'];
        if (count($readids) > 500) {
            $readids = array_slice($readids, -500);
        }
        set_user_preference('rapmobile_read_notifications', json_encode($readids), $userid);
    }

    $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
    $settings = $DB->get_record('custom_notification', ['id' => 1]);

    $items = [];

    // ---- Enrolled courses ----
    $enrolments = $DB->get_records_sql(
        "SELECT DISTINCT e.courseid, ue.timestart, ue.timeend, ue.status
           FROM {enrol} e
           JOIN {user_enrolments} ue ON ue.enrolid = e.id
          WHERE ue.userid = :uid AND e.status = 0",
        ['uid' => $userid]
    );

    foreach ($enrolments as $enrol) {
        $courseid = (int)$enrol->courseid;
        $course = $DB->get_record('course', ['id' => $courseid],
            'id,fullname,enddate,enablecompletion', IGNORE_MISSING);
        if (!$course) continue;

        $courseurl = $CFG->wwwroot . '/course/view.php?id=' . $courseid;

        // if (!$settings || $settings->user_enrolled_noti == 1) {
        //     $items[] = self::rapmobile_build_notification('user_enrolled', 'User Enrolled',
        //         'You have been enrolled in "' . $course->fullname . '".',
        //         $courseurl, $courseid, null, (int)($enrol->timestart ?: $now));
        // }

        if ((!$settings || $settings->user_unenrolled_noti == 1) && (int)$enrol->status === 1) {
            $items[] = self::rapmobile_build_notification('user_unenrolled', 'User Unenrolled',
                'You have been unenrolled from "' . $course->fullname . '".',
                $courseurl, $courseid, null, $now);
        }

        $completion = $DB->get_record('course_completions',
            ['userid' => $userid, 'course' => $courseid], 'timecompleted,timestarted', IGNORE_MISSING);
        $completed  = $completion && !empty($completion->timecompleted);
        $inprogress = $completion && !empty($completion->timestarted) && !$completed;

        if ((!$settings || $settings->course_completion_noti == 1) && $completed) {
            $items[] = self::rapmobile_build_notification('course_completion', 'Course Completed',
                'Congratulations! You completed "' . $course->fullname . '".',
                $courseurl, $courseid, null, (int)$completion->timecompleted);
        }

        if ((!$settings || $settings->course_in_progress_noti == 1) && $inprogress) {
            $items[] = self::rapmobile_build_notification('course_in_progress', 'Course In Progress',
                'Your course "' . $course->fullname . '" is in progress.',
                $courseurl, $courseid, null, (int)$completion->timestarted);
        }

        if ((!$settings || $settings->course_expiration_noti == 1) && !$completed && $course->enddate > 0) {
            $window = (int)($settings->course_expiration_when ?? 259200);
            $threshold = $course->enddate - $window;
            if ($now >= $threshold && $now <= $course->enddate) {
                $daysleft = ceil(($course->enddate - $now) / 86400);
                $items[] = self::rapmobile_build_notification('course_expiration', 'Course Expiring Soon',
                    'Your course "' . $course->fullname . '" expires in ' . $daysleft . ' day(s). Complete it now.',
                    $courseurl, $courseid, null, $now);
            }
        }

        if ((!$settings || $settings->course_not_completed_noti == 1) && !$completed && $course->enddate > 0) {
            $window = (int)($settings->course_not_completed_when ?? 604800);
            $threshold = $course->enddate - $window;
            if ($now >= $threshold && $now <= $course->enddate) {
                $items[] = self::rapmobile_build_notification('course_not_completed', 'Course Not Completed',
                    'You have not completed "' . $course->fullname . '" yet.',
                    $courseurl, $courseid, null, $now);
            }
        }
    }

    if (!$settings || $settings->not_loggedin_noti == 1) {
        $window = (int)($settings->not_loggedin_when ?? 604800);
        $lastlogin = (int)($user->lastlogin ?? 0);
        if ($lastlogin > 0 && ($now - $lastlogin) >= $window) {
            $daysinactive = floor(($now - $lastlogin) / 86400);
            $items[] = self::rapmobile_build_notification('not_loggedin', 'Not Logged In',
                "You haven't logged in for {$daysinactive} day(s). Continue learning.",
                $CFG->wwwroot . '/my', null, null, $now);
        }
    }

    if (!$settings || $settings->course_module_completion_noti == 1) {
        $modcompletions = $DB->get_records_sql(
            "SELECT cmc.id, cmc.coursemoduleid, cmc.timemodified, cm.course, m.name AS modname
               FROM {course_modules_completion} cmc
               JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
               JOIN {modules} m ON m.id = cm.module
              WHERE cmc.userid = :uid AND cmc.completionstate IN (1,2)
           ORDER BY cmc.timemodified DESC",
            ['uid' => $userid], 0, 20
        );
        foreach ($modcompletions as $mc) {
            $course = $DB->get_record('course', ['id' => $mc->course], 'id,fullname', IGNORE_MISSING);
            if (!$course) continue;
            $items[] = self::rapmobile_build_notification('course_module_completion', 'Module Completed',
                'You completed a module in "' . $course->fullname . '" successfully.',
                $CFG->wwwroot . '/mod/' . $mc->modname . '/view.php?id=' . $mc->coursemoduleid,
                (int)$course->id, (int)$mc->coursemoduleid, (int)$mc->timemodified);
        }
    }

    // ---- ILT notifications — using REAL schema: ilt_signups + ilt_signups_status ----
    // ilt_signups has no status column itself; current status is the latest
    // (superceded = 0) row in ilt_signups_status. statuscode meanings are
    // borrowed from the facetoface convention this plugin's schema mirrors
    // (40=requested, 70=approved/confirmed, 60=waitlisted, 10=cancelled) —
    // worth confirming against real ilt_signups_status.statuscode values.
    $iltsql = "SELECT su.id AS signupid, su.sessionid, su.userid,
                      st.statuscode, st.timecreated AS statustime,
                      s.ilt AS iltid, d.timestart,
                      i.name AS iltname, i.course AS courseid, cm.id AS cmid
                 FROM {ilt_signups} su
                 JOIN {ilt_sessions} s ON s.id = su.sessionid
                 JOIN {ilt} i ON i.id = s.ilt
                 JOIN {modules} mo ON mo.name = 'ilt'
                 JOIN {course_modules} cm ON cm.course = i.course
                                          AND cm.instance = i.id
                                          AND cm.module = mo.id
            LEFT JOIN {ilt_sessions_dates} d ON d.sessionid = s.id
                 JOIN {ilt_signups_status} st ON st.signupid = su.id AND st.superceded = 0
                WHERE su.userid = :uid
             ORDER BY st.timecreated DESC";

    $iltrows = $DB->get_records_sql($iltsql, ['uid' => $userid]);
    foreach ($iltrows as $row) {
        $courseurl = $CFG->wwwroot . '/mod/ilt/view.php?id=' . $row->cmid;
        $sessiontime = !empty($row->timestart) ? date('d-M-Y h:i A', (int)$row->timestart) : '';

        switch ((int)$row->statuscode) {
            case 40:
                $items[] = self::rapmobile_build_notification('ilt_request', 'ILT Request Submitted',
                    'Your request for "' . $row->iltname . '" has been submitted.',
                    $courseurl, (int)$row->courseid, (int)$row->cmid, (int)$row->statustime);
                break;
            case 70:
                $items[] = self::rapmobile_build_notification('ilt_confirmation', 'ILT Seat Confirmed',
                    'Your seat for "' . $row->iltname . '" has been confirmed.',
                    $courseurl, (int)$row->courseid, (int)$row->cmid, (int)$row->statustime);
                if (!empty($row->timestart) && (int)$row->timestart > $now && ((int)$row->timestart - $now) <= 86400) {
                    $items[] = self::rapmobile_build_notification('ilt_reminder', 'ILT Session Reminder',
                        'Reminder: Your ILT session "' . $row->iltname . '" starts at ' . $sessiontime . '.',
                        $courseurl, (int)$row->courseid, (int)$row->cmid, $now);
                }
                break;
            case 60:
                $items[] = self::rapmobile_build_notification('ilt_waitlisted', 'ILT Wait-listed',
                    'You are currently wait-listed for "' . $row->iltname . '".',
                    $courseurl, (int)$row->courseid, (int)$row->cmid, (int)$row->statustime);
                break;
            case 10:
                $items[] = self::rapmobile_build_notification('ilt_cancellation', 'ILT Session Cancelled',
                    'Your ILT session "' . $row->iltname . '" has been cancelled.',
                    $courseurl, (int)$row->courseid, (int)$row->cmid, (int)$row->statustime);
                break;
        }
    }

    // ---- Recommended: mandatory training, assessment due, new course, live soon ----
    $courseids = array_keys($enrolments);
    if (!empty($courseids)) {
        [$incourses, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'ecid');
        $dbman = $DB->get_manager();

        if ($dbman->table_exists('customfield_data') && $dbman->table_exists('customfield_field')) {
            $mandatory = $DB->get_records_sql(
                "SELECT c.id, c.fullname
                   FROM {course} c
                   JOIN {customfield_data} cd ON cd.instanceid = c.id
                   JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.shortname = 'mandatory'
                  WHERE c.id $incourses AND " . $DB->sql_compare_text('cd.value') . " = '1'",
                $inparams
            );
            foreach ($mandatory as $mc) {
                $cc = $DB->get_record('course_completions',
                    ['userid' => $userid, 'course' => $mc->id], 'timecompleted', IGNORE_MISSING);
                if (!$cc || empty($cc->timecompleted)) {
                    $items[] = self::rapmobile_build_notification('mandatory_training', 'Mandatory Training Pending',
                        'Mandatory training "' . $mc->fullname . '" is pending.',
                        $CFG->wwwroot . '/course/view.php?id=' . $mc->id, (int)$mc->id, null, $now);
                }
            }
        }

        $twodays = $now + (2 * 86400);

        if ($dbman->table_exists('assign')) {
            $assigns = $DB->get_records_sql(
                "SELECT a.id, a.name, a.course, a.duedate, cm.id AS cmid
                   FROM {assign} a
                   JOIN {modules} mo ON mo.name = 'assign'
                   JOIN {course_modules} cm ON cm.course = a.course AND cm.instance = a.id AND cm.module = mo.id
                  WHERE a.course $incourses AND a.duedate BETWEEN :tnow AND :ttwo AND a.duedate > 0",
                array_merge($inparams, ['tnow' => $now, 'ttwo' => $twodays])
            );
            foreach ($assigns as $a) {
                $submitted = $DB->record_exists('assign_submission',
                    ['assignment' => $a->id, 'userid' => $userid, 'status' => 'submitted']);
                if (!$submitted) {
                    $hours = ceil(($a->duedate - $now) / 3600);
                    $items[] = self::rapmobile_build_notification('assessment_due', 'Assessment Due Soon',
                        'Your assignment "' . $a->name . '" is due in ' . $hours . ' hour(s).',
                        $CFG->wwwroot . '/mod/assign/view.php?id=' . $a->cmid, (int)$a->course, (int)$a->cmid, $now);
                }
            }
        }

        if ($dbman->table_exists('quiz')) {
            $quizzes = $DB->get_records_sql(
                "SELECT q.id, q.name, q.course, q.timeclose, cm.id AS cmid
                   FROM {quiz} q
                   JOIN {modules} mo ON mo.name = 'quiz'
                   JOIN {course_modules} cm ON cm.course = q.course AND cm.instance = q.id AND cm.module = mo.id
                  WHERE q.course $incourses AND q.timeclose BETWEEN :tnow AND :ttwo AND q.timeclose > 0",
                array_merge($inparams, ['tnow' => $now, 'ttwo' => $twodays])
            );
            foreach ($quizzes as $q) {
                $attempted = $DB->record_exists_sql(
                    "SELECT 1 FROM {quiz_attempts} WHERE quiz = :qid AND userid = :uid AND state = 'finished'",
                    ['qid' => $q->id, 'uid' => $userid]
                );
                if (!$attempted) {
                    $hours = ceil(($q->timeclose - $now) / 3600);
                    $items[] = self::rapmobile_build_notification('assessment_due', 'Quiz Closing Soon',
                        'Your quiz "' . $q->name . '" closes in ' . $hours . ' hour(s).',
                        $CFG->wwwroot . '/mod/quiz/view.php?id=' . $q->cmid, (int)$q->course, (int)$q->cmid, $now);
                }
            }
        }

        $sevendays = $now - (7 * 86400);
        $newenrols = $DB->get_records_sql(
            "SELECT e.courseid, ue.timecreated AS enroltime
               FROM {enrol} e
               JOIN {user_enrolments} ue ON ue.enrolid = e.id
              WHERE ue.userid = :uid AND ue.status = 0 AND e.status = 0 AND ue.timecreated >= :seven
           ORDER BY ue.timecreated DESC",
            ['uid' => $userid, 'seven' => $sevendays]
        );
        foreach ($newenrols as $ne) {
            $course = $DB->get_record('course', ['id' => $ne->courseid], 'id,fullname', IGNORE_MISSING);
            if (!$course) continue;
            $items[] = self::rapmobile_build_notification('new_course_assigned', 'New Course Assigned',
                'A new course "' . $course->fullname . '" has been assigned to you.',
                $CFG->wwwroot . '/course/view.php?id=' . $course->id, (int)$course->id, null, (int)$ne->enroltime);
        }

        $fivemin = $now + 300;
        $supportedmods = ['zoom', 'googlemeet', 'ilt'];
        if ($dbman->table_exists('facetoface')) $supportedmods[] = 'facetoface';
        [$modsql, $modparams] = $DB->get_in_or_equal($supportedmods, SQL_PARAMS_NAMED, 'lsm');

        $live = $DB->get_records_sql(
            "SELECT e.id, e.modulename, e.instance, e.timestart, e.courseid, e.name AS eventname, cm.id AS cmid
               FROM {event} e
               JOIN {modules} mo ON mo.name = e.modulename
               JOIN {course_modules} cm ON cm.course = e.courseid AND cm.instance = e.instance AND cm.module = mo.id
              WHERE e.courseid $incourses AND e.modulename $modsql
                AND e.timestart BETWEEN :tnow AND :tfive AND cm.deletioninprogress = 0",
            array_merge($inparams, $modparams, ['tnow' => $now, 'tfive' => $fivemin])
        );
        foreach ($live as $ev) {
            $acturl = $CFG->wwwroot . '/mod/' . $ev->modulename . '/view.php?id=' . $ev->cmid;
            if ($ev->modulename === 'zoom') {
                $z = $DB->get_record('zoom', ['id' => $ev->instance], 'join_url', IGNORE_MISSING);
                if ($z && !empty($z->join_url)) $acturl = $z->join_url;
            } elseif ($ev->modulename === 'googlemeet') {
                $g = $DB->get_record('googlemeet', ['id' => $ev->instance]);
                $gurl = $g->meetingurl ?? $g->url ?? $g->joinurl ?? '';
                if (!empty($gurl)) $acturl = $gurl;
            }
            $mins = max(0, ceil(($ev->timestart - $now) / 60));
            $items[] = self::rapmobile_build_notification('live_session_soon', 'Live Session Starting Soon',
                'Your live session "' . $ev->eventname . '" starts in ' . $mins . ' minute(s). Tap to join.',
                $acturl, (int)$ev->courseid, (int)$ev->cmid, $now);
        }
    }

    // =========================================================
// ADDITIONAL — "Reminder" notifications: fires only when an event
// is happening TODAY or TOMORROW (1-day lookahead), for all types
// (quiz, assign, zoom, googlemeet, ilt). Separate from both the
// 15-day upcoming_* block and the original near-term triggers —
// distinct notification_type values so the app can treat these as
// the actual "remind me" push notifications.
// =========================================================
if (!empty($courseids)) {
    $todaystart  = mktime(0, 0, 0, (int)date('n'), (int)date('j'), (int)date('Y'));
    $reminderend = strtotime('+2 days', $todaystart) - 1; // end of tomorrow (23:59:59)

    // Helper to label "today" vs "tomorrow" from a timestamp.
    $daylabel = function($timestamp) use ($todaystart) {
        $tomorrowstart = strtotime('+1 day', $todaystart);
        if ($timestamp >= $todaystart && $timestamp < $tomorrowstart) {
            return 'today';
        }
        return 'tomorrow';
    };

    // ---- Quiz — closes today or tomorrow, not yet attempted ----
    if ($dbman->table_exists('quiz')) {
        $remquizzes = $DB->get_records_sql(
            "SELECT q.id, q.name, q.course, q.timeclose, cm.id AS cmid, c.fullname AS coursefullname
               FROM {quiz} q
               JOIN {course} c ON c.id = q.course
               JOIN {modules} mo ON mo.name = 'quiz'
               JOIN {course_modules} cm ON cm.course = q.course AND cm.instance = q.id AND cm.module = mo.id
              WHERE q.course $incourses AND q.timeclose BETWEEN :tstart AND :tend
                AND cm.deletioninprogress = 0 AND cm.visible = 1",
            array_merge($inparams, ['tstart' => $todaystart, 'tend' => $reminderend])
        );
        foreach ($remquizzes as $q) {
            $attempted = $DB->record_exists_sql(
                "SELECT 1 FROM {quiz_attempts} WHERE quiz = :qid AND userid = :uid AND state = 'finished'",
                ['qid' => $q->id, 'uid' => $userid]
            );
            if ($attempted) continue;
            $when = $daylabel($q->timeclose);
            $items[] = self::rapmobile_build_notification('reminder_quiz', 'Quiz Reminder',
                'Quiz "' . $q->name . '" in "' . $q->coursefullname . '" closes ' . $when . '.',
                 $CFG->wwwroot . '/mod/quiz/view.php?id=' . $q->cmid, (int)$q->course, (int)$q->cmid, $q->timeclose);
        }
    }

    // ---- Assignment — due today or tomorrow, not yet submitted ----
    if ($dbman->table_exists('assign')) {
        $remassigns = $DB->get_records_sql(
            "SELECT a.id, a.name, a.course, a.duedate, cm.id AS cmid, c.fullname AS coursefullname
               FROM {assign} a
               JOIN {course} c ON c.id = a.course
               JOIN {modules} mo ON mo.name = 'assign'
               JOIN {course_modules} cm ON cm.course = a.course AND cm.instance = a.id AND cm.module = mo.id
              WHERE a.course $incourses AND a.duedate BETWEEN :tstart AND :tend
                AND cm.deletioninprogress = 0 AND cm.visible = 1",
            array_merge($inparams, ['tstart' => $todaystart, 'tend' => $reminderend])
        );
        foreach ($remassigns as $a) {
            $submitted = $DB->record_exists('assign_submission',
                ['assignment' => $a->id, 'userid' => $userid, 'status' => 'submitted']);
            if ($submitted) continue;
            $when = $daylabel($a->duedate);
            $items[] = self::rapmobile_build_notification('reminder_assignment', 'Assignment Reminder',
                'Assignment "' . $a->name . '" in "' . $a->coursefullname . '" is due ' . $when . '.',
                $CFG->wwwroot . '/mod/assign/view.php?id=' . $a->cmid, (int)$a->course, (int)$a->cmid, $a->duedate);
        }
    }

    // ---- Zoom / Google Meet — happening today or tomorrow ----
    $remonlinemods = ['zoom', 'googlemeet'];
    [$remmodsql, $remmodparams] = $DB->get_in_or_equal($remonlinemods, SQL_PARAMS_NAMED, 'rem');

    $remevents = $DB->get_records_sql(
        "SELECT e.id, e.modulename, e.instance, e.timestart, e.courseid, e.name AS eventname,
                c.fullname AS coursefullname, cm.id AS cmid, cm.visible AS cmvisible
           FROM {event} e
           JOIN {course} c ON c.id = e.courseid
           JOIN {modules} mo ON mo.name = e.modulename
           JOIN {course_modules} cm ON cm.course = e.courseid AND cm.instance = e.instance AND cm.module = mo.id
          WHERE e.courseid $incourses AND e.modulename $remmodsql
            AND e.timestart BETWEEN :tstart AND :tend
            AND e.eventtype NOT IN ('category','site')
            AND cm.deletioninprogress = 0",
        array_merge($inparams, $remmodparams, ['tstart' => $todaystart, 'tend' => $reminderend])
    );
    foreach ($remevents as $ev) {
        if (!$ev->cmvisible) continue;

        $acturl = $CFG->wwwroot . '/mod/' . $ev->modulename . '/view.php?id=' . $ev->cmid;
        if ($ev->modulename === 'zoom') {
            $z = $DB->get_record('zoom', ['id' => $ev->instance], 'join_url', IGNORE_MISSING);
            if ($z && !empty($z->join_url)) $acturl = $z->join_url;
        } elseif ($ev->modulename === 'googlemeet') {
            $g = $DB->get_record('googlemeet', ['id' => $ev->instance]);
            $gurl = $g->meetingurl ?? $g->url ?? $g->joinurl ?? '';
            if (!empty($gurl)) $acturl = $gurl;
        }

        $when = $daylabel($ev->timestart);
        $label = ($ev->modulename === 'zoom') ? 'Zoom' : 'Google Meet';
        $items[] = self::rapmobile_build_notification('reminder_live_session', $label . ' Reminder',
            $label . ' session "' . $ev->eventname . '" in "' . $ev->coursefullname . '" is ' . $when . '.',
           $acturl, (int)$ev->courseid, (int)$ev->cmid, $ev->timestart);
    }

    // ---- ILT — happening today or tomorrow ----
    $remiltrows = $DB->get_records_sql(
        "SELECT s.id AS sessionid, s.sessionname, s.location,
                d.timestart, i.name AS iltname, i.course AS courseid,
                c.fullname AS coursefullname, cm.id AS cmid
           FROM {ilt_sessions} s
           JOIN {ilt_sessions_dates} d ON d.sessionid = s.id
           JOIN {ilt} i ON i.id = s.ilt
           JOIN {course} c ON c.id = i.course
           JOIN {modules} mo ON mo.name = 'ilt'
           JOIN {course_modules} cm ON cm.course = i.course AND cm.instance = i.id AND cm.module = mo.id
          WHERE i.course $incourses AND d.timestart BETWEEN :tstart AND :tend
            AND cm.deletioninprogress = 0 AND cm.visible = 1",
        array_merge($inparams, ['tstart' => $todaystart, 'tend' => $reminderend])
    );
    foreach ($remiltrows as $row) {
        $when = $daylabel($row->timestart);
        $loc = !empty($row->location) ? ' at ' . $row->location : '';
        $items[] = self::rapmobile_build_notification('reminder_ilt', 'ILT Session Reminder',
            'ILT session "' . (string)($row->sessionname ?: $row->iltname) . '" in "' . $row->coursefullname
                . '"' . $loc . ' is ' . $when . '.',
           $CFG->wwwroot . '/mod/ilt/view.php?id=' . $row->cmid, (int)$row->courseid, (int)$row->cmid, $row->timestart);
    }
}
foreach ($items as &$item) {
    $item['is_read'] = in_array($item['notification_id'], $readids, true);
}
unset($item);
    usort($items, fn($a, $b) => $b['created_at'] - $a['created_at']);

    // =========================================================
    // FCM PUSH — fires for every notification not yet pushed
    // =========================================================
    //
    // ALL 22 notification types are handled here automatically:
    //   user_unenrolled, course_completion, course_in_progress,
    //   course_expiration, course_not_completed, not_loggedin,
    //   course_module_completion, ilt_request, ilt_confirmation,
    //   ilt_reminder, ilt_waitlisted, ilt_cancellation,
    //   mandatory_training, assessment_due, new_course_assigned,
    //   live_session_soon, reminder_quiz, reminder_assignment,
    //   reminder_live_session, reminder_ilt
    //
    // HOW IT WORKS:
    //   1. Every notification has a unique notification_id (md5 hash of
    //      type + courseid + cmid + created_at).
    //   2. rapmobile_push_sent table stores every notification_id already pushed.
    //   3. On each poll: find items NOT in push_sent → fire FCM → insert to push_sent.
    //   4. Next poll: those ids are in push_sent → skip → no duplicate push.
    //
    // NOTE: Some notifications (course_in_progress, not_loggedin, etc.) use
    // created_at = time() which changes every poll. Their notification_id
    // is therefore time-bucketed by hour so they don't re-push every minute.
    // =========================================================
    try {
        if (!empty($items) && $DB->get_manager()->table_exists('rapmobile_push_sent')) {

            // Re-bucket time-sensitive notification_ids by HOUR so that
            // notifications using $now as created_at don't push every poll.
            // Types that use $now: course_in_progress, course_expiration,
            // course_not_completed, not_loggedin, mandatory_training,
            // assessment_due, live_session_soon, reminder_*
            $hour_floor = (int)(time() / 3600) * 3600; // floor to current hour

            $time_bucketed_types = [
                'course_in_progress', 'course_expiration', 'course_not_completed',
                'not_loggedin', 'mandatory_training', 'assessment_due',
                'live_session_soon', 'reminder_quiz', 'reminder_assignment',
                'reminder_live_session', 'reminder_ilt', 'ilt_reminder',
            ];

            // Build a lookup: original notification_id => push_check_id
            // For time-bucketed types use hour-floored id so same notification
            // doesn't re-push every poll within the same hour.
            $push_check_map = [];
            foreach ($items as $notif) {
                if (in_array($notif['notification_type'], $time_bucketed_types, true)) {
                    $bucketed_id = md5(
                        $notif['notification_type'] . '_'
                        . ($notif['courseid'] ?? 0) . '_'
                        . ($notif['cmid']     ?? 0) . '_'
                        . $hour_floor
                    );
                    $push_check_map[$notif['notification_id']] = $bucketed_id;
                } else {
                    $push_check_map[$notif['notification_id']] = $notif['notification_id'];
                }
            }

            // All push_check ids to look up
            $all_check_ids = array_values($push_check_map);

            // Which ones already pushed?
            [$in_sql, $in_params] = $DB->get_in_or_equal($all_check_ids, SQL_PARAMS_NAMED, 'nid');
            $in_params['uid'] = $userid;
            $already_sent = $DB->get_fieldset_sql(
                "SELECT notification_id FROM {rapmobile_push_sent}
                  WHERE userid = :uid AND notification_id {$in_sql}",
                $in_params
            );
            $already_sent = array_flip($already_sent);

            // Filter to only new ones
            $to_push = array_filter(
                $items,
                fn($n) => !isset($already_sent[$push_check_map[$n['notification_id']]])
            );

            if (!empty($to_push) && class_exists('\local_rapmobile\firebase\fcm_service')) {
                foreach ($to_push as $notif) {
                    $check_id = $push_check_map[$notif['notification_id']];

                    // Fire FCM to all active devices of this user
                    \local_rapmobile\firebase\fcm_service::send_to_user(
                        (int)$userid,
                        (string)$notif['title'],
                        (string)$notif['message'],
                        (string)$notif['notification_type'],
                        (int)($notif['courseid'] ?? 0),
                        (int)($notif['cmid']     ?? 0)
                    );

                    // Record using check_id (bucketed for time-sensitive types)
                    $DB->execute(
                        "INSERT IGNORE INTO {rapmobile_push_sent}
                                    (userid, notification_id, notification_type, timesent)
                         VALUES      (:uid, :nid, :ntype, :ts)",
                        [
                            'uid'   => $userid,
                            'nid'   => $check_id,
                            'ntype' => $notif['notification_type'],
                            'ts'    => time(),
                        ]
                    );
                }
            }
        }
    } catch (\Throwable $e) {
        // Never break the API response due to push errors
        debugging('[rapmobile FCM] push hook error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
    // =========================================================
    // END FCM PUSH
    // =========================================================

    $total = count($items);
    $paged = array_slice($items, $offset, $limit);

    return [
        'notifications' => $paged,
        'total_count'   => $total,
        'unread_count'  => count(array_filter($paged, fn($n) => !$n['is_read'])),
    ];
}

private static function rapmobile_build_notification($type, $title, $message, $redirecturl, $courseid, $cmid, $createdat) {
     $notification_id = md5($type . '_' . ($courseid ?? 0) . '_' . ($cmid ?? 0) . '_' . $createdat);

    return [
        'notification_id'   => $notification_id,
        'notification_type' => $type,
        'title'             => $title,
        'message'           => $message,
        'redirect_url'      => $redirecturl,
        'courseid'          => $courseid ?? 0,
        'cmid'              => $cmid ?? 0,
        'created_at'        => $createdat,
        'created_at_human'  => date('d-M-Y, h:i A', $createdat),
        'is_read'           => false,
    ];
}

public static function get_rapmobile_notifications_returns() {
    return new external_single_structure([
        'notifications' => new external_multiple_structure(
            new external_single_structure([
                 'notification_id'   => new external_value(PARAM_ALPHANUMEXT, 'Stable hash-based ID (no real DB row exists, so this is deterministic, not sequential)'),
                'notification_type' => new external_value(PARAM_ALPHANUMEXT, 'Type identifier'),
                'title'             => new external_value(PARAM_TEXT, 'Title'),
                'message'           => new external_value(PARAM_TEXT, 'Message'),
                'redirect_url'      => new external_value(PARAM_URL,  'URL to open on tap'),
                'courseid'          => new external_value(PARAM_INT,  'Course ID'),
                'cmid'              => new external_value(PARAM_INT,  'Course module ID'),
                'created_at'        => new external_value(PARAM_INT,  'Unix timestamp'),
                'created_at_human'  => new external_value(PARAM_TEXT, 'Formatted time'),
                'is_read'           => new external_value(PARAM_BOOL, 'Read state (client-managed)'),
            ])
        ),
        'total_count'  => new external_value(PARAM_INT, 'Total count'),
        'unread_count' => new external_value(PARAM_INT, 'Unread count in current page'),
    ]);
}

// =========================================================
// ░░ LIVE SESSIONS ░░
// =========================================================

public static function get_rapmobile_livesessions_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_rapmobile_livesessions($userid) {
    global $DB, $CFG;

    $params = self::validate_parameters(self::get_rapmobile_livesessions_parameters(), ['userid' => $userid]);
    $userid = $params['userid'];

    $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

    $now = mktime(0, 0, 0, (int)date('n'), (int)date('j'), (int)date('Y'));
    $windowend = strtotime('+15 days', $now);

    $enrolments = $DB->get_records_sql(
        "SELECT DISTINCT e.courseid
           FROM {enrol} e
           JOIN {user_enrolments} ue ON ue.enrolid = e.id
          WHERE ue.userid = :uid AND ue.status = 0 AND e.status = 0",
        ['uid' => $userid]
    );
    if (empty($enrolments)) {
        return ['livesessions' => []];
    }
    $courseids = array_keys($enrolments);
    [$incourses, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');

    $imgmap = [
        'zoom'       => $CFG->wwwroot . '/theme/remui/pix/zoom-demo.png',
        'googlemeet' => $CFG->wwwroot . '/theme/remui/pix/g-meet.png',
        'ilt'        => $CFG->wwwroot . '/theme/remui/pix/ilt.png',
        'facetoface' => $CFG->wwwroot . '/theme/remui/pix/ilt.png',
    ];

    $sessions = [];

    // ---- Zoom / Google Meet — via core {event} table (online sessions) ----
    $onlinemods = ['zoom', 'googlemeet'];
    [$modsql, $modparams] = $DB->get_in_or_equal($onlinemods, SQL_PARAMS_NAMED, 'lsm');

    $events = $DB->get_records_sql(
        "SELECT e.id AS eventid, e.courseid, e.modulename, e.instance, e.timestart, e.timeduration,
                e.name AS eventname, c.fullname AS coursefullname, cm.id AS cmid, cm.visible AS cmvisible
           FROM {event} e
           JOIN {course} c ON c.id = e.courseid
           JOIN {modules} m ON m.name = e.modulename
           JOIN {course_modules} cm ON cm.course = e.courseid AND cm.instance = e.instance AND cm.module = m.id
          WHERE e.courseid $incourses AND e.modulename $modsql
            AND e.timestart BETWEEN :tstart AND :tend
            AND e.eventtype NOT IN ('category','site')
            AND cm.deletioninprogress = 0
       ORDER BY e.timestart ASC",
        array_merge($inparams, $modparams, ['tstart' => $now, 'tend' => $windowend])
    );

    foreach ($events as $event) {
        if (!$event->cmvisible) continue;

        $activityname = '';
        $joinurl = '';

        if ($event->modulename === 'zoom') {
            $rec = $DB->get_record('zoom', ['id' => $event->instance], '*', IGNORE_MISSING);
            if (!$rec) continue;
            $activityname = $rec->name;
            // Direct third-party join_url — opens natively, no Moodle login involved.
            $joinurl = !empty($rec->join_url) ? $rec->join_url
                : $CFG->wwwroot . '/mod/zoom/view.php?id=' . $event->cmid;
        } elseif ($event->modulename === 'googlemeet') {
            $rec = $DB->get_record('googlemeet', ['id' => $event->instance], '*', IGNORE_MISSING);
            if (!$rec) continue;
            $activityname = $rec->name;
            $gurl = $rec->meetingurl ?? $rec->url ?? $rec->joinurl ?? '';
            // Direct meet.google.com URL — opens natively, no Moodle login involved.
            $joinurl = !empty($gurl) ? $gurl
                : $CFG->wwwroot . '/mod/googlemeet/view.php?id=' . $event->cmid;
        }

        $endtimestamp = ($event->timeduration > 0) ? ($event->timestart + $event->timeduration) : null;

        $sessions[] = [
            'activityname'   => $activityname,
            'modulename'     => $event->modulename,
            'sessiontype'    => 'Online',
            'starttime'      => date('d-M-Y, h:i A', $event->timestart),
            'starttimestamp' => (int)$event->timestart,
            'endtime'        => $endtimestamp ? date('d-M-Y, h:i A', $endtimestamp) : '',
            'endtimestamp'   => $endtimestamp ? (int)$endtimestamp : 0,
            'url'            => $joinurl,
            'courseid'       => (int)$event->courseid,
            'coursename'     => $event->coursefullname,
            'imgurl'         => $imgmap[$event->modulename] ?? '',
        ];
    }

    // ---- ILT — direct table query (offline, real schema) ----
    $iltrows = $DB->get_records_sql(
        "SELECT s.id AS sessionid, s.ilt AS iltid, s.location, s.classroom, s.instructor, s.capacity,
                d.timestart, d.timefinish,
                i.name AS iltname, i.course AS courseid, c.fullname AS coursefullname, cm.id AS cmid
           FROM {ilt_sessions} s
           JOIN {ilt_sessions_dates} d ON d.sessionid = s.id
           JOIN {ilt} i ON i.id = s.ilt
           JOIN {course} c ON c.id = i.course
           JOIN {modules} mo ON mo.name = 'ilt'
           JOIN {course_modules} cm ON cm.course = i.course AND cm.instance = i.id AND cm.module = mo.id
          WHERE i.course $incourses
            AND d.timestart BETWEEN :tstart AND :tend
            AND cm.deletioninprogress = 0 AND cm.visible = 1
       ORDER BY d.timestart ASC",
        array_merge($inparams, ['tstart' => $now, 'tend' => $windowend])
    );

    foreach ($iltrows as $row) {
        $sessions[] = [
            'activityname'   => $row->iltname,
            'modulename'     => 'ilt',
            'sessiontype'    => 'Offline',
            'starttime'      => date('d-M-Y, h:i A', $row->timestart),
            'starttimestamp' => (int)$row->timestart,
            'endtime'        => date('d-M-Y, h:i A', $row->timefinish),
            'endtimestamp'   => (int)$row->timefinish,
            // No join URL — in-person session; app should show location/classroom instead.
            'url'            => $CFG->wwwroot . '/mod/ilt/view.php?id=' . $row->cmid,
            'courseid'       => (int)$row->courseid,
            'coursename'     => $row->coursefullname,
            'imgurl'         => $imgmap['ilt'],
        ];
    }

    usort($sessions, fn($a, $b) => $a['starttimestamp'] - $b['starttimestamp']);

    return ['livesessions' => $sessions];
}

public static function get_rapmobile_livesessions_returns() {
    return new external_single_structure([
        'livesessions' => new external_multiple_structure(
            new external_single_structure([
                'activityname'   => new external_value(PARAM_TEXT, 'Activity name'),
                'modulename'     => new external_value(PARAM_ALPHANUMEXT, 'zoom/googlemeet/ilt'),
                'sessiontype'    => new external_value(PARAM_TEXT, 'Online or Offline'),
                'starttime'      => new external_value(PARAM_TEXT, 'Formatted start time'),
                'starttimestamp' => new external_value(PARAM_INT,  'Start unix timestamp'),
                'endtime'        => new external_value(PARAM_TEXT, 'Formatted end time'),
                'endtimestamp'   => new external_value(PARAM_INT,  'End unix timestamp'),
                'url'            => new external_value(PARAM_URL,  'Direct join URL — open as-is, no Moodle auth needed for zoom/googlemeet'),
                'courseid'       => new external_value(PARAM_INT,  'Course ID'),
                'coursename'     => new external_value(PARAM_TEXT, 'Course name'),
                'imgurl'         => new external_value(PARAM_URL,  'Icon URL'),
            ])
        ),
    ]);
}

// =========================================================
// ░░ UPCOMING ILT SESSIONS (all bookable sessions, with status) ░░
// =========================================================

public static function get_rapmobile_upcoming_sessions_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_rapmobile_upcoming_sessions($userid) {
    global $DB, $CFG;

    $params = self::validate_parameters(self::get_rapmobile_upcoming_sessions_parameters(), ['userid' => $userid]);
    $userid = $params['userid'];
    $now = time();
    $windowend = strtotime('+15 days', $now);

    $enrolments = $DB->get_records_sql(
        "SELECT DISTINCT e.courseid
           FROM {enrol} e
           JOIN {user_enrolments} ue ON ue.enrolid = e.id
          WHERE ue.userid = :uid AND ue.status = 0 AND e.status = 0",
        ['uid' => $userid]
    );
    if (empty($enrolments)) {
        return ['sessions' => [], 'total' => 0];
    }
    $courseids = array_keys($enrolments);
    [$incourses, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');
    $dbman = $DB->get_manager();

    $output = [];

    $blank = [
        'type' => '', 'sessionid' => 0, 'cmid' => 0, 'courseid' => 0, 'coursename' => '',
        'title' => '', 'starttime' => '', 'starttimestamp' => 0, 'endtime' => '', 'endtimestamp' => 0,
        'join_url' => '', 'view_url' => '', 'location' => '', 'classroom' => '', 'instructor' => '',
        'capacity' => 0, 'booked_count' => 0, 'is_full' => false, 'my_status' => '', 'can_book' => false,
    ];

    // ---- Quiz — closing within window, not yet attempted ----
    if ($dbman->table_exists('quiz')) {
        $quizzes = $DB->get_records_sql(
            "SELECT q.id, q.name, q.course, q.timeclose, cm.id AS cmid, c.fullname AS coursefullname
               FROM {quiz} q
               JOIN {course} c ON c.id = q.course
               JOIN {modules} mo ON mo.name = 'quiz'
               JOIN {course_modules} cm ON cm.course = q.course AND cm.instance = q.id AND cm.module = mo.id
              WHERE q.course $incourses AND q.timeclose BETWEEN :tnow AND :tend
                AND cm.deletioninprogress = 0 AND cm.visible = 1",
            array_merge($inparams, ['tnow' => $now, 'tend' => $windowend])
        );
        foreach ($quizzes as $q) {
            $attempted = $DB->record_exists_sql(
                "SELECT 1 FROM {quiz_attempts} WHERE quiz = :qid AND userid = :uid AND state = 'finished'",
                ['qid' => $q->id, 'uid' => $userid]
            );
            if ($attempted) continue;

            $row = $blank;
            $row['type']           = 'quiz';
            $row['cmid']           = (int)$q->cmid;
            $row['courseid']       = (int)$q->course;
            $row['coursename']     = $q->coursefullname;
            $row['title']          = $q->name;
            $row['starttime']      = date('d-M-Y, h:i A', $q->timeclose);
            $row['starttimestamp'] = (int)$q->timeclose;
            $row['view_url']       = $CFG->wwwroot . '/mod/quiz/view.php?id=' . $q->cmid;
            $output[] = $row;
        }
    }

    // ---- Assignment — due within window, not yet submitted ----
    if ($dbman->table_exists('assign')) {
        $assigns = $DB->get_records_sql(
            "SELECT a.id, a.name, a.course, a.duedate, cm.id AS cmid, c.fullname AS coursefullname
               FROM {assign} a
               JOIN {course} c ON c.id = a.course
               JOIN {modules} mo ON mo.name = 'assign'
               JOIN {course_modules} cm ON cm.course = a.course AND cm.instance = a.id AND cm.module = mo.id
              WHERE a.course $incourses AND a.duedate BETWEEN :tnow AND :tend
                AND cm.deletioninprogress = 0 AND cm.visible = 1",
            array_merge($inparams, ['tnow' => $now, 'tend' => $windowend])
        );
        foreach ($assigns as $a) {
            $submitted = $DB->record_exists('assign_submission',
                ['assignment' => $a->id, 'userid' => $userid, 'status' => 'submitted']);
            if ($submitted) continue;

            $row = $blank;
            $row['type']           = 'assign';
            $row['cmid']           = (int)$a->cmid;
            $row['courseid']       = (int)$a->course;
            $row['coursename']     = $a->coursefullname;
            $row['title']          = $a->name;
            $row['starttime']      = date('d-M-Y, h:i A', $a->duedate);
            $row['starttimestamp'] = (int)$a->duedate;
            $row['view_url']       = $CFG->wwwroot . '/mod/assign/view.php?id=' . $a->cmid;
            $output[] = $row;
        }
    }

    // ---- Zoom / Google Meet — via core {event} calendar table ----
    // Reuses the same reliable pattern as get_rapmobile_livesessions: every
    // module (including third-party zoom/googlemeet) populates {event} with
    // its own start time when it wants to show on the Moodle calendar, so
    // this avoids guessing each plugin's raw table field names directly.
    $onlinemods = ['zoom', 'googlemeet'];
    [$modsql, $modparams] = $DB->get_in_or_equal($onlinemods, SQL_PARAMS_NAMED, 'lsm');

    $events = $DB->get_records_sql(
        "SELECT e.id AS eventid, e.courseid, e.modulename, e.instance, e.timestart, e.timeduration,
                e.name AS eventname, c.fullname AS coursefullname, cm.id AS cmid, cm.visible AS cmvisible
           FROM {event} e
           JOIN {course} c ON c.id = e.courseid
           JOIN {modules} m ON m.name = e.modulename
           JOIN {course_modules} cm ON cm.course = e.courseid AND cm.instance = e.instance AND cm.module = m.id
          WHERE e.courseid $incourses AND e.modulename $modsql
            AND e.timestart BETWEEN :tstart AND :tend
            AND e.eventtype NOT IN ('category','site')
            AND cm.deletioninprogress = 0
       ORDER BY e.timestart ASC",
        array_merge($inparams, $modparams, ['tstart' => $now, 'tend' => $windowend])
    );

    foreach ($events as $event) {
        if (!$event->cmvisible) continue;

        $activityname = '';
        $joinurl = '';

        if ($event->modulename === 'zoom') {
            $rec = $DB->get_record('zoom', ['id' => $event->instance], '*', IGNORE_MISSING);
            if (!$rec) continue;
            $activityname = $rec->name;
            $joinurl = !empty($rec->join_url) ? $rec->join_url
                : $CFG->wwwroot . '/mod/zoom/view.php?id=' . $event->cmid;
        } elseif ($event->modulename === 'googlemeet') {
            $rec = $DB->get_record('googlemeet', ['id' => $event->instance], '*', IGNORE_MISSING);
            if (!$rec) continue;
            $activityname = $rec->name;
            $gurl = $rec->meetingurl ?? $rec->url ?? $rec->joinurl ?? '';
            $joinurl = !empty($gurl) ? $gurl
                : $CFG->wwwroot . '/mod/googlemeet/view.php?id=' . $event->cmid;
        }

        $endtimestamp = ($event->timeduration > 0) ? ($event->timestart + $event->timeduration) : null;

        $row = $blank;
        $row['type']           = $event->modulename;
        $row['cmid']           = (int)$event->cmid;
        $row['courseid']       = (int)$event->courseid;
        $row['coursename']     = $event->coursefullname;
        $row['title']          = $activityname;
        $row['starttime']      = date('d-M-Y, h:i A', $event->timestart);
        $row['starttimestamp'] = (int)$event->timestart;
        $row['endtime']        = $endtimestamp ? date('d-M-Y, h:i A', $endtimestamp) : '';
        $row['endtimestamp']   = $endtimestamp ? (int)$endtimestamp : 0;
        // Direct third-party join URL — open as-is, no Moodle auth needed.
        $row['join_url']       = $joinurl;
        $row['view_url']       = $CFG->wwwroot . '/mod/' . $event->modulename . '/view.php?id=' . $event->cmid;
        $output[] = $row;
    }

    // ---- ILT — confirmed real schema, in-person only ----
    $iltrows = $DB->get_records_sql(
        "SELECT s.id AS sessionid, s.ilt AS iltid, s.sessionname, s.location, s.classroom,
                s.instructor, s.capacity,
                d.timestart, d.timefinish,
                i.name AS iltname, i.course AS courseid, c.fullname AS coursefullname, cm.id AS cmid
           FROM {ilt_sessions} s
           JOIN {ilt_sessions_dates} d ON d.sessionid = s.id
           JOIN {ilt} i ON i.id = s.ilt
           JOIN {course} c ON c.id = i.course
           JOIN {modules} mo ON mo.name = 'ilt'
           JOIN {course_modules} cm ON cm.course = i.course AND cm.instance = i.id AND cm.module = mo.id
          WHERE i.course $incourses
            AND d.timestart BETWEEN :tnow AND :tend
            AND cm.deletioninprogress = 0 AND cm.visible = 1",
        array_merge($inparams, ['tnow' => $now, 'tend' => $windowend])
    );
    foreach ($iltrows as $row0) {
        $booked = (int)$DB->count_records_sql(
            "SELECT COUNT(*) FROM {ilt_signups} su
               JOIN {ilt_signups_status} st ON st.signupid = su.id AND st.superceded = 0
              WHERE su.sessionid = :sid AND st.statuscode = 70",
            ['sid' => $row0->sessionid]
        );
        $mystatus = $DB->get_record_sql(
            "SELECT st.statuscode
               FROM {ilt_signups} su
               JOIN {ilt_signups_status} st ON st.signupid = su.id AND st.superceded = 0
              WHERE su.sessionid = :sid AND su.userid = :uid",
            ['sid' => $row0->sessionid, 'uid' => $userid],
            IGNORE_MULTIPLE
        );
        $statusmap = [40 => 'requested', 70 => 'booked', 60 => 'waitlisted', 10 => 'cancelled'];
        $mystatuslabel = $mystatus ? ($statusmap[(int)$mystatus->statuscode] ?? 'unknown') : 'not_booked';
        $capacity = (int)$row0->capacity;
        $isfull = $capacity > 0 && $booked >= $capacity;

        $row = $blank;
        $row['type']           = 'ilt';
        $row['sessionid']      = (int)$row0->sessionid;
        $row['cmid']           = (int)$row0->cmid;
        $row['courseid']       = (int)$row0->courseid;
        $row['coursename']     = $row0->coursefullname;
        $row['title']          = (string)($row0->sessionname ?: $row0->iltname);
        $row['starttime']      = date('d-M-Y, h:i A', $row0->timestart);
        $row['starttimestamp'] = (int)$row0->timestart;
        $row['endtime']        = date('d-M-Y, h:i A', $row0->timefinish);
        $row['endtimestamp']   = (int)$row0->timefinish;
        $row['location']       = (string)($row0->location ?? '');
        $row['classroom']      = (string)($row0->classroom ?? '');
        $row['instructor']     = (string)($row0->instructor ?? '');
        $row['capacity']       = $capacity;
        $row['booked_count']   = $booked;
        $row['is_full']        = $isfull;
        $row['my_status']      = $mystatuslabel;
        $row['can_book']       = ($mystatuslabel === 'not_booked' && !$isfull);
        $row['view_url']       = $CFG->wwwroot . '/mod/ilt/view.php?id=' . $row0->cmid;
        $output[] = $row;
    }

    usort($output, fn($a, $b) => $a['starttimestamp'] - $b['starttimestamp']);

    return ['sessions' => $output, 'total' => count($output)];
}

// get_rapmobile_upcoming_sessions_returns() — unchanged from before, keep as-is.

public static function get_rapmobile_upcoming_sessions_returns() {
    return new external_single_structure([
        'sessions' => new external_multiple_structure(
            new external_single_structure([
                'type'           => new external_value(PARAM_ALPHANUMEXT, 'quiz/assign/zoom/googlemeet/ilt'),
                'sessionid'      => new external_value(PARAM_INT,  'Session ID (ilt only, 0 otherwise)'),
                'cmid'           => new external_value(PARAM_INT,  'Course module ID'),
                'courseid'       => new external_value(PARAM_INT,  'Course ID'),
                'coursename'     => new external_value(PARAM_TEXT, 'Course name'),
                'title'          => new external_value(PARAM_TEXT, 'Activity/session title'),
                'starttime'      => new external_value(PARAM_TEXT, 'Formatted start/due time'),
                'starttimestamp' => new external_value(PARAM_INT,  'Start/due unix timestamp'),
                'endtime'        => new external_value(PARAM_TEXT, 'Formatted end time (empty if n/a)'),
                'endtimestamp'   => new external_value(PARAM_INT,  'End unix timestamp (0 if n/a)'),
                'join_url'       => new external_value(PARAM_URL,  'Direct third-party join URL (zoom/googlemeet only) — open as-is, no Moodle login needed'),
                'view_url'       => new external_value(PARAM_RAW,  'Moodle activity view URL'),
                'location'       => new external_value(PARAM_TEXT, 'Location (ilt only)'),
                'classroom'      => new external_value(PARAM_TEXT, 'Classroom (ilt only)'),
                'instructor'     => new external_value(PARAM_TEXT, 'Instructor (ilt only)'),
                'capacity'       => new external_value(PARAM_INT,  'Capacity (ilt only)'),
                'booked_count'   => new external_value(PARAM_INT,  'Booked count (ilt only)'),
                'is_full'        => new external_value(PARAM_BOOL, 'Is full (ilt only)'),
                'my_status'      => new external_value(PARAM_TEXT, 'not_booked/requested/booked/waitlisted/cancelled (ilt only)'),
                'can_book'       => new external_value(PARAM_BOOL, 'Can book (ilt only)'),
            ])
        ),
        'total' => new external_value(PARAM_INT, 'Total upcoming items'),
    ]);
}
public static function get_user_courses_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function get_user_courses($userid) {
        global $CFG;

        $wstoken = optional_param('wstoken', '', PARAM_ALPHANUM);

        // Call core function
        $courses = core_enrol_external::get_users_courses($userid);

        // Fix courseimage URL in each course
        foreach ($courses as &$course) {
            if (!empty($course['courseimage'])) {
                $course['courseimage'] = str_replace(
                    $CFG->wwwroot . '/pluginfile.php',
                    $CFG->wwwroot . '/webservice/pluginfile.php',
                    $course['courseimage']
                ) . '?token=' . $wstoken;
            }
        }

        return $courses;
    }

    public static function get_user_courses_returns() {
        return core_enrol_external::get_users_courses_returns();
    }
    // =========================================================
    // ░░ AUTH & USER ░░
    // =========================================================

    // ----- get_user_profile -----
    public static function get_user_profile_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function get_user_profile($userid) {
        global $DB, $CFG;
        $params = self::validate_parameters(self::get_user_profile_parameters(), ['userid' => $userid]);
        $user   = $DB->get_record('user', ['id' => $params['userid'], 'deleted' => 0], '*', MUST_EXIST);
        $ctx    = context_user::instance($user->id);
        return [
            'userid'       => (int)$user->id,
            'username'     => $user->username,
            'fullname'     => fullname($user),
            'firstname'    => $user->firstname,
            'lastname'     => $user->lastname,
            'email'        => $user->email,
            'phone'        => $user->phone1 ?? '',
            'department'   => $user->department ?? '',
            'designation'  => $user->institution ?? '',
           'profileimage' => $CFG->wwwroot . "/webservice/pluginfile.php/{$ctx->id}/user/icon/f1?token=" . $params['wstoken'],
            'lastlogin'    => (int)$user->lastlogin,
        ];
    }

    public static function get_user_profile_returns() {
        return new external_single_structure([
            'userid'       => new external_value(PARAM_INT,   'User ID'),
            'username'     => new external_value(PARAM_TEXT,  'Username'),
            'fullname'     => new external_value(PARAM_TEXT,  'Full name'),
            'firstname'    => new external_value(PARAM_TEXT,  'First name'),
            'lastname'     => new external_value(PARAM_TEXT,  'Last name'),
            'email'        => new external_value(PARAM_EMAIL, 'Email'),
            'phone'        => new external_value(PARAM_TEXT,  'Phone'),
            'department'   => new external_value(PARAM_TEXT,  'Department'),
            'designation'  => new external_value(PARAM_TEXT,  'Designation'),
            'profileimage' => new external_value(PARAM_RAW,   'Profile image URL'),
            'lastlogin'    => new external_value(PARAM_INT,   'Last login timestamp'),
        ]);
    }

    // ----- change_password -----
    public static function change_password_parameters() {
        return new external_function_parameters([
            'userid'          => new external_value(PARAM_INT,  'User ID'),
            'newpassword'     => new external_value(PARAM_TEXT, 'New password'),
            'confirmpassword' => new external_value(PARAM_TEXT, 'Confirm password'),
        ]);
    }

    public static function change_password($userid, $newpassword, $confirmpassword) {
        global $DB;
        $params = self::validate_parameters(self::change_password_parameters(),
            ['userid' => $userid, 'newpassword' => $newpassword, 'confirmpassword' => $confirmpassword]);

        if ($params['newpassword'] !== $params['confirmpassword']) {
            return ['status' => '0', 'message' => 'Passwords do not match'];
        }
        $user = $DB->get_record('user', ['id' => $params['userid'], 'deleted' => 0, 'suspended' => 0]);
        if (!$user) {
            return ['status' => '0', 'message' => 'Invalid user'];
        }
        $upd           = new stdClass();
        $upd->id       = $user->id;
        $upd->password = hash_internal_user_password($params['newpassword']);
        $DB->update_record('user', $upd);
        return ['status' => '1', 'message' => 'Password changed successfully'];
    }

    public static function change_password_returns() {
        return new external_single_structure([
            'status'  => new external_value(PARAM_TEXT, '1=success 0=fail'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }

    // =========================================================
    // ░░ DASHBOARD ░░
    // =========================================================

    // ----- get_dashboard -----
    public static function get_dashboard_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function get_dashboard($userid) {
        global $CFG;
        $params  = self::validate_parameters(self::get_dashboard_parameters(), ['userid' => $userid]);
        $courses = enrol_get_users_courses($params['userid'], true);
        $now     = time();
        $week    = $now + (7  * 86400);
        $month   = $now + (30 * 86400);

        $past_due  = 0;
        $due_soon  = 0;
        $due_later = 0;

        foreach ($courses as $c) {
            if ($c->enddate > 0) {
                if ($c->enddate < $now)         $past_due++;
                elseif ($c->enddate <= $week)   $due_soon++;
                elseif ($c->enddate <= $month)  $due_later++;
            }
        }

        return [
            'userid'           => (int)$params['userid'],
            'past_due_count'   => $past_due,
            'due_soon_count'   => $due_soon,
            'due_later_count'  => $due_later,
            'total_enrolled'   => count($courses),
        ];
    }

    public static function get_dashboard_returns() {
        return new external_single_structure([
            'userid'          => new external_value(PARAM_INT, 'User ID'),
            'past_due_count'  => new external_value(PARAM_INT, 'Past due (red card)'),
            'due_soon_count'  => new external_value(PARAM_INT, 'Due soon within 7 days (blue card)'),
            'due_later_count' => new external_value(PARAM_INT, 'Due later within 30 days (dark card)'),
            'total_enrolled'  => new external_value(PARAM_INT, 'Total enrolled courses'),
        ]);
    }

    // =========================================================
    // ░░ LEARNING — COURSES ░░
    // =========================================================

    // ----- get_my_courses -----
    public static function get_my_courses_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function get_my_courses($userid) {
        global $DB, $CFG;
        $params  = self::validate_parameters(self::get_my_courses_parameters(), ['userid' => $userid]);
        $courses = enrol_get_users_courses($params['userid'], true);
        $output  = [];

        foreach ($courses as $c) {
            $progress = \core_completion\progress::get_course_progress_percentage($c, $params['userid']);
            $output[] = [
                'id'          => (int)$c->id,
                'fullname'    => $c->fullname,
                'shortname'   => $c->shortname,
                'progress'    => (int)round($progress ?? 0),
                'enddate'     => (int)($c->enddate ?? 0),
                'startdate'   => (int)($c->startdate ?? 0),
                'courseimage' => self::get_course_image($c),
                'category'    => (int)$c->category,
            ];
        }

        return ['courses' => $output, 'total' => count($output)];
    }

    public static function get_my_courses_returns() {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id'          => new external_value(PARAM_INT,  'Course ID'),
                    'fullname'    => new external_value(PARAM_TEXT, 'Course full name'),
                    'shortname'   => new external_value(PARAM_TEXT, 'Course short name'),
                    'progress'    => new external_value(PARAM_INT,  'Progress 0-100'),
                    'enddate'     => new external_value(PARAM_INT,  'Due date unix timestamp'),
                    'startdate'   => new external_value(PARAM_INT,  'Start date unix timestamp'),
                    'courseimage' => new external_value(PARAM_RAW,  'Course thumbnail URL'),
                    'category'    => new external_value(PARAM_INT,  'Category ID'),
                ])
            ),
            'total' => new external_value(PARAM_INT, 'Total courses'),
        ]);
    }

    // ----- get_inprogress_courses -----
    public static function get_inprogress_courses_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function get_inprogress_courses($userid) {
        global $DB, $CFG;
        $params  = self::validate_parameters(self::get_inprogress_courses_parameters(), ['userid' => $userid]);
        $courses = enrol_get_users_courses($params['userid'], true);
        $output  = [];

        foreach ($courses as $c) {
            $progress = (int)round(\core_completion\progress::get_course_progress_percentage($c, $params['userid']) ?? 0);
            // In-progress = started but not completed
            if ($progress > 0 && $progress < 100) {
                $output[] = [
                    'id'          => (int)$c->id,
                    'fullname'    => $c->fullname,
                    'shortname'   => $c->shortname,
                    'progress'    => $progress,
                    'enddate'     => (int)($c->enddate ?? 0),
                    'courseimage' => self::get_course_image($c),
                ];
            }
        }

        return ['courses' => $output, 'total' => count($output)];
    }

    public static function get_inprogress_courses_returns() {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id'          => new external_value(PARAM_INT,  'Course ID'),
                    'fullname'    => new external_value(PARAM_TEXT, 'Course name'),
                    'shortname'   => new external_value(PARAM_TEXT, 'Short name'),
                    'progress'    => new external_value(PARAM_INT,  'Progress %'),
                    'enddate'     => new external_value(PARAM_INT,  'Due date'),
                    'courseimage' => new external_value(PARAM_RAW,  'Thumbnail URL'),
                ])
            ),
            'total' => new external_value(PARAM_INT, 'Total in-progress courses'),
        ]);
    }

    // ----- get_course_activities -----
    public static function get_course_activities_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function get_course_activities($userid) {
        global $DB;
        $params  = self::validate_parameters(self::get_course_activities_parameters(), ['userid' => $userid]);
        $courses = enrol_get_users_courses($params['userid'], true);

        $total_activities = 0;
        $completed        = 0;

        foreach ($courses as $course) {
            try {
                $info = new \completion_info($course);
                if (!$info->is_enabled()) continue;
                $activities = $info->get_activities();
                foreach ($activities as $cm) {
                    $total_activities++;
                    $data = $info->get_data($cm, false, $params['userid']);
                    if ($data->completionstate == COMPLETION_COMPLETE ||
                        $data->completionstate == COMPLETION_COMPLETE_PASS) {
                        $completed++;
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return [
            'userid'            => (int)$params['userid'],
            'total_activities'  => $total_activities,
            'completed'         => $completed,
            'incomplete'        => max(0, $total_activities - $completed),
        ];
    }

    public static function get_course_activities_returns() {
        return new external_single_structure([
            'userid'           => new external_value(PARAM_INT, 'User ID'),
            'total_activities' => new external_value(PARAM_INT, 'Total activity count → top left card (6)'),
            'completed'        => new external_value(PARAM_INT, 'Completed activities → top right card (0)'),
            'incomplete'       => new external_value(PARAM_INT, 'Remaining activities'),
        ]);
    }

    // ----- search_courses -----
    public static function search_courses_parameters() {
        return new external_function_parameters([
            'userid'  => new external_value(PARAM_INT,  'User ID'),
            'keyword' => new external_value(PARAM_TEXT, 'Search keyword'),
        ]);
    }

    public static function search_courses($userid, $keyword) {
        global $DB;
        $params  = self::validate_parameters(self::search_courses_parameters(),
            ['userid' => $userid, 'keyword' => $keyword]);

        $enrolled = enrol_get_users_courses($params['userid'], true);
        $kw       = strtolower(trim($params['keyword']));
        $output   = [];

        foreach ($enrolled as $c) {
            if (str_contains(strtolower($c->fullname), $kw) ||
                str_contains(strtolower($c->shortname), $kw)) {
                $progress = (int)round(\core_completion\progress::get_course_progress_percentage($c, $params['userid']) ?? 0);
                $output[] = [
                    'id'          => (int)$c->id,
                    'fullname'    => $c->fullname,
                    'shortname'   => $c->shortname,
                    'progress'    => $progress,
                    'enddate'     => (int)($c->enddate ?? 0),
                    'courseimage' => self::get_course_image($c),
                ];
            }
        }

        return ['courses' => $output, 'total' => count($output), 'keyword' => $params['keyword']];
    }

    public static function search_courses_returns() {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id'          => new external_value(PARAM_INT,  'Course ID'),
                    'fullname'    => new external_value(PARAM_TEXT, 'Full name'),
                    'shortname'   => new external_value(PARAM_TEXT, 'Short name'),
                    'progress'    => new external_value(PARAM_INT,  'Progress %'),
                    'enddate'     => new external_value(PARAM_INT,  'Due date'),
                    'courseimage' => new external_value(PARAM_RAW,  'Thumbnail'),
                ])
            ),
            'total'   => new external_value(PARAM_INT,  'Results count'),
            'keyword' => new external_value(PARAM_TEXT, 'Search keyword used'),
        ]);
    }

    // =========================================================
    // ░░ ILT SESSIONS ░░
    // =========================================================

    // ----- get_ilt_sessions -----
    public static function get_ilt_sessions_parameters() {
        return new external_function_parameters([
            'userid'   => new external_value(PARAM_INT, 'User ID'),
            'upcoming' => new external_value(PARAM_INT, '1=upcoming only, 0=all', VALUE_DEFAULT, 1),
        ]);
    }

    public static function get_ilt_sessions($userid, $upcoming = 1) {
        global $DB, $CFG;
        $params  = self::validate_parameters(self::get_ilt_sessions_parameters(),
            ['userid' => $userid, 'upcoming' => $upcoming]);

        $enrolled = enrol_get_users_courses($params['userid'], true);
        if (empty($enrolled)) return ['sessions' => [], 'total' => 0];

        $course_ids = array_keys($enrolled);
        list($in_sql, $in_params) = $DB->get_in_or_equal($course_ids);

        $now         = time();
        $time_filter = $params['upcoming'] ? "AND s.timestart >= $now" : '';

        // Try to detect ILT sessions table
        $sessions_table = self::detect_table(['ilt_sessions', 'ilt_session', 'ilt_dates']);
        $ilt_table      = self::detect_table(['ilt']);
        $signup_table   = self::detect_table(['ilt_signups', 'ilt_signup', 'ilt_attendance', 'ilt_bookings']);

        if (!$sessions_table || !$ilt_table) {
            // Fallback: return ILT activities from course_modules
            return self::get_ilt_from_course_modules($enrolled, $params['userid'], $params['upcoming']);
        }

        $sql = "SELECT s.*, i.course AS courseid, i.name AS iltname,
                       c.fullname AS coursefullname,
                       cm.id AS cmid
                FROM {{$sessions_table}} s
                JOIN {{$ilt_table}} i  ON i.id = s.iltid
                JOIN {course} c        ON c.id = i.course
                JOIN {course_modules} cm ON cm.instance = i.id
                JOIN {modules} m       ON m.id = cm.module AND m.name = 'ilt'
                WHERE i.course $in_sql
                $time_filter
                ORDER BY s.timestart ASC";

        $records = $DB->get_records_sql($sql, $in_params);
        $output  = [];

        foreach ($records as $row) {
            $signed_up = false;
            if ($signup_table) {
                $signed_up = $DB->record_exists($signup_table, [
                    'userid'    => $params['userid'],
                    'sessionid' => $row->id,
                ]);
            }

            // Get instructor name
            $instructor = $row->instructor ?? '';
            if (!$instructor && !empty($row->instructorid)) {
                $inst = $DB->get_record('user', ['id' => $row->instructorid]);
                $instructor = $inst ? fullname($inst) : '';
            }

            $output[] = [
                'session_id'   => (int)$row->id,
                'ilt_id'       => (int)($row->iltid ?? 0),
                'cmid'         => (int)($row->cmid ?? 0),
                'name'         => (string)($row->sessionname ?? $row->name ?? $row->iltname ?? 'ILT Session'),
                'course_id'    => (int)($row->courseid ?? 0),
                'course_name'  => (string)($row->coursefullname ?? ''),
                'timestart'    => (int)($row->timestart ?? 0),
                'timefinish'   => (int)($row->timefinish ?? 0),
                'instructor'   => (string)$instructor,
                'location'     => (string)($row->location ?? $row->venue ?? ''),
                'capacity'     => (int)($row->capacity ?? $row->maxattendees ?? 0),
                'booked'       => (int)($row->booked ?? $row->signupcount ?? 0),
                'status'       => (string)($row->status ?? 'open'),
                'mode'         => (string)($row->sessiontype ?? $row->deliverymode ?? 'Online'),
                'description'  => strip_tags($row->details ?? $row->description ?? ''),
                'joinurl'      => (string)($row->joinurl ?? $row->meetingurl ?? ''),
                'is_signed_up' => $signed_up,
                'bookingopen'  => true,
                'view_url'     => $CFG->wwwroot . '/mod/ilt/view.php?id=' . ($row->cmid ?? 0),
            ];
        }

        return ['sessions' => $output, 'total' => count($output)];
    }

    public static function get_ilt_sessions_returns() {
        return new external_single_structure([
            'sessions' => new external_multiple_structure(
                new external_single_structure([
                    'session_id'   => new external_value(PARAM_INT,  'Session ID'),
                    'ilt_id'       => new external_value(PARAM_INT,  'ILT activity ID'),
                    'cmid'         => new external_value(PARAM_INT,  'Course module ID'),
                    'name'         => new external_value(PARAM_TEXT, 'Session name'),
                    'course_id'    => new external_value(PARAM_INT,  'Course ID'),
                    'course_name'  => new external_value(PARAM_TEXT, 'Course name'),
                    'timestart'    => new external_value(PARAM_INT,  'Start unix timestamp'),
                    'timefinish'   => new external_value(PARAM_INT,  'End unix timestamp'),
                    'instructor'   => new external_value(PARAM_TEXT, 'Instructor name'),
                    'location'     => new external_value(PARAM_TEXT, 'Location/venue'),
                    'capacity'     => new external_value(PARAM_INT,  'Max capacity'),
                    'booked'       => new external_value(PARAM_INT,  'Current bookings'),
                    'status'       => new external_value(PARAM_TEXT, 'open/closed/full'),
                    'mode'         => new external_value(PARAM_TEXT, 'Online/Offline/ILT'),
                    'description'  => new external_value(PARAM_TEXT, 'Description'),
                    'joinurl'      => new external_value(PARAM_TEXT, 'Online join URL'),
                    'is_signed_up' => new external_value(PARAM_BOOL, 'User booked?'),
                    'bookingopen'  => new external_value(PARAM_BOOL, 'Can book?'),
                    'view_url'     => new external_value(PARAM_TEXT, 'Moodle view URL'),
                ])
            ),
            'total' => new external_value(PARAM_INT, 'Total sessions'),
        ]);
    }

    // ----- get_ilt_session_detail -----
    public static function get_ilt_session_detail_parameters() {
        return new external_function_parameters([
            'userid'    => new external_value(PARAM_INT, 'User ID'),
            'sessionid' => new external_value(PARAM_INT, 'Session ID'),
        ]);
    }

public static function get_ilt_session_detail($userid, $sessionid) {
    global $DB, $CFG;
    $params = self::validate_parameters(self::get_ilt_session_detail_parameters(),
        ['userid' => $userid, 'sessionid' => $sessionid]);

    $sessions_table = self::detect_table(['ilt_sessions', 'ilt_session']);
    $dates_table    = self::detect_table(['ilt_sessions_dates', 'ilt_session_dates', 'ilt_dates']);
    $signup_table   = self::detect_table(['ilt_signups', 'ilt_signup', 'ilt_attendance']);

    $row = $sessions_table ? $DB->get_record($sessions_table, ['id' => $params['sessionid']]) : null;
    if (!$row) throw new moodle_exception('invalidsessionid', 'local_rapmobile');

    // Pull the date row(s) for this session — earliest first.
    $timestart = 0;
    $timefinish = 0;
    if ($dates_table) {
        $daterow = $DB->get_record_sql(
            "SELECT * FROM {" . $dates_table . "} WHERE sessionid = ? ORDER BY timestart ASC LIMIT 1",
            [$params['sessionid']]
        );
        if ($daterow) {
            $timestart  = (int)$daterow->timestart;
            $timefinish = (int)$daterow->timefinish;
        }
    }

    // FK to parent ilt instance is 'ilt', not 'iltid'.
    $iltid = (int)($row->ilt ?? 0);

    $cm = $iltid ? $DB->get_record_sql(
        "SELECT cm.id, cm.course FROM {course_modules} cm
         JOIN {modules} m ON m.id = cm.module AND m.name = 'ilt'
         WHERE cm.instance = ?", [$iltid]) : null;

    // Instructor is stored as a userid FK — resolve to a display name.
    $instructor_name = '';
    if (!empty($row->instructor) && is_numeric($row->instructor)) {
        $instructoruser = $DB->get_record('user', ['id' => (int)$row->instructor],
            'id, firstname, lastname');
        if ($instructoruser) {
            $instructor_name = fullname($instructoruser);
        }
    } else {
        $instructor_name = (string)($row->instructor ?? '');
    }
  // Location: ilt_sessions.location -> local_classroom.id -> classroom name + bu (city)
  $location_name = '';
  if (!empty($row->location) && is_numeric($row->location)) {
    $classroom = $DB->get_record('local_classroom', ['id' => (int)$row->location]);
    if ($classroom) {
        $bu = $DB->get_record('local_bu', ['id' => (int)$classroom->locationid]);
        $location_name = $classroom->classroom;
        if ($bu && !empty($bu->location)) {
            $location_name .= ' - ' . $bu->location;
        }
    }
   } else {
    $location_name = (string)($row->location ?? '');
   }
    $signed_up = $signup_table
        ? $DB->record_exists($signup_table, ['userid' => $params['userid'], 'sessionid' => $params['sessionid']])
        : false;

    return [
        'session_id'   => (int)$params['sessionid'],
        'ilt_id'       => $iltid,
        'cmid'         => (int)($cm->id ?? 0),
        'name'         => (string)($row->sessionname ?? ''),
        'course_id'    => (int)($cm->course ?? 0),
        'timestart'    => $timestart,
        'timefinish'   => $timefinish,
        'instructor'   => $instructor_name,
        'location'     => $location_name,
        'capacity'     => (int)($row->capacity ?? 0),
        'booked'       => $signup_table
            ? (int)$DB->count_records($signup_table, ['sessionid' => $params['sessionid']])
            : 0,
        'status'       => (string)($row->status ?? 'open'),
        'mode'         => (string)($row->sessiontype ?? 'Online'),
        'description'  => strip_tags($row->details ?? ''),
        'joinurl'      => (string)($row->joinurl ?? ''),
        'is_signed_up' => $signed_up,
        'bookingopen'  => true,
        'view_url'     => $CFG->wwwroot . '/mod/ilt/view.php?id=' . ($cm->id ?? 0),
    ];
}

    public static function get_ilt_session_detail_returns() {
        return new external_single_structure([
            'session_id'   => new external_value(PARAM_INT,  'Session ID'),
            'ilt_id'       => new external_value(PARAM_INT,  'ILT ID'),
            'cmid'         => new external_value(PARAM_INT,  'CMID'),
            'name'         => new external_value(PARAM_TEXT, 'Name'),
            'course_id'    => new external_value(PARAM_INT,  'Course ID'),
            'timestart'    => new external_value(PARAM_INT,  'Start time'),
            'timefinish'   => new external_value(PARAM_INT,  'End time'),
            'instructor'   => new external_value(PARAM_TEXT, 'Instructor'),
            'location'     => new external_value(PARAM_TEXT, 'Location'),
            'capacity'     => new external_value(PARAM_INT,  'Capacity'),
            'booked'       => new external_value(PARAM_INT,  'Booked'),
            'status'       => new external_value(PARAM_TEXT, 'Status'),
            'mode'         => new external_value(PARAM_TEXT, 'Mode'),
            'description'  => new external_value(PARAM_TEXT, 'Description'),
            'joinurl'      => new external_value(PARAM_TEXT, 'Join URL'),
            'is_signed_up' => new external_value(PARAM_BOOL, 'Signed up'),
            'bookingopen'  => new external_value(PARAM_BOOL, 'Booking open'),
            'view_url'     => new external_value(PARAM_TEXT, 'View URL'),
        ]);
    }

    // ----- signup_ilt_session -----
    public static function signup_ilt_session_parameters() {
        return new external_function_parameters([
            'userid'    => new external_value(PARAM_INT, 'User ID'),
            'sessionid' => new external_value(PARAM_INT, 'Session ID'),
        ]);
    }

    public static function signup_ilt_session($userid, $sessionid) {
        global $DB;
        $params       = self::validate_parameters(self::signup_ilt_session_parameters(),
            ['userid' => $userid, 'sessionid' => $sessionid]);
        $signup_table = self::detect_table(['ilt_signups', 'ilt_signup', 'ilt_attendance', 'ilt_bookings']);

        if (!$signup_table) return ['success' => false, 'message' => 'Signup table not found — contact admin'];

        if ($DB->record_exists($signup_table, ['userid' => $params['userid'], 'sessionid' => $params['sessionid']])) {
            return ['success' => false, 'message' => 'Already signed up'];
        }

       $signup                   = new stdClass();
        $signup->sessionid        = $params['sessionid'];
        $signup->userid           = $params['userid'];
        $signup->mailedreminder   = 0;
        $signup->notificationtype = 0;

        try {
   
            $DB->insert_record($signup_table, $signup);
            return ['success' => true, 'message' => 'Booked successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Booking failed: ' . $e->getMessage()];
        }
    }

    public static function signup_ilt_session_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success'),
            'message' => new external_value(PARAM_TEXT, 'Message'),
        ]);
    }

    // ----- cancel_ilt_signup -----
    public static function cancel_ilt_signup_parameters() {
        return new external_function_parameters([
            'userid'    => new external_value(PARAM_INT, 'User ID'),
            'sessionid' => new external_value(PARAM_INT, 'Session ID'),
        ]);
    }

    public static function cancel_ilt_signup($userid, $sessionid) {
        global $DB;
        $params       = self::validate_parameters(self::cancel_ilt_signup_parameters(),
            ['userid' => $userid, 'sessionid' => $sessionid]);
        $signup_table = self::detect_table(['ilt_signups', 'ilt_signup', 'ilt_attendance', 'ilt_bookings']);

        if (!$signup_table) return ['success' => false, 'message' => 'Signup table not found'];
        $deleted = $DB->delete_records($signup_table, ['userid' => $params['userid'], 'sessionid' => $params['sessionid']]);
        return ['success' => (bool)$deleted, 'message' => $deleted ? 'Booking cancelled' : 'No booking found'];
    }

    public static function cancel_ilt_signup_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success'),
            'message' => new external_value(PARAM_TEXT, 'Message'),
        ]);
    }
// ----- remove_assign_submission -----
// ----- remove_assign_submission -----
    public static function remove_assign_submission_parameters() {
        return new external_function_parameters([
            'cmid'   => new external_value(PARAM_INT, 'Course module ID'),
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

 public static function remove_assign_submission($cmid, $userid) {
        global $DB, $USER, $CFG;

        $params = self::validate_parameters(self::remove_assign_submission_parameters(),
            ['cmid' => $cmid, 'userid' => $userid]);

        $cm = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // Students can only remove their own submission; teachers need editothersubmission.
        if ($params['userid'] != $USER->id) {
            require_capability('mod/assign:editothersubmission', $context);
        } else {
            require_capability('mod/assign:submit', $context);
        }

        require_once($CFG->dirroot . '/mod/assign/locallib.php');
    $assign = new \assign($context, $cm, null);

    // Native core function — same one the "Remove submission" button calls.
    $result = $assign->remove_submission($params['userid']);

    return [
        'success' => (bool)$result,
        'message' => $result ? 'Submission removed successfully' : 'Failed to remove submission',
    ];
}

public static function remove_assign_submission_returns() {
    return new external_single_structure([
        'success' => new external_value(PARAM_BOOL, 'Success'),
        'message' => new external_value(PARAM_TEXT, 'Message'),
    ]);
}
// =========================================================
// ░░ ASSIGNMENT SUBMISSION COMMENTS ░░
// =========================================================

// ----- get_submission_comments -----
public static function get_submission_comments_parameters() {
    return new external_function_parameters([
        'cmid'   => new external_value(PARAM_INT, 'Assignment course module ID'),
        'itemid' => new external_value(PARAM_INT, 'Submission ID (assign_submission.id)'),
    ]);
}

public static function get_submission_comments($cmid, $itemid) {
    global $CFG, $DB;

    $params = self::validate_parameters(self::get_submission_comments_parameters(),
        ['cmid' => $cmid, 'itemid' => $itemid]);

    $cm      = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    self::validate_context($context);
    require_capability('mod/assign:viewgrades', $context, null, false)
        || require_capability('mod/assign:submit', $context);

    require_once($CFG->dirroot . '/comment/lib.php');

    $args = (object)[
        'context'   => $context,
        'area'      => 'submission_comments',
        'component' => 'assignsubmission_comments',
        'itemid'    => $params['itemid'],
        'cm'        => $cm,
        'course'    => get_course($cm->course),
    ];

    $comment  = new comment($args);
    $comments = $comment->get_comments();

    // get_comments() rows carry userid but no name fields, so fullname($c)
    // was returning '' every time. Batch-fetch the commenters instead.
    $userids = array_unique(array_map(fn($c) => (int)$c->userid, $comments));
    $users   = $userids ? $DB->get_records_list('user', 'id', $userids,
        '', 'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename') : [];

    $output = [];
    foreach ($comments as $c) {
        $user = $users[$c->userid] ?? null;

        $output[] = [
            'id'          => (int)$c->id,
            // Strip the "no-overflow / text_to_html" wrapper divs the
            // comment renderer adds — just the text underneath.
            'content'     => trim(html_entity_decode(strip_tags($c->content), ENT_QUOTES)),
            'userid'      => (int)$c->userid,
            'fullname'    => $user ? fullname($user) : '',
            'timecreated' => (int)$c->timecreated,
        ];
    }

    return ['comments' => $output, 'total' => count($output)];
}

public static function get_submission_comments_returns() {
    return new external_single_structure([
        'comments' => new external_multiple_structure(
            new external_single_structure([
                'id'          => new external_value(PARAM_INT,  'Comment ID'),
                'content'     => new external_value(PARAM_RAW,  'Comment text'),
                'userid'      => new external_value(PARAM_INT,  'Author user ID'),
                'fullname'    => new external_value(PARAM_TEXT, 'Author full name'),
                'timecreated' => new external_value(PARAM_INT,  'Unix timestamp'),
            ])
        ),
        'total' => new external_value(PARAM_INT, 'Total comments'),
    ]);
}

// ----- add_submission_comment -----
public static function add_submission_comment_parameters() {
    return new external_function_parameters([
        'cmid'    => new external_value(PARAM_INT, 'Assignment course module ID'),
        'itemid'  => new external_value(PARAM_INT, 'Submission ID (assign_submission.id)'),
        'content' => new external_value(PARAM_TEXT, 'Comment text'),
    ]);
}

public static function add_submission_comment($cmid, $itemid, $content) {
    global $CFG;

    $params = self::validate_parameters(self::add_submission_comment_parameters(),
        ['cmid' => $cmid, 'itemid' => $itemid, 'content' => $content]);

    if (trim($params['content']) === '') {
        return ['success' => false, 'message' => 'Comment cannot be empty', 'commentid' => 0];
    }

    $cm      = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    self::validate_context($context);

    require_once($CFG->dirroot . '/comment/lib.php');

    $args = (object)[
        'context'   => $context,
        'area'      => 'submission_comments',
        'component' => 'assignsubmission_comments',
        'itemid'    => $params['itemid'],
        'cm'        => $cm,
        'course'    => get_course($cm->course),
    ];

    $comment = new comment($args);
    $result  = $comment->add($params['content']);

    return [
        'success'   => (bool)$result,
        'message'   => $result ? 'Comment added' : 'Failed to add comment',
        'commentid' => (int)($result->id ?? 0),
    ];
}

public static function add_submission_comment_returns() {
    return new external_single_structure([
        'success'   => new external_value(PARAM_BOOL, 'Success'),
        'message'   => new external_value(PARAM_TEXT, 'Message'),
        'commentid' => new external_value(PARAM_INT,  'New comment ID'),
    ]);
}

// ----- delete_submission_comment -----
public static function delete_submission_comment_parameters() {
    return new external_function_parameters([
        'cmid'      => new external_value(PARAM_INT, 'Assignment course module ID'),
        'itemid'    => new external_value(PARAM_INT, 'Submission ID (assign_submission.id)'),
        'commentid' => new external_value(PARAM_INT, 'Comment ID to delete'),
    ]);
}

public static function delete_submission_comment($cmid, $itemid, $commentid) {
    global $CFG;

    $params = self::validate_parameters(self::delete_submission_comment_parameters(),
        ['cmid' => $cmid, 'itemid' => $itemid, 'commentid' => $commentid]);

    $cm      = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    self::validate_context($context);

    require_once($CFG->dirroot . '/comment/lib.php');

    $args = (object)[
        'context'   => $context,
        'area'      => 'submission_comments',
        'component' => 'assignsubmission_comments',
        'itemid'    => $params['itemid'],
        'cm'        => $cm,
        'course'    => get_course($cm->course),
    ];

    $comment = new comment($args);
    $result  = $comment->delete($params['commentid']);

    return ['success' => (bool)$result, 'message' => $result ? 'Comment deleted' : 'Delete failed'];
}

public static function delete_submission_comment_returns() {
    return new external_single_structure([
        'success' => new external_value(PARAM_BOOL, 'Success'),
        'message' => new external_value(PARAM_TEXT, 'Message'),
    ]);
}
    // =========================================================
    // ░░ GAMIFICATION ░░
    // =========================================================

    // ----- get_game_dashboard -----
    public static function get_game_dashboard_parameters() {
        return new external_function_parameters([
            'username' => new external_value(PARAM_USERNAME, 'Username'),
        ]);
    }

public static function get_game_dashboard($username) {
        global $DB, $CFG;
        $params = self::validate_parameters(self::get_game_dashboard_parameters(), ['username' => $username]);
        $user   = $DB->get_record('user', ['username' => $params['username'], 'deleted' => 0], '*', MUST_EXIST);

        self::ensure_user_points($user->id);
        set_my_rank($user->id);

        $ctx       = context_user::instance($user->id);
        $nextlevel = get_next_level($user->id);
        $badges    = $DB->get_records('badge_issued', ['userid' => $user->id, 'visible' => 1]);

        $leaderboard = get_leaderboard_api();
        $lb_output   = [];
        foreach ($leaderboard as $row) {
            $lb_output[] = [
                'rank'             => (int)($row['rank'] ?? 0),
                'name'             => (string)($row['name'] ?? ''),
                'image'            => (string)($row['image'] ?? ''),
                'user_rank'        => (string)($row['user_rank'] ?? '-'),
                'department'       => (string)($row['department'] ?? ''),
                'available_points' => (int)($row['available_points'] ?? 0),
                'is_current_user'  => (bool)($row['is_current_user'] ?? false),
                'is_outside_top10' => (bool)($row['is_outside_top10'] ?? false),
            ];
        }

return [
    'userid'           => (int)$user->id,
    'username'         => $user->username,
    'fullname'         => fullname($user),
    'profileimage'     => $CFG->wwwroot . "/pluginfile.php/{$ctx->id}/user/icon/f1",
    'total_points'     => (int)get_total_points($user->id),
    'available_points' => (int)get_available_points($user->id),
    'redeemed_points'  => (int)get_redeemed_points($user->id),
    'badge_count'      => count($badges),
    'my_rank'          => (string)(get_my_rank($user->id) ?? '-'),
    'nextlevel'        => (string)($nextlevel[2] ?? '-'),
    'points_needed'    => (string)($nextlevel[0] ?? '-'),
    'grade_needed'     => (string)($nextlevel[1] ?? '-'),   // <-- added
    'average_grade'    => (string)(get_user_grade($user->id) ?? '-'),  // <-- added
    'spinbutton'       => (string)get_spinwheel_button_api($user->id),
    'login_points'     => (int)get_points($user->id, 'login'),
    'quiz_points'      => (int)get_points($user->id, 'quiz'),
    'spinwheel_points' => (int)get_points($user->id, 'spinwheel'),
    'leaderboard'      => $lb_output,
];

    }

   public static function get_game_dashboard_returns() {
    return new external_single_structure([
        'userid'           => new external_value(PARAM_INT,   'User ID'),
        'username'         => new external_value(PARAM_TEXT,  'Username'),
        'fullname'         => new external_value(PARAM_TEXT,  'Full name'),
        'profileimage'     => new external_value(PARAM_RAW,   'Avatar URL'),
        'total_points'     => new external_value(PARAM_INT,   'Total points → Points card'),
        'available_points' => new external_value(PARAM_INT,   'Available to redeem'),
        'redeemed_points'  => new external_value(PARAM_INT,   'Already redeemed'),
        'badge_count'      => new external_value(PARAM_INT,   'Badge count → Badges card'),
        'my_rank'          => new external_value(PARAM_TEXT,  'Current level → Level card'),
        'nextlevel'        => new external_value(PARAM_TEXT,  'Next level name'),
        'points_needed'    => new external_value(PARAM_TEXT,  'Points to next level'),
        'grade_needed'     => new external_value(PARAM_TEXT,  'Grade needed for next level'),  // <-- add
        'average_grade'    => new external_value(PARAM_TEXT,  'User average grade'),           // <-- add
        'spinbutton' => new external_value(PARAM_RAW, 'Can spin today: 1/0 or HTML message'),
        'login_points'     => new external_value(PARAM_INT,   'Points from login'),
        'quiz_points'      => new external_value(PARAM_INT,   'Points from quiz'),
        'spinwheel_points' => new external_value(PARAM_INT,   'Points from spin'),
        'leaderboard'      => new external_multiple_structure(
            new external_single_structure([
                'rank'             => new external_value(PARAM_INT,  'Position'),
                'name'             => new external_value(PARAM_TEXT, 'Name'),
                'image'            => new external_value(PARAM_RAW,  'Avatar URL'),
                'user_rank'        => new external_value(PARAM_TEXT, 'Rank name'),
                'department'       => new external_value(PARAM_TEXT, 'Department'),
                'available_points' => new external_value(PARAM_INT,  'Points'),
                'is_current_user'  => new external_value(PARAM_BOOL, 'Highlight flag for the requesting user'),
                'is_outside_top10' => new external_value(PARAM_BOOL, 'True if this row is the appended current-user row beyond top 10'),
            ])
        ),
    ]);
}
 
public static function redeem_scratch_card_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
        'scid'   => new external_value(PARAM_INT, 'Scratch card ID from get_scratch_cards response'),
    ]);
}
 
public static function redeem_scratch_card($userid, $scid) {
    global $DB;
 
    $params = self::validate_parameters(
        self::redeem_scratch_card_parameters(),
        ['userid' => $userid, 'scid' => $scid]
    );
 
    // Find card
    $card = $DB->get_record('user_scratchcard', [
        'id'     => $params['scid'],
        'userid' => $params['userid'],
    ]);
 
    if (!$card) {
        return [
            'success'          => false,
            'message'          => 'Card not found',
            'points_won'       => 0,
            'already_redeemed' => false,
            'total_points'     => (int)get_total_points($params['userid']),
            'available_points' => (int)get_available_points($params['userid']),
            'remaining_cards'  => 0,
        ];
    }
 
    // Already scratched?
    if ((int)$card->redeemed === 1) {
        return [
            'success'          => false,
            'message'          => 'Card already scratched',
            'points_won'       => (int)$card->point,
            'already_redeemed' => true,
            'total_points'     => (int)get_total_points($params['userid']),
            'available_points' => (int)get_available_points($params['userid']),
            'remaining_cards'  => (int)$DB->count_records('user_scratchcard', [
                                      'userid' => $params['userid'],
                                      'redeemed' => 0,
                                      'card_type' => 'rank',
                                  ]),
        ];
    }
 
    // Mark redeemed
    $DB->set_field('user_scratchcard', 'redeemed', 1, ['id' => $card->id]);
 
    // Award points using existing lib.php function
  // Award points — scratchcard type only, never interferes with spinwheel
$points_won = (int)$card->point;
if ($points_won > 0) {
    self::ensure_user_points($params['userid']);
    add_point_log($params['userid'], 'scratchcard', 'added', $points_won);
}
 
    // Remaining unscratched cards
    $remaining = (int)$DB->count_records('user_scratchcard', [
        'userid'    => $params['userid'],
        'redeemed'  => 0,
        'card_type' => 'rank',
    ]);
 
    return [
        'success'          => true,
        'message'          => 'You won ' . $points_won . ' points!',
        'points_won'       => $points_won,
        'already_redeemed' => false,
        'total_points'     => (int)get_total_points($params['userid']),
        'available_points' => (int)get_available_points($params['userid']),
        'remaining_cards'  => $remaining,
    ];
}
 
public static function redeem_scratch_card_returns() {
    return new external_single_structure([
        'success'          => new external_value(PARAM_BOOL, 'true = scratched successfully'),
        'message'          => new external_value(PARAM_TEXT, 'You won X points! or error'),
        'points_won'       => new external_value(PARAM_INT,  'Points won from this card'),
        'already_redeemed' => new external_value(PARAM_BOOL, 'Was already scratched before?'),
        'total_points'     => new external_value(PARAM_INT,  'Updated total points'),
        'available_points' => new external_value(PARAM_INT,  'Updated available points'),
        'remaining_cards'  => new external_value(PARAM_INT,  'Remaining unscratched cards'),
    ]);
}
    // ----- spin_wheel -----
    public static function spin_wheel_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'points' => new external_value(PARAM_INT, 'Points won from spin segment'),
        ]);
    }

    public static function spin_wheel($userid, $points) {
        global $DB;
        $params = self::validate_parameters(self::spin_wheel_parameters(),
            ['userid' => $userid, 'points' => $points]);

        // Check already spun today
       $SQL = "SELECT * FROM {user_points_log}
        WHERE userid = {$params['userid']}
        AND point_type = 'spinwheel'
        AND DATE_FORMAT(FROM_UNIXTIME(timecreated), '%Y-%m-%d') = CURDATE()";
        if ($DB->record_exists_sql($SQL)) {
            return [
                'success'      => false,
                'message'      => 'Already spun today. Come back tomorrow!',
                'points_won'   => 0,
                'total_points' => (int)get_total_points($params['userid']),
            ];
        }

        self::ensure_user_points($params['userid']);
        add_point_log($params['userid'], 'spinwheel', 'added', $params['points']);

        return [
            'success'      => true,
            'message'      => 'You won ' . $params['points'] . ' points!',
            'points_won'   => (int)$params['points'],
            'total_points' => (int)get_total_points($params['userid']),
        ];
    }


public static function search_gift_users_parameters() {
    return new external_function_parameters([
        'userid'  => new external_value(PARAM_INT,  'Sender user ID'),
        'keyword' => new external_value(PARAM_TEXT, 'Search keyword (name/email/username)', VALUE_DEFAULT, ''),
    ]);
}
 
public static function search_gift_users($userid, $keyword = '') {
    global $DB, $CFG, $PAGE,$USER;
 
    $params = self::validate_parameters(
        self::search_gift_users_parameters(),
        ['userid' => $userid, 'keyword' => $keyword]
    );
 
    // Get sender's available points
    $available_points = (int)get_available_points($params['userid']);
 
    // Build search SQL — same company users (IOMAD aware)
    $keyword = trim($params['keyword']);
 
    if (!empty($keyword)) {
        $like = '%' . $DB->sql_like_escape($keyword) . '%';
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email,
                   u.username, u.department, u.picture,
                   u.imagealt, u.firstnamephonetic,
                   u.lastnamephonetic, u.middlename, u.alternatename,
                   ctx.id AS ctxid
            FROM {user} u
            JOIN {context} ctx ON ctx.instanceid = u.id AND ctx.contextlevel = 30
            WHERE u.deleted   = 0
              AND u.suspended = 0
              AND u.confirmed = 1
              AND u.id > 2
              AND u.id != :senderid
              AND u.email NOT LIKE '%localhost%'
              AND (
                  " . $DB->sql_like('u.firstname', ':kw1', false) . "
               OR " . $DB->sql_like('u.lastname',  ':kw2', false) . "
               OR " . $DB->sql_like('u.email',     ':kw3', false) . "
               OR " . $DB->sql_like('u.username',  ':kw4', false) . "
               OR " . $DB->sql_like(
                          $DB->sql_concat('u.firstname', "' '", 'u.lastname'),
                          ':kw5', false
                      ) . "
              )
            ORDER BY u.firstname ASC
            LIMIT 50";
 
        $sqlparams = [
            'senderid' => $params['userid'],
            'kw1' => $like, 'kw2' => $like,
            'kw3' => $like, 'kw4' => $like,
            'kw5' => $like,
        ];
    } else {
        // No keyword — return all site users except sender
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email,
                   u.username, u.department, u.picture,
                   u.imagealt, u.firstnamephonetic,
                   u.lastnamephonetic, u.middlename, u.alternatename,
                   ctx.id AS ctxid
            FROM {user} u
            JOIN {context} ctx ON ctx.instanceid = u.id AND ctx.contextlevel = 30
            WHERE u.deleted   = 0
              AND u.suspended = 0
              AND u.confirmed = 1
              AND u.id > 2
              AND u.id != :senderid
              AND u.email NOT LIKE '%localhost%'
            ORDER BY u.firstname ASC
            LIMIT 100";
 
        $sqlparams = ['senderid' => $params['userid']];
    }
 
    $users  = $DB->get_records_sql($sql, $sqlparams);
    $output = [];
 
    foreach ($users as $u) {
        $ctx    = context_user::instance($u->id);
        // Profile image — webservice compatible URL
        $userpicture = new \user_picture($u);
        $userpicture->size = 1;
        $profileimage = $CFG->wwwroot . "/webservice/pluginfile.php/{$ctx->id}/user/icon/f1?rev={$u->picture}&token=";
 
        $output[] = [
            'userid'       => (int)$u->id,
            'fullname'     => fullname($u),
            'username'     => $u->username,
            'email'        => $u->email,
            'department'   => $u->department ?? '',
            'profileimage' => $profileimage,
        ];
    }
 
    return [
        'users'            => $output,
        'total'            => count($output),
        'available_points' => $available_points,   // "Avl Pts: 367" in header
        'keyword'          => $keyword,
    ];
}
 
public static function search_gift_users_returns() {
    return new external_single_structure([
        'users' => new external_multiple_structure(
            new external_single_structure([
                'userid'       => new external_value(PARAM_INT,   'User ID'),
                'fullname'     => new external_value(PARAM_TEXT,  'Full name → list row name'),
                'username'     => new external_value(PARAM_TEXT,  'Username'),
                'email'        => new external_value(PARAM_EMAIL, 'Email'),
                'department'   => new external_value(PARAM_TEXT,  'Department'),
                'profileimage' => new external_value(PARAM_RAW,   'Profile avatar URL'),
            ])
        ),
        'total'            => new external_value(PARAM_INT, 'Total users found'),
        'available_points' => new external_value(PARAM_INT, 'Sender available pts → header "Avl Pts: 367"'),
        'keyword'          => new external_value(PARAM_TEXT, 'Search keyword used'),
    ]);
}
 
// =========================================================
// 2. SEND GIFT POINTS TO A USER
//    Screen: Enter points → tap Send button
//    Deducts from sender, adds to receiver
// =========================================================
 
public static function send_gift_points_parameters() {
    return new external_function_parameters([
        'senderid'   => new external_value(PARAM_INT, 'Sender user ID'),
        'receiverid' => new external_value(PARAM_INT, 'Receiver user ID'),
        'points'     => new external_value(PARAM_INT, 'Points to gift'),
    ]);
}
 
public static function send_gift_points($senderid, $receiverid, $points) {
    global $DB;
 
    $params = self::validate_parameters(
        self::send_gift_points_parameters(),
        [
            'senderid'   => $senderid,
            'receiverid' => $receiverid,
            'points'     => $points,
        ]
    );
 
    // ---- Validate points ----
    if ($params['points'] <= 0) {
        return [
            'success'                   => false,
            'message'                   => 'Points must be greater than 0',
            'sender_available_points'   => (int)get_available_points($params['senderid']),
            'receiver_total_points'     => (int)get_total_points($params['receiverid']),
        ];
    }
 
    // ---- Check sender exists ----
    $sender = $DB->get_record('user', [
        'id'      => $params['senderid'],
        'deleted' => 0,
    ]);
    if (!$sender) {
        return [
            'success'                 => false,
            'message'                 => 'Sender not found',
            'sender_available_points' => 0,
            'receiver_total_points'   => 0,
        ];
    }
 
    // ---- Check receiver exists ----
    $receiver = $DB->get_record('user', [
        'id'      => $params['receiverid'],
        'deleted' => 0,
    ]);
    if (!$receiver) {
        return [
            'success'                   => false,
            'message'                   => 'Receiver not found',
            'sender_available_points'   => (int)get_available_points($params['senderid']),
            'receiver_total_points'     => 0,
        ];
    }
 
    // ---- Cannot gift to yourself ----
    if ($params['senderid'] === $params['receiverid']) {
        return [
            'success'                   => false,
            'message'                   => 'You cannot gift points to yourself',
            'sender_available_points'   => (int)get_available_points($params['senderid']),
            'receiver_total_points'     => (int)get_total_points($params['receiverid']),
        ];
    }
 
    // ---- Check sender has enough points ----
    $sender_available = (int)get_available_points($params['senderid']);
    if ($params['points'] > $sender_available) {
        return [
            'success'                   => false,
            'message'                   => 'Insufficient points. Available: ' . $sender_available,
            'sender_available_points'   => $sender_available,
            'receiver_total_points'     => (int)get_total_points($params['receiverid']),
        ];
    }
 
    // ---- Ensure both users have user_points rows ----
    self::ensure_user_points($params['senderid']);
    self::ensure_user_points($params['receiverid']);
 
    // ---- Deduct from sender (gift action) ----
    // Uses add_point_log from lib.php — 'gift_send' type, negative action
    add_point_log(
        $params['senderid'],
        'gift_send',       // type
        'redeemed',        // action — deducts from available_points
        $params['points']
    );
 
    // ---- Add to receiver ----
    // Uses add_point_log from lib.php — 'gift_receive' type
    add_point_log(
        $params['receiverid'],
        'gift_receive',    // type
        'added',           // action — adds to available_points
        $params['points']
    );
 
    $sender_name   = fullname($sender);
    $receiver_name = fullname($receiver);
 
    return [
        'success'                 => true,
        'message'                 => 'You gifted ' . $params['points']
                                     . ' points to ' . $receiver_name,
        'sender_available_points' => (int)get_available_points($params['senderid']),
        'receiver_total_points'   => (int)get_total_points($params['receiverid']),
        'sender_name'             => $sender_name,
        'receiver_name'           => $receiver_name,
        'points_gifted'           => (int)$params['points'],
    ];
}
 
public static function send_gift_points_returns() {
    return new external_single_structure([
        'success'                 => new external_value(PARAM_BOOL, 'Gift successful'),
        'message'                 => new external_value(PARAM_TEXT, 'Result message'),
        'sender_available_points' => new external_value(PARAM_INT,  'Sender updated available points'),
        'receiver_total_points'   => new external_value(PARAM_INT,  'Receiver updated total points'),
        'sender_name'             => new external_value(PARAM_TEXT, 'Sender full name', VALUE_OPTIONAL),
        'receiver_name'           => new external_value(PARAM_TEXT, 'Receiver full name', VALUE_OPTIONAL),
        'points_gifted'           => new external_value(PARAM_INT,  'Points gifted', VALUE_OPTIONAL),
    ]);
}

    public static function spin_wheel_returns() {
        return new external_single_structure([
            'success'      => new external_value(PARAM_BOOL, 'Success'),
            'message'      => new external_value(PARAM_TEXT, 'Result message'),
            'points_won'   => new external_value(PARAM_INT,  'Points won'),
            'total_points' => new external_value(PARAM_INT,  'Updated total points'),
        ]);
    }

    // ----- get_scratch_cards -----
    public static function get_scratch_cards_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function get_scratch_cards($userid) {
        global $DB;
        $params = self::validate_parameters(self::get_scratch_cards_parameters(), ['userid' => $userid]);
        $cards  = get_my_scratchcard_api($params['userid']);

        $total_sql       = "SELECT COUNT(*) FROM {user_scratchcard} WHERE userid = ? AND redeemed = 0 AND card_type = 'rank'";
        $total_remaining = (int)$DB->count_records_sql($total_sql, [$params['userid']]);

        $output = [];
        foreach ($cards as $card) {
            $output[] = [
                'scid'         => (int)($card['scid'] ?? 0),
                'point'        => (int)($card['point'] ?? 0),
                'pointimage'   => (string)($card['pointimage'] ?? ''),
                'scratchimage' => (string)($card['scratchimage'] ?? ''),
            ];
        }

        return [
            'cards'           => $output,
            'cards_shown'     => count($output),
            'total_remaining' => $total_remaining,
        ];
    }

    public static function get_scratch_cards_returns() {
        return new external_single_structure([
            'cards' => new external_multiple_structure(
                new external_single_structure([
                    'scid'         => new external_value(PARAM_INT,  'Card ID'),
                    'point'        => new external_value(PARAM_INT,  'Prize points'),
                    'pointimage'   => new external_value(PARAM_RAW,  'Prize image URL'),
                    'scratchimage' => new external_value(PARAM_RAW,  'Cover/scratch image URL'),
                ])
            ),
            'cards_shown'     => new external_value(PARAM_INT, 'Cards shown (max 3)'),
            'total_remaining' => new external_value(PARAM_INT, 'Total unscratched — shown as "3 scratches remaining"'),
        ]);
    }

    // ----- get_leaderboard -----
    public static function get_leaderboard_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'Requesting user ID'),
        ]);
    }

    public static function get_leaderboard($userid) {
        $params = self::validate_parameters(self::get_leaderboard_parameters(), ['userid' => $userid]);
        $data   = get_leaderboard_api();
        $output = [];
        $rank   = 1;

        foreach ($data as $row) {
            $output[] = [
                'rank'             => $rank,
                'name'             => (string)($row['name'] ?? ''),
                'image'            => (string)($row['image'] ?? ''),
                'rank_icon'        => (string)($row['rank_icon'] ?? ''),
                'user_rank'        => (string)($row['user_rank'] ?? '-'),
                'department'       => (string)($row['department'] ?? ''),
                'available_points' => (int)($row['available_points'] ?? 0),
            ];
            $rank++;
        }

        return ['leaderboard' => $output, 'total' => count($output)];
    }

    public static function get_leaderboard_returns() {
        return new external_single_structure([
            'leaderboard' => new external_multiple_structure(
                new external_single_structure([
                    'rank'             => new external_value(PARAM_INT,  'Rank'),
                    'name'             => new external_value(PARAM_TEXT, 'Name'),
                    'image'            => new external_value(PARAM_RAW,  'Avatar URL'),
                    'rank_icon'        => new external_value(PARAM_RAW,  'Rank icon URL'),
                    'user_rank'        => new external_value(PARAM_TEXT, 'Level name'),
                    'department'       => new external_value(PARAM_TEXT, 'Department'),
                    'available_points' => new external_value(PARAM_INT,  'Points'),
                ])
            ),
            'total' => new external_value(PARAM_INT, 'Total users'),
        ]);
    }

    // ----- redeem_points -----
    public static function redeem_points_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'points' => new external_value(PARAM_INT, 'Points to redeem'),
        ]);
    }

    public static function redeem_points($userid, $points) {
        $params    = self::validate_parameters(self::redeem_points_parameters(),
            ['userid' => $userid, 'points' => $points]);
        $available = get_available_points($params['userid']);

        if ($params['points'] > $available) {
            return [
                'success'          => false,
                'message'          => 'Insufficient points. Available: ' . $available,
                'available_points' => (int)$available,
            ];
        }

        self::ensure_user_points($params['userid']);
        add_point_log($params['userid'], 'redeem', 'redeemed', abs($params['points']));

        return [
            'success'          => true,
            'message'          => 'Successfully redeemed ' . $params['points'] . ' points',
            'available_points' => (int)get_available_points($params['userid']),
        ];
    }

    public static function redeem_points_returns() {
        return new external_single_structure([
            'success'          => new external_value(PARAM_BOOL, 'Success'),
            'message'          => new external_value(PARAM_TEXT, 'Message'),
            'available_points' => new external_value(PARAM_INT,  'Updated available points'),
        ]);
    }

    // ----- add_point -----
    public static function add_point_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'type'   => new external_value(PARAM_RAW, 'Type: login/quiz/spinwheel/course_complete'),
            'point'  => new external_value(PARAM_INT, 'Points to add'),
        ]);
    }

    public static function add_point($userid, $type, $point) {
        $params = self::validate_parameters(self::add_point_parameters(),
            ['userid' => $userid, 'type' => $type, 'point' => $point]);

        $allowed = ['login', 'quiz', 'spinwheel', 'course_complete', 'poll', 'scratchcard'];
        if (!in_array($params['type'], $allowed)) {
            return ['success' => false, 'message' => 'Invalid type. Use: ' . implode(', ', $allowed),
                    'total_points' => (int)get_total_points($params['userid'])];
        }

        self::ensure_user_points($params['userid']);
        $result = add_point_log($params['userid'], $params['type'], 'added', $params['point']);

        return [
            'success'      => (bool)$result,
            'message'      => $result ? $params['point'] . ' points added' : 'Failed',
            'total_points' => (int)get_total_points($params['userid']),
        ];
    }

    public static function add_point_returns() {
        return new external_single_structure([
            'success'      => new external_value(PARAM_BOOL, 'Success'),
            'message'      => new external_value(PARAM_TEXT, 'Message'),
            'total_points' => new external_value(PARAM_INT,  'Updated total points'),
        ]);
    }

    // =========================================================
    // ░░ POLLS ░░
    // =========================================================

    // ----- get_polls -----
    public static function get_polls_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function get_polls($userid) {
        global $DB;
        $params   = self::validate_parameters(self::get_polls_parameters(), ['userid' => $userid]);
        $enrolled = enrol_get_users_courses($params['userid'], true);
        if (empty($enrolled)) return ['polls' => [], 'total_polls' => 0];

        $course_ids = array_keys($enrolled);
        list($in_sql, $in_params) = $DB->get_in_or_equal($course_ids);

        $sql = "SELECT c.id, c.name, c.intro, c.timeopen, c.timeclose, cm.id as cmid
                FROM {choice} c
                JOIN {course_modules} cm ON cm.instance = c.id
                JOIN {modules} m ON m.id = cm.module AND m.name = 'choice'
                WHERE c.course $in_sql
                AND (c.timeclose = 0 OR c.timeclose > ?)
                AND cm.visible = 1
                ORDER BY c.timeopen DESC";

        $polls  = $DB->get_records_sql($sql, array_merge($in_params, [time()]));
        $output = [];

        foreach ($polls as $poll) {
            $answered = $DB->record_exists('choice_answers', [
                'choiceid' => $poll->id, 'userid' => $params['userid'],
            ]);
            $options = $DB->get_records('choice_options', ['choiceid' => $poll->id], 'id ASC');
            $opt_out = [];
            foreach ($options as $opt) {
                $opt_out[] = ['option_id' => (int)$opt->id, 'text' => format_string($opt->text)];
            }
            $output[] = [
                'poll_id'   => (int)$poll->id,
                'cmid'      => (int)$poll->cmid,
                'name'      => format_string($poll->name),
                'intro'     => strip_tags($poll->intro ?? ''),
                'answered'  => $answered,
                'timeopen'  => (int)$poll->timeopen,
                'timeclose' => (int)$poll->timeclose,
                'options'   => $opt_out,
            ];
        }

        return ['polls' => $output, 'total_polls' => count($output)];
    }

    public static function get_polls_returns() {
        return new external_single_structure([
            'polls' => new external_multiple_structure(
                new external_single_structure([
                    'poll_id'   => new external_value(PARAM_INT,  'Poll ID'),
                    'cmid'      => new external_value(PARAM_INT,  'Course module ID'),
                    'name'      => new external_value(PARAM_TEXT, 'Poll title'),
                    'intro'     => new external_value(PARAM_TEXT, 'Description'),
                    'answered'  => new external_value(PARAM_BOOL, 'User already answered?'),
                    'timeopen'  => new external_value(PARAM_INT,  'Open time'),
                    'timeclose' => new external_value(PARAM_INT,  'Close time'),
                    'options'   => new external_multiple_structure(
                        new external_single_structure([
                            'option_id' => new external_value(PARAM_INT,  'Option ID'),
                            'text'      => new external_value(PARAM_TEXT, 'Option text'),
                        ])
                    ),
                ])
            ),
            'total_polls' => new external_value(PARAM_INT, 'Total active polls'),
        ]);
    }

    // ----- submit_poll -----
    public static function submit_poll_parameters() {
        return new external_function_parameters([
            'userid'   => new external_value(PARAM_INT, 'User ID'),
            'choiceid' => new external_value(PARAM_INT, 'Poll/Choice ID'),
            'optionid' => new external_value(PARAM_INT, 'Selected option ID'),
        ]);
    }

    public static function submit_poll($userid, $choiceid, $optionid) {
        global $DB;
        $params = self::validate_parameters(self::submit_poll_parameters(),
            ['userid' => $userid, 'choiceid' => $choiceid, 'optionid' => $optionid]);

        if ($DB->record_exists('choice_answers', ['choiceid' => $params['choiceid'], 'userid' => $params['userid']])) {
            return ['success' => false, 'message' => 'Already answered', 'points_earned' => 0,
                    'total_points' => (int)get_total_points($params['userid'])];
        }

        $option = $DB->get_record('choice_options', ['id' => $params['optionid'], 'choiceid' => $params['choiceid']]);
        if (!$option) {
            return ['success' => false, 'message' => 'Invalid option', 'points_earned' => 0,
                    'total_points' => (int)get_total_points($params['userid'])];
        }

        $ans              = new stdClass();
        $ans->choiceid    = $params['choiceid'];
        $ans->userid      = $params['userid'];
        $ans->optionid    = $params['optionid'];
        $ans->timemodified = time();
        $DB->insert_record('choice_answers', $ans);

        // Award 10 pts for poll participation
        self::ensure_user_points($params['userid']);
        add_point_log($params['userid'], 'quiz', 'added', 10);

        return [
            'success'      => true,
            'message'      => 'Poll submitted! You earned 10 points.',
            'points_earned'=> 10,
            'total_points' => (int)get_total_points($params['userid']),
        ];
    }

    public static function submit_poll_returns() {
        return new external_single_structure([
            'success'       => new external_value(PARAM_BOOL, 'Success'),
            'message'       => new external_value(PARAM_TEXT, 'Message'),
            'points_earned' => new external_value(PARAM_INT,  'Points earned'),
            'total_points'  => new external_value(PARAM_INT,  'Updated total points'),
        ]);
    }

    // =========================================================
    // ░░ PRIVATE HELPERS ░░
    // =========================================================

    /** Get course thumbnail URL */
    private static function get_course_image($course) {
        global $CFG;
        $context = context_course::instance($course->id);
        $fs      = get_file_storage();
        $files   = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0);
        foreach ($files as $f) {
            if (!$f->is_directory()) {
                return $CFG->wwwroot . '/pluginfile.php/' . $context->id
                    . '/course/overviewfiles/' . $f->get_filename();
            }
        }
        return $CFG->wwwroot . '/theme/image.php/remui/core/1/course_defaultimage';
    }

    /** Ensure user_points row exists before any add_point_log() call */
    private static function ensure_user_points($userid) {
        global $DB;
        if (!$DB->record_exists('user_points', ['userid' => $userid])) {
            $r              = new stdClass();
            $r->userid      = $userid;
            $r->available_points = 0;
            $r->total_points     = 0;
            $r->userrank         = '-';
            $r->timecreated      = time();
            $DB->insert_record('user_points', $r);
        }
    }

    /** Detect which table name exists in DB */
    private static function detect_table(array $candidates) {
        global $DB;
        foreach ($candidates as $t) {
            if ($DB->get_manager()->table_exists($t)) return $t;
        }
        return null;
    }

    /** Fallback: get ILT activities from course_modules when sessions table unknown */
    private static function get_ilt_from_course_modules($enrolled, $userid, $upcoming) {
        global $DB, $CFG;
        $output = [];
        foreach ($enrolled as $c) {
            $modinfo = get_fast_modinfo($c);
            foreach ($modinfo->get_instances_of('ilt') as $cm) {
                $ilt = $DB->get_record('ilt', ['id' => $cm->instance]);
                if (!$ilt) continue;
                $output[] = [
                    'session_id'   => (int)$cm->instance,
                    'ilt_id'       => (int)$cm->instance,
                    'cmid'         => (int)$cm->id,
                    'name'         => $cm->name,
                    'course_id'    => (int)$c->id,
                    'course_name'  => $c->fullname,
                    'timestart'    => (int)($ilt->timestart ?? 0),
                    'timefinish'   => (int)($ilt->timefinish ?? 0),
                    'instructor'   => '',
                    'location'     => '',
                    'capacity'     => (int)($ilt->capacity ?? 0),
                    'booked'       => 0,
                    'status'       => 'open',
                    'mode'         => 'Online',
                    'description'  => strip_tags($ilt->intro ?? ''),
                    'joinurl'      => '',
                    'is_signed_up' => false,
                    'bookingopen'  => true,
                    'view_url'     => $CFG->wwwroot . '/mod/ilt/view.php?id=' . $cm->id,
                ];
            }
        }
        return ['sessions' => $output, 'total' => count($output)];
    }

    // user dashboard stat card 
    public static function get_user_stats_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
        'filter' => new external_value(PARAM_TEXT,
            'Filter: all | today | thisweek | thismonth',
            VALUE_DEFAULT, 'all'),
    ]);
}
 
public static function get_user_stats($userid, $filter = 'all') {
    global $DB, $CFG;
 
    require_once($CFG->libdir . '/completionlib.php');
 
    $params = self::validate_parameters(
        self::get_user_stats_parameters(),
        ['userid' => $userid, 'filter' => $filter]
    );
 
    // ---- Get user ----
    $user = $DB->get_record('user',
        ['id' => $params['userid'], 'deleted' => 0], '*', MUST_EXIST);
 
    // ---- Get enrolled courses ----
    $courses = enrol_get_users_courses(
        $params['userid'], true,
        'id, fullname, startdate, enddate'
    );
 
    // ---- Apply date filter (same logic as core_renderer.php) ----
    $now = time();
    if ($params['filter'] !== 'all') {
        $filtered = [];
        foreach ($courses as $course) {
            $include = false;
            switch ($params['filter']) {
                case 'today':
                    if (!empty($course->startdate) &&
                        date('Y-m-d', $course->startdate) == date('Y-m-d', $now)) {
                        $include = true;
                    }
                    break;
                case 'thisweek':
                    $weekstart = strtotime('monday this week', $now);
                    $weekend   = strtotime('sunday this week', $now);
                    if (!empty($course->startdate) &&
                        $course->startdate >= $weekstart &&
                        $course->startdate <= $weekend) {
                        $include = true;
                    }
                    break;
                case 'thismonth':
                    if (!empty($course->startdate) &&
                        date('Y-m', $course->startdate) == date('Y-m', $now)) {
                        $include = true;
                    }
                    break;
            }
            if ($include) $filtered[] = $course;
        }
        $courses = $filtered;
    }
 
    // ---- Counters ----
    $totalcourses        = count($courses);
    $completedcourses    = 0;
    $totalactivities     = 0;
    $completedactivities = 0;
    $totalcertificates   = 0;
    $earnedcertificates  = 0;
    $notstarted          = 0;
    $inprogress          = 0;
 
    // ---- Per-course stats (exact logic from core_renderer.php) ----
    foreach ($courses as $course) {
        $completion = new \completion_info($course);
        $progress   = \core_completion\progress::get_course_progress_percentage(
            $course, $params['userid']);
 
        // Course status
        if ($progress == 100)       $completedcourses++;
        elseif ($progress > 0)      $inprogress++;
        else                        $notstarted++;
 
        // Activities — exact copy of renderer logic
        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->get_cms() as $cm) {
            // Skip section 0 (intro)
            if (isset($cm->sectionnum) && $cm->sectionnum == 0) continue;
            // Skip news forum
            if ($cm->modname === 'forum' &&
                isset($course->newsforum) &&
                $cm->instance == $course->newsforum) continue;
            // Skip deleted
            if (!empty($cm->deletioninprogress)) continue;
            if (method_exists($cm, 'is_deleted') && $cm->is_deleted()) continue;
            // Skip invisible
            if (!$cm->is_visible_on_course_page()) continue;
 
            $totalactivities++;
 
            if (!empty($cm->completion) && $cm->completion > 0) {
                $modcompletion = $completion->get_data($cm, false, $params['userid']);
                if (!empty($modcompletion->completionstate) &&
                    $modcompletion->completionstate != COMPLETION_INCOMPLETE) {
                    $completedactivities++;
                }
            }
        }
 
        // Custom Certificates — exact copy of renderer logic
        if ($DB->get_manager()->table_exists('customcert')) {
            $sql = "SELECT cc.id
                    FROM {customcert} cc
                    JOIN {course_modules} cm ON cm.instance = cc.id
                    JOIN {modules} m ON m.id = cm.module
                    WHERE cc.course = :courseid
                      AND m.name = 'customcert'
                      AND cm.deletioninprogress = 0
                      AND cm.visible = 1";
            $customcerts = $DB->get_records_sql($sql, ['courseid' => $course->id]);
            $totalcertificates += count($customcerts);
            foreach ($customcerts as $cert) {
                if ($DB->record_exists('customcert_issues', [
                    'customcertid' => $cert->id,
                    'userid'       => $params['userid'],
                ])) {
                    $earnedcertificates++;
                }
            }
        }
 
        // IOMAD Certificates — exact copy of renderer logic
        if ($DB->get_manager()->table_exists('iomadcertificate')) {
            $sql = "SELECT ic.id
                    FROM {iomadcertificate} ic
                    JOIN {course_modules} cm ON cm.instance = ic.id
                    JOIN {modules} m ON m.id = cm.module
                    WHERE ic.course = :courseid
                      AND m.name = 'iomadcertificate'
                      AND cm.deletioninprogress = 0
                      AND cm.visible = 1";
            $iomadcerts = $DB->get_records_sql($sql, ['courseid' => $course->id]);
            $totalcertificates += count($iomadcerts);
            foreach ($iomadcerts as $cert) {
                if ($DB->record_exists('iomadcertificate_issues', [
                    'iomadcertificateid' => $cert->id,
                    'userid'             => $params['userid'],
                ])) {
                    $earnedcertificates++;
                }
            }
        }
    }
 
    // ---- Build stat cards (same structure as renderer) ----
    $stats = [
        [
            'label'       => 'Courses Completed',
            'earned'      => $completedcourses,
            'total'       => $totalcourses,
            'value'       => "$completedcourses / $totalcourses",
            'percentage'  => $totalcourses
                             ? (int)round($completedcourses / $totalcourses * 100)
                             : 0,
            'icon'        => 'bi bi-check-circle',
            'color'       => 'rgb(30, 58, 138)',
        ],
        [
            'label'       => 'Activities Completed',
            'earned'      => $completedactivities,
            'total'       => $totalactivities,
            'value'       => "$completedactivities / $totalactivities",
            'percentage'  => $totalactivities
                             ? (int)round($completedactivities / $totalactivities * 100)
                             : 0,
            'icon'        => 'bi bi-activity',
            'color'       => 'rgb(132, 204, 22)',
        ],
        [
            'label'       => 'Certificates Earned',
            'earned'      => $earnedcertificates,
            'total'       => $totalcertificates,
            'value'       => "$earnedcertificates / $totalcertificates",
            'percentage'  => $totalcertificates
                             ? (int)round($earnedcertificates / $totalcertificates * 100)
                             : 0,
            'icon'        => 'bi bi-award',
            'color'       => 'rgb(239, 68, 68)',
        ],
    ];
 
    // ---- Course status breakdown ----
    $course_status = [
        'total'      => $totalcourses,
        'notstarted' => $notstarted,
        'inprogress' => $inprogress,
        'completed'  => $completedcourses,
    ];
 
    return [
        'userid'        => (int)$params['userid'],
        'fullname'      => fullname($user),
        'filter'        => $params['filter'],
        'stats'         => $stats,
        'course_status' => $course_status,
    ];
}
 
public static function get_user_stats_returns() {
    return new external_single_structure([
        'userid'   => new external_value(PARAM_INT,  'User ID'),
        'fullname' => new external_value(PARAM_TEXT, 'User full name'),
        'filter'   => new external_value(PARAM_TEXT, 'Filter applied'),
 
        // ---- The 3 stat cards ----
        'stats' => new external_multiple_structure(
            new external_single_structure([
                'label'      => new external_value(PARAM_TEXT, 'Card label'),
                'earned'     => new external_value(PARAM_INT,  'Earned/completed count'),
                'total'      => new external_value(PARAM_INT,  'Total count'),
                'value'      => new external_value(PARAM_TEXT, 'Display value e.g. "3 / 6"'),
                'percentage' => new external_value(PARAM_INT,  'Completion percentage 0-100'),
                'icon'       => new external_value(PARAM_TEXT, 'Bootstrap icon class'),
                'color'      => new external_value(PARAM_TEXT, 'CSS color value'),
            ])
        ),
 
        // ---- Course status breakdown ----
        'course_status' => new external_single_structure([
            'total'      => new external_value(PARAM_INT, 'Total enrolled courses'),
            'notstarted' => new external_value(PARAM_INT, 'Not started (progress = 0)'),
            'inprogress' => new external_value(PARAM_INT, 'In progress (0 < progress < 100)'),
            'completed'  => new external_value(PARAM_INT, 'Completed (progress = 100)'),
        ]),
    ]);
}

// ----- get_course_status_counts -----
public static function get_course_status_counts_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_course_status_counts($userid) {
    global $DB, $CFG;

    require_once($CFG->libdir . '/completionlib.php');

    $params = self::validate_parameters(
        self::get_course_status_counts_parameters(),
        ['userid' => $userid]
    );

    // Validate user exists
    $DB->get_record('user', ['id' => $params['userid'], 'deleted' => 0], '*', MUST_EXIST);

    // Get all enrolled courses
    $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname, shortname, enablecompletion');

    $notstarted  = 0;
    $inprogress  = 0;
    $completed   = 0;

    foreach ($courses as $course) {
        // Skip courses with completion disabled
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            // No completion tracking — count as not started
            $notstarted++;
            continue;
        }

        $progress = \core_completion\progress::get_course_progress_percentage(
            $course, $params['userid']
        );

        // Normalize null to 0
        $progress = $progress ?? 0;

        if ($progress >= 100) {
            $completed++;
        } elseif ($progress > 0) {
            $inprogress++;
        } else {
            $notstarted++;
        }
    }

    $total = count($courses);

    return [
        'userid'      => (int)$params['userid'],
        'total'       => $total,
        'notstarted'  => $notstarted,
        'inprogress'  => $inprogress,
        'completed'   => $completed,
        'notstarted_percent' => $total ? (int)round($notstarted / $total * 100) : 0,
        'inprogress_percent' => $total ? (int)round($inprogress / $total * 100) : 0,
        'completed_percent'  => $total ? (int)round($completed  / $total * 100) : 0,
    ];
}

public static function get_course_status_counts_returns() {
    return new external_single_structure([
        'userid'             => new external_value(PARAM_INT, 'User ID'),
        'total'              => new external_value(PARAM_INT, 'Total enrolled courses'),
        'notstarted'         => new external_value(PARAM_INT, 'Not started (progress = 0)'),
        'inprogress'         => new external_value(PARAM_INT, 'In progress (0 < progress < 100)'),
        'completed'          => new external_value(PARAM_INT, 'Completed (progress = 100)'),
        'notstarted_percent' => new external_value(PARAM_INT, 'Not started %'),
        'inprogress_percent' => new external_value(PARAM_INT, 'In progress %'),
        'completed_percent'  => new external_value(PARAM_INT, 'Completed %'),
    ]);
}

// =========================================================
// ░░ CUSTOM CERTIFICATE ░░
// =========================================================

// ----- get_my_certificates -----
public static function get_my_certificates_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_my_certificates($userid) {
    global $DB, $CFG;

    require_once($CFG->libdir . '/completionlib.php');

    $params = self::validate_parameters(
        self::get_my_certificates_parameters(),
        ['userid' => $userid]
    );

    $user = $DB->get_record('user',
        ['id' => $params['userid'], 'deleted' => 0], '*', MUST_EXIST);

    // The wstoken used to authenticate THIS REST call is available as a request
    // param (the REST server reads it before dispatching to this function).
    // We reuse it to build download URLs that work without a browser session,
    // matching how the official Moodle Mobile app pulls certificate PDFs.
    $wstoken = optional_param('wstoken', '', PARAM_ALPHANUM);

    $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname, shortname');

    $output = [];

    foreach ($courses as $course) {

        // ---- Custom Certificate ----
        if ($DB->get_manager()->table_exists('customcert')) {
            $sql = "SELECT cc.id, cc.name, cc.course,
                           cm.id AS cmid,
                           ci.timecreated AS issuedate,
                           ci.code
                    FROM {customcert} cc
                    JOIN {course_modules} cm ON cm.instance = cc.id
                    JOIN {modules} m         ON m.id = cm.module AND m.name = 'customcert'
                    LEFT JOIN {customcert_issues} ci
                           ON ci.customcertid = cc.id AND ci.userid = :userid
                    WHERE cc.course = :courseid
                      AND cm.visible = 1
                      AND cm.deletioninprogress = 0";

            $certs = $DB->get_records_sql($sql, [
                'userid'   => $params['userid'],
                'courseid' => $course->id,
            ]);

            foreach ($certs as $cert) {
                $earned = !empty($cert->issuedate);

                // Build download URL only if earned.
                // Uses mod/customcert/mobile/pluginfile.php (the endpoint the
                // plugin ships for mobile-app / token-based access) instead of
                // view.php?downloadown=1, which requires a logged-in session
                // and will just bounce a token-only Flutter client to login.
                $download_url = '';
                if ($earned) {
                    $download_url = $CFG->wwwroot
                        . '/mod/customcert/mobile/pluginfile.php'
                        . '?certificateid=' . $cert->id
                        . '&userid=' . $params['userid']
                        . '&token=' . $wstoken;
                }

                $output[] = [
                    'cert_id'       => (int)$cert->id,
                    'cmid'          => (int)$cert->cmid,
                    'name'          => format_string($cert->name),
                    'course_id'     => (int)$course->id,
                    'course_name'   => $course->fullname,
                    'type'          => 'customcert',
                    'earned'        => $earned,
                    'issuedate'     => (int)($cert->issuedate ?? 0),
                    'code'          => (string)($cert->code ?? ''),
                    'download_url'  => $download_url,
                    'view_url'      => $CFG->wwwroot . '/mod/customcert/view.php?id=' . $cert->cmid,
                ];
            }
        }

       // ---- IOMAD Certificate ----
        if ($DB->get_manager()->table_exists('iomadcertificate')) {
            $sql = "SELECT ic.id, ic.name, ic.course,
                           cm.id AS cmid,
                           ii.id AS issueid,
                           ii.timecreated AS issuedate,
                           ii.code
                    FROM {iomadcertificate} ic
                    JOIN {course_modules} cm ON cm.instance = ic.id
                    JOIN {modules} m         ON m.id = cm.module AND m.name = 'iomadcertificate'
                    LEFT JOIN {iomadcertificate_issues} ii
                           ON ii.iomadcertificateid = ic.id AND ii.userid = :userid
                    WHERE ic.course = :courseid
                      AND cm.visible = 1
                      AND cm.deletioninprogress = 0";

            $certs = $DB->get_records_sql($sql, [
                'userid'   => $params['userid'],
                'courseid' => $course->id,
            ]);

            foreach ($certs as $cert) {
                $earned = !empty($cert->issuedate);

                // Uses the 'onthefly' file area handled by
                // iomadcertificate_pluginfile() in mod/iomadcertificate/lib.php,
                // routed through Moodle's generic token-based file endpoint —
                // no custom mobile/pluginfile.php needed, since the module's
                // own _pluginfile() callback is invoked automatically and
                // regenerates the PDF fresh on each request.
                $download_url = '';
                if ($earned) {
                    $modcontext = context_module::instance($cert->cmid);

                  $download_url = '';
if ($earned) {
    $download_url = $CFG->wwwroot
        . '/mod/iomadcertificate/mobile/pluginfile.php'
        . '?issueid=' . $cert->issueid
        . '&token=' . $wstoken;
}
                }

                $output[] = [
                    'cert_id'      => (int)$cert->id,
                    'cmid'         => (int)$cert->cmid,
                    'name'         => format_string($cert->name),
                    'course_id'    => (int)$course->id,
                    'course_name'  => $course->fullname,
                    'type'         => 'iomadcertificate',
                    'earned'       => $earned,
                    'issuedate'    => (int)($cert->issuedate ?? 0),
                    'code'         => (string)($cert->code ?? ''),
                    'download_url' => $download_url,
                    'view_url'     => $CFG->wwwroot . '/mod/iomadcertificate/view.php?id=' . $cert->cmid,
                ];
            }
        }
    }

    // Split into earned and pending
    $earned  = array_values(array_filter($output, fn($c) => $c['earned']));
    $pending = array_values(array_filter($output, fn($c) => !$c['earned']));

    return [
        'userid'        => (int)$params['userid'],
        'fullname'      => fullname($user),
        'certificates'  => $output,
        'earned'        => $earned,
        'pending'       => $pending,
        'total'         => count($output),
        'total_earned'  => count($earned),
        'total_pending' => count($pending),
    ];
}

public static function get_my_certificates_returns() {
    $cert_structure = new external_single_structure([
        'cert_id'      => new external_value(PARAM_INT,  'Certificate ID'),
        'cmid'         => new external_value(PARAM_INT,  'Course module ID'),
        'name'         => new external_value(PARAM_TEXT, 'Certificate name'),
        'course_id'    => new external_value(PARAM_INT,  'Course ID'),
        'course_name'  => new external_value(PARAM_TEXT, 'Course name'),
        'type'         => new external_value(PARAM_TEXT, 'customcert or iomadcertificate'),
        'earned'       => new external_value(PARAM_BOOL, 'User earned this cert?'),
        'issuedate'    => new external_value(PARAM_INT,  'Issue unix timestamp (0 if not earned)'),
        'code'         => new external_value(PARAM_TEXT, 'Certificate unique code'),
        'download_url' => new external_value(PARAM_RAW,  'PDF download URL (empty if not earned)'),
        'view_url'     => new external_value(PARAM_RAW,  'View page URL'),
    ]);

    return new external_single_structure([
        'userid'        => new external_value(PARAM_INT,  'User ID'),
        'fullname'      => new external_value(PARAM_TEXT, 'User full name'),
        'certificates'  => new external_multiple_structure($cert_structure, 'All certificates'),
        'earned'        => new external_multiple_structure($cert_structure, 'Earned certificates only'),
        'pending'       => new external_multiple_structure($cert_structure, 'Pending certificates only'),
        'total'         => new external_value(PARAM_INT,  'Total certificates'),
        'total_earned'  => new external_value(PARAM_INT,  'Total earned'),
        'total_pending' => new external_value(PARAM_INT,  'Total pending'),
    ]);
}
// =========================================================
// ░░ PDFJSFOLDER — PDF Folder Viewer ░░
// =========================================================

// ----- get_pdfjsfolder_list -----
public static function get_pdfjsfolder_list_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_pdfjsfolder_list($userid) {
    global $DB, $CFG;

    $params = self::validate_parameters(
        self::get_pdfjsfolder_list_parameters(),
        ['userid' => $userid]
    );

    $user = $DB->get_record('user',
        ['id' => $params['userid'], 'deleted' => 0], '*', MUST_EXIST);

    $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname, shortname');

    $output = [];

    foreach ($courses as $course) {
        $modinfo = get_fast_modinfo($course);

        // Check if pdfjsfolder module exists in this course
        if (!array_key_exists('pdfjsfolder', $modinfo->get_instances())) {
            continue;
        }

        foreach ($modinfo->get_instances_of('pdfjsfolder') as $cm) {
            if (!$cm->uservisible) continue;

            $folder = $DB->get_record('pdfjsfolder', ['id' => $cm->instance]);
            if (!$folder) continue;

            // Get all PDF files in this folder
            $context = context_module::instance($cm->id);
            $fs      = get_file_storage();
            $files   = $fs->get_area_files(
                $context->id,
                'mod_pdfjsfolder',
                'content',
                false,
                'filename ASC',
                false
            );

            $file_list = [];
            foreach ($files as $file) {
                if ($file->is_directory()) continue;

                $file_url = $CFG->wwwroot
                    . '/pluginfile.php/' . $context->id
                    . '/mod_pdfjsfolder/content/'
                    . $file->get_itemid() . '/'
                    . rawurlencode($file->get_filename());

                // Build viewer URL
                $viewer_url = $CFG->wwwroot
                    . '/mod/pdfjsfolder/view.php?id=' . $cm->id;

                $file_list[] = [
                    'file_id'    => (int)$file->get_id(),
                    'filename'   => $file->get_filename(),
                    'filesize'   => (int)$file->get_filesize(),
                    'mimetype'   => (string)$file->get_mimetype(),
                    'file_url'   => $file_url,
                    'viewer_url' => $viewer_url,
                    'timemodified' => (int)$file->get_timemodified(),
                ];
            }

            // Completion status
            $completion      = new \completion_info($course);
            $completiondata  = null;
            $is_completed    = false;
            if ($completion->is_enabled() && $cm->completion) {
                $completiondata = $completion->get_data($cm, false, $params['userid']);
                $is_completed   = ($completiondata->completionstate == COMPLETION_COMPLETE ||
                                   $completiondata->completionstate == COMPLETION_COMPLETE_PASS);
            }

            $output[] = [
                'id'           => (int)$folder->id,
                'cmid'         => (int)$cm->id,
                'name'         => format_string($folder->name),
                'intro'        => strip_tags($folder->intro ?? ''),
                'course_id'    => (int)$course->id,
                'course_name'  => $course->fullname,
                'file_count'   => count($file_list),
                'files'        => $file_list,
                'is_completed' => $is_completed,
                'view_url'     => $CFG->wwwroot . '/mod/pdfjsfolder/view.php?id=' . $cm->id,
                'timemodified' => (int)($folder->timemodified ?? 0),
            ];
        }
    }

    return [
        'userid'   => (int)$params['userid'],
        'folders'  => $output,
        'total'    => count($output),
    ];
}

public static function get_pdfjsfolder_list_returns() {
    return new external_single_structure([
        'userid'  => new external_value(PARAM_INT, 'User ID'),
        'folders' => new external_multiple_structure(
            new external_single_structure([
                'id'           => new external_value(PARAM_INT,  'PDFJSFolder ID'),
                'cmid'         => new external_value(PARAM_INT,  'Course module ID'),
                'name'         => new external_value(PARAM_TEXT, 'Folder name'),
                'intro'        => new external_value(PARAM_TEXT, 'Description'),
                'course_id'    => new external_value(PARAM_INT,  'Course ID'),
                'course_name'  => new external_value(PARAM_TEXT, 'Course name'),
                'file_count'   => new external_value(PARAM_INT,  'Number of PDF files'),
                'files'        => new external_multiple_structure(
                    new external_single_structure([
                        'file_id'      => new external_value(PARAM_INT,  'File ID'),
                        'filename'     => new external_value(PARAM_TEXT, 'File name'),
                        'filesize'     => new external_value(PARAM_INT,  'File size in bytes'),
                        'mimetype'     => new external_value(PARAM_TEXT, 'MIME type'),
                        'file_url'     => new external_value(PARAM_RAW,  'Direct file URL'),
                        'viewer_url'   => new external_value(PARAM_RAW,  'PDF viewer URL'),
                        'timemodified' => new external_value(PARAM_INT,  'Last modified timestamp'),
                    ])
                ),
                'is_completed' => new external_value(PARAM_BOOL, 'User completed this activity?'),
                'view_url'     => new external_value(PARAM_RAW,  'Module view URL'),
                'timemodified' => new external_value(PARAM_INT,  'Last modified'),
            ])
        ),
        'total' => new external_value(PARAM_INT, 'Total folders'),
    ]);
}

// ----- get_pdfjsfolder_detail -----
public static function get_pdfjsfolder_detail_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
        'cmid'   => new external_value(PARAM_INT, 'Course module ID'),
    ]);
}

public static function get_pdfjsfolder_detail($userid, $cmid) {
    global $DB, $CFG;

    $params = self::validate_parameters(
        self::get_pdfjsfolder_detail_parameters(),
        ['userid' => $userid, 'cmid' => $cmid]
    );

    // Get course module
    $cm     = get_coursemodule_from_id('pdfjsfolder', $params['cmid'], 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $folder = $DB->get_record('pdfjsfolder', ['id' => $cm->instance], '*', MUST_EXIST);

    // Get files
    $context = context_module::instance($cm->id);
    $fs      = get_file_storage();
    $files   = $fs->get_area_files(
        $context->id,
        'mod_pdfjsfolder',
        'content',
        false,
        'filename ASC',
        false
    );

    $file_list = [];
    foreach ($files as $file) {
        if ($file->is_directory()) continue;

        $file_url = $CFG->wwwroot
            . '/pluginfile.php/' . $context->id
            . '/mod_pdfjsfolder/content/'
            . $file->get_itemid() . '/'
            . rawurlencode($file->get_filename());

        $file_list[] = [
            'file_id'      => (int)$file->get_id(),
            'filename'     => $file->get_filename(),
            'filesize'     => (int)$file->get_filesize(),
            'mimetype'     => (string)$file->get_mimetype(),
            'file_url'     => $file_url,
            'viewer_url'   => $CFG->wwwroot . '/mod/pdfjsfolder/view.php?id=' . $cm->id,
            'timemodified' => (int)$file->get_timemodified(),
        ];
    }

    // Mark as viewed
    $modinfo = get_fast_modinfo($course);
    $cm_info = $modinfo->get_cm($cm->id);
    \core_completion\cm_completion_details::get_instance(
        $cm_info, $params['userid']
    );

    // Completion
    $completion     = new \completion_info($course);
    $is_completed   = false;
    if ($completion->is_enabled() && $cm->completion) {
        $data         = $completion->get_data($cm_info, false, $params['userid']);
        $is_completed = ($data->completionstate == COMPLETION_COMPLETE ||
                         $data->completionstate == COMPLETION_COMPLETE_PASS);
    }

    return [
        'id'           => (int)$folder->id,
        'cmid'         => (int)$cm->id,
        'name'         => format_string($folder->name),
        'intro'        => strip_tags($folder->intro ?? ''),
        'course_id'    => (int)$course->id,
        'course_name'  => $course->fullname,
        'file_count'   => count($file_list),
        'files'        => $file_list,
        'is_completed' => $is_completed,
        'view_url'     => $CFG->wwwroot . '/mod/pdfjsfolder/view.php?id=' . $cm->id,
        'timemodified' => (int)($folder->timemodified ?? 0),
    ];
}

public static function get_pdfjsfolder_detail_returns() {
    return new external_single_structure([
        'id'           => new external_value(PARAM_INT,  'PDFJSFolder ID'),
        'cmid'         => new external_value(PARAM_INT,  'Course module ID'),
        'name'         => new external_value(PARAM_TEXT, 'Folder name'),
        'intro'        => new external_value(PARAM_TEXT, 'Description'),
        'course_id'    => new external_value(PARAM_INT,  'Course ID'),
        'course_name'  => new external_value(PARAM_TEXT, 'Course name'),
        'file_count'   => new external_value(PARAM_INT,  'Number of files'),
        'files'        => new external_multiple_structure(
            new external_single_structure([
                'file_id'      => new external_value(PARAM_INT,  'File ID'),
                'filename'     => new external_value(PARAM_TEXT, 'File name'),
                'filesize'     => new external_value(PARAM_INT,  'File size in bytes'),
                'mimetype'     => new external_value(PARAM_TEXT, 'MIME type'),
                'file_url'     => new external_value(PARAM_RAW,  'Direct file URL'),
                'viewer_url'   => new external_value(PARAM_RAW,  'PDF viewer URL'),
                'timemodified' => new external_value(PARAM_INT,  'Last modified'),
            ])
        ),
        'is_completed' => new external_value(PARAM_BOOL, 'Completed?'),
        'view_url'     => new external_value(PARAM_RAW,  'View URL'),
        'timemodified' => new external_value(PARAM_INT,  'Last modified'),
    ]);
}

// ----- mark_pdfjsfolder_complete -----
public static function mark_pdfjsfolder_complete_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
        'cmid'   => new external_value(PARAM_INT, 'Course module ID'),
    ]);
}

public static function mark_pdfjsfolder_complete($userid, $cmid) {
    global $DB;

    $params = self::validate_parameters(
        self::mark_pdfjsfolder_complete_parameters(),
        ['userid' => $userid, 'cmid' => $cmid]
    );

    $cm     = get_coursemodule_from_id('pdfjsfolder', $params['cmid'], 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

    $completion = new \completion_info($course);

    if (!$completion->is_enabled()) {
        return ['success' => false, 'message' => 'Completion not enabled for this course'];
    }

    $modinfo = get_fast_modinfo($course);
    $cm_info = $modinfo->get_cm($cm->id);

    $completion->update_state($cm_info, COMPLETION_COMPLETE, $params['userid']);

    return [
        'success' => true,
        'message' => 'Marked as complete',
        'cmid'    => (int)$params['cmid'],
        'userid'  => (int)$params['userid'],
    ];
}

public static function mark_pdfjsfolder_complete_returns() {
    return new external_single_structure([
        'success' => new external_value(PARAM_BOOL, 'Success'),
        'message' => new external_value(PARAM_TEXT, 'Message'),
        'cmid'    => new external_value(PARAM_INT,  'Course module ID'),
        'userid'  => new external_value(PARAM_INT,  'User ID'),
    ]);
}
// =========================================================
// ░░ PAGE ACTIVITY ░░
// =========================================================

public static function get_page_activities_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_page_activities($userid) {
    global $DB, $CFG;
    $params  = self::validate_parameters(self::get_page_activities_parameters(), ['userid' => $userid]);
    $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname');
    $output  = [];

    foreach ($courses as $course) {
        $modinfo = get_fast_modinfo($course);
        if (!array_key_exists('page', $modinfo->get_instances())) continue;

        foreach ($modinfo->get_instances_of('page') as $cm) {
            if (!$cm->uservisible) continue;
            $page = $DB->get_record('page', ['id' => $cm->instance]);
            if (!$page) continue;

            // Completion
            $completion    = new \completion_info($course);
            $is_completed  = false;
            if ($completion->is_enabled() && $cm->completion) {
                $data         = $completion->get_data($cm, false, $params['userid']);
                $is_completed = ($data->completionstate == COMPLETION_COMPLETE ||
                                 $data->completionstate == COMPLETION_COMPLETE_PASS);
            }

            $output[] = [
                'id'           => (int)$page->id,
                'cmid'         => (int)$cm->id,
                'name'         => format_string($page->name),
                'intro'        => strip_tags($page->intro ?? ''),
                'content'      => format_text($page->content ?? ''),
                'course_id'    => (int)$course->id,
                'course_name'  => $course->fullname,
                'is_completed' => $is_completed,
                'view_url'     => $CFG->wwwroot . '/mod/page/view.php?id=' . $cm->id,
                'timemodified' => (int)($page->timemodified ?? 0),
            ];
        }
    }

    return ['userid' => (int)$params['userid'], 'pages' => $output, 'total' => count($output)];
}

public static function get_page_activities_returns() {
    return new external_single_structure([
        'userid' => new external_value(PARAM_INT, 'User ID'),
        'pages'  => new external_multiple_structure(
            new external_single_structure([
                'id'           => new external_value(PARAM_INT,  'Page ID'),
                'cmid'         => new external_value(PARAM_INT,  'Course module ID'),
                'name'         => new external_value(PARAM_TEXT, 'Page title'),
                'intro'        => new external_value(PARAM_TEXT, 'Intro/description'),
                'content'      => new external_value(PARAM_RAW,  'Full HTML content'),
                'course_id'    => new external_value(PARAM_INT,  'Course ID'),
                'course_name'  => new external_value(PARAM_TEXT, 'Course name'),
                'is_completed' => new external_value(PARAM_BOOL, 'Completed?'),
                'view_url'     => new external_value(PARAM_RAW,  'View URL'),
                'timemodified' => new external_value(PARAM_INT,  'Last modified'),
            ])
        ),
        'total' => new external_value(PARAM_INT, 'Total pages'),
    ]);
}

// =========================================================
// ░░ URL ACTIVITY ░░
// =========================================================

public static function get_url_activities_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_url_activities($userid) {
    global $DB, $CFG;
    $params  = self::validate_parameters(self::get_url_activities_parameters(), ['userid' => $userid]);
    $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname');
    $output  = [];

    foreach ($courses as $course) {
        $modinfo = get_fast_modinfo($course);
        if (!array_key_exists('url', $modinfo->get_instances())) continue;

        foreach ($modinfo->get_instances_of('url') as $cm) {
            if (!$cm->uservisible) continue;
            $url = $DB->get_record('url', ['id' => $cm->instance]);
            if (!$url) continue;

            $completion   = new \completion_info($course);
            $is_completed = false;
            if ($completion->is_enabled() && $cm->completion) {
                $data         = $completion->get_data($cm, false, $params['userid']);
                $is_completed = ($data->completionstate == COMPLETION_COMPLETE ||
                                 $data->completionstate == COMPLETION_COMPLETE_PASS);
            }

            $output[] = [
                'id'           => (int)$url->id,
                'cmid'         => (int)$cm->id,
                'name'         => format_string($url->name),
                'intro'        => strip_tags($url->intro ?? ''),
                'externalurl'  => (string)$url->externalurl,
                'course_id'    => (int)$course->id,
                'course_name'  => $course->fullname,
                'is_completed' => $is_completed,
                'view_url'     => $CFG->wwwroot . '/mod/url/view.php?id=' . $cm->id,
                'timemodified' => (int)($url->timemodified ?? 0),
            ];
        }
    }

    return ['userid' => (int)$params['userid'], 'urls' => $output, 'total' => count($output)];
}

public static function get_url_activities_returns() {
    return new external_single_structure([
        'userid' => new external_value(PARAM_INT, 'User ID'),
        'urls'   => new external_multiple_structure(
            new external_single_structure([
                'id'           => new external_value(PARAM_INT,  'URL ID'),
                'cmid'         => new external_value(PARAM_INT,  'Course module ID'),
                'name'         => new external_value(PARAM_TEXT, 'Name'),
                'intro'        => new external_value(PARAM_TEXT, 'Description'),
                'externalurl'  => new external_value(PARAM_RAW,  'The actual URL'),
                'course_id'    => new external_value(PARAM_INT,  'Course ID'),
                'course_name'  => new external_value(PARAM_TEXT, 'Course name'),
                'is_completed' => new external_value(PARAM_BOOL, 'Completed?'),
                'view_url'     => new external_value(PARAM_RAW,  'Moodle view URL'),
                'timemodified' => new external_value(PARAM_INT,  'Last modified'),
            ])
        ),
        'total' => new external_value(PARAM_INT, 'Total URLs'),
    ]);
}

// =========================================================
// ░░ ZOOM MEETING ░░
// =========================================================

public static function get_zoom_meetings_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_zoom_meetings($userid) {
    global $DB, $CFG;
    $params  = self::validate_parameters(self::get_zoom_meetings_parameters(), ['userid' => $userid]);
    $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname');
    $output  = [];

    foreach ($courses as $course) {
        $modinfo = get_fast_modinfo($course);
        if (!array_key_exists('zoom', $modinfo->get_instances())) continue;

        foreach ($modinfo->get_instances_of('zoom') as $cm) {
            if (!$cm->uservisible) continue;
            $zoom = $DB->get_record('zoom', ['id' => $cm->instance]);
            if (!$zoom) continue;

            $completion   = new \completion_info($course);
            $is_completed = false;
            if ($completion->is_enabled() && $cm->completion) {
                $data         = $completion->get_data($cm, false, $params['userid']);
                $is_completed = ($data->completionstate == COMPLETION_COMPLETE ||
                                 $data->completionstate == COMPLETION_COMPLETE_PASS);
            }

            $now        = time();
            $status     = 'upcoming';
            if ($zoom->start_time <= $now && ($zoom->start_time + ($zoom->duration * 60)) >= $now) {
                $status = 'live';
            } elseif (($zoom->start_time + ($zoom->duration * 60)) < $now) {
                $status = 'ended';
            }

            $output[] = [
                'id'            => (int)$zoom->id,
                'cmid'          => (int)$cm->id,
                'name'          => format_string($zoom->name),
                'intro'         => strip_tags($zoom->intro ?? ''),
                'course_id'     => (int)$course->id,
                'course_name'   => $course->fullname,
                'meeting_id'    => (string)($zoom->meeting_id ?? ''),
                'join_url'      => (string)($zoom->join_url ?? ''),
                'start_time'    => (int)($zoom->start_time ?? 0),
                'duration'      => (int)($zoom->duration ?? 0),
                'status'        => $status,
                'meeting_type'  => (int)($zoom->type ?? 2),
                'is_webinar'    => (bool)($zoom->webinar ?? false),
                'password'      => (string)($zoom->password ?? ''),
                'is_completed'  => $is_completed,
                'view_url'      => $CFG->wwwroot . '/mod/zoom/view.php?id=' . $cm->id,
                'timemodified'  => (int)($zoom->timemodified ?? 0),
            ];
        }
    }

    return ['userid' => (int)$params['userid'], 'meetings' => $output, 'total' => count($output)];
}

public static function get_zoom_meetings_returns() {
    return new external_single_structure([
        'userid'   => new external_value(PARAM_INT, 'User ID'),
        'meetings' => new external_multiple_structure(
            new external_single_structure([
                'id'           => new external_value(PARAM_INT,  'Zoom activity ID'),
                'cmid'         => new external_value(PARAM_INT,  'Course module ID'),
                'name'         => new external_value(PARAM_TEXT, 'Meeting name'),
                'intro'        => new external_value(PARAM_TEXT, 'Description'),
                'course_id'    => new external_value(PARAM_INT,  'Course ID'),
                'course_name'  => new external_value(PARAM_TEXT, 'Course name'),
                'meeting_id'   => new external_value(PARAM_TEXT, 'Zoom meeting ID'),
                'join_url'     => new external_value(PARAM_RAW,  'Join URL'),
                'start_time'   => new external_value(PARAM_INT,  'Start unix timestamp'),
                'duration'     => new external_value(PARAM_INT,  'Duration in minutes'),
                'status'       => new external_value(PARAM_TEXT, 'upcoming/live/ended'),
                'meeting_type' => new external_value(PARAM_INT,  '1=instant 2=scheduled 3=recurring'),
                'is_webinar'   => new external_value(PARAM_BOOL, 'Is webinar?'),
                'password'     => new external_value(PARAM_TEXT, 'Meeting password'),
                'is_completed' => new external_value(PARAM_BOOL, 'Completed?'),
                'view_url'     => new external_value(PARAM_RAW,  'Moodle view URL'),
                'timemodified' => new external_value(PARAM_INT,  'Last modified'),
            ])
        ),
        'total' => new external_value(PARAM_INT, 'Total meetings'),
    ]);
}

// =========================================================
// ░░ GOOGLE MEET ░░
// =========================================================

public static function get_googlemeet_activities_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_googlemeet_activities($userid) {
    global $DB, $CFG;
    $params  = self::validate_parameters(self::get_googlemeet_activities_parameters(), ['userid' => $userid]);
    $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname');
    $output  = [];

    // Detect Google Meet table name
    $meet_table = self::detect_table(['googlemeet', 'google_meet', 'mod_googlemeet']);
    $mod_name   = $meet_table ?? 'googlemeet';

    foreach ($courses as $course) {
        $modinfo = get_fast_modinfo($course);
        if (!array_key_exists($mod_name, $modinfo->get_instances())) continue;

        foreach ($modinfo->get_instances_of($mod_name) as $cm) {
            if (!$cm->uservisible) continue;
            $meet = $meet_table ? $DB->get_record($meet_table, ['id' => $cm->instance]) : null;
            if (!$meet) continue;

            $completion   = new \completion_info($course);
            $is_completed = false;
            if ($completion->is_enabled() && $cm->completion) {
                $data         = $completion->get_data($cm, false, $params['userid']);
                $is_completed = ($data->completionstate == COMPLETION_COMPLETE ||
                                 $data->completionstate == COMPLETION_COMPLETE_PASS);
            }

            $now    = time();
            $status = 'upcoming';
            if (!empty($meet->started) && $meet->started) {
                $status = 'live';
            } elseif (!empty($meet->starttime) && $meet->starttime < $now) {
                $status = 'ended';
            }

            $output[] = [
                'id'           => (int)$meet->id,
                'cmid'         => (int)$cm->id,
                'name'         => format_string($meet->name),
                'intro'        => strip_tags($meet->intro ?? ''),
                'course_id'    => (int)$course->id,
                'course_name'  => $course->fullname,
                'meeting_url'  => (string)($meet->url ?? $meet->meetingurl ?? $meet->joinurl ?? ''),
                'start_time'   => (int)($meet->starttime ?? $meet->timestart ?? 0),
                'status'       => $status,
                'is_completed' => $is_completed,
                'view_url'     => $CFG->wwwroot . '/mod/' . $mod_name . '/view.php?id=' . $cm->id,
                'timemodified' => (int)($meet->timemodified ?? 0),
            ];
        }
    }

    return ['userid' => (int)$params['userid'], 'meetings' => $output, 'total' => count($output)];
}

public static function get_googlemeet_activities_returns() {
    return new external_single_structure([
        'userid'   => new external_value(PARAM_INT, 'User ID'),
        'meetings' => new external_multiple_structure(
            new external_single_structure([
                'id'           => new external_value(PARAM_INT,  'Activity ID'),
                'cmid'         => new external_value(PARAM_INT,  'Course module ID'),
                'name'         => new external_value(PARAM_TEXT, 'Meeting name'),
                'intro'        => new external_value(PARAM_TEXT, 'Description'),
                'course_id'    => new external_value(PARAM_INT,  'Course ID'),
                'course_name'  => new external_value(PARAM_TEXT, 'Course name'),
                'meeting_url'  => new external_value(PARAM_RAW,  'Google Meet join URL'),
                'start_time'   => new external_value(PARAM_INT,  'Start timestamp'),
                'status'       => new external_value(PARAM_TEXT, 'upcoming/live/ended'),
                'is_completed' => new external_value(PARAM_BOOL, 'Completed?'),
                'view_url'     => new external_value(PARAM_RAW,  'Moodle view URL'),
                'timemodified' => new external_value(PARAM_INT,  'Last modified'),
            ])
        ),
        'total' => new external_value(PARAM_INT, 'Total Google Meet activities'),
    ]);
}

// =========================================================
// ░░ GO1 CONTENT ░░
// =========================================================

public static function get_go1_content_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_go1_content($userid) {
    global $DB, $CFG;
    $params  = self::validate_parameters(self::get_go1_content_parameters(), ['userid' => $userid]);
    $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname');
    $output  = [];

    // Detect GO1 table
    $go1_table  = self::detect_table(['go1', 'go1content', 'go1_content']);
    $go1_modname = $go1_table ?? 'go1';

    foreach ($courses as $course) {
        $modinfo = get_fast_modinfo($course);
        if (!array_key_exists($go1_modname, $modinfo->get_instances())) continue;

        foreach ($modinfo->get_instances_of($go1_modname) as $cm) {
            if (!$cm->uservisible) continue;
            $go1 = $go1_table ? $DB->get_record($go1_table, ['id' => $cm->instance]) : null;
            if (!$go1) continue;

            $completion   = new \completion_info($course);
            $is_completed = false;
            if ($completion->is_enabled() && $cm->completion) {
                $data         = $completion->get_data($cm, false, $params['userid']);
                $is_completed = ($data->completionstate == COMPLETION_COMPLETE ||
                                 $data->completionstate == COMPLETION_COMPLETE_PASS);
            }

            $output[] = [
                'id'           => (int)$go1->id,
                'cmid'         => (int)$cm->id,
                'name'         => format_string($go1->name),
                'intro'        => strip_tags($go1->intro ?? ''),
                'course_id'    => (int)$course->id,
                'course_name'  => $course->fullname,
                'lo_id'        => (string)($go1->lo_id ?? $go1->loid ?? ''),
                'content_type' => (string)($go1->type ?? $go1->content_type ?? 'course'),
                'launch_url'   => (string)($go1->url ?? $go1->launchurl ?? ''),
                'thumbnail'    => (string)($go1->image ?? $go1->thumbnail ?? ''),
                'duration'     => (int)($go1->duration ?? 0),
                'is_completed' => $is_completed,
                'view_url'     => $CFG->wwwroot . '/mod/' . $go1_modname . '/view.php?id=' . $cm->id,
                'timemodified' => (int)($go1->timemodified ?? 0),
            ];
        }
    }

    return ['userid' => (int)$params['userid'], 'content' => $output, 'total' => count($output)];
}

public static function get_go1_content_returns() {
    return new external_single_structure([
        'userid'  => new external_value(PARAM_INT, 'User ID'),
        'content' => new external_multiple_structure(
            new external_single_structure([
                'id'           => new external_value(PARAM_INT,  'GO1 activity ID'),
                'cmid'         => new external_value(PARAM_INT,  'Course module ID'),
                'name'         => new external_value(PARAM_TEXT, 'Content name'),
                'intro'        => new external_value(PARAM_TEXT, 'Description'),
                'course_id'    => new external_value(PARAM_INT,  'Course ID'),
                'course_name'  => new external_value(PARAM_TEXT, 'Course name'),
                'lo_id'        => new external_value(PARAM_TEXT, 'GO1 learning object ID'),
                'content_type' => new external_value(PARAM_TEXT, 'Type: course/video/document'),
                'launch_url'   => new external_value(PARAM_RAW,  'GO1 launch URL'),
                'thumbnail'    => new external_value(PARAM_RAW,  'Thumbnail image URL'),
                'duration'     => new external_value(PARAM_INT,  'Duration in seconds'),
                'is_completed' => new external_value(PARAM_BOOL, 'Completed?'),
                'view_url'     => new external_value(PARAM_RAW,  'Moodle view URL'),
                'timemodified' => new external_value(PARAM_INT,  'Last modified'),
            ])
        ),
        'total' => new external_value(PARAM_INT, 'Total GO1 content items'),
    ]);
}
public static function get_ilt_activities_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}
public static function get_ilt_activities($userid) {
    global $DB, $CFG;

    $params = self::validate_parameters(
        self::get_ilt_activities_parameters(),
        ['userid' => $userid]
    );

    $courses = enrol_get_users_courses(
        $params['userid'],
        true,
        'id,fullname'
    );

    $output = [];

    foreach ($courses as $course) {

        $modinfo = get_fast_modinfo($course);

        if (!isset($modinfo->instances['ilt'])) {
            continue;
        }

        foreach ($modinfo->instances['ilt'] as $cm) {

            if (!$cm->uservisible) {
                continue;
            }

            $ilt = $DB->get_record('ilt', [
                'id' => $cm->instance
            ]);

            if (!$ilt) {
                continue;
            }

            $completion = new completion_info($course);

            $completed = false;

            if ($completion->is_enabled() && $cm->completion) {

                $data = $completion->get_data(
                    $cm,
                    false,
                    $params['userid']
                );

                $completed =
                    $data->completionstate == COMPLETION_COMPLETE ||
                    $data->completionstate == COMPLETION_COMPLETE_PASS;
            }

            $sessions = $DB->get_records(
                'ilt_sessions',
                ['ilt' => $ilt->id]
            );

            $sessiondata = [];

            foreach ($sessions as $session) {

                $sessiondata[] = [
                    'sessionid' => (int)$session->id,
                    'startdate' => (int)($session->datetimeknown ?? 0),
                    'capacity'  => (int)($session->capacity ?? 0),
                ];
            }

            $output[] = [
                'id'            => (int)$ilt->id,
                'cmid'          => (int)$cm->id,
                'name'          => format_string($ilt->name),
                'intro'         => strip_tags($ilt->intro ?? ''),
                'courseid'      => (int)$course->id,
                'coursename'    => $course->fullname,
                'sessions'      => $sessiondata,
                'is_completed'  => $completed,
                'view_url'      => $CFG->wwwroot .
                                   '/mod/ilt/view.php?id=' .
                                   $cm->id,
            ];
        }
    }

    return [
        'userid' => (int)$params['userid'],
        'activities' => $output,
        'total' => count($output)
    ];
}
public static function get_ilt_activities_returns() {
    return new external_single_structure([
        'userid' => new external_value(PARAM_INT, 'User ID'),

        'activities' => new external_multiple_structure(
            new external_single_structure([

                'id' => new external_value(
                    PARAM_INT,
                    'ILT ID'
                ),

                'cmid' => new external_value(
                    PARAM_INT,
                    'Course Module ID'
                ),

                'name' => new external_value(
                    PARAM_TEXT,
                    'ILT Name'
                ),

                'intro' => new external_value(
                    PARAM_RAW,
                    'Description'
                ),

                'courseid' => new external_value(
                    PARAM_INT,
                    'Course ID'
                ),

                'coursename' => new external_value(
                    PARAM_TEXT,
                    'Course Name'
                ),

                'sessions' => new external_multiple_structure(
                    new external_single_structure([
                        'sessionid' => new external_value(
                            PARAM_INT,
                            'Session ID'
                        ),
                        'startdate' => new external_value(
                            PARAM_INT,
                            'Start Date'
                        ),
                        'capacity' => new external_value(
                            PARAM_INT,
                            'Capacity'
                        ),
                    ])
                ),

                'is_completed' => new external_value(
                    PARAM_BOOL,
                    'Completed'
                ),

                'view_url' => new external_value(
                    PARAM_RAW,
                    'View URL'
                ),
            ])
        ),

        'total' => new external_value(
            PARAM_INT,
            'Total activities'
        ),
    ]);
}
private static function lp_seconds_to_time($inputSeconds) {
    if ($inputSeconds <= 0) return '';
    $sections = [
        'day'    => (int)floor($inputSeconds / 86400),
        'hour'   => (int)floor(($inputSeconds % 86400) / 3600),
        'minute' => (int)floor(($inputSeconds % 3600) / 60),
        'second' => (int)ceil($inputSeconds % 60),
    ];
    $parts = [];
    foreach ($sections as $name => $value) {
        if ($value > 0) {
            $parts[] = $value . ' ' . $name . ($value == 1 ? '' : 's');
        }
    }
    return implode(', ', $parts);
}
 
// ----- HELPER: get course thumbnail URL -----
private static function lp_get_course_image($courseid) {
    global $CFG, $DB, $OUTPUT;
    $context = \context_course::instance($courseid);
    $fs      = get_file_storage();
    $files   = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0);
    foreach ($files as $f) {
        if (!$f->is_directory()) {
            return $CFG->wwwroot . '/webservice/pluginfile.php/' . $context->id
                 . '/course/overviewfiles/' . $f->get_filename().'?token=';
        }
    }
    return $CFG->wwwroot . '/theme/image.php/remui/core/1/course_defaultimage';
}
 
// =========================================================
// 1. GET LEARNING PATHS LIST
//    Based on: main.php → get_activity_data()
//    Tables: learningpaths, learningpath_users, learningpath_courses
//    Screen: Learning Path list (name, description, progress, course count)
// =========================================================
 
public static function get_learning_paths_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}
 
public static function get_learning_paths($userid) {
    global $DB, $CFG;
 
    $params = self::validate_parameters(
        self::get_learning_paths_parameters(),
        ['userid' => $userid]
    );
 
    $uid = $params['userid'];
 
    // Get LPs assigned to this user — exact query from main.php
    $sql = "SELECT l.*
            FROM {learningpaths} l
            INNER JOIN {learningpath_users} lu ON l.id = lu.learningpathid
            WHERE lu.userid = :userid
              AND l.publish = 1
              AND l.deleted = 0
            ORDER BY l.id ASC";
 
    $lps = $DB->get_records_sql($sql, ['userid' => $uid]);
 
    $output = [];
 
    foreach ($lps as $lp) {
 
        // Course count
        $courses = $DB->get_records('learningpath_courses', ['learningpathid' => $lp->id]);
        $totalcourses    = 0;
        $completedcourses = 0;
 
        foreach ($courses as $c) {
            $course = $DB->get_record('course', ['id' => $c->courseid]);
            if (!$course) continue;
            $progress = \core_completion\progress::get_course_progress_percentage($course, $uid);
            $progress = (int)floor($progress ?? 0);
            $totalcourses++;
            if ($progress >= 100) $completedcourses++;
        }
 
        // Progress percentage — exact logic from main.php
        $progressRounded = ($totalcourses > 0)
            ? (int)round(($completedcourses / $totalcourses) * 100)
            : 0;
 
        // Status label
        if ($progressRounded >= 100) {
            $statuslabel = 'Completed';
            $buttontext  = 'Review';
        } else if ($progressRounded > 0) {
            $statuslabel = 'In Progress';
            $buttontext  = 'Continue Learning';
        } else {
            $statuslabel = 'Not Started';
            $buttontext  = 'Start Learning';
        }
 
        // Description — stored as JSON {text: "..."}
        $description = '';
        if (!empty($lp->description)) {
            $decoded = json_decode($lp->description, true);
            $description = $decoded['text'] ?? strip_tags($lp->description);
        }
 
        // Duration from start/end dates
        $differenceInSeconds = ($lp->enddate > 0 && $lp->startdate > 0)
            ? ($lp->enddate - $lp->startdate) : 0;
        $duration = self::lp_seconds_to_time($differenceInSeconds);
 
        // LP image URL
        $imageurl = $CFG->wwwroot . '/local/learningpaths/pluginfile.php?learningpathid=' . $lp->id . '&t=';
 
        // View URL
        $viewurl = $CFG->wwwroot . '/blocks/learningpathview/lp_view_course.php?id=' . $lp->id;
 
        $output[] = [
            'lpid'          => (int)$lp->id,
            'lpname'        => (string)$lp->name,
            'description'   => (string)$description,
            'imageurl'      => (string)$imageurl,
            'nocourses'     => $totalcourses,
            'duration'      => (string)$duration,
            'startdate'     => date('Y-m-d', $lp->startdate),
            'enddate'       => date('Y-m-d', $lp->enddate),
            'credits'       => (string)($lp->credits ?? ''),
            'progress'      => $progressRounded,
            'statuslabel'   => $statuslabel,
            'buttontext'    => $buttontext,
            'viewurl'       => $viewurl,
        ];
    }
 
    return [
        'learningpaths' => $output,
        'total'         => count($output),
    ];
}
 
public static function get_learning_paths_returns() {
    return new external_single_structure([
        'learningpaths' => new external_multiple_structure(
            new external_single_structure([
                'lpid'        => new external_value(PARAM_INT,  'Learning Path ID'),
                'lpname'      => new external_value(PARAM_TEXT, 'LP name → card title'),
               'description' => new external_value(PARAM_RAW, 'Description'),
                'imageurl'    => new external_value(PARAM_RAW,  'LP image URL'),
                'nocourses'   => new external_value(PARAM_INT,  'Number of courses → "2 courses"'),
                'duration'    => new external_value(PARAM_TEXT, 'Duration string'),
                'startdate'   => new external_value(PARAM_TEXT, 'Start date Y-m-d'),
                'enddate'     => new external_value(PARAM_TEXT, 'End date Y-m-d'),
                'credits'     => new external_value(PARAM_TEXT, 'Credits'),
                'progress'    => new external_value(PARAM_INT,  'Progress % → progress bar + "38%"'),
                'statuslabel' => new external_value(PARAM_TEXT, 'Not Started / In Progress / Completed'),
                'buttontext'  => new external_value(PARAM_TEXT, 'Start / Continue / Review'),
                'viewurl'     => new external_value(PARAM_TEXT, 'Web view URL'),
            ])
        ),
        'total' => new external_value(PARAM_INT, 'Total learning paths'),
    ]);
}
 
// =========================================================
// 2. GET COURSES INSIDE A LEARNING PATH
//    Based on: lpviewcourse.php → get_course_data()
//    Tables: learningpath_courses, course, learningpath_course_prereq
//    Screen: LP detail — Step 1, Step 2 with lock/progress
// =========================================================
 
public static function get_lp_courses_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
        'lpid'   => new external_value(PARAM_INT, 'Learning Path ID'),
    ]);
}
 
public static function get_lp_courses($userid, $lpid) {
    global $DB, $CFG;
 
    $params = self::validate_parameters(
        self::get_lp_courses_parameters(),
        ['userid' => $userid, 'lpid' => $lpid]
    );
 
    $uid  = $params['userid'];
    $lpid = $params['lpid'];
 
    // Verify LP exists
    $lp = $DB->get_record('learningpaths', ['id' => $lpid, 'deleted' => 0], '*', MUST_EXIST);
 
    // Get LP header info
    $description = '';
    if (!empty($lp->description)) {
        $decoded = json_decode($lp->description, true);
        $description = $decoded['text'] ?? strip_tags($lp->description);
    }
 
    // Get LP overall progress
    $all_courses   = $DB->get_records('learningpath_courses', ['learningpathid' => $lpid]);
    $total_lp      = 0;
    $completed_lp  = 0;
    foreach ($all_courses as $c) {
        $course = $DB->get_record('course', ['id' => $c->courseid]);
        if (!$course) continue;
        $prog = (int)floor(\core_completion\progress::get_course_progress_percentage($course, $uid) ?? 0);
        $total_lp++;
        if ($prog >= 100) $completed_lp++;
    }
    $lp_progress = $total_lp > 0 ? (int)round(($completed_lp / $total_lp) * 100) : 0;
 
    // Get courses IN ORDER — exact from lpviewcourse.php
    $course_modules = $DB->get_records_sql(
        "SELECT * FROM {learningpath_courses}
         WHERE learningpathid = ?
         ORDER BY id ASC",
        [$lpid]
    );
 
    $courses_output = [];
    $step = 1;
 
    foreach ($course_modules as $keyvalue) {
 
        $getcour = $DB->get_record('course', ['id' => $keyvalue->courseid]);
        if (!$getcour) { $step++; continue; }
 
        // Progress for this course
        $progressdata = \core_completion\progress::get_course_progress_percentage($getcour, $uid);
        $percentage   = (int)floor($progressdata ?? 0);
 
        // Prerequisite check — exact from lpviewcourse.php
        $courserepre = $DB->get_record('learningpath_course_prereq', [
            'learningpath_courseid' => $keyvalue->id
        ]);
 
        $islocked   = false;
        $prereqname = '';
 
        if (!empty($courserepre) && !empty($courserepre->prerequisite)) {
            $precourse = $DB->get_record('course', ['id' => $courserepre->prerequisite]);
            if ($precourse) {
                $prereqname = $precourse->fullname;
                $preprogress = (int)floor(
                    \core_completion\progress::get_course_progress_percentage($precourse, $uid) ?? 0
                );
                if ($preprogress < 100) {
                    $islocked = true;
                }
            }
        }
 
        // Duration
        $diffSecs = ($getcour->enddate > 0 && $getcour->startdate > 0)
            ? ($getcour->enddate - $getcour->startdate) : 0;
        $duration = self::lp_seconds_to_time($diffSecs);
 
        // Status — exact match from lpviewcourse.php
        if ($percentage >= 100) {
            $status         = 'completed';
            $timelineicon   = 'check';
            $statuslabel    = 'Done';
            $courselink     = $CFG->wwwroot . '/local/incourse/index.php?id=' . $getcour->id;
        } else if ($islocked) {
            $status         = 'locked';
            $timelineicon   = 'lock';
            $statuslabel    = 'Locked';
            $courselink     = '';
        } else if ($percentage > 0) {
            $status         = 'inprogress';
            $timelineicon   = 'play';
            $statuslabel    = 'In Progress';
            $courselink     = $CFG->wwwroot . '/local/incourse/index.php?id=' . $getcour->id;
        } else {
            $status         = 'notstarted';
            $timelineicon   = 'play';
            $statuslabel    = 'Not Started';
            $courselink     = $CFG->wwwroot . '/local/incourse/index.php?id=' . $getcour->id;
        }
 
        $courses_output[] = [
            'step'          => $step,
            'courseid'      => (int)$getcour->id,
            'coursename'    => (string)$getcour->fullname,
            'coursedesc'    => strip_tags($getcour->summary ?? ''),
            'courseimage'   => (string)self::lp_get_course_image($getcour->id),
            'courselink'    => (string)$courselink,
            'progress'      => $percentage,
            'duration'      => (string)$duration,
            'startdate'     => date('Y-m-d', $getcour->startdate),
            'enddate'       => $getcour->enddate ? date('Y-m-d', $getcour->enddate) : '',
            'status'        => $status,
            'statuslabel'   => $statuslabel,
            'timelineicon'  => $timelineicon,
            'islocked'      => $islocked,
            'prereqname'    => (string)$prereqname,
            'locktext'      => $islocked ? 'Complete previous courses' : '',
        ];
 
        $step++;
    }
 
    // Find start/resume button URL — exact from lpviewcourse.php
    $mainurl    = '';
    $buttontext = 'Start Learning';
 
    if (!empty($courses_output)) {
        // Find resume course (first in-progress)
        $resumecourse = null;
        foreach ($courses_output as $c) {
            if ($c['progress'] > 0 && $c['progress'] < 100) {
                $resumecourse = $c;
                break;
            }
        }
 
        if ($lp_progress == 0) {
            $buttontext = 'Start Learning';
            $mainurl    = $courses_output[0]['courselink'];
        } else if ($resumecourse) {
            $buttontext = 'Resume Learning';
            $mainurl    = $resumecourse['courselink'];
        } else {
            $buttontext = 'Review';
            $mainurl    = $courses_output[0]['courselink'];
        }
    }
 
    return [
        'lpid'        => (int)$lpid,
        'lpname'      => (string)$lp->name,
        'description' => (string)$description,
        'lp_progress' => $lp_progress,
        'buttontext'  => $buttontext,
        'mainurl'     => $mainurl,
        'courses'     => $courses_output,
        'totalcourses'=> count($courses_output),
    ];
}
 
public static function get_lp_courses_returns() {
    return new external_single_structure([
        'lpid'         => new external_value(PARAM_INT,  'LP ID'),
        'lpname'       => new external_value(PARAM_TEXT, 'LP name'),
        'description' => new external_value(PARAM_RAW, 'Description'),
        'lp_progress'  => new external_value(PARAM_INT,  'Overall LP progress %'),
        'buttontext'   => new external_value(PARAM_TEXT, 'Start / Resume / Review'),
        'mainurl'      => new external_value(PARAM_TEXT, 'Main action URL'),
        'totalcourses' => new external_value(PARAM_INT,  'Number of courses'),
        'courses' => new external_multiple_structure(
            new external_single_structure([
                'step'         => new external_value(PARAM_INT,  'Step number → Step 1, Step 2'),
                'courseid'     => new external_value(PARAM_INT,  'Course ID'),
                'coursename'   => new external_value(PARAM_TEXT, 'Course name'),
                'coursedesc'   => new external_value(PARAM_TEXT, 'Course description'),
                'courseimage'  => new external_value(PARAM_RAW,  'Course thumbnail URL'),
                'courselink'   => new external_value(PARAM_TEXT, 'Course URL (empty if locked)'),
                'progress'     => new external_value(PARAM_INT,  'Progress % → progress bar'),
                'duration'     => new external_value(PARAM_TEXT, 'Duration string'),
                'startdate'    => new external_value(PARAM_TEXT, 'Start date'),
                'enddate'      => new external_value(PARAM_TEXT, 'End date'),
                'status'       => new external_value(PARAM_TEXT, 'completed/inprogress/notstarted/locked'),
                'statuslabel'  => new external_value(PARAM_TEXT, 'Done / In Progress / Not Started / Locked'),
                'timelineicon' => new external_value(PARAM_TEXT, 'check/play/lock → timeline icon'),
                'islocked'     => new external_value(PARAM_BOOL, 'Is course locked by prerequisite?'),
                'prereqname'   => new external_value(PARAM_TEXT, 'Prerequisite course name'),
                'locktext'     => new external_value(PARAM_TEXT, '"Complete previous courses" if locked'),
            ])
        ),
    ]);
}
 
// =========================================================
// 3. MARK LP COURSE COMPLETE
//    Triggers course completion manually
// =========================================================
 
public static function finish_lp_course_parameters() {
    return new external_function_parameters([
        'userid'   => new external_value(PARAM_INT, 'User ID'),
        'courseid' => new external_value(PARAM_INT, 'Course ID'),
        'lpid'     => new external_value(PARAM_INT, 'Learning Path ID'),
    ]);
}
 
public static function finish_lp_course($userid, $courseid, $lpid) {
    global $DB,$CFG;
 require_once($CFG->libdir . '/completionlib.php');
    $params = self::validate_parameters(
        self::finish_lp_course_parameters(),
        ['userid' => $userid, 'courseid' => $courseid, 'lpid' => $lpid]
    );
 
    // Mark course complete using Moodle completion API
    $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
    $completion = new \completion_info($course);
 
    if ($completion->is_enabled()) {
        $completion->mark_course_completions(false, false, $params['userid']);
    }
 
    // Recalculate LP progress
    $courses = $DB->get_records('learningpath_courses', ['learningpathid' => $params['lpid']]);
    $total = 0; $completed = 0;
    foreach ($courses as $c) {
        $c2 = $DB->get_record('course', ['id' => $c->courseid]);
        if (!$c2) continue;
        $prog = (int)floor(\core_completion\progress::get_course_progress_percentage($c2, $params['userid']) ?? 0);
        $total++;
        if ($prog >= 100) $completed++;
    }
    $new_progress = $total > 0 ? (int)round(($completed / $total) * 100) : 0;
 
    return [
        'success'      => true,
        'message'      => 'Course marked complete',
        'lp_progress'  => $new_progress,
    ];
}
 
public static function finish_lp_course_returns() {
    return new external_single_structure([
        'success'     => new external_value(PARAM_BOOL, 'Success'),
        'message'     => new external_value(PARAM_TEXT, 'Message'),
        'lp_progress' => new external_value(PARAM_INT,  'Updated LP progress %'),
    ]);
}
// =========================================================
// ░░ ATTENDANCE — USER SCREEN ░░
// =========================================================

public static function get_my_attendance_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
        'filter' => new external_value(PARAM_TEXT,
            'all | attended | pending | absent',
            VALUE_DEFAULT, 'all'),
    ]);
}

public static function get_my_attendance($userid, $filter = 'all') {
    global $DB, $CFG;

    $params  = self::validate_parameters(
        self::get_my_attendance_parameters(),
        ['userid' => $userid, 'filter' => $filter]
    );

    $user    = $DB->get_record('user',
        ['id' => $params['userid'], 'deleted' => 0], '*', MUST_EXIST);
    $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname, shortname');

    $output        = [];
    $total_attended = 0;
    $total_pending  = 0;
    $total_absent   = 0;
    $total_points   = 0;

    foreach ($courses as $course) {
        // Get all attendance instances in this course
        $attendances = $DB->get_records('attendance', ['course' => $course->id]);

        foreach ($attendances as $attendance) {
            $cm = get_coursemodule_from_instance('attendance', $attendance->id, $course->id);
            if (!$cm || !$cm->visible) continue;

            // Get all sessions for this attendance
            $sessions = $DB->get_records('attendance_sessions',
                ['attendanceid' => $attendance->id],
                'sessdate ASC'
            );

            foreach ($sessions as $session) {

                // Get user's log for this session
                $log = $DB->get_record('attendance_log', [
                    'sessionid' => $session->id,
                    'studentid' => $params['userid'],
                ]);

                // Get status details
                $status_obj  = null;
                $status_text = 'Pending';
                $status_code = 'pending';
                $points      = 0;
                $method      = '';

                if ($log && !empty($log->statusid)) {
                    $status_obj = $DB->get_record('attendance_statuses',
                        ['id' => $log->statusid]);
                }

                if ($status_obj) {
                    $acronym = strtoupper(trim($status_obj->acronym ?? ''));
                    $grade   = (float)($status_obj->grade ?? 0);
                    $points  = (int)$grade;

                    // Map status acronym to screen tags
                    if (in_array($acronym, ['P', 'A', 'ATT', 'ATTENDED', 'PRESENT'])) {
                        $status_text = 'Attended';
                        $status_code = 'attended';
                        $total_attended++;
                        $total_points += $points;
                    } elseif (in_array($acronym, ['AB', 'ABS', 'ABSENT'])) {
                        $status_text = 'Absent';
                        $status_code = 'absent';
                        $total_absent++;
                    } elseif (in_array($acronym, ['L', 'LATE'])) {
                        $status_text = 'Late';
                        $status_code = 'late';
                        $total_attended++;
                        $total_points += $points;
                    } elseif (in_array($acronym, ['E', 'EX', 'EXCUSED'])) {
                        $status_text = 'Excused';
                        $status_code = 'excused';
                    } else {
                        $status_text = $status_obj->description ?? $acronym;
                        $status_code = 'attended';
                        $total_attended++;
                        $total_points += $points;
                    }

                    // Attendance method
                    if (!empty($log->timetaken)) {
                        if (!empty($session->automark) && $session->automark == 2) {
                            $method = 'QR scanned';
                        } elseif (!empty($log->ipaddress)) {
                            $method = 'QR scanned';
                        } else {
                            $method = 'Marked by trainer';
                        }
                        // Check remarks
                        if (!empty($log->remarks)) {
                            $method = $log->remarks;
                        }
                    } else {
                        $method = 'Marked by trainer';
                    }

                } else {
                    // No log = Pending (future) or Absent (past)
                    $now = time();
                    if ($session->sessdate > $now) {
                        $status_text = 'Pending';
                        $status_code = 'pending';
                        $total_pending++;
                    } else {
                        $status_text = 'Pending';
                        $status_code = 'pending';
                        $total_pending++;
                    }
                    $method = 'Regular class session';
                }

                // Apply filter
                if ($params['filter'] !== 'all' && $params['filter'] !== $status_code) {
                    continue;
                }

                // Build time string — e.g. "4:33PM - 5PM"
                $start_ts   = (int)$session->sessdate;
                $end_ts     = $start_ts + (int)($session->duration ?? 0);
                $start_time = self::format_clock($start_ts);
                $end_time   = self::format_clock($end_ts);
                $time_range = $start_time . ' - ' . $end_time;

                $output[] = [
                    'session_id'    => (int)$session->id,
                    'attendance_id' => (int)$attendance->id,
                    'cmid'          => (int)$cm->id,
                    'course_id'     => (int)$course->id,
                    'course_name'   => $course->fullname,

                    // ---- Screen fields ----
                    'title'         => format_string($attendance->name),
                    'status_text'   => $status_text,       // Attended / Pending / Absent
                    'status_code'   => $status_code,       // attended / pending / absent / late
                    'date'          => userdate($session->sessdate, '%a %d %b %Y'), // Thu 28 May 2026
                    'date_timestamp'=> (int)$session->sessdate,
                    'time_range'    => $time_range,        // 1PM - 3PM
                    'duration'      => (int)($session->duration ?? 0),
                    'method'        => $method,            // QR scanned / Marked by trainer / Regular class session
                    'points'        => $points,            // 50 pts / 0 pts
                    'description'   => strip_tags($session->description ?? ''),
                    'is_attended'   => ($status_code === 'attended' || $status_code === 'late'),
                    'is_pending'    => ($status_code === 'pending'),
                    'view_url'      => $CFG->wwwroot . '/mod/attendance/view.php?id=' . $cm->id,
                ];
            }
        }
    }

    // Sort by date DESC — latest first
    usort($output, fn($a, $b) => $b['date_timestamp'] - $a['date_timestamp']);

    return [
        'userid'         => (int)$params['userid'],
        'fullname'       => fullname($user),
        'filter'         => $params['filter'],
        'sessions'       => $output,
        'total'          => count($output),
        'total_attended' => $total_attended,
        'total_pending'  => $total_pending,
        'total_absent'   => $total_absent,
        'total_points'   => $total_points,
    ];
}

public static function get_my_attendance_returns() {
    return new external_single_structure([
        'userid'         => new external_value(PARAM_INT,  'User ID'),
        'fullname'       => new external_value(PARAM_TEXT, 'User full name'),
        'filter'         => new external_value(PARAM_TEXT, 'Filter applied'),
        'sessions'       => new external_multiple_structure(
            new external_single_structure([
                'session_id'     => new external_value(PARAM_INT,  'Session ID'),
                'attendance_id'  => new external_value(PARAM_INT,  'Attendance activity ID'),
                'cmid'           => new external_value(PARAM_INT,  'Course module ID'),
                'course_id'      => new external_value(PARAM_INT,  'Course ID'),
                'course_name'    => new external_value(PARAM_TEXT, 'Course name'),
                'title'          => new external_value(PARAM_TEXT, 'Session/Course title → Blended Learning Course'),
                'status_text'    => new external_value(PARAM_TEXT, 'Attended / Pending / Absent / Late'),
                'status_code'    => new external_value(PARAM_TEXT, 'attended / pending / absent / late / excused'),
                'date'           => new external_value(PARAM_TEXT, 'Thu 28 May 2026'),
                'date_timestamp' => new external_value(PARAM_INT,  'Unix timestamp'),
                'time_range'     => new external_value(PARAM_TEXT, '1PM - 3PM'),
                'duration'       => new external_value(PARAM_INT,  'Duration in seconds'),
                'method'         => new external_value(PARAM_TEXT, 'QR scanned / Marked by trainer / Regular class session'),
                'points'         => new external_value(PARAM_INT,  'Points earned e.g. 50'),
                'description'    => new external_value(PARAM_TEXT, 'Session description'),
                'is_attended'    => new external_value(PARAM_BOOL, 'True if attended/late'),
                'is_pending'     => new external_value(PARAM_BOOL, 'True if pending'),
                'view_url'       => new external_value(PARAM_RAW,  'Moodle view URL'),
            ])
        ),
        'total'          => new external_value(PARAM_INT, 'Total sessions'),
        'total_attended' => new external_value(PARAM_INT, 'Attended count'),
        'total_pending'  => new external_value(PARAM_INT, 'Pending count'),
        'total_absent'   => new external_value(PARAM_INT, 'Absent count'),
        'total_points'   => new external_value(PARAM_INT, 'Total points earned'),
    ]);
}
/**
     * Format a timestamp as the screen shows it: "4:33PM", or "5PM" on the hour.
     *
     * Built from %I:%M %p rather than %I so the minutes survive -- %I is the
     * hour alone, which is why 4:33PM was rendering as 4PM.
     */
    protected static function format_clock(int $timestamp): string {
        $formatted = trim(userdate($timestamp, '%I:%M %p'));

        // Strip the padding zero and drop ":00" on the hour.
        if (preg_match('/^0?(\d{1,2}):(\d{2})\s*(.*)$/', $formatted, $m)) {
            $hour   = (int)$m[1];
            $minute = $m[2];
            $suffix = \core_text::strtoupper(str_replace(' ', '', $m[3]));

            return ($minute === '00')
                ? $hour . $suffix
                : $hour . ':' . $minute . $suffix;
        }

        // Unexpected locale format — return it rather than mangling it.
        return $formatted;
    }
// =========================================================
// ░░ QR SCAN — MARK ATTENDANCE ░░
// =========================================================

// ----- get_session_qr_info -----
// Called first to check if session allows QR scanning
public static function get_session_qr_info_parameters() {
    return new external_function_parameters([
        'userid'    => new external_value(PARAM_INT,  'User ID'),
        'sessionid' => new external_value(PARAM_INT,  'Session ID'),
    ]);
}

public static function get_session_qr_info($userid, $sessionid) {
    global $DB, $CFG;

    $params = self::validate_parameters(
        self::get_session_qr_info_parameters(),
        ['userid' => $userid, 'sessionid' => $sessionid]
    );

    // Get session
    $session = $DB->get_record('attendance_sessions',
        ['id' => $params['sessionid']], '*', MUST_EXIST);

    // Get attendance activity
    $attendance = $DB->get_record('attendance',
        ['id' => $session->attendanceid], '*', MUST_EXIST);

    $course = $DB->get_record('course',
        ['id' => $attendance->course], '*', MUST_EXIST);

    $cm = get_coursemodule_from_instance('attendance', $attendance->id, $course->id);

    // Check if already marked
    $existing_log = $DB->get_record('attendance_log', [
        'sessionid' => $params['sessionid'],
        'studentid' => $params['userid'],
    ]);

    $already_marked = !empty($existing_log) && !empty($existing_log->statusid);

    // Get current status if already marked
    $current_status = '';
    if ($already_marked && $existing_log->statusid) {
        $status = $DB->get_record('attendance_statuses',
            ['id' => $existing_log->statusid]);
        $current_status = $status->description ?? $status->acronym ?? '';
    }

    // Check session is active (within time window)
    $now       = time();
    $sess_end  = $session->sessdate + ($session->duration ?? 0);
    $is_active = ($session->sessdate <= $now && $sess_end >= $now);

    // Check if QR automark is enabled (automark=2 means QR)
    $qr_enabled = (!empty($session->automark) && $session->automark == 2)
               || (!empty($session->studentpassword)); // password-based QR

    // Get available statuses for this attendance
    $statuses     = $DB->get_records('attendance_statuses',
        ['attendanceid' => $attendance->id, 'deleted' => 0],
        'grade DESC'
    );
    $status_list  = [];
    foreach ($statuses as $s) {
        $status_list[] = [
            'id'          => (int)$s->id,
            'acronym'     => $s->acronym,
            'description' => $s->description,
            'grade'       => (float)$s->grade,
        ];
    }

    return [
        'session_id'      => (int)$session->id,
        'attendance_id'   => (int)$attendance->id,
        'cmid'            => (int)($cm->id ?? 0),
        'course_id'       => (int)$course->id,
        'course_name'     => $course->fullname,
        'title'           => format_string($attendance->name),
        'date'            => userdate($session->sessdate, '%a %d %b %Y'),
        'date_timestamp'  => (int)$session->sessdate,
        'time_range'      => ltrim(userdate($session->sessdate, '%I%p'), '0')
                            . ' - '
                            . ltrim(userdate($sess_end, '%I%p'), '0'),
        'description'     => strip_tags($session->description ?? ''),
        'is_active'       => $is_active,
        'qr_enabled'      => $qr_enabled,
        'already_marked'  => $already_marked,
        'current_status'  => $current_status,
        'statuses'        => $status_list,
        'can_scan'        => (!$already_marked && $is_active),
        'view_url'        => $CFG->wwwroot . '/mod/attendance/view.php?id=' . ($cm->id ?? 0),
    ];
}

public static function get_session_qr_info_returns() {
    return new external_single_structure([
        'session_id'     => new external_value(PARAM_INT,  'Session ID'),
        'attendance_id'  => new external_value(PARAM_INT,  'Attendance ID'),
        'cmid'           => new external_value(PARAM_INT,  'Course module ID'),
        'course_id'      => new external_value(PARAM_INT,  'Course ID'),
        'course_name'    => new external_value(PARAM_TEXT, 'Course name'),
        'title'          => new external_value(PARAM_TEXT, 'Session title'),
        'date'           => new external_value(PARAM_TEXT, 'Thu 28 May 2026'),
        'date_timestamp' => new external_value(PARAM_INT,  'Unix timestamp'),
        'time_range'     => new external_value(PARAM_TEXT, '1PM - 3PM'),
        'description'    => new external_value(PARAM_TEXT, 'Session description'),
        'is_active'      => new external_value(PARAM_BOOL, 'Session is currently active'),
        'qr_enabled'     => new external_value(PARAM_BOOL, 'QR scan allowed'),
        'already_marked' => new external_value(PARAM_BOOL, 'Already marked attendance'),
        'current_status' => new external_value(PARAM_TEXT, 'Current status if marked'),
        'statuses'       => new external_multiple_structure(
            new external_single_structure([
                'id'          => new external_value(PARAM_INT,   'Status ID'),
                'acronym'     => new external_value(PARAM_TEXT,  'P/A/L etc'),
                'description' => new external_value(PARAM_TEXT,  'Present/Absent/Late'),
                'grade'       => new external_value(PARAM_FLOAT, 'Points for this status'),
            ])
        ),
        'can_scan'       => new external_value(PARAM_BOOL, 'User can scan now'),
        'view_url'       => new external_value(PARAM_RAW,  'View URL'),
    ]);
}

// ----- mark_attendance_by_qr -----
// Called after QR code is scanned successfully
public static function mark_attendance_by_qr_parameters() {
    return new external_function_parameters([
        'userid'     => new external_value(PARAM_INT,  'User ID'),
        'sessionid'  => new external_value(PARAM_INT,  'Session ID'),
        'qrpassword' => new external_value(PARAM_TEXT, 'QR code password/token from scan',
                        VALUE_DEFAULT, ''),
    ]);
}

public static function mark_attendance_by_qr($userid, $sessionid, $qrpassword = '') {
    global $DB, $CFG;

    $params = self::validate_parameters(
        self::mark_attendance_by_qr_parameters(),
        ['userid' => $userid, 'sessionid' => $sessionid, 'qrpassword' => $qrpassword]
    );

    // Get session
    $session = $DB->get_record('attendance_sessions',
        ['id' => $params['sessionid']], '*', MUST_EXIST);

    $attendance = $DB->get_record('attendance',
        ['id' => $session->attendanceid], '*', MUST_EXIST);

    // ---- Validate session is active ----
    $now      = time();
    $sess_end = $session->sessdate + ($session->duration ?? 3600);

    if ($now < $session->sessdate) {
        return [
            'success'      => false,
            'message'      => 'Session has not started yet',
            'status_text'  => '',
            'points'       => 0,
            'total_points' => 0,
            'method'       => '',
        ];
    }

    if ($now > $sess_end) {
        return [
            'success'      => false,
            'message'      => 'Session has already ended',
            'status_text'  => '',
            'points'       => 0,
            'total_points' => 0,
            'method'       => '',
        ];
    }

    // ---- Check if already marked ----
    $existing = $DB->get_record('attendance_log', [
        'sessionid' => $params['sessionid'],
        'studentid' => $params['userid'],
    ]);

    if ($existing && !empty($existing->statusid)) {
        $status = $DB->get_record('attendance_statuses',
            ['id' => $existing->statusid]);
        return [
            'success'      => false,
            'message'      => 'Attendance already marked as: '
                            . ($status->description ?? 'Attended'),
            'status_text'  => $status->description ?? 'Attended',
            'points'       => (int)($status->grade ?? 0),
            'total_points' => self::get_attendance_total_points(
                                $params['userid'], $attendance->id),
            'method'       => 'QR scanned',
        ];
    }

    // ---- Validate QR password if session requires it ----
    if (!empty($session->studentpassword) && !empty($params['qrpassword'])) {
        if (trim($session->studentpassword) !== trim($params['qrpassword'])) {
            return [
                'success'      => false,
                'message'      => 'Invalid QR code. Please scan the correct QR code.',
                'status_text'  => '',
                'points'       => 0,
                'total_points' => 0,
                'method'       => '',
            ];
        }
    }

    // ---- Find best "present" status for this attendance ----
    // Get highest grade status = "Present"
    $present_status = $DB->get_record_sql(
        "SELECT * FROM {attendance_statuses}
         WHERE attendanceid = ?
           AND deleted = 0
         ORDER BY grade DESC
         LIMIT 1",
        [$attendance->id]
    );

    // Fallback: find by acronym P or Present
    if (!$present_status) {
        $present_status = $DB->get_record_sql(
            "SELECT * FROM {attendance_statuses}
             WHERE attendanceid = ?
               AND deleted = 0
               AND (UPPER(acronym) = 'P' OR UPPER(acronym) = 'ATT')
             LIMIT 1",
            [$attendance->id]
        );
    }

    if (!$present_status) {
        return [
            'success'      => false,
            'message'      => 'No attendance status configured. Contact admin.',
            'status_text'  => '',
            'points'       => 0,
            'total_points' => 0,
            'method'       => '',
        ];
    }

    // ---- Write or update attendance log ----
    if ($existing) {
        // Update existing empty log
        $existing->statusid   = $present_status->id;
        $existing->timetaken  = $now;
        $existing->takenby    = $params['userid']; // self-marked via QR
        $existing->ipaddress  = getremoteaddr();
        $existing->remarks    = 'QR scanned';
        $DB->update_record('attendance_log', $existing);
    } else {
        // Insert new log
        $log              = new stdClass();
        $log->sessionid   = $params['sessionid'];
        $log->studentid   = $params['userid'];
        $log->statusid    = $present_status->id;
        $log->timetaken   = $now;
        $log->takenby     = $params['userid'];
        $log->ipaddress   = getremoteaddr();
        $log->remarks     = 'QR scanned';
        $DB->insert_record('attendance_log', $log);
    }

    // ---- Update attendance grade ----
    try {
        $course = $DB->get_record('course',
            ['id' => $attendance->course], '*', MUST_EXIST);
        $cm     = get_coursemodule_from_instance(
            'attendance', $attendance->id, $course->id);

        if ($cm) {
            // Trigger grade recalculation
            require_once($CFG->dirroot . '/mod/attendance/locallib.php');
            $att_obj = new mod_attendance_structure(
                $attendance, $cm, $course,
                context_module::instance($cm->id)
            );
            attendance_update_users_grade($att_obj);
        }
    } catch (Exception $e) {
        // Grade update failed but attendance was marked — still success
    }

    $points = (int)($present_status->grade ?? 0);

    return [
        'success'      => true,
        'message'      => 'QR scanned successfully! Attendance marked.',
        'status_text'  => $present_status->description ?? 'Attended',
        'points'       => $points,
        'total_points' => self::get_attendance_total_points(
                            $params['userid'], $attendance->id),
        'method'       => 'QR scanned',
    ];
}

public static function mark_attendance_by_qr_returns() {
    return new external_single_structure([
        'success'      => new external_value(PARAM_BOOL, 'Success'),
        'message'      => new external_value(PARAM_TEXT, 'QR scanned successfully! / error'),
        'status_text'  => new external_value(PARAM_TEXT, 'Attended / Absent / Late'),
        'points'       => new external_value(PARAM_INT,  'Points earned from this session'),
        'total_points' => new external_value(PARAM_INT,  'Total attendance points'),
        'method'       => new external_value(PARAM_TEXT, 'QR scanned'),
    ]);
}

// ---- Helper: get total attendance points for user ----
private static function get_attendance_total_points($userid, $attendanceid) {
    global $DB;
    $sql = "SELECT COALESCE(SUM(ats.grade), 0) AS total
            FROM {attendance_log} al
            JOIN {attendance_statuses} ats ON ats.id = al.statusid
            JOIN {attendance_sessions} ass  ON ass.id = al.sessionid
            WHERE al.studentid    = ?
              AND ass.attendanceid = ?";
    $result = $DB->get_record_sql($sql, [$userid, $attendanceid]);
    return (int)($result->total ?? 0);
}
public static function logout_parameters() {
    return new external_function_parameters([
        'token' => new external_value(PARAM_RAW, 'User token')
    ]);
}
public static function logout($token) {
    global $DB;

    self::validate_parameters(
        self::logout_parameters(),
        ['token' => $token]
    );

    if ($DB->record_exists('external_tokens', ['token' => $token])) {
        $DB->delete_records('external_tokens', ['token' => $token]);
    }

    return [
        'status' => true,
        'message' => 'Logout successful'
    ];
}
public static function logout_returns() {
    return new external_single_structure([
        'status'  => new external_value(PARAM_BOOL, 'Status'),
        'message' => new external_value(PARAM_TEXT, 'Message')
    ]);
}
// =========================================================
// ░░ SKILLGAP — MY SKILLS DASHBOARD ░░
// =========================================================

public static function get_my_skills_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_my_skills($userid) {
    global $DB, $CFG;

    $params = self::validate_parameters(
        self::get_my_skills_parameters(), ['userid' => $userid]
    );

    require_once($CFG->dirroot . '/local/skillgap/lib.php');

    $data = \local_skillgap\local\helper::user_dashboard_data($params['userid']);

    // ---- Assigned competencies (same source the web page uses) ----
    $competencies = array_map(function ($c) {
        $score = $c['has_score'] ? (float) explode('/', $c['score'])[0] : 0;
        $pct   = $c['has_score'] ? min(100, round(($score / 5) * 100)) : 0;
        return [
            'compid'       => (int)$c['id'],
            'name'         => $c['name'],
            'description'  => $c['description'] ?? '',
            'score'        => $score,
            'maxscore'     => 5,
            'pct'          => $pct,
            'score_label'  => $c['score'],
            'status'       => strtolower(str_replace(' ', '_', $c['status_label'])),
            'status_label' => $c['status_label'],
            'is_competent' => $c['badge_class'] === 'success',
            'progress'     => 'N/A',
            'trend_up'     => false,
            'trend_down'   => false,
            'is_first'     => !$c['has_score'],
        ];
    }, $data['assigned_competencies']);

    // ---- Pending assessments ----
    $pending_list = array_map(function ($a) {
        return [
            'assessment_id' => (int)$a['id'],
            'title'         => $a['title'],
            'frequency'     => $a['frequency'] ?? '',
            'url'           => $a['url'],
        ];
    }, $data['assigned_pending']);
// ---- In-progress assessments ----
    $inprogress_list = array_map(function ($a) {
        return [
            'attempt_id'    => (int)($a['attemptid'] ?? 0),
            'assessment_id' => (int)$a['id'],
            'title'         => $a['title'],
            'dept_name'     => $a['dept_name'] ?? '',
            'frequency'     => $a['frequency'] ?? '',
            'comp_count'    => $a['comp_count'] ?? 0,
            'total_q'       => $a['total_q'] ?? 0,
            'resume_url'    => $a['url'],
        ];
    }, $data['assigned_running']);
    // ---- Completed assessments ----
    $completed_list = array_map(function ($a) {
        return [
            'attempt_id'     => (int)($a['attemptid'] ?? 0),
            'assessment_id'  => (int)$a['id'],
            'title'          => $a['title'],
            'dept_name'      => $a['dept_name'] ?? '',
            'frequency'      => $a['frequency'] ?? '',
            'date'           => $a['timecompleted'] ?? '-',
            'date_timestamp' => 0, // add raw timestamp in helper if you need this precisely
            'avg_pct'        => $a['overall_pct'] ?? 0,
            'can_retake'     => true,
            'retake_url'     => $a['url'],
        ];
    }, $data['assigned_completed']);

    // ---- Radar chart data ----
    $radar_labels = json_decode($data['chart_labels_json'], true) ?: [];
    $radar_values = json_decode($data['chart_scores_json'], true) ?: [];

    // ---- Promotion history ----
    $promo_history = array_map(function ($p) {
        return [
            'from_desig'     => $p['from_desig'],
            'to_desig'       => $p['to_desig'],
            'label'          => $p['from_desig'] . ' → ' . $p['to_desig'],
            'date'           => $p['date'],
            'date_timestamp' => 0, // add raw timestamp in helper if you need this precisely
        ];
    }, $data['promotion_history']);

    // ---- Past assessment snapshots ----
    $past_snapshots = array_map(function ($s) {
        $comp_tags = array_map(function ($cs) {
            return [
                'name'         => $cs['compname'],
                'pct'          => $cs['score_pct'],
                'status'       => strtolower(str_replace(' ', '_', $cs['status_label'])),
                'status_label' => $cs['status_label'],
                'tag_label'    => $cs['compname'] . ': ' . $cs['score_pct'] . '% — ' . $cs['status_label'],
            ];
        }, $s['comp_snaps']);

        return [
            'attempt_id'     => 0, // add attemptid in helper snapshot array if needed
            'assessment_id'  => 0, // add assessmentid in helper snapshot array if needed
            'title'          => $s['assessment_title'],
            'date'           => $s['date'],
            'date_timestamp' => 0,
            'avg_pct'        => $s['avg_pct'],
            'comp_tags'      => $comp_tags,
        ];
    }, $data['past_competency_snapshots']);

    return [
        'userid'      => (int)$params['userid'],
        'fullname'    => $data['fullname'],
        'department'  => $data['department'],
        'designation' => $data['current_designation'],
        'is_promoted' => $data['recently_promoted'],
        'promo_label' => $data['recently_promoted']
            ? '↑ Promoted → ' . $data['promoted_designation'] : '',

        'avg_score'        => $data['avg_score'],
        'competencies_met' => $data['competent_count'] . '/' . $data['total_count'],
        'pending_count'    => count($pending_list),
        'completed_count'  => count($completed_list),

        'has_pending'         => !empty($pending_list),
        'first_pending_id'    => !empty($pending_list) ? $pending_list[0]['assessment_id'] : 0,
        'first_pending_title' => !empty($pending_list) ? $pending_list[0]['title'] : '',
        'pending_assessments' => $pending_list,

        'completed_assessments' => $completed_list,
'inprogress_count'        => count($inprogress_list),
        'has_inprogress'          => !empty($inprogress_list),
        'inprogress_assessments'  => $inprogress_list,
        'competencies' => $competencies,
        'radar_labels' => implode(',', $radar_labels),
        'radar_values' => implode(',', $radar_values),

        'has_promo_history' => !empty($promo_history),
        'promo_history'      => $promo_history,

        'past_snapshots' => $past_snapshots,

        'next_designation' => $data['next_designation'] ?? '',
        'readiness_pct' => $data['avg_score'], // adjust if you track a real % in helper
    ];
}

public static function get_my_skills_returns() {
    $comp_tag_struct = new external_single_structure([
        'name'         => new external_value(PARAM_TEXT,  'Competency name'),
        'pct'          => new external_value(PARAM_INT,   'Percentage score'),
        'status'       => new external_value(PARAM_TEXT,  'competent/moderate/needs_improvement'),
        'status_label' => new external_value(PARAM_TEXT,  'Competent/Moderate/Needs Improvement'),
        'tag_label'    => new external_value(PARAM_TEXT,  'e.g. Communication: 100% — Competent'),
    ]);

    return new external_single_structure([
        'userid'           => new external_value(PARAM_INT,  'User ID'),
        'fullname'         => new external_value(PARAM_TEXT, 'Full name → Emma Brown'),
        'department'       => new external_value(PARAM_TEXT, 'Department → Operations'),
        'designation'      => new external_value(PARAM_TEXT, 'Designation → Assistant'),
        'is_promoted'      => new external_value(PARAM_BOOL, 'Recently promoted?'),
        'promo_label'      => new external_value(PARAM_TEXT, '↑ Promoted → Assistant'),

        // Stat cards
        'avg_score'        => new external_value(PARAM_INT,  '92 → shown as 92%'),
        'competencies_met' => new external_value(PARAM_TEXT, '5/5 → COMPETENCIES MET'),
        'pending_count'    => new external_value(PARAM_INT,  '0 → PENDING ASSESSMENTS'),
        'completed_count'  => new external_value(PARAM_INT,  '5 → COMPLETED'),

        // Pending
        'has_pending'         => new external_value(PARAM_BOOL, 'Has pending assessments'),
        'first_pending_id'    => new external_value(PARAM_INT,  'First pending assessment ID'),
        'first_pending_title' => new external_value(PARAM_TEXT, 'Customer Acquisition'),
        'pending_assessments' => new external_multiple_structure(
            new external_single_structure([
                'assessment_id' => new external_value(PARAM_INT,  'Assessment ID'),
                'title'         => new external_value(PARAM_TEXT, 'Title'),
                'frequency'     => new external_value(PARAM_TEXT, 'Frequency'),
                'url'           => new external_value(PARAM_RAW,  'Start URL'),
            ])
        ),

        // Completed
        'completed_assessments' => new external_multiple_structure(
            new external_single_structure([
                'attempt_id'    => new external_value(PARAM_INT,  'Attempt ID'),
                'assessment_id' => new external_value(PARAM_INT,  'Assessment ID'),
                'title'         => new external_value(PARAM_TEXT, 'Title'),
                'dept_name'     => new external_value(PARAM_TEXT, 'Dept name'),
                'frequency'     => new external_value(PARAM_TEXT, 'Frequency'),
                'date'          => new external_value(PARAM_TEXT, '20 May 2026'),
                'date_timestamp'=> new external_value(PARAM_INT,  'Unix timestamp'),
                'avg_pct'       => new external_value(PARAM_INT,  'Avg score %'),
                'can_retake'    => new external_value(PARAM_BOOL, 'Can retake'),
                'retake_url'    => new external_value(PARAM_RAW,  'Retake URL'),
            ])
        ),
        // In-progress
        'inprogress_count'       => new external_value(PARAM_INT,  'In-progress assessment count'),
        'has_inprogress'         => new external_value(PARAM_BOOL, 'Has in-progress assessments'),
        'inprogress_assessments' => new external_multiple_structure(
            new external_single_structure([
                'attempt_id'    => new external_value(PARAM_INT,  'Attempt ID'),
                'assessment_id' => new external_value(PARAM_INT,  'Assessment ID'),
                'title'         => new external_value(PARAM_TEXT, 'Title'),
                'dept_name'     => new external_value(PARAM_TEXT, 'Dept name'),
                'frequency'     => new external_value(PARAM_TEXT, 'Frequency'),
                'comp_count'    => new external_value(PARAM_INT,  'Number of competencies'),
                'total_q'       => new external_value(PARAM_INT,  'Total questions'),
                'resume_url'    => new external_value(PARAM_RAW,  'Resume attempt URL'),
            ])
        ),

        // Competencies + Radar
        'competencies' => new external_multiple_structure(
    new external_single_structure([
        'compid'       => new external_value(PARAM_INT,   'Comp ID'),
        'name'         => new external_value(PARAM_TEXT,  'Analytical Thinking'),
        'description'  => new external_value(PARAM_TEXT,  'Competency description'),
        'score'        => new external_value(PARAM_FLOAT, 'Score'),
        'maxscore'     => new external_value(PARAM_INT,   'Max score'),
        'pct'          => new external_value(PARAM_INT,   'Percentage'),
        'score_label'  => new external_value(PARAM_TEXT,  '5/5'),
        'status'       => new external_value(PARAM_TEXT,  'competent'),
        'status_label' => new external_value(PARAM_TEXT,  'Competent'),
        'is_competent' => new external_value(PARAM_BOOL,  'Is competent'),
        'progress'     => new external_value(PARAM_TEXT,  '+1.0 / First Attempt'),
        'trend_up'     => new external_value(PARAM_BOOL,  'Trending up'),
        'trend_down'   => new external_value(PARAM_BOOL,  'Trending down'),
        'is_first'     => new external_value(PARAM_BOOL,  'First attempt'),
    ])
),
        'radar_labels' => new external_value(PARAM_TEXT, 'Comma-sep competency names for radar'),
        'radar_values' => new external_value(PARAM_TEXT, 'Comma-sep score values for radar'),

        // Promotion history
        'has_promo_history' => new external_value(PARAM_BOOL, 'Has promotion history'),
        'promo_history'     => new external_multiple_structure(
            new external_single_structure([
                'from_desig'     => new external_value(PARAM_TEXT, 'Trainee'),
                'to_desig'       => new external_value(PARAM_TEXT, 'Junior'),
                'label'          => new external_value(PARAM_TEXT, 'Trainee → Junior'),
                'date'           => new external_value(PARAM_TEXT, '22/05/26'),
                'date_timestamp' => new external_value(PARAM_INT,  'Unix timestamp'),
            ])
        ),

        // Past assessment records
        'past_snapshots' => new external_multiple_structure(
            new external_single_structure([
                'attempt_id'    => new external_value(PARAM_INT,  'Attempt ID'),
                'assessment_id' => new external_value(PARAM_INT,  'Assessment ID'),
                'title'         => new external_value(PARAM_TEXT, 'Assessment title'),
                'date'          => new external_value(PARAM_TEXT, '20/05/26'),
                'date_timestamp'=> new external_value(PARAM_INT,  'Unix timestamp'),
                'avg_pct'       => new external_value(PARAM_INT,  'Avg score %'),
                'comp_tags'     => new external_multiple_structure($comp_tag_struct),
            ])
        ),

        // Promotion track
        'next_designation' => new external_value(PARAM_TEXT, 'Senior Manager'),
        'readiness_pct'    => new external_value(PARAM_INT,  '100 → 100% readiness'),
    ]);
}

// =========================================================
// ░░ SKILLGAP — START / GET ASSESSMENT QUIZ ░░
// =========================================================

public static function get_assessment_quiz_parameters() {
    return new external_function_parameters([
        'userid'       => new external_value(PARAM_INT, 'User ID'),
        'assessmentid' => new external_value(PARAM_INT, 'Assessment ID'),
    ]);
}

public static function get_assessment_quiz($userid, $assessmentid) {
    global $DB, $CFG;

    $params = self::validate_parameters(
        self::get_assessment_quiz_parameters(),
        ['userid' => $userid, 'assessmentid' => $assessmentid]
    );

    $assessment = $DB->get_record('local_skillgap_assessment',
        ['id' => $params['assessmentid']], '*', MUST_EXIST);

    // ---- Get or create attempt ----
    $attempt = $DB->get_record('local_skillgap_attempt', [
        'userid'       => $params['userid'],
        'assessmentid' => $params['assessmentid'],
        'status'       => 'inprogress',
    ]);
    if (!$attempt) {
        $attempt_num = $DB->count_records('local_skillgap_attempt', [
            'userid'       => $params['userid'],
            'assessmentid' => $params['assessmentid'],
        ]) + 1;
        $attempt_id = $DB->insert_record('local_skillgap_attempt', [
            'userid'       => $params['userid'],
            'assessmentid' => $params['assessmentid'],
            'attemptnum'   => $attempt_num,
            'status'       => 'inprogress',
            'timecreated'  => time(),
        ]);
        $attempt = $DB->get_record('local_skillgap_attempt', ['id' => $attempt_id]);
    }

    // ---- Adaptive mode: only show weak competencies on retry ----
    $is_adaptive = $attempt->attemptnum > 1;
    $acs         = $DB->get_records('local_skillgap_assess_comp',
        ['assessmentid' => $params['assessmentid']]);

    if ($is_adaptive) {
        $active_acs = [];
        foreach ($acs as $ac) {
            $latest = $DB->get_record_sql(
                "SELECT status FROM {local_skillgap_score}
                  WHERE userid = :uid AND compid = :cid
                  ORDER BY timecreated DESC LIMIT 1",
                ['uid' => $params['userid'], 'cid' => $ac->compid]
            );
            if (!$latest || $latest->status !== 'competent') {
                $active_acs[] = $ac;
            }
        }
        if (empty($active_acs)) $active_acs = array_values($acs);
    } else {
        $active_acs = array_values($acs);
    }

    // ---- Build question blocks ----
    $comp_blocks = [];
    $total_q     = 0;
    $letters     = ['A', 'B', 'C', 'D'];

    foreach ($active_acs as $ac) {
        $comp_type = (!empty($ac->comp_type) && $ac->comp_type !== '')
            ? $ac->comp_type : 'main';
        $subcompid = (!empty($ac->subcompid) && (int)$ac->subcompid > 0)
            ? (int)$ac->subcompid : null;

        if ($comp_type === 'main' || !$subcompid) {
            $comp_name = $DB->get_field('local_skillgap_comp', 'name',
                ['id' => $ac->compid]) ?: '?';
            $questions = $DB->get_records_select('local_skillgap_question',
                'compid = :cid AND (subcompid IS NULL OR subcompid = 0)',
                ['cid' => $ac->compid], 'id ASC');
        } else {
            $comp_name = $DB->get_field('local_skillgap_subcomp', 'name',
                ['id' => $subcompid]) ?: '?';
            $questions = $DB->get_records('local_skillgap_question',
                ['compid' => $ac->compid, 'subcompid' => $subcompid], 'id ASC');
        }

        $q_rows = [];
        foreach ($questions as $q) {
            $options  = $DB->get_records('local_skillgap_qoption',
                ['questionid' => $q->id]);
            $opt_rows = [];
            $i = 0;
            foreach ($options as $o) {
                $opt_rows[] = [
                    'option_id'  => (int)$o->id,
                    'letter'     => $letters[$i] ?? chr(65 + $i),
                    'text'       => $o->optiontext,
                    'questionid' => (int)$q->id,
                    'attemptid'  => (int)$attempt->id,
                ];
                $i++;
            }

            // Already answered?
            $existing_resp = $DB->get_record('local_skillgap_response', [
                'attemptid'  => $attempt->id,
                'questionid' => $q->id,
            ]);

            $type_labels = [
                'main'   => 'Main Competency',
                'sub'    => 'Sub-Competency',
                'subsub' => 'Sub-Sub Competency',
            ];

            $q_rows[] = [
                'question_id'    => (int)$q->id,
                'text'           => $q->questiontext,
                'compid'         => (int)$ac->compid,
                'subcompid'      => (int)($subcompid ?? 0),
                'comp_name'      => $comp_name,
                'comp_type'      => $comp_type,
                'comp_type_label'=> $type_labels[$comp_type] ?? 'Main Competency',
                'attemptid'      => (int)$attempt->id,
                'answered'       => !empty($existing_resp),
                'selected_option'=> $existing_resp ? (int)$existing_resp->optionid : 0,
                'options'        => $opt_rows,
            ];
            $total_q++;
        }

        if (!empty($q_rows)) {
            $comp_blocks[] = [
                'compid'          => (int)$ac->compid,
                'subcompid'       => (int)($subcompid ?? 0),
                'compname'        => $comp_name,
                'comp_type'       => $comp_type,
                'comp_type_label' => $type_labels[$comp_type] ?? 'Main Competency',
                'is_sub_level'    => $comp_type !== 'main',
                'question_count'  => count($q_rows),
                'questions'       => $q_rows,
            ];
        }
    }

    // Answered so far
    $answered_count = (int)$DB->count_records('local_skillgap_response',
        ['attemptid' => $attempt->id]);

    return [
        'attemptid'        => (int)$attempt->id,
        'assessmentid'     => (int)$params['assessmentid'],
        'assessment_title' => $assessment->title,
        'userid'           => (int)$params['userid'],
        'is_adaptive'      => $is_adaptive,
        'adaptive_label'   => $is_adaptive ? 'Adaptive mode — weak competencies only' : '',
        'total_q'          => $total_q,
        'answered_count'   => $answered_count,
        'comp_blocks'      => $comp_blocks,
        'comp_count'       => count($comp_blocks),
        'submit_url'       => $CFG->wwwroot
            . '/local/skillgap/user/assessment.php?action=submit&attemptid='
            . $attempt->id . '&sesskey=' . sesskey(),
    ];
}

public static function get_assessment_quiz_returns() {
    $option_struct = new external_single_structure([
        'option_id'  => new external_value(PARAM_INT,  'Option ID'),
        'letter'     => new external_value(PARAM_TEXT, 'A/B/C/D'),
        'text'       => new external_value(PARAM_TEXT, 'Option text'),
        'questionid' => new external_value(PARAM_INT,  'Question ID'),
        'attemptid'  => new external_value(PARAM_INT,  'Attempt ID'),
    ]);

    $question_struct = new external_single_structure([
        'question_id'    => new external_value(PARAM_INT,  'Question ID'),
        'text'           => new external_value(PARAM_TEXT, 'Question text'),
        'compid'         => new external_value(PARAM_INT,  'Competency ID'),
        'subcompid'      => new external_value(PARAM_INT,  'Sub-comp ID (0 if main)'),
        'comp_name'      => new external_value(PARAM_TEXT, 'Customer Acquisition'),
        'comp_type'      => new external_value(PARAM_TEXT, 'main/sub/subsub'),
        'comp_type_label'=> new external_value(PARAM_TEXT, 'Main Competency'),
        'attemptid'      => new external_value(PARAM_INT,  'Attempt ID'),
        'answered'       => new external_value(PARAM_BOOL, 'Already answered?'),
        'selected_option'=> new external_value(PARAM_INT,  'Selected option ID (0=none)'),
        'options'        => new external_multiple_structure($option_struct),
    ]);

    $block_struct = new external_single_structure([
        'compid'          => new external_value(PARAM_INT,  'Competency ID'),
        'subcompid'       => new external_value(PARAM_INT,  'Sub-comp ID'),
        'compname'        => new external_value(PARAM_TEXT, 'Customer Acquisition'),
        'comp_type'       => new external_value(PARAM_TEXT, 'main/sub/subsub'),
        'comp_type_label' => new external_value(PARAM_TEXT, 'Main Competency'),
        'is_sub_level'    => new external_value(PARAM_BOOL, 'Is sub/subsub?'),
        'question_count'  => new external_value(PARAM_INT,  'Questions in this block'),
        'questions'       => new external_multiple_structure($question_struct),
    ]);

    return new external_single_structure([
        'attemptid'        => new external_value(PARAM_INT,  'Attempt ID'),
        'assessmentid'     => new external_value(PARAM_INT,  'Assessment ID'),
        'assessment_title' => new external_value(PARAM_TEXT, 'Customer Acquisition'),
        'userid'           => new external_value(PARAM_INT,  'User ID'),
        'is_adaptive'      => new external_value(PARAM_BOOL, 'Adaptive mode?'),
        'adaptive_label'   => new external_value(PARAM_TEXT, 'Adaptive mode — weak competencies only'),
        'total_q'          => new external_value(PARAM_INT,  '25 questions total'),
        'answered_count'   => new external_value(PARAM_INT,  'Answered so far'),
        'comp_count'       => new external_value(PARAM_INT,  'Number of competency blocks'),
        'comp_blocks'      => new external_multiple_structure($block_struct),
        'submit_url'       => new external_value(PARAM_RAW,  'Submit URL'),
    ]);
}

// =========================================================
// ░░ SKILLGAP — SUBMIT QUIZ ANSWER ░░
// =========================================================

public static function skillgap_save_response_parameters() {
    return new external_function_parameters([
        'attemptid'  => new external_value(PARAM_INT, 'Attempt ID'),
        'questionid' => new external_value(PARAM_INT, 'Question ID'),
        'optionid'   => new external_value(PARAM_INT, 'Selected option ID'),
    ]);
}

public static function skillgap_save_response($attemptid, $questionid, $optionid) {
    global $DB;

    $params = self::validate_parameters(
        self::skillgap_save_response_parameters(),
        ['attemptid' => $attemptid, 'questionid' => $questionid, 'optionid' => $optionid]
    );

    $attempt  = $DB->get_record('local_skillgap_attempt',
        ['id' => $params['attemptid']], '*', MUST_EXIST);
    $question = $DB->get_record('local_skillgap_question',
        ['id' => $params['questionid']], '*', MUST_EXIST);
    $option   = $DB->get_record('local_skillgap_qoption',
        ['id' => $params['optionid']], '*', MUST_EXIST);

    if ($attempt->status === 'completed') {
        return ['status' => 'error', 'message' => 'Attempt already completed',
                'iscorrect' => false, 'answered_count' => 0];
    }

    // Upsert
    $DB->delete_records('local_skillgap_response', [
        'attemptid' => $params['attemptid'],
        'questionid' => $params['questionid'],
    ]);
    $DB->insert_record('local_skillgap_response', [
        'attemptid'   => $params['attemptid'],
        'questionid'  => $params['questionid'],
        'optionid'    => $params['optionid'],
        'iscorrect'   => (int)(bool)$option->iscorrect,
        'compid'      => $question->compid,
        'timecreated' => time(),
    ]);

    $answered_count = (int)$DB->count_records('local_skillgap_response',
        ['attemptid' => $params['attemptid']]);

    return [
        'status'         => 'saved',
        'message'        => 'Answer saved',
        'iscorrect'      => (bool)$option->iscorrect,
        'answered_count' => $answered_count,
    ];
}

public static function skillgap_save_response_returns() {
    return new external_single_structure([
        'status'         => new external_value(PARAM_TEXT, 'saved/error'),
        'message'        => new external_value(PARAM_TEXT, 'Message'),
        'iscorrect'      => new external_value(PARAM_BOOL, 'Was answer correct'),
        'answered_count' => new external_value(PARAM_INT,  'Total answered so far'),
    ]);
}

// =========================================================
// ░░ SKILLGAP — SUBMIT ASSESSMENT (final) ░░
// =========================================================

public static function skillgap_submit_assessment_parameters() {
    return new external_function_parameters([
        'userid'    => new external_value(PARAM_INT, 'User ID'),
        'attemptid' => new external_value(PARAM_INT, 'Attempt ID'),
    ]);
}

public static function skillgap_submit_assessment($userid, $attemptid) {
    global $DB, $CFG;

    $params = self::validate_parameters(
        self::skillgap_submit_assessment_parameters(),
        ['userid' => $userid, 'attemptid' => $attemptid]
    );

    $attempt = $DB->get_record('local_skillgap_attempt',
        ['id' => $params['attemptid'], 'userid' => $params['userid']], '*', MUST_EXIST);

    if ($attempt->status === 'completed') {
        return ['success' => false, 'message' => 'Already submitted',
                'scores' => [], 'avg_pct' => 0, 'all_competent' => false];
    }

    // Mark completed
    $DB->set_field('local_skillgap_attempt', 'status',
        'completed', ['id' => $params['attemptid']]);
    $DB->set_field('local_skillgap_attempt', 'timecompleted',
        time(), ['id' => $params['attemptid']]);

    // Compute scores
    require_once($CFG->dirroot . '/local/skillgap/lib.php');
    local_skillgap_compute_scores($params['attemptid']);

    // Return new scores
    $scores    = $DB->get_records_sql(
        "SELECT s.compid, c.name, s.score, s.maxscore, s.status
           FROM {local_skillgap_score} s
           JOIN {local_skillgap_comp} c ON c.id = s.compid
          WHERE s.attemptid = :aid",
        ['aid' => $params['attemptid']]
    );

    $score_out    = [];
    $total_pct    = 0;
    $all_competent = true;
    $status_labels = [
        'competent'         => 'Competent',
        'moderate'          => 'Moderate',
        'needs_improvement' => 'Needs Improvement',
    ];

    foreach ($scores as $s) {
        $pct = $s->maxscore > 0
            ? min(100, round((float)$s->score / $s->maxscore * 100))
            : 0;
        $total_pct += $pct;
        if ($s->status !== 'competent') $all_competent = false;

        $score_out[] = [
            'compid'       => (int)$s->compid,
            'name'         => $s->name,
            'score'        => round((float)$s->score, 1),
            'maxscore'     => (int)$s->maxscore,
            'pct'          => $pct,
            'score_label'  => round((float)$s->score, 1) . '/' . $s->maxscore,
            'status'       => $s->status,
            'status_label' => $status_labels[$s->status] ?? ucfirst($s->status),
            'is_competent' => $s->status === 'competent',
        ];
    }

    $avg_pct = count($scores) > 0
        ? round($total_pct / count($scores))
        : 0;

    return [
        'success'       => true,
        'message'       => 'Assessment submitted! Your scores have been recorded.',
        'scores'        => $score_out,
        'avg_pct'       => $avg_pct,
        'all_competent' => $all_competent,
    ];
}

public static function skillgap_submit_assessment_returns() {
    return new external_single_structure([
        'success'       => new external_value(PARAM_BOOL, 'Success'),
        'message'       => new external_value(PARAM_TEXT, 'Message'),
        'scores'        => new external_multiple_structure(
            new external_single_structure([
                'compid'       => new external_value(PARAM_INT,   'Comp ID'),
                'name'         => new external_value(PARAM_TEXT,  'Competency name'),
                'score'        => new external_value(PARAM_FLOAT, 'Score'),
                'maxscore'     => new external_value(PARAM_INT,   'Max score'),
                'pct'          => new external_value(PARAM_INT,   'Percentage'),
                'score_label'  => new external_value(PARAM_TEXT,  '5/5'),
                'status'       => new external_value(PARAM_TEXT,  'competent'),
                'status_label' => new external_value(PARAM_TEXT,  'Competent'),
                'is_competent' => new external_value(PARAM_BOOL,  'Is competent'),
            ])
        ),
        'avg_pct'       => new external_value(PARAM_INT,  'Overall average %'),
        'all_competent' => new external_value(PARAM_BOOL, 'All competencies passed?'),
    ]);
}

// =========================================================
// HALL OF FAME — UPLOAD CERTIFICATE API
// Add to local/rapmobile/externallib.php
// Based on local/halloffame logic
// Created by Omkar
//
// Tables used:
//   {halloffame_submissions}  — certificate uploads
//   {halloffame_categories}   — cert categories
//   {files}                   — Moodle file storage
// =========================================================

// =========================================================
// 1. GET HALL OF FAME CATEGORIES
//    For the category dropdown in upload form
// =========================================================

public static function get_halloffame_categories_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_halloffame_categories($userid) {
    global $DB;

    $params = self::validate_parameters(
        self::get_halloffame_categories_parameters(),
        ['userid' => $userid]
    );

    // Get active categories
    $categories = $DB->get_records(
        'halloffame_categories',
        ['status' => 1],
        'name ASC',
        'id, name, description'
    );

    $output = [];
    foreach ($categories as $cat) {
        $output[] = [
            'id'          => (int)$cat->id,
            'name'        => (string)$cat->name,
            'description' => strip_tags($cat->description ?? ''),
        ];
    }

    return [
        'categories' => $output,
        'total'      => count($output),
    ];
}

public static function get_halloffame_categories_returns() {
    return new external_single_structure([
        'categories' => new external_multiple_structure(
            new external_single_structure([
                'id'          => new external_value(PARAM_INT,  'Category ID'),
                'name'        => new external_value(PARAM_TEXT, 'Category name'),
                'description' => new external_value(PARAM_TEXT, 'Category description'),
            ])
        ),
        'total' => new external_value(PARAM_INT, 'Total categories'),
    ]);
}

// =========================================================
// 2. GET MY SUBMISSIONS
//    List of certificates submitted by current user
// =========================================================

public static function get_my_halloffame_submissions_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User ID'),
    ]);
}

public static function get_my_halloffame_submissions($userid) {
    global $DB, $CFG;

    $params = self::validate_parameters(
        self::get_my_halloffame_submissions_parameters(),
        ['userid' => $userid]
    );

    $submissions = $DB->get_records(
        'halloffame_submissions',
        ['userid' => $params['userid']],
        'timecreated DESC'
    );

    $output = [];
    foreach ($submissions as $sub) {

        // Get category name
        $catname = '';
        if (!empty($sub->categoryid)) {
            $cat = $DB->get_record('halloffame_categories', ['id' => $sub->categoryid], 'name');
            $catname = $cat ? $cat->name : '';
        }

        // Get file URL
        $fileurl = '';
        if (!empty($sub->fileid)) {
            $fs   = get_file_storage();
            $file = $fs->get_file_by_id($sub->fileid);
            if ($file) {
                $fileurl = \moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false);
            }
        }

        // Status label
        $statuslabels = [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Rejected',
        ];

        $output[] = [
            'id'          => (int)$sub->id,
            'title'       => (string)($sub->title ?? ''),
            'description' => strip_tags($sub->description ?? ''),
            'categoryid'  => (int)($sub->categoryid ?? 0),
            'categoryname'=> (string)$catname,
            'fileurl'     => (string)$fileurl,
            'status'      => (int)($sub->status ?? 0),
            'statuslabel' => $statuslabels[$sub->status ?? 0] ?? 'Pending',
            'timecreated' => (int)($sub->timecreated ?? 0),
            'dateformatted'=> date('d M Y', $sub->timecreated ?? time()),
        ];
    }

    return [
        'submissions' => $output,
        'total'       => count($output),
    ];
}

public static function get_my_halloffame_submissions_returns() {
    return new external_single_structure([
        'submissions' => new external_multiple_structure(
            new external_single_structure([
                'id'           => new external_value(PARAM_INT,  'Submission ID'),
                'title'        => new external_value(PARAM_TEXT, 'Certificate title'),
                'description'  => new external_value(PARAM_TEXT, 'Description'),
                'categoryid'   => new external_value(PARAM_INT,  'Category ID'),
                'categoryname' => new external_value(PARAM_TEXT, 'Category name'),
                'fileurl'      => new external_value(PARAM_RAW,  'Certificate file URL'),
                'status'       => new external_value(PARAM_INT,  '0=Pending 1=Approved 2=Rejected'),
                'statuslabel'  => new external_value(PARAM_TEXT, 'Pending/Approved/Rejected'),
                'timecreated'  => new external_value(PARAM_INT,  'Submitted timestamp'),
                'dateformatted'=> new external_value(PARAM_TEXT, 'Formatted date e.g. 01 Jan 2026'),
            ])
        ),
        'total' => new external_value(PARAM_INT, 'Total submissions'),
    ]);
}
// =========================================================
// ░░ HALL OF FAME — SUBMIT CERTIFICATE WITH FILE UPLOAD ░░
// =========================================================

public static function halloffame_submit_certificate_parameters() {
    return new external_function_parameters([
        'userid'       => new external_value(PARAM_INT,  'User ID'),
        'title'        => new external_value(PARAM_TEXT, 'Certificate title'),
        'issuer'       => new external_value(PARAM_TEXT, 'Issuing organisation', VALUE_DEFAULT, ''),
        'issuedate'    => new external_value(PARAM_INT,  'Issue date unix timestamp', VALUE_DEFAULT, 0),
        'type'         => new external_value(PARAM_TEXT, 'Certificate type: Technical/Project Management/Leadership/Compliance/Other', VALUE_DEFAULT, ''),
        'notes'        => new external_value(PARAM_TEXT, 'Additional notes', VALUE_DEFAULT, ''),
        'draftitemid'  => new external_value(PARAM_INT,  'Draft item ID from upload.php Step 1', VALUE_DEFAULT, 0),
    ]);
}

public static function halloffame_submit_certificate(
    $userid, $title, $issuer = '', $issuedate = 0,
    $type = '', $notes = '', $draftitemid = 0
) {
    global $DB, $CFG, $USER;

    require_once($CFG->dirroot . '/local/halloffame/lib.php');

    $params = self::validate_parameters(
        self::halloffame_submit_certificate_parameters(),
        [
            'userid'      => $userid,
            'title'       => $title,
            'issuer'      => $issuer,
            'issuedate'   => $issuedate,
            'type'        => $type,
            'notes'       => $notes,
            'draftitemid' => $draftitemid,
        ]
    );

    // ---- Validate user ----
    $user = $DB->get_record('user',
        ['id' => $params['userid'], 'deleted' => 0], '*', MUST_EXIST);

    // ---- Validate title ----
    if (trim($params['title']) === '') {
        return [
            'success'  => false,
            'id'       => 0,
            'message'  => 'Certificate title is required',
            'fileurl'  => '',
        ];
    }

    // ---- Check duplicate ----
    $exists = $DB->record_exists('halloffame_submissions', [
        'userid' => $params['userid'],
        'title'  => $params['title'],
    ]);
    if ($exists) {
        return [
            'success'  => false,
            'id'       => 0,
            'message'  => 'You have already submitted a certificate with this title',
            'fileurl'  => '',
        ];
    }

    // ---- Process uploaded file from draft area ----
    $fileurl = '';

    if (!empty($params['draftitemid'])) {
        try {
            // Get user context
            $usercontext = context_user::instance($params['userid']);

            // Get file storage
            $fs = get_file_storage();

            // Get draft files
            $draft_files = $fs->get_area_files(
                $usercontext->id,
                'user',
                'draft',
                $params['draftitemid'],
                'filename',
                false
            );

            if (!empty($draft_files)) {
                $draft_file = reset($draft_files);

                // Validate file type — allow PDF + images
                $allowed_mimes = [
                    'application/pdf',
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/webp',
                ];
                $mimetype = $draft_file->get_mimetype();

                if (!in_array($mimetype, $allowed_mimes)) {
                    return [
                        'success'  => false,
                        'id'       => 0,
                        'message'  => 'Invalid file type. Allowed: PDF, JPG, PNG, WEBP',
                        'fileurl'  => '',
                    ];
                }

                // Validate file size — max 5MB
                if ($draft_file->get_filesize() > 5 * 1024 * 1024) {
                    return [
                        'success'  => false,
                        'id'       => 0,
                        'message'  => 'File too large. Maximum size is 5MB',
                        'fileurl'  => '',
                    ];
                }

                // Build unique itemid same as submit.php logic
                $itemid = (int)($params['userid'] * 10000 + (time() % 10000));

                // Get system context for storing
                $syscontext = context_system::instance();

                // File record for permanent storage
                $file_record = [
                    'contextid' => $syscontext->id,
                    'component' => 'local_halloffame',
                    'filearea'  => 'certificates',
                    'itemid'    => $itemid,
                    'filepath'  => '/',
                    'filename'  => clean_filename($draft_file->get_filename()),
                    'timecreated'  => time(),
                    'timemodified' => time(),
                    'userid'       => $params['userid'],
                ];

                // Copy from draft to permanent
                $stored_file = $fs->create_file_from_storedfile($file_record, $draft_file);

                if ($stored_file) {
                    // Build pluginfile URL
                    $fileurl = moodle_url::make_pluginfile_url(
                        $syscontext->id,
                        'local_halloffame',
                        'certificates',
                        $itemid,
                        '/',
                        $stored_file->get_filename()
                    )->out(false);

                    // Delete draft file after moving
                    $draft_file->delete();
                }
            }
        } catch (Exception $e) {
            // File processing failed — still allow submission without file
            $fileurl = '';
        }
    }

    // ---- Submit to halloffame using same logic as submit.php ----
    try {
        // Use manager::submit_achievement if available
        if (class_exists('\local_halloffame\manager')) {
            // Temporarily set $USER to the submitted userid
            // (submit_achievement uses $USER->id internally)
            $original_user = $USER;
            $USER = $user;

            $submission_data = [
                'title'     => $params['title'],
                'issuer'    => $params['issuer'],
                'issuedate' => $params['issuedate'],
                'type'      => $params['type'],
                'notes'     => $params['notes'],
                'fileurl'   => $fileurl,
            ];

            $id = \local_halloffame\manager::submit_achievement($submission_data);
            $USER = $original_user;

        } else {
            // Fallback: direct DB insert
            $record = new stdClass();
            $record->userid      = $params['userid'];
            $record->title       = $params['title'];
            $record->issuer      = $params['issuer'];
            $record->issuedate   = $params['issuedate'];
            $record->type        = $params['type'];
            $record->notes       = $params['notes'];
            $record->fileurl     = $fileurl;
            $record->status      = 'pending';
            $record->timecreated = time();
            $record->timemodified = time();

            $id = $DB->insert_record('halloffame_submissions', $record);
        }

        return [
            'success'  => true,
            'id'       => (int)$id,
            'message'  => 'Certificate submitted successfully! It will be reviewed by admin.',
            'fileurl'  => $fileurl,
        ];

    } catch (Exception $e) {
        return [
            'success'  => false,
            'id'       => 0,
            'message'  => 'Submission failed: ' . $e->getMessage(),
            'fileurl'  => '',
        ];
    }
}

public static function halloffame_submit_certificate_returns() {
    return new external_single_structure([
        'success'  => new external_value(PARAM_BOOL, 'Submission succeeded'),
        'id'       => new external_value(PARAM_INT,  'New submission record ID'),
        'message'  => new external_value(PARAM_TEXT, 'Success or error message'),
        'fileurl'  => new external_value(PARAM_RAW,  'Permanent file URL (empty if no file)'),
    ]);
}
// =========================================================
// 3. UPLOAD CERTIFICATE — MAIN API
//    Mobile sends file as base64
//    Saves to Moodle file storage + creates submission record
// =========================================================

public static function upload_halloffame_certificate_parameters() {
    return new external_function_parameters([
        'userid'      => new external_value(PARAM_INT,  'User ID'),
        'title'       => new external_value(PARAM_TEXT, 'Certificate title'),
        'description' => new external_value(PARAM_TEXT, 'Description', VALUE_DEFAULT, ''),
        'categoryid'  => new external_value(PARAM_INT,  'Category ID from get_halloffame_categories'),
        'filename'    => new external_value(PARAM_TEXT, 'File name e.g. certificate.pdf or cert.jpg'),
        'filedata'    => new external_value(PARAM_TEXT, 'Base64 encoded file content'),
        'filetype'    => new external_value(PARAM_TEXT, 'MIME type: application/pdf or image/jpeg or image/png', VALUE_DEFAULT, 'application/pdf'),
    ]);
}

public static function upload_halloffame_certificate(
    $userid, $title, $description, $categoryid,
    $filename, $filedata, $filetype
) {
    global $DB, $CFG, $USER;

    $params = self::validate_parameters(
        self::upload_halloffame_certificate_parameters(),
        [
            'userid'      => $userid,
            'title'       => $title,
            'description' => $description,
            'categoryid'  => $categoryid,
            'filename'    => $filename,
            'filedata'    => $filedata,
            'filetype'    => $filetype,
        ]
    );

    // ---- Validate user ----
    $user = $DB->get_record('user',
        ['id' => $params['userid'], 'deleted' => 0], '*', MUST_EXIST);

    // ---- Validate category ----
    if ($params['categoryid'] > 0) {
        $cat = $DB->get_record('halloffame_categories', ['id' => $params['categoryid']]);
        if (!$cat) {
            return [
                'success'       => false,
                'message'       => 'Invalid category',
                'submissionid'  => 0,
                'fileurl'       => '',
            ];
        }
    }

    // ---- Validate title ----
    if (empty(trim($params['title']))) {
        return [
            'success'      => false,
            'message'      => 'Title is required',
            'submissionid' => 0,
            'fileurl'      => '',
        ];
    }

    // ---- Validate file data ----
    if (empty($params['filedata'])) {
        return [
            'success'      => false,
            'message'      => 'File data is required',
            'submissionid' => 0,
            'fileurl'      => '',
        ];
    }

    // ---- Allowed file types ----
    $allowed_types = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
    ];
    if (!in_array($params['filetype'], $allowed_types)) {
        return [
            'success'      => false,
            'message'      => 'Invalid file type. Allowed: PDF, JPG, PNG',
            'submissionid' => 0,
            'fileurl'      => '',
        ];
    }

    // ---- Decode base64 file ----
    // Strip data URI prefix if present e.g. "data:application/pdf;base64,..."
    $filedata = $params['filedata'];
    if (strpos($filedata, ';base64,') !== false) {
        $filedata = substr($filedata, strpos($filedata, ';base64,') + 8);
    }
    $filedata = base64_decode($filedata);

    if ($filedata === false || strlen($filedata) === 0) {
        return [
            'success'      => false,
            'message'      => 'Invalid file data — could not decode base64',
            'submissionid' => 0,
            'fileurl'      => '',
        ];
    }

    // ---- File size limit: 10MB ----
    $maxsize = 10 * 1024 * 1024;
    if (strlen($filedata) > $maxsize) {
        return [
            'success'      => false,
            'message'      => 'File too large. Maximum 10MB allowed',
            'submissionid' => 0,
            'fileurl'      => '',
        ];
    }

    // ---- Save to Moodle file storage ----
    $context   = \context_user::instance($params['userid']);
    $fs        = get_file_storage();
    $itemid    = time(); // unique itemid per upload

    // Clean filename
    $cleanfilename = clean_filename($params['filename']);
    if (empty($cleanfilename)) {
        $ext = self::get_extension_from_mime($params['filetype']);
        $cleanfilename = 'certificate_' . time() . '.' . $ext;
    }

    // Delete any old draft with same name
    $fs->delete_area_files(
        $context->id,
        'local_rapmobile',
        'submission',
        $itemid
    );

    // Create file record
    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'local_rapmobile',
        'filearea'  => 'submission',
        'itemid'    => $itemid,
        'filepath'  => '/',
        'filename'  => $cleanfilename,
        'mimetype'  => $params['filetype'],
        'userid'    => $params['userid'],
        'timecreated'  => time(),
        'timemodified' => time(),
    ];

    $storedfile = $fs->create_file_from_string($fileinfo, $filedata);

    if (!$storedfile) {
        return [
            'success'      => false,
            'message'      => 'Failed to save file',
            'submissionid' => 0,
            'fileurl'      => '',
        ];
    }

    // ---- Create submission record ----
    $submission               = new \stdClass();
    $submission->userid       = $params['userid'];
    $submission->title        = trim($params['title']);
    $submission->description  = trim($params['description']);
    $submission->categoryid   = $params['categoryid'];
    $submission->fileid       = $storedfile->get_id();
    $submission->filename     = $cleanfilename;
    $submission->filetype     = $params['filetype'];
    $submission->status       = 0; // 0=Pending, 1=Approved, 2=Rejected
    $submission->timecreated  = time();
    $submission->timemodified = time();

    $submissionid = $DB->insert_record('halloffame_submissions', $submission);

    if (!$submissionid) {
        return [
            'success'      => false,
            'message'      => 'Failed to save submission',
            'submissionid' => 0,
            'fileurl'      => '',
        ];
    }

    // ---- Build file URL ----
    $fileurl = \moodle_url::make_pluginfile_url(
        $context->id,
         'local_rapmobile',
        'submission',
        $itemid,
        '/',
        $cleanfilename
    )->out(false);

    // ---- Notify admin (optional) ----
    self::notify_halloffame_admin($params['userid'], $submissionid, $params['title']);

    return [
        'success'      => true,
        'message'      => 'Certificate submitted successfully! Pending admin review.',
        'submissionid' => (int)$submissionid,
        'fileurl'      => (string)$fileurl,
    ];
}

public static function upload_halloffame_certificate_returns() {
    return new external_single_structure([
        'success'      => new external_value(PARAM_BOOL, 'Upload successful'),
        'message'      => new external_value(PARAM_TEXT, 'Result message'),
        'submissionid' => new external_value(PARAM_INT,  'New submission ID'),
        'fileurl'      => new external_value(PARAM_RAW,  'Uploaded file URL'),
    ]);
}

// =========================================================
// 4. DELETE MY SUBMISSION (if pending — not yet approved)
// =========================================================

public static function delete_halloffame_submission_parameters() {
    return new external_function_parameters([
        'userid'       => new external_value(PARAM_INT, 'User ID'),
        'submissionid' => new external_value(PARAM_INT, 'Submission ID to delete'),
    ]);
}

public static function delete_halloffame_submission($userid, $submissionid) {
    global $DB;

    $params = self::validate_parameters(
        self::delete_halloffame_submission_parameters(),
        ['userid' => $userid, 'submissionid' => $submissionid]
    );

    // Find submission — must belong to this user
    $sub = $DB->get_record('halloffame_submissions', [
        'id'     => $params['submissionid'],
        'userid' => $params['userid'],
    ]);

    if (!$sub) {
        return ['success' => false, 'message' => 'Submission not found'];
    }

    // Cannot delete approved submissions
    if ((int)$sub->status === 1) {
        return ['success' => false, 'message' => 'Cannot delete an approved submission'];
    }

    // Delete file from storage
    if (!empty($sub->fileid)) {
        $fs   = get_file_storage();
        $file = $fs->get_file_by_id($sub->fileid);
        if ($file) $file->delete();
    }

    // Delete record
    $DB->delete_records('halloffame_submissions', ['id' => $params['submissionid']]);

    return ['success' => true, 'message' => 'Submission deleted successfully'];
}

public static function delete_halloffame_submission_returns() {
    return new external_single_structure([
        'success' => new external_value(PARAM_BOOL, 'Deletion successful'),
        'message' => new external_value(PARAM_TEXT, 'Result message'),
    ]);
}

// =========================================================
// PRIVATE HELPERS
// =========================================================

private static function get_extension_from_mime($mime) {
    $map = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/jpg'       => 'jpg',
        'image/png'       => 'png',
    ];
    return $map[$mime] ?? 'pdf';
}

private static function notify_halloffame_admin($userid, $submissionid, $title) {
    global $DB, $CFG;
    try {
        $admin = get_admin();
        $user  = $DB->get_record('user', ['id' => $userid]);
        if (!$admin || !$user) return;

        $message = new \core\message\message();
        $message->component         = 'local_rapmobile';
        $message->name              = 'submission_notify';
        $message->userfrom          = $user;
        $message->userto            = $admin;
        $message->subject           = 'New Hall of Fame Certificate Submission';
        $message->fullmessage       = fullname($user)
            . ' submitted a new certificate: "' . $title . '" (ID: ' . $submissionid . ')';
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml   = '<p>' . fullname($user)
            . ' submitted: <strong>' . $title . '</strong></p>';
        $message->smallmessage      = fullname($user) . ' submitted: ' . $title;
        $message->notification      = 1;

        message_send($message);
    } catch (\Exception $e) {
        // Notification failure should not block the upload
    }
}
// =========================================================
// ░░ SKILLGAP — TEAM ANALYTICS ░░
// =========================================================

public static function get_team_analytics_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'Manager user ID'),
        'deptid' => new external_value(PARAM_INT, 'Dept filter (0=all)',
            VALUE_DEFAULT, 0),
        'compid' => new external_value(PARAM_INT, 'Comp filter (0=all)',
            VALUE_DEFAULT, 0),
    ]);
}

public static function get_team_analytics($userid, $deptid = 0, $compid = 0) {
    global $DB,$CFG;
 require_once($CFG->dirroot . '/local/skillgap/lib.php');
    $params = self::validate_parameters(
        self::get_team_analytics_parameters(),
        ['userid' => $userid, 'deptid' => $deptid, 'compid' => $compid]
    );

    $data = \local_skillgap\local\helper::manager_dashboard_data(
        $params['userid'],
        ['deptid' => $params['deptid'], 'compid' => $params['compid']]
    );

    if (!empty($data['no_team'])) {
        return [
            'manager_name' => $data['fullname'], 'team_count' => 0, 'team_readiness' => 0,
            'most_failing' => '-', 'most_improved' => '-',
            'comp_performance' => [], 'members' => [],
        ];
    }

    $comp_performance = array_map(function ($c) {
        return [
            'compid' => $c['compid'], 'name' => $c['compname'], 'avg_pct' => $c['avg_pct'],
            'total' => $c['total_users'], 'competent_count' => $c['competent'],
            'non_count' => $c['non_competent'],
            'label' => "Total: {$c['total_users']}  Competent: {$c['competent']}  Non: {$c['non_competent']}",
        ];
    }, $data['comp_report']);

    $members = array_map(function ($m) {
        return [
            'userid' => $m['userid'], 'fullname' => $m['fullname'],
            'department' => $m['department'] ?? '', 'avg_pct' => $m['avg_score'],
            'status' => strtolower(str_replace(' ', '_', $m['status_label'])),
            'status_label' => $m['status_label'],
            'competencies' => array_map(function ($mc) {
                return [
                    'name' => $mc['compname'], 'pct' => 0, 'score_label' => $mc['score'],
                    'status' => strtolower(str_replace(' ', '_', $mc['status_label'])),
                    'status_label' => $mc['status_label'],
                    'is_competent' => $mc['badge_class'] === 'success',
                ];
            }, $m['member_comps']),
        ];
    }, $data['team_members']);

    return [
        'manager_name' => $data['fullname'], 'team_count' => $data['team_count'],
        'team_readiness' => $data['team_readiness'], 'most_failing' => $data['most_failing'],
        'most_improved' => $data['most_improved'], 'comp_performance' => $comp_performance,
        'members' => $members,
    ];
}

public static function get_team_analytics_returns() {
    return new external_single_structure([
        'manager_name'   => new external_value(PARAM_TEXT, 'Manager name'),
        'team_count'     => new external_value(PARAM_INT,  '2 → TEAM MEMBERS'),
        'team_readiness' => new external_value(PARAM_INT,  '80 → 80% TEAM READINESS'),
        'most_failing'   => new external_value(PARAM_TEXT, 'Most failing competency name'),
        'most_improved'  => new external_value(PARAM_TEXT, 'Most improved competency name'),
        'comp_performance' => new external_multiple_structure(
            new external_single_structure([
                'compid'          => new external_value(PARAM_INT,  'Comp ID'),
                'name'            => new external_value(PARAM_TEXT, 'Analytical Thinking'),
                'avg_pct'         => new external_value(PARAM_INT,  '100%'),
                'total'           => new external_value(PARAM_INT,  'Total: 1'),
                'competent_count' => new external_value(PARAM_INT,  'Competent: 1'),
                'non_count'       => new external_value(PARAM_INT,  'Non: 0'),
                'label'           => new external_value(PARAM_TEXT, 'Total: 1  Competent: 1  Non: 0'),
            ])
        ),
        'members' => new external_multiple_structure(
            new external_single_structure([
                'userid'       => new external_value(PARAM_INT,  'User ID'),
                'fullname'     => new external_value(PARAM_TEXT, 'Pankaj Kichloo'),
                'department'   => new external_value(PARAM_TEXT, 'Department'),
                'avg_pct'      => new external_value(PARAM_INT,  '60%'),
                'status'       => new external_value(PARAM_TEXT, 'moderate'),
                'status_label' => new external_value(PARAM_TEXT, 'Moderate'),
                'competencies' => new external_multiple_structure(
                    new external_single_structure([
                        'name'         => new external_value(PARAM_TEXT,  'Comp name'),
                        'pct'          => new external_value(PARAM_INT,   'Pct'),
                        'score_label'  => new external_value(PARAM_TEXT,  '5/5'),
                        'status'       => new external_value(PARAM_TEXT,  'competent'),
                        'status_label' => new external_value(PARAM_TEXT,  'Competent'),
                        'is_competent' => new external_value(PARAM_BOOL,  'Is competent'),
                    ])
                ),
            ])
        ),
    ]);
}
// =========================================================
// PRIVATE — Resolve restrictions from the RAW availability JSON.
// {course_modules}.availability is static, so this works regardless of
// which user's token calls the API — unlike cm_info::availableinfo,
// which Moodle leaves empty for anyone able to bypass restrictions.
// =========================================================
private static function rapmobile_get_restrictions($cm, $modinfo) {
    global $DB;

    $raw = $cm->availability ?? null;
    if (empty($raw)) {
        $raw = $DB->get_field('course_modules', 'availability', ['id' => $cm->id]);
    }
    if (empty($raw)) {
        return ['raw' => '', 'reqs' => []];
    }

    $tree = json_decode($raw, true);
    if (!is_array($tree)) {
        return ['raw' => (string)$raw, 'reqs' => []];
    }

    $reqs = [];
    $seen = [];

    // completionstate values on a 'completion' condition node.
    $statelabels = [
        0 => 'not marked complete',
        1 => 'marked complete',
        2 => 'complete and passed',
        3 => 'complete and failed',
    ];

    $walk = function($node) use (&$walk, &$reqs, &$seen, $modinfo, $DB, $statelabels) {
        if (!is_array($node)) {
            return;
        }
        if (!empty($node['c']) && is_array($node['c'])) {
            foreach ($node['c'] as $child) {
                $walk($child);
            }
        }
        if (($node['type'] ?? '') !== 'completion' || empty($node['cm'])) {
            return;
        }

        $depcmid = (int)$node['cm'];
        if (isset($seen[$depcmid])) {
            return;
        }
        $seen[$depcmid] = true;

        $name = $modname = $viewurl = '';

        try {
            $depcm   = $modinfo->get_cm($depcmid);
            $name    = (string)$depcm->name;
            $modname = (string)$depcm->modname;
            $viewurl = $depcm->url ? $depcm->url->out(false) : '';
        } catch (\Exception $e) {
            // Dependency in a hidden/stealth section — fall back to the DB
            // so the name still reaches the app.
            $rec = $DB->get_record_sql(
                "SELECT cm.id, cm.instance, m.name AS modname
                   FROM {course_modules} cm
                   JOIN {modules} m ON m.id = cm.module
                  WHERE cm.id = :cmid",
                ['cmid' => $depcmid]
            );
            if ($rec) {
                $modname = (string)$rec->modname;
                $name    = (string)$DB->get_field($rec->modname, 'name', ['id' => $rec->instance]);
            }
        }

        if ($name === '') {
            return; // dependency deleted
        }

        $state = (int)($node['e'] ?? 1);

        $reqs[] = [
            'cmid'         => $depcmid,
            'name'         => format_string($name),
            'modname'      => $modname,
            'viewurl'      => $viewurl,
            'requirestate' => $state,
            'requirelabel' => $statelabels[$state] ?? 'marked complete',
        ];
    };

    $walk($tree);

    return ['raw' => (string)$raw, 'reqs' => $reqs];
}
// =========================================================
// GET LIVE SESSION DETAILS — upcoming zoom/googlemeet/facetoface/ilt
// sessions for a user, across all their enrolled courses.
// Add to local/rapmobile/externallib.php, inside local_rapmobile_external.
// =========================================================

public static function get_livesession_detail_parameters() {
    return new external_function_parameters([
        'userid' => new external_value(PARAM_INT, 'User id'),
    ]);
}

public static function get_livesession_detail($userid) {
    global $DB, $CFG;

    $params = self::validate_parameters(
        self::get_livesession_detail_parameters(),
        ['userid' => $userid]
    );
    $userid = $params['userid'];

    $currentdate         = strtotime(date('d-m-Y'));
    $fivedaysbeforedate  = strtotime(date('d-m-Y', strtotime('+15 days', $currentdate)));

    // ---- All courses this user is enrolled in ----
    $course_modules = $DB->get_records_sql(
        "SELECT e.courseid, ue.userid FROM {enrol} e 
         INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid
         WHERE ue.userid = :userid",
        ['userid' => $userid]
    );

    $userdetail = $DB->get_record('user', ['id' => $userid]);
    $dataget    = [];

    foreach ($course_modules as $keyvalue) {

        // ---- Upcoming live sessions in this course within the next 15 days ----
        $instances = $DB->get_records_sql(
            "SELECT e.*, cm.id as cmid, cm.module FROM {event} e 
             INNER JOIN {course_modules} cm ON cm.course = e.courseid 
             WHERE e.courseid = :courseid 
               AND e.modulename IN ('zoom','googlemeet','facetoface','ilt') 
               AND e.timestart BETWEEN :startdate AND :enddate
             ORDER BY e.timestart ASC",
            [
                'courseid'  => $keyvalue->courseid,
                'startdate' => $currentdate,
                'enddate'   => $fivedaysbeforedate,
            ]
        );

        foreach ($instances as $value) {
            $modulesname = $DB->get_record('modules', ['name' => $value->modulename]);
            if (!$modulesname) {
                continue; // module type not installed on this site
            }

            $getact = $DB->get_record('course_modules', [
                'course'   => $value->courseid,
                'instance' => $value->instance,
                'module'   => $modulesname->id,
            ]);
            if (!$getact) {
                continue;
            }

            $sessionmod = in_array($value->modulename, ['facetoface', 'ilt']) ? 'Offline' : 'Online';

            $record = null;
            $imgurl = '';

            switch ($value->modulename) {
                case 'zoom':
                    $record = $DB->get_record('zoom', ['id' => $value->instance]);
                    $imgurl = $CFG->wwwroot . '/theme/remui/pix/zoom-demo.png';
                    break;
                case 'googlemeet':
                    $record = $DB->get_record('googlemeet', ['id' => $value->instance]);
                    $imgurl = $CFG->wwwroot . '/theme/remui/pix/g-meet.png';
                    break;
                case 'facetoface':
                    $record = $DB->get_record('facetoface', ['id' => $value->instance]);
                    $imgurl = $CFG->wwwroot . '/theme/remui/pix/ilt.png';
                    break;
                case 'ilt':
                    $record = $DB->get_record('ilt', ['id' => $value->instance]);
                    $imgurl = $CFG->wwwroot . '/theme/remui/pix/ilt.png';
                    break;
            }

            if (!$record) {
                continue; // instance row missing/deleted — skip safely
            }

            $dataget[] = [
                'activityname' => (string)$record->name,
                'starttime'    => date('d-M-Y, H:i a', $value->timestart),
                'url'          => $CFG->wwwroot . '/mod/' . $value->modulename . '/view.php?id=' . $getact->id,
                'username'     => trim($userdetail->firstname . ' ' . $userdetail->lastname),
                'sessionmod'   => $sessionmod,
                'imgurl'       => $imgurl,
            ];
        }
    }

    // Returned as a JSON string inside 'livesessiondetail' — matches your
    // original response contract. Client must json_decode() this field.
    return [
        'livesessiondetail' => json_encode($dataget),
    ];
}

public static function get_livesession_detail_returns() {
    return new external_single_structure([
        'livesessiondetail' => new external_value(PARAM_RAW, 'JSON-encoded array of upcoming live sessions'),
    ]);
}
// =========================================================
// GET COURSE CONTENTS WITH DOWNLOAD URLS
// Wrapper around core_course_get_contents
// Adds: fileurl with token, downloadurl, type, icon per module
// Add to local/rapmobile/externallib.php
// Created by Omkar
// =========================================================

public static function get_course_contents_parameters() {
    return new external_function_parameters([
        'courseid' => new external_value(PARAM_INT,  'Course ID'),
        'userid'   => new external_value(PARAM_INT,  'User ID — for completion status'),
        'token'    => new external_value(PARAM_TEXT, 'User wstoken — appended to file URLs for direct download'),
    ]);
}

public static function get_course_contents($courseid, $userid, $token) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/course/lib.php');
    require_once($CFG->libdir  . '/completionlib.php');

    $params = self::validate_parameters(
        self::get_course_contents_parameters(),
        ['courseid' => $courseid, 'userid' => $userid, 'token' => $token]
    );

    // ---- Get course ----
    $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
// ---- Course-level custom field: Intro Video URL (RemUI custom fields) ----
$courseintrovideo = self::get_course_customfield_value($course->id, 'video');
    // ---- Get modinfo ----
    $modinfo    = get_fast_modinfo($course, $params['userid']);
    $completion = new \completion_info($course);

    $sections_output = [];

    foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {

        // Skip hidden sections
        if (!$section->uservisible) continue;

        $modules_output = [];

        $cms = $modinfo->sections[$sectionnum] ?? [];

        foreach ($cms as $cmid) {
            $cm = $modinfo->cms[$cmid];

          if ($cm->deletioninprogress) continue;

            // Raw availability survives even when Moodle suppresses
            // uservisible/availableinfo for a "hide entirely" restriction.
            $rawavail = (string)($cm->availability ?? '');

            // Original guard dropped restricted-and-hidden modules outright.
            // Keep them so the app can render a lock card — plain hidden
            // activities (no restriction at all) are still skipped.
            if (!$cm->uservisible && empty($cm->availableinfo) && $rawavail === '') continue;

            // ---- Completion state + full completiondata (matching core's shape) ----
            $completionstate = 0;
            $timecompleted   = 0;
            $completiondata_out = null;

            if ($cm->completion) {
                $compdata = $completion->get_data($cm, false, $params['userid']);
                $completionstate = (int)($compdata->completionstate ?? 0);
                $timecompleted   = (int)($compdata->timemodified ?? 0);

                $details = [];
if (class_exists('\core_completion\cm_completion_details')) {
    try {
        $cmcompletion = \core_completion\cm_completion_details::get_instance(
            $cm, $params['userid'], true
        );

        if (method_exists($cmcompletion, 'get_details')) {
            $raw = $cmcompletion->get_details();

            foreach ($raw as $key => $rule) {
                $rule = (array)$rule;

                if (!empty($rule['rulename']) || array_key_exists('rulevalue', $rule)) {
                    // Shape A: [{rulename: ..., rulevalue: {status, description}}, ...]
                    $rulevalue = (array)($rule['rulevalue'] ?? []);
                    $details[] = [
                        'rulename'  => (string)($rule['rulename'] ?? ''),
                        'rulevalue' => [
                            'status'      => (int)($rulevalue['status'] ?? 0),
                            'description' => (string)($rulevalue['description'] ?? ''),
                        ],
                    ];
                } elseif (is_string($key) && (array_key_exists('status', $rule) || array_key_exists('description', $rule))) {
                    // Shape B: keyed by rulename => {status, description}
                    $details[] = [
                        'rulename'  => (string)$key,
                        'rulevalue' => [
                            'status'      => (int)($rule['status'] ?? 0),
                            'description' => (string)($rule['description'] ?? ''),
                        ],
                    ];
                }
            }
        }

        $isoverallcomplete = method_exists($cmcompletion, 'is_overall_complete')
            ? (bool)$cmcompletion->is_overall_complete()
            : ($completionstate > 0);

    } catch (\Throwable $e) {
        $isoverallcomplete = ($completionstate > 0);
    }
} else {
    $isoverallcomplete = ($completionstate > 0);
}

                $completiondata_out = [
                    'state'             => $completionstate,
                    'timecompleted'     => $timecompleted,
                    'overrideby'        => (int)($compdata->overrideby ?? 0),
                    'valueused'         => (bool)($compdata->valueused ?? false),
                    'hascompletion'     => true,
                    'isautomatic'       => ((int)$cm->completion === COMPLETION_TRACKING_AUTOMATIC),
                    'istrackeduser'     => true,
                    'uservisible'       => (bool)$cm->uservisible,
                    'details'           => $details,
                    'isoverallcomplete' => $isoverallcomplete,
                ];
            }

         // ---- Availability/restriction info ----
            $restrict         = self::rapmobile_get_restrictions($cm, $modinfo);
            $availabilityreqs = $restrict['reqs'];
            $availabilityraw  = $restrict['raw'];
            $availabilityinfo = null;

            // 1st choice: Moodle's own sentence, with <AVAILABILITY_CMNAME_n/>
            // placeholders swapped for real names (core does this at render
            // time; the cached string alone has the name missing).
            if (!empty($cm->availableinfo)) {
                try {
                    $formatted = \core_availability\info::format_info($cm->availableinfo, $course);
                    $availabilityinfo = trim(html_entity_decode(strip_tags($formatted), ENT_QUOTES, 'UTF-8'));
                } catch (\Exception $e) {
                    $availabilityinfo = null;
                }
            }

            // 2nd choice: build the sentence ourselves. availableinfo is empty
            // whenever the calling user can bypass restrictions, so without
            // this the app gets nothing even though a student would be blocked.
            if (empty($availabilityinfo) && !empty($availabilityreqs)) {
                $bits = [];
                foreach ($availabilityreqs as $r) {
                    $bits[] = 'The activity ' . $r['name'] . ' is ' . $r['requirelabel'];
                }
                $availabilityinfo = 'Not available unless: ' . implode(' and ', $bits);
            }
            // ---- Module type classification ----
            $modtype = self::classify_module($cm->modname);

            // ---- Module icon ----
            $modicon = $CFG->wwwroot
                . '/theme/image.php/remui/'
                . $cm->modname . '/1/monologo';

            // ---- View URL ----
            $viewurl = $cm->url ? $cm->url->out(false) : '';

            // ---- Get files with download URL ----
            $files = self::get_module_files(
                $cm,
                $course,
                $params['token'],
                $params['userid']
            );

            // ---- Extra data per module type ----
            $extra = self::get_module_extra_data(
                $cm,
                $course,
                $params['token'],
                $params['userid']
            );

            $module_entry = [
                // Core fields
                'id'               => (int)$cm->id,
                'instance'         => (int)$cm->instance,
                'name'             => (string)$cm->name,
                'modname'          => (string)$cm->modname,
                'modtype'          => (string)$modtype,
                'modicon'          => (string)$modicon,
                'visible'          => (int)$cm->visible,
                'uservisible'      => (bool)$cm->uservisible,
                'availabilityinfo' => html_entity_decode(format_string($availabilityinfo), ENT_QUOTES, 'UTF-8'),
                'isrestricted'              => !empty($availabilityreqs),
                'availability_requirements' => $availabilityreqs,
                'availabilityraw'           => $availabilityraw,
                'viewurl'          => (string)$viewurl,

                // Completion
                'completion'      => (int)$cm->completion,
                'timecompleted'   => $timecompleted,
                'iscomplete'      => ($completionstate > 0),

                // Files with download URLs
                'files'           => $files,
                'hasfiles'        => count($files) > 0,

                // Extra data (video URL, quiz info, etc)
                'extra'           => $extra,
            ];

            // Only include completiondata when it actually exists — omitting
            // the key (rather than setting it to null) avoids the
            // "Only arrays/objects accepted" validator error.
            if ($completiondata_out !== null) {
                $module_entry['completiondata'] = $completiondata_out;
            }

            $modules_output[] = $module_entry;
        }

        $sections_output[] = [
            'id'      => (int)$section->id,
            'section' => (int)$sectionnum,
            'name'    => html_entity_decode(format_string($section->name ?? 'Section ' . $sectionnum), ENT_QUOTES),
            'visible' => (int)$section->visible,
            'summary' => strip_tags($section->summary ?? ''),
            'modules' => $modules_output,
        ];
    }

    // Overall course progress
    $progress = (int) (
    \core_completion\progress::get_course_progress_percentage(
        $course,
        $USER->id
    ) ?? 0
);

    return [
        'courseid'      => (int)$params['courseid'],
        'coursename'    => $course->fullname,
        'courseintrovideo'  => $courseintrovideo, 
        'progress'      => $progress,
        'sections'      => $sections_output,
        'totalsections' => count($sections_output),
    ];
}

public static function get_course_contents_returns() {
    return new external_single_structure([
        'courseid'      => new external_value(PARAM_INT,  'Course ID'),
        'coursename'    => new external_value(PARAM_TEXT, 'Course name'),
        'courseintrovideo'  => new external_value(PARAM_RAW,  'Course-level intro video URL, empty if not set'),
        'progress'      => new external_value(PARAM_INT,  'Overall progress %'),
        'totalsections' => new external_value(PARAM_INT,  'Total sections'),
        'sections'      => new external_multiple_structure(
            new external_single_structure([
                'id'      => new external_value(PARAM_INT,  'Section ID'),
                'section' => new external_value(PARAM_INT,  'Section number'),
                'name'    => new external_value(PARAM_TEXT, 'Section name'),
                'visible' => new external_value(PARAM_INT,  'Is visible'),
                'summary' => new external_value(PARAM_TEXT, 'Section summary'),
                'modules' => new external_multiple_structure(
                    new external_single_structure([
                        // Core
                        'id'               => new external_value(PARAM_INT,  'Course module ID (cmid)'),
                        'instance'         => new external_value(PARAM_INT,  'Module instance ID'),
                        'name'             => new external_value(PARAM_TEXT, 'Module name'),
                        'modname'          => new external_value(PARAM_TEXT, 'Module type: quiz/assign/resource etc'),
                        'modtype'          => new external_value(PARAM_TEXT, 'Classified type: video/pdf/quiz/live/assignment/other'),
                        'modicon'          => new external_value(PARAM_RAW,  'Module icon URL'),
                        'visible'          => new external_value(PARAM_INT,  'Visible'),
                        'uservisible'      => new external_value(PARAM_BOOL, 'Visible to this user'),
                        'availabilityinfo' => new external_value(PARAM_RAW,  'Restriction/availability info text', VALUE_OPTIONAL, null),
                        'isrestricted'    => new external_value(PARAM_BOOL, 'Has a completion restriction'),
                        'availabilityraw' => new external_value(PARAM_RAW,  'Raw availability JSON — temporary, for debugging', VALUE_OPTIONAL, ''),
                        'availability_requirements' => new external_multiple_structure(
                            new external_single_structure([
                                'cmid'         => new external_value(PARAM_INT,  'cmid that must be completed first'),
                                'name'         => new external_value(PARAM_TEXT, 'Show this name in the lock message'),
                                'modname'      => new external_value(PARAM_TEXT, 'Module type'),
                                'viewurl'      => new external_value(PARAM_RAW,  'Deep-link target'),
                                'requirestate' => new external_value(PARAM_INT,  '0=incomplete 1=complete 2=pass 3=fail'),
                                'requirelabel' => new external_value(PARAM_TEXT, 'marked complete / complete and passed'),
                            ]),
                            'Blocking activities', VALUE_DEFAULT, []
                        ),
                        'viewurl'          => new external_value(PARAM_RAW,  'Web view URL'),

                        // Completion
                        'completion'      => new external_value(PARAM_INT,  '0=none 1=manual 2=auto'),
                        'timecompleted'   => new external_value(PARAM_INT,  'Unix timestamp completed'),
                        'iscomplete'      => new external_value(PARAM_BOOL, 'Is activity complete?'),

                        'completiondata'  => new external_single_structure([
                            'state'             => new external_value(PARAM_INT,  'Completion state'),
                            'timecompleted'     => new external_value(PARAM_INT,  'Time completed'),
                            'overrideby'        => new external_value(PARAM_INT,  'Override by user', VALUE_OPTIONAL, 0),
                            'valueused'         => new external_value(PARAM_BOOL, 'Value used'),
                            'hascompletion'     => new external_value(PARAM_BOOL, 'Has completion'),
                            'isautomatic'       => new external_value(PARAM_BOOL, 'Is automatic'),
                            'istrackeduser'     => new external_value(PARAM_BOOL, 'Is tracked user'),
                            'uservisible'       => new external_value(PARAM_BOOL, 'User visible'),
                            'details'           => new external_multiple_structure(
                                new external_single_structure([
                                    'rulename'  => new external_value(PARAM_TEXT, 'Rule name'),
                                    'rulevalue' => new external_single_structure([
                                        'status'      => new external_value(PARAM_INT,  'Status'),
                                        'description' => new external_value(PARAM_TEXT, 'Description'),
                                    ]),
                                ])
                            ),
                            'isoverallcomplete' => new external_value(PARAM_BOOL, 'Is overall complete'),
                        ], 'Completion data', VALUE_OPTIONAL, null),

                        // Files
                        'hasfiles'        => new external_value(PARAM_BOOL, 'Has downloadable files?'),
                        'files'           => new external_multiple_structure(
                            new external_single_structure([
                                'filename'    => new external_value(PARAM_TEXT, 'File name'),
                                'filesize'    => new external_value(PARAM_INT,  'File size bytes'),
                                'mimetype'    => new external_value(PARAM_TEXT, 'MIME type'),
                                'fileurl'     => new external_value(PARAM_RAW,  'File URL with token — use directly in app'),
                                'downloadurl' => new external_value(PARAM_RAW,  'Direct download URL with token'),
                                'timemodified'=> new external_value(PARAM_INT,  'Last modified'),
                                'isimage'     => new external_value(PARAM_BOOL, 'Is image file?'),
                                'ispdf'       => new external_value(PARAM_BOOL, 'Is PDF?'),
                                'isvideo'     => new external_value(PARAM_BOOL, 'Is video?'),
                            ])
                        ),

                        // Extra data per type
                        'extra'           => new external_single_structure([
                            'externalurl'  => new external_value(PARAM_RAW,  'External URL (mod_url)', VALUE_OPTIONAL),
                            'content'      => new external_value(PARAM_RAW,  'Page HTML content (mod_page)', VALUE_OPTIONAL),
                            'vimeo_url'    => new external_value(PARAM_RAW,  'Vimeo URL (mod_videotime)', VALUE_OPTIONAL),
                            'join_url'     => new external_value(PARAM_RAW,  'Join URL (Zoom/BBB/Meet)', VALUE_OPTIONAL),
                            'quizid'       => new external_value(PARAM_INT,  'Quiz ID', VALUE_OPTIONAL),
                            'timelimit'    => new external_value(PARAM_INT,  'Quiz time limit seconds', VALUE_OPTIONAL),
                            'attempts'     => new external_value(PARAM_INT,  'Quiz attempts allowed', VALUE_OPTIONAL),
                        ], 'Module type specific data', VALUE_OPTIONAL),
                    ])
                ),
            ])
        ),
    ]);
}

// =========================================================
// PRIVATE — Classify module type for mobile UI
// =========================================================
private static function classify_module($modname) {
    $types = [
        // Video
        'supervideo'  => 'video',
        'videotime'   => 'video',
        'kaltura'     => 'video',

        // PDF / Document
        'resource'    => 'pdf',
        'pdfjsfolder' => 'pdf',
        'folder'      => 'pdf',

        // Quiz / Assessment
        'quiz'        => 'quiz',
        'assign'      => 'assignment',
        'scorm'       => 'scorm',
        'h5pactivity' => 'interactive',

        // Live / Virtual
        'zoom'            => 'live',
        'bigbluebuttonbn' => 'live',
        'ilt'             => 'live',
        'googlemeet'      => 'live',
        'airoleplay'      => 'roleplay',

        // Certificate
        'customcert'        => 'certificate',
        'iomadcertificate'  => 'certificate',

        // Page / URL
        'page'   => 'page',
        'url'    => 'url',
        'label'  => 'label',
        'goone'  => 'external',

        // Forum
        'forum'  => 'forum',

        // Choice / Poll
        'choice' => 'poll',

        // Feedback
        'feedback' => 'feedback',
    ];
    return $types[$modname] ?? 'other';
}
// =========================================================
// PRIVATE — Fetch a course-level custom field value by matching on
// the field's name/label (case-insensitive substring), since we don't
// have the exact shortname confirmed for this RemUI custom field.
// =========================================================
private static function get_course_customfield_value($courseid, $namematch) {
    global $DB;

    $dbman = $DB->get_manager();
    if (!$dbman->table_exists('customfield_data') || !$dbman->table_exists('customfield_field')) {
        return '';
    }

    $row = $DB->get_record_sql(
        "SELECT cd.value, cd.intvalue, cf.name, cf.shortname, cf.type
           FROM {customfield_data} cd
           JOIN {customfield_field} cf ON cf.id = cd.fieldid
           JOIN {customfield_category} cc ON cc.id = cf.categoryid
          WHERE cd.instanceid = :courseid
            AND cc.component = 'core_course'
            AND cc.area = 'course'
            AND " . $DB->sql_like('cf.name', ':namematch', false) . "
          LIMIT 1",
        ['courseid' => $courseid, 'namematch' => '%' . $DB->sql_like_escape($namematch) . '%']
    );

    if (!$row) {
        return '';
    }

    // Text-type custom fields store their value in the 'value' column.
    return trim((string)($row->value ?? ''));
}
// =========================================================
// PRIVATE — Get files with token-based download URLs
// =========================================================
private static function get_module_files($cm, $course, $token, $userid) {
    global $CFG;

    // pdfjsfolder needs special handling — multiple files, unknown filearea,
    // served through mobile/pluginfile.php rather than the generic
    // webservice/pluginfile.php pattern the other modules use.
    if ($cm->modname === 'pdfjsfolder') {
        return self::get_pdfjsfolder_files($cm, $token);
    }
     if ($cm->modname === 'supervideo') {
        return self::get_supervideo_files($cm, $token);
    }
    if ($cm->modname === 'videotime') {
        return self::get_videotime_files($cm, $token);
    }
    if ($cm->modname === 'page') {
    return self::get_page_files($cm, $token);
}
    // Modules that have downloadable files
    $file_modules = [
        'resource', 'folder',
        'assign', 'scorm',
        'h5pactivity', 'page',
    ];

    if (!in_array($cm->modname, $file_modules)) {
        return [];
    }

    $context = \context_module::instance($cm->id);
    $fs      = get_file_storage();

    $fileareas = [
        'resource'    => [['mod_resource', 'content', 0]],
        'folder'      => [['mod_folder', 'content', 0]],
        'assign'      => [['mod_assign', 'introattachment', 0]],
        'scorm'       => [['mod_scorm', 'package', 0]],
        'h5pactivity' => [['mod_h5pactivity', 'package', 0]],
        'page'        => [['mod_page', 'content', 0]],
        'supervideo'  => [['mod_supervideo', 'content', 0]],
    ];

    $areas  = $fileareas[$cm->modname] ?? [[$cm->modname, 'content', 0]];
    $output = [];

    foreach ($areas as [$component, $filearea, $itemid]) {
        $files = $fs->get_area_files(
            $context->id, $component, $filearea, $itemid, 'filename ASC', false
        );

        foreach ($files as $file) {
            $filename = $file->get_filename();
            $mimetype = $file->get_mimetype();

            $fileurl = \moodle_url::make_webservice_pluginfile_url(
                $file->get_contextid(), $file->get_component(), $file->get_filearea(),
                $file->get_itemid(), $file->get_filepath(), $filename
            )->out(false);

            $output[] = [
                'filename'     => $filename,
                'filesize'     => (int)$file->get_filesize(),
                'mimetype'     => $mimetype,
                'fileurl'      => $fileurl . '?token=' . $token,
                'downloadurl'  => $fileurl . '?token=' . $token . '&forcedownload=1',
                'timemodified' => (int)$file->get_timemodified(),
                'isimage'      => (bool)strstr($mimetype, 'image/'),
                'ispdf'        => ($mimetype === 'application/pdf'),
                'isvideo'      => (bool)strstr($mimetype, 'video/'),
            ];
        }
    }

    return $output;
}
private static function get_page_files($cm, $token) {
    global $DB, $CFG;

    $page = $DB->get_record('page', ['id' => $cm->instance], 'revision');
    if (!$page) return [];

    $context = \context_module::instance($cm->id);
    $fs      = get_file_storage();
    $files   = $fs->get_area_files($context->id, 'mod_page', 'content', $page->revision, 'filename ASC', false);

    $output = [];
    foreach ($files as $file) {
        if ($file->is_directory()) continue;
        $filename = $file->get_filename();
        $mimetype = $file->get_mimetype();

        $fileurl = \moodle_url::make_webservice_pluginfile_url(
            $file->get_contextid(), $file->get_component(), $file->get_filearea(),
            $file->get_itemid(), $file->get_filepath(), $filename
        )->out(false);

        $output[] = [
            'filename'     => $filename,
            'filesize'     => (int)$file->get_filesize(),
            'mimetype'     => $mimetype,
            'fileurl'      => $fileurl . '?token=' . $token,
            'downloadurl'  => $fileurl . '?token=' . $token . '&forcedownload=1',
            'timemodified' => (int)$file->get_timemodified(),
            'isimage'      => (bool)strstr($mimetype, 'image/'),
            'ispdf'        => ($mimetype === 'application/pdf'),
            'isvideo'      => (bool)strstr($mimetype, 'video/'),
        ];
    }
    return $output;
}
private static function get_supervideo_files($cm, $token) {
    global $DB, $CFG;

    $sv = $DB->get_record('supervideo', ['id' => $cm->instance]);
    if (!$sv || ($sv->origem ?? '') !== 'upload') {
        return []; // external link (youtube/vimeo) — no file to serve
    }

    $context = \context_module::instance($cm->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_supervideo', 'content', false, 'filename ASC', false);

    $output = [];
    foreach ($files as $file) {
        $filename = $file->get_filename();
        $mimetype = $file->get_mimetype();

        $baseurl = $CFG->wwwroot . '/local/rapmobile/fileserve.php'
            . '?token='     . $token
            . '&contextid=' . $file->get_contextid()
            . '&component=' . $file->get_component()
            . '&filearea='  . $file->get_filearea()
            . '&itemid='    . $file->get_itemid()
            . '&filename='  . urlencode($filename);

        $output[] = [
            'filename'     => $filename,
            'filesize'     => (int)$file->get_filesize(),
            'mimetype'     => $mimetype,
            'fileurl'      => $baseurl,
            'downloadurl'  => $baseurl . '&forcedownload=1',
            'timemodified' => (int)$file->get_timemodified(),
            'isimage'      => (bool)strstr($mimetype, 'image/'),
            'ispdf'        => ($mimetype === 'application/pdf'),
            'isvideo'      => (bool)strstr($mimetype, 'video/'),
        ];
    }

    return $output;
}
private static function get_videotime_files($cm, $token) {
    global $CFG, $DB;

    $context = \context_module::instance($cm->id);
    $fs      = get_file_storage();

    // VideoTime uploads live under the videojs SUB-PLUGIN, not mod_videotime:
    //   component = videotimeplugin_videojs, filearea = mediafile, itemid = 0
    $candidates = [
        ['videotimeplugin_videojs', 'mediafile'],
        ['videotimeplugin_videojs', 'poster'],
        ['mod_videotime',           'content'],
        ['mod_videotime',           'intro'],
    ];

    $all = [];
    foreach ($candidates as [$component, $filearea]) {
        foreach ($fs->get_area_files($context->id, $component, $filearea, 0, 'filename ASC', false) as $f) {
            $all[$f->get_pathnamehash()] = $f;
        }
    }

    // Fallback: any videotime-owned file in this context, whatever the area.
    if (empty($all)) {
        $rows = $DB->get_records_sql(
            "SELECT * FROM {files}
              WHERE contextid = :ctx
                AND (component = 'mod_videotime'
                     OR " . $DB->sql_like('component', ':likecomp') . ")
                AND filename <> '.'
                AND filesize > 0
           ORDER BY filename ASC",
            [
                'ctx'      => $context->id,
                'likecomp' => $DB->sql_like_escape('videotimeplugin_') . '%',
            ]
        );
        foreach ($rows as $r) {
            $f = $fs->get_file($r->contextid, $r->component, $r->filearea,
                               $r->itemid, $r->filepath, $r->filename);
            if ($f) {
                $all[$f->get_pathnamehash()] = $f;
            }
        }
    }

    $output = [];
    foreach ($all as $file) {
        if ($file->is_directory()) continue;

        $filename = $file->get_filename();
        $mimetype = $file->get_mimetype();

        $baseurl = $CFG->wwwroot . '/local/rapmobile/fileserve.php'
            . '?token='     . $token
            . '&contextid=' . $file->get_contextid()
            . '&component=' . $file->get_component()
            . '&filearea='  . $file->get_filearea()
            . '&itemid='    . $file->get_itemid()
            . '&filename='  . urlencode($filename);

        $output[] = [
            'filename'     => $filename,
            'filesize'     => (int)$file->get_filesize(),
            'mimetype'     => $mimetype,
            'fileurl'      => $baseurl,
            'downloadurl'  => $baseurl . '&forcedownload=1',
            'timemodified' => (int)$file->get_timemodified(),
            'isimage'      => (bool)strstr($mimetype, 'image/'),
            'ispdf'        => ($mimetype === 'application/pdf'),
            'isvideo'      => (bool)strstr($mimetype, 'video/'),
        ];
    }

    return $output;
}



    public static function activity_view_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
        ]);
    }

    public static function activity_view($cmid) {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::activity_view_parameters(),
            ['cmid' => $cmid]
        );

        $cm = get_coursemodule_from_id('', $params['cmid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        require_login($course, true, $cm);

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $completion = new completion_info($course);

        // Check completion is enabled on site + course level.
        if (!$completion->is_enabled()) {
            return [
                'success'         => false,
                'completionstate' => 0,
                'message'         => 'Completion is not enabled on this site or course.',
            ];
        }

        // Check this specific module has completion enabled.
        if (!$completion->is_enabled($cm)) {
            return [
                'success'         => false,
                'completionstate' => 0,
                'message'         => 'Completion is not enabled for this activity.',
            ];
        }

        // Check this module actually requires VIEW for completion.
        // This covers: page, resource, url, label, folder, book,
        // assign, quiz, scorm, h5pactivity, lti, video — anything
        // where the teacher ticked "Student must view this activity".
        if ((int)$cm->completionview !== COMPLETION_VIEW_REQUIRED) {
            return [
                'success'         => false,
                'completionstate' => 0,
                'message'         => 'This activity does not use view-based completion.',
            ];
        }

        // Already completed? No need to re-trigger.
        $data = $completion->get_data($cm, false, $USER->id);
        if ($data->completionstate >= COMPLETION_COMPLETE) {
            return [
                'success'         => true,
                'completionstate' => (int) $data->completionstate,
                'message'         => 'Already complete.',
            ];
        }

        // Mark as viewed — this is what flips completion state to COMPLETE
        // when completionview = COMPLETIONVIEW_REQUIRED.
        $completion->set_module_viewed($cm);

        // Read back updated state.
        $data = $completion->get_data($cm, false, $USER->id);

        return [
            'success'         => true,
            'completionstate' => (int) $data->completionstate,
            'message'         => 'Activity marked as viewed.',
        ];
    }

    public static function activity_view_returns() {
        return new external_single_structure([
            'success'         => new external_value(PARAM_BOOL, 'Whether the operation succeeded'),
            'completionstate' => new external_value(PARAM_INT,  '0=incomplete, 1=complete'),
            'message'         => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }

// =========================================================
// PRIVATE — pdfjsfolder: multiple files via mobile/pluginfile.php
// Mirrors the file-discovery logic already proven in
// mod/pdfjsfolder/mobile/pluginfile.php (multi-filearea + DB fallback),
// so this returns the exact same files that endpoint can serve.
// =========================================================
private static function get_pdfjsfolder_files($cm, $token) {
    global $CFG, $DB;

    $context    = \context_module::instance($cm->id);
    $fs         = get_file_storage();
    $all_files  = [];
    $file_areas = ['pdfs', 'content', 'intro', 'package'];

    foreach ($file_areas as $area) {
        $found = $fs->get_area_files(
            $context->id, 'mod_pdfjsfolder', $area, false, 'filepath, filename ASC', false
        );
        foreach ($found as $f) {
            $all_files[] = $f;
        }
    }

    // Same DB-direct fallback as mobile/pluginfile.php, in case the file
    // area name isn't one of the ones guessed above.
    if (empty($all_files)) {
        $dbfiles = $DB->get_records_sql(
            "SELECT * FROM {files}
             WHERE contextid = ? AND component = 'mod_pdfjsfolder' AND filename != '.'
             ORDER BY filepath, filename ASC",
            [$context->id]
        );
        foreach ($dbfiles as $dbf) {
            $file = $fs->get_file(
                $dbf->contextid, $dbf->component, $dbf->filearea,
                $dbf->itemid, $dbf->filepath, $dbf->filename
            );
            if ($file) {
                $all_files[] = $file;
            }
        }
    }

    $base_url = $CFG->wwwroot
        . '/mod/pdfjsfolder/mobile/pluginfile.php'
        . '?token=' . $token
        . '&id='    . $cm->id;

    $output = [];
    foreach ($all_files as $file) {
        $filename = $file->get_filename();
        $mimetype = $file->get_mimetype();

        $output[] = [
            'filename'     => $filename,
            'filesize'     => (int)$file->get_filesize(),
            'mimetype'     => $mimetype,
            // One link per file, served through your confirmed-working endpoint.
            'fileurl'      => $base_url . '&filename=' . urlencode($filename),
            'downloadurl'  => $base_url . '&filename=' . urlencode($filename),
            'timemodified' => (int)$file->get_timemodified(),
            'isimage'      => (strpos($mimetype, 'image/') === 0),
            'ispdf'        => ($mimetype === 'application/pdf'),
            'isvideo'      => (bool)strstr($mimetype, 'video/'),
        ];
    }

    return $output;
}

// =========================================================
// PRIVATE — Get extra data per module type
// =========================================================
private static function get_module_extra_data($cm, $course, $token, $userid) {
    global $DB, $CFG;

    $extra = [
        'externalurl' => '',
        'content'     => '',
        'vimeo_url'   => '',
        'join_url'    => '',
        'quizid'      => 0,
        'timelimit'   => 0,
        'attempts'    => 0,
    ];

    $first_file_url = function($component, $filearea, $itemid = 0) use ($cm, $token) {
        $context = \context_module::instance($cm->id);
        $fs      = get_file_storage();
        $files   = $fs->get_area_files($context->id, $component, $filearea, $itemid, 'filename ASC', false);
        foreach ($files as $file) {
            $url = \moodle_url::make_webservice_pluginfile_url(
                $file->get_contextid(), $file->get_component(), $file->get_filearea(),
                $file->get_itemid(), $file->get_filepath(), $file->get_filename()
            )->out(false);
            return $url . '?token=' . $token;
        }
        return '';
    };

    try {
        switch ($cm->modname) {

            case 'url':
                $url = $DB->get_record('url', ['id' => $cm->instance], 'externalurl');
                $extra['externalurl'] = $url ? $url->externalurl : '';
                break;

           case 'page':
    $page = $DB->get_record('page', ['id' => $cm->instance], 'id, content, contentformat, revision');
    if ($page) {
        $modcontext = \context_module::instance($cm->id);

        // Swap @@PLUGINFILE@@/xxx placeholders for real, downloadable URLs.
        // itemid must be $page->revision — that's what mod_page actually
        // stores content-area files under, not 0.
        $content = file_rewrite_pluginfile_urls(
            $page->content,
            'webservice/pluginfile.php',
            $modcontext->id,
            'mod_page',
            'content',
            $page->revision,
            ['reverse' => false]
        );

        // Embed the wstoken directly in every rewritten file URL so Flutter
        // can load images/video with zero extra requests — no separate
        // "attach token" step needed on the client.
        $content = preg_replace(
            '#(/webservice/pluginfile\.php/[^"\'\s]+)#',
            '$1?token=' . $token,
            $content
        );

        // Re-run through format_text so filters (YouTube embed, multimedia
        // plugin, etc.) still fire — noclean keeps your markup intact.
        $extra['content'] = format_text(
            $content,
            $page->contentformat ?? FORMAT_HTML,
            ['noclean' => true, 'context' => $modcontext, 'filter' => true]
        );
    }
    break;

           case 'videotime':
    $vt = $DB->get_record('videotime', ['id' => $cm->instance], 'id, vimeo_url');
    $vimeo = $vt ? trim((string)($vt->vimeo_url ?? '')) : '';

    // A real Vimeo/external link wins. Uploads leave vimeo_url empty
    // (or holding a local path), so fall through to the file lookup.
    if ($vimeo !== '' && preg_match('#^https?://#i', $vimeo)) {
        $extra['vimeo_url']   = $vimeo;
        $extra['externalurl'] = $vimeo;
        $extra['content']     = 'vimeo';
        break;
    }

    $vtfiles = self::get_videotime_files($cm, $token);
    foreach ($vtfiles as $f) {
        if (!$f['isvideo']) continue;
        $extra['externalurl'] = $f['fileurl'];
        $extra['content']     = 'Video file';
        break;
    }

    // Nothing flagged video/* (odd mimetype) — take the first file.
    if ($extra['externalurl'] === '' && !empty($vtfiles)) {
        $extra['externalurl'] = $vtfiles[0]['fileurl'];
        $extra['content']     = 'Video file';
    }
    break;

          case 'supervideo':
    $sv = $DB->get_record('supervideo', ['id' => $cm->instance]);
    if ($sv) {
        $origem = $sv->origem ?? '';

        if ($origem === 'upload') {
            $context = \context_module::instance($cm->id);
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'mod_supervideo', 'content', false, 'filename ASC', false);
            $file = reset($files);

            if ($file) {
                $extra['externalurl'] = $CFG->wwwroot . '/local/rapmobile/fileserve.php'
                    . '?token='     . $token
                    . '&contextid=' . $file->get_contextid()
                    . '&component=' . $file->get_component()
                    . '&filearea='  . $file->get_filearea()
                    . '&itemid='    . $file->get_itemid()
                    . '&filename='  . urlencode($file->get_filename());
                $extra['content'] = 'Video file';
            }
        } else {
            $extra['externalurl'] = $sv->videourl ?? '';
            $extra['vimeo_url']   = $extra['externalurl'];
            $extra['content']     = $origem;
        }
    }
    break;

            case 'googlemeet':
                $gm = $DB->get_record('googlemeet', ['id' => $cm->instance]);
                if ($gm) {
                    $extra['join_url'] = $gm->meetingurl ?? $gm->url ?? $gm->joinurl ?? '';
                }
                break;
             case 'ilt':
    $ilt = $DB->get_record('ilt', ['id' => $cm->instance]);
    if ($ilt) {
        // Real date/time lives in ilt_sessions_dates, joined via sessionid —
        // datetimeknown on ilt_sessions is just a 0/1 flag, not a timestamp.
        $session = $DB->get_record_sql(
            "SELECT s.*, d.timestart, d.timefinish
               FROM {ilt_sessions} s
               JOIN {ilt_sessions_dates} d ON d.sessionid = s.id
              WHERE s.ilt = :iltid
                AND d.timestart >= :now
           ORDER BY d.timestart ASC",
            ['iltid' => $ilt->id, 'now' => time()],
            IGNORE_MULTIPLE
        );

        if ($session) {
            // ilt_sessions has no URL-type column at all (location,
            // classroom, instructor, bu, resource — all in-person details).
            // This confirms ILT sessions here are physical/in-person only;
            // join_url intentionally stays empty rather than guessing at
            // a field that doesn't exist in the schema.
            $extra['join_url'] = '';

            $extra['content'] = json_encode([
                'sessionid'  => (int)$session->id,
                'sessionname'=> (string)($session->sessionname ?? ''),
                'timestart'  => (int)$session->timestart,
                'timefinish' => (int)$session->timefinish,
                'location'   => (string)($session->location ?? ''),
                'classroom'  => (string)($session->classroom ?? ''),
                'instructor' => (string)($session->instructor ?? ''),
                'capacity'   => (int)($session->capacity ?? 0),
            ]);
        }
    }
    break;
           case 'goone':
     $extra['externalurl'] = $CFG->wwwroot
    . '/mod/goone/mobile/launchurl.php'
    . '?id=' . $cm->id
    . '&userid=' . $userid
    . '&token=' . $token
    . '&app=true';
     break;

            case 'zoom':
                $zoom = $DB->get_record('zoom', ['id' => $cm->instance], 'join_url, start_time');
                $extra['join_url'] = $zoom ? $zoom->join_url : '';
                break;

            case 'bigbluebuttonbn':
                $bbb = $DB->get_record('bigbluebuttonbn', ['id' => $cm->instance], 'meetingid');
                if ($bbb) {
                    $extra['join_url'] = \moodle_url::make_pluginfile_url(
                        \context_module::instance($cm->id)->id,
                        'mod_bigbluebuttonbn', 'join', null, '/', 'join'
                    )->out(false);
                }
                break;

            case 'quiz':
                $quiz = $DB->get_record('quiz', ['id' => $cm->instance], 'id, timelimit, attempts');
                if ($quiz) {
                    $extra['quizid']    = (int)$quiz->id;
                    $extra['timelimit'] = (int)$quiz->timelimit;
                    $extra['attempts']  = (int)$quiz->attempts;
                }
                break;

            case 'h5pactivity':
                $extra['externalurl'] = $first_file_url('mod_h5pactivity', 'package', 0);
                break;

            case 'customcert':
                $extra['externalurl'] = $CFG->wwwroot
                    . '/mod/customcert/mobile/pluginfile.php'
                    . '?certificateid=' . $cm->instance
                    . '&userid=' . $userid
                    . '&token=' . $token;
                break;

            case 'iomadcertificate':
                $issue = $DB->get_record('iomadcertificate_issues', [
                    'iomadcertificateid' => $cm->instance,
                    'userid'             => $userid,
                ]);
                $extra['externalurl'] = $issue
                    ? $CFG->wwwroot . '/mod/iomadcertificate/mobile/pluginfile.php'
                        . '?issueid=' . $issue->id . '&token=' . $token
                    : '';
                break;

            case 'pdfjsfolder':
    // Files themselves come through the "files" array now (see
    // get_pdfjsfolder_files). This just exposes the list/JSON endpoint
    // in case the UI wants to fetch folder contents separately.
    $extra['externalurl'] = $CFG->wwwroot
        . '/mod/pdfjsfolder/mobile/pluginfile.php'
        . '?id=' . $cm->id
        . '&token=' . $token
        . '&action=list';
    break;

            case 'folder':
                $extra['externalurl'] = $first_file_url('mod_folder', 'content', 0);
                break;

            case 'assign':
                $assign = $DB->get_record('assign', ['id' => $cm->instance],
                    'id, duedate, allowsubmissionsfromdate, cutoffdate');
                if ($assign) {
                    $extra['quizid']    = (int)$assign->id;
                    $extra['timelimit'] = (int)$assign->duedate;
                    $extra['attempts']  = 0;
                    $extra['externalurl'] = $first_file_url('mod_assign', 'introattachment', 0);
                }
                break;
case 'airoleplay':
    $ar = $DB->get_record('airoleplay', ['id' => $cm->instance]);
    if ($ar) {
        $baseurl = $CFG->wwwroot . '/webservice/rest/server.php';

     $extra['content'] = json_encode([
            'scenario_name'     => (string)$ar->name,
            'description'       => strip_tags(!empty($ar->learning_objective) ? $ar->learning_objective : ($ar->intro ?? '')),
            'scenario_type'     => (string)($ar->scenario_type ?? 'chat'),
            'industry'          => (string)($ar->industry ?? ''),
            'difficulty'        => (string)($ar->difficulty ?? ''),
            'language'          => (string)($ar->language ?? 'en'),
            'max_attempts'      => (int)($ar->max_attempts ?? 0),
            'pass_score'        => (float)($ar->pass_score ?? 0),
            'time_limit_secs'   => (int)($ar->max_duration ?? 0),
            'max_turns'         => (int)($ar->max_turns ?? 0),
            'enable_voice'      => (bool)($ar->enable_voice ?? false),
            'enable_avatar'     => (bool)($ar->enable_avatar ?? false),
            'enable_transcript' => (bool)($ar->enable_transcript ?? false),
            'attempts_taken'    => (int)$DB->count_records('airoleplay_session', [
                'airoleplay' => $ar->id,
                'userid'     => $userid,
            ]),

            'available_languages' => [
                ['code' => 'en', 'label' => 'English'],
                ['code' => 'hi', 'label' => 'Hindi'],
                ['code' => 'ta', 'label' => 'Tamil'],
                ['code' => 'te', 'label' => 'Telugu'],
                ['code' => 'ar', 'label' => 'Arabic'],
                ['code' => 'fr', 'label' => 'French'],
                ['code' => 'de', 'label' => 'German'],
                ['code' => 'es', 'label' => 'Spanish'],
            ],
            'default_language'  => (string)($ar->language ?? 'en'),
            'start_button_voice_enabled' => (bool)($ar->enable_voice ?? false),

            // ---- Ready-to-call URLs (token already embedded) ----
            // GET requests work for all of these since Moodle's REST server
            // accepts params via query string regardless of the "type" field
            // (mirrors how the certificate/pdfjsfolder mobile endpoints work
            // elsewhere in this API). {sessionid} must be replaced with the
            // real value returned from create_session before calling the
            // rest — it's a placeholder, not a literal string to send as-is.
            // language/type/message must be URL-encoded by the app before
            // substitution if they contain special characters.
            'endpoints' => [
                'create_session' => [
                    'url'    => $baseurl . '?wstoken=' . $token
                        . '&wsfunction=mod_airoleplay_create_session'
                        . '&moodlewsrestformat=json'
                        . '&instanceid=' . $ar->id
                        . '&language={language}'
                        . '&type={type}',
                    'returns' => ['sessionid'],
                ],
                'get_opening_message' => [
                    'url'    => $baseurl . '?wstoken=' . $token
                        . '&wsfunction=mod_airoleplay_get_opening_message'
                        . '&moodlewsrestformat=json'
                        . '&sessionid={sessionid}',
                    'returns' => ['message', 'persona', 'persona_role', 'audio_url'],
                ],
                'send_message' => [
                    'url'    => $baseurl . '?wstoken=' . $token
                        . '&wsfunction=mod_airoleplay_send_message'
                        . '&moodlewsrestformat=json'
                        . '&sessionid={sessionid}'
                        . '&message={message}',
                    'returns' => ['reply', 'turn', 'audio_url'],
                ],
                'upload_session_audio' => [
                    'url'    => $baseurl . '?wstoken=' . $token
                        . '&wsfunction=mod_airoleplay_upload_session_audio'
                        . '&moodlewsrestformat=json'
                        . '&sessionid={sessionid}'
                        . '&audio_b64={audio_b64}'
                        . '&mimetype=audio/webm',
                    'returns' => ['success', 'audio_url'],
                    'note'    => 'Optional. Use POST, not GET — base64 audio is too large for a URL query string in practice.',
                ],
                'end_session' => [
                    'url'    => $baseurl . '?wstoken=' . $token
                        . '&wsfunction=mod_airoleplay_end_session'
                        . '&moodlewsrestformat=json'
                        . '&sessionid={sessionid}',
                    'returns' => ['success', 'redirecturl'],
                ],
                'get_evaluation' => [
                    'url'    => $baseurl . '?wstoken=' . $token
                        . '&wsfunction=mod_airoleplay_get_evaluation'
                        . '&moodlewsrestformat=json'
                        . '&session_id={sessionid}'
                        . '&trigger=1',
                    'returns' => ['evaluated', 'score_overall', 'passed', 'ai_summary', 'competencies', 'strengths', 'improvements', 'recommendations'],
                    'note'    => 'Poll every few seconds until evaluated=true.',
                ],
            ],
        ]);

        $extra['externalurl'] = $CFG->wwwroot
            . '/mod/airoleplay/mobile/pluginfile.php'
            . '?id=' . $cm->id
            . '&token=' . $token
            . '&app=true';
    }
    break;
  
        }
    } catch (\Exception $e) {
        // debugging($e->getMessage(), DEBUG_DEVELOPER);
    }

    return $extra;
}

// app control
 
public static function get_app_config_parameters() {
    return new external_function_parameters([
        'userid'   => new external_value(PARAM_INT,  'User ID'),
        'platform' => new external_value(PARAM_TEXT, 'android or ios', VALUE_DEFAULT, 'android'),
    ]);
}
 
public static function get_app_config($userid, $platform = 'android') {
    global $DB, $CFG;
 
    $params = self::validate_parameters(
        self::get_app_config_parameters(),
        ['userid' => $userid, 'platform' => $platform]
    );
 
    // ---- Helper: get config value with default ----
    $cfg = function($key, $default = '') use ($CFG) {
        $val = get_config('local_rapmobile', $key);
        return ($val !== false && $val !== null) ? $val : $default;
    };
 
    // ---- TAB VISIBILITY ----
    // Each tab: 1=show, 0=hide
    // Admin sets these in:
    // Site Admin → Plugins → Local plugins → RAP Mobile → Tab Settings
    $tabs = [
        // Bottom nav tabs
        [
            'key'     => 'home',
            'label'   => 'Home',
            'icon'    => 'home',
            'visible' => (int)(bool)$cfg('tab_home',          '1'),
            'order'   => 1,
            'location'=> 'bottom',
        ],
        [
            'key'     => 'learning',
            'label'   => 'Learning',
            'icon'    => 'book',
            'visible' => (int)(bool)$cfg('tab_learning',      '1'),
            'order'   => 2,
            'location'=> 'bottom',
        ],
        [
            'key'     => 'wallfame',
            'label'   => 'Wall of Fame',
            'icon'    => 'trophy',
            'visible' => (int)(bool)$cfg('tab_wallfame',      '1'),
            'order'   => 3,
            'location'=> 'bottom',
        ],
        [
            'key'     => 'game',
            'label'   => 'Game',
            'icon'    => 'game-controller',
            'visible' => (int)(bool)$cfg('tab_game',          '1'),
            'order'   => 4,
            'location'=> 'bottom',
        ],
        [
            'key'     => 'report',
            'label'   => 'Report',
            'icon'    => 'bar-chart',
            'visible' => (int)(bool)$cfg('tab_report',        '1'),
            'order'   => 5,
            'location'=> 'bottom',
        ],
 
        // More menu items
        [
            'key'     => 'profile',
            'label'   => 'Profile',
            'icon'    => 'person',
            'visible' => (int)(bool)$cfg('tab_profile',       '1'),
            'order'   => 1,
            'location'=> 'more',
        ],
        [
            'key'     => 'learningpath',
            'label'   => 'Learning Path',
            'icon'    => 'git-branch',
            'visible' => (int)(bool)$cfg('tab_learningpath',  '1'),
            'order'   => 2,
            'location'=> 'more',
        ],
        [
            'key'     => 'download',
            'label'   => 'Download',
            'icon'    => 'download',
            'visible' => (int)(bool)$cfg('tab_download',      '1'),
            'order'   => 3,
            'location'=> 'more',
        ],
        [
            'key'     => 'certificates',
            'label'   => 'Certificates',
            'icon'    => 'ribbon',
            'visible' => (int)(bool)$cfg('tab_certificates',  '1'),
            'order'   => 4,
            'location'=> 'more',
        ],
        [
            'key'     => 'skillgap',
            'label'   => 'Skill Gap Analysis',
            'icon'    => 'analytics',
            'visible' => (int)(bool)$cfg('tab_skillgap',      '1'),
            'order'   => 5,
            'location'=> 'more',
        ],
        [
            'key'     => 'attendance',
            'label'   => 'Attendance',
            'icon'    => 'calendar-check',
            'visible' => (int)(bool)$cfg('tab_attendance',    '1'),
            'order'   => 6,
            'location'=> 'more',
        ],
        [
            'key'     => 'live',
            'label'   => 'Live',
            'icon'    => 'radio',
            'visible' => (int)(bool)$cfg('tab_live',          '1'),
            'order'   => 7,
            'location'=> 'more',
        ],
    ];
 
    // ---- VERSION CONTROL ----
    $platform = strtolower($params['platform']);
 
    if ($platform === 'ios') {
        $current_version   = (string)$cfg('ios_current_version',   '1.0.0');
        $min_version       = (string)$cfg('ios_min_version',        '1.0.0');
        $store_url         = (string)$cfg('ios_store_url',          '');
        $force_update      = (int)(bool)$cfg('ios_force_update',    '0');
        $build_number      = (string)$cfg('ios_build_number',       '1');
    } else {
        $current_version   = (string)$cfg('android_current_version','1.0.0');
        $min_version       = (string)$cfg('android_min_version',    '1.0.0');
        $store_url         = (string)$cfg('android_store_url',      '');
        $force_update      = (int)(bool)$cfg('android_force_update','0');
        $build_number      = (string)$cfg('android_build_number',   '1');
    }
 
    // ---- MAINTENANCE MODE ----
    $maintenance_on  = (int)(bool)$cfg('app_maintenance',       '0');
    $maintenance_msg = (string)$cfg('app_maintenance_message',  'App is under maintenance. Please try again later.');
 
    // ---- FEATURE FLAGS ----
    $features = [
        'enable_push_notifications' => (int)(bool)$cfg('feature_push',         '1'),
        'enable_offline_mode'       => (int)(bool)$cfg('feature_offline',       '0'),
        'enable_biometric_login'    => (int)(bool)$cfg('feature_biometric',     '0'),
        'enable_social_login'       => (int)(bool)$cfg('feature_social_login',  '1'),
        'enable_gift_points'        => (int)(bool)$cfg('feature_gift_points',   '1'),
        'enable_leaderboard'        => (int)(bool)$cfg('feature_leaderboard',   '1'),
        'enable_spin_wheel'         => (int)(bool)$cfg('feature_spin_wheel',    '1'),
        'enable_scratch_card'       => (int)(bool)$cfg('feature_scratch_card',  '1'),
        'enable_hall_of_fame'       => (int)(bool)$cfg('feature_hall_of_fame',  '1'),
        'enable_qr_attendance'      => (int)(bool)$cfg('feature_qr_attendance', '1'),
        'enable_chat'               => (int)(bool)$cfg('feature_chat',          '0'),
    ];
 
    return [
        'userid'          => (int)$params['userid'],
        'platform'        => (string)$platform,
        'tabs'            => $tabs,
        'version'         => [
            'current_version' => $current_version,
            'min_version'     => $min_version,
            'build_number'    => $build_number,
            'store_url'       => $store_url,
            'force_update'    => (bool)$force_update,
            'update_message'  => (string)$cfg('update_message', 'A new version is available. Please update.'),
        ],
        'maintenance'     => [
            'enabled' => (bool)$maintenance_on,
            'message' => $maintenance_msg,
        ],
        'features'        => $features,
    ];
}
 
public static function get_app_config_returns() {
    return new external_single_structure([
        'userid'   => new external_value(PARAM_INT,  'User ID'),
        'platform' => new external_value(PARAM_TEXT, 'android or ios'),
 
        // Tab visibility
        'tabs' => new external_multiple_structure(
            new external_single_structure([
                'key'      => new external_value(PARAM_TEXT, 'Tab key e.g. home/learning/game'),
                'label'    => new external_value(PARAM_TEXT, 'Display label'),
                'icon'     => new external_value(PARAM_TEXT, 'Icon name'),
                'visible'  => new external_value(PARAM_INT,  '1=show 0=hide'),
                'order'    => new external_value(PARAM_INT,  'Display order'),
                'location' => new external_value(PARAM_TEXT, 'bottom=bottom nav, more=more menu'),
            ])
        ),
 
        // Version control
        'version' => new external_single_structure([
            'current_version' => new external_value(PARAM_TEXT, 'Latest version e.g. 1.2.0'),
            'min_version'     => new external_value(PARAM_TEXT, 'Minimum supported version'),
            'build_number'    => new external_value(PARAM_TEXT, 'Build number'),
            'store_url'       => new external_value(PARAM_RAW,  'Play Store / App Store URL'),
            'force_update'    => new external_value(PARAM_BOOL, 'Force update — block app if below min_version'),
            'update_message'  => new external_value(PARAM_TEXT, 'Update prompt message'),
        ]),
 
        // Maintenance
        'maintenance' => new external_single_structure([
            'enabled' => new external_value(PARAM_BOOL, 'App maintenance mode on/off'),
            'message' => new external_value(PARAM_TEXT, 'Maintenance message to show'),
        ]),
 
        // Feature flags
        'features' => new external_single_structure([
            'enable_push_notifications' => new external_value(PARAM_INT, 'Push notifications'),
            'enable_offline_mode'       => new external_value(PARAM_INT, 'Offline mode'),
            'enable_biometric_login'    => new external_value(PARAM_INT, 'Fingerprint/Face login'),
            'enable_social_login'       => new external_value(PARAM_INT, 'Google/LinkedIn login'),
            'enable_gift_points'        => new external_value(PARAM_INT, 'Gift points to friends'),
            'enable_leaderboard'        => new external_value(PARAM_INT, 'Leaderboard'),
            'enable_spin_wheel'         => new external_value(PARAM_INT, 'Spin wheel'),
            'enable_scratch_card'       => new external_value(PARAM_INT, 'Scratch & Win'),
            'enable_hall_of_fame'       => new external_value(PARAM_INT, 'Hall of Fame'),
            'enable_qr_attendance'      => new external_value(PARAM_INT, 'QR attendance'),
            'enable_chat'               => new external_value(PARAM_INT, 'In-app chat'),
        ]),
    ]);
}
 
// =========================================================
// 2. SAVE APP CONFIG (Admin only)
//    Admin calls this to update tab visibility + version
// =========================================================
 
public static function save_app_config_parameters() {
    return new external_function_parameters([
        'adminuserid' => new external_value(PARAM_INT,  'Admin user ID'),
        'configjson'  => new external_value(PARAM_RAW,  'JSON string of config values to update'),
    ]);
}
 
public static function save_app_config($adminuserid, $configjson) {
    global $DB;
 
    $params = self::validate_parameters(
        self::save_app_config_parameters(),
        ['adminuserid' => $adminuserid, 'configjson' => $configjson]
    );
 
    // Only admins can save config
    if (!is_siteadmin($params['adminuserid'])) {
        return [
            'success' => false,
            'message' => 'Only site administrators can update app config',
        ];
    }
 
    $config = json_decode($params['configjson'], true);
    if (!$config || !is_array($config)) {
        return [
            'success' => false,
            'message' => 'Invalid JSON config',
        ];
    }
 
    // Allowed config keys
    $allowed_keys = [
        // Tabs
        'tab_home', 'tab_learning', 'tab_wallfame', 'tab_game',
        'tab_report', 'tab_profile', 'tab_learningpath', 'tab_download',
        'tab_certificates', 'tab_skillgap', 'tab_attendance', 'tab_live',
 
        // Android version
        'android_current_version', 'android_min_version',
        'android_store_url', 'android_force_update', 'android_build_number',
 
        // iOS version
        'ios_current_version', 'ios_min_version',
        'ios_store_url', 'ios_force_update', 'ios_build_number',
 
        // Messages
        'update_message',
 
        // Maintenance
        'app_maintenance', 'app_maintenance_message',
 
        // Features
        'feature_push', 'feature_offline', 'feature_biometric',
        'feature_social_login', 'feature_gift_points', 'feature_leaderboard',
        'feature_spin_wheel', 'feature_scratch_card',
        'feature_hall_of_fame', 'feature_qr_attendance', 'feature_chat',
    ];
 
    $saved = 0;
    foreach ($config as $key => $value) {
        if (in_array($key, $allowed_keys)) {
            set_config($key, $value, 'local_rapmobile');
            $saved++;
        }
    }
 
    return [
        'success' => true,
        'message' => $saved . ' config values saved successfully',
    ];
}
 
public static function save_app_config_returns() {
    return new external_single_structure([
        'success' => new external_value(PARAM_BOOL, 'Saved successfully'),
        'message' => new external_value(PARAM_TEXT, 'Result message'),
    ]);
}

  // Question type codes — CONFIRM against your install's constants (see note above).
 // These are the commonly-used values across MOODLE_401_STABLE / MOODLE_404_STABLE.
    const QTYPE_YESNO      = 1;
    const QTYPE_TEXT       = 2;
    const QTYPE_ESSAY      = 3;
    const QTYPE_RADIO      = 4;
    const QTYPE_CHECKBOX   = 5;
    const QTYPE_DROPDOWN   = 6;
    const QTYPE_RATE       = 8;
  const QTYPE_DATE       = 9;
    const QTYPE_NUMERIC    = 10;
    const QTYPE_SLIDER     = 11;
    const QTYPE_SECTIONTEXT= 100;
    const QTYPE_PAGEBREAK  = 99;

    // ─────────────────────────────────────────────────────────
    // 1. LIST QUESTIONNAIRES
    // ─────────────────────────────────────────────────────────
    public static function get_questionnaire_list_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function get_questionnaire_list($userid) {
        global $DB;

        $params = self::validate_parameters(self::get_questionnaire_list_parameters(),
            ['userid' => $userid]);

        $courses = enrol_get_all_users_courses($params['userid'], true);
        $courseids = array_keys($courses);
        if (empty($courseids)) {
            return ['questionnaires' => []];
        }

        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $sql = "SELECT q.id, q.course, q.name, q.intro, q.cmid_placeholder, c.fullname AS coursename
                  FROM {questionnaire} q
                  JOIN {course} c ON c.id = q.course
                 WHERE q.course $insql";
        // NOTE: questionnaire table has no cmid column directly — resolve cmid via
        // get_coursemodules_in_course / $DB->get_record('course_modules', ...) below,
        // since the mobile app needs cmid for permission checks and completion.
        unset($sql); // built manually below to attach cmid safely.

        $questionnaires = [];
        foreach ($courseids as $cid) {
            $modinfo = get_fast_modinfo($cid, $params['userid']);
            foreach ($modinfo->get_instances_of('questionnaire') as $cm) {
                if (!$cm->uservisible) {
                    continue;
                }
                $q = $DB->get_record('questionnaire', ['id' => $cm->instance], '*', MUST_EXIST);

                // Has this user already submitted a complete response?
               $completed = $DB->record_exists('questionnaire_response', [
    'questionnaireid' => $q->id,
    'userid'          => $params['userid'],
    'complete'        => 'y',
]);

                $questionnaires[] = [
                    'id'          => (int)$q->id,
                    'cmid'        => (int)$cm->id,
                    'courseid'    => (int)$cid,
                    'coursename'  => $courses[$cid]->fullname,
                    'name'        => $q->name,
                    'intro'       => format_text($q->intro, $q->introformat ?? FORMAT_HTML),
                    'is_completed'=> $completed,
                    'resp_view'   => (int)($q->resp_view ?? 0), // whether user can see aggregate results
                ];
            }
        }

        return ['questionnaires' => $questionnaires];
    }

    public static function get_questionnaire_list_returns() {
        return new external_single_structure([
            'questionnaires' => new external_multiple_structure(
                new external_single_structure([
                    'id'           => new external_value(PARAM_INT,  'Questionnaire ID'),
                    'cmid'         => new external_value(PARAM_INT,  'Course module ID'),
                    'courseid'     => new external_value(PARAM_INT,  'Course ID'),
                    'coursename'   => new external_value(PARAM_TEXT, 'Course name'),
                    'name'         => new external_value(PARAM_TEXT, 'Questionnaire name'),
                    'intro'        => new external_value(PARAM_RAW,  'Intro HTML'),
                    'is_completed' => new external_value(PARAM_BOOL, 'Already submitted by this user'),
                    'resp_view'    => new external_value(PARAM_INT,  'Response visibility setting'),
                ])
            ),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 2. GET FULL STRUCTURE (ALL QUESTION TYPES)
    // ─────────────────────────────────────────────────────────
    public static function get_questionnaire_structure_parameters() {
        return new external_function_parameters([
            'questionnaireid' => new external_value(PARAM_INT, 'Questionnaire ID'),
        ]);
    }

    public static function get_questionnaire_structure($questionnaireid) {
        global $DB;

        $params = self::validate_parameters(self::get_questionnaire_structure_parameters(),
            ['questionnaireid' => $questionnaireid]);

        $q = $DB->get_record('questionnaire', ['id' => $params['questionnaireid']], '*', MUST_EXIST);
        $survey = $DB->get_record('questionnaire_survey', ['id' => $q->sid], '*', MUST_EXIST);

        $questions = $DB->get_records_select('questionnaire_question',
            'surveyid = :sid AND (deleted IS NULL OR deleted = :n)',
            ['sid' => $survey->id, 'n' => 'n'], 'position ASC');

        // Dependencies (branching logic), if the table exists on this install.
        $dependencies = [];
        try {
            $deps = $DB->get_records('questionnaire_dependency', ['surveyid' => $survey->id]);
            foreach ($deps as $d) {
                $dependencies[$d->questionid][] = [
                    'dependquestionid' => (int)$d->dependquestionid,
                    'dependchoiceid'   => (int)$d->dependchoiceid,
                    'dependlogic'      => (int)($d->dependlogic ?? 1),
                ];
            }
        } catch (\Exception $e) {
            // Older/newer install variant — skip branching silently, don't fatal error.
        }

        $rows = [];
        foreach ($questions as $qq) {
            $choices = [];
            if (in_array((int)$qq->type_id, [
                self::QTYPE_RADIO, self::QTYPE_CHECKBOX, self::QTYPE_DROPDOWN, self::QTYPE_RATE,
            ])) {
                $craw = $DB->get_records('questionnaire_quest_choice', ['question_id' => $qq->id], 'id ASC');
                foreach ($craw as $c) {
                    $choices[] = [
                        'id'      => (int)$c->id,
                        'content' => $c->content,
                        'value'   => $c->value ?? '',
                    ];
                }
            }

            $rows[] = [
                'id'          => (int)$qq->id,
                'type_id'     => (int)$qq->type_id,
                'type_name'   => self::type_name((int)$qq->type_id),
                'name'        => $qq->name ?? '',
                'content'     => format_text($qq->content, FORMAT_HTML),
                'required'    => ($qq->required === 'y' || $qq->required === '1' || $qq->required === 1),
                'position'    => (int)$qq->position,
                'length'      => (int)($qq->length ?? 0),   // text max length OR rate max value depending on type
                'precise'     => (int)($qq->precise ?? 0),  // sub-format: e.g. numeric decimals, rate display style
                'is_section'  => ((int)$qq->type_id === self::QTYPE_SECTIONTEXT),
                'is_pagebreak'=> ((int)$qq->type_id === self::QTYPE_PAGEBREAK),
                'choices'     => $choices,
                'dependencies'=> $dependencies[$qq->id] ?? [],
            ];
        }

        return [
            'questionnaireid' => (int)$q->id,
            'name'            => $q->name,
            'intro'           => format_text($q->intro, $q->introformat ?? FORMAT_HTML),
            'questions'       => $rows,
        ];
    }

    public static function get_questionnaire_structure_returns() {
        return new external_single_structure([
            'questionnaireid' => new external_value(PARAM_INT,  'Questionnaire ID'),
            'name'            => new external_value(PARAM_TEXT, 'Name'),
            'intro'           => new external_value(PARAM_RAW,  'Intro HTML'),
            'questions'       => new external_multiple_structure(
                new external_single_structure([
                    'id'           => new external_value(PARAM_INT,  'Question ID'),
                    'type_id'      => new external_value(PARAM_INT,  'Raw type code'),
                    'type_name'    => new external_value(PARAM_TEXT, 'yesno/text/essay/radio/checkbox/dropdown/rate/date/numeric/section/pagebreak'),
                    'name'         => new external_value(PARAM_TEXT, 'Internal question name'),
                    'content'      => new external_value(PARAM_RAW,  'Question text HTML'),
                    'required'     => new external_value(PARAM_BOOL, 'Is required'),
                    'position'     => new external_value(PARAM_INT,  'Display order'),
                    'length'       => new external_value(PARAM_INT,  'Length/max value depending on type'),
                    'precise'      => new external_value(PARAM_INT,  'Sub-format code'),
                    'is_section'   => new external_value(PARAM_BOOL, 'Is a section-text divider'),
                    'is_pagebreak' => new external_value(PARAM_BOOL, 'Is a page break'),
                    'choices'      => new external_multiple_structure(
                        new external_single_structure([
                            'id'      => new external_value(PARAM_INT,  'Choice ID'),
                            'content' => new external_value(PARAM_RAW,  'Choice label'),
                            'value'   => new external_value(PARAM_TEXT, 'Choice value'),
                        ])
                    ),
                    'dependencies' => new external_multiple_structure(
                        new external_single_structure([
                            'dependquestionid' => new external_value(PARAM_INT, 'Depends on question ID'),
                            'dependchoiceid'   => new external_value(PARAM_INT, 'Depends on choice ID'),
                            'dependlogic'      => new external_value(PARAM_INT, 'AND/OR logic code'),
                        ])
                    ),
                ])
            ),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 3. SUBMIT RESPONSE — ALL TYPES
    // ─────────────────────────────────────────────────────────
    public static function submit_questionnaire_parameters() {
        return new external_function_parameters([
            'questionnaireid' => new external_value(PARAM_INT, 'Questionnaire ID'),
            'userid'          => new external_value(PARAM_INT, 'User ID'),
            'answers'         => new external_multiple_structure(
                new external_single_structure([
                    'questionid' => new external_value(PARAM_INT, 'Question ID'),
                    'type_id'    => new external_value(PARAM_INT, 'Question type code (client must echo back)'),
                    // Only ONE of these will be populated depending on type_id; app sends what applies.
                    'text_value'   => new external_value(PARAM_RAW,  'Text/essay/numeric answer', VALUE_DEFAULT, ''),
                    'bool_value'   => new external_value(PARAM_TEXT, 'y/n for yes-no', VALUE_DEFAULT, ''),
                    'date_value'   => new external_value(PARAM_TEXT, 'YYYY-MM-DD for date', VALUE_DEFAULT, ''),
                    'choice_ids'   => new external_multiple_structure(
                        new external_value(PARAM_INT, 'Selected choice id'), VALUE_DEFAULT, []
                    ), // radio=1 item, checkbox=N items, dropdown=1 item
                    'rate_values'  => new external_multiple_structure(
                        new external_single_structure([
                            'choiceid' => new external_value(PARAM_INT, 'Rate row choice id'),
                            'rank'     => new external_value(PARAM_INT, 'Selected rank/value'),
                        ]), VALUE_DEFAULT, []
                    ),
                ])
            ),
        ]);
    }

    public static function submit_questionnaire($questionnaireid, $userid, $answers) {
        global $DB;

        $params = self::validate_parameters(self::submit_questionnaire_parameters(),
            ['questionnaireid' => $questionnaireid, 'userid' => $userid, 'answers' => $answers]);

        $q = $DB->get_record('questionnaire', ['id' => $params['questionnaireid']], '*', MUST_EXIST);

        $transaction = $DB->start_delegated_transaction();
        try {
            // 1. Create response row.
            $responseid = $DB->insert_record('questionnaire_response', [
                'questionnaireid' => $q->id,
                'userid'          => $params['userid'],
                'submitted'       => time(),
                'complete'        => 'y',
            ]);

            // 2. Insert per-type answers.
            foreach ($params['answers'] as $a) {
                $qid  = (int)$a['questionid'];
                $type = (int)$a['type_id'];

                switch ($type) {
                    case self::QTYPE_YESNO:
                        if ($a['bool_value'] !== '') {
                            $DB->insert_record('questionnaire_response_bool', [
                                'response_id' => $responseid,
                                'question_id' => $qid,
                                'choice_id'   => $a['bool_value'], // 'y' or 'n'
                            ]);
                        }
                        break;

                    case self::QTYPE_TEXT:
                    case self::QTYPE_ESSAY:
                    case self::QTYPE_NUMERIC:
                    case self::QTYPE_SLIDER:
                        if ($a['text_value'] !== '') {
                            $DB->insert_record('questionnaire_response_text', [
                                'response_id' => $responseid,
                                'question_id' => $qid,
                                'response'    => $a['text_value'],
                            ]);
                        }
                        break;

                    case self::QTYPE_DATE:
                        if ($a['date_value'] !== '') {
                            $DB->insert_record('questionnaire_response_date', [
                                'response_id' => $responseid,
                                'question_id' => $qid,
                                'response'    => $a['date_value'],
                            ]);
                        }
                        break;

                    case self::QTYPE_RADIO:
                    case self::QTYPE_DROPDOWN:
                        if (!empty($a['choice_ids'])) {
                            $DB->insert_record('questionnaire_resp_single', [
                                'response_id' => $responseid,
                                'question_id' => $qid,
                                'choice_id'   => reset($a['choice_ids']),
                            ]);
                        }
                        break;

                    case self::QTYPE_CHECKBOX:
                        foreach ($a['choice_ids'] as $cid) {
                            $DB->insert_record('questionnaire_resp_multiple', [
                                'response_id' => $responseid,
                                'question_id' => $qid,
                                'choice_id'   => $cid,
                            ]);
                        }
                        break;

                    case self::QTYPE_RATE:
                        foreach ($a['rate_values'] as $rv) {
                            $DB->insert_record('questionnaire_response_rank', [
                                'response_id' => $responseid,
                                'question_id' => $qid,
                                'choice_id'   => $rv['choiceid'],
                                'rank'        => $rv['rank'],
                            ]);
                        }
                        break;

                    // section/pagebreak: no stored answer, skip silently.
                }
            }

        // 3. Log the attempt (skip if table isn't provisioned yet — avoids failing the whole submission).
            if ($DB->get_manager()->table_exists('questionnaire_attempts')) {
                $DB->insert_record('questionnaire_attempts', [
                    'qid'          => $q->id,
                    'userid'       => $params['userid'],
                    'rid'          => $responseid,
                    'timemodified' => time(),
                ]);
            }

            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }

        return ['success' => true, 'responseid' => (int)$responseid];
    }

    public static function submit_questionnaire_returns() {
        return new external_single_structure([
            'success'    => new external_value(PARAM_BOOL, 'Submitted OK'),
            'responseid' => new external_value(PARAM_INT,  'New response ID'),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 4. TEACHER/ADMIN REPORT
    // ─────────────────────────────────────────────────────────
    public static function get_questionnaire_report_parameters() {
        return new external_function_parameters([
            'questionnaireid' => new external_value(PARAM_INT, 'Questionnaire ID'),
        ]);
    }

    public static function get_questionnaire_report($questionnaireid) {
        global $DB;

        $params = self::validate_parameters(self::get_questionnaire_report_parameters(),
            ['questionnaireid' => $questionnaireid]);

        $q = $DB->get_record('questionnaire', ['id' => $params['questionnaireid']], '*', MUST_EXIST);
        $survey = $DB->get_record('questionnaire_survey', ['id' => $q->sid], '*', MUST_EXIST);

        $total_responses = $DB->count_records('questionnaire_response',
            ['questionnaireid' => $q->id, 'complete' => 'y']);

        $questions = $DB->get_records('questionnaire_question', ['surveyid' => $survey->id], 'position ASC');
        $report = [];

        foreach ($questions as $qq) {
            $type = (int)$qq->type_id;
            $breakdown = [];

            if (in_array($type, [self::QTYPE_RADIO, self::QTYPE_DROPDOWN, self::QTYPE_CHECKBOX])) {
                $choices = $DB->get_records('questionnaire_quest_choice', ['question_id' => $qq->id]);
                foreach ($choices as $c) {
                    $count = $DB->count_records_sql(
                        "SELECT COUNT(*) FROM {questionnaire_response_other} ro
                          JOIN {questionnaire_response} r ON r.id = ro.response_id
                         WHERE ro.question_id = :qid AND ro.response = :choiceid
                           AND r.questionnaireid = :qnid AND r.complete = 'y'",
                        ['qid' => $qq->id, 'choiceid' => $c->id, 'qnid' => $q->id]
                    );
                    $breakdown[] = ['label' => $c->content, 'count' => $count];
                }
            } elseif ($type === self::QTYPE_YESNO) {
                foreach (['y' => 'Yes', 'n' => 'No'] as $val => $label) {
                    $count = $DB->count_records_sql(
                        "SELECT COUNT(*) FROM {questionnaire_response_bool} rb
                          JOIN {questionnaire_response} r ON r.id = rb.response_id
                         WHERE rb.question_id = :qid AND rb.choice_id = :val
                           AND r.questionnaireid = :qnid AND r.complete = 'y'",
                        ['qid' => $qq->id, 'val' => $val, 'qnid' => $q->id]
                    );
                    $breakdown[] = ['label' => $label, 'count' => $count];
                }
            } elseif ($type === self::QTYPE_RATE) {
                $avg = (float)($DB->get_field_sql(
                    "SELECT AVG(rr.rank) FROM {questionnaire_response_rank} rr
                       JOIN {questionnaire_response} r ON r.id = rr.response_id
                      WHERE rr.question_id = :qid AND r.questionnaireid = :qnid AND r.complete = 'y' AND rr.rank >= 0",
                    ['qid' => $qq->id, 'qnid' => $q->id]
                ) ?? 0);
                $breakdown[] = ['label' => 'Average rating', 'count' => round($avg, 2)];
            }
            // text/essay/numeric/date: left to raw response listing below rather than aggregated.

            $report[] = [
                'questionid' => (int)$qq->id,
                'content'    => format_text($qq->content, FORMAT_HTML),
                'type_name'  => self::type_name($type),
                'breakdown'  => $breakdown,
            ];
        }

        return [
            'questionnaireid'  => (int)$q->id,
            'name'             => $q->name,
            'total_responses'  => $total_responses,
            'questions_report' => $report,
        ];
    }

    public static function get_questionnaire_report_returns() {
        return new external_single_structure([
            'questionnaireid'  => new external_value(PARAM_INT,  'Questionnaire ID'),
            'name'             => new external_value(PARAM_TEXT, 'Name'),
            'total_responses'  => new external_value(PARAM_INT,  'Total complete responses'),
            'questions_report' => new external_multiple_structure(
                new external_single_structure([
                    'questionid' => new external_value(PARAM_INT,  'Question ID'),
                    'content'    => new external_value(PARAM_RAW,  'Question text'),
                    'type_name'  => new external_value(PARAM_TEXT, 'Type'),
                    'breakdown'  => new external_multiple_structure(
                        new external_single_structure([
                            'label' => new external_value(PARAM_TEXT,  'Choice label / metric name'),
                            'count' => new external_value(PARAM_FLOAT, 'Count or average'),
                        ])
                    ),
                ])
            ),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 5. USER'S OWN RESPONSE
    // ─────────────────────────────────────────────────────────
    public static function get_my_questionnaire_response_parameters() {
        return new external_function_parameters([
            'questionnaireid' => new external_value(PARAM_INT, 'Questionnaire ID'),
            'userid'          => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function get_my_questionnaire_response($questionnaireid, $userid) {
        global $DB;

        $params = self::validate_parameters(self::get_my_questionnaire_response_parameters(),
            ['questionnaireid' => $questionnaireid, 'userid' => $userid]);

        $responses = $DB->get_records('questionnaire_response', [
            'questionnaireid' => $params['questionnaireid'],
            'userid'          => $params['userid'],
            'complete'        => 'y',
        ], 'submitted DESC, id DESC', '*', 0, 1); // latest attempt only, since multiple attempts are allowed
        $response = $responses ? reset($responses) : false;

        if (!$response) {
            return ['has_response' => false, 'answers' => []];
        }

        $answers = [];
        foreach ($DB->get_records('questionnaire_response_text', ['response_id' => $response->id]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => $r->response];
        }
        foreach ($DB->get_records('questionnaire_response_bool', ['response_id' => $response->id]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => $r->choice_id];
        }
        foreach ($DB->get_records('questionnaire_response_date', ['response_id' => $response->id]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => $r->response];
        }
        foreach ($DB->get_records('questionnaire_response_other', ['response_id' => $response->id]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => (string)$r->response];
        }
        foreach ($DB->get_records('questionnaire_response_rank', ['response_id' => $response->id]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => $r->choice_id . ':' . $r->rank];
        }
        // Radio / dropdown answers — submit_questionnaire() stores these in
        // questionnaire_resp_single, which was previously never read here.
        foreach ($DB->get_records('questionnaire_resp_single', ['response_id' => $response->id]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => (string)$r->choice_id];
        }
        // Checkbox answers (multiple rows per question) — stored in
        // questionnaire_resp_multiple, also previously never read here.
        foreach ($DB->get_records('questionnaire_resp_multiple', ['response_id' => $response->id]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => (string)$r->choice_id];
        }

        return ['has_response' => true, 'answers' => $answers];
    }

    public static function get_my_questionnaire_response_returns() {
        return new external_single_structure([
            'has_response' => new external_value(PARAM_BOOL, 'User already responded'),
            'answers'      => new external_multiple_structure(
                new external_single_structure([
                    'questionid' => new external_value(PARAM_INT,  'Question ID'),
                    'value'      => new external_value(PARAM_RAW,  'Raw stored value'),
                ])
            ),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 5b. USER'S ALL RESPONSES (every attempt, for questionnaires that allow
    //     multiple submissions). Purely additive — does not touch or replace
    //     get_my_questionnaire_response() above, which still returns the
    //     latest attempt only.
    // ─────────────────────────────────────────────────────────
    public static function get_my_questionnaire_responses_parameters() {
        return new external_function_parameters([
            'questionnaireid' => new external_value(PARAM_INT, 'Questionnaire ID'),
            'userid'          => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function get_my_questionnaire_responses($questionnaireid, $userid) {
        global $DB;

        $params = self::validate_parameters(self::get_my_questionnaire_responses_parameters(),
            ['questionnaireid' => $questionnaireid, 'userid' => $userid]);

        // Oldest first, so attempt=1 matches Moodle's own "Individual responses" numbering.
        $rows = $DB->get_records('questionnaire_response', [
            'questionnaireid' => $params['questionnaireid'],
            'userid'          => $params['userid'],
            'complete'        => 'y',
        ], 'submitted ASC, id ASC');

        if (empty($rows)) {
            return ['has_response' => false, 'total_attempts' => 0, 'responses' => []];
        }

        $responses = [];
        $attempt = 0;
        foreach ($rows as $response) {
            $attempt++;
            $responses[] = [
                'responseid' => (int)$response->id,
                'attempt'    => $attempt,
                'submitted'  => (int)$response->submitted,
                'answers'    => self::get_answers_for_response($response->id),
            ];
        }

        return [
            'has_response'   => true,
            'total_attempts' => count($responses),
            'responses'      => $responses,
        ];
    }

    public static function get_my_questionnaire_responses_returns() {
        return new external_single_structure([
            'has_response'   => new external_value(PARAM_BOOL, 'User has at least one response'),
            'total_attempts' => new external_value(PARAM_INT,  'Number of attempts submitted'),
            'responses'      => new external_multiple_structure(
                new external_single_structure([
                    'responseid' => new external_value(PARAM_INT, 'Response record ID'),
                    'attempt'    => new external_value(PARAM_INT, 'Attempt number, 1 = first ever submitted'),
                    'submitted'  => new external_value(PARAM_INT, 'Unix timestamp of submission'),
                    'answers'    => new external_multiple_structure(
                        new external_single_structure([
                            'questionid' => new external_value(PARAM_INT, 'Question ID'),
                            'value'      => new external_value(PARAM_RAW,  'Raw stored value'),
                        ])
                    ),
                ])
            ),
        ]);
    }

    /**
     * Shared answer-lookup used only by get_my_questionnaire_responses().
     * Duplicates (rather than refactors) the per-type lookups already inlined
     * in get_my_questionnaire_response() above, so that function's working
     * code path is left completely untouched.
     */
    private static function get_answers_for_response(int $responseid): array {
        global $DB;

        $answers = [];
        foreach ($DB->get_records('questionnaire_response_text', ['response_id' => $responseid]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => $r->response];
        }
        foreach ($DB->get_records('questionnaire_response_bool', ['response_id' => $responseid]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => $r->choice_id];
        }
        foreach ($DB->get_records('questionnaire_response_date', ['response_id' => $responseid]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => $r->response];
        }
        foreach ($DB->get_records('questionnaire_response_other', ['response_id' => $responseid]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => (string)$r->response];
        }
        foreach ($DB->get_records('questionnaire_response_rank', ['response_id' => $responseid]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => $r->choice_id . ':' . $r->rank];
        }
        foreach ($DB->get_records('questionnaire_resp_single', ['response_id' => $responseid]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => (string)$r->choice_id];
        }
        foreach ($DB->get_records('questionnaire_resp_multiple', ['response_id' => $responseid]) as $r) {
            $answers[] = ['questionid' => (int)$r->question_id, 'value' => (string)$r->choice_id];
        }
        return $answers;
    }

    // ─────────────────────────────────────────────────────────
    private static function type_name(int $type): string {
        $map = [
            self::QTYPE_YESNO       => 'yesno',
            self::QTYPE_TEXT        => 'text',
            self::QTYPE_ESSAY       => 'essay',
            self::QTYPE_RADIO       => 'radio',
            self::QTYPE_CHECKBOX    => 'checkbox',
            self::QTYPE_DROPDOWN    => 'dropdown',
            self::QTYPE_RATE        => 'rate',
          self::QTYPE_DATE        => 'date',
            self::QTYPE_NUMERIC     => 'numeric',
            self::QTYPE_SLIDER      => 'slider',
            self::QTYPE_SECTIONTEXT => 'section',
            self::QTYPE_PAGEBREAK   => 'pagebreak',
        ];
        return $map[$type] ?? 'unknown';
    }

    // =========================================================
    // PUSH NOTIFICATIONS — FCM DEVICE TOKEN MANAGEMENT
    // =========================================================

    // ---------------------------------------------------------
    // rapmobile_register_device
    //
    // Called by Flutter:
    //   (a) After every successful login
    //   (b) Inside FirebaseMessaging.instance.onTokenRefresh listener
    //       (FCM can rotate the token at any time, not just on login)
    //
    // Uses MySQL INSERT … ON DUPLICATE KEY UPDATE for a true atomic
    // upsert driven by UNIQUE(userid, fcm_token) on rapmobile_devices.
    // No SELECT-then-INSERT race condition.
    // ---------------------------------------------------------

    public static function register_device_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(
                PARAM_INT,
                'Moodle user ID.',
                VALUE_REQUIRED
            ),
            'fcm_token' => new external_value(
                PARAM_TEXT,
                'FCM registration token from FirebaseMessaging.instance.getToken().',
                VALUE_REQUIRED
            ),
            'platform' => new external_value(
                PARAM_ALPHA,
                "Device platform: 'android' or 'ios'.",
                VALUE_REQUIRED
            ),
            'app_version' => new external_value(
                PARAM_TEXT,
                'App version string e.g. "1.4.2". Optional — used for diagnostics.',
                VALUE_OPTIONAL,
                null
            ),
        ]);
    }

    public static function register_device(
        int     $userid,
        string  $fcm_token,
        string  $platform,
        ?string $app_version = null
    ): array {
        global $DB;

        $p = self::validate_parameters(self::register_device_parameters(), [
            'userid'      => $userid,
            'fcm_token'   => $fcm_token,
            'platform'    => $platform,
            'app_version' => $app_version,
        ]);

        if (!in_array($p['platform'], ['android', 'ios'], true)) {
            throw new invalid_parameter_exception(
                "platform must be 'android' or 'ios', got: " . s($p['platform'])
            );
        }

        if (!$DB->record_exists('user', ['id' => $p['userid'], 'deleted' => 0])) {
            throw new invalid_parameter_exception("User {$p['userid']} not found.");
        }

        $now = time();

        // Atomic upsert — UNIQUE KEY on (userid, fcm_token) prevents duplicates.
        // On collision: refresh active=1, platform, app_version, lastupdated.
        // On insert: set created + lastupdated to now.
        $DB->execute(
            "INSERT INTO {rapmobile_devices}
                        (userid, fcm_token, platform, app_version, active, created, lastupdated)
             VALUES      (:userid, :token, :platform, :appver, 1, :created, :updated)
             ON DUPLICATE KEY UPDATE
                         platform    = VALUES(platform),
                         app_version = VALUES(app_version),
                         active      = 1,
                         lastupdated = VALUES(lastupdated)",
            [
                'userid'    => $p['userid'],
                'token'     => $p['fcm_token'],
                'platform'  => $p['platform'],
                'appver'    => $p['app_version'],
                'created'   => $now,
                'updated'   => $now,
            ]
        );

        return ['success' => true, 'message' => 'Device registered.'];
    }

    public static function register_device_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'true on success'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }

    // ---------------------------------------------------------
    // rapmobile_unregister_device
    //
    // Called by Flutter on logout (for THIS device only, not all devices).
    // Sets active=0 — NEVER deletes the row.
    // Row is preserved so if the user logs back in on the same device,
    // register_device just flips active back to 1.
    // Deliberately a no-op (not an error) if the token doesn't exist.
    // ---------------------------------------------------------

    public static function unregister_device_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(
                PARAM_INT,
                'Moodle user ID.',
                VALUE_REQUIRED
            ),
            'fcm_token' => new external_value(
                PARAM_TEXT,
                'The specific FCM token to deactivate. Only this device is affected.',
                VALUE_REQUIRED
            ),
        ]);
    }

    public static function unregister_device(int $userid, string $fcm_token): array {
        global $DB;

        $p = self::validate_parameters(self::unregister_device_parameters(), [
            'userid'    => $userid,
            'fcm_token' => $fcm_token,
        ]);

        // Single atomic UPDATE — 0 rows affected if not found, which is fine.
        $DB->execute(
            "UPDATE {rapmobile_devices}
                SET  active      = 0,
                     lastupdated = :now
              WHERE  userid    = :userid
                AND  fcm_token = :token",
            [
                'now'    => time(),
                'userid' => $p['userid'],
                'token'  => $p['fcm_token'],
            ]
        );

        return ['success' => true, 'message' => 'Device unregistered.'];
    }

    public static function unregister_device_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'true always — no-op if token not found'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }

}