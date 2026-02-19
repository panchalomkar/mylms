<?php
/**
 * Color picker form.
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class color_form extends moodleform {

    public function definition() {
        global $CFG, $DB;
		//check current editing company
        $id= '';
        if(!empty($SESSION->currenteditingcompany)){
            $id = $SESSION->currenteditingcompany;
        }else if(\iomad::is_company_user()){
            $id = \iomad::is_company_user();
        }  
      

       $mform = $this->_form; 
	   $mform->addElement('html', '
			<div class="col-sm-12 col-md-12 col-lg-12 col-xl-12 ">
			<span class="">'.get_string('color_desc', 'local_tenant_appearance').'</span>
			</div>
			');
			$mform->addElement('html','<br/>');
	   $mform->addElement('text', 'brandprimary_'.$id, get_string('brand_primary','local_tenant_appearance'), array('id'=>'color1'));
	   $brandprimaray='brandprimary_'.$id;
	   $mform->setType($brandprimaray, PARAM_TEXT);
	  
	   $mform->addElement('html', '<div class="form-group row fitem">
			<div class="contentlabel col-sm-12 col-md-4 col-lg-4 col-xl-4">
			</div>
			<div class=" col-sm-12 col-md-8 col-lg-8 col-xl-8 felement">
			<span class="">'.get_string('brand_color', 'local_tenant_appearance').'</span>
			
			<span class="">'.get_string('default', 'local_tenant_appearance').'</span>
			</div>
			</div>');
	   
	  
	   
	   $mform->addElement('text', 'bodybackground_'.$id, get_string('body_background','local_tenant_appearance'), array('id'=>'color3'));
	   $bodyback='bodybackground_'.$id;
	   $mform->setType($bodyback, PARAM_TEXT);
	   
       $mform->addElement('html', '<div class="form-group row fitem">
		    <div class="contentlabel col-sm-12 col-md-4 col-lg-4 col-xl-4">
			</div>
			<div class=" col-sm-12 col-md-8 col-lg-8 col-xl-8 felement">
			<span class="">'.get_string('body_background_color_pages', 'local_tenant_appearance').'</span>
			<span class="">'.get_string('default', 'local_tenant_appearance').'</span>
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
        return array();
    }

	
	
}
