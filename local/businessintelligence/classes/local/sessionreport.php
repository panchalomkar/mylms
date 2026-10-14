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
 * Training Sessions Analytics page: KPIs, charts, Department -> Designation -> User drill-down,
 * sessions popup and the per-learner session report.
 *
 * Sessions = ILT (face-to-face) + Zoom + Google Meet (see sessions class), for the filtered population.
 * Enrolled = learner registrations (ILT sign-ups, online participants); Attended = present / partially attended.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sessionreport extends metrics {
    /** Empty profile value marker. */
    const NONE = '__none__';

    /** @var string all|f2f|online */
    protected $stype = 'all';
    /** @var string all|internal|external */
    protected $tkind = 'all';
    /** @var array|null guest trainer ids */
    protected $guests = null;

    /**
     * Constructor.
     *
     * @param filters $f
     * @param string $stype all|f2f|online
     * @param string $tkind all|internal|external
     */
    public function __construct(filters $f, string $stype = 'all', string $tkind = 'all') {
        parent::__construct($f);
        $this->stype = in_array($stype, ['f2f', 'online'], true) ? $stype : 'all';
        $this->tkind = in_array($tkind, ['internal', 'external'], true) ? $tkind : 'all';
    }

    /**
     * Apply the session-type and trainer-type toggles.
     *
     * @param array $sessions
     * @return array
     */
    protected function toggle(array $sessions): array {
        if ($this->tkind !== 'all') {
            $ids = [];
            foreach ($sessions as $s) {
                foreach ($s['trainers'] as $t) {
                    $ids[$t] = $t;
                }
            }
            $this->guests = self::guest_trainers($ids) + ($this->guests ?? []);
        }
        return array_values(array_filter($sessions, function($s) {
            if ($this->stype !== 'all' && $s['type'] !== $this->stype) {
                return false;
            }
            if ($this->tkind !== 'all') {
                $external = false;
                foreach ($s['trainers'] as $t) {
                    if (isset($this->guests[$t])) {
                        $external = true;
                        break;
                    }
                }
                if (($this->tkind === 'external') !== $external) {
                    return false;
                }
            }
            return true;
        }));
    }

    /**
     * Sessions of the window (population-scoped and toggled).
     *
     * @return array
     */
    public function sessionlist(): array {
        return $this->toggle($this->scoped_sessions());
    }

    /**
     * Totals for sessions starting in [s, e].
     *
     * @param array $sessions
     * @param int $s
     * @param int $e
     * @return array
     */
    protected function totals(array $sessions, int $s, int $e): array {
        $t = ['sessions' => 0, 'f2f' => 0, 'online' => 0, 'enrolled' => 0, 'attended' => 0, 'noshows' => 0, 'secs' => 0];
        foreach ($sessions as $x) {
            if ($x['start'] < $s || $x['start'] > $e) {
                continue;
            }
            $t['sessions']++;
            $t[$x['type']]++;
            foreach ($x['att'] as $a) {
                $t['enrolled']++;
                if ($a['state'] === 'attended' || $a['state'] === 'partial') {
                    $t['attended']++;
                } else if ($a['state'] === 'noshow') {
                    $t['noshows']++;
                }
                $t['secs'] += $a['secs'];
            }
        }
        $t['pct'] = $t['enrolled'] ? round($t['attended'] * 100 / $t['enrolled'], 1) : 0.0;
        $t['hours'] = round($t['secs'] / HOURSECS, 1);
        return $t;
    }

    /**
     * KPIs and charts.
     *
     * @return array
     */
    public function overview(): array {
        $list = $this->sessionlist();
        $cur = $this->totals($list, $this->ws, $this->we);
        $prev = $this->prev ? $this->totals($list, $this->prev[0], $this->prev[1]) : null;
        $labels = [];
        $sp = ['sessions' => [], 'enrolled' => [], 'pct' => [], 'hours' => [], 'noshows' => []];
        foreach ($this->buckets as $b) {
            $labels[] = $b['label'];
            $bt = $this->totals($list, $b['start'], $b['end']);
            foreach ($sp as $k => $unused) {
                $sp[$k][] = $bt[$k];
            }
        }
        $attendance = [];
        foreach ($this->buckets as $i => $b) {
            $attendance[] = $sp['enrolled'][$i] ? $sp['pct'][$i] : null;
        }
        $s = function($k) {
            return get_string($k, 'local_businessintelligence');
        };
        return [
            'kpis' => [
                'sessions' => $this->kpi($cur['sessions'], $prev ? $prev['sessions'] : null, $sp['sessions']),
                'enrolled' => $this->kpi($cur['enrolled'], $prev ? $prev['enrolled'] : null, $sp['enrolled']),
                'attendance' => $this->kpi($cur['pct'], $prev ? $prev['pct'] : null, $sp['pct'], 'pct'),
                'hours' => $this->kpi($cur['hours'], $prev ? $prev['hours'] : null, $sp['hours']),
                'noshows' => $this->kpi($cur['noshows'], $prev ? $prev['noshows'] : null, $sp['noshows']),
            ],
            'charts' => [
                'mix' => ['items' => [
                    ['label' => $s('facetoface'), 'value' => $cur['f2f']],
                    ['label' => $s('online'), 'value' => $cur['online']],
                ]],
                'attendancetrend' => ['labels' => $labels, 'values' => $attendance, 'max' => 100],
                'hourstrend' => ['categories' => $labels, 'series' => [
                    ['label' => $s('ts_hours'), 'color' => 'chart1', 'values' => $sp['hours']],
                ]],
            ],
            'refreshed' => userdate(time(), get_string('strftimedatetimeshort', 'core_langconfig')),
        ];
    }

    /**
     * Members of a scope.
     *
     * @param string $dept
     * @param string $desig
     * @return array
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
     * Drill-down rows.
     *
     * @param string $level org|department|designation
     * @param string $dept
     * @param string $desig
     * @return array
     */
    public function level(string $level, string $dept, string $desig): array {
        $members = $this->members($level === 'org' ? '' : $dept, $level === 'designation' ? $desig : '');
        $groupof = function($u) use ($level) {
            if ($level === 'org') {
                return $u->department !== '' ? $u->department : self::NONE;
            }
            if ($level === 'department') {
                return $u->designation !== '' ? $u->designation : self::NONE;
            }
            return (string)$u->id;
        };
        $g = [];
        foreach ($members as $uid => $u) {
            $k = $groupof($u);
            if (!isset($g[$k])) {
                $g[$k] = ['users' => 0, 'sessions' => [], 'enrolled' => 0, 'attended' => 0, 'secs' => 0];
            }
            $g[$k]['users']++;
        }
        foreach ($this->sessionlist() as $x) {
            if ($x['start'] < $this->ws || $x['start'] > $this->we) {
                continue;
            }
            foreach ($x['att'] as $uid => $a) {
                if (!isset($members[$uid])) {
                    continue;
                }
                $k = $groupof($members[$uid]);
                $g[$k]['sessions'][$x['id']] = true;
                $g[$k]['enrolled']++;
                if ($a['state'] === 'attended' || $a['state'] === 'partial') {
                    $g[$k]['attended']++;
                }
                $g[$k]['secs'] += $a['secs'];
            }
        }
        $out = [];
        $none = get_string('notset', 'local_businessintelligence');
        if ($level === 'designation') {
            $managers = profile::managers();
            $mvalues = profile::values_for('manager', array_keys($members));
        }
        foreach ($g as $k => $r) {
            $row = [
                'key' => (string)$k,
                'users' => $r['users'],
                'sessions' => count($r['sessions']),
                'enrolled' => $r['enrolled'],
                'attended' => $r['attended'],
                'pct' => $r['enrolled'] ? round($r['attended'] * 100 / $r['enrolled'], 1) : 0,
                'hours' => round($r['secs'] / HOURSECS, 1),
            ];
            if ($level === 'designation') {
                $u = $members[(int)$k];
                $memail = profile::normalise_manager($mvalues[(int)$k] ?? '');
                $row += [
                    'name' => $u->fullname,
                    'email' => $u->email,
                    'branch' => $u->branch,
                    'manager' => $memail === '' ? '' : ($managers[$memail] ?? $memail),
                ];
            } else {
                $row['name'] = $k === self::NONE ? $none : (string)$k;
            }
            $out[] = $row;
        }
        usort($out, function($a, $b) use ($level) {
            $ka = $level === 'designation' ? 'enrolled' : 'users';
            return ($b[$ka] <=> $a[$ka]) ?: strcmp($a['name'], $b['name']);
        });
        return $out;
    }

    /**
     * Display names of users.
     *
     * @param int[] $ids
     * @return array id => name
     */
    protected function names(array $ids): array {
        global $DB;
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) {
            return [];
        }
        $p = new params();
        $in = $p->in($ids);
        $fields = implode(', ', array_map(function($f) {
            return 'u.' . $f;
        }, \core_user\fields::get_name_fields()));
        $out = [];
        foreach ($DB->get_records_sql("SELECT u.id, {$fields} FROM {user} u WHERE u.id {$in}", $p->values) as $u) {
            $out[(int)$u->id] = fullname($u);
        }
        return $out;
    }

    /**
     * Fallback session title.
     *
     * @param array $x
     * @return string
     */
    protected function title(array $x): string {
        if (!empty($x['name'])) {
            return $x['name'];
        }
        $src = ['ilt' => get_string('facetoface', 'local_businessintelligence'), 'zoom' => 'Zoom', 'gmeet' => 'Google Meet'];
        return ($src[$x['source']] ?? $x['source']) . ' · ' . userdate($x['start'], get_string('strftimedatefullshort',
            'core_langconfig'));
    }

    /**
     * Sessions of a department / designation (total-sessions popup).
     *
     * @param string $dept
     * @param string $desig
     * @return array
     */
    public function sessions(string $dept, string $desig): array {
        $members = $this->members($dept, $desig);
        $rows = [];
        $trainerids = [];
        foreach ($this->sessionlist() as $x) {
            if ($x['start'] < $this->ws || $x['start'] > $this->we) {
                continue;
            }
            $en = $pr = 0;
            foreach ($x['att'] as $uid => $a) {
                if (!isset($members[$uid])) {
                    continue;
                }
                $en++;
                if ($a['state'] === 'attended' || $a['state'] === 'partial') {
                    $pr++;
                }
            }
            if (!$en) {
                continue;
            }
            $rows[] = ['x' => $x, 'enrolled' => $en, 'present' => $pr];
            foreach ($x['trainers'] as $t) {
                $trainerids[] = $t;
            }
        }
        $tn = $this->names($trainerids);
        usort($rows, function($a, $b) {
            return $b['x']['start'] <=> $a['x']['start'];
        });
        return array_map(function($r) use ($tn) {
            return [
                'name' => $this->title($r['x']),
                'trainer' => implode(', ', array_filter(array_map(function($t) use ($tn) {
                    return $tn[$t] ?? '';
                }, $r['x']['trainers']))),
                'enrolled' => $r['enrolled'],
                'present' => $r['present'],
            ];
        }, $rows);
    }

    /**
     * Learner session report (includes upcoming sessions the learner is registered for).
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
        $now = time();
        $all = $this->toggle(sessions::fetch($this->ws, $now + YEARSECS, true));
        $rows = [];
        $trainerids = [];
        foreach ($all as $x) {
            if (isset($x['att'][$userid])) {
                $rows[] = $x;
                foreach ($x['trainers'] as $t) {
                    $trainerids[] = $t;
                }
            }
        }
        $tn = $this->names($trainerids);
        $s = function($k) {
            return get_string($k, 'local_businessintelligence');
        };
        $out = [];
        $attended = 0;
        $secs = 0;
        foreach ($rows as $x) {
            $a = $x['att'][$userid];
            $end = !empty($x['end']) ? $x['end'] : $x['start'] + $x['secs'];
            $sstatus = $now < $x['start'] ? 'upcoming' : ($now <= $end ? 'ongoing' : 'completed');
            if ($a['state'] === 'attended' || $a['state'] === 'partial') {
                $astatus = 'present';
                $attended++;
            } else if ($a['state'] === 'noshow' || $sstatus === 'completed') {
                $astatus = 'absent';
            } else {
                $astatus = 'registered';
            }
            $secs += $a['secs'];
            $pct = null;
            if ($astatus === 'present') {
                $pct = $x['secs'] > 0 ? min(100, (int)round($a['secs'] * 100 / $x['secs'])) : 100;
            } else if ($astatus === 'absent') {
                $pct = 0;
            }
            $out[] = [
                'name' => $this->title($x),
                'trainer' => implode(', ', array_filter(array_map(function($t) use ($tn) {
                    return $tn[$t] ?? '';
                }, $x['trainers']))),
                'attendance' => $astatus,
                'attendancelabel' => $s('ts_att_' . $astatus),
                'session' => $sstatus,
                'sessionlabel' => $s('ts_ss_' . $sstatus),
                'duration' => $this->num(round($x['secs'] / HOURSECS, 1)) . ' ' . $s('ts_hrs'),
                'pct' => $pct === null ? '—' : $pct . '%',
                'start' => $x['start'],
            ];
        }
        usort($out, function($a, $b) {
            return $b['start'] <=> $a['start'];
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
            'enrolled' => count($out),
            'attended' => $attended,
            'pct' => $this->pct(count($out) ? round($attended * 100 / count($out), 1) : 0),
            'hours' => $this->num(round($secs / HOURSECS, 1)),
            'sessions' => $out,
        ];
    }
}
