<?php

/**
 * Tenant image settings page file.
 * @package theme_remui
 * @author Manisha M.
 * @since  17-05-2019
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
    $page = new admin_settingpage('theme_remui_images', get_string('imagesettings', 'theme_remui'));

    // Favicon upload for tenant.
    $name = 'theme_remui/tenant_favicon_'.$id;
    $title = get_string ( 'favicon', 'theme_remui' );
    $description = get_string ( 'favicon_desc', 'theme_remui' );
    $setting = new admin_setting_configstoredfile( $name, $title, $description, 'tenant_favicon_'.$id, 0,
        array('maxfiles' => 1, 'accepted_types' => array('png', 'jpg', 'ico')));
   // $setting->set_updatedcallback ( 'theme_reset_all_caches' );
    $page->add($setting);
 

    // tenant login page image.
    $name = 'theme_remui/tenant_loginimage_'.$id;
    $title = get_string('loginimage', 'theme_remui');
    $description = get_string('loginimage_desc', 'theme_remui');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'tenant_loginimage_'.$id);
   // $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Must add the page after definiting all the settings!
    $settings->add($page);
 }else{
    redirect(new moodle_url('/local/mt_dashboard/index.php'));
}