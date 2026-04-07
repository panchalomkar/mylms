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
 * Colours settings page file.
 *
 * @packagetheme_remui
 * @copyright  2016 Chris Kenniburg
 * @creditstheme_remui - MoodleHQ
 * @licensehttp://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $USER, $CFG, $DB, $OUTPUT, $SESSION;
 
//$id =  $SESSION->currenteditingcompany;  
$id= '';
if(!empty($SESSION->currenteditingcompany)){
    $id = $SESSION->currenteditingcompany;
}else if(\iomad::is_company_user()){
    $id = \iomad::is_company_user();
}    

if(!empty($id)){
     
$page = new admin_settingpage('theme_remui_logos', get_string('logos_settings', 'theme_remui'));
$page->add(new admin_setting_heading('theme_remui_logos', get_string('logos_headingsub', 'theme_remui'), ''));

    // Logo file setting.
    $name = 'theme_remui/tenant_logo_'.$id;
    $title = get_string ( 'logo', 'theme_remui' );
    $description = get_string ( 'logo_desc', 'theme_remui' );
    $setting = new admin_setting_configstoredfile( $name, $title, $description, 'tenant_logo_'.$id, 0,
        array('maxfiles' => 1, 'accepted_types' => array('png', 'jpg', 'jpeg')));
    //$setting->set_updatedcallback ( 'theme_reset_all_caches' );
    $page->add($setting);

    // Small logo file setting. hide by sonali for Griffin DEV / Local / tenant_appearance
    /*$name = 'theme_remui/tenant_logo_compact_'.$id;
    $title = get_string('logocompact', 'theme_remui');
    $description = get_string('logocompact_desc', 'theme_remui');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'tenant_logo_compact_'.$id, 0,
        ['maxfiles' => 1, 'accepted_types' => ['.jpg', '.png']]);
   // $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);*/
    
    // Must add the page after definiting all the settings!
    $settings->add($page);
 }else{
     redirect(new moodle_url('/local/mt_dashboard/index.php'));
}