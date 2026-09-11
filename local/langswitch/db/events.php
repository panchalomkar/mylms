<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\user_loggedout',
        'callback'    => 'local_langswitch_observer::user_logout',
        'includefile' => '/local/langswitch/classes/observer.php',
        'internal'    => true,
        'priority'    => 9999,
    ],
    [
        'eventname'   => '\core\event\user_loggedin',
        'callback'    => 'local_langswitch_observer::user_login',
        'includefile' => '/local/langswitch/classes/observer.php',
        'internal'    => true,
        'priority'    => 9999,
    ],
];