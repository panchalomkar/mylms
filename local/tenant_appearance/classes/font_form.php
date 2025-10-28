<?php
/**
 * Font form.
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class font_form extends moodleform {

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
	    $fontuploads = array();
	    $fontnameval = $DB->get_records_sql("SELECT * FROM {font_upload_setting} WHERE companyid = $id");
        	foreach($fontnameval as $row) {
            $fontuploads["id_".$row->id] =  $row->font_family." - ".$row->font_type;
        	} 

	    $fontoptions = array(
		'Poppins'                                    => 'Poppins',
		'"Roboto","Helvetica","Arial",sans-serif'    => '"Roboto","Helvetica","Arial",sans-serif',
		'"Courier New", Courier, monospace'          => '"Courier New", Courier, monospace' ,
		'"Lucida Console", Monaco, monospace'        => '"Lucida Console", Monaco, monospace',
		'"Comic Sans MS", cursive, sans-serif'       => '"Comic Sans MS", cursive, sans-serif',
	   );
	    $fontmerge = array_merge($fontoptions,$fontuploads);
	    $mform->addElement('select', 'fontnametheme_'.$id, get_string('color_font_name','local_tenant_appearance'), $fontmerge);
	    $fontnametheme='fontnametheme_'.$id;
	    $mform->setType($fontnametheme, PARAM_TEXT);
			
        $mform->addElement('html', '<div class="form-group row fitem">
			<div class="contentlabel col-sm-12 col-md-4 col-lg-4 col-xl-4">
			</div>
			<div class=" col-sm-12 col-md-8 col-lg-8 col-xl-8 felement">
			<span class="">'.get_string('font_name_desc', 'local_tenant_appearance').'</span>
			<span class=""><a href="'.$CFG->wwwroot .'/local/tenant_appearance/font_upload_settings.php'.'">Click here</a></span>
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
