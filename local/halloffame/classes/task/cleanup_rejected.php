<?php
namespace local_halloffame\task;

defined('MOODLE_INTERNAL') || die();

class cleanup_rejected extends \core\task\scheduled_task {

    public function get_name(): string {
        return 'Hall of Fame: clean up old rejected submissions';
    }

    public function execute(): void {
        global $DB;
        $cutoff  = strtotime('-90 days');
        $deleted = $DB->delete_records_select(
            'halloffame_submissions',
            "status = 'rejected' AND timecreated < :cutoff",
            ['cutoff' => $cutoff]
        );
        mtrace("local_halloffame: removed {$deleted} old rejected submission(s).");
    }
}
