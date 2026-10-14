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
 * Learning Path Analytics page: KPIs, funnel, per-path status, Department -> Designation -> User drill-down,
 * learning-path popups and the per-user detail report.
 *
 * One record per (learning path, learner) assignment - direct (learningpath_users) or via cohort
 * (learningpath_cohorts). Status at the end of the selected period:
 *  - Completed: every required course (all active courses when none is flagged required) is completed.
 *  - In Progress: not completed but at least one course of the path was accessed / started / completed.
 *  - Not Started: anything else.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lppage extends metrics {
    /** Empty profile value marker. */
    const NONE = '__none__';

    /** @var array|null memo */
    protected $data = null;

    /**
     * Load paths, assignments and progress (memoised).
     *
     * @return array ['paths' => [id => name], 'courses' => [lpid => [cid]], 'pairs' => [...]]
     *         each pair: ['lp', 'uid', 'etime', 'stime', 'ctime', 'done' => [cid => time]]
     */
    public function data(): array {
        global $DB;
        if ($this->data !== null) {
            return $this->data;
        }
        $this->data = ['paths' => [], 'courses' => [], 'pairs' => []];
        if (!source::has_table('learningpaths') || !source::has_table('learningpath_courses')) {
            return $this->data;
        }
        $deleted = source::has_field('learningpaths', 'deleted') ? 'WHERE deleted = 0' : '';
        $paths = $DB->get_records_sql_menu("SELECT id, name FROM {learningpaths} {$deleted} ORDER BY name");
        if (!$paths) {
            return $this->data;
        }
        $pop = $this->population();

        $active = source::has_field('learningpath_courses', 'course_active') ? 'AND lc.course_active = 1' : '';
        $req = source::has_field('learningpath_courses', 'required') ? 'lc.required' : '0';
        $p = new params();
        $in = $p->in(array_keys($paths));
        $courses = $required = [];
        $rs = $DB->get_recordset_sql("SELECT lc.id, lc.learningpathid, lc.courseid, {$req} AS required
                                        FROM {learningpath_courses} lc
                                        JOIN {course} c ON c.id = lc.courseid
                                       WHERE lc.learningpathid {$in} {$active}", $p->values);
        foreach ($rs as $r) {
            $courses[(int)$r->learningpathid][(int)$r->courseid] = (int)$r->courseid;
            if ((int)$r->required) {
                $required[(int)$r->learningpathid][(int)$r->courseid] = (int)$r->courseid;
            }
        }
        $rs->close();

        // Members (direct + cohort) with the time they were added when known.
        $members = [];
        $add = function($lp, $uid, $t) use (&$members, $pop) {
            if (!isset($pop[$uid])) {
                return;
            }
            if (!isset($members[$lp][$uid]) || ($t > 0 && ($members[$lp][$uid] === 0 || $t < $members[$lp][$uid]))) {
                $members[$lp][$uid] = $t;
            }
        };
        if (source::has_table('learningpath_users')) {
            $tc = source::has_field('learningpath_users', 'timecreated') ? 'lu.timecreated' : '0';
            $p = new params();
            $in = $p->in(array_keys($paths));
            $rs = $DB->get_recordset_sql("SELECT lu.id, lu.learningpathid, lu.userid, {$tc} AS t
                                            FROM {learningpath_users} lu
                                           WHERE lu.learningpathid {$in}", $p->values);
            foreach ($rs as $r) {
                $add((int)$r->learningpathid, (int)$r->userid, (int)$r->t);
            }
            $rs->close();
        }
        if (source::has_table('learningpath_cohorts')) {
            $p = new params();
            $in = $p->in(array_keys($paths));
            $rs = $DB->get_recordset_sql("SELECT cm.id, lc.learningpathid, cm.userid, cm.timeadded AS t
                                            FROM {learningpath_cohorts} lc
                                            JOIN {cohort_members} cm ON cm.cohortid = lc.cohortid
                                           WHERE lc.learningpathid {$in}", $p->values);
            foreach ($rs as $r) {
                $add((int)$r->learningpathid, (int)$r->userid, (int)$r->t);
            }
            $rs->close();
        }

        // Completions and first activity on path courses.
        $allcourses = [];
        foreach ($courses as $list) {
            $allcourses += $list;
        }
        $done = $start = [];
        if ($allcourses && $members) {
            foreach (array_chunk(array_values($allcourses), 500) as $chunk) {
                $p = new params();
                $in = $p->in($chunk);
                $rs = $DB->get_recordset_sql("SELECT cc.id, cc.userid, cc.course, cc.timecompleted, cc.timestarted
                                                FROM {course_completions} cc
                                               WHERE cc.course {$in}", $p->values);
                foreach ($rs as $r) {
                    $uid = (int)$r->userid;
                    if (!isset($pop[$uid])) {
                        continue;
                    }
                    $cid = (int)$r->course;
                    if ((int)$r->timecompleted > 0) {
                        $done[$uid][$cid] = (int)$r->timecompleted;
                    }
                    foreach ([(int)$r->timestarted, (int)$r->timecompleted] as $t) {
                        if ($t > 0 && (!isset($start[$uid][$cid]) || $t < $start[$uid][$cid])) {
                            $start[$uid][$cid] = $t;
                        }
                    }
                }
                $rs->close();
                $p = new params();
                $in = $p->in($chunk);
                $rs = $DB->get_recordset_sql("SELECT la.id, la.userid, la.courseid, la.timeaccess
                                                FROM {user_lastaccess} la
                                               WHERE la.courseid {$in}", $p->values);
                foreach ($rs as $r) {
                    $uid = (int)$r->userid;
                    $cid = (int)$r->courseid;
                    $t = (int)$r->timeaccess;
                    if (isset($pop[$uid]) && $t > 0 && (!isset($start[$uid][$cid]) || $t < $start[$uid][$cid])) {
                        $start[$uid][$cid] = $t;
                    }
                }
                $rs->close();
            }
        }

        $pairs = [];
        foreach ($members as $lp => $users) {
            $list = $courses[$lp] ?? [];
            $req = !empty($required[$lp]) ? $required[$lp] : $list;
            foreach ($users as $uid => $etime) {
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
                $stime = 0;
                $mydone = [];
                foreach ($list as $cid) {
                    if (isset($start[$uid][$cid]) && ($stime === 0 || $start[$uid][$cid] < $stime)) {
                        $stime = $start[$uid][$cid];
                    }
                    if (isset($done[$uid][$cid])) {
                        $mydone[$cid] = $done[$uid][$cid];
                    }
                }
                $pairs[] = ['lp' => $lp, 'uid' => $uid, 'etime' => $etime, 'stime' => $stime, 'ctime' => $ctime,
                    'done' => $mydone];
            }
        }
        $this->data = ['paths' => $paths, 'courses' => $courses, 'pairs' => $pairs];
        return $this->data;
    }

    /**
     * Status of an assignment at a time.
     *
     * @param array $pair
     * @param int $at
     * @return string completed|inprogress|notstarted|none (not yet assigned)
     */
    public function status(array $pair, int $at): string {
        if ($pair['etime'] > $at) {
            return 'none';
        }
        if ($pair['ctime'] !== null && $pair['ctime'] <= $at) {
            return 'completed';
        }
        if ($pair['stime'] > 0 && $pair['stime'] <= $at) {
            return 'inprogress';
        }
        return 'notstarted';
    }

    /**
     * Counts at a time.
     *
     * @param array $pairs
     * @param int $at
     * @return array enrolled, completed, inprogress, notstarted
     */
    protected function counts(array $pairs, int $at): array {
        $c = ['enrolled' => 0, 'completed' => 0, 'inprogress' => 0, 'notstarted' => 0];
        foreach ($pairs as $pair) {
            $st = $this->status($pair, $at);
            if ($st === 'none') {
                continue;
            }
            $c['enrolled']++;
            $c[$st]++;
        }
        return $c;
    }

    /**
     * Header KPIs and the two charts.
     *
     * @return array
     */
    public function overview(): array {
        $d = $this->data();
        $cur = $this->counts($d['pairs'], $this->we);
        $prev = $this->ws > 0 ? $this->counts($d['pairs'], $this->ws) : null;
        $spark = ['enrolled' => [], 'completed' => [], 'inprogress' => [], 'notstarted' => []];
        foreach ($this->buckets as $b) {
            $c = $this->counts($d['pairs'], $b['end']);
            foreach ($spark as $k => $unused) {
                $spark[$k][] = $c[$k];
            }
        }
        $kpis = [];
        foreach (['enrolled', 'completed', 'inprogress', 'notstarted'] as $k) {
            $kpis[$k] = $this->kpi($cur[$k], $prev ? $prev[$k] : null, $spark[$k]);
        }

        // Per path (top 10 by assignments).
        $per = [];
        foreach ($d['pairs'] as $pair) {
            $st = $this->status($pair, $this->we);
            if ($st === 'none') {
                continue;
            }
            if (!isset($per[$pair['lp']])) {
                $per[$pair['lp']] = ['completed' => 0, 'inprogress' => 0, 'notstarted' => 0, 'total' => 0];
            }
            $per[$pair['lp']][$st]++;
            $per[$pair['lp']]['total']++;
        }
        uasort($per, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        $per = array_slice($per, 0, 10, true);
        $s = function($k) {
            return get_string($k, 'local_businessintelligence');
        };
        $bypath = ['categories' => [], 'series' => [
            ['label' => $s('completed'), 'color' => 'success', 'values' => []],
            ['label' => $s('inprogress'), 'color' => 'accent', 'values' => []],
            ['label' => $s('notstarted'), 'color' => 'muted', 'values' => []],
        ]];
        foreach ($per as $lp => $c) {
            $bypath['categories'][] = format_string($d['paths'][$lp]);
            $bypath['series'][0]['values'][] = $c['completed'];
            $bypath['series'][1]['values'][] = $c['inprogress'];
            $bypath['series'][2]['values'][] = $c['notstarted'];
        }

        return [
            'kpis' => $kpis,
            'charts' => [
                'funnel' => ['stages' => [
                    ['label' => $s('lpa_funnel_enrolled'), 'value' => $cur['enrolled']],
                    ['label' => $s('lpa_funnel_started'), 'value' => $cur['completed'] + $cur['inprogress']],
                    ['label' => $s('lpa_funnel_completed'), 'value' => $cur['completed']],
                ]],
                'bypath' => $bypath,
            ],
            'refreshed' => userdate(time(), get_string('strftimedatetimeshort', 'core_langconfig')),
        ];
    }

    /**
     * Members of the requested scope.
     *
     * @param string $dept
     * @param string $desig
     * @return array userid => population record
     */
    protected function members(string $dept, string $desig): array {
        $out = [];
        foreach ($this->population() as $uid => $u) {
            $d = $u->department !== '' ? $u->department : self::NONE;
            $g = $u->designation !== '' ? $u->designation : self::NONE;
            if (($dept !== '' && $d !== $dept) || ($desig !== '' && $g !== $desig)) {
                continue;
            }
            $out[$uid] = $u;
        }
        return $out;
    }

    /**
     * Assignments of the given members, grouped per user.
     *
     * @param array $members
     * @return array uid => [pair]
     */
    protected function byuser(array $members): array {
        $out = [];
        foreach ($this->data()['pairs'] as $pair) {
            if (isset($members[$pair['uid']]) && $this->status($pair, $this->we) !== 'none') {
                $out[$pair['uid']][] = $pair;
            }
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
        $byuser = $this->byuser($members);
        $none = get_string('notset', 'local_businessintelligence');

        if ($level === 'designation') {
            $managers = profile::managers();
            $mvalues = profile::values_for('manager', array_keys($members));
            $out = [];
            foreach ($members as $uid => $u) {
                $c = ['completed' => 0, 'inprogress' => 0, 'notstarted' => 0];
                foreach ($byuser[$uid] ?? [] as $pair) {
                    $c[$this->status($pair, $this->we)]++;
                }
                $n = count($byuser[$uid] ?? []);
                $memail = profile::normalise_manager($mvalues[$uid] ?? '');
                $out[] = [
                    'key' => (string)$uid,
                    'name' => $u->fullname,
                    'email' => $u->email,
                    'branch' => $u->branch,
                    'manager' => $memail === '' ? '' : ($managers[$memail] ?? $memail),
                    'enrolled' => $n,
                    'completed' => $c['completed'],
                    'inprogress' => $c['inprogress'],
                    'notstarted' => $c['notstarted'],
                    'pct' => $n ? round($c['completed'] * 100 / $n, 1) : 0,
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
                $groups[$k] = ['users' => 0, 'paths' => [], 'enrolled' => 0, 'completed' => 0, 'inprogress' => 0,
                    'notstarted' => 0];
            }
            $groups[$k]['users']++;
            foreach ($byuser[$uid] ?? [] as $pair) {
                $groups[$k]['paths'][$pair['lp']] = true;
                $groups[$k]['enrolled']++;
                $groups[$k][$this->status($pair, $this->we)]++;
            }
        }
        $out = [];
        foreach ($groups as $k => $g) {
            $out[] = [
                'key' => (string)$k,
                'name' => $k === self::NONE ? $none : (string)$k,
                'users' => $g['users'],
                'paths' => count($g['paths']),
                'enrolled' => $g['enrolled'],
                'completed' => $g['completed'],
                'inprogress' => $g['inprogress'],
                'notstarted' => $g['notstarted'],
                'pct' => $g['enrolled'] ? round($g['completed'] * 100 / $g['enrolled'], 1) : 0,
            ];
        }
        usort($out, function($a, $b) {
            return ($b['users'] <=> $a['users']) ?: strcmp($a['name'], $b['name']);
        });
        return $out;
    }

    /**
     * Learning paths of a department / designation (learning-path count popup).
     *
     * @param string $dept
     * @param string $desig
     * @return array list of ['name', 'enrolled', 'inprogress', 'completed', 'notstarted']
     */
    public function paths(string $dept, string $desig): array {
        $d = $this->data();
        $per = [];
        foreach ($this->byuser($this->members($dept, $desig)) as $pairs) {
            foreach ($pairs as $pair) {
                if (!isset($per[$pair['lp']])) {
                    $per[$pair['lp']] = ['enrolled' => 0, 'completed' => 0, 'inprogress' => 0, 'notstarted' => 0];
                }
                $per[$pair['lp']]['enrolled']++;
                $per[$pair['lp']][$this->status($pair, $this->we)]++;
            }
        }
        $out = [];
        foreach ($per as $lp => $c) {
            $out[] = ['name' => format_string($d['paths'][$lp])] + $c;
        }
        usort($out, function($a, $b) {
            return ($b['enrolled'] <=> $a['enrolled']) ?: strcmp($a['name'], $b['name']);
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
        $pop = $this->population();
        if (!isset($pop[$userid])) {
            throw new \moodle_exception('invaliduser');
        }
        $u = $pop[$userid];
        $d = $this->data();
        $pairs = $this->byuser([$userid => $u])[$userid] ?? [];
        $c = ['completed' => 0, 'inprogress' => 0, 'notstarted' => 0];
        $paths = [];
        foreach ($pairs as $pair) {
            $st = $this->status($pair, $this->we);
            $c[$st]++;
            $total = count($d['courses'][$pair['lp']] ?? []);
            $doneat = count(array_filter($pair['done'], function($t) {
                return $t <= $this->we;
            }));
            $progress = $st === 'completed' ? 100 : ($total ? round(min($doneat, $total) * 100 / $total) : 0);
            $paths[] = [
                'name' => format_string($d['paths'][$pair['lp']]),
                'courses' => $total,
                'progress' => (int)$progress,
                'status' => $st,
                'statuslabel' => get_string($st, 'local_businessintelligence'),
            ];
        }
        usort($paths, function($a, $b) {
            return ($b['progress'] <=> $a['progress']) ?: strcmp($a['name'], $b['name']);
        });
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
            'enrolled' => count($pairs),
            'completed' => $c['completed'],
            'inprogress' => $c['inprogress'],
            'notstarted' => $c['notstarted'],
            'paths' => $paths,
        ];
    }
}
