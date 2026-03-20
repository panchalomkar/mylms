<?php
defined('MOODLE_INTERNAL') || die;

// form notifications enrrolment in the learning path
class lp_notifications1 extends moodleform
{
    public function definition() {
    	global $CFG , $DB, $COURSE;
        $lpid = optional_param('id', 0, PARAM_INT);

    	$mform = $this->_form;
        $attributes = [
            'autocomplete' => 'off',
            'action' => $CFG->wwwroot.'/local/learningpaths/view.php?id='.$lpid.'&tab=notifications',
            'method' => 'post',
            'accept-charset' => 'utf-8',
            'id' => 'mform13',
            'class' => 'mform'
        ];
            $mform->setAttributes($attributes);
        
            $mform->addElement('html', '<div>');
            $mform->addElement( 'hidden', 'cron_lp_enrollment', '', []);
            $mform->setDefault('cron_lp_enrollment', '0');
            
            $mform->addElement('html', '<div id="form_id" class="row">');
                
            $mform->addElement('html', '<div class="col-md-2 txt_area_txt">');
            $text = $mform->addElement('static', '',get_string('enrollment_tag', 'local_learningpaths'));
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-2 txt_area_txt2">');
            $mform->addElement('html', '<div class="col-md-12">');
            $mform->addElement('static', 'description', get_string('enrollment_notification', 'local_learningpaths'));
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-12 email_template_position">');
            $mform->addElement('static', 'description', get_string('email_template', 'local_learningpaths'));
            $mform->addElement('html', '</div>');
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-8 txt_area_notifications">');
            $mform->addElement( 'checkbox', 'enrollment_editor_checkbox1', get_string('enable_student', 'local_learningpaths'), []);
            $mform->addElement( 'editor', 'enrollment_editor');
            $mform->addElement('html', '<p class="col-sm-12 col-md-12 col-lg-12 col-xl-12 tag_desc">'.get_string("tags_desciption", "local_learningpaths").'</p>');
            $mform->addElement('html', '</div>');
                 
            $mform->addElement('html', '</div>');

            $mform->addElement( 'hidden', 'cron_lp_expiration', '', []);
            $mform->setDefault('cron_lp_expiration', '1');

            $mform->addElement('html', '<div id="form_id"  class="row">');
                
            $mform->addElement('html', '<div class="col-md-2 txt_area_txt">');
            $text=$mform->addElement('static', '',get_string('enrollment_expiration', 'local_learningpaths'));
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-2 txt_area_txt2">');
                    
            $mform->addElement('html', '<div class="col-md-12">');
            $mform->addElement('static', 'description', get_string('enrollment_notification', 'local_learningpaths'));
            $mform->addElement('html', '</div>');
              
            $mform->addElement('html', '<div class="col-md-12 email_template_position">');
            $mform->addElement('static', 'description', get_string('email_template', 'local_learningpaths'));
            $mform->addElement('html', '</div>');
                
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-8 txt_area_notifications">');
            $mform->addElement( 'checkbox', 'expiration_editor_checkbox1', get_string('enable_student', 'local_learningpaths') );
            $mform->addElement( 'editor', 'expiration_editor');   
            $mform->addElement('html', '<p class="col-sm-12 col-md-12 col-lg-12 col-xl-12 tag_desc">'.get_string("tags_desciption", "local_learningpaths").'</p>');
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '</div>');

            $mform->addElement( 'hidden', 'cron_lp_enreminder', '', []);
            $mform->setDefault('cron_lp_enreminder', '1');

            $mform->addElement('html', '<div id="form_id"  class="row">');
                
            $mform->addElement('html', '<div class="col-md-2 txt_area_txt">');
            $text=$mform->addElement('static', '',get_string('enrollment_reminder', 'local_learningpaths'));
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-2 txt_area_txt2">');
                    
            $mform->addElement('html', '<div class="col-md-12">');
            $mform->addElement('static', 'description', get_string('enrollment_notification', 'local_learningpaths'));
            $mform->addElement('html', '</div>');
            
