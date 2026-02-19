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
* @package local_mydashboard
* @category local
* @copyright  ELS <admin@elearningstack.com>
* @author eLearningstack
*/
require_once('../../config.php');
require_login();
global $PAGE, $SESSION, $DB;

if (!empty($SESSION->currenteditingcompany)) {
    $selectedcompany = $SESSION->currenteditingcompany;
} else if (!empty($USER->profile->company)) {
    $usercompany = company::by_userid($USER->id);
    $selectedcompany = $usercompany->id;
} else {
    $selectedcompany = 0;
}
//require($CFG->dirroot.'/local/mydashboard/classes/forms/filterform.php');
$id = optional_param('id', 0, PARAM_TEXT);
$context = context_system::instance();
//$PAGE->requires->js('/local/mydashboard/js/selectvalues.js');
$PAGE->set_context($context);
 $PAGE->set_url('/local/mydashboard/smeleader_fdk_report.php');
$PAGE->set_heading(get_string('smeleader', 'local_mydashboard'));
$PAGE->set_title(get_string('smeleader', 'local_mydashboard'));
$PAGE->set_pagelayout('standard');
$reporturl = 'Home';
$PAGE->navbar->add($reporturl, new moodle_url('/my'));
$PAGE->navbar->add(get_string('smeleaderreport', 'local_mydashboard'), '/local/mydashboard/smeleader_report.php');
$PAGE->navbar->add(get_string('smeleader', 'local_mydashboard'));
 $categoryid = optional_param('categoryid', 0, PARAM_INT);
// $courseid = optional_param('courseid', 0, PARAM_INT);

echo $OUTPUT->header();

//if (is_siteadmin()) {
  if ($selectedcompany) {
    $categorydata = $DB->get_records_sql("SELECT * FROM {company} c 
    INNER JOIN {course_categories} cc ON c.category = cc.id WHERE c.id = $selectedcompany");
    $categories = array();
    foreach ($categorydata as $key => $value) {
        $categories[$key] = $value->name;
    }
  }else{
    $categories = \core_course_category::make_categories_list();
  } 
  ?>

    <div class="row">
    <div class="col-lg-3">
    <div class="form-group">
    <select class="form-control" id="ControlSelect1">
      <option>Select Category</option>
      <?php 
      if ($categoryid) {
        $getcat = $DB->get_record('course_categories', array('id' => $categoryid));
        echo '<option  value="'.$getcat->id.'" selected>'.$getcat->name.'</option>';
      }
      foreach ($categories as $key => $value) {
        echo '<option  value="'.$key.'">'.$value.'</option>';
      }
      ?>
    </select>
  </div>
    </div>
    <div class="col-lg-9">
   
    </div>
    </div>

   <?php
         
    // $mform = new filter_form(null, array(
    //     'categoryid' => $categoryid,
    //     'courseid' => $courseid
    // ), 'post', '', ['class' => 'timer_report','id' => 'elstimerreport']);
//if ($mform->is_cancelled()) {
  
// } else if ($fromform = $mform->get_data()) {
//     $categoryid = $fromform->categoryid;
//     $mform->display();
//     $renderer = $PAGE->get_renderer('local_mydashboard');
//     echo $renderer->access_feedback_reports($categoryid);
// } else {
    //$categoryid = optional_param('categoryid', 0, PARAM_INT);
    $feedbackreport = $CFG->wwwroot.'/local/mydashboard/smeleader_fdk_report.php';
    // $mform->display();
    $renderer = $PAGE->get_renderer('local_mydashboard');
    echo $renderer->access_feedback_reports($categoryid);
////}
// }else{
//     redirect($CFG->wwwroot, 'Sorry,Only admin user can view this page', null, \core\output\notification::NOTIFY_SUCCESS);
// }

echo $OUTPUT->footer();
?>
<script>
     $(document).ready(function() {
    $('#ControlSelect1').on('change', function() {
      if (this.value == 0) {
        window.location.href = "<?php echo $feedbackreport?>"; 
      }else{
        window.location.href = "<?php echo $feedbackreport?>?categoryid=" + this.value + "";
      }
    });
 });
</script>