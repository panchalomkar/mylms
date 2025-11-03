<?php

require_once('../../config.php');
require_once('lib.php');
require_once('addvenuemanangement_form.php');

global $CFG, $PAGE, $DB;

$PAGE->requires->jquery();
$PAGE->requires->js("/local/venuemanangement/js/venue.js");

$id = optional_param('id', 0, PARAM_INT);
$locationid = optional_param('locationid', 0, PARAM_INT);

require_login();
$context = context_system::instance();

// Fetch record only when editing
$venuemanangement = null;
$local_bu = null;

if (!empty($id)) {

    $venuemanangement = $DB->get_record('local_classroom', ['id' => $id], '*', IGNORE_MISSING);

    if ($venuemanangement) {
        $local_bu = $DB->get_record('local_bu', ['id' => $venuemanangement->locationid], '*', IGNORE_MISSING);
    } else {
        print_error('Unable to find venue/classroom record');
    }
}

$PAGE->set_context($context);
$returnurl = new moodle_url('/local/venuemanangement/index.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/venuemanangement/addvenuemanangement.php', ['id' => $id]);

// Check capability
if (!has_capability('local/venuemanangement:managevenue', $context)) {
    print_error('accessdenied', 'admin');
}

// Build form
$editform = new addvenuemanangement_form(null, ['venuemanangement' => $venuemanangement, 'local_bu' => $local_bu]);

if ($editform->is_cancelled()) {

    redirect($returnurl);

} elseif ($data = $editform->get_data()) {

    // Attach location id from request or form
    if ($locationid) {
        $data->locationid = $locationid;
    }

    if (empty($venuemanangement)) {
        // New record
        create_venuemanangement($data);
    } else {
        $data->id = $id;
        update_venuemanangement($data);
    }

    redirect($returnurl, 'Successfully saved!');
}

// Page display
$title = empty($id) ? "Add Classroom / Venue" : "Edit Classroom / Venue";
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo '<div class="card-box bord-all pad-all">';
$editform->display();
echo '</div>';
echo $OUTPUT->footer();
