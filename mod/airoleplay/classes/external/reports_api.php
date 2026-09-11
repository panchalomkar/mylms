<?php
/**
 * reports_api external API functions.
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_airoleplay\external;
defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use local_airoleplay_ai\orchestrator\ai_orchestrator;

class reports_api extends external_api {

    // ----------------------------------------------------------------
    // get_dashboard_data
    // ----------------------------------------------------------------

    public static function get_dashboard_data_parameters(): external_function_parameters {
        return new external_function_parameters([
            'role'       => new external_value(PARAM_ALPHA, 'learner|manager|ld|admin', VALUE_DEFAULT, 'learner'),
            'period'     => new external_value(PARAM_ALPHA, 'week|month|quarter|year',  VALUE_DEFAULT, 'month'),
            'cmid'       => new external_value(PARAM_INT,   'Course module ID (0=all)', VALUE_DEFAULT, 0),
            'department' => new external_value(PARAM_TEXT,  'Department filter',        VALUE_DEFAULT, ''),
        ]);
    }

    public static function get_dashboard_data(
        string $role = 'learner',
        string $period = 'month',
        int $cmid = 0,
        string $department = ''
    ): array {
        global $DB, $USER;

        self::validate_parameters(self::get_dashboard_data_parameters(),
            compact('role', 'period', 'cmid', 'department'));

        // Validate role access.
        if (in_array($role, ['manager', 'ld', 'admin'])) {
            require_capability('mod/airoleplay:viewteamreports', \context_system::instance());
        }

        $since  = self::period_to_timestamp($period);
        $data   = [];

        switch ($role) {
            case 'learner':
                $data = self::get_learner_dashboard($USER->id, $since, $cmid);
                break;
            case 'manager':
                $data = self::get_manager_dashboard($USER->id, $since, $department);
                break;
            case 'ld':
                $data = self::get_ld_dashboard($since, $department, $cmid);
                break;
            case 'admin':
                require_capability('mod/airoleplay:manageaisettings', \context_system::instance());
                $data = self::get_admin_dashboard($since);
                break;
        }

        return $data;
    }

    public static function get_dashboard_data_returns(): external_single_structure {
        return new external_single_structure([
            'summary_cards' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT,  'Card label'),
                    'value' => new external_value(PARAM_TEXT,  'Card value (string for flexibility)'),
                    'delta' => new external_value(PARAM_TEXT,  'Change from previous period', VALUE_OPTIONAL),
                    'trend' => new external_value(PARAM_TEXT,  'up|down|stable', VALUE_OPTIONAL),
                    'icon'  => new external_value(PARAM_TEXT,  'Icon identifier', VALUE_OPTIONAL),
                    'color' => new external_value(PARAM_TEXT,  'Color class', VALUE_OPTIONAL),
                ])
            ),
            'score_trend'   => new external_multiple_structure(
                new external_single_structure([
                    'date'  => new external_value(PARAM_TEXT,  'Date label'),
                    'score' => new external_value(PARAM_FLOAT, 'Average score'),
                    'count' => new external_value(PARAM_INT,   'Sessions count'),
                ]), 'Score over time', VALUE_OPTIONAL
            ),
            'competency_radar' => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT,  'Competency label'),
                    'score' => new external_value(PARAM_FLOAT, 'Average score'),
                ]), 'Competency radar data', VALUE_OPTIONAL
            ),
            'recent_sessions' => new external_multiple_structure(
                new external_single_structure([
                    'session_id'   => new external_value(PARAM_INT,   'Session ID'),
                    'scenario'     => new external_value(PARAM_TEXT,  'Scenario name'),
                    'score'        => new external_value(PARAM_FLOAT, 'Score'),
                    'passed'       => new external_value(PARAM_BOOL,  'Passed'),
                    'status'       => new external_value(PARAM_TEXT,  'Status'),
                    'timecompleted' => new external_value(PARAM_INT,  'Completion time'),
                ]), 'Recent sessions', VALUE_OPTIONAL
            ),
            'department_leaderboard' => new external_multiple_structure(
                new external_single_structure([
                    'rank'  => new external_value(PARAM_INT,   'Rank'),
                    'name'  => new external_value(PARAM_TEXT,  'User or dept name'),
                    'score' => new external_value(PARAM_FLOAT, 'Average score'),
                    'count' => new external_value(PARAM_INT,   'Sessions completed'),
                ]), 'Leaderboard', VALUE_OPTIONAL
            ),
            'ai_cost_summary' => new external_single_structure([
                'total_tokens' => new external_value(PARAM_INT,   'Total tokens'),
                'total_cost'   => new external_value(PARAM_FLOAT, 'Total cost USD'),
                'by_provider'  => new external_multiple_structure(
                    new external_single_structure([
                        'provider' => new external_value(PARAM_TEXT,  'Provider name'),
                        'tokens'   => new external_value(PARAM_INT,   'Tokens'),
                        'cost'     => new external_value(PARAM_FLOAT, 'Cost USD'),
                    ])
                ),
            ], 'AI cost summary (admin only)', VALUE_OPTIONAL),
        ]);
    }

    // ----------------------------------------------------------------
    // DASHBOARD DATA BUILDERS
    // ----------------------------------------------------------------

    private static function get_learner_dashboard(int $userid, int $since, int $cmid): array {
        global $DB;

        $where  = 's.userid = :uid AND s.timecompleted >= :since AND s.status = :status';
        $params = ['uid' => $userid, 'since' => $since, 'status' => 'completed'];

        if ($cmid) {
            $cm = get_coursemodule_from_id('airoleplay', $cmid, 0, false, MUST_EXIST);
            $where  .= ' AND s.airoleplay = :roleid';
            $params['roleid'] = $cm->instance;
        }

        $sessions = $DB->get_records_sql(
            "SELECT s.*, r.name AS scenario_name
               FROM {airoleplay_session} s
               JOIN {airoleplay} r ON r.id = s.airoleplay
              WHERE $where
           ORDER BY s.timecompleted DESC",
            $params
        );

        $total       = count($sessions);
        $passed      = count(array_filter($sessions, fn($s) => $s->passed));
        $avg_score   = $total ? round(array_sum(array_column((array)$sessions, 'score_overall')) / $total, 1) : 0;
        $recent      = array_slice(array_values((array)$sessions), 0, 10);

        // Score trend (weekly buckets).
        $trend = self::build_score_trend((array)$sessions, $since);

        // Competency radar.
        $radar = self::build_competency_radar_for_user($userid, $since);

        // Assigned roleplays pending.
        $pending_count = $DB->count_records_select(
            'airoleplay_assignment_user',
            "userid = :uid AND status IN ('assigned','inprogress','overdue')",
            ['uid' => $userid]
        );

        return [
            'summary_cards' => [
                ['label' => 'Sessions Completed', 'value' => (string)$total,     'icon' => 'check-circle', 'color' => 'primary'],
                ['label' => 'Pass Rate',           'value' => $total ? round($passed/$total*100).'%' : '0%', 'icon' => 'trophy', 'color' => 'success'],
                ['label' => 'Avg Score',           'value' => $avg_score . '/100', 'icon' => 'star',        'color' => 'warning'],
                ['label' => 'Pending Tasks',       'value' => (string)$pending_count, 'icon' => 'clock',   'color' => 'danger'],
            ],
            'score_trend'   => $trend,
            'competency_radar' => $radar,
            'recent_sessions' => array_values(array_map(fn($s) => [
                'session_id'    => (int)$s->id,
                'scenario'      => $s->scenario_name,
                'score'         => (float)$s->score_overall,
                'passed'        => (bool)$s->passed,
                'status'        => $s->status,
                'timecompleted' => (int)$s->timecompleted,
            ], $recent)),
            'department_leaderboard' => [],
        ];
    }

    private static function get_manager_dashboard(int $manager_userid, int $since, string $dept): array {
        global $DB;

        // Get team members using Moodle profile field mapping.
        $dept_field  = class_exists('\\mod_airoleplay\\profile\\profile_field_mapper') ? \mod_airoleplay\profile\profile_field_mapper::get_field('department') : null;
        $mgr_field   = class_exists('\\mod_airoleplay\\profile\\profile_field_mapper') ? \mod_airoleplay\profile\profile_field_mapper::get_field('manager') : null;

        // Get users in manager's team via profile field.
        $team_userids = class_exists('\\mod_airoleplay\\profile\\profile_field_mapper') ? \mod_airoleplay\profile\profile_field_mapper::get_team_members($manager_userid) : [];

        if (empty($team_userids)) {
            return self::empty_dashboard();
        }

        [$in_sql, $in_params] = $DB->get_in_or_equal($team_userids, SQL_PARAMS_NAMED);

        $sessions = $DB->get_records_sql(
            "SELECT s.userid, u.firstname, u.lastname,
                    COUNT(s.id) AS total_sessions,
                    AVG(s.score_overall) AS avg_score,
                    SUM(CASE WHEN s.passed = 1 THEN 1 ELSE 0 END) AS passed_count
               FROM {airoleplay_session} s
               JOIN {user} u ON u.id = s.userid
              WHERE s.userid $in_sql
                AND s.timecompleted >= :since
                AND s.status = 'completed'
           GROUP BY s.userid, u.firstname, u.lastname
           ORDER BY avg_score DESC",
            array_merge($in_params, ['since' => $since])
        );

        $leaderboard = array_values(array_map(function($row, $rank) {
            return [
                'rank'  => $rank + 1,
                'name'  => fullname((object)['firstname' => $row->firstname, 'lastname' => $row->lastname]),
                'score' => round((float)$row->avg_score, 1),
                'count' => (int)$row->total_sessions,
            ];
        }, (array)$sessions, array_keys((array)$sessions)));

        $total    = array_sum(array_column((array)$sessions, 'total_sessions'));
        $avg      = $total ? round(array_sum(array_column((array)$sessions, 'avg_score')) / count((array)$sessions), 1) : 0;
        $overdue  = $DB->count_records_select(
            'airoleplay_assignment_user',
            "userid $in_sql AND status = 'overdue'",
            $in_params
        );

        return [
            'summary_cards' => [
                ['label' => 'Team Size',         'value' => (string)count($team_userids), 'icon' => 'users',       'color' => 'primary'],
                ['label' => 'Team Avg Score',    'value' => $avg . '/100',                'icon' => 'bar-chart',   'color' => 'info'],
                ['label' => 'Total Sessions',    'value' => (string)$total,               'icon' => 'play-circle', 'color' => 'success'],
                ['label' => 'Overdue Tasks',     'value' => (string)$overdue,             'icon' => 'alert-circle','color' => 'danger'],
            ],
            'score_trend'            => [],
            'competency_radar'       => self::build_competency_radar_for_users($team_userids, $since),
            'recent_sessions'        => [],
            'department_leaderboard' => $leaderboard,
        ];
    }

    private static function get_ld_dashboard(int $since, string $dept, int $cmid): array {
        global $DB;

        $where  = "s.timecompleted >= :since AND s.status = 'completed'";
        $params = ['since' => $since];

        if ($dept) {
            $where .= ' AND s.user_dept = :dept';
            $params['dept'] = $dept;
        }

        $totals = $DB->get_record_sql(
            "SELECT COUNT(s.id) AS total, AVG(s.score_overall) AS avg_score,
                    SUM(CASE WHEN s.passed=1 THEN 1 ELSE 0 END) AS passed
               FROM {airoleplay_session} s
              WHERE $where",
            $params
        );

        $unique_learners = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT s.userid) FROM {airoleplay_session} s WHERE $where",
            $params
        );

        // Department performance heatmap data.
        $dept_data = $DB->get_records_sql(
            "SELECT s.user_dept AS dept,
                    COUNT(s.id) AS total,
                    AVG(s.score_overall) AS avg_score,
                    SUM(CASE WHEN s.passed=1 THEN 1 ELSE 0 END) AS passed
               FROM {airoleplay_session} s
              WHERE $where AND s.user_dept != ''
           GROUP BY s.user_dept
           ORDER BY avg_score DESC",
            $params
        );

        $leaderboard = array_values(array_map(function($row, $idx) {
            return [
                'rank'  => $idx + 1,
                'name'  => $row->dept ?: 'Unknown',
                'score' => round((float)$row->avg_score, 1),
                'count' => (int)$row->total,
            ];
        }, (array)$dept_data, array_keys((array)$dept_data)));

        return [
            'summary_cards' => [
                ['label' => 'Total Sessions',   'value' => (string)($totals->total ?? 0),         'icon' => 'activity',    'color' => 'primary'],
                ['label' => 'Unique Learners',  'value' => (string)$unique_learners,               'icon' => 'users',       'color' => 'info'],
                ['label' => 'Platform Avg',     'value' => round($totals->avg_score ?? 0, 1).'/100','icon' => 'trending-up', 'color' => 'success'],
                ['label' => 'Pass Rate',        'value' => ($totals->total ? round(($totals->passed/$totals->total)*100) : 0).'%', 'icon' => 'check-circle', 'color' => 'warning'],
            ],
            'score_trend'            => [],
            'competency_radar'       => [],
            'recent_sessions'        => [],
            'department_leaderboard' => $leaderboard,
        ];
    }

    private static function get_admin_dashboard(int $since): array {
        global $DB;

        $ld_data = self::get_ld_dashboard($since, '', 0);

        // AI cost summary.
        $ai_costs = $DB->get_records_sql(
            "SELECT provider,
                    SUM(total_tokens) AS tokens,
                    SUM(cost_usd)     AS cost
               FROM {airoleplay_ai_usage}
              WHERE timecreated >= :since
           GROUP BY provider",
            ['since' => $since]
        );

        $total_tokens = array_sum(array_column((array)$ai_costs, 'tokens'));
        $total_cost   = array_sum(array_column((array)$ai_costs, 'cost'));

        $ld_data['ai_cost_summary'] = [
            'total_tokens' => (int)$total_tokens,
            'total_cost'   => round((float)$total_cost, 4),
            'by_provider'  => array_values(array_map(fn($r) => [
                'provider' => $r->provider,
                'tokens'   => (int)$r->tokens,
                'cost'     => round((float)$r->cost, 4),
            ], (array)$ai_costs)),
        ];

        return $ld_data;
    }

    // ----------------------------------------------------------------
    // HELPERS
    // ----------------------------------------------------------------

    private static function build_score_trend(array $sessions, int $since): array {
        $buckets = [];
        $now     = time();
        $range   = $now - $since;
        $bucket_size = $range <= (7 * 86400) ? 86400 : (7 * 86400); // Daily or weekly.

        foreach ($sessions as $session) {
            if (empty($session->timecompleted)) continue;
            $bucket = floor($session->timecompleted / $bucket_size) * $bucket_size;
            if (!isset($buckets[$bucket])) {
                $buckets[$bucket] = ['scores' => [], 'count' => 0];
            }
            $buckets[$bucket]['scores'][] = (float)$session->score_overall;
            $buckets[$bucket]['count']++;
        }

        ksort($buckets);
        return array_values(array_map(fn($ts, $data) => [
            'date'  => date('M j', $ts),
            'score' => round(array_sum($data['scores']) / count($data['scores']), 1),
            'count' => $data['count'],
        ], array_keys($buckets), $buckets));
    }

    private static function build_competency_radar_for_user(int $userid, int $since): array {
        global $DB;

        $rows = $DB->get_records_sql(
            "SELECT cr.competency_key, cr.competency_label, AVG(cr.score) AS avg_score
               FROM {airoleplay_competency_result} cr
               JOIN {airoleplay_session} s ON s.id = cr.session
              WHERE s.userid = :uid AND s.timecompleted >= :since
           GROUP BY cr.competency_key, cr.competency_label
           ORDER BY avg_score DESC",
            ['uid' => $userid, 'since' => $since]
        );

        return array_values(array_map(fn($r) => [
            'label' => $r->competency_label,
            'score' => round((float)$r->avg_score, 1),
        ], (array)$rows));
    }

    private static function build_competency_radar_for_users(array $userids, int $since): array {
        global $DB;

        if (empty($userids)) return [];

        [$in_sql, $in_params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $rows = $DB->get_records_sql(
            "SELECT cr.competency_key, cr.competency_label, AVG(cr.score) AS avg_score
               FROM {airoleplay_competency_result} cr
               JOIN {airoleplay_session} s ON s.id = cr.session
              WHERE s.userid $in_sql AND s.timecompleted >= :since
           GROUP BY cr.competency_key, cr.competency_label",
            array_merge($in_params, ['since' => $since])
        );

        return array_values(array_map(fn($r) => [
            'label' => $r->competency_label,
            'score' => round((float)$r->avg_score, 1),
        ], (array)$rows));
    }

    private static function period_to_timestamp(string $period): int {
        return match($period) {
            'week'    => strtotime('-7 days'),
            'month'   => strtotime('-30 days'),
            'quarter' => strtotime('-90 days'),
            'year'    => strtotime('-365 days'),
            default   => strtotime('-30 days'),
        };
    }

    private static function empty_dashboard(): array {
        return [
            'summary_cards'          => [],
            'score_trend'            => [],
            'competency_radar'       => [],
            'recent_sessions'        => [],
            'department_leaderboard' => [],
        ];
    }

    // ----------------------------------------------------------------
    // get_competency_report
    // ----------------------------------------------------------------

    public static function get_competency_report_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'       => new external_value(PARAM_INT,  'Course module ID'),
            'userid'     => new external_value(PARAM_INT,  'User ID (0=all)',   VALUE_DEFAULT, 0),
            'department' => new external_value(PARAM_TEXT, 'Department filter', VALUE_DEFAULT, ''),
            'period'     => new external_value(PARAM_TEXT, 'Time period',       VALUE_DEFAULT, 'month'),
        ]);
    }

    public static function get_competency_report(int $cmid, int $userid = 0, string $department = '', string $period = 'month'): array {
        global $DB, $USER;

        self::validate_parameters(self::get_competency_report_parameters(),
            compact('cmid', 'userid', 'department', 'period'));

        $cm      = get_coursemodule_from_id('airoleplay', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        require_capability('mod/airoleplay:viewreports', $context);

        $since   = self::period_to_timestamp($period);
        $where   = "s.airoleplay = :roleid AND s.timecompleted >= :since AND s.status = 'completed'";
        $params  = ['roleid' => $cm->instance, 'since' => $since];

        if ($userid) {
            $where .= ' AND s.userid = :uid';
            $params['uid'] = $userid;
        }

        if ($department) {
            $where .= ' AND s.user_dept = :dept';
            $params['dept'] = $department;
        }

        $rows = $DB->get_records_sql(
            "SELECT cr.competency_key, cr.competency_label,
                    COUNT(cr.id) AS evaluations,
                    AVG(cr.score) AS avg_score,
                    MIN(cr.score) AS min_score,
                    MAX(cr.score) AS max_score,
                    STDDEV(cr.score) AS std_score
               FROM {airoleplay_competency_result} cr
               JOIN {airoleplay_session} s ON s.id = cr.session
              WHERE $where
           GROUP BY cr.competency_key, cr.competency_label
           ORDER BY avg_score ASC",
            $params
        );

        return array_values(array_map(fn($r) => [
            'key'         => $r->competency_key,
            'label'       => $r->competency_label,
            'evaluations' => (int)$r->evaluations,
            'avg_score'   => round((float)$r->avg_score, 1),
            'min_score'   => round((float)$r->min_score, 1),
            'max_score'   => round((float)$r->max_score, 1),
            'std_score'   => round((float)($r->std_score ?? 0), 1),
            'gap'         => round(100 - (float)$r->avg_score, 1),
        ], (array)$rows));
    }

    public static function get_competency_report_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'key'         => new external_value(PARAM_TEXT,  'Competency key'),
            'label'       => new external_value(PARAM_TEXT,  'Competency label'),
            'evaluations' => new external_value(PARAM_INT,   'Number of evaluations'),
            'avg_score'   => new external_value(PARAM_FLOAT, 'Average score'),
            'min_score'   => new external_value(PARAM_FLOAT, 'Minimum score'),
            'max_score'   => new external_value(PARAM_FLOAT, 'Maximum score'),
            'std_score'   => new external_value(PARAM_FLOAT, 'Standard deviation'),
            'gap'         => new external_value(PARAM_FLOAT, 'Gap from 100'),
        ]));
    }
}
