<?php
// This file is part of mod_airoleplay — evaluation queue without local_airoleplay_ai dependency.

namespace mod_airoleplay\task;

defined('MOODLE_INTERNAL') || die();

class process_ai_evaluation_queue extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_process_ai_evaluation', 'mod_airoleplay');
    }

    public function execute(): void {
        global $DB;

        if (!$DB->get_manager()->table_exists('airoleplay_session')) {
            return;
        }

        // Pick completed sessions that haven't been evaluated yet.
        $sessions = $DB->get_records_select(
            'airoleplay_session',
            "status = 'completed' AND (evaluated_by_ai = 0 OR evaluated_by_ai IS NULL)",
            null,
            'timecompleted ASC',
            'id',
            0,
            10
        );

        foreach ($sessions as $session) {
            $task = new evaluate_session_adhoc();
            $task->set_custom_data(['sessionid' => (int) $session->id]);
            \core\task\manager::queue_adhoc_task($task);
        }
    }
}
