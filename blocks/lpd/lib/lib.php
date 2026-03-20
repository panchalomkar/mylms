<?php

define('MAX_PAGE_SIZE', 10); 
//require_login();
/**
 * return LearningPathsView
 * @param $option
 * @param $userid
 * @param $page
 */
function displayLearningPathsView($option,$userid,$page=0) {
    global $DB, $SESSION, $USER, $OUTPUT;
    $perpage = 4;
    $url = new moodle_url('/blocks/lpd/block_lpd.php');
    $urlCourse = new moodle_url('/local/elisprogram/widgets/enrolment/ajax.php');
    $urlCourseDetail = new moodle_url('/course/view.php');
    
    $content = html_writer::start_tag('div', array('id' => 'block_lpd_content', 'class' => 'block_lpd_content container-fluid', 'data-children' => '.itemlpd'));
    $content .= getLearningPathsView($option,$userid,$page);
    $content .= html_writer::end_tag('div');
    list($result,$lpcount) = getLearningPathInfo($userid,$page,$perpage);
    $content .= $OUTPUT->paging_bar($lpcount, $page, $perpage, $url);

    $content .= html_writer::tag('input', '', array('id' => 'txtUrlDetail', 'type' => 'hidden', 'value' => "{$url}"));
    $content .= html_writer::tag('input', '', array('id' => 'txtUrlCourse', 'type' => 'hidden', 'value' => "{$urlCourse}"));
    $content .= html_writer::tag('input', '', array('id' => 'txtUrlCourseDetail', 'type' => 'hidden', 'value' => "{$urlCourseDetail}"));

    return $content;
} 

/**
 * return LearningPathsView from Template
 * @param $option
 * @param $userid
 * @param $page
 */

function getLearningPathsView($option,$userid,$page) {
    global $DB, $SESSION, $USER, $OUTPUT;
    
    $content    = "";
    list($result,$lpcount) = getLearningPathInfo($userid,$page,$perpage=4);
        if(count($result) > 0) {
            
            foreach ($result as $index => $value) {
                $startDate = '';
                $idConcat = 'block_lpd_'.$value->id;
                if($value->startdate) {
                    $startDate      = ucfirst(userdate($value->startdate,'%b %d, %Y'));
                }
                $completeDate   = '';
                if($value->enddate) {
                    $completeDate      = ucfirst(userdate($value->enddate,'%b %d, %Y'));
                }

                $companylabel = '';
                 // Added company name label , if company's LP
                if($value->companyid > 0 &&  is_siteadmin() && empty($SESSION->currenteditingcompany)){
                    $companyname = $DB->get_field('company', 'name', array('id' => $value->companyid));
                    $companylabel = '<span class="badge badge-primary companylabel">'.$companyname.'</span>';
                }
                if($value->credits) {
                    $option = 2;
                }
                //$lpprogress = getLpProgress($value->id,$option,$userid);
                $lpprogress = newLpprogress($value->id,$value->credits,$userid);

                $lplist[] = array('startDate' => $startDate,'completeDate' => $completeDate,'valueid'=>$value->id,
                            'idConcat'=>$idConcat,'companylabel' =>$companylabel,'valuelpname'=>$value->lpname,
                            'lpprogress'=>$lpprogress,'valuecredits'=>$value->credits);
                
            }

        } else {
            $lplist= array();
        }
        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'lpdata' => $lplist
      );
    return $OUTPUT->render_from_template('block_lpd/lpview', $template_data);
}

