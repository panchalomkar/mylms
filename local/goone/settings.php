<?php
/**
 * Resource module admin settings and defaults
 *
 * @package    local 
 * @subpackage goone
 * @copyright  2022
 * @author     Kalpana Patil <kalpana.t@paradisosolutions.com>
 * @author     Paradiso
 */

if ($hassiteconfig) {
    $moderator = get_admin();
    $site = get_site();

    $settings = new admin_settingpage('local_goone', get_string('pluginname', 'local_goone'));
    $ADMIN->add('localplugins', $settings);

    $name = 'local_goone/paradisogoonemessage';
    $heading = get_string('paradisogoonemessage', 'local_goone');
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $settings->add($setting);

    $name = 'local_goone/enable_go1';
    $title = get_string('goonetab', 'local_goone');
    $description = get_string('goonetabdesc', 'local_goone');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $settings->add($setting);

    $name = 'local_goone/go1clientid';
    $title = get_string('go1client_id', 'local_goone');
    $description = get_string('go1client_iddesc', 'local_goone');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'local_goone/go1clientsecretkey';
    $title = get_string('go1clientsecret_key', 'local_goone');
    $description = get_string('go1clientsecret_keydesc', 'local_goone');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);
}