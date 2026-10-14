<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin settings and navigation entry.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig || has_capability('local/businessintelligence:view', context_system::instance())) {
    $ADMIN->add('reports', new admin_externalpage(
        'local_businessintelligence_dashboard',
        get_string('pluginname', 'local_businessintelligence'),
        new moodle_url('/local/businessintelligence/index.php'),
        'local/businessintelligence:view'
    ));
}

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_businessintelligence', get_string('settings', 'local_businessintelligence'));
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $p = 'local_businessintelligence';

        $settings->add(new admin_setting_heading("$p/profilehdr", get_string('set_profilehdr', $p), get_string('set_profilehdr_desc', $p)));
        $fields = [
            'field_department'  => 'department',
            'field_designation' => 'designation',
            'field_branch'      => 'branchname',
            'field_manager'     => 'reportingmanager',
            'field_trainertype' => 'trainertype',
        ];
        foreach ($fields as $name => $default) {
            $settings->add(new admin_setting_configtext("$p/$name", get_string("set_$name", $p), '', $default, PARAM_ALPHANUMEXT));
        }
        $settings->add(new admin_setting_configtext("$p/guestpattern", get_string('set_guestpattern', $p),
            get_string('set_guestpattern_desc', $p), 'guest,external', PARAM_TEXT));

        $settings->add(new admin_setting_heading("$p/sessionhdr", get_string('set_sessionhdr', $p), get_string('set_sessionhdr_desc', $p)));
        $settings->add(new admin_setting_configtext("$p/iltmodule", get_string('set_iltmodule', $p),
            get_string('set_iltmodule_desc', $p), 'ilt', PARAM_ALPHANUMEXT));
        $settings->add(new admin_setting_configtext("$p/iltattended", get_string('set_iltattended', $p), '', '100', PARAM_SEQUENCE));
        $settings->add(new admin_setting_configtext("$p/iltpartial", get_string('set_iltpartial', $p), '', '90', PARAM_SEQUENCE));
        $settings->add(new admin_setting_configtext("$p/iltnoshow", get_string('set_iltnoshow', $p), '', '80', PARAM_SEQUENCE));
        $settings->add(new admin_setting_configtext("$p/trainerroles", get_string('set_trainerroles', $p),
            get_string('set_trainerroles_desc', $p), '', PARAM_TEXT));
        $settings->add(new admin_setting_configtext("$p/iltinstructorfield", get_string('set_iltinstructorfield', $p),
            get_string('set_iltinstructorfield_desc', $p), '', PARAM_ALPHANUMEXT));
        $settings->add(new admin_setting_configcheckbox("$p/usezoom", get_string('set_usezoom', $p), '', 1));
        $settings->add(new admin_setting_configtext("$p/gmeetmodule", get_string('set_gmeetmodule', $p),
            get_string('set_gmeetmodule_desc', $p), 'googlemeet', PARAM_ALPHANUMEXT));

        $settings->add(new admin_setting_heading("$p/thresholdhdr", get_string('set_thresholdhdr', $p), ''));
        $settings->add(new admin_setting_configtext("$p/weakthreshold", get_string('set_weakthreshold', $p), '', 60, PARAM_INT));
        $settings->add(new admin_setting_configtext("$p/greenthreshold", get_string('set_greenthreshold', $p), '', 80, PARAM_INT));
        $settings->add(new admin_setting_configduration("$p/cachettl", get_string('set_cachettl', $p),
            get_string('set_cachettl_desc', $p), 600, 60));
    }
}
