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
* @package local_custom_notification
* @category local
* @copyright  GreenLMS <admin@greenlms.com>
* @author GreenLMS
*/

require_once('../../config.php');
require_login();
global $DB, $PAGE;
require_once($CFG->dirroot.'/local/custom_notification/classes/forms/notification_form.php');
require_once($CFG->dirroot."/local/custom_notification/classes/Insertdataform.php");
$id = optional_param('id', 0, PARAM_TEXT);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/custom_notification/notification.php');
$PAGE->set_heading(get_string('headername', 'local_custom_notification'));
$PAGE->set_title(get_string('headername', 'local_custom_notification'));
// $PAGE->set_pagelayout('standrad');
$PAGE->navbar->add(get_string('pluginname', 'local_custom_notification'));
// $PAGE->requires->js('/local/custom_notification/js/selectvalues.js');
echo $OUTPUT->header();
$roles = $DB->get_record_sql("SELECT * FROM mdl_role_assignments WHERE userid = $USER->id");
$getmanager = $DB->get_record('company_users', array('userid' => $USER->id));
if(is_siteadmin() || $roles->roleid == 3 || $getmanager->managertype == 1){
    $mform = new notification_form();
    if ($mform->is_cancelled()) {
      
    } else if ($fromform = $mform->get_data()) {
            $formdatainsert = new \Insert_formdata();
            $formdatainsert->updatedatafunc($fromform);
            \core\notification::add(get_string('savechangesucess', 'local_custom_notification'), \core\output\notification::NOTIFY_INFO);
    }
    $mform->display();
}else{
    echo "Not Access";
}

echo $OUTPUT->footer();
?>