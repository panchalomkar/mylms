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
 * Local Course Progress Manager Plugin Events Onserver.
 *
 * @package     local_custom_notification
 * @category    admin
 * @copyright   2019 wisdmlabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Local Course Progress Manager Namespace
 */
namespace local_custom_notification\observers;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use core_user;
trait course_observer {
    /**
     * Get event data
     * @param  object $event Event object
     * @return object        Course event data
     */
    public static function course_completion_notification(\core\event\course_completed $event) {
        global $DB,$CFG,$PAGE,$USER;
        $eventdata = $event->get_data();
        $currentuserid = $eventdata['relateduserid'];
        $currentcourseid = $eventdata['courseid'];
        $getsetting = $DB->get_record('custom_notification', array('id' => 1));
        if ($getsetting->course_completion_noti == 1) {
            $data = explode(",",$getsetting->courseid);
            foreach ($data as $value) {
               if ($currentcourseid == $value) {
               $coursedata = $DB->get_record('course', array('id' => $currentcourseid));
               $user = $DB->get_record("user", ["id" => $currentuserid]);
    
               $email_user = $DB->get_record("user", ["id" => 2]);
    
               $subject = "Course Completion Notification";
               $body = self::replace_tags($getsetting->course_completion_tem, ['user' => (array) $user,'course' => (array) $coursedata]);
               $messagetext = $messageHtml = $body; 
              // $messagetext = $messageHtml = $getsetting->course_completion_tem; 
    
               email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
               }
    
                   }
                              
        }

        return true;  
    }

    /**
     * Course updated event
     * @param \core\event\course_updated $event Event Data
     */
    public static function course_module_completion_notification(\core\event\course_module_completion_updated $event) {
        global $DB,$CFG,$PAGE,$USER;
        $eventdata = $event->get_data();
        $currentuserid = $eventdata['relateduserid'];
        $currentcourseid = $eventdata['courseid'];
        $getsetting = $DB->get_record('custom_notification', array('id' => 1));
        if ($getsetting->course_module_completion_noti == 1) {
            $data = explode(",",$getsetting->courseid);
            foreach ($data as $value) {
    
               if ($currentcourseid == $value) {
                $coursedata = $DB->get_record('course', array('id' => $currentcourseid));
               $user = $DB->get_record("user", ["id" => $currentuserid]);
    
               $email_user = $DB->get_record("user", ["id" => 2]);
    
               $subject = "Course Module Completion Notification";

               $body = self::replace_tags($getsetting->course_module_completion_tem, ['user' => (array) $user,'course' => (array) $coursedata]);
               $messagetext = $messageHtml = $body; 
              // $messagetext = $messageHtml = $getsetting->course_module_completion_tem; 
    
               email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
               }
    
                   }
                              
        }

        return true;
    }

    public static function user_enrolled_notification(\core\event\user_enrolment_created $event) {
        global $DB,$CFG,$PAGE,$USER;
        $eventdata = $event->get_data();
        //$currentuserid = $eventdata['userid'];
        $currentuserid = $eventdata['relateduserid'];
        $currentcourseid = $eventdata['courseid'];
        $getsetting = $DB->get_record('custom_notification', array('id' => 1));
        if ($getsetting->user_enrolled_noti == 1) {

        $data = explode(",",$getsetting->courseid);
        foreach ($data as $value) {

           if ($currentcourseid == $value) {
           $coursedata = $DB->get_record('course', array('id' => $currentcourseid));
           $user = $DB->get_record("user", ["id" => $currentuserid]);

           $email_user = $DB->get_record("user", ["id" => 2]);

           $subject = "User Enrolled Notification";
           $enrolid = $DB->get_record('enrol', array('courseid' => $currentcourseid,'enrol' => 'manual'));
           $get_enroldata = $DB->get_record('user_enrolments', array('enrolid' => $enrolid->id,'userid' => $currentuserid));
        //    $getsql = "SELECT ue.* FROM {enrol} e INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid  WHERE e.courseid = $currentcourseid";
        //    $get_enroldata = $DB->get_records_sql($getsql);
           $body = self::replace_tags($getsetting->user_enrolled_tem, ['user' => (array) $user,'course' => (array) $coursedata]);
           $messagetext = $messageHtml = $body; 

           email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
           }

               }
                          
        }   
       return true;
    }

    public static function user_unenrolled_notification(\core\event\user_enrolment_deleted $event) {
        global $DB,$CFG,$PAGE,$USER;
        $eventdata = $event->get_data();
        $userenrolment = $eventdata['other']['userenrolment'];
        $getsetting = $DB->get_record('custom_notification', array('id' => 1));
        if ($getsetting->user_unenrolled_noti == 1) {
        $data = explode(",",$getsetting->courseid);
        foreach ($data as $value) {

            if ($userenrolment['courseid'] == $value) {

            $coursedata = $DB->get_record('course', array('id' => $userenrolment['courseid']));
            
            $user = $DB->get_record("user", ["id" => $userenrolment['userid']]);

            $email_user = $DB->get_record("user", ["id" => 2]);

            $subject = "User Unenrolled Notification";

          // $senddata = str_replace("{user_fullname}","Peter",$getsetting->user_unenrolled_tem);

           $body = self::replace_tags($getsetting->user_unenrolled_tem, ['user' => (array) $user,'course' => (array) $coursedata]);

            $messagetext = $messageHtml = $body; 

            email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
            }

                }

            }
        return true;
    }

    
    
    public function replace_tags($string, $settings = []) {
        global $DB;
        if (!empty($settings)) {
            /* we get all the tags string */
            $data_tags = self::get_string_between($string, '{', '}');
            foreach ($data_tags as $tag) {
                $pos = strpos($tag, '_');
                $model = '';
                $property = '';
                if ($pos !== false) {
                    $property = strip_tags(substr($tag, $pos + 1, strlen($tag)));
                    $model = strip_tags(substr($tag, 0, $pos));
                    if ($model == 'user' && $property == 'fullname') {
                        $settings[$model][$property] = $settings[$model]['firstname'] . ' ' . $settings[$model]['lastname'];
                    } elseif ($model == 'course' && $property == 'name') {
                        $settings[$model][$property] = $settings[$model]['fullname'];
                    } elseif ($model == 'userenroled' && $property == 'startdate') {
                        $settings[$model][$property] =  date("m-d-Y", $settings[$model]['timestart']);
                    }elseif ($model == 'userenroled' && $property == 'enddate') {
                        $settings[$model][$property] =  date("m-d-Y", $settings[$model]['timeend']);
                    }
                }
                if (!empty($model) && !empty($property) && array_key_exists($model, $settings)) {
                    $string = str_replace('{' . $tag . '}', $settings[$model][$property], $string);
                }
            }
        }
    
        return $string;
    }
    
    public function get_string_between($string, $start, $end) {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
    
        $array_tags = [];
        if ($ini == 0)
            return $array_tags;
    
        $exist_data = $ini;
        while ($exist_data != '') {
            $ini += strlen($start);
            $len = strpos($string, $end, $ini) - $ini;
            $str_tmp = substr($string, $ini, $len);
            $array_tags[] = $str_tmp;
            $string = str_replace($start . $str_tmp . $end, '', $string);
            $exist_data = strpos($string, $start);
        }
    
        return $array_tags;
    }

}
