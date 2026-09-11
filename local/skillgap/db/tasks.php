<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname'  => '\local_skillgap\task\reassessment_task',
        'blocking'   => 0,
        'minute'     => '0',
        'hour'       => '6',
        'day'        => '*',
        'month'      => '*',
        'dayofweek'  => '*',
        'disabled'   => 0,
    ],
    [
        'classname'  => '\local_skillgap\task\notification_task',
        'blocking'   => 0,
        'minute'     => '*/30',
        'hour'       => '*',
        'day'        => '*',
        'month'      => '*',
        'dayofweek'  => '*',
        'disabled'   => 0,
    ],
];
