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
$activepage = 'subsubcompetency';
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_title(get_string('sub_sub_competency', 'local_competency'));
$PAGE->set_url($CFG->wwwroot . '/local/competency/subsubcompetency.php');
$PAGE->set_heading(get_string('sub_sub_competency', 'local_competency'));
$PAGE->navbar->add(get_string('sub_sub_competency', 'local_competency'));
echo $OUTPUT->header();
//header added
require_once($CFG->dirroot . '/local/competency/header.php');
require_once($CFG->dirroot . '/local/competency/tabs.php');
if (!has_capability('local/competency:managesubsubcompetency', $context)) {
	redirect($CFG->wwwroot . '/my/', \core\notification::error('No access for Manager rating...'));
	exit();
}

// search post field
$subcid = optional_param('svsubid', '', PARAM_INT);
$subccid = optional_param('svsubsubid', '', PARAM_INT);

// add sub sub competency
$competencysubcategory = optional_param('competencysubcategory', '', PARAM_TEXT);
$courselist = optional_param_array('courseid', array(), PARAM_TEXT);
//$roleid = optional_param('roleid','', PARAM_TEXT);
$compentencyCategoryid = optional_param('compentencyCategoryid', '', PARAM_TEXT);

// Determine selected company from session or user profile field
$selectedcompany = 0;

if (!empty($SESSION->currenteditingcompany)) {
	$selectedcompany = $SESSION->currenteditingcompany;

} else {
	// Fallback: fetch from user profile field 'company' (if applicable)
	$fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => 'company']);

	if ($fieldid) {
		$companydata = $DB->get_record('user_info_data', [
			'userid' => $USER->id,
			'fieldid' => $fieldid
		]);

		if ($companydata && is_numeric($companydata->data)) {
			$selectedcompany = (int) $companydata->data;
		}
	}
}

if (!empty($competencysubcategory) && !empty($compentencyCategoryid)) {
	if (!has_capability('local/competency:managesubsubcompetency', $context)) {
		redirect($CFG->wwwroot . '/my/', \core\notification::error('No access for Manager rating...'));
		exit();
	}
	$courseAr = implode(',', $courselist);
	$courseArr = explode(',', $courseAr);
	$validate = getValidateStringField($competencysubcategory);
	$validateId = getValidateNumberField($compentencyCategoryid);
	if (empty($validate) && empty($validateId)) {
		//competency category table insertion
		$competencySubCategoryObj = new stdClass();
		$competencySubCategoryObj->comptencyname = $competencysubcategory;
		$competencySubCategoryObj->ccid = $compentencyCategoryid;
		//$competencySubCategoryObj->roleid = $roleid;
		$competencySubCategoryObj->isdeleted = 0;
		$competencySubCategoryObj->timecreated = time();
		$competencySubCategoryObj->timemodified = time();
		$insertedid = $DB->insert_record('competencies', $competencySubCategoryObj);

		//competency mdl_competency_courses table insertation
		foreach ($courseArr as $coid) {
			$competencysubCourseObj = new stdClass();
			$competencysubCourseObj->courseid = $coid;
			$competencysubCourseObj->competencyid = $insertedid;
			$competencysubCourseObj->isdeleted = 0;
			//$competencyCourseObj->timecreated = time();
			//$competencyCourseObj->timemodified = time();
			$insertecoursedid = $DB->insert_record('competency_courses', $competencysubCourseObj);
		}
		$message = "Sub Sub-Competency has been created successfully.";
		if (!empty($id)) {
			$id = $compentencyCategoryid;
		}
	} else {
		$message = "Sub Sub competency must only contain letters!";
	}
}

// edit sub sub competency

