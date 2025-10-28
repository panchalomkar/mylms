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
$bufilter = optional_param('bu', '', PARAM_TEXT);
$locationfilter = optional_param('location', '', PARAM_TEXT);
$resetfilter = optional_param('resetfilter', '', PARAM_ALPHANUM);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/venuemanangement/listbu.php');
$returnurl = new moodle_url($CFG->wwwroot . '/local/venuemanangement/listbu.php');

if ((has_capability('local/venuemanangement:managevenue', $context)) || (has_capability('local/venuemanangement:viewvenuelist', $context))) {

    if ($resetfilter) {
        redirect($PAGE->url, array());
    }

    if ($delete and confirm_sesskey()) {              // Delete a selected user, after confirmation
        $bu = $DB->get_record('local_bu', array('id' => $delete), '*', MUST_EXIST);

        if ($confirm != md5($delete)) {
            echo $OUTPUT->header();

            echo $OUTPUT->heading(get_string('deletebu', 'local_venuemanangement'), 4);
            $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
            echo $OUTPUT->confirm(get_string('deletecheckfull', '', " venuemanangement - '$bu->location'"), new moodle_url($returnurl, $optionsyes), $returnurl);
            echo $OUTPUT->footer();
            die;
        } else if (data_submitted()) {
            // Purge user preferences.
            $DB->delete_records('local_bu', array('id' => $bu->id));
            redirect($returnurl);
        }
    }

    $table = new html_table();
    $table->head = array();
    $table->colclasses = array();
    $table->attributes['class'] = 'admintable generaltable';
    $table->head[] = 'Sno';
    $table->head[] = get_string('location', 'local_venuemanangement');
    $table->head[] = "";
    $table->colclasses[] = 'centeralign';
    $table->colclasses[] = 'centeralign';

    $table->id = "bu_mapping";
    if (empty($CFG->loginhttps)) {
        $securewwwroot = $CFG->wwwroot;
    } else {
        $securewwwroot = str_replace('http:', 'https:', $CFG->wwwroot);
    }

    $stredit = get_string('edit');
    $strdelete = get_string('delete');

    $filter_params = array();
    if ($bufilter != '') {
        $bu_detail = $DB->get_record('local_bu', array('bucode' => $bufilter));
        if (!empty($bu_detail)) {
            $filter_params['bucode'] = $bufilter;
        } else {
            $filter_params['bucode'] = '';
        }
    }
    if ($locationfilter != '') {
        $bu_detail = $DB->get_record('local_bu', array('location' => $locationfilter));
        if (!empty($bu_detail)) {
            $filter_params['location'] = $locationfilter;
        } else {
            $filter_params['location'] = '';
        }
    }

    $flag = 0;
    $count = 0;
    if (($bufilter && $filter_params['bucode'] == '') || ($locationfilter && $filter_params['location'] == '')) {
        $row = array();
        $row[] = '<div class="col-sm-12 col-md-12 col-lg-12" id="rules_table"><div class="mar-top table-responsive"><div class="alert alert-mtlms fade in mar-top"><strong>Warning!</strong> No Location created, please create a Location.</div></div></div>';
        $table->data[] = $row;
    } else {
        $bus = get_bu_listing($filter_params, $page, $perpage);
        $count = get_bu_count($filter_params);
        
        $i = 1;
        if (empty($bus)) {
            $row = array();
            $row[] =  '<div class="col-sm-12 col-md-12 col-lg-12" id="rules_table"><div class="mar-top table-responsive" ><div class="alert alert-mtlms fade in mar-top"><strong>Warning!</strong> No Location created, please create a Location.</div></div></div>';
            $table->data[] = $row;
        } else {

            foreach ($bus as $bu) {
                global $DB;
                // print_object($bu);
                $row = array();
                $buttons = array();
                if ($page != 0) {
                    $row[] = $i + $perpage * $page;
                } else {
                    $row[] = $i;
                }
                $row[] = $bu->location;
     if (has_capability('local/venuemanangement:managevenue', $context)) {
                    $buttons[] = html_writer::link(new moodle_url($securewwwroot . '/local/venuemanangement/addbu.php', array('id' => $bu->id)), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/edit'), 'alt' => $stredit, 'class' => 'iconsmall')), array('title' => $stredit));
                    $buttons[] = html_writer::link(new moodle_url($securewwwroot . '/local/venuemanangement/listbu.php', array('delete' => $bu->id, 'sesskey' => sesskey())), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'), 'alt' => $strdelete, 'class' => 'iconsmall')), array('title' => $strdelete));
                    $row[] = implode(' ', $buttons);
                }
                $table->data[] = $row;
                $i++;

            }
        }
    }

    $title = "Location Lists";
    $PAGE->navbar->add($title);
    $PAGE->set_title($title);
    $PAGE->set_heading($title);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);
    if (!empty($table)) {
        $formcontent = html_writer::start_tag('div', array('class' => 'venuemanangement-list card-box bord-all pad-all'));

        $formcontent .= html_writer::start_tag('form', array('action' => new moodle_url('/local/venuemanangement/listbu.php'),
                    'id' => 'filter', 'class' => 'form-inline', 'method' => 'post'));

        $formcontent .= html_writer::start_tag('div', array('class' => ' pbottom-lg clearfix'));
        $formcontent .= html_writer::start_tag('div', array('class' => 'btn-toolbar navbar-right'));
        if (has_capability('local/venuemanangement:viewvenuelist', $context)) {
            $formcontent .= html_writer::start_tag('div', array('class' => 'btn-group'));
            $addacesscard = html_writer::tag('span', '', array('class' => 'glyphicon glyphicon-plus'));
            $addclassroomcard = html_writer::tag('span', '', array('class' => 'glyphicon glyphicon-plus'));
            
            $addacesscard.= 'Add Location';
            $formcontent .= html_writer::link(new moodle_url($CFG->wwwroot . '/local/venuemanangement/addbu.php'), $addacesscard, array('class' => 'btn btn-success'));
            
            /* Author VaibhavG
             * add new button to add classroom
             * 17Dec2018
             */
            $addclassroomcard.= 'Add Classroom';
            $formcontent .= html_writer::link(new moodle_url($CFG->wwwroot . '/local/venuemanangement/addvenuemanangement.php'), $addclassroomcard, array('class' => 'btn btn-success'));
            
            $formcontent .= html_writer::end_tag('div');
            $formcontent .= html_writer::end_tag('div');
        }

        $formcontent .= html_writer::end_tag('div');

        $formcontent .= html_writer::start_tag('div', array('class' => 'form-group'));
        $formcontent .= html_writer::start_tag('label', array('class' => 'modicon'));
        $formcontent .= 'Search by Location';
        $formcontent .= html_writer::end_tag('label');
        $formcontent .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'location', 'value' => $locationfilter));
        $formcontent .= html_writer::end_tag('div');
        $formcontent .= html_writer::tag('input', '', array('type' => 'submit', 'name' => 'submitbutton', 'class' => 'btn btn-default', 'value' => 'Search'));
        $formcontent .= html_writer::tag('input', '', array('type' => 'submit', 'name' => 'resetfilter', 'class' => 'btn btn-default', 'value' => 'Reset Filter'));


        $formcontent .= html_writer::table($table);
        $formcontent .= html_writer::end_tag('form');
        $formcontent .= html_writer::end_tag('div');
        echo $formcontent;
//    if ($flag > $perpage) {
 echo $OUTPUT->paging_bar($count, $page, $perpage, $PAGE->url);        //  }
    }
} else {
    print_error('accessdenied', 'admin');
}

echo $OUTPUT->footer();


