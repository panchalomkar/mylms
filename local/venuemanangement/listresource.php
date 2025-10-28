<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('../../config.php');
require_once('lib.php');
require_login();
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);        // how many per page
 $venuemanangementfilter = optional_param('bu', '', PARAM_TEXT);
$resourcefilter = optional_param('resource', '', PARAM_TEXT);
$resetfilter = optional_param('resetfilter', '', PARAM_ALPHANUM);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/venuemanangement/listresource.php');
$returnurl = new moodle_url($CFG->wwwroot . '/local/venuemanangement/listresource.php');

if ((has_capability('local/venuemanangement:managevenue', $context)) || (has_capability('local/venuemanangement:viewvenuelist', $context))) {

    if ($resetfilter) {
        redirect($PAGE->url, array());
    }

    if ($delete and confirm_sesskey()) {              // Delete a selected user, after confirmation
        $venuemanangement = $DB->get_record('local_resource', array('id' => $delete), '*', MUST_EXIST);

        if ($confirm != md5($delete)) {
            echo $OUTPUT->header();

            echo $OUTPUT->heading(get_string('deletevenuemanangement', 'local_venuemanangement'), 4);
            $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
            echo $OUTPUT->confirm(get_string('deletecheckfull', '', " venuemanangement - '$venuemanangement->resource'"), new moodle_url($returnurl, $optionsyes), $returnurl);
            echo $OUTPUT->footer();
            die;
        } else if (data_submitted()) {
            // Purge user preferences.
            $DB->delete_records('local_resource', array('id' => $venuemanangement->id));
            redirect($returnurl);
        }
    }

    $table = new html_table();
    $table->head = array();
    $table->colclasses = array();
    $table->attributes['class'] = 'admintable generaltable';
    $table->head[] = 'Sno';
    $table->head[] = get_string('classroom', 'local_venuemanangement');
    $table->head[] = get_string('resource', 'local_venuemanangement');
    $table->head[] = get_string('resourceqty', 'local_venuemanangement');
     $table->head[] = "";
    $table->colclasses[] = 'centeralign';
    $table->colclasses[] = 'centeralign';

    $table->id = "venuemanangements_mapping";
    if (empty($CFG->loginhttps)) {
        $securewwwroot = $CFG->wwwroot;
    } else {
        $securewwwroot = str_replace('http:', 'https:', $CFG->wwwroot);
    }

    $stredit = get_string('edit');
    $strdelete = get_string('delete');

    $filter_params = array();

    if ($resourcefilter != '') {
        $venuemanangement_detail = $DB->get_record('local_resource', array('resource' => $resourcefilter));
        if (!empty($venuemanangement_detail)) {
            $filter_params['resource'] = $resourcefilter;
        } else {
            $filter_params['resource'] = '';
        }
    }

    $flag = 0;
    $count = 0;
    if (($resourcefilter && $filter_params['resource'] == '')) {
        $row = array();
        $row[] = '<div class="col-sm-12 col-md-12 col-lg-12" id="rules_table"><div class="mar-top table-responsive"><div class="alert alert-mtlms fade in mar-top"><strong>Warning!</strong> No Resource created, please create a Resource.</div></div></div>';
        $table->data[] = $row;
    } else {
        $venuemanangements = get_resource_listing($filter_params, $page, $perpage);
        $count = get_resource_counts( $filter_params );

        $i = 1;
        if (empty($venuemanangements)) {
            $row = array();
            $row[] =  '<div class="col-sm-12 col-md-12 col-lg-12" id="rules_table"><div class="mar-top table-responsive" ><div class="alert alert-mtlms fade in mar-top"><strong>Warning!</strong> No Classroom created, please create a Classroom.</div></div></div>';
            $table->data[] = $row;
        } else {

            foreach ($venuemanangements as $venuemanangement) {
                global $DB;
                // print_object($venuemanangement);
                $row = array();
                $buttons = array();
                if ($page != 0) {
                    $row[] = $i + $perpage * $page;
                } else {
                    $row[] = $i;
                }
                $result = $DB->get_records_sql('SELECT r.classroomid,c.id,c.classroom FROM mdl_local_resource r JOIN mdl_local_classroom c ON c.id = r.classroomid WHERE r.classroomid = ?' , array($venuemanangement->classroomid));
                $sessionoptions=array();
                foreach($result as $key => $value){

                   $row[]  = $value->classroom;

                }
                $row[] = $venuemanangement->resource;
                $row[] = $venuemanangement->resourceqty;
                
                  if (has_capability('local/venuemanangement:managevenue', $context)) {
                    $buttons[] = html_writer::link(new moodle_url($securewwwroot . '/local/venuemanangement/addresource.php', array('id' => $venuemanangement->id)), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/edit'), 'alt' => $stredit, 'class' => 'iconsmall')), array('title' => $stredit));
                    $buttons[] = html_writer::link(new moodle_url($securewwwroot . '/local/venuemanangement/listresource.php', array('delete' => $venuemanangement->id, 'sesskey' => sesskey())), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'), 'alt' => $strdelete, 'class' => 'iconsmall')), array('title' => $strdelete));
                    $row[] = implode(' ', $buttons);
                }
                $table->data[] = $row;
                $i++;
//    if ($page != 0) {
//        $flag = $i + $perpage;
//    } else {
//        $flag = $i;}
            }
        }
    }

    $title = "Resource Lists";
    $PAGE->navbar->add($title);
    $PAGE->set_title($title);
    $PAGE->set_heading($title);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);
    if (!empty($table)) {
        $formcontent = html_writer::start_tag('div', array('class' => 'venuemanangement-list card-box bord-all pad-all'));

        $formcontent .= html_writer::start_tag('form', array('action' => new moodle_url('/local/venuemanangement/listresource.php'),
                    'id' => 'filter', 'class' => 'form-inline', 'method' => 'post'));

        $formcontent .= html_writer::start_tag('div', array('class' => ' pbottom-lg clearfix'));
        $formcontent .= html_writer::start_tag('div', array('class' => 'btn-toolbar navbar-right'));
        if (has_capability('local/venuemanangement:viewvenuelist', $context)) {
            $formcontent .= html_writer::start_tag('div', array('class' => 'btn-group'));
            $addacesscard = html_writer::tag('span', '', array('class' => 'glyphicon glyphicon-plus'));
            /*Author VaibhavG
             * add new button
             * 17Dec2018
             */    
            $addlocationcard= "Add Location";
            $formcontent .= html_writer::link(new moodle_url($CFG->wwwroot . '/local/venuemanangement/addbu.php'), $addlocationcard, array('class' => 'btn btn-success'));
            
            $addacesscard.= "Add Classroom";
            $formcontent .= html_writer::link(new moodle_url($CFG->wwwroot . '/local/venuemanangement/addvenuemanangement.php'), $addacesscard, array('class' => 'btn btn-success'));
            
            // add code for set button to go back to venue list by VaibhavG dated on 24Jan2019
            $gobackcard.= "Go Back";
            $formcontent .= html_writer::link(new moodle_url($CFG->wwwroot . '/local/venuemanangement/index.php'), $gobackcard, array('class' => 'btn btn-info'));
            
            $formcontent .= html_writer::end_tag('div');
            $formcontent .= html_writer::end_tag('div');
        }

        $formcontent .= html_writer::end_tag('div');

        $formcontent .= html_writer::start_tag('div', array('class' => 'form-group'));
        $formcontent .= html_writer::start_tag('label', array('class' => 'modicon'));
        $formcontent .= 'Search by Resource';
        $formcontent .= html_writer::end_tag('label');
        $formcontent .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'resource', 'value' => $resourcefilter));
        $formcontent .= html_writer::end_tag('div');
        $formcontent .= html_writer::tag('input', '', array('type' => 'submit', 'name' => 'submitbutton', 'class' => 'btn btn-default', 'value' => 'Search'));
        $formcontent .= html_writer::tag('input', '', array('type' => 'submit', 'name' => 'resetfilter', 'class' => 'btn btn-default', 'value' => 'Reset Filter'));


        $formcontent .= html_writer::table($table);
        $formcontent .= html_writer::end_tag('form');
        $formcontent .= html_writer::end_tag('div');
        echo $formcontent;
//    if ($flag > $perpage) {
        echo $OUTPUT->paging_bar($count, $page, $perpage, $PAGE->url);
        //  }
    }
} else {
    print_error('accessdenied', 'admin');
}

echo $OUTPUT->footer();