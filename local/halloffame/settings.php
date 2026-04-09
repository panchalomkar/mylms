<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_halloffame_settings',
        get_string('pluginname', 'local_halloffame')
    );
    $ADMIN->add('localplugins', $settings);

    // ── General ─────────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_halloffame/general_heading',
        get_string('settings_general', 'local_halloffame'), ''
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_halloffame/enable_likes',
        get_string('enable_likes', 'local_halloffame'),
        get_string('enable_likes_desc', 'local_halloffame'), 1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_halloffame/enable_submissions',
        get_string('enable_submissions', 'local_halloffame'),
        get_string('enable_submissions_desc', 'local_halloffame'), 1
    ));
    $settings->add(new admin_setting_configtext(
        'local_halloffame/max_filesize_mb',
        get_string('max_filesize_mb', 'local_halloffame'),
        get_string('max_filesize_mb_desc', 'local_halloffame'), 5, PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_halloffame/allowed_filetypes',
        get_string('allowed_filetypes', 'local_halloffame'),
        get_string('allowed_filetypes_desc', 'local_halloffame'), 'pdf,jpg,jpeg,png', PARAM_TEXT
    ));
    $settings->add(new admin_setting_configselect(
        'local_halloffame/cards_per_row',
        get_string('cards_per_row', 'local_halloffame'),
        get_string('cards_per_row_desc', 'local_halloffame'),
        2, [1 => '1', 2 => '2', 3 => '3', 4 => '4']
    ));
    $settings->add(new admin_setting_configselect(
        'local_halloffame/sort_order',
        get_string('sort_order', 'local_halloffame'),
        get_string('sort_order_desc', 'local_halloffame'),
        'newest', ['newest' => 'Newest First', 'oldest' => 'Oldest First', 'popular' => 'Most Liked']
    ));

    // ── Notifications ────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_halloffame/notifications_heading',
        get_string('settings_notifications', 'local_halloffame'), ''
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_halloffame/notify_on_submission',
        get_string('notify_on_submission', 'local_halloffame'),
        get_string('notify_on_submission_desc', 'local_halloffame'), 1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_halloffame/notify_user_on_approval',
        get_string('notify_user_on_approval', 'local_halloffame'),
        get_string('notify_user_on_approval_desc', 'local_halloffame'), 1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_halloffame/notify_user_on_award',
        get_string('notify_user_on_award', 'local_halloffame'),
        get_string('notify_user_on_award_desc', 'local_halloffame'), 1
    ));

    // ── Display ──────────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_halloffame/display_heading',
        get_string('settings_display', 'local_halloffame'), ''
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_halloffame/show_in_nav',
        get_string('show_in_nav', 'local_halloffame'),
        get_string('show_in_nav_desc', 'local_halloffame'), 1
    ));
    $settings->add(new admin_setting_configtext(
        'local_halloffame/nav_label',
        get_string('nav_label', 'local_halloffame'),
        get_string('nav_label_desc', 'local_halloffame'), '', PARAM_TEXT
    ));
}

    // ── IOMAD / Departments ───────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_halloffame/iomad_heading',
        'IOMAD & Departments', ''
    ));
    $settings->add(new admin_setting_configtext(
        'local_halloffame/dept_profile_field',
        'Department profile field shortname',
        'Shortname of the custom user profile field used as the department source. Default: department.',
        'department', PARAM_ALPHANUMEXT
    ));
