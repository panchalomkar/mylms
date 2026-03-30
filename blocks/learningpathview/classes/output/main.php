<?php

namespace block_learningpathview\output;
global $CFG;
defined('MOODLE_INTERNAL') || die();
require_once "{$CFG->dirroot}/blocks/lpd/lib/lib.php";
use renderable;
use renderer_base;
use templatable;
use completion_info;
use completion_completion;
class main implements renderable, templatable {

    public function export_for_template(renderer_base $output) {
        global $USER, $OUTPUT, $DB,$CFG;

    
     $data = self::get_activity_data();

        $defaultvariables = [
            'totallearningpath' => $data,
            'isempty' => empty($data)  
        ];
        return $defaultvariables;
    }

    public static function get_activity_data(){
        global $CFG, $DB, $USER, $PAGE, $SESSION, $OUTPUT;

        if (!empty($SESSION->currenteditingcompany)) {
            $selectedcompany = $SESSION->currenteditingcompany;
        } else if (!empty($USER->profile->company)) {
            $usercompany = company::by_userid($USER->id);
            $selectedcompany = $usercompany->id;
        } else {
            $selectedcompany = "";
        }
        if ($selectedcompany) {
            $query = " AND companyid = $selectedcompany";
        }

        if(is_siteadmin()){
            $course_modules = $DB->get_records_sql("SELECT * FROM {learningpaths} WHERE deleted = 0 AND publish = 1 $query");
        }else{
            $course_modules = $DB->get_records_sql("SELECT l.* FROM {learningpaths} l 
            INNER JOIN {learningpath_users} lu ON l.id = lu.learningpathid WHERE lu.userid = $USER->id AND l.publish = 1");
        }
        $completioned = 0;
        $inprogress = 0;
        $totalnotstarted = 0;
         $dataget = array();
         foreach ($course_modules as $keyalue) {
         $noofcourse = $DB->get_records('learningpath_courses', array('learningpathid' => $keyalue->id));
        
         
         $noofcoursess = $DB->get_record('learningpath_courses', array('learningpathid' => $keyalue->id));
      
         $jsonString = "$keyalue->description";

         // Decode the JSON string into a PHP associative array
        $data = json_decode($jsonString, true);
         // Access the "text" value
        $textValue = $data['text'];
        $startDateString = DATE('Y-m-d', $keyalue->startdate);
        $endDateString = DATE('Y-m-d', $keyalue->enddate);
        $timeFirst  = strtotime($startDateString);
        $timeSecond = strtotime($endDateString);
        $differenceInSeconds = $timeSecond - $timeFirst;
        $duration = self::secondsToTime($differenceInSeconds);

      
        $course = $DB->get_record('course', ['id' => $noofcoursess->courseid]);
        // $completion = new completion_info($course);
        // $completed = ($completion->is_course_complete($USER->id));
        // $percentage = COUNT($completed) / COUNT($noofcourse) * 100;
        define("MAX_LP_PAGE", 10);
        $offset = MAX_LP_PAGE * $page;

        //kkkkkkkkkk
       $learningpaths = $DB->get_record('learningpaths', ['id' => $keyalue->id]);
       $result = getCoursesInfo($learningpaths->id, false, $USER->id, MAX_LP_PAGE, $offset,null);
    //    $percentage = 0;
    //    foreach ($result as $key => $course) {
    //     $percentage .= $course->progress;
    //    }  
       $lpprogress = newLpprogress($learningpaths->id,$learningpaths->credits,$USER->id);  
       //print_r($result);
      // exit();

       $progress = '<div class="progress" style="width:100px">
        <div class="progress-bar bg-success" role="progressbar" style="width: '.$lpprogress.'%" aria-valuenow="'.$lpprogress.'" aria-valuemin="0" aria-valuemax="100">'.round($lpprogress).'%</div>
        </div>';

       $imagepathurl = $CFG->wwwroot."/local/learningpaths/pluginfile.php?learningpathid={$keyalue->id}&t=";

            $dataget[] = array(
                'learningpathname' => $keyalue->name,
                'learningpathimage' => $imagepathurl,
                'discriotion' => $textValue,
                'nocourses' => COUNT($noofcourse),
                'duration' => $duration,
                'progress' => $progress,
                'urllink' => $CFG->wwwroot."/blocks/learningpathview/lp_view_course.php?id=$keyalue->id",
            );
        }
        
       return $dataget;
    }

    public static function secondsToTime($inputSeconds) {
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;
    
        // Extract days
        $days = floor($inputSeconds / $secondsInADay);
    
        // Extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);
    
        // Extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);
    
        // Extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);
    
        // Format and return
        $timeParts = [];
        $sections = [
            'day' => (int)$days,
            'hour' => (int)$hours,
            'minute' => (int)$minutes,
            'second' => (int)$seconds,
        ];
    
        foreach ($sections as $name => $value){
            if ($value > 0){
                $timeParts[] = $value. ' '.$name.($value == 1 ? '' : 's');
            }
        }
    
        return implode(', ', $timeParts);
    }
}


//            // Load completion data.
//            $info = new completion_info($course);
//            // Is course complete?
//        $coursecomplete = $info->is_course_complete($USER->id);

//        // Has this user completed any criteria?
//        $criteriacomplete = $info->count_course_user_data($USER->id);

//        // Load course completion.
//        $params = array(
//            'userid' => $USER->id,
//            'course' => $noofcoursess->courseid,
//        );

//        $ccompletion = new completion_completion($params);

//        // Save row data.
//        $rows = array();

//        // Flag to set if current completion data is inconsistent with what is stored in the database.
//        $pendingupdate = false;

//        // Load criteria to display.
//        $completions = $info->get_completions($noofcoursess->courseid);

//        foreach ($completions as $completion) {
//        $criteria = $completion->get_criteria();
//        if (!$pendingupdate && $criteria->is_pending($completion)) {
//            $pendingupdate = true;
//        }
   
//        $row = array();
//        $row['type'] = $criteria->criteriatype;
//        $row['title'] = $criteria->get_title();
//        $row['status'] = $completion->get_status();
//        $row['complete'] = $completion->is_complete();
//        $row['timecompleted'] = $completion->timecompleted;
//        $row['details'] = $criteria->get_details($completion);
//        $rows[] = $row;
//    }

//    if ($pendingupdate) {
//        $pending++;
//        } else if ($coursecomplete) {
//        $completioned++;
//        } else if (!$criteriacomplete && !$ccompletion->timestarted) {
//        $totalnotstarted++;
//        } else {
//        $inprogress++;
//        }
//      if ($noofcourse) {
//         $percentage = $completioned / COUNT($noofcourse) * 100;
//      }
       