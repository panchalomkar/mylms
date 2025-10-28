<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('../../config.php');
require_once('lib.php');
require_once('addresource_form.php');

$id = optional_param('id', 0, PARAM_INT);
$classroomid = optional_param('classroomid', 0, PARAM_INT);
$classid = optional_param('classid', 0, PARAM_INT);

require_login();
//check capablity to access the page

if (!empty($id)) {
    $venuemanangement = $DB->get_record('local_resource', array('id' => $id));
    //print_r($venuemanangement);exit;    
    if (!empty($id) && empty($venuemanangement)) {
        print_error('Can not find data record');
    }
}

if (!empty($id) && empty($venuemanangement)) {
    print_error('Can not find data record');
}
$context = context_system::instance();

$PAGE->set_context($context);
$returnurl = new moodle_url($CFG->wwwroot . '/local/venuemanangement/listresource.php');
$cancelurl = new moodle_url($CFG->wwwroot . '/local/venuemanangement/index.php');
$PAGE->set_pagelayout('admin');
$pageparams = array();
if ($id) {
    $pageparams = array('id' => $id);
}
$PAGE->set_url('/local/venuemanangement/addresource.php', $pageparams);

if (has_capability('local/venuemanangement:managevenue', $context)) {

// First create the form.
    $editform = new addresource_form(NULL, array('venuemanangement' => $venuemanangement));
    if ($editform->is_cancelled()) {
        redirect($cancelurl);
    } else if ($data = $editform->get_data()) {
        $data->id = $id;
        //print_object($data);
        if (empty($venuemanangement->id)) {
            create_venuemanangement_resource($data);
        } else {
            // Save any changes to the files used in the editor.
            $data->id = $venuemanangement->id;
            update_venuemanangement_resource($data);
        }

        // Redirect user to newly created/updated course.
        redirect($returnurl, 'Successfully Created / Updated');
    }



    $title = "Add/Edit Resource";
    $PAGE->navbar->add($title);
    $PAGE->set_title($title);
    $PAGE->set_heading($title);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);
    echo '<div class = "card-box bord-all pad-all">';
    $editform->display();
    echo '</div>';
} else {
    print_error('accessdenied', 'admin');
}
echo $OUTPUT->footer();