function newLpprogress($learningPath,$creditsvallp,$userid) {
    global $DB;
    $fieldcredits = $DB->get_record('customfield_field',array('shortname'=>'credits'));
    $credits = 0;
    $percent = 0;
   // $customfielddata = get_course_metadata($lpcourse->courseid);

    // if (isset($customfielddata['credits'])) {
    //     $credits = $customfielddata['credits'];
    // }
    $courses_lp = $DB->get_records_sql('select id,courseid from {learningpath_courses} where learningpathid=? AND required != 1',[$learningPath]);
    $required_courses = $DB->get_records_sql('select id,courseid from {learningpath_courses} where learningpathid=? AND required = 1',[$learningPath]); 
    
    // If Required_courses Available
    if(count($required_courses) > 0 && empty($creditsvallp)) {
    $reqcount = 0;
    foreach ($required_courses as $reqlpcourse) {
        $reqprogress = getCourseProgress($reqlpcourse->courseid,$userid);
        if($reqprogress == 100) {
        $reqcount += 1;
        }
    }
    $percent = (100 * $reqcount) / count($required_courses);
    }
    // END Required Courses
    // If Credits Available
    $comcount = 0;
    if($creditsvallp && empty(count($required_courses))) {
    foreach ($courses_lp as $lpcourse) {

        $progress = getCourseProgress($lpcourse->courseid,$userid);
        $creditsvalue = $DB->get_record('customfield_data',array('fieldid'=>$lpcourse->courseid));
      //  $creditsvalue = $DB->get_record('course_info_data',array('courseid'=>$lpcourse->courseid ,'fieldid'=>$fieldcredits->id));
        if($progress == 100) {
        $credits += $creditsvalue->value;
        $comcount += 1;
        }
    }
        if(((100 * $credits) / $creditsvallp) >= 100) {
            $percent = 100;
        } else {
            $percent = (100 * $credits) / $creditsvallp;
        }
    }
    // END Credits
    
    // Both Condition If Credits & Required Courses
    if($creditsvallp && count($required_courses) > 0 ) {
        $reqcount = 0;
        foreach ($required_courses as $reqlpcourse) {
            $reqprogress = getCourseProgress($reqlpcourse->courseid,$userid);
            $creditsvalue = $DB->get_record('course_info_data',array('courseid'=>$reqlpcourse->courseid ,'fieldid'=>$fieldcredits->id));
            if($reqprogress == 100) {
            $reqcount += 1;
            }
            if($creditsvalue->data && $reqprogress == 100) {
            $credits += $credits;
            }
        }
        foreach ($courses_lp as $lpcourse) {
            $progress = getCourseProgress($lpcourse->courseid,$userid);
            $creditsvalue = $DB->get_record('customfield_data',array('fieldid'=>$fieldcredits->id));
            if($progress == 100) {
            $credits += $credits;
            $comcount += 1;
            }
        }
        $reqpercent = (100 * $reqcount) / count($required_courses);
        
        $crepercent = (100 * $credits) / $creditsvallp;
                    if($crepercent >= 100) {
                      $crepercent = 100;
                    }
                    if($reqpercent >= 100) {
                      $reqpercent = 100;
                    }
                    if($reqpercent >= 100) {
                      $percent = $crepercent;
                    } else if($crepercent >= 100) {
                      if($crepercent == 100 && $reqpercent == 0) {
                        $percent = 50;
                      } else {
                        $percent = $reqpercent;
                      }
                    } else {
                      $percent = $reqpercent + $crepercent;
                      if($percent > 100) {
                        if($reqpercent > $crepercent) {
                          $percent = $reqpercent;
                        } else {
                          $percent = $crepercent;
                        }
                      }
                    }
    }
    if(empty($creditsvallp) && empty(count($required_courses))) {
        $percent = getLpProgress($learningPath,$option = 1,$userid);
      }
    // END
    $percent = round($percent);
    return $percent;
}
/**
 * Return the learningpaths information of a user
 */
