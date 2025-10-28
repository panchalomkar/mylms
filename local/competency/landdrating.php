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
$activepage = 'landdrating';
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_title(get_string('landdrating', 'local_competency'));
$PAGE->set_url($CFG->wwwroot.'/local/competency/landdrating.php');
$PAGE->set_heading(get_string('landdrating', 'local_competency'));
$PAGE->navbar->add(get_string('landdrating', 'local_competency'));
$PAGE->requires->css( new moodle_url($CFG->wwwroot . '/local/competency/customtablelayout.css'));
echo $OUTPUT->header();
//header added
require_once($CFG->dirroot.'/local/competency/header.php');
require_once($CFG->dirroot.'/local/competency/tabs.php');
if (!has_capability('local/competency:landdrating', $context)) {
    redirect($CFG->wwwroot. '/my/', \core\notification::error('No access...'));
    exit();
}
//submit manager ration

if(optional_param('submitmanagerrating', '' , PARAM_TEXT) === 'addrating' ){
    if (!has_capability('local/competency:landdrating', $context)) {
        redirect($CFG->wwwroot. '/my/', \core\notification::error('No access...'));
        exit();
    }
    $mastercompetencyid = optional_param_array('mastercompetencyid', array(), PARAM_TEXT);
    $mastercompetencyid1 = optional_param_array('mastercompetencyid1', array(), PARAM_TEXT);
    $competencyid =  optional_param_array('competencyid',  array(), PARAM_TEXT);
    $competencyid1 =  optional_param_array('competencyid1',  array(), PARAM_TEXT);
    $landd_ratings =  optional_param_array('landd_rating',  array(), PARAM_TEXT);
    $subcompetencyid =  optional_param_array('subcompetencyid', array(), PARAM_TEXT);
    $landd_rating1 =  optional_param_array('landd_rating1',  array(), PARAM_TEXT);
    $landdstatus =  optional_param_array('landdstatus',  array(), PARAM_INT);
    $landdstatus1 =  optional_param_array('landdstatus1',  array(), PARAM_INT);
    $progstatus =  optional_param_array('progstatus',  array(), PARAM_INT);
    $progstatus1 =  optional_param_array('progstatus1',  array(), PARAM_INT);
    $tearmsid=  optional_param('tearmsid','', PARAM_INT);
    $userid =  optional_param('userid', '', PARAM_INT);
    $i=0;    
    $notificationflag = 0;
    //add sub competency rating
    foreach($landd_ratings as $key => $landd_rating){       

        $checkResult = getLanddRatingExists($mastercompetencyid[$i],$competencyid[$i], 0, $tearmsid, $USER->id);
        
        if(count($checkResult) > 0){
            foreach($checkResult as $key => $value){
                $id = $value->id;
            }          
             $landdObj = new stdClass();
             $landdObj->rating = $landd_rating;
             $landdObj->tearms = $tearmsid; 
             $landdObj->landdstatus = $landdstatus[$i];            
             if(!empty($progstatus[$i])){
              $landdObj->progstatus = $progstatus[$i];
            }else{
              $landdObj->progstatus = 0;
            }
             $landdObj->progstatus = $progstatus[$i] == '' ? 0 : $progstatus[$i];
             $landdObj->id = $id;
             $updatedid = $DB->update_record('landd_rating', $landdObj);
             $chkEmail = emailOnReject($userid,$landdstatus[$i]);            
             $i++;

        }else{
            $landdratingObj = new stdClass();
            $landdratingObj->master_competencyid = $mastercompetencyid[$i];
            $landdratingObj->competencyid = $competencyid[$i];    
            $landdratingObj->rating = $landd_rating;
            $landdratingObj->landdstatus = $landdstatus[$i];
            if(!empty($progstatus[$i])){
              $landdratingObj->progstatus = $progstatus[$i];
            }else{
              $landdratingObj->progstatus = 0;
            }
            $landdratingObj->tearms = $tearmsid;
            $landdratingObj->ldteamid = $USER->id;
            $insertedid = $DB->insert_record('landd_rating', $landdratingObj);
            $notificationflag = 1;
            $chkEmail = emailOnReject($userid,$landdstatus[$i]);
            $i++;
        }     
    }

    // add sub sub competency rating
    $i=0;

    foreach($landd_rating1 as $key => $ratingvalue){       
        $checkResult = getLanddRatingExists($mastercompetencyid1[$i], $competencyid1[$i], $subcompetencyid[$i], $tearmsid, $USER->id);        
         if(count($checkResult) > 0){
            foreach($checkResult as $key => $value){
                $id = $value->id;
            } 
               
             $landdratingUpdateObj = new stdClass();
             $landdratingUpdateObj->rating = $ratingvalue;
             $landdratingUpdateObj->tearms = $tearmsid;
             $landdratingUpdateObj->landdstatus = $landdstatus1[$i];
             if(!empty($progstatus1[$i])){
                $landdratingUpdateObj->progstatus = $progstatus1[$i];
              }else{
                $landdratingUpdateObj->progstatus = 0;
              }             
             $landdratingUpdateObj->id = $id;
             $updatedid = $DB->update_record('landd_rating', $landdratingUpdateObj);
             $chkEmail = emailOnReject($userid,$landdstatus1[$i]);
             $i++;
        }else{
            
            $competencyidids = $DB->get_record('competency_users', array('id'=>  $mastercompetencyid1[$i]));
            $landdratingInsertObj = new stdClass();
            $landdratingInsertObj->master_competencyid = $mastercompetencyid1[$i];
            $landdratingInsertObj->competencyid = $competencyidids->competencyid;; 	
            $landdratingInsertObj->subcomptencyid = $subcompetencyid[$i];
            $landdratingInsertObj->rating = $ratingvalue;
            $landdratingInsertObj->landdstatus = $landdstatus1[$i];
            if(!empty($progstatus1[$i])){
                $landdratingInsertObj->progstatus = $progstatus1[$i];
              }else{
                $landdratingInsertObj->progstatus = 0;
              }           
            $landdratingInsertObj->tearms = $tearmsid;
            $landdratingInsertObj->ldteamid = $USER->id;
            $insertedid = $DB->insert_record('landd_rating', $landdratingInsertObj);
            $chkEmail = emailOnReject($userid,$landdstatus1[$i]);
            $notificationflag = 1;
            $i++;
        }
    }

    // Notification to Manager rating...
    if(!empty($userid) &&  $notificationflag == 1){ 
      $a = new stdClass();
      $user = $DB->get_record_sql("SELECT DISTINCT u.* FROM {user} as u INNER JOIN {competency_users} as cu ON u.id = cu.userid 
        WHERE u.id = ?", array($userid));
      $subject = get_string('landdrating_subject', 'local_competency');
      $a->firstname = fullname($user);
      $body = get_string('landdrating_body', 'local_competency', $a);
      $messageText = '';
      $return = email_to_user($user, $USER, $subject, $messageText, $body, ", ", true);
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

$buselct .= '<select name="buid" id="buid" class="form-control">
  <option value="">Select Business Unit</option>';
    foreach ($buResult as $key => $value){
    	$buselct .= '<option value="'.$value->department.'">'.$value->department.'</option>';
    }
$buselct .='</select>';

//Get user info role list
$searchRoles = getsearchRoles();
$viewselct .= '<select name="roleid" id="roleid" class="form-control" onchange="changeDepartmentLandd(this.value)">
        <option value="">Select role</option>';
        foreach($searchRoles as $role) { 
        $viewselct .="<option value='".$role->id."'>".$role->shortname."</option>";
      }
      $viewselct .='</select>';

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
  <div class="col-md-2">
     '.$buselct.'
  </div>
  <div class="col-md-2">
     '.$viewselct.'
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
            <table class="main-table table competencytable">

					  </table> 
        </div>
				</form>

				</ul>	
		</div>
</div>
<?php $PAGE->requires->js('/local/competency/js/competency.js'); ?>
<?php echo $OUTPUT->footer(); ?>
<script>
function changeDepartmentLandd(roleid){  
    var buid= $('#buid :selected').val();
    if(roleid =='' && buid == ''){
        $('#errormessage').show();
        $('#errormessage').html("Please select bussiness unit & role!");
        setTimeout(function() {
            $('#errormessage').hide();
        }, 3000);
    }else{
  $.ajax({
        type: "POST",
        url: "competencyAjax.php",
        data: {
            departmentId: buid,
            roleid: roleid,
            ccase:'fiterDepartmentLandD'
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
            ccase:'fiterlanddRating'
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

//change sub competency course completion status filter JS
function filterCheckStatus(chkid,no){
   $.ajax({
        type: "POST",
        url: "competencyAjax.php",
        data: {            
            statusid:chkid,
            ccase:'filterCheckStatus'
        },
        success: function (response) {
            $("#prgshow_"+no).html(response);
        },
        error: function (e, msg) {
            
        }
    });


}
//change sub sub competency course completion status filter JS
function filterCheckStatus1(chkid,no){
   $.ajax({
        type: "POST",
        url: "competencyAjax.php",
        data: {            
            statusid:chkid,
            ccase:'filterCheckStatus1'
        },
        success: function (response) {
            $("#prgshow1_"+no).html(response);
        },
        error: function (e, msg) {
            
        }
    });


}
</script>
<script type="text/javascript">
  $(document).ready(function() {
   $(".main-table").clone(true).appendTo('#table-scroll').addClass('clone');   
 });
</script>