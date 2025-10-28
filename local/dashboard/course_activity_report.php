<?php

 
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot .'/lib/blocklib.php'); 

global $PAGE,$USER,$CFG,$DB;


//require_once(dirname(__FILE__).'/locallib.php');

require_login();
$PAGE->set_url('/local/paradiso_reports/index.php');
$PAGE->set_title('Reports');
$PAGE->set_pagelayout('reportnifty');




$PAGE->navbar->add(get_string('reports','Report'));
echo $OUTPUT->header();
$allCourses = $DB->get_records_sql("SELECT c.id , 
	c.fullname ,
	COUNT(c.fullname) as totalActivities, 
	sum(case when (if(concat(cm.module,cm.instance) IN( SELECT concat(cm.module,cm.instance) FROM {course_modules_completion} cmc INNER JOIN {course_modules} cm ON cm.id=cmc.coursemoduleid ),1, 0))=1 then 1 ELSE 0 END) AS Completed 
	FROM {user} u 
	INNER JOIN {user_enrolments} ue ON ue.userid = u.id 
	INNER JOIN {enrol} e ON e.id = ue.enrolid 
	INNER JOIN {course} c ON e.courseid = c.id 
	INNER JOIN {course_modules} cm ON c.id=cm.course 
	INNER JOIN {modules} module ON module.id=cm.module 
	INNER JOIN {course_sections} cs ON cs.id=cm.section 
	WHERE cs.section !=0 
	GROUP BY c.id, u.id
");

$html = "";
$html .= "<table class='table table-bordered'>";
$html .= "<tr>";
	$html .= "<th>Course</th>";
	$html .= "<th>Total Activity</th>";
	$html .= "<th>Total Student</th>";
	$html .= "<th>Total Activity Completed  Student</th>";
	$html .= "<th>Total Activity Incomplete Student</th>";
$html .= "</tr>";
foreach($allCourses as $course){
	$sqltotal = "SELECT COUNT(DISTINCT u.id)
                           FROM {user} u
                           JOIN {user_enrolments} ue ON (ue.userid = u.id  AND ue.enrolid $instancessql)
                           JOIN {enrol} e ON (e.id = ue.enrolid) WHERE e.courseid = ?";
             $totalusers = (int)$DB->count_records_sql($sqltotal, array('courseid' => $course->id));
	$html .= "<tr>";
		$html .= "<td>".$course->fullname."</td>";
		$html .= "<td>".$course->totalactivities."</td>";
		$html .= "<td>".$totalusers."</td>";
		
		$html .= "<td>".$course->completed."</td>";	
		$html .= "<td>".($totalusers - $course->completed)."</td>";	
			
	$html .= "</tr>";
}
$html .="</table>";

echo $html;

echo $OUTPUT->footer();