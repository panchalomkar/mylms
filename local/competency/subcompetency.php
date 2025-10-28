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
$activepage = 'subcompetency';
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_title(get_string('sub_competency', 'local_competency'));
$PAGE->set_url($CFG->wwwroot . '/local/competency/subcompetency.php');
$PAGE->set_heading(get_string('sub_competency', 'local_competency'));
$PAGE->navbar->add(get_string('sub_competency', 'local_competency'));
echo $OUTPUT->header();
//header added
require_once($CFG->dirroot . '/local/competency/header.php');
require_once($CFG->dirroot . '/local/competency/tabs.php');
if (!has_capability('local/competency:managesubcompetency', $context)) {
	redirect($CFG->wwwroot . '/my/', \core\notification::error('No access...'));
	exit();
}
// search post field
$mainid = optional_param('svmainid', '', PARAM_INT);
$subcid = optional_param('svsubid', '', PARAM_INT);
// add competency Category
$competencyCategory = optional_param('competencyCategory', '', PARAM_TEXT);
$courselist = optional_param_array('courseid', array(), PARAM_TEXT);
$roleid = optional_param('roleid', '', PARAM_TEXT);
$mainHeading = optional_param('mainHeading', '', PARAM_TEXT);
$buid = optional_param('buid', '', PARAM_TEXT);
if (!empty($competencyCategory) && !empty($mainHeading) && !empty($roleid)) {
	if (!has_capability('local/competency:managesubcompetency', $context)) {
		redirect($CFG->wwwroot . '/my/', \core\notification::error('No access...'));
		exit();
	}
	$courseAr = implode(',', $courselist);
	$courseArr = explode(',', $courseAr);
	$validate = getValidateStringField($competencyCategory);
	$validateId = getValidateNumberField($mainHeading);
	$validateId1 = getValidateUnitExistsField($buid);
	$validateId2 = getValidateNumberField($roleid);
	if (empty($validate) && empty($validateId) && empty($validateId1) && empty($validateId2)) {
		//competency category table insertion
		$competencyCategoryObj = new stdClass();
		$competencyCategoryObj->name = $competencyCategory;
		$competencyCategoryObj->ctid = $mainHeading;
		$competencyCategoryObj->buid = $buid;
		$competencyCategoryObj->roleid = $roleid;
		$competencyCategoryObj->isdeleted = 0;
		$competencyCategoryObj->timecreated = time();
		$competencyCategoryObj->timemodified = time();
		$insertedid = $DB->insert_record('competency_category', $competencyCategoryObj);

		//competency course cat table insertation
		foreach ($courseArr as $coid) {
			$competencyCourseObj = new stdClass();
			$competencyCourseObj->courseid = $coid;
			$competencyCourseObj->competencycatid = $insertedid;
			$competencyCourseObj->isdeleted = 0;
			//$competencyCourseObj->timecreated = time();
			//$competencyCourseObj->timemodified = time();
			$insertecoursedid = $DB->insert_record('competencycat_courses', $competencyCourseObj);
		}
		if (!empty($id)) {
			$id = $mainHeading;
		}
		$message = "Sub competency has been created successfully.";
	} else {
		$message = "Sub competency must only contain letters!";
	}
}


