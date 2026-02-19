<?php

function xmldb_local_lms_reports_install() {
	global $CFG, $DB;
	$result = true;
	$dbman = $DB->get_manager();
	
	// Migrate block instances.
	//$oldrecord = $DB->get_record('block', array('name' => 'rlip'), 'id');
	
	// Insert default report types 
	$transaction = $DB->start_delegated_transaction();
	$sql = "INSERT INTO {local_lms_report_type} SET `name`=?, `order`=? ";
	$params = array('courses','1');
	$DB->execute($sql, $params);
	//$type1 = new stdClass(); 
	//$type1->name         = 'courses'; 
	//$type1->order         = '1';
	
	$sql = "INSERT INTO {local_lms_report_type} SET `name`=?, `order`=? ";
	$params = array('learning_path','2');
	$DB->execute($sql, $params);

	$sql = "INSERT INTO {local_lms_report_type} SET `name`=?, `order`=? ";
	$params = array('users','3');
	$DB->execute($sql, $params);

	$sql = "INSERT INTO {local_lms_report_type} SET `name`=?, `order`=? ";
	$params = array('gamification','4');
	$DB->execute($sql, $params);

	$sql = "INSERT INTO {local_lms_report_type} SET `name`=?, `order`=? ";
	$params = array('system','5');
	$DB->execute($sql, $params);

	$sql = "INSERT INTO {local_lms_report_type} SET `name`=?, `order`=? ";
	$params = array('ecommerce','6');
	$DB->execute($sql, $params);

	$sql = "INSERT INTO {local_lms_report_type} SET `name`=?, `order`=? ";
	$params = array('performance_management','7');
	$DB->execute($sql, $params);
	
	
	$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=0 ";
	$params = array('course_completion','course_completion_desc','/report/completion/index.php?fromreports=1&course=0','1','1');
	$DB->execute($sql, $params);
	//$report1 = new stdClass(); 
	//$report1->name			= 'course_completion'; 
	//$report1->url			= '/report/completion/index.php?fromreports=1&course=0';
	//$report1->idtype		= '5';
	//$report1->favorite		= '0';
	//$report1->order			= '1';
	
	$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=0 ";
	$params = array('course_dedication','course_dedication_desc','/assets/jumps/dedication_instance.php?courseid=0','1','2');
	$DB->execute($sql, $params);
	
	$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=0 ";
	$params = array('detailed_activity_log','detailed_activity_log_desc','/report/log/index.php?chooselog=1&showusers=1&showcourses=1&id=1&user=&date=1413072000&modid=&modaction=&logformat=showashtml','5','3');
	$DB->execute($sql, $params);
		
	$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
	$params = array('live_user_traffic','live_user_traffic_desc','/report/loglive/index.php?id=1&inpopup=1','5','5');
	$DB->execute($sql, $params);
	
	$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
	$params = array('user_login_stats','user_login_stats_desc','/report/overviewstats/index.php','5','6');
	$DB->execute($sql, $params);
	
    
	$transaction->allow_commit();
	
	$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND (`name`=? || `name`=?) AND `type`=? ";
	$params= array('1', 'Users who completed a course', 'Course overview', 'sql');
	$records = $DB->get_record_sql($sql, $params ); 
	
	if(!empty($records->id)){
		$sql = "DELETE FROM {block_configurable_reports} WHERE `id`=? ";
		$params = array( $records->id );
		$DB->execute($sql, $params);
	}

	$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
	$params= array('1', 'Course overview', 'courseoverview');
	$records = $DB->get_record_sql($sql, $params );
	if(empty($records->id)){ 
		unset($record1);
		$record1 = new stdClass();
		$record1->courseid = '1'; 
		$record1->ownerid = '2'; 
		$record1->visible = '1'; 
		$record1->name = 'Course overview'; 
		$record1->summary = 'Course overview<br>'; 
		$record1->type = 'courseoverview'; 
		$record1->pagination = '10'; 
		$record1->components = 'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:17:{i:0;a:5:{s:2:"id";s:15:"T1pRkohCK1D6nFR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:8:"lastname";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:1;a:5:{s:2:"id";s:15:"N7pPQ4C2lWa1fZ2";s:8:"formdata";O:6:"object":3:{s:6:"column";s:5:"email";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:2;a:5:{s:2:"id";s:15:"oBJaalrApfyTU6N";s:8:"formdata";O:6:"object":3:{s:6:"column";s:4:"role";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:4:"Role";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:3;a:5:{s:2:"id";s:15:"Ibf1pkc2Ekv1gIa";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"date-enrolled";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Date+Enrolled";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+Enrolled";}i:4;a:5:{s:2:"id";s:15:"iQ8bGHflamD0g0F";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"date-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Date+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+Completed";}i:5;a:5:{s:2:"id";s:15:"zKDV08lHkVY3iFZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:16:"course-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"%25+Course+progress";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"%25+Course+progress";}i:6;a:5:{s:2:"id";s:15:"Qmd7XBOCIMqgoIL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:11:"grade-final";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:11:"Grade+Final";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+Final";}i:7;a:5:{s:2:"id";s:15:"kF763S7L6t9j51l";s:8:"formdata";O:6:"object":3:{s:6:"column";s:15:"time-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:15:"Time+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+Dedication";}i:8;a:5:{s:2:"id";s:15:"EkwgU8PubxHf46a";s:8:"formdata";O:6:"object":3:{s:6:"column";s:10:"coursename";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:6:"Course";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:9;a:5:{s:2:"id";s:15:"CE0ysTXQYVrnmpZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:20:"activities-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:20:"Activities+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:10;a:5:{s:2:"id";s:15:"bPyBEcSP0omVH5V";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activities-assigned";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activities+Assigned";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+Assigned";}i:11;a:5:{s:2:"id";s:15:"J7oBw9WfFaEdKBR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-name";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:12;a:5:{s:2:"id";s:15:"SEGd6ULJ7095mnL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-type";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Type";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:13;a:5:{s:2:"id";s:15:"7EHM9DojrR7DYuq";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"activity-grade";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Activity+Grade";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:14;a:5:{s:2:"id";s:15:"WfT90uBlFQxV0Oh";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Dedication";}i:15;a:5:{s:2:"id";s:15:"nQNTbU9V8A6lZck";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-completion";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Completion";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Completion";}i:16;a:5:{s:2:"id";s:15:"joLOByXp3wuFf4R";s:8:"formdata";O:6:"object":3:{s:6:"column";s:24:"activity-completion-date";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:24:"Activity+Completion+Date";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+Completion+Date";}}}s:7:"filters";a:1:{s:8:"elements";a:8:{i:3;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"EkwgU8PubxHf46a";s:5:"label";s:6:"Course";s:4:"name";s:6:"course";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:4:"LIKE";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:4;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Role";}i:5;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":10:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:20:"Enrolment+date+start";}i:6;a:5:{s:2:"id";s:15:"HxaOgnG3qcY3xFs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:16:"enrolmentdateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:18:"Enrolment+date+end";}i:7;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":10:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:21:"Completion+date+start";}i:8;a:5:{s:2:"id";s:15:"3xtHbzq4qxYjFCn";s:8:"formdata";O:6:"object":10:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:19:"Completion+date+end";}i:9;a:5:{s:2:"id";s:15:"EHwxrWfin3XF5O6";s:8:"formdata";O:6:"object":10:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:23:"Completed%2CUncompleted";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:22:"course-completed-state";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:16:"Course+completed";}i:10;a:5:{s:2:"id";s:15:"WYx4WFJuFkjyuAg";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"J7oBw9WfFaEdKBR";s:5:"label";s:13:"Activity+Name";s:4:"name";s:12:"activityname";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:13:"Activity+Name";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}'; 
		$record1->export = ''; 
		$record1->jsordering = '1'; 
		$record1->global = '1'; 
		$record1->cron = '0'; 
		
		unset($lastinsertid);
		$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false); 
		
		unset($sql,$params,$record);
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Course overview', 'courseoverview');
		$record = $DB->get_record_sql($sql, $params );
		$lastinsertid =$record->id;
		
		unset($record);
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? || `name`=? ";
		$params= array('users-who-completed-a-course','course-overview');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
			$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid);
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `idcr`=? ";
			$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid,$record->id);
			$DB->execute($sql, $params);
		}
	}else{
		$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
		$params = array(
			'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:17:{i:0;a:5:{s:2:"id";s:15:"T1pRkohCK1D6nFR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:8:"lastname";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:1;a:5:{s:2:"id";s:15:"N7pPQ4C2lWa1fZ2";s:8:"formdata";O:6:"object":3:{s:6:"column";s:5:"email";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:2;a:5:{s:2:"id";s:15:"oBJaalrApfyTU6N";s:8:"formdata";O:6:"object":3:{s:6:"column";s:4:"role";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:4:"Role";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:3;a:5:{s:2:"id";s:15:"Ibf1pkc2Ekv1gIa";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"date-enrolled";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Date+Enrolled";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+Enrolled";}i:4;a:5:{s:2:"id";s:15:"iQ8bGHflamD0g0F";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"date-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Date+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+Completed";}i:5;a:5:{s:2:"id";s:15:"zKDV08lHkVY3iFZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:16:"course-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"%25+Course+progress";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"%25+Course+progress";}i:6;a:5:{s:2:"id";s:15:"Qmd7XBOCIMqgoIL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:11:"grade-final";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:11:"Grade+Final";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+Final";}i:7;a:5:{s:2:"id";s:15:"kF763S7L6t9j51l";s:8:"formdata";O:6:"object":3:{s:6:"column";s:15:"time-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:15:"Time+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+Dedication";}i:8;a:5:{s:2:"id";s:15:"EkwgU8PubxHf46a";s:8:"formdata";O:6:"object":3:{s:6:"column";s:10:"coursename";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:6:"Course";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:9;a:5:{s:2:"id";s:15:"CE0ysTXQYVrnmpZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:20:"activities-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:20:"Activities+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:10;a:5:{s:2:"id";s:15:"bPyBEcSP0omVH5V";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activities-assigned";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activities+Assigned";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+Assigned";}i:11;a:5:{s:2:"id";s:15:"J7oBw9WfFaEdKBR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-name";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:12;a:5:{s:2:"id";s:15:"SEGd6ULJ7095mnL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-type";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Type";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:13;a:5:{s:2:"id";s:15:"7EHM9DojrR7DYuq";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"activity-grade";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Activity+Grade";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:14;a:5:{s:2:"id";s:15:"WfT90uBlFQxV0Oh";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Dedication";}i:15;a:5:{s:2:"id";s:15:"nQNTbU9V8A6lZck";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-completion";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Completion";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Completion";}i:16;a:5:{s:2:"id";s:15:"joLOByXp3wuFf4R";s:8:"formdata";O:6:"object":3:{s:6:"column";s:24:"activity-completion-date";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:24:"Activity+Completion+Date";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+Completion+Date";}}}s:7:"filters";a:1:{s:8:"elements";a:8:{i:3;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"EkwgU8PubxHf46a";s:5:"label";s:6:"Course";s:4:"name";s:6:"course";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:4:"LIKE";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:4;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Role";}i:5;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":10:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:20:"Enrolment+date+start";}i:6;a:5:{s:2:"id";s:15:"HxaOgnG3qcY3xFs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:16:"enrolmentdateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:18:"Enrolment+date+end";}i:7;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":10:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:21:"Completion+date+start";}i:8;a:5:{s:2:"id";s:15:"3xtHbzq4qxYjFCn";s:8:"formdata";O:6:"object":10:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:19:"Completion+date+end";}i:9;a:5:{s:2:"id";s:15:"EHwxrWfin3XF5O6";s:8:"formdata";O:6:"object":10:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:23:"Completed%2CUncompleted";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:22:"course-completed-state";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:16:"Course+completed";}i:10;a:5:{s:2:"id";s:15:"WYx4WFJuFkjyuAg";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"J7oBw9WfFaEdKBR";s:5:"label";s:13:"Activity+Name";s:4:"name";s:12:"activityname";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:13:"Activity+Name";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}',
			$records->id
		);
		$DB->execute($sql, $params);
		
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? || `name`=? ";
		$params= array('users-who-completed-a-course','course-overview');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
			$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id,$records->id);
			$DB->execute($sql, $params);
		}
	}
	
	unset($sql,$params,$records);
	$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
	$params= array('1', 'Quiz overview', 'sql');
	$records = $DB->get_record_sql($sql, $params );
	if(empty($records->id)){
		unset($record1);
		$record1 = new stdClass();
		$record1->courseid = '1'; 
		$record1->ownerid = '2'; 
		$record1->visible = '1'; 
		$record1->name = 'Quiz overview'; 
		$record1->summary = '<p><span class=\"task_content_text\">Quiz overview</span></p>'; 
		$record1->type = 'sql'; 
		$record1->pagination = '0'; 
		$record1->components = 'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:1359:"SELECT%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Fview.php%3Fid%3D%27%2C+cm.id%2C%27%22+%3E%27%2Cq.name%2C%27%3C%2Fa%3E%27%29+AS+%27Quiz%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C%27%22+%3E%27%2Cc.shortname%2C%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C%0D%0A+s.name%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Doverview%26id%3D%27%2C+cm.id%2C%27%22+%3EGrades+report%3C%2Fa%3E%27%29+AS+%27Grades+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Dresponses%26id%3D%27%2C+cm.id%2C%27%22+%3EResponses+report%3C%2Fa%3E%27%29+AS+%27Responses+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Dstatistics%26id%3D%27%2C+cm.id%2C%27%22+%3EStatistics%3C%2Fa%3E%27%29+AS+%27Statistics%27%0D%0AFROM%0D%0A+%7Bcourse%7D+as+c%0D%0A+JOIN+%7Bcourse_modules%7D+AS+cm+ON+c.id+%3D+cm.course%0D%0A+JOIN+%7Bmodules%7D+AS+m+ON+m.id+%3D+%28cm.module%29%0D%0A+JOIN+%7Bcourse_sections%7D+AS+s+ON+%28cm.section+%3D+s.id%29%0D%0A+JOIN+%7Bquiz%7D+AS+q+ON+%28q.id+%3D+cm.instance%29%0D%0AWHERE%0D%0A+m.name+%3D+%22quiz%22+%25%25FILTER_CUSTOM%25%25+%0D%0A";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:7:"filters";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"1kFLkiVFgEQyOS5";s:8:"formdata";O:6:"object":10:{s:5:"label";s:7:"Courses";s:4:"name";s:8:"courseid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:7:"Courses";}i:1;a:5:{s:2:"id";s:15:"hHnegYSYm0crvFs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Quiz";s:4:"name";s:6:"quizid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Bquiz%7D";s:5:"field";s:9:"id%2Cname";s:5:"where";s:0:"";s:7:"depends";s:17:"courseid%2Ccourse";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"q.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Quiz";}}}}'; 
		$record1->export = ''; 
		$record1->jsordering = '1'; 
		$record1->global = '1'; 
		$record1->lastexecutiontime = '50'; 
		$record1->cron = '0'; 
		unset($lastinsertid);
		$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);
			
		unset($sql,$params,$record);
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Quiz overview', 'sql');
		$record = $DB->get_record_sql($sql, $params );
		$lastinsertid =$record->id;
			
		unset($sql,$params,$record);
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('quiz-overview');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			unset($sql,$params);
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
			$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$lastinsertid);
			$DB->execute($sql, $params);
		}else{
			unset($sql,$params);
			$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
			$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$record->id);
			$DB->execute($sql, $params);
		}
	}else{
		unset($sql,$params);
		$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
		$params = array(
			'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:1359:"SELECT%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Fview.php%3Fid%3D%27%2C+cm.id%2C%27%22+%3E%27%2Cq.name%2C%27%3C%2Fa%3E%27%29+AS+%27Quiz%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C%27%22+%3E%27%2Cc.shortname%2C%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C%0D%0A+s.name%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Doverview%26id%3D%27%2C+cm.id%2C%27%22+%3EGrades+report%3C%2Fa%3E%27%29+AS+%27Grades+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Dresponses%26id%3D%27%2C+cm.id%2C%27%22+%3EResponses+report%3C%2Fa%3E%27%29+AS+%27Responses+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Dstatistics%26id%3D%27%2C+cm.id%2C%27%22+%3EStatistics%3C%2Fa%3E%27%29+AS+%27Statistics%27%0D%0AFROM%0D%0A+%7Bcourse%7D+as+c%0D%0A+JOIN+%7Bcourse_modules%7D+AS+cm+ON+c.id+%3D+cm.course%0D%0A+JOIN+%7Bmodules%7D+AS+m+ON+m.id+%3D+%28cm.module%29%0D%0A+JOIN+%7Bcourse_sections%7D+AS+s+ON+%28cm.section+%3D+s.id%29%0D%0A+JOIN+%7Bquiz%7D+AS+q+ON+%28q.id+%3D+cm.instance%29%0D%0AWHERE%0D%0A+m.name+%3D+%22quiz%22+%25%25FILTER_CUSTOM%25%25+%0D%0A";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:7:"filters";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"1kFLkiVFgEQyOS5";s:8:"formdata";O:6:"object":10:{s:5:"label";s:7:"Courses";s:4:"name";s:8:"courseid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:7:"Courses";}i:1;a:5:{s:2:"id";s:15:"hHnegYSYm0crvFs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Quiz";s:4:"name";s:6:"quizid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Bquiz%7D";s:5:"field";s:9:"id%2Cname";s:5:"where";s:0:"";s:7:"depends";s:17:"courseid%2Ccourse";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"q.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Quiz";}}}}',
			$records->id
		);
		$DB->execute($sql, $params);
		
		unset($sql,$params,$record);
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('quiz-overview');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			unset($sql,$params);
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
			$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$records->id);
			$DB->execute($sql, $params);
		}else{
			unset($sql,$params);
			$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
			$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$records->id);
			$DB->execute($sql, $params);
		}
	}
	unset($sql,$params,$records);
	$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
	$params= array('1', 'Most viewed courses', 'sql');
	$records = $DB->get_record_sql($sql, $params );
	if(empty($records->id)){ 
		unset($record1);
		$record1 = new stdClass();
		$record1->courseid = '1'; 
		$record1->ownerid = '2'; 
		$record1->visible = '1'; 
		$record1->name = 'Most viewed courses'; 
		$record1->summary = '<p><span class=\"task_content_text\">Most viewed courses</span></p>'; 
		$record1->type = 'sql'; 
		$record1->pagination = '0'; 
		$record1->components = 'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:564:"SELECT+%0D%0A%09COUNT%281%29+%27Views%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C+%0D%0A%09cc.name+%27Category%27+%0D%0AFROM+%7Bcourse%7D+c%0D%0AINNER+JOIN+%7Blog%7D+l+ON+%28l.course%3Dc.id%29+%0D%0AINNER+JOIN+%7Bcourse_categories%7D+cc+ON+%28c.category%3Dcc.id%29%0D%0AWHERE+%0D%0A%09c.visible%3D1+AND+c.id%3C%3E1+%0D%0A%25%25FILTER_CUSTOM%25%25+%0D%0AGROUP+BY%0D%0A%09c.id%0D%0AORDER+BY%0D%0A%09%60Views%60+DESC";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:4:"plot";a:1:{s:8:"elements";a:0:{}}}'; 
		$record1->export = ''; 
		$record1->jsordering = '1'; 
		$record1->global = '1'; 
		$record1->lastexecutiontime = '50'; 
		$record1->cron = '0'; 
		unset($lastinsertid);
		$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);
			
		unset($sql,$params,$record);
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Most viewed courses', 'sql');
		$record = $DB->get_record_sql($sql, $params );
		$lastinsertid =$record->id;
			
		unset($sql,$params,$record);
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('most-viewed-courses');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			unset($sql,$params);
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
			$params = array('most-viewed-courses','/blocks/configurable_reports/viewreport.php?id=','1','10',$lastinsertid);
			$DB->execute($sql, $params);
		}else{
			unset($sql,$params);
			$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
			$params = array('most-viewed-courses','/blocks/configurable_reports/viewreport.php?id=','1','10',$record->id);
			$DB->execute($sql, $params);
		}
	}else{
		unset($sql,$params);
		$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
		$params = array(
			'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:564:"SELECT+%0D%0A%09COUNT%281%29+%27Views%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C+%0D%0A%09cc.name+%27Category%27+%0D%0AFROM+%7Bcourse%7D+c%0D%0AINNER+JOIN+%7Blog%7D+l+ON+%28l.course%3Dc.id%29+%0D%0AINNER+JOIN+%7Bcourse_categories%7D+cc+ON+%28c.category%3Dcc.id%29%0D%0AWHERE+%0D%0A%09c.visible%3D1+AND+c.id%3C%3E1+%0D%0A%25%25FILTER_CUSTOM%25%25+%0D%0AGROUP+BY%0D%0A%09c.id%0D%0AORDER+BY%0D%0A%09%60Views%60+DESC";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:4:"plot";a:1:{s:8:"elements";a:0:{}}}',
			$records->id
		);
		$DB->execute($sql, $params);
		
		unset($sql,$params,$record);
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('most-viewed-courses');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			unset($sql,$params);
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
			$params = array('most-viewed-courses','/blocks/configurable_reports/viewreport.php?id=','1','10',$records->id);
			$DB->execute($sql, $params);
		}else{
			unset($sql,$params);
			$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
			$params = array('most-viewed-courses','/blocks/configurable_reports/viewreport.php?id=','1','10',$records->id);
			$DB->execute($sql, $params);
		}
	}
	
	
	unset($sql,$params,$records);
	$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
	$params= array('1', 'Most popular courses by enrollment', 'sql');
	$records = $DB->get_record_sql($sql, $params );
	if(empty($records->id)){ 
		unset($record1);
		$record1 = new stdClass();
		$record1->courseid = '1'; 
		$record1->ownerid = '2'; 
		$record1->visible = '1'; 
		$record1->name = 'Most popular courses by enrollment'; 
		$record1->summary = '<p><span class=\"task_content_text\">Most popular courses by enrollment</span></p>'; 
		$record1->type = 'sql'; 
		$record1->pagination = '0'; 
		$record1->components = 'a:1:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:554:"SELECT+%0D%0ACONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C%0D%0ACOUNT%28ue.id%29+AS+%27Enrolled%27%0D%0AFROM+%7Bcourse%7D+c+%0D%0AJOIN+%7Benrol%7D+en+ON+%28en.courseid+%3D+c.id%29%0D%0AJOIN+%7Buser_enrolments%7D+ue+ON+%28ue.enrolid+%3D+en.id%29%0D%0AJOIN+%7Buser%7D+u+ON+%28u.id%3Due.userid%29%0D%0AWHERE%0D%0A%09u.deleted%3D0%0D%0A%25%25FILTER_CUSTOM%25%25+%0D%0AGROUP+BY+c.id%0D%0AORDER+BY++%0D%0A+%60Enrolled%60+DESC";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}}'; 
		$record1->export = ''; 
		$record1->jsordering = '1'; 
		$record1->global = '1'; 
		$record1->lastexecutiontime = '50'; 
		$record1->cron = '0'; 
		unset($lastinsertid);
		$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);
			
		unset($sql,$params,$record);
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Most popular courses by enrollment', 'sql');
		$record = $DB->get_record_sql($sql, $params );
		$lastinsertid =$record->id;
			
		unset($sql,$params,$record);
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('most-popular-courses-by-enrollment');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			unset($sql,$params);
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
			$params = array('most-popular-courses-by-enrollment','/blocks/configurable_reports/viewreport.php?id=','1','10',$lastinsertid);
			$DB->execute($sql, $params);
		}else{
			unset($sql,$params);
			$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
			$params = array('most-popular-courses-by-enrollment','/blocks/configurable_reports/viewreport.php?id=','1','10',$record->id);
			$DB->execute($sql, $params);
		}
	}else{
		unset($sql,$params);
		$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
		$params = array(
			'a:1:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:554:"SELECT+%0D%0ACONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C%0D%0ACOUNT%28ue.id%29+AS+%27Enrolled%27%0D%0AFROM+%7Bcourse%7D+c+%0D%0AJOIN+%7Benrol%7D+en+ON+%28en.courseid+%3D+c.id%29%0D%0AJOIN+%7Buser_enrolments%7D+ue+ON+%28ue.enrolid+%3D+en.id%29%0D%0AJOIN+%7Buser%7D+u+ON+%28u.id%3Due.userid%29%0D%0AWHERE%0D%0A%09u.deleted%3D0%0D%0A%25%25FILTER_CUSTOM%25%25+%0D%0AGROUP+BY+c.id%0D%0AORDER+BY++%0D%0A+%60Enrolled%60+DESC";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}}',
			$records->id
		);
		$DB->execute($sql, $params);
		
		unset($sql,$params,$record);
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('most-popular-courses-by-enrollment');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			unset($sql,$params);
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
			$params = array('most-popular-courses-by-enrollment','/blocks/configurable_reports/viewreport.php?id=','1','10',$records->id);
			$DB->execute($sql, $params);
		}else{
			unset($sql,$params);
			$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
			$params = array('most-popular-courses-by-enrollment','/blocks/configurable_reports/viewreport.php?id=','1','10',$records->id);
			$DB->execute($sql, $params);
		}
	}
	
	
	unset($sql,$params,$records);
	$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
	$params= array('1', 'Video report', 'sql');
	$records = $DB->get_record_sql($sql, $params );
	if(empty($records->id)){ 
		unset($records);
		$record1 = new stdClass();
		$record1->courseid = '1'; 
		$record1->ownerid = '2'; 
		$record1->visible = '1'; 
		$record1->name = 'Video Report'; 
		$record1->summary = '<p><span class=\"task_content_text\">Video report</span></p>'; 
		$record1->type = 'sql'; 
		$record1->pagination = '0'; 
		$record1->components = 'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:835:"SELECT+%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C+%0D%0A%09v.name+%27Video+name%27%2C%0D%0A%09CONCAT%28u.firstname%2C%27+%27%2Cu.lastname%29+%27User%27%2C%0D%0A%09%2F%2A--+v.id%2C%0D%0A%09va.percentage%2C+--%2A%2F%0D%0A+%09CONCAT%28+MAX%28va.percentage%29%2C%27%25%27%29+%27Percentage%27%0D%0AFROM+%7Bvideo_attempts%7D+va%0D%0AJOIN+%7Buser%7D+u+ON+%28u.id%3Dva.userid%29%0D%0AJOIN+%7Bcourse_modules%7D+cm+ON+%28cm.id%3Dva.cmid%29%0D%0AJOIN+%7Bvideofile%7D+v+ON+%28v.id%3Dcm.instance%29%0D%0AJOIN+%7Bcourse%7D+c+ON+%28c.id%3Dv.course%29%0D%0AWHERE%0D%0A%09u.deleted%3D0%0D%0A%25%25FILTER_CUSTOM%25%25%0D%0AGROUP+BY+v.id%2Cu.id%0D%0AORDER+BY+c.fullname%2Cv.name%2C+u.firstname%2C+u.lastname";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:7:"filters";a:1:{s:8:"elements";a:5:{i:0;a:5:{s:2:"id";s:15:"oEzfAaeQYnLD981";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"User";s:4:"name";s:4:"user";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Buser%7D";s:5:"field";s:14:"id%2Cfirstname";s:5:"where";s:11:"deleted%3D0";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"u.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"User";}i:1;a:5:{s:2:"id";s:15:"ueCQFjQV9xj9Nvo";s:8:"formdata";O:6:"object":10:{s:5:"label";s:5:"Video";s:4:"name";s:7:"videoid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:15:"%7Bvideofile%7D";s:5:"field";s:9:"id%2Cname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"v.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:5:"Video";}i:2;a:5:{s:2:"id";s:15:"AcOQTkAzNPylufs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:6:"Course";s:4:"name";s:8:"courseid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:5:"where";s:11:"visible%3D1";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:3;a:5:{s:2:"id";s:15:"c7sIljXwtUchiza";s:8:"formdata";O:6:"object":10:{s:5:"label";s:16:"Percentage+start";s:4:"name";s:15:"percentagestart";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:43:"10%2C20%2C30%2C40%2C50%2C60%2C70%2C80%2C100";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"va.percentage";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:16:"Percentage+start";}i:4;a:5:{s:2:"id";s:15:"EJGMN8yTYuPd9AM";s:8:"formdata";O:6:"object":10:{s:5:"label";s:14:"Percentage+end";s:4:"name";s:13:"percentageend";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:43:"10%2C20%2C30%2C40%2C50%2C60%2C70%2C80%2C100";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"va.percentage";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:14:"Percentage+end";}}}}'; 
		$record1->export = ''; 
		$record1->jsordering = '1'; 
		$record1->global = '0'; 
		$record1->lastexecutiontime = '50'; 
		$record1->cron = '0'; 
		unset($lastinsertid);
		$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);
			
		unset($sql,$params,$record);
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Video report', 'sql');
		$record = $DB->get_record_sql($sql, $params );
		$lastinsertid =$record->id;
			
		unset($sql,$params,$record);
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('video-report');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			unset($sql,$params);
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
			$params = array('video-report','/blocks/configurable_reports/viewreport.php?id=','1','11',$lastinsertid);
			$DB->execute($sql, $params);
		}else{
			unset($sql,$params);
			$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
			$params = array('video-report','/blocks/configurable_reports/viewreport.php?id=','1','11',$record->id);
			$DB->execute($sql, $params);
		}
	}else{
		unset($sql,$params);
		$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
		$params = array(
			'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:835:"SELECT+%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C+%0D%0A%09v.name+%27Video+name%27%2C%0D%0A%09CONCAT%28u.firstname%2C%27+%27%2Cu.lastname%29+%27User%27%2C%0D%0A%09%2F%2A--+v.id%2C%0D%0A%09va.percentage%2C+--%2A%2F%0D%0A+%09CONCAT%28+MAX%28va.percentage%29%2C%27%25%27%29+%27Percentage%27%0D%0AFROM+%7Bvideo_attempts%7D+va%0D%0AJOIN+%7Buser%7D+u+ON+%28u.id%3Dva.userid%29%0D%0AJOIN+%7Bcourse_modules%7D+cm+ON+%28cm.id%3Dva.cmid%29%0D%0AJOIN+%7Bvideofile%7D+v+ON+%28v.id%3Dcm.instance%29%0D%0AJOIN+%7Bcourse%7D+c+ON+%28c.id%3Dv.course%29%0D%0AWHERE%0D%0A%09u.deleted%3D0%0D%0A%25%25FILTER_CUSTOM%25%25%0D%0AGROUP+BY+v.id%2Cu.id%0D%0AORDER+BY+c.fullname%2Cv.name%2C+u.firstname%2C+u.lastname";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:7:"filters";a:1:{s:8:"elements";a:5:{i:0;a:5:{s:2:"id";s:15:"oEzfAaeQYnLD981";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"User";s:4:"name";s:4:"user";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Buser%7D";s:5:"field";s:14:"id%2Cfirstname";s:5:"where";s:11:"deleted%3D0";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"u.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"User";}i:1;a:5:{s:2:"id";s:15:"ueCQFjQV9xj9Nvo";s:8:"formdata";O:6:"object":10:{s:5:"label";s:5:"Video";s:4:"name";s:7:"videoid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:15:"%7Bvideofile%7D";s:5:"field";s:9:"id%2Cname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"v.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:5:"Video";}i:2;a:5:{s:2:"id";s:15:"AcOQTkAzNPylufs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:6:"Course";s:4:"name";s:8:"courseid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:5:"where";s:11:"visible%3D1";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:3;a:5:{s:2:"id";s:15:"c7sIljXwtUchiza";s:8:"formdata";O:6:"object":10:{s:5:"label";s:16:"Percentage+start";s:4:"name";s:15:"percentagestart";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:43:"10%2C20%2C30%2C40%2C50%2C60%2C70%2C80%2C100";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"va.percentage";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:16:"Percentage+start";}i:4;a:5:{s:2:"id";s:15:"EJGMN8yTYuPd9AM";s:8:"formdata";O:6:"object":10:{s:5:"label";s:14:"Percentage+end";s:4:"name";s:13:"percentageend";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:43:"10%2C20%2C30%2C40%2C50%2C60%2C70%2C80%2C100";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"va.percentage";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:14:"Percentage+end";}}}}',
			$records->id
		);
		$DB->execute($sql, $params);
		
		unset($sql,$params,$record);
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('video-report');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			unset($sql,$params);
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
			$params = array('video-report','/blocks/configurable_reports/viewreport.php?id=','1','11',$records->id);
			$DB->execute($sql, $params);
		}else{
			unset($sql,$params);
			$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
			$params = array('video-report','/blocks/configurable_reports/viewreport.php?id=','1','11',$records->id);
			$DB->execute($sql, $params);
		}
	}
	
	$reporttableui = get_config('block_configurable_reports', 'reporttableui'); 
	if($reporttableui!='html'){
		set_config('reporttableui', 'html', 'block_configurable_reports');
	}

    return $result;
}
