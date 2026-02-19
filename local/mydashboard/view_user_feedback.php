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
global $DB, $PAGE;
$id = optional_param('id', 0, PARAM_TEXT);
$context = context_system::instance();
//$PAGE->requires->js('/local/mydashboard/js/selectvalues.js');
$PAGE->set_context($context);
$PAGE->set_url('/local/mydashboard/smeleader_report.php');
$PAGE->set_heading(get_string('viewfeedback', 'local_mydashboard'));
$PAGE->set_title(get_string('viewfeedback', 'local_mydashboard'));
$PAGE->set_pagelayout('standard');
$reporturl = 'Home';
$PAGE->navbar->add($reporturl, new moodle_url('/my'));
$PAGE->navbar->add(get_string('smeleaderreport', 'local_mydashboard'), '/local/mydashboard/smeleader_report.php');
$PAGE->navbar->add(get_string('smeleader', 'local_mydashboard'), '/local/mydashboard/smeleader_fdk_report.php');
$PAGE->navbar->add(get_string('viewfeedback', 'local_mydashboard'));
echo $OUTPUT->header();
$table = 'sme_leader_feedback';
$conditions = ['feedback_userid' => $id];
$sort = 'id DESC';
$getfed = $DB->get_records(
  $table,
  $conditions,
  $sort,
  $fields = '*',
  $limitfrom = 0,
  $limitnum = 0
);
$userdata = $DB->get_record('user', array('id' => $id));
?>
<style>.star-rating {
  line-height:32px;
  font-size:1.25em;
}
.checked {
  color: orange;
}
.star-rating .fa-star{color: yellow;}</style>
<div class="container py-3">
  <!-- Card Start -->
  <div class="card">
    <div class="row ">

      <div class="col-md-7 px-3">
        <div class="card-block px-6 feedbackblock">
          <h4 class="modal-title">Feedback by <?php echo $userdata->firstname .' '. $userdata->lastname ?></h4>
           
          <?php 
            foreach ($getfed as $kevalue) {
              echo '<p class="card-text"><i class="fa fa-comments-o mr-2"></i> '.$kevalue->feedback.' ('. date('d-M-Y', $kevalue->timecreated) .') </p>';
              for ($i=1; $i <= $kevalue->rate; $i++) { 
                echo '<div class="fedbacksec">
              <span class="fa fa-star checked"></span>
              </div>';
                }
                
           }
            ?>
        </div>
      </div>
      <!-- Carousel start -->
     
  </div>
  <!-- End of card -->

</div>
  </div>
 
 <br>
<br>
 
<?php
echo $OUTPUT->footer();
?>
<script>
   $(document).ready(function(){
   var $star_rating = $('.star-rating .fa');

var SetRatingStar = function() {
  return $star_rating.each(function() {
    if (parseInt($star_rating.siblings('input.rating-value').val()) >= parseInt($(this).data('rating'))) {
      return $(this).removeClass('fa-star-o').addClass('fa-star');
    } else {
      return $(this).removeClass('fa-star').addClass('fa-star-o');
    }
  });
};



SetRatingStar();
});
</script>