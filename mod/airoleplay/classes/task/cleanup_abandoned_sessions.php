<?php
namespace mod_airoleplay\task;
defined('MOODLE_INTERNAL') || die();

class cleanup_abandoned_sessions extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_cleanup_sessions', 'mod_airoleplay');
    }
    public function execute(): void {
        global $DB;
        $cutoff = time() - (4 * 3600);
        $abandoned = $DB->get_records_select(
            'airoleplay_session',
            "status = 'active' AND timemodified < :cutoff",
            ['cutoff' => $cutoff]
        );
        foreach ($abandoned as $session) {
            $DB->set_field('airoleplay_session', 'status', 'abandoned', ['id' => $session->id]);
        }
        mtrace("Cleanup: " . count($abandoned) . " abandoned sessions.");
    }
}
