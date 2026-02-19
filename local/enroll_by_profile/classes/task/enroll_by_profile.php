<?php

/**
 * enroll_by_profile
 *
 *
 * @author      VaibhavGhadage
 * @package     enroll_by_profile
 * @since       17 Feb 2021
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_enroll_by_profile\task;

require_once($CFG->dirroot."/config.php");
include_once($CFG->dirroot."/local/enroll_by_profile/lib.php");
global $DB, $CFG;
class enroll_by_profile extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('ruleenginepluginname', 'local_enroll_by_profile');
    }

    public function execute() {    
    	
    	enroll_by_profile_enrollment_allusers();
    	
	}
}
