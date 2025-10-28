<?php

defined('MOODLE_INTERNAL')||die();
global $SESSION;
$company = "";
if(!empty($SESSION->wall_selected_company)){
    $company = $SESSION->wall_selected_company;
}
if($company){
    $socialbgimg = 'socialbgimg_'.$company;
} else {
    $socialbgimg = 'socialbgimg';
}
$edit_expire_min = 'edit_expire_min';
$pagecontainer = 'pagecontainer';

if($ADMIN->fulltree){

    $settings = new \admin_settingpage('local_social_wall', get_string('pluginname', 'local_social_wall'));
    $ADMIN->add('localplugins', $settings);
    
    $options = array(
        '5'       => '5 minutes',
        '10'      => '10 minutes',
        '15'      => '15 minutes',
        '30'      => '30 minutes',
        '60'      => '1 hour',
        '120'     => '2 hour',
    );
    
    /*  Show the dropdown for time limit to edit post.  */
    $name = 'local_social_wall/'.$edit_expire_min;
    $title = get_string('edit_expire_min', 'local_social_wall');
    $description = get_string('edit_expire_min', 'local_social_wall');
    $default = 30;
    
    $setting = new admin_setting_configselect($name, $title, $description, $default,$options);
    $settings->add($setting);
    
    /* socail wall header image.  */
    $name = 'theme_remui/'.$socialbgimg;
    $title = get_string('social_wall_bg', 'local_social_wall');
    $description = get_string('social_wall_desc', 'local_social_wall');
    $setting = new admin_setting_configstoredfile($name, $title, $description,$socialbgimg);
    $settings->add($setting);
    
    /*Manage height width*/
    
    $options = array(
        'full'       => 'Full',
        'grid'      => 'Grid'
    );
    
    /*  Show the dropdown for time limit to edit post.  */
    $name = 'local_social_wall/'.$pagecontainer;
    $title = get_string('page_grid', 'local_social_wall');
    $description = get_string('page_grid_desc', 'local_social_wall');
    $default = 'full';
    
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $settings->add($setting);



    $options = array(
        '2'       => '2 Posts',
        '5'       => '5 Posts',
        '10'      => '10 Posts',
        '15'      => '15 Posts',
        '20'      => '20 Posts',
    );
    
    /*  Show the dropdown for default posts needs to show on page load.  */
    $name = 'local_social_wall/postsload';
    $title = get_string('default_post_load', 'local_social_wall');
    $description = get_string('default_post_load_desc', 'local_social_wall');
    $default = 10;
    
    $setting = new admin_setting_configselect($name, $title, $description, $default,$options);
    $settings->add($setting);

    $settings->add(new admin_setting_configcolourpicker('local_social_wall/backgroundcolor',
        get_string('backgroundcolor', 'local_social_wall'),
        get_string('backgroundcolor', 'local_social_wall'), '#000', null )
    );

    $settings->add(new admin_setting_configcolourpicker('local_social_wall/backgroundcolor1',
        get_string('backgroundcolor1', 'local_social_wall'),
        get_string('backgroundcolor1', 'local_social_wall'), '#7cd5ec', null )
    );

    $settings->add(new admin_setting_configcolourpicker('local_social_wall/backgroundcolor2',
        get_string('backgroundcolor2', 'local_social_wall'),
        get_string('backgroundcolor2', 'local_social_wall'), '#434348', null )
    );

    $settings->add(new admin_setting_configcolourpicker('local_social_wall/backgroundcolor3',
        get_string('backgroundcolor3', 'local_social_wall'),
        get_string('backgroundcolor3', 'local_social_wall'), '#90ed7d', null )
    );

    $settings->add(new admin_setting_configcolourpicker('local_social_wall/backgroundcolor4',
        get_string('backgroundcolor4', 'local_social_wall'),
        get_string('backgroundcolor4', 'local_social_wall'), '#000', null )
    );
}
