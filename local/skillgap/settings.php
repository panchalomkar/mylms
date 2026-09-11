<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category(
        'local_skillgap_cat',
        get_string('pluginname', 'local_skillgap')
    ));

    // Settings page.
    $settings = new admin_settingpage(
        'local_skillgap_settings',
        get_string('settings', 'local_skillgap')
    );

    $settings->add(new admin_setting_configpasswordunmask(
        'local_skillgap/openai_apikey',
        get_string('settings_openai_key', 'local_skillgap'),
        get_string('settings_openai_key_desc', 'local_skillgap'),
        ''
    ));

    $ADMIN->add('local_skillgap_cat', $settings);

    // Admin pages.
    $ADMIN->add('local_skillgap_cat', new admin_externalpage(
        'local_skillgap_admin',
        get_string('admin_dashboard', 'local_skillgap'),
        new moodle_url('/local/skillgap/admin/index.php')
    ));

    $ADMIN->add('local_skillgap_cat', new admin_externalpage(
        'local_skillgap_departments',
        get_string('departments', 'local_skillgap'),
        new moodle_url('/local/skillgap/admin/departments.php')
    ));

    $ADMIN->add('local_skillgap_cat', new admin_externalpage(
        'local_skillgap_designations',
        get_string('designations', 'local_skillgap'),
        new moodle_url('/local/skillgap/admin/designations.php')
    ));

    $ADMIN->add('local_skillgap_cat', new admin_externalpage(
        'local_skillgap_competencies',
        get_string('competencies', 'local_skillgap'),
        new moodle_url('/local/skillgap/admin/competencies.php')
    ));

    $ADMIN->add('local_skillgap_cat', new admin_externalpage(
        'local_skillgap_questions',
        get_string('questions', 'local_skillgap'),
        new moodle_url('/local/skillgap/admin/questions.php')
    ));

    $ADMIN->add('local_skillgap_cat', new admin_externalpage(
        'local_skillgap_assessments',
        get_string('assessments', 'local_skillgap'),
        new moodle_url('/local/skillgap/admin/assessments.php')
    ));

    $ADMIN->add('local_skillgap_cat', new admin_externalpage(
        'local_skillgap_clusters',
        get_string('clusters', 'local_skillgap'),
        new moodle_url('/local/skillgap/admin/clusters.php')
    ));

    $ADMIN->add('local_skillgap_cat', new admin_externalpage(
        'local_skillgap_promotion',
        get_string('promotion', 'local_skillgap'),
        new moodle_url('/local/skillgap/admin/promotion.php')
    ));

    $ADMIN->add('local_skillgap_cat', new admin_externalpage(
        'local_skillgap_weakzones',
        get_string('weakzones', 'local_skillgap'),
        new moodle_url('/local/skillgap/admin/weakzones.php')
    ));
}
