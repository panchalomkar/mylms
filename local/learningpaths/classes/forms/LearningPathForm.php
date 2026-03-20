<?php
defined('MOODLE_INTERNAL') || die;

// Global vars definition.
global $CFG, $PAGE;
require_once("{$CFG->libdir}/formslib.php");
require_once("{$CFG->dirroot}/local/properties/lpproperties.php");
$PAGE->requires->string_for_js('mandatory_msg', 'local_learningpaths');
class LearningPathForm extends moodleform
{
    public function definition() {
        global $PAGE;
        $maxbytes='';
        $mform = $this->_form;
        
       if ($mform->elementExists('general_info')) {
    $mform->setExpanded('general_info', true);
}

        // Following hidden field is important to know which form is being submit.
        $mform->addElement('hidden', 'form', "LearningPathForm");

        // If exist custom id add custom element with that value.
        if (isset($this->_customdata['id'])) {
            $mform->addElement('hidden', 'id', $this->_customdata['id']);
        }

        $mform->addElement('text', 'name', get_string('learning_pathname', 'local_learningpaths'));
        $mform->addHelpButton('name', 'name', 'local_learningpaths');

        // Credits field.
        $mform->addElement('text', 'credits', get_string('credits', 'local_learningpaths'));
        $mform->addHelpButton('credits', 'credits', 'local_learningpaths');
        
        $mform->disabledIf('startdate', 'enable_startdate', 'notchecked');
        $mform->addElement('checkbox', 'enable_startdate', get_string('enable_startdate', 'local_learningpaths'));
        // Start date element.
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'form-group row  fitem adddates')));
        
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'contentlabel col-sm-12 col-md-4 col-lg-4 col-xl-4')));
                $mform->addElement('html', html_writer::label(get_string('startdate', 'local_learningpaths'),'id_startdate' ,array('class' => 'col-form-label  d-inline-block')));
                $mform->addElement('html', html_writer::start_tag('div', array('class' => 'options d-inline float-right')));
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'btn btn-secondary p-a-0 buttonhelp',"data-container"=>"body", "data-toggle"=>"popover", "data-placement"=>"right", "data-content"=>"<div class=&quot;no-overflow&quot;><p>".get_string('startdatehelpicon', 'local_learningpaths')."</p></div> ", "data-html"=>"true", "tabindex"=>"0", "data-trigger"=>"focus")));
                        $mform->addElement('html', html_writer::tag('i','', array("class"=>"fa edw-icon edw-icon-Help text-info fa-fw", "aria-hidden"=>"true")));
                    $mform->addElement('html', html_writer::end_tag('div'));
 
                $mform->addElement('html', html_writer::end_tag('div'));
            $mform->addElement('html', html_writer::end_tag('div'));
            
            $mform->addElement('html', html_writer::start_tag('div', array('class' => ' col-sm-12 col-md-8 col-lg-8 col-xl-8 felement')));
                $mform->addElement('html', html_writer::start_tag('div', array('class' => 'input-group mb-3')));
                $mform->addElement('html', html_writer::empty_tag('input',array('type' => 'text','class' => 'form-control','name'=>'startdate','id'=>'id_startdate','value'=>'')));
                $mform->addElement('html', html_writer::start_tag('div', array('class' => 'input-group-append')));
                    $mform->addElement('html', html_writer::start_tag('span', array('class' => 'input-group-text')));
                        $mform->addElement('html', html_writer::tag('i','', array("class"=>"fa fa-calendar")));
                    $mform->addElement('html', html_writer::end_tag('span'));
                $mform->addElement('html', html_writer::end_tag('div'));
                $mform->addElement('html', html_writer::end_tag('div'));
            $mform->addElement('html', html_writer::end_tag('div'));
            
        $mform->addElement('html', html_writer::end_tag('div'));
         
       $mform->disabledIf('enddate', 'enable_enddate', 'notchecked');
        $mform->addElement('checkbox', 'enable_enddate', get_string('enable_enddate', 'local_learningpaths'));
         
        // End date element.
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'form-group row  fitem adddates')));
            
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'contentlabel col-sm-12 col-md-4 col-lg-4 col-xl-4')));
                $mform->addElement('html', html_writer::label(get_string('enddate', 'local_learningpaths'),'id_startdate' ,array('class' => 'col-form-label  d-inline-block')));
                $mform->addElement('html', html_writer::start_tag('div', array('class' => 'options d-inline float-right')));
                $mform->addElement('html', html_writer::start_tag('div', array('class' => 'btn btn-secondary p-a-0 buttonhelp',"data-container"=>"body", "data-toggle"=>"popover", "data-placement"=>"right", "data-content"=>"<div class=&quot;no-overflow&quot;><p>".get_string('enddatehelpicon', 'local_learningpaths')."</p></div> ", "data-html"=>"true", "tabindex"=>"0", "data-trigger"=>"focus")));
                        $mform->addElement('html', html_writer::tag('i','', array("class"=>"fa edw-icon edw-icon-Help text-info fa-fw", "aria-hidden"=>"true")));
                    $mform->addElement('html', html_writer::end_tag('div'));
                $mform->addElement('html', html_writer::end_tag('div'));
            $mform->addElement('html', html_writer::end_tag('div'));
            
            $mform->addElement('html', html_writer::start_tag('div', array('class' => ' col-sm-12 col-md-8 col-lg-8 col-xl-8 felement')));
                $mform->addElement('html', html_writer::start_tag('div', array('class' => 'input-group mb-3')));
                    $mform->addElement('html', html_writer::empty_tag('input' ,array('type' => 'text','class' => 'form-control','name'=>'enddate','id'=>'id_enddate','value'=>'')));
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'input-group-append')));
                        $mform->addElement('html', html_writer::start_tag('span', array('class' => 'input-group-text')));
                            $mform->addElement('html', html_writer::tag('i','', array("class"=>"fa fa-calendar")));
                        $mform->addElement('html', html_writer::end_tag('span'));
                    $mform->addElement('html', html_writer::end_tag('div'));
                $mform->addElement('html', html_writer::end_tag('div'));
            $mform->addElement('html', html_writer::end_tag('div'));
            
        $mform->addElement('html', html_writer::end_tag('div'));
         
        
        $mform->addElement('hidden', 'lp_startdate', "", array('id'=>'lp_startdate'));
        $mform->addElement('hidden', 'lp_enddate', "", array('id'=>'lp_enddate'));

        // Learningpath description.
        //$mform->addElement('editor', 'description', get_string('description'));
        $mform->addElement('editor', 'description', get_string('description'), null,array('maxfiles' => EDITOR_UNLIMITED_FILES,
        'noclean' => true, 'context' => $this->context, 'subdirs' => true));
        
        $mform->addHelpButton('description', 'description', 'local_learningpaths');
        
        $mform->addElement('checkbox', 'self_enrollment', get_string('self_enrollment', 'local_learningpaths'));
        $mform->addHelpButton('self_enrollment', 'self_enrollment', 'local_learningpaths');

        $params = ['maxbytes' => $maxbytes, 'accepted_types' => ['.png', '.jpg', '.jpeg']];
        $title = get_string('learningpath_image', 'local_learningpaths');
        $mform->addElement('filepicker', 'learningpath_image', get_string('learningpath_image', 'local_learningpaths'));
        $mform->addHelpButton('learningpath_image', 'learningpath_image', 'local_learningpaths');

        
        // Add default data if that was send.
        if (isset($this->_customdata['name'])) {
            /*
            * @author VaibhavG.
            * @since 8th Feb 2021
            * @desc #492 VAPT Fixes. 
            * @desc #527 When new LP is created Description is unreadable.
            */
            $this->_customdata['name'] = htmlspecialchars_decode($this->_customdata['name']);
            $mform->setDefault('name', $this->_customdata['name']);
        }
        
        if (isset($this->_customdata['self_enrollment'])) {
            $mform->setDefault('self_enrollment', $this->_customdata['self_enrollment']);
        }

        if (isset($this->_customdata['description'])) {
            $description = json_decode($this->_customdata['description']);
            /*
            * @author VaibhavG.
            * @since 8th Feb 2021
            * @desc #492 VAPT Fixes. 
            * @desc #527 When new LP is created Description is unreadable.
            */
            $description->text = htmlspecialchars_decode($description->text);
            $mform->setDefault('description', $description);
        }

        if (isset($this->_customdata['credits'])) {
            $mform->setDefault('credits', $this->_customdata['credits']);
        }

        if (isset($this->_customdata['startdate'])) {
            $startdate =date('m/d/Y',$this->_customdata['startdate']);
            $mform->setDefault('lp_startdate', $startdate);
            $mform->setDefault('enable_startdate', 'checked');
        }

        if (isset($this->_customdata['enddate'])) {
            $enddate =date('m/d/Y',$this->_customdata['enddate']);
            $mform->setDefault('lp_enddate', $enddate);
            $mform->setDefault('enable_enddate', 'checked');
        }

        /*
         * Added by Sunita for learning path properties
         * Dated 27Feb 2018
         * 
         */
        
       lp_profile_definition($mform, $this->_customdata['id']);
       /* End of sunita changes*/
       
        // Action buttons.
        $this->add_action_buttons(true);

        // Validation rules.
        $mform->addRule('name', get_string('required_field', 'local_learningpaths'), 'required', null, 'client');
        $mform->addRule('description', get_string('required_field', 'local_learningpaths'), 'required', null, 'client');
        $mform->addRule('learningpath_image', get_string('required_field', 'local_learningpaths'), 'required', null, 'client');
        $mform->addRule('credits', get_string('credits_numeric', 'local_learningpaths'), 'numeric', '', 'client');
    }

    // Perform some extra moodle validation
    function validation($data, $files) {
        global $DB, $USER;
        $errors = parent::validation($data, $files);
        $url = new moodle_url('/local/learningpaths/');
        
        if(!empty($data['id'])) {
            if($DB->record_exists('learningpaths',array('name'=>trim(htmlspecialchars($data['name'])),'id'=>$data['id']))){
                return false;
            }else{
                if($DB->record_exists('learningpaths',array('name'=>trim(htmlspecialchars($data['name']))))){
                    redirect($url, get_string('namealreadyexist', 'local_learningpaths'), null, \core\output\notification::NOTIFY_ERROR);
                }  
            }
        }else{
            if(!empty($data['name'])) {
                if($DB->record_exists('learningpaths',array('name'=>trim(htmlspecialchars($data['name']))))){
                    redirect($url, get_string('namealreadyexist', 'local_learningpaths'), null, \core\output\notification::NOTIFY_ERROR);
                }   
             
            }
        }
       

        return $errors;
    }
}