<?php
require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/local/competency/lib.php');
class uploadcompetency_form extends moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG, $DB;
        $maxbytes = 10240000;

        $mform = $this->_form; // Don't forget the underscore! 
        $completencyTitles = $this->_customdata['completencyTitles'];

        $mform->addElement('html', '<br><div class="qheader"> <a href="sample.csv" class="btn btn-primary" style="float:right;">Download Sample CSV  </a> </div><br><br>');


        //get role list
        $roleResult = getAllroles();
        $rolename = array(null => 'Select Role');
        foreach ($roleResult as $key => $role) {
            $rolename[$role->id] = $role->shortname;
        }
        $mform->addElement('select', 'roles', get_string('roles', 'local_competency'), $rolename);
        $mform->addHelpButton('roles', 'roles', 'local_competency');
        $mform->addRule('roles', get_string('required'), 'required', null, 'client');

        // Get BU departments list.
        $buResult = getdepartment();
        $buname = array(null => 'Select Business Unit');
        foreach ($buResult as $key => $burole) {
            $buname[$burole->department] = $burole->department;
        }

        $mform->addElement('select', 'bumaster', get_string('bumaster', 'local_competency'), $buname);
        $mform->addHelpButton('bumaster', 'bumaster', 'local_competency');
        $mform->addRule('bumaster', get_string('required'), 'required', null, 'client');

        // Competency title
        $competency_titleSql = "Select * from {competency_title} WHERE isdeleted=?";
        $competency_titleResult = $DB->get_records_sql($competency_titleSql, array(0));
        $competency_titleArr = array(null => 'Select Competency Title');
        foreach ($competency_titleResult as $key => $competency_title) {
            $competency_titleArr[$competency_title->id] = $competency_title->title;
        }

        $select = $mform->addElement('select', 'competency_title', get_string('competency_title', 'local_competency'), $competency_titleArr);
        $mform->addHelpButton('competency_title', 'competency_title', 'local_competency');
        $mform->addRule('competency_title', get_string('required'), 'required', null, 'client');

        $mform->addElement('filepicker', 'userfile', 'Upload CSV File', null, array('maxbytes' => $maxbytes, 'accepted_types' => '*'));
        $this->add_action_buttons();

    }
    //Custom validation should be added here
    function validation($data, $files)
    {
        return array();
    }
}