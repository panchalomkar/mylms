<?php
require_once($CFG->libdir . '/gradelib.php');
require_once $CFG->dirroot . '/grade/report/overview/lib.php';
require_once $CFG->dirroot . '/grade/lib.php';
require_once(__DIR__ . '/../../config.php');
function get_c_images($courseid) {
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
           "$CFG->wwwroot/webservice/pluginfile.php",
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


// function get_user_profile($userid) {
//     global $DB,$OUTPUT;
//     $sql = "SELECT u.*, p.available_points, p.userrank FROM {user_points} p 
//     INNER JOIN {user} u ON u.id = p.userid
//     WHERE u.deleted = 0 AND u.suspended = 0 AND p.userid = $userid ORDER BY  p.available_points";
//     $records = $DB->get_records_sql($sql);
//     $userdetail = [];
//     $achive = [];
//     foreach ($records as $row) {
//         $badges = $DB->get_records('badge_issued', array('userid' => $row->id));
//         $user_object = core_user::get_user($row->id);
//         $person_profile_pic = $OUTPUT->user_picture($user_object,array('link'=>false));

//         $userdetail[] = array(
//             'studentid' => $row->id,
//             'studentname' => $row->firstname.' '.$row->lastname,
//             'studentimage' => $person_profile_pic,
//             'studentemail' => $row->email,
//             'department' => $row->department,
//             'mobileno' => $row->phone1,
//         );

//         $achive[] = array(
//             'userpoints' => $row->available_points,
//             'badgesearn' => COUNT($badges),
//             'userlevel' => $row->userrank,
//         );
//     }
//    return ['userinfo' => $userdetail, 'achievement' => $achive];
// }
function get_user_profile($userid) {
    global $DB, $OUTPUT, $CFG; // $CFG is needed for full URL construction
    $sql = "SELECT u.*, p.available_points, p.userrank
            FROM {user_points} p
            INNER JOIN {user} u ON u.id = p.userid
            WHERE u.deleted = 0 AND u.suspended = 0 AND p.userid = :userid"; // Use :userid for parameter binding
    $user_record = $DB->get_record_sql($sql, ['userid' => $userid]); // Use get_record_sql for a single record

    $userdetail = [];
    $achive = [];

    if ($user_record) {
     //   $user_object = core_user::get_user($user_record->id);
        $person_profile_pic_url = $CFG->wwwroot.'/user/pix.php/'.$user_record->id.'/f2.jpg';//core_user::get_user_picture_url($user_object, true)->out(false);

        $userdetail[] = array(
            'studentid' => $user_record->id,
            'studentname' => $user_record->firstname . ' ' . $user_record->lastname,
            'studentimage' => $person_profile_pic_url, // Use the direct URL here
            'studentemail' => $user_record->email,
            'department' => $user_record->department,
            'mobileno' => $user_record->phone1,
        );

        // Fetch badges for the user
        $badges = $DB->get_records('badge_issued', array('userid' => $user_record->id));

        $achive[] = array(
            'userpoints' => $user_record->available_points,
            'badgesearn' => COUNT($badges),
            'userlevel' => $user_record->userrank,
        );
    }

    return ['userinfo' => $userdetail, 'achievement' => $achive];
}
function get_course_progress($userid) {
	global $DB,$OUTPUT;
   /*$getsqlss = "SELECT ue.*, e.courseid FROM {enrol} e 
    INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid WHERE ue.userid = $userid ORDER BY ue.id DESC"; 
    $courses = $DB->get_records_sql($getsqlss);
$inprogresssql = "SELECT COUNT(DISTINCT c.id) AS in_progress_count
FROM mdl_course c
JOIN mdl_enrol e ON e.courseid = c.id
JOIN mdl_user_enrolments ue ON ue.enrolid = e.id
LEFT JOIN mdl_course_completions cc ON cc.course = c.id AND cc.userid = ue.userid
WHERE ue.userid = $userid
AND cc.timecompleted IS NULL
AND ue.status = 0";
$inprogresscount = $DB->count_records_sql($inprogresssql);

    $completioned = 0;
    $inprogress = 0;
    $totalnotstarted = 0;
    foreach ($courses as $value) {
    $course = $DB->get_record('course', array('id' => $value->courseid));
    // Load completion data.
    $info = new completion_info($course);
          // Is course complete?
      $coursecomplete = $info->is_course_complete($userid);

      // Has this user completed any criteria?
      $criteriacomplete = $info->count_course_user_data($userid);

      // Load course completion.
      $params = array(
          'userid' => $userid,
          'course' => $value->courseid,
      );

      $ccompletion = new completion_completion($params);

      // Save row data.
      $rows = array();

      // Flag to set if current completion data is inconsistent with what is stored in the database.
      $pendingupdate = false;

      // Load criteria to display.
      $completions = $info->get_completions($userid);

      foreach ($completions as $completion) {
        $criteria = $completion->get_criteria();
        if (!$pendingupdate && $criteria->is_pending($completion)) {
            $pendingupdate = true;
        }
    
        $row = array();
        $row['type'] = $criteria->criteriatype;
        $row['title'] = $criteria->get_title();
        $row['status'] = $completion->get_status();
        $row['complete'] = $completion->is_complete();
        $row['timecompleted'] = $completion->timecompleted;
        $row['details'] = $criteria->get_details($completion);
        $rows[] = $row;
    }

    if ($pendingupdate) {
      $pending++;
      } else if ($coursecomplete) {
        $completioned++;
      } else if (!$criteriacomplete && !$ccompletion->timestarted) {
         $totalnotstarted++;
      } else {
        $inprogress++;
      }
   
   }
//echo $completioned ."-". $inprogress ."-".$totalnotstarted;
   $completedper = $completioned /count($courses) *100;
   $inprogressper = $inprogress /count($courses) *100;
   $totalnotstartedper = $totalnotstarted /count($courses) *100;
   $compledsql = "SELECT COUNT(DISTINCT c.id) AS completed_count
FROM mdl_course c
JOIN mdl_enrol e ON e.courseid = c.id
JOIN mdl_user_enrolments ue ON ue.enrolid = e.id
JOIN mdl_course_completions cc ON cc.course = c.id AND cc.userid = ue.userid
WHERE ue.userid = $userid
AND cc.timecompleted IS NOT NULL";
$completedcount = $DB->count_records_sql($compledsql);

$notstartedsql = "SELECT COUNT(DISTINCT c.id) AS not_started_count
FROM mdl_course c
JOIN mdl_enrol e ON e.courseid = c.id
JOIN mdl_user_enrolments ue ON ue.enrolid = e.id
LEFT JOIN mdl_logstore_standard_log l ON l.courseid = c.id AND l.userid = ue.userid
WHERE ue.userid = $userid
AND l.id IS NULL
AND ue.status = 0";
$notstartedcount = $DB->count_records_sql($notstartedsql);

$totalcoursesql = "SELECT COUNT(DISTINCT c.id) AS total_enrolled_courses
FROM mdl_course c
JOIN mdl_enrol e ON e.courseid = c.id
JOIN mdl_user_enrolments ue ON ue.enrolid = e.id
WHERE ue.userid = $userid
AND ue.status = 0";
   $coursecount = $DB->count_records_sql($totalcoursesql);
   $completedper = $completedcount /$coursecount *100;
   $inprogressper = $inprogresscount /$coursecount *100;
   $totalnotstartedper = $notstartedcount /$coursecount *100;*/
   return ['completioned' => 10, 'inprogress' => 15, 'totalnotstarted' => 30];
}


function get_learninpath_data($userid) {
    global $DB, $CFG, $USER;

    define("MAX_LP_PAGE", 10);
    $page = 0; // define default page or get from request
    $offset = MAX_LP_PAGE * $page;

    $dataget = array();
    $datagetcourse = array();

    // Fetch learning paths assigned to the user and are published
    $course_modules = $DB->get_records_sql(
        "SELECT l.* 
         FROM {learningpaths} l 
         INNER JOIN {learningpath_users} lu ON l.id = lu.learningpathid 
         WHERE lu.userid = ? AND l.publish = 1",
        [$userid]
    );

    foreach ($course_modules as $keyalue) {
        // Fetch course mapping for this learning path
        $noofcoursess = $DB->get_record('learningpath_courses', ['learningpathid' => $keyalue->id], '*', IGNORE_MISSING);
        $nocourses = $DB->get_records('learningpath_courses', ['learningpathid' => $keyalue->id]);

        // Decode JSON safely
        $textValue = '';
        if (!empty($keyalue->description)) {
            $data = json_decode($keyalue->description, true);
            $textValue = isset($data['text']) ? $data['text'] : '';
        }

        $startDateString = date('Y-m-d', $keyalue->startdate);
        $endDateString = date('Y-m-d', $keyalue->enddate);
        $duration = secondsToTime(strtotime($endDateString) - strtotime($startDateString));

        $progress = 0;
        if ($noofcoursess && !empty($noofcoursess->courseid)) {
            $course = $DB->get_record('course', ['id' => $noofcoursess->courseid], '*', IGNORE_MISSING);
        }

        $learningpaths = $DB->get_record('learningpaths', ['id' => $keyalue->id], '*', IGNORE_MISSING);
        if ($learningpaths) {
            $result = getCoursesInfo($learningpaths->id, false, $USER->id, MAX_LP_PAGE, $offset, null);
            $lpprogress = newLpprogress($learningpaths->id, $learningpaths->credits, $USER->id);
            $progress = round($lpprogress);
        }

        $imagepathurl = $CFG->wwwroot . "/local/learningpaths/pluginfile.php?learningpathid={$keyalue->id}&t=";

        $dataget[] = array(
            'learningpathid' => $keyalue->id,
            'learningpathname' => $keyalue->name,
            'creadit' => $keyalue->credits,
            'startdate' => $keyalue->startdate,
            'enddate' => $keyalue->enddate,
            'publish' => $keyalue->publish,
            'self_enrollment' => $keyalue->self_enrollment,
            'learningpathimage' => $imagepathurl,
            'discriotion' => $textValue,
            'nocourses' => count($nocourses),
            'duration' => $duration,
            'progress' => $progress,
            'urllink' => $CFG->wwwroot . "/blocks/learningpathview/lp_view_course.php?id={$keyalue->id}",
        );

        // Get all courses in the learning path
        $get_learningpath = $DB->get_records('learningpath_courses', ['learningpathid' => $keyalue->id]);

        foreach ($get_learningpath as $keyval) {
            $courserepre = $DB->get_record('learningpath_course_prereq', ['learningpath_courseid' => $keyval->id], '*', IGNORE_MISSING);
            $prereq_course = null;
            if ($courserepre && !empty($courserepre->prerequisite)) {
                $prereq_course = $DB->get_record('course', ['id' => $courserepre->prerequisite], '*', IGNORE_MISSING);
            }

            $getcour = $DB->get_record('course', ['id' => $keyval->courseid], '*', IGNORE_MISSING);
            if (!$getcour) {
                continue; // Skip if course not found
            }

            $progressdata = \core_completion\progress::get_course_progress_percentage($getcour, $USER->id);
            $percentage = floor($progressdata);

            $getimg = get_c_images($getcour->id);

            $courselink = $CFG->wwwroot . "/course/view.php?id=" . $getcour->id;

            $datagetcourse[] = array(
                'learningpath' => $keyval->learningpathid,
                'coursename' => $getcour->fullname,
                'coursedec' => $getcour->summary,
                'courseimg' => $getimg,
                'courseprogressbar' => $percentage,
                'required' => $keyval->required,
                'courseprerequisite' => $courserepre->prerequisite ?? '',
                'courselink' => $courselink,
                'courseid' => $getcour->id,
            );
        }
    }

    return ['learningpathdata' => $dataget, 'learningpathprogress' => $datagetcourse];
}


function get_user_grade_avg($userid) {
    global $DB,$OUTPUT;

    $getsqlss = "SELECT DISTINCT(e.courseid) FROM {enrol} e 
    INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid  
    WHERE ue.userid = $userid ORDER BY ue.id DESC"; 
    $getcourses = $DB->get_records_sql($getsqlss);
    
    $finalgrade = 0;
    $countgrade = 0;
    $countactivity = [];
    $comsession = [];
    foreach ($getcourses as $keyvaluess) {
    $countactivity += $DB->get_records('course_modules', array('course' => $keyvaluess->courseid));

    $comsession += $DB->get_records_sql("SELECT * FROM {course_modules} cm 
    INNER JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid 
    WHERE cmc.completionstate = 1 AND cmc.userid = $userid AND cm.course = $keyvaluess->courseid");
    // Get course grade_item
    $course_item = grade_item::fetch_course_item($keyvaluess->courseid);

    // Get the stored grade
    $course_grade = new grade_grade(array('itemid'=>$course_item->id, 'userid'=>$userid));
    $course_grade->grade_item =& $course_item;
    $finalgrade += $course_grade->finalgrade;
    $countgrade++;
    }
 

    $average = $finalgrade/$countgrade;
   
   return ['totalnoofactivity' => count($countactivity), 'completedactivity' => count($comsession), 'avaragegrade' => $average];
}

 function secondsToTime($inputSeconds) {
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
