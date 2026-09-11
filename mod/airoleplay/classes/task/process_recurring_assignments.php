<?php
namespace mod_airoleplay\task;
defined('MOODLE_INTERNAL') || die();

class process_recurring_assignments extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_recurring_assignments', 'mod_airoleplay');
    }
    public function execute(): void {
        global $DB;
        $recurring = $DB->get_records('airoleplay_assignment', ['is_recurring' => 1]);
        foreach ($recurring as $assignment) {
            if (!$assignment->recurrence_rule) continue;
            $rule = json_decode($assignment->recurrence_rule, true);
            if (!$rule || !isset($rule['frequency'])) continue;
            $engine = new \local_airoleplay_assignments\engine\assignment_engine();
            $engine->process_recurrence($assignment, $rule);
        }
    }
}