if (optional_param('editsubcompetencyname', '', PARAM_TEXT) === 'Edit') {
	if (!has_capability('local/competency:managesubsubcompetency', $context)) {
		redirect($CFG->wwwroot . '/my/', \core\notification::error('No access for Manager rating...'));
		exit();
	}
	$editCompetencySubCategory = optional_param('editCompetencySubCategory', '', PARAM_TEXT);
	$editccsid = optional_param('editccsid', '', PARAM_INT);
	$courselist = optional_param_array('editcourseid', array(), PARAM_TEXT);
	$editCompentencyCategoryid = optional_param('editCompentencyCategoryid', '', PARAM_TEXT);

	if (!empty($editCompetencySubCategory) && !empty($editccsid) && !empty($editCompentencyCategoryid)) {
		$courseid = implode(',', $courselist);
		$courseArr = explode(',', $courseid);
		$validate = getValidateStringField($editCompetencySubCategory);
		$validateId = getValidateNumberField($editCompentencyCategoryid);
		$validateId1 = getValidateNumberField($editccsid);
		if (empty($validate) && empty($validateId) && empty($validateId1)) {
			//updatation	
			$competencyCategoryObj = new stdClass();
			$competencyCategoryObj->comptencyname = $editCompetencySubCategory;
			$competencyCategoryObj->id = $editccsid;
			$competencyCategoryObj->ccid = $editCompentencyCategoryid;
			$competencyCategoryObj->timemodified = time();
			$updatedid = $DB->update_record('competencies', $competencyCategoryObj);

			//competency course cat table updatation
			$compArr = array();
			$compCountSql = "select * from {competency_courses} where competencyid=? and courseid NOT IN('" . $courseid . "')";
			$compCountResult = $DB->get_records_sql($compCountSql, array($editccsid));
			foreach ($compCountResult as $key => $comp) {
				//$catArr[] = $cat->courseid;
				$competencyCateCourseObj = new stdClass();
				$competencyCateCourseObj->courseid = $comp->courseid;
				$competencyCateCourseObj->competencyid = $editccsid;
				$competencyCateCourseObj->id = $comp->id;
				$competencyCateCourseObj->isdeleted = 1;
				$deletecoursedid = $DB->update_record('competency_courses', $competencyCateCourseObj);
			}

			foreach ($courseArr as $coid) {
				$catSql = "select * from {competency_courses} where competencyid=? and courseid=?";
				$catResult = $DB->get_records_sql($catSql, array($editccsid, $coid));
				if (count($catResult) > 0) {
					foreach ($catResult as $key => $value) {
						$competencySubCourseObj = new stdClass();
						$competencySubCourseObj->courseid = $coid;
						$competencySubCourseObj->competencyid = $editccsid;
						$competencySubCourseObj->id = $value->id;
						$competencySubCourseObj->isdeleted = 0;
						$updatecoursedid = $DB->update_record('competency_courses', $competencySubCourseObj);
					}
				} else {
					$competencySubCourseObj = new stdClass();
					$competencySubCourseObj->courseid = $coid;
					$competencySubCourseObj->competencyid = $editccsid;
					$competencySubCourseObj->isdeleted = 0;
					$insertecoursedid = $DB->insert_record('competency_courses', $competencySubCourseObj);
				}
			}
			$message = "Sub Sub-Competency has been updated successfully.";
			if (!empty($id)) {
				$id = $editCompentencyCategoryid;
			}
		} else {
			$message = "Sub Sub competency must only contain letters!";
		}
	}
}
// delete sub sub competency
$deleteccsid = optional_param('deleteccsid', '', PARAM_TEXT);
if (!empty($deleteccsid)) {
	$validate = getValidateNumberField($deleteccsid);
	if (empty($validate)) {
		//delete competencies table row
		$competencySubCategoryObj = new stdClass();
		$competencySubCategoryObj->id = $deleteccsid;
		$competencySubCategoryObj->isdeleted = 1;
		$competencySubCategoryObj->timemodified = time();
		$deletedid = $DB->update_record('competencies', $competencySubCategoryObj);

		//delete competency_courses table all courses
		$catSql = "select * from {competency_courses} where competencyid=? and isdeleted=0";
		$catResult = $DB->get_records_sql($catSql, array($deleteccsid));
		foreach ($catResult as $key => $value) {
			$competencyCateCourseObj = new stdClass();
			$competencyCateCourseObj->competencyid = $deleteccsid;
			$competencyCateCourseObj->id = $value->id;
			$competencyCateCourseObj->isdeleted = 1;
			$deletecoursedid = $DB->update_record('competency_courses', $competencyCateCourseObj);
		}
		$message = "Sub Sub-Competency has been deleted successfully.";
		if (!has_capability('local/competency:managesubsubcompetency', $context)) {
			redirect($CFG->wwwroot . '/my/', \core\notification::error('No access for Manager rating...'));
			exit();
		}
	} else {
		$message = "Something went wrong please try again!";
	}
}

// search code added
$viewcontentbody = '';
$subsubselct = '';
$subselct = '';
global $USER, $DB, $SESSION;

// Detect current company (tenant) user or main tenant
if (!empty($SESSION->currenteditingcompany)) {
	$selectedcompany = $SESSION->currenteditingcompany;
} else if (!empty($USER->profile->company)) {
	// fallback if needed
	$selectedcompany = $USER->profile->company;
} else {
	$selectedcompany = 0; // Main tenant
}

