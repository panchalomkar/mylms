<?php
// lib.php
require_once(__DIR__ . '/../../config.php');

//get business unit
function getdepartment()
{
	global $DB;

	$sqldepartment = 'SELECT DISTINCT department FROM {user} where confirmed = 1 and deleted = 0 AND suspended = 0 AND department != "" order by department ASC';
	$getdepartments = $DB->get_records_sql($sqldepartment, array());

	return $getdepartments;
}
//get specific business unit
function getUsersdepartment($userid)
{
	global $DB;

	$sqldepartment = 'SELECT department FROM {user} where id=?';
	$getdepartments = $DB->get_records_sql($sqldepartment, array($userid));

	return $getdepartments;
}

//get specific business unit
function getRatingdepartment($department)
{
	global $DB;

	$sqldepartment = 'SELECT id,username FROM {user} where department=?';
	$getdepartments = $DB->get_records_sql($sqldepartment, array($department));

	return $getdepartments;
}

//get user info role 
function getSearchRoles()
{
	global $DB;

	$searchRolesSql = "SELECT r.id, r.shortname FROM {role} as r INNER JOIN {user_info_field} as uif  ON r.shortname = uif.shortname  OR r.shortname = 'user' GROUP BY r.shortname";
	$getsearchRoles = $DB->get_records_sql($searchRolesSql, array());

	return $getsearchRoles;
}

//get competency title list count
function getListCompetencyTitleCount()
{
	global $DB;
	return $DB->count_records('competency_title', array('isdeleted' => 0));
}

//count pagination
function getPaginationDisplay($totalCount, $selectPageNo, $limit)
{
	$totalRecords = $totalCount;
	if ($selectPageNo == 1) {
		$startpage = 1;
	} else {
		$startpage = (int) $selectPageNo;
	}
	$pages = ceil($totalRecords / $limit);
	$start = ($selectPageNo - 1) * $limit;
	return array($pages, $start);
}

//get competency title list
function getListCompetencyTitle($start = 0, $limit = 0)
{
	global $USER, $CFG, $DB, $OUTPUT, $SESSION;
	if (!empty($SESSION->currenteditingcompany)) {
		$selectedcompany = $SESSION->currenteditingcompany;
	} else if (!empty($USER->profile->company)) {
		$usercompany = company::by_userid($USER->id);
		$selectedcompany = $usercompany->id;
	} else {
		$selectedcompany = "";
	}
	if ($selectedcompany) {
		$query = $selectedcompany;
	} else {
		$query = 0;
	}
	return $DB->get_records('competency_title', array('isdeleted' => 0, 'companyid' => $query), $sort = '', $fields = '*', $limitfrom = $start, $limitnum = $limit);
}

//specific data fetch from main heading title by Id
function getCompletencyTitleRecords($id = '')
{
	global $DB;
	$sqlForcompetency_title = "SELECT * FROM {competency_title} ";
	$where = '';
	if (!empty($id)) {
		$where .= 'WHERE id = ' . $id;
	}
	return $Forcompetency_title = $DB->get_records_sql($sqlForcompetency_title . $where, array());
}



//get sub competency list count
function getListSubCompetencyCount($id = '', $subid = '')
{
	global $DB;
	//return $DB->count_records('competency_category', array('isdeleted'=> 0));
	$sql = "SELECT * FROM {competency_category} WHERE isdeleted =0";
	$where = ' ';
	if (!empty($id)) {
		$where .= ' AND ctid = ' . $id;
	}
	if (!empty($subid)) {
		$where .= ' AND id = ' . $subid;
	}
	$totalcount = $DB->get_records_sql($sql . $where, array());
	return count($totalcount);
}

//get sub competency list
function getListSubCompetency($id = '', $subid = '', $start = 0, $limit = 10)
{
	global $USER, $CFG, $DB, $SESSION;

	$params = [];
	$where = ' WHERE cc.isdeleted = 0';

	// Get current selected company
	if (!empty($SESSION->currenteditingcompany)) {
		$selectedcompany = $SESSION->currenteditingcompany;
	} else if (!empty($USER->profile->company)) {
		$usercompany = company::by_userid($USER->id);
		$selectedcompany = $usercompany->id ?? '';
	} else {
		$selectedcompany = '';
	}

	// Add company condition if present
	if (!empty($selectedcompany)) {
		$where .= ' AND ct.companyid = :companyid';
		$params['companyid'] = $selectedcompany;
	}

	// Apply filters
	if (!empty($id)) {
		$where .= ' AND cc.ctid = :ctid';
		$params['ctid'] = $id;
	}

	if (!empty($subid)) {
		$where .= ' AND cc.id = :subid';
		$params['subid'] = $subid;
	}

	$sql = "SELECT cc.id as uniqueid, cc.*, ct.title 
            FROM {competency_category} cc 
            INNER JOIN {competency_title} ct ON ct.id = cc.ctid
            $where
            ORDER BY cc.timemodified DESC";

	// Add pagination safely
	return $DB->get_records_sql($sql, $params, $start, $limit);
}

