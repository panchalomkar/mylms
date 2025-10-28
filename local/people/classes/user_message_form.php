<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once("{$CFG->libdir}/formslib.php");

class user_message_form extends moodleform {

    function definition() {
        $mform = $this->_form;
        $mform->addElement('html', html_writer::start_tag('div', array('id' => 'message_bulk_container')));
        $mform->addElement('header', 'general', get_string('message', 'message'));
        $editor = $mform->addElement('editor', 'messagebody', get_string('messagebody'), null, null);
        $editor->setAttributes(['class' => 'col-md-12','messagebody','name' => 'messagebody', 'autosave' => false]);
        $mform->addRule('messagebody', '', 'required', null, 'server');
        
        $mform->addElement('hidden','users');
        $mform->addElement('html', html_writer::end_tag('div'));
    }
}
