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
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    core_cohort
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../../config.php');
require_once($CFG->dirroot.'/local/mt_dashboard/cohort/locallib.php');
$id = required_param('id', PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
require_login();
$cohort = $DB->get_record('cohort', array('id'=>$id), '*', MUST_EXIST);
$context = context::instance_by_id($cohort->contextid, MUST_EXIST);
// require_capability('moodle/cohort:assign', $context);
$PAGE->set_context($context);
$PAGE->set_url('/local/mt_dashboard/cohort/assign.php', array('id'=>$id));
$PAGE->set_pagelayout('admin');
if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url('/local/mt_dashboard/cohort/index.php', array('contextid' => $cohort->contextid));
}
if (!empty($cohort->component)) {
    // We can not manually edit cohorts that were created by external systems, sorry.
    redirect($returnurl);
}

if (optional_param('cancel', false, PARAM_BOOL)) {
    redirect($returnurl);
}

if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array('contextid'=>$cohort->contextid)));
} else {
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array()));
}
$PAGE->navbar->add(get_string('assign', 'cohort'));

$PAGE->set_title(get_string('assigncohorts', 'cohort'));
$PAGE->set_heading($COURSE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('assignto', 'cohort', format_string($cohort->name)));

echo $OUTPUT->notification(get_string('removeuserwarning', 'core_cohort'));

// Get the user_selector we will need.
$potentialuserselector = new mt_cohort_candidate_selector('addselect', array('cohortid'=>$cohort->id, 'accesscontext'=>$context));
$existinguserselector = new mt_cohort_existing_selector('removeselect', array('cohortid'=>$cohort->id, 'accesscontext'=>$context));

// Process incoming user assignments to the cohort
if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoassign = $potentialuserselector->get_selected_users();
    if (!empty($userstoassign)) {
        foreach ($userstoassign as $adduser) {
            mt_dashboard_cohort_add_member($cohort->id, $adduser->id);
        }
        $potentialuserselector->invalidate_selected_users();
        $existinguserselector->invalidate_selected_users();
    }
}
// Process removing user assignments to the cohort
if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoremove = $existinguserselector->get_selected_users();
    if (!empty($userstoremove)) {
        foreach ($userstoremove as $removeuser) {
            mt_dashboard_cohort_remove_member($cohort->id, $removeuser->id);
        }
        $potentialuserselector->invalidate_selected_users();
        $existinguserselector->invalidate_selected_users();
    }
}

$data['page_url'] = $PAGE->url;
$data['sesskey'] = sesskey();
$data['returnurl'] = $returnurl;
$data['existinguserselector'] = $existinguserselector->display(true);
$data['potentialuserselector'] = $potentialuserselector->display(true);

echo $OUTPUT->render_from_template('local_mt_dashboard/assign', $data);
echo $OUTPUT->footer();
