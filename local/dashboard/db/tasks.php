<?php

defined('MOODLE_INTERNAL') || die();

$tasks = [
        [
                'classname' => 'local_dashboard\task\notification_task',
                'blocking' => 0,
                'minute' => 30,
                'hour' => 0,
                'day' => '*',
                'dayofweek' => '*',
                'month' => '*'
        ]
];