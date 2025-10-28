<?php


function getliveClasses($userid, $where){
    global $CFG,$DB;
    $whereSql = "";
    if($where == "today"){
        $date = new DateTime("now", core_date::get_server_timezone_object());
        $date->setTime(0, 0, 0);
        $da = $date->getTimestamp();
        $d = date('d',$da);
        $m = date('m',$da);
        $y = date('Y',$da);
        $whereSql  = "WHERE FROM_UNIXTIME(openingtime , '%Y') = $y AND FROM_UNIXTIME(openingtime , '%d') = $d AND FROM_UNIXTIME(openingtime , '%m') = $m";
    }
    $listsession = $DB->get_recordset_sql("SELECT btn.*, cm.id as instanceid  FROM {bigbluebuttonbn} as btn 
        LEFT JOIN {course_modules} as cm ON btn.id = cm.instance 
        $whereSql ORDER BY openingtime");
    $sessionList = array();
    foreach($listsession as $session)
    {
        $participants = json_decode($session->participants);
        foreach($participants as $user){
            if($user->selectionid == $userid || $user->selectionid == 'all'){
                    //$sessionList[] = $session;
            }
        }
        $sessionList[] = $session;
    }
    return $sessionList;
}

function getCousesClasses($courseid = null, $where){
    global $CFG,$DB;
    $whereSql = "";
    if($where == "today"){
        $date = new DateTime("now", core_date::get_server_timezone_object());
        $date->setTime(0, 0, 0);
        $da = $date->getTimestamp();
        $d = date('d',$da);
        $m = date('m',$da);
        $y = date('Y',$da);
        $whereSql  = "WHERE FROM_UNIXTIME(openingtime , '%Y') = $y AND FROM_UNIXTIME(openingtime , '%d') = $d AND FROM_UNIXTIME(openingtime , '%m') = $m";
    } elseif ($where == "upcoming") {
         $date = new DateTime("7 day", core_date::get_server_timezone_object());
        $date->setTime(0, 0, 0);
        $da = $date->getTimestamp();
        $d = date('d',$da);
        $m = date('m',$da);
        $y = date('Y',$da);
        $whereSql  = "WHERE FROM_UNIXTIME(openingtime , '%Y') = $y AND FROM_UNIXTIME(openingtime , '%d') = $d AND FROM_UNIXTIME(openingtime , '%m') = $m";
    } 

    $listsession = $DB->get_recordset_sql("SELECT c.fullname,btn.*, cm.id as instanceid  FROM {bigbluebuttonbn} as btn 
        LEFT JOIN {course} as c ON c.id = btn.course
        LEFT JOIN {course_modules} as cm ON btn.id = cm.instance 
        $whereSql ORDER BY openingtime");
    $sessionList = array();
    foreach($listsession as $session)
    {
       
        $sessionList[] = $session;
    }
    return $sessionList;
}




function getchildliveClasses($userid, $where){
    global $CFG,$DB;
    $whereSql = "";
    if($where == "today"){
        $date = new DateTime("now", core_date::get_server_timezone_object());
        $date->setTime(0, 0, 0);
        $da = $date->getTimestamp();
        $d = date('d',$da);
        $m = date('m',$da);
        $y = date('Y',$da);
        $whereSql  = "WHERE FROM_UNIXTIME(openingtime , '%Y') = $y AND FROM_UNIXTIME(openingtime , '%d') = $d AND FROM_UNIXTIME(openingtime , '%m') = $m";
    } elseif ($where == "upcoming") {
         $date = new DateTime("1 day", core_date::get_server_timezone_object());
        $date->setTime(0, 0, 0);
        $da = $date->getTimestamp();
        $d = date('d',$da);
        $m = date('m',$da);
        $y = date('Y',$da);
        $whereSql  = "WHERE FROM_UNIXTIME(openingtime , '%Y') = $y AND FROM_UNIXTIME(openingtime , '%d') = $d AND FROM_UNIXTIME(openingtime , '%m') = $m";
    } elseif ($where == "all") {
         $date = new DateTime("-2 day", core_date::get_server_timezone_object());
        $date->setTime(0, 0, 0);
        $da = $date->getTimestamp();
        $d = date('d',$da);
        $m = date('m',$da);
        $y = date('Y',$da);
        $whereSql  = "WHERE FROM_UNIXTIME(openingtime , '%Y') = $y AND FROM_UNIXTIME(openingtime , '%d') = $d AND FROM_UNIXTIME(openingtime , '%m') = $m";
    }
    $listsession = $DB->get_recordset_sql("SELECT * FROM {bigbluebuttonbn} $whereSql ORDER BY openingtime");
    $sessionList = array();
    foreach($listsession as $session)
    {
        $participants = json_decode($session->participants);
        foreach($participants as $user){
            if(in_array($user->selectionid, $userid) || $user->selectionid == 'all'){
                   // $sessionList[] = $session;
            }
        }
        $sessionList[] = $session;
    }
    return $sessionList;
}
function getCourseEnrolledCount($userid){
    global $DB;
    
    $usercourses = enrol_get_users_courses($userid);
   
    return $usercourses;
}

function getCourseInprogressCount($userid){
      global $DB;
      //get courses enrolled for this user
        $courses = enrol_get_users_courses($uid);

        //completed courses array
        $completed = array();
        $data = array();
        foreach ($courses as $course) {

            $course = $DB -> get_record('course', array('id' => $course -> id), '*', MUST_EXIST);

            // Load completion data.
            $info = new completion_info($course);
            
            
            
            // Is course complete?
            $coursecomplete = $info -> is_course_complete($uid);
            
            // Has this user completed any criteria?
            $criteriacomplete = $info -> count_course_user_data($uid);
            
            // Load course completion.
            $params = array('userid' => $uid, 'course' => $course -> id);
            $ccompletion = new completion_completion($params);
            
            //is not the current course net started 
            if($criteriacomplete > 0 && !$coursecomplete){
                $data=array();
                
                $data[] = $ccompletion;
            }
        }
print_r($criteriacomplete);
        return $data;
}

function getCourseNotStartCount($userid){
      global $DB;
     //get courses enrolled for this user
        $courses = enrol_get_users_courses($uid);

        //completed courses array
        $completed = array();
        $data = array();
        foreach ($courses as $course) {

            $course = $DB -> get_record('course', array('id' => $course -> id), '*', MUST_EXIST);
           
            $sql = "SELECT 
                DATE_FORMAT(FROM_UNIXTIME(ue.timecreated), '%Y-%m-%d') as user_enrolment_date 
                FROM {course} as c 
                INNER JOIN {enrol} as e ON c.id = e.courseid 
                INNER JOIN {user_enrolments} as ue ON e.id = ue.enrolid 
                INNER JOIN {user} as u ON ue.userid = u.id WHERE c.id = ".$course->id." and u.id = ".$USER->id;

            $CEnrolment = $DB -> get_record_sql($sql);

            // Load completion data.
            $info = new completion_info($course);
            
            
            if(!$info->is_enabled())
            continue ;
            
            // Is course complete?
            $coursecomplete = $info -> is_course_complete($uid);
            
            // Has this user completed any criteria?
            $criteriacomplete = $info -> count_course_user_data($uid);
            
            // Load course completion.
            $params = array('userid' => $uid, 'course' => $course -> id);
            $ccompletion = new completion_completion($params);
            
            //is not the current course net started 
            if(!$criteriacomplete && !$ccompletion->timestarted && !$coursecomplete){
                $data=array();
                
                    $data[]=$CEnrolment;
                
               
            }
        }
        return count($data);
}


function getCourseCompletedCount($userid){
      global $DB;
      $count=$DB->count_records("course_completions",['userid'=>$userid]);
      return $count;
}

function getmygrades($user,$course)
{
    global $CFG,$DB;
    $Mytranscripts = $DB->get_recordset_sql('SELECT gg.finalgrade FROM 
    {course} AS c,
    {grade_items} AS gi,
    {grade_grades} AS gg,
    {user} AS u 
    WHERE u.id =' . $user . ' AND c.id=' . $course . ' AND c.id = gi.courseid AND gg.itemid = gi.id AND u.id = gg.userid AND gi.itemtype="mod"');

    foreach($Mytranscripts as $transcript)
    {
        if($transcript->finalgrade != ""){
            return $transcript->finalgrade;
        } else {
            return '-';
        }
    }
}

function getmyCoursesDrop($uid){
    global $DB;
    
    $mycourse = $DB->get_recordset_sql("SELECT c.id, c.fullname 
            FROM {course} c 
            JOIN {enrol} en ON en.courseid = c.id 
            JOIN {user_enrolments} ue ON ue.enrolid = en.id 
            WHERE ue.userid = $uid");
    $html = "<select class='form-control' id='attend_course'>";
    $html .="<option value='0'>Select Course</option>";
    foreach ($mycourse as $course) {
       $html .="<option value=".$course->id.">".$course->fullname."</option>";
    }
    $html .= "</select>";
    return $html;
}

function get_user_courses_attendances($userid) {
    global $DB;

    $usercourses = enrol_get_users_courses($userid);
    if(empty($usercourses)){
        echo "No enrolled course.";die;
    }

    list($usql, $uparams) = $DB->get_in_or_equal(array_keys($usercourses), SQL_PARAMS_NAMED, 'cid0');

    $sql = "SELECT att.id as attid, att.course as courseid, course.fullname as coursefullname,
                   course.startdate as coursestartdate, att.name as attname, att.grade as attgrade
              FROM {attendance} att
              JOIN {course} course
                   ON att.course = course.id
             WHERE att.course $usql
             group by course.id
          ORDER BY coursefullname ASC, attname ASC";

    $params = array_merge($uparams, array('uid' => $userid));

    return $DB->get_records_sql($sql, $params);
}

// function getcourseProgress($course, $userid = 0){
//     require_once($CFG->libdir . '/completionlib.php');
//      global $USER;

//         // Make sure we continue with a valid userid.
//         if (empty($userid)) {
//             $userid = $USER->id;
//         }

//         $completion = new \completion_info($course);

//         // First, let's make sure completion is enabled.
//         if (!$completion->is_enabled()) {
//             return null;
//         }

//         if (!$completion->is_tracked_user($userid)) {
//             return null;
//         }

//         // Before we check how many modules have been completed see if the course has.
//         if ($completion->is_course_complete($userid)) {
//             return 100;
//         }

//         // Get the number of modules that support completion.
//         $modules = $completion->get_activities();
//         $count = count($modules);
//         if (!$count) {
//             return null;
//         }

//         // Get the number of modules that have been completed.
//         $completed = 0;
//         foreach ($modules as $module) {
//             $data = $completion->get_data($module, true, $userid);
//             $completed += $data->completionstate == COMPLETION_INCOMPLETE ? 0 : 1;
//         }

//         return ($completed / $count) * 100;
// }


function activityprogress($course, $userid) {
    global $CFG, $USER;

    require_once($CFG->dirroot.'/grade/querylib.php');
    require_once($CFG->dirroot.'/grade/lib.php');
   require_once($CFG->libdir . '/completionlib.php');
    $course = get_course($course->id);
    
    $completionstatus = new stdClass();

    // Get course completion data.
    $coursecompletiondata = new completion_info($course);
    
    // Load criteria to display.
    $completions = $coursecompletiondata->get_completions($userid);

    // For aggregating activity completion.
    $activities = array();
    $activitiescompleted = 0;

    // Flag to set if current completion data is inconsistent with what is stored in the database.
    $pendingupdate = false;
   
    // Loop through course criteria.
    foreach ($completions as $completion) {
        $criteria = $completion->get_criteria();
        $iscomplete = $completion->is_complete();

        if (!$pendingupdate && $criteria->is_pending($completion)) {
            $pendingupdate = true;
        }

        // Activities are a special case, so cache them and leave them till last.
        if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
            $activities[$criteria->moduleinstance] = $iscomplete;

            if ($iscomplete) {
                $activitiescompleted++;
            }
            continue;
        }
    }

    $completionpercentage = 0;
    // Aggregate activities.
    if (!empty($activities)) {
        $completionstatus->min = $activitiescompleted;
        $completionstatus->max = count($activities);
        $completionpercentage = intval($completionstatus->min / $completionstatus->max * 100);
    }
    
    $activitiesstatus = new stdClass();

    $activitiescompleted = $activitiescompleted;
    $activities = count($activities);

    
    return $activitiescompleted."/".$activities ;
}
/**
 * Return the final grade for the given course and user
 * This function uses the core clases to get the final grade
 *

 */
function get_final_grade($courseid, $userid)
{
    global $CFG, $DB;

    require_once($CFG->dirroot . '/grade/lib.php');
    require_once($CFG->dirroot . '/grade/report/user/lib.php');

    $course = $DB->get_record('course', array('id' => $courseid));
    $context = context_course::instance($course->id);

    $gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'user', 'courseid'=>$courseid, 'userid'=>$userid));
    $report = new grade_report_user($courseid, $gpr, $context, $userid);
    $report->fill_table();
    $html = $report->print_table(true);

    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    @$dom->loadHtml($html);

    $domTd = $dom->getElementsByTagName('td');

    $r = '';
    foreach ($domTd as $td) {
        if($td->hasAttribute('headers')) {
            if (stripos($td->getAttribute('headers'), 'grade') !== false && stripos($td->getAttribute('headers'), 'lettergrade') === false) {

                $r = $td->textContent;

                if (strtolower($r)==='error' || trim($r) === '-' ) {
                    $r = '-';
                }else{

                }
            }
        }
    }
    return $r;
}

function get_course_news($course, $getsitenews = false) {
    global $USER, $OUTPUT, $COURSE;

    $posttext = '';

    $newsitems = array();

    // If getsitenews is set to true, get site news instead.
    if ($getsitenews) {
        global $SITE;

        if (! $newsforum = forum_get_course_forum($SITE->id, "news")) {
            return $newsitems;
        }
        $cm = get_coursemodule_from_instance('forum', $newsforum->id, $SITE->id, false, MUST_EXIST);

       

        // Optionally get the posts based on when a user either created a post or edited an existing post.
        // Ref: https://bitbucket.org/covuni/moodle-block_news_slider/issues/36/allow-ordering-of-posts-by-last-post-date.
        $sort = '';
        //$sort = forum_get_default_sort_order(true, 'p.modified', 'd', true);
        
        $discussions = forum_get_discussions($cm, $sort, true, null, null, null, null, null, null, null);
    } else {
        // Get course posts.
        $newsforum = forum_get_course_forum($course->id, 'news');
        $cm = get_coursemodule_from_instance('forum', $newsforum->id, $newsforum->course);
       
            // Last parameter set to true to include pinned posts.
            $sort = forum_get_default_sort_order(true, 'p.modified', 'd', true);
        
        $discussions = forum_get_discussions($cm, $sort, true);

       
    }
    $strftimerecent = get_string('strftimerecent');

    // If this is a site page, do not pin course posts.
    $getpinnedposts = true;
    if ( ($COURSE->id <= 1) && ($course->id > 1) ) {
        $getpinnedposts = false;
    }

    foreach ($discussions as $discussion) {
        // Get user profile picture.

        // Build an object that represents the posting user.
        $postuser = new stdClass;
        $postuserfields = explode(',', user_picture::fields());
        $postuser = username_load_fields_from_object($postuser, $discussion, null, $postuserfields);
        $postuser->id = $discussion->userid;
        $postuser->fullname    = $discussion->firstname . ' ' . $discussion->lastname;
        $postuser->profilelink = new moodle_url('/user/view.php', array('id' => $discussion->userid, 'course' => $course->id));

        $userpicture = $OUTPUT->user_picture($postuser, array('courseid' => $course->id, 'size' => 80));

        $newsitems[$discussion->id]['course'] = $course->shortname;
        $newsitems[$discussion->id]['courseid'] = $course->id;
        $newsitems[$discussion->id]['discussion'] = $discussion->discussion;
        $newsitems[$discussion->id]['modified'] = $discussion->modified;
        $newsitems[$discussion->id]['author'] = $discussion->firstname . ' ' . $discussion->lastname;
        $newsitems[$discussion->id]['subject'] = $discussion->subject;
        $newsitems[$discussion->id]['message'] = $discussion->message;
        $newsitems[$discussion->id]['pinned'] = ( ($COURSE->id <= 1) && ($course->id > 1) ) ? "" : $discussion->pinned;
        $newsitems[$discussion->id]['userdate'] = userdate($discussion->modified, $strftimerecent);
        $newsitems[$discussion->id]['userid'] = $discussion->userid;
        $newsitems[$discussion->id]['userpicture'] = $userpicture;

        // Check if message is pinned.
        if ($getpinnedposts == true) {
            if (FORUM_DISCUSSION_PINNED == $discussion->pinned) {
                $newsitems[$discussion->id]['pinned'] = $OUTPUT->pix_icon('i/pinned', get_string('discussionpinned', 'forum'),
                    'mod_forum', array ('style' => ' display: inline-block; vertical-align: middle;'));
            } else {
                $newsitems[$discussion->id]['pinned'] = "";
            }
        }

        $posttext .= $discussion->subject;
        $posttext .= userdate($discussion->modified, $strftimerecent);
        $posttext .= $discussion->message . "\n";
    }
    return $newsitems;
}


function gettotalCoursemod($courseid,$name){
    global $DB;

       $sql = "SELECT COUNT(l.courseid) as cnt 
                FROM mdl_logstore_standard_log AS l 
                JOIN mdl_course_modules AS cm ON cm.id = l.objectid 
                JOIN mdl_modules AS m ON m.id = cm.module 
                WHERE l.eventname = '\\\core\\\\event\\\course_module_created' 
                    AND m.name = :name 
                    AND l.courseid = :courseid";

        $CEnrolment = $DB->get_record_sql($sql,['name'=>$name, 'courseid'=> $courseid]);
        
        return $CEnrolment->cnt;
}

function get_course_news_parent($course){
    global $DB;
     $sql = "SELECT mfp.message,mfp.subject,mfd.name,mfp.created as userdate
                FROM mdl_forum AS mf
                JOIN mdl_forum_discussions AS mfd ON mf.id = mfd.forum 
                JOIN mdl_forum_posts AS mfp ON mfp.discussion = mfd.id
                WHERE  mf.type = 'news' 
                    AND mf.course = :course";

        $CEnrolment = $DB->get_records_sql($sql,['course'=> $course->id]);
        return $CEnrolment;
}

