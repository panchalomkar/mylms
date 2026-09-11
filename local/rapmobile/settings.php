<?php
// =========================================================
// local/rapmobile/settings.php
// Admin panel to control tab visibility + version settings
// Go to: Site Admin → Plugins → Local plugins → RAP Mobile
// Created by Omkar
// =========================================================

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage(
        'local_rapmobile',
        get_string('pluginname', 'local_rapmobile')
    );

    $ADMIN->add('localplugins', $settings);

    // =========================================================
    // SECTION 1 — BOTTOM NAV TABS
    // =========================================================
    $settings->add(new admin_setting_heading(
        'local_rapmobile/heading_tabs_bottom',
        'Bottom Navigation Tabs',
        'Control which tabs appear in the bottom navigation bar of the mobile app.'
    ));

    $tab_bottom = [
        'tab_home'     => 'Home',
        'tab_learning' => 'Learning',
        'tab_wallfame' => 'Wall of Fame',
        'tab_game'     => 'Game',
        'tab_report'   => 'Report',
    ];

    foreach ($tab_bottom as $key => $label) {
        $settings->add(new admin_setting_configcheckbox(
            'local_rapmobile/' . $key,
            $label,
            'Show/hide the ' . $label . ' tab in bottom navigation',
            1  // default = show
        ));
    }

    // =========================================================
    // SECTION 2 — MORE MENU ITEMS
    // =========================================================
    $settings->add(new admin_setting_heading(
        'local_rapmobile/heading_tabs_more',
        'More Menu Items',
        'Control which items appear in the More menu of the mobile app.'
    ));

    $tab_more = [
        'tab_profile'      => 'Profile',
        'tab_learningpath' => 'Learning Path',
        'tab_download'     => 'Download',
        'tab_certificates' => 'Certificates',
        'tab_skillgap'     => 'Skill Gap Analysis',
        'tab_attendance'   => 'Attendance',
        'tab_live'         => 'Live',
    ];

    foreach ($tab_more as $key => $label) {
        $settings->add(new admin_setting_configcheckbox(
            'local_rapmobile/' . $key,
            $label,
            'Show/hide ' . $label . ' in More menu',
            1  // default = show
        ));
    }

    // =========================================================
    // SECTION 3 — ANDROID VERSION CONTROL
    // =========================================================
    $settings->add(new admin_setting_heading(
        'local_rapmobile/heading_android',
        'Android App Version Control',
        'Control Android app versioning and update behaviour.'
    ));

    $settings->add(new admin_setting_configtext(
        'local_rapmobile/android_current_version',
        'Current Android Version',
        'Latest released Android version e.g. 1.2.0',
        '1.0.0',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_rapmobile/android_build_number',
        'Android Build Number',
        'Build number of the current release',
        '1',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_rapmobile/android_min_version',
        'Minimum Android Version',
        'Minimum supported version — users below this will be forced to update',
        '1.0.0',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_rapmobile/android_force_update',
        'Force Update (Android)',
        'If enabled, users below the minimum version cannot use the app until they update',
        0  // default = no force
    ));

    $settings->add(new admin_setting_configtext(
        'local_rapmobile/android_store_url',
        'Android Play Store URL',
        'Google Play Store URL for the app',
        'https://play.google.com/store/apps/details?id=com.yourapp',
        PARAM_URL
    ));

    // =========================================================
    // SECTION 4 — IOS VERSION CONTROL
    // =========================================================
    $settings->add(new admin_setting_heading(
        'local_rapmobile/heading_ios',
        'iOS App Version Control',
        'Control iOS app versioning and update behaviour.'
    ));

    $settings->add(new admin_setting_configtext(
        'local_rapmobile/ios_current_version',
        'Current iOS Version',
        'Latest released iOS version e.g. 1.2.0',
        '1.0.0',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_rapmobile/ios_build_number',
        'iOS Build Number',
        'Build number of the current release',
        '1',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_rapmobile/ios_min_version',
        'Minimum iOS Version',
        'Minimum supported version — users below this will be forced to update',
        '1.0.0',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_rapmobile/ios_force_update',
        'Force Update (iOS)',
        'If enabled, users below the minimum version cannot use the app until they update',
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_rapmobile/ios_store_url',
        'iOS App Store URL',
        'Apple App Store URL for the app',
        'https://apps.apple.com/app/yourapp/id000000000',
        PARAM_URL
    ));

    // =========================================================
    // SECTION 5 — UPDATE MESSAGE
    // =========================================================
    $settings->add(new admin_setting_heading(
        'local_rapmobile/heading_update_msg',
        'Update Message',
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_rapmobile/update_message',
        'Update Prompt Message',
        'Message shown to user when a new version is available',
        'A new version of the app is available. Please update to continue.',
        PARAM_TEXT
    ));

    // =========================================================
    // SECTION 6 — MAINTENANCE MODE
    // =========================================================
    $settings->add(new admin_setting_heading(
        'local_rapmobile/heading_maintenance',
        'App Maintenance Mode',
        'When enabled, all app users see a maintenance message instead of the app.'
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_rapmobile/app_maintenance',
        'Enable Maintenance Mode',
        'Turn on to show maintenance screen in app',
        0
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_rapmobile/app_maintenance_message',
        'Maintenance Message',
        'Message shown during maintenance',
        'The app is currently under maintenance. We will be back shortly.',
        PARAM_TEXT
    ));

    // =========================================================
    // SECTION 7 — FEATURE FLAGS
    // =========================================================
    $settings->add(new admin_setting_heading(
        'local_rapmobile/heading_features',
        'Feature Flags',
        'Enable or disable individual features in the mobile app.'
    ));

    $features = [
        'feature_push'         => ['Push Notifications',     'Enable push notifications'],
        'feature_offline'      => ['Offline Mode',           'Allow content download for offline use'],
        'feature_biometric'    => ['Biometric Login',        'Fingerprint / Face ID login'],
        'feature_social_login' => ['Social Login',           'Google and LinkedIn login buttons'],
        'feature_gift_points'  => ['Gift Points',            'Allow users to gift points to others'],
        'feature_leaderboard'  => ['Leaderboard',            'Show leaderboard in Game screen'],
        'feature_spin_wheel'   => ['Spin the Wheel',         'Daily spin wheel for points'],
        'feature_scratch_card' => ['Scratch & Win',          'Scratch cards for points'],
        'feature_hall_of_fame' => ['Hall of Fame',           'Hall of Fame / certificate upload'],
        'feature_qr_attendance'=> ['QR Attendance',          'QR code based attendance marking'],
        'feature_chat'         => ['In-app Chat',            'Messaging between users'],
    ];

    foreach ($features as $key => [$label, $desc]) {
        $default = ($key === 'feature_offline' || $key === 'feature_biometric' || $key === 'feature_chat') ? 0 : 1;
        $settings->add(new admin_setting_configcheckbox(
            'local_rapmobile/' . $key,
            $label,
            $desc,
            $default
        ));
    }

    // =========================================================
    // FIREBASE PUSH NOTIFICATION SETTINGS
    // =========================================================
    $settings->add(new admin_setting_heading(
        'local_rapmobile/heading_firebase',
        'Firebase Push Notifications (FCM)',
        'Configure Firebase Cloud Messaging for push notifications.<br>'
        . 'Project: <strong>rap-lms-9d219</strong> | Sender ID: <strong>1007856334196</strong> | '
        . 'Package: <strong>com.raptechsolutions.rapmobile</strong><br>'
        . '<a href="https://console.firebase.google.com/project/rap-lms-9d219/settings/serviceaccounts/adminsdk" target="_blank">'
        . 'Firebase Console → Service Accounts → Generate new private key</a>'
    ));

    $settings->add(new admin_setting_configtext(
        'local_rapmobile/rapmobile_firebase_project_id',
        'Firebase Project ID',
        'Your Firebase project ID. Pre-filled with your project.',
        'rap-lms-9d219',
        PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_rapmobile/rapmobile_service_account_json',
        'Service Account JSON',
        'Paste the full JSON content from Firebase Console → Project Settings → '
        . 'Service Accounts → Generate new private key.<br>'
        . '<strong style="color:red">Keep secret — grants push access to your project.</strong>',
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_rapmobile/rapmobile_fcm_timeout',
        'FCM Request Timeout (seconds)',
        'Seconds to wait for Firebase response. Default: 10.',
        '10',
        PARAM_INT
    ));
}