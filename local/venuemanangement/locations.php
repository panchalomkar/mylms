<?php

/**
 * This file will list out all the locations and do operations on this like
 * edit locations and delete it
 * @author Sandeep B
 * @since 03-02-2020
 * 
 */

require_once('../../config.php');
require_once('lib.php');
require_login();
global $DB;

$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/venuemanangement/locations.php');

$returnurl = new moodle_url($CFG->wwwroot . '/local/venuemanangement/locations.php');

if ((has_capability('local/venuemanangement:managevenue', $context)) || (has_capability('local/venuemanangement:viewvenuelist', $context))) {

    if ($delete and confirm_sesskey()) {              // Delete a selected user, after confirmation
        $location = $DB->get_record('local_bu', array('id' => $delete), '*', MUST_EXIST);

        if ($confirm != md5($delete)) {
            echo $OUTPUT->header();

            echo $OUTPUT->heading(get_string('deletevenuemanangement', 'local_venuemanangement'), 4);
            $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
            echo $OUTPUT->confirm(get_string('deletecheckfull', '', " Location - '$location->location'"), new moodle_url($returnurl, $optionsyes), $returnurl);
            echo $OUTPUT->footer();
            die;
        } else if (data_submitted()) {
            // Purge user preferences.
            $DB->delete_records('local_bu', array('id' => $location->id));
            redirect($returnurl);
        }
    }

    $table = new html_table();
        $table->head = array();
        $table->colclasses = array();
        $table->attributes['class'] = 'admintable generaltable';
        $table->head[] = get_string('sno', 'local_venuemanangement');
        $table->head[] = get_string('location', 'local_venuemanangement');
        $table->head[] = get_string('action');
        $table->colclasses[] = 'centeralign';
        $table->colclasses[] = 'centeralign';

        $table->id = "venue_locations";
        if (empty($CFG->loginhttps)) {
            $securewwwroot = $CFG->wwwroot;
        } else {
            $securewwwroot = str_replace('http:', 'https:', $CFG->wwwroot);
        }

        $stredit = get_string('edit');
        $strdelete = get_string('delete');

    
    $all_locations = $DB->count_records( 'local_bu', array() );
    $locations = get_venue_locations();
    if (!empty($locations)) {

        foreach ($locations as $location) {
            global $DB;
            // print_object($venuemanangement);
            $row = array();
            $buttons = array();
            $row[] = '#' .$location->id;
            $row[] = $location->location;
            
                if (has_capability('local/venuemanangement:managevenue', $context)) {
                $buttons[] = html_writer::link(new moodle_url($securewwwroot . '/local/venuemanangement/addbu.php', array('id' => $location->id)), html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/edit'), 'alt' => $stredit, 'class' => 'iconsmall')), array('title' => $stredit));
                $buttons[] = html_writer::link(new moodle_url($securewwwroot . '/local/venuemanangement/locations.php', array('delete' => $location->id, 'sesskey' => sesskey())), html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/delete'), 'alt' => $strdelete, 'class' => 'iconsmall')), array('title' => $strdelete));
                $row[] = implode(' ', $buttons);
            }
            $table->data[] = $row;
        }
    }

    $title = get_string("locations_lists", 'local_venuemanangement');
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
            
            $addlocationcard = get_string("add_location", 'local_venuemanangement');
            $formcontent .= html_writer::link(new moodle_url($CFG->wwwroot . '/local/venuemanangement/addbu.php'), $addlocationcard, array('class' => 'btn btn-success'));
            
            $formcontent .= html_writer::end_tag('div');
            $formcontent .= html_writer::end_tag('div');
        }

        $formcontent .= html_writer::end_tag('div');

            $formcontent .= html_writer::start_tag('div', array('class' => 'form-group'));
                $formcontent .= html_writer::start_tag('div', array('class'=> 'col-md-6'));                   
                $formcontent .= html_writer::end_tag('div');

                $formcontent .= html_writer::start_tag('div', array('class'=> 'col-md-6'));                   
                    $icon = html_writer::tag('i', '', array('class'=> 'fa fa-arrow-circle-left', "aria-hidden" => "true"));
                    $formcontent .= html_writer::tag('a', $icon . ' '. get_string('back_to_list', 'local_venuemanangement'), array('href' => new moodle_url('/local/venuemanangement/index.php'), 'class' => 'float-right' ) );
                $formcontent .= html_writer::end_tag('div');

            $formcontent .= html_writer::end_tag('div');
            if(empty($locations)){
                $formcontent .= html_writer::tag('div', get_string('no_records', 'local_venuemanangement'), array() );
            }else{
                $formcontent .= html_writer::table($table);
            }
        $formcontent .= html_writer::end_tag('form');
        $formcontent .= html_writer::end_tag('div');
        echo $formcontent;

        echo $OUTPUT->paging_bar( $all_locations, $page, $perpage, $PAGE->url, 'page' );
    }
} else {
    print_error('accessdenied', 'admin');
}

echo $OUTPUT->footer();