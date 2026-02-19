<?php

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('localplugins', 
	new admin_externalpage('local_enroll_by_profile', get_string('pluginname','local_enroll_by_profile'),
                                                        $CFG->wwwroot . '/local/enroll_by_profile/index.php'));