//get selected sub competency
function getSelectedSubCompetency($ccid = '', $companyid = '')
{
	global $DB;

	$sql = "SELECT cc.*
            FROM {competency_category} cc
            JOIN {competency_title} ct ON ct.id = cc.ctid
            WHERE cc.isdeleted = 0";

	$params = [];

	if (!empty($ccid)) {
		$sql .= " AND cc.id = :ccid";
		$params['ccid'] = $ccid;
	}

	if (!empty($companyid)) {
		$sql .= " AND ct.companyid = :companyid";
		$params['companyid'] = $companyid;
	}

	// OPTIONAL: If competency_title also has isdeleted column
	// Uncomment the next two lines only if needed:
	// $sql .= " AND ct.isdeleted = 0";
	// (ensure the column exists first)

	return $DB->get_records_sql($sql, $params);
}




function getAllroles($roleid = '')
{
	global $DB;
	$roleSqlId = "SELECT r.id, r.shortname FROM {role} as r 
					INNER JOIN {user_info_field} as uif  
					ON r.shortname = uif.shortname OR r.shortname = 'user'";
	$where = '';
	if (!empty($roleid)) {
		$where .= 'WHERE r.id = ' . $roleid;
	}

	$where .= ' GROUP BY r.shortname';
	$roleResultId = $DB->get_records_sql($roleSqlId . $where, array());
	return $roleResultId;
}

function getCourseList($courseid = '')
{
	global $CFG, $DB, $USER, $PAGE, $SESSION;
	if (!empty($SESSION->currenteditingcompany)) {
		$selectedcompany = $SESSION->currenteditingcompany;
	} else if (!empty($USER->profile->company)) {
		$usercompany = company::by_userid($USER->id);
		$selectedcompany = $usercompany->id;
	} else {
		$selectedcompany = 0;
	}

	if ($selectedcompany) {
		$sqlForcoures = "SELECT c.* FROM {course} c INNER JOIN {company_course} cc ON c.id = cc.courseid WHERE c.id != 1 AND cc.companyid = $selectedcompany";
	} else {
		$sqlForcoures = "SELECT * FROM {course} WHERE id != 1 ";
	}

	$where = ' ';
	if (!empty($courseid)) {
		$where .= ' AND id = ' . $courseid;
	}
	return $courselists = $DB->get_records_sql($sqlForcoures . $where, array());
}


//get sub sub competency list count
function getListSubSubCompetencyCount($id = '', $subccid = '', $companyid = '')
{
	global $DB;

	$sql = "SELECT COUNT(ssc.id)
            FROM {competencies} ssc
            JOIN {competency_category} cc ON cc.id = ssc.ccid
            JOIN {competency_title} ct ON ct.id = cc.ctid
            WHERE ssc.isdeleted = 0 AND cc.isdeleted = 0 AND ct.isdeleted = 0";

	$params = [];

	if (!empty($companyid)) {
		$sql .= " AND ct.companyid = :companyid";
		$params['companyid'] = $companyid;
	}

	if (!empty($id)) {
		$sql .= " AND cc.id = :ccid";
		$params['ccid'] = $id;
	}

	if (!empty($subccid)) {
		$sql .= " AND ssc.id = :subccid";
		$params['subccid'] = $subccid;
	}

	return $DB->count_records_sql($sql, $params);
}



//get sub sub competency list
function getListSubSubCompetency($id = '', $subccid = '', $start = '', $limit = '', $companyid = '')
{
	global $DB;

	$sql = "SELECT ssc.*
            FROM {competencies} ssc
            JOIN {competency_category} cc ON cc.id = ssc.ccid
            JOIN {competency_title} ct ON ct.id = cc.ctid
            WHERE ssc.isdeleted = 0 AND cc.isdeleted = 0 AND ct.isdeleted = 0";

	$params = [];

	if (!empty($companyid)) {
		$sql .= " AND ct.companyid = :companyid";
		$params['companyid'] = $companyid;
	}

	if (!empty($id)) {
		$sql .= " AND cc.id = :ccid";
		$params['ccid'] = $id;
	}

	if (!empty($subccid)) {
		$sql .= " AND ssc.id = :subccid";
		$params['subccid'] = $subccid;
	}

	if (is_numeric($start) && is_numeric($limit)) {
		$sql .= " LIMIT $start, $limit";
	}

	return $DB->get_records_sql($sql, $params);
}



