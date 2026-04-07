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
* Presets settings page file.
*
* @package    theme_remui
* @copyright  2017 OCJ
* @credits    theme_boost - MoodleHQ
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

$page = new admin_settingpage('theme_remui_studentportal', get_string('other_configuration', 'theme_remui'));

//Toggle my courses dropdown on header.
$name  = 'theme_remui/enabletenantinfo';
$title = get_string('enabletenantinfo', 'theme_remui');
$description = get_string('enabletenantdesc', 'theme_remui');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

/**
 * Student Portal : Email Notification Config
 * @author Abhishek V
 * @since  11-02-2021
 * @ticket #52
 */
$name = 'theme_remui/emailnotificationinfo';
$heading = get_string('emailnotificationinfo', 'theme_remui');
$a = new stdClass();
$a->wwwroot = $CFG->wwwroot;
$information = get_string('emailnotificationinfodesc', 'theme_remui',$a);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

/**
 * Student Portal : Site setting access by role
 * @author Sanyogita D
 * @since  19-05-2021
 * @ticket #752
 */
if(is_siteadmin()){
    $name = 'theme_remui/site_setting_access_by_role';
    $heading = get_string('site_setting_access_by_role', 'theme_remui');
    $a = new stdClass();
    $a->wwwroot = $CFG->wwwroot;
    $information = get_string('site_setting_access_by_role_desc', 'theme_remui',$a);
    $setting = new admin_setting_heading($name, $heading, $information);
    $page->add($setting);
}

/**
 * Student Portal : Manage Student frontpage
 * @author Dnyaneshwar K.
 * @since  06-05-2019
 * @ticket #418
 */
$name = 'theme_remui/studentportalinfo';
$heading = get_string('studentportalinfo', 'theme_remui');
$a = new stdClass();
$a->wwwroot = $CFG->wwwroot;
$information = get_string('studentportalnav', 'theme_remui',$a);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// This is the descriptor for the custom navigation link
$name = 'theme_remui/customnavigationinfo';
$heading = get_string('customnavigationinfo', 'theme_remui');
$a = new stdClass();
$a->wwwroot = $CFG->wwwroot;
$information = get_string('customnavigationinfodesc', 'theme_remui',$a);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);


/*
 * Add switch language dropdown on header
 * @author Akshay P.
 * @since 029-1-2021
 * @Ticket #489
 * @paradiso
 */
$name = 'theme_remui/enablelangdropdown';
$heading = get_string('enablelangdropdown', 'theme_remui');
$information = '';
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);
$name = 'theme_remui/enable_lang_dropdown';
$title = get_string('enable_lang_dropdown', 'theme_remui');
$description = get_string('enable_lang_dropdown_desp', 'theme_remui');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/enableloginlangdropdown';
$heading = get_string('enableloginlangdropdown', 'theme_remui');
$information = '';
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);
$name = 'theme_remui/enable_login_lang_dropdown';
$title = get_string('enable_login_lang_dropdown', 'theme_remui');
$description = get_string('enable_login_lang_dropdown_desp', 'theme_remui');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


$name = 'theme_remui/enablevideoconverter';
$heading = get_string('enablevideoconverter', 'theme_remui');
$information = '';
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);
$name = 'theme_remui/enable_video_resolution';
$title = get_string('enable_video_resolution', 'theme_remui');
$description = get_string('enable_video_resolution_desp', 'theme_remui');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// This is the descriptor for My Courses dropdown in header
$name = 'theme_remui/mycoursesdropdowninfo';
$heading = get_string('dropdowninfo', 'theme_remui');
$information = get_string('dropdowninfodesc', 'theme_remui');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

//Toggle my courses dropdown on header.
$name  = 'theme_remui/hidemycoursesdropdown';
$title = get_string('mycoursesdropdown', 'theme_remui');
$description = get_string('dropdownmycoursesdesc', 'theme_remui');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// This is the descriptor for enable learningpath tab on course catalog
 $name = 'theme_remui/enablelptab';
 $heading = get_string('lptabinfo', 'theme_remui');
 $information = get_string('lptabdesc', 'theme_remui');
 $setting = new admin_setting_heading($name, $heading, $information);
 $page->add($setting);
 
 // Toggle learningpath tab enable on course catalog.
 $name = 'theme_remui/enable_lp_field';
 $title = get_string('lptab', 'theme_remui');
 $description = get_string('lptabdesc', 'theme_remui');
 $default = 0;
 $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
 $page->add($setting);

$name = 'theme_remui/show_back_to_top';
$heading = get_string('show_back_to_top', 'theme_remui');
$information = '';
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// back to top button
$name = 'theme_remui/showbacktotop';
$title = get_string('showbacktotop', 'theme_remui');
$description = get_string('showbacktotop_desc', 'theme_remui');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


$name = 'theme_remui/paradisohideduedatemessage';
$heading = get_string('paradisohideduedatemessage', 'theme_remui');
$information = '';
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

/*  Show the tab eccomerce in the multitenant.  */
$name = 'local_paradisolms/paradiso_hide_due_date_message';
$title = get_string('paradiso_hide_due_date_message', 'local_paradisolms');
$description = get_string('paradiso_hide_due_date_message_desc', 'local_paradisolms');
$default = 0;

$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

// This is the descriptor for My Courses dropdown in header
$name = 'theme_remui/chatboatinfo';
$heading = get_string('chatbot_heading', 'theme_remui');
$information = get_string('chatbot_heading_desc', 'theme_remui');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Chatboat setting.
$name = 'theme_remui/chatboat';
$title = get_string('chatbot', 'theme_remui');
$description = get_string('chatbot_desc', 'theme_remui');
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);




$settings->add($page);