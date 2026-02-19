<?php

/** 
 * @author VaibhavG
 * @desc cron run
 */
define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');

// Now get cli options.
require_once($CFG->dirroot .'/local/enroll_by_profile/lib.php');
enroll_by_profile_enrollment_allusers();