//get competency course list
function getComptencyCoursesList($id = '')
{
	global $DB;
	$sql = "Select c.*,cc.* from {competency_courses} cc INNER JOIN {course} c ON c.id=cc.courseid where cc.isdeleted=0";
	$where = ' ';
	if (!empty($id)) {
		$where .= ' AND  cc.competencyid = ' . $id;
	}
	return $DB->get_records_sql($sql . $where, array());
}

//view competency data show 
/*function getViewCompetencyData($id){
	global $DB;
	$searchSqlcomp = "SELECT cp.id, cc.id as cctid, cp.comptencyname, cc.name, r.shortname 
          FROM {competency_category} as cc 
          LEFT JOIN {competencies} as cp ON cc.id = cp.ccid 
          LEFT JOIN {role} as r ON cc.roleid = r.id 
          WHERE cc.isdeleted=0 and cc.ctid=? 
          ORDER by cc.id";
	return $DB->get_records_sql($searchSqlcomp, array($id));
}*/
function getViewCompetencyData($id)
{
	global $DB;
	//ROW_NUMBER() OVER (ORDER BY cc.id) AS num,
	$searchSqlcomp = "SELECT (@cnt := @cnt + 1) AS rowNumber, cc.id as cctid, cp.id, ct.id as ctitleid,   
                        cp.comptencyname, cc.name, r.shortname 
          FROM {competency_category} as cc 
          CROSS JOIN (SELECT @cnt := 0) AS dummy
          LEFT JOIN {competencies} as cp ON cc.id = cp.ccid 
          LEFT JOIN {competency_title} as ct ON ct.id = cc.ctid
          LEFT JOIN {role} as r ON cc.roleid = r.id 
          WHERE cc.isdeleted = 0 and cc.ctid = ? 
          ";
	return $DB->get_records_sql($searchSqlcomp, array($id));
}
function getSearchFieldsCompetency()
{

	$searchRoles = getSearchRoles();
	$buResult = getdepartment();
	$buselct = '';
	$viewselct = '';
	$buselct .= '<select name="svbuid" id="svbuid" class="form-control">
	<option value="">Select Business Unit</option>';
	foreach ($buResult as $key => $value) {
		$buselct .= '<option value="' . $value->department . '">' . $value->department . '</option>';
	}
	$buselct .= '</select>';

	$viewselct .= '<select name="svroleid" id="svroleid" class="form-control">
	<option value="">Select role</option>';
	foreach ($searchRoles as $role) {
		$viewselct .= "<option value='" . $role->id . "'>" . $role->shortname . "</option>";
	}
	$viewselct .= '</select>';
	return array($buselct, $viewselct);
}

function getManagerRatingMasterId($id, $tearmsid)
{
	global $DB;
	$managerSql = "select * from {manager_rating} where master_competencyid=? and tearms=?";
	$managerResult = $DB->get_records_sql($managerSql, array($id, $tearmsid));
	$managerArrCount = count($managerResult);
	$managerArr = array();
	if ($managerArrCount > 0) {
		foreach ($managerResult as $key => $value) {
			$managerArr['competencyid'] = $value->competencyid;
			$managerArr['subcomptencyid'] = $value->subcomptencyid;
			$managerArr['rating'] = $value->rating;
			$managerArr['finalrating'] = $value->finalrating;
		}
	} else {
		$managerArr['competencyid'] = '';
		$managerArr['subcomptencyid'] = '';
		$managerArr['rating'] = '';
		$managerArr['finalrating'] = '';
	}
	return array($managerArrCount, $managerArr);
}

function getStudentRatingMasterId($id, $tearmsid)
{
	global $DB;
	$studentSql = "select * from {sudent_rating} where master_competencyid=? and tearms=?";
	$studentResult = $DB->get_records_sql($studentSql, array($id, $tearmsid));
	$studentArrCount = count($studentResult);
	$studentArr = array();
	foreach ($studentResult as $key => $value) {
		$studentArr['competencyid'] = $value->competencyid;
		$studentArr['subcomptencyid'] = $value->subcomptencyid;
		$studentArr['rating'] = $value->rating;
	}
	return array($studentArrCount, $studentArr);

}


function getLandDRatingMasterId($id, $tearmsid)
{
	global $DB;
	$landdSql = "select * from {landd_rating} where master_competencyid=? and tearms=?";
	$landdResult = $DB->get_records_sql($landdSql, array($id, $tearmsid));
	$landdArrCount = count($landdResult);
	$landdArr = array();
	foreach ($landdResult as $key => $value) {
		$landdArr['competencyid'] = $value->competencyid;
		$landdArr['subcomptencyid'] = $value->subcomptencyid;
		$landdArr['rating'] = $value->rating;
		if ($value->landdstatus == 1) {
			$landdArr['landdstatus'] = $value->landdstatus;
			$landdArr['landdstatuschecked'] = 'selected="selected"';
		} else {
			$landdArr['landdstatus'] = $value->landdstatus;
			$landdArr['landdstatuschecked'] = 'selected="selected"';
		}
	}
	return array($landdArrCount, $landdArr);

}

