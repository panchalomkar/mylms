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
 * Class containing data for myprofile block.
 *
 * @package    block_myprofile
 * @copyright  2018 Mihail Geshoski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_myprofile\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Class containing data for myprofile block.
 *
 * @copyright  2018 Mihail Geshoski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class myprofile implements renderable, templatable {

    /**
     * @var object An object containing the configuration information for the current instance of this block.
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param object $config An object containing the configuration information for the current instance of this block.
     */
    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER, $OUTPUT, $DB, $CFG;
        require_once($CFG->dirroot . '/local/mydashboard/lib.php'); // 🔗 Reuse same functions

        $data = new \stdClass();

        // --- ✅ Fetch custom profile field: designation ---
        $designation = '';
        $customfield = $DB->get_record('user_info_data', [
            'userid' => $USER->id,
            'fieldid' => $DB->get_field('user_info_field', 'id', ['shortname' => 'designation'])
        ]);

        if (!empty($customfield) && !empty($customfield->data)) {
            $designation = $customfield->data;
        }

        $data->designation = $designation ?: '';

        // --- Basic User Info ---
        $data->userpicture = $OUTPUT->user_picture($USER, ['size' => 100]);
        $data->userfullname = ucwords(fullname($USER)); // Capitalize each word
        $data->useremail = s($USER->email);

        // --- Dynamic Role (Admin, Teacher, Student, Manager, etc.) ---
        $context = \context_system::instance();
        $roles = get_user_roles($context, $USER->id, true);
        if (!empty($roles)) {
            $role = reset($roles);
            $roleinfo = $DB->get_record('role', ['id' => $role->roleid]);
            $data->userrole = ucfirst(strtolower(role_get_name($roleinfo, $context)));
        } else {
            $data->userrole = 'User';
        }

        // --- Ensure country property is always valid ---
        if (!isset($USER->country)) {
            // Default value from Moodle config if country not set
            $USER->country = \core_user::get_property_default('country') ?: '';
        }

        // --- Build city + country string safely ---
        $city = trim($USER->city ?? '');
        $countryname = '';

        if (!empty($USER->country)) {
            $countries = get_string_manager()->get_list_of_countries();
            if (isset($countries[$USER->country])) {
                $countryname = $countries[$USER->country];
            }
        }

        if (!empty($city) && !empty($countryname)) {
            $data->userlocation = $city . ', ' . $countryname;
        } else if (!empty($city)) {
            $data->userlocation = $city;
        } else if (!empty($countryname)) {
            $data->userlocation = $countryname;
        } else {
            $data->userlocation = ''; // Moodle-friendly fallback
        }

        // --- Edit Profile Link ---
        $data->editprofileurl = new \moodle_url('/user/edit.php', [
            'id' => $USER->id,
            'returnto' => 'profile'
        ]);
        $data->gamificationurl = new \moodle_url('/local/mydashboard/');
        // $data->attendanceurl = new \moodle_url('/local/attendance/');
$data->attendanceurl = new \moodle_url('/local/mydashboard/attendance.php');
        // --- ✅ Add Points and Rank ---
        $data->mypoints = 0;
        $data->myrank = '-';

        if (function_exists('get_total_points') && function_exists('get_my_rank')) {
            $data->mypoints = get_available_points($USER->id);
            $data->myrank = get_my_rank($USER->id);
        }

        // --- ✅ Combined Attendance: ILT sessions + mod_attendance sessions ---
        // TOTAL = every session the user is signed up / rostered for, across ALL courses,
        //         regardless of whether a trainer has taken attendance yet.
        // ATTENDED = the subset of those marked as "present".
        $ilttotal = 0;
        $iltattended = 0;
        $modtotal = 0;
        $modattended = 0;

        // --- ILT (mod_ilt) sessions, across all courses ---
        if (file_exists($CFG->dirroot . '/mod/ilt/lib.php')) {
            require_once($CFG->dirroot . '/mod/ilt/lib.php');
        }

        if ($DB->get_manager()->table_exists('ilt_signups') && $DB->get_manager()->table_exists('ilt_signups_status')) {

            // Total = every session the user is signed up for, from {ilt_signups} directly.
            // (Previously this joined {ilt_signups_status} with an INNER JOIN, which silently
            // dropped any session that hadn't been marked/graded yet, undercounting the total.)
            $totalsql = "
                SELECT COUNT(DISTINCT s.sessionid)
                FROM {ilt_signups} s
                WHERE s.userid = :userid
            ";
            $ilttotal = $DB->count_records_sql($totalsql, ['userid' => $USER->id]);

            // Attended = signups whose CURRENT (non-superceded) status is 100 (attended).
            $attendsql = "
                SELECT COUNT(DISTINCT s.sessionid)
                FROM {ilt_signups} s
                JOIN {ilt_signups_status} ss ON ss.signupid = s.id
                WHERE s.userid = :userid
                  AND ss.superceded = 0
                  AND ss.statuscode = 100
            ";
            $iltattended = $DB->count_records_sql($attendsql, ['userid' => $USER->id]);
        }

        // --- mod_attendance activity sessions, across all courses ---
        if ($DB->get_manager()->table_exists('attendance_sessions')
            && $DB->get_manager()->table_exists('attendance')
            && $DB->get_manager()->table_exists('attendance_log')
            && $DB->get_manager()->table_exists('attendance_statuses')) {

            // Total = every session in every attendance activity, in every course the user is
            // actively enrolled in, that applies to this user (either an "all students" session,
            // groupid = 0, or a session restricted to a group the user belongs to).
            // This counts sessions whether or not the trainer has taken attendance yet, fixing
            // the previous query which only counted sessions with an existing {attendance_log} row.
            $modtotalsql = "
                SELECT COUNT(DISTINCT s.id)
                FROM {attendance_sessions} s
                JOIN {attendance} att ON att.id = s.attendanceid
                JOIN {course_modules} cm ON cm.instance = att.id
                JOIN {modules} md ON md.id = cm.module AND md.name = 'attendance'
                JOIN {enrol} e ON e.courseid = att.course
                JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = :userid1
                WHERE cm.visible = 1
                  AND e.status = 0
                  AND ue.status = 0
                  AND (
                        s.groupid = 0
                        OR s.groupid IN (
                            SELECT gm.groupid FROM {groups_members} gm WHERE gm.userid = :userid2
                        )
                      )
            ";
            $modtotal = $DB->count_records_sql($modtotalsql, [
                'userid1' => $USER->id,
                'userid2' => $USER->id,
            ]);

            // Attended = sessions where a log entry exists for this user with a status marked
            // "Present" (matches acronym 'P' or a description starting with 'Present', to cope
            // with activities that use custom status sets).
            $modattendsql = "
                SELECT COUNT(DISTINCT al.sessionid)
                FROM {attendance_log} al
                JOIN {attendance_sessions} s ON s.id = al.sessionid
                JOIN {attendance_statuses} st ON st.id = al.statusid
                WHERE al.studentid = :userid
                  AND st.deleted = 0
                  AND (st.acronym = :acronym OR " . $DB->sql_like('st.description', ':description') . ")
            ";
            $modattended = $DB->count_records_sql($modattendsql, [
                'userid' => $USER->id,
                'acronym' => 'P',
                'description' => 'Present%',
            ]);
        }

        // --- Combine both sources ---
        $totalsessions = $ilttotal + $modtotal;
        $attendedsessions = $iltattended + $modattended;

        $data->attendance = sprintf('%02d/%02d', $attendedsessions, $totalsessions);

        return $data;
    }
}