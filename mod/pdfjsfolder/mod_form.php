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
 * The main pdfjsfolder configuration form.
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_pdfjsfolder
 * @copyright  2013 Jonas Nockert <jonasnockert@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/filelib.php');

class mod_pdfjsfolder_mod_form extends moodleform_mod {
    /**
     * Defines the pdfjsfolder instance configuration form.
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        $config = get_config('pdfjsfolder');
        $mform =& $this->_form;

        // Name and description fields.
        $mform->addElement('header',
                           'general',
                           get_string('general', 'form'));
        $mform->addElement('text',
                           'name',
                           get_string('name'), array('size' => '48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name',
                        get_string('maximumchars', '', 255),
                        'maxlength',
                        255,
                        'client');
        $this->add_intro_editor(false);

        // Option for showing folder inline or on separate page.
        $mform->addElement(
            'select',
            'display',
            get_string('display', 'pdfjsfolder'),
            array(PDFJS_FOLDER_DISPLAY_PAGE => get_string('displaypage', 'pdfjsfolder')));
        $mform->addHelpButton('display', 'display', 'pdfjsfolder');

        // Option for showing sub-folders expanded or collapsed.
        $mform->addElement('advcheckbox',
                           'showexpanded',
                           get_string('showexpanded', 'pdfjsfolder'));
        $mform->addHelpButton('showexpanded', 'showexpanded', 'pdfjsfolder');
        $mform->setDefault('showexpanded', $config->showexpanded);

        // Option for opening PDFs in new tabs or windows.
        $mform->addElement('advcheckbox',
                           'openinnewtab',
                           get_string('openinnewtab', 'pdfjsfolder'));
        $mform->addHelpButton('openinnewtab', 'openinnewtab', 'pdfjsfolder');
        $mform->setDefault('openinnewtab', $config->openinnewtab);

        // Folder fields.
        $mform->addElement('header',
                           'pdf_fieldset',
                           get_string('pdf_fieldset', 'pdfjsfolder'));

        // Folder file manager.
        $options = array('subdirs' => true,
                         'maxbytes' => 0,
                         'maxfiles' => -1,
                         'accepted_types' => array('.pdf'));
        $mform->addElement(
            'filemanager',
            'pdfs',
            get_string('pdfs', 'pdfjsfolder'),
            null,
            $options);
        $mform->addHelpButton('pdfs', 'pdfs', 'pdfjsfolder');
        $mform->addRule('pdfs', null, 'required', null, 'client');
        
        $js = 'window.addEventListener("load", function () {
            $(document).ready(function() {
            $(".filemanager  .dndupload-message").html("'.get_string('pdflabel', 'pdfjsfolder').'");
            })
            }, false)';
        $mform->addElement('html', html_writer::tag('script', $js));


        // Standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Prepares the form before data are set.
     *
     * @param array $data to be set
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $options = array('subdirs' => true,
                             'maxbytes' => 0,
                             'maxfiles' => -1);
            $draftitemid = file_get_submitted_draft_itemid('pdfs');
            file_prepare_draft_area($draftitemid,
                                    $this->context->id,
                                    'mod_pdfjsfolder',
                                    'pdfs',
                                    0,
                                    $options);
            $defaultvalues['pdfs'] = $draftitemid;
        }
    }

    /**
     * Validates the form input.
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array eventual errors indexed by the field name
     */
    public function validation($data, $files) {
        $errors = array();

        // On-view completion can not work together with display
        // inline option.
        if (empty($errors['completion']) &&
                array_key_exists('completion', $data) &&
                $data['completion'] == COMPLETION_TRACKING_AUTOMATIC &&
                !empty($data['completionview']) &&
                $data['display'] == PDFJS_FOLDER_DISPLAY_INLINE) {
            $errors['completion'] = get_string('noautocompletioninline', 'pdfjsfolder');
        }

        return $errors;
    }
}
