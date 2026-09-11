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

        $data = json_decode($jsonString, true);
        $textValue = $data['text'];
        $startDateString = DATE('Y-m-d', $keyalue->startdate);
        $endDateString = DATE('Y-m-d', $keyalue->enddate);
        $timeFirst  = strtotime($startDateString);
        $timeSecond = strtotime($endDateString);
        $differenceInSeconds = $timeSecond - $timeFirst;
        $duration = self::secondsToTime($differenceInSeconds);

      
        $course = $DB->get_record('course', ['id' => $noofcoursess->courseid]);
        define("MAX_LP_PAGE", 10);
        $offset = MAX_LP_PAGE * $page;

       $learningpaths = $DB->get_record('learningpaths', ['id' => $keyalue->id]);
       $result = getCoursesInfo($learningpaths->id, false, $USER->id, MAX_LP_PAGE, $offset,null);
     $totalcourses = 0;
$completedcourses = 0;

$courses = $DB->get_records('learningpath_courses', [
    'learningpathid' => $learningpaths->id
]);

foreach ($courses as $c) {
    $course = $DB->get_record('course', ['id' => $c->courseid]);
    if (!$course) continue;

    $progress = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
    $progress = floor($progress ?? 0);

    $totalcourses++;

    if ($progress >= 100) {
        $completedcourses++;
    }
}

$progressRounded = ($totalcourses > 0) 
    ? round(($completedcourses / $totalcourses) * 100) 
    : 0;

       if ($progressRounded >= 100) {
           $statuslabel = 'Completed';
           $statusclass = 'lp-status-completed';
           $buttontext = 'Review';
       } else if ($progressRounded > 0) {
           $statuslabel = 'In Progress';
           $statusclass = 'lp-status-inprogress';
           $buttontext = 'Continue Learning';
       } else {
           $statuslabel = 'Not Started';
           $statusclass = 'lp-status-notstarted';
           $buttontext = 'Start Learning';
       }

       $imagepathurl = $CFG->wwwroot."/local/learningpaths/pluginfile.php?learningpathid={$keyalue->id}&t=";

            $dataget[] = array(
                'learningpathname' => $keyalue->name,
                'learningpathimage' => $imagepathurl,
                'discriotion' => $textValue,
                'nocourses' => COUNT($noofcourse),
                'duration' => $duration,
                'progresspercent' => $progressRounded,
                'statuslabel' => $statuslabel,
                'statusclass' => $statusclass,
                'buttontext' => $buttontext,
                'urllink' => $CFG->wwwroot."/blocks/learningpathview/lp_view_course.php?id=$keyalue->id",
            );
        }
        
       return $dataget;
    }

    public static function secondsToTime($inputSeconds) {
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;
    
        $days = floor($inputSeconds / $secondsInADay);
    
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);
    
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);
    
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);
    
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
