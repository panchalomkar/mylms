<?php

/**
 * Tenant marketing tiles settings.
 * @package theme_remui
 * @author Manisha M.
 * @since  22-10-2019
*/

defined('MOODLE_INTERNAL') || die();

global $SESSION;

//$id =  $SESSION->currenteditingcompany;  
//$id =  $SESSION->currenteditingcompany;  
$id= '';
if(!empty($SESSION->currenteditingcompany)){
    $id = $SESSION->currenteditingcompany;
}else if(\iomad::is_company_user()){
    $id = \iomad::is_company_user();
}  
if(!empty($id)){
/* Marketing Spot Settings temp*/
$page = new admin_settingpage('tenant_setting_marketing', get_string('marketingheading', 'theme_remui'));
$page->add(new admin_setting_heading('tenant_setting_marketing', get_string('marketingheading', 'theme_remui'), ''));

// Toggle FP Textbox Spots.
$name = 'theme_remui/togglemarketing_'.$id;
$title = get_string('togglemarketing' , 'theme_remui');
$description = get_string('togglemarketing_desc', 'theme_remui');
$displaytop = get_string('displaytop', 'theme_remui');
$displaybottom = get_string('displaybottom', 'theme_remui');
$default = '2';
$choices = array('1'=>$displaytop, '2'=>$displaybottom);
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/hidemarketingtile_'.$id;
$title = get_string('hidemarketingtile' , 'theme_remui');
$description = get_string('marketinghidedesc', 'theme_remui');
$hide = get_string('hide', 'theme_remui');
$show = get_string('show', 'theme_remui');
$default = '1';
$choices = array('1'=>$hide, '2'=>$show);
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// This is the descriptor for Marketing Spot One
$name = 'theme_remui/marketing1info_'.$id;
$heading = get_string('marketing1', 'theme_remui');
$information = get_string('marketinginfodesc', 'theme_remui');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Marketing Spot One
$name = 'theme_remui/marketing1_'.$id;
$title = get_string('marketingtitle', 'theme_remui');
$description = get_string('marketingtitledesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Background image setting.
$name = 'theme_remui/marketing1image_'.$id;
$title = get_string('marketingimage', 'theme_remui');
$description = get_string('marketingimage_desc', 'theme_remui');
$setting = new admin_setting_configstoredfile($name, $title, $description, 'marketing1image_'.$id);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing1content_'.$id;
$title = get_string('marketingcontent', 'theme_remui');
$description = get_string('marketingcontentdesc', 'theme_remui');
$default = '';
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing1buttontext_'.$id;
$title = get_string('marketingbuttontext', 'theme_remui');
$description = get_string('marketingbuttontextdesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing1buttonurl_'.$id;
$title = get_string('marketingbuttonurl', 'theme_remui');
$description = get_string('marketingbuttonurldesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing1target_'.$id;
$title = get_string('marketingurltarget' , 'theme_remui');
$description = get_string('marketingurltargetdesc', 'theme_remui');
$target1 = get_string('marketingurltargetself', 'theme_remui');
$target2 = get_string('marketingurltargetnew', 'theme_remui');
$target3 = get_string('marketingurltargetparent', 'theme_remui');
$default = 'target1';
$choices = array('_self'=>$target1, '_blank'=>$target2, '_parent'=>$target3);
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// This is the descriptor for Marketing Spot Two
$name = 'theme_remui/marketing2info_'.$id;
$heading = get_string('marketing2', 'theme_remui');
$information = get_string('marketinginfodesc', 'theme_remui');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Marketing Spot Two.
$name = 'theme_remui/marketing2_'.$id;
$title = get_string('marketingtitle', 'theme_remui');
$description = get_string('marketingtitledesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Background image setting.
$name = 'theme_remui/marketing2image_'.$id;
$title = get_string('marketingimage', 'theme_remui');
$description = get_string('marketingimage_desc', 'theme_remui');
$setting = new admin_setting_configstoredfile($name, $title, $description, 'marketing2image_'.$id);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing2content_'.$id;
$title = get_string('marketingcontent', 'theme_remui');
$description = get_string('marketingcontentdesc', 'theme_remui');
$default = '';
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing2buttontext_'.$id;
$title = get_string('marketingbuttontext', 'theme_remui');
$description = get_string('marketingbuttontextdesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing2buttonurl_'.$id;
$title = get_string('marketingbuttonurl', 'theme_remui');
$description = get_string('marketingbuttonurldesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing2target_'.$id;
$title = get_string('marketingurltarget' , 'theme_remui');
$description = get_string('marketingurltargetdesc', 'theme_remui');
$target1 = get_string('marketingurltargetself', 'theme_remui');
$target2 = get_string('marketingurltargetnew', 'theme_remui');
$target3 = get_string('marketingurltargetparent', 'theme_remui');
$default = 'target1';
$choices = array('_self'=>$target1, '_blank'=>$target2, '_parent'=>$target3);
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// This is the descriptor for Marketing Spot Three
$name = 'theme_remui/marketing3info_'.$id;
$heading = get_string('marketing3', 'theme_remui');
$information = get_string('marketinginfodesc', 'theme_remui');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Marketing Spot Three.
$name = 'theme_remui/marketing3_'.$id;
$title = get_string('marketingtitle', 'theme_remui');
$description = get_string('marketingtitledesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Background image setting.
$name = 'theme_remui/marketing3image_'.$id;
$title = get_string('marketingimage', 'theme_remui');
$description = get_string('marketingimage_desc', 'theme_remui');
$setting = new admin_setting_configstoredfile($name, $title, $description, 'marketing3image_'.$id);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing3content_'.$id;
$title = get_string('marketingcontent', 'theme_remui');
$description = get_string('marketingcontentdesc', 'theme_remui');
$default = '';
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing3buttontext_'.$id;
$title = get_string('marketingbuttontext', 'theme_remui');
$description = get_string('marketingbuttontextdesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing3buttonurl_'.$id;
$title = get_string('marketingbuttonurl', 'theme_remui');
$description = get_string('marketingbuttonurldesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing3target_'.$id;
$title = get_string('marketingurltarget' , 'theme_remui');
$description = get_string('marketingurltargetdesc', 'theme_remui');
$target1 = get_string('marketingurltargetself', 'theme_remui');
$target2 = get_string('marketingurltargetnew', 'theme_remui');
$target3 = get_string('marketingurltargetparent', 'theme_remui');
$default = 'target1';
$choices = array('_self'=>$target1, '_blank'=>$target2, '_parent'=>$target3);
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// This is the descriptor for Marketing Spot Four
$name = 'theme_remui/marketing4info_'.$id;
$heading = get_string('marketing4', 'theme_remui');
$information = get_string('marketinginfodesc', 'theme_remui');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Marketing Spot
$name = 'theme_remui/marketing4_'.$id;
$title = get_string('marketingtitle', 'theme_remui');
$description = get_string('marketingtitledesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Background image setting.
$name = 'theme_remui/marketing4image_'.$id;
$title = get_string('marketingimage', 'theme_remui');
$description = get_string('marketingimage_desc', 'theme_remui');
$setting = new admin_setting_configstoredfile($name, $title, $description, 'marketing4image_'.$id);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing4content_'.$id;
$title = get_string('marketingcontent', 'theme_remui');
$description = get_string('marketingcontentdesc', 'theme_remui');
$default = '';
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing4buttontext_'.$id;
$title = get_string('marketingbuttontext', 'theme_remui');
$description = get_string('marketingbuttontextdesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing4buttonurl_'.$id;
$title = get_string('marketingbuttonurl', 'theme_remui');
$description = get_string('marketingbuttonurldesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing4target_'.$id;
$title = get_string('marketingurltarget' , 'theme_remui');
$description = get_string('marketingurltargetdesc', 'theme_remui');
$target1 = get_string('marketingurltargetself', 'theme_remui');
$target2 = get_string('marketingurltargetnew', 'theme_remui');
$target3 = get_string('marketingurltargetparent', 'theme_remui');
$default = 'target1';
$choices = array('_self'=>$target1, '_blank'=>$target2, '_parent'=>$target3);
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// This is the descriptor for Marketing Spot Four
$name = 'theme_remui/marketing5info_'.$id;
$heading = get_string('marketing5', 'theme_remui');
$information = get_string('marketinginfodesc', 'theme_remui');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Marketing Spot
$name = 'theme_remui/marketing5_'.$id;
$title = get_string('marketingtitle', 'theme_remui');
$description = get_string('marketingtitledesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Background image setting.
$name = 'theme_remui/marketing5image_'.$id;
$title = get_string('marketingimage', 'theme_remui');
$description = get_string('marketingimage_desc', 'theme_remui');
$setting = new admin_setting_configstoredfile($name, $title, $description, 'marketing5image_'.$id);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing5content_'.$id;
$title = get_string('marketingcontent', 'theme_remui');
$description = get_string('marketingcontentdesc', 'theme_remui');
$default = '';
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing5buttontext_'.$id;
$title = get_string('marketingbuttontext', 'theme_remui');
$description = get_string('marketingbuttontextdesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing5buttonurl_'.$id;
$title = get_string('marketingbuttonurl', 'theme_remui');
$description = get_string('marketingbuttonurldesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing5target_'.$id;
$title = get_string('marketingurltarget' , 'theme_remui');
$description = get_string('marketingurltargetdesc', 'theme_remui');
$target1 = get_string('marketingurltargetself', 'theme_remui');
$target2 = get_string('marketingurltargetnew', 'theme_remui');
$target3 = get_string('marketingurltargetparent', 'theme_remui');
$default = 'target1';
$choices = array('_self'=>$target1, '_blank'=>$target2, '_parent'=>$target3);
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// This is the descriptor for Marketing Spot Four
$name = 'theme_remui/marketing6info_'.$id;
$heading = get_string('marketing6', 'theme_remui');
$information = get_string('marketinginfodesc', 'theme_remui');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Marketing Spot
$name = 'theme_remui/marketing6_'.$id;
$title = get_string('marketingtitle', 'theme_remui');
$description = get_string('marketingtitledesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Background image setting.
$name = 'theme_remui/marketing6image_'.$id;
$title = get_string('marketingimage', 'theme_remui');
$description = get_string('marketingimage_desc', 'theme_remui');
$setting = new admin_setting_configstoredfile($name, $title, $description, 'marketing6image_'.$id);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing6content_'.$id;
$title = get_string('marketingcontent', 'theme_remui');
$description = get_string('marketingcontentdesc', 'theme_remui');
$default = '';
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing6buttontext_'.$id;
$title = get_string('marketingbuttontext', 'theme_remui');
$description = get_string('marketingbuttontextdesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing6buttonurl_'.$id;
$title = get_string('marketingbuttonurl', 'theme_remui');
$description = get_string('marketingbuttonurldesc', 'theme_remui');
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_remui/marketing6target_'.$id;
$title = get_string('marketingurltarget' , 'theme_remui');
$description = get_string('marketingurltargetdesc', 'theme_remui');
$target1 = get_string('marketingurltargetself', 'theme_remui');
$target2 = get_string('marketingurltargetnew', 'theme_remui');
$target3 = get_string('marketingurltargetparent', 'theme_remui');
$default = 'target1';
$choices = array('_self'=>$target1, '_blank'=>$target2, '_parent'=>$target3);
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Must add the page after definiting all the settings!
$settings->add($page);

}else{
    redirect(new moodle_url('/local/mt_dashboard/index.php'));
}