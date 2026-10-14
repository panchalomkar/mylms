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
 * Learning Progress Analytics page: KPIs, charts, Department -> Designation -> User drill-down,
 * course popups and the per-user detail report.
 *
 * Scope: course assignments (enrolments, teachers excluded) made in the selected date range for the filtered
 * learner population - the same definition as the dashboard card.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learning extends metrics {
    /** Empty profile value marker. */
    const NONE = '__none__';

    /** @var array|null memo of assignment rows */
    protected $rowcache = null;

    /**
     * One row per (learner, course) assignment in the window.
     *
     * @return array list of [userid, courseid, due, timecompleted, started]
     */
    public function rows(): array {
        global $DB;
        if ($this->rowcache !== null) {
            return $this->rowcache;
        }
        $p = new params();
        $base = $this->enrol_base($p);
        $due = self::due();
        $sql = "SELECT x.userid, x.courseid, {$due} AS due,
                       COALESCE(cc.timecompleted, 0) AS timecompleted,
                       CASE WHEN la.id IS NOT NULL OR cc.timestarted > 0 THEN 1 ELSE 0 END AS started
                  FROM ({$base}) x
             LEFT JOIN {course_completions} cc ON cc.userid = x.userid AND cc.course = x.courseid
             LEFT JOIN {user_lastaccess} la ON la.userid = x.userid AND la.courseid = x.courseid
                 WHERE x.timeassigned >= " . $p->add($this->ws) . " AND x.timeassigned <= " . $p->add($this->we);
        $rows = [];
        $rs = $DB->get_recordset_sql($sql, $p->values);
        foreach ($rs as $r) {
            $rows[] = [(int)$r->userid, (int)$r->courseid, (int)$r->due, (int)$r->timecompleted, (int)$r->started];
        }
        $rs->close();
        $this->rowcache = $rows;
        return $rows;
    }

    /**
     * Status of an assignment row at the window end.
     *
     * @param array $row
     * @return string completed|overdue|inprogress|notstarted
     */
    public function status(array $row): string {
        [, , $due, $done, $started] = $row;
        if ($done > 0 && $done <= $this->we) {
            return 'completed';
        }
        if ($due > 0 && $due < $this->we) {
            return 'overdue';
        }
        return $started ? 'inprogress' : 'notstarted';
    }

    /**
     * Header KPIs and the four charts.
     *
     * @return array
     */
    public function overview(): array {
        global $DB;
        $labels = array_column($this->buckets, 'label');
        $cur = $this->enrol_totals($this->enrol_per_user($this->ws, $this->we));
        $prev = $this->prev ? $this->enrol_totals($this->enrol_per_user($this->prev[0], $this->prev[1])) : null;
        $learners = $this->learner_counts();
        $eb = $this->enrol_buckets();

        // Assignments and completions per bucket.
        $p = new params();
        $base = $this->enrol_base($p);
        $cols = [];
        foreach ($this->buckets as $i => $b) {
            $cols[] = "SUM(CASE WHEN x.timeassigned >= " . $p->add($b['start']) . " AND x.timeassigned <= " . $p->add($b['end'])
                . " THEN 1 ELSE 0 END) AS a{$i}";
            $cols[] = "SUM(CASE WHEN cc.timecompleted >= " . $p->add($b['start']) . " AND cc.timecompleted <= "
                . $p->add($b['end']) . " THEN 1 ELSE 0 END) AS c{$i}";
        }
        $r = $DB->get_record_sql("SELECT " . implode(', ', $cols) . "
                                    FROM ({$base}) x
                               LEFT JOIN {course_completions} cc ON cc.userid = x.userid AND cc.course = x.courseid",
            $p->values);
        $assigned = $completions = [];
        foreach ($this->buckets as $i => $b) {
            $assigned[] = (int)$r->{'a' . $i};
            $completions[] = (int)$r->{'c' . $i};
        }

        $overduecourses = 0;
        foreach ($this->rows() as $row) {
            if ($this->status($row) === 'overdue') {
                $overduecourses++;
            }
        }
        $pending = max(0, $cur['assigned'] - $cur['completed'] - $overduecourses);
        $s = function($k) {
            return get_string($k, 'local_businessintelligence');
        };

        return [
            'kpis' => [
                'totalusers' => $this->kpi($learners['total'], $learners['atstart'], $learners['spark']),
                'assigned' => $this->kpi($cur['assigned'], $prev ? $prev['assigned'] : null, $assigned),
                'completed' => $this->kpi($cur['completed'], $prev ? $prev['completed'] : null, $completions),
                'completionpct' => $this->kpi($cur['rate'], $prev ? $prev['rate'] : null, $eb['rate'], 'pct'),
                'overdueusers' => $this->kpi($cur['overduelearners'], $prev ? $prev['overduelearners'] : null, $eb['overdue']),
            ],
            'charts' => [
                'status' => [
                    'items' => [
                        ['label' => $s('lp_status_completed'), 'value' => $cur['completed'], 'color' => 'success'],
                        ['label' => $s('lp_status_overdue'), 'value' => $overduecourses, 'color' => 'destructive'],
                        ['label' => $s('lp_status_pending'), 'value' => $pending, 'color' => 'accent'],
                    ],
                    'center' => $this->pct($cur['rate']),
                    'centerlabel' => $s('lp_completed_upper'),
                ],
                'funnel' => ['stages' => [
                    ['label' => $s('funnel_assigned'), 'value' => $cur['assigned']],
                    ['label' => $s('funnel_started'), 'value' => $cur['started']],
                    ['label' => $s('funnel_completed'), 'value' => $cur['completed']],
                ]],
                'completiontrend' => ['labels' => $labels, 'values' => $completions],
                'overduetrend' => ['labels' => $labels, 'values' => $eb['overdue']],
            ],
            'refreshed' => userdate(time(), get_string('strftimedatetimeshort', 'core_langconfig')),
        ];
    }

    /**
     * Members of the requested scope.
     *
     * @param string $dept department key ('' = all, NONE = empty)
     * @param string $desig designation key ('' = all)
     * @return array userid => population record
     */
    protected function members(string $dept, string $desig): array {
        $out = [];
        foreach ($this->population() as $uid => $u) {
            $d = $u->department !== '' ? $u->department : self::NONE;
            $g = $u->designation !== '' ? $u->designation : self::NONE;
            if ($dept !== '' && $d !== $dept) {
                continue;
            }
            if ($desig !== '' && $g !== $desig) {
                continue;
            }
            $out[$uid] = $u;
        }
        return $out;
    }

    /**
     * Drill-down rows for a level.
     *
     * @param string $level org|department|designation
     * @param string $dept
     * @param string $desig
     * @return array
     */
    public function level(string $level, string $dept, string $desig): array {
        $members = $this->members($level === 'org' ? '' : $dept, $level === 'designation' ? $desig : '');
        $byuser = [];
        foreach ($this->rows() as $row) {
            if (isset($members[$row[0]])) {
                $byuser[$row[0]][] = $row;
            }
        }
        $none = get_string('notset', 'local_businessintelligence');

        if ($level === 'designation') {
            $managers = profile::managers();
            $mvalues = profile::values_for('manager', array_keys($members));
            $out = [];
            foreach ($members as $uid => $u) {
                $rows = $byuser[$uid] ?? [];
                $c = $o = 0;
                foreach ($rows as $row) {
                    $st = $this->status($row);
                    $c += $st === 'completed' ? 1 : 0;
                    $o += $st === 'overdue' ? 1 : 0;
                }
                $memail = profile::normalise_manager($mvalues[$uid] ?? '');
                $out[] = [
                    'key' => (string)$uid,
                    'name' => $u->fullname,
                    'email' => $u->email,
                    'branch' => $u->branch,
                    'manager' => $memail === '' ? '' : ($managers[$memail] ?? $memail),
                    'enrolled' => count($rows),
                    'completed' => $c,
                    'overdue' => $o,
                    'pct' => count($rows) ? round($c * 100 / count($rows), 1) : 0,
                ];
            }
            usort($out, function($a, $b) {
                return ($b['enrolled'] <=> $a['enrolled']) ?: strcmp($a['name'], $b['name']);
            });
            return $out;
        }

        $groups = [];
        foreach ($members as $uid => $u) {
            $k = $level === 'org' ? ($u->department !== '' ? $u->department : self::NONE)
                : ($u->designation !== '' ? $u->designation : self::NONE);
            if (!isset($groups[$k])) {
                $groups[$k] = ['users' => 0, 'courses' => [], 'enrolments' => 0, 'completed' => 0, 'overdueusers' => 0];
            }
            $g = &$groups[$k];
            $g['users']++;
            $hasoverdue = false;
            foreach ($byuser[$uid] ?? [] as $row) {
                $g['courses'][$row[1]] = true;
                $g['enrolments']++;
                $st = $this->status($row);
                if ($st === 'completed') {
                    $g['completed']++;
                } else if ($st === 'overdue') {
                    $hasoverdue = true;
                }
            }
            if ($hasoverdue) {
                $g['overdueusers']++;
            }
            unset($g);
        }
        $out = [];
        foreach ($groups as $k => $g) {
            $out[] = [
                'key' => (string)$k,
                'name' => $k === self::NONE ? $none : (string)$k,
                'users' => $g['users'],
                'courses' => count($g['courses']),
                'enrolments' => $g['enrolments'],
                'completed' => $g['completed'],
                'pct' => $g['enrolments'] ? round($g['completed'] * 100 / $g['enrolments'], 1) : 0,
                'overdueusers' => $g['overdueusers'],
            ];
        }
        usort($out, function($a, $b) {
            return ($b['users'] <=> $a['users']) ?: strcmp($a['name'], $b['name']);
        });
        return $out;
    }

    /**
     * Courses assigned to a department / designation (for the course-count popup).
     *
     * @param string $dept
     * @param string $desig
     * @return array list of ['name', 'assigned', 'completed']
     */
    public function courses(string $dept, string $desig): array {
        global $DB;
        $members = $this->members($dept, $desig);
        $per = [];
        foreach ($this->rows() as $row) {
            if (!isset($members[$row[0]])) {
                continue;
            }
            if (!isset($per[$row[1]])) {
                $per[$row[1]] = ['assigned' => 0, 'completed' => 0];
            }
            $per[$row[1]]['assigned']++;
            if ($this->status($row) === 'completed') {
                $per[$row[1]]['completed']++;
            }
        }
        if (!$per) {
            return [];
        }
        $names = $DB->get_records_list('course', 'id', array_keys($per), '', 'id, fullname');
        $out = [];
        foreach ($per as $cid => $c) {
            $out[] = [
                'name' => isset($names[$cid]) ? format_string($names[$cid]->fullname) : '#' . $cid,
                'assigned' => $c['assigned'],
                'completed' => $c['completed'],
            ];
        }
        usort($out, function($a, $b) {
            return ($b['assigned'] <=> $a['assigned']) ?: strcmp($a['name'], $b['name']);
        });
        return $out;
    }

    /**
     * Detail report for one learner.
     *
     * @param int $userid
     * @return array
     */
    public function user(int $userid): array {
        global $DB;
        $pop = $this->population();
        if (!isset($pop[$userid])) {
            throw new \moodle_exception('invaliduser');
        }
        $u = $pop[$userid];
        $rows = array_values(array_filter($this->rows(), function($r) use ($userid) {
            return $r[0] === $userid;
        }));
        $names = $rows ? $DB->get_records_list('course', 'id', array_column($rows, 1), '', 'id, fullname') : [];
        $courses = [];
        $c = $o = 0;
        foreach ($rows as $row) {
            $st = $this->status($row);
            $c += $st === 'completed' ? 1 : 0;
            $o += $st === 'overdue' ? 1 : 0;
            $courses[] = [
                'name' => isset($names[$row[1]]) ? format_string($names[$row[1]]->fullname) : '#' . $row[1],
                'completiondate' => $st === 'completed' ? userdate($row[3], '%Y-%m-%d', 99, false) : '',
                'duedate' => $row[2] > 0 ? userdate($row[2], '%Y-%m-%d', 99, false) : '',
                'status' => $st,
                'statuslabel' => get_string('lp_st_' . $st, 'local_businessintelligence'),
                'sort' => $row[2] ?: PHP_INT_MAX,
            ];
        }
        $memail = profile::normalise_manager(profile::values_for('manager', [$userid])[$userid] ?? '');
        $manager = '';
        if ($memail !== '') {
            $managers = profile::managers();
            $manager = $managers[$memail] ?? $memail;
        }
        $initials = '';
        foreach (preg_split('/\s+/', trim($u->fullname)) as $part) {
            if ($part !== '' && \core_text::strlen($initials) < 2) {
                $initials .= \core_text::strtoupper(\core_text::substr($part, 0, 1));
            }
        }
        return [
            'name' => $u->fullname,
            'initials' => $initials,
            'email' => $u->email,
            'meta' => implode(' · ', array_filter([$u->designation, $u->department, $u->branch])),
            'manager' => $manager,
            'enrolled' => count($rows),
            'completed' => $c,
            'overdue' => $o,
            'pct' => $this->pct(count($rows) ? round($c * 100 / count($rows), 1) : 0),
            'courses' => $courses,
        ];
    }
}
