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
 * Reports block external apis
 *
 * @package     local_corporate_api
 * @copyright   2019 wisdmlabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_corporate_api\external;

defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use context_system;
use stdClass;
use core_user;
use core_completion\progress;

/**
 * Trait implementing the external function local_corporate_api_complete_edwiserreports_installation.
 */
trait get_course_report_detail {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function get_course_report_detail_parameters() {
        return new external_function_parameters(
            array (
                'userid' => new external_value(PARAM_INT, 'User id')
            )
        );
    }

    /**
     * Complete edwiser report installation
     *
     * @return object Configuration
     */
    public static function get_course_report_detail($userid) {
        global $DB,$CFG,$OUTPUT;;
        require_once($CFG->dirroot."/local/corporate_api/lib.php");

        $getsqlss = "SELECT e.courseid FROM {enrol} e 
                INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid  
                WHERE ue.userid = $userid ORDER BY ue.id DESC"; 
                $courses = $DB->get_records_sql($getsqlss);
                $activities = [];
                foreach ($courses as $value) {
                    $course = $DB->get_record('course', array('id' => $value->courseid));
                    $progressdata = \core_completion\progress::get_course_progress_percentage($course,$userid);
                    $percentage = floor($progressdata);

                    $getimg = get_c_images($value->courseid);
                    $progress = '<div class="progress">
                    <div class="progress-bar bg-success" role="progressbar" style="width: '.$percentage.'%" aria-valuenow="'.$percentage.'" aria-valuemin="0" aria-valuemax="100">'.$percentage.'%</div>
                    </div>';


                    $getcust = "SELECT cd.value FROM {customfield_field} cf
                    INNER JOIN {customfield_data} cd ON cf.id = cd.fieldid  
                    WHERE cd.instanceid = $value->courseid AND cf.shortname = 'edwcoursedurationinhours' ORDER BY cd.id DESC"; 
                    $getduration = $DB->get_record_sql($getcust);

                    $videourl = "SELECT cd.value FROM {customfield_field} cf
                    INNER JOIN {customfield_data} cd ON cf.id = cd.fieldid  
                    WHERE cd.instanceid = $value->courseid AND cf.shortname = 'edwcourseintrovideourlembedded' ORDER BY cd.id DESC"; 
                    $coursevideo = $DB->get_record_sql($videourl);
		    $courseURL = $CFG->wwwroot."course/view.php?id=".$course->id;
		    if ($course ->enddate== 0) {
    $formatted_date = null;
} else {
   $formatted_date = date('Y-m-d', $course->enddate);
}
                    $activities[] = [
                        'id' => $course->id,
                        'name' => $course->fullname,
                        'courseimg' => $getimg,
                        'courseprogress' => $progress,
                        'coursedescription' => $course->description,
                        'coursestartdate' => DATE('Y-m-d', $course->startdate),
                        'courseendate' => $formatted_date,
                        'course_videourl' => $coursevideo->value,
                        'course_duration' => $getduration->value,
                        'course_url' => $getduration->value
                    ];
                };
                  

                    // get user profile detail ---------------
                $getusers = $DB->get_record('user', array('id' => $userid));
                    $user_object = core_user::get_user($getusers->id);
                    $person_profile_pic = $OUTPUT->user_picture($user_object,array('link'=>false));
                    $getuserdetail[] = array(
                        'id' => $getusers->id,
                        'studentname' => $getusers->firstname.' '.$getusers->lastname,
                        'studentimage' => $person_profile_pic,
                        'studentemail' => $getusers->email,
                        'department' => $getusers->department,
                        'mobileno' => $getusers->phone1,
                    );



                $response = array(
                    "allcourses" => $activities,
                    "userdetail" => $getuserdetail,
                );

        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8"); 
        echo json_encode($response);
        die;
//        return $response;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function get_course_report_detail_returns() {
        return new external_single_structure(
            array(
                'allcourses' => new external_value(PARAM_RAW, 'Reports Data', 0),
                'userdetail' => new external_value(PARAM_RAW, 'user detail', 0),
            )
        );
    }
}