//check manager rating exist
function getManagerRatingExists($masterid, $competencyid, $subcomptencyid, $tearms, $managerid)
{
	global $DB;
	$chksql = "select * from {manager_rating} where master_competencyid=? and competencyid=? and subcomptencyid=? and tearms=? and managerid=?";
	$checkResult = $DB->get_records_sql($chksql, array($masterid, $competencyid, $subcomptencyid, $tearms, $managerid));
	return $checkResult;
}

//check student rating exist
function getStudentRatingExists($masterid, $competencyid, $subcomptencyid, $tearms)
{
	global $DB;
	$chksql = "select * from {sudent_rating} where master_competencyid=? and competencyid=? and subcomptencyid=? and tearms=? ";
	$checkResult = $DB->get_records_sql($chksql, array($masterid, $competencyid, $subcomptencyid, $tearms));
	return $checkResult;
}

function getLanddRatingExists($masterid, $competencyid, $subcomptencyid, $tearms, $ldteamid)
{
	global $DB;
	$chksql = "select * from {landd_rating} where master_competencyid=? and competencyid=? and subcomptencyid=? and tearms=? and ldteamid=?";
	$checkResult = $DB->get_records_sql($chksql, array($masterid, $competencyid, $subcomptencyid, $tearms, $ldteamid));
	return $checkResult;
}
function getLandDRatingValueViaId($id, $tearms = 1)
{
	global $DB;
	$chksql = "select * from {landd_rating} where master_competencyid=? and tearms=? and landdstatus=1";
	$checkResult = $DB->get_records_sql($chksql, array($id, $tearms));
	$checkResultCount = count($checkResult);
	$landdArr = array();
	if ($checkResultCount > 0) {
		foreach ($checkResult as $key => $value) {
			$landdArr['rating'] = $value->rating;
		}
	} else {
		$landdArr['rating'] = '';
	}
	return array($checkResultCount, $landdArr);

}

function getexistingStudentRanking($userid, $tearms)
{
	global $DB;
	$sql = "SELECT  cu.id as cuid, sr.rating,sr.id as srid  
			FROM {sudent_rating} as sr 
			INNER JOIN {competency_users} as cu ON sr.master_competencyid = cu.id 
			WHERE cu.userid = ? AND sr.tearms = ?";
	return $DB->get_records_sql($sql, array($userid, $tearms));
}

function getexistingManagerRanking($userid, $tearms)
{
	global $DB;
	$sql = "SELECT  mr.id as mrid , cu.id as cuid, mr.rating
			FROM {manager_rating} as mr 
			INNER JOIN {competency_users} as cu ON mr.master_competencyid = cu.id 
			WHERE cu.userid = ? AND mr.tearms = ?";
	return $DB->get_records_sql($sql, array($userid, $tearms));
}


function getUsersIds($userid)
{
	global $DB;
	$sql = "SELECT uid.id, uid.data FROM {user_info_data} as uid 
            INNER JOIN {user_info_field} as uif ON uid.fieldid = uif.id 
            WHERE uid.userid = ?";

	return $DB->get_records_sql($sql, array($userid));
}

//get user list
function getuserlist()
{
	global $CFG, $DB, $USER, $PAGE, $SESSION;
	if (!empty($SESSION->currenteditingcompany)) {
		$selectedcompany = $SESSION->currenteditingcompany;
	} else if (!empty($USER->profile->company)) {
		$usercompany = company::by_userid($USER->id);
		$selectedcompany = $usercompany->id;
	} else {
		$selectedcompany = 0;
	}
	if ($selectedcompany) {
		$sqlusers = "SELECT DISTINCT u.id, u.firstname,u.lastname,u.username FROM {user} u 
	INNER JOIN {company_users} cu ON u.id = cu.userid 
	where u.confirmed = 1 and u.deleted = 0 AND u.suspended = 0 AND cu.companyid = $selectedcompany";
	} else {
		$sqlusers = 'SELECT DISTINCT id, firstname,lastname,username FROM {user} where confirmed = 1 and deleted = 0 AND suspended = 0';
	}

	$getusers = $DB->get_records_sql($sqlusers, array());

	return $getusers;
}

//get Main competency
function getmaincomplist()
{
	global $USER, $CFG, $DB, $OUTPUT, $SESSION;
	if (!empty($SESSION->currenteditingcompany)) {
		$selectedcompany = $SESSION->currenteditingcompany;
	} else if (!empty($USER->profile->company)) {
		$usercompany = company::by_userid($USER->id);
		$selectedcompany = $usercompany->id;
	} else {
		$selectedcompany = "";
	}
	if ($selectedcompany) {
		$query = "AND companyid = $selectedcompany";
	}

	$sqlmaincompetency = "SELECT id,title FROM {competency_title} where isdeleted = 0 $query";
	$getmaincompetency = $DB->get_records_sql($sqlmaincompetency, array());

	return $getmaincompetency;
}