function getLearningPathInfo($userid,$page,$perpage) {
    global $DB, $OUTPUT, $SESSION;
    $pagination = $company = $whr ='';
    if(!empty($SESSION->currenteditingcompany)){
        $company = $SESSION->currenteditingcompany;
    }else if(\iomad::is_company_user()){
        $company = \iomad::is_company_user();
    }
     $limit = $page*$perpage;
    
    if($company){
        $whr = " AND lp.companyid = $company";
    }
    // if site admin, show all learningpaths
    if(is_siteadmin()){
        $fields = "SELECT lp.id, lp.name as lpname, lp.description, lp.credits, lp.startdate, lp.enddate, lp.companyid";
        $from = " FROM {learningpaths} as lp WHERE lp.deleted = :deleted $whr";
        $learningpaths = $DB->get_records_sql($fields . $from, array('deleted'=>0), $limit, $perpage);
        $countfield = "SELECT COUNT(lp.id) ";
        $lpcount = $DB->count_records_sql($countfield . $from, array('deleted'=>0));

        return array($learningpaths,$lpcount);
    }
  

    $fields = "SELECT lp.id, lp.name as lpname, lp.description, lp.credits, lp.startdate, lp.enddate";
    $from =" FROM {learningpaths} as lp 
        LEFT JOIN {learningpath_users} lpu on lpu.learningpathid = lp.id
        WHERE lp.deleted = :deleted
       ";
    
    // It's for just an user?
    if ($userid) {
        $from .= " AND lpu.userid = {$userid}";
    }

    // Get records and return
    $learningpaths = $DB->get_records_sql($fields . $from, array('deleted'=>0), $limit, $perpage);
    $learningpaths_users = $DB->get_records_sql($fields . $from, array('deleted'=>0));
    $countfield = "SELECT COUNT(lp.id) ";
    $lpcount = $DB->count_records_sql($countfield . $from, array('deleted'=>0));
    
    $lpid = array();
    $not_lpid = '';
    if(count($learningpaths_users) > 0) {
        foreach ($learningpaths_users as $user_data) {
            $lpid[] = $user_data->id;
        }
    }
    $lpid_list = implode(",",$lpid);
    if($lpid_list) {
        $not_lpid = ' AND lp.id NOT IN ('.$lpid_list.')';
    }
    // It's for just an user?
    if ($userid) {
        //Get learning path where user was added by cohort
        $from = "
            FROM {learningpaths} AS lp
            JOIN {learningpath_cohorts} lpu ON lpu.learningpathid = lp.id
            JOIN {cohort_members} members ON lpu.cohortid = members.cohortid
            WHERE lp.deleted = :deleted
            ";

        // It's for just an user?
        if ($userid) {
            $from .= " AND members.userid = $userid $not_lpid";
        }

        $learningpathcohorts = $DB->get_records_sql($fields . $from, array('deleted'=>0));
        //$countfield = "SELECT COUNT(lp.id) ";
        //$lpcount = $DB->count_records_sql($countfield . $from, array('deleted'=>0));
            $learningpaths = array_merge($learningpaths, $learningpathcohorts);
    }
    return array($learningpaths,$lpcount);
}

/**
 * return LearningPathsView Details from Template
 * @param $learningPath
 * @param $userid
 * @param $page
 */

