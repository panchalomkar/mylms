<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
        
        [
                'eventname' => '\core\event\user_loggedin',
                'includefile' => '/local/dashboard/locallib.php',
                'callback' => 'rolewise_dashboard'
        ]

    ];