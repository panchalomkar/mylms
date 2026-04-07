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
 
//include_once('/../lib/scss_lib.php');
//theme_remui_get_pre_scss($theme='');
//$id =  $SESSION->currenteditingcompany;  
$id= '';
if(!empty($SESSION->currenteditingcompany)){
    $id = $SESSION->currenteditingcompany;
}else if(\iomad::is_company_user()){
    $id = \iomad::is_company_user();
}  
 if(!empty($id)){
     
$page = new admin_settingpage('theme_remui_colours', get_string('colours_settings', 'theme_remui'));

$page->add(new admin_setting_heading('theme_remui_colours', get_string('colours_headingsub', 'theme_remui'), format_text(get_string('colours_desc', 'theme_remui'), FORMAT_MARKDOWN)));
  

    // Raw SCSS to include before the content.
   /* $setting = new admin_setting_configtextarea('theme_remui/scsspre_'.$id,
    get_string('rawscsspre', 'theme_remui'), get_string('rawscsspre_desc', 'theme_remui'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);*/

    // Variable $brandprimary.
 
        $name = 'theme_remui/brandprimary_'.$id;
        $title = get_string('brandprimary', 'theme_remui');
        $description = get_string('brandprimary_desc', 'theme_remui');
        $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
        //$setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

    

 
    // Background varius pages
    $name = 'theme_remui/bodycolorotherpages_'.$id;
    $title = get_string('bodybackground', 'theme_remui');
    $description = get_string('bodybackground_desc', 'theme_remui');  
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    //$setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // @bodyBackground setting.
    $name = 'theme_remui/bodybackground_'.$id;    
    $title = get_string('bodycolor_other_pages', 'theme_remui'); 
    $description = get_string('bodycolor_other_pages_desc', 'theme_remui');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
   // $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

  
    // marketing tile text background
    $name = 'theme_remui/marketimagebg_'.$id;
    $title = get_string('marketimagebg', 'theme_remui');
    $description = get_string('marketimagebg_desc', 'theme_remui');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
   // $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);


    // Raw SCSS to include after the content.
    $setting = new admin_setting_configtextarea('theme_remui/css_'.$id, get_string('rawcss', 'theme_remui'),
    get_string('rawcss_desc', 'theme_remui'), '', PARAM_RAW);
    //$setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    

// Must add the page after definiting all the settings!
   
    $settings->add($page);
    
 }else{
    redirect(new moodle_url('/local/mt_dashboard/index.php'));
 }