<?php
/**
 * Slideshow form.
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class slideshow_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $id= '';
        if(!empty($SESSION->currenteditingcompany)){
            $id = $SESSION->currenteditingcompany;
        }else if(\iomad::is_company_user()){
            $id = \iomad::is_company_user();
        }  
        

       $mform = $this->_form; 
       $filemanageroptions = array('maxbytes'       => $CFG->maxbytes,
                             'subdirs'        => 0,
                             'maxfiles'       => 1,
                             'accepted_types' => array('.png', '.jpg'));
		 					 
        
       
        $mform->addElement('checkbox', 'showslideshow_'.$id, get_string('act_slideshow','local_tenant_appearance'), get_string('defaultno','local_tenant_appearance'));
	    $mform->addElement('html', '<div class="form-group row fitem">
			<div class="contentlabel col-sm-12 col-md-4 col-lg-4 col-xl-4">
			
			</div>
			<div class=" col-sm-12 col-md-8 col-lg-8 col-xl-8 felement">
			<span class="">'.get_string('act_desc', 'local_tenant_appearance').'</span>
			
			</div>
			</div>');
	   
		$mform->addElement('html', '
			<div class="col-sm-12 col-md-12 col-lg-12 col-xl-12 ">
			<span class=""><h5>'.get_string('Slide1', 'local_tenant_appearance').'</h5></span>
			</div>
			');
	    $mform->addElement('html', '<hr/>');
	    $mform->addElement('html', '
			<div class="col-sm-12 col-md-12 col-lg-12 col-xl-12 ">
			<span class="">'.get_string('Slide1details', 'local_tenant_appearance').'</span>
			</div>
			');
		$mform->addElement('html', '<br/>');
	    $mform->addElement('text', 'slide1title_'.$id, get_string('slide_title','local_tenant_appearance'), null,$attributes
            );
	  
	    $mform->addElement('editor', 'slide1content_'.$id, get_string('description','local_tenant_appearance'), null
            );
	   
	    $mform->addElement('filemanager', 'slide1image_'.$id, get_string('slide_image','local_tenant_appearance'), null, $filemanageroptions);
		
	    $mform->addElement('html', '
			<div class="col-sm-12 col-md-12 col-lg-12 col-xl-12 ">
			<span class=""><h5>'.get_string('slide2', 'local_tenant_appearance').'</h5></span>
			</div>
			');
					
	    $mform->addElement('html', '<hr/>');
	    $mform->addElement('html', '
			<div class="col-sm-12 col-md-12 col-lg-12 col-xl-12 ">
			<span class="">'.get_string('Slide2details', 'local_tenant_appearance').'</span>
			</div>
			');
		$mform->addElement('html', '<br/>');
		$mform->addElement('text', 'slide2title_'.$id, get_string('slide_title','local_tenant_appearance'), null,$attributes
            );
	    $mform->addElement('editor', 'slide2content_'.$id, get_string('description','local_tenant_appearance'), null
            );
	    $mform->addElement('filemanager', 'slide2image_'.$id, get_string('slide_image','local_tenant_appearance'), null, $filemanageroptions);
		
		$mform->addElement('html', '
			<div class="col-sm-12 col-md-12 col-lg-12 col-xl-12 ">
			<span class=""><h5>'.get_string('slide3', 'local_tenant_appearance').'</h5></span>
			</div>
			');
					
		$mform->addElement('html', '<hr/>');
		$mform->addElement('html', '
			<div class="col-sm-12 col-md-12 col-lg-12 col-xl-12 ">
			<span class="">'.get_string('slide3details', 'local_tenant_appearance').'</span>
			</div>
			');
		$mform->addElement('html', '<br/>');
		$mform->addElement('text', 'slide3title_'.$id, get_string('slide_title','local_tenant_appearance'), null,$attributes);
	    
	    $mform->addElement('editor', 'slide3content_'.$id, get_string('description','local_tenant_appearance'), null);
	    
	    $mform->addElement('filemanager', 'slide3image_'.$id, get_string('slide_image','local_tenant_appearance'), null, $filemanageroptions);
		
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
