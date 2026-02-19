<?php
/**
 * Social wall post creation form.
 * 
 * @package   local_social_wall
 * @author    Manisha M
 * @paradiso
*/

defined('MOODLE_INTERNAL') || die;

global $CFG, $PAGE;
require_once("{$CFG->libdir}/formslib.php");

class uploadform extends moodleform {

    public function definition() {
        global $PAGE;

        $mform = & $this->_form;
        $options = array('subdirs' => true,'maxbytes' => 0,'maxfiles' => -1,'accepted_types' => array('.mp4', '.webm', '.ogv','.png','.jpg'));
        $mform->addElement('filemanager', 'videos', get_string('videos', 'videofile'), null, $options);

        $mform->addElement('html', html_writer::start_tag('div', array('class' => '', 'id' => 'upload_container')));
        $mform->addElement('hidden', 'msg_id', '',array('class'=>'update_msg_id'));    
        
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'btn-group','style'=>'float: right')));
                $params = array('class' => 'btn btn-cancel btn btn-round', 'type'=>'button');
                $mform->addElement('html', html_writer::tag('button', get_string('cancel'), $params));
                $params1 = array('class' => 'btn btn btn-round btn-primary');
                $mform->addElement('html', html_writer::tag('button', get_string("share","local_social_wall"), $params1));
           
          $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addRule('message', get_string('required_field', 'local_social_wall'), 'required', null, 'client');
    }
 
}