// edit competency category
$competencyEditCategory = optional_param('competencyEditCategory', '', PARAM_TEXT);
$editccid = optional_param('editccid', '', PARAM_INT);
$courselist = optional_param('editcourseid', '', PARAM_TEXT);
$roleid = optional_param('editroleid', '', PARAM_TEXT);
$catid = optional_param('catid', '', PARAM_TEXT);
$mainHeadingEditId = optional_param('mainHeadingEditId', '', PARAM_TEXT);
$buEditId = optional_param('buEditId', '', PARAM_TEXT);
if (!empty($competencyEditCategory) && !empty($editccid) && !empty($mainHeadingEditId)) {
	if (!has_capability('local/competency:managesubcompetency', $context)) {
		redirect($CFG->wwwroot . '/my/', \core\notification::error('No access...'));
		exit();
	}
	$courseid = implode(',', $courselist);
	$courseArr = explode(',', $courseid);
	$validate = getValidateStringField($competencyEditCategory);
	$validateId = getValidateNumberField($mainHeadingEditId);
	$validateId1 = getValidateUnitExistsField($buEditId);
	$validateId2 = getValidateNumberField($roleid);
	if (empty($validate) && empty($validateId) && empty($validateId1) && empty($validateId2)) {
		//updatation	
		$competencyCategoryObj = new stdClass();
		$competencyCategoryObj->name = $competencyEditCategory;
		$competencyCategoryObj->id = $editccid;
		$competencyCategoryObj->ctid = $mainHeadingEditId;
		$competencyCategoryObj->roleid = $roleid;
		$competencyCategoryObj->buid = $buEditId;
		$competencyCategoryObj->timemodified = time();
		$updatedid = $DB->update_record('competency_category', $competencyCategoryObj);

		//competency course cat table updatation
		$catArr = array();
		$catCountSql = "select * from {competencycat_courses} where competencycatid=? and courseid NOT IN('" . $courseid . "')";
		$catCountResult = $DB->get_records_sql($catCountSql, array($editccid));
		foreach ($catCountResult as $key => $cat) {
			//$catArr[] = $cat->courseid;
			$competencyCateCourseObj = new stdClass();
			$competencyCateCourseObj->courseid = $cat->courseid;
			$competencyCateCourseObj->competencycatid = $editccid;
			$competencyCateCourseObj->id = $cat->id;
			$competencyCateCourseObj->isdeleted = 1;
			$deletecoursedid = $DB->update_record('competencycat_courses', $competencyCateCourseObj);
		}

		foreach ($courseArr as $coid) {
			$catSql = "select * from {competencycat_courses} where competencycatid=? and courseid=?";
			$catResult = $DB->get_records_sql($catSql, array($editccid, $coid));
			if (count($catResult) > 0) {
				foreach ($catResult as $key => $value) {
					$competencyCourseObj = new stdClass();
					$competencyCourseObj->courseid = $coid;
					$competencyCourseObj->competencycatid = $editccid;
					$competencyCourseObj->id = $value->id;
					$competencyCourseObj->isdeleted = 0;
					$updatecoursedid = $DB->update_record('competencycat_courses', $competencyCourseObj);
				}
			} else {
				$competencyCourseObj = new stdClass();
				$competencyCourseObj->courseid = $coid;
				$competencyCourseObj->competencycatid = $editccid;
				$competencyCourseObj->isdeleted = 0;
				$insertecoursedid = $DB->insert_record('competencycat_courses', $competencyCourseObj);
			}
		}
		if (!empty($id)) {
			$id = $mainHeadingEditId;
		}
		$message = "Sub competency has been updated successfully.";
	} else {
		$message = "Sub competency must only contain letters!";
	}
}

// delete competency 
$deletecompetencyid = optional_param('deletecompetencyid', '', PARAM_TEXT);
if (!empty($deletecompetencyid)) {
	$validate = getValidateNumberField($deletecompetencyid);
	if (empty($validate)) {
		//delete competency_category table row
		$competencyCategoryObj = new stdClass();
		$competencyCategoryObj->id = $deletecompetencyid;
		$competencyCategoryObj->isdeleted = 1;
		$competencyCategoryObj->timemodified = time();
		$deletedid = $DB->update_record('competency_category', $competencyCategoryObj);

		//delete competencycat_courses table all courses
		$catSql = "select * from {competencycat_courses} where competencycatid=? and isdeleted=0";
		$catResult = $DB->get_records_sql($catSql, array($deletecompetencyid));
		foreach ($catResult as $key => $value) {
			$competencyCateCourseObj = new stdClass();
			$competencyCateCourseObj->competencycatid = $deletecompetencyid;
			$competencyCateCourseObj->id = $value->id;
			$competencyCateCourseObj->isdeleted = 1;
			$deletecoursedid = $DB->update_record('competencycat_courses', $competencyCateCourseObj);
		}
		$message = "Sub competency has been deleted successfully.";
		if (!has_capability('local/competency:managesubcompetency', $context)) {
			redirect($CFG->wwwroot . '/my/', \core\notification::error('No access...'));
			exit();
		}
		//delete for sub sub competency
		$subcompdelete = "update {competencies} set isdeleted= 1 where ccid = ?";
		$DB->execute($subcompdelete, array($deletecompetencyid));
	} else {

		$message = "Something went wrong please try again!";
	}
}

// search added
$viewselct = '';
$viewcontentbody = '';
$subselct = '';
$searchmaincomp = getmaincomplist();
$viewselct .= '<select name="svmainid" id="svmainid" class="form-control" onchange="changeMaincomp(this.value)">
	<option value="">Select Main Competency</option>';
foreach ($searchmaincomp as $key1 => $value1) {
	if ($mainid == $value1->id) {
		$viewselct .= '<option value="' . $value1->id . '" selected="selected">' . $value1->title . '</option>';
	} else {
		$viewselct .= "<option value='" . $value1->id . "'>" . $value1->title . "</option>";
	}

}
$viewselct .= '</select>';

