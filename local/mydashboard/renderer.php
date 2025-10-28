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
* @package local_mydashboard
* @category local
* @copyright  ELS <admin@elearningstack.com>
* @author eLearningstack
*/
require_once('../../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once $CFG->dirroot . '/grade/report/overview/lib.php';
require_once $CFG->dirroot . '/grade/lib.php';
require_once('lib.php');
require_login();
class local_mydashboard_renderer extends plugin_renderer_base {

  public function activity_access_reports($categoryid) {     
    global $CFG, $DB, $OUTPUT;  
    $baseurl = new moodle_url('/local/mydashboard/smeleader_report.php', array('categoryid' => $categoryid));
    $page = optional_param('page', 0, PARAM_TEXT);
    $limit = 10;
    $perpage = $page * $limit;
     $table = new html_table();
      $table->head = array(get_string('serialno', 'local_mydashboard'), 
        get_string('username', 'local_mydashboard'), 
        get_string('catname', 'local_mydashboard'),
        get_string('points', 'local_mydashboard'),
        get_string('pendingpoint', 'local_mydashboard'),
        get_string('gradeaverage', 'local_mydashboard'),
        get_string('ratingaverage', 'local_mydashboard'),
        get_string('role', 'local_mydashboard'),
        get_string('action', 'local_mydashboard'),
      ); 
      //$point = '';
       $count = $perpage+1;
       $get_alltime = get_user_point($categoryid, $perpage, $limit);
      //  foreach ($get_alltime as $kevalue) {
      //  $point += $kevalue->points;
      //  echo $point."<br>";
      //  }
       
      //  echo "<pre>";
      //  print_r($get_alltime);
      //  echo "</pre>";
      
       $totalcount = $get_alltime['count']; 
      foreach ($get_alltime['name'] as $keyvalue) {
        $userdata = $DB->get_record('user', array('id' => $keyvalue->userid));
        $catname = $DB->get_record('course_categories', array('id' => $categoryid));
        //$getcatname = $DB->get_record_sql("SELECT * FROM {course} c INNER JOIN {course_categories} cc ON cc.id = c.category WHERE cc.id = $keyvalue->userid");
        $catdata = $DB->get_record('assign_cat_point', array('categoryid' => $categoryid));
        if ($keyvalue->pointsss >= $catdata->assignpoint) {
          $assignsmeleader = $DB->get_record('assign_smeleader', array('smeleader_id' => $keyvalue->userid));
          if (empty($assignsmeleader)) {
            $insert = new stdClass();
            $insert->smeleader_id = $keyvalue->userid;
            $insert->roleid = 16;
            $insert->categoryid = $categoryid;
            $insert->timecreated = time();
            $DB->insert_record('assign_smeleader', $insert);
          }else{
            $insert = new stdClass();
            $insert->id = $assignsmeleader->id;
            $insert->smeleader_id = $keyvalue->userid;
            $insert->roleid = 16;
            $insert->categoryid = $categoryid;
            $insert->timecreated = time();
            $DB->update_record('assign_smeleader', $insert);
          }
        
        $requestbtn = '<a href="'.$CFG->wwwroot.'/local/mydashboard/sme_request.php?id='.$userdata->id.'" type="button" class="btn btn-primary question">Send Request</a>';
        $role = "SME leader".'<i class="fa fa-paper-plane ml-2 smeleadersendmsg" value="'.$userdata->id.'" style="cursor: pointer" title="Send Message"></i>' .'  '. '<i class="fa fa-comments-o ml-2 smeleaderfeedback" value="'.$userdata->id.'" style="cursor: pointer" title="Rating and Comments"></i>';
        }else{
          $role = "Student";
          $requestbtn = "--";
        }

        $getcourses = $DB->get_records('course', array('category' => $categoryid));
        $finalgrade = 0;
        $countgrade = 0;

        foreach ($getcourses as $keyvaluess) {
        // Get course grade_item
        $course_item = grade_item::fetch_course_item($keyvaluess->id);

        // Get the stored grade
        $course_grade = new grade_grade(array('itemid'=>$course_item->id, 'userid'=>$keyvalue->userid));
        $course_grade->grade_item =& $course_item;
        $finalgrade += $course_grade->finalgrade;
        $countgrade++;
        }
     

        $average = $finalgrade/$countgrade;

        $user_object = core_user::get_user($keyvalue->userid);
        $person_profile_pic = $OUTPUT->user_picture($user_object,array('link'=>true));

        $pendingpoint = $catdata->assignpoint - $keyvalue->pointsss;
        $stargetdata ='';
        $getdat = $DB->get_records('sme_leader_feedback', array('smeleader_id' => $userdata->id));
        $tarte = 0;
        foreach ($getdat as $keyvalue) {
          $tarte += $keyvalue->rate;
        }
        if ($getdat) {
          // $stargetdata = '<div class="fedbacksec">
          // <span class="fa fa-star checked"  style="color:yellow"></span>
          
          // </div>';
          $stargetdata = '';
       
          for ($i=1; $i <= $tarte/5; $i++) { 
           
            if ($i <= 5) {
              $stargetdata .= '<div class="fedbacksec">
          <span class="fa fa-star checked" style="color:orange"></span>
          </div>';
            }
           

            }
        }
      
        $table->data[] = array(
          'serialno' => $count++,
          'username' =>  $person_profile_pic .' '. $userdata->firstname.' '.$userdata->lastname,
          'catname' =>  $catname->name,
          'points' => $keyvalue->pointsss,
          'pendingpoints' => $pendingpoint,
          'gradeaverage' => round($average),
          'ratingaverage' => $stargetdata.'  '. $tarte/5 . ' ('. count($getdat) .' Review)',
          'role' => $role,
          'action' => $requestbtn,
          );
      } 

        echo '<div id="tblCustomers">';
        echo html_writer::table($table);
        if(empty($totalcount)){
          echo '<center><b>'.get_string("norecordfound", "local_mydashboard").'</b></center>';
         // \core\notification::add(get_string('norecordfound', 'local_mydashboard'), \core\notification::ERROR);
        }
        echo $OUTPUT->paging_bar($totalcount, $page, $limit, $baseurl);
        echo '<a href="'.$CFG->wwwroot.'/local/mydashboard/smeleader_report_csv.php?categoryid='.$categoryid.'"id="export" role="button" class="btn btn-primary" style="margin-right:50px;" >
        '.get_string('download_csv', 'local_mydashboard').'
         </a>';
         echo '</div>';
         echo $OUTPUT->render_from_template('local_mydashboard/smeleader', '');
         echo $OUTPUT->render_from_template('local_mydashboard/smeleaderfeedback', '');
}  

// get sme leader feedback function here

public function access_feedback_reports($categoryid) {     
  global $CFG, $DB, $OUTPUT;  
  $baseurl = new moodle_url('/local/mydashboard/smeleader_fdk_report.php', array('categoryid' => $categoryid));
  $page = optional_param('page', 0, PARAM_INT);
  $limit = 10;
  $perpage = $page * $limit;
   $table = new html_table();
    $table->head = array(get_string('serialno', 'local_mydashboard'), 
      get_string('username', 'local_mydashboard'), 
      get_string('catname', 'local_mydashboard'),
      get_string('fromuser', 'local_mydashboard'),
      get_string('viewfeedback', 'local_mydashboard')
    ); 
     $count = $perpage+1;
     $get_alltime = get_feedback_sme($categoryid, $perpage, $limit);

     $totalcount = $get_alltime['count']; 
    foreach ($get_alltime['name'] as $keyvalue) {
      $userdata = $DB->get_record('user', array('id' => $keyvalue->smeleader_id, 'deleted' => 0));
      $catname = $DB->get_record('course_categories', array('id' => $keyvalue->categoryid));
      $feedbackuser = $DB->get_record('user', array('id' => $keyvalue->feedback_userid));

      $user_object = core_user::get_user($keyvalue->smeleader_id);
      $person_profile_pic = $OUTPUT->user_picture($user_object,array('link'=>true));

      $user_objects = core_user::get_user($feedbackuser->id);
      $person_profile_pics = $OUTPUT->user_picture($user_objects,array('link'=>true));
     

     
      
      $table->data[] = array(
        'serialno' => $count++,
        'username' =>  $person_profile_pic .' '. $userdata->firstname.' '.$userdata->lastname,
        'catname' =>  $catname->name,
        'fromuser' =>  $person_profile_pics .' '. $feedbackuser->firstname.' '.$feedbackuser->lastname,
        //'viewfeedback' => ' <a href="'.$CFG->wwwroot.'/local/mydashboard/view_user_feedback.php?id='.$keyvalue->feedback_userid.'" class="mt-auto btn btn-primary  "><i class="fa fa-eye" style="cursor: pointer" > View</i></a>'
       'viewfeedback' => '<i class="fa fa-eye ml-2 viewcomments" smeuserid="'.$userdata->id.'" value="'.$feedbackuser->id.'" style="cursor: pointer" title="View Comments"></i>'
        );
    } 

    // $datastore = array(
    //   '' => ,
    // );

     echo $OUTPUT->render_from_template('local_mydashboard/viewfeedback', $datastore);
      echo '<div id="tblCustomers">';
      echo html_writer::table($table);
      if(empty($totalcount)){
        echo '<center><b>'.get_string("norecordfound", "local_mydashboard").'</b></center>';
       // \core\notification::add(get_string('norecordfound', 'local_mydashboard'), \core\notification::ERROR);
      }
      echo $OUTPUT->paging_bar($totalcount, $page, $limit, $baseurl);
       echo '</div>';
}  


public function send_request($fromform) {     
  global $USER, $CFG, $DB, $OUTPUT, $SESSION,$PAGE,$SITE;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }
    if ($selectedcompany) {
      $commanageruser = $DB->get_records_sql("SELECT cu.userid as id FROM {role} r 
      INNER JOIN {role_assignments} ra ON ra.roleid = r.id 
      INNER JOIN {company_users} cu ON cu.userid = ra.userid  
      WHERE cu.companyid = $selectedcompany AND r.name = 'landmanager' ");
    }else{
      $commanageruser = $DB->get_records_sql("SELECT ra.userid as id FROM {role} r 
      INNER JOIN {role_assignments} ra ON ra.roleid = r.id
      WHERE r.name = 'landmanager' ");
    }

    foreach ($commanageruser as $kevalue) {
      
    if ($fromform) {

      $getfeed = $DB->get_record('send_request', array('from_userid' => $USER->id, 'for_userid' => $fromform->for_userid, 'purpose' => $fromform->yesno));

      if (!$getfeed) {
        $insert = new stdClass();
        $insert->subject = $fromform->subjecttitle;
        $insert->message = $fromform->message['text'];
        $insert->for_userid = $fromform->for_userid;
        $insert->from_userid = $USER->id;
        $insert->to_userid = $kevalue->id;
        $insert->status = 0;
        $insert->purpose = $fromform->yesno;
        $insert->timecreated = time();
        $messageid = $DB->insert_record('send_request', $insert);
      }else{
        $insert = new stdClass();
        $insert->id = $getfeed->id;
        $insert->subject = $fromform->subjecttitle;
        $insert->message = $fromform->message['text'];
        $insert->for_userid = $fromform->for_userid;
        $insert->from_userid = $USER->id;
        $insert->to_userid = $kevalue->id;
        $insert->status = 0;
        $insert->purpose = $fromform->yesno;
        $insert->timecreated = time();
        $messageid = $DB->update_record('send_request', $insert);
      }

      /////////////////////////////////////////////////////////

      $userto = $DB->get_record('user', array('id' => $kevalue->id), '*', MUST_EXIST);
      $userfrom = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
      $subject = $fromform->subjecttitle;

      $messagetext = $messageHtml = '<div class="card" style="background-color:#f3f2f0">
      <div class="card-header">
       '.$userto->firstname.' '.$userto->lastname.'
      </div>
      <div class="card-body">
        <blockquote class="blockquote mb-0">
          <p>'.$fromform->message['text'].'</p>
          
        </blockquote>
      </div>
    </div>'; 

      $sentmail = email_to_user($userto, $userfrom, $subject, $messagetext, $messageHtml, "", "", true);

      if ($sentmail) {
        
      $message = new \core\message\message();
      $message->courseid = $SITE->id;
      $message->component = 'moodle';
      $message->name = 'instantmessage';
      $message->userfrom = $userfrom;
      $message->userto = $userto;
      $message->subject = $fromform->subjecttitle;
      $message->fullmessage = $fromform->message['text'];
      $message->fullmessageformat = FORMAT_MARKDOWN;
      $message->fullmessagehtml = "<p>$fromform->message['text']</p>";
      $message->smallmessage = $fromform->message['text'];
      $message->notification = 0;
      $message->contexturl = '';
      $message->contexturlname = '';
      $message->replyto = $userfrom->email;
    // User image.
      $userpicture = new user_picture($userfrom);
      $userpicture->size = 1; // Use f1 size.
      $userpicture->includetoken = $userto->id; // Generate an out-of-session token for the user receiving the message.
      $message->customdata = [
          'notificationiconurl' => $userpicture->get_url($PAGE)->out(false),
          'actionbuttons' => [
              'send' => get_string_manager()->get_string('send', 'message', null, $message->userto->lang),
          ],
          'placeholders' => [
              'send' => get_string_manager()->get_string('writeamessage', 'message', null, $message->userto->lang),
          ],
      ];
      $messageid = message_send($message);
      }

    }
  }
   return true;  
} 

public function get_approval_request() {     
  global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }
    $baseurl = new moodle_url('/local/mydashboard/l_and_d_aprroval.php', array());
    $page = optional_param('page', 0, PARAM_INT);
    $limit = 10;
    $perpage = $page * $limit;
     $table = new html_table();
      $table->head = array(get_string('serialno', 'local_mydashboard'), 
        get_string('username', 'local_mydashboard'),
        get_string('fromuser', 'local_mydashboard'),
        get_string('porposerequest', 'local_mydashboard'),
        get_string('status', 'local_mydashboard'),
        get_string('message', 'local_mydashboard')
      ); 
       $count = $perpage+1;
       $get_alltime = get_aproval_request($perpage, $limit);
     
        $totalcount = $get_alltime['count']; 
      foreach ($get_alltime['name'] as $keyvalue) {
        $userdata = $DB->get_record('user', array('id' => $keyvalue->from_userid, 'deleted' => 0));
        $feedbackuser = $DB->get_record('user', array('id' => $keyvalue->for_userid));
  
        $user_object = core_user::get_user($keyvalue->from_userid);
        $person_profile_pic = $OUTPUT->user_picture($user_object,array('link'=>true));
  
        $user_objects = core_user::get_user($feedbackuser->id);
        $person_profile_pics = $OUTPUT->user_picture($user_objects,array('link'=>true));

        //$feedbackusersss = $DB->get_record('send_request', array('from_userid' => $keyvalue->from_userid, 'for_userid' => $keyvalue->for_userid));

        if ($keyvalue->status == 0) {
          $aprrovastatus = '<a href="#" class="status1id" purposeid="'.$keyvalue->purpose.'" smeuserid="'.$userdata->id.'" value="'.$feedbackuser->id.'">Approve!</a>';
        }else{
          $aprrovastatus = '<a href="#"  class="text-danger status2id" purposeid="'.$keyvalue->purpose.'" smeuserid="'.$userdata->id.'" value="'.$feedbackuser->id.'">Approved!</a>';
        }

        if ($keyvalue->purpose == 1) {
          $purposestatus = "Mentor";
        }else{
          $purposestatus = "Student";
        }
        $showmsg = '<a href="#" class="msgid" purposeid="'.$keyvalue->purpose.'" smeuserid="'.$userdata->id.'" value="'.$feedbackuser->id.'"><i class="fa fa-envelope-o" aria-hidden="true"></i> !</a>';
        $table->data[] = array(
          'serialno' => $count++,
          'username' => $person_profile_pics .' '. $feedbackuser->firstname.' '.$feedbackuser->lastname,
          'fromuser' => $person_profile_pic .' '. $userdata->firstname.' '.$userdata->lastname,
          'purpose' => $purposestatus,
          'approve' => $aprrovastatus,
          'message' => $showmsg
          );
     }
  
      // $datastore = array(
      //   '' => ,
      // );
  
       echo $OUTPUT->render_from_template('local_mydashboard/requestmsgview','');
        echo '<div id="tblCustomers">';
        echo html_writer::table($table);
        if(empty($totalcount)){
          echo '<center><b>'.get_string("norecordfound", "local_mydashboard").'</b></center>';
          \core\notification::add(get_string('norecordfound', 'local_mydashboard'), \core\notification::ERROR);
        }
        echo $OUTPUT->paging_bar($totalcount, $page, $limit, $baseurl);
        echo '</div>'; 
}
}
