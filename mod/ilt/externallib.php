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
 * External ilt API
 *
 * @package    mod_ilt
 * @since      2021
 * @copyright  paradiso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/user/externallib.php");
require_once("$CFG->dirroot/mod/ilt/lib.php");

/**
 * Assign functions
 * @copyright paradiso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ilt_external extends external_api {

    public static function get_ilt_session_ecommerce() {
        global $CFG, $USER,$DB;

        $coursetype_ilt = $DB->get_records('course_info_data', ['data' => 'Instructor Led Training']);
        $finaldata = array();
        $getdata = array();
        $currenttime = time();
        $modulesid = $DB->get_record('modules', ['name' => 'ilt']);
        foreach($coursetype_ilt as $row) {
            //$getdata['courses'][$row->courseid]['courseids'] =  $row->courseid;
            $coursemodules = $DB->get_records('course_modules', ['course' => $row->courseid,'module' => $modulesid->id]);
            foreach($coursemodules as $modules) {
                $iltdata = $DB->get_records_sql("SELECT s.id as sessionid,s.ilt,s.sessionname,
                            s.sessionname,s.duration,s.capacity,m.*
                            FROM {ilt_sessions} s
                            LEFT JOIN (SELECT group_concat(timestart ORDER BY id ASC separator ',') as `timestarted`, 
                            group_concat(timefinish ORDER BY id ASC separator ',') as `timefinished`,
                            group_concat(id ORDER BY id ASC separator ',') as `datesid`,
                            sessionid,timestart,timefinish, min(timestart) AS mintimestart
                            FROM {ilt_sessions_dates} GROUP BY sessionid) m ON m.sessionid = s.id
                            WHERE s.ilt = $modules->instance AND m.mintimestart > $currenttime
                            ORDER BY s.datetimeknown, m.mintimestart");
                foreach($iltdata as $iltdata_row) {
                    $getdata['courses'][$row->courseid]['courseids'] =  $row->courseid;
                    $activityname = $DB->get_record('ilt', ['id' => $iltdata_row->ilt]);
                    $getdata['courses'][$row->courseid]['activities'][$modules->id]['activityname'] =  $activityname->name;
                    $getdata['courses'][$row->courseid]['activities'][$modules->id]['sessions'][$iltdata_row->sessionid]['iltactivityid'] =  $modules->id;
                    $getdata['courses'][$row->courseid]['activities'][$modules->id]['sessions'][$iltdata_row->sessionid]['iltsessionid'] =  $iltdata_row->sessionid;
                    $getdata['courses'][$row->courseid]['activities'][$modules->id]['sessions'][$iltdata_row->sessionid]['iltsessionname'] =  $iltdata_row->sessionname;
                    $getdata['courses'][$row->courseid]['activities'][$modules->id]['sessions'][$iltdata_row->sessionid]['iltsessionduration'] =  $iltdata_row->duration;
                    $getdata['courses'][$row->courseid]['activities'][$modules->id]['sessions'][$iltdata_row->sessionid]['iltsessioncapacity'] =  $iltdata_row->capacity;

                    $datesid = array();$timestarted = array();$timefinished = array();
                    $datesid = explode(",",$iltdata_row->datesid);
                    $timestarted = explode(",",$iltdata_row->timestarted);
                    $timefinished = explode(",",$iltdata_row->timefinished);
                    for($j=0;$j<count($datesid);$j++) {
                        $getdata['courses'][$row->courseid]['activities'][$modules->id]['sessions'][$iltdata_row->sessionid]['sessiondates'][$datesid[$j]]['sessiondateid'] = $datesid[$j];
                        $getdata['courses'][$row->courseid]['activities'][$modules->id]['sessions'][$iltdata_row->sessionid]['sessiondates'][$datesid[$j]]['sessionstartdates'] = $timestarted[$j];
                        $getdata['courses'][$row->courseid]['activities'][$modules->id]['sessions'][$iltdata_row->sessionid]['sessiondates'][$datesid[$j]]['sessionendtdates'] = $timefinished[$j];  
                    }
                }
            }
        }
        $responsedata = array();
        
        if($getdata) {
            $responsedata = $getdata;
            $responsedata['status'] = True;
            $responsedata['message'] = get_string('ecommerce_status_available', 'ilt');
        } else {
            $responsedata['courses'] = array();
            $responsedata['status'] = False;
            $responsedata['message'] = get_string('ecommerce_status_notavailable', 'ilt');
        }
        return $responsedata;
    }

    public static function get_ilt_session_ecommerce_parameters() {
        return new external_function_parameters(
            array(
                //'sessionid' => new external_value(PARAM_TEXT, 'session id')
            )
        );
    }

    public static function get_ilt_session_ecommerce_returns() {
        return new external_single_structure(
            array(
                'courses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'courseids' => new external_value(PARAM_RAW, 'The Course id'),
                            'activities' => new external_multiple_structure(
                                
                                new external_single_structure(
                                    array(
                                        'activityname' => new external_value(PARAM_RAW, 'The Activity Name'),
                                        'sessions' => new external_multiple_structure(
                                            new external_single_structure(
                                                array(
                                                    'iltactivityid' => new external_value(PARAM_RAW,'The ILT Activity Id'),
                                                    'iltsessionid' => new external_value(PARAM_RAW,'The ILT Session Id'),
                                                    'iltsessionname' => new external_value(PARAM_RAW,'The ILT Session Name'),
                                                    'iltsessionduration' => new external_value(PARAM_RAW,'The ILT Session Duration'),
                                                    'iltsessioncapacity' => new external_value(PARAM_RAW,'The ILT Session Capacity'),
                                                    'sessiondates' => new external_multiple_structure(
                                                        new external_single_structure(
                                                            array(
                                                                'sessiondateid' => new external_value(PARAM_RAW,'The ILT Session Dates'),
                                                                'sessionstartdates' => new external_value(PARAM_RAW,'The ILT Session Start Dates'),
                                                                'sessionendtdates' => new external_value(PARAM_RAW,'The ILT Session End Dates'),
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                        
                                    )
                                )
                                
                            )
                        )
                    )
                ),
                'warnings' => new external_warnings(),
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'message' => new external_value(PARAM_TEXT, 'message: message string'),
            )
        );
    }

    public static function enroll_ilt_session_ecommerce($userid,$courseid,$sessionid) {
        global $CFG, $USER,$DB;
        $enrolldata = '';
        $params = self::validate_parameters(self::enroll_ilt_session_ecommerce_parameters(),
                        array('userid' => $userid,'courseid' => $courseid,'sessionid' => $sessionid));

        $requestedcourseid  = $params['courseid'];
        $requesteduserid    = $params['userid'];
        $requestedsessionid = $params['sessionid'];
        $splitsessionsid = explode(",",$requestedsessionid);
        $enroll=array();
        foreach ($splitsessionsid as $sessionsid) {
            if(is_numeric(trim($sessionsid))) {
            $coursedata = get_course($requestedcourseid);
            $sessiondata = $DB->get_record('ilt_sessions', ['id' => $sessionsid]);
                if($sessiondata) {
                    $ilttabledata = $DB->get_record('ilt', ['id' => $sessiondata->ilt]);
                    $enrolldata = ilt_user_signup($sessiondata, $ilttabledata, $coursedata, '', '', MDL_ILT_STATUS_BOOKED, $requesteduserid);
                    if($enrolldata) {
                        $enroll['status']['enrolled'][$sessionsid]['userid']= $requesteduserid; 
                        $enroll['status']['enrolled'][$sessionsid]['session']= $sessionsid; 
                        $enroll['status']['not_enrolled'][$sessionsid]['userid']= ''; 
                        $enroll['status']['not_enrolled'][$sessionsid]['session']= ''; 
                    } 
                } else {
                        $enroll['status']['enrolled'][$sessionsid]['userid']= ''; 
                        $enroll['status']['enrolled'][$sessionsid]['session']= ''; 
                        $enroll['status']['not_enrolled'][$sessionsid]['userid']= $requesteduserid; 
                        $enroll['status']['not_enrolled'][$sessionsid]['session']= $sessionsid; 
                }
            
            } else {
                        $enroll['status']['enrolled'][$sessionsid]['userid']= ''; 
                        $enroll['status']['enrolled'][$sessionsid]['session']= ''; 
                        $enroll['status']['not_enrolled'][$sessionsid]['userid']= $requesteduserid; 
                        $enroll['status']['not_enrolled'][$sessionsid]['session']= $sessionsid; 
            }
        }

        $responsedata = array();
        if($enrolldata) {
            $responsedata['status'] = $enroll;
            $responsedata['message'] = get_string('ecommerce_status_enroll', 'ilt');
        } else {
            $responsedata['status'] = $enroll;
            $responsedata['message'] = get_string('ecommerce_status_notenroll', 'ilt');
        }
        return $responsedata;
        exit; 
    }

    public static function enroll_ilt_session_ecommerce_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'userid id'),
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'sessionid' => new external_value(PARAM_TEXT, 'Multiple ilt session ids with comma')
            )
        );
    }

    public static function enroll_ilt_session_ecommerce_returns() {
        return new external_single_structure(
            array(
                'status' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'enrolled' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'userid' => new external_value(PARAM_RAW,'Enrolled Userid'),
                                        'session' => new external_value(PARAM_RAW,'Enrolled Session id'),
                                    )
                                )
                            ),
                            'not_enrolled' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'userid' => new external_value(PARAM_RAW,'Enrolled Userid'),
                                        'session' => new external_value(PARAM_RAW,'Enrolled Session id'),
                                    )
                                )
                            )
                        )
                    )
                ),
                'warnings' => new external_warnings(),
                'message' => new external_value(PARAM_TEXT, 'message: message string'),
            )
        );
    }
    
}
