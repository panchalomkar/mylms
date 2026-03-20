<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Capability to manage all platform learning paths.
    'local/learningpaths:managealllearningpaths' => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE
    ],

    // Capability to manage company learning paths.
    'local/learningpaths:managecompanylearningpaths' => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE
    ],
    
    // Capability to view the learning paths.
    'local/learningpaths:viewlearningpaths' => [
        'captype'      => 'view',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW
        )
    ],
    
    // Capability to view the learning paths.
    'local/learningpaths:enrollmyselflearningpaths' => [
        'captype'      => 'view',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW
        )
    ],
    
    // Capability to create training session.
    'local/learningpaths:create_training_session' => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes'   => array(
            'manager' => CAP_ALLOW,
        )
    ],

    // Capability to delete learning paths.
    'local/learningpaths:delete_learning_path' => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes'   => array(
            'manager' => CAP_ALLOW,
        )
    ],
    
    // Capability to create learning paths.
    'local/learningpaths:create_learning_path' => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes'   => array(
            'manager' => CAP_ALLOW,
        )
    ],
    
    // Capability to add courses in learning paths.
    'local/learningpaths:add_courses_learning_path' => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes'   => array(
            'manager' => CAP_ALLOW,
        )
    ],
    
    // Capability to delete learning path courses.
    'local/learningpaths:delete_courses_learning_path' => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes'   => array(
            'manager' => CAP_ALLOW 
            )
        ]
];