//get User Report list count
function getListUserReportCount($query)
{
	global $DB;
	$sql = "select lr.id,u.username,ct.title,cc.name as subcompetency,c.comptencyname as subsubcomp,lr.rating,
                        MAX(CASE WHEN ud.fieldid = '1' THEN ud.data END) 'buhead',
						MAX(CASE WHEN ud.fieldid = '2' THEN ud.data END) 'manager',
						MAX(CASE WHEN ud.fieldid = '3' THEN ud.data END) 'finalmanager',
						MAX(CASE WHEN ud.fieldid = '4' THEN ud.data END) 'ldmanager' 
                        from {landd_rating} AS lr 
						inner join {competency_users} AS cu ON lr.master_competencyid=cu.id
                        inner join {competency_title} AS ct on cu.ctid=ct.id
						INNER join {competency_category} AS cc on lr.competencyid=cc.id
						INNER JOIN {competencies} AS c on lr.subcomptencyid=c.id
						inner join {user} AS u on cu.userid=u.id
						INNER JOIN {user_info_data} as ud on cu.userid=ud.userid
						inner join {user_info_field} uf on ud.fieldid=uf.id
						" . $query;
	$getresult = $DB->get_records_sql($sql);
	return count($getresult);

}
// //get  list
function getListUsercomp($start = 0, $limit = 0)
{
	global $DB;
	$sql = "select lr.id,u.username,ct.title,cc.name as subcompetency,c.comptencyname as subsubcomp,lr.rating,
                        MAX(CASE WHEN ud.fieldid = '1' THEN ud.data END) 'buhead',
						MAX(CASE WHEN ud.fieldid = '2' THEN ud.data END) 'manager',
						MAX(CASE WHEN ud.fieldid = '3' THEN ud.data END) 'finalmanager',
						MAX(CASE WHEN ud.fieldid = '4' THEN ud.data END) 'ldmanager' 
                        from {landd_rating} AS lr 
						inner join {competency_users} AS cu ON lr.master_competencyid=cu.id
                        inner join {competency_title} AS ct on cu.ctid=ct.id
						INNER join {competency_category} AS cc on lr.competencyid=cc.id
						INNER JOIN {competencies} AS c on lr.subcomptencyid=c.id
						inner join {user} AS u on cu.userid=u.id
						INNER JOIN {user_info_data} as ud on cu.userid=ud.userid
						inner join {user_info_field} uf on ud.fieldid=uf.id
						group by lr.id";
	$test .= ' LIMIT ' . $start . ' , ' . $limit;
	$result = $DB->get_records_sql($sql . $test, array());
	return $result;
}

//string title validation
function getValidateStringField($title)
{
	$error = '';
	if (empty($title)) {
		$error .= 'error';
	}
	return $error;
}

//number id validation
function getValidateNumberField($id)
{
	$error = '';
	if (!is_numeric($id)) {
		$error .= 'error';
	}
	return $error;
}

//business unit validation
function getValidateUnitExistsField($id)
{
	$error = '';
	if (empty($id)) {
		$error .= 'error';
	}
	return $error;
}

