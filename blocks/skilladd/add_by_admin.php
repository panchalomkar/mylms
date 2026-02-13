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
* @package block_skilladd
* @category local
* @copyright  ELS <admin@elearningstack.com>
* @author eLearningstack
*/
require_once('../../config.php');
require_login();
global $DB, $PAGE;
require($CFG->dirroot.'/blocks/skilladd/classes/forms/filterform.php');
$id = optional_param('id', 0, PARAM_TEXT);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/blocks/skilladd/add_by_admin.php');
$PAGE->set_heading(get_string('adminaddskillheader', 'block_skilladd'));
$PAGE->set_title(get_string('adminaddskillheader', 'block_skilladd'));
$PAGE->navbar->add(get_string('pluginname', 'block_skilladd'));
echo $OUTPUT->header();

if (is_siteadmin()) {
    $mform = new filter_form();
if ($mform->is_cancelled()) {
  
} else if ($fromform = $mform->get_data()) {
    $renderer = $PAGE->get_renderer('block_skilladd');
    echo $renderer->insertdata($fromform);
} 
$mform->display();
}else{
    redirect($CFG->wwwroot, 'Sorry,Only admin user can view this page', null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->footer();
?>