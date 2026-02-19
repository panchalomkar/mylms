<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    // Create settings page and add to admin menus.
    $settings = new admin_settingpage('local_ticketing', get_string('pluginname', 'local_ticketing'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtextarea('local_ticketing/assingtoemails',
                    'Emails to assign ticket',
                    'Comma separated emails', ''));
}

