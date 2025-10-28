<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * competency local caps.
 *
 * @package    local_competency
 * @copyright  Daniel Neis <danielneis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/competency/pagination.php');
require_once($CFG->dirroot . '/local/competency/lib.php');
$activepage = 'userwisereport';
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_title(get_string('userwisereport', 'local_competency'));
$PAGE->set_url($CFG->wwwroot . '/local/competency/userwisereport.php');
$PAGE->set_heading(get_string('userwisereport', 'local_competency'));
$PAGE->navbar->add(get_string('userwisereport', 'local_competency'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/competency/custom.css?v=1'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/competency/customtablelayout.css?v=1'));

echo $OUTPUT->header();
//header added
require_once($CFG->dirroot . '/local/competency/header.php');
require_once($CFG->dirroot . '/local/competency/tabs.php');
if (!has_capability('local/competency:landdreport', $context)) {
	redirect($CFG->wwwroot . '/my/', \core\notification::error('No access...'));
	exit();
}
//$listCompetencyCount = getListCompetencyTitleCount();

//$pagesArr = getPaginationDisplay($listCompetencyCount, $selectPageNo, $limit);
//$pages = $pagesArr[0];
//$start = $pagesArr[1];
$buselct = '';
$viewselct = '';
$deptselect='';
$subselct = '';
$tearms = '';
$ratstatus = '';
$viewcontentbody = '';
$searchListShow = '';
$subsubselct = '';
$progressstatus = '';
$errormessage = '';
$termsselect = '';
$yearselect = '';
if (optional_param('submituserwisereport', '', PARAM_TEXT) === 'usersearch') {
	$userid = required_param('userid', PARAM_INT);
	if ($userid == '') {
		$errormessage .= "Please select user!";
	}
} else {
	$userid = optional_param('userid', '', PARAM_INT);
}
$ctid = optional_param('svmainid', '', PARAM_INT);
$ccid = optional_param('svsubid', '', PARAM_INT);
$competenciesid = optional_param('svsubsubid', '', PARAM_INT);
$rateid = optional_param('tearmsid', '', PARAM_INT);
$terms = optional_param('terms', '', PARAM_INT);
$yearid = optional_param('yearid', '', PARAM_INT);
//$searchcompetencyheading = getListCompetencyTitle($start, $limit);
echo '<form method="post">';
// $buselct =''; $viewselct='';$subselct='';$tearms =''; 
// $viewcontentbody=''; $searchListShow=''; $subsubselct='';

