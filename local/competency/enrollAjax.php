<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/local/competency/lib.php');
global $CFG, $DB, $OUTPUT, $PAGE;
$userid = required_param('userid', PARAM_INT);
$subcomptencyid = required_param('subcomptencyid', PARAM_INT);
$subsubcomptencyid = required_param('subsubcomptencyid', PARAM_INT);

// $userid = 3;
// $comptencyid = 1;
// $subcompetencyid = 1;

$plugin = enrol_get_plugin('manual');
$subcompetencycourses = array();
$allcourses  = array();
if($subsubcomptencyid != 0){
	$allcourses = $DB->get_records_sql("SELECT courseid FROM {competency_courses} as cc WHERE cc.competencyid = ?", array($subsubcomptencyid));
} else {
	$allcourses = $DB->get_records_sql("SELECT courseid FROM {competencycat_courses} as cc WHERE cc.competencycatid = ?", array($subcomptencyid));
}

//$allcourses = array_merge($subcompetencycourses, $subsubcompetencycourses);

if(!empty($allcourses)){
	$courArra = array();
	$i =0;
	foreach($allcourses as $courses){
		$courArra[$i] =  $courses->courseid;
		$i++;
	}
	$courArrayfinal = array_unique($courArra);
	foreach ($courArrayfinal as $key => $value) {
		$plugininstance = $DB->get_record("enrol", array("enrol" => 'manual', "status" => 0, "courseid" => $value));
		// Enrol user.
		$plugin->enrol_user($plugininstance, $userid, $plugininstance->roleid, time(), time()+(365 * 24 * 60 * 60));
	}
	echo 'true';
} else {
	echo 'false';
}