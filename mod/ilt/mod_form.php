<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Copyright (C) 2007-2011 Catalyst IT (http://www.catalyst.net.nz)
 * Copyright (C) 2011-2013 Totara LMS (http://www.totaralms.com)
 * Copyright (C) 2014 onwards Catalyst IT (http://www.catalyst-eu.net)
 *
 * @package    mod
 * @subpackage ilt
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @author     Alastair Munro <alastair.munro@totaralms.com>
 * @author     Aaron Barnes <aaron.barnes@totaralms.com>
 * @author     Francois Marier <francois@catalyst.net.nz>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/ilt/lib.php');

class mod_ilt_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG;

        $mform =& $this->_form;

        // General.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $mform->addElement('text', 'thirdparty', get_string('thirdpartyemailaddress', 'ilt'), array('size' => '64'));
        $mform->setType('thirdparty', PARAM_NOTAGS);
        $mform->addHelpButton('thirdparty', 'thirdpartyemailaddress', 'ilt');

        $mform->addElement('checkbox', 'thirdpartywaitlist', get_string('thirdpartywaitlist', 'ilt'));
        $mform->addHelpButton('thirdpartywaitlist', 'thirdpartywaitlist', 'ilt');

        $display = array();
        for ($i = 0; $i <= 18; $i += 2) {
            $display[$i] = $i;
        }
        $mform->addElement('select', 'display', get_string('sessionsoncoursepage', 'ilt'), $display);
        $mform->setDefault('display', 6);
        $mform->addHelpButton('display', 'sessionsoncoursepage', 'ilt');

        $mform->addElement('checkbox', 'approvalreqd', get_string('approvalreqd', 'ilt'));
        $mform->addHelpButton('approvalreqd', 'approvalreqd', 'ilt');

        if (has_capability('mod/ilt:configurecancellation', $this->context)) {
            $mform->addElement('advcheckbox', 'allowcancellationsdefault', get_string('allowcancellationsdefault', 'ilt'));
            $mform->setDefault('allowcancellationsdefault', 1);
            $mform->addHelpButton('allowcancellationsdefault', 'allowcancellationsdefault', 'ilt');
        }

        $mform->addElement('header', 'calendaroptions', get_string('calendaroptions', 'ilt'));

        $calendaroptions = array(
            ILT_CAL_NONE   => get_string('none'),
            ILT_CAL_COURSE => get_string('course'),
            ILT_CAL_SITE   => get_string('site')
        );
        $mform->addElement('select', 'showoncalendar', get_string('showoncalendar', 'ilt'), $calendaroptions);
        $mform->setDefault('showoncalendar', ILT_CAL_COURSE);
        $mform->addHelpButton('showoncalendar', 'showoncalendar', 'ilt');

        $mform->addElement('advcheckbox', 'usercalentry', get_string('usercalentry', 'ilt'));
        $mform->setDefault('usercalentry', true);
        $mform->addHelpButton('usercalentry', 'usercalentry', 'ilt');

        $mform->addElement('text', 'shortname', get_string('shortname'), array('size' => 32, 'maxlength' => 32));
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addHelpButton('shortname', 'shortname', 'ilt');
        $mform->addRule('shortname', null, 'maxlength', 32);

        // Request message.
        $mform->addElement('header', 'request', get_string('requestmessage', 'ilt'));
        $mform->addHelpButton('request', 'requestmessage', 'ilt');

        $mform->addElement('text', 'requestsubject', get_string('email:subject', 'ilt'), array('size' => '55'));
        $mform->setType('requestsubject', PARAM_TEXT);
        $mform->setDefault('requestsubject', get_string('setting:defaultrequestsubjectdefault', 'ilt'));
        $mform->disabledIf('requestsubject', 'approvalreqd');

        $mform->addElement('textarea', 'requestmessage', get_string('email:message', 'ilt'), 'wrap="virtual" rows="15" cols="70"');
        $mform->setDefault('requestmessage', get_string('setting:defaultrequestmessagedefault', 'ilt'));
        $mform->disabledIf('requestmessage', 'approvalreqd');

        $mform->addElement('textarea', 'requestinstrmngr', get_string('email:instrmngr', 'ilt'), 'wrap="virtual" rows="10" cols="70"');
        $mform->setDefault('requestinstrmngr', get_string('setting:defaultrequestinstrmngrdefault', 'ilt'));
        $mform->disabledIf('requestinstrmngr', 'approvalreqd');

        // Confirmation message.
        $mform->addElement('header', 'confirmation', get_string('confirmationmessage', 'ilt'));
        $mform->addHelpButton('confirmation', 'confirmationmessage', 'ilt');

        $mform->addElement('text', 'confirmationsubject', get_string('email:subject', 'ilt'), array('size' => '55'));
        $mform->setType('confirmationsubject', PARAM_TEXT);
        $mform->setDefault('confirmationsubject', get_string('setting:defaultconfirmationsubjectdefault', 'ilt'));

        $mform->addElement('textarea', 'confirmationmessage', get_string('email:message', 'ilt'), 'wrap="virtual" rows="15" cols="70"');
        $mform->setDefault('confirmationmessage', get_string('setting:defaultconfirmationmessagedefault', 'ilt'));

        $mform->addElement('checkbox', 'emailmanagerconfirmation', get_string('emailmanager', 'ilt'));
        $mform->addHelpButton('emailmanagerconfirmation', 'emailmanagerconfirmation', 'ilt');

        $mform->addElement('textarea', 'confirmationinstrmngr', get_string('email:instrmngr', 'ilt'), 'wrap="virtual" rows="4" cols="70"');
        $mform->addHelpButton('confirmationinstrmngr', 'confirmationinstrmngr', 'ilt');
        $mform->disabledIf('confirmationinstrmngr', 'emailmanagerconfirmation');
        $mform->setDefault('confirmationinstrmngr', get_string('setting:defaultconfirmationinstrmngrdefault', 'ilt'));

        // Reminder message.
        $mform->addElement('header', 'reminder', get_string('remindermessage', 'ilt'));
        $mform->addHelpButton('reminder', 'remindermessage', 'ilt');

        $mform->addElement('text', 'remindersubject', get_string('email:subject', 'ilt'), array('size' => '55'));
        $mform->setType('remindersubject', PARAM_TEXT);
        $mform->setDefault('remindersubject', get_string('setting:defaultremindersubjectdefault', 'ilt'));

        $mform->addElement('textarea', 'remindermessage', get_string('email:message', 'ilt'), 'wrap="virtual" rows="15" cols="70"');
        $mform->setDefault('remindermessage', get_string('setting:defaultremindermessagedefault', 'ilt'));

        $mform->addElement('checkbox', 'emailmanagerreminder', get_string('emailmanager', 'ilt'));
        $mform->addHelpButton('emailmanagerreminder', 'emailmanagerreminder', 'ilt');

        $mform->addElement('textarea', 'reminderinstrmngr', get_string('email:instrmngr', 'ilt'), 'wrap="virtual" rows="4" cols="70"');
        $mform->addHelpButton('reminderinstrmngr', 'reminderinstrmngr', 'ilt');
        $mform->disabledIf('reminderinstrmngr', 'emailmanagerreminder');
        $mform->setDefault('reminderinstrmngr', get_string('setting:defaultreminderinstrmngrdefault', 'ilt'));

        $reminderperiod = array();
        for ($i = 1; $i <= 20; $i += 1) {
            $reminderperiod[$i] = $i;
        }
        $mform->addElement('select', 'reminderperiod', get_string('reminderperiod', 'ilt'), $reminderperiod);
        $mform->setDefault('reminderperiod', 2);
        $mform->addHelpButton('reminderperiod', 'reminderperiod', 'ilt');

        // Waitlisted message.
        $mform->addElement('header', 'waitlisted', get_string('waitlistedmessage', 'ilt'));
        $mform->addHelpButton('waitlisted', 'waitlistedmessage', 'ilt');

        $mform->addElement('text', 'waitlistedsubject', get_string('email:subject', 'ilt'), array('size' => '55'));
        $mform->setType('waitlistedsubject', PARAM_TEXT);
        $mform->setDefault('waitlistedsubject', get_string('setting:defaultwaitlistedsubjectdefault', 'ilt'));

        $mform->addElement('textarea', 'waitlistedmessage', get_string('email:message', 'ilt'), 'wrap="virtual" rows="15" cols="70"');
        $mform->setDefault('waitlistedmessage', get_string('setting:defaultwaitlistedmessagedefault', 'ilt'));

        // Cancellation message.
        $mform->addElement('header', 'cancellation', get_string('cancellationmessage', 'ilt'));
        $mform->addHelpButton('cancellation', 'cancellationmessage', 'ilt');

        $mform->addElement('text', 'cancellationsubject', get_string('email:subject', 'ilt'), array('size' => '55'));
        $mform->setType('cancellationsubject', PARAM_TEXT);
        $mform->setDefault('cancellationsubject', get_string('setting:defaultcancellationsubjectdefault', 'ilt'));

        $mform->addElement('textarea', 'cancellationmessage', get_string('email:message', 'ilt'), 'wrap="virtual" rows="15" cols="70"');
        $mform->setDefault('cancellationmessage', get_string('setting:defaultcancellationmessagedefault', 'ilt'));

        $mform->addElement('checkbox', 'emailmanagercancellation', get_string('emailmanager', 'ilt'));
        $mform->addHelpButton('emailmanagercancellation', 'emailmanagercancellation', 'ilt');

        $mform->addElement('textarea', 'cancellationinstrmngr', get_string('email:instrmngr', 'ilt'), 'wrap="virtual" rows="4" cols="70"');
        $mform->addHelpButton('cancellationinstrmngr', 'cancellationinstrmngr', 'ilt');
        $mform->disabledIf('cancellationinstrmngr', 'emailmanagercancellation');
        $mform->setDefault('cancellationinstrmngr', get_string('setting:defaultcancellationinstrmngrdefault', 'ilt'));

        $features = new stdClass;
        $features->groups = false;
        $features->groupings = false;
        $features->groupmembersonly = false;
        $features->outcomes = false;
        $features->gradecat = false;
        $features->idnumber = true;
        $this->standard_coursemodule_elements($features);

        $this->add_action_buttons();
    }

    public function data_preprocessing(&$defaultvalues) {

        // Fix manager emails.
        if (empty($defaultvalues['confirmationinstrmngr'])) {
            $defaultvalues['confirmationinstrmngr'] = null;
        } else {
            $defaultvalues['emailmanagerconfirmation'] = 1;
        }

        if (empty($defaultvalues['reminderinstrmngr'])) {
            $defaultvalues['reminderinstrmngr'] = null;
        } else {
            $defaultvalues['emailmanagerreminder'] = 1;
        }

        if (empty($defaultvalues['cancellationinstrmngr'])) {
            $defaultvalues['cancellationinstrmngr'] = null;
        } else {
            $defaultvalues['emailmanagercancellation'] = 1;
        }
    }
}
