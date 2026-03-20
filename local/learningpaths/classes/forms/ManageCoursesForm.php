<?php
defined('MOODLE_INTERNAL') || die;

// Global vars definition.
global $CFG;
require_once("{$CFG->libdir}/formslib.php");

class ManageCoursesForm extends moodleform
{
    public function definition() {
        $mform = $this->_form;

        // Important Hidden fields.
        $mform->addElement('hidden', 'learningpathid', $this->_customdata['learningpath']);
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->addElement('hidden', 'form', "ManageCoursesForm");

        // Section pre-requisites title.
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'lp-course-prerequisites')));

        if (isset($this->_customdata['learningpath_courses'])) {
            $leftcolumn = $rightcolumn = '';
            // Build columns based on required.
            foreach ($this->_customdata['learningpath_courses'] as $course) {
                $coursename = (strlen($course->data->coursename) >= 40)?substr($course->data->coursename, 0, 40).'...':$course->data->coursename;
                if (!in_array($course->data->id, $this->_customdata['does_not_prerequisites'])) {
                    if (in_array($course->data->courseid, $this->_customdata['prerequisites'])) {
                        $params = array('data-courseid' => $course->data->courseid, 'class' => 'name');
                        $rightcolumn .= html_writer::start_tag('li', $params);
                            $params = array('class' => 'name-course');
                            $rightcolumn .= html_writer::tag('span', $coursename, $params);
                        $rightcolumn .= html_writer::end_tag('li');
                    } else if (!in_array($course->data->id, $this->_customdata['already_added_prerequisites'])) {
                        if (!empty($course->data->courseid)) {
                            $params = array('data-courseid' => $course->data->courseid, 'class' => 'name');
                            $leftcolumn .= html_writer::start_tag('li', $params);
                             
                                $leftcolumn .= html_writer::start_tag('span',array('class' => 'name-course'));
                                     $leftcolumn .= html_writer::tag('i', '',array('class' => 'fa men men-icon-phbullets icons_bullets'));
                                     $leftcolumn .= html_writer::tag('p', $coursename);
                             $leftcolumn .= html_writer::end_tag('span');

                            $leftcolumn .= html_writer::end_tag('li');
                        }
                    }
                }
            }

            $params = array('class' => 'prerequisites-drag-and-drop', 'data-courseid' => $this->_customdata['courseid']);
            $columns = $this->get_courses_columns($leftcolumn, $rightcolumn, $course->data->id, $this->_customdata['courseid']);
            $mform->addElement('html', html_writer::tag('div', $columns, $params));
        }

        // Close pre-requisites section.
        $mform->addElement('html', html_writer::end_tag('div'));

        // Action buttons.
        $this->add_action_buttons();
    }

    private function get_courses_columns($leftcourses, $rightcourses, $courseid, $lpcourse) {
        global $OUTPUT;
        // Left column, this column has courses availables to add as pre-requisites.

        $templatecontext = array(
          'site_url' => $CFG->wwwroot,
          'leftcourses' => $leftcourses,
          'rightcourses'=>$rightcourses,
          'courseid'=>$courseid,
          'lpcourse'=>$lpcourse
      );
        return $OUTPUT->render_from_template('local_learningpaths/get_courses_columns', $templatecontext);
    }

    // Add action buttons.
    public function add_action_buttons ($cancel = false, $submitlabel = null) {
        $mform = $this->_form;
        $buttonarray = array();

        if ($cancel) {
            $params = array('class' => 'btn btn-cancel');
            $buttonarray[] = &$mform->createElement('html', html_writer::tag('button', get_string('cancel'), $params));
        }

        if ($submitlabel !== false) {

            $submitlabel = get_string('save', 'local_learningpaths');
            $params = ['data-courseid' => $this->_customdata['courseid'], 'data-class' => 'submit-lpcourse'];
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel, $params);
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(''), false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->closeHeaderBefore('buttonar');
    }
}