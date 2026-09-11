<?php
defined('MOODLE_INTERNAL') || die();
$definitions = [
    'analytics' => [
        'mode'       => cache_store::MODE_APPLICATION,
        'ttl'        => 86400,
        'simplekeys' => true,
    ],
];
