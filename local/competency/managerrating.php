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
 * classroom course format. Display the whole course as "classroom" made of modules.
 *
 * @package local_competency
 * @copyright 2020 Nilesh Pathade
 * @author Nilesh Pathade
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/local/competency/pagination.php');
require_once($CFG->dirroot.'/local/competency/lib.php');
$activepage = 'managerrating';
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_title(get_string('managersrating', 'local_competency'));
$PAGE->set_url($CFG->wwwroot.'/local/competency/managersrating.php');
$PAGE->set_heading(get_string('managersrating', 'local_competency'));
$PAGE->navbar->add(get_string('managersrating', 'local_competency'));
$PAGE->requires->css( new moodle_url($CFG->wwwroot . '/local/competency/customtablelayout.css?v=1'));
echo $OUTPUT->header();
//header added
require_once($CFG->dirroot.'/local/competency/header.php');
require_once($CFG->dirroot.'/local/competency/tabs.php');
if (!has_capability('local/competency:managerrating', $context)) {
    redirect($CFG->wwwroot. '/my/', \core\notification::error('No access for Manager rating...'));
    exit();
}
//submit manager ration
echo '<style>
  .competencytable {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
  }

  .competencytable th,
  .competencytable td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: center;
    vertical-align: middle;
    font-size: 14px;
  }

  .competencytable th {
    background-color: #003152;
    color: #fff;
    font-weight: bold;
    position: sticky;
    top: 0;
    z-index: 2;
  }

  .sticky-col {
    position: sticky;
    left: 0;
    background-color: #f4f7fa;
    z-index: 1;
  }

  .first-col {
    min-width: 240px;
    font-weight: bold;
    color: #003152;
  }

  .subcompcolor {
    background-color: #ecf0f4;
    font-weight: 500;
  }

  .usersrow input[type="number"],
  .usersrow input[type="text"] {
    width: 60px;
    text-align: center;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 4px;
  }

  .btn-primary {
    background-color: #ec9707;
    border-color: #ec9707;
    color: #fff;
  }

  .btn-primary:hover {
    background-color: #d27c00;
    border-color: #d27c00;
  }

  #page-local-competency-managersrating .table-wrap.wrapper1{
    border-radius: 10px !important;
        overflow: hidden;
        text-align: center;
        box-shadow: 0px 5px 0px 0px #003152;
    }

  .rating-label {
    font-weight: 600;
    color: #003152;
  }

  .competencytable tr:hover {
    background-color: #f9fbfd;
  }
</style>';

