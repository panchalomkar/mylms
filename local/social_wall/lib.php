<?php
/**
 * This file returns the response according to actions called in ajax.
 * @package   local_social_wall
 * @author     Manisha M
 * @paradiso
*/

defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->libdir}/moodlelib.php");
/**
 * Return list of all messages
 * @return object messagerecord
*/
function get_messages($last_postid, $to = 2){

    global $SESSION, $USER, $DB,$OUTPUT;
    $company = $DB->get_field('company_users', 'companyid', array('userid'=>$USER->id));

    if($SESSION->wall_selected_company && is_siteadmin()){
        $companyid = $SESSION->wall_selected_company;
    } else if(!empty($SESSION->currenteditingcompany)){
        $companyid = $SESSION->currenteditingcompany;
    } else {
        $companyid = $company;
    }
    
    $fetch_fields ="M.id,M.course_id, M.activity_id,  M.uid, M.message, M.datecreated, M.companyid, U.username, U.firstname, U.lastname";
    if ($to) {
        $limit = "LIMIT ".$to;
    }
    $where_post   = null;
    if ($last_postid) {
        $where_post   =  "AND M.id < $last_postid";
    }

    if(is_siteadmin($USER->id)){    

        //if user is a normal user, and it belongs to a tenant
        if($companyid){
            $where_by   = ( $companyid ? " M.companyid = $companyid AND " : null);
        }

       //if user is an admin, show all activities
       $messagerecord = $DB->get_records_sql("SELECT $fetch_fields FROM {social_wall_messages} M, {user} U WHERE $where_by M.uid = U.id AND M.deleted = 0 AND U.deleted = 0 $where_post ORDER BY M.id DESC $limit");
    }elseif( !$company ){

        //if user is a normal user, but it doesn't belong to any tenant
        $messagerecord = $DB->get_records_sql("SELECT $fetch_fields, U.id AS usrid FROM {social_wall_messages} M, {user} U  
                                        LEFT JOIN {company_users} C ON C.userid = U.id 
                                        WHERE M.uid = U.id $where_post AND M.deleted = 0 AND U.deleted = 0 AND (M.companyid is null or M.companyid = 0) AND U.id NOT IN
                                        (SELECT userid   FROM {company_users}   WHERE userid = U.id)
                                        ORDER BY M.id DESC $limit");
    }else {

        //if user is a normal user, and it belongs to a tenant
        $where_by 	= ( $company ? " M.companyid = $company  AND " : null);

        $messagerecord = $DB->get_records_sql("SELECT $fetch_fields FROM {social_wall_messages} M, {user} U WHERE $where_by M.uid = U.id AND M.deleted = 0 $where_post AND U.deleted = 0 ORDER BY M.id DESC $limit");
    }
//print_object($messagerecord);
    return $messagerecord;
}

// Render like button html
function render_likes_dislikes($msg_id,$uid){
    global $DB;
    $output ='';

    //count the number of likes & dislikes
    $likes = $DB->count_records_sql("SELECT COUNT(id) FROM {social_wall_ratings} WHERE msg_id='$msg_id' AND rating='1'");
    $dislikes = $DB->count_records_sql("SELECT COUNT(id) FROM {social_wall_ratings} WHERE msg_id='$msg_id' AND rating='2'");
    
    //Check if user alreasy rated content
    $r = $DB->get_record_sql("SELECT id,rating FROM {social_wall_ratings} WHERE msg_id='$msg_id' AND userid='$uid'"); 

    $activelike_cls = '';
    //If so, then add active class
    if($r->rating == 1){
        $activelike_cls = "active";
    }

    $output .= html_writer::start_div("likes_opt $activelike_cls",['id'=>"rating$msg_id"]);
        $output  .= html_writer::tag("span","",['class'=>"","style"=>"cursor:pointer",'id'=>"$msg_id","data-value"=>"1"]);
        $output .= html_writer::start_div();
            $output .= html_writer::start_tag('span');
                $like = get_string('like', 'local_social_wall'); 
                $output .= html_writer::tag('a',"$likes $like",['class'=>"countlike$msg_id getlikedata",'id'=>$msg_id,'href'=>'JavaScript:void(0)','data-toggle'=>"modal", 'data-target'=>"#myModal"]);
            $output .= html_writer::end_tag('span');
        $output .= html_writer::end_div();
    $output .= html_writer::end_div();

    return $output;
}

function get_comment_count($msg_id,$uid){
    global $DB;
     $totalCnt = $DB->count_records_sql("SELECT COUNT(id) FROM {social_wall_comments} WHERE msg_id='$msg_id'");
     $commentCount = 0;
     if($totalCnt > 0){
         $commentCount = $totalCnt;
     }
     return $commentCount;
}

// Render like button html
function render_likes_dislikes_btn($msg_id,$uid){
    global $DB, $USER;
    $output ='';

    //count the number of likes & dislikes
    $likes = $DB->count_records_sql("SELECT COUNT(id) FROM {social_wall_ratings} WHERE msg_id='$msg_id' AND rating='1'");
    $dislikes = $DB->count_records_sql("SELECT COUNT(id) FROM {social_wall_ratings} WHERE msg_id='$msg_id' AND rating='2'");
    
    //Check if user alreasy rated content
    $r = $DB->get_record_sql("SELECT id,rating FROM {social_wall_ratings} WHERE msg_id='$msg_id' AND userid='$USER->id'"); 

    $activelike_cls = '';
    //If so, then add active class
    if($r->rating == 1){
        $activelike_cls = "active";
    }

    $output .= html_writer::start_div("social-like social_like likes-opt $activelike_cls",['id'=>"ratings$msg_id",'title'=> get_string('like', 'local_social_wall'),"data-value"=>"1"]);
        $like = get_string('like', 'local_social_wall'); 
        $output  .= html_writer::tag("span","",['class'=>"fa fa-thumbs-o-up icon-like","style"=>"cursor:pointer",'id'=>"$msg_id","data-value"=>"1"]);
        $output .= html_writer::start_tag("span",["class"=>"text-like"]);
            $output .= " $like";
        $output .= html_writer::end_tag('span');
    $output .= html_writer::end_div();

    return $output;
}

function get_user_picture_s($uid){
    global $DB,$OUTPUT;

    $user = $DB->get_record_sql("SELECT * FROM {user} WHERE id = $uid");
    $picture = $OUTPUT->user_picture($user, array('size'=>40));
    $picture = str_replace('<a', '<a target="_blank"', $picture);
    
    return $picture;
}

// Render all messages html
function load_messages($ajax = false, $last_postid = ''){
    global $SESSION, $USER, $DB,$OUTPUT;
    $to = get_config('local_social_wall', 'postsload');
    $messagerecord =  get_messages($last_postid, $to);
    if(empty($messagerecord)){
        if($ajax){
            return json_encode(['html' => '']);
        }
        return '';
    }else{
        foreach($messagerecord AS $data){
            $msg_id     =   $data->id;
            $messageArr =   json_decode($data->message);
            $message    =   $messageArr->text;
            $time       =   $data->datecreated;
            $username   =   $data->firstname.' '.$data->lastname;
            $uid        =   $data->uid;
            $numrows    =   2;

            $output .= html_writer::start_div('stbody _cardone col-xs-12 col-sm-12 col-md-12 col-lg-12', array(id => "stbody$msg_id"));
               // $output .= html_writer::start_div('stbody-intern col-xs-12 col-sm-12 col-md-12 col-lg-12');
                $output .= html_writer::start_div( 'stimg cardone-header');
                    $output .= html_writer::start_div( 'cardone-header-image d-flex justify-content-between align-items-center');
                        
                        $output .= html_writer::start_div( 'd-flex justify-content-between align-items-center');
                            $output .= html_writer::start_div( 'mr-1');
                                //$output .= get_user_picture_s($uid); previous changed to add id for social popover
                                $output .= get_popover_picture($uid,$msg_id);
                            $output .= html_writer::end_div();
                            
                            $output .= html_writer::start_div( 'ml-1');
                                $username = html_writer::tag('a',"$username", array("class" => 'h5 m-0'));
                                $output .=html_writer::tag('h3',$username,array('class'=>'h3 text-muted usr_heading'));
                            $output .= html_writer::end_div();
                        $output .= html_writer::end_div();
                        
                        if(($uid==$USER->id) || is_siteadmin($USER->id)) {
                            $output .= html_writer::start_div( 'dropdown');
                                $output .= html_writer::tag( 'button', "<i class='fa fa-ellipsis-h' aria-hidden='true'></i>" , array('class' => 'btn btn-link dropdown-toggle','type'=>'button', 'data-toggle'=>'dropdown', 'aria-haspopup' => "true",'aria-expanded' => "false"));
                           
                                $output .= html_writer::start_div( 'dropdown-menu dropdown-menu-right');
                                   $output .= html_writer::tag('a',"<i class='fa fa-pencil-square-o' aria-hidden='true'></i> Edit", array('class' => 'dropdown-item stedit','id'=>$msg_id, 'title'=>'Edit'));
                                   $output .= html_writer::tag('a',"<i class='fa fa-trash' aria-hidden='true'></i> Delete", array('class' => 'dropdown-item stdelete','id'=>$msg_id, 'title'=>'Delete'));
                                $output .= html_writer::end_div();
                            $output .= html_writer::end_div();
                        }
                         
                    $output .= html_writer::end_div();

                    $output .= html_writer::start_div('sttext ');
                        $output .= html_writer::tag('input',"",array('type'=>'hidden','id'=>'upd_comid'));
                            $output .= html_writer::tag('div',$message,['class'=>'msg_content']);
                        $output .= html_writer::start_div('row row-margin'); 
                            $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 pl-1');
                                
                                $output .= html_writer::start_div('sttime');
                                    $output .= html_writer::tag('span', get_string('posted', 'local_social_wall'),['class'=>'']).time_stamp($time);
                                $output .= html_writer::end_div();
                            $output .= html_writer::end_div();
                            
                            $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right pr-1');
                            // render like button 
                                $output .= render_likes_dislikes($msg_id,$uid);
                                $commentCount = get_comment_count($msg_id,$uid);
                                $comment = get_string('comment', 'local_social_wall'); 
                                $output .= html_writer::tag('a',"$commentCount $comment <i class='fa fa-angle-down'></i>",['class'=>'comment-icon commentopen','href'=>'JavaScript:void(0)','id'=>$msg_id]);
                            $output .= html_writer::end_div();
                        $output .= html_writer::end_div();
                        $output .= html_writer::start_div('',['id'=>'stexpandbox']);
                            $output .= html_writer::tag('div', "",['id'=>"stexpand$msg_id"]);
                        $output .= html_writer::end_div();
                    $output .= html_writer::end_div();
                $output .= html_writer::end_div();
                        
                $output .= html_writer::start_div('row action-box'); 
                    $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center cardone-footer-buttons');
                        $output .= render_likes_dislikes_btn($msg_id,$uid);
                    $output .= html_writer::end_div();
                    
                    $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center cardone-footer-buttons');
                        $commentStr = get_string("comment",'local_social_wall');
                        $output .= html_writer::tag('a',"<i class='fa fa-comment-o'></i> $commentStr",['class'=>'social-like  commentopen','href'=>'JavaScript:void(0)','id'=>$msg_id,'title'=>$commentStr]);
                    $output .= html_writer::end_div();
                $output .= html_writer::end_div();
                
                
                $output .= html_writer::start_div('',['class'=>'commentcontainer col-xs-12 col-sm-12 col-md-12 col-lg-12 py-3','style'=>"display:none",'id'=>"commentload$msg_id"]);
                    $output .= load_comments($msg_id,$startrow,$numrows);
                $output .= html_writer::end_div();
            $output .= html_writer::end_div();
        } 
    } 
    if($ajax){
        return json_encode(['html' => $output]);
    }
    return  $output;
}

/**
 * Create new message
 * @param (data) array of content
 * @return int msgid
*/
function create_message($data,$msg_id){

    global $USER,$DB, $SESSION;
    $context = context_system::instance();

    /*if(!empty($USER->company->id) && !is_siteadmin($USER->id)){
        $company = $USER->company->id;
    } else if($SESSION->wall_selected_company){
        $company = $SESSION->wall_selected_company;
    } else if(!empty($SESSION->currenteditingcompany)){
        $company = $SESSION->currenteditingcompany;
    }*/

    $companyid = $DB->get_field('company_users', 'companyid', array('userid'=>$USER->id));

   if($SESSION->wall_selected_company && is_siteadmin()){
       $company = $SESSION->wall_selected_company;
   } else if(!empty($SESSION->currenteditingcompany)){
       $company = $SESSION->currenteditingcompany;
   } else {
       $company = $companyid;
   }


    if($company){
        $edit_expire_min = 'edit_expire_min_'.$company;
    } else {
        $edit_expire_min = 'edit_expire_min';
    }
    // if(!has_capability('local/social_wall:addmessage', context_system::instance())){
    //    throw new moodle_exception(get_string('access_denied'));
    // }
    if(!empty($data['text'])){
        if(!empty($msg_id)){
            $record= $DB->get_record('social_wall_messages', ['id'=>$msg_id]);
            $minutes_diff =  round((time() - $record->datecreated) / 60);
            $expirymins = get_config('local_social_wall', $edit_expire_min);
            if($expirymins == "" || empty($expirymins)){
                $expirymins = 30;
            }
            if($minutes_diff >= $expirymins){
                return 'expired';
            }else{
                $record                 =   new stdClass();
                $record->id             =   $msg_id;
                $message                =   str_replace("zx81plus","+",$data);
                $message                =   str_replace("zorilla","&",$data);
                $record->message        =   json_encode($message);
                $record->datemodified   =   time();
                if($company){
                    $record->companyid  =   $company;
                }
                $DB->update_record('social_wall_messages', $record);
                social_wall_add_log('post','updated','u',$msg_id); 
                return  'updated';
            }
        }else{
            $itemid = file_get_submitted_draft_itemid('message');
            $messageDrafttext = file_save_draft_area_files($itemid, $context->id, 'local_social_wall', 'message', $itemid, array('subdirs'=>true), $data);
            $messagetext = file_rewrite_pluginfile_urls($messageDrafttext, 'pluginfile.php',$context->id, 'local_social_wall', 'message', $itemid);

            $record                 =   new stdClass();
            $message                =   str_replace("zx81plus","+",$messagetext);
            $message                =   str_replace("zorilla","&",$messagetext);
            $record->message        =   json_encode($message);
            $record->uid            =   $USER->id;
            $record->ip             =   get_user_ip();
            if($company){
                $record->companyid  =   $company;
            }
            $record->datecreated    =   time();
            $record->datemodified   =   time();
    
            $msgid  = $DB->insert_record('social_wall_messages', $record);
            social_wall_add_log('post','created','c',$msgid); 
            return  $msgid;
        }
    }
}

function local_social_wall_pluginfile($course, $cm, context $context, $filearea, $args, $forcedownload) {
    global $USER, $DB, $CFG;

    if ($context->contextlevel == CONTEXT_SYSTEM) {
        $itemid = (int)array_shift($args);
        $relativepath = implode('/', $args);
        $fullpath = "/{$context->id}/local_social_wall/$filearea/$itemid/$relativepath";
        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
        // Download MUST be forced - security!
        send_stored_file($file, 0, 0, true);// Check if we want to retrieve the stamps.
    }
}

/**
 * Delete message
 * @param (msg_id) int id of post to delete
*/
function delete_post($msg_id){
    global $USER,$DB;
    
    $uid= $DB->get_field_sql("SELECT uid FROM {social_wall_messages} WHERE id = $msg_id");
    if(!empty($msg_id)){
        if($uid = $USER->id || has_capability('local/social_wall:deletemessage', context_system::instance())){
            $obj = new stdClass();
            $obj->deleted = 1;
            $obj->id = $msg_id;
            $DB->update_record('social_wall_messages', $obj);
            $DB->delete_records("social_wall_comments",array('msg_id'=>$msg_id));
            $DB->delete_records("social_wall_ratings",array('msg_id'=>$msg_id));
            social_wall_add_log('post','deleted','d',$msg_id); 
            return $msg_id;
        }else {
            return 'access_denied';
        }
    }
}

/**
 * Delete comment of update
 * @param (comid) int comment id to delete
*/
function delete_comment($comid){
    global $USER,$DB;

    $comments= $DB->get_records_sql("SELECT uid,msg_id FROM {social_wall_comments} WHERE id = $comid");
    $uid= $comments->uid;

    if(!empty($comid)){
        if($uid = $USER->id || has_capability('local/social_wall:deletemessage', context_system::instance())){
            $DB->delete_records("social_wall_comments",array('id'=>$comid));
            social_wall_add_log('comment','deleted','d',$comments->msg_id); 
            return $comid;
        }else {
            return 'access_denied';
        }
    } 
}

/**
 * Insert  new comment
 * @param (msg_id) msg_id to add comments
*/
function insert_comment($cmntid,$msgid,$comment){
    global $USER,$DB,$SESSION;
    if(!empty($USER->company->id) && !is_siteadmin($USER->id)){
        $company = $USER->company->id;
    } else if($SESSION->wall_selected_company){
        $company = $SESSION->wall_selected_company;
    } else if(!empty($SESSION->currenteditingcompany)){
        $company = $SESSION->currenteditingcompany;
    }
    if($company){
        $edit_expire_min = 'edit_expire_min_'.$company;
    } else {
        $edit_expire_min = 'edit_expire_min';
    }
    $where ="" ;
    if(!is_siteadmin()){
        $where = " uid='$USER->id' and ";
    }
    $checkcomment= $DB->get_record_sql("SELECT  id,comment,datecreated FROM {social_wall_comments} WHERE $where  msg_id='$msgid'  order by id desc limit 1 ");
    
    if (!empty($comment)) {
          
        if(!empty($cmntid)){
           
            $minutes_diff =  round(abs(time() - $checkcomment->datecreated) % 60);
            $expirymins = get_config('local_social_wall', $edit_expire_min);
            if($minutes_diff >= $expirymins && !is_siteadmin()){
                return 'expired';
            }else{
                $record                 =   new stdClass();
                $record->id             =   $cmntid;
                $record->comment        =   $comment;
                $record->datemodified   =   time();
                $DB->update_record('social_wall_comments', $record);
                social_wall_add_log('comment','updated','u',$msgid); 
            }
        }else{
            $record                 =   new stdClass();
            $record->msg_id         =   $msgid;
            $record->uid            =   $USER->id;
            $record->ip             =   get_user_ip();
            $record->datecreated    =   time();
            $record->datemodified   =   time();
            $record->comment        =   $comment;

            $commentid  =$DB->insert_record('social_wall_comments', $record);

            // Added By Vinay B // Changed by Jayesh T
            $user_data = core_user::get_user($USER->id);
            $username = $user_data->firstname. ' '. $user_data->lastname;
            $comment = $DB->get_record('social_wall_comments', array('id'=>$commentid));
            $currentmessage = $DB->get_record('social_wall_messages', array('id'=>$msgid));
            $messageactionuser = $DB->get_record('user', array('id'=>$currentmessage->uid));

            $companydetails = json_decode(get_user_company_name_link($messageactionuser));
            $link = new moodle_url($companydetails->hostname . '/local/social_wall/', ['id' => $msgid, 't' => 'comment', 'u' => $USER->id, 'd' => $comment->datecreated]);
            $seepost = ' <a href="'.$link.'">'.get_string('seepost','local_social_wall').'</a>';
            $message = get_string('commentedonpostemail', 'local_social_wall', ['currentusername' => $messageactionuser->firstname .' '. $messageactionuser->lastname , 'postuser' => $username, 'seepost' => $seepost]);            

            send_system_notification($msgid, $message);
            // End

            social_wall_add_log('comment','created','c',$msgid); 
        }
        $count = $DB->count_records_sql("SELECT COUNT(id) FROM {social_wall_comments} WHERE msg_id='$msgid'");
        if($count > 2){
            $loadcommenthtml = load_comments($msgid,'0',$count);
        }
        $loadcommenthtml = load_comments($msgid,'','');
        $loadcommenthtml = load_messages();
        return $loadcommenthtml;
      
    } else {
        return false;
    }
}

/**
 * Return a list of comments according to msg_id
 * @param (msg_id) msg_id to get comments
 * @return object commentrecord
*/
function get_comments($msg_id,$startrow = 0,$numrows){
    global $DB;
    $commentrecord = $DB->get_records_sql("SELECT C.id,C.course_id, C.uid, C.comment, C.datecreated, U.username, U.firstname, U.lastname FROM {social_wall_comments} C, {user} U WHERE C.uid=U.id and C.msg_id='$msg_id' order by C.id desc LIMIT $startrow, $numrows");
    if(!empty($commentrecord)){
        return $commentrecord;
    }else{
        return '';
    } 
}

/**
 * Return count of comments according to their msgid
 * @param (msg_id) msg_id to get comments count
 * @return int count
*/
function count_comments($msg_id){
    global $DB;
    $countcomments = $DB->count_records('social_wall_comments', ['msg_id'=>$msg_id]);
    return $countcomments;
}

/**
 * Return a list of this comments html according to their msgid
 * @param (msg_id) msg_id to get comments
 * @return html of comments
*/
function load_comments($msg_id,$startrow,$numrows){

    global $DB,$OUTPUT,$USER; 
    if(empty($startrow)){
        $startrow = 0;
    }
    if(empty($numrows)){
        $numrows = 2;
    }
    $commentsArray = get_comments($msg_id,$startrow,$numrows); 
    $output =""; 
    
    foreach($commentsArray as $cdata) {
        $comment = $cdata->comment;

        $comment = str_replace('  ', ' &nbsp;', $comment);
        $comment = str_replace('	','&nbsp;&nbsp;&nbsp;&nbsp;',$comment);
        $comment = str_replace("\t",'&nbsp;&nbsp;&nbsp;',$comment);
        $comment = str_replace("zorilla","&",$comment);
        
        $comment = str_replace('zx81plus', '+', $comment);
        $comment = str_replace('&gt;', '>', $comment);
        $comment = str_replace('&lt;', '<', $comment);
        $comment = str_replace('&quot;', '"', $comment);
        $comment = str_replace('&Acirc;', '&nbsp;', $comment);
        
        $comment = str_replace('pamzpam', '&amp;', $comment);
        $comment = str_replace('&amp;amp;', '&amp;', $comment); 
        
        $time       = $cdata->datecreated;
        $username   = $cdata->firstname.' '.$cdata->lastname;
        $uid        = $cdata->uid;
        $com_id     = $cdata->id;
   
        $output .= html_writer::start_div('stcommentbody col-xs-12 col-sm-12 col-md-12 col-lg-12', array(id => "stcommentbody$com_id"));
            $output .= html_writer::start_div('stcommentimg');
                $picture = get_user_picture_s($uid);
                $output .= html_writer::tag('div', $picture, array('class' => 'stimg'));
            $output .= html_writer::end_div();

            $output .= html_writer::start_div('stcommenttext col-11');
                 if(($uid==$USER->id) || is_siteadmin($USER->id)) {
                    $output .= html_writer::tag('a',"<i class='fa fa-trash' aria-hidden='true'></i>",array('class'=>'stcommentaction stcommentdelete','id'=>"$com_id",'title'=>"Delete Comment"));
                    $output .= html_writer::tag('a',"<i class='fa fa-pencil-square-o' aria-hidden='true'></i>",array('class'=>'stcommentaction stcommentedit','id'=>"$com_id",'title'=>"Edit Comment","data-id"=>$msg_id));
                }
            // $output .= html_writer::tag('div', $deletebtn);
            
            $usrname = html_writer::tag('a',"$username", array('target'=>"_blank"));
            $output .=html_writer::tag('h3',$usrname,array('class'=>'cmntbox_usrname text-muted'));
            $output .=html_writer::start_div('msg_content mt-2 ml-1');
                 $output .=html_writer::tag('p',$comment,array('class' => ''));
             $output .= html_writer::end_div();
            $output .= html_writer::start_div('stcommenttime ml-1');
                $output .=html_writer::tag('span','',array('class'=>'wall_stcommenttime_span fa fa-clock-o')). time_stamp($time);
            $output .= html_writer::end_div();
            
            $output .= html_writer::end_div();
        $output .= html_writer::end_div();
  
    }
    // render View More/ View Less button
    $output .= html_writer::start_div('row');
        $output .= html_writer::start_div('col-1');
        $output .= html_writer::end_div();
        $output .= html_writer::start_div('text-left px-2 col-8 p-3');
        $countcomments = count_comments($msg_id);
        if($countcomments > 2){
            $viewmore = html_writer::tag('a', get_string("view_more",'local_social_wall'), array('class' => "loadComments text-primary",'data-count'=>$countcomments,'href'=>"JavaScript:void(0)",'data-id'=>$msg_id));
            $output .=html_writer::tag('h6', $viewmore);
            $viewless = html_writer::tag('a', get_string("view_less",'local_social_wall'), array('class' => "hideComments hidden text-primary",'data-count'=>'2','href'=>"JavaScript:void(0)",'data-id'=>$msg_id));
            $output .=html_writer::tag('h6', $viewless);
        }
        $output .= html_writer::end_div();
    $output .= html_writer::end_div();
    // render comments div

    $output .= html_writer::start_div('',['class'=>'commentupdate d-flex position-relative bg-light p-3','id'=>"commentbox$msg_id"]);
        $picture = get_user_picture_s($USER->id);
        $output .= html_writer::tag('div', $picture, array('class' => 'stcommentimg'));

        $output .= html_writer::start_div('',['class'=>'stcommenttext commentwidth w-100']);
            $output .= html_writer::tag('textarea',"",['name'=>'comment','class'=>'comment comment-txtarea',
                                        'placeholder'=>get_string('writecomment', 'local_social_wall'),'maxlength'=>'15000' , 'id'=>"ctextarea$msg_id",'data-class'=>'comment']);
        $output .= html_writer::end_div();
        $output .= html_writer::start_div('',['class'=>'position-absolute _send-btn','id'=>"container_submit"]);
            $output .= html_writer::tag('a',' <i class="fa fa-send"></i> ',['href'=>'#','data-id'=>"$msg_id",'class'=>'comment_button']);
        $output .= html_writer::end_div();
    $output .= html_writer::end_div();
    
    return $output;
} 

function time_stamp($session_time){ 
    
    $time_difference    =   time() - $session_time ; 
    $seconds            =   $time_difference ; 
    $minutes            =   round($time_difference / 60 );
    $hours              =   round($time_difference / 3600 ); 
    $days               =   round($time_difference / 86400 ); 
    $weeks              =   round($time_difference / 604800 ); 
    $months             =   round($time_difference / 2419200 ); 
    $years              =   round($time_difference / 29030400 ); 

    if($seconds <= 60) {
        $time= get_string('sec_ago', 'local_social_wall', $seconds); 
    } else if($minutes <=60) { 
        if($minutes==1) {
            $time= get_string('one_min', 'local_social_wall'); 
        } else {
            $time= get_string('min_ago', 'local_social_wall', $minutes); 
        }
   }else if($hours <=24) { 
        if($hours==1) {
            $time=get_string('one_hour', 'local_social_wall'); 
        } else {
            $time= get_string('hour_ago', 'local_social_wall', $hours); 
         }
    }else if($days <=7) { 
        if($days==1) {
            $time=get_string('one_day', 'local_social_wall'); 
        } else  {
            $time=get_string('days_ago', 'local_social_wall', $days); 
        }
    } else if($weeks <=4) {
        if($weeks==1) {
            $time=get_string('one_week', 'local_social_wall'); 
        } else {
            $time=get_string('week_ago', 'local_social_wall', $weeks); 
        }
    }else if($months <=12) {
        if($months==1) {
            $time=get_string('one_month', 'local_social_wall'); 
        } else {
            $time=get_string('month_ago', 'local_social_wall', $months); 
        }
    } else {
        if($years==1) {
            $time=get_string('one_year', 'local_social_wall'); 
        } else  {
            $time=get_string('year_ago', 'local_social_wall', $years); 
        }
    }
    return ' '.$time;
} 
 
// Method to get the client IP address
function get_user_ip() {

    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function social_wall_company_list(){
    global $DB, $SESSION;
    $sql = "SELECT id,name from {company} WHERE suspended = 0";
    $tenants = $DB->get_records_sql($sql);
    if(!empty($SESSION->currenteditingcompany)){
        $companyid = $SESSION->currenteditingcompany;
    } else {
        $companyid = '';
    }
    if($SESSION->wall_selected_company){
        //$companyid = $SESSION->wall_selected_company;
        $companyid = rap_is_company_user();
    }
    
    $companies = array();
    $html = html_writer::start_div("col-sm-12 col-md-7 mb-2 pr-0 d-flex");
        $html .= html_writer::label('Select Branch', 'company-list', false, array('class' => 'pt-2', 'style' => ''));
        //$html .= html_writer::start_tag("select", array("class" => "form-control float-right ml-2","id"=> "company-list"));
            //$html .= "<option value=0>".get_string('showall', 'local_social_wall')."</option>";
            $companies[] = get_string('showall', 'local_social_wall');
            foreach ($tenants as $company ) {
                //$sel = ($company->id == $companyid) ? "selected" : "";
                $companies[$company->id] = $company->name;
                //$html .= "<option value=$company->id $sel>".$company->name."</option>";
            }
        //$html .= html_writer::end_tag("select");
        $html .= html_writer::select($companies, 'social', $companyid,'');
    $html .= html_writer::end_tag("div");
    return $html;
}

function load_company_post($company){
    global $SESSION, $USER, $DB,$OUTPUT;
   
    $fetch_fields ="M.id,M.course_id, M.activity_id,  M.uid, M.message, M.datecreated, M.companyid, U.username, U.firstname, U.lastname";
    //if user is a normal user, and it belongs to a tenant
    $where_by 	= ( $company ? " M.companyid = $company  AND " : '');
    if($company == 0){
        $socialbgimg = 'socialbgimg';
    } else {
        $socialbgimg = 'socialbgimg_'.$company;
    }

    $messagerecord = $DB->get_records_sql("SELECT $fetch_fields FROM {social_wall_messages} M, {user} U WHERE $where_by M.uid = U.id AND M.deleted = 0 AND U.deleted = 0 ORDER BY M.id DESC");
    $theme = theme_config::load('paradiso');
    $pagebg = $theme->setting_file_url($socialbgimg, $socialbgimg);
    if(!$pagebg){
        $pagebg = $theme->setting_file_url('loginimage', 'loginimage');
	}
    if(empty($messagerecord)){
        return json_encode(['html' => '', 'img' => $pagebg]);
    }else{
        foreach($messagerecord AS $data){
            $msg_id     =   $data->id;
            $messageArr =   json_decode($data->message);
            $message    =   $messageArr->text;
            $time       =   $data->datecreated;
            $username   =   $data->firstname.' '.$data->lastname;
            $uid        =   $data->uid;
            $numrows    =   2;

            $output .= html_writer::start_div('stbody _cardone col-xs-12 col-sm-12 col-md-12 col-lg-12', array(id => "stbody$msg_id"));
               // $output .= html_writer::start_div('stbody-intern col-xs-12 col-sm-12 col-md-12 col-lg-12');
                $output .= html_writer::start_div( 'stimg cardone-header');
                    $output .= html_writer::start_div( 'cardone-header-image d-flex justify-content-between align-items-center');
                        
                        $output .= html_writer::start_div( 'd-flex justify-content-between align-items-center');
                            $output .= html_writer::start_div( 'mr-1');
                                $output .= get_user_picture_s($uid);
                            $output .= html_writer::end_div();
                            
                            $output .= html_writer::start_div( 'ml-1');
                                $username = html_writer::tag('a',"$username", array("class" => 'h5 m-0'));
                                $output .=html_writer::tag('h3',$username,array('class'=>'h3 text-muted usr_heading'));
                            $output .= html_writer::end_div();
                        $output .= html_writer::end_div();
                        
                        if(($uid==$USER->id) || is_siteadmin($USER->id)) {
                            $output .= html_writer::start_div( 'dropdown');
                                $output .= html_writer::tag( 'button', "<i class='fa fa-ellipsis-h' aria-hidden='true'></i>" , array('class' => 'btn btn-link dropdown-toggle','type'=>'button', 'data-toggle'=>'dropdown', 'aria-haspopup' => "true",'aria-expanded' => "false"));
                           
                                $output .= html_writer::start_div( 'dropdown-menu dropdown-menu-right');
                                   $output .= html_writer::tag('a',"<i class='fa fa-pencil-square-o' aria-hidden='true'></i> Edit", array('class' => 'dropdown-item stedit','id'=>$msg_id, 'title'=>'Edit'));
                                   $output .= html_writer::tag('a',"<i class='fa fa-trash' aria-hidden='true'></i> Delete", array('class' => 'dropdown-item stdelete','id'=>$msg_id, 'title'=>'Delete'));
                                $output .= html_writer::end_div();
                            $output .= html_writer::end_div();
                        }
                         
                    $output .= html_writer::end_div();

                    $output .= html_writer::start_div('sttext ');
                        $output .= html_writer::tag('input',"",array('type'=>'hidden','id'=>'upd_comid'));
                            $output .= html_writer::tag('div',$message,['class'=>'msg_content']);
                        $output .= html_writer::start_div('row row-margin'); 
                            $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 pl-0');
                                
                                $output .= html_writer::start_div('sttime');
                                    $output .= html_writer::tag('span', get_string('posted','local_social_wall'),['class'=>'']).time_stamp($time);
                                $output .= html_writer::end_div();
                            $output .= html_writer::end_div();
                            
                            $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right pr-0');
                            // render like button 
                                $output .= render_likes_dislikes($msg_id,$uid);
                                $commentCount = get_comment_count($msg_id,$uid);
                                $comment = get_string('comment', 'local_social_wall'); 
                                $output .= html_writer::tag('a',"$commentCount $comment <i class='fa fa-angle-down'></i>",['class'=>'comment-icon commentopen','href'=>'JavaScript:void(0)','id'=>$msg_id]);
                            $output .= html_writer::end_div();
                        $output .= html_writer::end_div();
                        $output .= html_writer::start_div('',['id'=>'stexpandbox']);
                            $output .= html_writer::tag('div', "",['id'=>"stexpand$msg_id"]);
                        $output .= html_writer::end_div();
                    $output .= html_writer::end_div();
                $output .= html_writer::end_div();
                    
                $output .= html_writer::start_div('row action-box'); 
                    $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center cardone-footer-buttons');
                        // $output .= html_writer::tag('span', "<i class='fa fa-thumbs-o-up social_like'></i> Likes",['class'=>'social-like']);
                        $output .= render_likes_dislikes_btn($msg_id,$uid);
                    $output .= html_writer::end_div();
                    
                    $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center cardone-footer-buttons');
                        $commentStr = get_string("comment",'local_social_wall');
                        $output .= html_writer::tag('a',"<i class='fa fa-comment-o'></i> $commentStr",['class'=>'social-like  commentopen','href'=>'JavaScript:void(0)','id'=>$msg_id,'title'=>$commentStr]);
                    $output .= html_writer::end_div();
                $output .= html_writer::end_div();
                
                $output .= html_writer::start_div('',['class'=>'commentcontainer col-xs-12 col-sm-12 col-md-12 col-lg-12 py-3','style'=>"display:none",'id'=>"commentload$msg_id"]);
                    $output .= load_comments($msg_id,$startrow,$numrows);
                $output .= html_writer::end_div();

            $output .= html_writer::end_div();
        } 
    } 
	//    return  $output;
	return json_encode(['html' => $output, 'img' => $pagebg]);
}

// Method to add logs in social_wall_log table after every action performed by user
function social_wall_add_log($type,$action,$crud,$postid){
    global $DB,$USER;

    $context    = context_system::instance();
    $data       = new stdClass();
    
    $data->type         =   $type;
    $data->action       =   $action;
    $data->crud         =   $crud;
    $data->courseid     =   0;
    $data->postid       =   $postid;
    $data->contextid    =   $context->id;
    $data->contextlevel =   $context->contextlevel;
    $data->userid       =   $USER->id;
    $data->datecreated  =   time();
    $data->ip           =   get_user_ip();
    
    $DB->insert_record('social_wall_log', $data);
}

 /**
     * Count No.of Post 
     * @global type $DB
     * @global type $SESSION
     * @return type
     */
function social_wall_no_of_post(){
    global $SESSION, $USER, $DB;
    $id=$USER->id;
    $countpost = $DB->count_records('social_wall_messages', ['uid'=>$id,'deleted'=>0]);
    return $countpost;
}

 /**
     * Updates Like & Comments 
     * @global type $DB
     * @global type $SESSION
     * @return type
     */

function updates_like_comment() {
    global $DB, $USER;

    $unionsql = "SELECT
                    swc.datecreated,
                    swc.datemodified,
                    swc.msg_id,
                    swc.uid as userid,
                    swc.viewed,
                    u.firstname as firstname,
                    u.lastname as lastname,
                    'comment' as type
                FROM 
                    mdl_social_wall_comments as swc
                    INNER JOIN mdl_user as u on u.id = swc.uid
                    INNER JOIN mdl_social_wall_messages as swmc ON swmc.id = swc.msg_id
                WHERE
                    swmc.uid = $USER->id
                    AND swc.uid <> $USER->id
                UNION ALL
                SELECT 
                    swr.datecreated,
                    swr.datemodified,
                    swr.msg_id,
                    swr.userid as userid,
                    swr.viewed,
                    u.firstname as firstname,
                    u.lastname as lastname,
                    'rating' as type
                FROM mdl_social_wall_ratings as swr
                    INNER JOIN mdl_user as u on u.id = swr.userid
                    INNER JOIN mdl_social_wall_messages as swmr ON swmr.id = swr.msg_id
                WHERE
                    rating = 1
                    AND swmr.uid = $USER->id
                    AND userid <> $USER->id
                ORDER BY
                    datemodified DESC
                LIMIT 20";
    
    $notifications = $DB->get_records_sql($unionsql);

    $output = html_writer::start_tag('div', ['class' => 'notification-container-box']);
        $output .= '<ul id="notifications">';
        foreach($notifications as $notification){
            $viewdclass = '';
            if(!$notification->viewed){
                $viewdclass = 'class="viewed-notification" ';
            }
            $link = new moodle_url('/local/social_wall/', ['id' => $notification->msg_id, 't' => $notification->type, 'u' => $notification->userid, 'd' => $notification->datecreated]);
            $output .= '<a href="'.$link.'">';
            $output .= '<li '.$viewdclass.' id="'.$notification->type.'-'.$notification->msg_id.'-'.$notification->userid.'-'.$notification->datecreated.'">';
            $userpic = strip_tags(get_user_picture_s($notification->userid),'<img>');
            
            if($notification->type == 'comment'){
                $output .= $userpic;
                $output .= '<span>';
                $output .= get_string('commentedonpost', 'local_social_wall', $notification->firstname.' '.$notification->lastname);
                $output .= '</span>';
            }else if($notification->type == 'rating'){
                $output .= $userpic;
                $output .= '<span>';
                $output .= get_string('likesapost', 'local_social_wall', $notification->firstname.' '.$notification->lastname);
                $output .= '</span>';
            }
            $output .= '</li>';
            $output .= '</a>';
        }
        if(count($notifications) > 10){
            $output .= '<li class="seemore-btn viewed-notification" id="0">';
                $output .= '<span>';
                    $output .= get_string('seemore', 'local_social_wall');
                $output .= '</span>';
            $output .= '</li>';
        }
        if(!count($notifications)){
            $output .= '<div class="seemore-btn center">';
                $output .= '<span>';
                    $output .= get_string('nonotification', 'local_social_wall');
                $output .= '</span>';
            $output .= '</div>';
        }
        $output .= '</ul>';
    $output .= html_writer::end_tag('div');
    echo $output;
}

function updatereadstatus($type, $id, $userid, $d){
    global $DB;
    $table = '';
    if($type == 'comment'){
        $table = 'social_wall_comments';
        $usercolumn = 'uid';
    } else if ($type == 'rating'){
        $table = 'social_wall_ratings';
        $usercolumn = 'userid';
    }
    if($table){
        $updaterecord = $DB->get_record($table, array($usercolumn => $userid, 'msg_id'=>$id, 'datecreated'=>$d ));
        if(!$updaterecord->viewed){
            $obj = new stdClass();
            $obj->id = $updaterecord->id;
            $obj->viewed = 1;
            $res = $DB->update_record($table, $obj);
        }
        return json_encode(['status' => true]);
    }
    return json_encode(['status' => false]);
}

function see_all_notifications(){
    global $DB, $USER;

    $unionsql = "SELECT
                    swc.datecreated,
                    swc.datemodified,
                    swc.msg_id,
                    swc.uid as userid,
                    swc.viewed,
                    u.firstname as firstname,
                    u.lastname as lastname,
                    'comment' as type
                FROM 
                    mdl_social_wall_comments as swc
                    INNER JOIN mdl_user as u on u.id = swc.uid
                    INNER JOIN mdl_social_wall_messages as swmc ON swmc.id = swc.msg_id
                WHERE
                    swmc.uid = $USER->id
                    AND swc.uid <> $USER->id
                UNION ALL
                SELECT 
                    swr.datecreated,
                    swr.datemodified,
                    swr.msg_id,
                    swr.userid as userid,
                    swr.viewed,
                    u.firstname as firstname,
                    u.lastname as lastname,
                    'rating' as type
                FROM mdl_social_wall_ratings as swr
                    INNER JOIN mdl_user as u on u.id = swr.userid
                    INNER JOIN mdl_social_wall_messages as swmr ON swmr.id = swr.msg_id
                WHERE
                    rating = 1
                    AND swmr.uid = $USER->id
                    AND userid <> $USER->id
                ORDER BY
                    datemodified DESC
                LIMIT 100";
    
    $notifications = $DB->get_records_sql($unionsql);
    $output = html_writer::start_tag('div', ['class' => 'notification-container']);
        $output .= html_writer::start_tag('div', ['class' => 'notification-header']);
            $output .= html_writer::start_tag('div', ['class' => 'notification-back cardone', 'id' => 'back_to_notification']);
                $output .= html_writer::start_tag('span', ['class' => 'back-to-timeline']);
                    $output .= html_writer::tag('i',null,array('class'=>'wid wid-icon-phback-to'));
                $output .= html_writer::end_tag('span');
            $output .= html_writer::end_tag('div');
            $output .= html_writer::start_tag('div', ['class' => 'notification-heading cardone']);
                $output .= html_writer::tag('h2', get_string('notifications', 'local_social_wall'));
            $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', ['class' => 'notification-wrap cardone']);
            $output .= '<ul id="notification-list">';
            foreach($notifications as $notification){
                $viewdclass = '';
                if(!$notification->viewed){
                    $viewdclass = 'class="viewed-notification" ';
                }
                $output .= '<li '.$viewdclass.' id="'.$notification->type.'-'.$notification->msg_id.'-'.$notification->userid.'-'.$notification->datecreated.'-notificationlist">';
                $userpic = strip_tags(get_user_picture_s($notification->userid),'<img>');
                
                if($notification->type == 'comment'){
                    $output .= $userpic;
                    $output .= '<span>';
                    $output .= get_string('commentedonpost', 'local_social_wall', $notification->firstname.' '.$notification->lastname);
                    $output .= '</span>';
                }else if($notification->type == 'rating'){
                    $output .= $userpic;
                    $output .= '<span>';
                    $output .= get_string('likesapost', 'local_social_wall', $notification->firstname.' '.$notification->lastname);
                    $output .= '</span>';
                }
                $output .= '</li>';
            }
            $output .= '</ul>';
        $output .= html_writer::end_tag('div');
    $output .= html_writer::end_tag('div');
    return json_encode(['html' => $output, 'status' => true]);
}

function see_notified_post($t, $m, $u, $d){
    global $DB, $USER;
    $currentmessage = $DB->get_record('social_wall_messages', array('id'=>$m));
    if(!$currentmessage){
        $output = html_writer::start_tag('div', ['class' => 'no-post']);
            $output .= '<h3>'. get_string('nonotification', 'local_social_wall').'</h3>';
        $output .= html_writer::end_tag('div');
        return json_encode(['html' => $output, 'status' => false]);
    }
    $currentmessageuser = $DB->get_record('user', array('id'=>$USER->id));
    $messageactionuser = $DB->get_record('user', array('id'=>$u));
    if(!$messageactionuser){
        $output = html_writer::start_tag('div', ['class' => 'no-post']);
            $output .= '<h3>'. get_string('nonotification', 'local_social_wall').'</h3>';
        $output .= html_writer::end_tag('div');
        return json_encode(['html' => $output, 'status' => false]);
    }
    $messageArr =   json_decode($currentmessage->message);
    $message    =   $messageArr->text;
    $time       =   $currentmessage->datecreated;
    $username   =   $currentmessageuser->firstname.' '.$currentmessageuser->lastname;
    $uid        =   $currentmessage->uid;
    $messageuser = $DB->get_record('user', array('id'=>$uid));
    $messageusername = $messageuser->firstname.' '.$messageuser->lastname;
    $startrow   =   0;
    $numrows    =   2;

    $table = '';
    if($t == 'comment'){
        $table = 'social_wall_comments';
        $usercolumn = 'uid';
        $notificationmessage = get_string('commentedonpost', 'local_social_wall', $messageactionuser->firstname.' '.$messageactionuser->lastname);
    } else if ($t == 'rating'){
        $table = 'social_wall_ratings';
        $usercolumn = 'userid';
        $notificationmessage = get_string('likesapost', 'local_social_wall', $messageactionuser->firstname.' '.$messageactionuser->lastname);
    }

    if($table){
        $updaterecord = $DB->get_record($table, array($usercolumn => $u, 'msg_id'=>$m, 'datecreated'=>$d ));
        if(!$updaterecord){
            $output = html_writer::start_tag('div', ['class' => 'no-post']);
                $output .= '<h3>'. get_string('nonotification', 'local_social_wall').'</h3>';
            $output .= html_writer::end_tag('div');
            return json_encode(['html' => $output, 'status' => false]);
        }
        $output = html_writer::start_tag('div', ['class' => 'notification-container']);
            $output .= html_writer::start_tag('div', ['class' => 'notification-header']);
                $output .= html_writer::start_tag('div', ['class' => 'notification-back cardone', 'id' => 'back_to_notification']);
                    $output .= html_writer::start_tag('span', ['class' => 'back-to-timeline']);
                        $output .= html_writer::tag('i',null,array('class'=>'wid wid-icon-phback-to'));
                    $output .= html_writer::end_tag('span');
                $output .= html_writer::end_tag('div');
                $output .= html_writer::start_tag('div', ['class' => 'notification-heading cardone']);
                    $output .= html_writer::tag('h2', get_string('notifications', 'local_social_wall'));
                $output .= html_writer::end_tag('div');
            $output .= html_writer::end_tag('div');
            $output .= html_writer::start_tag('div', ['class' => 'notification-wrap single-notification-wrap cardone']);
                $output .= html_writer::start_tag('div', ['class'=>'single-notification-message m-3 d-flex']);
                    $output .= html_writer::start_tag('div', ['id' => 'back-to-notification-btn']);
                        $output .= html_writer::tag('i',null,array('class'=>'wid wid-icon-back-large'));
                    $output .= html_writer::end_tag('div');
                    $output .= html_writer::start_tag('div', ['class' => 'back-to-notification ml-3']);
                        $output .= html_writer::tag('h6',$notificationmessage);
                    $output .= html_writer::end_tag('div');
                $output .= html_writer::end_tag('div');
                $output .= html_writer::start_div('stbody _cardone col-xs-12 col-sm-12 col-md-12 col-lg-12', array(id => "stbody$m"));
                    $output .= html_writer::start_div( 'stimg cardone-header');
                        $output .= html_writer::start_div( 'cardone-header-image d-flex justify-content-between align-items-center');            
                            $output .= html_writer::start_div( 'd-flex justify-content-between align-items-center');
                                $output .= html_writer::start_div( 'mr-1');
                                    $output .= get_user_picture_s($uid);
                                $output .= html_writer::end_div();        
                                $output .= html_writer::start_div( 'ml-1');
                                    $messageusername = html_writer::tag('a',"$messageusername", array("class" => 'h5 m-0'));
                                    $output .=html_writer::tag('h3',$messageusername,array('class'=>'h3 text-muted usr_heading'));
                                $output .= html_writer::end_div();
                            $output .= html_writer::end_div();
                        if(($uid==$USER->id) || is_siteadmin($USER->id)) {
                            $output .= html_writer::start_div( 'dropdown');
                                $output .= html_writer::tag( 'button', "<i class='fa fa-ellipsis-h' aria-hidden='true'></i>" , array('class' => 'btn btn-link dropdown-toggle','type'=>'button', 'data-toggle'=>'dropdown', 'aria-haspopup' => "true",'aria-expanded' => "false"));
                                $output .= html_writer::start_div( 'dropdown-menu dropdown-menu-right');
                                    $output .= html_writer::tag('a',"<i class='fa fa-pencil-square-o' aria-hidden='true'></i> Edit", array('class' => 'dropdown-item stedit','id'=>$m, 'title'=>'Edit'));
                                    $output .= html_writer::tag('a',"<i class='fa fa-trash' aria-hidden='true'></i> Delete", array('class' => 'dropdown-item stdelete','id'=>$m, 'title'=>'Delete'));
                                $output .= html_writer::end_div();
                            $output .= html_writer::end_div();
                        }                         
                        $output .= html_writer::end_div();

                        $output .= html_writer::start_div('sttext ');
                            $output .= html_writer::tag('input',"",array('type'=>'hidden','id'=>'upd_comid'));
                            $output .= html_writer::tag('div',$message,['class'=>'msg_content']);
                            $output .= html_writer::start_div('row row-margin'); 
                                $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 pl-0');
                                    $output .= html_writer::start_div('sttime');
                                        $output .= html_writer::tag('span', get_string('posted', 'local_social_wall'),['class'=>'']).time_stamp($time);
                                    $output .= html_writer::end_div();
                                $output .= html_writer::end_div();
                                $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right pr-0');
                                    $output .= render_likes_dislikes($m,$uid);
                                    $commentCount = get_comment_count($m,$uid);
                                    $comment = get_string('comment', 'local_social_wall'); 
                                    $output .= html_writer::tag('a',"$commentCount $comment <i class='fa fa-angle-down'></i>",['class'=>'comment-icon commentopen','href'=>'JavaScript:void(0)','id'=>$m]);
                                $output .= html_writer::end_div();
                            $output .= html_writer::end_div();
                            $output .= html_writer::start_div('',['id'=>'stexpandbox']);
                                $output .= html_writer::tag('div', "",['id'=>"stexpand$m"]);
                            $output .= html_writer::end_div();
                        $output .= html_writer::end_div();
                    $output .= html_writer::end_div();    
                    $output .= html_writer::start_div('row action-box'); 
                        $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center cardone-footer-buttons');
                                $output .= render_likes_dislikes_btn($m,$uid);
                        $output .= html_writer::end_div();
                        $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center cardone-footer-buttons');
                            $commentStr = get_string("comment",'local_social_wall');
                            $output .= html_writer::tag('a',"<i class='fa fa-comment-o'></i> $commentStr",['class'=>'social-like  commentopen','href'=>'JavaScript:void(0)','id'=>$m,'title'=>$commentStr]);
                        $output .= html_writer::end_div();
                    $output .= html_writer::end_div();
                    $output .= html_writer::start_div('',['class'=>'commentcontainer col-xs-12 col-sm-12 col-md-12 col-lg-12 py-3','style'=>"display:none",'id'=>"commentload$m"]);
                        $output .= load_comments($m,$startrow,$numrows);
                    $output .= html_writer::end_div();
                $output .= html_writer::end_div();
            $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        if(!$updaterecord->viewed){
            $obj = new stdClass();
            $obj->id = $updaterecord->id;
            $obj->viewed = 1;
            $res = $DB->update_record($table, $obj);
        }
        return json_encode(['html' => $output, 'status' => true]);
    }
    return json_encode(['status' => false]);
}

/**
 * Return list of Particular Userid
 * @return object messagerecord
 * Timeline
*/
function get_messages_timeline($companyid, $last_postid, $to = 2){

    global $SESSION, $USER, $DB,$OUTPUT;
    
    $fetch_fields ="M.id,M.course_id, M.activity_id,  M.uid, M.message, M.datecreated, M.companyid, U.username, U.firstname, U.lastname";
    $limit = "LIMIT ".$to;
    $where_by = '';
    //if user is a normal user, and it belongs to a tenant
    if($companyid){
        $where_by   = ( $companyid ? " M.companyid = $companyid AND " : null);
    }

    $where_post   = null;
    if ($last_postid) {
        $where_post   =  "AND M.id < $last_postid";
    }

    $messagerecord = $DB->get_records_sql("SELECT $fetch_fields FROM {social_wall_messages} M, {user} U WHERE $where_by M.uid = $USER->id AND M.uid = U.id AND M.deleted = 0 AND U.deleted = 0 $where_post ORDER BY M.id DESC $limit");
    return $messagerecord;
}

// Render all messages html for Timeline
function load_messages_timeline($cid, $last_postid){
    global $SESSION, $USER, $DB,$OUTPUT;
    $to = get_config('local_social_wall', 'postsload');
    $messagerecord =  get_messages_timeline($cid, $last_postid, $to);

    if(empty($messagerecord)){
        $output = '<div class="no-post"><h3>'. get_string('haventposted', 'local_social_wall').'</h3></div>';
    }else{
        foreach($messagerecord AS $data){
            $msg_id     =   $data->id;
            $messageArr =   json_decode($data->message);
            $message    =   $messageArr->text;
            $time       =   $data->datecreated;
            $username   =   $data->firstname.' '.$data->lastname;
            $uid        =   $data->uid;
            $numrows    =   2;

            $output .= html_writer::start_div('stbody _cardone col-xs-12 col-sm-12 col-md-12 col-lg-12', array(id => "stbody$msg_id"));
               // $output .= html_writer::start_div('stbody-intern col-xs-12 col-sm-12 col-md-12 col-lg-12');
                $output .= html_writer::start_div( 'stimg cardone-header');
                    $output .= html_writer::start_div( 'cardone-header-image d-flex justify-content-between align-items-center');
                        
                        $output .= html_writer::start_div( 'd-flex justify-content-between align-items-center');
                            $output .= html_writer::start_div( 'mr-1');
                                $output .= get_popover_picture($uid,$msg_id);
                            $output .= html_writer::end_div();
                            
                            $output .= html_writer::start_div( 'ml-1');
                                $username = html_writer::tag('a',"$username", array("class" => 'h5 m-0'));
                                $output .=html_writer::tag('h3',$username,array('class'=>'h3 text-muted usr_heading'));
                            $output .= html_writer::end_div();
                        $output .= html_writer::end_div();

                        $output .= html_writer::start_div( 'ml-1');
                            $output .= html_writer::tag('a',"<i class='fa fa-pencil-square-o' aria-hidden='true'></i> Edit", array('class' => '','id'=>'post'.$msg_id, 'title'=>'Link'));
                            $output .= html_writer::end_div();
                        
                        if(($uid==$USER->id) || is_siteadmin($USER->id)) {
                            $output .= html_writer::start_div( 'dropdown');
                                $output .= html_writer::tag( 'button', "<i class='fa fa-ellipsis-h' aria-hidden='true'></i>" , array('class' => 'btn btn-link dropdown-toggle','type'=>'button', 'data-toggle'=>'dropdown', 'aria-haspopup' => "true",'aria-expanded' => "false"));
                           
                                $output .= html_writer::start_div( 'dropdown-menu dropdown-menu-right');
                                   $output .= html_writer::tag('a',"<i class='fa fa-pencil-square-o' aria-hidden='true'></i> Edit", array('class' => 'dropdown-item stedit','id'=>$msg_id, 'title'=>'Edit'));
                                   $output .= html_writer::tag('a',"<i class='fa fa-trash' aria-hidden='true'></i> Delete", array('class' => 'dropdown-item stdelete','id'=>$msg_id, 'title'=>'Delete'));
                                $output .= html_writer::end_div();
                            $output .= html_writer::end_div();
                        }
                         
                    $output .= html_writer::end_div();

                    $output .= html_writer::start_div('sttext ');
                        $output .= html_writer::tag('input',"",array('type'=>'hidden','id'=>'upd_comid'));
                            $output .= html_writer::tag('div',$message,['class'=>'msg_content']);
                        $output .= html_writer::start_div('row row-margin'); 
                            $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 pl-0');
                                
                                $output .= html_writer::start_div('sttime');
                                    $output .= html_writer::tag('span', "Posted ",['class'=>'']).time_stamp($time);
                                $output .= html_writer::end_div();
                            $output .= html_writer::end_div();
                            
                            $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right pr-0');
                            // render like button 
                                $output .= render_likes_dislikes($msg_id,$uid);
                                $commentCount = get_comment_count($msg_id,$uid);
                                $comment = get_string('comment', 'local_social_wall'); 
                                $output .= html_writer::tag('a',"$commentCount $comment <i class='fa fa-angle-down'></i>",['class'=>'comment-icon commentopen','href'=>'JavaScript:void(0)','id'=>$msg_id]);
                            $output .= html_writer::end_div();
                        $output .= html_writer::end_div();
                        $output .= html_writer::start_div('',['id'=>'stexpandbox']);
                            $output .= html_writer::tag('div', "",['id'=>"stexpand$msg_id"]);
                        $output .= html_writer::end_div();
                    $output .= html_writer::end_div();
                $output .= html_writer::end_div();
                    
                $output .= html_writer::start_div('row action-box'); 
                    $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center cardone-footer-buttons');
                        // $output .= html_writer::tag('span', "<i class='fa fa-thumbs-o-up social_like'></i> Likes",['class'=>'social-like']);
                        $output .= render_likes_dislikes_btn($msg_id,$uid);
                    $output .= html_writer::end_div();
                    
                    $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center cardone-footer-buttons');
                        $commentStr = get_string("comment",'local_social_wall');
                        $output .= html_writer::tag('a',"<i class='fa fa-comment-o'></i> $commentStr",['class'=>'social-like  commentopen','href'=>'JavaScript:void(0)','id'=>$msg_id,'title'=>$commentStr]);
                    $output .= html_writer::end_div();
                $output .= html_writer::end_div();
                    
                $output .= html_writer::start_div('',['class'=>'commentcontainer col-xs-12 col-sm-12 col-md-12 col-lg-12 py-3','style'=>"display:none",'id'=>"commentload$msg_id"]);
                    $output .= load_comments($msg_id,$startrow,$numrows);
                $output .= html_writer::end_div();
                    
            $output .= html_writer::end_div();
        } 
    } 
    return json_encode(['html' => $output]);
}

/*
 Updates See Post
*/
function get_messages_updates_seepost($msgid){

    global $SESSION, $USER, $DB,$OUTPUT;
    
    $fetch_fields ="M.id,M.course_id, M.activity_id,  M.uid, M.message, M.datecreated, M.companyid, U.username, U.firstname, U.lastname";

    
    $messagerecord = $DB->get_records_sql("SELECT $fetch_fields FROM {social_wall_messages} M, {user} U WHERE M.id=$msgid AND M.uid = $USER->id AND M.uid = U.id AND M.deleted = 0 AND U.deleted = 0 ORDER BY M.id DESC");
    return $messagerecord;
}

// Render all messages html for Updates See Post
function load_messages_updates_seepost($msgid){
    global $SESSION, $USER, $DB,$OUTPUT;

    $messagerecord =  get_messages_updates_seepost($msgid);
    if(empty($messagerecord)){
        $output = '<div class="no-post"><h3>'. get_string('haventposted', 'local_social_wall').'</h3></div>';
    }else{
        foreach($messagerecord AS $data){
            $msg_id     =   $data->id;
            $messageArr =   json_decode($data->message);
            $message    =   $messageArr->text;
            $time       =   $data->datecreated;
            $username   =   $data->firstname.' '.$data->lastname;
            $uid        =   $data->uid;
            $numrows    =   2;

            $output .= html_writer::start_div('stbody _cardone col-xs-12 col-sm-12 col-md-12 col-lg-12', array(id => "stbody$msg_id"));
               // $output .= html_writer::start_div('stbody-intern col-xs-12 col-sm-12 col-md-12 col-lg-12');
                $output .= html_writer::start_div( 'stimg cardone-header');
                    $output .= html_writer::start_div( 'cardone-header-image d-flex justify-content-between align-items-center');
                        
                        $output .= html_writer::start_div( 'd-flex justify-content-between align-items-center');
                            $output .= html_writer::start_div( 'mr-1');
                                $output .= get_user_picture_s($uid);
                            $output .= html_writer::end_div();
                            
                            $output .= html_writer::start_div( 'ml-1');
                                $username = html_writer::tag('a',"$username", array("class" => 'h5 m-0'));
                                $output .=html_writer::tag('h3',$username,array('class'=>'h3 text-muted usr_heading'));
                            $output .= html_writer::end_div();
                        $output .= html_writer::end_div();

                        $output .= html_writer::start_div( 'ml-1');
                            $output .= html_writer::tag('a',"<i class='fa fa-pencil-square-o' aria-hidden='true'></i> Edit", array('class' => '','id'=>'post'.$msg_id, 'title'=>'Link'));
                            $output .= html_writer::end_div();
                        
                        if(($uid==$USER->id) || is_siteadmin($USER->id)) {
                            $output .= html_writer::start_div( 'dropdown');
                                $output .= html_writer::tag( 'button', "<i class='fa fa-ellipsis-h' aria-hidden='true'></i>" , array('class' => 'btn btn-link dropdown-toggle','type'=>'button', 'data-toggle'=>'dropdown', 'aria-haspopup' => "true",'aria-expanded' => "false"));
                           
                                $output .= html_writer::start_div( 'dropdown-menu dropdown-menu-right');
                                   $output .= html_writer::tag('a',"<i class='fa fa-pencil-square-o' aria-hidden='true'></i> Edit", array('class' => 'dropdown-item stedit','id'=>$msg_id, 'title'=>'Edit'));
                                   $output .= html_writer::tag('a',"<i class='fa fa-trash' aria-hidden='true'></i> Delete", array('class' => 'dropdown-item stdelete','id'=>$msg_id, 'title'=>'Delete'));
                                $output .= html_writer::end_div();
                            $output .= html_writer::end_div();
                        }
                         
                    $output .= html_writer::end_div();

                    $output .= html_writer::start_div('sttext ');
                        $output .= html_writer::tag('input',"",array('type'=>'hidden','id'=>'upd_comid'));
                            $output .= html_writer::tag('div',$message,['class'=>'msg_content']);
                        $output .= html_writer::start_div('row row-margin'); 
                            $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 pl-0');
                                
                                $output .= html_writer::start_div('sttime');
                                    $output .= html_writer::tag('span', "Posted ",['class'=>'']).time_stamp($time);
                                $output .= html_writer::end_div();
                            $output .= html_writer::end_div();
                            
                            $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right pr-0');
                            // render like button 
                                $output .= render_likes_dislikes($msg_id,$uid);
                                $commentCount = get_comment_count($msg_id,$uid);
                                $comment = get_string('comment', 'local_social_wall'); 
                                $output .= html_writer::tag('a',"$commentCount $comment <i class='fa fa-angle-down'></i>",['class'=>'comment-icon commentopen','href'=>'JavaScript:void(0)','id'=>$msg_id]);
                            $output .= html_writer::end_div();
                        $output .= html_writer::end_div();
                        $output .= html_writer::start_div('',['id'=>'stexpandbox']);
                            $output .= html_writer::tag('div', "",['id'=>"stexpand$msg_id"]);
                        $output .= html_writer::end_div();
                    $output .= html_writer::end_div();
                $output .= html_writer::end_div();
                    
                $output .= html_writer::tag('hr',"");
                
                $output .= html_writer::start_div('row action-box'); 
                    $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center cardone-footer-buttons');
                        // $output .= html_writer::tag('span', "<i class='fa fa-thumbs-o-up social_like'></i> Likes",['class'=>'social-like']);
                        $output .= render_likes_dislikes_btn($msg_id,$uid);
                    $output .= html_writer::end_div();
                    
                    $output .= html_writer::start_div('col-xs-6 col-sm-6 col-md-6 col-lg-6 text-center cardone-footer-buttons');
                        $commentStr = get_string("comment",'local_social_wall');
                        $output .= html_writer::tag('a',"<i class='fa fa-comment-o'></i> $commentStr",['class'=>'social-like  commentopen','href'=>'JavaScript:void(0)','id'=>$msg_id,'title'=>$commentStr]);
                    $output .= html_writer::end_div();
                $output .= html_writer::end_div();
                
                $output .= html_writer::start_div('',['class'=>'commentcontainer col-xs-12 col-sm-12 col-md-12 col-lg-12 py-3','style'=>"display:none",'id'=>"commentload$msg_id"]);
                    $output .= load_comments($msg_id,$startrow,$numrows);
                $output .= html_writer::end_div();
                    
            $output .= html_writer::end_div();
        } 
    } 
    return json_encode(['html' => $output]);
}
/**
 * Send system Notification
 *
 * This function is used to send system notification to user
 * when someone like or comments on social wall post
 *
 * @package local_social_wall
 * @version 9.3.1
 * @author  Vinay B
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @paradiso
 */

function send_system_notification($msgid, $message) {
    global $DB, $CFG, $USER;
    $record = $DB->get_record('social_wall_messages', ['id'=>$msgid]);
    $msg_user = $record->uid;
    $subject = get_string('socialnotification', 'local_social_wall');
    $data                    = new \core\message\message();
    $data->component         = 'moodle';
    $data->name              = 'instantmessage';
    $data->userfrom          = 2;
    $data->userto            = $msg_user;
    $data->subject           = $subject;
    $data->notification      = 1;
    $data->fullmessagehtml   = $message;
    $data->fullmessageformat = FORMAT_MARKDOWN;
    $msgid = message_send($data);

    // Send Email
    if($USER->id != $msg_user){
        $email = core_user::get_user($msg_user);
        $from_email = generate_email_user($CFG->noreplyaddress);
        $mail = email_to_user($email, $from_email, $subject, $message, text_to_html($message));
    }

}
/**
 * Temporary email user
 *
 * This function is used to create temporary user for sending an email
 *
 * @package local_social_wall
 * @version 9.3.1
 * @author  Vinay B
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @paradiso
 */
function generate_email_user($email, $name = '', $id = -99) {
    $emailuser = new stdClass();
    $emailuser->email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailuser->email = '';
    }
    $name = format_text($name, FORMAT_HTML, array('trusted' => false, 'noclean' => false));
    $emailuser->firstname = trim(filter_var($name, FILTER_SANITIZE_STRING));
    $emailuser->lastname = '';
    $emailuser->maildisplay = true;
    $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML emails.
    $emailuser->id = $id;
    $emailuser->firstnamephonetic = '';
    $emailuser->lastnamephonetic = '';
    $emailuser->middlename = '';
    $emailuser->alternatename = '';
    return $emailuser;
}
/**
 * Count all social media posts
 *
 * This function is used to count all social media posts that is visible to user
 *
 * @package local_social_wall
 * @version 9.3.1
 * @author  Vinay B
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @paradiso
 */
function all_social_wall_posts_count() {
    $records = get_messages(null, 0);
    return count($records);
}

function get_popover_picture($uid,$msg_id){
    global $DB,$OUTPUT;

    $user = $DB->get_record_sql("SELECT * FROM {user} WHERE id = $uid");
    $picture = $OUTPUT->user_picture($user, array('size'=>40));
    $picture = str_replace('title=', '', $picture);
    $picture = str_replace('<a', '<a target="_blank" id="popover-'.$uid.'-'.$msg_id.'" class="user-popover" rel="popover" data-placement="bottom" ', $picture);
    
    return $picture;
}

function social_user_bio(){
    global $DB;

    $messagerecord = $DB->get_records_sql("SELECT * FROM {social_wall_messages} M, {user} U WHERE $where_by M.uid = U.id AND M.deleted = 0 AND U.deleted = 0 ORDER BY M.id DESC");
    // echo "<pre>";
    // print_r($messagerecord);exit;
    $msg_id = '';$uid ='';
    foreach($messagerecord AS $data){
            $msg_id     =   $data->id;
            $uid        =   $data->uid;
        }

        echo render_likes_dislikes($msg_id,$uid);
}

function get_new_notification_count(){
    global $DB, $USER;

    $unionsql = "SELECT
                    swc.datecreated,
                    swc.datemodified,
                    swc.msg_id,
                    swc.uid as userid,
                    swc.viewed,
                    u.firstname as firstname,
                    u.lastname as lastname,
                    'comment' as type
                FROM 
                    mdl_social_wall_comments as swc
                    INNER JOIN mdl_user as u on u.id = swc.uid
                    INNER JOIN mdl_social_wall_messages as swmc ON swmc.id = swc.msg_id
                WHERE
                    swmc.uid = $USER->id
                    AND swc.uid <> $USER->id
                    AND swc.viewed IS NULL
                UNION ALL
                SELECT 
                    swr.datecreated,
                    swr.datemodified,
                    swr.msg_id,
                    swr.userid as userid,
                    swr.viewed,
                    u.firstname as firstname,
                    u.lastname as lastname,
                    'rating' as type
                FROM mdl_social_wall_ratings as swr
                    INNER JOIN mdl_user as u on u.id = swr.userid
                    INNER JOIN mdl_social_wall_messages as swmr ON swmr.id = swr.msg_id
                WHERE
                    rating = 1
                    AND swmr.uid = $USER->id
                    AND userid <> $USER->id
                    AND swr.viewed IS NULL
                ORDER BY
                    datemodified DESC
                LIMIT 100";

    $notificationcount = $DB->get_records_sql($unionsql);
    return count($notificationcount);
}
