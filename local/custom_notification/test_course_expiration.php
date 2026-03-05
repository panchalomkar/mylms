<?php
require_once(__DIR__ . '/../../config.php');
global $CFG, $DB;

mtrace("Task started: course_expiration_noti");
 $getdatag = $DB->get_records('custom_notification');
        foreach ($getdatag as $keyvalue) {
            $getsetting = $DB->get_record('custom_notification', array('id' => $keyvalue->id));
            
// print_r($getsetting);

mtrace("Setting found, expiration_noti = {$getsetting->course_expiration_noti}");

if ($getsetting->course_expiration_noti == 1) {

    $courseid = $getsetting->courseid; // Single ID, no explode needed.
    // mtrace("Processing course ID: {$courseid}");

    $getsql = "SELECT ue.* 
               FROM {enrol} e
               INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid
               WHERE e.courseid = ?";
    $get_group = $DB->get_records_sql($getsql, [$courseid]);
    // mtrace("Found " . count($get_group) . " enrolments for course {$courseid}");
 $frequency = $getsetting->course_expiration_frequency ?? 'once';
    foreach ($get_group as $kevalue) {
         $lastsent = get_last_sent1($getsetting->id, $kevalue->userid, 'course_expiration');
        $courseidid = $DB->get_record("course", ["id" => 26]);
        $currentdate = strtotime(date('d-m-Y'));
        $daysss = $getsetting->course_expiration_when;
        $daysaction = secondsToTime1($daysss);
        //   if (!should_send_notification1($frequency, $lastsent)) {
        //                 continue;
        //             }
        mtrace("Course end date: {$courseidid->enddate}, Days action: $daysaction");

        $threedaysbeforedate = strtotime(date('d-m-Y', strtotime("-$daysaction", $courseidid->enddate)));
        mtrace("Target notify date: " . date('d-m-Y', $threedaysbeforedate));

        if ($threedaysbeforedate === $currentdate) {
            mtrace("Sending email to user ID {$kevalue->userid}");
            $coursedata = $DB->get_record('course', ['id' => $kevalue->courseid]);
            $user = $DB->get_record("user", ["id" => $kevalue->userid]);
            $email_user = $DB->get_record("user", ["id" => 2]);

            $subject = "Course Expiration Notification";
            // $body = replace_tags(
            //     $getsetting->course_expiration_tem,
            //     ['user' => (array) $user, 'course' => (array) $coursedata]
            // );
            $body='hello praveen';

            email_to_user($user, $email_user, $subject, $body, $body, "", "", false);
            set_last_sent1($getsetting->id, $kevalue->userid, 'course_expiration');
        }
    }

}
        }
mtrace("Task finished: course_expiration_noti");

// Helper function (outside loop).
    function secondsToTime1($inputSeconds) {
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

     function get_last_sent1($notificationid, $userid, $type) {
        global $DB;
        $record = $DB->get_record('custom_notification_sent', [
            'notificationid' => $notificationid,
            'userid' => $userid,
            'notificationtype' => $type
        ]);
        return $record ? $record->lastsent : 0;
    }

     function set_last_sent1($notificationid, $userid, $type) {
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

function should_send_notification1($frequency, $lastsent) {
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
