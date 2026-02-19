<?php
require_once(__DIR__ . '/../../config.php');

global $DB;
$cid = optional_param('cid', 0, PARAM_INT);
$cctid = optional_param('cctid', 0, PARAM_INT);
$sqlcourse = "SELECT * FROM {course} as c INNER JOIN {competency_courses} as cc ON c.id = cc.courseid WHERE cc.competencyid =?";
$getcourses = $DB->get_records_sql($sqlcourse, array($cid));

$sqlCatcourse = "SELECT * FROM {course} as c INNER JOIN {competencycat_courses} as ccc ON c.id = ccc.courseid 
 WHERE ccc.isdeleted=0 and ccc.competencycatid = '".$cctid."'";
$getcatcourses = $DB->get_records_sql($sqlCatcourse, array());
$temp =1;
$html =  "<dl>";
$catSql1 = "select * from {competency_category} where id=?";
	$getResult = $DB->get_records_sql($catSql1, array($cctid));
	foreach ($getResult as $key => $val1) {
	$html	.="<dt>$val1->name</dt>";
	}
if(!empty($getcatcourses) && empty($getcourses)){
foreach ($getcatcourses as $key => $course) {
	$html .= "<dd> $course->fullname </dd>";
}
//$html .= "</dl> ";
} else {
	$html .= 'No course found...';
}
$catSql = "select * from {competencies} where id=?";
$getcatResult = $DB->get_records_sql($catSql, array($cid));
foreach ($getcatResult as $key => $val) {
$html	.="<dt style='border-top:1px solid #dee2e6;'>$val->comptencyname</dt>";
}
if(!empty($getcourses) && empty($getcatcourses)){
//$html =  "<dl>";
foreach ($getcourses as $key => $course1) {
	$html .= "<dd> $course1->fullname </dd>";
}
$html .= "</dl> ";
}else {
	$html .= 'No course found...';
}
echo $html;