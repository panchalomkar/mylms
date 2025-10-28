<?php
use \moodleform;
use \context_system;
class control_form extends moodleform {

    public function definition() {
        global $DB, $OUTPUT;
        $mform =& $this->_form;
        
        $companyid = $this->_customdata['companyid'];
        $mform->addElement('hidden', 'companyid', $companyid, PARAM_INT);
        $companies = company::get_companies_select($showsuspendedcompanies);
        $companylist = [''=> get_string('select')];
        foreach ($companies as $id=> $company) {
            if(is_array($companies[$id])) {
                foreach ($companies[$id]['Colleges'] as $sid => $subcompany) {
                    $id = str_replace('-children', '', $id);
                    $companylist[$sid] = $companies[$id].'-'.$subcompany;
                }
            }else {
            $companylist[$id] = $company;
            }
        }

        $options = array(                                                                                                           
            'multiple' => false,                                                  
            'noselectionstring' => 'No selection',                                                                
        );         
       // $mform->addElement('autocomplete', 'companyids', get_string('selectcompanies', 'local_tenant_control'), $companylist, $options);
      //  $mform->addRule('companyids', get_string('required'), 'required', 'nonzero', 'client');
        $mform->addElement('header', 'coursecontrols', get_string('managecoursecontrol', 'local_tenant_control'));
        //$mform->addElement( 'html', html_writer::start_div('col-xs-12 col-sm-12 col-md-12 col-lg-12') );
        $mform->addElement( 'html', html_writer::start_div('row'));
        $mform->addElement( 'html', html_writer::start_div('col-xs-6 col-sm-4 col-md-6 col-lg-6') );
        $mform->addElement('advcheckbox', 'coursecreate', "<i class='fa fa-file-text' style=width:25px; ></i>&nbsp;". ucwords(get_string('createcourse', 'block_iomad_company_admin')), '<span class="checkmark"></span>', array('group' => 0, 'class' => 'customcheck'), array(1,0));
        $mform->addElement( 'html', html_writer::end_div());   
        $mform->addElement( 'html', html_writer::start_div('col-xs-6 col-sm-4 col-md-6 col-lg-6') );
        $mform->addElement('advcheckbox', 'coursedelete', "<i class='fa fa-remove' style=width:25px;></i>&nbsp;". ucwords(get_string('removecourse', 'local_tenant_control')), '<span class="checkmark"></span>', array('group' => 1), array(1, 0));
        $mform->addElement( 'html', html_writer::end_div()); 
        $mform->addElement( 'html', html_writer::end_div()); 

        $mform->addElement( 'html', html_writer::start_div('row'));
        $mform->addElement( 'html', html_writer::start_div('col-xs-6 col-sm-4 col-md-6 col-lg-6') );
        $mform->addElement('advcheckbox', 'learningpath', "<i class='fa fa-map-signs' style=width:25px;></i>&nbsp;". ucwords(get_string('learningpaths', 'local_iomad_learningpath')), '<span class="checkmark"></span> ', array('group' => 1), array(1, 0));
        $mform->addElement( 'html', html_writer::end_div());  
        $mform->addElement( 'html', html_writer::start_div('col-xs-6 col-sm-4 col-md-6 col-lg-6') );
        $mform->addElement('advcheckbox', 'program', "<i class='fa fa-plus' style=width:25px;></i>&nbsp;". ucwords(get_string('pluginname', 'local_learningpaths')), ' <span class="checkmark"></span>', array('group' => 1), array(1, 0));
        $mform->addElement( 'html', html_writer::end_div());  
        $mform->addElement( 'html', html_writer::end_div());  

         
        //$mform->addElement( 'html', html_writer::end_div());    
        //$mform->addElement( 'html', html_writer::end_div());
        $html = '';
        $modules = $DB->get_records('modules', array('visible' => 1), 'name ASC');
        $x = 1;
        $z = 1;
	foreach ($modules as $module) {
if($module->name == 'hpactivity'){

continue;
}
                   // Check if $x is bigger than 3 then we set it back to 1
              $x = ($x > 2) ? 1 : $x;                // if $x = 1 then we start a new row
              
              if($x == 1) {
                $mform->addElement( 'html', html_writer::start_div('row') );
              }
             
               //$html .='<div class="col-md-4 col-sm-6 col-xs-12">';
               $mform->addElement( 'html', html_writer::start_div('col-md-6 col-sm-6 col-xs-12') );
              //$mform->addElement( 'html', html_writer::start_div('col-xs-12 col-sm-12 col-md-12 col-lg-12') );
              //$html .= get_string('pluginname', "mod_{$module->name}"). '<input type="checkbox" name= '.$module->name.'>';
              //$mform->addElement('html',"<img src=\"" . $OUTPUT->image_url('icon', $module->name) . "\" class=\"icon\" alt=\"\" />"); 
              $mform->addElement('advcheckbox', $module->name, "<img style='margin:0px ;max-width:25px; width:40px;' src=\"" . $OUTPUT->image_url('icon', $module->name) . "\" class=\"icon\" alt=\"\" />&nbsp;" .get_string('create', 'local_tenant_control') . ' ' . get_string('pluginname', "mod_{$module->name}"), '<span class="checkmark"></span> ', array('group' => 1), array(1, 0));
               $mform->addElement( 'html', html_writer::end_div());
               // $html .='</div>';
               // Check if $x is equal to 3 or if $z equal to the total of the items in the repeater
               // then its true we close the row
              if(($x == 2)) {
             $mform->addElement( 'html', html_writer::end_div());
              }
               $x++;
             $z++;
        /*$mform->addElement( 'html', html_writer::start_div('col-xs-12 col-sm-12 col-md-12 col-lg-12') );
        $mform->addElement( 'html', html_writer::start_div('col-xs-6 col-sm-4 col-md-6 col-lg-6') );
        $mform->addElement('advcheckbox', $module->name, 'Create '. get_string('pluginname', "mod_{$module->name}"), ' ', array('group' => 1), array(1, 0));
        $mform->addElement( 'html', html_writer::end_div());    
        $mform->addElement( 'html', html_writer::end_div());*/
            
        }
        // $mform->addElement( 'html', html_writer::start_div('row'));
        // $mform->addElement( 'html', html_writer::start_div('col-xs-6 col-sm-4 col-md-6 col-lg-6') );
        // $mform->addElement('advcheckbox', 'proctoring',  "<i class='fa fa-camera'></i>&nbsp;". ucwords(get_string('proctoring', 'local_tenant_control')), '<span class="checkmark"></span> ', array('group' => 1), array(1, 0));
        // $mform->addElement( 'html', html_writer::end_div()); 
       // $mform->addElement( 'html', html_writer::end_div()); 

        $mform->addElement('html', $html);
        
        $mform->addElement('header', 'usercontrols', get_string('manageusercontrols', 'local_tenant_control'));
        $mform->addElement('advcheckbox', 'usercreate', "<i class='fa fa-user-plus'></i>&nbsp;". ucwords(get_string('createuser', 'block_iomad_company_admin')), '<span class="checkmark"></span> ', array('group' => 1), array(1, 0));
        $mform->addElement('advcheckbox', 'useredit', "<i class='fa fa-user-times'></i>&nbsp;". ucwords(get_string('edituser', 'block_iomad_company_admin')), '<span class="checkmark"></span> ', array('group' => 1), array(1, 0));
        
        $mform->addElement('header', 'managetabvisibltiycontrols', get_string('managetabvisibltiycontrols', 'local_tenant_control'));
        $mform->addElement('advcheckbox', 'competencies', "<i class='fa fa-list'></i>&nbsp;". ucwords(get_string('competencymanagement', 'block_iomad_company_admin')), '<span class="checkmark"></span> ', array('group' => 1), array(1, 0));
        $mform->addElement('advcheckbox', 'reports', "<i class='fa fa-bar-chart'></i>&nbsp;".ucwords('Manage Reports'), '<span class="cd ."></span> ', array('group' => 1), array(1, 0));
        $mform->addElement('advcheckbox', 'licenses', "<i class='fa fa-legal'></i>&nbsp;". ucwords(get_string('licensemanagement', 'block_iomad_company_admin')), '<span class="checkmark"></span> ', array('group' => 1), array(1, 0));
        $mform->addElement('advcheckbox', 'ecommerce', "<i class='fa fa-cart-arrow-down'></i>&nbsp;". ucwords(get_string('ecommerce', 'local_tenant_control')), '<span class="checkmark"></span> ', array('group' => 1), array(1, 0));

        /*$mform->addElement('header', 'allowedactivities', get_string('manageactivitiescontrols', 'local_tenant_control'));
        $modules = $DB->get_records('modules', array('visible' => 1), 'name ASC');
        
        foreach ($modules as $module) {
            $mform->addElement('advcheckbox', $module->name, get_string('pluginname', "mod_{$module->name}"), ' ', array('group' => 1), array(1, 0));
        }*/
    
        $this->add_action_buttons();
    }
}    
