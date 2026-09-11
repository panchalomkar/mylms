<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/skillgap:admin' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => ['manager' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW],
    ],
    'local/skillgap:managerviewteam' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => ['manager' => CAP_ALLOW],
    ],
    'local/skillgap:takeassessment' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => ['student' => CAP_ALLOW, 'user' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    'local/skillgap:viewown' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => ['student' => CAP_ALLOW, 'user' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
];
