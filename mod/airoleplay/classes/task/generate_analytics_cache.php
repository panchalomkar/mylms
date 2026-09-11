<?php
namespace mod_airoleplay\task;
defined('MOODLE_INTERNAL') || die();

class generate_analytics_cache extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_generate_analytics', 'mod_airoleplay');
    }
    public function execute(): void {
        global $DB;
        $cache = \cache::make('mod_airoleplay', 'analytics');
        $stats = $DB->get_record_sql(
            "SELECT COUNT(id) AS total_sessions, AVG(score_overall) AS avg_score,
                    SUM(CASE WHEN passed=1 THEN 1 ELSE 0 END) AS total_passed,
                    COUNT(DISTINCT userid) AS unique_learners
               FROM {airoleplay_session}
              WHERE status = 'completed' AND timecompleted >= :since",
            ['since' => strtotime('-30 days')]
        );
        $cache->set('platform_stats_30d', $stats);
        mtrace('Analytics cache refreshed.');
    }
}
