<?php
namespace mod_airoleplay\task;
defined('MOODLE_INTERNAL') || die();

class mark_overdue_assignments extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_mark_overdue', 'mod_airoleplay');
    }
    public function execute(): void {
        global $DB;
        $now = time();
        $DB->execute(
            "UPDATE {airoleplay_assignment_user}
                SET status = 'overdue', timemodified = :now
              WHERE status IN ('assigned','inprogress')
                AND assignment IN (
                    SELECT id FROM {airoleplay_assignment}
                     WHERE due_date IS NOT NULL AND due_date < :now2
                )",
            ['now' => $now, 'now2' => $now]
        );
        mtrace("Marked overdue assignments.");
    }
}
