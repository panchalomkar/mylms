<?php
defined('MOODLE_INTERNAL') || die;

// Global vars definition.
global $CFG;
require_once("{$CFG->libdir}/formslib.php");

class ManageCoursesPositionForm extends moodleform
{
    public function definition() {
        $mform = $this->_form;

        // Important Hidden fields.
        $mform->addElement('hidden', 'learningpathid', $this->_customdata['learningpath']);
        $mform->addElement('hidden', 'coursesposition', []);
        $mform->addElement('hidden', 'form', "ManageCoursesPositionForm");

        // Action buttons.
        $this->add_action_buttons();
    }

    // Add action buttons.
    public function add_action_buttons ($cancel = true, $submitlabel = null) {
          parent::add_action_buttons($cancel, $submitlabel);
        $mform = $this->_form;
        $buttonarray = array();

        if ($submitlabel !== false) {
            $submitlabel = get_string('saveposition', 'local_learningpaths');
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->closeHeaderBefore('buttonar');
    }
}