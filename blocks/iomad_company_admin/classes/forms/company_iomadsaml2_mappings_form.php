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
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

defined('MOODLE_INTERNAL') || die;

use \iomad;
use \company;
use \moodle_url;
use context_system;
use auth_iomadsaml2\utils;
use core_text;
use moodleform;

class company_iomadsaml2_mappings_form extends moodleform {
    public function definition() {
        global $CFG, $PAGE, $DB, $postfix;

        $mform = & $this->_form;

        $strrequired = get_string('required');

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);

        $authplugin = get_auth_plugin('iomadsaml2');
        $auth = $authplugin->authtype;
        $userfields = $authplugin->userfields;
        $helptext = get_string('auth_updatelocal_expl', 'auth');
        $helptext .= get_string('auth_fieldlock_expl', 'auth');
        $helptext .= get_string('auth_updateremote_expl', 'auth');

        $mapremotefields = true;
        $updateremotefields = false;
        // get all of the profile field categories.
        $profilecategories = iomad::iomad_filter_profile_categories($DB->get_records('user_info_category'));
        $customfields = [];
        if (!empty($profilecategories)) {
            $customfields = $DB->get_records_sql_menu("SELECT id,concat('profile_field_',shortname)
                                                  FROM {user_info_field}
                                                  WHERE categoryid IN (" . implode(',', array_keys($profilecategories)) . ")");
            $customfields = array_values($customfields);
        }
        
        // Introductory explanation and help text.
        $mform->addElement('html', "<h2>" . format_string(get_string('pluginname', 'auth_iomadsaml2') . " : " .
                                                          get_string('auth_data_mapping', 'auth')) . "</h2>");

        // Generate the list of options.
        $lockoptions = array ('unlocked'        => get_string('unlocked', 'auth_iomadsaml2'),
                              'unlockedifempty' => get_string('unlockedifempty', 'auth_iomadsaml2'),
                              'locked'          => get_string('locked', 'auth_iomadsaml2'));
        $updatelocaloptions = array('oncreate'  => get_string('update_oncreate', 'auth_iomadsaml2'),
                                    'onlogin'   => get_string('update_onlogin', 'auth_iomadsaml2'));
        $updateextoptions = array('0'  => get_string('update_never', 'auth_iomadsaml2'),
                                  '1'  => get_string('update_onupdate', 'auth_iomadsaml2'));
    
        // Generate the list of profile fields to allow updates / lock.
        if (!empty($customfields)) {
            $userfields = array_merge($userfields, $customfields);
            $customfieldname = $DB->get_records('user_info_field', null, '', 'shortname, name');
        }

        foreach ($userfields as $field) {
            // Define the fieldname we display to the  user.
            // this includes special handling for some profile fields.
            $fieldname = $field;
            $fieldnametoolong = false;
            if ($fieldname === 'lang') {
                $fieldname = get_string('language');
            } else if (!empty($customfields) && in_array($field, $customfields)) {
                // If custom field then pick name from database.
                $fieldshortname = str_replace('profile_field_', '', $fieldname);
                $fieldname = $customfieldname[$fieldshortname]->name;
                if (core_text::strlen($fieldshortname) > 67) {
                    // If custom profile field name is longer than 67 characters we will not be able to store the setting
                    // such as 'field_updateremote_profile_field_NOTSOSHORTSHORTNAME' in the database because the character
                    // limit for the setting name is 100.
                    $fieldnametoolong = true;
                }
            } else if ($fieldname === 'lang') {
                $fieldname = get_string('language');
            } else if ($fieldname == 'url') {
                $fieldname = get_string('webpage');
            } else if ($fieldname == 'phone1') {
                $fieldname = get_string('phone1', 'auth_iomadsaml2');
            } else {
                $fieldname = get_string($fieldname);
            }

            // Generate the list of fields / mappings.
            if ($fieldnametoolong) {
                // Display a message that the field can not be mapped because it's too long.
                $url = new moodle_url('/user/profile/index.php');
                $a = (object)['fieldname' => s($fieldname), 'shortname' => s($field), 'charlimit' => 67, 'link' => ''];
                $mform->addElement('static', $auth.'/field_not_mapped_'.sha1($field) . $postfix, '',
                                    get_string('cannotmapfield', 'auth', $a));
            } else if ($mapremotefields) {
                // We are mapping to a remote field here.
                // Mapping.
                $mform->addElement('text', "field_map_{$field}{$postfix}",
                                    get_string('auth_fieldmapping', 'auth', $fieldname));
                $mform->setType("field_map_{$field}{$postfix}", PARAM_RAW);

                // Update local.
                $mform->addElement('select', "field_updatelocal_{$field}{$postfix}",
                                    get_string('auth_updatelocalfield', 'auth', $fieldname),
                                    $updatelocaloptions);

                // Update remote.
                if ($updateremotefields) {
                    $mform->addElement('select', "field_updateremote_{$field}{$postfix}",
                                        get_string('auth_updateremotefield', 'auth', $fieldname),
                                        $updateextoptions);
                }

                // Lock fields.
                $mform->addElement('select', "field_lock_{$field}{$postfix}",
                                    get_string('auth_fieldlockfield', 'auth', $fieldname),
                                    $lockoptions);
            } else {
                // Lock fields Only.
                $mform->addElement('select', "field_lock_{$field}{$postfix}",
                                    get_string('auth_fieldlockfield', 'auth', $fieldname),
                                    $lockoptions);
            }
        }

        // Disable the onchange popup.
        $mform->disable_form_change_checker();

        $this->add_action_buttons();
    }
}
