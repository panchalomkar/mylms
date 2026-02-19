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
$classroomfilter = optional_param('classroom', '', PARAM_TEXT);
$resetfilter = optional_param('resetfilter', '', PARAM_ALPHANUM);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/venuemanangement/index.php');
$returnurl = new moodle_url($CFG->wwwroot . '/local/venuemanangement/index.php');

if ((has_capability('local/venuemanangement:managevenue', $context)) || (has_capability('local/venuemanangement:viewvenuelist', $context))) {

    if ($resetfilter) {
        redirect($PAGE->url, array());
    }

    if ($delete and confirm_sesskey()) {              // Delete a selected user, after confirmation
        $venuemanangement = $DB->get_record('local_classroom', array('id' => $delete), '*', MUST_EXIST);

        if ($confirm != md5($delete)) {
            echo $OUTPUT->header();

            echo $OUTPUT->heading(get_string('deletevenuemanangement', 'local_venuemanangement'), 4);
            $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
            echo $OUTPUT->confirm(get_string('deletecheckfull', '', " venuemanangement - '$venuemanangement->classroom'"), new moodle_url($returnurl, $optionsyes), $returnurl);
            echo $OUTPUT->footer();
            die;
        } else if (data_submitted()) {
            // Purge user preferences.
            $DB->delete_records('local_classroom', array('id' => $venuemanangement->id));
            redirect($returnurl);
        }
    }

    $table = new html_table();
    $table->head = array();
    $table->colclasses = array();
    $table->attributes['class'] = 'admintable generaltable';
    $table->head[] = 'Sno';
    $table->head[] = get_string('location', 'local_venuemanangement');
    $table->head[] = get_string('classroom', 'local_venuemanangement');
    $table->head[] = get_string('capacity', 'local_venuemanangement');
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

    if ($classroomfilter != '') {
        // $venuemanangement_detail = $DB->get_record('local_classroom', array('classroom' => $classroomfilter));
        $sql='SELECT classroom FROM {local_classroom}';
        $sql .= " WHERE " . $DB->sql_like('classroom', ':classroom', false, false);
        $params['classroom'] = '%' . $classroomfilter . '%';
        $venuemanangement_detail = $DB->get_record_sql($sql, $params);
        foreach($venuemanangement_detail as $clssroom){
            if (!empty($venuemanangement_detail)) {
                $filter_params['classroom'] = $clssroom;
            } else {
                $filter_params['classroom'] = '';
            }
        }
    
    }

    $flag = 0;
    $count = 0;
    if (($classroomfilter && $filter_params['classroom'] == '')) {
        $row = array();
        $row[] = '<div class="col-sm-12 col-md-12 col-lg-12" id="rules_table"><div class="mar-top table-responsive"><div class="alert alert-mtlms fade in mar-top"><strong>Warning!</strong> No Classroom created, please create a Classroom.</div></div></div>';
        $table->data[] = $row;
    } else {
         $venuemanangements = get_venuemanangement_listing($filter_params, $page, $perpage);
        $count = get_venuemanangement_counts($filter_params);

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
                $sessionoptions = array();
                $result = $DB->get_records('local_bu',array('id'=> $venuemanangement->locationid));
                if( count($result) <=0 ){
                    $row[] = '-';
                }else{
                    foreach($result as $key => $value){
                       $row[]  = $value->location;
                    }
                }
                $row[] = $venuemanangement->classroom;
                $row[] = $venuemanangement->capacity;
                
                if ( has_capability('local/venuemanangement:managevenue', $context) ) {
                    $buttons[] = html_writer::link(new moodle_url($securewwwroot . '/local/venuemanangement/addresource.php', array('classid' => $venuemanangement->id)),get_string('addresource','local_venuemanangement'));
                    $buttons[] = html_writer::link(new moodle_url($securewwwroot . '/local/venuemanangement/addvenuemanangement.php', array('id' => $venuemanangement->id)), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/edit'), 'alt' => $stredit, 'class' => 'iconsmall')), array('title' => $stredit));
                    $buttons[] = html_writer::link(new moodle_url($securewwwroot . '/local/venuemanangement/index.php', array('delete' => $venuemanangement->id, 'sesskey' => sesskey())), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'), 'alt' => $strdelete, 'class' => 'iconsmall')), array('title' => $strdelete));
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

    $title = "Venue Lists";
    $PAGE->navbar->add($title);
    $PAGE->set_title($title);
    $PAGE->set_heading($title);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);
    if (!empty($table)) {
        $formcontent = html_writer::start_tag('div', array('class' => 'venuemanangement-list card-box bord-all pad-all'));

        $formcontent .= html_writer::start_tag('form', array('action' => new moodle_url('/local/venuemanangement/index.php'),
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
            //$addlocationcard.= "Add Location";
            //$formcontent .= html_writer::link(new moodle_url($CFG->wwwroot . '/local/venuemanangement/addbu.php'), $addlocationcard, array('class' => 'btn btn-success'));
            
            $addacesscard.= get_string('add_classroom', 'local_venuemanangement');
            $formcontent .= html_writer::link(new moodle_url($CFG->wwwroot . '/local/venuemanangement/addvenuemanangement.php'), $addacesscard, array('class' => 'btn btn-success'));
            $formcontent .= html_writer::end_tag('div');
            $formcontent .= html_writer::end_tag('div');
        }

        $formcontent .= html_writer::end_tag('div');

            $formcontent .= html_writer::start_tag('div', array('class' => 'form-group'));
                $formcontent .= html_writer::start_tag('div', array('class'=> 'col-md-6'));
                    $formcontent .= html_writer::start_tag('label', array('class' => 'modicon float-left mt-2'));
                        $formcontent .= get_string('search_by_classroom', 'local_venuemanangement');
                    $formcontent .= html_writer::end_tag('label');
                    $formcontent .= html_writer::empty_tag('input', array('type' => 'text', 'class' => 'form-control mb-2 ml-2', 'name' => 'classroom', 'value' => $classroomfilter));
                $formcontent .= html_writer::end_tag('div');

                $formcontent .= html_writer::start_tag('div', array('class'=> 'col-md-6'));
                    $icon = html_writer::tag('i', '', array('class'=> 'fa fa-cog'));
                    $formcontent .= html_writer::tag('a', $icon . ' '. 'Manage Locations', array('href' => new moodle_url('/local/venuemanangement/locations.php'), 'class' => 'float-right' ) );
                $formcontent .= html_writer::end_tag('div');

            $formcontent .= html_writer::end_tag('div');
            $formcontent .= html_writer::tag('input', '', array('type' => 'submit', 'name' => 'submitbutton', 'class' => 'btn btn-default mr-1', 'value' => 'Search'));
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