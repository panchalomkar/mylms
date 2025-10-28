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

global $CFG, $USER, $DB, $SESSION;
define('AJAX_SCRIPT', true);

require_once('../../../config.php');
require_once("{$CFG->dirroot}/local/social_wall/lib.php");
require_once("{$CFG->libdir}/moodlelib.php");
require_login();
require_sesskey();

$action = optional_param('mode','', PARAM_TEXT);
$msg_id = optional_param('msg_id', 0, PARAM_INT);
$last_postid = optional_param('lastId', 0, PARAM_INT);
switch($action){
    case 'delete_message':
        $res = delete_post($msg_id);  
        if(!empty($res)){
            if($res == 'access_denied'){   
                $response =array('status'=>'error','message'=>get_string('access_denied','local_social_wall'));
            }else{ 
                $response =array('status'=>'success','message'=>get_string('delete_post_success','local_social_wall'));
            }
        }
        break;
        
    case 'delete_comment':
        $comid = optional_param('comid', 0, PARAM_INT);
        $res = delete_comment($comid);
        if(!empty($res)){
            if($res == 'access_denied'){
                $response =array('status'=>'error','message'=>get_string('access_denied','local_social_wall'));
            }else{
                $response =array('status'=>'success','message'=>get_string('delete_cmnt_success','local_social_wall'));
            }
        }
        break;

    case 'rating':
        $rid = optional_param('id', '', PARAM_INT);
        $ratingtxt = optional_param('rate_val', '', PARAM_INT);

            //COUNTS LIKES & DISLIKES IF $rid EXISTS
            if($rid) {
                $checkrate= $DB->get_record_sql("SELECT * FROM {social_wall_ratings} WHERE msg_id='$rid' AND userid = $USER->id");
                

                if($checkrate->rating){
                    if($checkrate->rating==$ratingtxt){
                        $DB->delete_records("social_wall_ratings",array('msg_id'=>$rid,'userid'=>$USER->id));
                        social_wall_add_log('post','unliked','u',$rid);
                        
                        $response =array('data'=>'no-like');
                    } else {
                        $dataobject             = new stdClass();
                        $dataobject->userid     = $rid;
                        $dataobject->uid        = $USER->id;
                        $dataobject->rating     = $ratingtxt;
                        $dataobject->datemodified = time();

                        $DB->update_record("social_wall_ratings", $dataobject);
                        $response =array('data'=>$ratingtxt);
                    }
                } else {
                    $record             = new stdClass();
                    $record->msg_id     = $rid;
                    $record->userid     = $USER->id;
                    $record->ip         = get_user_ip();
                    $record->rating     = $ratingtxt;
                    $record->datecreated  = time();
                    $record->datemodified = time();

                    $DB->insert_record('social_wall_ratings', $record);
                    social_wall_add_log('post','liked','c',$rid);
                    // Added By Vinay B // Changed by Jayesh T
                    $user_data = core_user::get_user($USER->id);
                    $username = $user_data->firstname. ' '. $user_data->lastname;
                    $currentmessage = $DB->get_record('social_wall_messages', array('id'=>$rid));
                    $messageactionuser = $DB->get_record('user', array('id'=>$currentmessage->uid));

                    $companydetails = json_decode(get_user_company_name_link($messageactionuser));
                    $link = new moodle_url($companydetails->hostname . '/local/social_wall/', ['id' => $currentmessage->id, 't' => 'rating', 'u' => $USER->id, 'd' => $record->datecreated]);
                    $seepost = ' <a href="'.$link.'">'.get_string('seepost','local_social_wall').'</a>';
                    $message = get_string('likesapostemail', 'local_social_wall', ['currentusername' => $messageactionuser->firstname .' '. $messageactionuser->lastname , 'postuser' => $username, 'seepost' => $seepost]);
                    
                    send_system_notification($rid, $message);
                    // End
                    $response =array('data'=>'like');
                }
            }
        
        break;
        
    case 'count_likes':
        $countlikes =   $DB->count_records("social_wall_ratings",['msg_id'=>$msg_id]);
        $response   =   array('data'=>$countlikes);
        break;

    case 'add_comment':
        $cmntid  = optional_param('cmntid',0, PARAM_INT);
        $comment = optional_param('comment', '', PARAM_TEXT);
        $comment = str_replace("zx81plus","+",$comment);

        $res =insert_comment($cmntid,$msg_id, $comment);   
        if(!empty($res)){
            if($res == 'expired'){
                $response = array('status'=>'error','message'=>get_string('expired','local_social_wall'));
             }else{
                $response =array('status'=>'success','message'=>get_string('comment_added','local_social_wall'),'data'=>$res);
            }
        }
        break;

    case 'load_more_comment':
        $count    =  optional_param('count', 0, PARAM_INT);
        $html     =  load_comments($msg_id,'',$count);
        $response =  array('data'=>$html);
        break;

    case 'company_message':
        $companyid = optional_param('companyid', 0 ,PARAM_INT);
        $SESSION->wall_selected_company = $companyid;
        $html = load_company_post($companyid);
        $response =  array('data'=>$html);
        break;
    
    case 'timeline_messages':
        $companyid = optional_param('companyid', 0 ,PARAM_INT);
        $html = load_messages_timeline($companyid, $last_postid);
        $response =  array('data'=>$html);
        break;

    case 'back_from_timeline':
        $html = load_messages(true, $last_postid);
        $response =  array('data'=>$html);
        break;
    
    case 'show_seepost':
        $msgid = optional_param('msgid', 0 ,PARAM_INT);
        $html = load_messages_updates_seepost($msgid);
        $response =  array('data'=>$html);
        break;

    case 'readcomment':
        $type = optional_param('type', 0 ,PARAM_TEXT);
        $messageid = optional_param('messageid', 0 ,PARAM_INT);
        $userid = optional_param('userid', 0 ,PARAM_INT);
        $datecreated = optional_param('datecreated', 0 ,PARAM_INT);
        $html = updatereadstatus($type, $messageid, $userid, $datecreated);
        $response =  array('data'=>$html);
        break;

    case 'see_all_notifications':
        $html = see_all_notifications();
        $response =  array('data'=>$html);
        break;

    case 'see_notified_post':
        $type = optional_param('type', 0 ,PARAM_TEXT);
        $messageid = optional_param('messageid', 0 ,PARAM_INT);
        $userid = optional_param('userid', 0 ,PARAM_INT);
        $datecreated = optional_param('datecreated', 0 ,PARAM_INT);
        $html = see_notified_post($type, $messageid, $userid, $datecreated);
        $response =  array('data'=>$html);
        break;

   
    default:
    # code...
    break;
}

echo json_encode($response);
