<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_incourse', get_string('pluginname', 'local_incourse'));

    $settings->add(new admin_setting_heading('local_incourse_header', '',
        get_string('description', 'local_incourse')));

    $settings->add(new admin_setting_configcheckbox(
        'local_incourse/enablecustomui',
        get_string('enablecustomui', 'local_incourse'),
        get_string('enablecustomui_desc', 'local_incourse'),
        1
    ));

    $ADMIN->add('localplugins', $settings);
}
