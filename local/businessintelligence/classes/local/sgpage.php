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
 * Skill Gap Analytics page: KPIs, top weak competencies, Department -> Designation -> User drill-down,
 * competency / weak-competency popups and the per-user skill gap report. Source: local_skillgap.
 *
 * A "competency" is a competency of local_skillgap_comp, or one of its sub-competencies (local_skillgap_subcomp)
 * when scores are recorded per sub-competency - shown as "Competency — Sub-competency", like the dashboard card.
 *
 * Per learner and competency the latest score of the selected period counts. Score % = score / maxscore.
 *  - Weak competency (group): average score % below the "Weak / red threshold" setting (same rule as the
 *    dashboard card). Severity High when more than half of the assessed learners are in the weak zone.
 *  - Weak zone (learner): status needs_improvement. Users requiring improvement: learners with at least one.
 *  - Competencies of a group: competencies mapped to the members' department / designation (deptid / desigid 0 = all)
 *    plus any competency assessed in the period.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sgpage extends metrics {
    /** Empty profile value marker. */
    const NONE = '__none__';

    /** @var array|null memo */
    protected $data = null;

    /**
     * Load competencies and scores (memoised).
     *
     * @return array ['items' => [key => [name, type, compid]], 'comps' => [...], 'scores' => [...]]
     *         each score: [uid, key, pct, status, time, attemptid]
     */
    public function data(): array {
        global $DB;
        if ($this->data !== null) {
            return $this->data;
        }
        $this->data = ['items' => [], 'comps' => [], 'subs' => [], 'scores' => []];
        if (!source::has_table('local_skillgap_score') || !source::has_table('local_skillgap_comp')) {
            return $this->data;
        }
        $pop = $this->population();
        $comps = $DB->get_records('local_skillgap_comp', null, 'name ASC', 'id, name, deptid, desigid, timecreated');
        $subs = [];
        if (source::has_table('local_skillgap_subcomp')) {
            $parent = source::has_field('local_skillgap_subcomp', 'parentsubid') ? 'WHERE parentsubid IS NULL OR parentsubid = 0'
                : '';
            foreach ($DB->get_records_sql("SELECT id, compid, name FROM {local_skillgap_subcomp} {$parent} ORDER BY name")
                    as $s) {
                $subs[(int)$s->compid][(int)$s->id] = format_string($s->name);
            }
        }
        $hassub = source::has_field('local_skillgap_score', 'subcompid');
        $items = [];
        $additem = function(int $cid, int $sid) use (&$items, $comps, $subs) {
            $key = $cid . ':' . $sid;
            if (isset($items[$key])) {
                return $key;
            }
            $cname = isset($comps[$cid]) ? format_string($comps[$cid]->name) : '#' . $cid;
            $name = $cname;
            if ($sid) {
                $name .= ' — ' . ($subs[$cid][$sid] ?? '#' . $sid);
            }
            $items[$key] = ['name' => $name, 'type' => $cname, 'compid' => $cid];
            return $key;
        };

        $p = new params();
        $where = "s.timecreated <= " . $p->add($this->we);
        if ($this->f->competency > 0) {
            $where .= " AND s.compid = " . $p->add($this->f->competency);
        }
        $subsel = $hassub ? 'COALESCE(s.subcompid, 0)' : '0';
        $pct = source::skillgap_pct('s');
        $rs = $DB->get_recordset_sql("SELECT s.id, s.userid, s.compid, {$subsel} AS subcompid, {$pct} AS pct, s.status,
                                             s.timecreated, s.attemptid
                                        FROM {local_skillgap_score} s
                                       WHERE {$where}
                                    ORDER BY s.timecreated, s.id", $p->values);
        $scores = [];
        foreach ($rs as $r) {
            $uid = (int)$r->userid;
            if (!isset($pop[$uid])) {
                continue;
            }
            $key = $additem((int)$r->compid, (int)$r->subcompid);
            $scores[] = [$uid, $key, min(100.0, max(0.0, (float)$r->pct)), (string)$r->status, (int)$r->timecreated,
                (int)$r->attemptid];
        }
        $rs->close();
        $this->data = ['items' => $items, 'comps' => $comps, 'subs' => $subs, 'scores' => $scores];
        return $this->data;
    }

    /**
     * Latest score per (learner, competency) within a time range.
     *
     * @param int $from
     * @param int $to
     * @param array|null $members userid => x (null = everybody)
     * @return array uid => key => ['pct', 'status', 'time', 'n' (scores in range), 'attempts' => [id => true]]
     */
    protected function latest(int $from, int $to, ?array $members = null): array {
        $out = [];
        foreach ($this->data()['scores'] as [$uid, $key, $pct, $status, $time, $attempt]) {
            if ($time < $from || $time > $to || ($members !== null && !isset($members[$uid]))) {
                continue;
            }
            $n = isset($out[$uid][$key]) ? $out[$uid][$key]['n'] + 1 : 1;
            $out[$uid][$key] = ['pct' => $pct, 'status' => $status, 'time' => $time, 'n' => $n];
        }
        return $out;
    }

    /**
     * Average latest skill-gap score % per learner in the period (for the learning score).
     *
     * @param array $uids
     * @return array uid => pct
     */
    public function userpct(array $uids): array {
        $out = [];
        foreach ($this->latest($this->ws, $this->we, array_flip($uids)) as $uid => $items) {
            if ($items) {
                $out[$uid] = round(array_sum(array_column($items, 'pct')) / count($items), 1);
            }
        }
        return $out;
    }

    /**
     * Distinct attempts within a range for members.
     *
     * @param int $from
     * @param int $to
     * @param array $members
     * @return int
     */
    protected function attempts(int $from, int $to, array $members): int {
        $seen = [];
        foreach ($this->data()['scores'] as [$uid, , , , $time, $attempt]) {
            if ($time >= $from && $time <= $to && isset($members[$uid])) {
                $seen[$uid . ':' . $attempt] = true;
            }
        }
        return count($seen);
    }

    /**
     * Per-competency aggregates of a "latest" set.
     *
     * @param array $latest
     * @return array key => ['sum', 'users', 'weakusers', 'n', 'avg']
     */
    protected function per_item(array $latest): array {
        $per = [];
        foreach ($latest as $uid => $items) {
            foreach ($items as $key => $s) {
                if (!isset($per[$key])) {
                    $per[$key] = ['sum' => 0.0, 'users' => 0, 'weakusers' => 0, 'n' => 0];
                }
                $per[$key]['sum'] += $s['pct'];
                $per[$key]['users']++;
                $per[$key]['n'] += $s['n'];
                if ($s['status'] === self::SG_WEAK) {
                    $per[$key]['weakusers']++;
                }
            }
        }
        foreach ($per as $k => $v) {
            $per[$k]['avg'] = $v['users'] ? $v['sum'] / $v['users'] : 0;
        }
        return $per;
    }

    /**
     * Weak-competency threshold (%).
     *
     * @return float
     */
    protected function threshold(): float {
        return (float)source::config('weakthreshold', 60);
    }

    /**
     * Headline numbers of a range.
     *
     * @param int $from
     * @param int $to
     * @return array users, weak, needimprovement
     */
    protected function headline(int $from, int $to): array {
        $latest = $this->latest($from, $to);
        $weak = 0;
        foreach ($this->per_item($latest) as $v) {
            if ($v['avg'] < $this->threshold()) {
                $weak++;
            }
        }
        $ni = 0;
        foreach ($latest as $items) {
            foreach ($items as $s) {
                if ($s['status'] === self::SG_WEAK) {
                    $ni++;
                    break;
                }
            }
        }
        return ['users' => count($latest), 'weak' => $weak, 'needimprovement' => $ni];
    }

    /**
     * Header KPIs and the top weak competencies chart.
     *
     * @return array
     */
    public function overview(): array {
        $d = $this->data();
        $comps = $d['comps'];
        if ($this->f->competency > 0) {
            $comps = array_intersect_key($comps, [$this->f->competency => true]);
        }
        $total = function(int $at) use ($comps) {
            return count(array_filter($comps, function($c) use ($at) {
                return (int)$c->timecreated <= $at;
            }));
        };
        $cur = $this->headline($this->ws, $this->we);
        $prev = $this->prev ? $this->headline($this->prev[0], $this->prev[1]) : null;
        $spark = ['total' => [], 'users' => [], 'weak' => [], 'needimprovement' => []];
        foreach ($this->buckets as $b) {
            $h = $this->headline($b['start'], $b['end']);
            $spark['total'][] = $total($b['end']);
            $spark['users'][] = $h['users'];
            $spark['weak'][] = $h['weak'];
            $spark['needimprovement'][] = $h['needimprovement'];
        }
        $kpis = [
            'total' => $this->kpi(count($comps), $this->ws > 0 ? $total($this->ws) : null, $spark['total']),
            'users' => $this->kpi($cur['users'], $prev ? $prev['users'] : null, $spark['users']),
            'weak' => $this->kpi($cur['weak'], $prev ? $prev['weak'] : null, $spark['weak']),
            'needimprovement' => $this->kpi($cur['needimprovement'], $prev ? $prev['needimprovement'] : null,
                $spark['needimprovement']),
        ];

        // Top weak competencies: lowest average score.
        $per = $this->per_item($this->latest($this->ws, $this->we));
        uasort($per, function($a, $b) {
            return $a['avg'] <=> $b['avg'];
        });
        $chart = ['categories' => [], 'series' => [
            ['label' => get_string('sga_avgscore', 'local_businessintelligence'), 'color' => 'destructive', 'values' => []],
        ]];
        foreach (array_slice($per, 0, 8, true) as $key => $v) {
            $chart['categories'][] = get_string('sga_toplabel', 'local_businessintelligence',
                ['name' => $d['items'][$key]['name'], 'users' => $v['weakusers']]);
            $chart['series'][0]['values'][] = (int)round($v['avg']);
        }
        return [
            'kpis' => $kpis,
            'charts' => ['topweak' => $chart],
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
     * Competencies mapped to the given members (by department / designation of the competency) - item keys.
     *
     * @param array $members
     * @return array key => true
     */
    protected function mapped(array $members): array {
        global $DB;
        static $deptnames = null, $designames = null;
        $d = $this->data();
        if (!$d['comps']) {
            return [];
        }
        if ($deptnames === null) {
            $deptnames = source::has_table('local_skillgap_dept') ?
                $DB->get_records_menu('local_skillgap_dept', null, '', 'id, name') : [];
            $designames = source::has_table('local_skillgap_desig_hierarchy') ?
                $DB->get_records_menu('local_skillgap_desig_hierarchy', null, '', 'id, designation_name') : [];
        }
        $depts = $desigs = [];
        foreach ($members as $u) {
            $depts[\core_text::strtolower(trim($u->department))] = true;
            $desigs[\core_text::strtolower(trim($u->designation))] = true;
        }
        $out = [];
        foreach ($d['comps'] as $cid => $c) {
            if ($this->f->competency > 0 && (int)$cid !== $this->f->competency) {
                continue;
            }
            $dn = (int)$c->deptid ? \core_text::strtolower(trim($deptnames[$c->deptid] ?? "\0")) : '';
            $gn = (int)$c->desigid ? \core_text::strtolower(trim($designames[$c->desigid] ?? "\0")) : '';
            if (($dn !== '' && !isset($depts[$dn])) || ($gn !== '' && !isset($desigs[$gn]))) {
                continue;
            }
            if (!empty($d['subs'][$cid])) {
                foreach ($d['subs'][$cid] as $sid => $unused) {
                    $out[$cid . ':' . $sid] = true;
                }
            } else {
                $out[$cid . ':0'] = true;
            }
        }
        return $out;
    }

    /**
     * Group statistics for a set of members.
     *
     * @param array $members
     * @param array $latest latest scores in the period
     * @return array
     */
    protected function group_stats(array $members, array $latest): array {
        $mine = array_intersect_key($latest, $members);
        $per = $this->per_item($mine);
        $comps = $this->mapped($members) + array_fill_keys(array_keys($per), true);
        $weak = 0;
        $sum = 0.0;
        $n = 0;
        foreach ($per as $v) {
            if ($v['avg'] < $this->threshold()) {
                $weak++;
            }
            $sum += $v['sum'];
            $n += $v['users'];
        }
        $ni = 0;
        foreach ($mine as $items) {
            foreach ($items as $s) {
                if ($s['status'] === self::SG_WEAK) {
                    $ni++;
                    break;
                }
            }
        }
        return [
            'competencies' => count($comps),
            'usersassessed' => count($mine),
            'assessed' => count($per),
            'assessments' => $this->attempts($this->ws, $this->we, $members),
            'weak' => $weak,
            'needimprovement' => $ni,
            'avg' => $n ? round($sum / $n, 1) : 0,
        ];
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
        $latest = $this->latest($this->ws, $this->we, $members);
        $none = get_string('notset', 'local_businessintelligence');
        $yes = get_string('yes');
        $no = get_string('no');

        if ($level === 'designation') {
            $managers = profile::managers();
            $mvalues = profile::values_for('manager', array_keys($members));
            $out = [];
            foreach ($members as $uid => $u) {
                $items = $latest[$uid] ?? [];
                $sum = 0.0;
                $weak = 0;
                foreach ($items as $s) {
                    $sum += $s['pct'];
                    $weak += $s['status'] === self::SG_WEAK ? 1 : 0;
                }
                $memail = profile::normalise_manager($mvalues[$uid] ?? '');
                $out[] = [
                    'key' => (string)$uid,
                    'name' => $u->fullname,
                    'email' => $u->email,
                    'designation' => $u->designation !== '' ? $u->designation : $none,
                    'branch' => $u->branch,
                    'manager' => $memail === '' ? '' : ($managers[$memail] ?? $memail),
                    'assessed' => count($items),
                    'avg' => $items ? (int)round($sum / count($items)) : 0,
                    'weak' => $weak,
                    'improvement' => $weak > 0 ? $yes : $no,
                ];
            }
            usort($out, function($a, $b) {
                return ($b['assessed'] <=> $a['assessed']) ?: strcmp($a['name'], $b['name']);
            });
            return $out;
        }

        $groups = [];
        foreach ($members as $uid => $u) {
            $k = $level === 'org' ? ($u->department !== '' ? $u->department : self::NONE)
                : ($u->designation !== '' ? $u->designation : self::NONE);
            $groups[$k][$uid] = $u;
        }
        $out = [];
        foreach ($groups as $k => $gm) {
            $out[] = ['key' => (string)$k, 'name' => $k === self::NONE ? $none : (string)$k,
                'users' => count($gm)] + $this->group_stats($gm, $latest);
        }
        usort($out, function($a, $b) {
            return ($b['usersassessed'] <=> $a['usersassessed']) ?: (($b['users'] <=> $a['users']) ?: strcmp($a['name'],
                $b['name']));
        });
        return $out;
    }

    /**
     * Competencies of a department / designation (competency-count popup).
     *
     * @param string $dept
     * @param string $desig
     * @return array
     */
    public function competencies(string $dept, string $desig): array {
        $members = $this->members($dept, $desig);
        $per = $this->per_item($this->latest($this->ws, $this->we, $members));
        $keys = $this->mapped($members) + array_fill_keys(array_keys($per), true);
        $items = $this->full_items();
        $assessed = get_string('sga_st_assessed', 'local_businessintelligence');
        $notassessed = get_string('sga_st_notassessed', 'local_businessintelligence');
        $out = [];
        foreach (array_keys($keys) as $key) {
            $v = $per[$key] ?? null;
            $out[] = [
                'name' => $items[$key]['name'] ?? $key,
                'type' => $items[$key]['type'] ?? '',
                'status' => $v ? $assessed : $notassessed,
                'assessments' => $v ? $v['n'] : 0,
                'users' => $v ? $v['users'] : 0,
                'avg' => $v ? (int)round($v['avg']) : null,
            ];
        }
        usort($out, function($a, $b) {
            return ($b['users'] <=> $a['users']) ?: strcmp($a['name'], $b['name']);
        });
        return $out;
    }

    /**
     * Weak competencies of a department / designation (weak-count popup).
     *
     * @param string $dept
     * @param string $desig
     * @return array
     */
    public function weak(string $dept, string $desig): array {
        $members = $this->members($dept, $desig);
        $per = $this->per_item($this->latest($this->ws, $this->we, $members));
        $items = $this->full_items();
        $out = [];
        foreach ($per as $key => $v) {
            if ($v['avg'] >= $this->threshold()) {
                continue;
            }
            $high = $v['users'] && ($v['weakusers'] * 2 > $v['users']);
            $out[] = [
                'name' => $items[$key]['name'] ?? $key,
                'avg' => (int)round($v['avg']),
                'weakusers' => $v['weakusers'],
                'severity' => $high ? 'high' : 'moderate',
                'severitylabel' => get_string($high ? 'sga_sev_high' : 'sga_sev_moderate', 'local_businessintelligence'),
            ];
        }
        usort($out, function($a, $b) {
            return ($a['avg'] <=> $b['avg']) ?: strcmp($a['name'], $b['name']);
        });
        return $out;
    }

    /**
     * All item names, including mapped competencies never scored.
     *
     * @return array key => [name, type, compid]
     */
    protected function full_items(): array {
        $d = $this->data();
        $items = $d['items'];
        foreach ($d['comps'] as $cid => $c) {
            $cname = format_string($c->name);
            if (!empty($d['subs'][$cid])) {
                foreach ($d['subs'][$cid] as $sid => $sname) {
                    $items[$cid . ':' . $sid] = $items[$cid . ':' . $sid] ?? ['name' => $cname . ' — ' . $sname,
                        'type' => $cname, 'compid' => (int)$cid];
                }
            } else {
                $items[$cid . ':0'] = $items[$cid . ':0'] ?? ['name' => $cname, 'type' => $cname, 'compid' => (int)$cid];
            }
        }
        return $items;
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
        $items = $this->full_items();
        $latest = $this->latest($this->ws, $this->we, [$userid => true])[$userid] ?? [];
        // Previous score: the latest score of the same competency before the current one.
        $history = [];
        foreach ($this->data()['scores'] as [$uid, $key, $pct, , $time]) {
            if ($uid === $userid) {
                $history[$key][] = [$time, $pct];
            }
        }
        $labels = [
            self::SG_COMPETENT => get_string('sga_st_proficient', 'local_businessintelligence'),
            self::SG_MODERATE => get_string('sga_st_developing', 'local_businessintelligence'),
            self::SG_WEAK => get_string('sga_st_weakzone', 'local_businessintelligence'),
        ];
        $rows = [];
        $sum = 0.0;
        $weak = 0;
        foreach ($latest as $key => $s) {
            $prev = null;
            foreach ($history[$key] ?? [] as [$t, $pct]) {
                if ($t < $s['time']) {
                    $prev = $pct;
                }
            }
            $score = (int)round($s['pct']);
            $isweak = $s['status'] === self::SG_WEAK;
            $sum += $s['pct'];
            $weak += $isweak ? 1 : 0;
            $diff = $prev === null ? null : $score - (int)round($prev);
            $rows[] = [
                'name' => $items[$key]['name'] ?? $key,
                'score' => $score,
                'previous' => $prev === null ? '' : (string)(int)round($prev),
                'progress' => $diff === null ? '' : ($diff > 0 ? '+' . $diff : (string)$diff),
                'trend' => $diff === null ? '' : ($diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat')),
                'status' => $s['status'],
                'statuslabel' => $labels[$s['status']] ?? $s['status'],
                'weakzone' => $isweak,
                'weakzonelabel' => get_string($isweak ? 'yes' : 'no'),
            ];
        }
        usort($rows, function($a, $b) {
            return strcmp($a['name'], $b['name']);
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
            'assessed' => count($latest),
            'avg' => $latest ? (int)round($sum / count($latest)) : 0,
            'weak' => $weak,
            'improvement' => get_string($weak > 0 ? 'yes' : 'no'),
            'competencies' => $rows,
        ];
    }
}
