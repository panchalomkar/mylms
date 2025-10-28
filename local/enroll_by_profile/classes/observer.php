<?php


defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for local/enroll_by_profile.
 */

class local_enroll_by_profile_observer {

    public static function user_create(\core\event\user_created $event) {
        global $DB, $CFG;
        require_once($CFG->dirroot .'/local/enroll_by_profile/lib.php');
        $data = $event->get_data();
        $userid = $data['relateduserid'];
        $eventdata = array();
        observer_enroll_by_profile($eventdata,true,$userid);
    }

    public static function user_update(\core\event\user_updated $event) {
        global $DB, $CFG;
        require_once($CFG->dirroot .'/local/enroll_by_profile/lib.php');
        $data = $event->get_data();
        $userid = $data['relateduserid'];
        $eventdata = array();
        observer_enroll_by_profile($eventdata,true,$userid);
    }
}
