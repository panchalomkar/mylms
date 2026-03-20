<?php
// form notifications enrrolment in the learning path
class lp_notifications1 extends moodleform
{
    public function definition() {
        global $CFG , $DB, $COURSE;

        $mform = $this->_form;

        $mform->addElement( 'checkbox', 'enrollment_editor_checkbox1', get_string('enable_student', 'local_learningpaths'), []);
        $mform->addElement( 'checkbox', 'enrollment_editor_checkbox2', get_string('enable_isntructor', 'local_learningpaths'), []);
        $this->add_action_buttons(true, get_string('savechanges'),$backtocourse);
    }

    public function add_action_buttons($cancel = true, $submitlabel = null ,$urlback=null) {
        $mform =& $this->_form;
        $buttonarray = array();

        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('button', 'cancelbutton', get_string('cancel'), array('onclick'=>"location.href='".$urlback."'"));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}

// form notifications expiration in the learning path
class lp_expiration extends moodleform
{
	public function definition() {

    	$mform = $this->_form;

    	$mform->addElement( 'checkbox', 'expiration_editor_checkbox1', get_string('enable_student', 'local_learningpaths') );
    	$mform->addElement( 'checkbox', 'expiration_editor_checkbox2', get_string('enable_isntructor', 'local_learningpaths') );
    	$mform->addElement( 'editor', 'expiration_editor', get_string('message_template', 'local_learningpaths'));
	    $this->add_action_buttons(true, get_string('savechanges'),$backtocourse);
	}

    public function add_action_buttons($cancel = true, $submitlabel = null ,$urlback=null) {
        $mform =& $this->_form;
        $buttonarray = array();

        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('button', 'cancelbutton', get_string('cancel'), array('onclick'=>"location.href='".$urlback."'"));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}

// form notifications enrollment reminder in the learning path
class lp_enreminder extends moodleform
{
	public function definition() {

    	$mform = $this->_form;

    	$mform->addElement( 'checkbox', 'enreminder_editor_checkbox1', get_string('enable_student', 'local_learningpaths') );
    	$mform->addElement( 'checkbox', 'enreminder_editor_checkbox2', get_string('enable_isntructor', 'local_learningpaths') );
    	$mform->addElement( 'text', 'enreminder_editor_text', get_string('days_before', 'local_learningpaths'));
    	$mform->addElement( 'editor', 'enreminder_editor', get_string('message_template', 'local_learningpaths'));
	    $this->add_action_buttons(true, get_string('savechanges'),$backtocourse);
	}

    public function add_action_buttons($cancel = true, $submitlabel = null ,$urlback=null) {
        $mform =& $this->_form;
        $buttonarray = array();

        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('button', 'cancelbutton', get_string('cancel'), array('onclick'=>"location.href='".$urlback."'"));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}

// form notifications expiration reminder in the learning path
class lp_exreminder extends moodleform
{
	public function definition() {

    	$mform = $this->_form;

    	$mform->addElement( 'checkbox', 'exreminder_editor_checkbox1', get_string('enable_student', 'local_learningpaths') );
    	$mform->addElement( 'checkbox', 'exreminder_editor_checkbox2', get_string('enable_isntructor', 'local_learningpaths') );
    	$mform->addElement( 'text', 'exreminder_editor_text', get_string('days_after', 'local_learningpaths'));
    	$mform->addElement( 'editor', 'exreminder_editor', get_string('message_template', 'local_learningpaths'));
	    $this->add_action_buttons(true, get_string('savechanges'),$backtocourse);
	}

    public function add_action_buttons($cancel = true, $submitlabel = null ,$urlback=null) {
        $mform =& $this->_form;
        $buttonarray = array();

        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('button', 'cancelbutton', get_string('cancel'), array('onclick'=>"location.href='".$urlback."'"));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}

// Accordion notifications path completion in the learning path
class lp_notifications extends moodleform
{
	public function definition() {

    	$mform = $this->_form;

    	$mform->addElement( 'checkbox', 'notifications_editor_checkbox1', get_string('enable_student', 'local_learningpaths') );
    	$mform->addElement( 'checkbox', 'notifications_editor_checkbox2', get_string('enable_isntructor', 'local_learningpaths') );
    	$mform->addElement( 'editor', 'notifications_editor', get_string('message_template', 'local_learningpaths'));
	    $this->add_action_buttons(true, get_string('savechanges'),$backtocourse);
	}

    public function add_action_buttons($cancel = true, $submitlabel = null ,$urlback=null) {
        $mform =& $this->_form;
        $buttonarray = array();

        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('button', 'cancelbutton', get_string('cancel'), array('onclick'=>"location.href='".$urlback."'"));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}

// Accordion notifications certificated issued in the learning path
class lp_certificated extends moodleform
{
	public function definition() {

    	$mform = $this->_form;
    	$mform->addElement( 'checkbox', 'certificated_editor_checkbox1', get_string('enable_student', 'local_learningpaths'));
    	$mform->addElement( 'checkbox', 'certificated_editor_checkbox2', get_string('enable_isntructor', 'local_learningpaths') );
    	$mform->addElement( 'editor', 'certificated_editor', get_string('message_template', 'local_learningpaths'));
	    $this->add_action_buttons(true, get_string('savechanges'),$backtocourse);
	}

    public function add_action_buttons($cancel = true, $submitlabel = null ,$urlback=null) {
        $mform =& $this->_form;
        $buttonarray = array();

        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('button', 'cancelbutton', get_string('cancel'), array('onclick'=>"location.href='".$urlback."'"));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}