// Query only sub competencies belonging to selected company (or all for main tenant)
$params = ['isdeleted' => 0];
$sql = "SELECT cc.*
        FROM {competency_category} cc
        JOIN {competency_title} ct ON ct.id = cc.ctid
        WHERE cc.isdeleted = 0";

// Filter by company for company users
if ($selectedcompany > 0) {
	$sql .= " AND ct.companyid = :companyid";
	$params['companyid'] = $selectedcompany;
}

$getsubcomp = $DB->get_records_sql($sql, $params);

// Generate dropdown
$subselct = '<select name="svsubid" id="svsubid" class="form-control" onchange="changeSubcomp(this.value)">
            <option value="">Select Sub Competency</option>';
foreach ($getsubcomp as $subcomp) {
	$selected = ($subcid == $subcomp->id) ? "selected='selected'" : '';
	$subselct .= "<option value='{$subcomp->id}' {$selected}>{$subcomp->name}</option>";
}
$subselct .= '</select>';
// Get Sub Sub Competency dropdown
if (empty($subcid) && empty($subccid)) {
	$subsubselct .= '<select name="svsubsubid" id="svsubsubid" class="form-control">
            <option value="">Select Sub Sub Competency</option>
        </select>';
} else {
	$subsubselct .= '<select name="svsubsubid" id="svsubsubid" class="form-control">
        <option value="">Select Sub Sub Competency</option>';

	$getsubsubcomp = $DB->get_records('competencies', ['ccid' => $subcid, 'isdeleted' => 0]);

	foreach ($getsubsubcomp as $subsubcomp) {
		$selected = ($subccid == $subsubcomp->id) ? "selected='selected'" : '';
		$subsubselct .= "<option value='{$subsubcomp->id}' {$selected}>{$subsubcomp->comptencyname}</option>";
	}

	$subsubselct .= '</select>';
}
$viewcontentbody = '<form method="post"><div class="row" style="text-align:center;margin-bottom:10px;margin-top:10px;">
	<div class="col-md-3">
		' . $subselct . '
	</div>
	<div class="col-md-4" id="cometenciesshow">
		' . $subsubselct . '
	</div>
		
	<div class="col-md-2"><button type="submit" class="btn btn-primary">Search</button></div>
	</div></form><p id="errormessage" style="color:red;text-align:center;"></p>';


if (!empty($message)) {
	?>
	<br />
	<div class="alert alert-success successmessgae"> <?php echo $message; ?></div>
<?php }


// Sub sub competency
$table = new html_table();
$table->head = array('Sub Competency', 'Sub Sub Competency', 'Courses', 'Action');

// Get count for pagination
if (!empty($subcid) && !empty($subccid)) {
	$listSubSubCompetencyCount = getListSubSubCompetencyCount($subcid, $subccid, $selectedcompany);
	$id = $subcid;
} elseif (!empty($subcid)) {
	$listSubSubCompetencyCount = getListSubSubCompetencyCount($subcid, '', $selectedcompany);
	$id = $subcid;
} else {
	$listSubSubCompetencyCount = getListSubSubCompetencyCount('', '', $selectedcompany);
}

$pagesArr = getPaginationDisplay($listSubSubCompetencyCount, $selectPageNo, $limit);
$pages = $pagesArr[0];
$start = $pagesArr[1];

$subsubCompetencyResult = getListSubSubCompetency($id, $subccid, $start, $limit, $selectedcompany);

// Sub sub Competency pagination (prepare early)
$pagination = '';
if ($pages > 1) {
	$pagination = custompagination2($selectPageNo, $pages, $subcid, $subccid, 'tabsubsubcompetency');
}

// Output table rows
foreach ($subsubCompetencyResult as $subsubcompetency) {
	$action = '
        <a href="#" data-toggle="modal" data-target="#deleteModalSubcompetency" onclick="competencySubCategoryDelete(' . $subsubcompetency->id . ')">
            <i class="icon fa fa-trash fa-fw" title="Delete" aria-label="Delete"></i>
        </a>
        <a href="#" data-toggle="modal" data-target="#editModalSubcompetency" onclick="competencySubCategoryEdit(' . $subsubcompetency->id . ')">
            <i class="icon fa fa-cog fa-fw" title="Edit"></i>
        </a>';

	// Get sub competency name
	$subname = '-';
	$subResultId = getSelectedSubCompetency($subsubcompetency->ccid, $selectedcompany);
	if (!empty($subResultId)) {
		foreach ($subResultId as $sublist) {
			$subname = $sublist->name;
		}
	}

	// Get course names
	$coursename = '';
	$getcourseResult = getComptencyCoursesList($subsubcompetency->id);
	if (!empty($getcourseResult)) {
		foreach ($getcourseResult as $cvalue) {
			$coursename .= $cvalue->shortname . ', ';
		}
		$coursename = rtrim($coursename, ', ');
	}

	$table->data[] = array($subname, $subsubcompetency->comptencyname, $coursename, $action);
}

