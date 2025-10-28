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
$activepage = 'approval';
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_title(get_string('approval', 'local_competency'));
$PAGE->set_url($CFG->wwwroot . '/local/competency/approval.php');
$PAGE->set_heading(get_string('approval', 'local_competency'));
$PAGE->navbar->add(get_string('approval', 'local_competency'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/competency/customtablelayout.css?v=1'));
echo $OUTPUT->header();
//header added
require_once($CFG->dirroot . '/local/competency/header.php');
require_once($CFG->dirroot . '/local/competency/tabs.php');
if (!has_capability('local/competency:competencyapproval', $context)) {
	redirect($CFG->wwwroot . '/my/', \core\notification::error('No access...'));
	exit();
}

$approvalcontentbody = '';
$viewselct = '';
$buselct = '';
$rows;
$searchRolesSql = "SELECT r.id, r.shortname FROM {role} as r INNER JOIN {user_info_field} as uif  ON r.shortname = uif.shortname OR r.shortname = 'user' GROUP BY r.shortname";
$searchRoles = $DB->get_records_sql($searchRolesSql, array());

//approval search
$search = getSearchFieldsCompetency();
$buselct = $search[0];
$viewselct = $search[1];

$approvalcontentbody .= '<div class="row" style="text-align:center;margin-bottom:10px;margin-top:10px;">
  <div class="col-md-4">
     ' . $buselct . '
  </div>
  <div class="col-md-4">
     ' . $viewselct . '
  </div>
  <div class="col-md-3"><button type="button" class="btn btn-primary" onclick="filtclickfunapproval()">Search</button></div>
</div><p id="errormessage" style="color:red;text-align:center;"></p><br/><div class="row">
	<div class="view1"><ul> 
	 <div id="table-scroll" class="table-scroll">
        <div class="table-wrap wrapper1 competencytable">';

/*$rows .='<table class="competencytable table" border="1px solid #000">';
$get_competencyheading = getListCompetencyTitle();


$i=0;
foreach ($get_competencyheading as $key => $competencyheading) {
	$sqlcompetency_category = "SELECT cc.id , cc.name,cc.roleid
								FROM {competency_category} as cc 
								WHERE cc.isdeleted=0 and cc.ctid=? 
								ORDER by cc.id";
	$competency_category = $DB->get_records_sql($sqlcompetency_category, array($competencyheading->id));
	$rows .='<tr>';
	$rows .='<th class="competency_title sticky-col first-col"><span>'.$competencyheading->title.'</span></th>';
	$allUsers = $DB->get_records_sql('SELECT * FROM {user} WHERE id != 1', array());
	$userArr =array();
	foreach ($allUsers as $key => $user) {
		$rows .='<th class="userlist" >'.fullname($user).'</th>';
		$userArr[]=$user->id;
	}

	$rows .='</tr>';
	$rows .='<tr>';
	foreach($competency_category as $competency_categorys){
		$rows .='<td class="sticky-col first-col" style="padding-left: 20px !important;">- '.$competency_categorys->name.'</td>';
		for($i=0;$i<count($allUsers);$i++){
			$chkuserinsertSql = "select * from {competency_users} where userid='".$userArr[$i]."' and ctid='".$competencyheading->id."' and competencyid='".$competency_categorys->id."' and subcompetencyid='0' and roleid='".$competency_categorys->roleid."'";
			$chkuserinsertResult = $DB->get_records_sql($chkuserinsertSql, array());
			$checksubVal =$userArr[$i].'~'.$competency_categorys->id.'~'.$competencyheading->id.'~'.$competency_categorys->roleid;
			$passVal =$userArr[$i].'~'.$competency_categorys->id.'~'.$competencyheading->id.'~'.$competency_categorys->roleid;
			if(count($chkuserinsertResult)>0){
				$checked ="checked";
				$rows .='<td  class="usersrow" ><input type="checkbox" name="check_list[]" class="subcompetencyclass" id="subcompetencyId" value="'.$checksubVal.'" rel ="'.$checksubVal.'" '.$checked.' onclick="subcompetencyfunc('.$userArr[$i].',1,'.$competencyheading->id.','.$competency_categorys->id.',0,'.$competency_categorys->roleid.',3);" /></td>';
			}else{
				$checked ="";
				$rows .='<td  class="usersrow" ><input type="checkbox" name="check_list[]" class="subcompetencyclass" id="subcompetencyId" value="'.$checksubVal.'" rel ="'.$checksubVal.'" '.$checked.' onclick="subcompetencyfunc('.$userArr[$i].',1,'.$competencyheading->id.','.$competency_categorys->id.',0,'.$competency_categorys->roleid.',4);" /></td>';
			}

		}
		$rows .='</tr>';
		$sqlcompetencies = "SELECT * FROM {competencies} as co
								WHERE co.isdeleted=0 and co.ccid=? 
								ORDER by co.id";
		$competencies = $DB->get_records_sql($sqlcompetencies, array($competency_categorys->id));
		foreach ($competencies as $key => $competencie) {
			$comptencyname = isset($competencie->comptencyname)?$competencie->comptencyname:''; 	
			$rows .='<tr>';
			$rows .='<td class="sticky-col first-col" style="padding-left: 40px !important;">-- '.$comptencyname.'</td>';
			for($i=0;$i<count($allUsers);$i++){
			$chkuserinsertSql1 = "select * from {competency_users} where userid='".$userArr[$i]."' and ctid='".$competencyheading->id."' and competencyid='".$competency_categorys->id."' and subcompetencyid='".$competencie->id."' and roleid='".$competency_categorys->roleid."'";
			$chkuserinsertResult1 = $DB->get_records_sql($chkuserinsertSql1, array());
			$checksubsubVal =$userArr[$i].'~'.$competency_categorys->id.'~'.$competencyheading->id.'~'.$competency_categorys->roleid.'~'.$competencie->id;
				if(count($chkuserinsertResult1)>0){
					$checked1 ="checked";
					$rows .='<td  class="usersrow" ><input type="checkbox" name="check_list1[]" id="subsubcompId" '.$checked1.' onclick="subcompetencyfunc('.$userArr[$i].',2,'.$competencyheading->id.','.$competency_categorys->id.','.$competencie->id.','.$competency_categorys->roleid.',3);" value="'.$checksubsubVal.'"></td>';
				}else{
					$checked1 ="";
					$rows .='<td class="usersrow" ><input type="checkbox" name="check_list1[]" id="subsubcompId" onclick="subcompetencyfunc('.$userArr[$i].',2,'.$competencyheading->id.','.$competency_categorys->id.','.$competencie->id.','.$competency_categorys->roleid.',4);" value="'.$checksubsubVal.'"></td>';
				}
			}
			$rows .='</tr>';
		}
	}
}
$rows .='</table>';*/
$approvalcontentbody .= '</div></ul></div></div>';
echo $approvalcontentbody;
//echo $rows;

?>
<?php $PAGE->requires->js('/local/competency/js/competency.js'); ?>
<?php echo $OUTPUT->footer(); ?>

<script type="text/javascript">
	function filtclickfunapproval() {
		var apbuid = $('#svbuid :selected').val();
		var aproleid = $('#svroleid :selected').val();
		if (apbuid == '') {
			$('#errormessage').show();
			$('#errormessage').html("Please select bussiness unit and role!");
			setTimeout(function () {
				$('#errormessage').hide();
			}, 3000);
		} else if (aproleid == '') {
			$('#errormessage').show();
			$('#errormessage').html("Please select bussiness unit and role!");
			setTimeout(function () {
				$('#errormessage').hide();
			}, 3000);
		} else {
			$.ajax({
				type: "POST",
				url: "competencyAjax.php",
				data: {
					roleid: aproleid,
					buid: apbuid,
					ccase: 'approvalform'
				},
				success: function (response) {
					$('#errormessage').hide();
					$(".competencytable").html(response);
				},
				error: function (e, msg) {

				}
			});
		}
	}
</script>
<script type="text/javascript">
	$(document).ready(function () {
		$(".main-table").clone(true).appendTo('#table-scroll').addClass('clone');
	});
</script>