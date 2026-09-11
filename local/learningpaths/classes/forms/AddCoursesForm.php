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
        $mform->addElement('hidden', 'form', "AddCoursesForm");

        // Form definition.
        if (isset($this->_customdata['courses'])) {
            $avcourses = $this->_customdata['courses'];
            $totalcount = count($avcourses);

            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'lp-search-row')));

                // Search box - same id/data-target as before, restyled via CSS.
                $params = array('id' => 'searchbox', 'class' => 'searchbox search-popup lp-search-box', 'role' => 'search');
                $mform->addElement('html', html_writer::start_tag('div', $params));
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'mt-search input-group custom-search-form')));
                        $mform->addElement('html', html_writer::start_tag('span', array('class' => 'input-group-btn')));
                            $params = array('class' => 'text-muted btn btn-default', 'type' => 'button', 'onclick' => '', 'aria-label' => '');
                            $mform->addElement('html', html_writer::start_tag('button', $params));
                                $params = array('class' => 'fa fa-search header-txtmen men-icon-search i-search', 'aria-hidden' => 'true');
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

                // Total / Selected badges. "Selected" count starts at 0 and needs
                // learningpaths.js to update #lp-selected-count on checkbox change.
                $mform->addElement('html', html_writer::tag(
                    'span',
                    get_string('course_name_lp', 'local_learningpaths') . ': ' .
                        html_writer::tag('span', $totalcount, array('class' => 'course-count')),
                    array('class' => 'lp-badge lp-badge-total')
                ));
               $mform->addElement('html', html_writer::tag(
                    'span',
                    'Selected: ' . html_writer::tag('span', 0, array('id' => 'lp-selected-count')),
                    array('class' => 'lp-badge lp-badge-selected', 'id' => 'lp-selected-badge')
                ));

            $mform->addElement('html', html_writer::end_tag('div')); // .lp-search-row

            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'container_popup','style'=>'box-shadow:unset;')));

                // Select all row.
               // Select all row.
                $mform->addElement('html', html_writer::start_tag('div', array('class' => 'lp-select-all-row header-courses')));
                    $checkbox = \theme_remui\widget::checkbox('', false, 'course-all', 'course-all', false,
                        array('class' => 'courses-lpall lp-check-input', 'value' => $course->id,'style' => 'width:100vh !important;top: -12px !important;
    left: -15px;
    height: 35px;'));
                    $mform->addElement('html', "<span class='lp-check-wrap'>{$checkbox}<span class='lp-check-dot' aria-hidden='true'></span></span>");
                    $mform->addElement('html', html_writer::tag('span', get_string('course_name', 'local_learningpaths')));
                $mform->addElement('html', html_writer::end_tag('div'));

                // Course list.
                $mform->addElement('html', '<div id="available-courses-list" class="lp-course-list content-addcourse card-box">');
                foreach ($avcourses as $course) {
                    $checkbox = \theme_remui\widget::checkbox('', false, '', 'courses[]', false,
                        array('class' => 'course-learninpath lp-check-input', 'value' => $course->id,'style' => 'width:100vh !important;top: -12px !important;
    left: -15px;
    height: 35px;'));

                    $metaHtml = '';
                    if (isset($course->modulescount) || isset($course->duration)) {
                        $metaparts = array_filter(array($course->modulescount ?? null, $course->duration ?? null));
                        if ($metaparts) {
                            $metaHtml = html_writer::tag(
                                'div',
                                '<i class="fa fa-clock-o" aria-hidden="true"></i> ' . implode(' &middot; ', $metaparts),
                                array('class' => 'lp-course-meta')
                            );
                        }
                    }

                    $tagHtml = '';
                    if (!empty($course->categoryname)) {
                        $tagHtml = html_writer::tag('span', $course->categoryname, array('class' => 'lp-course-tag'));
                    }

                    $mform->addElement('html', "
                        <div class='lp-course-item row course-lp' data-course-id='{$course->id}'>
                            <div class='lp-course-checkbox-wrap name col-xs-12 col-sm-12'>
                                <span class='lp-check-wrap'>{$checkbox}<span class='lp-check-dot' aria-hidden='true'></span></span>
                                <span class='lp-course-icon' aria-hidden='true'></span>
                                <div class='lp-course-info'>
                                    <div class='lp-course-title'>{$course->coursename}</div>
                                    {$metaHtml}
                                </div>
                                {$tagHtml}
                            </div>
                        </div>
                    ");
                }
                $mform->addElement('html', '</div>');

            $mform->addElement('html', html_writer::end_tag('div')); // .container_popup

            $this->add_action_buttons(false);
        }
    }
}