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


namespace local_custom_notification;
use core_user;
use completion_info;
use completion_completion;
use core_completion\progress;
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/completion/classes/progress.php');
defined('MOODLE_INTERNAL') || die();
/**
 * An example of a scheduled task.
 */
class helper {

    /**
     * Execute the task.
     */

    public static function course_expiration_noti() {
        global $CFG, $DB;
        $getsetting = $DB->get_record('custom_notification', array('id' => 1));
        if ($getsetting->course_expiration_noti == 1) {
            $data = explode(",",$getsetting->courseid);
            foreach ($data as $value) {
        $getsql = "SELECT ue.* FROM {enrol} e INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid  WHERE e.courseid = $value";
        $get_group = $DB->get_records_sql($getsql);

        foreach ($get_group as $kevalue) {  
            // $getsqls = "SELECT * FROM {course} WHERE id = 275";
            // $courseid = $DB->get_record_sql($getsqls);
            $courseidid = $DB->get_record("course", ["id" => $kevalue->courseid]);
            $currentdate = strtotime(date('d-m-Y'));
            $daysss = $getsetting->course_expiration_when;
            $daysaction = self::secondsToTime($daysss);
            $threedaysbeforedate = strtotime(date('d-m-Y', strtotime("-$daysaction",$courseidid->enddate)));
            // $courseid = $DB->get_record("course", ["id" => 275]);
            //echo $yesterdaydate;
            if ($threedaysbeforedate === $currentdate) {
                $coursedata = $DB->get_record('course', array('id' => $kevalue->courseid));
                $user = $DB->get_record("user", ["id" => $kevalue->userid]);

                $email_user = $DB->get_record("user", ["id" => 2]);

                $subject = "Course Expiration Notification";

                $body = self::replace_tags($getsetting->course_expiration_tem, ['user' => (array) $user,'course' => (array) $coursedata]);
                $messagetext = $messageHtml = $body; 
                //$messagetext = $messageHtml = $getsetting->course_expiration_tem; 

                email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
            }
        }
}
    }

        return true;
    }

    public static function course_in_progress_noti() {
        global $CFG, $DB, $USER;
        
        // $getsql = "SELECT ue.* FROM {enrol} e INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid WHERE e.courseid = 275";
        // $get_group = $DB->get_records_sql($getsql);
        $getsetting = $DB->get_record('custom_notification', array('id' => 1));
        if ($getsetting->course_in_progress_noti == 1) {
            $data = explode(",",$getsetting->courseid);
            foreach ($data as $value) {
                $getsql = "SELECT ue.* FROM {enrol} e INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid  WHERE e.courseid = $value";
                $get_group = $DB->get_records_sql($getsql);
        
                foreach ($get_group as $kevalue) {  

        $course = $DB->get_record("course", ["id" => $kevalue->courseid]);
        $info = new completion_info($course);
      
        $completions = $info->get_completions($kevalue->userid);
        // print_r($completions);
        // exit();
        $pending_update = false;
        foreach ($completions as $completion) {
            $criteria = $completion->get_criteria();
            if (!$pending_update && $criteria->is_pending($completion)) {
                $pending_update = true;
            }
            $coursecomplete = $info->is_course_complete($kevalue->userid);

            $params = array(
                'userid' => $kevalue->userid,
                'course' => $kevalue->courseid
            );
            
            $ccompletion = new completion_completion($params);
            $criteriacomplete = $info->count_course_user_data($kevalue->userid);

            if ($pending_update) {
                $content = 'pending';
            } else if ($coursecomplete) {
                $content = "complete";
            } else if (!$criteriacomplete && !$ccompletion->timestarted) {
                $content = "not started";
            } else {
                $coursedata = $DB->get_record('course', array('id' => $kevalue->courseid));
                $user = $DB->get_record("user", ["id" => $kevalue->userid]);

                $email_user = $DB->get_record("user", ["id" => 2]);
    
                $subject = "Course in Progress Notification";

                $body = self::replace_tags($getsetting->course_in_progress_tem, ['user' => (array) $user,'course' => (array) $coursedata]);
                $messagetext = $messageHtml = $body; 
               // $messagetext = $messageHtml = $getsetting->course_in_progress_tem; 
    
                email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
            }

        }
    }
    }
    }

    //     foreach ($get_group as $kevalue) {

    //     $courseid = $DB->get_record("course", ["id" => 275]);
    //     $progress = \core_completion\progress::get_course_progress_percentage($courseid,$kevalue->userid);
    //     $percentage = floor($progress);

    //       if ($percentage < 100) {
    //         $user = $DB->get_record("user", ["id" => $kevalue->userid]);

    //         $email_user = $DB->get_record("user", ["id" => 2]);

    //         $subject = "test mail";

    //         $messagetext = $messageHtml = "<p>Hello  rajveer singh,"; 

    //         email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
    //     }
    // }
        return true;
    }

