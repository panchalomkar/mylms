<?php
defined('MOODLE_INTERNAL') || die();

//require_once($CFG->dirroot.'/local/datahub/lib.php');

function xmldb_local_lms_reports_upgrade($oldversion = 0) {
    global $DB, $CFG;

    $result = true;

    $dbman = $DB->get_manager();


    if ($oldversion < 2015052602) {

		$r = $DB->get_record('local_lms_reports', array('name'=>'course_dedication'), 'id') ;
		if(!empty($r->id)){
			$sql = "UPDATE {local_lms_reports} SET `idtype`=? WHERE `id`=? ";
			$params = array('1',$r->id);
			$DB->execute($sql, $params);
		}
		unset($r);
		$r = $DB->get_record('local_lms_reports', array('name'=>'detailed_activity_log'), 'id') ;
		if(!empty($r->id)){
			$sql = "UPDATE {local_lms_reports} SET `idtype`=? WHERE `id`=? ";
			$params = array('1',$r->id);
			$DB->execute($sql, $params);
		}
		unset($r);
		//echo $sql;exit();die();
		//upgrade_plugin_savepoint(true, 2015052602, 'local', 'lms_reports');
    }

    if ($oldversion < 2015052701) {
		$table = new xmldb_table('local_lms_reports');
        $field = new xmldb_field('iduser', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL,null,'1','idtype' );
        // Conditionally launch add field senderid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		//upgrade_plugin_savepoint(true, 2015052701, 'local', 'lms_reports');
    }

    if ($oldversion < 2015052902) {
		$table = new xmldb_table('local_lms_reports');
        $field = new xmldb_field('summary', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'name');
        // Conditionally launch add field senderid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('idcr', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null,null,'0','url' );
        // Conditionally launch add field senderid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

		$sql = "UPDATE {local_lms_reports} SET summary= CONCAT(`name`,'_desc') ";
		$params = array();
		$DB->execute($sql, $params);

		//upgrade_plugin_savepoint(true, 2015052902, 'local', 'lms_reports');
    }
    if ($oldversion < 2015061215) {
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('course_completion');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=0 ";
			$params = array('course_completion','course_completion_desc','/report/completion/index.php?fromreports=1&course=0','1','1');
			$DB->execute($sql, $params);
		}
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('course_dedication');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=0 ";
			$params = array('course_dedication','course_dedication_desc','/assets/jumps/dedication_instance.php?courseid=0','1','2');
			$DB->execute($sql, $params);
		}
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('detailed_activity_log');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=0 ";
			$params = array('detailed_activity_log','detailed_activity_log_desc','/report/log/index.php?chooselog=1&showusers=1&showcourses=1&id=1&user=&date=1413072000&modid=&modaction=&logformat=showashtml','5','3');
			$DB->execute($sql, $params);
		}
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('live_user_traffic');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=0 ";
			$params = array('live_user_traffic','live_user_traffic_desc','/report/loglive/index.php?id=1&inpopup=1','5','5');
			$DB->execute($sql, $params);
		}
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('user_login_stats');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=0 ";
			$params = array('user_login_stats','user_login_stats_desc','/report/overviewstats/index.php','5','6');
			$DB->execute($sql, $params);
		}


		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Users who completed a course', 'sql');
		$records = $DB->get_record_sql($sql, $params );

		if(!empty($records->id)){
			$sql = "UPDATE {block_configurable_reports} SET `name`=?, `summary`=? WHERE `id`=? ";
			$params = array(
				'Course overview',
				'<p><span class=\"task_content_text\">Course overview</span></p>',
				$records->id
			);
			$DB->execute($sql, $params);
		}
		//$DB->set_debug(true);
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Course overview', 'sql');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
			$record1->courseid = '1';
			$record1->ownerid = '2';
			$record1->visible = '1';
			$record1->name = 'Course overview';
			$record1->summary = '<p><span class=\"task_content_text\">Course overview</span></p>';
			$record1->type = 'sql';
			$record1->pagination = '0';
			$record1->components = 'a:3:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:3736:"SELECT+%0D%0A+u.firstname+%27Firstname%27%2C%0D%0A+u.lastname+%27Lastname%27%2C%0D%0A+u.email+%27Email%27%2C+%0D%0A+r.shortname+%27Role%27%2C+%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C+%0D%0A+DATE_FORMAT%28FROM_UNIXTIME%28ue.timecreated%29%2C%27%25Y-%25m-%25d+%25r%27%29+%27Date+enrolled%27%2C+%0D%0A+DATE_FORMAT%28FROM_UNIXTIME%28p.timecompleted%29%2C%27%25Y-%25m-%25d+%25r%27%29+%27Date+completed%27+%2C+%0D%0A+IFNULL%0D%0A+%28%0D%0A++%28%0D%0A+++SELECT+COUNT%28gg.finalgrade%29%0D%0A+++FROM+%7Bgrade_grades%7D+AS+gg%0D%0A+++JOIN+%7Bgrade_items%7D+AS+gi+ON+gg.itemid%3Dgi.id%0D%0A+++WHERE+gi.courseid%3Dc.id%0D%0A+++AND+gg.userid%3Du.id%0D%0A+++AND+gi.itemtype%3D%27mod%27%0D%0A+++GROUP+BY+u.id%2Cc.id%0D%0A++%29%2C%0D%0A++%270%27%0D%0A+%29+AS+%27Activities+Completed%27%2C%0D%0A+IFNULL%0D%0A+%28%0D%0A++%28%0D%0A+++SELECT+COUNT%28gi.itemname%29%0D%0A+++FROM+%7Bgrade_items%7D+AS+gi%0D%0A+++WHERE+gi.courseid+%3D+c.id%0D%0A+++AND+gi.itemtype%3D%27mod%27%0D%0A++%29%2C%0D%0A++%270%27%0D%0A+%29+AS+%27Activities+Assigned%27+%2C%0D%0A+%28%0D%0A++SELECT+IF%0D%0A++%28%0D%0A+++%60Activities+Assigned%60%21%3D%270%27%2C+%0D%0A+++%28%0D%0A++++SELECT+IF%0D%0A++++%28%0D%0A+++++%28%60Activities+Completed%60%29%3D%28%60Activities+Assigned%60%29%2C%0D%0A+++++%2F%2A--Last+log+entry--%2A%2F%0D%0A+++++%28%0D%0A++++++SELECT+CONCAT%28%27100%25+completed+%27%2CFROM_UNIXTIME%28MAX%28log.TIME%29%2C%27%25m%2F%25d%2F%25Y%27%29%29%0D%0A++++++FROM+%7Blog%7D+log%0D%0A++++++WHERE+log.course%3Dc.id%0D%0A++++++AND+log.userid%3Du.id%0D%0A+++++%29%2C%0D%0A+++++%2F%2A--Percent+completed--%2A%2F%0D%0A+++++%28%0D%0A++++++SELECT+CONCAT%28IFNULL%28ROUND%28%28%60Activities+Completed%60%29%2F%28%60Activities+Assigned%60%29%2A100%2C0%29%2C+%270%27%29%2C%27%25+complete%27%29%0D%0A+++++%29%0D%0A++++%29%0D%0A+++%29%2C%0D%0A+++%27n%2Fa%27%0D%0A++%29%0D%0A+%29+AS+%27%25+of+Course+Completed%27%2C%0D%0A+IFNULL+%28+%28+ROUND%28c4.gradefinal%2C2%29+%29%2C+%270%27+%29+AS+%27Grade+final%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fassets%2Fjumps%2Fdedication_instance.php%3Fcourseid%3D%27%2C+c.id%2C+%27%26id%3D%27%2C+u.id%2C+%27%22+%3ETime+dedication%3C%2Fa%3E%27%29+AS+%27Time+dedication%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Freport%2Foutline%2Findex.php%3Fid%3D%27%2C+c.id%2C%27%22+%3EActivity+report%3C%2Fa%3E%27%29+AS+%27Activity+Report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Freport%2Fparticipation%2Findex.php%3Fid%3D%27%2C+c.id%2C%27%22+%3EParticipation+report%3C%2Fa%3E%27%29+AS+%27Participation+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fengagement%2Findex.php%3Fid%3D%27%2C+c.id%2C%27%22+%3EEngagement+report%3C%2Fa%3E%27%29+AS+%27Engagement+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Freport%2Fcompletion%2Findex.php%3Fcourse%3D%27%2C+c.id%2C%27%22+%3ECompletion+report%3C%2Fa%3E%27%29+AS+%27Completion+report%27%0D%0AFROM+%7Bcourse%7D+AS+c+%0D%0AJOIN+%7Benrol%7D+AS+en+ON+%28en.courseid+%3D+c.id%29%0D%0AJOIN+%7Buser_enrolments%7D+AS+ue+ON+%28ue.enrolid+%3D+en.id%29%0D%0AJOIN+%7Brole%7D+as+r+ON+%28r.id%3Den.roleid%29+%0D%0AJOIN+%7Buser%7D+AS+u+ON+%28ue.userid+%3D+u.id%29%0D%0ALEFT+JOIN+%7Bcourse_completions%7D+AS+p+ON+%28p.course%3Dc.id+AND+p.userid+%3D+u.id%29%0D%0ALEFT+JOIN+%7Bcourse_completion_crit_compl%7D+AS+c4+ON+%28u.id+%3D+c4.userid%29%0D%0AWHERE+%0D%0A+u.deleted%3D0+%0D%0A%25%25FILTER_CUSTOM%25%25+%0D%0AORDER+BY+%0D%0A+u.firstname%2Cu.lastname%2C+u.id%2Cc.fullname%2Cc.id";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"filters";a:1:{s:8:"elements";a:8:{i:0;a:5:{s:2:"id";s:15:"cFRol4zEhSfny0k";s:8:"formdata";O:6:"object":8:{s:5:"label";s:6:"Course";s:4:"name";s:3:"cid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:1;a:5:{s:2:"id";s:15:"xb1iYc6mU5PhOLv";s:8:"formdata";O:6:"object":8:{s:5:"label";s:10:"First+name";s:4:"name";s:5:"uname";s:4:"type";s:8:"opentext";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:4:"LIKE";s:6:"column";s:25:"u.firstname%2C+u.lastname";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:2;a:5:{s:2:"id";s:15:"RtR5hohjyARS9xx";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"r.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:3;a:5:{s:2:"id";s:15:"j7vXsX1XG9BOiVP";s:8:"formdata";O:6:"object":8:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:14:"ue.timecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:4;a:5:{s:2:"id";s:15:"A2mIHGllO057z1b";s:8:"formdata";O:6:"object":8:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:17:"enrolment+dateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:14:"ue.timecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:5;a:5:{s:2:"id";s:15:"w9Ws124LeIPcF65";s:8:"formdata";O:6:"object":8:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:15:"p.timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:6;a:5:{s:2:"id";s:15:"h3FkM5OOY0LQniu";s:8:"formdata";O:6:"object":8:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:15:"p.timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:7;a:5:{s:2:"id";s:15:"c5lOCG9vw6e8FiJ";s:8:"formdata";O:6:"object":8:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:7:"notnull";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:15:"p.timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}}}}';
			$record1->export = '';
			$record1->jsordering = '1';
			$record1->global = '0';
			$record1->lastexecutiontime = '50';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Course overview', 'sql');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? || `name`=? ";
			$params= array('users-who-completed-a-course','course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}

		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
			$params = array(
				'a:3:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:3736:"SELECT+%0D%0A+u.firstname+%27Firstname%27%2C%0D%0A+u.lastname+%27Lastname%27%2C%0D%0A+u.email+%27Email%27%2C+%0D%0A+r.shortname+%27Role%27%2C+%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C+%0D%0A+DATE_FORMAT%28FROM_UNIXTIME%28ue.timecreated%29%2C%27%25Y-%25m-%25d+%25r%27%29+%27Date+enrolled%27%2C+%0D%0A+DATE_FORMAT%28FROM_UNIXTIME%28p.timecompleted%29%2C%27%25Y-%25m-%25d+%25r%27%29+%27Date+completed%27+%2C+%0D%0A+IFNULL%0D%0A+%28%0D%0A++%28%0D%0A+++SELECT+COUNT%28gg.finalgrade%29%0D%0A+++FROM+%7Bgrade_grades%7D+AS+gg%0D%0A+++JOIN+%7Bgrade_items%7D+AS+gi+ON+gg.itemid%3Dgi.id%0D%0A+++WHERE+gi.courseid%3Dc.id%0D%0A+++AND+gg.userid%3Du.id%0D%0A+++AND+gi.itemtype%3D%27mod%27%0D%0A+++GROUP+BY+u.id%2Cc.id%0D%0A++%29%2C%0D%0A++%270%27%0D%0A+%29+AS+%27Activities+Completed%27%2C%0D%0A+IFNULL%0D%0A+%28%0D%0A++%28%0D%0A+++SELECT+COUNT%28gi.itemname%29%0D%0A+++FROM+%7Bgrade_items%7D+AS+gi%0D%0A+++WHERE+gi.courseid+%3D+c.id%0D%0A+++AND+gi.itemtype%3D%27mod%27%0D%0A++%29%2C%0D%0A++%270%27%0D%0A+%29+AS+%27Activities+Assigned%27+%2C%0D%0A+%28%0D%0A++SELECT+IF%0D%0A++%28%0D%0A+++%60Activities+Assigned%60%21%3D%270%27%2C+%0D%0A+++%28%0D%0A++++SELECT+IF%0D%0A++++%28%0D%0A+++++%28%60Activities+Completed%60%29%3D%28%60Activities+Assigned%60%29%2C%0D%0A+++++%2F%2A--Last+log+entry--%2A%2F%0D%0A+++++%28%0D%0A++++++SELECT+CONCAT%28%27100%25+completed+%27%2CFROM_UNIXTIME%28MAX%28log.TIME%29%2C%27%25m%2F%25d%2F%25Y%27%29%29%0D%0A++++++FROM+%7Blog%7D+log%0D%0A++++++WHERE+log.course%3Dc.id%0D%0A++++++AND+log.userid%3Du.id%0D%0A+++++%29%2C%0D%0A+++++%2F%2A--Percent+completed--%2A%2F%0D%0A+++++%28%0D%0A++++++SELECT+CONCAT%28IFNULL%28ROUND%28%28%60Activities+Completed%60%29%2F%28%60Activities+Assigned%60%29%2A100%2C0%29%2C+%270%27%29%2C%27%25+complete%27%29%0D%0A+++++%29%0D%0A++++%29%0D%0A+++%29%2C%0D%0A+++%27n%2Fa%27%0D%0A++%29%0D%0A+%29+AS+%27%25+of+Course+Completed%27%2C%0D%0A+IFNULL+%28+%28+ROUND%28c4.gradefinal%2C2%29+%29%2C+%270%27+%29+AS+%27Grade+final%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fassets%2Fjumps%2Fdedication_instance.php%3Fcourseid%3D%27%2C+c.id%2C+%27%26id%3D%27%2C+u.id%2C+%27%22+%3ETime+dedication%3C%2Fa%3E%27%29+AS+%27Time+dedication%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Freport%2Foutline%2Findex.php%3Fid%3D%27%2C+c.id%2C%27%22+%3EActivity+report%3C%2Fa%3E%27%29+AS+%27Activity+Report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Freport%2Fparticipation%2Findex.php%3Fid%3D%27%2C+c.id%2C%27%22+%3EParticipation+report%3C%2Fa%3E%27%29+AS+%27Participation+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fengagement%2Findex.php%3Fid%3D%27%2C+c.id%2C%27%22+%3EEngagement+report%3C%2Fa%3E%27%29+AS+%27Engagement+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Freport%2Fcompletion%2Findex.php%3Fcourse%3D%27%2C+c.id%2C%27%22+%3ECompletion+report%3C%2Fa%3E%27%29+AS+%27Completion+report%27%0D%0AFROM+%7Bcourse%7D+AS+c+%0D%0AJOIN+%7Benrol%7D+AS+en+ON+%28en.courseid+%3D+c.id%29%0D%0AJOIN+%7Buser_enrolments%7D+AS+ue+ON+%28ue.enrolid+%3D+en.id%29%0D%0AJOIN+%7Brole%7D+as+r+ON+%28r.id%3Den.roleid%29+%0D%0AJOIN+%7Buser%7D+AS+u+ON+%28ue.userid+%3D+u.id%29%0D%0ALEFT+JOIN+%7Bcourse_completions%7D+AS+p+ON+%28p.course%3Dc.id+AND+p.userid+%3D+u.id%29%0D%0ALEFT+JOIN+%7Bcourse_completion_crit_compl%7D+AS+c4+ON+%28u.id+%3D+c4.userid%29%0D%0AWHERE+%0D%0A+u.deleted%3D0+%0D%0A%25%25FILTER_CUSTOM%25%25+%0D%0AORDER+BY+%0D%0A+u.firstname%2Cu.lastname%2C+u.id%2Cc.fullname%2Cc.id";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"filters";a:1:{s:8:"elements";a:8:{i:0;a:5:{s:2:"id";s:15:"cFRol4zEhSfny0k";s:8:"formdata";O:6:"object":8:{s:5:"label";s:6:"Course";s:4:"name";s:3:"cid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:1;a:5:{s:2:"id";s:15:"xb1iYc6mU5PhOLv";s:8:"formdata";O:6:"object":8:{s:5:"label";s:10:"First+name";s:4:"name";s:5:"uname";s:4:"type";s:8:"opentext";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:4:"LIKE";s:6:"column";s:25:"u.firstname%2C+u.lastname";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:2;a:5:{s:2:"id";s:15:"RtR5hohjyARS9xx";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"r.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:3;a:5:{s:2:"id";s:15:"j7vXsX1XG9BOiVP";s:8:"formdata";O:6:"object":8:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:14:"ue.timecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:4;a:5:{s:2:"id";s:15:"A2mIHGllO057z1b";s:8:"formdata";O:6:"object":8:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:17:"enrolment+dateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:14:"ue.timecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:5;a:5:{s:2:"id";s:15:"w9Ws124LeIPcF65";s:8:"formdata";O:6:"object":8:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:15:"p.timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:6;a:5:{s:2:"id";s:15:"h3FkM5OOY0LQniu";s:8:"formdata";O:6:"object":8:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:15:"p.timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:7;a:5:{s:2:"id";s:15:"c5lOCG9vw6e8FiJ";s:8:"formdata";O:6:"object":8:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:7:"notnull";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:15:"p.timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}}}}',
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
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}
		}

		//upgrade_plugin_savepoint(true, 2015061215, 'local', 'lms_reports');
    }
	if($oldversion < 2015061217){
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Quiz overview', 'sql');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
			$record1->courseid = '1';
			$record1->ownerid = '2';
			$record1->visible = '1';
			$record1->name = 'Quiz overview';
			$record1->summary = '<p><span class=\"task_content_text\">Quiz overview</span></p>';
			$record1->type = 'sql';
			$record1->pagination = '0';
			$record1->components = 'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:1359:"SELECT%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Fview.php%3Fid%3D%27%2C+cm.id%2C%27%22+%3E%27%2Cq.name%2C%27%3C%2Fa%3E%27%29+AS+%27Quiz%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C%27%22+%3E%27%2Cc.shortname%2C%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C%0D%0A+s.name%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Doverview%26id%3D%27%2C+cm.id%2C%27%22+%3EGrades+report%3C%2Fa%3E%27%29+AS+%27Grades+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Dresponses%26id%3D%27%2C+cm.id%2C%27%22+%3EResponses+report%3C%2Fa%3E%27%29+AS+%27Responses+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Dstatistics%26id%3D%27%2C+cm.id%2C%27%22+%3EStatistics%3C%2Fa%3E%27%29+AS+%27Statistics%27%0D%0AFROM%0D%0A+%7Bcourse%7D+as+c%0D%0A+JOIN+%7Bcourse_modules%7D+AS+cm+ON+c.id+%3D+cm.course%0D%0A+JOIN+%7Bmodules%7D+AS+m+ON+m.id+%3D+%28cm.module%29%0D%0A+JOIN+%7Bcourse_sections%7D+AS+s+ON+%28cm.section+%3D+s.id%29%0D%0A+JOIN+%7Bquiz%7D+AS+q+ON+%28q.id+%3D+cm.instance%29%0D%0AWHERE%0D%0A+m.name+%3D+%22quiz%22+%25%25FILTER_CUSTOM%25%25+%0D%0A";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:7:"filters";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"1kFLkiVFgEQyOS5";s:8:"formdata";O:6:"object":8:{s:5:"label";s:7:"Courses";s:4:"name";s:8:"courseid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:1;a:5:{s:2:"id";s:15:"hHnegYSYm0crvFs";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Quiz";s:4:"name";s:6:"quizid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Bquiz%7D";s:5:"field";s:9:"id%2Cname";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"q.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}}}}';
			$record1->export = '';
			$record1->jsordering = '1';
			$record1->global = '0';
			$record1->lastexecutiontime = '50';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Quiz overview', 'sql');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? || `name`=? ";
			$params= array('quiz-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$records->id);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
			$params = array(
				'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:1359:"SELECT%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Fview.php%3Fid%3D%27%2C+cm.id%2C%27%22+%3E%27%2Cq.name%2C%27%3C%2Fa%3E%27%29+AS+%27Quiz%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C%27%22+%3E%27%2Cc.shortname%2C%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C%0D%0A+s.name%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Doverview%26id%3D%27%2C+cm.id%2C%27%22+%3EGrades+report%3C%2Fa%3E%27%29+AS+%27Grades+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Dresponses%26id%3D%27%2C+cm.id%2C%27%22+%3EResponses+report%3C%2Fa%3E%27%29+AS+%27Responses+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Dstatistics%26id%3D%27%2C+cm.id%2C%27%22+%3EStatistics%3C%2Fa%3E%27%29+AS+%27Statistics%27%0D%0AFROM%0D%0A+%7Bcourse%7D+as+c%0D%0A+JOIN+%7Bcourse_modules%7D+AS+cm+ON+c.id+%3D+cm.course%0D%0A+JOIN+%7Bmodules%7D+AS+m+ON+m.id+%3D+%28cm.module%29%0D%0A+JOIN+%7Bcourse_sections%7D+AS+s+ON+%28cm.section+%3D+s.id%29%0D%0A+JOIN+%7Bquiz%7D+AS+q+ON+%28q.id+%3D+cm.instance%29%0D%0AWHERE%0D%0A+m.name+%3D+%22quiz%22+%25%25FILTER_CUSTOM%25%25+%0D%0A";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:7:"filters";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"1kFLkiVFgEQyOS5";s:8:"formdata";O:6:"object":8:{s:5:"label";s:7:"Courses";s:4:"name";s:8:"courseid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:1;a:5:{s:2:"id";s:15:"hHnegYSYm0crvFs";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Quiz";s:4:"name";s:6:"quizid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Bquiz%7D";s:5:"field";s:9:"id%2Cname";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"q.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('quiz-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$records->id);
				$DB->execute($sql, $params);
			}
		}
		//upgrade_plugin_savepoint(true, 2015061217, 'local', 'lms_reports');
    }
	/*
	 * To remove the last 4 columns with link (Activity report, Participation report, Engagement Report y Completion report)
	 */
	if($oldversion < 2015061603){
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Course overview', 'sql');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
			$record1->courseid = '1';
			$record1->ownerid = '2';
			$record1->visible = '1';
			$record1->name = 'Course overview';
			$record1->summary = '<p><span class=\"task_content_text\">Course overview</span></p>';
			$record1->type = 'sql';
			$record1->pagination = '0';
			$record1->components = 'a:3:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:4276:"SELECT%0D%0A%09u.firstname+%27Firstname%27%2C%0D%0A%09u.lastname+%27Lastname%27%2C%0D%0A%09u.email+%27Email%27%2C+%0D%0A%09r.shortname+%27Role%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C+%0D%0A%09DATE_FORMAT%28FROM_UNIXTIME%28ue.timecreated%29%2C%27%25Y-%25m-%25d+%25r%27%29+%27Date+enrolled%27%2C+%0D%0A%09DATE_FORMAT%28FROM_UNIXTIME%28p.timecompleted%29%2C%27%25Y-%25m-%25d+%25r%27%29+%27Date+completed%27+%2C+%0D%0A%09IFNULL%0D%0A%09%28%0D%0A%09%09%28%0D%0A%09%09%09SELECT+COUNT%28gg.finalgrade%29%0D%0A%09%09%09FROM+%7Bgrade_grades%7D+AS+gg%0D%0A%09%09%09JOIN+%7Bgrade_items%7D+AS+gi+ON+gg.itemid%3Dgi.id%0D%0A%09%09%09WHERE+gi.courseid%3Dc.id%0D%0A%09%09%09AND+gg.userid%3Du.id%0D%0A%09%09%09AND+gi.itemtype%3D%27mod%27%0D%0A%09%09%09GROUP+BY+u.id%2Cc.id%0D%0A%09%09%29%2C%0D%0A%09%09%270%27%0D%0A%09%29+AS+%27Activities+Completed%27%2C%0D%0A%09IFNULL%0D%0A%09%28%0D%0A%09%09%28%0D%0A%09%09%09SELECT+COUNT%28gi.itemname%29%0D%0A%09%09%09FROM+%7Bgrade_items%7D+AS+gi%0D%0A%09%09%09WHERE+gi.courseid+%3D+c.id%0D%0A%09%09%09AND+gi.itemtype%3D%27mod%27%0D%0A%09%09%29%2C%0D%0A%09%09%270%27%0D%0A%09%29+AS+%27Activities+Assigned%27+%2C%0D%0A%09%28%0D%0A%09%09SELECT+IF%0D%0A%09%09%28%0D%0A%09%09%09%60Activities+Assigned%60%21%3D%270%27%2C+%0D%0A%09%09%09%28%0D%0A%09%09%09%09SELECT+IF%0D%0A%09%09%09%09%28%0D%0A%09%09%09%09%09%28%60Activities+Completed%60%29%3D%28%60Activities+Assigned%60%29%2C%0D%0A%09%09%09%09%09%2F%2A--Last+log+entry--%2A%2F%0D%0A%09%09%09%09%09%28%0D%0A%09%09%09%09%09%09SELECT+CONCAT%28%27100%25+completed+%27%2CFROM_UNIXTIME%28MAX%28log.TIME%29%2C%27%25m%2F%25d%2F%25Y%27%29%29%0D%0A%09%09%09%09%09%09FROM+%7Blog%7D+log%0D%0A%09%09%09%09%09%09WHERE+log.course%3Dc.id%0D%0A%09%09%09%09%09%09AND+log.userid%3Du.id%0D%0A%09%09%09%09%09%29%2C%0D%0A%09%09%09%09%09%2F%2A--Percent+completed--%2A%2F%0D%0A%09%09%09%09%09%28%0D%0A%09%09%09%09%09%09SELECT+CONCAT%28IFNULL%28ROUND%28%28%60Activities+Completed%60%29%2F%28%60Activities+Assigned%60%29%2A100%2C0%29%2C+%270%27%29%2C%27%25+complete%27%29%0D%0A%09%09%09%09%09%29%0D%0A%09%09%09%09%29%0D%0A%09%09%09%29%2C%0D%0A%09%09%09%27n%2Fa%27%0D%0A%09%09%29%0D%0A%09%29+AS+%27%25+of+Course+Completed%27%2C%0D%0A%09%28+%0D%0A%09%09SELECT+IFNULL+%28+%28+ROUND%28c4.gradefinal%2C2%29+%29%2C+%270%27+%29+%0D%0A%09%09FROM+%7Bcourse_completion_crit_compl%7D+AS+c4+%0D%0A%09%09INNER++JOIN+%7Bcourse_completion_criteria%7D+cc+ON+%28c4.criteriaid+%3D+cc.id+AND+cc.criteriatype+%3D+6+%29%0D%0A%09%09WHERE%09u.id+%3D+c4.userid+AND+c4.course+%3D+c.id%0D%0A%09%29+AS+%27Grade+final%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fassets%2Fjumps%2Fdedication_instance.php%3Fcourseid%3D%27%2C+c.id%2C+%27%26id%3D%27%2C+u.id%2C+%27%22+%3ETime+dedication%3C%2Fa%3E%27%29+AS+%27Time+dedication%27%0D%0A%09%2F%2A--%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Freport%2Foutline%2Findex.php%3Fid%3D%27%2C+c.id%2C%27%22+%3EActivity+report%3C%2Fa%3E%27%29+AS+%27Activity+Report%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Freport%2Fparticipation%2Findex.php%3Fid%3D%27%2C+c.id%2C%27%22+%3EParticipation+report%3C%2Fa%3E%27%29+AS+%27Participation+report%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fengagement%2Findex.php%3Fid%3D%27%2C+c.id%2C%27%22+%3EEngagement+report%3C%2Fa%3E%27%29+AS+%27Engagement+report%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Freport%2Fcompletion%2Findex.php%3Fcourse%3D%27%2C+c.id%2C%27%22+%3ECompletion+report%3C%2Fa%3E%27%29+AS+%27Completion+report%27--%2A%2F%0D%0AFROM+%7Bcourse%7D+AS+c+%0D%0AJOIN+%7Benrol%7D+AS+en+ON+%28en.courseid+%3D+c.id%29%0D%0AJOIN+%7Buser_enrolments%7D+AS+ue+ON+%28ue.enrolid+%3D+en.id%29%0D%0AJOIN+%7Brole%7D+as+r+ON+%28r.id%3Den.roleid%29+%0D%0AJOIN+%7Buser%7D+AS+u+ON+%28ue.userid+%3D+u.id%29%0D%0ALEFT+JOIN+%7Bcourse_completions%7D+AS+p+ON+%28p.course%3Dc.id+AND+p.userid+%3D+u.id%29+%0D%0AWHERE+%0D%0A%09u.deleted%3D0+%0D%0A%25%25FILTER_CUSTOM%25%25+%0D%0AORDER+BY+%0D%0A%09u.firstname%2Cu.lastname%2C+u.id%2Cc.fullname%2Cc.id";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"filters";a:1:{s:8:"elements";a:8:{i:0;a:5:{s:2:"id";s:15:"cFRol4zEhSfny0k";s:8:"formdata";O:6:"object":8:{s:5:"label";s:6:"Course";s:4:"name";s:3:"cid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:1;a:5:{s:2:"id";s:15:"xb1iYc6mU5PhOLv";s:8:"formdata";O:6:"object":8:{s:5:"label";s:10:"First+name";s:4:"name";s:5:"uname";s:4:"type";s:8:"opentext";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:4:"LIKE";s:6:"column";s:25:"u.firstname%2C+u.lastname";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:2;a:5:{s:2:"id";s:15:"RtR5hohjyARS9xx";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"r.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:3;a:5:{s:2:"id";s:15:"j7vXsX1XG9BOiVP";s:8:"formdata";O:6:"object":8:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:14:"ue.timecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:4;a:5:{s:2:"id";s:15:"A2mIHGllO057z1b";s:8:"formdata";O:6:"object":8:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:17:"enrolment+dateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:14:"ue.timecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:5;a:5:{s:2:"id";s:15:"w9Ws124LeIPcF65";s:8:"formdata";O:6:"object":8:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:15:"p.timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:6;a:5:{s:2:"id";s:15:"h3FkM5OOY0LQniu";s:8:"formdata";O:6:"object":8:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:15:"p.timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:7;a:5:{s:2:"id";s:15:"c5lOCG9vw6e8FiJ";s:8:"formdata";O:6:"object":8:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:7:"notnull";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:15:"p.timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}}}}';
			$record1->export = '';
			$record1->jsordering = '1';
			$record1->global = '0';
			$record1->lastexecutiontime = '50';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Course overview', 'sql');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}

		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
			$params = array(
				'a:3:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:4276:"SELECT%0D%0A%09u.firstname+%27Firstname%27%2C%0D%0A%09u.lastname+%27Lastname%27%2C%0D%0A%09u.email+%27Email%27%2C+%0D%0A%09r.shortname+%27Role%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C+%0D%0A%09DATE_FORMAT%28FROM_UNIXTIME%28ue.timecreated%29%2C%27%25Y-%25m-%25d+%25r%27%29+%27Date+enrolled%27%2C+%0D%0A%09DATE_FORMAT%28FROM_UNIXTIME%28p.timecompleted%29%2C%27%25Y-%25m-%25d+%25r%27%29+%27Date+completed%27+%2C+%0D%0A%09IFNULL%0D%0A%09%28%0D%0A%09%09%28%0D%0A%09%09%09SELECT+COUNT%28gg.finalgrade%29%0D%0A%09%09%09FROM+%7Bgrade_grades%7D+AS+gg%0D%0A%09%09%09JOIN+%7Bgrade_items%7D+AS+gi+ON+gg.itemid%3Dgi.id%0D%0A%09%09%09WHERE+gi.courseid%3Dc.id%0D%0A%09%09%09AND+gg.userid%3Du.id%0D%0A%09%09%09AND+gi.itemtype%3D%27mod%27%0D%0A%09%09%09GROUP+BY+u.id%2Cc.id%0D%0A%09%09%29%2C%0D%0A%09%09%270%27%0D%0A%09%29+AS+%27Activities+Completed%27%2C%0D%0A%09IFNULL%0D%0A%09%28%0D%0A%09%09%28%0D%0A%09%09%09SELECT+COUNT%28gi.itemname%29%0D%0A%09%09%09FROM+%7Bgrade_items%7D+AS+gi%0D%0A%09%09%09WHERE+gi.courseid+%3D+c.id%0D%0A%09%09%09AND+gi.itemtype%3D%27mod%27%0D%0A%09%09%29%2C%0D%0A%09%09%270%27%0D%0A%09%29+AS+%27Activities+Assigned%27+%2C%0D%0A%09%28%0D%0A%09%09SELECT+IF%0D%0A%09%09%28%0D%0A%09%09%09%60Activities+Assigned%60%21%3D%270%27%2C+%0D%0A%09%09%09%28%0D%0A%09%09%09%09SELECT+IF%0D%0A%09%09%09%09%28%0D%0A%09%09%09%09%09%28%60Activities+Completed%60%29%3D%28%60Activities+Assigned%60%29%2C%0D%0A%09%09%09%09%09%2F%2A--Last+log+entry--%2A%2F%0D%0A%09%09%09%09%09%28%0D%0A%09%09%09%09%09%09SELECT+CONCAT%28%27100%25+completed+%27%2CFROM_UNIXTIME%28MAX%28log.TIME%29%2C%27%25m%2F%25d%2F%25Y%27%29%29%0D%0A%09%09%09%09%09%09FROM+%7Blog%7D+log%0D%0A%09%09%09%09%09%09WHERE+log.course%3Dc.id%0D%0A%09%09%09%09%09%09AND+log.userid%3Du.id%0D%0A%09%09%09%09%09%29%2C%0D%0A%09%09%09%09%09%2F%2A--Percent+completed--%2A%2F%0D%0A%09%09%09%09%09%28%0D%0A%09%09%09%09%09%09SELECT+CONCAT%28IFNULL%28ROUND%28%28%60Activities+Completed%60%29%2F%28%60Activities+Assigned%60%29%2A100%2C0%29%2C+%270%27%29%2C%27%25+complete%27%29%0D%0A%09%09%09%09%09%29%0D%0A%09%09%09%09%29%0D%0A%09%09%09%29%2C%0D%0A%09%09%09%27n%2Fa%27%0D%0A%09%09%29%0D%0A%09%29+AS+%27%25+of+Course+Completed%27%2C%0D%0A%09%28+%0D%0A%09%09SELECT+IFNULL+%28+%28+ROUND%28c4.gradefinal%2C2%29+%29%2C+%270%27+%29+%0D%0A%09%09FROM+%7Bcourse_completion_crit_compl%7D+AS+c4+%0D%0A%09%09INNER++JOIN+%7Bcourse_completion_criteria%7D+cc+ON+%28c4.criteriaid+%3D+cc.id+AND+cc.criteriatype+%3D+6+%29%0D%0A%09%09WHERE%09u.id+%3D+c4.userid+AND+c4.course+%3D+c.id%0D%0A%09%29+AS+%27Grade+final%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fassets%2Fjumps%2Fdedication_instance.php%3Fcourseid%3D%27%2C+c.id%2C+%27%26id%3D%27%2C+u.id%2C+%27%22+%3ETime+dedication%3C%2Fa%3E%27%29+AS+%27Time+dedication%27%0D%0A%09%2F%2A--%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Freport%2Foutline%2Findex.php%3Fid%3D%27%2C+c.id%2C%27%22+%3EActivity+report%3C%2Fa%3E%27%29+AS+%27Activity+Report%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Freport%2Fparticipation%2Findex.php%3Fid%3D%27%2C+c.id%2C%27%22+%3EParticipation+report%3C%2Fa%3E%27%29+AS+%27Participation+report%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fengagement%2Findex.php%3Fid%3D%27%2C+c.id%2C%27%22+%3EEngagement+report%3C%2Fa%3E%27%29+AS+%27Engagement+report%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Freport%2Fcompletion%2Findex.php%3Fcourse%3D%27%2C+c.id%2C%27%22+%3ECompletion+report%3C%2Fa%3E%27%29+AS+%27Completion+report%27--%2A%2F%0D%0AFROM+%7Bcourse%7D+AS+c+%0D%0AJOIN+%7Benrol%7D+AS+en+ON+%28en.courseid+%3D+c.id%29%0D%0AJOIN+%7Buser_enrolments%7D+AS+ue+ON+%28ue.enrolid+%3D+en.id%29%0D%0AJOIN+%7Brole%7D+as+r+ON+%28r.id%3Den.roleid%29+%0D%0AJOIN+%7Buser%7D+AS+u+ON+%28ue.userid+%3D+u.id%29%0D%0ALEFT+JOIN+%7Bcourse_completions%7D+AS+p+ON+%28p.course%3Dc.id+AND+p.userid+%3D+u.id%29+%0D%0AWHERE+%0D%0A%09u.deleted%3D0+%0D%0A%25%25FILTER_CUSTOM%25%25+%0D%0AORDER+BY+%0D%0A%09u.firstname%2Cu.lastname%2C+u.id%2Cc.fullname%2Cc.id";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"filters";a:1:{s:8:"elements";a:8:{i:0;a:5:{s:2:"id";s:15:"cFRol4zEhSfny0k";s:8:"formdata";O:6:"object":8:{s:5:"label";s:6:"Course";s:4:"name";s:3:"cid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:1;a:5:{s:2:"id";s:15:"xb1iYc6mU5PhOLv";s:8:"formdata";O:6:"object":8:{s:5:"label";s:10:"First+name";s:4:"name";s:5:"uname";s:4:"type";s:8:"opentext";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:4:"LIKE";s:6:"column";s:25:"u.firstname%2C+u.lastname";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:2;a:5:{s:2:"id";s:15:"RtR5hohjyARS9xx";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"r.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:3;a:5:{s:2:"id";s:15:"j7vXsX1XG9BOiVP";s:8:"formdata";O:6:"object":8:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:14:"ue.timecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:4;a:5:{s:2:"id";s:15:"A2mIHGllO057z1b";s:8:"formdata";O:6:"object":8:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:17:"enrolment+dateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:14:"ue.timecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:5;a:5:{s:2:"id";s:15:"w9Ws124LeIPcF65";s:8:"formdata";O:6:"object":8:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:15:"p.timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:6;a:5:{s:2:"id";s:15:"h3FkM5OOY0LQniu";s:8:"formdata";O:6:"object":8:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:15:"p.timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:7;a:5:{s:2:"id";s:15:"c5lOCG9vw6e8FiJ";s:8:"formdata";O:6:"object":8:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:7:"notnull";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:15:"p.timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}
		}
		//upgrade_plugin_savepoint(true, 2015061603, 'local', 'lms_reports');
    }
    if ($oldversion < 2015061707) {
		//$DB->set_debug(true);
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND ( `name`=?) AND `type`=? ";
		$params= array('1', 'Course overview', 'sql');
		$records = $DB->get_record_sql($sql, $params );

		if(!empty($records->id)){
			$sql = "DELETE FROM {block_configurable_reports} WHERE `id`=? ";
			$params = array( $records->id );
			$DB->execute($sql, $params);
		}
		//$DB->set_debug(true);

		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Course overview', 'courseoverview');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
			$record1->courseid = '1';
			$record1->ownerid = '2';
			$record1->visible = '1';
			$record1->name = 'Course overview';
			$record1->summary = 'Course overview<br>';
			$record1->type = 'courseoverview';
			$record1->pagination = '10';
			$record1->components = 'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:12:{i:9;a:5:{s:2:"id";s:15:"dc3y2bZ6szv0rjg";s:8:"formdata";O:6:"object":6:{s:6:"column";s:9:"firstname";s:9:"columname";s:15:"User+first+name";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"User+first+name";}i:10;a:5:{s:2:"id";s:15:"R4tnday8bDs3Cic";s:8:"formdata";O:6:"object":6:{s:6:"column";s:8:"lastname";s:9:"columname";s:13:"User+lastname";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"User+lastname";}i:11;a:5:{s:2:"id";s:15:"z33YfspXpXvrIPb";s:8:"formdata";O:6:"object":6:{s:6:"column";s:5:"email";s:9:"columname";s:10:"User+email";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:10:"User+email";}i:12;a:5:{s:2:"id";s:15:"wXxf4YodV8VdpTN";s:8:"formdata";O:6:"object":6:{s:6:"column";s:4:"role";s:9:"columname";s:4:"Role";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:13;a:5:{s:2:"id";s:15:"gSnHn8itTq9Pev7";s:8:"formdata";O:6:"object":6:{s:6:"column";s:10:"coursename";s:9:"columname";s:6:"Course";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:14;a:5:{s:2:"id";s:15:"arJbUqoyZ0FJqkQ";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"date-enrolled";s:9:"columname";s:13:"Date+enrolled";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+enrolled";}i:15;a:5:{s:2:"id";s:15:"aE6EffHWio8oO1p";s:8:"formdata";O:6:"object":6:{s:6:"column";s:14:"date-completed";s:9:"columname";s:14:"Date+completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+completed";}i:16;a:5:{s:2:"id";s:15:"5Fkuwf8bsfNXqzC";s:8:"formdata";O:6:"object":6:{s:6:"column";s:20:"activities-completed";s:9:"columname";s:20:"Activities+Completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:17;a:5:{s:2:"id";s:15:"TTj4VoSSMZKwm2m";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activities-assigned";s:9:"columname";s:19:"Activities+assigned";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+assigned";}i:18;a:5:{s:2:"id";s:15:"BzLozK9wz0ZuRVN";s:8:"formdata";O:6:"object":6:{s:6:"column";s:16:"course-completed";s:9:"columname";s:23:"%25+of+Course+completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:23:"%25+of+Course+completed";}i:19;a:5:{s:2:"id";s:15:"R2di1jVN3lEoPG9";s:8:"formdata";O:6:"object":6:{s:6:"column";s:11:"grade-final";s:9:"columname";s:11:"Grade+final";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+final";}i:20;a:5:{s:2:"id";s:15:"G7NRd6NyTg8EJFe";s:8:"formdata";O:6:"object":6:{s:6:"column";s:15:"time-dedication";s:9:"columname";s:15:"Time+dedication";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+dedication";}}}s:7:"filters";a:1:{s:8:"elements";a:8:{i:0;a:5:{s:2:"id";s:15:"jRwH0yOwklthF91";s:8:"formdata";O:6:"object":8:{s:5:"label";s:6:"Course";s:4:"name";s:3:"cid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:8:"operator";s:3:"%3D";s:6:"column";s:8:"courseid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:1;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Name";s:4:"name";s:5:"uname";s:4:"type";s:8:"opentext";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:4:"LIKE";s:6:"column";s:21:"firstname%2C+lastname";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:2;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:3;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":8:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:4;a:5:{s:2:"id";s:15:"HxaOgnG3qcY3xFs";s:8:"formdata";O:6:"object":8:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:16:"enrolmentdateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:5;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":8:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:6;a:5:{s:2:"id";s:15:"3xtHbzq4qxYjFCn";s:8:"formdata";O:6:"object":8:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:7;a:5:{s:2:"id";s:15:"EHwxrWfin3XF5O6";s:8:"formdata";O:6:"object":8:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:7:"notnull";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}';
			$record1->export = '';
			$record1->jsordering = '1';
			$record1->global = '0';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);


			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Course overview', 'courseoverview');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid,$record->id);
				$DB->execute($sql, $params);
			}

		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
			$params = array(
				'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:12:{i:9;a:5:{s:2:"id";s:15:"dc3y2bZ6szv0rjg";s:8:"formdata";O:6:"object":6:{s:6:"column";s:9:"firstname";s:9:"columname";s:15:"User+first+name";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"User+first+name";}i:10;a:5:{s:2:"id";s:15:"R4tnday8bDs3Cic";s:8:"formdata";O:6:"object":6:{s:6:"column";s:8:"lastname";s:9:"columname";s:13:"User+lastname";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"User+lastname";}i:11;a:5:{s:2:"id";s:15:"z33YfspXpXvrIPb";s:8:"formdata";O:6:"object":6:{s:6:"column";s:5:"email";s:9:"columname";s:10:"User+email";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:10:"User+email";}i:12;a:5:{s:2:"id";s:15:"wXxf4YodV8VdpTN";s:8:"formdata";O:6:"object":6:{s:6:"column";s:4:"role";s:9:"columname";s:4:"Role";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:13;a:5:{s:2:"id";s:15:"gSnHn8itTq9Pev7";s:8:"formdata";O:6:"object":6:{s:6:"column";s:10:"coursename";s:9:"columname";s:6:"Course";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:14;a:5:{s:2:"id";s:15:"arJbUqoyZ0FJqkQ";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"date-enrolled";s:9:"columname";s:13:"Date+enrolled";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+enrolled";}i:15;a:5:{s:2:"id";s:15:"aE6EffHWio8oO1p";s:8:"formdata";O:6:"object":6:{s:6:"column";s:14:"date-completed";s:9:"columname";s:14:"Date+completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+completed";}i:16;a:5:{s:2:"id";s:15:"5Fkuwf8bsfNXqzC";s:8:"formdata";O:6:"object":6:{s:6:"column";s:20:"activities-completed";s:9:"columname";s:20:"Activities+Completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:17;a:5:{s:2:"id";s:15:"TTj4VoSSMZKwm2m";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activities-assigned";s:9:"columname";s:19:"Activities+assigned";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+assigned";}i:18;a:5:{s:2:"id";s:15:"BzLozK9wz0ZuRVN";s:8:"formdata";O:6:"object":6:{s:6:"column";s:16:"course-completed";s:9:"columname";s:23:"%25+of+Course+completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:23:"%25+of+Course+completed";}i:19;a:5:{s:2:"id";s:15:"R2di1jVN3lEoPG9";s:8:"formdata";O:6:"object":6:{s:6:"column";s:11:"grade-final";s:9:"columname";s:11:"Grade+final";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+final";}i:20;a:5:{s:2:"id";s:15:"G7NRd6NyTg8EJFe";s:8:"formdata";O:6:"object":6:{s:6:"column";s:15:"time-dedication";s:9:"columname";s:15:"Time+dedication";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+dedication";}}}s:7:"filters";a:1:{s:8:"elements";a:8:{i:0;a:5:{s:2:"id";s:15:"jRwH0yOwklthF91";s:8:"formdata";O:6:"object":8:{s:5:"label";s:6:"Course";s:4:"name";s:3:"cid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:8:"operator";s:3:"%3D";s:6:"column";s:8:"courseid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:1;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Name";s:4:"name";s:5:"uname";s:4:"type";s:8:"opentext";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:4:"LIKE";s:6:"column";s:21:"firstname%2C+lastname";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:2;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:3;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":8:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:4;a:5:{s:2:"id";s:15:"HxaOgnG3qcY3xFs";s:8:"formdata";O:6:"object":8:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:16:"enrolmentdateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:5;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":8:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:6;a:5:{s:2:"id";s:15:"3xtHbzq4qxYjFCn";s:8:"formdata";O:6:"object":8:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:7;a:5:{s:2:"id";s:15:"EHwxrWfin3XF5O6";s:8:"formdata";O:6:"object":8:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:7:"notnull";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array( 'course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id,$record->id);
				$DB->execute($sql, $params);
			}
		}

		//upgrade_plugin_savepoint(true, 2015061707, 'local', 'lms_reports');
    }
    if ($oldversion < 2015062302) {
		//$DB->set_debug(true);

		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Course overview', 'courseoverview');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
			$record1->courseid = '1';
			$record1->ownerid = '2';
			$record1->visible = '1';
			$record1->name = 'Course overview';
			$record1->summary = 'Course overview<br>';
			$record1->type = 'courseoverview';
			$record1->pagination = '10';
			$record1->components = 'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:18:{i:9;a:5:{s:2:"id";s:15:"dc3y2bZ6szv0rjg";s:8:"formdata";O:6:"object":6:{s:6:"column";s:9:"firstname";s:9:"columname";s:15:"User+first+name";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"User+first+name";}i:10;a:5:{s:2:"id";s:15:"R4tnday8bDs3Cic";s:8:"formdata";O:6:"object":6:{s:6:"column";s:8:"lastname";s:9:"columname";s:13:"User+lastname";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"User+lastname";}i:11;a:5:{s:2:"id";s:15:"z33YfspXpXvrIPb";s:8:"formdata";O:6:"object":6:{s:6:"column";s:5:"email";s:9:"columname";s:10:"User+email";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:10:"User+email";}i:12;a:5:{s:2:"id";s:15:"wXxf4YodV8VdpTN";s:8:"formdata";O:6:"object":6:{s:6:"column";s:4:"role";s:9:"columname";s:4:"Role";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:13;a:5:{s:2:"id";s:15:"gSnHn8itTq9Pev7";s:8:"formdata";O:6:"object":6:{s:6:"column";s:10:"coursename";s:9:"columname";s:6:"Course";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:14;a:5:{s:2:"id";s:15:"arJbUqoyZ0FJqkQ";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"date-enrolled";s:9:"columname";s:13:"Date+enrolled";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+enrolled";}i:15;a:5:{s:2:"id";s:15:"aE6EffHWio8oO1p";s:8:"formdata";O:6:"object":6:{s:6:"column";s:14:"date-completed";s:9:"columname";s:14:"Date+completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+completed";}i:16;a:5:{s:2:"id";s:15:"5Fkuwf8bsfNXqzC";s:8:"formdata";O:6:"object":6:{s:6:"column";s:20:"activities-completed";s:9:"columname";s:20:"Activities+Completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:17;a:5:{s:2:"id";s:15:"TTj4VoSSMZKwm2m";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activities-assigned";s:9:"columname";s:19:"Activities+assigned";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+assigned";}i:18;a:5:{s:2:"id";s:15:"MOkOIYwLVaHBBpt";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"activity-name";s:9:"columname";s:13:"Activity+Name";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:19;a:5:{s:2:"id";s:15:"XZhwEyol9sCFTtS";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"activity-type";s:9:"columname";s:13:"Activity+Type";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:20;a:5:{s:2:"id";s:15:"SIDEMMOBgyEKxbA";s:8:"formdata";O:6:"object":6:{s:6:"column";s:14:"activity-grade";s:9:"columname";s:14:"Activity+Grade";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:21;a:5:{s:2:"id";s:15:"spEiCdKMExEXeBp";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activity-dedication";s:9:"columname";s:19:"Activity+dedication";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+dedication";}i:22;a:5:{s:2:"id";s:15:"OhnXLvnJHmaGwYx";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activity-completion";s:9:"columname";s:19:"Activity+completion";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+completion";}i:23;a:5:{s:2:"id";s:15:"6VnqJXo4oqzjn7F";s:8:"formdata";O:6:"object":6:{s:6:"column";s:24:"activity-completion-date";s:9:"columname";s:24:"Activity+completion+date";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+completion+date";}i:24;a:5:{s:2:"id";s:15:"BzLozK9wz0ZuRVN";s:8:"formdata";O:6:"object":6:{s:6:"column";s:16:"course-completed";s:9:"columname";s:23:"%25+of+Course+completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:23:"%25+of+Course+completed";}i:25;a:5:{s:2:"id";s:15:"R2di1jVN3lEoPG9";s:8:"formdata";O:6:"object":6:{s:6:"column";s:11:"grade-final";s:9:"columname";s:11:"Grade+final";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+final";}i:26;a:5:{s:2:"id";s:15:"G7NRd6NyTg8EJFe";s:8:"formdata";O:6:"object":6:{s:6:"column";s:15:"time-dedication";s:9:"columname";s:15:"Time+dedication";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+dedication";}}}s:7:"filters";a:1:{s:8:"elements";a:10:{i:0;a:5:{s:2:"id";s:15:"jRwH0yOwklthF91";s:8:"formdata";O:6:"object":8:{s:5:"label";s:6:"Course";s:4:"name";s:3:"cid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:8:"operator";s:3:"%3D";s:6:"column";s:8:"courseid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:1;a:5:{s:2:"id";s:15:"QStk2zlSL2MW4PJ";s:8:"formdata";O:6:"object":10:{s:5:"label";s:8:"Activity";s:4:"name";s:10:"activityid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:17:"%7Bgrade_items%7D";s:5:"field";s:13:"id%2Citemname";s:5:"where";s:20:"itemtype%3D%27mod%27";s:7:"depends";s:14:"cid%2Ccourseid";s:8:"operator";s:3:"%3D";s:6:"column";s:10:"activityid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:2;a:5:{s:2:"id";s:15:"0WstGU0t3nEKYr1";s:8:"formdata";O:6:"object":10:{s:5:"label";s:19:"Activity+completion";s:4:"name";s:18:"activitycompletion";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:23:"Completed%2CUncompleted";s:5:"where";s:0:"";s:7:"depends";s:3:"cid";s:8:"operator";s:3:"%3D";s:6:"column";s:19:"activity-completion";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:3;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Name";s:4:"name";s:5:"uname";s:4:"type";s:8:"opentext";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:4:"LIKE";s:6:"column";s:21:"firstname%2C+lastname";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:4;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:5;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":8:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:6;a:5:{s:2:"id";s:15:"HxaOgnG3qcY3xFs";s:8:"formdata";O:6:"object":8:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:16:"enrolmentdateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:7;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":8:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:8;a:5:{s:2:"id";s:15:"3xtHbzq4qxYjFCn";s:8:"formdata";O:6:"object":8:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:9;a:5:{s:2:"id";s:15:"EHwxrWfin3XF5O6";s:8:"formdata";O:6:"object":8:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:7:"notnull";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}';
			$record1->export = '';
			$record1->jsordering = '1';
			$record1->global = '0';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);


			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Course overview', 'courseoverview');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid,$record->id);
				$DB->execute($sql, $params);
			}

		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
			$params = array(
				'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:18:{i:9;a:5:{s:2:"id";s:15:"dc3y2bZ6szv0rjg";s:8:"formdata";O:6:"object":6:{s:6:"column";s:9:"firstname";s:9:"columname";s:15:"User+first+name";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"User+first+name";}i:10;a:5:{s:2:"id";s:15:"R4tnday8bDs3Cic";s:8:"formdata";O:6:"object":6:{s:6:"column";s:8:"lastname";s:9:"columname";s:13:"User+lastname";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"User+lastname";}i:11;a:5:{s:2:"id";s:15:"z33YfspXpXvrIPb";s:8:"formdata";O:6:"object":6:{s:6:"column";s:5:"email";s:9:"columname";s:10:"User+email";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:10:"User+email";}i:12;a:5:{s:2:"id";s:15:"wXxf4YodV8VdpTN";s:8:"formdata";O:6:"object":6:{s:6:"column";s:4:"role";s:9:"columname";s:4:"Role";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:13;a:5:{s:2:"id";s:15:"gSnHn8itTq9Pev7";s:8:"formdata";O:6:"object":6:{s:6:"column";s:10:"coursename";s:9:"columname";s:6:"Course";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:14;a:5:{s:2:"id";s:15:"arJbUqoyZ0FJqkQ";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"date-enrolled";s:9:"columname";s:13:"Date+enrolled";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+enrolled";}i:15;a:5:{s:2:"id";s:15:"aE6EffHWio8oO1p";s:8:"formdata";O:6:"object":6:{s:6:"column";s:14:"date-completed";s:9:"columname";s:14:"Date+completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+completed";}i:16;a:5:{s:2:"id";s:15:"5Fkuwf8bsfNXqzC";s:8:"formdata";O:6:"object":6:{s:6:"column";s:20:"activities-completed";s:9:"columname";s:20:"Activities+Completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:17;a:5:{s:2:"id";s:15:"TTj4VoSSMZKwm2m";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activities-assigned";s:9:"columname";s:19:"Activities+assigned";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+assigned";}i:18;a:5:{s:2:"id";s:15:"MOkOIYwLVaHBBpt";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"activity-name";s:9:"columname";s:13:"Activity+Name";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:19;a:5:{s:2:"id";s:15:"XZhwEyol9sCFTtS";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"activity-type";s:9:"columname";s:13:"Activity+Type";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:20;a:5:{s:2:"id";s:15:"SIDEMMOBgyEKxbA";s:8:"formdata";O:6:"object":6:{s:6:"column";s:14:"activity-grade";s:9:"columname";s:14:"Activity+Grade";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:21;a:5:{s:2:"id";s:15:"spEiCdKMExEXeBp";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activity-dedication";s:9:"columname";s:19:"Activity+dedication";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+dedication";}i:22;a:5:{s:2:"id";s:15:"OhnXLvnJHmaGwYx";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activity-completion";s:9:"columname";s:19:"Activity+completion";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+completion";}i:23;a:5:{s:2:"id";s:15:"6VnqJXo4oqzjn7F";s:8:"formdata";O:6:"object":6:{s:6:"column";s:24:"activity-completion-date";s:9:"columname";s:24:"Activity+completion+date";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+completion+date";}i:24;a:5:{s:2:"id";s:15:"BzLozK9wz0ZuRVN";s:8:"formdata";O:6:"object":6:{s:6:"column";s:16:"course-completed";s:9:"columname";s:23:"%25+of+Course+completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:23:"%25+of+Course+completed";}i:25;a:5:{s:2:"id";s:15:"R2di1jVN3lEoPG9";s:8:"formdata";O:6:"object":6:{s:6:"column";s:11:"grade-final";s:9:"columname";s:11:"Grade+final";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+final";}i:26;a:5:{s:2:"id";s:15:"G7NRd6NyTg8EJFe";s:8:"formdata";O:6:"object":6:{s:6:"column";s:15:"time-dedication";s:9:"columname";s:15:"Time+dedication";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+dedication";}}}s:7:"filters";a:1:{s:8:"elements";a:10:{i:0;a:5:{s:2:"id";s:15:"jRwH0yOwklthF91";s:8:"formdata";O:6:"object":8:{s:5:"label";s:6:"Course";s:4:"name";s:3:"cid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:8:"operator";s:3:"%3D";s:6:"column";s:8:"courseid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:1;a:5:{s:2:"id";s:15:"QStk2zlSL2MW4PJ";s:8:"formdata";O:6:"object":10:{s:5:"label";s:8:"Activity";s:4:"name";s:10:"activityid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:17:"%7Bgrade_items%7D";s:5:"field";s:13:"id%2Citemname";s:5:"where";s:20:"itemtype%3D%27mod%27";s:7:"depends";s:14:"cid%2Ccourseid";s:8:"operator";s:3:"%3D";s:6:"column";s:10:"activityid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:2;a:5:{s:2:"id";s:15:"0WstGU0t3nEKYr1";s:8:"formdata";O:6:"object":10:{s:5:"label";s:19:"Activity+completion";s:4:"name";s:18:"activitycompletion";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:23:"Completed%2CUncompleted";s:5:"where";s:0:"";s:7:"depends";s:3:"cid";s:8:"operator";s:3:"%3D";s:6:"column";s:19:"activity-completion";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:3;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Name";s:4:"name";s:5:"uname";s:4:"type";s:8:"opentext";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:4:"LIKE";s:6:"column";s:21:"firstname%2C+lastname";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:4;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":8:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:5;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":8:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:6;a:5:{s:2:"id";s:15:"HxaOgnG3qcY3xFs";s:8:"formdata";O:6:"object":8:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:16:"enrolmentdateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:7;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":8:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:8;a:5:{s:2:"id";s:15:"3xtHbzq4qxYjFCn";s:8:"formdata";O:6:"object":8:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}i:9;a:5:{s:2:"id";s:15:"EHwxrWfin3XF5O6";s:8:"formdata";O:6:"object":8:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:7:"notnull";s:5:"table";s:0:"";s:5:"field";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:37:"This+filter+is+totally+custom+by+user";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array( 'course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id,$record->id);
				$DB->execute($sql, $params);
			}
		}

		//upgrade_plugin_savepoint(true, 2015062302, 'local', 'lms_reports');
    }

    if ($oldversion < 2015092403) {
		//$DB->set_debug(true);

		/*
		 * Most viewed courses
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Most viewed courses', 'sql');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
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
			$record1->global = '0';
			$record1->lastexecutiontime = '50';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Most viewed courses', 'sql');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Most viewed courses', 'sql');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('most-viewed-courses');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('most-viewed-courses','/blocks/configurable_reports/viewreport.php?id=','1','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('most-viewed-courses','/blocks/configurable_reports/viewreport.php?id=','1','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
			$params = array(
				'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:564:"SELECT+%0D%0A%09COUNT%281%29+%27Views%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C+%0D%0A%09cc.name+%27Category%27+%0D%0AFROM+%7Bcourse%7D+c%0D%0AINNER+JOIN+%7Blog%7D+l+ON+%28l.course%3Dc.id%29+%0D%0AINNER+JOIN+%7Bcourse_categories%7D+cc+ON+%28c.category%3Dcc.id%29%0D%0AWHERE+%0D%0A%09c.visible%3D1+AND+c.id%3C%3E1+%0D%0A%25%25FILTER_CUSTOM%25%25+%0D%0AGROUP+BY%0D%0A%09c.id%0D%0AORDER+BY%0D%0A%09%60Views%60+DESC";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:4:"plot";a:1:{s:8:"elements";a:0:{}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('most-viewed-courses');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('most-viewed-courses','/blocks/configurable_reports/viewreport.php?id=','1','10',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('most-viewed-courses','/blocks/configurable_reports/viewreport.php?id=','1','10',$records->id);
				$DB->execute($sql, $params);
			}
		}

		/*
		 * Most popular courses by enrollment
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Most popular courses by enrollment', 'sql');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
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
			$record1->global = '0';
			$record1->lastexecutiontime = '50';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Most popular courses by enrollment', 'sql');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Most popular courses by enrollment', 'sql');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('most-popular-courses-by-enrollment');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('most-popular-courses-by-enrollment','/blocks/configurable_reports/viewreport.php?id=','1','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('most-popular-courses-by-enrollment','/blocks/configurable_reports/viewreport.php?id=','1','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
			$params = array(
				'a:1:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:554:"SELECT+%0D%0ACONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C%0D%0ACOUNT%28ue.id%29+AS+%27Enrolled%27%0D%0AFROM+%7Bcourse%7D+c+%0D%0AJOIN+%7Benrol%7D+en+ON+%28en.courseid+%3D+c.id%29%0D%0AJOIN+%7Buser_enrolments%7D+ue+ON+%28ue.enrolid+%3D+en.id%29%0D%0AJOIN+%7Buser%7D+u+ON+%28u.id%3Due.userid%29%0D%0AWHERE%0D%0A%09u.deleted%3D0%0D%0A%25%25FILTER_CUSTOM%25%25+%0D%0AGROUP+BY+c.id%0D%0AORDER+BY++%0D%0A+%60Enrolled%60+DESC";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('most-popular-courses-by-enrollment');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('most-popular-courses-by-enrollment','/blocks/configurable_reports/viewreport.php?id=','1','10',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('most-popular-courses-by-enrollment','/blocks/configurable_reports/viewreport.php?id=','1','10',$records->id);
				$DB->execute($sql, $params);
			}
		}

		/*
		 * Video report
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Video report', 'sql');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
			$record1->courseid = '1';
			$record1->ownerid = '2';
			$record1->visible = '1';
			$record1->name = 'Video report';
			$record1->summary = '<p><span class=\"task_content_text\">Video report</span></p>';
			$record1->type = 'sql';
			$record1->pagination = '0';
			$record1->components = 'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:835:"SELECT+%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C+%0D%0A%09v.name+%27Video+name%27%2C%0D%0A%09CONCAT%28u.firstname%2C%27+%27%2Cu.lastname%29+%27User%27%2C%0D%0A%09%2F%2A--+v.id%2C%0D%0A%09va.percentage%2C+--%2A%2F%0D%0A+%09CONCAT%28+MAX%28va.percentage%29%2C%27%25%27%29+%27Percentage%27%0D%0AFROM+%7Bvideo_attempts%7D+va%0D%0AJOIN+%7Buser%7D+u+ON+%28u.id%3Dva.userid%29%0D%0AJOIN+%7Bcourse_modules%7D+cm+ON+%28cm.id%3Dva.cmid%29%0D%0AJOIN+%7Bvideofile%7D+v+ON+%28v.id%3Dcm.instance%29%0D%0AJOIN+%7Bcourse%7D+c+ON+%28c.id%3Dv.course%29%0D%0AWHERE%0D%0A%09u.deleted%3D0%0D%0A%25%25FILTER_CUSTOM%25%25%0D%0AGROUP+BY+v.id%2Cu.id%0D%0AORDER+BY+c.fullname%2Cv.name%2C+u.firstname%2C+u.lastname";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:7:"filters";a:1:{s:8:"elements";a:5:{i:0;a:5:{s:2:"id";s:15:"oEzfAaeQYnLD981";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"User";s:4:"name";s:4:"user";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Buser%7D";s:5:"field";s:14:"id%2Cfirstname";s:5:"where";s:11:"deleted%3D0";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"u.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"User";}i:1;a:5:{s:2:"id";s:15:"ueCQFjQV9xj9Nvo";s:8:"formdata";O:6:"object":10:{s:5:"label";s:5:"Video";s:4:"name";s:7:"videoid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:15:"%7Bvideofile%7D";s:5:"field";s:9:"id%2Cname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"v.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:5:"Video";}i:2;a:5:{s:2:"id";s:15:"AcOQTkAzNPylufs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:6:"Course";s:4:"name";s:8:"courseid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:5:"where";s:11:"visible%3D1";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:3;a:5:{s:2:"id";s:15:"c7sIljXwtUchiza";s:8:"formdata";O:6:"object":10:{s:5:"label";s:16:"Percentage+start";s:4:"name";s:15:"percentagestart";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:43:"10%2C20%2C30%2C40%2C50%2C60%2C70%2C80%2C100";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"va.percentage";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:16:"Percentage+start";}i:4;a:5:{s:2:"id";s:15:"EJGMN8yTYuPd9AM";s:8:"formdata";O:6:"object":10:{s:5:"label";s:14:"Percentage+end";s:4:"name";s:13:"percentageend";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:43:"10%2C20%2C30%2C40%2C50%2C60%2C70%2C80%2C100";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"va.percentage";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:14:"Percentage+end";}}}}';
			$record1->export = '';
			$record1->jsordering = '1';
			$record1->global = '0';
			$record1->lastexecutiontime = '50';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Video report', 'sql');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Video report', 'sql');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('video-report');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('video-report','/blocks/configurable_reports/viewreport.php?id=','1','11',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('video-report','/blocks/configurable_reports/viewreport.php?id=','1','11',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
			$params = array(
				'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:835:"SELECT+%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C+%0D%0A%09v.name+%27Video+name%27%2C%0D%0A%09CONCAT%28u.firstname%2C%27+%27%2Cu.lastname%29+%27User%27%2C%0D%0A%09%2F%2A--+v.id%2C%0D%0A%09va.percentage%2C+--%2A%2F%0D%0A+%09CONCAT%28+MAX%28va.percentage%29%2C%27%25%27%29+%27Percentage%27%0D%0AFROM+%7Bvideo_attempts%7D+va%0D%0AJOIN+%7Buser%7D+u+ON+%28u.id%3Dva.userid%29%0D%0AJOIN+%7Bcourse_modules%7D+cm+ON+%28cm.id%3Dva.cmid%29%0D%0AJOIN+%7Bvideofile%7D+v+ON+%28v.id%3Dcm.instance%29%0D%0AJOIN+%7Bcourse%7D+c+ON+%28c.id%3Dv.course%29%0D%0AWHERE%0D%0A%09u.deleted%3D0%0D%0A%25%25FILTER_CUSTOM%25%25%0D%0AGROUP+BY+v.id%2Cu.id%0D%0AORDER+BY+c.fullname%2Cv.name%2C+u.firstname%2C+u.lastname";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:7:"filters";a:1:{s:8:"elements";a:5:{i:0;a:5:{s:2:"id";s:15:"oEzfAaeQYnLD981";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"User";s:4:"name";s:4:"user";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Buser%7D";s:5:"field";s:14:"id%2Cfirstname";s:5:"where";s:11:"deleted%3D0";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"u.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"User";}i:1;a:5:{s:2:"id";s:15:"ueCQFjQV9xj9Nvo";s:8:"formdata";O:6:"object":10:{s:5:"label";s:5:"Video";s:4:"name";s:7:"videoid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:15:"%7Bvideofile%7D";s:5:"field";s:9:"id%2Cname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"v.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:5:"Video";}i:2;a:5:{s:2:"id";s:15:"AcOQTkAzNPylufs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:6:"Course";s:4:"name";s:8:"courseid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:5:"where";s:11:"visible%3D1";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:3;a:5:{s:2:"id";s:15:"c7sIljXwtUchiza";s:8:"formdata";O:6:"object":10:{s:5:"label";s:16:"Percentage+start";s:4:"name";s:15:"percentagestart";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:43:"10%2C20%2C30%2C40%2C50%2C60%2C70%2C80%2C100";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"va.percentage";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:16:"Percentage+start";}i:4;a:5:{s:2:"id";s:15:"EJGMN8yTYuPd9AM";s:8:"formdata";O:6:"object":10:{s:5:"label";s:14:"Percentage+end";s:4:"name";s:13:"percentageend";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:43:"10%2C20%2C30%2C40%2C50%2C60%2C70%2C80%2C100";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"va.percentage";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:14:"Percentage+end";}}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('video-report');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('video-report','/blocks/configurable_reports/viewreport.php?id=','1','11',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('video-report','/blocks/configurable_reports/viewreport.php?id=','1','11',$records->id);
				$DB->execute($sql, $params);
			}
		}

		/*
		 * quiz overview
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Quiz overview', 'sql');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
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
			$record1->global = '0';
			$record1->lastexecutiontime = '50';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Quiz overview', 'sql');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('quiz-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
			$params = array(
				'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:1359:"SELECT%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Fview.php%3Fid%3D%27%2C+cm.id%2C%27%22+%3E%27%2Cq.name%2C%27%3C%2Fa%3E%27%29+AS+%27Quiz%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C%27%22+%3E%27%2Cc.shortname%2C%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C%0D%0A+s.name%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Doverview%26id%3D%27%2C+cm.id%2C%27%22+%3EGrades+report%3C%2Fa%3E%27%29+AS+%27Grades+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Dresponses%26id%3D%27%2C+cm.id%2C%27%22+%3EResponses+report%3C%2Fa%3E%27%29+AS+%27Responses+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Dstatistics%26id%3D%27%2C+cm.id%2C%27%22+%3EStatistics%3C%2Fa%3E%27%29+AS+%27Statistics%27%0D%0AFROM%0D%0A+%7Bcourse%7D+as+c%0D%0A+JOIN+%7Bcourse_modules%7D+AS+cm+ON+c.id+%3D+cm.course%0D%0A+JOIN+%7Bmodules%7D+AS+m+ON+m.id+%3D+%28cm.module%29%0D%0A+JOIN+%7Bcourse_sections%7D+AS+s+ON+%28cm.section+%3D+s.id%29%0D%0A+JOIN+%7Bquiz%7D+AS+q+ON+%28q.id+%3D+cm.instance%29%0D%0AWHERE%0D%0A+m.name+%3D+%22quiz%22+%25%25FILTER_CUSTOM%25%25+%0D%0A";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:7:"filters";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"1kFLkiVFgEQyOS5";s:8:"formdata";O:6:"object":10:{s:5:"label";s:7:"Courses";s:4:"name";s:8:"courseid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:7:"Courses";}i:1;a:5:{s:2:"id";s:15:"hHnegYSYm0crvFs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Quiz";s:4:"name";s:6:"quizid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Bquiz%7D";s:5:"field";s:9:"id%2Cname";s:5:"where";s:0:"";s:7:"depends";s:17:"courseid%2Ccourse";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"q.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Quiz";}}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('quiz-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$records->id);
				$DB->execute($sql, $params);
			}
		}

		/*
		 * Course overview
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Course overview', 'courseoverview');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
			$record1->courseid = '1';
			$record1->ownerid = '2';
			$record1->visible = '1';
			$record1->name = 'Course overview';
			$record1->summary = 'Course overview<br>';
			$record1->type = 'courseoverview';
			$record1->pagination = '10';
			$record1->components = 'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:18:{i:0;a:5:{s:2:"id";s:15:"dc3y2bZ6szv0rjg";s:8:"formdata";O:6:"object":6:{s:6:"column";s:9:"firstname";s:9:"columname";s:15:"User+First+Name";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"User+First+Name";}i:1;a:5:{s:2:"id";s:15:"R4tnday8bDs3Cic";s:8:"formdata";O:6:"object":6:{s:6:"column";s:8:"lastname";s:9:"columname";s:14:"User+Last+Name";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"User+Last+Name";}i:2;a:5:{s:2:"id";s:15:"z33YfspXpXvrIPb";s:8:"formdata";O:6:"object":6:{s:6:"column";s:5:"email";s:9:"columname";s:10:"User+Email";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:10:"User+Email";}i:3;a:5:{s:2:"id";s:15:"wXxf4YodV8VdpTN";s:8:"formdata";O:6:"object":6:{s:6:"column";s:4:"role";s:9:"columname";s:4:"Role";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:4;a:5:{s:2:"id";s:15:"gSnHn8itTq9Pev7";s:8:"formdata";O:6:"object":6:{s:6:"column";s:10:"coursename";s:9:"columname";s:6:"Course";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:5;a:5:{s:2:"id";s:15:"arJbUqoyZ0FJqkQ";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"date-enrolled";s:9:"columname";s:13:"Date+Enrolled";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+Enrolled";}i:6;a:5:{s:2:"id";s:15:"aE6EffHWio8oO1p";s:8:"formdata";O:6:"object":6:{s:6:"column";s:14:"date-completed";s:9:"columname";s:14:"Date+Completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+Completed";}i:7;a:5:{s:2:"id";s:15:"5Fkuwf8bsfNXqzC";s:8:"formdata";O:6:"object":6:{s:6:"column";s:20:"activities-completed";s:9:"columname";s:20:"Activities+Completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:8;a:5:{s:2:"id";s:15:"TTj4VoSSMZKwm2m";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activities-assigned";s:9:"columname";s:19:"Activities+Assigned";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+Assigned";}i:9;a:5:{s:2:"id";s:15:"MOkOIYwLVaHBBpt";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"activity-name";s:9:"columname";s:13:"Activity+Name";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:10;a:5:{s:2:"id";s:15:"XZhwEyol9sCFTtS";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"activity-type";s:9:"columname";s:13:"Activity+Type";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:11;a:5:{s:2:"id";s:15:"SIDEMMOBgyEKxbA";s:8:"formdata";O:6:"object":6:{s:6:"column";s:14:"activity-grade";s:9:"columname";s:14:"Activity+Grade";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:12;a:5:{s:2:"id";s:15:"spEiCdKMExEXeBp";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activity-dedication";s:9:"columname";s:19:"Activity+Dedication";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Dedication";}i:13;a:5:{s:2:"id";s:15:"OhnXLvnJHmaGwYx";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activity-completion";s:9:"columname";s:19:"Activity+Completion";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Completion";}i:14;a:5:{s:2:"id";s:15:"6VnqJXo4oqzjn7F";s:8:"formdata";O:6:"object":6:{s:6:"column";s:24:"activity-completion-date";s:9:"columname";s:24:"Activity+Completion+Date";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+Completion+Date";}i:15;a:5:{s:2:"id";s:15:"BzLozK9wz0ZuRVN";s:8:"formdata";O:6:"object":6:{s:6:"column";s:16:"course-completed";s:9:"columname";s:23:"%25+Of+Course+Completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:23:"%25+Of+Course+Completed";}i:16;a:5:{s:2:"id";s:15:"R2di1jVN3lEoPG9";s:8:"formdata";O:6:"object":6:{s:6:"column";s:11:"grade-final";s:9:"columname";s:11:"Final+Grade";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Final+Grade";}i:17;a:5:{s:2:"id";s:15:"G7NRd6NyTg8EJFe";s:8:"formdata";O:6:"object":6:{s:6:"column";s:15:"time-dedication";s:9:"columname";s:15:"Dedication+Time";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Dedication+Time";}}}s:7:"filters";a:1:{s:8:"elements";a:10:{i:0;a:5:{s:2:"id";s:15:"jRwH0yOwklthF91";s:8:"formdata";O:6:"object":10:{s:5:"label";s:6:"Course";s:4:"name";s:3:"cid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:8:"courseid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:1;a:5:{s:2:"id";s:15:"QStk2zlSL2MW4PJ";s:8:"formdata";O:6:"object":10:{s:5:"label";s:8:"Activity";s:4:"name";s:10:"activityid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:17:"%7Bgrade_items%7D";s:5:"field";s:13:"id%2Citemname";s:5:"where";s:20:"itemtype%3D%27mod%27";s:7:"depends";s:14:"cid%2Ccourseid";s:8:"operator";s:3:"%3D";s:6:"column";s:10:"activityid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:8:"Activity";}i:2;a:5:{s:2:"id";s:15:"0WstGU0t3nEKYr1";s:8:"formdata";O:6:"object":10:{s:5:"label";s:19:"Activity+completion";s:4:"name";s:18:"activitycompletion";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:23:"Completed%2CUncompleted";s:5:"where";s:0:"";s:7:"depends";s:3:"cid";s:8:"operator";s:3:"%3D";s:6:"column";s:19:"activity-completion";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:19:"Activity+completion";}i:3;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Name";s:4:"name";s:5:"uname";s:4:"type";s:8:"opentext";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:4:"LIKE";s:6:"column";s:21:"firstname%2C+lastname";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Name";}i:4;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Role";}i:5;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":10:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:20:"Enrolment+date+start";}i:6;a:5:{s:2:"id";s:15:"HxaOgnG3qcY3xFs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:16:"enrolmentdateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:18:"Enrolment+date+end";}i:7;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":10:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:21:"Completion+date+start";}i:8;a:5:{s:2:"id";s:15:"3xtHbzq4qxYjFCn";s:8:"formdata";O:6:"object":10:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:19:"Completion+date+end";}i:9;a:5:{s:2:"id";s:15:"EHwxrWfin3XF5O6";s:8:"formdata";O:6:"object":10:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:23:"Completed%2CUncompleted";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:22:"course-completed-state";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:16:"Course+completed";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}';
			$record1->export = '';
			$record1->jsordering = '1';
			$record1->global = '0';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);


			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Course overview', 'courseoverview');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid,$record->id);
				$DB->execute($sql, $params);
			}

		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
			$params = array(
				'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:18:{i:0;a:5:{s:2:"id";s:15:"dc3y2bZ6szv0rjg";s:8:"formdata";O:6:"object":6:{s:6:"column";s:9:"firstname";s:9:"columname";s:15:"User+First+Name";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"User+First+Name";}i:1;a:5:{s:2:"id";s:15:"R4tnday8bDs3Cic";s:8:"formdata";O:6:"object":6:{s:6:"column";s:8:"lastname";s:9:"columname";s:14:"User+Last+Name";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"User+Last+Name";}i:2;a:5:{s:2:"id";s:15:"z33YfspXpXvrIPb";s:8:"formdata";O:6:"object":6:{s:6:"column";s:5:"email";s:9:"columname";s:10:"User+Email";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:10:"User+Email";}i:3;a:5:{s:2:"id";s:15:"wXxf4YodV8VdpTN";s:8:"formdata";O:6:"object":6:{s:6:"column";s:4:"role";s:9:"columname";s:4:"Role";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:4;a:5:{s:2:"id";s:15:"gSnHn8itTq9Pev7";s:8:"formdata";O:6:"object":6:{s:6:"column";s:10:"coursename";s:9:"columname";s:6:"Course";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:5;a:5:{s:2:"id";s:15:"arJbUqoyZ0FJqkQ";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"date-enrolled";s:9:"columname";s:13:"Date+Enrolled";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+Enrolled";}i:6;a:5:{s:2:"id";s:15:"aE6EffHWio8oO1p";s:8:"formdata";O:6:"object":6:{s:6:"column";s:14:"date-completed";s:9:"columname";s:14:"Date+Completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+Completed";}i:7;a:5:{s:2:"id";s:15:"5Fkuwf8bsfNXqzC";s:8:"formdata";O:6:"object":6:{s:6:"column";s:20:"activities-completed";s:9:"columname";s:20:"Activities+Completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:8;a:5:{s:2:"id";s:15:"TTj4VoSSMZKwm2m";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activities-assigned";s:9:"columname";s:19:"Activities+Assigned";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+Assigned";}i:9;a:5:{s:2:"id";s:15:"MOkOIYwLVaHBBpt";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"activity-name";s:9:"columname";s:13:"Activity+Name";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:10;a:5:{s:2:"id";s:15:"XZhwEyol9sCFTtS";s:8:"formdata";O:6:"object":6:{s:6:"column";s:13:"activity-type";s:9:"columname";s:13:"Activity+Type";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:11;a:5:{s:2:"id";s:15:"SIDEMMOBgyEKxbA";s:8:"formdata";O:6:"object":6:{s:6:"column";s:14:"activity-grade";s:9:"columname";s:14:"Activity+Grade";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:12;a:5:{s:2:"id";s:15:"spEiCdKMExEXeBp";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activity-dedication";s:9:"columname";s:19:"Activity+Dedication";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Dedication";}i:13;a:5:{s:2:"id";s:15:"OhnXLvnJHmaGwYx";s:8:"formdata";O:6:"object":6:{s:6:"column";s:19:"activity-completion";s:9:"columname";s:19:"Activity+Completion";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Completion";}i:14;a:5:{s:2:"id";s:15:"6VnqJXo4oqzjn7F";s:8:"formdata";O:6:"object":6:{s:6:"column";s:24:"activity-completion-date";s:9:"columname";s:24:"Activity+Completion+Date";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+Completion+Date";}i:15;a:5:{s:2:"id";s:15:"BzLozK9wz0ZuRVN";s:8:"formdata";O:6:"object":6:{s:6:"column";s:16:"course-completed";s:9:"columname";s:23:"%25+Of+Course+Completed";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:23:"%25+Of+Course+Completed";}i:16;a:5:{s:2:"id";s:15:"R2di1jVN3lEoPG9";s:8:"formdata";O:6:"object":6:{s:6:"column";s:11:"grade-final";s:9:"columname";s:11:"Final+Grade";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Final+Grade";}i:17;a:5:{s:2:"id";s:15:"G7NRd6NyTg8EJFe";s:8:"formdata";O:6:"object":6:{s:6:"column";s:15:"time-dedication";s:9:"columname";s:15:"Dedication+Time";s:5:"align";s:6:"center";s:4:"size";s:0:"";s:4:"wrap";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Dedication+Time";}}}s:7:"filters";a:1:{s:8:"elements";a:10:{i:0;a:5:{s:2:"id";s:15:"jRwH0yOwklthF91";s:8:"formdata";O:6:"object":10:{s:5:"label";s:6:"Course";s:4:"name";s:3:"cid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:8:"courseid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:1;a:5:{s:2:"id";s:15:"QStk2zlSL2MW4PJ";s:8:"formdata";O:6:"object":10:{s:5:"label";s:8:"Activity";s:4:"name";s:10:"activityid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:17:"%7Bgrade_items%7D";s:5:"field";s:13:"id%2Citemname";s:5:"where";s:20:"itemtype%3D%27mod%27";s:7:"depends";s:14:"cid%2Ccourseid";s:8:"operator";s:3:"%3D";s:6:"column";s:10:"activityid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:8:"Activity";}i:2;a:5:{s:2:"id";s:15:"0WstGU0t3nEKYr1";s:8:"formdata";O:6:"object":10:{s:5:"label";s:19:"Activity+completion";s:4:"name";s:18:"activitycompletion";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:23:"Completed%2CUncompleted";s:5:"where";s:0:"";s:7:"depends";s:3:"cid";s:8:"operator";s:3:"%3D";s:6:"column";s:19:"activity-completion";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:19:"Activity+completion";}i:3;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Name";s:4:"name";s:5:"uname";s:4:"type";s:8:"opentext";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:4:"LIKE";s:6:"column";s:21:"firstname%2C+lastname";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Name";}i:4;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Role";}i:5;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":10:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:20:"Enrolment+date+start";}i:6;a:5:{s:2:"id";s:15:"HxaOgnG3qcY3xFs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:16:"enrolmentdateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:18:"Enrolment+date+end";}i:7;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":10:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:21:"Completion+date+start";}i:8;a:5:{s:2:"id";s:15:"3xtHbzq4qxYjFCn";s:8:"formdata";O:6:"object":10:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:19:"Completion+date+end";}i:9;a:5:{s:2:"id";s:15:"EHwxrWfin3XF5O6";s:8:"formdata";O:6:"object":10:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:23:"Completed%2CUncompleted";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:22:"course-completed-state";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:16:"Course+completed";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array( 'course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id,$record->id);
				$DB->execute($sql, $params);
			}
		}

		$sql = "UPDATE {local_lms_reports} SET `state`=0 WHERE `idcr`=0 ";
		$DB->execute($sql);

		$reporttableui = get_config('block_configurable_reports', 'reporttableui');
		if($reporttableui!='html'){
			set_config('reporttableui', 'html', 'block_configurable_reports');
		}

		$sql = "SELECT p.id "
		." FROM {block_configurable_reports} cr "
		." RIGHT JOIN {local_lms_reports} p ON (cr.id=p.idcr) "
		." WHERE cr.id IS NULL AND p.idcr>0";

		$rs = $DB->get_records_sql($sql);
		foreach ($rs as $r) {
			$sql = "DELETE FROM {local_lms_reports} WHERE `id`= :idvar ";
			$params = array('idvar' => $r->id );
			$DB->execute($sql,$params);
		}

		$sql = "UPDATE {local_lms_reports} SET `state`=1 WHERE `id` IN (3,4,5) ";
		$DB->execute($sql);

		$sql = "UPDATE {local_lms_reports} SET `idtype`=5 WHERE `id`=4 ";
		$DB->execute($sql);

		/*
		 * Performance Management
		 */
		$sql="SELECT id FROM {local_lms_report_type} WHERE `name`=? ";
		$params= array('performance_management');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_report_type} SET `name`=?, `order`=?, `state`=1 ";
			$params = array('performance_management', '7');
			$DB->execute($sql, $params);
		}

		/*
		 * user analytics
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('user_analytics');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('user_analytics','user_analytics_desc','/report/overviewstats/index.php','7','14');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * absence report
		 */
		$sql = "DELETE FROM  {local_lms_reports} WHERE `name`=? ";
		$params= array('absence_report');
		$DB->execute($sql, $params);

		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('absence_report');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('absence_report','absence_report_desc','/local/elisreports/render_report_page.php?report=nonstarter','7','15');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * class completion gas gauge
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('class_completion_gas_gauge');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('class_completion_gas_gauge','class_completion_gas_gauge_desc','/local/elisreports/render_report_page.php?report=class_completion_gas_gauge','7','16');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * completion by organization
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('completion_by_organization');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('completion_by_organization','completion_by_organization_desc','/local/elisreports/render_report_page.php?report=course_completion_by_cluster','7','17');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * course completion gas Gauge
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('course_completion_gas_Gauge');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('course_completion_gas_Gauge','course_completion_gas_gauge_desc','/local/elisreports/render_report_page.php?report=course_completion_gas_gauge','7','18');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * course progress summary report
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('course_progress_summary_report');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('course_progress_summary_report','course_progress_summary_report_desc','/local/elisreports/render_report_page.php?report=course_progress_summary','7','19');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * grade
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('grade');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('grade','grade_desc','/grade/report/user/index.php?id=24&userid=0','7','20');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * individual course progress
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('individual_course_progress');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('individual_course_progress','individual_course_progress_desc','/local/elisreports/render_report_page.php?report=individual_course_progress','7','21');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * individual user
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('individual_user');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('individual_user','individual_user_desc','/local/elisreports/render_report_page.php?report=individual_user','7','22');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * learning path
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('learning_path');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('learning_path','learning_path_desc','/local/elisreports/render_report_page.php?report=curricula','7','23');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * new registrants by course report
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('new_registrants_by_course_report');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('new_registrants_by_course_report','new_registrants_by_course_report_desc','/local/elisreports/render_report_page.php?report=registrants_by_course','7','24');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * new registrants by student report
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('new_registrants_by_student_report');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('new_registrants_by_student_report','new_registrants_by_student_report_desc','/local/elisreports/render_report_page.php?report=registrants_by_student','7','25');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * roster
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('roster');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('roster','roster_desc','/local/elisreports/render_report_page.php?report=class_roster','7','26');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * schedule
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('schedule');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('schedule','schedule_desc','/local/elisreports/schedule.php?action=list','7','27');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * site usage summary report
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('site_usage_summary_report');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('site_usage_summary_report','site_usage_summary_report_desc','/local/elisreports/render_report_page.php?report=course_usage_summary','7','28');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * sitewide course completion
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('sitewide_course_completion');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('sitewide_course_completion','sitewide_course_completion_desc','/local/elisreports/render_report_page.php?report=sitewide_course_completion','7','29');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * sitewide time summary report
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('sitewide_time_summary_report');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('sitewide_time_summary_report','sitewide_time_summary_report_desc','/local/elisreports/render_report_page.php?report=sitewide_time_summary','7','30');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * sitewide transcript report
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('sitewide_transcript_report');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('sitewide_transcript_report','sitewide_transcript_report_desc','/local/elisreports/render_report_page.php?report=sitewide_transcript','7','31');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		/*
		 * user class completion
		 */
		$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
		$params= array('user_class_completion');
		$record = $DB->get_record_sql($sql, $params );
		if(empty($record->id)){
			$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `summary`=?, `url`=?, `idtype`=?, `order`=?, `state`=1 ";
			$params = array('user_class_completion','user_class_completion_desc','/local/elisreports/render_report_page.php?report=user_class_completion','7','32');
			$DB->execute($sql, $params);
		}else{
			$sql = "UPDATE {local_lms_reports} SET `state`=? WHERE `id`=? ";
			$params = array('1', $record->id);
			$DB->execute($sql, $params);
		}

		upgrade_plugin_savepoint(true, 2015092403, 'local', 'lms_reports');
    }
    if ($oldversion < 2015120700) {
		//$DB->set_debug(true);
		/*
		 * Course overview
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Course overview', 'courseoverview');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
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
			$record1->global = '0';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);


			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Course overview', 'courseoverview');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid,$record->id);
				$DB->execute($sql, $params);
			}

		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
			$params = array(
				'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:17:{i:0;a:5:{s:2:"id";s:15:"T1pRkohCK1D6nFR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:8:"lastname";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:1;a:5:{s:2:"id";s:15:"N7pPQ4C2lWa1fZ2";s:8:"formdata";O:6:"object":3:{s:6:"column";s:5:"email";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:2;a:5:{s:2:"id";s:15:"oBJaalrApfyTU6N";s:8:"formdata";O:6:"object":3:{s:6:"column";s:4:"role";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:4:"Role";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:3;a:5:{s:2:"id";s:15:"Ibf1pkc2Ekv1gIa";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"date-enrolled";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Date+Enrolled";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+Enrolled";}i:4;a:5:{s:2:"id";s:15:"iQ8bGHflamD0g0F";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"date-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Date+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+Completed";}i:5;a:5:{s:2:"id";s:15:"zKDV08lHkVY3iFZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:16:"course-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"%25+Course+progress";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"%25+Course+progress";}i:6;a:5:{s:2:"id";s:15:"Qmd7XBOCIMqgoIL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:11:"grade-final";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:11:"Grade+Final";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+Final";}i:7;a:5:{s:2:"id";s:15:"kF763S7L6t9j51l";s:8:"formdata";O:6:"object":3:{s:6:"column";s:15:"time-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:15:"Time+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+Dedication";}i:8;a:5:{s:2:"id";s:15:"EkwgU8PubxHf46a";s:8:"formdata";O:6:"object":3:{s:6:"column";s:10:"coursename";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:6:"Course";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:9;a:5:{s:2:"id";s:15:"CE0ysTXQYVrnmpZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:20:"activities-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:20:"Activities+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:10;a:5:{s:2:"id";s:15:"bPyBEcSP0omVH5V";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activities-assigned";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activities+Assigned";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+Assigned";}i:11;a:5:{s:2:"id";s:15:"J7oBw9WfFaEdKBR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-name";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:12;a:5:{s:2:"id";s:15:"SEGd6ULJ7095mnL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-type";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Type";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:13;a:5:{s:2:"id";s:15:"7EHM9DojrR7DYuq";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"activity-grade";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Activity+Grade";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:14;a:5:{s:2:"id";s:15:"WfT90uBlFQxV0Oh";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Dedication";}i:15;a:5:{s:2:"id";s:15:"nQNTbU9V8A6lZck";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-completion";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Completion";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Completion";}i:16;a:5:{s:2:"id";s:15:"joLOByXp3wuFf4R";s:8:"formdata";O:6:"object":3:{s:6:"column";s:24:"activity-completion-date";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:24:"Activity+Completion+Date";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+Completion+Date";}}}s:7:"filters";a:1:{s:8:"elements";a:8:{i:3;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"EkwgU8PubxHf46a";s:5:"label";s:6:"Course";s:4:"name";s:6:"course";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:4:"LIKE";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:4;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Role";}i:5;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":10:{s:5:"label";s:20:"Enrolment+date+start";s:4:"name";s:18:"enrolmentdatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:20:"Enrolment+date+start";}i:6;a:5:{s:2:"id";s:15:"HxaOgnG3qcY3xFs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:18:"Enrolment+date+end";s:4:"name";s:16:"enrolmentdateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"uetimecreated";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:18:"Enrolment+date+end";}i:7;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":10:{s:5:"label";s:21:"Completion+date+start";s:4:"name";s:19:"completiondatestart";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3E%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:21:"Completion+date+start";}i:8;a:5:{s:2:"id";s:15:"3xtHbzq4qxYjFCn";s:8:"formdata";O:6:"object":10:{s:5:"label";s:19:"Completion+date+end";s:4:"name";s:17:"completiondateend";s:4:"type";s:8:"datetime";s:5:"table";s:0:"";s:5:"field";s:0:"";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:6:"%3C%3D";s:6:"column";s:13:"timecompleted";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:19:"Completion+date+end";}i:9;a:5:{s:2:"id";s:15:"EHwxrWfin3XF5O6";s:8:"formdata";O:6:"object":10:{s:5:"label";s:16:"Course+completed";s:4:"name";s:15:"coursecompleted";s:4:"type";s:15:"selectlisttable";s:5:"table";s:0:"";s:5:"field";s:23:"Completed%2CUncompleted";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:22:"course-completed-state";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:16:"Course+completed";}i:10;a:5:{s:2:"id";s:15:"WYx4WFJuFkjyuAg";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"J7oBw9WfFaEdKBR";s:5:"label";s:13:"Activity+Name";s:4:"name";s:12:"activityname";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:13:"Activity+Name";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array( 'course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id,$record->id);
				$DB->execute($sql, $params);
			}
		}

		upgrade_plugin_savepoint(true, 2015120700, 'local', 'lms_reports');
    }

    if ($oldversion < 2016031600) {
		//$DB->set_debug(true);
		/*
		 * Course overview
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Course overview', 'courseoverview');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
			$record1->courseid = '1';
			$record1->ownerid = '2';
			$record1->visible = '1';
			$record1->name = 'Course overview';
			$record1->summary = 'Course overview<br>';
			$record1->type = 'courseoverview';
			$record1->pagination = '10';
			$record1->components = 'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:17:{i:0;a:5:{s:2:"id";s:15:"T1pRkohCK1D6nFR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:8:"lastname";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:1;a:5:{s:2:"id";s:15:"N7pPQ4C2lWa1fZ2";s:8:"formdata";O:6:"object":3:{s:6:"column";s:5:"email";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:2;a:5:{s:2:"id";s:15:"oBJaalrApfyTU6N";s:8:"formdata";O:6:"object":3:{s:6:"column";s:4:"role";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:4:"Role";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:3;a:5:{s:2:"id";s:15:"Ibf1pkc2Ekv1gIa";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"date-enrolled";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Date+Enrolled";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+Enrolled";}i:4;a:5:{s:2:"id";s:15:"iQ8bGHflamD0g0F";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"date-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Date+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+Completed";}i:5;a:5:{s:2:"id";s:15:"zKDV08lHkVY3iFZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:16:"course-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"%25+Course+progress";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"%25+Course+progress";}i:6;a:5:{s:2:"id";s:15:"Qmd7XBOCIMqgoIL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:11:"grade-final";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:11:"Grade+Final";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+Final";}i:7;a:5:{s:2:"id";s:15:"kF763S7L6t9j51l";s:8:"formdata";O:6:"object":3:{s:6:"column";s:15:"time-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:15:"Time+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+Dedication";}i:8;a:5:{s:2:"id";s:15:"EkwgU8PubxHf46a";s:8:"formdata";O:6:"object":3:{s:6:"column";s:10:"coursename";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:6:"Course";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:9;a:5:{s:2:"id";s:15:"CE0ysTXQYVrnmpZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:20:"activities-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:20:"Activities+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:10;a:5:{s:2:"id";s:15:"bPyBEcSP0omVH5V";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activities-assigned";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activities+Assigned";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+Assigned";}i:11;a:5:{s:2:"id";s:15:"J7oBw9WfFaEdKBR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-name";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:12;a:5:{s:2:"id";s:15:"SEGd6ULJ7095mnL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-type";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Type";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:13;a:5:{s:2:"id";s:15:"7EHM9DojrR7DYuq";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"activity-grade";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Activity+Grade";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:14;a:5:{s:2:"id";s:15:"WfT90uBlFQxV0Oh";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Dedication";}i:15;a:5:{s:2:"id";s:15:"nQNTbU9V8A6lZck";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-completion";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Completion";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Completion";}i:16;a:5:{s:2:"id";s:15:"joLOByXp3wuFf4R";s:8:"formdata";O:6:"object":3:{s:6:"column";s:24:"activity-completion-date";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:24:"Activity+Completion+Date";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+Completion+Date";}}}s:7:"filters";a:1:{s:8:"elements";a:5:{i:3;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"EkwgU8PubxHf46a";s:5:"label";s:6:"Course";s:4:"name";s:6:"course";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:4:"LIKE";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:4;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Role";}i:5;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"Ibf1pkc2Ekv1gIa";s:5:"label";s:13:"Date+Enrolled";s:4:"name";s:12:"dateenrolled";s:4:"type";s:8:"datetime";s:8:"operator";s:6:"%3E%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:13:"Date+Enrolled";}i:7;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"iQ8bGHflamD0g0F";s:5:"label";s:14:"Date+Completed";s:4:"name";s:13:"datecompleted";s:4:"type";s:8:"datetime";s:8:"operator";s:6:"%3E%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:14:"Date+Completed";}i:10;a:5:{s:2:"id";s:15:"WYx4WFJuFkjyuAg";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"J7oBw9WfFaEdKBR";s:5:"label";s:13:"Activity+Name";s:4:"name";s:12:"activityname";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:13:"Activity+Name";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}';
			$record1->export = '';
			$record1->jsordering = '1';
			$record1->global = '0';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);


			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Course overview', 'courseoverview');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid,$record->id);
				$DB->execute($sql, $params);
			}

		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=? WHERE `id`=? ";
			$params = array(
				'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:17:{i:0;a:5:{s:2:"id";s:15:"T1pRkohCK1D6nFR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:8:"lastname";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:1;a:5:{s:2:"id";s:15:"N7pPQ4C2lWa1fZ2";s:8:"formdata";O:6:"object":3:{s:6:"column";s:5:"email";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:2;a:5:{s:2:"id";s:15:"oBJaalrApfyTU6N";s:8:"formdata";O:6:"object":3:{s:6:"column";s:4:"role";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:4:"Role";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:3;a:5:{s:2:"id";s:15:"Ibf1pkc2Ekv1gIa";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"date-enrolled";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Date+Enrolled";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+Enrolled";}i:4;a:5:{s:2:"id";s:15:"iQ8bGHflamD0g0F";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"date-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Date+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+Completed";}i:5;a:5:{s:2:"id";s:15:"zKDV08lHkVY3iFZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:16:"course-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"%25+Course+progress";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"%25+Course+progress";}i:6;a:5:{s:2:"id";s:15:"Qmd7XBOCIMqgoIL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:11:"grade-final";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:11:"Grade+Final";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+Final";}i:7;a:5:{s:2:"id";s:15:"kF763S7L6t9j51l";s:8:"formdata";O:6:"object":3:{s:6:"column";s:15:"time-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:15:"Time+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+Dedication";}i:8;a:5:{s:2:"id";s:15:"EkwgU8PubxHf46a";s:8:"formdata";O:6:"object":3:{s:6:"column";s:10:"coursename";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:6:"Course";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:9;a:5:{s:2:"id";s:15:"CE0ysTXQYVrnmpZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:20:"activities-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:20:"Activities+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:10;a:5:{s:2:"id";s:15:"bPyBEcSP0omVH5V";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activities-assigned";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activities+Assigned";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+Assigned";}i:11;a:5:{s:2:"id";s:15:"J7oBw9WfFaEdKBR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-name";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:12;a:5:{s:2:"id";s:15:"SEGd6ULJ7095mnL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-type";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Type";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:13;a:5:{s:2:"id";s:15:"7EHM9DojrR7DYuq";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"activity-grade";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Activity+Grade";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:14;a:5:{s:2:"id";s:15:"WfT90uBlFQxV0Oh";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Dedication";}i:15;a:5:{s:2:"id";s:15:"nQNTbU9V8A6lZck";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-completion";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Completion";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Completion";}i:16;a:5:{s:2:"id";s:15:"joLOByXp3wuFf4R";s:8:"formdata";O:6:"object":3:{s:6:"column";s:24:"activity-completion-date";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:24:"Activity+Completion+Date";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+Completion+Date";}}}s:7:"filters";a:1:{s:8:"elements";a:5:{i:3;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"EkwgU8PubxHf46a";s:5:"label";s:6:"Course";s:4:"name";s:6:"course";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:4:"LIKE";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:4;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Role";}i:5;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"Ibf1pkc2Ekv1gIa";s:5:"label";s:13:"Date+Enrolled";s:4:"name";s:12:"dateenrolled";s:4:"type";s:8:"datetime";s:8:"operator";s:6:"%3E%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:13:"Date+Enrolled";}i:7;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"iQ8bGHflamD0g0F";s:5:"label";s:14:"Date+Completed";s:4:"name";s:13:"datecompleted";s:4:"type";s:8:"datetime";s:8:"operator";s:6:"%3E%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:14:"Date+Completed";}i:10;a:5:{s:2:"id";s:15:"WYx4WFJuFkjyuAg";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"J7oBw9WfFaEdKBR";s:5:"label";s:13:"Activity+Name";s:4:"name";s:12:"activityname";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:13:"Activity+Name";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array( 'course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id,$record->id);
				$DB->execute($sql, $params);
			}
		}

		upgrade_plugin_savepoint(true, 2016031600, 'local', 'lms_reports');
    }

    if ($oldversion < 2016050200) {
		//$DB->set_debug(true);
		/*
		 * Course overview
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Course overview', 'courseoverview');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
			$record1->courseid = '1';
			$record1->ownerid = '2';
			$record1->visible = '1';
			$record1->name = 'Course overview';
			$record1->summary = 'Course overview<br>';
			$record1->type = 'courseoverview';
			$record1->pagination = '10';
			$record1->components = 'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:17:{i:0;a:5:{s:2:"id";s:15:"T1pRkohCK1D6nFR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:8:"lastname";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:1;a:5:{s:2:"id";s:15:"N7pPQ4C2lWa1fZ2";s:8:"formdata";O:6:"object":3:{s:6:"column";s:5:"email";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:2;a:5:{s:2:"id";s:15:"oBJaalrApfyTU6N";s:8:"formdata";O:6:"object":3:{s:6:"column";s:4:"role";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:4:"Role";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:3;a:5:{s:2:"id";s:15:"Ibf1pkc2Ekv1gIa";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"date-enrolled";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Date+Enrolled";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+Enrolled";}i:4;a:5:{s:2:"id";s:15:"iQ8bGHflamD0g0F";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"date-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Date+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+Completed";}i:5;a:5:{s:2:"id";s:15:"zKDV08lHkVY3iFZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:16:"course-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"%25+Course+progress";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"%25+Course+progress";}i:6;a:5:{s:2:"id";s:15:"Qmd7XBOCIMqgoIL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:11:"grade-final";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:11:"Grade+Final";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+Final";}i:7;a:5:{s:2:"id";s:15:"kF763S7L6t9j51l";s:8:"formdata";O:6:"object":3:{s:6:"column";s:15:"time-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:15:"Time+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+Dedication";}i:8;a:5:{s:2:"id";s:15:"EkwgU8PubxHf46a";s:8:"formdata";O:6:"object":3:{s:6:"column";s:10:"coursename";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:6:"Course";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:9;a:5:{s:2:"id";s:15:"CE0ysTXQYVrnmpZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:20:"activities-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:20:"Activities+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:10;a:5:{s:2:"id";s:15:"bPyBEcSP0omVH5V";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activities-assigned";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activities+Assigned";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+Assigned";}i:11;a:5:{s:2:"id";s:15:"J7oBw9WfFaEdKBR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-name";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:12;a:5:{s:2:"id";s:15:"SEGd6ULJ7095mnL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-type";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Type";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:13;a:5:{s:2:"id";s:15:"7EHM9DojrR7DYuq";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"activity-grade";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Activity+Grade";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:14;a:5:{s:2:"id";s:15:"WfT90uBlFQxV0Oh";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Dedication";}i:15;a:5:{s:2:"id";s:15:"nQNTbU9V8A6lZck";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-completion";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Completion";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Completion";}i:16;a:5:{s:2:"id";s:15:"joLOByXp3wuFf4R";s:8:"formdata";O:6:"object":3:{s:6:"column";s:24:"activity-completion-date";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:24:"Activity+Completion+Date";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+Completion+Date";}}}s:7:"filters";a:1:{s:8:"elements";a:5:{i:3;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"EkwgU8PubxHf46a";s:5:"label";s:6:"Course";s:4:"name";s:6:"course";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:4:"LIKE";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:4;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Role";}i:5;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"Ibf1pkc2Ekv1gIa";s:5:"label";s:13:"Date+Enrolled";s:4:"name";s:12:"dateenrolled";s:4:"type";s:8:"datetime";s:8:"operator";s:6:"%3E%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:13:"Date+Enrolled";}i:7;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"iQ8bGHflamD0g0F";s:5:"label";s:14:"Date+Completed";s:4:"name";s:13:"datecompleted";s:4:"type";s:8:"datetime";s:8:"operator";s:6:"%3E%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:14:"Date+Completed";}i:10;a:5:{s:2:"id";s:15:"WYx4WFJuFkjyuAg";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"J7oBw9WfFaEdKBR";s:5:"label";s:13:"Activity+Name";s:4:"name";s:12:"activityname";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:13:"Activity+Name";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}';
			$record1->export = '';
			$record1->jsordering = '1';
			$record1->global = '1';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);


			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Course overview', 'courseoverview');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid,$record->id);
				$DB->execute($sql, $params);
			}

		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
			$params = array(
				'a:4:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:17:{i:0;a:5:{s:2:"id";s:15:"T1pRkohCK1D6nFR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:8:"lastname";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:1;a:5:{s:2:"id";s:15:"N7pPQ4C2lWa1fZ2";s:8:"formdata";O:6:"object":3:{s:6:"column";s:5:"email";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:2;a:5:{s:2:"id";s:15:"oBJaalrApfyTU6N";s:8:"formdata";O:6:"object":3:{s:6:"column";s:4:"role";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:4:"Role";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:3;a:5:{s:2:"id";s:15:"Ibf1pkc2Ekv1gIa";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"date-enrolled";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Date+Enrolled";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+Enrolled";}i:4;a:5:{s:2:"id";s:15:"iQ8bGHflamD0g0F";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"date-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Date+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+Completed";}i:5;a:5:{s:2:"id";s:15:"zKDV08lHkVY3iFZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:16:"course-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"%25+Course+progress";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"%25+Course+progress";}i:6;a:5:{s:2:"id";s:15:"Qmd7XBOCIMqgoIL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:11:"grade-final";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:11:"Grade+Final";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+Final";}i:7;a:5:{s:2:"id";s:15:"kF763S7L6t9j51l";s:8:"formdata";O:6:"object":3:{s:6:"column";s:15:"time-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:15:"Time+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+Dedication";}i:8;a:5:{s:2:"id";s:15:"EkwgU8PubxHf46a";s:8:"formdata";O:6:"object":3:{s:6:"column";s:10:"coursename";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:6:"Course";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Course";}i:9;a:5:{s:2:"id";s:15:"CE0ysTXQYVrnmpZ";s:8:"formdata";O:6:"object":3:{s:6:"column";s:20:"activities-completed";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:20:"Activities+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:10;a:5:{s:2:"id";s:15:"bPyBEcSP0omVH5V";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activities-assigned";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activities+Assigned";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+Assigned";}i:11;a:5:{s:2:"id";s:15:"J7oBw9WfFaEdKBR";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-name";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:12;a:5:{s:2:"id";s:15:"SEGd6ULJ7095mnL";s:8:"formdata";O:6:"object":3:{s:6:"column";s:13:"activity-type";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:13:"Activity+Type";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:13;a:5:{s:2:"id";s:15:"7EHM9DojrR7DYuq";s:8:"formdata";O:6:"object":3:{s:6:"column";s:14:"activity-grade";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:14:"Activity+Grade";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:14;a:5:{s:2:"id";s:15:"WfT90uBlFQxV0Oh";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-dedication";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Dedication";}i:15;a:5:{s:2:"id";s:15:"nQNTbU9V8A6lZck";s:8:"formdata";O:6:"object":3:{s:6:"column";s:19:"activity-completion";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:19:"Activity+Completion";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Completion";}i:16;a:5:{s:2:"id";s:15:"joLOByXp3wuFf4R";s:8:"formdata";O:6:"object":3:{s:6:"column";s:24:"activity-completion-date";s:12:"submitbutton";s:3:"Add";s:9:"columname";s:24:"Activity+Completion+Date";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+Completion+Date";}}}s:7:"filters";a:1:{s:8:"elements";a:5:{i:3;a:5:{s:2:"id";s:15:"i2fxiy6mEYngfmn";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"EkwgU8PubxHf46a";s:5:"label";s:6:"Course";s:4:"name";s:6:"course";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:4:"LIKE";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:6:"Course";}i:4;a:5:{s:2:"id";s:15:"I9aStXxtVOzfG3A";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Role";s:4:"name";s:6:"roleid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Brole%7D";s:5:"field";s:14:"id%2Cshortname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:3:"rid";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Role";}i:5;a:5:{s:2:"id";s:15:"wdu9cLDiE7c9b2f";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"Ibf1pkc2Ekv1gIa";s:5:"label";s:13:"Date+Enrolled";s:4:"name";s:12:"dateenrolled";s:4:"type";s:8:"datetime";s:8:"operator";s:6:"%3E%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:13:"Date+Enrolled";}i:7;a:5:{s:2:"id";s:15:"VittEjlVri98zgb";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"iQ8bGHflamD0g0F";s:5:"label";s:14:"Date+Completed";s:4:"name";s:13:"datecompleted";s:4:"type";s:8:"datetime";s:8:"operator";s:6:"%3E%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:14:"Date+Completed";}i:10;a:5:{s:2:"id";s:15:"WYx4WFJuFkjyuAg";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"J7oBw9WfFaEdKBR";s:5:"label";s:13:"Activity+Name";s:4:"name";s:12:"activityname";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:13:"Activity+Name";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array( 'course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id,$record->id);
				$DB->execute($sql, $params);
			}
		}

		/*
		 * Quiz overview
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Quiz overview', 'sql');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
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
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Quiz overview', 'sql');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? || `name`=? ";
			$params= array('quiz-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$records->id);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
			$params = array(
				'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:1359:"SELECT%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Fview.php%3Fid%3D%27%2C+cm.id%2C%27%22+%3E%27%2Cq.name%2C%27%3C%2Fa%3E%27%29+AS+%27Quiz%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C%27%22+%3E%27%2Cc.shortname%2C%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C%0D%0A+s.name%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Doverview%26id%3D%27%2C+cm.id%2C%27%22+%3EGrades+report%3C%2Fa%3E%27%29+AS+%27Grades+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Dresponses%26id%3D%27%2C+cm.id%2C%27%22+%3EResponses+report%3C%2Fa%3E%27%29+AS+%27Responses+report%27%2C%0D%0A+CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fmod%2Fquiz%2Freport.php%3Fmode%3Dstatistics%26id%3D%27%2C+cm.id%2C%27%22+%3EStatistics%3C%2Fa%3E%27%29+AS+%27Statistics%27%0D%0AFROM%0D%0A+%7Bcourse%7D+as+c%0D%0A+JOIN+%7Bcourse_modules%7D+AS+cm+ON+c.id+%3D+cm.course%0D%0A+JOIN+%7Bmodules%7D+AS+m+ON+m.id+%3D+%28cm.module%29%0D%0A+JOIN+%7Bcourse_sections%7D+AS+s+ON+%28cm.section+%3D+s.id%29%0D%0A+JOIN+%7Bquiz%7D+AS+q+ON+%28q.id+%3D+cm.instance%29%0D%0AWHERE%0D%0A+m.name+%3D+%22quiz%22+%25%25FILTER_CUSTOM%25%25+%0D%0A";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:7:"filters";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"1kFLkiVFgEQyOS5";s:8:"formdata";O:6:"object":10:{s:5:"label";s:7:"Courses";s:4:"name";s:8:"courseid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:12:"%7Bcourse%7D";s:5:"field";s:13:"id%2Cfullname";s:5:"where";s:0:"";s:7:"depends";s:0:"";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"c.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:7:"Courses";}i:1;a:5:{s:2:"id";s:15:"hHnegYSYm0crvFs";s:8:"formdata";O:6:"object":10:{s:5:"label";s:4:"Quiz";s:4:"name";s:6:"quizid";s:4:"type";s:15:"selectlisttable";s:5:"table";s:10:"%7Bquiz%7D";s:5:"field";s:9:"id%2Cname";s:5:"where";s:0:"";s:7:"depends";s:17:"courseid%2Ccourse";s:8:"operator";s:3:"%3D";s:6:"column";s:4:"q.id";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+filter";s:7:"summary";s:4:"Quiz";}}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('quiz-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('quiz-overview','/blocks/configurable_reports/viewreport.php?id=','1','9',$records->id);
				$DB->execute($sql, $params);
			}
		}

		/*
		 * Most viewed courses
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Most viewed courses', 'sql');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
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
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Most viewed courses', 'sql');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Most viewed courses', 'sql');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('most-viewed-courses');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('most-viewed-courses','/blocks/configurable_reports/viewreport.php?id=','1','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('most-viewed-courses','/blocks/configurable_reports/viewreport.php?id=','1','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
			$params = array(
				'a:2:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:564:"SELECT+%0D%0A%09COUNT%281%29+%27Views%27%2C%0D%0A%09CONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C+%0D%0A%09cc.name+%27Category%27+%0D%0AFROM+%7Bcourse%7D+c%0D%0AINNER+JOIN+%7Blog%7D+l+ON+%28l.course%3Dc.id%29+%0D%0AINNER+JOIN+%7Bcourse_categories%7D+cc+ON+%28c.category%3Dcc.id%29%0D%0AWHERE+%0D%0A%09c.visible%3D1+AND+c.id%3C%3E1+%0D%0A%25%25FILTER_CUSTOM%25%25+%0D%0AGROUP+BY%0D%0A%09c.id%0D%0AORDER+BY%0D%0A%09%60Views%60+DESC";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}s:4:"plot";a:1:{s:8:"elements";a:0:{}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('most-viewed-courses');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('most-viewed-courses','/blocks/configurable_reports/viewreport.php?id=','1','10',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('most-viewed-courses','/blocks/configurable_reports/viewreport.php?id=','1','10',$records->id);
				$DB->execute($sql, $params);
			}
		}
		/*
		 * Most popular courses by enrollment
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Most popular courses by enrollment', 'sql');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
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
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Most popular courses by enrollment', 'sql');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Most popular courses by enrollment', 'sql');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('most-popular-courses-by-enrollment');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('most-popular-courses-by-enrollment','/blocks/configurable_reports/viewreport.php?id=','1','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('most-popular-courses-by-enrollment','/blocks/configurable_reports/viewreport.php?id=','1','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
			$params = array(
				'a:1:{s:9:"customsql";a:1:{s:6:"config";O:6:"object":3:{s:8:"querysql";s:554:"SELECT+%0D%0ACONCAT%28%27%3Ca+target%3D%22_blank%22+href%3D%22%25%25WWWROOT%25%25%2Fcourse%2Fview.php%3Fid%3D%27%2C+c.id%2C+%27%22+%3E%27%2C+c.fullname%2C+%27%3C%2Fa%3E%27%29+AS+%27Course%27%2C%0D%0ACOUNT%28ue.id%29+AS+%27Enrolled%27%0D%0AFROM+%7Bcourse%7D+c+%0D%0AJOIN+%7Benrol%7D+en+ON+%28en.courseid+%3D+c.id%29%0D%0AJOIN+%7Buser_enrolments%7D+ue+ON+%28ue.enrolid+%3D+en.id%29%0D%0AJOIN+%7Buser%7D+u+ON+%28u.id%3Due.userid%29%0D%0AWHERE%0D%0A%09u.deleted%3D0%0D%0A%25%25FILTER_CUSTOM%25%25+%0D%0AGROUP+BY+c.id%0D%0AORDER+BY++%0D%0A+%60Enrolled%60+DESC";s:8:"courseid";i:1;s:12:"submitbutton";s:12:"Save+changes";}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('most-popular-courses-by-enrollment');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('most-popular-courses-by-enrollment','/blocks/configurable_reports/viewreport.php?id=','1','10',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('most-popular-courses-by-enrollment','/blocks/configurable_reports/viewreport.php?id=','1','10',$records->id);
				$DB->execute($sql, $params);
			}
		}
		/*
		 * Logins per day
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Logins Per day', 'courseoverview');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
			$record1->courseid = '1';
			$record1->ownerid = '2';
			$record1->visible = '1';
			$record1->name = 'Logins Per day';
			$record1->summary = '';
			$record1->type = 'courseoverview';
			$record1->pagination = '10';
			$record1->components = 'a:2:{s:7:"columns";a:1:{s:8:"elements";a:4:{i:0;a:5:{s:2:"id";s:15:"hMaRORtkieOwu7p";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"username";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:9:"User+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"User+Name";}i:1;a:5:{s:2:"id";s:15:"Z3oKYg19UgdzFch";s:8:"formdata";O:6:"object":4:{s:6:"column";s:9:"firstname";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:10:"First+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:10:"First+Name";}i:2;a:5:{s:2:"id";s:15:"inBRSwgccwVeNQu";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"lastname";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:3;a:5:{s:2:"id";s:15:"axuYT49yYcgmy9l";s:8:"formdata";O:6:"object":4:{s:6:"column";s:11:"user-logins";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:16:"Number+of+Logins";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:16:"Number+of+Logins";}}}s:7:"filters";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"AdhSazh7xpqumHj";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"hMaRORtkieOwu7p";s:5:"label";s:9:"User+Name";s:4:"name";s:8:"username";s:4:"type";s:8:"opentext";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:9:"User+Name";}i:1;a:5:{s:2:"id";s:15:"5enVIzxcnA63B12";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"axuYT49yYcgmy9l";s:5:"label";s:16:"Number+of+Logins";s:4:"name";s:14:"numberoflogins";s:4:"type";s:8:"opentext";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:16:"Number+of+Logins";}}}}';
			$record1->export = 'csv,ods,xls,';
			$record1->jsordering = '1';
			$record1->global = '1';
			$record1->lastexecutiontime = '50';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Logins Per day', 'courseoverview');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Logins Per day', 'courseoverview');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('logins-per-day-slms');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('logins-per-day-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('logins-per-day-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
			$params = array(
				'a:2:{s:7:"columns";a:1:{s:8:"elements";a:4:{i:0;a:5:{s:2:"id";s:15:"hMaRORtkieOwu7p";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"username";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:9:"User+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"User+Name";}i:1;a:5:{s:2:"id";s:15:"Z3oKYg19UgdzFch";s:8:"formdata";O:6:"object":4:{s:6:"column";s:9:"firstname";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:10:"First+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:10:"First+Name";}i:2;a:5:{s:2:"id";s:15:"inBRSwgccwVeNQu";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"lastname";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:3;a:5:{s:2:"id";s:15:"axuYT49yYcgmy9l";s:8:"formdata";O:6:"object":4:{s:6:"column";s:11:"user-logins";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:16:"Number+of+Logins";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:16:"Number+of+Logins";}}}s:7:"filters";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"AdhSazh7xpqumHj";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"hMaRORtkieOwu7p";s:5:"label";s:9:"User+Name";s:4:"name";s:8:"username";s:4:"type";s:8:"opentext";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:9:"User+Name";}i:1;a:5:{s:2:"id";s:15:"5enVIzxcnA63B12";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"axuYT49yYcgmy9l";s:5:"label";s:16:"Number+of+Logins";s:4:"name";s:14:"numberoflogins";s:4:"type";s:8:"opentext";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:16:"Number+of+Logins";}}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('logins-per-day-slms');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('logins-per-day-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('logins-per-day-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}
		/*
		 * Users registration
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Users registration', 'courseoverview');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
			$record1->courseid = 1 ;
	        $record1->ownerid = 2 ;
	        $record1->visible = 1 ;
	        $record1->name = 'Users registration' ;
	        $record1->summary = '' ;
	        $record1->type = 'courseoverview' ;
	        $record1->pagination = 10 ;
	        $record1->components = 'a:2:{s:7:"columns";a:1:{s:8:"elements";a:5:{i:0;a:5:{s:2:"id";s:15:"DoYiLPYGbF6CbDc";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"username";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:9:"User+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"User+Name";}i:1;a:5:{s:2:"id";s:15:"5blQ2uX1YhSLx0M";s:8:"formdata";O:6:"object":4:{s:6:"column";s:9:"firstname";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:10:"First+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:10:"First+Name";}i:2;a:5:{s:2:"id";s:15:"T4w8yVBJnDwDyxQ";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"lastname";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:3;a:5:{s:2:"id";s:15:"rZiHB8I1SuOVxhf";s:8:"formdata";O:6:"object":4:{s:6:"column";s:5:"email";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:4;a:5:{s:2:"id";s:15:"kwYqwqnX2jqV2aC";s:8:"formdata";O:6:"object":4:{s:6:"column";s:22:"user-registration-date";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:17:"Registration+date";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:17:"Registration+date";}}}s:7:"filters";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"AkY5o4877K7DZvs";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"DoYiLPYGbF6CbDc";s:5:"label";s:9:"User+Name";s:4:"name";s:8:"username";s:4:"type";s:8:"opentext";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:9:"User+Name";}i:1;a:5:{s:2:"id";s:15:"u56Ukq6w2OEI6Ew";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"kwYqwqnX2jqV2aC";s:5:"label";s:17:"Registration+date";s:4:"name";s:16:"registrationdate";s:4:"type";s:8:"datetime";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:17:"Registration+date";}}}}' ;
	        $record1->export = 'csv,ods,xls,' ;
	        $record1->jsordering = 1 ;
	        $record1->global = 1 ;
	        $record1->lastexecutiontime = NULL ;
	        $record1->cron = 0 ;
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Users registration', 'courseoverview');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Users registration', 'courseoverview');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('users-registration-slms');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('users-registration-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('users-registration-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
			$params = array(
				'a:2:{s:7:"columns";a:1:{s:8:"elements";a:5:{i:0;a:5:{s:2:"id";s:15:"DoYiLPYGbF6CbDc";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"username";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:9:"User+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"User+Name";}i:1;a:5:{s:2:"id";s:15:"5blQ2uX1YhSLx0M";s:8:"formdata";O:6:"object":4:{s:6:"column";s:9:"firstname";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:10:"First+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:10:"First+Name";}i:2;a:5:{s:2:"id";s:15:"T4w8yVBJnDwDyxQ";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"lastname";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:3;a:5:{s:2:"id";s:15:"rZiHB8I1SuOVxhf";s:8:"formdata";O:6:"object":4:{s:6:"column";s:5:"email";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:4;a:5:{s:2:"id";s:15:"kwYqwqnX2jqV2aC";s:8:"formdata";O:6:"object":4:{s:6:"column";s:22:"user-registration-date";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:17:"Registration+date";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:17:"Registration+date";}}}s:7:"filters";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"AkY5o4877K7DZvs";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"DoYiLPYGbF6CbDc";s:5:"label";s:9:"User+Name";s:4:"name";s:8:"username";s:4:"type";s:8:"opentext";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:9:"User+Name";}i:1;a:5:{s:2:"id";s:15:"u56Ukq6w2OEI6Ew";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"kwYqwqnX2jqV2aC";s:5:"label";s:17:"Registration+date";s:4:"name";s:16:"registrationdate";s:4:"type";s:8:"datetime";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:17:"Registration+date";}}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('users-registration-slms');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('users-registration-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('users-registration-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}
		/*
		 * Total Users
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Total Users', 'courseoverview');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
	        $record1->courseid = 1 ;
	        $record1->ownerid = 2 ;
	        $record1->visible = 1 ;
	        $record1->name = 'Total Users' ;
	        $record1->summary = '' ;
	        $record1->type = 'courseoverview' ;
	        $record1->pagination = 10 ;
	        $record1->components = 'a:2:{s:7:"columns";a:1:{s:8:"elements";a:5:{i:0;a:5:{s:2:"id";s:15:"6LZ2PuoNV3FDpfk";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"username";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:9:"User+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"User+Name";}i:1;a:5:{s:2:"id";s:15:"WCwBWATaRi9jRSe";s:8:"formdata";O:6:"object":4:{s:6:"column";s:9:"firstname";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:10:"First+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:10:"First+Name";}i:2;a:5:{s:2:"id";s:15:"Vt4iIoVDA2Z8Vmf";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"lastname";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:3;a:5:{s:2:"id";s:15:"pxrd3dtRf6xMjn4";s:8:"formdata";O:6:"object":4:{s:6:"column";s:5:"email";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:4;a:5:{s:2:"id";s:15:"wBQdyiMqKRhqX1t";s:8:"formdata";O:6:"object":4:{s:6:"column";s:10:"userstatus";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:6:"Status";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Status";}}}s:7:"filters";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"6jqRLKhBX9G3BCH";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"6LZ2PuoNV3FDpfk";s:5:"label";s:9:"User+Name";s:4:"name";s:8:"username";s:4:"type";s:8:"opentext";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:9:"User+Name";}i:1;a:5:{s:2:"id";s:15:"WmxX8Kpb4qCjqwO";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"wBQdyiMqKRhqX1t";s:5:"label";s:6:"Status";s:4:"name";s:6:"status";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:6:"Status";}}}}' ;
	        $record1->export = 'csv,ods,xls,' ;
	        $record1->jsordering = 1 ;
	        $record1->global = 1 ;
	        $record1->lastexecutiontime = NULL ;
	        $record1->cron = 0 ;
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Total Users', 'courseoverview');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Total Users', 'courseoverview');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('total-users-slms');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('total-users-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('total-users-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
			$params = array(
				'a:2:{s:7:"columns";a:1:{s:8:"elements";a:5:{i:0;a:5:{s:2:"id";s:15:"6LZ2PuoNV3FDpfk";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"username";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:9:"User+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"User+Name";}i:1;a:5:{s:2:"id";s:15:"WCwBWATaRi9jRSe";s:8:"formdata";O:6:"object":4:{s:6:"column";s:9:"firstname";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:10:"First+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:10:"First+Name";}i:2;a:5:{s:2:"id";s:15:"Vt4iIoVDA2Z8Vmf";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"lastname";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:3;a:5:{s:2:"id";s:15:"pxrd3dtRf6xMjn4";s:8:"formdata";O:6:"object":4:{s:6:"column";s:5:"email";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:4;a:5:{s:2:"id";s:15:"wBQdyiMqKRhqX1t";s:8:"formdata";O:6:"object":4:{s:6:"column";s:10:"userstatus";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:6:"Status";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:6:"Status";}}}s:7:"filters";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"6jqRLKhBX9G3BCH";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"6LZ2PuoNV3FDpfk";s:5:"label";s:9:"User+Name";s:4:"name";s:8:"username";s:4:"type";s:8:"opentext";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:9:"User+Name";}i:1;a:5:{s:2:"id";s:15:"WmxX8Kpb4qCjqwO";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"wBQdyiMqKRhqX1t";s:5:"label";s:6:"Status";s:4:"name";s:6:"status";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:6:"Status";}}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('total-users-slms');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('total-users-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('total-users-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}
		/*
		 * Most popular courses by enrollment
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Most popular courses by enrollment', 'courseoverview');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
	        $record1->courseid = 1 ;
	        $record1->ownerid = 2 ;
	        $record1->visible = 1 ;
	        $record1->name = 'Most popular courses by enrollment' ;
	        $record1->summary = '' ;
	        $record1->type = 'courseoverview' ;
	        $record1->pagination = 10 ;
	        $record1->components = 'a:2:{s:7:"columns";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"wLXQEeGKnCkrttb";s:8:"formdata";O:6:"object":4:{s:6:"column";s:10:"coursename";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:11:"Course+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Course+Name";}i:1;a:5:{s:2:"id";s:15:"8oLKxHxHjkL7btD";s:8:"formdata";O:6:"object":4:{s:6:"column";s:23:"number_enrollees_course";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:33:"Number+of+enrollees+in+the+course";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:33:"Number+of+enrollees+in+the+course";}}}s:7:"filters";a:1:{s:8:"elements";a:1:{i:0;a:5:{s:2:"id";s:15:"oMDBx7cIBtMldP7";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"wLXQEeGKnCkrttb";s:5:"label";s:11:"Course+Name";s:4:"name";s:10:"coursename";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:11:"Course+Name";}}}}' ;
	        $record1->export = 'csv,ods,xls,' ;
	        $record1->jsordering = 1 ;
	        $record1->global = 1 ;
	        $record1->lastexecutiontime = NULL ;
	        $record1->cron = 0 ;
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Most popular courses by enrollment', 'courseoverview');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Most popular courses by enrollment', 'courseoverview');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('courses-by-enrollment-slms');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('courses-by-enrollment-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('courses-by-enrollment-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
			$params = array(
				'a:2:{s:7:"columns";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"wLXQEeGKnCkrttb";s:8:"formdata";O:6:"object":4:{s:6:"column";s:10:"coursename";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:11:"Course+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Course+Name";}i:1;a:5:{s:2:"id";s:15:"8oLKxHxHjkL7btD";s:8:"formdata";O:6:"object":4:{s:6:"column";s:23:"number_enrollees_course";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:33:"Number+of+enrollees+in+the+course";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:33:"Number+of+enrollees+in+the+course";}}}s:7:"filters";a:1:{s:8:"elements";a:1:{i:0;a:5:{s:2:"id";s:15:"oMDBx7cIBtMldP7";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"wLXQEeGKnCkrttb";s:5:"label";s:11:"Course+Name";s:4:"name";s:10:"coursename";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:11:"Course+Name";}}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('courses-by-enrollment-slms');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('courses-by-enrollment-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('courses-by-enrollment-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}
		/*
		 * Most viewed courses
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Most viewed courses', 'courseoverview');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
	        $record1->courseid = 1 ;
	        $record1->ownerid = 2 ;
	        $record1->visible = 1 ;
	        $record1->name = 'Most viewed courses' ;
	        $record1->summary = '' ;
	        $record1->type = 'courseoverview' ;
	        $record1->pagination = 10 ;
	        $record1->components = 'a:2:{s:7:"columns";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"jOK65oasLdLr9hE";s:8:"formdata";O:6:"object":4:{s:6:"column";s:10:"coursename";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:11:"Course+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Course+Name";}i:1;a:5:{s:2:"id";s:15:"jMw1tUkPPOdCPWR";s:8:"formdata";O:6:"object":4:{s:6:"column";s:12:"course-views";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:12:"Course+Views";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:12:"Course+Views";}}}s:7:"filters";a:1:{s:8:"elements";a:1:{i:0;a:5:{s:2:"id";s:15:"FQ0w99rZcvNCehK";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"jOK65oasLdLr9hE";s:5:"label";s:11:"Course+Name";s:4:"name";s:10:"coursename";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:11:"Course+Name";}}}}' ;
	        $record1->export = 'csv,ods,xls,' ;
	        $record1->jsordering = 1 ;
	        $record1->global = 1 ;
	        $record1->lastexecutiontime = NULL ;
	        $record1->cron = 0 ;
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Most viewed courses', 'courseoverview');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Most viewed courses', 'courseoverview');
			$record = $DB->get_record_sql($sql, $params );

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('course-views-slms');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-views-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('course-views-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
			$params = array(
				'a:2:{s:7:"columns";a:1:{s:8:"elements";a:2:{i:0;a:5:{s:2:"id";s:15:"jOK65oasLdLr9hE";s:8:"formdata";O:6:"object":4:{s:6:"column";s:10:"coursename";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:11:"Course+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Course+Name";}i:1;a:5:{s:2:"id";s:15:"jMw1tUkPPOdCPWR";s:8:"formdata";O:6:"object":4:{s:6:"column";s:12:"course-views";s:6:"wizard";s:1:"1";s:12:"submitbutton";s:4:"Next";s:9:"columname";s:12:"Course+Views";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:12:"Course+Views";}}}s:7:"filters";a:1:{s:8:"elements";a:1:{i:0;a:5:{s:2:"id";s:15:"FQ0w99rZcvNCehK";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"jOK65oasLdLr9hE";s:5:"label";s:11:"Course+Name";s:4:"name";s:10:"coursename";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:11:"Course+Name";}}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('course-views-slms');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-views-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=? WHERE `idcr`=? ";
				$params = array('course-views-slms','/blocks/configurable_reports/viewreport.php?id=','3','10',$lastinsertid);
				$DB->execute($sql, $params);
			}
		}

		upgrade_plugin_savepoint(true, 2016050200, 'local', 'lms_reports');
    }

    if ($oldversion < 2016061001) {
		//$DB->set_debug(true);
		/*
		 * Course overview
		 */
		$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
		$params= array('1', 'Course overview', 'courseoverview');
		$records = $DB->get_record_sql($sql, $params );
		if(empty($records->id)){
			$record1 = new stdClass();
			$record1->courseid = '1';
			$record1->ownerid = '2';
			$record1->visible = '1';
			$record1->name = 'Course overview';
			$record1->summary = 'Course overview<br>';
			$record1->type = 'courseoverview';
			$record1->pagination = '10';
			$record1->components = 'a:5:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:17:{i:0;a:5:{s:2:"id";s:15:"9MF5lNNXLFheP3d";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"lastname";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:1;a:5:{s:2:"id";s:15:"Ekyp8yQlNMjcRzC";s:8:"formdata";O:6:"object":4:{s:6:"column";s:5:"email";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:2;a:5:{s:2:"id";s:15:"0dDvgtN1H0HaBXu";s:8:"formdata";O:6:"object":4:{s:6:"column";s:4:"role";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:4:"Role";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:3;a:5:{s:2:"id";s:15:"8Tt0GPfkfijTHtp";s:8:"formdata";O:6:"object":4:{s:6:"column";s:13:"date-enrolled";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:13:"Date+Enrolled";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+Enrolled";}i:4;a:5:{s:2:"id";s:15:"O1aYnMl1z30wF8l";s:8:"formdata";O:6:"object":4:{s:6:"column";s:14:"date-completed";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:14:"Date+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+Completed";}i:5;a:5:{s:2:"id";s:15:"ryMO1fNQX0XVPlD";s:8:"formdata";O:6:"object":4:{s:6:"column";s:16:"course-completed";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:19:"%25+Course+progress";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"%25+Course+progress";}i:6;a:5:{s:2:"id";s:15:"RNjxHuRE3FQ0ERA";s:8:"formdata";O:6:"object":4:{s:6:"column";s:11:"grade-final";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:11:"Grade+Final";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+Final";}i:7;a:5:{s:2:"id";s:15:"0lqvY97uvNpP5CW";s:8:"formdata";O:6:"object":4:{s:6:"column";s:15:"time-dedication";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:15:"Time+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+Dedication";}i:8;a:5:{s:2:"id";s:15:"IUg1OMzsde6NnNa";s:8:"formdata";O:6:"object":4:{s:6:"column";s:10:"coursename";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:11:"Course+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Course+Name";}i:9;a:5:{s:2:"id";s:15:"o27bgE2dRjd2YOE";s:8:"formdata";O:6:"object":4:{s:6:"column";s:20:"activities-completed";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:20:"Activities+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:10;a:5:{s:2:"id";s:15:"Vdd7dSG30sDRmdh";s:8:"formdata";O:6:"object":4:{s:6:"column";s:19:"activities-assigned";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:19:"Activities+Assigned";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+Assigned";}i:11;a:5:{s:2:"id";s:15:"09tSGIWIZivcEk9";s:8:"formdata";O:6:"object":4:{s:6:"column";s:13:"activity-name";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:13:"Activity+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:12;a:5:{s:2:"id";s:15:"E6oRAkrnLzkc8bF";s:8:"formdata";O:6:"object":4:{s:6:"column";s:13:"activity-type";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:13:"Activity+Type";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:13;a:5:{s:2:"id";s:15:"DJyPj2GMO1AfpJS";s:8:"formdata";O:6:"object":4:{s:6:"column";s:14:"activity-grade";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:14:"Activity+Grade";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:14;a:5:{s:2:"id";s:15:"VD958F9WgOrGEjR";s:8:"formdata";O:6:"object":4:{s:6:"column";s:19:"activity-dedication";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:19:"Activity+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Dedication";}i:15;a:5:{s:2:"id";s:15:"i932VyH3MkhEP1I";s:8:"formdata";O:6:"object":4:{s:6:"column";s:19:"activity-completion";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:19:"Activity+Completion";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Completion";}i:16;a:5:{s:2:"id";s:15:"AHLFjFd44kWTUyJ";s:8:"formdata";O:6:"object":4:{s:6:"column";s:24:"activity-completion-date";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:24:"Activity+Completion+Date";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+Completion+Date";}}}s:7:"filters";a:1:{s:8:"elements";a:5:{i:0;a:5:{s:2:"id";s:15:"02ZEsSzVqvtvNc7";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"IUg1OMzsde6NnNa";s:5:"label";s:11:"Course+Name";s:4:"name";s:10:"coursename";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:11:"Course+Name";}i:1;a:5:{s:2:"id";s:15:"PO33UkvEwSafYPD";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"0dDvgtN1H0HaBXu";s:5:"label";s:4:"Role";s:4:"name";s:4:"role";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:4:"Role";}i:2;a:5:{s:2:"id";s:15:"BdE6MD6heNFitWK";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"8Tt0GPfkfijTHtp";s:5:"label";s:13:"Date+Enrolled";s:4:"name";s:12:"dateenrolled";s:4:"type";s:8:"datetime";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:13:"Date+Enrolled";}i:3;a:5:{s:2:"id";s:15:"2s0prXv3xBA39WS";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"O1aYnMl1z30wF8l";s:5:"label";s:14:"Date+Completed";s:4:"name";s:13:"datecompleted";s:4:"type";s:8:"datetime";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:14:"Date+Completed";}i:4;a:5:{s:2:"id";s:15:"4wmzqeXtdDVdklb";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"09tSGIWIZivcEk9";s:5:"label";s:13:"Activity+Name";s:4:"name";s:12:"activityname";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:13:"Activity+Name";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:4:"plot";a:1:{s:8:"elements";a:0:{}}}';
			$record1->export = '';
			$record1->jsordering = '1';
			$record1->global = '1';
			$record1->cron = '0';
			$lastinsertid = $DB->insert_record('block_configurable_reports', $record1, false);


			$sql="SELECT id FROM {block_configurable_reports} WHERE `courseid`=? AND `name`=? AND `type`=? ";
			$params= array('1', 'Course overview', 'courseoverview');
			$lastinsertid = $DB->get_record_sql($sql, $params );
			$lastinsertid = $lastinsertid->id;

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array('course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$lastinsertid,$record->id);
				$DB->execute($sql, $params);
			}

		}else{
			$sql = "UPDATE {block_configurable_reports} SET `components`=?, `global`=1 WHERE `id`=? ";
			$params = array(
				'a:5:{s:10:"conditions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:7:"columns";a:1:{s:8:"elements";a:17:{i:0;a:5:{s:2:"id";s:15:"9MF5lNNXLFheP3d";s:8:"formdata";O:6:"object":4:{s:6:"column";s:8:"lastname";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:9:"Last+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:9:"Last+Name";}i:1;a:5:{s:2:"id";s:15:"Ekyp8yQlNMjcRzC";s:8:"formdata";O:6:"object":4:{s:6:"column";s:5:"email";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:5:"Email";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:5:"Email";}i:2;a:5:{s:2:"id";s:15:"0dDvgtN1H0HaBXu";s:8:"formdata";O:6:"object":4:{s:6:"column";s:4:"role";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:4:"Role";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:4:"Role";}i:3;a:5:{s:2:"id";s:15:"8Tt0GPfkfijTHtp";s:8:"formdata";O:6:"object":4:{s:6:"column";s:13:"date-enrolled";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:13:"Date+Enrolled";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Date+Enrolled";}i:4;a:5:{s:2:"id";s:15:"O1aYnMl1z30wF8l";s:8:"formdata";O:6:"object":4:{s:6:"column";s:14:"date-completed";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:14:"Date+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Date+Completed";}i:5;a:5:{s:2:"id";s:15:"ryMO1fNQX0XVPlD";s:8:"formdata";O:6:"object":4:{s:6:"column";s:16:"course-completed";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:19:"%25+Course+progress";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"%25+Course+progress";}i:6;a:5:{s:2:"id";s:15:"RNjxHuRE3FQ0ERA";s:8:"formdata";O:6:"object":4:{s:6:"column";s:11:"grade-final";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:11:"Grade+Final";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Grade+Final";}i:7;a:5:{s:2:"id";s:15:"0lqvY97uvNpP5CW";s:8:"formdata";O:6:"object":4:{s:6:"column";s:15:"time-dedication";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:15:"Time+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:15:"Time+Dedication";}i:8;a:5:{s:2:"id";s:15:"IUg1OMzsde6NnNa";s:8:"formdata";O:6:"object":4:{s:6:"column";s:10:"coursename";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:11:"Course+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:11:"Course+Name";}i:9;a:5:{s:2:"id";s:15:"o27bgE2dRjd2YOE";s:8:"formdata";O:6:"object":4:{s:6:"column";s:20:"activities-completed";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:20:"Activities+Completed";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:20:"Activities+Completed";}i:10;a:5:{s:2:"id";s:15:"Vdd7dSG30sDRmdh";s:8:"formdata";O:6:"object":4:{s:6:"column";s:19:"activities-assigned";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:19:"Activities+Assigned";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activities+Assigned";}i:11;a:5:{s:2:"id";s:15:"09tSGIWIZivcEk9";s:8:"formdata";O:6:"object":4:{s:6:"column";s:13:"activity-name";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:13:"Activity+Name";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Name";}i:12;a:5:{s:2:"id";s:15:"E6oRAkrnLzkc8bF";s:8:"formdata";O:6:"object":4:{s:6:"column";s:13:"activity-type";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:13:"Activity+Type";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:13:"Activity+Type";}i:13;a:5:{s:2:"id";s:15:"DJyPj2GMO1AfpJS";s:8:"formdata";O:6:"object":4:{s:6:"column";s:14:"activity-grade";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:14:"Activity+Grade";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:14:"Activity+Grade";}i:14;a:5:{s:2:"id";s:15:"VD958F9WgOrGEjR";s:8:"formdata";O:6:"object":4:{s:6:"column";s:19:"activity-dedication";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:19:"Activity+Dedication";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Dedication";}i:15;a:5:{s:2:"id";s:15:"i932VyH3MkhEP1I";s:8:"formdata";O:6:"object":4:{s:6:"column";s:19:"activity-completion";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:19:"Activity+Completion";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:19:"Activity+Completion";}i:16;a:5:{s:2:"id";s:15:"AHLFjFd44kWTUyJ";s:8:"formdata";O:6:"object":4:{s:6:"column";s:24:"activity-completion-date";s:6:"wizard";s:1:"0";s:12:"submitbutton";s:4:"Save";s:9:"columname";s:24:"Activity+Completion+Date";}s:10:"pluginname";s:19:"courseoverviewfield";s:14:"pluginfullname";s:21:"Course+overview+field";s:7:"summary";s:24:"Activity+Completion+Date";}}}s:7:"filters";a:1:{s:8:"elements";a:5:{i:0;a:5:{s:2:"id";s:15:"02ZEsSzVqvtvNc7";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"IUg1OMzsde6NnNa";s:5:"label";s:11:"Course+Name";s:4:"name";s:10:"coursename";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:11:"Course+Name";}i:1;a:5:{s:2:"id";s:15:"PO33UkvEwSafYPD";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"0dDvgtN1H0HaBXu";s:5:"label";s:4:"Role";s:4:"name";s:4:"role";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:4:"Role";}i:2;a:5:{s:2:"id";s:15:"BdE6MD6heNFitWK";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"8Tt0GPfkfijTHtp";s:5:"label";s:13:"Date+Enrolled";s:4:"name";s:12:"dateenrolled";s:4:"type";s:8:"datetime";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:13:"Date+Enrolled";}i:3;a:5:{s:2:"id";s:15:"2s0prXv3xBA39WS";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"O1aYnMl1z30wF8l";s:5:"label";s:14:"Date+Completed";s:4:"name";s:13:"datecompleted";s:4:"type";s:8:"datetime";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:14:"Date+Completed";}i:4;a:5:{s:2:"id";s:15:"4wmzqeXtdDVdklb";s:8:"formdata";O:6:"object":7:{s:6:"column";s:15:"09tSGIWIZivcEk9";s:5:"label";s:13:"Activity+Name";s:4:"name";s:12:"activityname";s:4:"type";s:15:"selectlisttable";s:8:"operator";s:3:"%3D";s:7:"depends";s:0:"";s:12:"submitbutton";s:3:"Add";}s:10:"pluginname";s:12:"customfilter";s:14:"pluginfullname";s:13:"Custom+Filter";s:7:"summary";s:13:"Activity+Name";}}}s:11:"permissions";a:1:{s:6:"config";O:6:"object":1:{s:13:"conditionexpr";s:0:"";}}s:4:"plot";a:1:{s:8:"elements";a:0:{}}}',
				$records->id
			);
			$DB->execute($sql, $params);

			$sql="SELECT id FROM {local_lms_reports} WHERE `name`=? ";
			$params= array( 'course-overview');
			$record = $DB->get_record_sql($sql, $params );
			if(empty($record->id)){
				$sql = "INSERT INTO {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id);
				$DB->execute($sql, $params);
			}else{
				$sql = "UPDATE {local_lms_reports} SET `name`=?, `url`=?, `idtype`=?, `order`=?, `idcr`=? WHERE `id`=? ";
				$params = array('course-overview','/blocks/configurable_reports/viewreport.php?id=','1','8',$records->id,$record->id);
				$DB->execute($sql, $params);
			}
		}

		upgrade_plugin_savepoint(true, 2016061001, 'local', 'lms_reports');
    }

    if ($oldversion < 2018031201) {
		$sql = "DELETE FROM {local_lms_reports} WHERE name = 'engagement_analytics' ";
		$DB->execute($sql);
		upgrade_plugin_savepoint(true, 2018031201, 'local', 'lms_reports');
    }

     if ($oldversion < 2017070501)
     {
     	$sql = "UPDATE {local_lms_reports} SET state = 0 WHERE name = 'user_class_completion'";
		$update =$DB->execute($sql);

        if($update) {
     		$result = true;
     	} else {
     		$result = false;
     	}
     }
     
     if ($oldversion < 2018031203) {
                // Define field to be added to assign.
                $table = new xmldb_table('local_reports_schedule');

                $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
                $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
                $table->add_field('report', XMLDB_TYPE_CHAR, '255', null, null, null, null);
                $table->add_field('config', XMLDB_TYPE_TEXT, 'long', null, null, null, null);
                $table->add_index('userid_idx', XMLDB_INDEX_NOTUNIQUE, array('userid'));
                $table->add_index('report_idx', XMLDB_INDEX_NOTUNIQUE, array('report'));
                $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
                if (!$dbman->table_exists($table)) {
                    $dbman->create_table($table);
                }
                 
                $table = new xmldb_table('local_reports_schtask');
                 
                $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);;
                $table->add_field('plugin', XMLDB_TYPE_CHAR, '166', null, null, null, null);
                $table->add_field('taskname', XMLDB_TYPE_CHAR, '50', null, null, null, null);
                $table->add_field('callfile', XMLDB_TYPE_CHAR, '255', null, null, null, null);
                $table->add_field('callfunction', XMLDB_TYPE_CHAR, '255', null, null, null, null);
                $table->add_field('lastruntime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
                $table->add_field('nextruntime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
                $table->add_field('blocking', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
                $table->add_field('minute', XMLDB_TYPE_CHAR, '50', null, null, null, null);
                $table->add_field('hour', XMLDB_TYPE_CHAR, '50', null, null, null, null);
                $table->add_field('day', XMLDB_TYPE_CHAR, '50', null, null, null, null);
                $table->add_field('month', XMLDB_TYPE_CHAR, '50', null, null, null, null);
                $table->add_field('dayofweek', XMLDB_TYPE_CHAR, '50', null, null, null, null);
                $table->add_field('timezone', XMLDB_TYPE_CHAR, '100', null, null, null, null);
                $table->add_field('runsremaining', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
                $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
                $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
                $table->add_field('customized', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
                $table->add_field('blocked', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
                $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

                if (!$dbman->table_exists($table)) { $dbman->create_table($table); }
                
                $table = new xmldb_table('local_reports_wkflo');

                $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
                $table->add_field('type', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
                $table->add_field('subtype', XMLDB_TYPE_CHAR, '127', null, null, null, null);
                $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
                $table->add_field('data', XMLDB_TYPE_TEXT, 'long', null, null, null, null);
                $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
                    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
                
                if (!$dbman->table_exists($table)) {
                    $dbman->create_table($table);
                }

                upgrade_plugin_savepoint(true, 2018031203,'local', 'lms_reports');
        }
     

    return $result;
}