// Fetch full category list (optional: if needed for dropdown)
$categoryResult = getSelectedSubCompetency('', $selectedcompany);

// Fetch all courses (if needed elsewhere)
$courseResult = getCourseList();

// Output final HTML
$subsubcompetencybody = '<div class="tab-pane show active" id="tabsubsubcompetency" role="tabpanel" aria-labelledby="completed-tab">' .
	html_writer::link(new moodle_url('#'), 'Add Sub Sub Competency', array(
		'class' => 'btn btn-primary',
		'data-toggle' => 'modal',
		'data-target' => '#AddModalSubcompetency',
		'style' => 'margin-bottom:10px;margin-top:10px;'
	)) .
	$viewcontentbody .
	html_writer::table($table) .
	$pagination .
	'</div>';

echo $subsubcompetencybody;
?>
<!--Sub Competency --->
<!-- Add Modal -->
<div class="modal fade" id="AddModalSubcompetency" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
	aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Add Sub Sub Competency</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="POST" action="" name="competencysubAddfms" id="competencysubAddfms">
				<div class="modal-body">
					<div class="form-group">
						<label for="compentencyCategoryAdd">Competency</label>
						<select class="form-control" id="sel1" name="compentencyCategoryid" id="compentencyCategoryid"
							required="true">
							<option value="">Select field</option>
							<?php foreach ($categoryResult as $key => $ctlist) { ?>

								<option value="<?php echo $ctlist->id; ?>"><?php echo $ctlist->name; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group">
						<!--<label for="competencyHeading">Competency Title</label>-->
						<input type="text" class="form-control" id="competencysubcategory" name="competencysubcategory"
							aria-describedby="emailHelp" placeholder="Enter Sub Sub Competency" required="true">
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
					<!--<div class="form-group">
		<label for="roleid">Role</label>
		<select class="form-control" name="roleid" id="roleid">
					   <option value=""></option>
				</select>
	  </div>-->
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Add</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>
<!-- Edit Modal -->
<div class="modal fade" id="editModalSubcompetency" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
	aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Edit Sub Sub Competency</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="POST" action="" name="subcompetencyEditfms" id="subcompetencyEditfms">
				<div class="modal-body">
					<div class="form-group">
						<label for="compentencyCategoryEditlabel">Competency</label>
						<span id="compentencyCategoryEdit"></span>
					</div>
					<div class="form-group">
						<input type="hidden" name="editccsid" id="editccsid" value="" />
						<input type="text" class="form-control" id="editCompetencySubCategory" value=""
							name="editCompetencySubCategory" aria-describedby="emailHelp"
							placeholder="Enter Sub Sub Competency" required="true">
					</div>
					<div class="form-group">
						<label for="editcoid">Courses</label>
						<span id="editSubCourseid"></span>
					</div>

					<!--<div class="form-group">
		<label for="rolid">Role</label>
		<span id="editroleid"></span>
	  </div>-->
				</div>
				<div class="modal-footer">
					<button type="submit" value="Edit" name="editsubcompetencyname"
						class="btn btn-primary">Save</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- delete Model-->
<div class="modal fade" id="deleteModalSubcompetency" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
	aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel"><b>Delete Sub Sub Competency</b></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="POST" action="" name="competencySubDeletefms" id="competencySubDeletefms">
				<div class="modal-body">
					<h5 class="modal-title" id="exampleModalLabel">Are you want to delete ?</h5>
					<input type="hidden" name="deleteccsid" id="deleteccsid" value="" />
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
		// Prevent duplicate submission on refresh
		if (window.history.replaceState) {
			window.history.replaceState(null, null, window.location.href);
		}

		// Disable submit button after submit to prevent double click
		const form = document.getElementById('competencysubAddfms');
		const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

		if (form && submitBtn) {
			form.addEventListener('submit', function () {
				submitBtn.disabled = true;
				submitBtn.innerText = 'Submitting...'; // Optional visual feedback
			});
		}
	});
</script>

<?php $PAGE->requires->js('/local/competency/js/competency.js'); ?>
<?php $PAGE->requires->js('/local/competency/js/report.js?v=1'); ?>
<?php echo $OUTPUT->footer(); ?>