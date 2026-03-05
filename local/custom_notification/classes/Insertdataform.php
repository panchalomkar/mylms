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
* @package local_batch
* @category local
* @copyright  ELS <admin@elearningstack.com>
* @author eLearningstack
*/

class Insert_formdata{

  public function updatedatafunc($paramdatat) {
    global $DB,$CFG,$PAGE,$USER;

    $getdata = $DB->get_record_sql("SELECT * FROM {custom_notification} WHERE courseid = $paramdatat->courseid");

    $currentdevicedata = new \stdClass();
    $currentdevicedata->id = $getdata->id;
    $currentdevicedata->courseid = $paramdatat->courseid;
    $currentdevicedata->course_completion_noti = $paramdatat->course_completion_noti;
    $currentdevicedata->course_completion_tem = $paramdatat->course_completion_tem['text'];
    $currentdevicedata->course_module_completion_noti = $paramdatat->course_module_completion_noti;
    $currentdevicedata->course_module_completion_tem = $paramdatat->course_module_completion_tem['text'];
    $currentdevicedata->course_in_progress_noti = $paramdatat->course_in_progress_noti;
    $currentdevicedata->course_in_progress_tem = $paramdatat->course_in_progress_tem['text'];

    $currentdevicedata->course_expiration_noti = $paramdatat->course_expiration_noti;
    $currentdevicedata->course_expiration_when = $paramdatat->course_expiration_when;
    $currentdevicedata->course_expiration_tem = $paramdatat->course_expiration_tem['text'];
    $currentdevicedata->course_not_completed_noti = $paramdatat->course_not_completed_noti;
    $currentdevicedata->course_not_completed_when = $paramdatat->course_not_completed_when;
    $currentdevicedata->course_not_completed_tem = $paramdatat->course_not_completed_tem['text'];
    $currentdevicedata->not_loggedin_noti = $paramdatat->not_loggedin_noti;

    $currentdevicedata->not_loggedin_when = $paramdatat->not_loggedin_when;
    $currentdevicedata->not_loggedin_tem = $paramdatat->not_loggedin_tem['text'];
    $currentdevicedata->user_enrolled_noti = $paramdatat->user_enrolled_noti;
    $currentdevicedata->user_enrolled_tem = $paramdatat->user_enrolled_tem['text'];
    $currentdevicedata->user_unenrolled_noti = $paramdatat->user_unenrolled_noti;
    $currentdevicedata->user_unenrolled_tem = $paramdatat->user_unenrolled_tem['text'];
    $currentdevicedata->course_in_progress_frequency = $paramdatat->course_in_progress_frequency;
    $currentdevicedata->course_expiration_frequency = $paramdatat->course_expiration_frequency;
    $currentdevicedata->course_not_completed_frequency = $paramdatat->course_not_completed_frequency;
    $currentdevicedata->not_loggedin_frequency = $paramdatat->not_loggedin_frequency;
    $nowDate = time();
    $currentdevicedata->timecreated = $nowDate;
    // print_r($currentdevicedata);
    // exit();
    if ($getdata->id) { 
        $DB->update_record('custom_notification', $currentdevicedata, true);
    }else{
        $DB->insert_record('custom_notification', $currentdevicedata, true);
    }
     return true;
}
}
?>