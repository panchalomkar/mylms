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
trait get_livesession_detail {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function get_livesession_detail_parameters() {
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
    public static function get_livesession_detail($userid) {
        global $DB,$CFG;
        $currentdate = strtotime(date('d-m-Y'));
        
        $fivedaysbeforedate = strtotime(date('d-m-Y', strtotime("+15 days",$currentdate)));
 
        $course_modules = $DB->get_records_sql("SELECT e.courseid,ue.userid FROM {enrol} e 
        INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid
        WHERE ue.userid = $userid" );
      
         $dataget = array();
        foreach ($course_modules as $keyalue) {
               
        $userdetail = $DB->get_record('user', array('id' => $keyalue->userid));

        $instanceid = $DB->get_records_sql("SELECT e.*, cm.id as cmid, cm.module FROM {event} e 
        INNER JOIN {course_modules} cm ON cm.course = e.courseid 
        WHERE e.courseid = $keyalue->courseid AND e.modulename IN('zoom','googlemeet','facetoface','ilt') AND e.timestart BETWEEN $currentdate AND $fivedaysbeforedate ORDER BY e.timestart ASC");
      foreach ($instanceid as $value) {   
        $modulesname = $DB->get_record('modules', array('name' => $value->modulename));
        $getact = $DB->get_record('course_modules', array('course' => $value->courseid, 'instance' => $value->instance, 'module' => $modulesname->id));
        // $current = date();
        // $instanceid = $DB->get_record('course_modules', array('instance' => $getact->id, 'course' => $getact->course,  'module' => $modulesid->id));
        if ($value->modulename == 'facetoface' || $value->modulename == 'ilt') {
            $sessionmod = 'Offline';
           }else{
            $sessionmod = 'Online';
	   }
	if ($value->modulename == 'zoom') {
		$record = $DB->get_record('zoom', ['id' => $value->instance]);
		$imgurl = $CFG->wwwroot.'/theme/remui/pix/zoom-demo.png';
} elseif ($value->modulename == 'googlemeet') {
	$record = $DB->get_record('googlemeet', ['id' => $value->instance]);
	$imgurl = $CFG->wwwroot.'/theme/remui/pix/g-meet.png';
} elseif ($value->modulename == 'facetoface') {
	$record = $DB->get_record('facetoface', ['id' => $value->instance]);
	$imgurl = $CFG->wwwroot.'/theme/remui/pix/ilt.png';
} elseif ($value->modulename == 'ilt') {
	$record = $DB->get_record('ilt', ['id' => $value->instance]); // Assuming 'ilt' is the table name
	$imgurl = $CFG->wwwroot.'/theme/remui/pix/ilt.png';
}

        $dataget[] = array(
             'activityname' => $record->name,
             'starttime' => date("d-M-Y, H:i a", $value->timestart),
             'url' => $CFG->wwwroot.'/mod/'.$value->modulename.'/view.php?id='.$getact->id,
             'username' => $userdetail->firstname.' '.$userdetail->lastname,
             'sessionmod' => $sessionmod,
             'imgurl' => $imgurl
         );
       }
       }
 
       $response = array(
        "livesessiondetail" => $dataget,
    );

        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8"); 
        echo json_encode($response);
        die;
    return $response;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function get_livesession_detail_returns() {
        return new external_single_structure(
            array(
                'livesessiondetail' => new external_value(PARAM_RAW, 'livesessiondetail', null),
            )
        );
    }
}
