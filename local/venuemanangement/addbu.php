<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('../../config.php');
require_once('lib.php');
require_once('addbu_form.php');

$id = optional_param('id', 0, PARAM_INT);
//$specialtype = optional_param('specialtype', '', PARAM_ALPHANUM);
require_login();
$bu_name = '';
//check capablity to access the page
if (!empty($id)) {
    $bu = $DB->get_record('local_bu', array('id' => $id));
    if (!empty($id) && empty($bu)) {
        print_error('Can not find data record');
    }
}

$context = context_system::instance();

$PAGE->set_context($context);
$returnurl = new moodle_url($CFG->wwwroot . '/local/venuemanangement/locations.php');
$cancelurl = new moodle_url($CFG->wwwroot . '/local/venuemanangement/locations.php');
$PAGE->set_pagelayout('admin');
$pageparams = array();
if ($id) {
    $pageparams = array('id' => $id);
}
$PAGE->set_url('/local/venuemanangement/addvenuemanangement.php', $pageparams);

if (has_capability('local/venuemanangement:managevenue', $context)) {

// First create the form.
    $editform = new addbu_form(NULL, array('bu' => $bu));
    if ($editform->is_cancelled()) {
        redirect($cancelurl);
    } else if ($data = $editform->get_data()) {
//print_object($data);die;
        if (empty($bu->id)) {
            create_bu($data);
        } else {
            // Save any changes to the files used in the editor.
            $data->id = $bu->id;
            update_bu($data);
        }

        // Redirect user to newly created/updated course.
        redirect($returnurl, 'Successfully Created / Updated');
    }



    $title = "Add/Edit Location";
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
