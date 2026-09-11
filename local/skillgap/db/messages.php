<?php
defined('MOODLE_INTERNAL') || die();

// MESSAGE_PERMITTED = 2, MESSAGE_DEFAULT_ENABLED = 4
// These numeric values avoid the undefined constant error during upgrade.
$messageproviders = [
    'assessment_due' => [
        'defaults' => [
            'popup' => 2 + 4,  // MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED
            'email' => 2 + 4,
        ],
    ],
    'competency_assigned' => [
        'defaults' => [
            'popup' => 2 + 4,
            'email' => 2 + 4,
        ],
    ],
    'promoted' => [
        'defaults' => [
            'popup' => 2 + 4,
            'email' => 2 + 4,
        ],
    ],
];
