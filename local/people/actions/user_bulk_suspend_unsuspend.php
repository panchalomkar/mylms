<?php

/**
* script for bulk user suspend and unsupend operations
*/

/**
* This script allows to suspend or 
* unsusped users accounts in bulk
* @author Esteban E.
* @since September 23 of 2016
* @paradiso
*/

require_once('../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/authlib.php');



$returnurl = new moodle_url($SESSION->return);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$unlock = optional_param('unlock', 0, PARAM_BOOL);
$suspend = optional_param('suspend', 0, PARAM_BOOL);
$cancel = optional_param('cancel', 0, PARAM_BOOL);
$sitecontext = context_system::instance();


if(empty($SESSION->bulk_users))
{
    if($_POST['bulk_action'])
    {
        $json = $_POST['bulk_action'];
        $dataArray = json_decode($json);
        $SESSION->bulk_users = array();

        foreach ($dataArray->data as $key => $value) {
            $SESSION->bulk_users[$value] = $value;
        }
        
    }
}

if($suspend)
{
    $stringIdentifier = 'suspend';
}elseif($unlock)
{
    $stringIdentifier  = 'unlock';
}

/**
* Suspend users
*/
if ($confirm && $suspend) 
{
    require_capability('moodle/user:update', $sitecontext);
    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    $rs = $DB->get_recordset_select('user', "id $in", $params);
    $countrs=0;
    foreach ($rs as $user) 
    {
        if (!is_siteadmin($user) && $USER->id != $user->id && $user->suspended != 1) 
        {
            $user->suspended = 1;
            // Force logout.
            \core\session\manager::kill_user_sessions($user->id);
            user_update_user($user, false);
            $userssuspende .= fullname($user, true).', ';
        } 
        else 
        {
            $usersnotsuspende .= fullname($user, true).',';
        }
        $countrs++;
    }
    if($countrs>1){
        $notifications = get_string('users_suspended1','local_people',rtrim(trim($userssuspende), ','));
    }else{
        $notifications = get_string('users_suspended','local_people',rtrim(trim($userssuspende), ','));
    }
      unset($SESSION->bulk_users);
       
        if($usersnotsuspende || $userssuspende ){

        echo $OUTPUT->header();

        $formcontinue = new single_button(new moodle_url($returnurl, array()), get_string('continue'));

        echo $OUTPUT->box_start('generalbox', 'notice');
        // users suspended
            if($userssuspende)
            {
                    if (!empty($notifications)) {
                        echo $OUTPUT->notification($notifications, 'notifysuccess');
                    }

                echo $OUTPUT->box_end();
            }
            if($usersnotsuspende)
            {  
            // users that cant be suspended
            $notifications=get_string('users_cant_be_suspended','local_people',rtrim(trim($usersnotsuspende), ','));
                if (!empty($notifications)) {
                    echo $OUTPUT->notification($notifications, 'error');
                }
                echo $OUTPUT->box_end();
            }

            echo $OUTPUT->render($formcontinue);

        echo $OUTPUT->box_end();

        echo $OUTPUT->footer();
        die;
    }
    else 
    { 
        redirect($returnurl);
    }
    
}

/**
* Unsuspend users
*/
if ($confirm && $unlock)
{
    require_capability('moodle/user:update', $sitecontext);
    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    $rs = $DB->get_recordset_select('user', "id $in", $params);
    $notifications = get_string('users_unsuspended','local_people');
    foreach ($rs as $user) 
    {
        if (!is_siteadmin($user) && $USER->id != $user->id && $user->suspended == 1) 
        {
            $user->suspended = 0;
            user_update_user($user, false);
            login_unlock_account($user);
            $usersunsuspende .= fullname($user, true).',';
        } 
       

    }
    unset($SESSION->bulk_users);

    if($usersunsuspende)
    {
        $notifications .=$usersunsuspende;
        echo $OUTPUT->header();

        $formcontinue = new single_button(new moodle_url($returnurl, array()), get_string('continue'));

        echo $OUTPUT->box_start('generalbox', 'notice');
        if (!empty($notifications)) {
            echo $notifications;
        }
        
        echo $OUTPUT->box_end();
        echo $OUTPUT->render($formcontinue);
        echo $OUTPUT->footer();
        die;

    }else 
    {
        redirect($returnurl);
    }
}
if($cancel)
{
    unset($SESSION->bulk_users);
    redirect($returnurl);
}

echo $OUTPUT->header();
list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
$userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname');
$usernames = implode(', ', $userlist);

$messagestring = 'bulk_user_'.$stringIdentifier ;
if(count($userlist) > 1)
{
   $messagestring = str_replace('bulk_user_', 'bulk_users_', $messagestring) ;
}

$formcontinue = new single_button(new moodle_url('user_bulk_suspend_unsuspend.php', array('confirm' => 1,'unlock'=>$unlock ,'suspend'=>$suspend)), get_string('yes'));
$formcancel = new single_button(new moodle_url('user_bulk_suspend_unsuspend.php',array('cancel'=>1)), get_string('no'), 'get');
echo $OUTPUT->confirm(get_string($messagestring,'local_people').$usernames.' ?' , $formcontinue, $formcancel);
echo $OUTPUT->footer();