if(optional_param('submitmanagerrating', '' , PARAM_TEXT) === 'addrating' ){
    if (!has_capability('local/competency:managerrating', $context)) {
        redirect($CFG->wwwroot. '/my/', \core\notification::error('No access for Manager rating...'));
        exit();
    }
    $mastercompetencyid     =  optional_param_array('mastercompetencyid', array(), PARAM_TEXT);
    $mastercompetencyid1    =  optional_param_array('mastercompetencyid1', array(), PARAM_TEXT);
    $competencyid           =  optional_param_array('competencyid',  array(), PARAM_TEXT);
    $competencyid1           =  optional_param_array('competencyid1',  array(), PARAM_TEXT);
    $manager_ratings        =  optional_param_array('manager_rating',  array(), PARAM_TEXT);
    $subcompetencyid        =  optional_param_array('subcompetencyid', array(), PARAM_TEXT);
    $manager_rating1        =  optional_param_array('manager_rating1',  array(), PARAM_TEXT);
    $managerfinal_rating    =  optional_param_array('managerfinal_rating',  array(), PARAM_TEXT);
    $managerfinal_rating1   =  optional_param_array('managerfinal_rating1',  array(), PARAM_TEXT);
    $tearmsid               =  optional_param('tearmsid', '', PARAM_INT);
    $userid                 =  optional_param('userid', '', PARAM_INT);


    $i=0;
    $notificationflag = 0; $insertflag = 0;
    //add sub competency rating
    foreach($manager_ratings as $key => $manager_rating) {

        $checkResult = getManagerRatingExists($mastercompetencyid[$i],$competencyid[$i], 0, $tearmsid, $USER->id);

        if(count($checkResult) > 0){
            foreach($checkResult as $key => $value){
                $id = $value->id;
            }
             $ratestatus = getManagerFinalRatingExists($mastercompetencyid[$i],$competencyid[$i], 0, $tearmsid, $USER->id);
             $managerObj = new stdClass();
             $managerObj->rating = $manager_rating;
             $managerObj->tearms = $tearmsid;
             $managerObj->userid = $userid;
             $managerObj->finalrating = $managerfinal_rating[$i];
             if($ratestatus['ratefinal'] != $managerfinal_rating[$i]){
                $managerObj->ratestate = $ratestatus['ratestatus'];
                 // rerated status update on email  &  Notification to all Managers and Heads.
                $data = getUsersIds($USER->id);
                $uqArra = array();
                foreach ($data as $key => $userid2) {
                    $userids  = explode('-', trim($userid2->data));
                    $uqArra[] = $userids[0];
                }
                $usertomail = array_unique($uqArra);
                foreach ($usertomail as $key => $touser) {
                    $a = new stdClass();
                    $reuser = $DB->get_record("user", array('id' => $touser));
                    $userselected = $DB->get_record("user", array('id' => $userid));
                    $a->firstname = fullname($reuser);
                    $a->userselected = fullname($userselected);
                    $subject = get_string('rerating_subject', 'local_competency');            
                    $body = get_string('rerating_body', 'local_competency', $a);
                    $messageText = '';
                    $return = email_to_user($reuser, $USER, $subject, $messageText, $body, ", ", true);
                }
             }
             $managerObj->id = $id;
             $updatedid = $DB->update_record('manager_rating', $managerObj);            
             $i++;
             if(!empty($managerfinal_rating[$i])){
                $notificationflag = 2;
             }
        }else{
            $managerratingObj = new stdClass();
            $managerratingObj->master_competencyid = $mastercompetencyid[$i];
            $managerratingObj->competencyid = $competencyid[$i];    
            $managerratingObj->rating = $manager_rating;
            $managerratingObj->finalrating = $managerfinal_rating[$i];
            $managerratingObj->ratestate = 'Rated';
            $managerratingObj->tearms = $tearmsid;
            $managerratingObj->managerid = $USER->id;
            $managerratingObj->userid = $userid;
            $insertedid = $DB->insert_record('manager_rating', $managerratingObj);
            
            $insertflag = 1;
            $i++;
        }     
    }

    // add sub sub competency rating
    $i=0; 
    foreach($manager_rating1 as $key => $ratingvalue) {

        $checkResult = getManagerRatingExists($mastercompetencyid1[$i], $competencyid1[$i], $subcompetencyid[$i], $tearmsid, $USER->id);
         if(count($checkResult) > 0){
            foreach($checkResult as $key => $value){
                $id = $value->id;
            }
             $ratestatus1 = getManagerFinalRatingExists($mastercompetencyid1[$i], $competencyid1[$i], $subcompetencyid[$i], $tearmsid, $USER->id);
             $managerratingUpdateObj = new stdClass();
             $managerratingUpdateObj->rating = $ratingvalue;
             $managerratingUpdateObj->tearms = $tearmsid;
             $managerratingUpdateObj->userid = $userid;
             $managerratingUpdateObj->finalrating = $managerfinal_rating1[$i];
             
              if($ratestatus1['ratefinal'] != $managerfinal_rating1[$i]){
                $managerratingUpdateObj->ratestate = $ratestatus1['ratestatus'];
                // rerated status update on email  &  Notification to all Managers and Heads.
                $data = getUsersIds($USER->id);
                $uqArra = array();
                foreach ($data as $key => $userid3) {
                    $userids  = explode('-', trim($userid3->data));
                    $uqArra[] = $userids[0];
                }
                $usertomail = array_unique($uqArra);
                foreach ($usertomail as $key => $touser) {
                    $a = new stdClass();
                    $reuser = $DB->get_record("user", array('id' => $touser));
                    $a->firstname = fullname($reuser);
                    $a->userself = fullname($USER);
                    $subject = get_string('rerating_subject', 'local_competency');            
                    $body = get_string('rerating_body', 'local_competency', $a);
                    $messageText = '';
                    $return = email_to_user($reuser, $USER, $subject, $messageText, $body, ", ", true);
                }
             }             
             $managerratingUpdateObj->id = $id;
             $updatedid = $DB->update_record('manager_rating', $managerratingUpdateObj);
             if(!empty($managerfinal_rating1[$i])){
                $notificationflag = 2;
             }
             $i++;
        }else{
            $competencyidids = $DB->get_record('competency_users', array('id'=>  $mastercompetencyid1[$i]));
            $managerratingInsertObj = new stdClass();
            $managerratingInsertObj->master_competencyid = $mastercompetencyid1[$i];
            $managerratingInsertObj->competencyid = $competencyidids->competencyid;; 	
            $managerratingInsertObj->subcomptencyid = $subcompetencyid[$i];
            $managerratingInsertObj->rating = $ratingvalue;
            $managerratingInsertObj->finalrating = $managerfinal_rating1[$i];
            $managerratingInsertObj->ratestate = 'Rated';
            $managerratingInsertObj->tearms = $tearmsid;
            $managerratingInsertObj->managerid = $USER->id;
            $managerratingInsertObj->userid = $userid;
            $insertedid = $DB->insert_record('manager_rating', $managerratingInsertObj);
            $insertflag = 1;
            $i++;
        }
    }
    // Notification to Manager rating...
    if($insertflag == 1){ 
      $a = new stdClass();
      $user = $DB->get_record_sql("SELECT DISTINCT u.* FROM {user} as u INNER JOIN {competency_users} as cu ON u.id = cu.userid 
        WHERE u.id = ?", array($userid));
      $subject = get_string('managerrating_subject', 'local_competency');
      $a->firstname = fullname($user);
      $a->managername = fullname($USER);
      $body = get_string('managerrating_body', 'local_competency', $a);
      $messageText = '';
      $return = email_to_user($user, $USER, $subject, $messageText, $body, ", ", true);
    }

    // Notification to Manager final rating...
    if($notificationflag == 2){ 
      $a = new stdClass();
      $user = $DB->get_record_sql("SELECT DISTINCT u.* FROM {user} as u INNER JOIN {competency_users} as cu ON u.id = cu.userid 
        WHERE u.id = ?", array($userid));
      $subject = get_string('managerfinalrating_subject', 'local_competency');
      $a->firstname = fullname($user);
      $body = get_string('managerfinalrating_body', 'local_competency', $a);
      $messageText = '';
      $return = email_to_user($user, $USER, $subject, $messageText, $body, ", ", true);

      // After Manager final rating mail to L and D Manager.

      $sqlFor_getLandD = "SELECT uid.data, u.department FROM {user} as u 
                INNER JOIN {user_info_data} as uid ON u.id = uid.userid 
                INNER JOIN {user_info_field} as uif ON uif.id = uid.fieldid 
                WHERE uif.shortname = 'landdmanager' and u.id = ?";

      $landduser = $DB->get_record_sql($sqlFor_getLandD, array($userid));

      if(!empty($landduser->data)){
          $landuseridarr  = explode('-', trim($landduser->data));
          $landuserid = $landuseridarr[0];
          $userarr = $DB->get_record('user', array('id' => $landuserid));

          $userselected = $DB->get_record('user', array('id' => $userid));

          $subject = get_string('tolandd_subject', 'local_competency');
          $a->firstname = fullname($userarr);
          $a->managername = fullname($USER);
          $a->userselected = fullname($userselected);
          $a->department = $landduser->department;
          $body = get_string('tolandd_body', 'local_competency', $a);
          $messageText = '';
          $return = email_to_user($userarr, $USER, $subject, $messageText, $body, ", ", true);

        
      }

    }
    $message ="You have successfully added manager rating";
}

if( !empty($message) ){ ?> 
<br/>
<div class="alert alert-success successmessgae">
  <?php echo $message; ?>
</div> 

<?php }

$viewcontent=''; $viewselct=''; $buselct=''; $viewuser ='';

// Get BU departments list.
$buResult = getdepartment();

$buselct .= '<select name="buid" id="buid" class="form-control" onchange="changeDepartment(this.value, '.$USER->id.')">
  <option value="">Select Business Unit</option>';
    foreach ($buResult as $key => $value){
    	$buselct .= '<option value="'.$value->department.'">'.$value->department.'</option>';
    }
$buselct .='</select>';

//Get user info role list
/*$searchRoles = getsearchRoles();
$viewselct .= '<select name="roleid" id="roleid" class="form-control">
        <option value="">Select role</option>';
        foreach($searchRoles as $role) { 
        $viewselct .="<option value='".$role->id."'>".$role->shortname."</option>";
      }
      $viewselct .='</select>';*/

//Get user list 
//$users = $DB->get_records('user', array());
$viewuser .= '<select name="userid" id="userid" class="form-control">
        <option value="">Select user</option>';
       /* foreach($users as $user) { 
        $viewuser .="<option value='".$user->id."'>".fullname($user)."</option>";
      }*/
      $viewuser .='</select>';
$tearms ='';
$tearms .= '<select name="tearmsid" id="tearmsid" class="form-control">
        <option value="">Select terms</option>
        <option value="1">First Half</option>
        <option value="2">Second Half</option>';
$tearms .='</select>';


$viewcontent ='<div class="row" style="text-align:center;margin-bottom:10px;margin-top:10px;">
  <div class="col-md-3">
     '.$buselct.'
  </div>
  
  <div class="col-md-3" id="departmentshow">
     '.$viewuser.'
  </div>
  <div class="col-md-2" >
     '.$tearms.'
  </div>
  <div class="col-md-3"><button type="button" class="btn btn-primary" onclick="filterManagerRating()">Search</button></div>
</div><p id="errormessage" style="color:red;text-align:center;"></p><br/>';


echo $viewcontent; 
?>
<div class="view1">
  	<div id="table-scroll" class="table-scroll">
			<ul> 
				<form name="managerrating" id="managerrating" method="POST">
         <div class="table-wrap wrapper1">
					<table class="rating-table competencytable">


					</table>
          </div> 
				</form>

				</ul>	
		</div>
</div>
<?php $PAGE->requires->js('/local/competency/js/competency.js'); ?>
<?php echo $OUTPUT->footer(); ?>
<script>
function changeDepartment(buid, userid){  
   // var buid= $('#buid :selected').val();
    if(buid ==''){
        $('#errormessage').show();
        $('#errormessage').html("Please select bussiness unit!");
        setTimeout(function() {
            $('#errormessage').hide();
        }, 3000);
    }else{
  $.ajax({
        type: "POST",
        url: "competencyAjax.php",
        data: {
            departmentId: buid,
            userid:userid,
            ccase:'fiterDepartment'
        },
        success: function (response) {
            $('#errormessage').hide();
            $("#departmentshow").html(response);
        },
        error: function (e, msg) {
            
        }
    });
    }
}

//manager rating filter JS
function filterManagerRating(){
    var buid= $('#buid :selected').val();
   // var roleid= $('#roleid :selected').val();
    var userid= $('#userid :selected').val();   
    var tearmsid =  $('#tearmsid :selected').val(); 
    if(buid ==''){
        $('#errormessage').show();
        $('#errormessage').html("Please select bussiness unit and role!");
        setTimeout(function() {
            $('#errormessage').hide();
        }, 3000);
    }/*else if(roleid ==''){
        $('#errormessage').show();
        $('#errormessage').html("Please select bussiness unit and role!");
        setTimeout(function() {
            $('#errormessage').hide();
        }, 3000);
    }*/else if(userid ==''){
        $('#errormessage').show();
        $('#errormessage').html("Please select at least one user!");
        setTimeout(function() {
            $('#errormessage').hide();
        }, 3000);
    }else{
  $.ajax({
        type: "POST",
        url: "competencyAjax.php",
        data: {
            //roleid: roleid,
            buid :buid,
            userid :userid,
            tearmsid:tearmsid,
            ccase:'fiterManagerRating'
        },
        success: function (response) {
            $('#errormessage').hide();
            $(".competencytable").html(response);
        },
        error: function (e, msg) {
            
        }
    });
    }
}
</script>
<script type="text/javascript">
  $(document).ready(function() {
   $(".main-table").clone(true).appendTo('#table-scroll').addClass('clone');   
 });
</script>