// $userid = $_POST["userid"];
// $ccid = $_POST["svsubid"]; 
// $competenciesid =  $_POST["svsubsubid"];
// $rateid = $_POST["tearmsid"];
$query = '';
if ($userid != '' && $ctid == '' && $ccid == '' && $competenciesid == '' && $rateid == '' && $terms == '' && $yearid == '') {
	$query = "where cu.userid=" . $userid . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid != '' && $ccid == '' && $competenciesid == '' && $rateid == '' && $terms == '' && $yearid == '') {
	$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid == '' && $rateid == '' && $terms == '' && $yearid == '') {
	$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid != '' && $rateid == '' && $terms == '' && $yearid == '') {
	$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid == '' && $ccid == '' && $competenciesid == '' && $rateid != '' && $terms == '' && $yearid == '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid != '' && $rateid != '' && $terms == '' && $yearid == '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid != '' && $ccid == '' && $competenciesid == '' && $rateid != '' && $terms == '' && $yearid == '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid == '' && $rateid != '' && $terms == '' && $yearid == '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid == '' && $ccid == '' && $competenciesid == '' && $rateid != '' && $terms != '' && $yearid == '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid != '' && $rateid != '' && $terms != '' && $yearid == '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid != '' && $ccid == '' && $competenciesid == '' && $rateid != '' && $terms != '' && $yearid == '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid == '' && $rateid != '' && $terms != '' && $yearid == '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid == '' && $ccid == '' && $competenciesid == '' && $rateid != '' && $terms == '' && $yearid != '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid != '' && $rateid != '' && $terms != '' && $yearid != '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid != '' && $ccid == '' && $competenciesid == '' && $rateid != '' && $terms == '' && $yearid != '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid == '' && $rateid != '' && $terms == '' && $yearid != '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid == '' && $rateid != '' && $terms != '' && $yearid != '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid != '' && $rateid != '' && $terms == '' && $yearid != '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid == '' && $ccid == '' && $competenciesid == '' && $rateid != '' && $terms != '' && $yearid != '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid != '' && $ccid == '' && $competenciesid == '' && $rateid != '' && $terms != '' && $yearid != '') {
	if ($rateid == 1) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 0 and 4 group by lr.id";
	}
	if ($rateid == 2) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 5 and 7 group by lr.id";
	}
	if ($rateid == 3) {
		$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 and lr.rating between 8 and 10 group by lr.id";
	}
} else if ($userid != '' && $ctid == '' && $ccid == '' && $competenciesid == '' && $rateid == '' && $terms != '' && $yearid == '') {
	$query = "where cu.userid=" . $userid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid != '' && $ccid == '' && $competenciesid == '' && $rateid == '' && $terms != '' && $yearid == '') {
	$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid == '' && $rateid == '' && $terms != '' && $yearid == '') {
	$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid != '' && $rateid == '' && $terms != '' && $yearid == '') {
	$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and lr.tearms=" . $terms . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid == '' && $ccid == '' && $competenciesid == '' && $rateid == '' && $terms == '' && $yearid != '') {
	$query = "where cu.userid=" . $userid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid != '' && $ccid == '' && $competenciesid == '' && $rateid == '' && $terms == '' && $yearid != '') {
	$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid == '' && $rateid == '' && $terms == '' && $yearid != '') {
	$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid != '' && $rateid == '' && $terms == '' && $yearid != '') {
	$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and cu.year=" . $yearid . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid != '' && $rateid == '' && $terms != '' && $yearid != '') {
	$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.subcomptencyid=" . $competenciesid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid == '' && $ccid == '' && $competenciesid == '' && $rateid == '' && $terms != '' && $yearid != '') {
	$query = "where cu.userid=" . $userid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid != '' && $ccid == '' && $competenciesid == '' && $rateid == '' && $terms != '' && $yearid != '') {
	$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 group by lr.id";
} else if ($userid != '' && $ctid != '' && $ccid != '' && $competenciesid == '' && $rateid == '' && $terms != '' && $yearid != '') {
	$query = "where cu.userid=" . $userid . " and cu.ctid=" . $ctid . " and cc.id=" . $ccid . " and lr.tearms=" . $terms . " and cu.year=" . $yearid . " and lr.landdstatus!=2 group by lr.id";
} else {
	$query = "where lr.landdstatus!=2 group by lr.id";
}
$listCompetencyCount = getListUserReportCount($query);
$pagesArr = getPaginationDisplay($listCompetencyCount, $selectPageNo, $limit);
$pages = $pagesArr[0];
$start = $pagesArr[1];

//$completencyTitles = getListUsercomp($start, $limit);
$searchSqlcomp = "select lr.id, concat(u.firstname,' ',u.lastname) AS fullname, u.id as userid, u.username,ct.title, cc.id as ccid, cc.name as subcompetency, c.id as comptencyid, c.comptencyname as subsubcomp,lr.rating,
					MAX(CASE WHEN r.shortname = 'sbuhead' THEN ud.data END) 'sbuhead', 
					MAX(CASE WHEN r.shortname ='reportingmanager' THEN ud.data END) 'manager', 
					MAX(CASE WHEN r.shortname = 'interimmanager' THEN ud.data END) 'finalmanager',
					MAX(CASE WHEN udf.shortname = 'department' THEN udd.data END) AS 'department',
					MAX(CASE WHEN r.shortname ='landmanager' THEN ud.data END) 'ldmanager',lr.tearms,lr.progstatus,lr.rating,cu.year  
					from {landd_rating} AS lr 
					inner join {competency_users} AS cu ON lr.master_competencyid=cu.id 
					inner join {competency_title} AS ct on cu.ctid=ct.id 
					left join {competency_category} AS cc on lr.competencyid=cc.id 
					left JOIN {competencies} AS c on lr.subcomptencyid=c.id 
					inner join {user} AS u on cu.userid=u.id 
					inner JOIN {user_info_data} as ud on cu.userid=ud.userid 
					inner join {user_info_field} uf on ud.fieldid=uf.id
					inner join {role} r on uf.shortname=r.shortname

					-- Additional join for department (independent of role)
					left join {user_info_data} AS udd on cu.userid=udd.userid
					left join {user_info_field} AS udf on udd.fieldid=udf.id
					
					" . $query . " limit $start, $limit";

