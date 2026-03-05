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

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');
class notification_form extends moodleform {

    public function definition() {
        global $DB;
        $cid = $_GET['cid'];

        $getdata = null;
        if ($cid) {
            $getdata = $DB->get_record_sql("SELECT * FROM {custom_notification} WHERE courseid = ?", [$cid]);
        }

        $mform =& $this->_form;                                                                                                                           
        $options2 = array(
            'multiple' => false,
            'noselectionstring' => 'Select a Course',
        );         
   
        $courses = self::getenrolcourse();
        $mform->addElement('select', 'courseid', get_string('coursestitle', 'local_custom_notification'), $courses, $options2);
        $mform->addRule('courseid', get_string('required'), 'required', null, 'client');

        if (!empty($getdata->courseid)) {
            $mform->setDefault('courseid',  $getdata->courseid);
        } else {
            $mform->setDefault('courseid',  $cid);
        }

        $frequencyoptions = [
            'once' => 'Once',
            'daily' => 'Daily',
            'weekly' => 'Weekly'
        ];

        //--------------- course completion ----------------//
        $mform->addElement('header', 'course_completion_notification', get_string('course_completion_notification', 'local_custom_notification'));
        $mform->addElement('advcheckbox', 'course_completion_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
        $mform->setDefault('course_completion_noti', $getdata->course_completion_noti ?? 0);
$mform->addElement('html', '
    <style>
        #page-local-custom_notification-notification .checkboxgroup1 .col-lg-3.col-md-4.col-form-label.pb-0.pt-0 {
                max-width: 25% !important;
    padding: 0px !important;

        }#page-local-custom_notification-notification .checkboxgroup1{
    padding-top: 10px;
    padding-bottom: 10px;
}
    </style>
');

        $course_completion_tem = $getdata->course_completion_tem ?? ' ';
        $mform->addElement('editor', 'course_completion_tem', get_string('emailtemplate', 'local_custom_notification'))
              ->setValue(array('text' => $course_completion_tem));
        $mform->setType('course_completion_tem', PARAM_RAW);
        $mform->addElement('html', '<p>'.get_string("course_completion_tags_desciption", "local_custom_notification").'</p>');

        //----------------- course_module_completion ----------------//
        $mform->addElement('header', 'course_module_completion_notification', get_string('course_module_completion_notification', 'local_custom_notification'));
        $mform->addElement('advcheckbox', 'course_module_completion_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
        $mform->setDefault('course_module_completion_noti', $getdata->course_module_completion_noti ?? 0);

        $course_module_completion_tem = $getdata->course_module_completion_tem ?? ' ';
        $mform->addElement('editor', 'course_module_completion_tem', get_string('emailtemplate', 'local_custom_notification'))
              ->setValue(array('text' => $course_module_completion_tem));
        $mform->setType('course_module_completion_tem', PARAM_RAW);
        $mform->addElement('html', '<p>'.get_string("course_module_completion_tags_desciption", "local_custom_notification").'</p>');

        //----------------- course_in_progress ----------------//
        $mform->addElement('header', 'course_in_progress_notification', get_string('course_in_progress_notification', 'local_custom_notification'));
        $mform->addElement('advcheckbox', 'course_in_progress_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
        $mform->setDefault('course_in_progress_noti', $getdata->course_in_progress_noti ?? 0);
        $mform->addElement('select', 'course_in_progress_frequency', 'Frequency', $frequencyoptions);
        $mform->setDefault('course_in_progress_frequency', $getdata->course_in_progress_frequency ?? 'once');

        $course_in_progress_tem = $getdata->course_in_progress_tem ?? ' ';
        $mform->addElement('editor', 'course_in_progress_tem', get_string('emailtemplate', 'local_custom_notification'))
              ->setValue(array('text' => $course_in_progress_tem));
        $mform->setType('course_in_progress_tem', PARAM_RAW);
        $mform->addElement('html', '<p>'.get_string("course_in_progress_tags_desciption", "local_custom_notification").'</p>');

        //----------------- course_expiration ----------------//
        $mform->addElement('header', 'course_expiration_notification', get_string('course_expiration_notification', 'local_custom_notification'));
        $mform->addElement('advcheckbox', 'course_expiration_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
        $mform->setDefault('course_expiration_noti', $getdata->course_expiration_noti ?? 0);
        $mform->addElement('duration', 'course_expiration_when', get_string('course_expirationday', 'local_custom_notification'));
        $mform->setDefault('course_expiration_when', $getdata->course_expiration_when ?? 0);
        $mform->addElement('select', 'course_expiration_frequency', 'Frequency', $frequencyoptions);
        $mform->setDefault('course_expiration_frequency', $getdata->course_expiration_frequency ?? 'once');

        $course_expiration_tem = $getdata->course_expiration_tem ?? ' ';
        $mform->addElement('editor', 'course_expiration_tem', get_string('emailtemplate', 'local_custom_notification'))
              ->setValue(array('text' => $course_expiration_tem));
        $mform->setType('course_expiration_tem', PARAM_RAW);
        $mform->addElement('html', '<p>'.get_string("course_expiration_tags_desciption", "local_custom_notification").'</p>');

        //----------------- course_not_completed ----------------//
        $mform->addElement('header', 'course_not_completed_notification', get_string('course_not_completed_notification', 'local_custom_notification'));
        $mform->addElement('advcheckbox', 'course_not_completed_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
        $mform->setDefault('course_not_completed_noti', $getdata->course_not_completed_noti ?? 0);
        $mform->addElement('duration', 'course_not_completed_when', get_string('beforenotcompletetime', 'local_custom_notification'));
        $mform->setDefault('course_not_completed_when', $getdata->course_not_completed_when ?? 0);
        $mform->addElement('select', 'course_not_completed_frequency', 'Frequency', $frequencyoptions);
        $mform->setDefault('course_not_completed_frequency', $getdata->course_not_completed_frequency ?? 'once');

        $course_not_completed_tem = $getdata->course_not_completed_tem ?? ' ';
        $mform->addElement('editor', 'course_not_completed_tem', get_string('emailtemplate', 'local_custom_notification'))
              ->setValue(array('text' => $course_not_completed_tem));
        $mform->setType('course_not_completed_tem', PARAM_RAW);
        $mform->addElement('html', '<p>'.get_string("course_not_completed_tags_desciption", "local_custom_notification").'</p>');

        //----------------- not_loggedin ----------------//
        $mform->addElement('header', 'not_loggedin_notification', get_string('not_loggedin_notification', 'local_custom_notification'));
        $mform->addElement('advcheckbox', 'not_loggedin_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
        $mform->setDefault('not_loggedin_noti', $getdata->not_loggedin_noti ?? 0);
        $mform->addElement('duration', 'not_loggedin_when', get_string('beforelogintime', 'local_custom_notification'));
        $mform->setDefault('not_loggedin_when', $getdata->not_loggedin_when ?? 0);
        $mform->addElement('select', 'not_loggedin_frequency', 'Frequency', $frequencyoptions);
        $mform->setDefault('not_loggedin_frequency', $getdata->not_loggedin_frequency ?? 'once');

        $not_loggedin_tem = $getdata->not_loggedin_tem ?? ' ';
        $mform->addElement('editor', 'not_loggedin_tem', get_string('emailtemplate', 'local_custom_notification'))
              ->setValue(array('text' => $not_loggedin_tem));
        $mform->setType('not_loggedin_tem', PARAM_RAW);
        $mform->addElement('html', '<p>'.get_string("not_loggedin_tags_desciption", "local_custom_notification").'</p>');

        //----------------- user_enrolled ----------------//
        $mform->addElement('header', 'user_enrolled_notification', get_string('user_enrolled_notification', 'local_custom_notification'));
        $mform->addElement('advcheckbox', 'user_enrolled_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
        $mform->setDefault('user_enrolled_noti', $getdata->user_enrolled_noti ?? 0);

        $user_enrolled_tem = $getdata->user_enrolled_tem ?? ' ';
        $mform->addElement('editor', 'user_enrolled_tem', get_string('emailtemplate', 'local_custom_notification'))
              ->setValue(array('text' => $user_enrolled_tem));
        $mform->setType('user_enrolled_tem', PARAM_RAW);
        $mform->addElement('html', '<p>'.get_string("user_enrolled_tags_desciption", "local_custom_notification").'</p>');

        //----------------- user_unenrolled ----------------//
        $mform->addElement('header', 'user_unenrolled_notification', get_string('user_unenrolled_notification', 'local_custom_notification'));
        $mform->addElement('advcheckbox', 'user_unenrolled_noti', get_string('notification', 'local_custom_notification'), 'Default: No', array('group' => 1), array(0, 1));
        $mform->setDefault('user_unenrolled_noti', $getdata->user_unenrolled_noti ?? 0);

        $user_unenrolled_tem = $getdata->user_unenrolled_tem ?? ' ';
        $mform->addElement('editor', 'user_unenrolled_tem', get_string('emailtemplate', 'local_custom_notification'))
              ->setValue(array('text' => $user_unenrolled_tem));
        $mform->setType('user_unenrolled_tem', PARAM_RAW);
        $mform->addElement('html', '<p>'.get_string("user_unenrolled_tags_desciption", "local_custom_notification").'</p>');

        $this->add_action_buttons(false,  get_string('submitbutton','local_custom_notification'));
    }

    public function getenrolcourse(){
        global $DB;
        $urs = array();
        $urs[0] = get_string('allcourses','local_custom_notification');
        $users = $DB->get_records_sql("SELECT * FROM {course}");
        foreach ($users as $user ) {                                                                          
            $urs[$user->id] = $user->fullname;                                                                  
        }
        return $urs;
    }
}
$siteurl = $CFG->wwwroot."/local/custom_notification/notification.php";
?>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<script>
 $(document).ready(function() {

    $('#id_courseid').on('change', function() {
      
      if (this.value == 0) {
        window.location.href = "<?php echo $siteurl?>#status"; 
      }else{
        window.location.href = "<?php echo $siteurl?>?cid=" + this.value + "";
      }
    });
 });
 </script>