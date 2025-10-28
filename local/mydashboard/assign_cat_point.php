<?php
// This file is part of Moodle - http://moodle.org/
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
 * This block allows the user to give the course a rating, which
 * is displayed in a custom table (<prefix>_block_rate_course).
 *
 * @package    recent_access
 * @subpackage reportes
 * @copyright  2021 elearningstack.com 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('asg_cat_form.php');
require_once('lib.php');
require_login();
global $OUTPUT,$PAGE,$DB,$CFG;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/mydashboard/assign_cat_point.php');
$PAGE->set_heading(get_string('assigncategorypoint', 'local_mydashboard'));
$PAGE->set_title(get_string('assigncategorypoint', 'local_mydashboard'));

echo $OUTPUT->header();
if (is_siteadmin()) {
    $mform = new asg_cat_form();
if ($mform->is_cancelled()) {
  
} else if ($fromform = $mform->get_data()) {
        $categoryid= $fromform->categoryid;
        $assignpoint= $fromform->assignpoint;
        $dataadd = add_category_point($categoryid, $assignpoint);
}
$mform->display();
}else{
    redirect($CFG->wwwroot, 'Sorry,Only admin user can view this page', null, \core\output\notification::NOTIFY_SUCCESS);
}
echo $OUTPUT->footer();
