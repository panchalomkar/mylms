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
 * Organization -> Department -> Designation -> Learner drill-down for each BI report.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class drilldown {
    /** Report keys in display order. */
    const REPORTS = ['learning', 'sessions', 'learningpaths', 'trainer', 'skillgap', 'ojt', 'roleplay', 'outcome'];

    /** Marker for an empty department/designation. */
    const NONE = '__none__';

    /** Max learner rows returned at the last level. */
    const MAXROWS = 1000;

    /** @var metrics */
    protected $m;
    /** @var filters */
    protected $f;

    /**
     * Constructor.
     *
     * @param filters $f
     */
    public function __construct(filters $f) {
        $this->f = $f;
        $this->m = new metrics($f);
    }

    /**
     * Column definitions per report.
     *
     * Types: int (sum), hours (sum of seconds), pct (num/den), avg (sum/n), signedpct (sum/n with sign).
     *
     * @param string $report
     * @return array
     */
    public static function columns(string $report): array {
        $s = function($k) {
            return get_string('col_' . $k, 'local_businessintelligence');
        };
        switch ($report) {
            case 'learning':
                return [
                    ['key' => 'assigned', 'label' => $s('assigned'), 'type' => 'int'],
                    ['key' => 'started', 'label' => $s('started'), 'type' => 'int'],
                    ['key' => 'completed', 'label' => $s('completed'), 'type' => 'int'],
                    ['key' => 'rate', 'label' => $s('completionrate'), 'type' => 'pct', 'num' => 'completed', 'den' => 'assigned'],
                    ['key' => 'overdue', 'label' => $s('overduecourses'), 'type' => 'int'],
                ];
            case 'sessions':
                return [
                    ['key' => 'attended', 'label' => $s('sessionsattended'), 'type' => 'int'],
                    ['key' => 'noshow', 'label' => $s('noshows'), 'type' => 'int'],
                    ['key' => 'attpct', 'label' => $s('attendance'), 'type' => 'pct', 'num' => 'iltattended', 'den' => 'marked'],
                    ['key' => 'secs', 'label' => $s('traininghours'), 'type' => 'hours'],
                ];
            case 'learningpaths':
                return [
                    ['key' => 'assigned', 'label' => $s('pathsassigned'), 'type' => 'int'],
                    ['key' => 'completed', 'label' => $s('completed'), 'type' => 'int'],
                    ['key' => 'inprogress', 'label' => $s('inprogress'), 'type' => 'int'],
                    ['key' => 'notstarted', 'label' => $s('notstarted'), 'type' => 'int'],
                    ['key' => 'rate', 'label' => $s('completionrate'), 'type' => 'pct', 'num' => 'completed', 'den' => 'assigned'],
                ];
            case 'trainer':
                return [
                    ['key' => 'sessions', 'label' => $s('sessionsdelivered'), 'type' => 'int'],
                    ['key' => 'secs', 'label' => $s('hoursdelivered'), 'type' => 'hours'],
                    ['key' => 'learners', 'label' => $s('learnerstrained'), 'type' => 'int'],
                    ['key' => 'guest', 'label' => $s('guesttrainers'), 'type' => 'int'],
                ];
            case 'skillgap':
                return [
                    ['key' => 'comps', 'label' => $s('compsassessed'), 'type' => 'int'],
                    ['key' => 'avg', 'label' => $s('avgscore'), 'type' => 'avg', 'sum' => 'sumpct', 'n' => 'n', 'suffix' => '%'],
                    ['key' => 'weak', 'label' => $s('needimprovement'), 'type' => 'int'],
                    ['key' => 'moderate', 'label' => $s('moderate'), 'type' => 'int'],
                    ['key' => 'competent', 'label' => $s('competent'), 'type' => 'int'],
                ];
            case 'ojt':
                return [
                    ['key' => 'rated', 'label' => $s('compsrated'), 'type' => 'int'],
                    ['key' => 'red', 'label' => $s('red'), 'type' => 'int'],
                    ['key' => 'yellow', 'label' => $s('yellow'), 'type' => 'int'],
                    ['key' => 'green', 'label' => $s('green'), 'type' => 'int'],
                    ['key' => 'greenpct', 'label' => $s('greenpct'), 'type' => 'pct', 'num' => 'green', 'den' => 'rated'],
                ];
            case 'roleplay':
                return [
                    ['key' => 'attempts', 'label' => $s('attempts'), 'type' => 'int'],
                    ['key' => 'participated', 'label' => $s('participated'), 'type' => 'int'],
                    ['key' => 'avg', 'label' => $s('avgscore'), 'type' => 'avg', 'sum' => 'sumscore', 'n' => 'scored', 'suffix' => '%'],
                    ['key' => 'passed', 'label' => $s('passed'), 'type' => 'int'],
                    ['key' => 'passrate', 'label' => $s('passrate'), 'type' => 'pct', 'num' => 'passed', 'den' => 'attempts'],
                ];
            case 'outcome':
                return [
                    ['key' => 'trained', 'label' => $s('trained'), 'type' => 'int'],
                    ['key' => 'assessed', 'label' => $s('assessed'), 'type' => 'int'],
                    ['key' => 'outcome', 'label' => $s('withoutcome'), 'type' => 'int'],
                    ['key' => 'positive', 'label' => $s('positive'), 'type' => 'int'],
                    ['key' => 'avgchange', 'label' => $s('avgchange'), 'type' => 'signedpct', 'sum' => 'deltasum', 'n' => 'outcome'],
                ];
        }
        throw new \invalid_parameter_exception('Unknown report ' . $report);
    }

    /**
     * Raw per-user values for a report (only users with data).
     *
     * @param string $report
     * @return array userid => array
     */
    public function per_user(string $report): array {
        $pop = $this->m->population();
        [$ws, $we] = $this->m->window();
        $out = [];
        switch ($report) {
            case 'learning':
                return $this->m->enrol_per_user($ws, $we);

            case 'sessions':
                foreach ($this->m->scoped_sessions() as $s) {
                    if ($s['start'] < $ws || $s['start'] > $we) {
                        continue;
                    }
                    foreach ($s['att'] as $uid => $a) {
                        $r = &$out[$uid];
                        $r = ($r ?? []) + ['attended' => 0, 'noshow' => 0, 'iltattended' => 0, 'marked' => 0, 'secs' => 0];
                        $r['secs'] += $a['secs'];
                        if ($a['state'] === 'attended' || $a['state'] === 'partial') {
                            $r['attended']++;
                            if ($s['source'] === 'ilt') {
                                $r['iltattended']++;
                                $r['marked']++;
                            }
                        } else if ($a['state'] === 'noshow') {
                            $r['noshow']++;
                            $r['marked']++;
                        }
                        unset($r);
                    }
                }
                return $out;

            case 'learningpaths':
                foreach ($this->m->lp_pairs()['pairs'] as [$lpid, $uid, $ctime, $started]) {
                    $r = &$out[$uid];
                    $r = ($r ?? []) + ['assigned' => 0, 'completed' => 0, 'inprogress' => 0, 'notstarted' => 0];
                    $r['assigned']++;
                    if ($ctime !== null && $ctime <= $we) {
                        $r['completed']++;
                    } else if ($started) {
                        $r['inprogress']++;
                    } else {
                        $r['notstarted']++;
                    }
                    unset($r);
                }
                return $out;

            case 'trainer':
                $trainers = [];
                foreach ($this->m->scoped_sessions() as $s) {
                    if (!$s['trainers'] || $s['start'] < $ws || $s['start'] > $we) {
                        continue;
                    }
                    foreach ($s['trainers'] as $t) {
                        $r = &$out[$t];
                        $r = ($r ?? []) + ['sessions' => 0, 'secs' => 0, 'learners' => 0, 'guest' => 0];
                        $r['sessions']++;
                        $r['secs'] += $s['secs'];
                        $r['learners'] += count($s['att']);
                        $trainers[$t] = $t;
                        unset($r);
                    }
                }
                foreach (metrics::guest_trainers($trainers) as $uid => $unused) {
                    $out[$uid]['guest'] = 1;
                }
                // Trainers outside the filtered population (e.g. guests with no department) are still listed.
                return $out;

            case 'skillgap':
                return $this->m->skillgap_per_user();

            case 'ojt':
                foreach ($this->m->ojt_rows() as [$uid, $cid, $name, $zone]) {
                    $r = &$out[$uid];
                    $r = ($r ?? []) + ['rated' => 0, 'red' => 0, 'yellow' => 0, 'green' => 0];
                    $r['rated']++;
                    $r[$zone]++;
                    unset($r);
                }
                return $out;

            case 'roleplay':
                return $this->m->roleplay_per_user();

            case 'outcome':
                foreach ($this->m->outcome_per_user() as $uid => $r) {
                    $r['deltasum'] = $r['outcome'] ? $r['delta'] : 0;
                    $out[$uid] = $r;
                }
                return $out;
        }
        return [];
    }

    /**
     * Drill-down rows.
     *
     * @param string $report
     * @param string $level org|department|designation
     * @param string $department
     * @param string $designation
     * @return array
     */
    public function get(string $report, string $level, string $department, string $designation): array {
        global $DB;
        $columns = self::columns($report);
        $data = $this->per_user($report);
        $pop = $this->m->population();

        // Members: everyone in the population, plus trainers outside it for the trainer report.
        $members = $pop;
        if ($report === 'trainer') {
            $members = array_intersect_key($pop, $data);
            $missing = array_diff_key($data, $pop);
            if ($missing) {
                $names = implode(', ', array_map(function($f) {
                    return 'u.' . $f;
                }, \core_user\fields::get_name_fields()));
                $p = new params();
                $in = $p->in(array_keys($missing));
                $rs = $DB->get_recordset_sql("SELECT u.id, u.email, {$names} FROM {user} u WHERE u.id {$in}", $p->values);
                $extra = [];
                foreach ($rs as $u) {
                    $extra[(int)$u->id] = (object)['id' => (int)$u->id, 'fullname' => fullname($u), 'email' => $u->email,
                        'department' => '', 'designation' => '', 'branch' => ''];
                }
                $rs->close();
                foreach (['department', 'designation'] as $k) {
                    foreach (profile::values_for($k, array_keys($extra)) as $uid => $v) {
                        $extra[$uid]->$k = $v;
                    }
                }
                $members += $extra;
            }
        }

        $deptkey = function($u) {
            return $u->department !== '' ? $u->department : self::NONE;
        };
        $desigkey = function($u) {
            return $u->designation !== '' ? $u->designation : self::NONE;
        };

        // Scope members to the requested level.
        if ($level !== 'org') {
            $members = array_filter($members, function($u) use ($deptkey, $department) {
                return $deptkey($u) === $department;
            });
        }
        if ($level === 'designation') {
            $members = array_filter($members, function($u) use ($desigkey, $designation) {
                return $desigkey($u) === $designation;
            });
        }

        $none = get_string('notset', 'local_businessintelligence');
        $rows = [];
        $total = $this->aggregate($columns, array_keys($members), $data);
        if ($level === 'designation') {
            foreach ($members as $uid => $u) {
                $agg = $this->aggregate($columns, [$uid], $data);
                $agg['label'] = $u->fullname;
                $agg['sub'] = $u->email;
                $agg['key'] = (string)$uid;
                $agg['url'] = (new \moodle_url('/user/profile.php', ['id' => $uid]))->out(false);
                $agg['hasdata'] = isset($data[$uid]);
                $rows[] = $agg;
            }
            usort($rows, function($a, $b) {
                return ($b['hasdata'] <=> $a['hasdata']) ?: strcmp($a['label'], $b['label']);
            });
            $rows = array_slice($rows, 0, self::MAXROWS);
        } else {
            $groups = [];
            foreach ($members as $uid => $u) {
                $k = $level === 'org' ? $deptkey($u) : $desigkey($u);
                $groups[$k][] = $uid;
            }
            foreach ($groups as $k => $uids) {
                $agg = $this->aggregate($columns, $uids, $data);
                $agg['label'] = $k === self::NONE ? $none : $k;
                $agg['key'] = (string)$k;
                $agg['drill'] = true;
                $rows[] = $agg;
            }
            usort($rows, function($a, $b) {
                return ($b['members'] <=> $a['members']) ?: strcmp($a['label'], $b['label']);
            });
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'total' => $total,
            'truncated' => $level === 'designation' && count($members) > self::MAXROWS,
        ];
    }

    /**
     * Aggregate the given users' values into display cells.
     *
     * @param array $columns
     * @param int[] $uids
     * @param array $data
     * @return array
     */
    protected function aggregate(array $columns, array $uids, array $data): array {
        $sum = [];
        $active = 0;
        foreach ($uids as $uid) {
            if (!isset($data[$uid])) {
                continue;
            }
            $active++;
            foreach ($data[$uid] as $k => $v) {
                if (is_numeric($v)) {
                    $sum[$k] = ($sum[$k] ?? 0) + $v;
                }
            }
        }
        $cells = [];
        foreach ($columns as $c) {
            switch ($c['type']) {
                case 'hours':
                    $v = round(($sum[$c['key']] ?? 0) / HOURSECS, 1);
                    $cells[] = ['value' => $v, 'display' => $this->m->num($v)];
                    break;
                case 'pct':
                    $den = $sum[$c['den']] ?? 0;
                    $v = $den > 0 ? round(($sum[$c['num']] ?? 0) * 100 / $den, 1) : null;
                    $cells[] = ['value' => $v, 'display' => $this->m->pct($v)];
                    break;
                case 'avg':
                case 'signedpct':
                    $n = $sum[$c['n']] ?? 0;
                    $v = $n > 0 ? round(($sum[$c['sum']] ?? 0) / $n, 1) : null;
                    if ($v === null) {
                        $display = '–';
                    } else if ($c['type'] === 'signedpct') {
                        $display = ($v > 0 ? '+' : '') . $this->m->pct($v);
                    } else {
                        $display = $this->m->num($v) . ($c['suffix'] ?? '');
                    }
                    $cells[] = ['value' => $v, 'display' => $display];
                    break;
                default:
                    $v = $sum[$c['key']] ?? 0;
                    $cells[] = ['value' => $v, 'display' => $this->m->num($v)];
            }
        }
        return ['members' => count($uids), 'active' => $active, 'cells' => $cells];
    }

    /**
     * Metrics accessor.
     *
     * @return metrics
     */
    public function metrics(): metrics {
        return $this->m;
    }
}
