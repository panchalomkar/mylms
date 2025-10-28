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
* @package local_custom_notification
* @category local
* @copyright  ELS <admin@elearningstack.com>
* @author eLearningstack
*/
require_once('../../config.php');
global $DB;
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');
class notification_form extends moodleform {

    public function definition() {
       $mform =& $this->_form;                                                                                                                           
        $options2 = array(                                                                                                           
            'multiple' => true,                                                  
            'noselectionstring' => 'Select a Course',                                                                
        );         
       
        $courses = self::getenrolcourse();
        $mform->addElement('autocomplete', 'courseid', '', $courses,$options2);
        $mform->addRule('courseid', get_string('required'), 'required', null, 'client');
        //--------------- course noti----------------//
        $mform->addElement('header', 'course_completion_notification', get_string('course_completion_notification', 'local_custom_notification'));
        $mform->addElement('advcheckbox', 'course_completion_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
        $value = 0;
        $mform->setDefault('course_completion_noti',  $value);
       
        // $mform->addElement('header', 'packagehdr', get_string('packagehdr', 'scorm'));
        // $mform->setExpanded('packagehdr', true);
        $mform->addElement('editor', 'course_completion_tem', get_string('emailtemplate', 'local_custom_notification'));
        $mform->setType('fieldname', PARAM_RAW);
        // New local package upload.
        $mform->addElement('html', '<p>'.get_string("course_completion_tags_desciption", "local_custom_notification").'</p>');
        $mform->addElement('html', '</div>');
     
       //----------------- end course notifi --------------//

       //-----------------course_module_completion_notification --------------//

       $mform->addElement('header', 'course_module_completion_notification', get_string('course_module_completion_notification', 'local_custom_notification'));
       $mform->addElement('advcheckbox', 'course_module_completion_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
       $value = 0;
       $mform->setDefault('course_module_completion_noti',  $value);
       // $mform->addElement('header', 'packagehdr', get_string('packagehdr', 'scorm'));
       // $mform->setExpanded('packagehdr', true);
       $mform->addElement('editor', 'course_module_completion_tem', get_string('emailtemplate', 'local_custom_notification'));
       $mform->setType('fieldname', PARAM_RAW);
       // New local package upload.
       $mform->addElement('html', '<p>'.get_string("course_module_completion_tags_desciption", "local_custom_notification").'</p>');

       //-----------------end course_module_completion_notification --------------//


       $mform->addElement('header', 'course_in_progress_notification', get_string('course_in_progress_notification', 'local_custom_notification'));
       $mform->addElement('advcheckbox', 'course_in_progress_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
       $value = 0;
       $mform->setDefault('course_in_progress_noti',  $value);
       // $mform->addElement('header', 'packagehdr', get_string('packagehdr', 'scorm'));
       // $mform->setExpanded('packagehdr', true);
       $mform->addElement('editor', 'course_in_progress_tem', get_string('emailtemplate', 'local_custom_notification'));
       $mform->setType('fieldname', PARAM_RAW);
       // New local package upload.
       $mform->addElement('html', '<p>'.get_string("course_in_progress_tags_desciption", "local_custom_notification").'</p>');
       $mform->addElement('html', '</div>');

       //-----------------course_in_progress --------------//

       $mform->addElement('header', 'course_expiration_notification', get_string('course_expiration_notification', 'local_custom_notification'));
       $mform->addElement('advcheckbox', 'course_expiration_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
       $value = 0;
       $mform->setDefault('course_expiration_noti',  $value);
       $mform->addElement('duration', 'course_expiration_when', get_string('course_expirationday', 'local_custom_notification'));

       // $mform->addElement('header', 'packagehdr', get_string('packagehdr', 'scorm'));
       // $mform->setExpanded('packagehdr', true);
       $mform->addElement('editor', 'course_expiration_tem', get_string('emailtemplate', 'local_custom_notification'));
       $mform->setType('fieldname', PARAM_RAW);
       // New local package upload.
       $mform->addElement('html', '<p>'.get_string("course_expiration_tags_desciption", "local_custom_notification").'</p>');

       //-----------------end course_in_progress --------------//


       //-----------------course_not_completed_noti --------------//

       $mform->addElement('header', 'course_not_completed_notification', get_string('course_not_completed_notification', 'local_custom_notification'));
       $mform->addElement('advcheckbox', 'course_not_completed_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
       $value = 0;
       $mform->setDefault('course_not_completed_noti',  $value);
       $mform->addElement('duration', 'course_not_completed_when', get_string('beforenotcompletetime', 'local_custom_notification'));

       // $mform->addElement('header', 'packagehdr', get_string('packagehdr', 'scorm'));
       // $mform->setExpanded('packagehdr', true);
       $mform->addElement('editor', 'course_not_completed_tem', get_string('emailtemplate', 'local_custom_notification'));
       $mform->setType('fieldname', PARAM_RAW);
       // New local package upload.
       $mform->addElement('html', '<p>'.get_string("course_not_completed_tags_desciption", "local_custom_notification").'</p>');

       //-----------------end course_not_completed_noti --------------//


        //-----------------not_loggedin_noti --------------//

        $mform->addElement('header', 'not_loggedin_notification', get_string('not_loggedin_notification', 'local_custom_notification'));
        $mform->addElement('advcheckbox', 'not_loggedin_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
        $value = 0;
        $mform->setDefault('not_loggedin_noti',  $value);

        $mform->addElement('duration', 'not_loggedin_when', get_string('beforelogintime', 'local_custom_notification'));

        // $mform->addElement('header', 'packagehdr', get_string('packagehdr', 'scorm'));
        // $mform->setExpanded('packagehdr', true);
        $mform->addElement('editor', 'not_loggedin_tem', get_string('emailtemplate', 'local_custom_notification'));
        $mform->setType('fieldname', PARAM_RAW);
        // New local package upload.
        $mform->addElement('html', '<p>'.get_string("not_loggedin_tags_desciption", "local_custom_notification").'</p>');

        //-----------------end not_loggedin_noti --------------//


        //-----------------not_loggedin_noti --------------//

        $mform->addElement('header', 'user_enrolled_notification', get_string('user_enrolled_notification', 'local_custom_notification'));
        $mform->addElement('advcheckbox', 'user_enrolled_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
        $value = 0;
        $mform->setDefault('user_enrolled_noti',  $value);
        // $mform->addElement('header', 'packagehdr', get_string('packagehdr', 'scorm'));
        // $mform->setExpanded('packagehdr', true);
        $mform->addElement('editor', 'user_enrolled_tem', get_string('emailtemplate', 'local_custom_notification'));
        $mform->setType('fieldname', PARAM_RAW);
        // New local package upload.
        $mform->addElement('html', '<p>'.get_string("user_enrolled_tags_desciption", "local_custom_notification").'</p>');

        //-----------------end not_loggedin_noti --------------//

        //-----------------not_loggedin_noti --------------//

        $mform->addElement('header', 'user_unenrolled_notification', get_string('user_unenrolled_notification', 'local_custom_notification'));
        $mform->addElement('advcheckbox', 'user_unenrolled_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
        $value = 0;
        $mform->setDefault('user_unenrolled_noti',  $value);
        // $mform->addElement('header', 'packagehdr', get_string('packagehdr', 'scorm'));
        // $mform->setExpanded('packagehdr', true);
        $mform->addElement('editor', 'user_unenrolled_tem', get_string('emailtemplate', 'local_custom_notification'));
        $mform->setType('fieldname', PARAM_RAW);
        // New local package upload.
        $mform->addElement('html', '<p>'.get_string("user_unenrolled_tags_desciption", "local_custom_notification").'</p>');

        //-----------------end not_loggedin_noti --------------//
        

        $this->add_action_buttons(false,  get_string('submitbutton','local_custom_notification'));
    }

    public function getenrolcourse(){
        global $DB;
        $urs = array();
        $urs[0] = get_string('allcourses','local_custom_notification');
            $users = $DB->get_records_sql("SELECT * FROM {course} ");
               foreach ($users as $user ) {                                                                          
                $urs[$user->id] = $user->fullname;                                                                  
            }
    
        return $urs;
    }

}
?>