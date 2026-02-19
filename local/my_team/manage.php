<?php

require_once("../../config.php");
require_once($CFG->dirroot.'/local/my_team/lib.php');

require_login();

$context = context_system::instance();

if(!is_siteadmin()){
    print_error('Access denied');
}

global $CFG, $OUTPUT, $PAGE;
// $PAGE->requires->css(new moodle_url("/local/my_team/styles.css"));
$PAGE->requires->js_call_amd('local_my_team/script', 'init');

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/my_team/manage.php'));

$manageteam = get_string("manageteam", "local_my_team");
$PAGE->set_title($manageteam);
$PAGE->set_heading($manageteam);

echo $OUTPUT->header();

$managers = get_managers();
$back = new moodle_url('/local/my_team/index_1.php');
echo '<div class="main-wrap pb-3">
        <div class="row p-4">
            <a href="'.$back.'" class="btn btn-primary">
                <i class="fa fa-arrow-left" aria-hidden="true"></i>
                My Team Report
            </a>
        </div>
        <div class="selector-wrap row pb-3 px-4">
            <div class="col-md-6 pl-0">
                <select class="form-control" name="select_team" id="select_team">
                    <option value="0">'.get_string('select').'</option>';
                    foreach($managers as $manager){
                        echo '<option value="'.$manager->id.'">'.$manager->firstname.' '.$manager->lastname.'</option>';
                    }
            echo '</select>
            </div>
            <div class="col-md-6">
            </div>
        </div>
        <div class="user-assign-wrap row px-2 py-4">
            <div class="col-md-5">
                <div class="form-group">
                    <label for="assignedusersbox"><h4>'.get_string('assignedusers', 'local_my_team').'</h4></label>
                    <select multiple class="form-control pl-0" id="assignedusersbox" name="assignedusersbox[]" >
                    </select>
                </div>
                <div class="">
                    <label><h4>'.get_string('search').'</h4></label>
                    <input type="text" name="assigneduserssearch" id="assigneduserssearch" class="form-control">
                </div>
            </div>
            <div class="col-md-2 text-center align-self-center">
                <div class="pb-3">
                    <button class="btn btn-secondary" id="addtoteam">
                        <i class="fa fa-caret-left" aria-hidden="true"></i> '.get_string('add').'
                    </button>
                </div>
                <div class="pb-3">
                    <button class="btn btn-secondary" id="removefromteam">
                        '.get_string('remove').' <i class="fa fa-caret-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label for="availableusersbox"><h4>'.get_string('availableusers', 'local_my_team').'</h4></label>
                    <select multiple class="form-control pl-0" id="availableusersbox" name="availableusersbox[]" >
                    </select>
                </div>
                <div class="">
                    <label><h4>'.get_string('search').'</h4></label>
                    <input type="text" name="availableuserssearch" id="availableuserssearch" class="form-control">
                </div>
            </div>
        </div>
    </div>';

echo $OUTPUT->footer();