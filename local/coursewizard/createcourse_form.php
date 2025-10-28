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
 * @author Matthias Schwabe <support@eledia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package eledia_coursewizard
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');
//require_once($CFG->libdir.'/coursecatlib.php');
use core_course_category;
use core_course_list_element;
class eledia_course_edit_form extends moodleform {
    protected $course;
    protected $context;
    function definition() {
        global $CFG, $DB, $PAGE, $COURSE;
        $mform = $this->_form;
        $PAGE->requires->yui_module('moodle-course-formatchooser', 'M.course.init_formatchooser',
               array(array('formid' => $mform->getAttribute('id'))));

        $editoroptions = $this->_customdata['editoroptions'];
        $returnto      = $this->_customdata['returnto'];
        $cid           = $this->_customdata['cid'];
		if($cid>1){
			$course = get_course($cid);
		}else{
			$course = $DB->get_record('course', array('id' => $cid), 'id, category');
		}
		if ($course) { // Should always exist, but just in case.
			if($course->id == 1){
				$categoryid = optional_param('category', 0, PARAM_INT);
			} else {
				/**
				 * Change for set the categoryid when the user comes from explore courses
				 * @author Andres Ag.
				 * @since 08/13/2015
				 * @paradiso
				 */
				$categoryid = $course->category;
			}
		}
        $systemcontext = context_system::instance();
		if ($COURSE->category != 0) {
			$categorycontext = core_course_category::instance($categoryid);
		} else {
			$categorycontext = $systemcontext;
		}
        $coursecontext = context_course::instance($cid);
		if (has_capability('block/coursewizard:create_course', $coursecontext) OR
			has_capability('moodle/course:create', $categorycontext)) {
			$this->course = $course;
			$this->context = $coursecontext;
			$mform->addElement('header', 'general', get_string('general', 'form'));
			$mform->addElement('hidden', 'returnto', null);
			$mform->setType('returnto', PARAM_ALPHANUM);
			$mform->setConstant('returnto', $returnto);
			$mform->addElement('hidden', 'cid', null);
			$mform->setType('cid', PARAM_INT);
			$mform->setConstant('cid', $cid);
			// Verify permissions to change course category or keep current.
            if (has_capability('block/coursewizard:change_category', $coursecontext)) {
                $displaylist =\core_course_category::make_categories_list();
                $mform->addElement('select', 'category', get_string('coursecategory'), $displaylist);
				/**
				 * Change for set the categoryid when the user comes from explore courses
				 * @author Andres Ag.
				 * @since 08/13/2015
				 * @paradiso
				 */
				$mform->setConstant('category', $categoryid);
                $mform->addHelpButton('category', 'coursecategory');
            } else {
                $mform->addElement('hidden', 'category', null);
                $mform->setType('category', PARAM_INT);
                $mform->setConstant('category', $categoryid);
            }
			$mform->addElement('text', 'fullname', get_string('fullnamecourse'), 'maxlength="254" size="50"');
			$mform->addHelpButton('fullname', 'fullnamecourse');
			$mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
			$mform->setType('fullname', PARAM_TEXT);
			$mform->setType('shortname', PARAM_TEXT);
			$PAGE->requires->strings_for_js(array(
				'drag_drop_images',
				'or',
				'click_open_file_browser',
				'selected_file',
				'status',
				'uploading',
				'upload_complete',
				'has_not_allowed_extencion',
				'browser_not_supported',
			), 'local_coursewizard');
			
			$PAGE->requires->css(new moodle_url('/local/coursewizard/style/lightslider.css'));
			$PAGE->requires->css(new moodle_url('/local/coursewizard/style/course_wizard.css'));
			$mform->addElement('html','<div id="drag-drop-element-course-lms">');
				$mform->addElement('text', 'thumbnail_file', get_string('courseoverviewfiles'), ' ');
				$mform->addElement('hidden', 'shortname', null);
			$mform->addElement('html','</div>');
			if($course->id>1){
				$mform->setDefault('summary_editor[text]',$course->summary);
				$mform->setDefault('shortname',$course->shortname);
				$coursethumb = $DB->get_record('course_thumbnail', array('courseid' => $course->id), 'id,path');
				if(!empty($coursethumb->id)){
					$mform->setDefault('thumbnail_file',$coursethumb->path);
				}
			}
			$courseformats = get_sorted_course_formats(true);
			$formcourseformats = array();
			foreach ($courseformats as $courseformat) {
				$formcourseformats[$courseformat] = get_string('pluginname', "format_$courseformat");
			}
			if (isset($course->format)) {
				$course->format = course_get_format($course)->get_format(); // Replace with default if not found.
				if (!in_array($course->format, $courseformats)) {
					// This format is disabled. Still display it in the dropdown.
					$formcourseformats[$course->format] = get_string('withdisablednote', 'moodle',
                        get_string('pluginname', 'format_'.$course->format));
				}
			}
			if($course->id>1){
				$this->add_action_buttons(true, true, get_string('save_changes_button', 'local_coursewizard'));
			}else{
				$this->add_action_buttons(true, false, get_string('next_button', 'local_coursewizard'));
			}
			$mform->addElement('hidden', 'id', null);
			$mform->setType('id', PARAM_INT);
			// Finally set the current form data.
			$this->set_data($course);
		} else {
			$mform->addElement('static', 'norights', '', get_string('norights', 'local_coursewizard'));
			$mform->addElement('static', 'backbutton', '', '<br><a href='.$CFG->wwwroot.'/course/view.php?id='.$cid.'>'
					.get_string('backbutton_cancel', 'local_coursewizard').'</a>');
		}
	}
function definition_after_data() {
        global $DB;
        $mform = $this->_form;
        // Add available groupings.
        if ($courseid = $mform->getElementValue('id') and $mform->elementExists('defaultgroupingid')) {
            $options = array();
            if ($groupings = $DB->get_records('groupings', array('courseid' => $courseid))) {
                foreach ($groupings as $grouping) {
                    $options[$grouping->id] = format_string($grouping->name);
                }
            }
            $gr_el =& $mform->getElement('defaultgroupingid');
            $gr_el->load($options);
        }
    }
    // Perform some extra moodle validation.
    function validation($data, $files) {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);
        if ($foundcourses = $DB->get_records('course', array('shortname' => $data['shortname']))) {
            if (!empty($data['id'])) {
                unset($foundcourses[$data['id']]);
            }
            if (!empty($foundcourses)) {
                foreach ($foundcourses as $foundcourse) {
                    $foundcoursenames[] = $foundcourse->fullname;
                }
                $foundcoursenamestring = implode(',', $foundcoursenames);
                $errors['shortname'] = get_string('shortnametaken', '', $foundcoursenamestring);
            }
        }
        $errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));
        return $errors;
    }
    /** 
	* Overriding formslib's add_action_buttons() method, to add an extra advanced setings button.
    * @param bool $cancel show cancel button
    * @param bool $advanced show advanced setings button
    * @param string $submitlabel null means default, false means none, string is label text
    * @param string $submit2label  null means default, false means none, string is label text
	* @author @andres.a_paradiso
	* @since 2015-05-21
	* @paradiso
	*/
    function add_action_buttons($cancel=true, $advanced=true, $submitlabel=null, $submit2label=null) {
        if (is_null($submitlabel)) {
            $submitlabel = get_string('savechangesanddisplay');
        }
        if (is_null($submit2label)) {
            $submit2label = get_string('savechangesandreturntocourse');
        }
        $mform = $this->_form;
        // elements in a row need a group
        $buttonarray = array(); 
	    if ($submitlabel !== false) {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        }
        if ($advanced) {
            $buttonarray[] = &$mform->createElement('button', 'advancedbutton', get_string('advanced_settings', 'local_coursewizard'));
        }
        if ($cancel) {
            $buttonarray[] = &$mform->createElement('cancel');
        }
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->closeHeaderBefore('buttonar');
    }
}
