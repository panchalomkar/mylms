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
require('../../../config.php');
require_once "{$CFG->dirroot}/local/mt_dashboard/cohort_sync/CohortSyncForm.php";
require_once "{$CFG->dirroot}/local/mt_dashboard/cohort/lib.php";
require_once "{$CFG->dirroot}/local/mt_dashboard/lib.php";
require_once "{$CFG->dirroot}/enrol/cohort/lib.php";
//$context = context_course::instance($course->id, MUST_EXIST);
$return = new moodle_url('/local/mt_dashboard/index.php');
$id         = optional_param('id',0, PARAM_INT); // course id
$action     = optional_param('action', '', PARAM_ALPHANUMEXT);
$instanceid = optional_param('instance', 0, PARAM_INT);
$confirm    = optional_param('confirm', 0, PARAM_BOOL);
$confirm2   = optional_param('confirm2', 0, PARAM_BOOL);
$type = 'cohort';
$systemcontext = context_system::instance();
require_capability( 'local/mt_dashboard:viewcohortsync', $systemcontext );
$plugin = enrol_get_plugin($type);
if (!$plugin) {
    throw new moodle_exception('invaliddata', 'error');
}
$PAGE->set_url('/local/mt_dashboard/cohort_sync/index.php');
// No instance yet, we have to add new instance.
$plugin = enrol_get_plugin('cohort');
if ($action and confirm_sesskey()) {
    if ($action === 'delete' && has_capability('local/mt_dashboard:deletecohortsync', $systemcontext)) {
        $instances = enrol_get_instances($id, false);
        $plugins = enrol_get_plugins(false);
        $instance = $instances[$instanceid];
        $plugin = $plugins[$instance->enrol];
        if ($plugin->can_delete_instance($instance)) {
            if ($confirm) {
                if (enrol_accessing_via_instance($instance)) {
                    if (!$confirm2) {
                        $yesurl = new moodle_url('/local/mt_dashboard/cohort_sync/index.php',
                                                 array('id' => $id,
                                                       'action' => 'delete',
                                                       'instance' => $instance->id,
                                                       'confirm' => 1,
                                                       'confirm2' => 1,
                                                       'sesskey' => sesskey()));
                        $displayname = $plugin->get_instance_name($instance);
                        $message = markdown_to_html(get_string('deleteinstanceconfirmself',
                                                               'enrol',
                                                               array('name' => $displayname)));
                        echo $OUTPUT->header();
                        echo $OUTPUT->confirm($message, $yesurl, $PAGE->url);
                        echo $OUTPUT->footer();
                        die();
                    }
                }
                $plugin->delete_instance($instance);
                redirect($PAGE->url);
            }
            echo $OUTPUT->header(); 
            $yesurl = new moodle_url('/local/mt_dashboard/cohort_sync/index.php',
                                     array('id' => $id,
                                           'action' => 'delete',
                                           'instance' => $instance->id,
                                           'confirm' => 1,
                                           'sesskey' => sesskey()));
            $displayname = $plugin->get_instance_name($instance);
            $users = $DB->count_records('user_enrolments', array('enrolid' => $instance->id));
            if ($users) {
                $message = markdown_to_html(get_string('deleteinstanceconfirm', 'enrol',
                                                       array('name' => $displayname,
                                                             'users' => $users)));
            } else {
                $message = markdown_to_html(get_string('deleteinstancenousersconfirm', 'enrol',
                                                       array('name' => $displayname)));
            }
            echo $OUTPUT->confirm($message, $yesurl, $PAGE->url);
            echo $OUTPUT->footer();
            die();
        }
    } 
}

if ($instanceid) {
    $instance = $DB->get_record('enrol', array('courseid' => $id, 'enrol' => $type, 'id' => $instanceid), '*', MUST_EXIST);

} else {
    require_capability('local/mt_dashboard:addcohortsyncenrol', $systemcontext);
    $instance           = (object)$plugin->get_instance_defaults();
    $instance->id       = null;
    $instance->courseid = $id;
    $instance->status   = ENROL_INSTANCE_ENABLED; // Do not use default for automatically created instances here.
}
$mform = new CohortSyncForm(null, array($instance, $plugin, $systemcontext, $type));
if ($mform->is_cancelled()) {
    redirect($return);
} else if ($data = $mform->get_data()) {
    $course = $DB->get_record('course', array('id' => $data->courseid), '*', MUST_EXIST);
    $data->roleid = 5;
    $fields = (array) $data;
    if(has_capability('local/mt_dashboard:addcohortsyncenrol', $systemcontext)) {
        $plugin->add_instance($course, $fields);
        redirect($PAGE->url);
    }
}

$PAGE->set_title(get_string('pluginname', 'enrol_' . $type));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_' . $type));
$mform->display();
$strdelete  = get_string('delete');

$table = new html_table();
$table->head  = array(get_string('course'), get_string('cohort','local_mt_dashboard'), get_string('users'), get_string('edit'));
$table->align = array('left', 'center', 'center', 'center');
$table->width = '100%';
$table->data  = array();

$cohort_enrol_sql = $DB->get_records_sql("SELECT e.id,e.customint1,e.courseid,c.fullname,ch.name FROM {enrol} e  join {company_cohorts} cc on e.customint1 = cc.cohortid left join {course} c on e.courseid = c.id left join {cohort} ch on e.customint1 = ch.id where e.enrol = 'cohort'");

foreach($cohort_enrol_sql as $enrol){
    $courseid = $enrol->courseid;
    $instanceid = $enrol->id;
    $instances = enrol_get_instances($courseid, false);
    $instance = $instances[$instanceid];
    $displayname = $enrol->fullname;
    $cohortname = $enrol->name;
    $users = $DB->count_records("cohort_members", ['cohortid' => $enrol->customint1]);
    $url = new moodle_url('/local/mt_dashboard/cohort_sync/index.php', array('sesskey'=>sesskey(), 'id'=>$courseid));
    if ($plugin->can_delete_instance($instance)) {
        $aurl = new moodle_url($url, array('action'=>'delete', 'instance'=>$instance->id));
        $edit = $OUTPUT->action_icon($aurl, new pix_icon('t/delete', $strdelete, 'core', array('class' => 'iconsmall')));
    }
    $table->data[] = array($displayname,$cohortname, $users,$edit);
}
echo html_writer::table($table);
echo $OUTPUT->footer();