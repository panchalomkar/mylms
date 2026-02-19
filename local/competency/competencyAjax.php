<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/competency/lib.php');
global $CFG, $DB, $OUTPUT, $PAGE;
$id = optional_param('cid', '', PARAM_INT);
$case = optional_param('ccase', '', PARAM_TEXT);

if ($case == 'competencyHeading') {
	$sql = "Select * from {competency_title} where isdeleted=0 and id=?";
	$completencyTitles = $DB->get_records_sql($sql, array($id));
	$data = array();
	foreach ($completencyTitles as $key => $completencyTitle) {
		$data['cid'] = $completencyTitle->id;
		$data['title'] = $completencyTitle->title;
	}
	header('HTTP/1.1 200 Success');
	echo json_encode($data);
} else if ($case == 'competencyCategory') {
	$catArr = array();
	$sql = "Select * from {competency_category} where isdeleted=0 and id=?";
	$completencyCategory = $DB->get_records_sql($sql, array($id));
	$data = array();
	foreach ($completencyCategory as $key => $category) {
		$catid = $category->id;
		$ctid = $category->ctid;
		$buid = $category->buid;
		$data['ccid'] = $category->id;
		$data['title'] = $category->name;
		$catSql = "select * from {competencycat_courses} where competencycatid=? and isdeleted=0";
		$catResult = $DB->get_records_sql($catSql, array($catid));
		foreach ($catResult as $key => $cat) {
			$catArr[] = $cat->courseid;
		}
	}
	//get heading list
	$headingdata = '';
	$options2 = '';
	$headingSql = "Select * from {competency_title} where isdeleted =0";
	$headingResult = $DB->get_records_sql($headingSql, array());
	$headingdata .= "<select class='form-control' name='mainHeadingEditId' id='mainHeadingEditId' required='true' >";
	foreach ($headingResult as $key => $headlist) {
		if ($ctid == $headlist->id) {
			$options2 .= "<option value='" . $headlist->id . "' selected=selected>" . $headlist->title . "</option>";
		} else {
			$options2 .= "<option value='" . $headlist->id . "'>" . $headlist->title . "</option>";
		}
	}
	$headingdata .= $options2;
	$headingdata .= "</select>";
	$data['headingdata'] = $headingdata;

	//get BU list
	$budata = '';
	$options3 = '';
	// Get BU departments list.
	$buResult = getdepartment();
	$budata .= '<select name="buEditId" id="buEditId" class="form-control" required="true" >
	<option value="">Select Business Unit</option>';
	foreach ($buResult as $key => $value) {
		if ($buid == $value->department) {
			$budata .= '<option value="' . $value->department . '" selected=selected >' . $value->department . '</option>';
		} else {
			$budata .= '<option value="' . $value->department . '">' . $value->department . '</option>';
		}
	}
	$budata .= '</select>';


	// $budata .="<select class='form-control' name='buEditId' id='buEditId'>"; 
	// foreach ($buResult as $key => $bulist) {
	// 		if($buid == $bulist->id){
	// 			$options3 .="<option value='".$bulist->id."' selected=selected>".$bulist->name."</option>";
	// 		}else{
	// 			$options3 .="<option value='".$bulist->id."'>".$bulist->name."</option>";
	// 		}
	// }
	// $budata .= $options3;
	// $budata .= "</select>";
	$data['budata'] = $budata;


	//get role list
	$roleSql = "SELECT r.id, r.shortname FROM {role} as r INNER JOIN {user_info_field} as uif  ON r.shortname = uif.shortname OR r.shortname = 'user' GROUP BY r.shortname";
	$roleResult = $DB->get_records_sql($roleSql, array());
	$roledata = '';
	$options1 = '';
	$options = '';
	$roledata .= "<select class='form-control' name='editroleid' id='editroleid' required='true' >";
	foreach ($roleResult as $key => $rolelist) {
		if ($category->roleid == $rolelist->id) {
			$options1 .= "<option value='" . $rolelist->id . "' selected=selected>" . $rolelist->shortname . "</option>";
		} else {
			$options1 .= "<option value='" . $rolelist->id . "'>" . $rolelist->shortname . "</option>";
		}
	}
	$roledata .= $options1;
	$roledata .= "</select>";
	$data['roleid'] = $roledata;

	//get course list
	// $courseSql = "select * from {course} order by shortname ASC";
	// $courseResult = $DB->get_records_sql($courseSql, array());
	$courseResult = getCourseList();
	$coursedata = "<select multiple class='form-control' name='editcourseid[]' id='editcourseid'>";
$coursedata .= "<option value='' disabled style='pointer-events: none; color: #888;'>Select courses</option>";

foreach ($courseResult as $courselist) {
    if (in_array($courselist->id, $catArr)) {
        $coursedata .= "<option value='" . $courselist->id . "' selected='selected'>" . $courselist->shortname . "</option>";
    } else {
        $coursedata .= "<option value='" . $courselist->id . "'>" . $courselist->shortname . "</option>";
    }
}

$coursedata .= "</select>";
$data['courseid'] = $coursedata;


	header('HTTP/1.1 200 Success');
	echo json_encode($data);
} else if ($case == 'competencySubCategory') {
	$compArr = array();
	$sql = "Select * from {competencies} where isdeleted=0 and id=?";
	$completencySubCategory = $DB->get_records_sql($sql, array($id));
	$data = array();
	foreach ($completencySubCategory as $key => $subcategory) {
		$catid = $subcategory->id;
		$ccid = $subcategory->ccid;
		$data['ccid'] = $subcategory->id;
		$data['title'] = $subcategory->comptencyname;
		$compSql = "select * from {competency_courses} where competencyid=? and isdeleted=0";
		$compResult = $DB->get_records_sql($compSql, array($catid));
		foreach ($compResult as $key => $comp) {
			$compArr[] = $comp->courseid;
		}
	}

	//get compentency category list
	$categorydata = '';
	$options2 = '';
	$categorySql = "Select * from {competency_category} where isdeleted =0";
	$categoryResult = $DB->get_records_sql($categorySql, array());
	$categorydata .= "<select class='form-control' name='editCompentencyCategoryid' id='editCompentencyCategoryid'>";
	foreach ($categoryResult as $key => $ctlist) {
		if ($ccid == $ctlist->id) {
			$options2 .= "<option value='" . $ctlist->id . "' selected=selected>" . $ctlist->name . "</option>";
		} else {
			$options2 .= "<option value='" . $ctlist->id . "'>" . $ctlist->name . "</option>";
		}
	}
	$categorydata .= $options2;
	$categorydata .= "</select>";
	$data['categorydata'] = $categorydata;

	//get course list
	// $courseSql = "select * from {course} where id != ? order by shortname ASC";
	// $courseResult = $DB->get_records_sql($courseSql, array(SITEID));
	$courseResult = getCourseList();
	$coursedata = "<select multiple class='form-control' name ='editcourseid[]' id='editcourseid'>";
	$options = "<option value='' disabled style='pointer-events: none; color: #888;'>Select courses</option>";
	foreach ($courseResult as $key => $courselist) {
		if (in_array($courselist->id, $compArr)) {
			$options .= "<option value='" . $courselist->id . "' selected=selected>" . $courselist->shortname . "</option>";
		} else {
			$options .= "<option value='" . $courselist->id . "'>" . $courselist->shortname . "</option>";
		}
	}
	$coursedata .= $options;
	$coursedata .= "</select>";
	$data['courseid'] = $coursedata;

	header('HTTP/1.1 200 Success');
	echo json_encode($data);
} else if ($case == 'viewcompetency') {
	$roleid = required_param('roleid', PARAM_INT);
	$buid = required_param('buid', PARAM_TEXT);
	$get_competencyheading = $DB->get_records('competency_title', array('isdeleted' => 0));
	$show = 'show';
	$i = 0;
	$temp = 1;
	$searchListShow = '';
	$searchListShow .= '<div class="accordion md-accordion accordion-blocks" id="accordionEx78" role="tablist" aria-multiselectable="false">
<div class="card">';
	foreach ($get_competencyheading as $key => $seachVal) {
		if ($i > 0) {
			$show = '';
		}
		if ($roleid) {
			$query = " and cc.roleid=$roleid";
		} else {
			$query = "";
		}
		$searchSqlcomp = "SELECT cp.id, cc.id as cctid, cp.comptencyname, cc.name, r.shortname 
			FROM {competency_category} as cc 
			LEFT JOIN {competencies} as cp ON cc.id = cp.ccid 
			LEFT JOIN {role} as r ON cc.roleid = r.id
			WHERE cc.isdeleted=0 and cc.ctid=? and cc.buid=? $query
			ORDER by cc.id";
		$searchCompResult = $DB->get_records_sql($searchSqlcomp, array($seachVal->id, $buid));
		if (count($searchCompResult) > 0) {
			$searchListShow .= '<div class="card-header" role="tab" id="heading"' . $i . '"">
      <!-- Heading -->
      <a data-toggle="collapse" data-parent="#accordionEx78" href="#collapse' . $i . '" aria-expanded="false" aria-controls="collapse' . $i . '">
        <h5 class="mt-1 mb-0">
          <span>' . $seachVal->title . '</span>
        </h5>
      </a>
    </div>
	<!-- Card body -->
    <div id="collapse' . $i . '" class="collapse ' . $show . '" role="tabpanel" aria-labelledby="heading' . $i . '" data-parent="#accordionEx78">
      <div class="card-body">
        <!-- Table responsive wrapper -->
        <div class="table-responsive mx-3">
          <!--Table-->
          <table class="table table-hover mb-0">
            <!--Table head-->
            <thead>
              <tr>
                <th class="th-lg">Sub Competency Name</th>
                <th class="th-lg">Sub Sub Competency Name </a></th>
                <th class="th-lg">Role</th>
                <th class="th-lg">View course</th>
              </tr>
            </thead>
            <!--Table head-->
            <!--Table body-->
            <tbody>';
			foreach ($searchCompResult as $competency_categorys_val) {

				if (empty($competency_categorys_val->id)) {
					$svid = 0;
				} else {
					$svid = $competency_categorys_val->id;
				}
				if (empty($competency_categorys_val->cctid)) {
					$svcctid = 0;
				} else {
					$svcctid = $competency_categorys_val->cctid;
				}
				if (empty($competency_categorys_val->id) && empty($competency_categorys_val->cctid)) {
					$searchcomptencyname = '-';
				} else {
					$searchcomptencyname = $competency_categorys_val->comptencyname;
				}
				$searchListShow .= '<tr>
                <td>' . $competency_categorys_val->name . '</td>
                <td>' . $searchcomptencyname . '</td>
                <td>' . $competency_categorys_val->shortname . '</td>
                <td>
                  <a href="#" class="btn btn-primary" data-target="#tabView" data-toggle="modal" onclick="getcourses(' . $svid . ',' . $svcctid . ')"> View Courses </a>
                </td>
              </tr>';
			}
			$searchListShow .= '</tbody>
            <!--Table body-->
          </table>
          <!--Table-->
        </div>
        <!-- Table responsive wrapper -->
      </div>
    </div>

	<div class="modal fade" id="tabView" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	  <div class="modal-dialog" role="document">
	    <div class="modal-content">
	      
	      <div class="modal-header">
	        <h5 class="modal-title" id="exampleModalLabel">Course lists</h5>
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	          <span aria-hidden="true">&times;</span>
	        </button>
	      </div>

	      <div class="modal-body">
	       <div class="form-group courselistclass" id="courselist">
	           
	        </div>    
	      </div>

	      <div class="modal-footer">
	        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>     
	      </div>

	    </div>
	  </div>
	</div>

    ';
			echo $searchListShow;
			$temp = 0;
		}
	}
	if ($temp == 1) {
		echo "<p style='color:red;text-align:center;'>No such a record found!</p>";
		$temp++;
	}
} else if ($case == 'viewcompetencycourse') {

	$cid = optional_param('cid', 0, PARAM_INT);
	$cctid = optional_param('cctid', 0, PARAM_INT);
	$sqlcourse = "SELECT c.* FROM {course} as c INNER JOIN {competency_courses} as cc ON c.id = cc.courseid WHERE cc.competencyid =? and c.id != ?";
	$getcourses = $DB->get_records_sql($sqlcourse, array($cid, SITEID));

	$sqlCatcourse = "SELECT c.* FROM {course} as c INNER JOIN {competencycat_courses} as ccc ON c.id = ccc.courseid 
	 WHERE ccc.isdeleted=0 and ccc.competencycatid = '" . $cctid . "' and c.id != ?";
	$getcatcourses = $DB->get_records_sql($sqlCatcourse, array(SITEID));
	$temp = 1;
	$html = '';
	$html = "<dl>";
	$catSql1 = "select * from {competency_category} where id=?";
	$getResult = $DB->get_records_sql($catSql1, array($cctid));
	foreach ($getResult as $key => $val1) {
		$html .= "<dt>Sub Competency : $val1->name</dt>";
	}
	if (!empty($getcatcourses)) {
		foreach ($getcatcourses as $key => $course) {
			$html .= "<dd style='margin-left:20px;'><span class='dot'></span> <a href=" . $CFG->wwwroot . "/course/view.php?id=$course->id target='_blank'> $course->fullname</a></dd>";
		}
		$html .= "<dt style='border-top:1px solid #dee2e6;'></td>";
		//$html .= "</dl> ";
	} else if (!empty($val1->name)) {
		$html .= 'No such a course found!';
	}

	$catSql = "select * from {competencies} where id=?";
	$getcatResult = $DB->get_records_sql($catSql, array($cid));
	foreach ($getcatResult as $key => $val) {
		$html .= "<br/><dt>Sub Sub Competency : $val->comptencyname</dt>";
	}
	if (!empty($getcourses)) {
		//$html =  "<dl>";
		foreach ($getcourses as $key => $course1) {
			$html .= "<dd style='margin-left:20px;'><span class='dot'></span> <a href=" . $CFG->wwwroot . "/course/view.php?id=$course1->id target='_blank'>$course1->fullname<a/> </dd>";
		}
		$html .= "</dl> ";
	} else if (!empty($val->comptencyname)) {
		$html .= 'No such a course found!';
	}
	echo $html;

}else if($case == 'approvalform'){

	$roleid = required_param('roleid', PARAM_INT);
	$buid = required_param('buid', PARAM_TEXT);
	$rows='';$tabid='';
	$get_competencyheading = $DB->get_records('competency_title', array('isdeleted' => 0));
	$i=0; $rowCnt=0; $allUsers=0;
	   $rows .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">';
    $rows .= '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
    $rows .= '<style>
	.subcompcolor{
  background-color: #a0bbcd !important;
  }
        .main-table th, .main-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
            font-size: 13px;
        }
        .sticky-col {
            position: sticky;
            left: 0;
            background: #f9f9f9;
            z-index: 1;
        }
        .first-col {
            min-width: 200px;
            font-weight: bold;
        }
		th.sticky-col.first-col,th.userlist{background: #003152;
			color: #fff;}
        .userlist {
            position: sticky;
            top: 0;
            background-color: #f1f8ff;
            z-index: 2;
            min-width: 140px;
            white-space: nowrap;
        }
        .subcompcolor {
            background-color: #e8f5e9;
        }
        .responsive-table-wrapper {
            overflow-x: auto;
        }
    </style>';


	foreach ($get_competencyheading as $key => $competencyheading) {
	
	$sqlcompetency_category = "SELECT cc.id , cc.name,cc.roleid
						          FROM {competency_category} as cc 
						          WHERE cc.isdeleted=0 and cc.ctid=? and cc.roleid=? and cc.buid=?
						          ORDER by cc.id";
	$competency_category = $DB->get_records_sql($sqlcompetency_category, array($competencyheading->id,$roleid,$buid));
	 
	if(!empty($competency_category)){
				 $rows.= '<div class="card mb-3">';
            $rows .= '<div class="card-header text-white" data-bs-toggle="collapse" data-bs-target="#heading' . $competencyheading->id . '" style="cursor:pointer; background:#003152;">'.$competencyheading->title.'</div>';
            $rows .= '<div class="collapse show" id="heading' . $competencyheading->id . '">';
            $rows .= '<div class="card-body">';
            $rows .= '<div class="responsive-table-wrapper" style="border-radius: 10px;
			border: 1px solid lightgrey;">';
            $rows .= '<table class="main-table table table-bordered" style="border-collapse: collapse;" >';
		$rows .='<tr style="border:1px solid #000; background:#003152;">';
			if($roleid != 7){
				$alluserquery = "SELECT u.* FROM {user} as u 
				INNER JOIN {role_assignments} as ra  ON ra.userid=u.id
				INNER JOIN {role} as r  ON r.id=ra.roleid 
				WHERE u.id != 1 and r.id = '".$roleid."' and u.department = '".$buid."'";
				$allUsers = $DB->get_records_sql($alluserquery, array());
			} else {
				// $alluserquery = "SELECT u.* FROM {user} as u WHERE u.id NOT IN(SELECT ra.userid FROM {user} as u 
				// INNER JOIN {role_assignments} as ra  ON ra.userid=u.id
				// INNER JOIN {role} as r  ON r.id=ra.roleid 
				// WHERE u.id != 1  and u.department = '".$buid."') AND u.id != 1";

				$allUsers = get_authenticated_users($buid);

			}
			
			$userArr =array();
			// if(count($allUsers) > 0){
			// 	$rows .='<th class="competency_title sticky-col first-col" style="width:200px;"><span>'.$competencyheading->title.'</span></th>';
			// 	$rowCnt ++;
			// }
			$rows.='<th class="competency_title sticky-col first-col" style="width:200px;">Competency</th>';
			foreach ($allUsers as $key => $user) {
				
				$rows .='<th class="userlist">'.fullname($user).'</th>';
				$userArr[]=$user->id;

			}
			
		$rows .='</tr>';
	}
		if (!empty($competency_category)) {
			$rows .= '<tr >';
			foreach ($competency_category as $competency_categorys) {
				if (count($allUsers) > 0) {
					$rows .= '<td class="sticky-col first-col subcompcolor"> &nbsp; ' . $competency_categorys->name . '</td>';
				}

				for ($i = 0; $i < count($allUsers); $i++) {
					$chkuserinsertSql = "select * from {competency_users} where userid='" . $userArr[$i] . "' and ctid='" . $competencyheading->id . "' and competencyid='" . $competency_categorys->id . "' and subcompetencyid='0' and roleid='" . $competency_categorys->roleid . "'";
					$chkuserinsertResult = $DB->get_records_sql($chkuserinsertSql, array());
					$checksubVal = $userArr[$i] . '~' . $competency_categorys->id . '~' . $competencyheading->id . '~' . $competency_categorys->roleid;
					$passVal = $userArr[$i] . '~' . $competency_categorys->id . '~' . $competencyheading->id . '~' . $competency_categorys->roleid;

					if (count($chkuserinsertResult) > 0) {
						$checked = "checked";
						$rows .= '<td class="usersrow" ><input type="checkbox" name="check_list[]" class="subcompetencyclass" id="subcompetencyId" value="' . $checksubVal . '" rel ="' . $checksubVal . '" ' . $checked . ' onclick="subcompetencyfunc(' . $userArr[$i] . ',1,' . $competencyheading->id . ',' . $competency_categorys->id . ',0,' . $competency_categorys->roleid . ',3);" /></td>';
					} else {
						$checked = "";
						$rows .= '<td class="usersrow" ><input type="checkbox" name="check_list[]" class="subcompetencyclass" id="subcompetencyId" value="' . $checksubVal . '" rel ="' . $checksubVal . '" ' . $checked . ' onclick="subcompetencyfunc(' . $userArr[$i] . ',1,' . $competencyheading->id . ',' . $competency_categorys->id . ',0,' . $competency_categorys->roleid . ',4);" /></td>';
					}
					$rowCnt++;
				}


				$rows .= '</tr>';
				$sqlcompetencies = "SELECT * FROM {competencies} as co
							          WHERE co.isdeleted=0 and co.ccid=? 
							          ORDER by co.id";
				$competencies = $DB->get_records_sql($sqlcompetencies, array($competency_categorys->id));

				foreach ($competencies as $key => $competencie) {
					if ($competencie->comptencyname == '') {
						continue;
					}
					$comptencyname = $competencie->comptencyname;
					$rows .= '<tr>';
					if (count($allUsers) > 0) {
						$rows .= '<td style="padding-left: 40px !important; width:200px;" class="sticky-col first-col"> &nbsp;&nbsp; ' . $comptencyname . '</td>';
					}
					for ($i = 0; $i < count($allUsers); $i++) {
						$chkuserinsertSql1 = "select * from {competency_users} where userid='" . $userArr[$i] . "' and ctid='" . $competencyheading->id . "' and competencyid='" . $competency_categorys->id . "' and subcompetencyid='" . $competencie->id . "' and roleid='" . $competency_categorys->roleid . "'";
						$chkuserinsertResult1 = $DB->get_records_sql($chkuserinsertSql1, array());
						$checksubsubVal = $userArr[$i] . '~' . $competency_categorys->id . '~' . $competencyheading->id . '~' . $competency_categorys->roleid . '~' . $competencie->id;

						if (count($chkuserinsertResult1) > 0) {
							$checked1 = "checked";
							$rows .= '<td class="usersrow" ><input type="checkbox" name="check_list1[]" id="subsubcompId" ' . $checked1 . ' onclick="subcompetencyfunc(' . $userArr[$i] . ',2,' . $competencyheading->id . ',' . $competency_categorys->id . ',' . $competencie->id . ',' . $competency_categorys->roleid . ',3);" value="' . $checksubsubVal . '"></td>';
						} else {
							$checked1 = "";
							$rows .= '<td class="usersrow" ><input type="checkbox" name="check_list1[]" id="subsubcompId" onclick="subcompetencyfunc(' . $userArr[$i] . ',2,' . $competencyheading->id . ',' . $competency_categorys->id . ',' . $competencie->id . ',' . $competency_categorys->roleid . ',4);" value="' . $checksubsubVal . '"></td>';
						}

					}
					$rows .= '</tr>';
				}
			}
			   $rows .= '</tbody></table>';
            $rows .= '</div>'; // responsive wrapper
            $rows .= '<div class="mt-2 text-end"><a href="approval.php" class="btn btn-primary">Submit</a></div>';
            $rows .= '</div></div></div>';
		}
			
	}
	if (count($allUsers) == 0 || $allUsers <= 0) {
		$rows .= "<tr><td>No record found !</td></tr>";
	} else {
	
	}

	echo $rows;

}
 else if ($case == 'approvalformAdd') {

	$userId = required_param('userId', PARAM_INT);
	$mainId = required_param('mainId', PARAM_INT);
	$subcompId = required_param('subcompId', PARAM_INT);
	$subsubcompId = required_param('subsubcompId', PARAM_INT);
	if (empty($subsubcompId)) {
		$subsubcompId = 0;
	}
	$roleId = required_param('roleId', PARAM_INT);
	$year = date('Y');
	$competencyUserObj = new stdClass();
	$competencyUserObj->userid = $userId;
	$competencyUserObj->ctid = $mainId;
	$competencyUserObj->competencyid = $subcompId;
	$competencyUserObj->subcompetencyid = $subsubcompId;
	$competencyUserObj->roleid = $roleId;
	$competencyUserObj->year = $year;
	$insertResult = $DB->insert_record('competency_users', $competencyUserObj);
	if ($insertResult) {
		if ($subsubcompId == 0) {
			$mappingSql = "select * from {competencycat_courses} where competencycatid=? and isdeleted=0";
			$mappingResult = $DB->get_records_sql($mappingSql, array($subcompId));
			foreach ($mappingResult as $key => $value) {
				$courseids .= $value->courseid . ',';
			}
			$courseids = rtrim($courseids, ',');

			$mappingObj = new stdClass();
			$mappingObj->master_competencyid = $mainId;
			$mappingObj->competencyid = $subcompId;
			$mappingObj->subcomptencyid = $subsubcompId;
			$mappingObj->courseid = $courseids;
			$mappingResult = $DB->insert_record('course_mapping', $mappingObj);
		} else {
			$mappingSql = "select * from {competency_courses} where competencyid=? and isdeleted=0";
			$mappingResult = $DB->get_records_sql($mappingSql, array($subsubcompId));
			foreach ($mappingResult as $key => $value) {
				$courseids .= $value->courseid . ',';
			}
			$courseids = rtrim($courseids, ',');
			$mappingObj = new stdClass();
			$mappingObj->master_competencyid = $mainId;
			$mappingObj->competencyid = $subcompId;
			$mappingObj->subcomptencyid = $subsubcompId;
			$mappingObj->courseid = $courseids;
			$mappingResult = $DB->insert_record('course_mapping', $mappingObj);
		}
		$message = "selected user has been inserted successfully.";
	} else {
		$message = "Something went wrong please try again !";
	}
	echo $message;


} else if ($case == 'approvalformDelete') {

	$userId = required_param('userId', PARAM_INT);
	$mainId = required_param('mainId', PARAM_INT);
	$subcompId = required_param('subcompId', PARAM_INT);
	$subsubcompId = required_param('subsubcompId', PARAM_INT);
	if (empty($subsubcompId)) {
		$subsubcompId = 0;
	}
	$roleId = required_param('roleId', PARAM_INT);
	$year = date('Y');
	$chkuserinsertSql = "select * from {competency_users} where userid=? and ctid=? and competencyid=? and subcompetencyId=? and roleid=?";
	$chkuserinsertResult = $DB->get_records_sql($chkuserinsertSql, array($userId, $mainId, $subcompId, $subsubcompId, $roleId));
	foreach ($chkuserinsertResult as $key => $value) {
		$deletedId = $value->id;
	}
	$result = $DB->delete_records('competency_users', array('id' => $deletedId));
	if ($subsubcompId == 0) {
		$mappingSql = "select * from {course_mapping} where competencyid=? and master_competencyid=? and subcomptencyid=?";
		$mappingResult = $DB->get_records_sql($mappingSql, array($subcompId, $mainId, $subsubcompId));
		foreach ($mappingResult as $key => $value) {
			$mappingdeleteid = $value->id;
			$mapdeleteresult = $DB->delete_records('course_mapping', array('id' => $mappingdeleteid));
		}
	} else {
		$mappingSql = "select * from {course_mapping} where competencyid=? and master_competencyid=? and subcomptencyid=?";
		$mappingResult = $DB->get_records_sql($mappingSql, array($subcompId, $mainId, $subsubcompId));
		foreach ($mappingResult as $key => $value) {
			$mappingdeleteid = $value->id;
			$mapdeleteresult = $DB->delete_records('course_mapping', array('id' => $mappingdeleteid));
		}
	}

} else if ($case == 'fiterManagerRating') {

	//$roleid = required_param('roleid', PARAM_INT);
	$buid = required_param('buid', PARAM_TEXT);
	$userid = required_param('userid', PARAM_INT);
	$tearmsid = required_param('tearmsid', PARAM_INT);
	$firstQuery = "SELECT DISTINCT cu.ctid, ct.title,mr.finalrating
						FROM {competency_users} as cu 
						LEFT JOIN {competency_title} ct ON cu.ctid = ct.id 
						LEFT JOIN {competency_category} cc ON cu.competencyid = cc.id 
						LEFT JOIN {competencies} c ON c.id = cu.subcompetencyid 
						LEFT JOIN {user} u ON u.id=cu.userid
						LEFT JOIN {manager_rating} mr ON cu.id=mr.master_competencyid and mr.tearms = '" . $tearmsid . "'
						where cu.userid = ? and u.department = ?";

	$firstQueryResult = $DB->get_records_sql($firstQuery, array($userid, $buid));

	$i = 0;
	$rows = '';
	$temp = 0;
	$readonly = 'readonly="readonly"';
	$value = '';

	$readonly1 = 'readonly="readonly"';
	$value1 = '';
	$ratingValue = 0;
	$ratingValue1 = 0;
	$landdArr = array();
	foreach ($firstQueryResult as $key => $firstValue) {
		$rows .= '<table class="rating-table">';

		$rows .= '<tr>';

		$rows .= '<th class="competency_title sticky-col first-col" style="width:200px;">' . $firstValue->title . '</th>';
		$rows .= '<th class="userlist" >' . get_string('managersrating', 'local_competency') . '</th>';
		$rows .= '<th class="userlist" > ' . get_string('studentsrating', 'local_competency') . ' </th>';
		if ($tearmsid == 2) {
			$rows .= '<th class="userlist" style="color:#000;background-color:#ec9707;"> ' . get_string('landdpreviousrating', 'local_competency') . ' </th>';
			$rowcnt = 6;
		} else {
			$rowcnt = 5;
		}
		$rows .= '<th class="userlist" > ' . get_string('managerfinalrating', 'local_competency') . ' </th>';

		$rows .= '<th class="userlist" > ' . get_string('landdstatuslabel', 'local_competency') . ' </th>';

		$rows .= '</tr>';


		$secondQuery = "SELECT DISTINCT cu.id as cuid, cc.name, c.comptencyname, cc.id, cu.subcompetencyid,cu.competencyid,mr.finalrating
	        					FROM {competency_users} as cu 
						        LEFT JOIN {competency_title} ct ON cu.ctid = ct.id 
						        LEFT JOIN {competency_category} cc ON cu.competencyid = cc.id 
						        LEFT JOIN {competencies} c ON c.id = cu.subcompetencyid 
						        LEFT JOIN {user} u ON u.id=cu.userid 
						        LEFT JOIN {manager_rating} mr ON cu.id = mr.master_competencyid and mr.tearms = '" . $tearmsid . "'
						        WHERE cu.userid = $userid and u.department = '" . $buid . "' and cu.ctid = $firstValue->ctid order by cu.competencyid, cu.subcompetencyid";

		$secondQueryResult = $DB->get_records_sql($secondQuery, array());


		foreach ($secondQueryResult as $key => $secondValue) {	// sub competency name as second Value	        			    		
			$managerid = getManagerRatingMasterId($secondValue->cuid, $tearmsid);

			$studentid = getStudentRatingMasterId($secondValue->cuid, $tearmsid);
			$studentArrCount = $studentid[0];
			$studentArr = $studentid[1];

			$managerArrCount = $managerid[0];
			$managerArr = $managerid[1];
			$preManagerRating = 0;
			if ($tearmsid == 2) {
				$preManagerRating = previousManagerRating($secondValue->cuid, $tearmsid);
				if ($preManagerRating == 0) {
					$predisable = 'readonly';
				}
			}

			if ($managerArrCount > 0 && $studentArrCount > 0) {
				$readonly = '';
			}
			if ($managerArrCount > 0) {
				$value = 'value="' . $managerArr['rating'] . '"';
				$showvalue = $managerArr['rating'];
			} else {
				$value = 'value=""';
				$showvalue = 0;
			}
			if ($studentArrCount > 0) {
				$ratingValue = $studentArr['rating'];
			} else {
				$ratingValue = 0;
			}
			if ($ratingValue == 0) {
				$readonly = 'readonly';
			}
			$landdratingid = getLandDRatingStatusMasterId($secondValue->cuid, $secondValue->id, $secondValue->subcompetencyid, $tearmsid);
			$landdArrCount = $landdratingid[0];
			$landdArr = $landdratingid[1];
			if ($landdArr['landdstatus'] == 1) {
				$landdstatus = 'Approved';
				$readonly = 'readonly';
			} else if ($landdArr['landdstatus'] == 2) {
				$landdstatus = 'Rejected';
				$readonly = '';
			} else {
				if ($ratingValue == 0) {
					$readonly = 'readonly';
				} else {
					$readonly = '';
				}
				$landdstatus = 'NA';
			}


			if ($secondValue->subcompetencyid == 0) {
				$rows .= '<tr>';

				$rows .= '<td class="sticky-col first-col subcompcolor"> &nbsp; ' . $secondValue->name . '</td>';

				$rows .= '<td class="usersrow" ><input type="hidden" name="mastercompetencyid[]" id="mastercompetencyid" value="' . $secondValue->cuid . '" /><input type="hidden" name="tearmsid" id="tearmsid" value="' . $tearmsid . '" /> <input type="hidden" name="competencyid[]" id="competencyid" value="' . $secondValue->competencyid . '" />';
				if ($studentArrCount > 0) {
					$rows .= ' <input type="hidden" min="0" max="10" name="manager_rating[]"  id="managerrating" ' . $value . '' . $predisable . '  />' . $showvalue . '</td>';
				} else {
					$rows .= ' <input type="number" min="0" max="10" name="manager_rating[]"  id="managerrating" ' . $value . '' . $predisable . '  /></td>';
				}
				$rows .= '<td class="usersrow" ><span>' . $ratingValue . '</span></td>';

				if ($tearmsid == 2) {
					$rows .= '<td class="usersrow" style="background-color:#ec9707; color:#003152;"><span>' . $preManagerRating . '</span></td>';
				}


				$rows .= '<td class="usersrow" ><input type="number" min="0" max="10" name="managerfinal_rating[]" id="managerfinalrating" value="' . $secondValue->finalrating . '"  ' . $readonly . ' /></td>';
				$rows .= '<td class="usersrow" ><span>' . $landdstatus . '</span></td>';

				$rows .= '<tr>';
			}

			$thirdQuery = "SELECT DISTINCT  c.id as ccid,c.comptencyname, cu.subcompetencyid, cu.id, cu.competencyid, mr.finalrating 
	            			   FROM {competency_users} as cu 
	            			   LEFT JOIN {competency_title} ct ON cu.ctid = ct.id 
	            			   LEFT JOIN {competency_category} cc ON cu.competencyid = cc.id 
	            			   LEFT JOIN {competencies} c ON c.id = cu.subcompetencyid
	            			   LEFT JOIN {user} u ON u.id=cu.userid 
	            			   LEFT JOIN {manager_rating} mr ON cu.id = mr.master_competencyid and mr.tearms = '" . $tearmsid . "'
	            			   where cu.userid = ?  and u.department = ?  and c.id = ? order by c.comptencyname";
			$thirdQueryResult = $DB->get_records_sql($thirdQuery, array($userid, $buid, $secondValue->subcompetencyid));
			foreach ($thirdQueryResult as $thirdValue) {				// Sub sub competency as thirdValue


				$managerid = getManagerRatingMasterId($secondValue->cuid, $tearmsid);

				$studentid = getStudentRatingMasterId($secondValue->cuid, $tearmsid);

				$studentArrCount = $studentid[0];
				$managerArrCount = $managerid[0];
				$managerArr = $managerid[1];

				$preManagerRating = '';
				if ($tearmsid == 2) {
					$preManagerRating1 = previousManagerRating($secondValue->cuid, $tearmsid);
					if ($preManagerRating1 == 0) {
						$predisable = 'readonly';
					}
				}

				if ($managerArrCount > 0 && $studentArrCount > 0) {
					$readonly1 = '';
				}
				if ($managerArrCount > 0) {
					$value1 = 'value="' . $managerArr['rating'] . '"';
					$showvalue1 = $managerArr['rating'];
				} else {
					$value1 = 'value=""';
					$showvalue1 = 0;
				}

				if ($studentArrCount > 0) {
					$ratingValue1 = $studentArr['rating'];
				} else {
					$ratingValue1 = 0;
				}

				if ($ratingValue1 == 0) {
					$readonly1 = 'readonly';
				}
				$landdratingid1 = getLandDRatingStatusMasterId($thirdValue->id, $thirdValue->competencyid, $thirdValue->subcompetencyid, $tearmsid);
				$landdArrCount1 = $landdratingid1[0];
				$landdArr1 = $landdratingid1[1];

				if ($landdArr1['landdstatus'] == 1) {
					$landdstatus1 = 'Approved';
					$readonly1 = 'readonly';
				} else if ($landdArr1['landdstatus'] == 2) {
					$landdstatus1 = 'Rejected';
					$readonly1 = '';
				} else {
					if ($ratingValue1 == 0) {
						$readonly1 = 'readonly';
					} else {
						$readonly1 = '';
					}
					$landdstatus1 = 'NA';

				}


				$rows .= '<tr>';

				$rows .= '<td style="padding-left: 40px !important; width:200px;" class="sticky-col first-col"> &nbsp;&nbsp; ' . $thirdValue->comptencyname . '</td>';
				$rows .= '<td class="usersrow" ><input type="hidden" name="mastercompetencyid1[]" id="mastercompetencyid" value="' . $thirdValue->id . '" /><input type="hidden" name="competencyid1[]" id="competencyid" value="' . $thirdValue->competencyid . '" /> <input type="hidden" name="subcompetencyid[]" id="subcompetencyid" value=' . $thirdValue->subcompetencyid . ' />';
				if ($studentArrCount > 0) {
					$rows .= '<input type="hidden" min="0" max="10" name="manager_rating1[]"  id="managerrating" ' . $value1 . '' . $predisable . '  />' . $showvalue1 . '</td>';
				} else {
					$rows .= '<input type="number" min="0" max="10" name="manager_rating1[]"  id="managerrating" ' . $value1 . '' . $predisable . '  /></td>';
				}
				$rows .= '<td class="usersrow" ><span> ' . $ratingValue1 . ' </span></td>';

				if ($tearmsid == 2) {
					$rows .= '<td class="usersrow" style="background-color:#ec9707; color:#003152;"><span>' . $preManagerRating1 . '</span></td>';
				}

				$rows .= '<td class="usersrow" ><input type="number" min="0" max="10" name="managerfinal_rating1[]" id="managerfinalrating1" value="' . $thirdValue->finalrating . '" ' . $readonly1 . ' /></td>';
				$rows .= '<td class="usersrow" ><span>' . $landdstatus1 . '</span></td>';

				$rows .= '<tr>';
				

			}

		}
		$temp = 1;
	}
	if ($temp != 1) {
		$rows .= "No records found !";
	} else {
		$rows .= '<tr style="border:0px;"><td colspan="' . $rowcnt . '"><button type="submit" class="btn btn-primary" value="addrating" name="submitmanagerrating">Submit</button></td></tr>';
	}
	$rows .= '<input type="hidden" name="userid" value="' . $userid . '" />';
	echo $rows;


} else if ($case == 'fiterDepartment') {

	$departmentId = required_param('departmentId', PARAM_TEXT);
	$userid = required_param('userid', PARAM_INT);
	$dapartment = '';

	$userSql = "SELECT DISTINCT ud.userid, u.* from {user_info_data} as ud 
				  INNER join {user_info_field} as uf on ud.fieldid=uf.id 
				  Inner join {user} u on ud.userid=u.id where ud.data LIKE '" . $userid . "%' and department = ?";

	$allUsers = $DB->get_records_sql($userSql, array($departmentId));

	$dapartment .= '<select name="userid" id="userid" class="form-control">
            <option value="">Select user</option>';
	foreach ($allUsers as $user) {
		$fullname = fullname($user);
		$dapartment .= "<option value='" . $user->userid . "'>" . $fullname . "  ( " . $user->email . " ) </option>";
	}
	$dapartment .= '</select>';

	echo $dapartment;


} else if ($case == 'fiterlanddRating') {

	//$roleid = required_param('roleid', PARAM_INT);
	$buid = required_param('buid', PARAM_TEXT);
	$userid = required_param('userid', PARAM_INT);
	$tearmsid = required_param('tearmsid', PARAM_INT);
	$firstQuery = "SELECT DISTINCT cu.ctid, ct.title
						FROM {competency_users} as cu 
						LEFT JOIN {competency_title} ct ON cu.ctid = ct.id 
						LEFT JOIN {competency_category} cc ON cu.competencyid = cc.id 
						LEFT JOIN {competencies} c ON c.id = cu.subcompetencyid 
						LEFT JOIN {user} u ON u.id=cu.userid						
						LEFT JOIN {landd_rating} lr ON cu.id=lr.master_competencyid and lr.tearms = '" . $tearmsid . "'
						where cu.userid = ? and u.department = ?";

	$firstQueryResult = $DB->get_records_sql($firstQuery, array($userid, $buid));


	$i = 0;
	$rows = '';
	$temp = 0;
	$readonly = 'readonly="readonly"';
	$value = '';
	$rowcnt = 0;
	$readonly1 = 'readonly="readonly"';
	$value1 = '';
	$ratingValue = 0;
	$ratingValue1 = 0;
	$no = 0;
	$no1 = 0;
	foreach ($firstQueryResult as $key => $firstValue) {

		$rows .= '<tr>';

		$rows .= '<th class="competency_title sticky-col first-col" style="width:200px;"><span>' . $firstValue->title . '</span></th>';
		$rows .= '<th class="userlist" >' . get_string('managersrating', 'local_competency') . '</th>';
		$rows .= '<th class="userlist" > ' . get_string('studentsrating', 'local_competency') . ' </th>';
		$rows .= '<th class="userlist" > ' . get_string('managerfinalrating', 'local_competency') . ' </th>';
		if ($tearmsid == 2) {
			$rows .= '<th class="userlist" style="color:#000; background-color:#5b7f99;"> ' . get_string('landdpreviousrating', 'local_competency') . ' </th>';
			$rowcnt = 9;
		} else {
			$rowcnt = 8;
		}
		$rows .= '<th class="userlist" > ' . get_string('landdrating', 'local_competency') . ' </th>';
		$rows .= '<th class="userlist" > ' . get_string('landdratingstatus', 'local_competency') . ' </th>';
		$rows .= '<th class="userlist" style="color:#fff; background-color:#5b7f99; width:100px;"> ' . get_string('managerratestate', 'local_competency') . ' </th>';
		$rows .= '<th class="userlist" > ' . get_string('landdratingcompletingstatus', 'local_competency') . ' </th>';

		$rows .= '</tr>';

		$secondQuery = "SELECT cu.id as cuid, cc.name, c.comptencyname, cc.id, 
	        					cu.subcompetencyid,cu.competencyid,lr.progstatus
	        				    FROM {competency_users} as cu 
								LEFT JOIN {competency_title} ct ON cu.ctid = ct.id 
								LEFT JOIN {competency_category} cc ON cu.competencyid = cc.id 
								LEFT JOIN {competencies} c ON c.id = cu.subcompetencyid 
								LEFT JOIN {user} u ON u.id=cu.userid								
								LEFT JOIN {landd_rating} lr ON cu.id=lr.master_competencyid and lr.tearms = '" . $tearmsid . "'
						        WHERE cu.userId = ? and u.department = ? and cu.ctid = ? order by cu.competencyid , cu.subcompetencyid";

		$secondQueryResult = $DB->get_records_sql($secondQuery, array($userid, $buid, $firstValue->ctid));


		foreach ($secondQueryResult as $key => $secondValue) {	// sub competency name as second Value	        			    		
			$managerid = getManagerRatingMasterId($secondValue->cuid, $tearmsid);

			$studentid = getStudentRatingMasterId($secondValue->cuid, $tearmsid);

			$landdratingid = getLandDRatingMasterId($secondValue->cuid, $tearmsid);
			$preRating = '';
			if ($tearmsid == 2) {
				$preRating = previousLanddRating($secondValue->cuid, $tearmsid);
				if ($preRating == 0) {
					$predisable = 'readonly';
				}
			}
			//$preRating ='';
			$studentArrCount = $studentid[0];
			$studentArr = $studentid[1];

			$managerArrCount = $managerid[0];
			$managerArr = $managerid[1];

			$landdratingArrCount = $landdratingid[0];
			$landdratingArr = $landdratingid[1];

			if ($managerArrCount > 0 && $studentArrCount > 0) {
				$readonly = '';
			}
			$approvalStatus = "No";
			if ($managerArrCount > 0) {
				$value = 'value="' . $managerArr['rating'] . '"';
				$valueshow = $managerArr['rating'];
				$finalvalue = 'value="' . $managerArr['finalrating'] . '"';
				$finalvalueshow = $managerArr['finalrating'];

				if ($secondValue->progstatus == 1) {
					$progressOption = '<option value="2">Inprogress</option>
                     	<option value="1" selected="selected">Completed</option>';

				} else if ($secondValue->progstatus == 2) {
					$progressOption = '<option value="2" selected="selected" >Inprogress</option>
                     	<option value="1" >Completed</option>';
				} else {
					$progressOption = '<option value="2">Inprogress</option>
                     	<option value="1" >Completed</option>';
				}
				$progressStatus = '<select  name="progstatus[]" id="progstatus" class="progSize">
	        		<option value="0">Status</option>' . $progressOption . '</select>';

			} else {
				$value = 'value=""';
				$valueshow = 0;
				$finalvalue = 'value=""';
				$finalvalueshow = 0;
				$progressStatus = '<select  name="progstatus[]" id="progstatus" class="progSize">
	        		<option value="0">Status</option></select>';

			}
			if ($studentArrCount > 0) {
				$ratingValue = $studentArr['rating'];
			} else {
				$ratingValue = 0;
			}

			if ($landdratingArrCount > 0) {
				$landdratingValue = 'value="' . $landdratingArr['rating'] . '"';
				//$landdstatusValue= 'value="'.$landdratingArr['landdstatus'].'"';
				if ($landdratingArr['landdstatus'] == 1) {
					$landdstatuschecked = '<select  name="landdstatus[]" id="landdstatus" onchange ="filterCheckStatus(this.value,' . $no . ')" class="landdsize">
		        		<option value="0">Status</option>
		        		<option value="1" selected="selected">Approve</option>
	                    <option value="2" >Reject</option></select>';
					$approvalStatus = "Yes";

				} else {
					if ($managerArr['rating'] == 0) {
						$landdstatuschecked = '<select  name="landdstatus[]" id="landdstatus" onchange ="filterCheckStatus(this.value,' . $no . ')" class="landdsize">
	                     	<option value="0" selected="selected">Status</option>
			        		<option value="1" >Approve</option>
		                    <option value="2">Reject</option></select>';
					} else {
						$landdstatuschecked = '<select  name="landdstatus[]" id="landdstatus" onchange ="filterCheckStatus(this.value,' . $no . ')" class="landdsize">
		                     	<option value="0">Status</option>
				        		<option value="1" >Approve</option>
			                    <option value="2" selected="selected">Reject</option></select>';
					}
					$approvalStatus = "No";
				}
			} else {
				$landdratingValue = 'value="' . $managerArr['finalrating'] . '"';
				$landdstatuschecked = '<select  name="landdstatus[]" id="landdstatus" onchange ="filterCheckStatus(this.value,' . $no . ')" class="landdsize">
	        		<option value="0">Status</option>
	        		<option value="1">Approve</option>
                    <option value="2" >Reject</option></select>';
				$approvalStatus = "No";
			}

			$managerRateStatus = getManagerRatingRateMasterId($secondValue->cuid, $secondValue->id, $secondValue->subcompetencyid, $tearmsid, $userid);


			if ($secondValue->subcompetencyid == 0) {
				$rows .= '<tr>';

				$rows .= '<td class="sticky-col first-col subcompcolor"> &nbsp; ' . $secondValue->name . '</td>';

				$rows .= '<td class="usersrow" ><input type="hidden" name="mastercompetencyid[]" id="mastercompetencyid" value="' . $secondValue->cuid . '" /><input type="hidden" name="tearmsid" id="tearmsid" value="' . $tearmsid . '" /> <input type="hidden" name="competencyid[]" id="competencyid" value="' . $secondValue->competencyid . '" /> <input type="hidden" min="0" max="10" name="manager_rating[]"  id="managerrating" ' . $value . '  />' . $valueshow . '</td>';

				$rows .= '<td class="usersrow" ><span>' . $ratingValue . '</span></td>';

				$rows .= '<td class="usersrow" ><input type="hidden" min="0" max="10" name="managerfinal_rating[]" id="managerfinalrating" value="' . $finalvalue . '"  ' . $readonly . ' class="txtSize"/>' . $finalvalueshow . '</td>';
				if ($tearmsid == 2) {
					$rows .= '<td class="usersrow" style="background-color:#5b7f99; color:#fff"><span>' . $preRating . '</span></td>';
				}

				$rows .= '<td class="usersrow" ><input type="number" min="0" max="10" name="landd_rating[]" id="landd_rating" ' . $landdratingValue . '  ' . $readonly . '' . $predisable . ' class="txtSize" /></td>';

				$rows .= '<td class="usersrow" >' . $landdstatuschecked . '</td>';

				$rows .= '<td class="usersrow" style="background-color:#5b7f99; color:#fff"><span>' . $managerRateStatus . '</span></td>';

				if ($approvalStatus == 'Yes') {
					$rows .= '<td class="usersrow" ><span id="prgshow_' . $no . '">' . $progressStatus . '</span></td>';
				} else {
					$rows .= '<td class="usersrow" ><span id="prgshow_' . $no . '"><select  name="progstatus[]" id="progstatus" class="progSize">
	        			<option value="0">Status</option></select></span></td>';
				}

				$rows .= '<tr>';
				$no++;
			}

			$thirdQuery = "SELECT  c.id as ccid, c.comptencyname,cu.subcompetencyid, cu.id, cu.competencyid,lr.progstatus 
	            			   FROM {competency_users} as cu 
								LEFT JOIN {competency_title} ct ON cu.ctid = ct.id 
								LEFT JOIN {competency_category} cc ON cu.competencyid = cc.id 
								LEFT JOIN {competencies} c ON c.id = cu.subcompetencyid 
								LEFT JOIN {user} u ON u.id=cu.userid								
								LEFT JOIN {landd_rating} lr ON cu.id=lr.master_competencyid and lr.tearms = '" . $tearmsid . "'
	            			   where cu.userid = ?  and u.department = ?  and c.id = ? order by c.comptencyname";
			$thirdQueryResult = $DB->get_records_sql($thirdQuery, array($userid, $buid, $secondValue->subcompetencyid));
			foreach ($thirdQueryResult as $thirdValue) {				// Sub sub competency as thirdValue


				$managerid = getManagerRatingMasterId($secondValue->cuid, $tearmsid);

				$studentid = getStudentRatingMasterId($secondValue->cuid, $tearmsid);

				$landdratingid = getLandDRatingMasterId($secondValue->cuid, $tearmsid);
				$preRating1 = '';
				if ($tearmsid == 2) {
					$preRating1 = previousLanddRating($secondValue->cuid, $tearmsid);
					if ($preRating1 == 0) {
						$predisable = 'readonly';
					}
				}
				//$preRating1 ='';
				$studentArrCount = $studentid[0];
				$managerArrCount = $managerid[0];
				$managerArr = $managerid[1];

				$landdratingArrCount = $landdratingid[0];
				$landdratingArr = $landdratingid[1];
				$approvalStatus1 = 'No';
				if ($managerArrCount > 0 && $studentArrCount > 0) {
					$readonly1 = '';
				}
				if ($managerArrCount > 0) {
					$value1 = 'value="' . $managerArr['rating'] . '"';
					$valueshow1 = $managerArr['rating'];
					$finalvalue1 = 'value="' . $managerArr['finalrating'] . '"';
					$finalvalueshow1 = $managerArr['finalrating'];

					if ($thirdValue->progstatus == 1) {
						$progressOption1 = '<option value="2">Inprogress</option>
	                     	<option value="1" selected="selected">Completed</option>';
					} else if ($thirdValue->progstatus == 2) {
						$progressOption1 = '<option value="2" selected="selected" >Inprogress</option>
	                     	<option value="1" >Completed</option>';
					} else {
						$progressOption1 = '<option value="2">Inprogress</option>
	                     	<option value="1" >Completed</option>';
					}
					$progressStatus1 = '<select  name="progstatus1[]" id="progstatus" class="progSize">
		        		<option value="0">Status</option>' . $progressOption1 . '</select>';

				} else {
					$value1 = 'value=""';
					$valueshow1 = 0;
					$finalvalue1 = 'value=""';
					$finalvalueshow1 = $managerArr['finalrating'];
					$progressStatus1 = '<select  name="progstatus1[]" id="progstatus" class="progSize">
	        			<option value="0">Status</option></select>';
				}

				if ($studentArrCount > 0) {
					$ratingValue1 = $studentArr['rating'];
				} else {
					$ratingValue1 = 0;
				}

				if ($landdratingArrCount > 0) {
					$landdratingValue1 = 'value="' . $landdratingArr['rating'] . '"';

					if ($landdratingArr['landdstatus'] == 1) {
						$landdstatuschecked1 = '<select  name="landdstatus1[]" id="landdstatus" onchange ="filterCheckStatus1(this.value,' . $no1 . ')" class="landdsize">
			        		<option value="0">Status</option>
			        		<option value="1" selected="selected">Approve</option>
		                     	<option value="2" >Reject</option></select>';
						$approvalStatus1 = "Yes";
					} else if ($landdratingArr['landdstatus'] == 2) {
						$landdstatuschecked1 = '<select  name="landdstatus1[]" id="landdstatus" onchange ="filterCheckStatus1(this.value,' . $no1 . ')" class="landdsize">
	                     	<option value="0">Status</option>
			        		<option value="1" >Approve</option>
		                    <option value="2" selected="selected">Reject</option></select>';
						$approvalStatus1 = "No";


					} else if ($landdratingArr['landdstatus'] == 0) {
						$landdstatuschecked1 = '<select  name="landdstatus1[]" id="landdstatus" onchange ="filterCheckStatus1(this.value,' . $no1 . ')" class="landdsize">
	                     	<option value="0" selected="selected">Status</option>
			        		<option value="1" >Approve</option>
		                    <option value="2">Reject</option></select>';
						$approvalStatus1 = "No";


					}
				} else {
					$landdratingValue1 = 'value="' . $managerArr['finalrating'] . '"';
					$landdstatuschecked1 = '<select  name="landdstatus1[]" id="landdstatus" onchange ="filterCheckStatus1(this.value,' . $no1 . ')" class="landdsize">
	                     	<option value="0">Status</option>
			        		<option value="1" >Approve</option>
		                    <option value="2">Reject</option></select>';
					$approvalStatus1 = "No";
				}

				$managerRateStatus1 = getManagerRatingRateMasterId($thirdValue->id, $thirdValue->competencyid, $thirdValue->subcompetencyid, $tearmsid, $userid);

				$rows .= '<tr>';

				$rows .= '<td class="sticky-col first-col wraptxt" style="padding-left: 40px !important;width:200px;"> &nbsp;&nbsp; ' . $thirdValue->comptencyname . '</td>';
				$rows .= '<td class="usersrow" ><input type="hidden" name="mastercompetencyid1[]" id="mastercompetencyid" value="' . $thirdValue->id . '" /><input type="hidden" name="competencyid1[]" id="competencyid" value="' . $thirdValue->competencyid . '" /> <input type="hidden" name="subcompetencyid[]" id="subcompetencyid" value=' . $thirdValue->subcompetencyid . ' /> <input type="hidden" min="0" max="10" name="manager_rating1[]"  id="managerrating" ' . $value1 . '   />' . $valueshow1 . '</td>';

				$rows .= '<td class="usersrow" ><span> ' . $ratingValue1 . ' </span></td>';

				$rows .= '<td class="usersrow" ><input type="hidden" min="0" max="10" name="managerfinal_rating1[]" id="managerfinalrating1" value="' . $finalvalue1 . '"  ' . $readonly1 . ' class="txtSize" />' . $finalvalueshow1 . '</td>';
				if ($tearmsid == 2) {
					$rows .= '<td class="usersrow" style="background-color:#5b7f99; color:#fff;"><span>' . $preRating1 . '</span></td>';
				}

				$rows .= '<td class="usersrow" ><input type="number" min="0" max="10" name="landd_rating1[]" id="landd_rating" ' . $landdratingValue1 . '  ' . $readonly1 . '' . $predisable . ' class="txtSize" /></td>';

				$rows .= '<td class="usersrow" >' . $landdstatuschecked1 . '</td>';

				$rows .= '<td class="usersrow" style="background-color:#5b7f99; color:#fff"><span> ' . $managerRateStatus1 . ' </span></td>';

				if ($approvalStatus1) {
					$rows .= '<td class="usersrow" ><span id="prgshow1_' . $no1 . '">' . $progressStatus1 . '</span></td>';
				} else {
					$rows .= '<td class="usersrow" ><span id="prgshow1_' . $no1 . '"><select  name="progstatus1[]" id="progstatus" class="progSize">
	        			<option value="0">Status</option></select></span></td>';
				}
				$rows .= '<tr>';
				$no1++;
			}

		}
		$temp = 1;
	}
	if ($temp != 1) {
		$rows .= "No records found !";
	} else {
		$rows .= '<tr style="border:0px;"><td colspan="' . $rowcnt . '"><button type="submit" class="btn btn-primary" value="addrating" name="submitmanagerrating">Submit</button></td></tr>';
	}
	$rows .= '<input type="hidden" name="userid" value="' . $userid . '" />';
	echo $rows;


} else if ($case == 'fiterDepartmentLandD') {

	$departmentId = required_param('departmentId', PARAM_TEXT);
	$roleid = required_param('roleid', PARAM_INT);
	$dapartment = '';
	if ($roleid != 7) {
		$userSql = "SELECT u.id as uid, u.* FROM {user} as u 
			INNER JOIN {role_assignments} as ra  ON ra.userid=u.id
			INNER JOIN {role} as r  ON r.id=ra.roleid 
			WHERE u.id != 1 and r.id = ? and u.department = ? ";

		$allUsers = $DB->get_records_sql($userSql, array($roleid, $departmentId));
	} else {
		$allUsers = get_authenticated_users($departmentId);
	}
	$dapartment .= '<select name="userid" id="userid" class="form-control">
            <option value="">Select user</option>';
	foreach ($allUsers as $user) {
		$fullname = fullname($user);
		$dapartment .= "<option value='" . $user->id . "'>" . $fullname . "  ( " . $user->email . " ) </option>";
	}
	$dapartment .= '</select>';

	echo $dapartment;


} else if ($case == 'filterCheckStatus') {

	$statusid = required_param('statusid', PARAM_TEXT);
	if ($statusid == 1) {
		$status = '<select  name="progstatus[]" id="progstatus">
	        		<option value="0">Status</option>
	        		<option value="1" >Completed</option>
	        		<option value="2">Inprogress</option>
	               </select>';
	} else if ($statusid == 0) {
		$status = '<select  name="progstatus[]" id="progstatus">
	        		<option value="0">Status</option>
	               </select>';
	} else if ($statusid == 2) {
		$status = '<select  name="progstatus[]" id="progstatus">
	        		<option value="0">Status</option>
	        		<option value="1" >Completed</option>
	        		<option value="2">Inprogress</option>
	               </select>';
	}

	echo $status;
} else if ($case == 'filterCheckStatus1') {

	$statusid = required_param('statusid', PARAM_TEXT);
	if ($statusid == 1) {
		$status = '<select  name="progstatus1[]" id="progstatus" style="width:108px">
	        		<option value="0">Status</option>
	        		<option value="1" >Completed</option>
	        		<option value="2">Inprogress</option>
	               </select>';
	} else if ($statusid == 0) {
		$status = '<select  name="progstatus1[]" id="progstatus" style="width:108px">
	        		<option value="0">Status</option>
	               </select>';
	} else if ($statusid == 2) {
		$status = '<select  name="progstatus1[]" id="progstatus" style="width:108px">
	        		<option value="0">Status</option>
	        		<option value="1" >Completed</option>
	        		<option value="2">Inprogress</option>
	               </select>';
	}


	echo $status;
}
?>