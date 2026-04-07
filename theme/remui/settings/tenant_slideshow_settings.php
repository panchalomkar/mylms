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
 * Heading and course images settings page file.
 *
 * @packagetheme_remui
 * @copyright  2016 Chris Kenniburg
 * @creditstheme_boost - MoodleHQ
 * @licensehttp://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $SESSION;

//$id =  $SESSION->currenteditingcompany;  
$id= '';
if(!empty($SESSION->currenteditingcompany)){
    $id = $SESSION->currenteditingcompany;
}else if(\iomad::is_company_user()){
    $id = \iomad::is_company_user();
}   

if(!empty($id)){

$page = new admin_settingpage('tenant_setting_slideshow', get_string('slideshowsettings', 'theme_remui'));
$page->add(new admin_setting_heading('tenant_setting_slideshow', get_string('slideshowsettings', 'theme_remui'), ''));


// Show hide user enrollment toggle.
$name = 'theme_remui/showslideshow_'.$id;
$title = get_string('showslideshow', 'theme_remui');
$description = get_string('showslideshow_desc', 'theme_remui');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// This is the descriptor for slide
$name = 'theme_remui/slide1info_'.$id;
$heading = get_string('slide1info', 'theme_remui');
$information = get_string('slide1infodesc', 'theme_remui');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Slide title
$name = 'theme_remui/slide1title_'.$id;
$title = get_string('slidetitle', 'theme_remui');
$description = get_string('slidetitle_desc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

//Slide Description
$name = 'theme_remui/slide1content_'.$id;
$title = get_string('slidecontent', 'theme_remui');
$description = get_string('slidecontent_desc', 'theme_remui');
$default = '';
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// logo image.
$name = 'theme_remui/slide1image_'.$id;
$title = get_string('slideimage', 'theme_remui');
$description = get_string('slideimage_desc', 'theme_remui');
$setting = new admin_setting_configstoredfile($name, $title, $description, 'slide1image_'.$id);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// This is the descriptor for slide
$name = 'theme_remui/slide2info_'.$id;
$heading = get_string('slide2info', 'theme_remui');
$information = get_string('slide2infodesc', 'theme_remui');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Slide title
$name = 'theme_remui/slide2title_'.$id;
$title = get_string('slidetitle', 'theme_remui');
$description = get_string('slidetitle_desc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

//Slide Description
$name = 'theme_remui/slide2content_'.$id;
$title = get_string('slidecontent', 'theme_remui');
$description = get_string('slidecontent_desc', 'theme_remui');
$default = '';
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// logo image.
$name = 'theme_remui/slide2image_'.$id;
$title = get_string('slideimage', 'theme_remui');
$description = get_string('slideimage_desc', 'theme_remui');
$setting = new admin_setting_configstoredfile($name, $title, $description, 'slide2image_'.$id);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// This is the descriptor for slide
$name = 'theme_remui/slide3info_'.$id;
$heading = get_string('slide3info', 'theme_remui');
$information = get_string('slide3infodesc', 'theme_remui');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);
// Slide title
$name = 'theme_remui/slide3title_'.$id;
$title = get_string('slidetitle', 'theme_remui');
$description = get_string('slidetitle_desc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

//Slide Description
$name = 'theme_remui/slide3content_'.$id;
$title = get_string('slidecontent', 'theme_remui');
$description = get_string('slidecontent_desc', 'theme_remui');
$default = '';
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Slide image.
$name = 'theme_remui/slide3image_'.$id;
$title = get_string('slideimage', 'theme_remui');
$description = get_string('slideimage_desc', 'theme_remui');
$setting = new admin_setting_configstoredfile($name, $title, $description, 'slide3image_'.$id);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Must add the page after definiting all the settings!
$settings->add($page);
}else{
    redirect(new moodle_url('/local/mt_dashboard/index.php'));
}