//land d rating terms
function previousLanddRating($masterid, $tearmsid)
{
	global $DB;
	if ($tearmsid == 1) {
		$year = date('Y') - 1;
		$tearmsid = 2;
	} else if ($tearmsid == 2) {
		$year = date('Y');
		$tearmsid = 1;
	}
	$previousRating = '';
	$sql = "select * from {competency_users} where id = ? and year = ?";
	$resultSql = $DB->get_records_sql($sql, array($masterid, $year));
	if (count($resultSql) > 0) {

		$landratingSql = "select * from {landd_rating} where master_competencyid=? and tearms=?";
		$landResult = $DB->get_records_sql($landratingSql, array($masterid, $tearmsid));
		$landArrCount = count($landResult);

		foreach ($landResult as $key => $value) {
			$previousRating = $value->rating;
		}
	} else {

		$previousRating = '-';
	}
	return $previousRating;
}
function getmanageruserlist($userid)
{
	global $DB;
	$sql = "SELECT DISTINCT u.id,u.firstname,u.lastname from {user_info_data} as ud 
			INNER join {user_info_field} as uf on ud.fieldid=uf.id 
			Inner join {user} u on ud.userid=u.id where ud.data LIKE '" . $userid . "%'";
	return $DB->get_records_sql($sql, array());
}
function getListLoginUserRptCount($query)
{
	global $DB;
	$sql = "select lr.id, concat(u.firstname,' ',u.lastname) AS fullname, u.id as userid, u.username,ct.title, cc.id as ccid, cc.name as subcompetency, c.id as comptencyid, c.comptencyname as subsubcomp,lr.rating,
					MAX(CASE WHEN r.shortname = 'buhead' THEN ud.data END) 'buhead', 
					MAX(CASE WHEN r.shortname ='reportingmanager' THEN ud.data END) 'manager', 
					MAX(CASE WHEN r.shortname = 'intemmanager' THEN ud.data END) 'finalmanager', 
					MAX(CASE WHEN r.shortname ='landdmanager' THEN ud.data END) 'ldmanager',lr.tearms 
					from {landd_rating} AS lr inner join {competency_users} AS cu ON lr.master_competencyid=cu.id 
					inner join {competency_title} AS ct on cu.ctid=ct.id 
					left join {competency_category} AS cc on lr.competencyid=cc.id 
					left JOIN {competencies} AS c on lr.subcomptencyid=c.id 
					inner join {user} AS u on cu.userid=u.id INNER JOIN {user_info_data} as ud on cu.userid=ud.userid 
					inner join {user_info_field} uf on ud.fieldid=uf.id
					inner join {role} r on uf.shortname=r.shortname
						" . $query;
	$getcntresult = $DB->get_records_sql($sql);
	return count($getcntresult);
}
function getListManagerUserRptCount($query)
{
	global $DB;
	$sql = "select lr.id, concat(u.firstname,' ',u.lastname) AS fullname, u.id as userid, u.username,ct.title, cc.id as ccid, cc.name as subcompetency, c.id as comptencyid, c.comptencyname as subsubcomp,lr.rating,
					MAX(CASE WHEN r.shortname = 'buhead' THEN ud.data END) 'buhead', 
					MAX(CASE WHEN r.shortname ='reportingmanager' THEN ud.data END) 'manager', 
					MAX(CASE WHEN r.shortname = 'intemmanager' THEN ud.data END) 'finalmanager', 
					MAX(CASE WHEN r.shortname ='landdmanager' THEN ud.data END) 'ldmanager',lr.tearms 
					from {landd_rating} AS lr inner join {competency_users} AS cu ON lr.master_competencyid=cu.id 
					inner join {competency_title} AS ct on cu.ctid=ct.id 
					left join {competency_category} AS cc on lr.competencyid=cc.id 
					left JOIN {competencies} AS c on lr.subcomptencyid=c.id 
					inner join {user} AS u on cu.userid=u.id INNER JOIN {user_info_data} as ud on cu.userid=ud.userid 
					inner join {user_info_field} uf on ud.fieldid=uf.id
					inner join {role} r on uf.shortname=r.shortname
					" . $query;
	$getmngresult = $DB->get_records_sql($sql);
	return count($getmngresult);
}

//previous manager rating terms
function previousManagerRating($masterid, $tearmsid)
{
	global $DB;
	if ($tearmsid == 1) {
		$year = date('Y') - 1;
		$tearmsid = 2;
	} else if ($tearmsid == 2) {
		$year = date('Y');
		$tearmsid = 1;
	}
	$previousRating = '';
	$sql = "select * from {competency_users} where id = ? and year = ?";
	$resultSql = $DB->get_records_sql($sql, array($masterid, $year));
	if (count($resultSql) > 0) {

		$managerratingSql = "select * from {manager_rating} where master_competencyid=? and tearms=?";
		$managerResult = $DB->get_records_sql($managerratingSql, array($masterid, $tearmsid));
		$managerArrCount = count($managerResult);

		foreach ($managerResult as $key => $value) {
			$previousRating = $value->finalrating;
		}
	} else {

		$previousRating = '-';
	}
	return $previousRating;
}

function getLandDRatingStatusMasterId($id, $subid, $subsubid, $tearmsid)
{
	global $DB;
	$landdSql = "select * from {landd_rating} where master_competencyid=? and competencyid = ? and subcomptencyid = ? and tearms=?";
	$landdResult = $DB->get_records_sql($landdSql, array($id, $subid, $subsubid, $tearmsid));
	$landdArrCount = count($landdResult);
	$landdArr = array();
	if ($landdArrCount > 0) {
		foreach ($landdResult as $key => $value) {
			if ($value->rating > 0) {
				if ($value->landdstatus == 1) {
					$landdArr['landdstatus'] = $value->landdstatus;
					$landdArr['landdrating'] = $value->rating;
				} else {
					$landdArr['landdstatus'] = $value->landdstatus;
					$landdArr['landdrating'] = $value->rating;
				}
			} else {
				$landdArr['landdstatus'] = '';
				$landdArr['landdrating'] = '';
			}
		}
	} else {
		$landdArr['landdstatus'] = '';
		$landdArr['landdrating'] = '';
	}
	return array($landdArrCount, $landdArr);

}

