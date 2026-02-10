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
  // --- ✅ Add Points and Rank ---
        $data->mypoints = 0;
        $data->myrank = '-';

        if (function_exists('get_total_points') && function_exists('get_my_rank')) {
            $data->mypoints = get_available_points($USER->id);
            $data->myrank = get_my_rank($USER->id);
        }

        require_once($CFG->dirroot . '/mod/ilt/lib.php');
// --- ✅ Fetch Attendance Data ---
$attended = 0;
$total = 0;
$data->attendance = '00/00';

if ($DB->get_manager()->table_exists('ilt_signups') && $DB->get_manager()->table_exists('ilt_signups_status')) {

    // Total ILT sessions user has signed up for
    $totalsql = "
        SELECT COUNT(DISTINCT s.id)
        FROM {ilt_signups} s
        JOIN {ilt_signups_status} ss ON ss.signupid = s.id
        WHERE s.userid = :userid
          AND ss.superceded != 1
    ";
    $total = $DB->count_records_sql($totalsql, ['userid' => $USER->id]);

    // Sessions attended (statuscode = 100, superceded = 0)
    $attendsql = "
        SELECT COUNT(DISTINCT s.id)
        FROM {ilt_signups} s
        JOIN {ilt_signups_status} ss ON ss.signupid = s.id
        WHERE s.userid = :userid
          AND ss.superceded = 0
          AND ss.statuscode = 100
    ";

    $attended = $DB->count_records_sql($attendsql, [
        'userid' => $USER->id
    ]);

    // Format output
    $data->attendance = sprintf('%02d/%02d', $attended, $total);
}

        return $data;
    }
}
