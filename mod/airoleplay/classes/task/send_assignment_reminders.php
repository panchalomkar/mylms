<?php
namespace mod_airoleplay\task;
defined('MOODLE_INTERNAL') || die();

class send_assignment_reminders extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_send_reminders', 'mod_airoleplay');
    }
    public function execute(): void {
        global $DB;
        $now = time();
        foreach ([86400, 259200, 604800] as $window) {
            $pending = $DB->get_records_sql(
                "SELECT au.*, a.title, a.airoleplay, a.due_date,
                        u.firstname, u.lastname, u.email
                   FROM {airoleplay_assignment_user} au
                   JOIN {airoleplay_assignment} a ON a.id = au.assignment
                   JOIN {user} u ON u.id = au.userid
                  WHERE au.status IN ('assigned','inprogress')
                    AND a.due_date BETWEEN :from AND :to",
                ['from' => $now + $window, 'to' => $now + $window + 3600]
            );
            foreach ($pending as $record) {
                try {
                    \local_airoleplay_notifications\handler\notification_handler::send_reminder($record, $window);
                } catch (\Throwable $e) {
                    debugging("Reminder failed: " . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }
        }
    }
}
