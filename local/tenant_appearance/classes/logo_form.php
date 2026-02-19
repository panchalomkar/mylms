<?php
/**
 * Logo form.
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot. '/local/iomad/lib/iomad.php');

class logo_form extends moodleform {

    public function definition() {
        global $CFG, $DB;
        //check current editing company        
        $id= '';
        if(!empty($SESSION->currenteditingcompany)){
            $id = $SESSION->currenteditingcompany;
        }else if(\iomad::is_company_user()){
            $id = \iomad::is_company_user();
        }  
		
        
       $mform = $this->_form; // Don't forget the underscore! 
       $filemanageroptions = array('maxbytes'       => $CFG->maxbytes,
                             'subdirs'        => 0,
                             'maxfiles'       => 1,
                             'accepted_types' => array('.png', '.jpg'));
		 					 
       $mform->addElement('filemanager', 'tenant_logo_'.$id, get_string('logo','local_tenant_appearance'), null, $filemanageroptions);
	  
	   $mform->addElement('html', '<div class="form-group row fitem">
			<div class="contentlabel col-sm-12 col-md-4 col-lg-4 col-xl-4">
			
			</div>
			<div class=" col-sm-12 col-md-8 col-lg-8 col-xl-8 felement">
			<span class="">'.get_string('default', 'local_tenant_appearance').'</span><br/>
			<span class="">'.get_string('logo_desc', 'local_tenant_appearance').'</span>
			
			</div>
			</div>');
	  
	  
		 // Add action buttons.
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
                            get_string('save', 'local_tenant_appearance'));
        
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
		
    
    }

    //Custom validation should be added here
    function validation($data, $files) {
		global $DB, $CFG, $SESSION;
       $errors = parent::validation($data, $files);
	   return $errors;
    }

}
