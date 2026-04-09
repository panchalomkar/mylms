<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\user_deleted',
        'callback'    => '\local_halloffame\observer::user_deleted',
        'includefile' => null,
        'internal'    => false,
        'priority'    => 0,
    ],
];
