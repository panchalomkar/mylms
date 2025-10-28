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

class urlform extends moodleform {

    public function definition() {
        global $PAGE;

        $mform = & $this->_form;
        
        $mform->addElement('html', html_writer::start_tag('div', array('class' => '', 'id' => 'url_container')));
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'form-group')));
//        $mform->addElement('html', html_writer::start_tag('input', array('type'=>'text', 'name'=>'video_url', 'id' => 'video_url','placeholder'=>'URL Youtube or Vimeo','style'=>'width:100%;','class'=>'form-control')));
        $mform->addElement('url', 'url', get_string('userevidenceurl', 'tool_lp'), array('size' => '60','id'=>'video_url','placeholder'=>'URL Youtube or Vimeo'), array('usefilepicker' => false,));
        $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addElement('hidden', 'msg_id', '',array('class'=>'update_msg_id'));    
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'btn-group','style'=>'float: right;')));
                $params = array('class' => 'btn btn-cancel btn btn-round', 'type'=>'button');
                $mform->addElement('html', html_writer::tag('button', get_string('cancel'), $params));
                $params1 = array('class' => 'btn btn btn-round btn-primary');
                $mform->addElement('html', html_writer::tag('button', get_string("share","local_social_wall"), $params1));
                
          $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addRule('message', get_string('required_field', 'local_social_wall'), 'required', null, 'client');
    }
 
}