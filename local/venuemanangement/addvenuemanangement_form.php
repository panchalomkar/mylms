<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
global $CFG, $PAGE;
$PAGE->requires->jquery();
$PAGE->requires->js("/local/venuemanangement/js/venue.js");

class addvenuemanangement_form extends moodleform {

    function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $venuemanangement = $this->_customdata['venuemanangement'] ?? null;
        $local_bu       = $this->_customdata['local_bu'] ?? null;

        // ✅ Edit mode
        if (!empty($local_bu) && !empty($venuemanangement)) {

            $mform->addElement('text', 'location', get_string('location', 'local_venuemanangement'),
                ['readonly' => 'readonly', 'size' => '50', 'value' => $local_bu->location]);
            $mform->addRule('location', get_string('missinglocation', 'local_venuemanangement'), 'required');
            $mform->setType('location', PARAM_TEXT);

            $mform->addElement('html', '<div id="classroomalert" class="alert alert-danger col-sm-12" style="display:none;">
                <strong style="font-size:10px;">'.get_string('classroomalreadyexist', 'local_venuemanangement').'</strong>
            </div>');

            $mform->addElement('text', 'classroom', get_string('classroom', 'local_venuemanangement'),
                ['size' => '50', 'value' => $venuemanangement->classroom]);
            $mform->addRule('classroom', get_string('missingclassroom', 'local_venuemanangement'), 'required');
            $mform->setType('classroom', PARAM_TEXT);

            $mform->addElement('hidden', 'locationid', $venuemanangement->locationid);
            $mform->setType('locationid', PARAM_INT);

            $mform->addElement('text', 'capacity', get_string('capacity', 'local_venuemanangement'),
                ['size' => '10', 'value' => $venuemanangement->capacity]);
            $mform->addRule('capacity', get_string('missingcapacity', 'local_venuemanangement'), 'required');
            $mform->addRule('capacity', get_string('numericfield', 'local_venuemanangement'), 'numeric');
            $mform->setType('capacity', PARAM_INT);

            $mform->addElement('hidden', 'id', $venuemanangement->id);
            $mform->setType('id', PARAM_INT);

        } else {

            // ✅ Fetch BU list safely (avoiding "current()" crash)
            $bulistrow = $DB->get_record_sql("SELECT param1 FROM {user_info_field} WHERE shortname='businessunit'");
            $bulist = $bulistrow->param1 ?? '';
            $bulist_array = !empty($bulist) ? explode("\n", $bulist) : [];

            $buoptions = [];
            foreach ($bulist_array as $value) {
                $value = trim($value);
                if ($value !== '') $buoptions[$value] = $value;
            }

            // ✅ Location dropdown
            $locationoptions = ['0' => 'Select Location'];
            $venuemanangement_detail = $DB->get_records_sql('SELECT id, location FROM {local_bu}');

            foreach ($venuemanangement_detail as $venue) {
                $locationoptions[$venue->id] = $venue->location;
            }

            $mform->addElement(
                'selectwithlink',
                'locationid',
                get_string('location','local_venuemanangement'),
                $locationoptions,
                null,
                ['link' => $CFG->wwwroot.'/local/venuemanangement/addbu.php', 'label' => get_string('addlocation','local_venuemanangement')]
            );
            $mform->setType('locationid', PARAM_INT);

            $mform->addElement('html', '<div id="classroomalert" class="alert alert-danger col-sm-12" style="display:none;">
                <strong style="font-size:10px;">'.get_string('classroomalreadyexist', 'local_venuemanangement').'</strong>
            </div>');

            $mform->addElement('text', 'classroom', get_string('classroom', 'local_venuemanangement'), 'size="50"');
            $mform->addRule('classroom', get_string('missingclassroom', 'local_venuemanangement'), 'required');
            $mform->setType('classroom', PARAM_TEXT);

            $mform->addElement('text', 'capacity', get_string('capacity', 'local_venuemanangement'), 'size="10"');
            $mform->addRule('capacity', get_string('missingcapacity', 'local_venuemanangement'), 'required');
            $mform->addRule('capacity', get_string('numericfield', 'local_venuemanangement'), 'numeric');
            $mform->setType('capacity', PARAM_INT);

            $mform->addElement('hidden', 'id', 0);
            $mform->setType('id', PARAM_INT);
        }

        $this->add_action_buttons();
    }
}