//Get sub comptency 
if (empty($mainid) && empty($subcid)) {

	$subselct .= '<select name="svsubid" id="svsubid" class="form-control" onchange="changeSubcomp(this.value)">
	        <option value="">Select Sub competency</option>';
	$subselct .= '</select>';

} else {

	$getsubcomp = $DB->get_records('competency_category', array('ctid' => $mainid));
	$subselct = '';
	$subselct .= '<select name="svsubid" id="svsubid" class="form-control" onchange="changeSubcomp(this.value)">
            <option value="">Select Sub Competency</option>';
	foreach ($getsubcomp as $subcomp) {
		if ($subcid == $subcomp->id) {
			$subselct .= "<option value='" . $subcomp->id . "' selected='selected'>" . $subcomp->name . "</option>";
		} else {
			$subselct .= "<option value='" . $subcomp->id . "'>" . $subcomp->name . "</option>";
		}
	}
	$subselct .= '</select>';

}

$viewcontentbody = '<form method="post"><div class="row" style="text-align:center;margin-bottom:10px;margin-top:10px;">
	<div class="col-md-3">
		' . $viewselct . '
	</div>
	<div class="col-md-3" id="subcompshow">
		' . $subselct . '
	</div>
	
	<div class="col-md-2"><button type="submit" class="btn btn-primary">Search</button></div>
	</div></form><p id="errormessage" style="color:red;text-align:center;"></p>';

if (!empty($message)) { ?>
	<br />
	<div class="alert alert-success successmessgae">
		<?php echo $message; ?>
	</div>
<?php }



//compentency category
$table = new html_table();
$table->head = array('Main Competency', 'Sub Competency', 'Business Unit', 'Role', 'Courses', 'Action');
if (!empty($mainid) && !empty($subcid)) {
	$listSubCompetencyCount = getListSubCompetencyCount($mainid, $subcid);
	$id = $mainid;
} elseif (!empty($mainid)) {
	$listSubCompetencyCount = getListSubCompetencyCount($mainid);
	$id = $mainid;
} else {
	$listSubCompetencyCount = getListSubCompetencyCount($id);
}

$pagesArr = getPaginationDisplay($listSubCompetencyCount, $selectPageNo, $limit);
$pages = $pagesArr[0];
$start = $pagesArr[1];

$completencyCategorys = getListSubCompetency($id, $subcid, $start, $limit);
//print_object($completencyCategorys);exit();

//get main heading list
$mainTitleResult = getListCompetencyTitle();

//get course list
$courseResult = getCourseList();

// Get BU departments list.
$buResult = getdepartment();

//get role list
$roleResult = getAllroles();


foreach ($completencyCategorys as $key => $completencyCategory) {

	$action = '<a href="#" data-toggle="modal" data-target="#deleteModalCompetency" onclick="competencyCategoryDelete(' . $completencyCategory->id . ')"><i class="icon fa fa-trash fa-fw " title="Delete title" aria-label="Delete"></i></a> <a href="subsubcompetency.php?id=' . $completencyCategory->id . '"><i class="icon fa fa-plus-square fa-fw " title="View Competency Category"></i></a> <a href="#" data-toggle="modal" data-target="#editModalCompetency" 
	onclick="competencyCategoryEdit(' . $completencyCategory->id . ')"><i class="icon fa fa-cog fa-fw" title="Edit title"></i></a>';


	//get Main compentency
	$mainTitleresult = getCompletencyTitleRecords($completencyCategory->ctid);
	foreach ($mainTitleresult as $mainVal) {
		$mainTitle = $mainVal->title;
	}
	//Sub Competency pagination
	if ($pages > 1) {
		$pagination = custompagination1($selectPageNo, $pages, $mainid, $subcid, 'tabsubcompetency');
	}

	//get role name	
	$roleResultId = getAllroles($completencyCategory->roleid);
	foreach ($roleResultId as $roles) {
		$rolename = $roles->shortname;
	}
	//get all coruses
	$coursename = '';
	$getcourseSql = "Select * from {competencycat_courses} cc INNER JOIN {course} c ON c.id=cc.courseid where cc.competencycatid=? and cc.isdeleted=0";
	$getcourseResult = $DB->get_records_sql($getcourseSql, array($completencyCategory->id));
	foreach ($getcourseResult as $key => $cvalue) {
		$coursename .= $cvalue->shortname . ',';
	}
	$coursename = rtrim($coursename, ',');

	$table->data[] = array($mainTitle, $completencyCategory->name, $completencyCategory->buid, $rolename, $coursename, $action);

}
$subcompetencybody = '<div class="tab-pane show active" id="tabsubcompetency" role="tabpanel" aria-labelledby="upcoming-tab">
								' . html_writer::link(new moodle_url('#', array()), 'Add Sub Competency', array('class' => 'btn btn-primary', 'data-toggle' => 'modal', 'data-target' => '#AddModalCompentency', 'style' => 'margin-bottom:10px;margin-top:10px;')) . ' ' . $viewcontentbody . ' ' . html_writer::table($table) . ' ' . $pagination . '
								</div> ';