    public static function course_not_completed_noti() {
        global $CFG, $DB, $USER;
        
        $getsetting = $DB->get_record('custom_notification', array('id' => 1));
        if ($getsetting->course_not_completed_noti == 1) {
            $data = explode(",",$getsetting->courseid);
            foreach ($data as $value) {

        $getsql = "SELECT ue.* FROM {enrol} e INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid WHERE e.courseid = $value";
        $get_group = $DB->get_records_sql($getsql);

        foreach ($get_group as $kevalue) {
           
            // $getsqls = "SELECT * FROM {course} WHERE id = 275";
            // $courseid = $DB->get_record_sql($getsqls);
            $courseid = $DB->get_record("course", ["id" => $kevalue->courseid]);
            $currentdate = strtotime(date('d-m-Y'));
            $daysss = $getsetting->course_not_completed_when;
            $daysaction = self::secondsToTime($daysss);
            $threedaysbeforedate = strtotime(date('d-m-Y', strtotime("-$daysaction",$courseid->enddate)));
           // $sevendaysbeforedate = strtotime(date('d-m-Y', strtotime("-7 day",$courseid->enddate)));
        // $courseid = $DB->get_record("course", ["id" => 275]);
        //echo $yesterdaydate;
        if ($threedaysbeforedate === $currentdate) {
            $coursedata = $DB->get_record('course', array('id' => $kevalue->courseid));
            $user = $DB->get_record("user", ["id" => $kevalue->userid]);

            $email_user = $DB->get_record("user", ["id" => 2]);

            $subject = "Course not Completed Notification";

            $body = self::replace_tags($getsetting->course_not_completed_tem, ['user' => (array) $user,'course' => (array) $coursedata]);
            $messagetext = $messageHtml = $body; 

            //$messagetext = $messageHtml = $getsetting->course_not_completed_tem; 

            email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
        }

    }
}

        }

        return true;
    }

    public static function not_loggedin_noti() {
        global $CFG, $DB, $USER;
       // $courseid = $DB->get_record("course", ["id" => 275]);
       $getsetting = $DB->get_record('custom_notification', array('id' => 1));
       if ($getsetting->not_loggedin_noti == 1) {
           $data = explode(",",$getsetting->courseid);
           foreach ($data as $value) {

            $getsql = "SELECT ue.* FROM {enrol} e INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid WHERE e.courseid = $value";
            $get_group = $DB->get_records_sql($getsql);
       
        // $user = $DB->get_record("user", ["id" => $kevalue->userid]);
        foreach ($get_group as $kevalue) {
            $useridget = $DB->get_record("user", ["id" => $kevalue->userid]);
            $currentdate = strtotime(date('d-m-Y'));
            $daysss = $getsetting->not_loggedin_when;
            $daysaction = self::secondsToTime($daysss);

            $twintyaysbeforedate = strtotime(date('d-m-Y', strtotime("-$daysaction", $currentdate)));
             if ($useridget->lastlogin < $twintyaysbeforedate) {
            $coursedata = $DB->get_record('course', array('id' => $kevalue->courseid));
            $user = $DB->get_record("user", ["id" => $kevalue->userid]);

            $email_user = $DB->get_record("user", ["id" => 2]);

            $subject = "Not loggedin Notification";

            $body = self::replace_tags($getsetting->not_loggedin_tem, ['user' => (array) $user,'course' => (array) $coursedata]);
            $messagetext = $messageHtml = $body; 
            //$messagetext = $messageHtml = $getsetting->not_loggedin_tem; 

            email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);

            }
        }
    }
}
        return true;
    }

    function secondsToTime($inputSeconds) {
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;
    
        // Extract days
        $days = floor($inputSeconds / $secondsInADay);
    
        // Extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);
    
        // Extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);
    
        // Extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);
    
        // Format and return
        $timeParts = [];
        $sections = [
            'day' => (int)$days,
            'hour' => (int)$hours,
            'minute' => (int)$minutes,
            'second' => (int)$seconds,
        ];
    
        foreach ($sections as $name => $value){
            if ($value > 0){
                $timeParts[] = $value. ' '.$name.($value == 1 ? '' : 's');
            }
        }
    
        return implode(', ', $timeParts);
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
