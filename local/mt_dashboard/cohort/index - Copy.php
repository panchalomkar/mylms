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

// if ($context->contextlevel != CONTEXT_COURSECAT and $context->contextlevel != CONTEXT_SYSTEM) {
//     print_error('invalidcontext');
// }

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

echo "<pre>";
echo $OUTPUT->header();

$cohorts = mt_dashboard_cohort_get_cohorts($context->id, $page, 25, $searchquery);

echo "<pre>";
print_r($cohorts); 

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


/*// Add search form.
$search  = html_writer::start_tag('form', array('id'=>'searchcohortquery', 'method'=>'get', 'class' => 'form-inline search-cohort'));
$search .= html_writer::start_div('m-b-1');
$search .= html_writer::label(get_string('searchcohort', 'cohort'), 'cohort_search_q', true,
        array('class' => 'm-r-1')); // No : in form labels!
$search .= html_writer::empty_tag('input', array('id' => 'cohort_search_q', 'type' => 'text', 'name' => 'search',
        'value' => $searchquery, 'class' => 'form-control m-r-1'));
$search .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('search', 'cohort'),
        'class' => 'btn btn-secondary'));
$search .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'contextid', 'value'=>$contextid));
$search .= html_writer::end_div();
$search .= html_writer::end_tag('form');
echo $search;*/

echo  $OUTPUT->render_from_template('local_mt_dashboard/cohort_index', $data); //  added by nilesh


// Output pagination bar.
echo $OUTPUT->paging_bar($cohorts['totalcohorts'], $page, 25, $baseurl);

foreach($cohorts['cohorts'] as $cohort) {
    echo $cohort->name;
    echo "</br>";
    echo $cohort->idnumber;
    echo "</br>";
    echo $cohort->description;
    echo "</br>";

    echo $mem =  $DB->count_records('cohort_members', array('cohortid'=>$cohort->cohortid));
    echo "</br>";

}






$data = array();
$editcolumnisempty = true;
foreach($cohorts['cohorts'] as $cohort) {

    //print_r($cohort);

    $line = array();
    $cohortcontext = context::instance_by_id($cohort->contextid);
  
    $cohort->description = file_rewrite_pluginfile_urls($cohort->description, 'pluginfile.php', $cohortcontext->id,
            'cohort', 'description', $cohort->cohortid);

    //print_r($cohort->description);
    if ($showall) {
        if ($cohortcontext->contextlevel == CONTEXT_COURSECAT) {
            $line[] = html_writer::link(new moodle_url('/local/mt_dashboard/cohort/index.php' ,
                    array('contextid' => $cohort->contextid)), $cohortcontext->get_context_name(false));
        } else {
            $line[] = $cohortcontext->get_context_name(false);
        }
    }
    $tmpl = new \local_mt_dashboard\output\cohortname($cohort);
    $line[] = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
    $tmpl = new \local_mt_dashboard\output\cohortidnumber($cohort);
    $line[] = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
    $line[] = format_text($cohort->description, $cohort->descriptionformat);

    $line[] = $DB->count_records('cohort_members', array('cohortid'=>$cohort->cohortid));

    if (empty($cohort->component)) {
        $line[] = get_string('nocomponent', 'cohort');
    } else {
        $line[] = get_string('pluginname', $cohort->component);
    }
    
    $buttons = array();
    if (empty($cohort->component)) {

        $cohortmanager   =  has_capability('local/mt_dashboard:managecompanycohort', $context);
        $cohortcanassign =  has_capability('local/mt_dashboard:assigncompanycohort', $context);
        $url             =  new moodle_url('/local/mt_dashboard/cohort/index.php', array( 'page' => 0 ));
        $urlparams       =  array('id' => $cohort->cohortid, 'returnurl' => $url);
        $showhideurl     =  new moodle_url('/local/mt_dashboard/cohort/edit.php', $urlparams + array('sesskey' => sesskey()));
        
        if ($cohortmanager) {
            if ($cohort->visible) {
                $showhideurl->param('hide', 1);
                $visibleimg = $OUTPUT->pix_icon('t/hide', get_string('hide'));
                $buttons[]  = html_writer::link($showhideurl, $visibleimg, array('title' => get_string('hide')));
            } else {
                $showhideurl->param('show', 1);
                $visibleimg = $OUTPUT->pix_icon('t/show', get_string('show'));
                $buttons[]  = html_writer::link($showhideurl, $visibleimg, array('title' => get_string('show')));
            }
            $buttons[] = html_writer::link(new moodle_url('/local/mt_dashboard/cohort/edit.php', $urlparams + array('delete' => 1)),
                $OUTPUT->pix_icon('t/delete', get_string('delete')),
                array('title' => get_string('delete')));
            $buttons[] = html_writer::link(new moodle_url('/local/mt_dashboard/cohort/edit.php', $urlparams),
                $OUTPUT->pix_icon('t/edit', get_string('edit')),
                array('title' => get_string('edit')));
            $editcolumnisempty = false;
        }
        if ($cohortcanassign) {
            $buttons[] = html_writer::link(new moodle_url('/local/mt_dashboard/cohort/assign.php', $urlparams),
                $OUTPUT->pix_icon('i/users', get_string('assign', 'core_cohort')),
                array('title' => get_string('assign', 'core_cohort')));
            $editcolumnisempty = false;
        }
    }
    $line[] = implode(' ', $buttons);

    $data[] = $row = new html_table_row($line);
    if (!$cohort->visible) {
        $row->attributes['class'] = 'dimmed_text';
    }
}
$table = new html_table();
$table->head  = array(get_string('name', 'cohort'), get_string('idnumber', 'cohort'), get_string('description', 'cohort'),
                      get_string('memberscount', 'cohort'), get_string('component', 'cohort'));
$table->colclasses = array('leftalign name', 'leftalign id', 'leftalign description', 'leftalign size','centeralign source');
if ($showall) {
    array_unshift($table->head, get_string('category'));
    array_unshift($table->colclasses, 'leftalign category');
}
if (!$editcolumnisempty) {
    $table->head[] = get_string('edit');
    $table->colclasses[] = 'centeralign action';
} else {
    // Remove last column from $data.
    foreach ($data as $row) {
        array_pop($row->cells);
    }
}
/*echo "<pre>"; 
print_r($data);*/

$table->id = 'cohorts';
$table->attributes['class'] = 'admintable generaltable';
$table->data  = $data;
echo html_writer::table($table);


//echo  $OUTPUT->render_from_template('local_mt_dashboard/cohort_index', $data); //  added by nilesh

echo $OUTPUT->paging_bar($cohorts['totalcohorts'], $page, 25, $baseurl);
echo $OUTPUT->footer();