echo $subcompetencybody;

?>
<!--Competency category--->
<!-- Add Modal -->
<div class="modal fade" id="AddModalCompentency" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
	aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Add Sub Competency</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="POST" action="" name="competencycategoryAddfms" id="competencycategoryAddfms">
				<div class="modal-body">
					<div class="form-group">
						<label for="mainHeading">Competency Heading</label>
						<input type="hidden" name="hiddencompetencyheading" id="hiddencompetencyheading" value="2">
						<select class="form-control" id="sel1" name="mainHeading" id="mainHeading" required="true">
							<option value="">Select Competency</option>
							<?php foreach ($mainTitleResult as $key => $titlename) { ?>
								<option value="<?php echo $titlename->id; ?>"><?php echo $titlename->title; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group">
						<!--<label for="competencyHeading">Competency Title</label>-->
						<input type="text" class="form-control" id="competencyCategory" name="competencyCategory"
							aria-describedby="emailHelp" placeholder="Enter Sub Competency" required="true">
					</div>
					<div class="form-group">
						<label for="courseid">Courses</label>
						<select multiple class="form-control" name="courseid[]" id="courseid">
							<?php foreach ($courseResult as $key => $courselist) { ?>
								<option value="<?php echo $courselist->id; ?>"><?php echo $courselist->shortname; ?>
								</option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group">
						<label for="roleid">Business Unit</label>
						<select class="form-control" name="buid" id="buid" required="true">
							<option value="">Select Business Unit</option>
							<?php
							foreach ($buResult as $key => $value) { ?>
								<option value="<?php echo $value->department; ?>"><?php echo $value->department; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group">
						<label for="roleid">Role</label>
						<select class="form-control" name="roleid" id="roleid" required="true">
							<option value="">Select Role</option>
							<?php foreach ($roleResult as $key => $rolelist) { ?>
								<option value="<?php echo $rolelist->id; ?>"><?php echo $rolelist->shortname; ?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="modal-footer">
					<button type="submit" name="subcompetency" id="subcompetency" value="subcompetency"
						class="btn btn-primary">Add</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>
<!-- Edit Modal -->
<div class="modal fade" id="editModalCompetency" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
	aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Edit Sub Competency</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="POST" action="" name="competencycategoryEditfms" id="competencycategoryEditfms">
				<div class="modal-body">
					<div class="form-group">
						<input type="hidden" name="hiddencompetencyheading" id="hiddencompetencyheading" value="2">
						<label for="mainHeadingEditlabel">Competency Heading</label>
						<span id="mainHeadingEdit"></span>
					</div>
					<div class="form-group">
						<input type="hidden" name="editccid" id="editccid" value="" />
						<input type="hidden" name="catid" id="catid" value="" />
						<input type="text" class="form-control" id="competencyEditCategory" value=""
							name="competencyEditCategory" aria-describedby="emailHelp"
							placeholder="Enter Sub Competency" required="true">
					</div>
					<div class="form-group">
						<label for="editcoid">Courses</label>
						<span id="editcourseid"></span>
					</div>
					<div class="form-group">
						<label for="buidlabel">Business Unit</label>
						<span id="buEdit"></span>
					</div>
					<div class="form-group">
						<label for="rolid">Role</label>
						<span id="editroleid"></span>
					</div>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Save</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- delete Model-->
<div class="modal fade" id="deleteModalCompetency" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
	aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel"><b>Delete Sub Competency</b></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="POST" action="" name="competencyAddfms" id="competencyAddfms">
				<div class="modal-body">
					<h5 class="modal-title" id="exampleModalLabel">Are you want to delete ?</h5>
					<input type="hidden" name="hiddencompetencyheading" id="hiddencompetencyheading" value="2">
					<input type="hidden" name="deletecompetencyid" id="deletecompetencyid" value="" />
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Delete</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		// If form submitted, replace state to prevent duplicate submission on refresh
		if (window.history.replaceState) {
			window.history.replaceState(null, null, window.location.href);
		}

		// Optional: Disable submit button after form submit to avoid double click
		const form = document.getElementById('competencycategoryAddfms');
		const submitBtn = document.getElementById('subcompetency');

		if (form) {
			form.addEventListener('submit', function () {
				submitBtn.disabled = true;
				submitBtn.innerText = 'Submitting...'; // Optional
			});
		}
	});
</script>

<?php $PAGE->requires->js('/local/competency/js/competency.js'); ?>
<?php $PAGE->requires->js('/local/competency/js/report.js?v=1'); ?>
<?php echo $OUTPUT->footer(); ?>