$searchCompResult = $DB->get_records_sql($searchSqlcomp);


if (count($searchCompResult) > 0) {
	//$show = 'show';
//$i=0; 
	$searchListShow .= '
        <div id="table-scroll" class="table-scroll">
        <div class="table-wrap wrapper">
		
          <!--Table-->
          <div class="alert alert-success" id="successmessgae" style="display: none"></div>
          <table class="main-table table">
            <!--Table head-->
            <thead>
              <tr>
			   <th class="th-lg sticky-col first-col">User name</th>
                <th class="th-lg sticky-col second-col">Main Competency</th>
                <th class="th-lg sticky-col third-col">Sub Competency</th>
                <th class="th-lg sticky-col fourth-col">Sub Sub Competency </th>
				<th class="th-lg">Department</th>
				<th class="th-lg">Buhead</th>
				<th class="th-lg">Reporting Manager</th>
				<th class="th-lg">Intem Manager</th>
				<th class="th-lg">L and D manager</th>
				<th class="th-lg">Final Rating</th>
				<th class="th-lg">Rating Status </th>
				<th class="th-lg">Terms</th>
				<th class="th-lg">Course Compeletion</th>
				<th class="th-lg">User Report</th>
              </tr>
            </thead>
            <!--Table head-->
            <!--Table body-->
            <tbody>';
	foreach ($searchCompResult as $competency_categorys_val) {

		if ($competency_categorys_val->rating >= 0 && $competency_categorys_val->rating <= 4) {
			$colorrate = "circle_red";
		} else if ($competency_categorys_val->rating >= 5 && $competency_categorys_val->rating <= 7) {
			$colorrate = "circle_yellow";
		} else if ($competency_categorys_val->rating >= 8 && $competency_categorys_val->rating <= 10) {
			$colorrate = "circle_green";
		}
		$subsubcomptencyid = $competency_categorys_val->comptencyid;
		if (empty($competency_categorys_val->comptencyid)) {
			$subsubcomptencyid = 0;
		}

		$subcomptencyid = $competency_categorys_val->ccid;
		if (empty($competency_categorys_val->ccid)) {
			$subcomptencyid = 0;
		}
		if ($competency_categorys_val->tearms == 1) {
			$tearms = 'First Half';
		} else {
			$tearms = 'Second Half';
		}
		if ($competency_categorys_val->progstatus == 1) {
			$progressstatus = 'Completed';
		} else {
			$progressstatus = 'Inprogress';
		}
		$searchListShow .= '<tr>
               <td class="sticky-col first-col">' . $competency_categorys_val->fullname . '</td>
                <td class="sticky-col second-col">' . $competency_categorys_val->title . '</td>
                <td class="sticky-col third-col">' . $competency_categorys_val->subcompetency . '</td>
				 <td class="sticky-col fourth-col">' . $competency_categorys_val->subsubcomp . '</td>
				 	   <td>' . $competency_categorys_val->department . '</td>
				 <td>' . $competency_categorys_val->sbuhead . '</td>
				 <td>' . $competency_categorys_val->manager . '</td>
				  <td>' . $competency_categorys_val->finalmanager . '</td>
				  <td>' . $competency_categorys_val->ldmanager . '</td>
				   <td>' . $competency_categorys_val->rating . '</td>
				  <td><div class="' . $colorrate . '"></div></td>
				  <td>' . $tearms . '</td>
				   <td>' . $progressstatus . '</td>
				  <td> <a href="#" class="btn btn-primary" onclick="enrollcourse(' . $competency_categorys_val->userid . ',' . $subsubcomptencyid . ',' . $subcomptencyid . ')" > Enroll Course </a></td>
			
              </tr>';
	}
	$searchListShow .= '</tbody>
            <!--Table body-->
          </table>
          <!--Table-->
        </div>
    ';
} else {
	$searchListShow .= "No records found !";
}
//Sub sub Competency pagination
//if($pages > 1){
//	$pagination = custompagination3($selectPageNo,$pages,'tabviewcompetency');
//}

//view compentency search button


$users = getuserlist();


