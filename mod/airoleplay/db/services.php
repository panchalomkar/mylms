<?php
// This file is part of mod_airoleplay.

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_airoleplay_get_opening_message' => [
        'classname'   => 'mod_airoleplay_external',
        'methodname'  => 'get_opening_message',
        'classpath'   => 'mod/airoleplay/externallib.php',
        'description' => 'Get AI opening message for a session',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'mod/airoleplay:view',
    ],
    'mod_airoleplay_send_message' => [
        'classname'   => 'mod_airoleplay_external',
        'methodname'  => 'send_message',
        'classpath'   => 'mod/airoleplay/externallib.php',
        'description' => 'Send user message and receive AI reply',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'mod/airoleplay:attempt',
    ],
    'mod_airoleplay_end_session' => [
        'classname'   => 'mod_airoleplay_external',
        'methodname'  => 'end_session',
        'classpath'   => 'mod/airoleplay/externallib.php',
        'description' => 'End roleplay session',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'mod/airoleplay:attempt',
    ],
    'mod_airoleplay_create_session' => [
        'classname'   => 'mod_airoleplay_external',
        'methodname'  => 'create_session',
        'classpath'   => 'mod/airoleplay/externallib.php',
        'description' => 'Create a new roleplay session',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'mod/airoleplay:attempt',
    ],
    'mod_airoleplay_get_evaluation' => [
        'classname'   => 'mod_airoleplay_external',
        'methodname'  => 'get_evaluation',
        'classpath'   => 'mod/airoleplay/externallib.php',
        'description' => 'Poll for completion of AI evaluation and fetch results',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'mod/airoleplay:view',
    ],
    'mod_airoleplay_upload_session_audio' => [
        'classname'   => 'mod_airoleplay_external',
        'methodname'  => 'upload_session_audio',
        'classpath'   => 'mod/airoleplay/externallib.php',
        'description' => 'Upload the merged conversation audio recording for the session',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'mod/airoleplay:attempt',
    ],
];

$services = [];
