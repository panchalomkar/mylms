<?php
// This file is part of Moodle - http://moodle.org/
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
 * This block allows the user to give the course a rating, which
 * is displayed in a custom table (<prefix>_block_rate_course).
 *
 * @package    recent_access
 * @subpackage reportes
 * @copyright  2021 elearningstack.com 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');


class asg_cat_form extends moodleform {

    public function definition() {
        global $PAGE, $SESSION, $DB;

        if (!empty($SESSION->currenteditingcompany)) {
            $selectedcompany = $SESSION->currenteditingcompany;
        } else if (!empty($USER->profile->company)) {
            $usercompany = company::by_userid($USER->id);
            $selectedcompany = $usercompany->id;
        } else {
            $selectedcompany = 0;
        }
        $mform =& $this->_form;
       // $searchareas = \core\core_course_category::get(true);
       if ($selectedcompany) {
        $categorydata = $DB->get_records_sql("SELECT * FROM {company} c 
        INNER JOIN {course_categories} cc ON c.category = cc.id WHERE c.id = $selectedcompany");
        $categories = array();
        foreach ($categorydata as $key => $value) {
            $categories[$key] = $value->name;
        }
      }else{
        $categories = \core_course_category::make_categories_list();
      }
                                                       
         $areanames = array();                                                                                                       
        foreach ($categories as  $key => $value) {                                                                          
            $areanames[$key] = $value;                                                                  
        }                                                                                                                           
        $options = array(                                                                                                           
            'multiple' => false,                                                  
            'noselectionstring' => get_string('allcat', 'local_mydashboard'),                                                                
        );         
        $mform->addElement('select', 'categoryid', get_string('selectcategory', 'local_mydashboard'), $areanames, $options);
        $mform->addRule('categoryid', 'This field is required', 'required', null, 'client', false, false);

        $attributes=array('size'=>'20');
        $mform->addElement('text', 'assignpoint', get_string('assignpoint', 'local_mydashboard'), $attributes);
        $mform->addRule('assignpoint', get_string('pleaseenternumeric', 'local_mydashboard'), 'numeric', null, 'client', false, false);
        $mform->addRule('assignpoint', 'This field is required', 'required', null, 'client', false, false);
        $this->add_action_buttons(false,  get_string('addpoint','local_mydashboard'));
    }
}