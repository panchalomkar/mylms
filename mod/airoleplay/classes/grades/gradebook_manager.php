<?php
namespace mod_airoleplay\grades;
defined('MOODLE_INTERNAL') || die();

class gradebook_manager {
    public static function update_grade(object $scenario, object $session): void {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        if (empty($session->grade)) return;

        $grade           = new \stdClass();
        $grade->userid   = $session->userid;
        $grade->rawgrade = (float)$session->grade;

        airoleplay_grade_item_update($scenario, $grade);
    }
}
