<?php
/**
 * Event observers for mod_airoleplay.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_airoleplay\event;

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Triggered when a session completes.
     * Queues AI evaluation as a background task.
     */
    public static function session_completed(\core\event\base $event): void {
        $session_id = $event->objectid;
        \mod_airoleplay\task\process_ai_evaluation_queue::queue_session($session_id);
    }

    /**
     * Triggered when AI evaluation completes.
     * Updates the Moodle gradebook.
     */
    public static function update_gradebook(\core\event\base $event): void {
        global $DB;

        $session_id = $event->objectid;
        $session    = $DB->get_record('airoleplay_session', ['id' => $session_id]);
        if (!$session || empty($session->grade)) {
            return;
        }

        $scenario = $DB->get_record('airoleplay', ['id' => $session->airoleplay]);
        if (!$scenario) {
            return;
        }

        \mod_airoleplay\grades\gradebook_manager::update_grade($scenario, $session);
    }

    /**
     * Update activity completion when a session is passed.
     */
    public static function update_completion(\core\event\base $event): void {
        global $DB;

        $session_id = $event->objectid;
        $session    = $DB->get_record('airoleplay_session', ['id' => $session_id]);
        if (!$session) {
            return;
        }

        $cm = get_coursemodule_from_instance('airoleplay', $session->airoleplay);
        if (!$cm) {
            return;
        }

        $completion = new \completion_info(get_course($cm->course));
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_COMPLETE, $session->userid);
        }
    }

    /**
     * Invalidate user profile cache when user profile is updated.
     */
    public static function invalidate_user_profile_cache(\core\event\base $event): void {
        if (class_exists('\\mod_airoleplay\\profile\\profile_field_mapper')) {
            \mod_airoleplay\profile\profile_field_mapper::invalidate_cache($event->relateduserid ?? $event->userid);
        }
    }
}