//check manager final rating exist
function getManagerFinalRatingExists($masterid, $competencyid, $subcomptencyid, $tearms, $managerid)
{
	global $DB;
	$chksql = "select * from {manager_rating} where master_competencyid=? and competencyid=? and subcomptencyid=? and tearms=? and managerid=?";
	$checkResult = $DB->get_records_sql($chksql, array($masterid, $competencyid, $subcomptencyid, $tearms, $managerid));
	$rateArr = array();
	foreach ($checkResult as $key => $value) {
		if ($value->finalrating == 0) {
			$rateArr['ratestatus'] = 'Rated';
			$rateArr['ratefinal'] = $value->finalrating;
		} else {
			$rateArr['ratestatus'] = 'Re-rated';
			$rateArr['ratefinal'] = $value->finalrating;
		}
	}
	return $rateArr;
}

//check manager rate state exist
function getManagerRatingRateMasterId($masterid, $competencyid, $subcomptencyid, $tearms, $managerid)
{
	global $DB;
	$chksql = "select * from {manager_rating} where master_competencyid=? and tearms=?";
	$checkResult = $DB->get_records_sql($chksql, array($masterid, $tearms));
	$rateArr = array();
	$ratestatestatus = '';
	foreach ($checkResult as $key => $value) {
		$ratestatestatus = $value->ratestate;
	}
	return $ratestatestatus;
}

function emailOnReject($userid, $status)
{

	global $USER, $DB;
	if ($status == 2) {
		/*$a = new stdClass();
					$user = $DB->get_record_sql("SELECT DISTINCT u.* FROM {user} as u INNER JOIN {competency_users} as cu ON u.id = cu.userid 
					WHERE u.id = ?", array($userid));
					$subject = get_string('finalscorerejected_subject', 'local_competency');
					$a->firstname = fullname($user);
					$body = get_string('finalscorerejected_body', 'local_competency', $a);
					$messageText = '';
					$return = email_to_user($user, $USER, $subject, $messageText, $body, ", ", true);*/

		$data = getUsersIds($userid);
		$uqArra = array();
		foreach ($data as $key => $userid) {
			$userids = explode('-', trim($userid->data));
			$uqArra[] = $userids[0];
		}
		$usertomail = array_unique($uqArra);
		foreach ($usertomail as $key => $touser) {
			$a = new stdClass();
			$reuser = $DB->get_record("user", array('id' => $touser));
			$a->firstname = fullname($reuser);
			$a->department = $reuser->department;
			$a->userself = fullname($USER);
			$subject = get_string('finalscorerejected_subject', 'local_competency');
			$body = get_string('finalscorerejected_body', 'local_competency', $a);
			$messageText = '';
			$return = email_to_user($reuser, $USER, $subject, $messageText, $body, ", ", true);
		}

	}
}

// function get_authenticated_users($department = ''){
//     global $DB;
//     $output = array();
//     $condition = '';
//     if($department != ''){
//         $condition = " AND u.department = '$department' ";
//     }
//     $SQL = "SELECT u.*, GROUP_CONCAT(r.shortname) as roles FROM {user} u
//              LEFT JOIN {role_assignments} a ON a.userid = u.id
//              LEFT JOIN {role} r ON r.id = a.roleid
//              WHERE u.suspended = 0 AND u.deleted = 0 $condition GROUP BY u.id";
//     $records = $DB->get_records_sql($SQL);
//     foreach ($records as $record) {
//         if(is_siteadmin($record) || isguestuser($record)){
//             continue;
//         }
//         if($record->roles == 'student,teacher' ||
//                 $record->roles == 'teacher,student' ||
//                 $record->roles == 'teacher' ||
//                 $record->roles == 'student' ||
//                 $record->roles == NULL){
//             $output[] = $record;

//         }
//     }
//     return $output;

// }