            $mform->addElement('html', '<div class="col-md-12">');
            $mform->addElement('static', 'description', get_string('days_after', 'local_learningpaths'));
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-12 email_template_position2">');
            $mform->addElement('static', 'description', get_string('email_template', 'local_learningpaths'));
            $mform->addElement('html', '</div>');
                
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-8 txt_area_notifications enrollment-reminder left_container">');
            $mform->addElement( 'checkbox', 'enreminder_editor_checkbox1', get_string('enable_student', 'local_learningpaths') );
            $mform->addElement( 'text', 'enreminder_editor_text', get_string('days_after', 'local_learningpaths'));
            $mform->addElement( 'editor', 'enreminder_editor');  
            $mform->addElement('html', '<p class="col-sm-12 col-md-12 col-lg-12 col-xl-12 tag_desc">'.get_string("tags_desciption", "local_learningpaths").'</p>');
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '</div>');

            $mform->addElement( 'hidden', 'cron_lp_exreminder', '', []);
            $mform->setDefault('cron_lp_exreminder', '1');

            $mform->addElement('html', '<div id="form_id"  class="row">');
                
            $mform->addElement('html', '<div class="col-md-2 txt_area_txt">');
            $text=$mform->addElement('static', '',get_string('expiration_reminder', 'local_learningpaths'));
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-2 txt_area_txt2">');
                    
            $mform->addElement('html', '<div class="col-md-12">');
            $mform->addElement('static', 'description', get_string('enrollment_notification', 'local_learningpaths'));
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-12">');
            $mform->addElement('static', 'description', get_string('days_before_expiration', 'local_learningpaths'));
            $mform->addElement('html', '</div>');
              
            $mform->addElement('html', '<div class="col-md-12 email_template_position2">');
            $mform->addElement('static', 'description', get_string('email_template', 'local_learningpaths'));
            $mform->addElement('html', '</div>');
                
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-8 txt_area_notifications enrollment-reminder left_container">');
            $mform->addElement( 'checkbox', 'exreminder_editor_checkbox1', get_string('enable_student', 'local_learningpaths') );
            $mform->addElement( 'text', 'exreminder_editor_text', get_string('days_before_expiration', 'local_learningpaths'));
            $mform->addElement( 'editor', 'exreminder_editor'); 
            $mform->addElement('html', '<p class="col-sm-12 col-md-12 col-lg-12 col-xl-12 tag_desc">'.get_string("tags_desciption", "local_learningpaths").'</p>');
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '</div>');

            /** completion reminder start */
            $mform->addElement( 'hidden', 'cron_lp_completion_reminder', '', []);
            $mform->setDefault('cron_lp_completion_reminder', '1');

            $mform->addElement('html', '<div id="completion_reminder_form"  class="row">');
                
            $mform->addElement('html', '<div class="col-md-2 txt_area_txt">');
            $text=$mform->addElement('static', '',get_string('completion_reminder', 'local_learningpaths'));
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-2 txt_area_txt2">');
                    
            $mform->addElement('html', '<div class="col-md-12">');
            $mform->addElement('static', 'description', get_string('enrollment_notification', 'local_learningpaths'));
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-12">');
            $mform->addElement('static', 'description', get_string('day_frequency', 'local_learningpaths'));
            $mform->addElement('html', '</div>');
              
            $mform->addElement('html', '<div class="col-md-12 email_template_position2">');
            $mform->addElement('static', 'description', get_string('email_template', 'local_learningpaths'));
            $mform->addElement('html', '</div>');
                
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-8 txt_area_notifications enrollment-reminder left_container">');
            $mform->addElement( 'checkbox', 'completion_reminder_editor_checkbox', get_string('enable_student', 'local_learningpaths') );
            $attributes = array( 'onkeypress' => 'return onlyNumberKey(event)', 'maxlength' => '2');
            $mform->addElement( 'text', 'completion_reminder_editor_text', get_string('days_before_expiration', 'local_learningpaths'), $attributes);
            $mform->addElement( 'editor', 'completion_reminder_editor'); 
            $mform->addElement('html', '<p class="col-sm-12 col-md-12 col-lg-12 col-xl-12 tag_desc">'.get_string("tags_desciption", "local_learningpaths").'</p>');
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '</div><script> 
                function onlyNumberKey(evt) { 
                    console.log("type");
                    // Only ASCII charactar in that range allowed 
                    var ASCIICode = (evt.which) ? evt.which : evt.keyCode 
                    if (ASCIICode > 31 && (ASCIICode < 48 || ASCIICode > 57)) 
                        return false; 
                    return true; 
                } 
            </script>');
            /** completion reminder end */

            $mform->addElement( 'hidden', 'cron_lp_notifications', '', []);
            $mform->setDefault('cron_lp_notifications', '1');

            $mform->addElement('html', '<div id="form_id"  class="row">');
                
            $mform->addElement('html', '<div class="col-md-2 txt_area_txt">');
            $text=$mform->addElement('static', '',get_string('path_completion', 'local_learningpaths'));
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-2 txt_area_txt2">');
                    
            $mform->addElement('html', '<div class="col-md-12">');
            $mform->addElement('static', 'description', get_string('enrollment_notification', 'local_learningpaths'));
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-12 email_template_position">');
            $mform->addElement('static', 'description', get_string('email_template', 'local_learningpaths'));
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-md-8 txt_area_notifications">');
            $mform->addElement( 'checkbox', 'notifications_editor_checkbox1', get_string('enable_student', 'local_learningpaths') );
            $mform->addElement( 'editor', 'notifications_editor');
            $mform->addElement('html', '<p class="col-sm-12 col-md-12 col-lg-12 col-xl-12 tag_desc">'.get_string("tags_desciption", "local_learningpaths").'</p>');
            $mform->addHelpButton('notifications_editor', 'message_template','local_learningpaths');
            $this->add_action_buttons(true, get_string('savechanges'),$backtocourse);
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '</div>');
            $mform->addElement('html', '</div>');	
    }

    // Perform some extra moodle validation
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if($data['enreminder_editor_checkbox1'] == true && empty($data['enreminder_editor_text'])) {
             $errors['enreminder_editor_text'] ="This field is required!";
        }
        if($data['exreminder_editor_checkbox1'] == true && empty($data['exreminder_editor_text'])) {
             $errors['exreminder_editor_text'] ="This field is required!";
        }
        if($data['completion_reminder_editor_checkbox'] == true && empty($data['completion_reminder_editor_text'])) {
             $errors['completion_reminder_editor_text'] ="This field is required!";
        }

        return $errors;
    }
}

