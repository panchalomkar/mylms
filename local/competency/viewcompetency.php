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
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/competency/competency_pro.css?v=2'));

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
$searchListShow .= '<div class="accordion custom-accordion" id="accordionEx78">';

foreach ($searchcompetencyheading as $key => $seachVal) {

    $show = ($i == 0) ? 'show' : '';
    $expanded = ($i == 0) ? 'true' : 'false';

    // Get data
    $searchCompResult = getViewCompetencyData($seachVal->id);

    $searchListShow .= '
    <div class="card custom-card">

        <!-- HEADER -->
        <div class="card-header" id="heading'.$i.'">
            <a class="accordion-link"
               data-toggle="collapse"
               href="#collapse'.$i.'"
               aria-expanded="'.$expanded.'"
               aria-controls="collapse'.$i.'">

                <div class="header-left">
                    <i class="fa fa-layer-group icon-main"></i>
                    <span>'.$seachVal->title.'</span>
                </div>

                <i class="fa fa-chevron-down toggle-icon"></i>
            </a>
        </div>

        <!-- BODY -->
        <div id="collapse'.$i.'" class="collapse '.$show.'" data-parent="#accordionEx78">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table modern-table">

                        <thead>
                            <tr>
                                <th>Sub Competency</th>
                                <th>Sub Sub Competency</th>
                                <th>Role</th>
                                <th class="text-center">Courses</th>
                            </tr>
                        </thead>

                        <tbody>';
    
    foreach ($searchCompResult as $competency_categorys_val) {

        $svid = !empty($competency_categorys_val->id) ? $competency_categorys_val->id : 0;
        $svcctid = !empty($competency_categorys_val->cctid) ? $competency_categorys_val->cctid : 0;

        $searchcomptencyname = (empty($competency_categorys_val->id) && empty($competency_categorys_val->cctid))
            ? '-' 
            : $competency_categorys_val->comptencyname;

        $searchListShow .= '
            <tr>
                <td class="fw-bold">'.$competency_categorys_val->name.'</td>
                <td>'.$searchcomptencyname.'</td>
                <td>
                    <span class="role-badge">
                        '.$competency_categorys_val->shortname.'
                    </span>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-view"
                        data-toggle="modal"
                        data-target="#tabView"
                        onclick="getcourses('.$svid.','.$svcctid.')">
                        <i class="fa fa-eye"></i> View
                    </button>
                </td>
            </tr>';
    }

    $searchListShow .= '
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>';

    $i++;
}

$searchListShow .= '</div>';
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