function displayLearningPathsViewDetail($learningPath, $userid, $page = 0, $lpid_selected = 0, $pageTypeLocalAjax = false) {

global $CFG ,$PAGE, $OUTPUT, $DB;
$inoverview = ($PAGE->pagetype == 'local-learningpaths-view' || $pageTypeLocalAjax) ? true : false ;
define("MAX_LP_PAGE", 10);

// Require completion library
require_once "{$CFG->dirroot}/lib/completionlib.php";

$content    = "";
$html = "";
$params = [];
if ($lpid_selected != $learningPath && !$inoverview) {
    $page = 0;
}

$offset = MAX_LP_PAGE * $page;
$params["lp_id"] = $learningPath;
$params["id"] = $learningPath;
$baseurl = new moodle_url('', $params);
// Get learningpath courses and count
$result = getCoursesInfo($learningPath, false, $userid, MAX_LP_PAGE, $offset,$inoverview);
$countCourses = count(getCoursesInfo($learningPath,true,$userid));

// If learning path has courses
if(count($result) > 0) {
        $has_data = array('inoverview'=>$inoverview);

        // Flag to count number of courses
        $count = 1;                
        foreach ($result as $key => $course) {
          $startdate = '';
            $element = '' ;
            
            // Which course is it showing?
            if ($count == 1 && count($result) > 1) {
                $element = 'first' ;
            }

            if ($count == count( $result )) {
                $element = 'last' ;
            }

            if ($count > 1 && ($count < count($result))) {
                $element = 'middle' ;
            }

            $prerequisites = getCoursePrerequisite($course->learningpath_course);
            $prereq = getPrereqHTMl($prerequisites);

                // When course has prerequisites, calculate completion of those
                if ($prereq && (int) $course->progress <> 100) {
                    
                    // Flag to count courses completed
                    $req_completed = 0;
                    foreach ($prerequisites as $pre) {
                        $course_prereq = $DB->get_record('course', ['id' => $pre->id]);
                        $completion = new completion_info($course_prereq);
                        $req_completed = ($completion->is_course_complete($userid)) ? $req_completed + 1 : $req_completed;
                    }
                    
                    // Show icon depending if all prerequisites were completed or not
                    if ($req_completed == count($prerequisites)) {
                        $attrs = array (
                            'title' => $prereq,
                            'data-color' => '#94b7c3',
                            'class' => 'wid wid-icon-course-inprogress'
                        );
                        $iconclass= '<i data-color = "#94b7c3" title = "'.$prereq.'" 
                        class="wid wid-icon-course-inprogress"></i>';
                    } else {
                        $attrs = array (
                            'data-color' => '#94b7c3',
                            'title' => $prereq,
                            'class' => 'red wid wid-bloki icon_block'
                        );
                        $iconclass= '<i data-color = "#94b7c3" title = "'.$prereq.'"
                        class="red wid wid-bloki icon_block"></i>';
                    }
                } elseif ((int) $course->progress == 100) {
                    if ($prereq) {
                        $attrs = array (
                            'title' => $prereq,
                            'data-color' => '#94b7c3',
                            'class' => 'wid wid-icon-checked icon_ok'
                        );
                        $iconclass= '<i data-color = "#94b7c3" title = "'.$prereq.'"
                        class="wid wid-icon-checked icon_ok"></i>';
                    } else {
                        $iconclass= '<i data-color = "#94b7c3" class="wid wid-icon-checked icon_ok"></i>';
                    }
                } else {
                    $iconclass= '<i data-color = "#94b7c3" class="men men-icon-phcircle"></i>';
                }

                // Get the course name and validate if is longer than 40 chars.
                $coursename = (strlen($course->name) >= 40)?substr($course->name, 0, 40).'...':$course->name;
                   
                    $startdate = ucfirst(userdate($course->startdate,'%b %d, %Y'));
                    if ($course->enddate){
                      $userenddate = ucfirst(userdate($course->enddate,'%b %d, %Y'));
                    }else{
                        $userenddate = get_string('not_set', 'block_lpd');
                    }
                    $certificatelink = getCourseCertificateSelected($course->id,$userid);
        
                    if ($certificatelink) {
                        $iconcerti = $certificatelink;
                    } else {
                        $iconcerti = '<i class="not-issued wid wid-icon-phcertificate edw-icon fa fa-certificate"></i>'; //fa fa-cloud-download
                    }
            $count ++ ;
            if($inoverview){
                $pagination= $OUTPUT->paging_bar($countCourses, $page, MAX_PAGE_SIZE, $baseurl);
            }  
            $lplist[] = array('canvas' => $canvas,'icon'=>$iconclass,'coursename'=>$coursename,'courseid'=>$course->id,
          'courselink'=>$courselink,'startdate'=>$startdate,'courseenddate'=>$course->enddate,'sprogress'=>round($course->progress),
          'progress'=>$course->progress,'coursecredits'=>$course->credits,'iconcerti'=>$iconcerti,'inoverview'=>$inoverview,'element'=>$element,
          'userenddate'=>$userenddate
        );
        }
    //condition added to avoid double pagination display in block after opening LP
    if($pageTypeLocalAjax){
        $html .= $OUTPUT->paging_bar($countCourses, $page, MAX_PAGE_SIZE, $baseurl);
    }    
    
} else {
    $lplist =  array();
}
$template_data = array(
  'site_url' => $CFG->wwwroot,
  'lpdata' => $lplist,
  'has_data'=> $has_data,
  'pagination'=>$pagination
);
  $html .= $OUTPUT->render_from_template('block_lpd/lpview_details', $template_data);
  return $html;
}

/**
 * return Course Info by LP id
 * @param $learningPath
 * @param $getcoursesonly
 * @param $userid
 */

