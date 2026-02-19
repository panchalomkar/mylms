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
* @package block_skilladd
* @category local
* @copyright  ELS <admin@elearningstack.com>
* @author eLearningstack
*/
require_once('../../config.php');
global $DB;
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');
class filterform_user extends moodleform {

    public function definition() {
       // $userid = optional_param('userid', 0, PARAM_INT);                                                        
        $dept = self::get_department_list();                                                                                                                            
        $options1 = array(                                                                                                           
            'multiple' => false,                                                  
            'noselectionstring' => get_string('alldept', 'block_skilladd'),                                                                
        ); 
        
        $skill = self::get_skill_list();                                                                                                                            
        $options2 = array(                                                                                                           
            'multiple' => false,                                                  
            'noselectionstring' => get_string('allskill', 'block_skilladd'),                                                                
        ); 

        $mform = $this->_form;
        $mform->addElement('autocomplete', 'department', get_string('department','block_skilladd'), $dept, $options1);
        $mform->addRule('department', get_string('requiredfield', 'block_skilladd'), 'required', null, 'client', false, false);
        $mform->addElement('autocomplete', 'skill', get_string('skill','block_skilladd'), $skill, $options2);
        $skillsarray = array(
            'level1' => 'Beginner',
            'level2' => 'Intermediate',
            'level3' => 'Advanced'
        );
        $select = $mform->addElement('select', 'skilllevel', get_string('skilllevel', 'block_skilladd'), $skillsarray);
        $select->setSelected('level1');

        $attributes=array('size'=>'20');
        $mform->addElement('text', 'position', get_string('position', 'block_skilladd'), $attributes);
        $mform->addElement('text', 'grade', get_string('grade', 'block_skilladd'), $attributes);

        $this->add_action_buttons(false,  get_string('submitbutton','block_skilladd'));
    }

    public function get_department_list(){
        global $USER, $CFG, $DB, $OUTPUT, $SESSION;
        if (!empty($SESSION->currenteditingcompany)) {
            $selectedcompany = $SESSION->currenteditingcompany;
        } else if (!empty($USER->profile->company)) {
            $usercompany = company::by_userid($USER->id);
            $selectedcompany = $usercompany->id;
        } else {
            $selectedcompany = "";
        }

        if ($selectedcompany) {
            $query = "WHERE company = $selectedcompany";
        }
        $urs = array();
        $urs[''] = get_string('alldept','block_skilladd');
        $users = $DB->get_records_sql("SELECT id,name as username FROM {department} $query");
        foreach ($users as $user ) {                                                                          
            $urs[$user->id] = $user->username;                                                                  
        }
        return $urs;
    }
    public function get_skill_list(){
        global $USER, $CFG, $DB, $OUTPUT, $SESSION;
        if (!empty($SESSION->currenteditingcompany)) {
            $selectedcompany = $SESSION->currenteditingcompany;
        } else if (!empty($USER->profile->company)) {
            $usercompany = company::by_userid($USER->id);
            $selectedcompany = $usercompany->id;
        } else {
            $selectedcompany = "";
        }

        if ($selectedcompany) {
            $query = "WHERE companyid = $selectedcompany";
        }
        $urs = array();
        $urs[''] = get_string('allskill','block_skilladd');
        $users = $DB->get_records_sql("SELECT id,name as username FROM {block_skilladd_items}  $query");
        foreach ($users as $user ) {                                                                          
            $urs[$user->id] = $user->username;                                                                  
        }
        return $urs;
    }
}
?>