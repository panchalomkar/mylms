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
require_once($CFG->dirroot.'/local/competency/pagination.php');
require_once($CFG->dirroot.'/local/competency/lib.php');
$activepage = 'mainheading';
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_title(get_string('competency_title', 'local_competency'));
$PAGE->set_url($CFG->wwwroot.'/local/competency/mainheading.php');
$PAGE->set_heading(get_string('competency_title', 'local_competency'));
$PAGE->navbar->add(get_string('competency_title', 'local_competency'));
echo $OUTPUT->header();
global $USER, $CFG, $DB, $OUTPUT, $SESSION;
if (!empty($SESSION->currenteditingcompany)) {
    $selectedcompany = $SESSION->currenteditingcompany;
} else if (!empty($USER->profile->company)) {
    $usercompany = company::by_userid($USER->id);
    $selectedcompany = $usercompany->id;
} else {
    $selectedcompany = "";
}
//header added
require_once($CFG->dirroot.'/local/competency/header.php');
require_once($CFG->dirroot.'/local/competency/tabs.php');
if (!has_capability('local/competency:managemainheading', $context)) {
    redirect($CFG->wwwroot. '/my/', \core\notification::error('No access...'));
    exit();
}
// add competency heading
$competencyHeading = optional_param('competencyHeading','', PARAM_TEXT);
if(!empty($competencyHeading)){
  $validate = getValidateStringField($competencyHeading);
  if(empty($validate)){
    	$competencyHeadingObj = new stdClass();
      $competencyHeadingObj->title = $competencyHeading;
      $competencyHeadingObj->isdeleted = 0;
      $competencyHeadingObj->timecreated = time();
    	$competencyHeadingObj->timemodified = time();
      $competencyHeadingObj->companyid = $selectedcompany;
    	$insertedid = $DB->insert_record('competency_title', $competencyHeadingObj);
    	$message = "Main competency has been created successfully.";
  }else{
      $message = "Main competency must only contain letters!";
  }
  if (!has_capability('local/competency:managemainheading', $context)) {
      redirect($CFG->wwwroot. '/my/', \core\notification::error('No access...'));
      exit();
  }
}

// edit competency heading
$competencyEditHeading = optional_param('competencyEditHeading','', PARAM_TEXT);
$editcid = optional_param('editcid','', PARAM_INT);
if(!empty($competencyEditHeading) && !empty($editcid)){
  $validate = getValidateStringField($competencyEditHeading);
  $validateId = getValidateNumberField($editcid);
  if(empty($validate) && empty($validateId)){
    	$competencyHeadingObj = new stdClass();
      $competencyHeadingObj->title = $competencyEditHeading;
    	$competencyHeadingObj->id = $editcid;
    	$competencyHeadingObj->timemodified = time();
      $competencyHeadingObj->companyid = $selectedcompany;
    	$updatedid = $DB->update_record('competency_title', $competencyHeadingObj);
    	$message = "Main competency has been updated successfully.";
  }else{
      $message = "Main competency must only contain letters!";
  }
  if (!has_capability('local/competency:managemainheading', $context)) {
      redirect($CFG->wwwroot. '/my/', \core\notification::error('No access...'));
      exit();
  }
}

// delete competency heading
$deletecid = optional_param('deletecid','', PARAM_TEXT);
if(!empty($deletecid)){
      $validateId = getValidateNumberField($deletecid);
      if(empty($validateId)){
    if (!has_capability('local/competency:managemainheading', $context)) {
        redirect($CFG->wwwroot. '/my/', \core\notification::error('No access...'));
        exit();
    }
    //delete title heading
  	  $competencyHeadingObj = new stdClass();
      $competencyHeadingObj->id = $deletecid;
      $competencyHeadingObj->isdeleted = 1;
    	$competencyHeadingObj->timemodified = time();
    	$deletedid = $DB->update_record('competency_title', $competencyHeadingObj);

      //delete sub competency
      $subcompdelete = "update {competency_category} set isdeleted= 1 where ctid = ?";
      $DB->execute($subcompdelete, array($deletecid));

      //delete for sub sub competency
       $subsubcompdelete = "Update {competencies}  set isdeleted = 1 where ccid IN (select id From {competency_category} where ctid = ?)";
      $DB->execute($subsubcompdelete, array($deletecid));

    	$message = "Main competency has been deleted successfully.";
  }else{
      $message = "Something went wrong please try again!";
  }
}