function getCoursesInfo($learningPath,$getcoursesonly = false,$userid, $limit = null, $offset = null,$inoverview = null) {
    
        global $DB,$PAGE;
        $course_active = "";
        if($PAGE->pagetype != 'local-learningpaths-view') {
            $course_active = " AND lpc.course_active = 1 ";
        }
        $arrayCD  = $courseObj   = array();
        $sqlLimit = "";
        $param = array();
        if(!is_null($limit) && !is_null($offset) && $limit > 0){
            $limit = (int)$limit;
            $offset = (int)$offset;
            $sqlLimit = " LIMIT {$limit} OFFSET {$offset}";
        }
        /* get course properties credits and enddate */

        $fieldcredits = $DB->get_record('customfield_field',array('shortname'=>'credits'));
        $sql = "
            SELECT  c.id,
                    c.fullname,
                    c.startdate ,
                    c.enddate ,
                    c.timecreated,
                    lpc.id as learningpath_course
            FROM 
                {learningpath_courses} as lpc , {course} as c 
            WHERE 
            lpc.courseid = c.id AND
            lpc.learningpathid = ".$learningPath." $course_active ORDER BY position ASC";
    
        /* get LP courses */
        if($inoverview) {
            $lpcourses = $DB->get_records_sql($sql,$param,$offset,$limit);
        } else {
            $lpcourses = $DB->get_records_sql($sql,$param);
        }
        if($getcoursesonly && $lpcourses )
        {
            return $lpcourses; 
        }
        if( count($lpcourses) > 0 ){
            foreach ($lpcourses as $key => $course) {
            
            $coursestd = new stdClass();

            $creditsvalue = '';
            $enddatevalue = '';
          //  $customfielddata = get_course_metadata($course->id);
          //  if (isset($customfielddata['credits'])) {
           //     $credits = $customfielddata['credits'];
          //  }
            /*if course properties credits and enddate exist and they have data then search specific course value */
            if($fieldcredits) {
               $creditsvalue = $DB->get_record('customfield_data',array('fieldid'=>$fieldcredits->id,'instanceid'=>$course->id));
            }
            $coursestd->id = $course->id; 
            $coursestd->name = $course->fullname; 
            if(empty($course->startdate)){
                $coursestd->startdate = $course->timecreated;
            }else{
                $coursestd->startdate = $course->startdate;
            }
            $coursestd->learningpath_course = $course->learningpath_course;
            
            if($creditsvalue->value == null || empty($creditsvalue->value)) {
                $coursestd->credits = 0; 
            } else {
                $coursestd->credits = $creditsvalue->value;
            }

            if($enddatevalue) {
                $coursestd->enddate = $enddatevalue->data; 
            } else {
                $coursestd->enddate = $course->enddate; 
            } 
            $progress = getCourseProgress($course->id,$userid);
            $coursestd->progress = $progress ;
            $courseObj[$course->id] = $coursestd ;

        }
    }   

    return $courseObj;
}

/**
 * return Course Progress
 * @param $courseid
 * @param $userid
 */
function getCourseProgress($courseid,$userid)
{
    global $DB, $USER;
    $total_progress = 0 ;

    $course = $DB->get_record('course',array('id'=>$courseid));

    if(empty($course)) {
        return;
    }
    //Get mod info
    $modinfo = get_fast_modinfo($course);
    //Get the completion info of the course
    $info = new completion_info($course);
    $complete = $info->is_course_complete($userid);
    if($complete){
        $total_progress = 100;
    } else{
        //check if the current user is enrolled in the current course
        $my_courses = enrol_get_my_courses();
        $is_enrollled = false;
        if (isset($my_courses[$course->id]->id))
            $is_enrollled = true;

        //If eht completion info is enabled for the site and for the course
        if (completion_info::is_enabled_for_site() && $info->is_enabled()) {
            //Get the completions for current user
            $completions = $info->get_completions($userid);
             // For aggregating activity completion.
            $activities = array();
            $activities_complete = 0;
             // Loop through course criteria.
            foreach ($completions as $completion) {
                //If is a videofile get the progress of the user in the video
                $criteria = $completion->get_criteria();
                $complete = $completion->is_complete();
                // Activities are a special case, so cache them and leave them till last.
                if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                    $activities[$criteria->moduleinstance] = $complete;
                    if ($complete) {
                        $activities_complete++;
                    } else if($completion->get_criteria()->module == 'videofile' ){
                    }
                }
                else if($complete)
                {
                    $activities_complete++;
                }
            }

            if ( count($activities) > 0 ) {
                $total_progress = ($activities_complete / count($activities));
                $total_progress = $total_progress * 100;
            } else {
               $total_progress = 0 ; 
            }
        }
    }

    return $total_progress ;
}
/**
 * return LP Course Progress 
 * @param $learningpathid
 * @param $userid
 */

function getLPProgressCourseCompleted($learningpathid,$userid)
{   
    global $DB;
  
    $courses = getCoursesInfo($learningpathid,true,$userid);
    $coursesCompleted = array();
    $countCourses = 0;
    $percentage = 0;
    // Check if there is courses
    if( count($courses) > 0 ){
        foreach ($courses as $key => $coure) {
            $courseprogress = getCourseProgress($coure->id,$userid) ;
            $required = $DB->get_record('learningpath_courses',array('learningpathid'=>$learningpathid,'courseid' => $coure->id));
            if ($required) {
                $countCourses ++;
            }
            if( (int)$courseprogress == 100 && $required) {
              $coursesCompleted[$coure->id] = 1;    
            } 
        }
    }
    if( count($coursesCompleted) > 0 ) {
        $lpprogress =  ( ( count($coursesCompleted) * 100 ) / $countCourses ) ;
    } else {
        $lpprogress = 0 ;
    }
    $completed = $DB->get_record('learningpath_completion', ['userid' => $userid,'learningpathid'=>$learningpathid]);
    if($completed) {
        $percentage = 100;
    } else {
        $percentage = $lpprogress;
    } 
    return round($percentage) ;

}