$buselct = '<div class="custom-dropdown-wrapper">
	<div class="dropdown-input" onclick="toggleDropdown()"><span>Select User</span>
	<i class="fa fa-caret-down" style="margin-left: 8px;"></i></div>
	<div class="dropdown-menu" id="customDropdown">
		<input type="text" id="searchInput" class="dropdown-search" placeholder="Search..."
			onkeyup="filterDropdownItems()">
		<ul id="dropdownList">';

foreach ($users as $user) {
	$buselct .= '<li onclick="selectUser(this)" data-id="' . $user->id . '">' .
		$user->firstname . ' ' . $user->lastname . ' (' . $user->lastname . ')' .
		'</li>';
}

$buselct .= '</ul>
	</div>
	<input type="hidden" name="userid" id="selectedUserId">
</div>';
//Get user info role list
$searchmaincomp = getmaincomplist();
$viewselct .= '<select name="svmainid" id="svmainid" class="form-control" onchange="changeMaincomp(this.value)">
	<option value="">Select Main Competency</option>';
foreach ($searchmaincomp as $key1 => $value1) {
	if ($ctid == $value1->id) {
		$viewselct .= '<option value="' . $value1->id . '" selected="selected">' . $value1->title . '</option>';
	} else {
		$viewselct .= "<option value='" . $value1->id . "'>" . $value1->title . "</option>";
	}

}
$viewselct .= '</select>';
//Get sub comptency 
if (empty($ccid) && empty($ctid)) {

	$subselct .= '<select name="svsubid" id="svsubid" class="form-control" onchange="changeSubcomp(this.value)">
	        <option value="">Select Sub competency</option>';
	$subselct .= '</select>';

} else {

	$getsubcomp = $DB->get_records('competency_category', array('ctid' => $ctid));
	$subselct = '';
	$subselct .= '<select name="svsubid" id="svsubid" class="form-control" onchange="changeSubcomp(this.value)">
            <option value="">Select Sub Competency</option>';
	foreach ($getsubcomp as $subcomp) {
		if ($ccid == $subcomp->id) {
			$subselct .= "<option value='" . $subcomp->id . "' selected='selected'>" . $subcomp->name . "</option>";
		} else {
			$subselct .= "<option value='" . $subcomp->id . "'>" . $subcomp->name . "</option>";
		}
	}
	$subselct .= '</select>';

}
//Get sub sub comptency   

// $department = isset($department) ? trim($department) : '';

// $departments = $DB->get_records_sql("
//     SELECT DISTINCT TRIM(ud.data) AS department
//     FROM {user_info_data} ud
//     JOIN {user_info_field} uf ON ud.fieldid = uf.id
//     WHERE uf.shortname = 'department' AND TRIM(ud.data) != ''
//     ORDER BY department ASC
// ");

// $deptselect = '<select name="department" id="department" class="form-control">';
// $deptselect .= '<option value="">Select Department</option>';

// foreach ($departments as $dept) {
//     $value = trim($dept->department);
//     if ($value === '') continue; // skip empty departments again just in case
//     $selected = ($department === $value) ? 'selected' : '';
//     $deptselect .= "<option value=\"$value\" $selected>$value</option>";
// }

// $deptselect .= '</select>';


$getsubsubcomp = $DB->get_records('competencies', array('ccid' => $ccid));
$subsubselct = '';
$subsubselct .= '<select name="svsubsubid" id="svsubsubid" class="form-control">
	<option value="">Select Sub Sub Competency</option>';
foreach ($getsubsubcomp as $subsubcomp) {
	if ($competenciesid == $subsubcomp->id) {
		$subsubselct .= "<option value='" . $subsubcomp->id . "' selected='selected'>" . $subsubcomp->comptencyname . "</option>";
	} else {
		$subsubselct .= "<option value='" . $subsubcomp->id . "'>" . $subsubcomp->comptencyname . "</option>";
	}
}
$subsubselct .= '</select>';
//Get year list
$searchyear = getyearlist();
$yearselect .= '<select name="yearid" id="yearid" class="form-control">
	<option value="">Select Year</option>';
foreach ($searchyear as $key2 => $value2) {
	if ($yearid == $value2->year) {
		$yearselect .= '<option value="' . $value2->year . '" selected="selected">' . $value2->year . '</option>';
	} else {
		$yearselect .= "<option value='" . $value2->year . "'>" . $value2->year . "</option>";
	}

}
$yearselect .= '</select>';
$ratstatus .= '<select name="tearmsid" id="tearmsid" class="form-control">
        <option value="">Select Rating Status</option>';
