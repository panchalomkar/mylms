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
* @package local_mydashboard
* @category local
* @copyright  ELS <admin@elearningstack.com>
* @author eLearningstack
*/
require_once('../../config.php');
global $DB;
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');
class filter_form extends moodleform {

    public function definition() {
        global $DB;
       $id = optional_param('id', 0, PARAM_INT);
       $getuser = $DB->get_record('user', array('id' => $id));
       if ($id) {
        echo "Resquest for: ".$getuser->firstname.' '.$getuser->lastname;  
       }
        
       $mform =& $this->_form;
       $mform->addElement('hidden', 'for_userid', $id);
    //    $options = array(
    //     '1' => 'rajveer singh'
    //     );
    //    $mform->addElement('select', 'rldusers', get_string('rldusers','local_mydashboard'), $options);

       $mform->addElement('text', 'subjecttitle', get_string('subjecttitle', 'local_mydashboard'));
       $mform->addRule('subjecttitle', get_string('missingsubjecttitle', 'local_mydashboard'), 'required', null, 'client');

       $mform->addElement('editor', 'message', get_string('message', 'local_mydashboard'));
       $mform->addRule('message', get_string('missingmessage', 'local_mydashboard'), 'required', null, 'client');
       $mform->setType('message', PARAM_RAW);

        $radioarray=array();
        $radioarray[] = $mform->createElement('radio', 'yesno', '', get_string('mentor', 'local_mydashboard'), 1, $attributes);
        $radioarray[] = $mform->createElement('radio', 'yesno', '', get_string('student', 'local_mydashboard'), 0, $attributes);
        $mform->addGroup($radioarray, 'radioar', get_string('porposerequest', 'local_mydashboard'), array(' '), false);

       $this->add_action_buttons(false,  get_string('sendrequest','local_mydashboard'));
    }
}
?>