function getLPProgressCreditsEarned($learningpathid,$userid)
{
    global $USER ,$DB ;
    $courses = getCoursesInfo($learningpathid,false,$userid);
    $lpinfo = $DB->get_record('learningpaths',array('id'=>$learningpathid));
    $coursesCompleted = 0 ;
    foreach ($courses as $key => $coure) {
        $courseprogress = getCourseProgress($coure->id,$userid) ;
        if( (int)$courseprogress == 100 )
        {
            $coursesCompleted += $coure->credits;    
        } 
    }

    if( $coursesCompleted > 0 ) {
        $lpprogress =  ( ( (int)$coursesCompleted * 100 ) / (int)$lpinfo->credits ) ;
    } else {
        $lpprogress = 0 ;
    }
    return $lpprogress ;
}

function getLpProgress($learningpathid,$option,$userid) 
{
    switch ($option) {
        case 1:
           return getLPProgressCourseCompleted($learningpathid,$userid) ;
            break; 
        case 2:
           return getLPProgressCreditsEarned($learningpathid,$userid) ;
            break;
    }
}

/**
* Get course prerequisites using learning path course id
*/
function getCoursePrerequisite($learningpath_courseid)
{
    global $DB;
    $sql = "SELECT {course}.id as id, {course}.fullname as fullname
            FROM {learningpath_course_prereq}
            INNER JOIN {course} ON {course}.id = {learningpath_course_prereq}.prerequisite
            WHERE {learningpath_course_prereq}.learningpath_courseid = ? {$company_courses_sql} ORDER BY {learningpath_course_prereq}.prerequisite";
    $prerequisites = $DB->get_records_sql($sql, array('courseid'=> $learningpath_courseid));
    return $prerequisites ;
}

function getPrereqHTMl($courses)
{
    $content = '';
    if(count($courses) > 0)
    {    
        $content = get_string('prereq', 'block_lpd'); 
        $html=  strip_tags($content, array('class'=>'tooltip_title'));
        foreach ($courses as $key => $course) {
            $content.= strip_tags(" • ".($course->fullname));
        }
    }

    return $content ;
}

function getCourseCertificateSelected($courseid ,$userid) 
{
    global $DB,$CFG ;   
    
    /* get course properties credits and enddate */
    $fieldcertificate = $DB->get_record('course_info_field',array('shortname'=>'certificate'));
    if ($fieldcertificate == null) {
        $fieldcertificate = 0;
    }
    
    $data = $DB->get_record('course_info_data',array('courseid'=>$courseid , 'fieldid' => $fieldcertificate->id ));
    
    $output  ='' ;
    if($data)
    {
        // Load completion library
        require_once "{$CFG->dirroot}/lib/completionlib.php";
        $course = $DB->get_record('course', ['id' => $courseid]);
        $completion = new completion_info($course);
        //if ($completion->is_course_complete($userid)) {
            $getissued = getcertissuedlpd($userid, $courseid ,$data->data);
            if($getissued->id != '') {
                $activity_completed = $DB->get_record('course_modules_completion',array('userid'=>$userid , 'coursemoduleid' => $getissued->id,'completionstate' => 1 ));
                if($activity_completed->id) {
                    $output = '
                    <a href="' . $CFG->wwwroot . '/mod/certificate/view.php?id=' . $getissued->id . '" target="_blank" aria-label="">
                        <i class="issued wid wid-icon-phcertificate edw-icon fa fa-certificate"></i>
                    </a>';  
                }
            }
        //}
    }
    return $output ; 
}

/**
 * Issued certificate.
 */
function getcertissuedlpd($userid, $course, $activity) {
    
    global $CFG, $DB;
    $certificatemod = $DB->get_field('modules', 'id', array('name' => 'certificate'));
    $getissued = $DB->get_record_sql('
        SELECT cm.id 
        FROM {course_modules} AS cm,
        {certificate_issues} AS ci,
        {certificate} AS c 
        WHERE cm.course='.$course.'
        AND cm.course=c.course
        AND cm.module = '.$certificatemod
    );

    if($getissued) {
        return $getissued;
    } else {
        return null;
    }
}