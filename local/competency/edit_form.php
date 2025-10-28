<?php

class local_competency_edit_form extends local_edit_form {

    protected function specific_definition($mform) {

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('localettings', 'local'));

        // A sample string variable with a default value.
        $mform->addElement('text', 'config_text', get_string('localtring', 'local_competency'));
        $mform->setDefault('config_text', 'default value');
        $mform->setType('config_text', PARAM_TEXT);        

    }
}
