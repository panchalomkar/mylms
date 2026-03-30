<?php
defined('MOODLE_INTERNAL') || die;

// Global vars definition.
global $CFG;
require_once("{$CFG->libdir}/formslib.php");

class AddCoursesForm extends moodleform
{
    public function definition() {
        global $PAGE;
        $PAGE->requires->js_call_amd('local_learningpaths/learningpaths', 'lpactions');
        $mform = $this->_form;


        // Important Hidden fields.
        $mform->addElement('hidden', 'learningpathid', $this->_customdata['learningpath']);
        $mform->setType('learningpathid', PARAM_INT);
        $mform->addElement('hidden', 'form', "AddCoursesForm");
$mform->setType('form', PARAM_RAW);
        // Form definition.
        if (isset($this->_customdata['courses'])) {
            $avcourses = $this->_customdata['courses'];

            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'content-search')));
                $mform->addElement('html', html_writer::start_tag('div', array('class' => 'row searchbox-add')));
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'col-sm-12')));

                        $params = array('id' => 'searchbox', 'class' => 'searchbox search-popup', 'role' => 'search');
                        $mform->addElement('html', html_writer::start_tag('div', $params));
                            $params = array(
                                'class' => 'mt-search input-group custom-search-form'
                            );
                            $mform->addElement('html', html_writer::start_tag('div', $params));
                                $mform->addElement('html', html_writer::start_tag('span', array('class' => 'input-group-btn')));
                                    $params = array(
                                        'class' => 'text-muted btn btn-default',
                                        'type' => 'button',
                                        'onclick' => '',
                                        'aria-label' => ''
                                    );
                                    $mform->addElement('html', html_writer::start_tag('button', $params));
                                        $params = array(
                                            'class' => 'fa fa-search header-txtmen men-icon-search i-search',
                                            'aria-hidden' => 'true'
                                        );
                                        $mform->addElement('html', html_writer::tag('i', '', $params));
                                    $mform->addElement('html', html_writer::end_tag('button'));
                                $mform->addElement('html', html_writer::end_tag('span'));

                                $params = array(
                                    'id' => 'add-courses-search',
                                    'class' => 'form-control search_courses',
                                    'data-target' => 'available-courses-list',
                                    'type' => 'text',
                                    'placeholder' => get_string('search_course', 'local_learningpaths')
                                );
                                $mform->addElement('html', html_writer::tag('input', '', $params));
                            $mform->addElement('html', html_writer::end_tag('div'));
                        $mform->addElement('html', html_writer::end_tag('div'));
                    $mform->addElement('html', html_writer::end_tag('div'));
                $mform->addElement('html', html_writer::end_tag('div'));
            $mform->addElement('html', html_writer::end_tag('div'));


            $mform->addElement('html' , html_writer::start_tag('div' , array('class' => 'container_popup')));
            $sallcourses =get_string('course_name_lp', 'local_learningpaths').': '. html_writer::tag('span', count($avcourses), $params = array('class' => 'course-count'));
              $mform->addElement('html', html_writer::start_tag('div', array('class' => 'count_tittle')));
                $mform->addElement('html', html_writer::tag('span', $sallcourses));
              $mform->addElement('html', html_writer::end_tag('div'));

                $mform->addElement('html', html_writer::start_tag('div', array('class' => 'container header-add-course')));
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'header-courses col-xs-12 col-sm-12')));
                        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'col-xs-9 col-sm-9 header-title')));
                         $checkbox = \theme_remui\widget::checkbox('', false, 'course-all','course-all', false,array('class' => 'courses-lpall form-check-input' ));
                    $mform->addElement('html',$checkbox );
                            $mform->addElement('html', html_writer::tag('span', get_string('course_name', 'local_learningpaths')));
                        $mform->addElement('html', html_writer::end_tag('div'));
                    $mform->addElement('html', html_writer::end_tag('div'));
                $mform->addElement('html', html_writer::end_tag('div'));

                $mform->addElement('html', '<div id="available-courses-list" class="content-addcourse card-box">');
                foreach ($avcourses as $course) {

                    $checkbox = \theme_remui\widget::checkbox($course->coursename, false, '','courses[]', false,array('class' => 'course-learninpath form-check-input' ,'value' => $course->id) );
                    $mform->addElement('html', "
                        <div class='row course-lp'>
                            <div class='name col-xs-12 col-sm-12'>". $checkbox."</div>
                        </div>
                    ");
                }
                $mform->addElement('html', '</div>');
            $mform->addElement('html', html_writer::end_tag('div'));
            $this->add_action_buttons(false);
            
        }
    }
}