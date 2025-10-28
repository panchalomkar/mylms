<?php

defined('MOODLE_INTERNAL') || die();

function delete_fields_cohort($deletefields) {
    global $DB;

    foreach ($deletefields as $deletefield) {
		$consult = $DB->get_records('cohort_info_data', array('fieldid' => $deletefield->id));
		if($consult)
		{
    		$DB->delete_records('cohort_info_data', array('id' => $deletefield->id));
		}
    	$DB->delete_records('cohort_info_field', array('id' => $deletefield->id));
    }
    
    return true;
}

function delete_fields_course($deletefields) {
    global $DB;

    foreach ($deletefields as $deletefield) {
		$consult = $DB->get_records('course_info_data', array('fieldid' => $deletefield->id));
		if($consult)
		{
    		$DB->delete_records('course_info_data', array('fieldid' => $deletefield->id));
		}
    	$DB->delete_records('course_info_field', array('id' => $deletefield->id));
    }
    
    return true;
}

function delete_fields_user($deletefields) {
    global $DB;

    foreach ($deletefields as $deletefield) {
		$consult = $DB->get_records('user_info_data', array('fieldid' => $deletefield->id));
		if($consult)
		{
    		$DB->delete_records('user_info_data', array('fieldid' => $deletefield->id));
		}
    	$DB->delete_records('user_info_field', array('id' => $deletefield->id));
    }
    
    return true;
}

function delete_fields_lp($deletefields) {
    global $DB;

    foreach ($deletefields as $deletefield) {
		$consult = $DB->get_records('lp_info_data', array('fieldid' => $deletefield->id));
		if($consult)
		{
    		$DB->delete_records('lp_info_data', array('fieldid' => $deletefield->id));
		}
    	$DB->delete_records('lp_info_field', array('id' => $deletefield->id));
    }
    
    return true;
}