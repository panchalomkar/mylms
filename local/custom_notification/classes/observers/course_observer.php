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
 * Local Course Progress Manager Plugin Events Observer.
 *
 * @package     local_custom_notification
 * @category    admin
 * @copyright   2019 wisdmlabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_custom_notification\observers;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use core_user;

trait course_observer {

    /**
     * Course completion notification.
     * Sends notification immediately when event fires, no frequency/lastsent check.
     */
    public static function course_completion_notification(\core\event\course_completed $event) {
        global $DB;
        $eventdata = $event->get_data();
        $currentuserid = $eventdata['relateduserid'];
        $currentcourseid = $eventdata['courseid'];
$completiondate = time();
        $notifications = $DB->get_records('custom_notification');
        foreach ($notifications as $setting) {
            if ($setting->course_completion_noti != 1) {
                continue;
            }
            if ($currentcourseid != $setting->courseid) {
                continue;
            }
            $coursedata = $DB->get_record('course', ['id' => $currentcourseid]);
            $user = $DB->get_record("user", ["id" => $currentuserid]);
            $email_user = $DB->get_record("user", ["id" => 2]);
            $subject = "Course Completion Notification";
            // $body = self::replace_tags($setting->course_completion_tem, ['user' => (array) $user, 'course' => (array) $coursedata]);
            $body = self::replace_tags(
    $setting->course_completion_tem,
    [
        'user' => (array) $user,
        'course' => (array) $coursedata,
        'course' => array_merge((array)$coursedata, [
            'completion_date' => $completiondate
        ])
    ]
);
            $messagetext = $messageHtml = $body;
            email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
        }
        return true;
    }

    /**
     * Course module completion notification.
     * Sends notification immediately when event fires, no frequency/lastsent check.
     */
  public static function course_module_completion_notification(\core\event\course_module_completion_updated $event) {
    global $DB;

    $eventdata = $event->get_data();
    $currentuserid = $eventdata['relateduserid'];
    $currentcourseid = $eventdata['courseid'];
    $cmid = $eventdata['contextinstanceid']; // course_modules.id

    // Get course module
    $cm = get_coursemodule_from_id(null, $cmid, $currentcourseid, false, MUST_EXIST);
    $activityname = $DB->get_field($cm->modname, 'name', ['id' => $cm->instance]);

    // Dates
    $activitystartdate = $cm->availability ? '' : ''; // Put your real start date logic here
    $activityenddate = $cm->completionexpected ? $cm->completionexpected : '';
    $completiondate = time(); // Or from completion API if needed

    $notifications = $DB->get_records('custom_notification');
    foreach ($notifications as $setting) {
        if ($setting->course_module_completion_noti != 1) {
            continue;
        }
        if ($currentcourseid != $setting->courseid) {
            continue;
        }

        $coursedata = $DB->get_record('course', ['id' => $currentcourseid]);
        $user = $DB->get_record("user", ["id" => $currentuserid]);
        $email_user = $DB->get_record("user", ["id" => 2]);

        $subject = "Course Module Completion Notification";

        // Pass activity data too
        $body = self::replace_tags(
            $setting->course_module_completion_tem,
            [
                'user' => (array) $user,
                'course' => (array) $coursedata,
                'activity' => [
                    'name' => $activityname,
                    'startdate' => $activitystartdate,
                    'enddate' => $activityenddate,
                    'completion_date' => $completiondate
                ]
            ]
        );

        $messagetext = $messageHtml = $body;
        email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
    }

    return true;
}


    /**
     * User enrolled notification.
     * Sends notification immediately when event fires, no frequency/lastsent check.
     */
    public static function user_enrolled_notification(\core\event\user_enrolment_created $event) {
        global $DB;
        $eventdata = $event->get_data();
        $currentuserid = $eventdata['relateduserid'];
        $currentcourseid = $eventdata['courseid'];

        $notifications = $DB->get_records('custom_notification');
        foreach ($notifications as $setting) {
            if ($setting->user_enrolled_noti != 1) {
                continue;
            }
            if ($currentcourseid != $setting->courseid) {
                continue;
            }
            $coursedata = $DB->get_record('course', ['id' => $currentcourseid]);
            $user = $DB->get_record("user", ["id" => $currentuserid]);
            $email_user = $DB->get_record("user", ["id" => 2]);
            $subject = "User Enrolled Notification";
            $body = self::replace_tags($setting->user_enrolled_tem, ['user' => (array) $user, 'course' => (array) $coursedata]);
            $messagetext = $messageHtml = $body;
            email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
        }
        return true;
    }

    /**
     * User unenrolled notification.
     * Sends notification immediately when event fires, no frequency/lastsent check.
     */
    public static function user_unenrolled_notification(\core\event\user_enrolment_deleted $event) {
        global $DB;
        $eventdata = $event->get_data();
        $userenrolment = $eventdata['other']['userenrolment'];

        $notifications = $DB->get_records('custom_notification');
        foreach ($notifications as $setting) {
            if ($setting->user_unenrolled_noti != 1) {
                continue;
            }
            if ($userenrolment['courseid'] != $setting->courseid) {
                continue;
            }
            $coursedata = $DB->get_record('course', ['id' => $userenrolment['courseid']]);
            $user = $DB->get_record("user", ["id" => $userenrolment['userid']]);
            $email_user = $DB->get_record("user", ["id" => 2]);
            $subject = "User Unenrolled Notification";
            $body = self::replace_tags($setting->user_unenrolled_tem, ['user' => (array) $user, 'course' => (array) $coursedata]);
            $messagetext = $messageHtml = $body;
            email_to_user($user, $email_user, $subject, $messagetext, $messageHtml, "", "", false);
        }
        return true;
    }

    // --- Helper functions below ---

   public static function replace_tags($string, $settings = []) {
    if (!empty($settings)) {
        $data_tags = self::get_string_between($string, '{', '}');

        foreach ($data_tags as $tag) {
            $pos = strpos($tag, '_');
            $model = '';
            $property = '';

            if ($pos !== false) {
                $property = strip_tags(substr($tag, $pos + 1));
                $model = strip_tags(substr($tag, 0, $pos));

                // User fullname.
                if ($model == 'user' && $property == 'fullname') {
                    $settings[$model][$property] = $settings[$model]['firstname'] . ' ' . $settings[$model]['lastname'];

                // Course name.
                } elseif ($model == 'course' && $property == 'name') {
                    $settings[$model][$property] = $settings[$model]['fullname'];

                // Any property name containing 'date' or 'time' is treated as a timestamp.
                } elseif (isset($settings[$model][$property]) && is_numeric($settings[$model][$property]) &&
                          (stripos($property, 'date') !== false || stripos($property, 'time') !== false)) {
                    $settings[$model][$property] = date("m-d-Y", $settings[$model][$property]);
                }
            }

            if (!empty($model) && !empty($property) && array_key_exists($model, $settings)) {
                $string = str_replace('{' . $tag . '}', $settings[$model][$property], $string);
            }
        }
    }
    return $string;
}

    public static function get_string_between($string, $start, $end) {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        $array_tags = [];
        if ($ini == 0)
            return $array_tags;
        $exist_data = $ini;
        while ($exist_data !== false) {
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
