<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('../../config.php');
require_once('lib.php');
require_once('addvenuemanangement_form.php');
global $CFG, $PAGE;
$PAGE->requires->jquery();
$PAGE->requires->js("/local/venuemanangement/js/venue.js");

$id = optional_param('id', 0, PARAM_INT);
$locaionid = optional_param('locationid', 0, PARAM_INT);

require_login();
//check capablity to access the page

if (!empty($id)) {
    $venuemanangement = $DB->get_record('local_classroom', array('id' => $id));
    $local_bu = $DB->get_record('local_bu', array('id' => $venuemanangement->locationid));
        
    if (!empty($id) && empty($venuemanangement)) {
        print_error('Can not find data record');
    }
}

if (!empty($id) && empty($venuemanangement)) {
    print_error('Can not find data record');
}
$context = context_system::instance();

$PAGE->set_context($context);
$returnurl = new moodle_url($CFG->wwwroot . '/local/venuemanangement/index.php');
$PAGE->set_pagelayout('admin');
$pageparams = array();
if ($id) {
    $pageparams = array('id' => $id);
}
$PAGE->set_url('/local/venuemanangement/addvenuemanangement.php', $pageparams);

if (has_capability('local/venuemanangement:managevenue', $context)) {

// First create the form.
    $editform = new addvenuemanangement_form(NULL, array('venuemanangement' => $venuemanangement,'local_bu'=>$local_bu));
    if ($editform->is_cancelled()) {
        redirect($returnurl);
    } else if ($data = $editform->get_data()) {
        $data->locationid = $locaionid;
        //print_object($data);
        if (empty($venuemanangement->id)) {
            create_venuemanangement($data);
        } else {
            // Save any changes to the files used in the editor.
            $data->id = $venuemanangement->id;
            update_venuemanangement($data);
        }

        // Redirect user to newly created/updated course.
        redirect($returnurl, 'Successfully Created / Updated');
    }



    $title = "Add/Edit Classroom";
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

