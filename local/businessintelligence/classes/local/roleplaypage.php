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
 * AI Role-Play Analytics: Scenario -> Department -> User, with the attempt-history popup.
 *
 * Source: mod_airoleplay. Every completed session (airoleplay_session.status = completed) in the period is an attempt.
 * Score = score_overall (0-100). An attempt passes when the plugin flagged it passed, or its score reaches the
 * scenario pass score.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class roleplaypage extends analysis {
    /** @var array|null memo */
    protected $data = null;

    /**
     * KPI cards.
     *
     * @return array
     */
    public static function kpidefs(): array {
        return ['scenarios' => 'layers', 'users' => 'users', 'attempts' => 'bot', 'avgscore' => 'gauge',
            'passrate' => 'circle-check'];
    }

    /**
     * Scenarios and attempts (memoised).
     *
     * @return array
     */
    public function data(): array {
        global $DB;
        if ($this->data !== null) {
            return $this->data;
        }
        $this->data = ['scenarios' => [], 'attempts' => []];
        if (!source::has_table('airoleplay_session') || !source::has_table('airoleplay')) {
            return $this->data;
        }
        $scen = [];
        foreach ($DB->get_records_sql("SELECT a.id, a.name, a.pass_score, a.timecreated, c.fullname AS coursename
                                         FROM {airoleplay} a
                                    LEFT JOIN {course} c ON c.id = a.course") as $a) {
            $scen[(int)$a->id] = ['name' => self::txt($a->name), 'course' => self::txt($a->coursename),
                'pass' => (float)$a->pass_score, 'created' => (int)$a->timecreated];
        }
        $pop = $this->population();
        $p = new params();
        $audio = source::has_field('airoleplay_session', 'session_audio_url') ? 's.session_audio_url' : "''";
        $rs = $DB->get_recordset_sql("SELECT s.id, s.airoleplay, s.userid, s.attempt, s.score_overall, s.passed,
                                             s.duration_secs, s.timestarted, s.timecompleted, {$audio} AS audio
                                        FROM {airoleplay_session} s
                                       WHERE s.status = " . $p->add('completed') . "
                                         AND s.timecompleted >= " . $p->add($this->lower) . "
                                         AND s.timecompleted <= " . $p->add($this->we) . "
                                    ORDER BY s.timecompleted, s.id", $p->values);
        $attempts = [];
        foreach ($rs as $r) {
            $uid = (int)$r->userid;
            $sid = (int)$r->airoleplay;
            if (!isset($pop[$uid]) || !isset($scen[$sid])) {
                continue;
            }
            $score = $r->score_overall === null ? null : (float)$r->score_overall;
            $passed = $r->passed !== null ? (int)$r->passed === 1 : ($score !== null && $score >= $scen[$sid]['pass']);
            $attempts[] = ['id' => (int)$r->id, 'sid' => $sid, 'uid' => $uid, 'n' => (int)$r->attempt, 'score' => $score,
                'passed' => $passed, 'secs' => self::secs((int)$r->duration_secs, (int)$r->timecompleted - (int)$r->timestarted),
                'time' => (int)$r->timecompleted, 'audio' => (string)$r->audio];
        }
        $rs->close();
        $this->data = ['scenarios' => $scen, 'attempts' => $attempts];
        return $this->data;
    }

    /**
     * Attempts inside a range, optionally filtered.
     *
     * @param int $from
     * @param int $to
     * @param callable|null $keep
     * @return array
     */
    protected function attempts(int $from, int $to, ?callable $keep = null): array {
        return array_values(array_filter($this->data()['attempts'], function($a) use ($from, $to, $keep) {
            return $a['time'] >= $from && $a['time'] <= $to && (!$keep || $keep($a));
        }));
    }

    /**
     * Aggregate of attempts.
     *
     * @param array $list
     * @return array users, attempts, avg, passrate, depts
     */
    protected function agg(array $list): array {
        $pop = $this->population();
        $users = $depts = [];
        $sum = 0.0;
        $n = 0;
        $pass = 0;
        foreach ($list as $a) {
            $users[$a['uid']] = true;
            $depts[self::gkey($pop[$a['uid']], 'department')] = true;
            if ($a['score'] !== null) {
                $sum += $a['score'];
                $n++;
            }
            $pass += $a['passed'] ? 1 : 0;
        }
        return ['users' => count($users), 'attempts' => count($list), 'avg' => $n ? round($sum / $n, 1) : null,
            'passrate' => $list ? round($pass * 100 / count($list), 1) : null, 'depts' => count($depts)];
    }

    /**
     * KPIs and overview chart.
     *
     * @return array
     */
    public function overview(): array {
        $d = $this->data();
        $scencount = function(int $at) use ($d) {
            return count(array_filter($d['scenarios'], function($s) use ($at) {
                return $s['created'] <= $at;
            }));
        };
        $cur = $this->agg($this->attempts($this->ws, $this->we));
        $prev = $this->prev ? $this->agg($this->attempts($this->prev[0], $this->prev[1])) : null;
        $spark = ['scenarios' => [], 'users' => [], 'attempts' => [], 'avgscore' => [], 'passrate' => []];
        foreach ($this->buckets as $b) {
            $a = $this->agg($this->attempts($b['start'], $b['end']));
            $spark['scenarios'][] = $scencount($b['end']);
            $spark['users'][] = $a['users'];
            $spark['attempts'][] = $a['attempts'];
            $spark['avgscore'][] = $a['avg'] ?? 0;
            $spark['passrate'][] = $a['passrate'] ?? 0;
        }
        $kpis = [
            'scenarios' => $this->kpi(count($d['scenarios']), $this->ws > 0 ? $scencount($this->ws) : null, $spark['scenarios']),
            'users' => $this->kpi($cur['users'], $prev ? $prev['users'] : null, $spark['users']),
            'attempts' => $this->kpi($cur['attempts'], $prev ? $prev['attempts'] : null, $spark['attempts']),
            'avgscore' => $this->kpi($cur['avg'] ?? 0, $prev ? $prev['avg'] : null, $spark['avgscore'], 'pct'),
            'passrate' => $this->kpi($cur['passrate'] ?? 0, $prev ? $prev['passrate'] : null, $spark['passrate'], 'pct'),
        ];
        $kpis['avgscore']['display'] = self::p($cur['avg'], 0);
        $kpis['passrate']['display'] = self::p($cur['passrate']);

        $per = [];
        foreach ($this->attempts($this->ws, $this->we) as $a) {
            $per[$a['sid']][] = $a;
        }
        $rows = [];
        foreach ($per as $sid => $list) {
            $rows[] = ['name' => $d['scenarios'][$sid]['name'], 'avg' => $this->agg($list)['avg'] ?? 0, 'n' => count($list)];
        }
        usort($rows, function($a, $b) {
            return $b['n'] <=> $a['n'];
        });
        $rows = array_slice($rows, 0, 10);
        return [
            'kpis' => $kpis,
            'charts' => [[
                'title' => self::s('an_roleplay_chart_perf'), 'info' => self::s('an_roleplay_chart_perf_info'), 'chart' => 'hbar',
                'height' => max(240, count($rows) * 34 + 50),
                'data' => ['categories' => array_column($rows, 'name'), 'series' => [
                    ['label' => self::s('an_roleplay_avgscore'), 'color' => 'chart1', 'values' => array_column($rows, 'avg')]]],
                'opts' => ['max' => 100, 'ticksuffix' => '%', 'suffix' => '%', 'labelw' => 170],
            ]],
            'refreshed' => userdate(time(), get_string('strftimedatetimeshort', 'core_langconfig')),
        ];
    }

    /**
     * Drill-down: Scenario -> Department -> User.
     *
     * @param array $path
     * @return array
     */
    public function level(array $path): array {
        $d = $this->data();
        $pop = $this->population();
        $path = array_slice($path, 0, 2);
        $levels = [self::s('an_lvl_scenario'), self::s('an_lvl_department'), self::s('an_lvl_user')];
        $crumbs = [];
        if ($path) {
            $crumbs[] = $d['scenarios'][(int)$path[0]]['name'] ?? $path[0];
        }
        if (count($path) > 1) {
            $crumbs[] = self::keyname($path[1]);
        }
        $info = $this->levelinfo($levels, $path, $crumbs);
        $zonemeta = function($a) {
            return [['label' => self::s('an_roleplay_users'), 'value' => $a['users']],
                ['label' => self::s('an_roleplay_attempts'), 'value' => $a['attempts']]];
        };

        if (!$path) {
            $per = [];
            foreach ($this->attempts($this->ws, $this->we) as $a) {
                $per[$a['sid']][] = $a;
            }
            $rows = [];
            foreach ($d['scenarios'] as $sid => $sc) {
                $a = $this->agg($per[$sid] ?? []);
                $rows[] = ['key' => (string)$sid, 'name' => $sc['name'], 'coursesub' => $sc['course'] ?
                    self::s('an_roleplay_coursesub', $sc['course']) : '', 'course' => $sc['course'], 'depts' => $a['depts'],
                    'users' => $a['users'], 'attempts' => $a['attempts'], 'avg' => $a['avg'], 'passrate' => $a['passrate']];
            }
            usort($rows, function($a, $b) {
                return $b['attempts'] <=> $a['attempts'];
            });
            $chart = array_map(function($r) use ($zonemeta) {
                return ['label' => $r['name'], 'value' => $r['avg'], 'meta' => $zonemeta($r)];
            }, $rows);
            return $info + [
                'blocks' => [self::zonechart(self::s('an_roleplay_scen_overview'), self::s('an_roleplay_zone_info'), $chart,
                    self::s('an_roleplay_avgscore'))],
                'table' => [
                    'title' => self::s('an_roleplay_scenarios'), 'info' => self::s('an_roleplay_scenarios_info'),
                    'columns' => [
                        self::col('name', 'an_roleplay_col_scenario', 'name'),
                        self::col('course', 'an_roleplay_col_course', 'text'),
                        self::col('depts', 'an_roleplay_col_depts'),
                        self::col('users', 'an_roleplay_col_users'),
                        self::col('attempts', 'an_roleplay_col_attempts'),
                        self::col('avg', 'an_roleplay_col_avg', 'pct'),
                        self::col('passrate', 'an_roleplay_col_passrate', 'pct'),
                        self::actioncol('next', 'lp_viewmore'),
                    ],
                    'rows' => $rows,
                    'filters' => [['field' => 'course', 'label' => self::s('an_roleplay_col_course')]],
                    'searchkeys' => ['name', 'course'],
                ],
            ];
        }

        $sid = (int)$path[0];
        $scenario = $d['scenarios'][$sid]['name'] ?? '';
        if (count($path) === 1) {
            $per = [];
            foreach ($this->attempts($this->ws, $this->we, function($a) use ($sid) {
                return $a['sid'] === $sid;
            }) as $a) {
                $per[self::gkey($pop[$a['uid']], 'department')][] = $a;
            }
            $rows = [];
            foreach ($per as $g => $list) {
                $a = $this->agg($list);
                $rows[] = ['key' => (string)$g, 'name' => self::keyname($g), 'users' => $a['users'],
                    'attempts' => $a['attempts'], 'avg' => $a['avg'], 'passrate' => $a['passrate']];
            }
            usort($rows, function($a, $b) {
                return $b['users'] <=> $a['users'];
            });
            $chart = array_map(function($r) use ($zonemeta) {
                return ['label' => $r['name'], 'value' => $r['avg'], 'meta' => $zonemeta($r)];
            }, $rows);
            return $info + [
                'blocks' => [self::zonechart(self::s('an_roleplay_dept_perf', $scenario), self::s('an_roleplay_zone_info'),
                    $chart, self::s('an_roleplay_avgscore'))],
                'table' => [
                    'title' => self::s('an_roleplay_bydept', $scenario), 'info' => self::s('an_roleplay_bydept_info'),
                    'columns' => [
                        self::col('name', 'lp_col_department', 'name'),
                        self::col('users', 'an_roleplay_col_users'),
                        self::col('attempts', 'an_roleplay_col_attempts'),
                        self::col('avg', 'an_roleplay_col_avg', 'pct'),
                        self::col('passrate', 'an_roleplay_col_passrate', 'pct'),
                        self::actioncol('next', 'lp_viewmore'),
                    ],
                    'rows' => $rows,
                ],
            ];
        }

        $dept = $path[1];
        $per = [];
        foreach ($this->attempts($this->ws, $this->we, function($a) use ($sid, $dept, $pop) {
            return $a['sid'] === $sid && self::gkey($pop[$a['uid']], 'department') === $dept;
        }) as $a) {
            $per[$a['uid']][] = $a;
        }
        $rows = [];
        foreach ($per as $uid => $list) {
            $a = $this->agg($list);
            $last = end($list);
            $rows[] = ['key' => (string)$uid, 'name' => $pop[$uid]->fullname, 'email' => $pop[$uid]->email,
                'designation' => $pop[$uid]->designation, 'attempts' => $a['attempts'], 'avg' => $a['avg'],
                'passrate' => $a['passrate'], 'latest' => $last['score'],
                'result' => self::s($last['passed'] ? 'an_roleplay_passed' : 'an_roleplay_failed'),
                'result_tone' => $last['passed'] ? 'att-present' : 'att-absent',
                'lastattempt' => userdate($last['time'], '%b %Y'), 'lastattempt_sort' => $last['time'],
                'lastattempt_d' => userdate($last['time'], '%b %Y')];
        }
        usort($rows, function($a, $b) {
            return ($b['avg'] ?? 0) <=> ($a['avg'] ?? 0);
        });
        $chartrows = array_slice($rows, 0, 15);
        $chart = array_map(function($r) {
            return ['label' => $r['name'], 'value' => $r['avg'],
                'meta' => [['label' => self::s('an_roleplay_col_latest'), 'value' => $r['latest'] === null ? 0 :
                    round($r['latest'])]]];
        }, $chartrows);
        return $info + [
            'blocks' => [self::zonechart(self::s('an_roleplay_userscores', self::keyname($dept)), self::s('an_roleplay_users_info'),
                $chart, self::s('an_roleplay_avgscore'))],
            'table' => [
                'title' => self::s('an_roleplay_usersof', $scenario . ' · ' . self::keyname($dept)),
                'info' => self::s('an_roleplay_users_table_info'),
                'columns' => [
                    self::col('name', 'lp_col_user', 'name'),
                    self::col('designation', 'lp_col_designation', 'text'),
                    self::col('attempts', 'an_roleplay_col_attempts'),
                    self::col('avg', 'an_roleplay_col_avg', 'pct'),
                    self::col('passrate', 'an_roleplay_col_passrate', 'pct'),
                    self::col('latest', 'an_roleplay_col_latest', 'pct'),
                    self::col('result', 'an_roleplay_col_result', 'badge'),
                    self::col('lastattempt', 'an_roleplay_col_last', 'disp', ['sortk' => 'lastattempt_sort']),
                    self::actioncol('detail', 'an_viewdetail'),
                ],
                'rows' => $rows,
                'filters' => [['field' => 'designation', 'label' => self::s('lp_col_designation')],
                    ['field' => 'result', 'label' => self::s('an_roleplay_col_result')],
                    ['field' => 'lastattempt', 'label' => self::s('an_roleplay_col_last')]],
                'searchkeys' => ['name', 'email'],
            ],
        ];
    }

    /**
     * Attempt-history popup.
     *
     * @param string $id
     * @param array $path
     * @param string $key
     * @return array
     */
    public function popup(string $id, array $path, string $key): array {
        $d = $this->data();
        $sid = (int)($path[0] ?? 0);
        $uid = (int)$key;
        $sc = $d['scenarios'][$sid] ?? null;
        if (!$sc) {
            throw new \moodle_exception('invalidrecord', 'error');
        }
        $list = $this->attempts($this->ws, $this->we, function($a) use ($sid, $uid) {
            return $a['sid'] === $sid && $a['uid'] === $uid;
        });
        $a = $this->agg($list);
        $last = $list ? end($list) : null;
        $rows = [];
        $i = 0;
        foreach ($list as $at) {
            $i++;
            $rows[] = [
                self::s('an_roleplay_attemptn', $at['n'] ?: $i),
                userdate($at['time'], '%d %b %Y'),
                $at['score'] === null ? '—' : self::p($at['score'], 0),
                self::badge(self::s($at['passed'] ? 'an_roleplay_passed' : 'an_roleplay_failed'),
                    $at['passed'] ? 'att-present' : 'att-absent'),
                $at['secs'] ? self::clock($at['secs']) : '—',
                $at['audio'] !== '' ? ['t' => self::clock($at['secs']),
                    'audio' => $at['audio']] : '—',
            ];
        }
        $rows = array_reverse($rows);
        $passed = $last && $last['passed'];
        return [
            'title' => self::s('an_roleplay_popup', ['user' => $this->population()[$uid]->fullname ?? '', 'scenario' => $sc['name']]),
            'desc' => self::s('an_roleplay_popup_desc', round($sc['pass'])),
            'profile' => $this->profilecard($uid),
            'stats' => [
                ['label' => self::s('an_roleplay_col_scenario'), 'value' => $sc['name'], 'icon' => 'scenario', 'small' => true],
                ['label' => self::s('an_roleplay_col_attempts'), 'value' => (string)$a['attempts'], 'icon' => 'attempts',
                    'tone' => 'info'],
                ['label' => self::s('an_roleplay_col_avg'), 'value' => self::p($a['avg'], 0), 'icon' => 'pct', 'tone' => 'accent'],
                ['label' => self::s('an_roleplay_col_passrate'), 'value' => self::p($a['passrate']), 'icon' => 'score',
                    'tone' => 'primary'],
                ['label' => self::s('an_roleplay_latestresult'), 'value' => self::s($passed ? 'an_roleplay_passed' :
                    'an_roleplay_failed'), 'icon' => $passed ? 'passed' : 'failed', 'tone' => $passed ? 'success' : 'destructive'],
            ],
            'table' => ['heads' => [
                ['label' => self::s('an_roleplay_attempt')], ['label' => self::s('td_f_date')],
                ['label' => self::s('an_roleplay_score')],
                ['label' => self::s('an_roleplay_col_result'), 'info' => self::s('an_roleplay_result_info')],
                ['label' => self::s('an_roleplay_duration')],
                ['label' => self::s('an_roleplay_audio'), 'info' => self::s('an_roleplay_audio_info')],
            ], 'rows' => $rows],
        ];
    }

    /**
     * Attempt duration: the stored duration, else completed - started when plausible (under 12 hours).
     *
     * @param int $stored
     * @param int $diff
     * @return int seconds
     */
    protected static function secs(int $stored, int $diff): int {
        if ($stored > 0) {
            return $stored;
        }
        return ($diff > 0 && $diff < 12 * HOURSECS) ? $diff : 0;
    }

    /**
     * mm:ss or h:mm:ss.
     *
     * @param int $secs
     * @return string
     */
    protected static function clock(int $secs): string {
        if ($secs >= HOURSECS) {
            return sprintf('%d:%02d:%02d', intdiv($secs, HOURSECS), intdiv($secs % HOURSECS, 60), $secs % 60);
        }
        return sprintf('%02d:%02d', intdiv($secs, 60), $secs % 60);
    }
}
