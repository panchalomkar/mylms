<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_businessintelligence\local;

/**
 * Computes the Executive KPI ribbon and the eight Business Intelligence report cards.
 *
 * Data sources:
 *  - Learners / progress: core user, enrolments, course completion, user_lastaccess, standard log.
 *  - Learning paths: local_learningpaths tables (learningpaths, learningpath_courses, learningpath_users,
 *    learningpath_cohorts).
 *  - Training sessions / hours / trainers: ILT (face-to-face), Zoom, Google Meet (see sessions class).
 *  - Competencies / skill gap: local_skillgap (local_skillgap_score).
 *  - OJT ratings: core competencies (competency_usercomp ratings).
 *  - AI role play: mod_airoleplay (airoleplay_session).
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metrics {
    /** @var filters */
    protected $f;
    /** @var int */
    protected $ws;
    /** @var int */
    protected $we;
    /** @var array|null */
    protected $prev;
    /** @var array */
    protected $buckets;
    /** @var int lowest timestamp any query needs */
    protected $lower;
    /** @var array|null memo */
    protected $lpcache = null;
    /** @var array|null memo */
    protected $sessioncache = null;
    /** @var array|null memo */
    protected $popcache = null;

    /** Skill-gap status values (local_skillgap). */
    const SG_WEAK = 'needs_improvement';
    /** Skill-gap status values (local_skillgap). */
    const SG_MODERATE = 'moderate';
    /** Skill-gap status values (local_skillgap). */
    const SG_COMPETENT = 'competent';

    /**
     * Constructor.
     *
     * @param filters $f
     */
    public function __construct(filters $f) {
        $this->f = $f;
        [$this->ws, $this->we] = $f->window();
        $this->prev = $f->previous_window();
        $this->buckets = $f->buckets();
        $this->lower = min(array_filter([
            $this->ws > 0 ? $this->ws : PHP_INT_MAX,
            $this->prev ? $this->prev[0] : PHP_INT_MAX,
            $this->buckets[0]['start'],
        ]));
    }

    // ---------------------------------------------------------------------------------------------------------------
    // Public API.
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * Full dashboard payload (cached).
     *
     * @return array
     */
    public function dashboard(): array {
        $cache = \cache::make('local_businessintelligence', 'reportdata');
        $key = $this->f->cache_key('dashboard');
        $ttl = (int)source::config('cachettl', 600);
        $hit = $cache->get($key);
        if ($hit && $ttl > 0 && (time() - $hit['time']) < $ttl) {
            return $hit['data'];
        }
        $data = $this->build_dashboard();
        if ($ttl > 0) {
            $cache->set($key, ['time' => time(), 'data' => $data]);
        }
        return $data;
    }

    /**
     * Population (memoised).
     *
     * @return array
     */
    public function population(): array {
        if ($this->popcache === null) {
            $this->popcache = $this->f->population();
        }
        return $this->popcache;
    }

    /**
     * Build the payload without cache.
     *
     * @return array
     */
    protected function build_dashboard(): array {
        $labels = array_column($this->buckets, 'label');
        $kpis = [];
        $reports = [];

        // Learners.
        $learners = $this->learner_counts();
        $kpis['totallearners'] = $this->kpi($learners['total'], $learners['atstart'], $learners['spark']);
        $active = $this->active_learners();
        $kpis['activelearners'] = $this->kpi($active['cur'], $active['prev'], $active['spark']);

        // Learning progress.
        $cur = $this->enrol_totals($this->enrol_per_user($this->ws, $this->we));
        $prev = $this->prev ? $this->enrol_totals($this->enrol_per_user($this->prev[0], $this->prev[1])) : null;
        $eb = $this->enrol_buckets();
        $kpis['completionrate'] = $this->kpi($cur['rate'], $prev ? $prev['rate'] : null, $eb['rate'], 'pct');
        $kpis['overduelearners'] = $this->kpi($cur['overduelearners'], $prev ? $prev['overduelearners'] : null, $eb['overdue']);
        $reports['learning'] = [
            'stats' => [
                'learners' => $this->num($learners['total']),
                'assigned' => $this->num($cur['assigned']),
                'completedlearners' => $this->num($cur['completedlearners']),
                'overduelearners' => $this->num($cur['overduelearners']),
            ],
            'charts' => [
                'funnel' => ['stages' => [
                    ['label' => get_string('funnel_assigned', 'local_businessintelligence'), 'value' => $cur['assigned']],
                    ['label' => get_string('funnel_started', 'local_businessintelligence'), 'value' => $cur['started']],
                    ['label' => get_string('funnel_completed', 'local_businessintelligence'), 'value' => $cur['completed']],
                ]],
                'trend' => ['labels' => $labels, 'values' => $eb['overdue']],
            ],
        ];

        // Learning paths.
        $lp = $this->lp_summary();
        $kpis['lpcompleted'] = $this->kpi($lp['completed'], $lp['completedprev'], $lp['spark']);
        $reports['learningpaths'] = [
            'stats' => [
                'total' => $this->num($lp['paths']),
                'completed' => $this->num($lp['completed']),
                'inprogress' => $this->num($lp['inprogress']),
                'notstarted' => $this->num($lp['notstarted']),
            ],
            'charts' => ['bars' => $lp['chart']],
        ];

        // Sessions / hours / trainers.
        $sessions = $this->scoped_sessions();
        $scur = sessions::stats($sessions, $this->ws, $this->we);
        $sprev = $this->prev ? sessions::stats($sessions, $this->prev[0], $this->prev[1]) : null;
        $sparksessions = $sparkhours = $sparktrainers = $attendance = [];
        foreach ($this->buckets as $b) {
            $bs = sessions::stats($sessions, $b['start'], $b['end']);
            $sparksessions[] = $bs['sessions'];
            $sparkhours[] = round($bs['learnersecs'] / HOURSECS, 1);
            $sparktrainers[] = count($bs['trainers']);
            $attendance[] = $bs['attendancepct'];
        }
        $kpis['sessions'] = $this->kpi($scur['sessions'], $sprev ? $sprev['sessions'] : null, $sparksessions);
        $hours = round($scur['learnersecs'] / HOURSECS, 1);
        $kpis['traininghours'] = $this->kpi($hours, $sprev ? round($sprev['learnersecs'] / HOURSECS, 1) : null, $sparkhours);
        $kpis['trainers'] = $this->kpi(count($scur['trainers']), $sprev ? count($sprev['trainers']) : null, $sparktrainers);
        $reports['sessions'] = [
            'stats' => [
                'total' => $this->num($scur['sessions']),
                'attendance' => $scur['attendancepct'] === null ? '–' : $this->pct($scur['attendancepct'], 0),
                'hours' => $this->num($hours),
                'noshow' => $this->num(count($scur['noshowusers'])),
            ],
            'charts' => [
                'donut' => ['items' => [
                    ['label' => get_string('facetoface', 'local_businessintelligence'), 'value' => $scur['f2f']],
                    ['label' => get_string('online', 'local_businessintelligence'), 'value' => $scur['online']],
                ]],
                'trend' => ['labels' => $labels, 'values' => $attendance, 'max' => 100],
            ],
        ];
        $reports['trainer'] = $this->trainer_report($sessions, $scur);

        // Competencies (skill gap).
        $sg = $this->skillgap_summary();
        $kpis['competencies'] = $this->kpi($sg['kpi'], $sg['kpiprev'], $sg['spark']);
        $reports['skillgap'] = [
            'stats' => [
                'total' => $this->num($sg['total']),
                'users' => $this->num($sg['users']),
                'weak' => $this->num($sg['weak']),
                'needimprovement' => $this->num($sg['needimprovement']),
            ],
            'charts' => ['list' => $sg['list']],
        ];

        // OJT competency ratings.
        $reports['ojt'] = $this->ojt_report();

        // AI role play.
        $rp = $this->roleplay_summary();
        $kpis['roleplay'] = $this->kpi($rp['kpi'], $rp['kpiprev'], $rp['spark']);
        $reports['roleplay'] = $rp['report'];

        // Training outcome.
        $reports['outcome'] = $this->outcome_report($scur);

        return [
            'kpis' => $kpis,
            'reports' => $reports,
            'labels' => $labels,
            'filtercount' => $this->f->active_count(),
            'generated' => userdate(time(), get_string('strftimedatetimeshort', 'core_langconfig')),
        ];
    }

    // ---------------------------------------------------------------------------------------------------------------
    // Formatting helpers.
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * KPI tile structure.
     *
     * @param float|int $value
     * @param float|int|null $prev
     * @param array $spark
     * @param string $format int|pct
     * @return array
     */
    protected function kpi($value, $prev, array $spark, string $format = 'int'): array {
        $trend = null;
        if ($prev !== null) {
            if ((float)$prev == 0.0) {
                $trend = ((float)$value > 0) ? 100.0 : 0.0;
            } else {
                $trend = round(((float)$value - (float)$prev) * 100 / abs((float)$prev), 1);
            }
        }
        return [
            'value' => $value,
            'display' => $format === 'pct' ? $this->pct($value) : $this->num($value),
            'trend' => $trend,
            'spark' => array_map(function($v) {
                return $v === null ? 0 : (float)$v;
            }, $spark),
        ];
    }

    /**
     * Grouped number.
     *
     * @param float|int $n
     * @return string
     */
    public function num($n): string {
        $n = (float)$n;
        $dec = (abs($n - round($n)) > 0.05) ? 1 : 0;
        return number_format($n, $dec, '.', ',');
    }

    /**
     * Percentage string.
     *
     * @param float|int|null $n
     * @param int $dec
     * @return string
     */
    public function pct($n, int $dec = 1): string {
        if ($n === null) {
            return '–';
        }
        return number_format((float)$n, $dec, '.', ',') . '%';
    }

    /**
     * Percent helper.
     *
     * @param float|int $num
     * @param float|int $den
     * @return float|null
     */
    protected static function ratio($num, $den): ?float {
        return $den > 0 ? round($num * 100 / $den, 1) : null;
    }

    // ---------------------------------------------------------------------------------------------------------------
    // Learners.
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * Total learners now, at window start, and per bucket (cumulative by account creation).
     *
     * @return array
     */
    protected function learner_counts(): array {
        global $DB;
        $p = new params();
        $cols = ["COUNT(1) AS total", "SUM(CASE WHEN u.timecreated <= " . $p->add(max($this->ws, 0)) . " THEN 1 ELSE 0 END) AS atstart"];
        foreach ($this->buckets as $i => $b) {
            $cols[] = "SUM(CASE WHEN u.timecreated <= " . $p->add($b['end']) . " THEN 1 ELSE 0 END) AS b{$i}";
        }
        $cond = $this->f->user_condition('u.id', $p);
        $r = $DB->get_record_sql("SELECT " . implode(', ', $cols) . " FROM {user} u WHERE {$cond}", $p->values);
        $spark = [];
        foreach ($this->buckets as $i => $b) {
            $spark[] = (int)$r->{'b' . $i};
        }
        return [
            'total' => (int)$r->total,
            'atstart' => $this->ws > 0 ? (int)$r->atstart : null,
            'spark' => $spark,
        ];
    }

    /**
     * Distinct learners with any activity (standard log) in the window, previous window and buckets.
     *
     * @return array
     */
    protected function active_learners(): array {
        global $DB;
        $p = new params();
        if (!source::has_log()) {
            $cols = ["SUM(CASE WHEN u.lastaccess >= " . $p->add(max(1, $this->ws)) . " THEN 1 ELSE 0 END) AS cur"];
            foreach ($this->buckets as $i => $b) {
                $cols[] = "SUM(CASE WHEN u.lastaccess >= " . $p->add($b['start']) . " THEN 1 ELSE 0 END) AS b{$i}";
            }
            $cond = $this->f->user_condition('u.id', $p);
            $r = $DB->get_record_sql("SELECT " . implode(', ', $cols) . " FROM {user} u WHERE {$cond}", $p->values);
            $spark = [];
            foreach ($this->buckets as $i => $b) {
                $spark[] = (int)$r->{'b' . $i};
            }
            return ['cur' => (int)$r->cur, 'prev' => null, 'spark' => $spark];
        }

        $cols = [];
        if ($this->ws > 0) {
            $cols[] = "COUNT(DISTINCT CASE WHEN l.timecreated >= " . $p->add($this->ws) . " THEN l.userid END) AS cur";
        }
        if ($this->prev) {
            $cols[] = "COUNT(DISTINCT CASE WHEN l.timecreated >= " . $p->add($this->prev[0]) . " AND l.timecreated <= "
                . $p->add($this->prev[1]) . " THEN l.userid END) AS prev";
        }
        foreach ($this->buckets as $i => $b) {
            $cols[] = "COUNT(DISTINCT CASE WHEN l.timecreated >= " . $p->add($b['start']) . " AND l.timecreated <= "
                . $p->add($b['end']) . " THEN l.userid END) AS b{$i}";
        }
        $cond = $this->f->user_condition('l.userid', $p);
        $lower = $p->add($this->lower);
        $r = $DB->get_record_sql("SELECT " . implode(', ', $cols) . "
                                    FROM {logstore_standard_log} l
                                   WHERE l.timecreated >= {$lower} AND {$cond}", $p->values);
        $spark = [];
        foreach ($this->buckets as $i => $b) {
            $spark[] = (int)$r->{'b' . $i};
        }
        if ($this->ws > 0) {
            $cur = (int)$r->cur;
        } else {
            // All time: everyone who has ever logged in.
            $p2 = new params();
            $cond2 = $this->f->user_condition('u.id', $p2);
            $cur = $DB->count_records_sql("SELECT COUNT(1) FROM {user} u WHERE u.lastaccess > 0 AND {$cond2}", $p2->values);
        }
        return ['cur' => $cur, 'prev' => $this->prev ? (int)$r->prev : null, 'spark' => $spark];
    }

    // ---------------------------------------------------------------------------------------------------------------
    // Enrolments / learning progress.
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * One row per (learner, course) assignment for the population. Teachers are excluded.
     *
     * @param params $p
     * @return string
     */
    protected function enrol_base(params $p): string {
        $cond = $this->f->user_condition('ue.userid', $p);
        $site = $p->add(SITEID);
        $ctx = $p->add(CONTEXT_COURSE);
        return "SELECT ue.userid, e.courseid, MIN(ue.timecreated) AS timeassigned, MAX(ue.timeend) AS timeend,
                       MAX(c.enddate) AS cenddate
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.status = 0
                  JOIN {course} c ON c.id = e.courseid AND c.visible = 1 AND c.id <> {$site}
                 WHERE ue.status = 0 AND {$cond}
                   AND NOT EXISTS (SELECT 1
                                     FROM {role_assignments} tra
                                     JOIN {context} tctx ON tctx.id = tra.contextid AND tctx.contextlevel = {$ctx}
                                     JOIN {role} tr ON tr.id = tra.roleid AND tr.archetype IN ('editingteacher', 'teacher')
                                    WHERE tra.userid = ue.userid AND tctx.instanceid = e.courseid)
              GROUP BY ue.userid, e.courseid";
    }

    /**
     * Due date SQL expression for the enrolment base alias x.
     *
     * @return string
     */
    protected static function due(): string {
        return "(CASE WHEN x.timeend > 0 THEN x.timeend WHEN x.cenddate > 0 THEN x.cenddate ELSE 0 END)";
    }

    /**
     * Per-learner assignment stats for courses assigned in [s, e], evaluated at e.
     *
     * @param int $s
     * @param int $e
     * @return array userid => [assigned, started, completed, overdue]
     */
    public function enrol_per_user(int $s, int $e): array {
        global $DB;
        $p = new params();
        $base = $this->enrol_base($p);
        $due = self::due();
        $sql = "SELECT x.userid,
                       COUNT(1) AS assigned,
                       SUM(CASE WHEN la.id IS NOT NULL OR cc.timestarted > 0 OR cc.timecompleted > 0 THEN 1 ELSE 0 END)
                           AS started,
                       SUM(CASE WHEN cc.timecompleted > 0 AND cc.timecompleted <= " . $p->add($e) . " THEN 1 ELSE 0 END)
                           AS completed,
                       SUM(CASE WHEN {$due} > 0 AND {$due} < " . $p->add($e) . "
                                 AND (cc.timecompleted IS NULL OR cc.timecompleted = 0 OR cc.timecompleted > " . $p->add($e) . ")
                                THEN 1 ELSE 0 END) AS overdue
                  FROM ({$base}) x
             LEFT JOIN {course_completions} cc ON cc.userid = x.userid AND cc.course = x.courseid
             LEFT JOIN {user_lastaccess} la ON la.userid = x.userid AND la.courseid = x.courseid
                 WHERE x.timeassigned >= " . $p->add($s) . " AND x.timeassigned <= " . $p->add($e) . "
              GROUP BY x.userid";
        $out = [];
        $rs = $DB->get_recordset_sql($sql, $p->values);
        foreach ($rs as $r) {
            $out[(int)$r->userid] = [
                'assigned' => (int)$r->assigned,
                'started' => (int)$r->started,
                'completed' => (int)$r->completed,
                'overdue' => (int)$r->overdue,
            ];
        }
        $rs->close();
        return $out;
    }

    /**
     * Totals from per-user enrolment rows.
     *
     * @param array $rows
     * @return array
     */
    protected function enrol_totals(array $rows): array {
        $t = ['assigned' => 0, 'started' => 0, 'completed' => 0, 'overduelearners' => 0, 'completedlearners' => 0,
            'learners' => count($rows)];
        foreach ($rows as $r) {
            $t['assigned'] += $r['assigned'];
            $t['started'] += $r['started'];
            $t['completed'] += $r['completed'];
            if ($r['overdue'] > 0) {
                $t['overduelearners']++;
            }
            if ($r['assigned'] > 0 && $r['completed'] >= $r['assigned']) {
                $t['completedlearners']++;
            }
        }
        $t['rate'] = self::ratio($t['completed'], $t['assigned']) ?? 0.0;
        return $t;
    }

    /**
     * Completion rate of each bucket's assignment cohort, and overdue learners at each bucket end.
     *
     * @return array ['rate' => float[], 'overdue' => int[]]
     */
    protected function enrol_buckets(): array {
        global $DB;
        $p = new params();
        $base = $this->enrol_base($p);
        $due = self::due();
        $cols = [];
        $rs = max(0, $this->ws);
        foreach ($this->buckets as $i => $b) {
            $cols[] = "SUM(CASE WHEN x.timeassigned >= " . $p->add($b['start']) . " AND x.timeassigned <= " . $p->add($b['end'])
                . " THEN 1 ELSE 0 END) AS a{$i}";
            $cols[] = "SUM(CASE WHEN x.timeassigned >= " . $p->add($b['start']) . " AND x.timeassigned <= " . $p->add($b['end'])
                . " AND cc.timecompleted > 0 THEN 1 ELSE 0 END) AS c{$i}";
            $cols[] = "COUNT(DISTINCT CASE WHEN x.timeassigned >= " . $p->add($rs) . " AND x.timeassigned <= " . $p->add($b['end'])
                . " AND {$due} > 0 AND {$due} < " . $p->add($b['end'])
                . " AND (cc.timecompleted IS NULL OR cc.timecompleted = 0 OR cc.timecompleted > " . $p->add($b['end']) . ")"
                . " THEN x.userid END) AS o{$i}";
        }
        $r = $DB->get_record_sql("SELECT " . implode(', ', $cols) . "
                                    FROM ({$base}) x
                               LEFT JOIN {course_completions} cc ON cc.userid = x.userid AND cc.course = x.courseid",
            $p->values);
        $rate = $overdue = [];
        foreach ($this->buckets as $i => $b) {
            $rate[] = self::ratio((int)$r->{'c' . $i}, (int)$r->{'a' . $i}) ?? 0.0;
            $overdue[] = (int)$r->{'o' . $i};
        }
        return ['rate' => $rate, 'overdue' => $overdue];
    }

    // ---------------------------------------------------------------------------------------------------------------
    // Learning paths (local_learningpaths).
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * Learning path membership and completion state for the population.
     *
     * A learner completes a path when every required course (learningpath_courses.required = 1; all active courses
     * if none are flagged) has a course completion. Completion time = the latest of those completions.
     *
     * @return array ['paths' => [id => name], 'pairs' => [[lpid, userid, ctime|null, started bool]]]
     */
    public function lp_pairs(): array {
        global $DB;
        if ($this->lpcache !== null) {
            return $this->lpcache;
        }
        $this->lpcache = ['paths' => [], 'pairs' => []];
        if (!source::has_table('learningpaths') || !source::has_table('learningpath_courses')) {
            return $this->lpcache;
        }
        $deleted = source::has_field('learningpaths', 'deleted') ? 'WHERE deleted = 0' : '';
        $paths = $DB->get_records_sql_menu("SELECT id, name FROM {learningpaths} {$deleted} ORDER BY name");
        if (!$paths) {
            return $this->lpcache;
        }
        $active = source::has_field('learningpath_courses', 'course_active') ? 'AND lc.course_active = 1' : '';
        $req = source::has_field('learningpath_courses', 'required') ? 'lc.required' : '0';
        $p = new params();
        $in = $p->in(array_keys($paths));
        $courses = [];
        $required = [];
        $rs = $DB->get_recordset_sql("SELECT lc.id, lc.learningpathid, lc.courseid, {$req} AS required
                                        FROM {learningpath_courses} lc
                                       WHERE lc.learningpathid {$in} {$active}", $p->values);
        foreach ($rs as $r) {
            $courses[(int)$r->learningpathid][(int)$r->courseid] = (int)$r->courseid;
            if ((int)$r->required) {
                $required[(int)$r->learningpathid][(int)$r->courseid] = (int)$r->courseid;
            }
        }
        $rs->close();

        // Members: direct users + cohort members.
        $members = [];
        if (source::has_table('learningpath_users')) {
            $p = new params();
            $in = $p->in(array_keys($paths));
            $cond = $this->f->user_condition('lu.userid', $p);
            $rs = $DB->get_recordset_sql("SELECT lu.id, lu.learningpathid, lu.userid FROM {learningpath_users} lu
                                           WHERE lu.learningpathid {$in} AND {$cond}", $p->values);
            foreach ($rs as $r) {
                $members[(int)$r->learningpathid][(int)$r->userid] = true;
            }
            $rs->close();
        }
        if (source::has_table('learningpath_cohorts')) {
            $p = new params();
            $in = $p->in(array_keys($paths));
            $cond = $this->f->user_condition('cm.userid', $p);
            $rs = $DB->get_recordset_sql("SELECT cm.id, lc.learningpathid, cm.userid
                                            FROM {learningpath_cohorts} lc
                                            JOIN {cohort_members} cm ON cm.cohortid = lc.cohortid
                                           WHERE lc.learningpathid {$in} AND {$cond}", $p->values);
            foreach ($rs as $r) {
                $members[(int)$r->learningpathid][(int)$r->userid] = true;
            }
            $rs->close();
        }

        // Completions and access for all LP courses.
        $allcourses = [];
        foreach ($courses as $list) {
            $allcourses += $list;
        }
        $done = [];
        $touched = [];
        if ($allcourses && $members) {
            foreach (array_chunk(array_values($allcourses), 500) as $chunk) {
                $p = new params();
                $in = $p->in($chunk);
                $cond = $this->f->user_condition('cc.userid', $p);
                $rs = $DB->get_recordset_sql("SELECT cc.id, cc.userid, cc.course, cc.timecompleted, cc.timestarted
                                                FROM {course_completions} cc
                                               WHERE cc.course {$in} AND {$cond}", $p->values);
                foreach ($rs as $r) {
                    if ((int)$r->timecompleted > 0) {
                        $done[(int)$r->userid][(int)$r->course] = (int)$r->timecompleted;
                    }
                    if ((int)$r->timestarted > 0 || (int)$r->timecompleted > 0) {
                        $touched[(int)$r->userid][(int)$r->course] = true;
                    }
                }
                $rs->close();
                $p = new params();
                $in = $p->in($chunk);
                $cond = $this->f->user_condition('la.userid', $p);
                $rs = $DB->get_recordset_sql("SELECT la.id, la.userid, la.courseid FROM {user_lastaccess} la
                                               WHERE la.courseid {$in} AND {$cond}", $p->values);
                foreach ($rs as $r) {
                    $touched[(int)$r->userid][(int)$r->courseid] = true;
                }
                $rs->close();
            }
        }

        $pairs = [];
        foreach ($members as $lpid => $users) {
            $req = !empty($required[$lpid]) ? $required[$lpid] : ($courses[$lpid] ?? []);
            foreach ($users as $uid => $unused) {
                $ctime = null;
                if ($req) {
                    $times = [];
                    foreach ($req as $cid) {
                        if (!isset($done[$uid][$cid])) {
                            $times = null;
                            break;
                        }
                        $times[] = $done[$uid][$cid];
                    }
                    if ($times) {
                        $ctime = max($times);
                    }
                }
                $started = false;
                foreach ($courses[$lpid] ?? [] as $cid) {
                    if (isset($touched[$uid][$cid]) || isset($done[$uid][$cid])) {
                        $started = true;
                        break;
                    }
                }
                $pairs[] = [$lpid, $uid, $ctime, $started];
            }
        }
        $this->lpcache = ['paths' => $paths, 'pairs' => $pairs];
        return $this->lpcache;
    }

    /**
     * Learning path KPI + card.
     *
     * @return array
     */
    protected function lp_summary(): array {
        $data = $this->lp_pairs();
        $at = $this->we;
        $res = ['paths' => count($data['paths']), 'completed' => 0, 'inprogress' => 0, 'notstarted' => 0,
            'completedprev' => $this->ws > 0 ? 0 : null, 'spark' => array_fill(0, count($this->buckets), 0)];
        $perlp = [];
        foreach ($data['pairs'] as [$lpid, $uid, $ctime, $started]) {
            if (!isset($perlp[$lpid])) {
                $perlp[$lpid] = ['c' => 0, 'i' => 0, 'n' => 0];
            }
            if ($ctime !== null && $ctime <= $at) {
                $res['completed']++;
                $perlp[$lpid]['c']++;
            } else if ($started) {
                $res['inprogress']++;
                $perlp[$lpid]['i']++;
            } else {
                $res['notstarted']++;
                $perlp[$lpid]['n']++;
            }
            if ($ctime !== null) {
                if ($this->ws > 0 && $ctime <= $this->ws) {
                    $res['completedprev']++;
                }
                foreach ($this->buckets as $i => $b) {
                    if ($ctime <= $b['end']) {
                        $res['spark'][$i]++;
                    }
                }
            }
        }
        uasort($perlp, function($a, $b) {
            return ($b['c'] + $b['i'] + $b['n']) <=> ($a['c'] + $a['i'] + $a['n']);
        });
        $perlp = array_slice($perlp, 0, 6, true);
        $chart = [
            'categories' => [],
            'series' => [
                ['label' => get_string('notstarted', 'local_businessintelligence'), 'color' => 'muted', 'values' => []],
                ['label' => get_string('inprogress', 'local_businessintelligence'), 'color' => 'accent', 'values' => []],
                ['label' => get_string('completed', 'local_businessintelligence'), 'color' => 'success', 'values' => []],
            ],
        ];
        foreach ($perlp as $lpid => $c) {
            $chart['categories'][] = format_string($data['paths'][$lpid]);
            $chart['series'][0]['values'][] = $c['n'];
            $chart['series'][1]['values'][] = $c['i'];
            $chart['series'][2]['values'][] = $c['c'];
        }
        $res['chart'] = $chart;
        return $res;
    }

    // ---------------------------------------------------------------------------------------------------------------
    // Sessions and trainers.
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * Sessions between the lowest needed timestamp and now, scoped to the population.
     *
     * @return array
     */
    public function scoped_sessions(): array {
        if ($this->sessioncache === null) {
            $all = sessions::fetch($this->ws > 0 ? (int)$this->lower : 0, $this->we);
            $this->sessioncache = sessions::scope($all, $this->population(), $this->f->has_user_filters());
        }
        return $this->sessioncache;
    }

    /**
     * Guest (external) trainer ids among the given trainers, via the trainer-type profile field.
     *
     * @param int[] $trainerids
     * @return array userid => true
     */
    public static function guest_trainers(array $trainerids): array {
        $patterns = array_filter(array_map(function($v) {
            return \core_text::strtolower(trim($v));
        }, explode(',', (string)source::config('guestpattern', 'guest,external'))));
        $guests = [];
        foreach (profile::values_for('trainertype', $trainerids) as $uid => $value) {
            $value = \core_text::strtolower($value);
            foreach ($patterns as $pat) {
                if ($pat !== '' && strpos($value, $pat) !== false) {
                    $guests[$uid] = true;
                    break;
                }
            }
        }
        return $guests;
    }

    /**
     * Trainer analytics card.
     *
     * @param array $sessions
     * @param array $scur
     * @return array
     */
    protected function trainer_report(array $sessions, array $scur): array {
        $alltrainers = [];
        foreach ($sessions as $s) {
            foreach ($s['trainers'] as $t) {
                $alltrainers[$t] = $t;
            }
        }
        $guests = self::guest_trainers($alltrainers);
        $inhouse = 0;
        $guest = 0;
        foreach ($scur['trainers'] as $t) {
            isset($guests[$t]) ? $guest++ : $inhouse++;
        }
        $labels = [];
        $sin = [];
        $sgu = [];
        foreach ($this->buckets as $b) {
            $labels[] = $b['label'];
            $ci = 0;
            $cg = 0;
            foreach ($sessions as $s) {
                if (!$s['trainers'] || $s['start'] < $b['start'] || $s['start'] > $b['end']) {
                    continue;
                }
                $isguest = false;
                foreach ($s['trainers'] as $t) {
                    if (isset($guests[$t])) {
                        $isguest = true;
                        break;
                    }
                }
                $isguest ? $cg++ : $ci++;
            }
            $sin[] = $ci;
            $sgu[] = $cg;
        }
        return [
            'stats' => [
                'inhouse' => $this->num($inhouse),
                'guest' => $this->num($guest),
                'hours' => $this->num(round($scur['iltsecs'] / HOURSECS, 1)),
                'sessions' => $this->num($scur['iltsessions']),
            ],
            'charts' => [
                'bars' => [
                    'categories' => $labels,
                    'series' => [
                        ['label' => get_string('inhouse', 'local_businessintelligence'), 'color' => 'chart1', 'values' => $sin],
                        ['label' => get_string('guest', 'local_businessintelligence'), 'color' => 'chart2', 'values' => $sgu],
                    ],
                ],
                'ratings' => $this->trainer_ratings($scur['trainers'], $guests),
            ],
        ];
    }

    /**
     * Average trainer feedback rating (out of 5) from the ILT plugin, when it stores one.
     *
     * Looks for a "<iltmodule>_trainer_feedback" style table with (trainerid|userid, rating). Returns nulls otherwise.
     *
     * @param int[] $trainers
     * @param array $guests
     * @return array
     */
    protected function trainer_ratings(array $trainers, array $guests): array {
        global $DB;
        $out = [
            ['label' => get_string('inhouse', 'local_businessintelligence'), 'value' => null],
            ['label' => get_string('guest', 'local_businessintelligence'), 'value' => null],
        ];
        $mod = clean_param((string)source::config('iltmodule', 'ilt'), PARAM_ALPHANUMEXT);
        $table = null;
        foreach ([$mod . '_trainer_feedback', $mod . '_trainer_rating', $mod . '_feedback'] as $t) {
            if (source::has_table($t) && source::has_field($t, 'rating')
                    && (source::has_field($t, 'trainerid') || source::has_field($t, 'userid'))) {
                $table = $t;
                break;
            }
        }
        if (!$table || !$trainers) {
            return $out;
        }
        $col = source::has_field($table, 'trainerid') ? 'trainerid' : 'userid';
        $p = new params();
        $in = $p->in(array_values($trainers));
        $rows = $DB->get_records_sql("SELECT {$col} AS tid, AVG(rating) AS avgrating FROM {{$table}}
                                       WHERE {$col} {$in} GROUP BY {$col}", $p->values);
        $sum = [0 => [0, 0], 1 => [0, 0]];
        foreach ($rows as $r) {
            $k = isset($guests[(int)$r->tid]) ? 1 : 0;
            $sum[$k][0] += (float)$r->avgrating;
            $sum[$k][1]++;
        }
        foreach ($sum as $k => [$s, $n]) {
            $out[$k]['value'] = $n ? round($s / $n, 1) : null;
        }
        return $out;
    }

    // ---------------------------------------------------------------------------------------------------------------
    // Skill gap (local_skillgap).
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * Extra WHERE for skill-gap queries (competency filter).
     *
     * @param params $p
     * @param string $alias
     * @return string
     */
    protected function sg_where(params $p, string $alias = 's'): string {
        $sql = $this->f->user_condition("{$alias}.userid", $p);
        if ($this->f->competency > 0) {
            $sql .= " AND {$alias}.compid = " . $p->add($this->f->competency);
        }
        return $sql;
    }

    /**
     * Skill-gap KPI and card data.
     *
     * @return array
     */
    protected function skillgap_summary(): array {
        global $DB;
        $empty = ['kpi' => 0, 'kpiprev' => $this->prev ? 0 : null, 'spark' => array_fill(0, count($this->buckets), 0),
            'total' => 0, 'users' => 0, 'weak' => 0, 'needimprovement' => 0, 'list' => []];
        if (!source::has_table('local_skillgap_score')) {
            return $empty;
        }
        // KPI: competencies with at least one assessment result in the window.
        $p = new params();
        $cols = ["COUNT(DISTINCT CASE WHEN s.timecreated >= " . $p->add($this->ws) . " AND s.timecreated <= "
            . $p->add($this->we) . " THEN s.compid END) AS cur"];
        if ($this->prev) {
            $cols[] = "COUNT(DISTINCT CASE WHEN s.timecreated >= " . $p->add($this->prev[0]) . " AND s.timecreated <= "
                . $p->add($this->prev[1]) . " THEN s.compid END) AS prev";
        }
        foreach ($this->buckets as $i => $b) {
            $cols[] = "COUNT(DISTINCT CASE WHEN s.timecreated >= " . $p->add($b['start']) . " AND s.timecreated <= "
                . $p->add($b['end']) . " THEN s.compid END) AS b{$i}";
        }
        $where = $this->sg_where($p);
        $r = $DB->get_record_sql("SELECT " . implode(', ', $cols) . " FROM {local_skillgap_score} s WHERE {$where}", $p->values);
        $res = $empty;
        $res['kpi'] = (int)$r->cur;
        $res['kpiprev'] = $this->prev ? (int)$r->prev : null;
        foreach ($this->buckets as $i => $b) {
            $res['spark'][$i] = (int)$r->{'b' . $i};
        }

        // Card.
        $res['total'] = $this->f->competency > 0 ? 1 :
            (source::has_table('local_skillgap_comp') ? $DB->count_records('local_skillgap_comp') : 0);
        $p = new params();
        $weak = $p->add(self::SG_WEAK);
        $where = $this->sg_where($p) . " AND s.timecreated >= " . $p->add($this->ws) . " AND s.timecreated <= " . $p->add($this->we);
        $r = $DB->get_record_sql("SELECT COUNT(DISTINCT s.userid) AS users,
                                         COUNT(DISTINCT CASE WHEN s.status = {$weak} THEN s.userid END) AS ni
                                    FROM {local_skillgap_score} s WHERE {$where}", $p->values);
        $res['users'] = (int)$r->users;
        $res['needimprovement'] = (int)$r->ni;

        $hassub = source::has_field('local_skillgap_score', 'subcompid');
        $subsel = $hassub ? 'COALESCE(s.subcompid, 0)' : '0';
        $p = new params();
        $weak = $p->add(self::SG_WEAK);
        $where = $this->sg_where($p) . " AND s.timecreated >= " . $p->add($this->ws) . " AND s.timecreated <= " . $p->add($this->we);
        $pct = source::skillgap_pct('s');
        $groupby = $hassub ? "s.compid, COALESCE(s.subcompid, 0)" : "s.compid";
        $rs = $DB->get_recordset_sql("SELECT s.compid, {$subsel} AS subcompid, AVG({$pct}) AS avgpct,
                                             COUNT(DISTINCT CASE WHEN s.status = {$weak} THEN s.userid END) AS weakusers
                                        FROM {local_skillgap_score} s
                                       WHERE {$where}
                                    GROUP BY {$groupby}", $p->values);
        $groups = [];
        foreach ($rs as $g) {
            $groups[] = $g;
        }
        $rs->close();
        $threshold = (float)source::config('weakthreshold', 60);
        $comps = [];
        $subs = [];
        if ($groups) {
            $compids = array_unique(array_map(function($g) {
                return (int)$g->compid;
            }, $groups));
            $comps = source::has_table('local_skillgap_comp') ? $DB->get_records_list('local_skillgap_comp', 'id', $compids,
                '', 'id, name') : [];
            $subids = array_filter(array_unique(array_map(function($g) {
                return (int)$g->subcompid;
            }, $groups)));
            if ($subids && source::has_table('local_skillgap_subcomp')) {
                $subs = $DB->get_records_list('local_skillgap_subcomp', 'id', $subids, '', 'id, name');
            }
        }
        usort($groups, function($a, $b) {
            return (float)$a->avgpct <=> (float)$b->avgpct;
        });
        foreach ($groups as $g) {
            if ((float)$g->avgpct < $threshold) {
                $res['weak']++;
            }
        }
        $rank = 0;
        foreach (array_slice($groups, 0, 5) as $g) {
            $name = isset($comps[$g->compid]) ? format_string($comps[$g->compid]->name) : '#' . $g->compid;
            if ((int)$g->subcompid && isset($subs[$g->subcompid])) {
                $name .= ' — ' . format_string($subs[$g->subcompid]->name);
            }
            $res['list'][] = [
                'rank' => ++$rank,
                'name' => $name,
                'avg' => (int)round((float)$g->avgpct),
                'users' => (int)$g->weakusers,
            ];
        }
        return $res;
    }

    /**
     * Per-learner skill-gap stats in the window.
     *
     * @return array userid => [...]
     */
    public function skillgap_per_user(): array {
        global $DB;
        if (!source::has_table('local_skillgap_score')) {
            return [];
        }
        $p = new params();
        $w = $p->add(self::SG_WEAK);
        $m = $p->add(self::SG_MODERATE);
        $c = $p->add(self::SG_COMPETENT);
        $pct = source::skillgap_pct('s');
        $where = $this->sg_where($p) . " AND s.timecreated >= " . $p->add($this->ws) . " AND s.timecreated <= " . $p->add($this->we);
        $rs = $DB->get_recordset_sql("SELECT s.userid, COUNT(DISTINCT s.compid) AS comps, SUM({$pct}) AS sumpct, COUNT(1) AS n,
                                             COUNT(DISTINCT CASE WHEN s.status = {$w} THEN s.compid END) AS weak,
                                             COUNT(DISTINCT CASE WHEN s.status = {$m} THEN s.compid END) AS moderate,
                                             COUNT(DISTINCT CASE WHEN s.status = {$c} THEN s.compid END) AS competent
                                        FROM {local_skillgap_score} s
                                       WHERE {$where}
                                    GROUP BY s.userid", $p->values);
        $out = [];
        foreach ($rs as $r) {
            $out[(int)$r->userid] = [
                'assessed' => 1, 'comps' => (int)$r->comps, 'sumpct' => (float)$r->sumpct, 'n' => (int)$r->n,
                'weak' => (int)$r->weak, 'moderate' => (int)$r->moderate, 'competent' => (int)$r->competent,
            ];
        }
        $rs->close();
        return $out;
    }

    // ---------------------------------------------------------------------------------------------------------------
    // OJT competency ratings (core competencies).
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * Rated (user, competency) rows in the window with their Red/Yellow/Green zone.
     *
     * @return array list of [userid, competencyid, name, zone]
     */
    public function ojt_rows(): array {
        global $DB;
        if (!source::has_table('competency_usercomp')) {
            return [];
        }
        $p = new params();
        $cond = $this->f->user_condition('uc.userid', $p);
        $rs = $DB->get_recordset_sql("SELECT uc.id, uc.userid, uc.competencyid, uc.grade, uc.proficiency, c.shortname,
                                             c.scaleid, f.scaleid AS fscaleid
                                        FROM {competency_usercomp} uc
                                        JOIN {competency} c ON c.id = uc.competencyid
                                        JOIN {competency_framework} f ON f.id = c.competencyframeworkid
                                       WHERE uc.grade IS NOT NULL
                                         AND uc.timemodified >= " . $p->add($this->ws) . "
                                         AND uc.timemodified <= " . $p->add($this->we) . "
                                         AND {$cond}", $p->values);
        $rows = [];
        $scaleids = [];
        foreach ($rs as $r) {
            $sid = (int)($r->scaleid ?: $r->fscaleid);
            $scaleids[$sid] = $sid;
            $rows[] = [(int)$r->userid, (int)$r->competencyid, format_string($r->shortname), (int)$r->grade,
                (int)$r->proficiency, $sid];
        }
        $rs->close();
        $sizes = [];
        if ($scaleids) {
            foreach ($DB->get_records_list('scale', 'id', array_values($scaleids), '', 'id, scale') as $s) {
                $sizes[(int)$s->id] = max(1, count(explode(',', $s->scale)));
            }
        }
        $green = (float)source::config('greenthreshold', 80);
        $weak = (float)source::config('weakthreshold', 60);
        $out = [];
        foreach ($rows as [$uid, $cid, $name, $grade, $prof, $sid]) {
            $pct = $grade * 100 / ($sizes[$sid] ?? max(1, $grade));
            if ($prof === 1 || $pct >= $green) {
                $zone = 'green';
            } else if ($pct >= $weak) {
                $zone = 'yellow';
            } else {
                $zone = 'red';
            }
            $out[] = [$uid, $cid, $name, $zone];
        }
        return $out;
    }

    /**
     * OJT card.
     *
     * @return array
     */
    protected function ojt_report(): array {
        global $DB;
        $rows = $this->ojt_rows();
        $percomp = [];
        $learners = [];
        foreach ($rows as [$uid, $cid, $name, $zone]) {
            if (!isset($percomp[$cid])) {
                $percomp[$cid] = ['name' => $name, 'red' => 0, 'yellow' => 0, 'green' => 0];
            }
            $percomp[$cid][$zone]++;
            $learners[$uid] = true;
        }
        $competent = 0;
        foreach ($percomp as $c) {
            $n = $c['red'] + $c['yellow'] + $c['green'];
            if ($n && $c['green'] * 2 >= $n) {
                $competent++;
            }
        }
        $total = 0;
        if (source::has_table('competency_usercomp')) {
            $total = (int)$DB->count_records_sql("SELECT COUNT(DISTINCT t.competencyid) FROM (
                                                    SELECT competencyid FROM {competency_coursecomp}
                                                    UNION
                                                    SELECT competencyid FROM {competency_usercomp} WHERE grade IS NOT NULL
                                                  ) t");
        }
        uasort($percomp, function($a, $b) {
            return $b['red'] <=> $a['red'] ?: ($b['yellow'] <=> $a['yellow']);
        });
        $chart = ['categories' => [], 'series' => [
            ['label' => get_string('zone_red', 'local_businessintelligence'), 'color' => 'destructive', 'values' => []],
            ['label' => get_string('zone_yellow', 'local_businessintelligence'), 'color' => 'warning', 'values' => []],
            ['label' => get_string('zone_green', 'local_businessintelligence'), 'color' => 'success', 'values' => []],
        ]];
        foreach (array_slice($percomp, 0, 7, true) as $c) {
            $chart['categories'][] = $c['name'];
            $chart['series'][0]['values'][] = $c['red'];
            $chart['series'][1]['values'][] = $c['yellow'];
            $chart['series'][2]['values'][] = $c['green'];
        }
        return [
            'stats' => [
                'total' => $this->num(max($total, count($percomp))),
                'learners' => $this->num(count($learners)),
                'assessed' => $this->num(count($percomp)),
                'competent' => $this->num($competent),
            ],
            'charts' => ['bars' => $chart],
        ];
    }

    // ---------------------------------------------------------------------------------------------------------------
    // AI role play (mod_airoleplay).
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * AI role play KPI + card.
     *
     * @return array
     */
    protected function roleplay_summary(): array {
        global $DB;
        $res = ['kpi' => 0, 'kpiprev' => $this->prev ? 0 : null, 'spark' => array_fill(0, count($this->buckets), 0),
            'report' => [
                'stats' => ['scenarios' => '0', 'learners' => '0', 'avgscore' => '–', 'passrate' => '–'],
                'charts' => ['bars' => ['categories' => [], 'series' => [
                    ['label' => get_string('avgscore', 'local_businessintelligence'), 'color' => 'chart1', 'values' => []],
                ]]],
            ]];
        if (!source::has_table('airoleplay_session')) {
            return $res;
        }
        // KPI: every submitted (completed) attempt counts - user A x10 + user B x3 = 13.
        $p = new params();
        $done = $p->add('completed');
        $cols = ["SUM(CASE WHEN s.timecompleted >= " . $p->add($this->ws) . " AND s.timecompleted <= " . $p->add($this->we)
            . " THEN 1 ELSE 0 END) AS cur"];
        if ($this->prev) {
            $cols[] = "SUM(CASE WHEN s.timecompleted >= " . $p->add($this->prev[0]) . " AND s.timecompleted <= "
                . $p->add($this->prev[1]) . " THEN 1 ELSE 0 END) AS prev";
        }
        foreach ($this->buckets as $i => $b) {
            $cols[] = "SUM(CASE WHEN s.timecompleted >= " . $p->add($b['start']) . " AND s.timecompleted <= "
                . $p->add($b['end']) . " THEN 1 ELSE 0 END) AS b{$i}";
        }
        $cond = $this->f->user_condition('s.userid', $p);
        $r = $DB->get_record_sql("SELECT " . implode(', ', $cols) . "
                                    FROM {airoleplay_session} s
                                   WHERE s.status = {$done} AND {$cond}", $p->values);
        $res['kpi'] = (int)$r->cur;
        $res['kpiprev'] = $this->prev ? (int)$r->prev : null;
        foreach ($this->buckets as $i => $b) {
            $res['spark'][$i] = (int)$r->{'b' . $i};
        }

        $p = new params();
        $done = $p->add('completed');
        $cond = $this->f->user_condition('s.userid', $p);
        $win = "s.timecompleted >= " . $p->add($this->ws) . " AND s.timecompleted <= " . $p->add($this->we);
        $r = $DB->get_record_sql("SELECT COUNT(DISTINCT s.userid) AS learners, AVG(s.score_overall) AS avgscore,
                                         SUM(CASE WHEN s.passed = 1 THEN 1 ELSE 0 END) AS passed, COUNT(1) AS n
                                    FROM {airoleplay_session} s
                                   WHERE s.status = {$done} AND {$win} AND {$cond}", $p->values);
        $res['report']['stats'] = [
            'scenarios' => $this->num(source::has_table('airoleplay') ? $DB->count_records('airoleplay') : 0),
            'learners' => $this->num((int)$r->learners),
            'avgscore' => $r->avgscore === null ? '–' : $this->pct(round((float)$r->avgscore), 0),
            'passrate' => $this->pct(self::ratio((int)$r->passed, (int)$r->n)),
        ];

        if (source::has_table('airoleplay')) {
            $p = new params();
            $done = $p->add('completed');
            $cond = $this->f->user_condition('s.userid', $p);
            $win = "s.timecompleted >= " . $p->add($this->ws) . " AND s.timecompleted <= " . $p->add($this->we);
            $rows = $DB->get_records_sql("SELECT a.id, a.name, AVG(s.score_overall) AS avgscore, COUNT(1) AS n
                                            FROM {airoleplay_session} s
                                            JOIN {airoleplay} a ON a.id = s.airoleplay
                                           WHERE s.status = {$done} AND {$win} AND {$cond}
                                        GROUP BY a.id, a.name
                                        ORDER BY COUNT(1) DESC", $p->values, 0, 7);
            foreach ($rows as $row) {
                $res['report']['charts']['bars']['categories'][] = format_string($row->name);
                $res['report']['charts']['bars']['series'][0]['values'][] = round((float)$row->avgscore, 1);
            }
        }
        return $res;
    }

    /**
     * Per-learner AI role play stats in the window.
     *
     * @return array
     */
    public function roleplay_per_user(): array {
        global $DB;
        if (!source::has_table('airoleplay_session')) {
            return [];
        }
        $p = new params();
        $done = $p->add('completed');
        $cond = $this->f->user_condition('s.userid', $p);
        $win = "s.timecompleted >= " . $p->add($this->ws) . " AND s.timecompleted <= " . $p->add($this->we);
        $rs = $DB->get_recordset_sql("SELECT s.userid, COUNT(1) AS attempts, SUM(COALESCE(s.score_overall, 0)) AS sumscore,
                                             SUM(CASE WHEN s.score_overall IS NULL THEN 0 ELSE 1 END) AS scored,
                                             SUM(CASE WHEN s.passed = 1 THEN 1 ELSE 0 END) AS passed,
                                             COUNT(DISTINCT s.airoleplay) AS scenarios
                                        FROM {airoleplay_session} s
                                       WHERE s.status = {$done} AND {$win} AND {$cond}
                                    GROUP BY s.userid", $p->values);
        $out = [];
        foreach ($rs as $r) {
            $out[(int)$r->userid] = ['attempts' => (int)$r->attempts, 'sumscore' => (float)$r->sumscore,
                'scored' => (int)$r->scored, 'passed' => (int)$r->passed, 'scenarios' => (int)$r->scenarios,
                'participated' => 1];
        }
        $rs->close();
        return $out;
    }

    // ---------------------------------------------------------------------------------------------------------------
    // Training outcome.
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * Per-learner outcome chain: trained -> assessed -> outcome data (2+ results) -> positive change.
     *
     * @param array|null $scur session stats for the window (computed if null)
     * @return array userid => ['trained','assessed','outcome','positive','delta']
     */
    public function outcome_per_user(?array $scur = null): array {
        global $DB;
        if ($scur === null) {
            $scur = sessions::stats($this->scoped_sessions(), $this->ws, $this->we);
        }
        // Trained: completed a course or attended a session in the window.
        $p = new params();
        $cond = $this->f->user_condition('cc.userid', $p);
        $trained = array_flip(array_map('intval', $DB->get_fieldset_sql("SELECT DISTINCT cc.userid
                                  FROM {course_completions} cc
                                 WHERE cc.timecompleted >= " . $p->add(max(1, $this->ws)) . "
                                   AND cc.timecompleted <= " . $p->add($this->we) . " AND {$cond}", $p->values)));
        foreach ($scur['attendees'] as $uid) {
            $trained[$uid] = true;
        }
        if (!$trained) {
            return [];
        }
        // Assessed: skill-gap result or AI role play attempt in the window.
        $assessed = [];
        foreach ($this->skillgap_per_user() as $uid => $unused) {
            $assessed[$uid] = true;
        }
        foreach ($this->roleplay_per_user() as $uid => $unused) {
            $assessed[$uid] = true;
        }
        $assessed = array_intersect_key($assessed, $trained);

        // Outcome: first vs latest result per competency / scenario (all history).
        $series = [];
        $ids = array_keys($assessed);
        foreach (array_chunk($ids, 1000) as $chunk) {
            if (source::has_table('local_skillgap_score')) {
                $p = new params();
                $in = $p->in($chunk);
                $pct = source::skillgap_pct('s');
                $comp = $this->f->competency > 0 ? " AND s.compid = " . $p->add($this->f->competency) : '';
                $rs = $DB->get_recordset_sql("SELECT s.id, s.userid, s.compid, {$pct} AS pct, s.timecreated
                                                FROM {local_skillgap_score} s
                                               WHERE s.userid {$in}{$comp} AND s.timecreated <= " . $p->add($this->we) . "
                                            ORDER BY s.timecreated, s.id", $p->values);
                foreach ($rs as $r) {
                    $series[(int)$r->userid]['sg' . $r->compid][] = (float)$r->pct;
                }
                $rs->close();
            }
            if (source::has_table('airoleplay_session')) {
                $p = new params();
                $in = $p->in($chunk);
                $rs = $DB->get_recordset_sql("SELECT s.id, s.userid, s.airoleplay, s.score_overall
                                                FROM {airoleplay_session} s
                                               WHERE s.userid {$in} AND s.status = " . $p->add('completed') . "
                                                 AND s.score_overall IS NOT NULL AND s.timecompleted <= " . $p->add($this->we) . "
                                            ORDER BY s.timecompleted, s.id", $p->values);
                foreach ($rs as $r) {
                    $series[(int)$r->userid]['rp' . $r->airoleplay][] = (float)$r->score_overall;
                }
                $rs->close();
            }
        }
        $out = [];
        foreach ($trained as $uid => $unused) {
            $row = ['trained' => 1, 'assessed' => isset($assessed[$uid]) ? 1 : 0, 'outcome' => 0, 'positive' => 0, 'delta' => 0.0];
            if ($row['assessed'] && !empty($series[$uid])) {
                $deltas = [];
                foreach ($series[$uid] as $vals) {
                    if (count($vals) >= 2) {
                        $deltas[] = end($vals) - reset($vals);
                    }
                }
                if ($deltas) {
                    $row['outcome'] = 1;
                    $row['delta'] = array_sum($deltas) / count($deltas);
                    $row['positive'] = $row['delta'] > 0 ? 1 : 0;
                }
            }
            $out[$uid] = $row;
        }
        return $out;
    }

    /**
     * Training outcome card.
     *
     * @param array $scur
     * @return array
     */
    protected function outcome_report(array $scur): array {
        $rows = $this->outcome_per_user($scur);
        $t = ['trained' => 0, 'assessed' => 0, 'outcome' => 0, 'positive' => 0, 'delta' => 0.0];
        foreach ($rows as $r) {
            $t['trained'] += $r['trained'];
            $t['assessed'] += $r['assessed'];
            $t['outcome'] += $r['outcome'];
            $t['positive'] += $r['positive'];
            $t['delta'] += $r['outcome'] ? $r['delta'] : 0;
        }
        $avg = $t['outcome'] ? round($t['delta'] / $t['outcome'], 1) : null;
        return [
            'stats' => [
                'trained' => $this->num($t['trained']),
                'withdata' => $this->num($t['outcome']),
                'avgchange' => $avg === null ? '–' : (($avg > 0 ? '+' : '') . $this->pct($avg)),
                'positive' => $this->pct(self::ratio($t['positive'], $t['outcome'])),
            ],
            'charts' => [
                'funnel' => ['stages' => [
                    ['label' => get_string('funnel_trained', 'local_businessintelligence'), 'value' => $t['trained']],
                    ['label' => get_string('funnel_assessed', 'local_businessintelligence'), 'value' => $t['assessed']],
                    ['label' => get_string('funnel_outcome', 'local_businessintelligence'), 'value' => $t['outcome']],
                    ['label' => get_string('funnel_positive', 'local_businessintelligence'), 'value' => $t['positive']],
                ]],
            ],
        ];
    }

    /**
     * Window accessor.
     *
     * @return int[]
     */
    public function window(): array {
        return [$this->ws, $this->we];
    }
}
