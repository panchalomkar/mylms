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
//require($CFG->dirroot.'/local/mydashboard/classes/forms/filterform.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/mydashboard/sme_request.php');
$PAGE->set_heading(get_string('landdreport', 'local_mydashboard'));
$PAGE->set_title(get_string('landdreport', 'local_mydashboard'));
$PAGE->set_pagelayout('standard');
$reporturl = 'Home';
$PAGE->navbar->add($reporturl, new moodle_url('/my'));
$PAGE->navbar->add(get_string('smeleaderreport', 'local_mydashboard'), '/local/mydashboard/smeleader_report.php');
$PAGE->navbar->add(get_string('landdreport', 'local_mydashboard'));
$categoryid = optional_param('categoryid', 0, PARAM_INT);
echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_mydashboard');
echo $renderer->get_approval_request();

echo $OUTPUT->footer();
?>
<script>
     $(function() {
              $('.status1id').on('click', function() {
                var smeuserid = $(this).attr("smeuserid");
                var fdid = $(this).attr("value");
                var purposeid = $(this).attr("purposeid");
                    $.ajax({
                       url: M.cfg.wwwroot + "/local/mydashboard/aproval.php",
                       type: 'post',
                       data: {smeuserid: smeuserid, fdid: fdid,purposeid:purposeid},
                       success: function (response) {
                        window.location.reload();
                       }
                });               
                 });

                 $('.status2id').on('click', function() {
                var smeuserid = $(this).attr("smeuserid");
                var fdid = $(this).attr("value");
                var purposeid = $(this).attr("purposeid");
                    $.ajax({
                       url: M.cfg.wwwroot + "/local/mydashboard/notaproval.php",
                       type: 'post',
                       data: {smeuserid: smeuserid, fdid: fdid,purposeid:purposeid},
                       success: function (response) {
                        window.location.reload();
                       }
                });               
                 });

      });
</script>