if(!empty($message)){?> 
	<br/>
	<div class="alert alert-success successmessgae">
<?php echo $message; ?>
	</div> <?php }

//data show 
$table = new html_table();
$table->head = array('Main Competency', 'Action');

$listCompetencyCount = getListCompetencyTitleCount();

$pagesArr = getPaginationDisplay($listCompetencyCount, $selectPageNo, $limit);
$pages = $pagesArr[0];
$start = $pagesArr[1];

$completencyTitles = getListCompetencyTitle($start, $limit);
foreach ($completencyTitles as $key => $completencyTitle) {
	$action = '<a href="#" data-toggle="modal" data-target="#deleteModalHeading" onclick="competencyHeadingDelete('.$completencyTitle->id.')"><i class="icon fa fa-trash fa-fw " title="Delete title" aria-label="Delete"></i></a> <a href="subcompetency.php?id='.$completencyTitle->id.'"><i class="icon fa fa-plus-square fa-fw" title="View Competency Category"></i></a> <a href="#" data-toggle="modal" data-target="#editModalHeading" 
	onclick="competencyHeadingEdit('.$completencyTitle->id.')"><i class="icon fa fa-cog fa-fw" title="Edit title"></i></a>';

	$table->data[] = array($completencyTitle->title, $action);
}

//Main Heading pagination
if($pages > 1){
	$pagination = custompagination($selectPageNo,$pages,'tabmiainheading');
}
$mainbody ='<div class="tab-pane fade show active" id="tabmainheading" role="tabpanel" aria-labelledby="today-tab">'.html_writer::link(new moodle_url('#', array()), 'Add Main Competency', array('class' => 'btn btn-primary','data-toggle'=>'modal', 'data-target'=>'#AddModalHeading','style'=>'margin-bottom:10px;margin-top:10px;')).''.html_writer::table($table).' '.$pagination.'</div>';
echo $mainbody;
?>

<!--Main competency -->
<!-- Add Modal -->
<div class="modal fade" id="AddModalHeading" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add Main Competency</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
	  <form method="POST" action="" name="competencyAddfms" id="competencyAddfms">
      <div class="modal-body">
	  <div class="form-group">
		<!--<label for="competencyHeading">Competency Title</label>-->
		<input type="hidden" name="hiddencompetencyheading" id="hiddencompetencyheading" value="1">
		<input type="text" class="form-control" id="competencyHeading" name="competencyHeading" aria-describedby="emailHelp" placeholder="Enter Main Competency name" required="true">		
	  </div>	  
      </div>
      <div class="modal-footer">
	     <button type="submit" class="btn btn-primary" id="subhead" name="subhead" value="subhead" onclick="subhead(1)">Add</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>     
      </div>
	  </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModalHeading" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit Main Competency</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
	  <form method="POST" action="" name="competencyEditfms" id="competencyEditfms">
      <div class="modal-body">
	  <div class="form-group">
		<!--<label for="competencyHeading">Competency Title</label>-->
		<input type="hidden" name="editcid" id="editcid" value="" />
		<input type="hidden" name="hiddencompetencyheading" id="hiddencompetencyheading" value="1">
		<input type="text" class="form-control" id="competencyEditHeading" value="" name="competencyEditHeading" aria-describedby="emailHelp" placeholder="Enter Main Competency" required="true">		
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
<div class="modal fade" id="deleteModalHeading" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel"><b>Delete Main Competency</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
	  <form method="POST" action="" name="competencyAddfms" id="competencyAddfms">
      <div class="modal-body">      
		<h5 class="modal-title" id="exampleModalLabel">Are you want to delete ?</h5>
		<input type="hidden" name="deletecid" id="deletecid" value="" />
		<input type="hidden" name="hiddencompetencyheading" id="hiddencompetencyheading" value="1">
      </div>
      <div class="modal-footer">
	     <button type="submit" class="btn btn-primary">Delete</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>        
      </div>
	  </form>
    </div>
  </div>
</div>
<?php $PAGE->requires->js('/local/competency/js/competency.js'); ?>
<?php echo $OUTPUT->footer(); ?>