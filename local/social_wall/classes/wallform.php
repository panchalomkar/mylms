<?php
/**
 * Social wall post creation form.
 * 
 * @package   local_social_wall
 * @author    Manisha M
 * @paradiso
*/

defined('MOODLE_INTERNAL') || die;

// Global vars definition.
global $CFG, $PAGE;
require_once("{$CFG->libdir}/formslib.php");

class wallform extends moodleform {

    public function definition() {
        global $PAGE;

        $mform = $this->_form;

        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'post-text-area hidden', 'id' => 'textarea_container')));
            $mform->addElement('hidden', 'msg_id', '',array('id'=>'update_msg_id'));    

            $mform->addElement('editor', 'message','',array('id'=>'message','cols'=>"120" ),$this->_customdata['editoroptions']);
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'button-div')));
                $params = array('class' => 'btn btn-cancel btn btn-round btn-secondary custom-form-buttons', 'type'=>'button');
//                $mform->addElement('html', html_writer::tag('button', get_string('cancel'), $params));
		    $this->add_action_buttons(false,get_string("share","local_social_wall"),$params);
            $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addRule('message', get_string('required_field', 'local_social_wall'), 'required', null, 'client');
    }
}