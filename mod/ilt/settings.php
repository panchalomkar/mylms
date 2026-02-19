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

require_once($CFG->dirroot . '/mod/ilt/lib.php');

$settings->add(new admin_setting_configtext(
    'ilt_fromaddress',
    get_string('setting:fromaddress_caption', 'ilt'),
    get_string('setting:fromaddress', 'ilt'),
    get_string('setting:fromaddressdefault', 'ilt'),
    "/^((?:[\w\.\-])+\@(?:(?:[a-zA-Z\d\-])+\.)+(?:[a-zA-Z\d]{2,4}))$/",
    30
));

// Load roles.
$choices = array();
if ($roles = role_fix_names(get_all_roles(), context_system::instance())) {
    foreach ($roles as $role) {
        $choices[$role->id] = format_string($role->localname);
    }
}

$settings->add(new admin_setting_configmultiselect(
    'ilt_session_roles',
    get_string('setting:sessionroles_caption', 'ilt'),
    get_string('setting:sessionroles', 'ilt'),
    array(),
    $choices
));


$settings->add(new admin_setting_heading(
    'ilt_manageremail_header',
    get_string('manageremailheading', 'ilt'),
    ''
));

$settings->add(new admin_setting_configcheckbox(
    'ilt_addchangemanageremail',
    get_string('setting:addchangemanageremail_caption', 'ilt'),
    get_string('setting:addchangemanageremail', 'ilt'),
    0
));

$settings->add(new admin_setting_configtext(
    'ilt_manageraddressformat',
    get_string('setting:manageraddressformat_caption', 'ilt'),
    get_string('setting:manageraddressformat', 'ilt'),
    get_string('setting:manageraddressformatdefault', 'ilt'),
    PARAM_TEXT
));

$settings->add(new admin_setting_configtext(
    'ilt_manageraddressformatreadable',
    get_string('setting:manageraddressformatreadable_caption', 'ilt'),
    get_string('setting:manageraddressformatreadable', 'ilt'),
    get_string('setting:manageraddressformatreadabledefault', 'ilt'),
    PARAM_NOTAGS
));

$settings->add(new admin_setting_heading('ilt_cost_header', get_string('costheading', 'ilt'), ''));

$settings->add(new admin_setting_configcheckbox(
    'ilt_hidecost',
    get_string('setting:hidecost_caption', 'ilt'),
    get_string('setting:hidecost', 'ilt'),
    0
));

$settings->add(new admin_setting_configcheckbox(
    'ilt_hidediscount',
    get_string('setting:hidediscount_caption', 'ilt'),
    get_string('setting:hidediscount', 'ilt'),
    0
));

$settings->add(new admin_setting_heading('ilt_icalendar_header', get_string('icalendarheading', 'ilt'), ''));

$settings->add(new admin_setting_configcheckbox(
    'ilt_oneemailperday',
    get_string('setting:oneemailperday_caption', 'ilt'),
    get_string('setting:oneemailperday', 'ilt'),
    0
));

$settings->add(new admin_setting_configcheckbox(
    'ilt_disableicalcancel',
    get_string('setting:disableicalcancel_caption', 'ilt'),
    get_string('setting:disableicalcancel', 'ilt'),
    0
));

// List of existing custom fields.
$html  = ilt_list_of_customfields();
$html .= html_writer::start_tag('p');
$url   = new moodle_url('/mod/ilt/customfield.php', array('id' => 0));
$html .= html_writer::link($url, get_string('addnewfieldlink', 'ilt'));
$html .= html_writer::end_tag('p');

$settings->add(new admin_setting_heading('ilt_customfields_header', get_string('customfieldsheading', 'ilt'), $html));

// List of existing site notices.
$html  = ilt_list_of_sitenotices();
$html .= html_writer::start_tag('p');
$url  = new moodle_url('/mod/ilt/sitenotice.php', array('id' => 0));
$html .= html_writer::link($url, get_string('addnewnoticelink', 'ilt'));
$html .= html_writer::end_tag('p');

$settings->add(new admin_setting_heading('ilt_sitenotices_header', get_string('sitenoticesheading', 'ilt'), $html));
