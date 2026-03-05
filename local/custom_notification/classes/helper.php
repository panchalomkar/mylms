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
* @package local_custom_notification
* @category local
* @copyright  GreenLMS <admin@greenlms.com>
* @author GreenLMS
*/

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

    private static function get_last_sent($notificationid, $userid, $type) {
       global $DB;
        $record = $DB->get_record('custom_notification_sent', [
            'notificationid' => $notificationid,
            'userid' => $userid,
            'notificationtype' => $type
        ]);
        return $record ? $record->lastsent : 0;
    }

    private static function set_last_sent($notificationid, $userid, $type) {
      global $DB;
        $record = $DB->get_record('custom_notification_sent', [
            'notificationid' => $notificationid,
            'userid' => $userid,
            'notificationtype' => $type
        ]);
        $now = time();
        if ($record) {
            $record->lastsent = $now;
            $DB->update_record('custom_notification_sent', $record);
        } else {
            $DB->insert_record('custom_notification_sent', [
                'notificationid' => $notificationid,
                'userid' => $userid,
                'notificationtype' => $type,
                'lastsent' => $now
            ]);
        }
    }
           // if ($getsetting->course_expiration_noti == 1) {
            //     $frequency = $getsetting->course_expiration_frequency ?? 'once';
            //     $getsql = "SELECT ue.* FROM {enrol} e INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid  WHERE e.courseid = $getsetting->courseid";
            //     $get_group = $DB->get_records_sql($getsql);
            //     foreach ($get_group as $kevalue) {
            //         $lastsent = self::get_last_sent($getsetting->id, $kevalue->userid, 'course_expiration');
            //         if (!self::should_send_notification($frequency, $lastsent)) {
            //             continue;
            //         }
            //         $courseidid = $DB->get_record("course", ["id" => $kevalue->courseid]);
            //         $currentdate = strtotime(date('d-m-Y'));
            //         $daysss = $getsetting->course_expiration_when;
            //         $daysaction = self::secondsToTime($daysss);
            //         $threedaysbeforedate = strtotime(date('d-m-Y', strtotime("-$daysaction",$courseidid->enddate)));
            //         if ($threedaysbeforedate === $currentdate) {
            //             $coursedata = $DB->get_record('course', array('id' => $kevalue->courseid));
            //             $user = $DB->get_record("user", ["id" => $kevalue->userid]);
            //             $email_user = $DB->get_record("user", ["id" => 2]);
            //             $subject = "Course Expiration Notification";
            //             $body = self::replace_tags($getsetting->course_expiration_tem, ['user' => (array) $user,'course' => (array) $coursedata]);
            //             $messagetext = $messageHtml = $body; 
            //             email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
            //             self::set_last_sent($getsetting->id, $kevalue->userid, 'course_expiration');
            //         }
            //     }
            // }

    public static function course_expiration_noti() {
        global $CFG, $DB;
           
        $getdatag = $DB->get_records('custom_notification');
        foreach ($getdatag as $keyvalue) {
           
            $getsetting = $DB->get_record('custom_notification', array('id' => $keyvalue->id));
        
      $courseid = $getsetting->courseid; // Single ID, no explode needed.
    
      if ($getsetting->course_expiration_noti == 1) {
          $getsql = "SELECT ue.* 
               FROM {enrol} e
               INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid
               WHERE e.courseid = ?";
              
          $get_group = $DB->get_records_sql($getsql, [$courseid]);
        //   mtrace("Found " . count($get_group) . " enrolments for course {$courseid}");
       $frequency = $getsetting->course_expiration_frequency ?? 'once';
          foreach ($get_group as $kevalue) {
               $lastsent = self::get_last_sent($getsetting->id, $kevalue->userid, 'course_expiration');
             $courseidid = $DB->get_record("course", ["id" => $courseid]);
              $currentdate = strtotime(date('d-m-Y'));
              $daysss = $getsetting->course_expiration_when;
              $daysaction = self::secondsToTime($daysss);
          if (!self::should_send_notification($frequency, $lastsent)) {
                        continue;
                    }
        // mtrace("Course end date: {$courseidid->enddate}, Days action: $daysaction");

        $threedaysbeforedate = strtotime(date('d-m-Y', strtotime("-$daysaction", $courseidid->enddate)));
        // mtrace("Target notify date: " . date('d-m-Y', $threedaysbeforedate));

        if ($threedaysbeforedate === $currentdate) {
            $coursedata = $DB->get_record('course', ['id' => $courseid]);
            $user = $DB->get_record("user", ["id" => $kevalue->userid]);
            $email_user = $DB->get_record("user", ["id" => 2]);
$coursedata->startdate = userdate($coursedata->startdate); // Moodle formatted date
$coursedata->enddate   = userdate($coursedata->enddate);   // Moodle formatted date
            $subject = "Course Expiration Notification";
                 $body = self::replace_tags($getsetting->course_expiration_tem, ['user' => (array) $user,'course' => (array) $coursedata]);
                 
            // $body='hello praveen';
 $messagetext = $messageHtml = $body; 
                            email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
            self::set_last_sent($getsetting->id, $kevalue->userid, 'course_expiration');
        }
      }
      }
       }
        return true;
    }

      

    public static function course_in_progress_noti() {
        global $CFG, $DB, $USER;
        $getdatag = $DB->get_records('custom_notification');
        foreach ($getdatag as $keyvalue) {
            
            $getsetting = $DB->get_record('custom_notification', array('id' => $keyvalue->id));
            if ($getsetting->course_in_progress_noti == 1) {
                $courseid = $getsetting->courseid;
                $frequency = $getsetting->course_in_progress_frequency ?? 'once';
                 $getsql = "SELECT ue.* 
               FROM {enrol} e
               INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid
               WHERE e.courseid = ?";
                // $getsql = "SELECT ue.* FROM {enrol} e INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid  WHERE e.courseid = $getsetting->courseid";
                $get_group = $DB->get_records_sql($getsql, [$courseid]);
                foreach ($get_group as $kevalue) {
                    $lastsent = self::get_last_sent($getsetting->id, $kevalue->userid, 'course_in_progress');
                    if (!self::should_send_notification($frequency, $lastsent)) {
                        continue;
                    }
                    $course = $DB->get_record("course", ["id" => $courseid]);
                    $info = new completion_info($course);
                    $completions = $info->get_completions($kevalue->userid);
                    $pending_update = false;
                    foreach ($completions as $completion) {
                        $criteria = $completion->get_criteria();
                        if (!$pending_update && $criteria->is_pending($completion)) {
                            $pending_update = true;
                        }
                        $coursecomplete = $info->is_course_complete($kevalue->userid);
                        $params = array(
                            'userid' => $kevalue->userid,
                            'course' =>  $courseid 
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
                            $coursedata = $DB->get_record('course', array('id' =>  $courseid ));
                            $coursedata->startdate = userdate($coursedata->startdate); // Moodle formatted date
$coursedata->enddate   = userdate($coursedata->enddate);   // Moodle formatted date
                            $user = $DB->get_record("user", ["id" => $kevalue->userid]);
                            $email_user = $DB->get_record("user", ["id" => 2]);
                            $subject = "Course in Progress Notification";
                            $body = self::replace_tags($getsetting->course_in_progress_tem, ['user' => (array) $user,'course' => (array) $coursedata]);
                            $messagetext = $messageHtml = $body; 
                            email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
                            self::set_last_sent($getsetting->id, $kevalue->userid, 'course_in_progress');
                        }
                    }
                }
            }
        }
        return true;
    }

    public static function course_not_completed_noti() {
        global $CFG, $DB, $USER;
        $getdatag = $DB->get_records('custom_notification');
        foreach ($getdatag as $keyvalue) {
            $getsetting = $DB->get_record('custom_notification', array('id' => $keyvalue->id));
              
            if ($getsetting->course_not_completed_noti == 1) {
                 $courseid = $getsetting->courseid;
                $frequency = $getsetting->course_not_completed_frequency ?? 'once';
                // $getsql = "SELECT ue.* FROM {enrol} e INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid WHERE e.courseid = $getsetting->courseid";
                 $getsql = "SELECT ue.* 
               FROM {enrol} e
               INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid
               WHERE e.courseid = ?";
                 $get_group = $DB->get_records_sql($getsql, [$courseid]);
                foreach ($get_group as $kevalue) {
                    $lastsent = self::get_last_sent($getsetting->id, $kevalue->userid, 'course_not_completed');
                    if (!self::should_send_notification($frequency, $lastsent)) {
                        continue;
                    }
                    // $courseid = $DB->get_record("course", ["id" => $courseid]);
                    $currentdate = strtotime(date('d-m-Y'));
                    $daysss = $getsetting->course_not_completed_when;
                    $daysaction = self::secondsToTime($daysss);
                    $threedaysbeforedate = strtotime(date('d-m-Y', strtotime("-$daysaction",$courseid->enddate)));
                    if ($threedaysbeforedate === $currentdate) {
                        $coursedata = $DB->get_record('course', array('id' =>  $courseid ));
                     
                        $coursedata->startdate = userdate($coursedata->startdate); // Moodle formatted date
                         $coursedata->enddate   = userdate($coursedata->enddate);   // Moodle formatted date
                        $user = $DB->get_record("user", ["id" => $kevalue->userid]);
                        $email_user = $DB->get_record("user", ["id" => 2]);
                        $subject = "Course not Completed Notification";
                        $body = self::replace_tags($getsetting->course_not_completed_tem, ['user' => (array) $user,'course' => (array) $coursedata]);
                        $messagetext = $messageHtml = $body; 
                        email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
                        self::set_last_sent($getsetting->id, $kevalue->userid, 'course_not_completed');
                    }
                }
            }
        }
        return true;
    }

    public static function not_loggedin_noti() {
        global $CFG, $DB, $USER;
        $getdatag = $DB->get_records('custom_notification');
        foreach ($getdatag as $keyvalue) {
            $getsetting = $DB->get_record('custom_notification', array('id' => $keyvalue->id));
            if ($getsetting->not_loggedin_noti == 1) {
                $courseid = $getsetting->courseid;
                $frequency = $getsetting->not_loggedin_frequency ?? 'once';
                $getsql = "SELECT ue.* 
               FROM {enrol} e
               INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid
               WHERE e.courseid = ?";
                 $get_group = $DB->get_records_sql($getsql, [$courseid]);
                foreach ($get_group as $kevalue) {
                    $lastsent = self::get_last_sent($getsetting->id, $kevalue->userid, 'not_loggedin');
                    if (!self::should_send_notification($frequency, $lastsent)) {
                        continue;
                    }
                    $useridget = $DB->get_record("user", ["id" => $kevalue->userid]);
                    $currentdate = strtotime(date('d-m-Y'));
                    $daysss = $getsetting->not_loggedin_when;
                    $daysaction = self::secondsToTime($daysss);
                    $twintyaysbeforedate = strtotime(date('d-m-Y', strtotime("-$daysaction", $currentdate)));
                    if ($useridget->lastlogin < $twintyaysbeforedate) {
                        $coursedata = $DB->get_record('course', array('id' =>  $courseid ));
                        $user = $DB->get_record("user", ["id" => $kevalue->userid]);
               $user->firstaccess_date = $user->firstaccess ? userdate($user->firstaccess) : 'Never';
    $user->lastlogin_date   = $user->lastlogin   ? userdate($user->lastlogin)   : 'Never';
                     
                        $email_user = $DB->get_record("user", ["id" => 2]);
                        $subject = "Not loggedin Notification";
                        $body = self::replace_tags($getsetting->not_loggedin_tem, ['user' => (array) $user,'course' => (array) $coursedata]);
                          
                        $messagetext = $messageHtml = $body; 
                        email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
                        self::set_last_sent($getsetting->id, $kevalue->userid, 'not_loggedin');
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

    /**
     * Helper to check if notification should be sent based on frequency and last sent time.
     */
    private static function should_send_notification($frequency, $lastsent) {
        $now = time();

    if ($frequency === 'once') {
        return empty($lastsent);

    } else if ($frequency === 'daily') {
        // If never sent, send it now
        if (empty($lastsent)) {
            return true;
        }
        // Compare date (Y-m-d) so it only sends once per day
        return date('Y-m-d', $now) !== date('Y-m-d', $lastsent);

    } else if ($frequency === 'weekly') {
        if (empty($lastsent)) {
            return true;
        }
        // Only send if 7 days have passed
        return ($now - $lastsent) >= 604800;
    }

    return true;
    }

}
