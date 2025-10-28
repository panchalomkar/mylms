<?php

/**
 * Capabilities Definition
 *
 * @package    local_enroll_by_profile
 * @author     Esteban E. 
 * @copyright     Paradiso Solutions LLC
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'local/enroll_by_profile:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),

    ),
);