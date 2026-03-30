<?php

namespace block_learningpathview\output;

defined('MOODLE_INTERNAL') || die();
use core_course_list_element;
use renderable;
use renderer_base;
use templatable;
use core_completion\progress;
class lpviewcourse implements renderable, templatable {
    protected $idlp;

    public function __construct($idlp) {
        $this->idlp = $idlp;
    }

    public function export_for_template(renderer_base $output) {
        global $USER, $OUTPUT, $DB,$CFG;

        $data = self::get_activity_data($this->idlp);
        $getdata = self::get_course_data($this->idlp);
       
        $defaultvariables = [
            'totallearningpath' => $data,
            'totalcourse' => $getdata,
        ];
        return $defaultvariables;
    }

    public static function get_activity_data($idlp){
        global $CFG, $DB, $USER, $PAGE, $SESSION, $OUTPUT;

            // $course_modules = $DB->get_record_sql("SELECT * FROM {learningpaths} l 
            // INNER JOIN {learningpath_users} lu ON l.id = lu.learningpathid WHERE l.id = $idlp");
      $course_modules = $DB->get_record_sql("SELECT * FROM {learningpaths} WHERE id = $idlp AND deleted = 0");
         $dataget = array();
         $noofcourse = $DB->get_records('learningpath_courses', array('learningpathid' => $course_modules->id));
         $jsonString = "$course_modules->description";

         // Decode the JSON string into a PHP associative array
        $data = json_decode($jsonString, true);
         // Access the "text" value
        $textValue = $data['text'];
        $startDateString = DATE('Y-m-d', $course_modules->startdate);
        $endDateString = DATE('Y-m-d', $course_modules->enddate);
        $timeFirst  = strtotime($startDateString);
        $timeSecond = strtotime($endDateString);
        $differenceInSeconds = $timeSecond - $timeFirst;
        $duration = self::secondsToTime($differenceInSeconds);

        $completedlearningpath = $DB->get_records('learningpath_completion', array('learningpathid' => $course_modules->id));
        $totallearningpath = $DB->get_records('learningpaths', array('deleted' => 0));
        $percentage = COUNT($completedlearningpath) / COUNT($totallearningpath) * 100;
        $progress = '<div class="progress" style="width:100px">
        <div class="progress-bar bg-success" role="progressbar" style="width: '.$percentage.'%" aria-valuenow="'.$percentage.'" aria-valuemin="0" aria-valuemax="100">'.round($percentage).'%</div>
      </div>';

      $imagepathurl = $CFG->wwwroot."/local/learningpaths/pluginfile.php?learningpathid={$course_modules->id}&t=";
      if(is_siteadmin()){
        $continuebtn = '<div class="lpcontbtn"><a href="'.$CFG->wwwroot.'/local/learningpaths/view.php?id='.$course_modules->id.'" class="btn btn-primary">Continue</a></div>';
      }else{
        $continuebtn = '';
      }
      
            $dataget[] = array(
                'learningpathname' => $course_modules->name,
                'learningpathimage' => $imagepathurl,
                'discriotion' => $textValue,
                'nocourses' => COUNT($noofcourse),
                'duration' => $duration,
                'progress' => $progress,
                'continuebtn' => $continuebtn,
                'urllink' => $CFG->wwwroot."/blocks/learningpathview/lp_view_course.php?id=$course_modules->id",
            );
        
       return $dataget;
    }

    public static function get_course_data($idlp){
        global $CFG, $DB, $USER, $PAGE, $SESSION, $OUTPUT;

            // $course_modules = $DB->get_record_sql("SELECT * FROM {learningpaths} l 
            // INNER JOIN {learningpath_users} lu ON l.id = lu.learningpathid WHERE l.id = $idlp");
           
            
        $course_modules = $DB->get_records_sql("SELECT * FROM {learningpath_courses} WHERE learningpathid = $idlp");

        foreach ($course_modules as $keyvalue) {
            $courserepre = $DB->get_record_sql("SELECT * FROM {learningpath_course_prereq} WHERE learningpath_courseid = $keyvalue->id");
            $getcourse = $DB->get_record('course', array('id' => $courserepre->prerequisite));
           
            
            $getcour = $DB->get_record('course', array('id' => $keyvalue->courseid));
            $progressdata = \core_completion\progress::get_course_progress_percentage($getcour,$USER->id);
            $percentage = floor($progressdata);
            if (!$courserepre->learningpath_courseid && $percentage != 100) {
                $requred = "lpactive";
                $localicon = '<i class="fa fa-circle" aria-hidden="true"></i>';
                $courselink = 'href="'.$CFG->wwwroot.'/course/view.php?id='.$getcour->id.'"';
            }else{
                $requred = "lpdisable";
                $localicon = '<i class="fa fa-lock" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="'.$getcourse->fullname.'"></i>';
                $courselink = '';
            }
            $getimg = self::get_course_image($getcour->id);

            $progress = '<div class="progress">
            <div class="progress-bar bg-success" role="progressbar" style="width: '.$percentage.'%" aria-valuenow="'.$percentage.'" aria-valuemin="0" aria-valuemax="100">'.$percentage.'%</div>
            </div>';
            $dataget[] = array(
                'coursename' => $getcour->fullname,
                'coursedec' => $getcour->summary,
                'courseimg' => $getimg,
                'courseprogressbar' => $progress,
                'required' => $requred,
                'courselink' => $courselink,
                'localicon' => $localicon
            );
        }
       return $dataget;
    }

    public static function get_course_image($courseid) {
        global $USER, $CFG, $OUTPUT, $DB, $PAGE;
        $course = $DB->get_record('course', array('id' => $courseid));
        require_once($CFG->dirroot.'/course/renderer.php');
           $chelper = new \coursecat_helper();
           if (is_array($course)) {
               $course = (object)$course;
           }
           $course->fullname = strip_tags($chelper->get_course_formatted_name($course));
       $course  = new core_course_list_element($course);
     //  print_object($course);
       foreach ($course->get_course_overviewfiles() as $file) {
           $isimage = $file->is_valid_image();
           $imageurl = file_encode_url(
               "$CFG->wwwroot/pluginfile.php",
               '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
               $file->get_filearea(). $file->get_filepath(). $file->get_filename(),
               !$isimage
           );
       }
   
       if (empty($imageurl)) {
           $imageurl = $OUTPUT->get_generated_image_for_id($courseid);
       }
       return $imageurl;
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
