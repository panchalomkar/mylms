<?php
defined('MOODLE_INTERNAL') || die;

// Global vars definition.
global $CFG, $PAGE;
 

class CohortSyncForm extends moodleform
{
    public function definition() {
        global $PAGE;
        $mform = $this->_form;
        list($instance, $plugin, $context, $type) = $this->_customdata;
        $companyid = getcompanyid();
        $cohorts = mt_dashboard_cohort_get_all_cohorts();
        foreach($cohorts['cohorts'] as $cohort){
            $cohortarray[$cohort->id] = $cohort->name;
        }
        $courses = get_tenant_courses($companyid);
        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_COMPONENT);
        $mform->addElement('select', 'courseid', get_string('course'), $courses);
        $mform->addElement('select', 'customint1', get_string('cohort','local_mt_dashboard'), $cohortarray);
        // Action buttons.
        $this->add_action_buttons(true, get_string('addinstance', 'enrol'));
        // Validation rules.
        $mform->addRule('courseid', get_string('required_field', 'local_learningpaths'), 'required', null, 'client');
        $mform->addRule('customint1', get_string('required_field', 'local_learningpaths'), 'required', null, 'client');
     }
    /**
     * Validate this form. Calls plugin validation method.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        list($instance, $plugin, $context, $type) = $this->_customdata;
        $data['roleid'] = 5;
        $params = array(
            'roleid' => $data['roleid'],
            'customint1' => $data['customint1'],
            'courseid' => $data['courseid']
        );
        $sql = "roleid = :roleid AND customint1 = :customint1 AND courseid = :courseid AND enrol = 'cohort'";
        if ($DB->record_exists_select('enrol', $sql, $params)) {
            $errors['customint1'] = get_string('instanceexists', 'enrol_cohort');
        }
        
        return $errors;
    }
}