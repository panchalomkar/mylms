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
use core_completion\progress;

/**
 * Trait implementing the external function local_corporate_api_complete_edwiserreports_installation.
 */
trait get_courses_detail {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function get_courses_detail_parameters() {
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
    public static function get_courses_detail($userid) {
        global $DB,$CFG;
        require_once($CFG->dirroot."/local/corporate_api/lib.php");
        $getsqlss = "SELECT e.courseid FROM {enrol} e 
                INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid  
                WHERE ue.userid = $userid ORDER BY ue.id DESC"; 
                $courses = $DB->get_records_sql($getsqlss);
                $activities = [];
                $geteventdetail = [];
                $activitiescount = '';
                foreach ($courses as $value) {
                    $course = $DB->get_record('course', array('id' => $value->courseid));
                    $progressdata = \core_completion\progress::get_course_progress_percentage($course,$userid);
                    $percentage = floor($progressdata);

                    $getimg = get_c_images($value->courseid);
                    $progress = $percentage;


                    $getcust = "SELECT cd.value FROM {customfield_field} cf
                    INNER JOIN {customfield_data} cd ON cf.id = cd.fieldid  
                    WHERE cd.instanceid = $value->courseid AND cf.shortname = 'edwcoursedurationinhours' ORDER BY cd.id DESC"; 
                    $getduration = $DB->get_record_sql($getcust);

                    $videourl = "SELECT cd.value FROM {customfield_field} cf
                    INNER JOIN {customfield_data} cd ON cf.id = cd.fieldid  
                    WHERE cd.instanceid = $value->courseid AND cf.shortname = 'edwcourseintrovideourlembedded' ORDER BY cd.id DESC"; 
                    $coursevideo = $DB->get_record_sql($videourl);
                    $customcertisql = "SELECT A.id, B.userid, A.templateid, A.course, A.name,B.timecreated FROM {customcert} A INNER JOIN {customcert_issues} B ON 
                    B.customcertid = A.templateid Where A.course = $course->id and B.userid = $userid";
		    $getcertificate = $DB->get_record_sql($customcertisql);
		    $certificateurl = $CFG->wwwroot."/mod/customcert/my_certificates.php?userid=$userid&certificateid=1&downloadcert=1";
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
                        'coursedescription' => $course->summary,
                        'coursestartdate' => DATE('Y-m-d', $course->startdate),
                        'courseendate' => $formatted_date,
                        'course_videourl' => $coursevideo->value,
                        'course_duration' => $getduration->value,
                        'certificatename' => $getcertificate->name,
                        'awarddate'       => userdate($getcertificate->timecreated),
                        'certificateurl'  => $certificateurl,
                    ];

                    
                    $currentdate = strtotime(date('d-m-Y'));
                    $threedaysbeforedate = strtotime(date('d-m-Y', strtotime("+7day",$currentdate)));
                    $geteventde = $DB->get_records_sql("SELECT * FROM {event}  WHERE courseid = $value->courseid AND timestart BETWEEN  $currentdate AND $threedaysbeforedate");
                    foreach ($geteventde as $value) {
                        $getcmid = $DB->get_record('course_modules', array('course' => $value->courseid, 'instance' => $value->instance));
                        $geteventdetail[] = [
                            'id' => $value->id,
                            'name' => $value->name,
                            'eventtype' => $value->name,
                            'duedate ' => DATE('Y-m-d', $value->timestart),
                            'timeduration' => $value->timeduration,
                            'url' => $CFG->wwwroot.'/mod/'.$value->modulename.'/view.php?id='. $getcmid->id
                        ];
                    }

                 
                
                    $mods = get_fast_modinfo($value->courseid);
                   $activitiescount = count($mods->get_cms());
                  
                };

        $response = array(
            "countactivity" => $activitiescount,
            "countsevendays" => $activitiescount,
            "countthirtydays" => $activitiescount,

            "evendata" => $geteventdetail,
            "allcourses" => $activities,
        );
        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8"); 
        echo json_encode($response);
        die;
        //return $response;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function get_courses_detail_returns() {
        return new external_single_structure(
            array(
                'countactivity' => new external_value(PARAM_RAW, 'Status', null),
                'countsevendays' => new external_value(PARAM_RAW, 'Status', null),
                'countthirtydays' => new external_value(PARAM_RAW, 'Status', null),
                'evendata' => new external_value(PARAM_RAW, 'Status', null),
                'allcourses' => new external_value(PARAM_RAW, 'Reports Data', 0)
            )
        );
    }
}
