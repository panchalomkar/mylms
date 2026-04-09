<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\local_halloffame\task\cleanup_rejected',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '2',
        'day'       => '1',
        'month'     => '*',
        'dayofweek' => '*',
        'disabled'  => 0,
    ],
];
