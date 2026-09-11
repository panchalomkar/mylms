<?php
/**
 * AI Roleplay - Reports & Analytics page.
 *
 * Role-based analytics for L&D managers, department managers, and admins.
 * Includes competency heatmaps, leaderboards, AI cost tracking.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/airoleplay/lib.php');

$cmid       = required_param('id',         PARAM_INT);
$period     = optional_param('period',     'month',   PARAM_ALPHA);
$department = optional_param('department', '',        PARAM_TEXT);
$userid     = optional_param('userid',     0,         PARAM_INT);
$format     = optional_param('format',     '',        PARAM_ALPHA);  // csv|pdf

[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'airoleplay');
require_login($course, true, $cm);

$context  = context_module::instance($cm->id);
$scenario = $DB->get_record('airoleplay', ['id' => $cm->instance], '*', MUST_EXIST);

require_capability('mod/airoleplay:viewreports', $context);

// Handle CSV export.
if ($format === 'csv') {
    require_sesskey();
    require_once($CFG->libdir . '/csvlib.class.php');

    $sessions = $DB->get_records_sql(
        "SELECT s.id, u.firstname, u.lastname, u.email,
                s.attempt, s.score_overall, s.passed, s.turn_count,
                s.duration_secs, s.user_dept, s.user_designation,
                s.language, s.scenario_type, s.timecompleted
           FROM {airoleplay_session} s
           JOIN {user} u ON u.id = s.userid
          WHERE s.airoleplay = :rid AND s.status = 'completed'
            AND s.timecompleted >= :since
          ORDER BY s.timecompleted DESC",
        ['rid' => $scenario->id, 'since' => strtotime("-1 $period")]
    );

    $export = new csv_export_writer();
    $export->set_filename('airoleplay_report_' . date('Y-m-d'));
    $export->add_data([
        'First Name', 'Last Name', 'Email', 'Department', 'Designation',
        'Attempt', 'Score', 'Passed', 'Turns', 'Duration (s)',
        'Language', 'Type', 'Completed Date',
    ]);

    foreach ($sessions as $row) {
        $export->add_data([
            $row->firstname, $row->lastname, $row->email,
            $row->user_dept, $row->user_designation,
            $row->attempt, round($row->score_overall ?? 0),
            $row->passed ? 'Yes' : 'No',
            $row->turn_count, $row->duration_secs,
            $row->language, $row->scenario_type,
            $row->timecompleted ? userdate($row->timecompleted) : '',
        ]);
    }

    $export->download_file();
    exit;
}

$PAGE->set_url('/mod/airoleplay/report.php', ['id' => $cmid]);
$PAGE->set_title(format_string($scenario->name) . ' — Reports');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/airoleplay/styles.css');
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'));
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js'));

// Resolve the report time window. Using an array map rather than a match()
// expression so this works on PHP 7.x as well as 8.x (match is 8.0+, and an
// unsupported syntax there would fatal-error the whole reports page).
$periodwindows = [
    'week'    => '-7 days',
    'month'   => '-30 days',
    'quarter' => '-90 days',
    'year'    => '-365 days',
];
$since = strtotime($periodwindows[$period] ?? '-30 days');

// ----------------------------------------------------------------
// Summary stats.
// ----------------------------------------------------------------
$base_where  = "s.airoleplay = :rid AND s.status = 'completed' AND s.timecompleted >= :since";
$base_params = ['rid' => $scenario->id, 'since' => $since];

if ($department) {
    $base_where .= ' AND s.user_dept = :dept';
    $base_params['dept'] = $department;
}

$totals = $DB->get_record_sql(
    "SELECT COUNT(s.id) AS total,
            COUNT(DISTINCT s.userid) AS unique_users,
            AVG(s.score_overall) AS avg_score,
            SUM(CASE WHEN s.passed = 1 THEN 1 ELSE 0 END) AS passed_count
       FROM {airoleplay_session} s
      WHERE $base_where",
    $base_params
);

// Department breakdown.
$dept_data = $DB->get_records_sql(
    "SELECT s.user_dept AS dept,
            COUNT(s.id) AS sessions,
            AVG(s.score_overall) AS avg_score,
            SUM(CASE WHEN s.passed=1 THEN 1 ELSE 0 END) AS passed,
            COUNT(DISTINCT s.userid) AS users
       FROM {airoleplay_session} s
      WHERE s.airoleplay = :rid AND s.status = 'completed' AND s.timecompleted >= :since
        AND s.user_dept != ''
   GROUP BY s.user_dept
   ORDER BY avg_score DESC",
    ['rid' => $scenario->id, 'since' => $since]
);

// Top learners.
$top_learners = $DB->get_records_sql(
    "SELECT u.id, u.firstname, u.lastname, s.user_dept,
            AVG(s.score_overall) AS avg_score,
            COUNT(s.id) AS sessions,
            MAX(s.score_overall) AS best_score
       FROM {airoleplay_session} s
       JOIN {user} u ON u.id = s.userid
      WHERE $base_where
   GROUP BY u.id, u.firstname, u.lastname, s.user_dept
   ORDER BY avg_score DESC",
    $base_params,
    0, 10
);

// Score trend (weekly buckets).
// Done in PHP for cross-DB portability — DATE_TRUNC is Postgres-only and the
// timecompleted column is already a Unix timestamp (INT).
$trend_rows = $DB->get_records_sql(
    "SELECT s.id, s.score_overall, s.timecompleted
       FROM {airoleplay_session} s
      WHERE $base_where
   ORDER BY s.timecompleted ASC",
    $base_params
);

$weekly = [];
foreach ($trend_rows as $row) {
    // Bucket by ISO week start (Monday 00:00 UTC) so buckets are stable across runs.
    $ts        = (int)$row->timecompleted;
    $weekStart = strtotime('monday this week', $ts);
    if ($weekStart === false) {
        $weekStart = $ts - ($ts % (7 * 86400));
    }
    if (!isset($weekly[$weekStart])) {
        $weekly[$weekStart] = ['week_start' => $weekStart, 'sum' => 0.0, 'sessions' => 0];
    }
    $weekly[$weekStart]['sum']      += (float)$row->score_overall;
    $weekly[$weekStart]['sessions'] += 1;
}
ksort($weekly);
$trend_data = [];
foreach ($weekly as $w) {
    $trend_data[] = (object)[
        'week_start' => date('Y-m-d', $w['week_start']),
        'avg_score'  => $w['sessions'] ? $w['sum'] / $w['sessions'] : 0,
        'sessions'   => $w['sessions'],
    ];
}

// Competency averages.
$comp_averages = $DB->get_records_sql(
    "SELECT cr.competency_key, cr.competency_label,
            AVG(cr.score) AS avg_score,
            COUNT(cr.id) AS count,
            MIN(cr.score) AS min_score,
            MAX(cr.score) AS max_score
       FROM {airoleplay_competency_result} cr
       JOIN {airoleplay_session} s ON s.id = cr.session
      WHERE $base_where
   GROUP BY cr.competency_key, cr.competency_label
   ORDER BY avg_score ASC",
    $base_params
);

// Available departments for filter.
$departments = $DB->get_fieldset_sql(
    "SELECT DISTINCT user_dept FROM {airoleplay_session}
      WHERE airoleplay = :rid AND user_dept != ''
   ORDER BY user_dept",
    ['rid' => $scenario->id]
);

// Note: the report page renders its trend line and competency radar via the
// inline JS in report.mustache (using competency_json / trend_json passed
// below). We deliberately do not bootstrap the AMD dashboard controller, which
// depended on a server-side data service and would double-render the charts.

$tpl = [
    'scenario_name'    => format_string($scenario->name),
    'period'           => $period,
    'department'       => $department,
    'departments'      => array_values(array_map(fn($d) => ['name' => $d, 'selected' => $d === $department], $departments)),
    'periods'          => [
        ['value' => 'week',    'label' => 'Last 7 days',  'selected' => $period === 'week'],
        ['value' => 'month',   'label' => 'Last 30 days', 'selected' => $period === 'month'],
        ['value' => 'quarter', 'label' => 'Last quarter', 'selected' => $period === 'quarter'],
        ['value' => 'year',    'label' => 'Last year',    'selected' => $period === 'year'],
    ],
    'stats' => [
        'total_sessions'  => (int)($totals->total ?? 0),
        'unique_learners' => (int)($totals->unique_users ?? 0),
        'avg_score'       => round((float)($totals->avg_score ?? 0), 1),
        'pass_rate'       => $totals->total
            ? round(($totals->passed_count / $totals->total) * 100)
            : 0,
    ],
    'department_data' => array_values(array_map(fn($d) => [
        'dept'      => $d->dept ?: 'Unknown',
        'sessions'  => (int)$d->sessions,
        'avg_score' => round((float)$d->avg_score, 1),
        'passed'    => (int)$d->passed,
        'users'     => (int)$d->users,
        'pass_rate' => $d->sessions ? round(($d->passed / $d->sessions) * 100) : 0,
    ], (array)$dept_data)),
    'has_department_data' => !empty($dept_data),
    'top_learners' => array_values(array_map(function($l, $i) use ($cmid) {
        return [
            'rank'       => $i + 1,
            'userid'     => (int)$l->id,
            'name'       => fullname($l),
            'dept'       => $l->user_dept ?: '—',
            'avg_score'  => round((float)$l->avg_score, 1),
            'best_score' => round((float)$l->best_score),
            'sessions'   => (int)$l->sessions,
            'detail_url' => (new moodle_url('/mod/airoleplay/user_report.php', ['id' => $cmid, 'userid' => $l->id]))->out(false),
        ];
    }, array_values((array)$top_learners), array_keys(array_values((array)$top_learners)))),
    'has_top_learners' => !empty($top_learners),
    'competency_data' => array_values(array_map(fn($c) => [
        'label'     => $c->competency_label,
        'avg_score' => round((float)$c->avg_score, 1),
        'count'     => (int)$c->count,
        'min_score' => round((float)$c->min_score, 1),
        'max_score' => round((float)$c->max_score, 1),
        'gap'       => round(100 - (float)$c->avg_score, 1),
    ], (array)$comp_averages)),
    'has_competency_data' => !empty($comp_averages),
    'view_url'   => (new moodle_url('/mod/airoleplay/view.php',   ['id' => $cmid]))->out(false),
    'export_url' => (new moodle_url('/mod/airoleplay/report.php', ['id' => $cmid, 'format' => 'csv', 'period' => $period, 'sesskey' => sesskey()]))->out(false),
    'cmid'       => $cmid,
    'can_export' => has_capability('mod/airoleplay:viewreports', $context),
    'competency_json' => json_encode(array_values(array_map(fn($c) => [
        'label' => $c->competency_label,
        'score' => round((float)$c->avg_score, 1),
    ], (array)$comp_averages))),
    'trend_json' => json_encode(array_values(array_map(fn($t) => [
        'date'  => date('M j', strtotime($t->week_start ?? 'now')),
        'score' => round((float)$t->avg_score, 1),
        'count' => (int)$t->sessions,
    ], (array)$trend_data))),
    'has_trend_data' => !empty($trend_data),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_airoleplay/report', $tpl);
echo $OUTPUT->footer();