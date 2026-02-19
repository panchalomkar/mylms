<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
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
require_once($CFG->dirroot.'/local/mt_dashboard/cohort/lib.php');
require_once($CFG->libdir.'/adminlib.php');
$contextid = optional_param('contextid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$searchquery  = optional_param('search', '', PARAM_RAW);
$showall = false;
require_login();
if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}
$category = null;
if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);
}
$manager = has_capability('local/mt_dashboard:managecompanycohort', $context);
if (!$manager) {
    require_capability('local/mt_dashboard:companycohort_view', $context);
}
$strcohorts = get_string('cohorts', 'cohort');
if ($category) {
    $PAGE->set_pagetype('local-iomad-dashboard-index');
    $PAGE->set_context($context);
    $PAGE->set_url('/local/mt_dashboard/cohort/index.php', array('contextid'=>$context->id));
    $PAGE->set_title($strcohorts);
    $PAGE->set_heading($COURSE->fullname);
} else {
    $PAGE->set_pagetype('local-iomad-dashboard-index');
}

echo $OUTPUT->header();
$cohorts = mt_dashboard_cohort_get_cohorts($context->id, $page, 25, $searchquery);
$count = '';
if ($cohorts['allcohorts'] > 0) {
    if ($searchquery === '') {
        $count = ' ('.$cohorts['allcohorts'].')';
    } else {
        $count = ' ('.$cohorts['totalcohorts'].'/'.$cohorts['allcohorts'].')';
    }
}
 
$companyobj = new company($SESSION->currenteditingcompany);
$compnay_name = $companyobj->get_name();
echo $OUTPUT->heading(get_string('cohortsin', 'cohort', $compnay_name).$count);
$params = array('page' => $page);
if ($contextid) {
    $params['contextid'] = $contextid;
}
if ($searchquery) {
    $params['search'] = $searchquery;
}
$baseurl = new moodle_url('/local/mt_dashboard/cohort/index.php', $params);
if ($editcontrols = mt_dashboard_cohort_edit_controls($context, $baseurl)) {
    echo $OUTPUT->render($editcontrols);
}
$data['searchquery'] = $searchquery;
$data['contextid'] = $contextid;
$data['showform'] = true;
$data['showtable'] = false;
echo  $OUTPUT->render_from_template('local_mt_dashboard/cohort_index', $data); //  added by nilesh

// Output pagination bar.
echo $OUTPUT->paging_bar($cohorts['totalcohorts'], $page, 25, $baseurl);

$data = array();
$editcolumnisempty = true;

$tt= array();
foreach($cohorts['cohorts'] as $cohort) {
    $cohort->name;
    $cohort->idnumber;
    $cohort->description;
    $mem =  $DB->count_records('cohort_members', array('cohortid'=>$cohort->cohortid));
    if (empty($cohort->component)) {
        $component_name = get_string('nocomponent', 'cohort');
    } else {
        $component_name = get_string('pluginname', $cohort->component);
    }
    $datanew['data_new'] = true;
    $datanew['cohort_name'] = $cohort->name;
    $datanew['cohort_idnumber'] = $cohort->idnumber;
    $datanew['cohort_desc'] = $cohort->description;
    $datanew['cohort_members'] = $mem;
    $datanew['cohort_component'] = $component_name;

    if (empty($cohort->component)) {
        $cohortmanager   =  has_capability('local/mt_dashboard:managecompanycohort', $context);
        $cohortcanassign =  has_capability('local/mt_dashboard:assigncompanycohort', $context);
        $url             =  new moodle_url('/local/mt_dashboard/cohort/index.php', array( 'page' => 0 ));
        $urlparams       =  array('id' => $cohort->cohortid, 'returnurl' => $url);
        $showhideurl     =  new moodle_url('/local/mt_dashboard/cohort/edit.php', $urlparams + array('sesskey' => sesskey()));

        if ($cohortmanager) {
            $datanew['cohortmanager'] = true;
            if ($cohort->visible) {
                $datanew['visible'] = true;
                $showhideurl->param('hide', 1);
                $showhideurl1 = $showhideurl;
                $datanew['showhideurl1'] = $showhideurl1;
            } else {
                $datanew['visible'] = false;
                $showhideurl->param('show', 1);
                $showhideurl2 = $showhideurl;
                $datanew['showhideurl2'] = $showhideurl2;
            }

            $deleteurl = new moodle_url('/local/mt_dashboard/cohort/edit.php', $urlparams + array('delete' => 1));
            $editeurl = new moodle_url('/local/mt_dashboard/cohort/edit.php', $urlparams);

            $datanew['deleteurl'] = $deleteurl;
            $datanew['editeurl'] = $editeurl;
        }
        if ($cohortcanassign) {
            $assign_url = new moodle_url('/local/mt_dashboard/cohort/assign.php', $urlparams);
            $datanew['cohortcanassign'] = true;
            $datanew['assign_url'] = $assign_url;
        }
    }
    $tt[]=$datanew;
    $data['showform'] = false;
    $data_new_one['showtable'] = true;
}
$data_new_one['cohort_listing'] = $tt;
echo  $OUTPUT->render_from_template('local_mt_dashboard/cohort_index', $data_new_one); //  added by nilesh
echo $OUTPUT->paging_bar($cohorts['totalcohorts'], $page, 25, $baseurl);
echo $OUTPUT->footer();