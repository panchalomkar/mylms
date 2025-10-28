<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('root', new admin_externalpage('properties', new lang_string('properties', 'local_properties'), $CFG->wwwroot . '/local/properties/index.php', array('local/properties:properties_view')));    
}