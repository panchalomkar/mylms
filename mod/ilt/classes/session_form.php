<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Copyright (C) 2007-2011 Catalyst IT (http://www.catalyst.net.nz)
 * Copyright (C) 2011-2013 Totara LMS (http://www.totaralms.com)
 * Copyright (C) 2014 onwards Catalyst IT (http://www.catalyst-eu.net)
 *
 * @package    mod
 * @subpackage ilt
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @author     Alastair Munro <alastair.munro@totaralms.com>
 * @author     Aaron Barnes <aaron.barnes@totaralms.com>
 * @author     Francois Marier <francois@catalyst.net.nz>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/ilt/lib.php');

/* @Author VaibhavG
 * @desc include the ilt.js file to get classroom values according to it's location
 * @date 13Dec2018
 * Start code
 */
global $PAGE;
//$PAGE->requires->js(new moodle_url('/mod/ilt/js/ilt.js'));
$PAGE->requires->js_call_amd('mod_ilt/ilt', 'init');
/* @Author VaibhavG
 * @desc include the ilt.js file to get classroom values according to it's location
 * @date 13Dec2018
 * End Code
 */

class mod_ilt_session_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;
        $context = context_course::instance($this->_customdata['course']->id);

        // Course Module ID.
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);

        // ILT Instance ID.
        $mform->addElement('hidden', 'f', $this->_customdata['f']);
        $mform->setType('f', PARAM_INT);

        // ILT Session ID.
        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        // Copy Session Flag.
        $mform->addElement('hidden', 'c', $this->_customdata['c']);
        $mform->setType('c', PARAM_INT);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $editoroptions = $this->_customdata['editoroptions'];

        // Show all custom fields.
        $customfields = $this->_customdata['customfields'];
        ilt_add_customfields_to_form($mform, $customfields);

        // Hack to put help files on these custom fields.
        // TODO: add to the admin page a feature to put help text on custom fields.
        if ($mform->elementExists('custom_location')) {
            $mform->addHelpButton('custom_location', 'location', 'ilt');
        }
        if ($mform->elementExists('custom_venue')) {
            $mform->addHelpButton('custom_venue', 'venue', 'ilt');
        }
        if ($mform->elementExists('custom_room')) {
            $mform->addHelpButton('custom_room', 'room', 'ilt');
        }
        
        $formarray  = array(); 
        /*
        *@Author VaibhavG
        *@desc #10: ILT Custom work - Assign multiple instructors in ILT Session
        *@desc added instructor drop down to multiselect it. Added Location text field. Added classroom text field. Added cost center dropdown
        *@desc changed the form field order as per the suggesstion of Praveen Shukla 
        *@date 12Dec2018
        *@start code
        *@14Dec2018
        */
        $courseid = $this->_customdata['course']->id;
    
        
        //set session name
        $sessionname = $mform->addElement('text', 'sessionname', get_string('sessionname', 'ilt'));        
        $mform->addRule('sessionname', get_string('sessionname', 'ilt'), 'required', null);
        
        //set date
        $formarray[] = $mform->createElement('selectyesno', 'datetimeknown', get_string('sessiondatetimeknown', 'ilt'));
        $formarray[] = $mform->createElement('static', 'datetimeknownhint', '',
            html_writer::tag('span', get_string('datetimeknownhinttext', 'ilt'), array('class' => 'hint-text')));
        $mform->addGroup($formarray, 'datetimeknown_group', get_string('sessiondatetimeknown', 'ilt'), array(' '), false);
        $mform->addGroupRule('datetimeknown_group', null, 'required', null, 'client');
        $mform->setDefault('datetimeknown', false);
        $mform->addHelpButton('datetimeknown_group', 'sessiondatetimeknown', 'ilt');

        $repeatarray = array();
        $repeatarray[] = &$mform->createElement('hidden', 'sessiondateid', 0);
        $mform->setType('sessiondateid', PARAM_INT);
        $repeatarray[] = &$mform->createElement('date_time_selector', 'timestart', get_string('timestart', 'ilt'));
        $repeatarray[] = &$mform->createElement('date_time_selector', 'timefinish', get_string('timefinish', 'ilt'));
        $checkboxelement = &$mform->createElement('checkbox', 'datedelete', '', get_string('dateremove', 'ilt'));
        unset($checkboxelement->_attributes['id']); // Necessary until MDL-20441 is fixed.
        $repeatarray[] = $checkboxelement;
        $repeatarray[] = &$mform->createElement('html', html_writer::empty_tag('br')); // Spacer.

        $repeatcount = $this->_customdata['nbdays'];

        $repeatoptions = array();
        $repeatoptions['timestart']['disabledif'] = array('datetimeknown', 'eq', 0);
        $repeatoptions['timefinish']['disabledif'] = array('datetimeknown', 'eq', 0);
        $mform->setType('timestart', PARAM_INT);
        $mform->setType('timefinish', PARAM_INT);

        $this->repeat_elements($repeatarray, $repeatcount, $repeatoptions, 'date_repeats', 'date_add_fields',
                               1, get_string('dateadd', 'ilt'), true);

        //set duration, costing
        $mform->addElement('text', 'duration', get_string('duration', 'ilt'), 'size="5"');
        $mform->setType('duration', PARAM_TEXT);
        $mform->addHelpButton('duration', 'duration', 'ilt');

        if (!get_config(null, 'ilt_hidecost')) {
            $formarray  = array();
            $formarray[] = $mform->createElement('text', 'normalcost', get_string('normalcost', 'ilt'), 'size="5"');
            $formarray[] = $mform->createElement('static', 'normalcosthint', '', html_writer::tag('span',
                get_string('normalcosthinttext', 'ilt'), array('class' => 'hint-text')));
            $mform->addGroup($formarray, 'normalcost_group', get_string('normalcost', 'ilt'), array(' '), false);
            $mform->setType('normalcost', PARAM_TEXT);
            $mform->addHelpButton('normalcost_group', 'normalcost', 'ilt');

            if (!get_config(null, 'ilt_hidediscount')) {
                $formarray  = array();
                $formarray[] = $mform->createElement('text', 'discountcost', get_string('discountcost', 'ilt'), 'size="5"');
                $formarray[] = $mform->createElement('static', 'discountcosthint', '', html_writer::tag('span',
                    get_string('discountcosthinttext', 'ilt'), array('class' => 'hint-text')));
                $mform->addGroup($formarray, 'discountcost_group', get_string('discountcost', 'ilt'), array(' '), false);
                $mform->setType('discountcost', PARAM_TEXT);
                $mform->addHelpButton('discountcost_group', 'discountcost', 'ilt');
            }
        }
        
        //getting all values of current session
        $s = optional_param('s', 0, PARAM_INT); // ilt session ID.
        $f = optional_param('f', 0, PARAM_INT); // ilt form ID.
        
        $get_session = $DB->get_records_sql('SELECT b.id,s.location,s.classroom,s.capacity,s.bu,s.instructor,s.resource FROM mdl_ilt_sessions s JOIN mdl_local_bu b ON s.location = b.id WHERE s.id = ?',array($s));
        $mysession_array = array();
        $instructor = array();
        $resource = array();
        $capa = '';
        foreach($get_session as $mysession)
        {
            $key = $mysession->id;
            $loc =$mysession->location;
            $class = $mysession->classroom;
            $capa = $mysession->capacity;
            $costcenter = $mysession->costcenter;
            $instructor = $mysession->instructor;
            $resource = $mysession->resource;
        }
        //instructor alert box
        $mform->addElement('html', '<div class="alert alert-danger col-sm-12" style="display:none;font-size:20px;"></div>');        
        //getting instructor 
            $sql = "SELECT u.id, u.username, u.firstname , u.lastname ,u.email
                FROM mdl_course c 
                INNER JOIN mdl_context cx ON c.id = cx.instanceid 
                INNER JOIN mdl_role_assignments ra ON cx.id = ra.contextid AND ra.roleid = '3' 
                INNER JOIN mdl_user u ON ra.userid = u.id WHERE cx.contextlevel = '50' AND c.id= $courseid";
               $availableusers = $DB->get_records_sql($sql);
        $options = array();
        if(!empty($availableusers))
        {
            foreach($availableusers as $sessioninstructor)
            {
                $key = $sessioninstructor->id;
                $value =$sessioninstructor->firstname .' '. $sessioninstructor->lastname .' ['. $sessioninstructor->email.']';
                $options[$key] = $value;
            }
            $select = $mform->addElement('select', 'sessioninstructor', get_string('sessioninstructorsform','ilt'), $options);
            //$mform->addRule('sessioninstructor', get_string('sessioninstructorsform', 'ilt'), 'required', null);
            $select->setMultiple(true);
            if(!empty($instructor))
                $mform->getElement('sessioninstructor')->setSelected($instructor);
        }
        else
        {
           $options[] = get_string('sessioninstructornone', 'ilt');
           //if need link to redirect enroll user page then use below commented code
//           $mform->addElement('html', '<a href="'.$CFG->wwwroot.'/user/index.php?id='.$COURSE->id.'">');
//                   $select = $mform->addElement('select', 'sessioninstructor', get_string('sessioninstructorsform','ilt'), $options);
//           $mform->addElement('html', '</a>');            
           $select = $mform->addElement('select', 'sessioninstructor', get_string('sessioninstructorsform','ilt'), $options);
           $mform->disabledIf('sessioninstructor','sessioninstructor'); 
           //$mform->addRule('sessioninstructor', get_string('sessioninstructorsform', 'ilt'), 'required', null);
           $select->setMultiple(true);
        }
        
        //location alert box
        $mform->addElement('html', '<div class="alert alert-success col-sm-12" style="display:none;font-size:20px;">Already Booked Location</div>');
        
        //getting locations
        $locations = array();
        $locations[0] = 'Select Location';
        $venuemanangement_detail = $DB->get_records_sql('select id,location from mdl_local_bu');
        if(!empty($venuemanangement_detail))
        {
            foreach($venuemanangement_detail as $venue)
            {
                $key = $venue->id;
                $value =$venue->location;
                $locations[$key] = $value;
            }
            $selectlocation = $mform->addElement('select', 'sessionlocation', get_string('location','ilt'), $locations);
            //$mform->addRule('sessionlocation', get_string('sessionlocation', 'ilt'), 'required', null);
            $mform->setType('sessionlocation', PARAM_TEXT);
            if(!empty($mysession->id))
                $mform->getElement('sessionlocation')->setSelected($mysession->id);
        }
        else
        {
           $locations[0] = get_string('sessionlocationnone', 'ilt');
           $selectlocation = $mform->addElement('select', 'sessionlocation', get_string('location','ilt'), $locations);
           //$mform->addRule('sessionlocation', get_string('sessionlocation', 'ilt'), 'required', null);
           $mform->setType('sessionlocation', PARAM_TEXT);
        }
        
        //getting classroom
        //classroom alert box
        $mform->addElement('html', '<div id="classroom_alert" class="alert alert-info col-sm-12" style="display:none;font-size:20px;"></div>');       
        if($s)
        {
            $get_classroom = $DB->get_records_sql('SELECT c.id,c.classroom FROM mdl_local_classroom c WHERE c.locationid = ?',array($mysession->id));
            $myclassroom_array = array();
            foreach($get_classroom as $myclassroom)
            {
                $myclassroom_array[$myclassroom->id] = $myclassroom->classroom;
            }
            $selectsessionclassroom = $mform->addElement('select', 'classroom', get_string('sessclassroom','ilt'),$myclassroom_array);
            $mform->setType('classroom', PARAM_TEXT);
            if(!empty($mysession->classroom))
                $mform->getElement('classroom')->setSelected($mysession->classroom);
        }
        else 
        {
            $selectsessionclassroom = $mform->addElement('select', 'classroom', get_string('sessclassroom','ilt'));
            $mform->addRule('classroom', get_string('sessclassroom', 'ilt'), 'required', null);
            $mform->setType('classroom', PARAM_TEXT);
            if(!empty($mysession->classroom))
                $mform->getElement('classroom')->setSelected($mysession->classroom);   
        }
        
        // sign-up cancellation
         if (has_capability('mod/facetoface:configurecancellation', $context)) {
            $mform->addElement('advcheckbox', 'allowcancellations', get_string('allowcancellations', 'ilt'));
            $mform->setDefault('allowcancellations', $this->_customdata['ilt']->allowcancellationsdefault);
            $mform->addHelpButton('allowcancellations', 'allowcancellations', 'ilt');
        }
        
        //getting capacity
        if(!$capa)
            $cap = $mform->addElement('text', 'sessioncapacity', get_string('capacity', 'ilt'),array('readonly'=>'readonly'));
        else
            $cap = $mform->addElement('text', 'sessioncapacity', get_string('capacity', 'ilt'),array('readonly'=>'readonly','value'=>$mysession->capacity));
        
        //
        $mform->addElement('checkbox', 'allowoverbook', get_string('allowoverbook', 'ilt'));
        $mform->addHelpButton('allowoverbook', 'allowoverbook', 'ilt');
        
        //getting resource  
        if($s)
        { 
            $get_resource = $DB->get_records_sql('SELECT c.id,r.id,r.classroomid,r.resource,r.resourceqty FROM mdl_local_classroom c JOIN mdl_local_resource r ON c.id = r.classroomid WHERE r.classroomid  = ? ',array($mysession->classroom));
            $myclassroom_resource = array();
            foreach($get_resource as $myclassroomresource)
            {
                $key = $myclassroomresource->id;
                $value = $myclassroomresource->resource .' '. $myclassroomresource->resourceqty;
                $myclassroom_resource[$key] = $value;
            }
            $select = $mform->addElement('select', 'sessionresource', get_string('sessionresources','ilt'),$myclassroom_resource);
            $select->setMultiple(true);
            if(!empty($resource))
                $mform->getElement('sessionresource')->setSelected($resource);
        }
        if($f) 
        {
            $select = $mform->addElement('select', 'sessionresource', get_string('sessionresources','ilt'));
            //$mform->addRule('sessionresource', get_string('sessionresources', 'ilt'), 'required', null);
            $select->setMultiple(true);
            if(!empty($resource))
                $mform->getElement('sessionresource')->setSelected($resource);
        }

        $bunits = $DB->get_field('user_info_field', 'param1', array('shortname'=>'businessunit'));
        $bunits = explode(PHP_EOL,$bunits);
        $bu = array();
        foreach($bunits as $k=>$v){
            $bu[$v] = $v;
        }
       
        //getting cost center
         $select = $mform->addElement('select', 'sessioncostcenter', get_string('sessioncostcenter','ilt'),$bu);
        if(!empty($mysession->bu))
            $mform->getElement('sessioncostcenter')->setSelected($mysession->bu);
        /*
        * @Author VaibhavG
        * @desc #10: ILT Custom work - Assign multiple instructors in ILT Session
        * @desc 11Dec2018
        * @End code
        */

        $mform->addElement('editor', 'details_editor', get_string('details', 'ilt'), null, $editoroptions);
        $mform->setType('details_editor', PARAM_RAW);
        $mform->addHelpButton('details_editor', 'details', 'ilt');

        // Choose users for trainer roles.
        $rolenames = ilt_get_trainer_roles();

        if ($rolenames) {

            // Get current trainers.
            $currenttrainers = ilt_get_trainers($this->_customdata['s']);

            // Loop through all selected roles.
            $headershown = false;
            foreach ($rolenames as $role => $rolename) {
                $rolename = $rolename->name;

                // Attempt to load users with this role in this course.
                $usernamefields = get_all_user_name_fields(true);
                $rs = $DB->get_recordset_sql("
                    SELECT
                        u.id,
                        {$usernamefields}
                    FROM
                        {role_assignments} ra
                    LEFT JOIN
                        {user} u
                      ON ra.userid = u.id
                    WHERE
                        contextid = {$context->id}
                    AND roleid = {$role}
                ");

                if (!$rs) {
                    continue;
                }

                $choices = array();
                foreach ($rs as $roleuser) {
                    $choices[$roleuser->id] = fullname($roleuser);
                }
                $rs->close();

                // Show header (if haven't already).
                if ($choices && !$headershown) {
                    $mform->addElement('header', 'trainerroles', get_string('sessionroles', 'ilt'));
                    $headershown = true;
                }

                // If only a few, use checkboxes.
                if (count($choices) < 4) {
                    $roleshown = false;
                    foreach ($choices as $cid => $choice) {

                        // Only display the role title for the first checkbox for each role.
                        if (!$roleshown) {
                            $roledisplay = $rolename;
                            $roleshown = true;
                        } else {
                            $roledisplay = '';
                        }

                        $mform->addElement('advcheckbox', 'trainerrole[' . $role . '][' . $cid . ']', $roledisplay, $choice,
                            null, array('', $cid));
                        $mform->setType('trainerrole[' . $role . '][' . $cid . ']', PARAM_INT);
                    }
                } else {
                    $mform->addElement('select', 'trainerrole[' . $role . ']', $rolename, $choices,
                        array('multiple' => 'multiple'));
                    $mform->setType('trainerrole[' . $role . ']', PARAM_SEQUENCE);
                }

                // Select current trainers.
                if ($currenttrainers) {
                    foreach ($currenttrainers as $role => $trainers) {
                        $t = array();
                        foreach ($trainers as $trainer) {
                            $t[] = $trainer->id;
                            $mform->setDefault('trainerrole[' . $role . '][' . $trainer->id . ']', $trainer->id);
                        }

                        $mform->setDefault('trainerrole[' . $role . ']', implode(',', $t));
                    }
                }
            }
        }

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $dateids = $data['sessiondateid'];
        $dates = count($dateids);
        for ($i = 0; $i < $dates; $i++) {
            $starttime = $data["timestart"][$i];
            $endtime = $data["timefinish"][$i];
            $removecheckbox = empty($data["datedelete"]) ? array() : $data["datedelete"];
            if ($starttime > $endtime && !isset($removecheckbox[$i])) {
                $errstr = get_string('error:sessionstartafterend', 'ilt');
                $errors['timestart'][$i] = $errstr;
                $errors['timefinish'][$i] = $errstr;
                unset($errstr);
            }
        }

        if (!empty($data['datetimeknown'])) {
            $datefound = false;
            for ($i = 0; $i < $data['date_repeats']; $i++) {
                if (empty($data['datedelete'][$i])) {
                    $datefound = true;
                    break;
                }
            }

            if (!$datefound) {
                $errors['datetimeknown'] = get_string('validation:needatleastonedate', 'ilt');
            }
        }

        return $errors;
    }
}
