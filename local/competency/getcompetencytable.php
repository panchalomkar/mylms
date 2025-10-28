<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/competency/lib.php');
global $USER, $DB, $CFG;
$tearms = required_param('tearms', PARAM_INT);
//$tearms =  2;
if (empty($tearms)) {
	return false;
}

$checkManagerRating = getexistingManagerRanking($USER->id, $tearms);
//print_object($checkManagerRating); exit();
//To display 
$studentData = getexistingStudentRanking($USER->id, $tearms);
?>
<table class="main-table table competencytable">
	<?php
	$userid = $USER->id;

	$firstQuery = "SELECT DISTINCT cu.ctid, ct.title
		FROM {competency_users} as cu 
		LEFT JOIN {competency_title} ct ON cu.ctid = ct.id 
		LEFT JOIN {competency_category} cc ON cu.competencyid = cc.id 
		LEFT JOIN {competencies} c ON c.id = cu.subcompetencyid 
		LEFT JOIN {user} u ON u.id=cu.userid 
		LEFT JOIN {manager_rating} mr ON cu.id=mr.master_competencyid And mr.competencyid = cc.id and mr.tearms = ?
		where cu.userid = ? ";

	$firstQueryResult = $DB->get_records_sql($firstQuery, array($tearms, $userid));
	// print_object($firstQueryResult);exit();
	$i = 0;
	$rows = '';
	$temp = 0;
	$readonly = 'readonly="readonly"'; //$value= '';
	$readonly1 = 'readonly="readonly"';
	$value1 = '';
	foreach ($firstQueryResult as $key => $firstValue) {

		$rows .= '<tr>';

		$rows .= '<th class="competency_title" style="width:200px"><span>' . $firstValue->title . '</span></th>';
		$rows .= '<th class="userlist" >' . get_string('studentsrating', 'local_competency') . '</th>';
		$rows .= '<th class="userlist" >' . get_string('finalrating', 'local_competency') . '</th>';
		$rows .= '</tr>';

		$secondQuery = "SELECT DISTINCT cu.id as cuid, cc.name, c.comptencyname, cc.id, cu.subcompetencyid,cu.competencyid
    					FROM {competency_users} as cu 
				        LEFT JOIN {competency_title} ct ON cu.ctid = ct.id 
				        LEFT JOIN {competency_category} cc ON cu.competencyid = cc.id 
				        LEFT JOIN {competencies} c ON c.id = cu.subcompetencyid 
				        LEFT JOIN {user} u ON u.id=cu.userid 
				        LEFT JOIN {manager_rating} mr ON cu.id=mr.master_competencyid And mr.competencyid = cc.id and mr.tearms = ?
				        WHERE cu.userid = ?  and cu.ctid = ? order by cu.competencyid, cu.subcompetencyid";

		$secondQueryResult = $DB->get_records_sql($secondQuery, array($tearms, $userid, $firstValue->ctid));

		$readonly = '';
		if (empty($checkManagerRating)) {
			$readonly = 'disabled=disabled';
		}


		foreach ($secondQueryResult as $key => $secondValue) {	// sub competency name as second Value	        			    		
			$value = '';
			if (!empty($studentData))
				if (isset($studentData[$secondValue->cuid]->rating))
					$value = 'value="' . $studentData[$secondValue->cuid]->rating . '"';


			$landdrating = getLandDRatingValueViaId($secondValue->cuid, $tearms);
			$landdratingvalue = '';
			if (count($landdrating)) {
				$landdratingvalue = $landdrating[1]['rating'];
				// $readonly = 'disabled=disabled';
			}
			$readonly = '';

			if (($tearms == 1 || $tearms == 2) && (!empty($checkManagerRating)) && $landdrating[0] == 1 && !empty($landdrating[1]['rating'])) {
				$readonly = 'disabled=disabled';
			}
			if (($tearms == 1 || $tearms == 2) && (empty($checkManagerRating))) {
				$readonly = 'disabled=disabled';
			}


			//echo $readonly; exit();
			if ($secondValue->subcompetencyid == 0) {
				$rows .= '<tr>';


				$rows .= '<td class="sticky-col first-col subcompcolor" > &nbsp; ' . $secondValue->name . '</td>';

				$rows .= '<td class="usersrow" ><input type="hidden" name="mastercompetencyid[]" id="mastercompetencyid" value="' . $secondValue->cuid . '" /> <input type="hidden" name="competencyid[]" id="competencyid" value="' . $secondValue->competencyid . '" /> <input type="number" min="0" ' . $readonly . ' max="10" name="student_rating[]"  id="studentrating"  ' . $readonly . ' ' . $value . ' required="true"   /></td>';

				$rows .= '<td class="usersrow" ><span> ' . $landdrating[1]['rating'] . ' </span></td>';

				$rows .= '</tr>';
			}

			$thirdQuery = "SELECT DISTINCT c.id , c.comptencyname,cu.subcompetencyid, cu.id  as cuid
            			   FROM {competency_users} as cu 
            			   LEFT JOIN {competency_title} ct ON cu.ctid = ct.id 
            			   LEFT JOIN {competency_category} cc ON cu.competencyid = cc.id 
            			   LEFT JOIN {competencies} c ON c.id = cu.subcompetencyid
            			   LEFT JOIN {user} u ON u.id=cu.userid 
            			   LEFT JOIN {manager_rating} mr ON cu.id=mr.master_competencyid And mr.competencyid = cc.id and mr.tearms = ?
            			   where cu.userid = ?  and c.id = ? order by c.comptencyname";
			$thirdQueryResult = $DB->get_records_sql($thirdQuery, array($tearms, $userid, $secondValue->subcompetencyid));
			foreach ($thirdQueryResult as $thirdValue) {				// Sub sub competency as thirdValue
				if (!empty($studentData))
					if (isset($studentData[$secondValue->cuid]->rating))
						$value = 'value="' . $studentData[$thirdValue->cuid]->rating . '"';
				$landdrating1 = getLandDRatingValueViaId($thirdValue->cuid, $tearms);
				$rows .= '<tr>';

				$rows .= '<td style="padding-left: 40px !important;"> &nbsp;&nbsp; ' . $thirdValue->comptencyname . '</td>';
				$rows .= '<td class="usersrow" ><input type="hidden" name="mastercompetencyid1[]" id="mastercompetencyid" value="' . $thirdValue->cuid . '" /> <input type="hidden" name="subcompetencyid[]" id="subcompetencyid" value=' . $thirdValue->subcompetencyid . ' /> <input type="number"  ' . $readonly . ' min="0" max="10" name="student_rating1[]"  id="studentrating" ' . $value . '  required="true" /></td>';

				$rows .= '<td class="usersrow" ><span> ' . $landdrating1[1]['rating'] . ' </span></td>';

				$rows .= '</tr>';

			}

		}
		$temp = 1;
	}
	if ($temp != 1) {
		$rows .= "<h3 style='color:red;'> No records found ! </h3>";
	} else {
		$rows .= '<tr style="border:0px;"><td colspan="4"><button type="submit" class="btn btn-primary" value="addrating" name="submituserselfrating">Submit</button></td></tr>';
	}

	echo $rows;
	?>
</table>