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
class addvenuemanangement_form extends moodleform {

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
        
        //access no
        if(!empty($local_bu) && !empty($venuemanangement) ){

            //date
            $mform->addElement('text', 'location', get_string('location', 'local_venuemanangement'), array('readonly'=>'readonly','size'=>'50','value'=>$local_bu->location));
            $mform->addRule('location', get_string('missinglocation', 'local_venuemanangement'), 'required', null, 'client');
            $mform->setType('location', PARAM_TEXT);
            
            $mform->addElement('html', '<div id="classroomalert" class="alert alert-danger col-sm-12" style="display:none;">
                <strong style="font-size:10px;"> '. get_string('classroomalreadyexist', 'local_venuemanangement').'</strong>
            </div>');
            $mform->addElement('text', 'classroom', get_string('classroom', 'local_venuemanangement'), array('size'=>'50','value'=>$venuemanangement->classroom));
            $mform->addRule('classroom', get_string('missingclassroom', 'local_venuemanangement'), 'required', null, 'client');
            $mform->setType('classroom', PARAM_TEXT);
            $mform->addElement('hidden', 'locationid', $venuemanangement->locationid);
            $mform->addElement('text', 'capacity', get_string('capacity', 'local_venuemanangement'), array('size'=>'10','value'=>$venuemanangement->capacity));
            $mform->addRule('capacity', get_string('missingcapacity', 'local_venuemanangement'), 'required', null, 'client');
            $mform->addRule('capacity', get_string('numericfield', 'local_venuemanangement'), 'numeric', null, 'client');
            $mform->setType('capacity', PARAM_INT);


            $this->add_action_buttons();

            $mform->addElement('hidden', 'id', $venuemanangement->id);
            $mform->setType('id', PARAM_INT);
        }
        else{
            $bulist = current($DB->get_record_sql("select param1 from {user_info_field} where shortname='businessunit'"));
            $bulist_array= explode("\n", $bulist);
        
            $buoptions=array();
           // $buoptions[0]='Select';
            foreach($bulist_array as $key => $value){
               $buoptions[$value]=$value;
            }
            //print_object($yearoptions);
            
            $locationoptions = array();
            $locationoptions[0]='Select Location';
            $venuemanangement_detail = $DB->get_records_sql('select id,location from mdl_local_bu');
            if(!empty($venuemanangement_detail))
            {
                foreach($venuemanangement_detail as $venue)
                {
                    $key = $venue->id;
                    $value =$venue->location;
                    $locationoptions[$key] = $value;
                }
                //$select = $mform->addElement('select', 'locationid', get_string('location', 'local_venuemanangement'), $locationoptions);
                //$mform->addRule('locationid', get_string('missinglocation', 'local_venuemanangement'), 'required', null, 'client');
                //$mform->setType('locationid', PARAM_INT);
                
            }
                $mform->addElement('selectwithlink', 'locationid', get_string('location','local_venuemanangement'), $locationoptions, null, 
                    array('link' => $CFG->wwwroot.'/local/venuemanangement/addbu.php', 'label' => get_string('addlocation','local_venuemanangement')));   
                $mform->setType('locationid', PARAM_INT);
                
                
            //date
            $mform->addElement('html', '<div id="classroomalert" class="alert alert-danger col-sm-12" style="display:none;">
                <strong style="font-size:10px;"> '. get_string('classroomalreadyexist', 'local_venuemanangement').'</strong>
            </div>');
            $mform->addElement('text', 'classroom', get_string('classroom', 'local_venuemanangement'), 'size="50"');
            $mform->addRule('classroom', get_string('missingclassroom', 'local_venuemanangement'), 'required', null, 'client');
            $mform->setType('classroom', PARAM_TEXT);

            $mform->addElement('text', 'capacity', get_string('capacity', 'local_venuemanangement'), 'size="10"');
            $mform->addRule('capacity', get_string('missingcapacity', 'local_venuemanangement'), 'required', null, 'client');
            $mform->addRule('capacity', get_string('numericfield', 'local_venuemanangement'), 'numeric', null, 'client');
            $mform->setType('capacity', PARAM_INT);
                
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