// form notifications expiration in the learning path
class lp_expiration extends moodleform
{
	public function definition() {
        global $CFG , $DB, $COURSE;
    	$lpid = optional_param('id', 0, PARAM_INT);

    	$mform = $this->_form;
        $attributes = [
            'autocomplete' => 'off',
            'action' => $CFG->wwwroot.'/local/learningpaths/view.php?id='.$lpid.'#learningpath-notifications-tab',
            'method' => 'post',
            'accept-charset' => 'utf-8',
            'id' => 'mform13',
            'class' => 'mform'
        ];
        $mform->setAttributes($attributes);

    }
}

// form notifications enrollment reminder in the learning path
class lp_enreminder extends moodleform
{
	public function definition() {

    	global $CFG , $DB, $COURSE;
    	$lpid = optional_param('id', 0, PARAM_INT);

    	$mform = $this->_form;
        $attributes = [
            'autocomplete' => 'off',
            'action' => $CFG->wwwroot.'/local/learningpaths/view.php?id='.$lpid.'#learningpath-notifications-tab',
            'method' => 'post',
            'accept-charset' => 'utf-8',
            'id' => 'mform13',
            'class' => 'mform'
        ];
        $mform->setAttributes($attributes);
    }      
}

// form notifications expiration reminder in the learning path
class lp_exreminder extends moodleform
{
	public function definition() {

    	global $CFG , $DB, $COURSE;
    	$lpid = optional_param('id', 0, PARAM_INT);

    	$mform = $this->_form;
        $attributes = [
            'autocomplete' => 'off',
            'action' => $CFG->wwwroot.'/local/learningpaths/view.php?id='.$lpid.'#learningpath-notifications-tab',
            'method' => 'post',
            'accept-charset' => 'utf-8',
            'id' => 'mform13',
            'class' => 'mform'
        ];
        $mform->setAttributes($attributes);
    }      
}

// Accordion notifications path completion in the learning path
class lp_notifications extends moodleform
{
	public function definition() {

    	global $CFG , $DB, $COURSE;
    	$lpid = optional_param('id', 0, PARAM_INT);

    	$mform = $this->_form;
        $attributes = [
            'autocomplete' => 'off',
            'action' => $CFG->wwwroot.'/local/learningpaths/view.php?id='.$lpid.'#learningpath-notifications-tab',
            'method' => 'post',
            'accept-charset' => 'utf-8',
            'id' => 'mform13',
            'class' => 'mform'
        ];
        $mform->setAttributes($attributes);
    }

    public function add_action_buttons($cancel = true, $submitlabel = null ,$urlback=null) {
        $mform =& $this->_form;
        $buttonarray = array();

        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}

// Accordion notifications certificated issued in the learning path
class lp_certificated extends moodleform
{
	public function definition() {

    	global $CFG , $DB, $COURSE;
    	$lpid = optional_param('id', 0, PARAM_INT);

    	$mform = $this->_form;
        $attributes = [
            'autocomplete' => 'off',
            'action' => $CFG->wwwroot.'/local/learningpaths/view.php?id='.$lpid.'#learningpath-notifications-tab',
            'method' => 'post',
            'accept-charset' => 'utf-8',
            'id' => 'mform13',
            'class' => 'mform'
        ];
        $mform->setAttributes($attributes);
        
    	$mform->addElement( 'checkbox', 'certificated_editor_checkbox1', get_string('enable_student', 'local_learningpaths'));
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