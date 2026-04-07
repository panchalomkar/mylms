<?php

/**
 * Tenant Login settings page.
 * @package theme_remui
 * @author Manisha M.
 * @since  22-10-2019
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
$page = new admin_settingpage('theme_remui_presets', get_string('presets_settings', 'theme_remui'));

// modchooser settings tab.
$page = new admin_settingpage('theme_remui_login', get_string('login_setting', 'theme_remui'));

// This is the descriptor for the custom navigation link
$name = 'theme_remui/logininfo';
$heading = get_string('login_setting', 'theme_remui');
$a = new stdClass();
$a->wwwroot = $CFG->wwwroot;
$information = get_string('logininfodesc', 'theme_remui',$a);
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

$name = 'theme_remui/dropdlogin_'.$id;
$title = get_string('loginlayout', 'theme_remui');
$description = get_string('login_messagedesc', 'theme_remui');
$default = 'login_right'; // Default keep login right
$options = array();
$options[''] = get_string('selectlog', 'theme_remui');
$options['login_right'] = get_string('selectright', 'theme_remui');
$options['login_center'] = get_string('selectcenter', 'theme_remui');
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/loginboxopacity_'.$id;
$title = get_string('loginopacity', 'theme_remui');
$description = '';
$default = 1;
$options = array();
$options[0] = get_string('choosedots');
for ($i=1; $i <=10 ; $i++) { 
	$index = (string) $i/10 ;

	$options[' '.$index] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/logintext_'.$id;
$title = get_string('login_message', 'theme_remui');
$description = get_string('login_messagedesc', 'theme_remui');
$default = get_string('account', 'theme_remui');
$default = get_string('account', 'theme_remui');
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/footertext_'.$id;
$title = get_string('login_footer', 'theme_remui');
$description = get_string('login_footerdescription', 'theme_remui');
$default = get_string('footer_cont', 'theme_remui');
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$settings->add($page);

}else{
    redirect(new moodle_url('/local/mt_dashboard/index.php'));
}