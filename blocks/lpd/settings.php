<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $options[1] = get_string('course_completed', 'block_lpd');
    $options[2] = get_string('credits_earned', 'block_lpd');
    $settings->add(new admin_setting_configselect('block_lpd/evaluate_progress',
            get_string('chooseoption','block_lpd'),
            get_string('chooseoption','block_lpd'),
            1,
            $options));
}
