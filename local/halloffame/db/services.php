<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_halloffame_get_awards' => [
        'classname'     => 'local_halloffame\external\get_awards',
        'methodname'    => 'execute',
        'description'   => 'Get awards with optional filters.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:view',
    ],
    'local_halloffame_get_achievements' => [
        'classname'     => 'local_halloffame\external\get_achievements',
        'methodname'    => 'execute',
        'description'   => 'Get approved achievements with optional filters.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:view',
    ],
    'local_halloffame_submit_certificate' => [
        'classname'     => 'local_halloffame\external\submit_certificate',
        'methodname'    => 'execute',
        'description'   => 'Submit an external certificate for admin review.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:submit',
    ],
    'local_halloffame_like_item' => [
        'classname'     => 'local_halloffame\external\like_item',
        'methodname'    => 'execute',
        'description'   => 'Toggle like on an award or achievement.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:view',
    ],
];
