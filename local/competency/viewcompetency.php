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
$activepage = 'viewcompetency';
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_title(get_string('viewcompetency', 'local_competency'));
$PAGE->set_url($CFG->wwwroot.'/local/competency/viewcompetency.php');
$PAGE->set_heading(get_string('viewcompetency', 'local_competency'));
$PAGE->navbar->add(get_string('viewcompetency', 'local_competency'));
echo $OUTPUT->header();
//header added
require_once($CFG->dirroot.'/local/competency/header.php');
require_once($CFG->dirroot.'/local/competency/tabs.php');
if (!has_capability('local/competency:viewcompetency', $context)) {
    redirect($CFG->wwwroot. '/my/', \core\notification::error('No access...'));
    exit();
}
$listCompetencyCount = getListCompetencyTitleCount();

$pagesArr = getPaginationDisplay($listCompetencyCount, $selectPageNo, $limit);
$pages = $pagesArr[0];
$start = $pagesArr[1];

$searchcompetencyheading = getListCompetencyTitle($start, $limit);

//$searchRolesSql = "SELECT r.id, r.shortname FROM {role} as r INNER JOIN {user_info_field} as uif  ON r.shortname = uif.shortname";

$show = 'show';
$i=0; $buselct =''; $viewselct=''; 
$viewcontentbody=''; $searchListShow='';
$searchListShow .='<div class="accordion md-accordion accordion-blocks" id="accordionEx78" role="tablist" aria-multiselectable="false"><div class="card">';
foreach ($searchcompetencyheading as $key => $seachVal) {
       if($i > 0){
          $show = '';
        }
     //To show data view competency
	$searchCompResult = getViewCompetencyData($seachVal->id);
	$searchListShow.='<div class="card-header" role="tab" id="heading'. $i.'">
      <!-- Heading -->
      <a data-toggle="collapse" data-parent="#accordionEx78" href="#collapse'.$i.'" aria-expanded="false" aria-controls="collapse'.$i.'">
        <h5 class="mt-1 mb-0">
          <span>'.$seachVal->title.'</span>
        </h5>
      </a>
    </div>
	<!-- Card body -->
    <div id="collapse'.$i.'" class="collapse '.$show.'" role="tabpanel" aria-labelledby="heading'.$i.'" data-parent="#accordionEx78">
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
              foreach($searchCompResult as $competency_categorys_val){
                  
                      if(empty($competency_categorys_val->id)){ 
						$svid = 0;
					  }else{
						$svid = $competency_categorys_val->id;  
					  }
					  if(empty($competency_categorys_val->cctid)){ 
						$svcctid = 0;
					  }else{
						$svcctid = $competency_categorys_val->cctid;  
					  }
					  if(empty($competency_categorys_val->id) && empty($competency_categorys_val->cctid)){
						    $searchcomptencyname = '-';
					  }else{
						   $searchcomptencyname = $competency_categorys_val->comptencyname; 
					  } 
             $searchListShow.= '<tr>
                <td>'.$competency_categorys_val->name.'</td>
                <td>'.$searchcomptencyname.'</td>
                <td>'.$competency_categorys_val->shortname.'</td>
                <td>
                  <a href="#" class="btn btn-primary" data-target="#tabView" data-toggle="modal" onclick="getcourses('.$svid.','.$svcctid.')"> View Courses </a>
                </td>
              </tr>';
              }
            $searchListShow.='</tbody>
            <!--Table body-->
          </table>
          <!--Table-->
        </div>
        <!-- Table responsive wrapper -->
      </div>
    </div>';
	$i++;
}
	//Sub sub Competency pagination
	if($pages > 1){
		$pagination = custompagination3($selectPageNo,$pages,'tabviewcompetency');
	}

	//view compentency search button
	$search = getSearchFieldsCompetency();
	$buselct = $search[0];
	$viewselct = $search[1];
	
	$viewcontentbody ='<div class="row" style="text-align:center;margin-bottom:10px;margin-top:10px;">
	<div class="col-md-4">
		'.$buselct.'
	</div>
	<div class="col-md-4">
		'.$viewselct.'
	</div>
	<div class="col-md-3"><button type="button" class="btn btn-primary" onclick="filtclickfun()">Search</button></div>
	</div><p id="errormessage" style="color:red;text-align:center;"></p><br>'.$searchListShow;
echo $viewcontentbody;
echo "<br/>";
echo $pagination;
?>
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
<?php $PAGE->requires->js('/local/competency/js/competency.js'); ?>
<?php echo $OUTPUT->footer(); ?>