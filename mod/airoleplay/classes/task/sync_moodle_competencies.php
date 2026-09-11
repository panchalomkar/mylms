<?php
namespace mod_airoleplay\task;
defined('MOODLE_INTERNAL') || die();

class sync_moodle_competencies extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_sync_competencies', 'mod_airoleplay');
    }
    public function execute(): void {
        \local_airoleplay_competency\engine\competency_engine::sync_all_pending();
        mtrace('Competency sync complete.');
    }
}
