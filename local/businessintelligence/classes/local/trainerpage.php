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
 * Trainer Performance Analytics: Department -> Trainer, with the sessions-delivered popup.
 *
 * Deliveries come from two places:
 *  - LMS sessions (ILT / Zoom / Google Meet) with their "Select instructor" trainers. A Moodle trainer is External when
 *    the trainer-type profile field matches the guest pattern setting, Internal otherwise.
 *  - Training feedback uploaded on the Trainer Data page (local_bi_feedback): internal or external trainer, mode,
 *    date, duration, attendees and rating.
 * Ratings: feedback ratings (and the ILT trainer feedback table when it exists), out of 5.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class trainerpage extends analysis {
    /** @var array|null memo */
    protected $data = null;

    /**
     * KPI cards.
     *
     * @return array
     */
    public static function kpidefs(): array {
        return ['internal' => 'users', 'external' => 'user-cog', 'sessions' => 'layers', 'hours' => 'clock',
            'rating' => 'star'];
    }

    /**
     * Header links.
     *
     * @return array
     */
    public static function headerlinks(): array {
        if (!has_capability('local/businessintelligence:managedata', \context_system::instance())) {
            return [];
        }
        return [['url' => (new \moodle_url('/local/businessintelligence/trainerdata.php'))->out(false),
            'label' => self::s('td_manage'), 'icon' => 'upload']];
    }

    /**
     * Trainers and deliveries (memoised).
     *
     * @return array ['trainers' => [key => info], 'deliveries' => [...]]
     */
    public function data(): array {
        global $DB;
        if ($this->data !== null) {
            return $this->data;
        }
        $from = min($this->lower, $this->ws > 0 ? $this->ws : $this->lower);
        $trainers = [];
        $deliveries = [];

        // LMS sessions.
        $sessions = sessions::fetch($from, $this->we, true);
        $uids = [];
        foreach ($sessions as $s) {
            foreach ($s['trainers'] as $t) {
                $uids[(int)$t] = (int)$t;
            }
        }
        // Feedback rows.
        $fb = [];
        if (source::has_table('local_bi_feedback')) {
            $p = new params();
            $fb = $DB->get_records_sql("SELECT * FROM {local_bi_feedback}
                                         WHERE trainingdate >= " . $p->add($from) . " AND trainingdate <= " . $p->add(
                                             max($this->we, time() + 365 * DAYSECS)), $p->values);
            foreach ($fb as $r) {
                if ((int)$r->traineruserid) {
                    $uids[(int)$r->traineruserid] = (int)$r->traineruserid;
                }
            }
        }
        // Moodle trainers.
        if ($uids) {
            $users = $DB->get_records_list('user', 'id', array_values($uids), '',
                'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename, email, deleted');
            $guests = self::guest_trainers(array_values($uids));
            $dept = profile::values_for('department', array_values($uids));
            $desig = profile::values_for('designation', array_values($uids));
            foreach ($users as $u) {
                if ($u->deleted) {
                    continue;
                }
                $trainers['u' . $u->id] = [
                    'name' => fullname($u), 'email' => $u->email, 'external' => isset($guests[(int)$u->id]),
                    'dept' => trim((string)($dept[$u->id] ?? '')), 'designation' => trim((string)($desig[$u->id] ?? '')),
                ];
            }
        }
        foreach ($sessions as $s) {
            $keys = [];
            foreach ($s['trainers'] as $t) {
                if (isset($trainers['u' . $t])) {
                    $keys[] = 'u' . $t;
                }
            }
            if (!$keys) {
                continue;
            }
            $attended = 0;
            foreach ($s['att'] as $a) {
                if ($a['state'] === 'attended' || $a['state'] === 'partial') {
                    $attended++;
                }
            }
            $deliveries[] = ['id' => $s['id'], 'trainers' => $keys, 'name' => $s['name'], 'start' => (int)$s['start'],
                'hours' => round($s['secs'] / HOURSECS, 2), 'attended' => $attended, 'rating' => null,
                'mode' => $s['type'] === 'f2f' ? self::s('td_mode_classroom') : self::s('td_mode_virtual'),
                'dept' => '', 'feedback' => ''];
        }
        // External trainers.
        $ext = source::has_table('local_bi_ext_trainer') ?
            $DB->get_records('local_bi_ext_trainer', null, 'fullname', '*') : [];
        foreach ($ext as $x) {
            $trainers['x' . $x->id] = ['name' => self::txt($x->fullname), 'email' => $x->email, 'external' => true,
                'dept' => '', 'designation' => self::txt($x->designation),
                'organisation' => self::txt($x->organisation)];
        }
        foreach ($fb as $r) {
            $key = (int)$r->traineruserid ? 'u' . $r->traineruserid : 'x' . $r->extrainerid;
            if (!isset($trainers[$key])) {
                continue;
            }
            if ($key[0] === 'u' && $r->trainertype === 'external') {
                $trainers[$key]['external'] = true;
            }
            $deliveries[] = ['id' => 'fb-' . $r->id, 'trainers' => [$key], 'name' => self::txt($r->trainingname),
                'start' => (int)$r->trainingdate, 'hours' => round((int)$r->duration / 60, 2), 'attended' => (int)$r->attendees,
                'rating' => $r->rating === null ? null : (float)$r->rating,
                'mode' => self::s('td_mode_' . (in_array($r->mode, ['classroom', 'virtual', 'hybrid', 'onthejob']) ?
                    $r->mode : 'classroom')),
                'dept' => trim((string)$r->department), 'feedback' => (string)$r->feedback];
        }
        // Department of external trainers: the department they delivered to most.
        $votes = [];
        foreach ($deliveries as $d) {
            if ($d['dept'] === '') {
                continue;
            }
            foreach ($d['trainers'] as $k) {
                $votes[$k][$d['dept']] = ($votes[$k][$d['dept']] ?? 0) + 1;
            }
        }
        foreach ($trainers as $k => $t) {
            if ($t['dept'] === '' && !empty($votes[$k])) {
                arsort($votes[$k]);
                $trainers[$k]['dept'] = (string)array_key_first($votes[$k]);
            }
        }
        // ILT trainer feedback table ratings (per trainer, no session detail).
        $extra = $this->ilt_ratings(array_values($uids));
        // Population filters: department / designation narrow the trainers.
        foreach ($trainers as $k => $t) {
            $dk = $t['dept'] !== '' ? $t['dept'] : self::NONE;
            if ($this->f->department !== '' && $dk !== $this->f->department) {
                unset($trainers[$k]);
            } else if ($this->f->designation !== '' && $t['designation'] !== $this->f->designation) {
                unset($trainers[$k]);
            }
        }
        $this->data = ['trainers' => $trainers, 'deliveries' => $deliveries, 'iltratings' => $extra];
        return $this->data;
    }

    /**
     * Ratings from an ILT trainer feedback table, when the ILT plugin has one.
     *
     * @param int[] $uids
     * @return array key => [sum, n]
     */
    protected function ilt_ratings(array $uids): array {
        global $DB;
        if (!$uids) {
            return [];
        }
        $mod = clean_param((string)source::config('iltmodule', 'ilt'), PARAM_ALPHANUMEXT);
        foreach ([$mod . '_trainer_feedback', $mod . '_trainer_rating', $mod . '_feedback'] as $t) {
            if (source::has_table($t) && source::has_field($t, 'rating')
                    && (source::has_field($t, 'trainerid') || source::has_field($t, 'userid'))) {
                $col = source::has_field($t, 'trainerid') ? 'trainerid' : 'userid';
                $p = new params();
                $in = $p->in($uids);
                $out = [];
                foreach ($DB->get_records_sql("SELECT {$col} AS tid, SUM(rating) AS s, COUNT(1) AS n FROM {{$t}}
                                                WHERE {$col} {$in} GROUP BY {$col}", $p->values) as $r) {
                    $out['u' . $r->tid] = [(float)$r->s, (int)$r->n];
                }
                return $out;
            }
        }
        return [];
    }

    /**
     * Per-trainer totals for deliveries that started inside a range (and not in the future).
     *
     * @param int $from
     * @param int $to
     * @return array key => [sessions, hours, attended, rsum, rn, list]
     */
    protected function totals(int $from, int $to): array {
        $d = $this->data();
        $to = min($to, time());
        $out = [];
        foreach ($d['deliveries'] as $x) {
            if ($x['start'] < $from || $x['start'] > $to) {
                continue;
            }
            foreach ($x['trainers'] as $k) {
                if (!isset($d['trainers'][$k])) {
                    continue;
                }
                if (!isset($out[$k])) {
                    $out[$k] = ['sessions' => 0, 'hours' => 0.0, 'attended' => 0, 'rsum' => 0.0, 'rn' => 0];
                }
                $out[$k]['sessions']++;
                $out[$k]['hours'] += $x['hours'];
                $out[$k]['attended'] += $x['attended'];
                if ($x['rating'] !== null) {
                    $out[$k]['rsum'] += $x['rating'];
                    $out[$k]['rn']++;
                }
            }
        }
        foreach ($d['iltratings'] as $k => [$s, $n]) {
            if (isset($out[$k])) {
                $out[$k]['rsum'] += $s;
                $out[$k]['rn'] += $n;
            }
        }
        return $out;
    }

    /**
     * Headline numbers of a range.
     *
     * @param int $from
     * @param int $to
     * @return array
     */
    protected function headline(int $from, int $to): array {
        $d = $this->data();
        $t = $this->totals($from, $to);
        $h = ['internal' => 0, 'external' => 0, 'sessions' => 0, 'hours' => 0.0, 'rsum' => 0.0, 'rn' => 0];
        foreach ($t as $k => $v) {
            $d['trainers'][$k]['external'] ? $h['external']++ : $h['internal']++;
            $h['rsum'] += $v['rsum'];
            $h['rn'] += $v['rn'];
        }
        $seen = [];
        foreach ($d['deliveries'] as $x) {
            if ($x['start'] < $from || $x['start'] > min($to, time()) || isset($seen[$x['id']])) {
                continue;
            }
            if (array_intersect($x['trainers'], array_keys($d['trainers']))) {
                $seen[$x['id']] = true;
                $h['sessions']++;
                $h['hours'] += $x['hours'];
            }
        }
        $h['rating'] = $h['rn'] ? round($h['rsum'] / $h['rn'], 1) : 0;
        return $h;
    }

    /**
     * Average feedback rating of internal and external trainers in the window (dashboard card).
     *
     * @return array [internal|null, external|null]
     */
    public function typeratings(): array {
        $d = $this->data();
        $sum = [[0.0, 0], [0.0, 0]];
        foreach ($this->totals($this->ws, $this->we) as $k => $v) {
            $i = $d['trainers'][$k]['external'] ? 1 : 0;
            $sum[$i][0] += $v['rsum'];
            $sum[$i][1] += $v['rn'];
        }
        return [$sum[0][1] ? round($sum[0][0] / $sum[0][1], 1) : null, $sum[1][1] ? round($sum[1][0] / $sum[1][1], 1) : null];
    }

    /**
     * KPIs and overview charts.
     *
     * @return array
     */
    public function overview(): array {
        $cur = $this->headline($this->ws, $this->we);
        $prev = $this->prev ? $this->headline($this->prev[0], $this->prev[1]) : null;
        $spark = ['internal' => [], 'external' => [], 'sessions' => [], 'hours' => [], 'rating' => []];
        foreach ($this->buckets as $b) {
            $h = $this->headline($b['start'], $b['end']);
            foreach ($spark as $k => $unused) {
                $spark[$k][] = $h[$k];
            }
        }
        $kpis = [];
        foreach (['internal', 'external', 'sessions'] as $k) {
            $kpis[$k] = $this->kpi($cur[$k], $prev ? $prev[$k] : null, $spark[$k]);
        }
        $kpis['hours'] = $this->kpi(round($cur['hours']), $prev ? round($prev['hours']) : null, $spark['hours']);
        $kpis['rating'] = $this->kpi($cur['rating'], $prev && $prev['rn'] ? $prev['rating'] : null, $spark['rating']);
        $kpis['rating']['display'] = $cur['rn'] ? number_format($cur['rating'], 1) : '–';

        $d = $this->data();
        $t = $this->totals($this->ws, $this->we);
        // Rating distribution of trainers.
        $buckets = ['< 3.0' => 0, '3.0–3.4' => 0, '3.5–3.9' => 0, '4.0–4.4' => 0, '4.5–5.0' => 0];
        foreach ($t as $v) {
            if (!$v['rn']) {
                continue;
            }
            $r = $v['rsum'] / $v['rn'];
            $b = $r < 3 ? '< 3.0' : ($r < 3.5 ? '3.0–3.4' : ($r < 4 ? '3.5–3.9' : ($r < 4.5 ? '4.0–4.4' : '4.5–5.0')));
            $buckets[$b]++;
        }
        // Hours by trainer.
        uasort($t, function($a, $b) {
            return $b['hours'] <=> $a['hours'];
        });
        $hours = ['categories' => [], 'series' => [['label' => self::s('an_trainer_hours'), 'color' => 'chart1',
            'values' => []]]];
        foreach (array_slice($t, 0, 8, true) as $k => $v) {
            $hours['categories'][] = $d['trainers'][$k]['name'];
            $hours['series'][0]['values'][] = round($v['hours'], 1);
        }
        // Sessions by trainer type.
        $bytype = [0, 0];
        $seen = [];
        foreach ($d['deliveries'] as $x) {
            if ($x['start'] < $this->ws || $x['start'] > min($this->we, time()) || isset($seen[$x['id']])) {
                continue;
            }
            $ext = null;
            foreach ($x['trainers'] as $k) {
                if (isset($d['trainers'][$k])) {
                    $ext = $ext || $d['trainers'][$k]['external'];
                }
            }
            if ($ext === null) {
                continue;
            }
            $seen[$x['id']] = true;
            $bytype[$ext ? 1 : 0]++;
        }
        $s = function($k) {
            return self::s($k);
        };
        return [
            'kpis' => $kpis,
            'charts' => [
                ['title' => $s('an_trainer_chart_rating'), 'info' => $s('an_trainer_chart_rating_info'), 'chart' => 'vbar',
                    'height' => 270, 'data' => ['categories' => array_keys($buckets), 'series' => [
                        ['label' => $s('an_trainer_trainers'), 'color' => 'chart1', 'values' => array_values($buckets)]]],
                    'opts' => ['nolegend' => true, 'barwidth' => 22]],
                ['title' => $s('an_trainer_chart_hours'), 'info' => $s('an_trainer_chart_hours_info'), 'chart' => 'hbar',
                    'height' => 270, 'data' => $hours, 'opts' => []],
                ['title' => $s('an_trainer_chart_type'), 'info' => $s('an_trainer_chart_type_info'), 'chart' => 'vbar',
                    'height' => 270, 'data' => ['categories' => [$s('internal'), $s('external')], 'series' => [
                        ['label' => $s('an_trainer_sessions'), 'color' => 'chart2', 'values' => $bytype]]],
                    'opts' => ['nolegend' => true, 'barwidth' => 22]],
            ],
            'refreshed' => userdate(time(), get_string('strftimedatetimeshort', 'core_langconfig')),
        ];
    }

    /**
     * Drill-down levels: Department -> Trainers.
     *
     * @param array $path
     * @return array
     */
    public function level(array $path): array {
        $d = $this->data();
        $t = $this->totals($this->ws, $this->we);
        $levels = [self::s('an_lvl_department'), self::s('an_lvl_trainer')];
        $path = array_slice($path, 0, 1);
        $info = $this->levelinfo($levels, $path, array_map([self::class, 'keyname'], $path));
        $ratingopts = ['scale' => 5, 'decimals' => 1, 'suffix' => ' / 5', 'nodatalabel' => self::s('norating')];

        if (!$path) {
            $groups = [];
            foreach ($d['trainers'] as $k => $tr) {
                $g = $tr['dept'] !== '' ? $tr['dept'] : self::NONE;
                if (!isset($groups[$g])) {
                    $groups[$g] = ['trainers' => 0, 'sessions' => 0, 'hours' => 0.0, 'rsum' => 0.0, 'rn' => 0];
                }
                if (!isset($t[$k])) {
                    continue;
                }
                $groups[$g]['trainers']++;
                $groups[$g]['sessions'] += $t[$k]['sessions'];
                $groups[$g]['hours'] += $t[$k]['hours'];
                $groups[$g]['rsum'] += $t[$k]['rsum'];
                $groups[$g]['rn'] += $t[$k]['rn'];
            }
            $rows = [];
            foreach ($groups as $g => $v) {
                if (!$v['trainers']) {
                    continue;
                }
                $rows[] = ['key' => (string)$g, 'name' => self::keyname($g), 'trainers' => $v['trainers'],
                    'sessions' => $v['sessions'], 'hours' => round($v['hours'], 1),
                    'rating' => $v['rn'] ? round($v['rsum'] / $v['rn'], 1) : null];
            }
            usort($rows, function($a, $b) {
                return $b['sessions'] <=> $a['sessions'];
            });
            $chart = array_map(function($r) {
                return ['label' => $r['name'], 'value' => $r['rating'] === null ? null : $r['rating'] * 20,
                    'meta' => [['label' => self::s('an_trainer_trainers'), 'value' => $r['trainers']],
                        ['label' => self::s('an_trainer_sessions'), 'value' => $r['sessions']]]];
            }, $rows);
            return $info + [
                'blocks' => [self::zonechart(self::s('an_trainer_bydept_overview'), self::s('an_trainer_zone_info'), $chart,
                    self::s('an_trainer_avgrating'), $ratingopts)],
                'table' => [
                    'title' => self::s('an_trainer_bydept'), 'info' => self::s('an_trainer_bydept_info'),
                    'columns' => [
                        self::col('name', 'lp_col_department', 'name'),
                        self::col('trainers', 'an_trainer_trainers'),
                        self::col('sessions', 'an_trainer_sessions', 'num', ['link' => 'detail']),
                        self::col('hours', 'an_trainer_col_hours', 'dec'),
                        self::col('rating', 'an_trainer_avgrating', 'dec'),
                        self::actioncol('next', 'lp_viewmore'),
                    ],
                    'rows' => $rows,
                ],
            ];
        }

        $dept = $path[0];
        $rows = [];
        foreach ($d['trainers'] as $k => $tr) {
            $g = $tr['dept'] !== '' ? $tr['dept'] : self::NONE;
            if ($g !== $dept || !isset($t[$k])) {
                continue;
            }
            $v = $t[$k];
            $rows[] = ['key' => $k, 'name' => $tr['name'], 'designation' => $tr['designation'],
                'type' => self::s($tr['external'] ? 'external' : 'internal'), 'sessions' => $v['sessions'],
                'hours' => round($v['hours'], 1), 'users' => $v['attended'],
                'rating' => $v['rn'] ? round($v['rsum'] / $v['rn'], 1) : null];
        }
        usort($rows, function($a, $b) {
            return $b['sessions'] <=> $a['sessions'];
        });
        $chart = array_map(function($r) {
            return ['label' => $r['name'], 'value' => $r['rating'] === null ? null : $r['rating'] * 20,
                'meta' => [['label' => self::s('an_trainer_sessions'), 'value' => $r['sessions']],
                    ['label' => self::s('an_trainer_col_hours'), 'value' => $r['hours']]]];
        }, $rows);
        return $info + [
            'blocks' => [self::zonechart(self::s('an_trainer_trainers_overview', self::keyname($dept)),
                self::s('an_trainer_zone_info'), $chart, self::s('an_trainer_avgrating'), $ratingopts)],
            'table' => [
                'title' => self::s('an_trainer_trainersof', self::keyname($dept)), 'info' => self::s('an_trainer_trainers_info'),
                'columns' => [
                    self::col('name', 'an_trainer_col_trainer', 'name'),
                    self::col('designation', 'lp_col_designation', 'text'),
                    self::col('type', 'an_trainer_col_type', 'text'),
                    self::col('sessions', 'an_trainer_sessions', 'num', ['link' => 'detail']),
                    self::col('hours', 'an_trainer_col_hours', 'dec'),
                    self::col('users', 'an_trainer_col_users'),
                    self::col('rating', 'an_trainer_col_rating', 'dec'),
                    self::actioncol('detail', 'lp_viewdetail'),
                ],
                'rows' => $rows,
                'filters' => [['field' => 'type', 'label' => self::s('an_trainer_col_type')]],
            ],
        ];
    }

    /**
     * Sessions-delivered popup.
     *
     * @param string $id
     * @param array $path
     * @param string $key
     * @return array
     */
    public function popup(string $id, array $path, string $key): array {
        $d = $this->data();
        if (!isset($d['trainers'][$key])) {
            throw new \moodle_exception('invalidrecord', 'error');
        }
        $tr = $d['trainers'][$key];
        $t = $this->totals($this->ws, $this->we)[$key] ?? ['sessions' => 0, 'hours' => 0, 'attended' => 0, 'rn' => 0,
            'rsum' => 0];
        $rating = $t['rn'] ? round($t['rsum'] / $t['rn'], 1) : null;
        $rows = [];
        $now = time();
        $list = array_filter($d['deliveries'], function($x) use ($key) {
            return in_array($key, $x['trainers'], true) && $x['start'] >= $this->ws;
        });
        usort($list, function($a, $b) {
            return $b['start'] <=> $a['start'];
        });
        foreach ($list as $x) {
            $done = $x['start'] <= $now;
            $rows[] = [$x['name'], $x['mode'], userdate($x['start'], get_string('strftimedatefullshort', 'core_langconfig')),
                self::badge(self::s($done ? 'ts_ss_completed' : 'ts_ss_upcoming'), $done ? 'ss-completed' : 'ss-upcoming'),
                $x['rating'] === null ? '—' : number_format($x['rating'], 1), $done ? (string)$x['attended'] : '—'];
        }
        $meta = array_filter([self::s($tr['external'] ? 'an_trainer_external' : 'an_trainer_internal'), $tr['designation'],
            $tr['organisation'] ?? '', $rating === null ? '' : self::s('an_trainer_avgrating_is', number_format($rating, 1))]);
        return [
            'title' => self::s('an_trainer_popup', $tr['name']),
            'desc' => implode(' · ', $meta),
            'size' => 'lg',
            'stats' => [
                ['label' => self::s('an_trainer_sessions'), 'value' => (string)$t['sessions'], 'icon' => 'sessions'],
                ['label' => self::s('an_trainer_col_hours'), 'value' => (string)round($t['hours'], 1), 'icon' => 'hours',
                    'tone' => 'info'],
                ['label' => self::s('an_trainer_col_users'), 'value' => (string)$t['attended'], 'icon' => 'users',
                    'tone' => 'success'],
                ['label' => self::s('an_trainer_avgrating'), 'value' => $rating === null ? '–' : number_format($rating, 1) . ' / 5',
                    'icon' => 'rating', 'tone' => 'accent'],
            ],
            'table' => ['heads' => [
                ['label' => self::s('an_trainer_col_session')], ['label' => self::s('td_f_mode')],
                ['label' => self::s('td_f_date')], ['label' => self::s('ts_col_sessstatus')],
                ['label' => self::s('an_trainer_col_rating'), 'info' => self::s('an_trainer_col_rating_info')],
                ['label' => self::s('an_trainer_col_users')],
            ], 'rows' => $rows],
        ];
    }
}
