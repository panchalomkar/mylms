<?php
/**
 * This file returns the json response for specific action.
 * 
 * @package	local_social_wall
 * @version	9.3
 * @author	Manisha M
 * @since 12-06-2019
 * @paradiso
 */

global $CFG, $USER, $DB, $SESSION,$OUTPUT;
define('AJAX_SCRIPT', true);

require_once('../../../config.php');
require_once("{$CFG->dirroot}/local/social_wall/lib.php");
require_once("{$CFG->libdir}/moodlelib.php");
require_login();
require_sesskey();

$userid = $_REQUEST['userid'];
if(!empty($userid)){
    $userdetails= $DB->get_record_sql("SELECT * FROM {user} WHERE id='$userid'");
    
    $userPic = $OUTPUT->user_picture($userdetails, array('size'=>60));
    $obj_merged = (object) array_merge((array) $userdetails, (array) $userPic);
    // echo "<pre>";
    // print_r($userPic);exit;
    echo json_encode($obj_merged);
}

if(!empty($_REQUEST['user_id'])){

	$record 				= new stdClass();
	$record->id         	=   $_REQUEST['user_id'];
	$record->country    	=   $_REQUEST['user_country'];
	$record->city   		=   $_REQUEST['user_city'];
	$record->description   	=   $_REQUEST['user_bio'];

	$recordBackground 				= new stdClass();
	$recordBackground->userid       =   $_REQUEST['user_id'];
	$recordBackground->name    		=   'user_background_color';
	$recordBackground->value   		=   $_REQUEST['user_background'];

	$checkuser = $DB->get_record('user',['id'=>$_REQUEST['user_id']]);
	if(!empty($checkuser->id)){
		$userupdated  = $DB->update_record('user', $record);
		if($userupdated==true){
			$status = 'success';
		}else{
			$status = 'error';
		}
	}
	
	if($status=='success'){
		$checkpreference = $DB->get_record('user_preferences',['userid'=>$_REQUEST['user_id'],'name'=>'user_background_color']);
		if(!empty($checkpreference->id)){
			$recordBackground->id   =   $checkpreference->id;
			$userPrefUpdated  = $DB->update_record('user_preferences', $recordBackground);
			if($userPrefUpdated==true){
				$result = "success";
			}else{
				$result = "error";
			}
		}else{
			$userPrefUpdated  = $DB->insert_record('user_preferences', $recordBackground);
			if($userPrefUpdated==true){
				$result = "success";
			}else{
				$result = "error";
			}
		}

		if($result == "success"){
			echo json_encode($checkpreference);
		}else{
			echo json_encode($result);
		}
	}
	
	
	//return false;
}



