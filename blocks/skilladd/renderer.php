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
* @package block_skilladd
* @category local
* @copyright  ELS 
* @author greenlms
*/
require_once('../../config.php');

class block_skilladd_renderer extends plugin_renderer_base {

    public function insertdata($fromform) {  
      global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }
      $getexitdata = $DB->get_record('block_skilladd', array('userid' => $fromform->userid, 'createdby' => 'Admin'));
      $currentdevicedata = new \stdClass();
      $currentdevicedata->userid =  $fromform->userid;
      $currentdevicedata->department = $fromform->department;
      $currentdevicedata->skill =  $fromform->skill;
      $currentdevicedata->skilllevel = $fromform->skilllevel;
      $currentdevicedata->position = $fromform->position;
      $currentdevicedata->grade = $fromform->grade;
      $currentdevicedata->createdby = "Admin";
      $currentdevicedata->companyid = $selectedcompany;
      $nowDate = time();
      $currentdevicedata->timecreated = $nowDate;

    if (!$getexitdata) {
        $insertid = $DB->insert_record('block_skilladd', $currentdevicedata, true);
        redirect($CFG->wwwroot.'/blocks/skilladd/add_by_admin.php', get_string('successmage', 'block_skilladd'), null, \core\output\notification::NOTIFY_SUCCESS);

    }else{
        $currentdevicedata->id = $getexitdata->id;
        $insertid = $DB->update_record('block_skilladd', $currentdevicedata, true);
        redirect($CFG->wwwroot.'/blocks/skilladd/add_by_admin.php', get_string('updatemessage', 'block_skilladd'), null, \core\output\notification::NOTIFY_SUCCESS);

    }
    
      return $notis;
    }

    public function insertdata_user($fromform,$userid) {  
        global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }
        $getexitdata = $DB->get_record('block_skilladd', array('userid' => $userid, 'createdby' => 'User'));
      
        $currentdevicedata = new \stdClass();
        $currentdevicedata->userid = $userid;
        $currentdevicedata->department = $fromform->department;
        $currentdevicedata->skill = $fromform->skill;
        $currentdevicedata->skilllevel = $fromform->skilllevel;
        $currentdevicedata->position = $fromform->position;
        $currentdevicedata->grade = $fromform->grade;
        $currentdevicedata->createdby = "User";
        $currentdevicedata->companyid = $selectedcompany;
        $nowDate = time();
        $currentdevicedata->timecreated = $nowDate;
        if (!$getexitdata) {
            $insertid = $DB->insert_record('block_skilladd', $currentdevicedata, true);
            redirect($CFG->wwwroot.'/blocks/skilladd/add_by_admin.php', get_string('successmage', 'block_skilladd'), null, \core\output\notification::NOTIFY_SUCCESS);
        }else{
            $currentdevicedata->id = $getexitdata->id;
            $insertid = $DB->update_record('block_skilladd', $currentdevicedata, true);
            redirect($CFG->wwwroot.'/blocks/skilladd/add_by_admin.php', get_string('updatemessage', 'block_skilladd'), null, \core\output\notification::NOTIFY_SUCCESS);

        }
        
        return $notis;
      }

//   public function activity_access_reports($userid) {     
//     global $CFG, $DB, $OUTPUT;  
//     $baseurl = new moodle_url('/local/user_time_spent/activity_time_report.php', array('userid' => $userid));
//     $page = optional_param('page', 0, PARAM_INT);
//     $limit = 10;
//     $perpage = $page * $limit;
//      $table = new html_table();
//       $table->head = array(get_string('serialno', 'local_user_time_spent'), 
//         get_string('username', 'local_user_time_spent'), 
//         get_string('coursename', 'local_user_time_spent'),
//         get_string('activityname', 'local_user_time_spent'), 
//         get_string('spentime', 'local_user_time_spent')
//       ); 
//       $count=$perpage+1;
//       $get_alltime = get_user_spend_time($userid, $perpage, $limit);   
//       $totalcount = $get_alltime['count']; 
//       foreach ($get_alltime['name'] as $keyvalue) {
//         $user_name = $keyvalue->username;
//         $course_name = $keyvalue->fullname;
//         $spendtime = $keyvalue->timespent;
//         $mod_name = $keyvalue->name;
//         $userspenddate = secondsToTime($spendtime);
//         $table->data[] = array(
//           'serialno' => $count++,
//           'username' =>  $user_name,
//           'coursename' =>  $course_name,
//           'activityname' => $mod_name,
//           'spentime' => $userspenddate 
//           );
//       } 
//         echo '<div id="tblCustomers">';
//         echo html_writer::table($table);
//         echo $OUTPUT->paging_bar($totalcount, $page, $limit, $baseurl);
//         echo '<a href="'.$CFG->wwwroot.'/local/user_time_spent/activity_time_report_csv.php?userid='.$userid.'"id="export" role="button" class="btn btn-primary" style="margin-right:50px;" >
//         '.get_string('download_csv', 'local_user_time_spent').'
//          </a>';
//          echo '</div>';
// }  
}
