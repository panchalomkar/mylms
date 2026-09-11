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

      $course_modules = $DB->get_record_sql("SELECT * FROM {learningpaths} WHERE id = $idlp AND deleted = 0");
         $dataget = array();
         $noofcourse = $DB->get_records('learningpath_courses', array('learningpathid' => $course_modules->id));
         $jsonString = "$course_modules->description";

        $data = json_decode($jsonString, true);
        $textValue = $data['text'];
        $startDateString = DATE('Y-m-d', $course_modules->startdate);
        $endDateString = DATE('Y-m-d', $course_modules->enddate);
        $timeFirst  = strtotime($startDateString);
        $timeSecond = strtotime($endDateString);
        $differenceInSeconds = $timeSecond - $timeFirst;
        $duration = self::secondsToTime($differenceInSeconds);

       $totalcourses = 0;
$completedcourses = 0;

$courses = $DB->get_records('learningpath_courses', [
    'learningpathid' => $course_modules->id
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

$percentage = ($totalcourses > 0) ? ($completedcourses / $totalcourses) * 100 : 0;

      $imagepathurl = $CFG->wwwroot."/local/learningpaths/pluginfile.php?learningpathid={$course_modules->id}&t=";
      if(is_siteadmin()){
        $continuebtn = '<a href="'.$CFG->wwwroot.'/local/learningpaths/view.php?id='.$course_modules->id.'" class="btn btn-primary">Continue</a>';
      }else{
        $continuebtn = '';
      }

      $progressRounded = round($percentage);
      if ($progressRounded >= 100) {
          $buttontext = 'Review';
      } else if ($progressRounded > 0) {
          $buttontext = 'Resume Learning';
      } else {
          $buttontext = 'Start Learning';
      }

      $smartspecific = isset($course_modules->specific) ? $course_modules->specific : '';
      $smartmeasurable = isset($course_modules->measurable) ? $course_modules->measurable : '';
      $smartachievable = isset($course_modules->achievable) ? $course_modules->achievable : '';
      $smartrelevant = isset($course_modules->relevant) ? $course_modules->relevant : '';
      $smarttimebound = isset($course_modules->timebound) ? $course_modules->timebound : '';

      $hassmartgoals = !empty($smartspecific) || !empty($smartmeasurable) || !empty($smartachievable) || !empty($smartrelevant) || !empty($smarttimebound);
// ✅ Find FIRST course (for Start Learning)
$firstcourse = $DB->get_record_sql(
    "SELECT lc.*, c.id as courseid
     FROM {learningpath_courses} lc
     JOIN {course} c ON c.id = lc.courseid
     WHERE lc.learningpathid = ?
     ORDER BY lc.id ASC",
    [$course_modules->id]
);

// ✅ Find LAST IN-PROGRESS course (for Resume)
$resumecourse = null;

$courses = $DB->get_records_sql(
    "SELECT lc.*, c.*
     FROM {learningpath_courses} lc
     JOIN {course} c ON c.id = lc.courseid
     WHERE lc.learningpathid = ?
     ORDER BY lc.id ASC",
    [$course_modules->id]
);

$mainurl = '';
$buttontext = 'Start Learning';

// FIRST COURSE
$firstcourse = reset($courses);

// FIND RESUME COURSE
$resumecourse = null;

foreach ($courses as $c) {
    $course = $DB->get_record('course', ['id' => $c->courseid]);
    if (!$course) continue;

    $progress = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
    $progress = floor($progress ?? 0);

    if ($progress > 0 && $progress < 100) {
        $resumecourse = $c;
        break;
    }
}

// BUTTON LOGIC
if ($percentage == 0 && $firstcourse) {
    $buttontext = 'Start Learning';
    $mainurl = $CFG->wwwroot."/local/incourse/index.php?id=".$firstcourse->courseid;

} else if ($resumecourse) {
    $buttontext = 'Resume Learning';
    $mainurl = $CFG->wwwroot."/local/incourse/index.php?id=".$resumecourse->courseid;

} else {
    $buttontext = 'Review';
    $mainurl = $CFG->wwwroot."/local/incourse/index.php?id=".$firstcourse->courseid;
}
            $dataget[] = array(
                'learningpathname' => $course_modules->name,
                'learningpathimage' => $imagepathurl,
                'discriotion' => $textValue,
                'nocourses' => COUNT($noofcourse),
                'duration' => $duration,
                'progresspercent' => $progressRounded,
                'buttontext' => $buttontext,
                'continuebtn' => $continuebtn,
                'urllink' => $mainurl,
                'backurl' => $CFG->wwwroot."/my",
                'smartspecific' => $smartspecific,
                'smartmeasurable' => $smartmeasurable,
                'smartachievable' => $smartachievable,
                'smartrelevant' => $smartrelevant,
                'smarttimebound' => $smarttimebound,
                'hassmartgoals' => $hassmartgoals,
            );
        
       return $dataget;
    }

public static function get_course_data($idlp){
    global $CFG, $DB, $USER;

    $dataget = [];

    // ✅ IMPORTANT: maintain order
    $course_modules = $DB->get_records_sql(
        "SELECT * FROM {learningpath_courses} 
         WHERE learningpathid = ? 
         ORDER BY id ASC", 
        [$idlp]
    );

    foreach ($course_modules as $keyvalue) {

        // ✅ Get course
        $getcour = $DB->get_record('course', ['id' => $keyvalue->courseid]);
        if (!$getcour) continue;

        // ✅ Progress
        $progressdata = \core_completion\progress::get_course_progress_percentage($getcour, $USER->id);
        $percentage = floor($progressdata ?? 0);

        // ✅ Prerequisite
        $courserepre = $DB->get_record('learningpath_course_prereq', [
            'learningpath_courseid' => $keyvalue->id
        ]);

        $islocked = false;
        $prereqname = '';

        if (!empty($courserepre) && !empty($courserepre->prerequisite)) {

            $precourse = $DB->get_record('course', ['id' => $courserepre->prerequisite]);

            if ($precourse) {
                $prereqname = $precourse->fullname;

                $preprogress = \core_completion\progress::get_course_progress_percentage($precourse, $USER->id);
                $preprogress = floor($preprogress ?? 0);

                if ($preprogress < 100) {
                    $islocked = true;
                }
            }
        }

        // ✅ Duration (safe)
        $differenceInSeconds = ($getcour->enddate > 0 && $getcour->startdate > 0)
            ? ($getcour->enddate - $getcour->startdate)
            : 0;

        $courseduration = self::secondsToTime($differenceInSeconds);

        // ✅ FINAL STATUS LOGIC (MATCH IMAGE UI)
        if ($percentage >= 100) {
            // ✅ DONE (GREEN)
            $coursestatushtml = '<span class="lp-course-status-done"><i class="fa fa-check"></i> Done</span>';
            $coursestatusclass = 'lp-course-done';
            $timelineicon = 'check';
            $timelineiconclass = 'lp-timeline-completed';

            $courselink = 'href="'.$CFG->wwwroot.'/local/incourse/index.php?id='.$getcour->id.'"';
            $localicon = '<i class="fa fa-circle"></i>';

        } else if ($islocked) {
            // ✅ LOCKED (GREY)
            $coursestatushtml = '';
            $coursestatusclass = 'lp-course-locked';
            $timelineicon = 'lock';
            $timelineiconclass = 'lp-timeline-locked';

            $courselink = '';
            $localicon = '<i class="fa fa-lock" title="'.$prereqname.'"></i>';

        } else if ($percentage > 0) {
            // ✅ IN PROGRESS (YELLOW)
$coursestatushtml = '<a href="'.$CFG->wwwroot.'/local/incourse/index.php?id='.$getcour->id.'" class="lp-course-status-continue">Continue &rarr;</a>';            $coursestatusclass = 'lp-course-continue';
            $timelineicon = 'play';
            $timelineiconclass = 'lp-timeline-inprogress';

            $courselink = 'href="'.$CFG->wwwroot.'/local/incourse/index.php?id='.$getcour->id.'"';
            $localicon = '<i class="fa fa-circle"></i>';

        } else {
            // ✅ NOT STARTED BUT AVAILABLE
            $coursestatushtml = '';
            $coursestatusclass = 'lp-course-continue';
            $timelineicon = 'play';
            $timelineiconclass = 'lp-timeline-inprogress';

            $courselink = 'href="'.$CFG->wwwroot.'/local/incourse/index.php?id='.$getcour->id.'"';
            $localicon = '<i class="fa fa-circle"></i>';
        }

        $getimg = self::get_course_image($getcour->id);

        $dataget[] = [
            'coursename' => $getcour->fullname,
            'coursedec' => $getcour->summary,
            'courseimg' => $getimg,
            'progresspercent' => $percentage,
            'courseduration' => $courseduration,
            'required' => $islocked ? "lpdisable" : "lpactive",
            'courselink' => $courselink,
            'localicon' => $localicon,
            'coursestatushtml' => $coursestatushtml,
            'coursestatusclass' => $coursestatusclass,
            'timelineicon' => $timelineicon,
            'timelineiconclass' => $timelineiconclass,
        ];
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