if ($rateid == 1) {
	$ratstatus .= '<option value="1" selected="selected">Red</option>';
	$ratstatus .= '<option value="2">Yellow</option>';
	$ratstatus .= '<option value="3">Green</option>';
} else if ($rateid == 2) {
	$ratstatus .= '<option value="1">Red</option>';
	$ratstatus .= '<option value="2" selected="selected">Yellow</option>';
	$ratstatus .= '<option value="3">Green</option>';
} else if ($rateid == 3) {
	$ratstatus .= '<option value="1">Red</option>';
	$ratstatus .= '<option value="2">Yellow</option>';
	$ratstatus .= '<option value="3" selected="selected">Green</option>';
} else {
	$ratstatus .= '<option value="1">Red</option>
        <option value="2">Yellow</option>
		<option value="3">Green</option>';
}
$ratstatus .= '</select>';
$termsselect .= '<select name="terms" id="terms" class="form-control">
 		<option value="">Select Terms</option>';
if ($terms == 1) {
	$termsselect .= '<option value="1" selected="selected">First Half</option>';
	$termsselect .= '<option value="2">Second Half</option>';
} else if ($terms == 2) {
	$termsselect .= '<option value="1">First Half</option>';
	$termsselect .= '<option value="2" selected="selected">Second Half</option>';
} else {
	$termsselect .= '<option value="1">First Half</option>
          <option value="2">Second Half</option>';
}
$termsselect .= '</select>';

$viewcontentbody = '<div class="row" style="text-align:center;margin-bottom:10px;margin-top:10px;">
	<div class="col-md-3">
		' . $buselct . '
	</div>
	<div class="col-md-3">
		' . $viewselct . '
	</div>
	<div class="col-md-3" id="subcompshow">
		' . $subselct . '
	</div>
	<div class="col-md-3">
		' . $deptselect . '
	</div>
	</div>
	<div class="row" style="text-align:center;margin-bottom:10px;margin-top:10px;">
	<div class="col-md-3" id="cometenciesshow">
		' . $subsubselct . '
	</div>
	<div class="col-md-2">
		' . $ratstatus . '
	</div>
	<div class="col-md-2">
		' . $termsselect . '
	</div>
	<div class="col-md-2">
		' . $yearselect . '
	</div>
	<div class="col-md-1"><button type="submit" class="btn btn-primary" value="usersearch" name="submituserwisereport">Search</button></div>	
	<div class="col-md-1"><a href="userwisereport.php" class="btn btn-primary">Clear</a></div>
	<div class="col-md-1 d-flex"><a href="download_csv.php">
     <img width="48" height="48" style="    width: 30px;height: 30px; border-bottom: solid #003152;" src="https://img.icons8.com/color/48/export-csv.png" alt="export-csv"/>
</a></div>
	
	</div><p id="errormessage" style="color:red;text-align:center;">' . $errormessage . '</p><br>' . $searchListShow;
echo $viewcontentbody;
echo "<br/>";

//Main Heading pagination
if ($pages > 1) {

	$pagination = viewreportpagination($selectPageNo, $pages, $userid, $ctid, $ccid, $competenciesid, $rateid);
}
echo $pagination;
?>
</form>

<?php $PAGE->requires->js('/local/competency/js/report.js?v=1'); ?>
<script type="text/javascript">
	$(document).ready(function () {
		$(".main-table").clone(true).appendTo('#table-scroll').addClass('clone');
	});
	function toggleDropdown() {
		document.getElementById("customDropdown").classList.toggle("active");
	}

	function filterDropdownItems() {
		const input = document.getElementById("searchInput").value.toLowerCase();
		const items = document.querySelectorAll("#dropdownList li");

		items.forEach(item => {
			const text = item.textContent.toLowerCase();
			item.style.display = text.includes(input) ? "" : "none";
		});
	}

	function selectUser(element) {
		const selectedText = element.textContent;
		const selectedId = element.getAttribute("data-id");

		document.querySelector(".dropdown-input").textContent = selectedText;
		document.getElementById("selectedUserId").value = selectedId;

		document.getElementById("customDropdown").classList.remove("active");
	}
</script>
<?php echo $OUTPUT->footer(); ?>