<?php

namespace local_ticketing\output;

require_once("$CFG->libdir/formslib.php");

class createticket extends \moodleform {

    function definition() {
        global $CFG;

        $mform = $this->_form;
        $mform->addElement('text', 'title', get_string('title', 'local_ticketing'), array());
    }

}
