<?php

/**
 * Bulk enrollment base on rules and profile fields 
 *
 * Event Handlers
 *
 * @package    local_enroll_by_profile
 * @author     Esteban E. 
 * @copyright     Paradiso Solutions LLC
 */

defined('MOODLE_INTERNAL') || die();


$observers = array(
    array (
        'eventname'   => '\core\event\user_created',
        'includefile' => '/local/enroll_by_profile/lib.php',
        'callback'    => 'local_enroll_by_profile_observer::user_create',
    ),    
    array (
        'eventname'   => '\core\event\user_updated',
        'includefile' => '/local/enroll_by_profile/lib.php',
        'callback'    => 'local_enroll_by_profile_observer::user_update',
    ),  
);