<?php
/**
 * This file returns the html of newly added message.
 * 
 * @package	local_social_wall
 * @version	9.3
 * @author	Manisha M
 * @since 16-06-2019
 * @paradiso
*/

global $CFG;
require_once('../../../config.php');
require_once("{$CFG->dirroot}/local/social_wall/lib.php");

define('AJAX_SCRIPT', true);

require_login();

// Validate capabilities.
 
// if (!has_capability('local/social_wall:addmessage', context_system::instance())) {
//     $response   =   new moodle_exception(get_string('access_denied'));
//     $response   =   array('status'=>'error','message'=>get_string('access_denied','local_social_wall'));
    
// }else{
  $messageArray = optional_param('message', array(), PARAM_RAW);
  $msg_id = optional_param('msg_id', 0, PARAM_INT);
  
  if (!confirm_sesskey()) {
    // throw new moodle_exception('invalidsesskey', 'error');
    $response = array('status'=>'error','message'=>get_string('invalidsesskey','local_social_wall'));
  }

  if(!empty($messageArray)){  
      $res=  create_message($messageArray,$msg_id);
     
      if(!empty($res)){
        $dataresp = load_messages();
        if($res == 'updated'){
          $response = array('status'=>'success','message'=>get_string('post_upd','local_social_wall'),'data'=>$dataresp,'btn' =>'Share');
        }else if($res == 'expired'){
          $response = array('status'=>'error','message'=>get_string('expired','local_social_wall') ,'data'=>$dataresp,'btn' =>'Share');
       }else{
          $response = array('status'=>'success','message'=>get_string('post_added','local_social_wall') ,'data'=>$dataresp,'btn' =>'Share');
       }
      }else {
        $response = array('status'=>'error','message'=>get_string('post_req','local_social_wall'));
      }
    } 
// }

echo json_encode($response);
