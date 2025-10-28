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
 * classroom course format. Display the whole course as "classroom" made of modules.
 *
 * @package local_competency
 * @copyright 2020 Nilesh Pathade
 * @author Nilesh Pathade
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/competency/pagination.php');
require_once($CFG->dirroot . '/local/competency/lib.php');
$activepage = 'usersrating';
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_title(get_string('userselfrating', 'local_competency'));
$PAGE->set_url($CFG->wwwroot . '/local/competency/userselfrating.php');
$PAGE->set_heading(get_string('userselfrating', 'local_competency'));
$PAGE->navbar->add(get_string('userselfrating', 'local_competency'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/competency/customtablelayout.css?v=1'));
echo $OUTPUT->header();
//header added
require_once($CFG->dirroot . '/local/competency/header.php');
require_once($CFG->dirroot . '/local/competency/tabs.php');
//submit manager ration
if (!has_capability('local/competency:userselfrating', $context)) {
    redirect($CFG->wwwroot . '/my/', \core\notification::error('No access...'));
    exit();
}
global $USER;

if (optional_param('submituserselfrating', '', PARAM_TEXT) === 'addrating') {

    if (!has_capability('local/competency:userselfrating', $context)) {
        redirect($CFG->wwwroot . '/my/', \core\notification::error('No access...'));
        exit();
    }
    $mastercompetencyid = optional_param_array('mastercompetencyid', array(), PARAM_TEXT);
    $mastercompetencyid1 = optional_param_array('mastercompetencyid1', array(), PARAM_TEXT);
    $competencyid = optional_param_array('competencyid', array(), PARAM_TEXT);
    $student_ratings = optional_param_array('student_rating', array(), PARAM_TEXT);
    $subcompetencyid = optional_param_array('subcompetencyid', array(), PARAM_TEXT);
    $student_ratings1 = optional_param_array('student_rating1', array(), PARAM_TEXT);
    $tearmsid = optional_param('tearmsid', null, PARAM_INT);
    // $managerfinal_rating1=  optional_param_array('managerfinal_rating1',  array(), PARAM_TEXT);
    $i = 0;
    $j = 0;

    $studentData = getexistingStudentRanking($USER->id, $tearmsid);

    $competencyarr = array();
    if (empty($studentData)) {
        //add sub competency rating
        foreach ($student_ratings as $key => $student_rating) {
            $sudent_ratingObj = new stdClass();
            $sudent_ratingObj->master_competencyid = $mastercompetencyid[$i];
            $sudent_ratingObj->competencyid = $competencyid[$i];
            $sudent_ratingObj->rating = $student_rating;
            $sudent_ratingObj->tearms = $tearmsid;
            $insertedid = $DB->insert_record('sudent_rating', $sudent_ratingObj);
            $i++;
        }
        // add sub sub competency rating
        foreach ($student_ratings1 as $key => $value) {
            $competencyidids = $DB->get_record('competency_users', array('id' => $mastercompetencyid1[$j]));
            $sudent_ratingObj = new stdClass();
            $sudent_ratingObj->master_competencyid = $mastercompetencyid1[$j];
            $sudent_ratingObj->competencyid = $competencyidids->competencyid;
            $sudent_ratingObj->subcomptencyid = $subcompetencyid[$j];
            $sudent_ratingObj->rating = $value;
            $sudent_ratingObj->tearms = $tearmsid;
            $insertedid = $DB->insert_record('sudent_rating', $sudent_ratingObj);
            $j++;
        }
        $message = "You have successfully added student rating";

        // Notification to all Managers and Heads.
        $data = getUsersIds($USER->id);
        $uqArra = array();
        foreach ($data as $key => $userid) {
            $userids = explode('-', trim($userid->data));
            $uqArra[] = $userids[0];
        }
        $usertomail = array_unique($uqArra);
        foreach ($usertomail as $key => $touser) {
            $a = new stdClass();
            $user = $DB->get_record("user", array('id' => $touser));
            $a->firstname = fullname($user);
            $subject = get_string('userselfrating_subject', 'local_competency', $a);
            $body = get_string('userselfrating_body', 'local_competency', $a);
            $messageText = '';
            $return = email_to_user($user, $USER, $subject, $messageText, $body, ", ", true);
        }

    } else {
        //update sub competency rating
        foreach ($student_ratings as $key => $student_rating) {
            $checkResult = getStudentRatingExists($mastercompetencyid[$i], $competencyid[$i], 0, $tearmsid);
            if (count($checkResult) > 0) {
                $landdrating = getLandDRatingValueViaId($mastercompetencyid[$i], $tearmsid);
                if (count($landdrating) == 0) {
                    $sudent_ratingObj = new stdClass();
                    $sudent_ratingObj->id = $studentData[$mastercompetencyid[$i]]->srid;
                    $sudent_ratingObj->master_competencyid = $mastercompetencyid[$i];
                    $sudent_ratingObj->competencyid = $competencyid[$i];
                    $sudent_ratingObj->rating = $student_rating;
                    $sudent_ratingObj->tearms = $tearmsid;
                    $insertedid = $DB->update_record('sudent_rating', $sudent_ratingObj);
                    // $i++;
                }
            } else {
                //add sub competency rating
                $sudent_InsertratingObj = new stdClass();
                $sudent_InsertratingObj->master_competencyid = $mastercompetencyid[$i];
                $sudent_InsertratingObj->competencyid = $competencyid[$i];
                $sudent_InsertratingObj->rating = $student_rating;
                $sudent_InsertratingObj->tearms = $tearmsid;
                $insertedid = $DB->insert_record('sudent_rating', $sudent_InsertratingObj);
                // $i++;                
            }
            $i++;
        }

        // update sub sub competency rating

        foreach ($student_ratings1 as $key => $value) {
            $competencyidids = $DB->get_record('competency_users', array('id' => $mastercompetencyid1[$j]));
            $checkResult1 = getStudentRatingExists($mastercompetencyid1[$j], $competencyidids->competencyid, $subcompetencyid[$j], $tearmsid);
            if (count($checkResult1) > 0) {
                $landdrating1 = getLandDRatingValueViaId($mastercompetencyid1[$j], $tearmsid);
                if (count($landdrating1) == 0) {
                    $sudent_ratingObj = new stdClass();
                    $sudent_ratingObj->id = $studentData[$mastercompetencyid1[$j]]->srid;
                    $sudent_ratingObj->master_competencyid = $mastercompetencyid1[$j];
                    $sudent_ratingObj->competencyid = $competencyidids->competencyid;
                    $sudent_ratingObj->subcomptencyid = $subcompetencyid[$j];
                    $sudent_ratingObj->rating = $value;
                    $sudent_ratingObj->tearms = $tearmsid;
                    $insertedid = $DB->update_record('sudent_rating', $sudent_ratingObj);
                    //$j++;
                }
            } else {

                // add sub sub competency rating                    
                $competencyidids = $DB->get_record('competency_users', array('id' => $mastercompetencyid1[$j]));
                $sudent_InsertratingObj1 = new stdClass();
                $sudent_InsertratingObj1->master_competencyid = $mastercompetencyid1[$j];
                $sudent_InsertratingObj1->competencyid = $competencyidids->competencyid;
                $sudent_InsertratingObj1->subcomptencyid = $subcompetencyid[$j];
                $sudent_InsertratingObj1->rating = $value;
                $sudent_InsertratingObj1->tearms = $tearmsid;
                $insertedid = $DB->insert_record('sudent_rating', $sudent_InsertratingObj1);
                // $j++;
                $message = "You have successfully added student rating";
            }
            $j++;
        }


        // Notification to all Managers and Heads.
        $data = getUsersIds($USER->id);
        $uqArra = array();
        foreach ($data as $key => $userid) {
            $userids = explode('-', trim($userid->data));
            $uqArra[] = $userids[0];
        }
        $usertomail = array_unique($uqArra);
        foreach ($usertomail as $key => $touser) {
            $a = new stdClass();
            $user = $DB->get_record("user", array('id' => $touser));
            $a->firstname = fullname($user);
            $subject = get_string('userselfrating_subject', 'local_competency', $a);
            $body = get_string('userselfrating_body', 'local_competency', $a);
            $messageText = '';
            $return = email_to_user($user, $USER, $subject, $messageText, $body, ", ", true);
        }
    }


}

$checkManagerRating = getexistingManagerRanking($USER->id, 1);
//print_object($checkManagerRating); exit();
if (!empty($message)) { ?>
    <br />
    <div class="alert alert-success successmessgae">
        <?php echo $message; ?>
    </div>
<?php }
$viewcontent = '';
$viewselct = '';
$buselct = '';
$viewuser = '';

//To display 
$studentData = getexistingStudentRanking($USER->id, 1);
?>
<div class="view1">
    <div id="table-scroll" class="table-scroll">
        <ul>
            <form name="userselfrating" id="userselfrating" method="POST">
                <?php
                $tearms = '';
                $tearms .= '<select name="tearmsid" id="tearmsid" class="form-control" style="width:450px" onchange="getcompetencytable(this.value)">
                <option value="">Select Field</option>
                                    <option value="1">First Half</option>
                                    <option value="2">Second Half</option>';
                $tearms .= '</select>';

                $viewcontent = '<div class="row" style="text-align:center;margin-bottom:10px;margin-top:10px;">
                          <div class="col-md-4" >
                             ' . $tearms . '
                          </div>
                        <p id="errormessage" style="color:red;text-align:center;"></p><br/></div>';
                echo $viewcontent;

                ?>
                <div class="table-wrap wrapper1" style="width: 75%">

                    <table class="main-table table competencytable">
                        <div id="slectfieldd" style="color: #888; font-style: italic; margin-top: 20px;">Please select a
                            field above to
                            load
                            data.</div>
                        <?php
                        // $userid = $USER->id;
                        
                        // $firstQuery = "SELECT DISTINCT cu.ctid, ct.title
                        // FROM {competency_users} as cu 
                        // LEFT JOIN {competency_title} ct ON cu.ctid = ct.id 
                        // LEFT JOIN {competency_category} cc ON cu.competencyid = cc.id 
                        // LEFT JOIN {competencies} c ON c.id = cu.subcompetencyid 
                        // LEFT JOIN {user} u ON u.id=cu.userid 
                        // LEFT JOIN {manager_rating} mr ON cu.id=mr.master_competencyid and mr.tearms = 1
                        // where cu.userid = ? ";
                        
                        // $firstQueryResult = $DB->get_records_sql($firstQuery, array($userid));
                        // // print_object($firstQueryResult);exit();
                        // $i = 0;
                        // $rows = '';
                        // $temp = 0;
                        // $readonly = 'readonly="readonly"'; //$value= '';
                        // $readonly1 = 'readonly="readonly"';
                        // $value1 = '';
                        // $readonly = '';
                        // if (empty($checkManagerRating)) {
                        //     $readonly = 'disabled=disabled';
                        // }
                        // foreach ($firstQueryResult as $key => $firstValue) {
                        
                        //     $rows .= '<tr>';
                        
                        //     $rows .= '<th class="competency_title" style="width:200px"><span>' . $firstValue->title . '</span></th>';
                        //     $rows .= '<th class="userlist" >' . get_string('studentsrating', 'local_competency') . '</th>';
                        //     $rows .= '<th class="userlist" >' . get_string('finalrating', 'local_competency') . '</th>';
                        //     $rows .= '</tr>';
                        
                        //     $secondQuery = "SELECT DISTINCT cu.id as cuid, cc.name, c.comptencyname, cc.id, cu.subcompetencyid, cu.competencyid
                        // 				FROM {competency_users} as cu 
                        // 		        LEFT JOIN {competency_title} ct ON cu.ctid = ct.id 
                        // 		        LEFT JOIN {competency_category} cc ON cu.competencyid = cc.id 
                        // 		        LEFT JOIN {competencies} c ON c.id = cu.subcompetencyid 
                        // 		        LEFT JOIN {user} u ON u.id=cu.userid 
                        //                 LEFT JOIN {manager_rating} mr ON cu.id = mr.master_competencyid And mr.competencyid = cc.id and mr.tearms = 1
                        // 		        WHERE cu.userid = ?  and cu.ctid = ? order by cu.competencyid, cu.subcompetencyid";
                        
                        //     $secondQueryResult = $DB->get_records_sql($secondQuery, array($userid, $firstValue->ctid));
                        

                        //     foreach ($secondQueryResult as $key => $secondValue) {	// sub competency name as second Value	        			    		
                        //         $value = '';
                        //         if (!empty($studentData))
                        //             if (isset($studentData[$secondValue->cuid]->rating))
                        //                 $value = 'value="' . $studentData[$secondValue->cuid]->rating . '"';
                        

                        //         $landdratingvalue = '';
                        //         $landdrating = getLandDRatingValueViaId($secondValue->cuid, 1);
                        //         if (count($landdrating)) {
                        //             $landdratingvalue = $landdrating[1]['rating'];
                        //             // $readonly = 'disabled=disabled';
                        //         }
                        
                        //         //echo $readonly; exit();
                        //         if ($secondValue->subcompetencyid == 0) {
                        //             $rows .= '<tr>';
                        

                        //             $rows .= '<td class="sticky-col first-col subcompcolor"> ' . $secondValue->name . '</td>';
                        
                        //             $rows .= '<td class="usersrow" ><input type="hidden" name="mastercompetencyid[]" id="mastercompetencyid" value="' . $secondValue->cuid . '" /> <input type="hidden" name="competencyid[]" id="competencyid" value="' . $secondValue->competencyid . '" /> <input type="number" min="0" ' . $readonly . ' max="10" name="student_rating[]"  id="studentrating" ' . $value . ' required="true"   /></td>';
                        
                        //             $rows .= '<td class="usersrow" ><span> ' . $landdratingvalue . ' </span></td>';
                        
                        //             $rows .= '</tr>';
                        //         }
                        
                        //         $thirdQuery = "SELECT  DISTINCT c.id , c.comptencyname,cu.subcompetencyid, cu.id  as cuid
                        //     			   FROM {competency_users} as cu 
                        //     			   LEFT JOIN {competency_title} ct ON cu.ctid = ct.id 
                        //     			   LEFT JOIN {competency_category} cc ON cu.competencyid = cc.id 
                        //     			   LEFT JOIN {competencies} c ON c.id = cu.subcompetencyid
                        //     			   LEFT JOIN {user} u ON u.id=cu.userid 
                        //                    LEFT JOIN {manager_rating} mr ON cu.id=mr.master_competencyid And mr.competencyid = cc.id and mr.tearms = 1
                        //     			   where cu.userid = ?  and c.id = ? order by c.comptencyname";
                        //         $thirdQueryResult = $DB->get_records_sql($thirdQuery, array($userid, $secondValue->subcompetencyid));
                        //         foreach ($thirdQueryResult as $thirdValue) {				// Sub sub competency as thirdValue
                        //             if (!empty($studentData))
                        //                 if (isset($studentData[$secondValue->cuid]->rating))
                        //                     $value = 'value="' . $studentData[$thirdValue->cuid]->rating . '"';
                        //             $landdratingvalue1 = '';
                        //             $landdrating1 = getLandDRatingValueViaId($thirdValue->cuid, 1);
                        //             $landdratingvalue1 = $landdrating1[1]['rating'];
                        //             $rows .= '<tr>';
                        
                        //             $rows .= '<td style="padding-left: 40px !important;" class="sticky-col first-col">  ' . $thirdValue->comptencyname . '</td>';
                        //             $rows .= '<td class="usersrow" ><input type="hidden" name="mastercompetencyid1[]" id="mastercompetencyid" value="' . $thirdValue->cuid . '" /> <input type="hidden" name="subcompetencyid[]" id="subcompetencyid" value=' . $thirdValue->subcompetencyid . ' /> <input type="number"  ' . $readonly . ' min="0" max="10" name="student_rating1[]"  id="studentrating" ' . $value . '  required="true" /></td>';
                        
                        //             $rows .= '<td class="usersrow" ><span> ' . $landdratingvalue1 . ' </span></td>';
                        
                        //             $rows .= '</tr>';
                        
                        //         }
                        
                        //     }
                        //     $temp = 1;
                        // }
                        // if ($temp != 1) {
                        //     $rows .= "<h3 style='color:red;'> No records found ! </h3>";
                        // } else {
                        //     $rows .= '<tr style="border:0px;"><td colspan="4"><button type="submit" class="btn btn-primary" value="addrating" name="submituserselfrating">Submit</button></td></tr>';
                        // }
                        
                        // echo $rows;
                        
                        ?>
                    </table>
                </div>
            </form>
        </ul>
    </div>
</div>
<?php $PAGE->requires->js('/local/competency/js/competency.js'); ?>
<?php echo $OUTPUT->footer(); ?>
<script>
    function getcompetencytable(tearms) {

        $.ajax({
            type: "POST",
            url: "getcompetencytable.php",
            data: {
                tearms: tearms
            },
            success: function (response) {
                const labelElement = document.getElementById('slectfieldd');
                if (labelElement) {
                    labelElement.style.display = 'none';
                }
                console.log(response);
                $('#errormessage').hide();
                $(".competencytable").html(response);
            },
            error: function (e, msg) {

            }
        });
    }
</script>
<script type="text/javascript">
    $(document).ready(function () {
        $(".main-table").clone(true).appendTo('#table-scroll').addClass('clone');
    });
</script>