<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
global $CFG, $PAGE;
$PAGE->requires->jquery();
$PAGE->requires->js("/local/venuemanangement/js/venue.js");
/**
 * The form for handling editing a course.
 */
class addbu_form extends moodleform {

    /**
     * Form definition.
     */
    function definition() {
        global $CFG,$DB, $PAGE;

        $mform = $this->_form;

        // Form definition with new course defaults.
        $mform->addElement('header', 'general', get_string('general', 'form'));
       $bu = $this->_customdata['bu'] ?? new stdClass(); // ensure object

// Initialize default properties if empty
$bu->bu = $bu->bu ?? '';
$bu->location = $bu->location ?? '';


       $bulirecord = $DB->get_record_sql("SELECT param1 FROM {user_info_field} WHERE shortname = 'businessunit'");

$bulist = $bulirecord->param1 ?? ''; // Prevent boolean false
$bulist_array = !empty($bulist) ? explode("\n", $bulist) : [];

        
        $buoptions=array();
       // $buoptions[0]='Select';
        foreach($bulist_array as $key => $value){
           $buoptions[$value]=$value;
        }
        //print_object($yearoptions);
        $mform->addElement( 'html', html_writer::start_div('col-xs-12 col-sm-12 col-md-12 col-lg-12') );
        $mform->addElement('html', '<div id="locationalert" class="alert alert-danger col-sm-12" style="display:none;">
                <strong style="font-size:10px;"> '. get_string('locationalreadyexist', 'local_venuemanangement').'</strong>
            </div>');
        $mform->addElement('text', 'location', get_string('location', 'local_venuemanangement'),'maxlength="254" size="50"');
        $mform->addRule('location', get_string('missinglocation', 'local_venuemanangement'), 'required', null, 'client');
        $mform->setType('location', PARAM_TEXT);



        $this->add_action_buttons();

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        $this->set_data($bu);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        return $errors;
    }

}
