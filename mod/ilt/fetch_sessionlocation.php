<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/*
 * @Author VaibhavG
 * @desc code to getting classroom according to it's location
 * @date 13 Dec 2018
 * Start Code
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/ilt/lib.php');
//$s  = required_param('s', PARAM_INT); // ILT session ID.
GLOBAL $DB;

//Getting classroom of location
$location = optional_param('location',  0,  PARAM_ALPHANUMEXT);
// If locations exists
if($location) {
    $class_arr = $DB->get_records_sql('SELECT id,classroom FROM mdl_local_classroom where locationid= ?', array($location));
    echo '<option>Select Classroom</option>';

    foreach ($class_arr as $classroom) {
        echo '<option value="'.$classroom->id.'">'.$classroom->classroom.'</option>';
    }
}


//Getting capacity of classroom
$classroom = optional_param('classroom',  0,  PARAM_ALPHANUMEXT);
// If classroom exists
if($classroom) {
    $cap_arr = $DB->get_records_sql('SELECT c.capacity FROM mdl_local_classroom c WHERE c.id= ?', array($classroom));
    
    foreach ($cap_arr as $capacity) {
        echo $capacity->capacity;
    }
}


//Getting resources of classroom
$classroomres = optional_param('classroomres',  0,  PARAM_ALPHANUMEXT);
// If resource exists
if($classroomres) {
    $res_arr = $DB->get_records_sql('SELECT c.id,r.id,r.classroomid,r.resource,r.resourceqty FROM mdl_local_classroom c JOIN mdl_local_resource r ON c.id = r.classroomid WHERE c.id= ?', array($classroomres));
    
    foreach ($res_arr as $resource) {
        echo '<option value="'.$resource->id.'">'.$resource->resource .' '. $resource->resourceqty.'</option>';
    }
}


//getting count of session attendees
$sessionids = optional_param('sessionids',  0,  PARAM_ALPHANUMEXT);
if($sessionids) 
{
    $sql = 'SELECT capacity
        FROM
            mdl_ilt_sessions 
        WHERE
            id = ?
        ';
    echo $user_count = $DB->count_records_sql($sql, array($sessionids));
}


//getting all & course attendees/users of session 
 $coursetype = optional_param('coursetype',  0,  PARAM_ALPHANUMEXT);
 $sessionid = optional_param('sessionid',  0,  PARAM_ALPHANUMEXT);
 $courseid = optional_param('courseid',  0,  PARAM_ALPHANUMEXT);
 $MDL_ILT_STATUS_WAITLISTED = MDL_ILT_STATUS_WAITLISTED;
 //all course/system users
if($coursetype == "All" && $sessionid)
{
    $sql = "SELECT u.id,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename,u.firstname,u.lastname,u.email 
                FROM mdl_user u WHERE u.id!= 1 AND u.deleted = 0 AND u.confirmed = 1 AND u.id 
                NOT IN ( SELECT u2.id FROM mdl_ilt_signups s 
                JOIN mdl_ilt_signups_status ss ON s.id = ss.signupid 
                JOIN mdl_user u2 ON u2.id = s.userid 
                WHERE s.sessionid = ? AND ss.statuscode >= ? AND ss.superceded = 0 )
                ORDER BY u.lastname ASC, u.firstname ASC";
    $alluserscount = $DB->count_records_sql($sql,array($sessionid, $MDL_ILT_STATUS_WAITLISTED));
    $allusers = $DB->get_records_sql($sql,array($sessionid, $MDL_ILT_STATUS_WAITLISTED));
    
    foreach ($allusers as $users) {
        echo '<option value="'.$users->id.'">'.$users->firstname .' '. $users->lastname.' ['.$users->email.'] </option>';
    }
}

//selected course users
if($coursetype == "Course" && $sessionid && $courseid)
{    
    $sql = "SELECT u.id,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename,u.firstname,u.lastname,u.email
                FROM mdl_user u 
                JOIN mdl_user_enrolments ue 
                ON (ue.userid = u.id) 
                JOIN mdl_enrol e ON (e.id = ue.enrolid) 
                WHERE u.id <> 1 AND u.deleted = 0 AND u.confirmed = 1 AND e.courseid = ? AND u.id 
                NOT IN ( SELECT u2.id FROM mdl_ilt_signups s 
                JOIN mdl_ilt_signups_status ss 
                ON s.id = ss.signupid 
                JOIN mdl_user u2 
                ON u2.id = s.userid 
                WHERE s.sessionid = ? 
                AND ss.statuscode >= ?
                AND ss.superceded = 0 ) 
                ORDER BY u.lastname ASC, u.firstname ASC";
    $courseuserscount = $DB->count_records_sql($sql,array($courseid, $sessionid, $MDL_ILT_STATUS_WAITLISTED));
    
    $courseusers = $DB->get_records_sql($sql,array($courseid, $sessionid, $MDL_ILT_STATUS_WAITLISTED));
    foreach ($courseusers as $users) {
        echo '<option value="'.$users->id.'">'.$users->firstname .' '. $users->lastname.' ['.$users->email.'] </option>';
    }
}



//sending already booked user for all previous sessions with same time
$addselect = optional_param('addselect',  0,  PARAM_ALPHANUMEXT);
$current_sessionid = optional_param('current_sessionid',  0,  PARAM_ALPHANUMEXT);
$flag = false;
if($addselect && $current_sessionid)
{   
    $get_sessiontime = $DB->get_records('ilt_sessions_dates',array('sessionid' => $current_sessionid));
    foreach($get_sessiontime as $sessiontime)
    {
        $sql_session_date = "SELECT sessionid
                        FROM mdl_ilt_sessions_dates
                        WHERE ($sessiontime->timestart BETWEEN timestart AND timefinish)
                        OR ($sessiontime->timefinish BETWEEN timestart AND timefinish)                            
                        ";
        $get_session_date = $DB->get_records_sql($sql_session_date,array(null));
    }
    
    $sessionuser_array = array();
    $searchString = ',';
    $sessionuser_array = explode(',', $addselect);
    
    if(stripos($sessionuser_array, $searchString) == false )
    {
        foreach($sessionuser_array as $user_val)
        {
            foreach($get_session_date as $val)
            {
                $sql = "SELECT * 
                        FROM mdl_ilt_signups s
                        JOIN mdl_ilt_signups_status ss
                        ON s.id = ss.signupid
                        WHERE s.sessionid = $val->sessionid
                        AND ss.statuscode = 70 
                        AND ss.superceded = 0
                        AND ss.createdby = $user_val";
                $courseusers = $DB->get_records_sql($sql,array(null));//exit;
                foreach($courseusers as $userid)
                {
                    if($userid->id)
                        $flag = true;
                }

            }
        }
        if($flag == true)
            echo get_string('alreadyenrolled','mod_ilt');;
    }
    else
    {
        foreach($get_session_date as $val)
        {
            $sql = "SELECT * 
                        FROM mdl_ilt_signups s
                        JOIN mdl_ilt_signups_status ss
                        ON s.id = ss.signupid
                        WHERE s.sessionid = $val->sessionid
                        AND ss.statuscode = 70 
                        AND ss.superceded = 0 
                        AND ss.createdby = $addselect";
            $courseusers = $DB->get_records_sql($sql,array(null));//exit;
            foreach($courseusers as $userid)
            {
                if($userid->id)
                    $flag = true;
            }

        }
        if($flag == true)
            echo get_string('alreadyenrolled','mod_ilt');
    }
}




//sending already booked instructor for all previous sessions in system level not course level
 $sessioninstructor = optional_param('sessioninstructor',  0,  PARAM_ALPHANUMEXT);
 $current_session = optional_param('current_session',  0,  PARAM_ALPHANUMEXT);
 $start = optional_param('start',  0,  PARAM_TEXT);
 $finish = optional_param('finish',  0,  PARAM_TEXT);
 $start_date = strtotime($start);
 $finish_date = strtotime($finish);
//it checks current session instructor
if($sessioninstructor && $current_session != 0)
{
     $sql_session_date = "SELECT sessionid
                        FROM mdl_ilt_sessions_dates
                        WHERE ($start_date BETWEEN timestart AND timefinish)
                        OR ($finish_date BETWEEN timestart AND timefinish)                            
                        ";
    $get_session_date = $DB->get_records_sql($sql_session_date,array(null));
    if(!empty($get_session_date))
    {
        $searchString = ',';
        if(strstr($sessioninstructor, $searchString) == true ) 
        {
            $name="";
            $sessioninstructor_array = array();
            $sessioninstructor_array = explode(',',$sessioninstructor);
            foreach($sessioninstructor_array as $instructor_val)
            {
                foreach($get_session_date as $val)
                {
                    $sql = "SELECT id,instructor 
                            FROM mdl_ilt_sessions
                            WHERE id != $current_session";
                    $courseusers = $DB->get_records_sql($sql,array(null));
                    foreach($courseusers as $userid)
                    {
                        $myarray = array();
                        $myarray = explode(',',$userid->instructor);
                        if(in_array($instructor_val, $myarray))
                        {
                            $username = $DB->get_records('user',array('id'=>$instructor_val));
                            $username= array_shift($username);
                            $name = $username->firstname; 

                        }
                    }

                }
            }
            if($name)
                echo $name . get_string('alreadyenrolled','mod_ilt');
        }else
        {
            $name ="";
            foreach($get_session_date as $val)
            {
                 $sql = "SELECT id,instructor
                            FROM mdl_ilt_sessions
                            WHERE id != $current_session";
                $courseusers = $DB->get_records_sql($sql,array(null));
                foreach($courseusers as $userid)
                {
                    $myarray = array();
                    $myarray = explode(',',$userid->instructor);
                    if(in_array($sessioninstructor, $myarray))
                    {
                        $username = $DB->get_records('user',array('id'=>$sessioninstructor));
                        $username= array_shift($username);
                        $name = $username->firstname; 
                    }
                }

            }
            if($name)        
                echo $name . get_string('alreadyenrolled','mod_ilt');
        }
    }
}


//it doesn't checks current session instructor.. it checks for other session for same time
if($sessioninstructor && $current_session == 0)
{
      $sql_session_date = "SELECT sessionid
                        FROM mdl_ilt_sessions_dates
                        WHERE ($start_date BETWEEN timestart AND timefinish)
                        OR ($finish_date BETWEEN timestart AND timefinish)                            
                        ";
    $get_session_date = $DB->get_records_sql($sql_session_date,array(null));
    if(!empty($get_session_date))
    {
        $searchString = ',';
        if(strstr($sessioninstructor, $searchString) == true ) 
        {
            $name="";
            $sessioninstructor_array = array();
            $sessioninstructor_array = explode(',',$sessioninstructor);
            foreach($sessioninstructor_array as $instructor_val)
            {
                foreach($get_session_date as $val)
                {
                    $sql = "SELECT id,instructor 
                            FROM mdl_ilt_sessions
                            WHERE id != $current_session";
                    $courseusers = $DB->get_records_sql($sql,array(null));
                    foreach($courseusers as $userid)
                    {
                        $myarray = array();
                        $myarray = explode(',',$userid->instructor);
                        if(in_array($instructor_val, $myarray))
                        {
                            $username = $DB->get_records('user',array('id'=>$instructor_val));
                            $username= array_shift($username);
                            $name = $username->firstname; 
                        }
                    }

                }
            }
            if($name)
                echo $name . get_string('alreadyenrolled','mod_ilt');
        }else
        {
            $name ="";
            foreach($get_session_date as $val)
            {
                $sql = "SELECT id,instructor
                            FROM mdl_ilt_sessions
                            WHERE id != $current_session";
                $courseusers = $DB->get_records_sql($sql,array(null));
                foreach($courseusers as $userid)
                {
                    $myarray = array();
                    $myarray = explode(',',$userid->instructor);
                    if(in_array($sessioninstructor, $myarray))
                    {
                        $username = $DB->get_records('user',array('id'=>$sessioninstructor));
                        $username= array_shift($username);
                        $name = $username->firstname; 
                    }
                }
            }
            if($name)
             echo $name . get_string('alreadyenrolled','mod_ilt');
        }
    }
}
   
//Codede by VaibhavG dated on 12 April2019
//sending already booked classrooms for all previous sessions in system level not course level
$sessionlocation = optional_param('sessionlocation',  0,  PARAM_ALPHANUMEXT);
$sessionclassroom = optional_param('sessionclassroom',  0,  PARAM_ALPHANUMEXT);
$current_session = optional_param('current_session',  0,  PARAM_ALPHANUMEXT);
$start = optional_param('start',  0,  PARAM_TEXT);
$finish = optional_param('finish',  0,  PARAM_TEXT);
$start_date = strtotime($start);
$finish_date = strtotime($finish);
//it checks current session instructor
if($sessionclassroom)
{
     $sql_session_date = "SELECT sessionid
                        FROM mdl_ilt_sessions_dates
                        WHERE ($start_date BETWEEN timestart AND timefinish)
                        OR ($finish_date BETWEEN timestart AND timefinish)                            
                        ";
    $get_session_date = $DB->get_records_sql($sql_session_date,array(null));
    if(!empty($get_session_date))
    {
        $searchString = ',';
        if(strstr($sessionclassroom, $searchString) == true ) 
        {
            $name="";
            $sessionclassroom_array = array();
            $sessionclassroom_array = explode(',',$sessionclassroom);
            foreach($sessionclassroom_array as $sessionclassroom_val)
            {
                foreach($get_session_date as $val)
                {
                    $sql = "SELECT id,classroom 
                            FROM mdl_ilt_sessions WHERE id != $current_session";
                    $courseusers = $DB->get_records_sql($sql,array(null));
                    foreach($courseusers as $userid)
                    {
                        $myarray = array();
                        $myarray = explode(',',$userid->instructor);
                        if(in_array($sessionclassroom_val, $myarray))
                        {
                            $classroom = $DB->get_records('local_classroom',array('classroom'=>$sessionclassroom_val));
                            $classroom= array_shift($classroom);
                            $name = $classroom->classroom; 

                        }
                    }

                }
            }
            if($name)
                echo $name . get_string('alreadyenrolled','mod_ilt');
        }else
        {
            $name ="";
            foreach($get_session_date as $val)
            {
                 $sql = "SELECT id,classroom
                            FROM mdl_ilt_sessions  WHERE id != $current_session";
                $courseusers = $DB->get_records_sql($sql,array(null));
                foreach($courseusers as $userid)
                {
                    $myarray = array();
                    $myarray = explode(',',$userid->classroom);
                    if(in_array($sessionclassroom, $myarray))
                    {
                        $classroom = $DB->get_records('local_classroom',array('id'=>$sessionclassroom));
                        $classroom= array_shift($classroom);
                        $name = $classroom->classroom; 
                    }
                }

            }
            if($name)        
                echo $name . get_string('alreadyenrolled','mod_ilt');
        }
    }
}
/*
 * @Author VaibhavG
 * @desc code to getting classroom according to it's location
 * @date 28 Dec 2018
 * End Code
 */