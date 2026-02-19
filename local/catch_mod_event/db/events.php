<?php

defined('MOODLE_INTERNAL') || die();
$observers = array(
    array(
        'eventname' => '\core\event\course_module_created',
        'callback' => 'local_catch_mod_event_observer::add_mod_event',
    ),
    array(
        'eventname' => '\core\event\course_module_updated',
        'callback' => 'local_catch_mod_event_observer::update_mod_event',
    )
);