function get_authenticated_users($department = '')
{
	global $DB;

	$params = [];
	$condition = "u.suspended = 0 AND u.deleted = 0";

	if (!empty($department)) {
		$condition .= " AND u.department = :department";
		$params['department'] = $department;
	}

	$sql = "SELECT u.*, GROUP_CONCAT(r.shortname) AS roles
            FROM {user} u
            LEFT JOIN {role_assignments} a ON a.userid = u.id
            LEFT JOIN {role} r ON r.id = a.roleid
            WHERE $condition
            GROUP BY u.id";

	$records = $DB->get_records_sql($sql, $params);
	$output = [];

	foreach ($records as $record) {
		// Skip site admin and guest
		if (is_siteadmin($record) || isguestuser($record)) {
			continue;
		}

		// Split roles string into array
		$roleArray = array_filter(explode(',', $record->roles ?? ''));

		// Accept only users with roles in student/teacher or no roles
		if (empty($roleArray) || array_intersect($roleArray, ['student', 'teacher'])) {
			$output[] = $record;
		}
	}


	return $output;

}
function get_authenticated_users_against_manager($managername, $department = '')
{
	global $DB, $USER, $SESSION;
	$issiteadmin = is_siteadmin($USER);
	$params = [];
	$output = [];

	$MAIN_COMPANY_ID = 1;

	// Get current user's company
	if (!empty($SESSION->currenteditingcompany)) {
		$companyid = $SESSION->currenteditingcompany;

	} else {
		$companyid = 1;
		// $DB->get_field('company_users', 'companyid', ['userid' => $USER->id]) ?? $MAIN_COMPANY_ID;
	}
	// MAIN TENANT: use simplified logic
	if ($companyid == $MAIN_COMPANY_ID) {
		// Extract user ID from "123-ManagerName"
		$userid = explode('-', $managername)[0];

		$sql = "SELECT DISTINCT ud.userid, u.*, GROUP_CONCAT(r.shortname) AS roles
				FROM {user_info_data} ud
				INNER JOIN {user_info_field} uf ON ud.fieldid = uf.id AND uf.shortname = 'reportingmanager'
				INNER JOIN {user} u ON ud.userid = u.id
				LEFT JOIN {role_assignments} a ON a.userid = u.id
				LEFT JOIN {role} r ON r.id = a.roleid
				LEFT JOIN {company_users} cu ON cu.userid = u.id
				WHERE ud.data LIKE :likeuserid
				  AND u.suspended = 0 AND u.deleted = 0
				  AND (cu.companyid = :maincompanyid OR cu.companyid IS NULL)";

		$params['likeuserid'] = $userid . '%';
		$params['maincompanyid'] = $MAIN_COMPANY_ID;

		if (!empty($department)) {
			$sql .= " AND u.department = :department";
			$params['department'] = $department;
		}

		$sql .= " GROUP BY u.id";
	} else {
		// TENANT LOGIC
		$sql = "SELECT u.*, GROUP_CONCAT(r.shortname) AS roles
				FROM {user} u
				LEFT JOIN {role_assignments} a ON a.userid = u.id
				LEFT JOIN {role} r ON r.id = a.roleid
				LEFT JOIN {user_info_data} d ON d.userid = u.id
				LEFT JOIN {user_info_field} f ON f.id = d.fieldid AND f.shortname = 'reportingmanager'
				LEFT JOIN {company_users} cu ON cu.userid = u.id
				WHERE u.suspended = 0 AND u.deleted = 0
				  AND d.data = :managername
				  AND cu.companyid = :companyid";

		$params['managername'] = $managername;
		$params['companyid'] = $companyid;

		if (!empty($department)) {
			$sql .= " AND u.department = :department";
			$params['department'] = $department;
		}

		$sql .= " GROUP BY u.id";
	}

	// Execute query
	$records = $DB->get_records_sql($sql, $params);

	// Filter only student/teacher/no-role users
	foreach ($records as $record) {
		if (isguestuser($record))
			continue;

		$roleArray = array_filter(explode(',', $record->roles ?? ''));
		if (empty($roleArray) || array_intersect($roleArray, ['student', 'teacher'])) {
			$output[] = $record;
		}
	}

	return $output;
}


//manager
// function get_authenticated_users_against_manager($managername, $department = '')
// {
// 	global $DB;

// 	$params = ['managername' => $managername];

// 	$sql = "SELECT u.*, GROUP_CONCAT(r.shortname) AS roles
// 			FROM {user} u
// 			LEFT JOIN {user_info_data} d ON d.userid = u.id
// 			LEFT JOIN {user_info_field} f ON f.id = d.fieldid AND f.shortname = 'reportingmanager'
// 			LEFT JOIN {role_assignments} a ON a.userid = u.id
// 			LEFT JOIN {role} r ON r.id = a.roleid
// 			WHERE u.suspended = 0 AND u.deleted = 0
// 			  AND d.data = :managername";

// 	if (!empty($department)) {
// 		$sql .= " AND (u.department = :department OR u.department IS NULL OR u.department = '')";
// 		$params['department'] = $department;
// 	}

// 	$sql .= " GROUP BY u.id";

// 	$records = $DB->get_records_sql($sql, $params);
// 	$output = [];

// 	foreach ($records as $record) {
// 		if (is_siteadmin($record) || isguestuser($record)) {
// 			continue;
// 		}

// 		$roleArray = array_filter(explode(',', $record->roles ?? ''));

// 		if (empty($roleArray) || array_intersect($roleArray, ['student', 'teacher'])) {
// 			$output[] = $record;
// 		}
// 	}

// 	return $output;
// }

//get year competency
function getyearlist()
{
	global $DB;

	$sqlyear = 'SELECT distinct year FROM {competency_users}';
	$getyear = $DB->get_records_sql($sqlyear, array());

	return $getyear;
}
