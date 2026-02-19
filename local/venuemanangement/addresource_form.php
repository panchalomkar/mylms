<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
$PAGE->requires->js("/local/venuemanangement/js/venue.js");

/**
 * The form for handling editing a course.
 */
class addresource_form extends moodleform {

    /**
     * Form definition.
     */
    function definition() {
        global $CFG,$DB, $PAGE;

        $mform = $this->_form;

        // Form definition with new course defaults.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $venuemanangement = $this->_customdata['venuemanangement']; // this contains the data of this form
        $local_bu = $this->_customdata['local_bu']; // this contains the data of this form
        $classid = optional_param('classid', 0, PARAM_INT);

        //access no
        if(!empty($venuemanangement) ){

            $classroom_detail = $DB->get_records_sql('select id,classroom from mdl_local_classroom WHERE id = ? ', array($venuemanangement->classroomid));
            if(!empty($classroom_detail))
            {
                foreach($classroom_detail as $class)
                {
                    $key = $class->id;
                    $classvalue =$class->classroom;
                }
            }   
            $mform->addElement('text', 'classroom', get_string('classroom', 'local_venuemanangement'), array('readonly'=>'readonly','size'=>'50','value'=>$classvalue));
            $mform->addRule('classroom', get_string('missingclassroom', 'local_venuemanangement'), 'required', null, 'client');
            $mform->setType('classroom', PARAM_TEXT);
            
            $mform->addElement('hidden', 'classroomid', $venuemanangement->classroomid);
            
            $mform->addElement('text', 'resource', get_string('resource', 'local_venuemanangement'), array('value'=>$venuemanangement->resource));
            $mform->addRule('resource', get_string('missingresource', 'local_venuemanangement'), 'required', null, 'client');
            
            $mform->addElement('text', 'resourceqty', get_string('resourceqty', 'local_venuemanangement'), array('value'=>$venuemanangement->resourceqty));
            $mform->addRule('resourceqty', get_string('missingresourceqty', 'local_venuemanangement'), 'required', null, 'client');
            $mform->addRule('resourceqty', get_string('numericfield', 'local_venuemanangement'), 'numeric', null, 'client');
            $mform->setType('resourceqty', PARAM_INT);
            
            $this->add_action_buttons();

            $mform->addElement('hidden', 'id', $venuemanangement->id);
            $mform->setType('id', PARAM_INT);
        }
        else{
            
            $mform->addElement('hidden', 'classroomid', $classid);   
                
            $mform->addElement('text', 'resource', get_string('resource', 'local_venuemanangement'));
            $mform->addRule('resource', get_string('missingresource', 'local_venuemanangement'), 'required', null, 'client');
            $mform->setType('resource', PARAM_TEXT);

            $mform->addElement('text', 'resourceqty', get_string('resourceqty', 'local_venuemanangement'));
            $mform->addRule('resourceqty', get_string('missingresourceqty', 'local_venuemanangement'), 'required', null, 'client');
            $mform->setType('resourceqty', PARAM_INT);
            
            $this->add_action_buttons();

            $mform->addElement('hidden', 'id', null);
            $mform->setType('id', PARAM_INT);
        }
        //$this->set_data($venuemanangement);
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
