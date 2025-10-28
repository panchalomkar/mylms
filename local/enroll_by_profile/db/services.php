<?php
/**
 * Paradiso LMS is powered by Paradiso Solutions LLC
 *
 * This package includes all core features handled by Paradiso LMS Platform
 *
 *
 * @package local_enroll_by_profile
 * @author Paradiso Solutions LLC
 */

$functions = array(

    'local_enroll_by_profile_search' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'search',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Search rules',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_delete_rule_btn' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'delete_rule_btn',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Delete rules button',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_keep_unenroll_all' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'keep_unenroll_all',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Keep all unenroll from rule',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_keep_enroll_all' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'keep_enroll_all',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Keep all enroll from rule',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_keep_enroll' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'keep_enroll',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Keep enrolled',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_keep_unenroll' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'keep_unenroll',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Keep unenrolled',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_addcond' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'addcond',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Add condition to rules form',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_get_category' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'get_category',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Get category',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_action_count' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'action_count',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Count total rules',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_save_category' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'save_category',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Save rules category',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_disable_rule' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'disable_rule',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Disable single rule service',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_disable_all_rule' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'disable_all_rule',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Disable all rules service',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_enable_rule' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'enable_rule',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Enable rules',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_delete_rule' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'delete_rule',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Delete rules',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_edit_rule' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'edit_rule',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Edit rules',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_conditional_html' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'conditional_html',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Conditional data',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_input_html' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'input_html',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Input field data',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_conditional_buttons' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'conditional_buttons',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Conditional buttons',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_enroll_by_profile_bool_opdropdown' => array(
        'classname' => 'local_enroll_by_profile_external',
        'methodname' => 'bool_opdropdown',
        'classpath' => 'local/enroll_by_profile/externallib.php',
        'description' => 'Bool options dropdown',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
);