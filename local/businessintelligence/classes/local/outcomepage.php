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
 * Learning & Performance Intelligence: LMS learning performance next to external business performance (KPIs fed by
 * API or CSV into local_bi_kpi_value) before and after training. Department -> Designation -> User + outcome popup.
 *
 *  - Enrolled: learners with a course assignment in the period. Trained: learners with a course completion in the
 *    period; the latest completion is "the training" (course + training month).
 *  - Pre-training value of a KPI: average of the N months before the training month; post-training: average of the
 *    N months after it (N = "KPI comparison months" setting). Change % = (post - pre) / |pre|, sign flipped for KPIs
 *    where lower is better. A learner's performance change = average change of their KPIs.
 *  - Learning score: weighted average of the learner's component scores (quiz, assignment, AI role play, SCORM,
 *    OJT, skill gap) with the weights of the "Learning score weights" setting; components without data are skipped.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcomepage extends analysis {
    /** Learning score components => [lang key, source lang key]. */
    const COMPONENTS = ['assessment', 'assignment', 'roleplay', 'scorm', 'ojt', 'skillgap'];

    /** @var array|null memo */
    protected $data = null;

    /**
     * KPI cards.
     *
     * @return array
     */
    public static function kpidefs(): array {
        return ['trained' => 'graduation-cap', 'withdata' => 'database', 'change' => 'trending-up', 'positive' => 'users'];
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
        return [['url' => (new \moodle_url('/local/businessintelligence/trainerdata.php', ['tab' => 'kpi']))->out(false),
            'label' => self::s('td_kpi_upload'), 'icon' => 'upload']];
    }

    /**
     * Learning score weights from the setting ("assessment:40,assignment:20,...").
     *
     * @return array component => weight
     */
    public static function weights(): array {
        $w = array_fill_keys(self::COMPONENTS, 0);
        $raw = (string)source::config('lsweights', 'assessment:40,assignment:20,roleplay:20,scorm:0,ojt:20,skillgap:0');
        foreach (explode(',', $raw) as $part) {
            [$k, $v] = array_map('trim', explode(':', $part) + [1 => 0]);
            if (isset($w[$k])) {
                $w[$k] = max(0, (float)$v);
            }
        }
        return $w;
    }

    /**
     * Everything per learner (memoised).
     *
     * @return array uid => [enrolled, trained, course, courseid, ctime, scores => [comp => pct], score, kpis, change,
     *         outcome positive|negative|pending|nottrained]
     */
    public function data(): array {
        global $DB;
        if ($this->data !== null) {
            return $this->data;
        }
        $pop = $this->population();
        $out = [];
        // Enrolled (assignments in the period).
        $learning = new learning($this->f);
        foreach ($learning->rows() as [$uid]) {
            $out[$uid]['enrolled'] = true;
        }
        // Trained: latest completion in the period.
        $p = new params();
        $rs = $DB->get_recordset_sql("SELECT cc.id, cc.userid, cc.course, cc.timecompleted, c.fullname
                                        FROM {course_completions} cc
                                        JOIN {course} c ON c.id = cc.course
                                       WHERE cc.timecompleted >= " . $p->add(max(1, $this->ws)) . "
                                         AND cc.timecompleted <= " . $p->add($this->we) . "
                                    ORDER BY cc.timecompleted", $p->values);
        foreach ($rs as $r) {
            $uid = (int)$r->userid;
            if (!isset($pop[$uid])) {
                continue;
            }
            $out[$uid]['enrolled'] = true;
            $out[$uid]['trained'] = true;
            $out[$uid]['courseid'] = (int)$r->course;
            $out[$uid]['course'] = self::txt($r->fullname);
            $out[$uid]['ctime'] = (int)$r->timecompleted;
        }
        $rs->close();
        $trained = array_keys(array_filter($out, function($u) {
            return !empty($u['trained']);
        }));

        // Learning score components.
        $scores = $this->component_scores($out, $trained);
        $weights = self::weights();
        // External KPIs.
        $kpis = $this->kpi_values($trained);
        $months = max(1, (int)source::config('kpimonths', 2));
        foreach ($out as $uid => &$u) {
            $u += ['enrolled' => false, 'trained' => false, 'course' => '', 'courseid' => 0, 'ctime' => 0];
            $u['scores'] = $scores[$uid] ?? [];
            $sum = $wsum = 0;
            foreach ($weights as $c => $w) {
                if ($w > 0 && isset($u['scores'][$c])) {
                    $sum += $w * $u['scores'][$c];
                    $wsum += $w;
                }
            }
            $u['score'] = $wsum ? round($sum / $wsum, 1) : null;
            $u['kpis'] = [];
            $u['change'] = null;
            $u['outcome'] = $u['trained'] ? 'pending' : 'nottrained';
            if (!$u['trained'] || empty($kpis[$uid])) {
                continue;
            }
            $tm = date('Y-m', $u['ctime']);
            $before = $after = [];
            for ($i = 1; $i <= $months; $i++) {
                $before[] = date('Y-m', strtotime("-{$i} month", strtotime($tm . '-01')));
                $after[] = date('Y-m', strtotime("+{$i} month", strtotime($tm . '-01')));
            }
            $changes = [];
            foreach ($kpis[$uid] as $name => $k) {
                $pre = array_intersect_key($k['values'], array_flip($before));
                $post = array_intersect_key($k['values'], array_flip($after));
                $row = ['unit' => $k['unit'], 'source' => $k['source'], 'direction' => $k['direction'],
                    'pre' => $pre ? array_sum($pre) / count($pre) : null, 'post' => $post ? array_sum($post) / count($post) : null,
                    'change' => null];
                if ($row['pre'] !== null && $row['post'] !== null && (float)$row['pre'] != 0.0) {
                    $c = ($row['post'] - $row['pre']) * 100 / abs($row['pre']);
                    $row['change'] = round($k['direction'] === 'lower' ? -$c : $c, 1);
                    $changes[] = $row['change'];
                }
                $u['kpis'][$name] = $row;
            }
            if ($changes) {
                $u['change'] = round(array_sum($changes) / count($changes), 1);
                $u['outcome'] = $u['change'] > 0 ? 'positive' : 'negative';
            }
        }
        unset($u);
        $this->data = $out;
        return $out;
    }

    /**
     * Component scores (0-100) per trained learner.
     *
     * @param array $out
     * @param int[] $uids
     * @return array uid => [component => pct]
     */
    protected function component_scores(array $out, array $uids): array {
        global $DB;
        $res = [];
        if (!$uids) {
            return $res;
        }
        foreach (array_chunk($uids, 500) as $chunk) {
            // Gradebook: quiz / assign / scorm of the completed course.
            $p = new params();
            $in = $p->in($chunk);
            $rs = $DB->get_recordset_sql("SELECT gg.id, gg.userid, gi.courseid, gi.itemmodule,
                                                 gg.finalgrade, gg.rawgrademax, gg.rawgrademin
                                            FROM {grade_grades} gg
                                            JOIN {grade_items} gi ON gi.id = gg.itemid
                                           WHERE gi.itemtype = 'mod' AND gi.itemmodule IN ('quiz', 'assign', 'scorm')
                                             AND gg.finalgrade IS NOT NULL AND gg.userid {$in}", $p->values);
            $acc = [];
            foreach ($rs as $r) {
                $uid = (int)$r->userid;
                if ((int)$r->courseid !== ($out[$uid]['courseid'] ?? 0)) {
                    continue;
                }
                $range = (float)$r->rawgrademax - (float)$r->rawgrademin;
                if ($range <= 0) {
                    continue;
                }
                $c = ['quiz' => 'assessment', 'assign' => 'assignment', 'scorm' => 'scorm'][$r->itemmodule];
                $acc[$uid][$c][] = ((float)$r->finalgrade - (float)$r->rawgrademin) * 100 / $range;
            }
            $rs->close();
            // AI role play of the course.
            if (source::has_table('airoleplay_session') && source::has_table('airoleplay')) {
                $p = new params();
                $in = $p->in($chunk);
                $rs = $DB->get_recordset_sql("SELECT s.id, s.userid, a.course, s.score_overall
                                                FROM {airoleplay_session} s
                                                JOIN {airoleplay} a ON a.id = s.airoleplay
                                               WHERE s.status = " . $p->add('completed') . "
                                                 AND s.score_overall IS NOT NULL AND s.userid {$in}", $p->values);
                foreach ($rs as $r) {
                    $uid = (int)$r->userid;
                    if ((int)$r->course === ($out[$uid]['courseid'] ?? 0)) {
                        $acc[$uid]['roleplay'][] = (float)$r->score_overall;
                    }
                }
                $rs->close();
            }
            foreach ($acc as $uid => $comps) {
                foreach ($comps as $c => $vals) {
                    $res[$uid][$c] = round(array_sum($vals) / count($vals), 1);
                }
            }
        }
        foreach ((new ojtpage($this->f))->userpct($uids) as $uid => $v) {
            $res[$uid]['ojt'] = $v;
        }
        foreach ((new sgpage($this->f))->userpct($uids) as $uid => $v) {
            $res[$uid]['skillgap'] = $v;
        }
        return $res;
    }

    /**
     * External KPI values of learners.
     *
     * @param int[] $uids
     * @return array uid => kpi => [unit, source, direction, values => [period => value]]
     */
    protected function kpi_values(array $uids): array {
        global $DB;
        $out = [];
        if (!$uids || !source::has_table('local_bi_kpi_value')) {
            return $out;
        }
        foreach (array_chunk($uids, 500) as $chunk) {
            $p = new params();
            $in = $p->in($chunk);
            $rs = $DB->get_recordset_sql("SELECT id, userid, kpi, period, value, unit, direction, source
                                            FROM {local_bi_kpi_value} WHERE userid {$in} ORDER BY period, id", $p->values);
            foreach ($rs as $r) {
                $k = &$out[(int)$r->userid][$r->kpi];
                $k['unit'] = (string)$r->unit;
                $k['source'] = (string)$r->source;
                $k['direction'] = $r->direction === 'lower' ? 'lower' : 'higher';
                $k['values'][$r->period] = (float)$r->value;
                unset($k);
            }
            $rs->close();
        }
        return $out;
    }

    /**
     * Format a KPI value by its unit.
     *
     * @param float|null $v
     * @param string $unit
     * @return string
     */
    public static function fmtvalue(?float $v, string $unit): string {
        if ($v === null) {
            return '';
        }
        $u = \core_text::strtolower(trim($unit));
        if (in_array($u, ['₹', 'inr', 'rs', 'rupee', 'rupees', '₹ (inr)'], true)) {
            if (abs($v) >= 10000000) {
                return '₹' . round($v / 10000000, 1) . 'Cr';
            }
            if (abs($v) >= 100000) {
                return '₹' . round($v / 100000, 1) . 'L';
            }
            return '₹' . number_format($v);
        }
        if (in_array($u, ['%', 'percent', 'percentage'], true)) {
            return number_format($v, 1) . '%';
        }
        $dec = abs($v - round($v)) > 0.05 ? 1 : 0;
        return number_format($v, $dec) . (in_array($u, ['', 'count', 'number', 'nos'], true) ? '' : ' ' . $unit);
    }

    /**
     * Aggregate a set of learners.
     *
     * @param array $uids
     * @return array
     */
    protected function agg(array $uids): array {
        $d = $this->data();
        $a = ['enrolled' => 0, 'trained' => 0, 'withdata' => 0, 'pending' => 0, 'positive' => 0, 'ssum' => 0, 'sn' => 0,
            'csum' => 0, 'kpis' => []];
        foreach ($uids as $uid) {
            if (!isset($d[$uid])) {
                continue;
            }
            $u = $d[$uid];
            $a['enrolled'] += $u['enrolled'] ? 1 : 0;
            $a['trained'] += $u['trained'] ? 1 : 0;
            if ($u['score'] !== null && $u['trained']) {
                $a['ssum'] += $u['score'];
                $a['sn']++;
            }
            foreach (array_keys($u['kpis']) as $k) {
                $a['kpis'][$k] = true;
            }
            if ($u['change'] !== null) {
                $a['withdata']++;
                $a['csum'] += $u['change'];
                $a['positive'] += $u['change'] > 0 ? 1 : 0;
            } else if ($u['trained']) {
                $a['pending']++;
            }
        }
        return [
            'enrolled' => $a['enrolled'], 'trained' => $a['trained'], 'withdata' => $a['withdata'], 'pending' => $a['pending'],
            'score' => $a['sn'] ? round($a['ssum'] / $a['sn']) : null,
            'change' => $a['withdata'] ? round($a['csum'] / $a['withdata'], 1) : null,
            'positivepct' => $a['withdata'] ? round($a['positive'] * 100 / $a['withdata'], 1) : null,
            'positive' => $a['positive'], 'kpis' => array_keys($a['kpis']),
        ];
    }

    /**
     * KPIs and overview charts.
     *
     * @return array
     */
    public function overview(): array {
        $d = $this->data();
        $all = $this->agg(array_keys($d));
        $prev = null;
        if ($this->prev) {
            $o = new self($this->f);
            [$o->ws, $o->we] = $this->prev;
            $prev = $o->agg_all();
        }
        $kpis = [
            'trained' => $this->kpi($all['trained'], $prev ? $prev['trained'] : null, $this->spark('trained')),
            'withdata' => $this->kpi($all['withdata'], $prev ? $prev['withdata'] : null, $this->spark('withdata')),
            'change' => $this->kpi($all['change'] ?? 0, $prev ? $prev['change'] : null, $this->spark('change')),
            'positive' => $this->kpi($all['positivepct'] ?? 0, $prev ? $prev['positivepct'] : null, $this->spark('positivepct'),
                'pct'),
        ];
        $kpis['change']['display'] = $all['change'] === null ? '–' : (($all['change'] > 0 ? '+' : '') . self::p($all['change']));
        $kpis['positive']['display'] = self::p($all['positivepct']);

        $groups = [];
        foreach ($this->members() as $uid => $u) {
            $groups[self::gkey($u, 'department')][] = $uid;
        }
        $cats = $ls = $po = [];
        foreach ($groups as $g => $uids) {
            $a = $this->agg($uids);
            if (!$a['trained']) {
                continue;
            }
            $cats[] = self::keyname($g);
            $ls[] = $a['score'] ?? 0;
            $po[] = $a['positivepct'] ?? 0;
        }
        return [
            'kpis' => $kpis,
            'charts' => [
                ['title' => self::s('an_outcome_chart_scope'), 'info' => self::s('an_outcome_chart_scope_info'), 'chart' => 'funnel',
                    'height' => 290, 'data' => ['stages' => [
                        ['label' => self::s('an_outcome_f_enrolled'), 'value' => $all['enrolled']],
                        ['label' => self::s('an_outcome_f_trained'), 'value' => $all['trained']],
                        ['label' => self::s('an_outcome_f_data'), 'value' => $all['withdata']],
                        ['label' => self::s('an_outcome_f_positive'), 'value' => $all['positive']],
                    ]]],
                ['title' => self::s('an_outcome_chart_lms'), 'info' => self::s('an_outcome_chart_lms_info'), 'chart' => 'vbar',
                    'height' => 290, 'data' => ['categories' => $cats, 'series' => [
                        ['label' => self::s('an_outcome_avgscore'), 'color' => 'chart1', 'values' => $ls],
                        ['label' => self::s('an_outcome_positivepct'), 'color' => 'chart2', 'values' => $po],
                    ]], 'opts' => ['rotate' => count($cats) > 6]],
            ],
            'refreshed' => userdate(time(), get_string('strftimedatetimeshort', 'core_langconfig')),
        ];
    }

    /**
     * Totals of the whole population.
     *
     * @return array
     */
    public function agg_all(): array {
        return $this->agg(array_keys($this->data()));
    }

    /**
     * Sparkline of a total per bucket (trained by bucket end; others from completions in the bucket).
     *
     * @param string $k
     * @return array
     */
    protected function spark(string $k): array {
        $d = $this->data();
        $out = [];
        foreach ($this->buckets as $b) {
            $uids = array_keys(array_filter($d, function($u) use ($b) {
                return $u['trained'] && $u['ctime'] >= $b['start'] && $u['ctime'] <= $b['end'];
            }));
            $a = $this->agg($uids);
            $out[] = $a[$k] ?? 0;
        }
        return $out;
    }

    /**
     * Drill-down: Department -> Designation -> User.
     *
     * @param array $path
     * @return array
     */
    public function level(array $path): array {
        $path = array_slice($path, 0, 2);
        $levels = [self::s('an_lvl_department'), self::s('an_lvl_designation'), self::s('an_lvl_user')];
        $info = $this->levelinfo($levels, $path, array_map([self::class, 'keyname'], $path));
        $bands = ['lms' => ['label' => self::s('an_outcome_band_lms'), 'mark' => '▤'],
            'ext' => ['label' => self::s('an_outcome_band_ext'), 'mark' => '◆']];

        if (count($path) < 2) {
            $field = $path ? 'designation' : 'department';
            $groups = [];
            foreach ($this->members($path[0] ?? '') as $uid => $u) {
                $groups[self::gkey($u, $field)][] = $uid;
            }
            $rows = [];
            foreach ($groups as $g => $uids) {
                $a = $this->agg($uids);
                if (!$a['enrolled']) {
                    continue;
                }
                $rows[] = ['key' => (string)$g, 'name' => self::keyname($g), 'kpinames' => implode(' · ', $a['kpis']),
                    'enrolled' => $a['enrolled'], 'trained' => $a['trained'], 'score' => $a['score'],
                    'withdata' => $a['withdata'], 'pending' => $a['pending'], 'change' => $a['change'],
                    'positivepct' => $a['positivepct']];
            }
            usort($rows, function($a, $b) {
                return $b['enrolled'] <=> $a['enrolled'];
            });
            $chart = array_map(function($r) {
                return ['label' => $r['name'], 'value' => $r['positivepct'], 'meta' => [
                    ['label' => self::s('an_outcome_col_trained'), 'value' => $r['trained']],
                    ['label' => self::s('an_outcome_col_withdata'), 'value' => $r['withdata']]]];
            }, $rows);
            $title = $path ? self::s('lp_bydesig', self::keyname($path[0])) : self::s('an_outcome_bydept');
            $blocks = [self::zonechart($path ? self::s('an_outcome_scope_desig', self::keyname($path[0])) :
                self::s('an_outcome_scope_dept'), self::s('an_outcome_zone_info'), $chart, self::s('an_outcome_positivepct'))];
            $level = $info + [
                'blocks' => $blocks,
                'table' => [
                    'title' => $title, 'info' => self::s('an_outcome_table_info'), 'bands' => $bands,
                    'columns' => [
                        self::col('name', $path ? 'lp_col_designation' : 'lp_col_department', 'name', ['sub' => 'kpinames']),
                        self::col('enrolled', 'an_outcome_col_enrolled', 'num', ['band' => 'lms']),
                        self::col('trained', 'an_outcome_col_trained', 'num', ['band' => 'lms']),
                        self::col('score', 'an_outcome_col_score', 'num', ['band' => 'lms']),
                        self::col('withdata', 'an_outcome_col_withdata', 'num', ['band' => 'ext']),
                        self::col('pending', 'an_outcome_col_pending', 'num', ['band' => 'ext']),
                        self::col('change', 'an_outcome_col_change', 'signed', ['band' => 'ext']),
                        self::col('positivepct', 'an_outcome_positivepct', 'pct', ['band' => 'ext']),
                        self::actioncol('next', 'lp_viewmore'),
                    ],
                    'rows' => $rows,
                    'searchkeys' => ['name', 'kpinames'],
                ],
            ];
            if (!$path) {
                $level['after'] = [$this->feedblock()];
            }
            return $level;
        }

        // Users.
        $members = $this->members($path[0], $path[1]);
        $d = $this->data();
        $idnumbers = $this->idnumbers(array_keys($members));
        $kpinames = [];
        foreach (array_keys($members) as $uid) {
            foreach (array_keys($d[$uid]['kpis'] ?? []) as $k) {
                $kpinames[$k] = $d[$uid]['kpis'][$k]['unit'];
            }
        }
        ksort($kpinames);
        $columns = [
            self::col('name', 'lp_col_user', 'name', ['sub' => 'empid']),
            self::col('course', 'an_outcome_col_course', 'text'),
            self::col('period', 'an_outcome_col_period', 'disp', ['sortk' => 'ctime']),
            self::col('score', 'an_outcome_col_lscore', 'num', ['band' => 'lms']),
        ];
        $i = 0;
        foreach ($kpinames as $k => $unit) {
            $i++;
            $columns[] = ['k' => 'k' . $i . 'pre', 'label' => self::s('an_outcome_pre', $k), 'type' => 'disp', 'band' => 'ext',
                'info' => self::s('an_outcome_pre_info')];
            $columns[] = ['k' => 'k' . $i . 'post', 'label' => self::s('an_outcome_post', $k), 'type' => 'disp', 'band' => 'ext',
                'info' => self::s('an_outcome_post_info')];
            $columns[] = ['k' => 'k' . $i . 'chg', 'label' => self::s('an_outcome_chg', $k), 'type' => 'signed', 'band' => 'ext',
                'info' => self::s('an_outcome_col_change_info')];
        }
        $columns[] = self::col('outcomelabel', 'an_outcome_col_outcome', 'badge', ['band' => 'out']);
        $columns[] = self::actioncol('detail', 'an_viewdetail');
        $rows = [];
        $kpichange = [];
        foreach ($members as $uid => $u) {
            $x = $d[$uid] ?? null;
            if (!$x || !$x['enrolled']) {
                continue;
            }
            $row = ['key' => (string)$uid, 'name' => $u->fullname, 'email' => $u->email, 'empid' => $idnumbers[$uid] ?? '',
                'course' => $x['course'], 'ctime' => $x['ctime'], 'period' => $x['ctime'] ? userdate($x['ctime'], '%b %Y') : '',
                'period_d' => $x['ctime'] ? userdate($x['ctime'], '%b %Y') : '', 'score' => $x['score'],
                'outcomelabel' => self::s('an_outcome_o_' . $x['outcome']), 'outcomelabel_tone' => 'o-' . $x['outcome'],
                'outcome' => self::s('an_outcome_o_' . $x['outcome'])];
            $i = 0;
            foreach ($kpinames as $k => $unit) {
                $i++;
                $kv = $x['kpis'][$k] ?? null;
                $row['k' . $i . 'pre'] = $kv['pre'] ?? null;
                $row['k' . $i . 'pre_d'] = $kv ? self::fmtvalue($kv['pre'], $unit) : '';
                $row['k' . $i . 'post'] = $kv['post'] ?? null;
                $row['k' . $i . 'post_d'] = $kv ? self::fmtvalue($kv['post'], $unit) : '';
                $row['k' . $i . 'chg'] = $kv['change'] ?? null;
                if ($kv && $kv['change'] !== null) {
                    $kpichange[$k][] = $kv['change'];
                }
            }
            $rows[] = $row;
        }
        usort($rows, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        $cats = $vals = [];
        foreach ($kpichange as $k => $list) {
            $cats[] = $k;
            $vals[] = round(array_sum($list) / count($list), 1);
        }
        return $info + [
            'blocks' => [['type' => 'chart', 'title' => self::s('an_outcome_kpichart'), 'info' => self::s('an_outcome_kpichart_info'),
                'chart' => 'vbar', 'height' => 270, 'data' => ['categories' => $cats, 'series' => [
                    ['label' => self::s('an_outcome_col_change'), 'color' => 'chart2', 'values' => $vals]]],
                'opts' => ['nolegend' => true, 'barwidth' => 22]]],
            'table' => [
                'title' => self::s('lp_byusers', self::keyname($path[0]) . ' · ' . self::keyname($path[1])),
                'subtitle' => $kpinames ? self::s('an_outcome_configured', implode(', ', array_keys($kpinames))) : '',
                'info' => self::s('an_outcome_users_info'), 'bands' => $bands + ['out' => ['label' => '', 'mark' => '']],
                'columns' => $columns, 'rows' => $rows,
                'filters' => [['field' => 'course', 'label' => self::s('an_outcome_col_course')],
                    ['field' => 'period', 'label' => self::s('an_outcome_col_period')],
                    ['field' => 'outcome', 'label' => self::s('an_outcome_col_outcome')]],
                'searchkeys' => ['name', 'email', 'empid', 'course'],
                'wide' => true,
            ],
        ];
    }

    /**
     * Employee ids of learners (user ID number, else username).
     *
     * @param int[] $uids
     * @return array
     */
    protected function idnumbers(array $uids): array {
        global $DB;
        if (!$uids) {
            return [];
        }
        $out = [];
        foreach (array_chunk($uids, 1000) as $chunk) {
            foreach ($DB->get_records_list('user', 'id', $chunk, '', 'id, idnumber, username') as $u) {
                $out[(int)$u->id] = $u->idnumber !== '' ? $u->idnumber : $u->username;
            }
        }
        return $out;
    }

    /**
     * "External data feed" block: the latest KPI records.
     *
     * @return array
     */
    protected function feedblock(): array {
        global $DB;
        $rows = [];
        if (source::has_table('local_bi_kpi_value')) {
            foreach ($DB->get_records('local_bi_kpi_value', null, 'timecreated DESC, id DESC', '*', 0, 8) as $r) {
                $rows[] = [$r->employeeid, $r->kpi, $r->period, self::fmtvalue((float)$r->value, (string)$r->unit),
                    $r->source ?: '—'];
            }
        }
        return ['type' => 'table', 'tone' => 'ext', 'title' => self::s('an_outcome_feed'),
            'info' => self::s('an_outcome_feed_info', max(1, (int)source::config('kpimonths', 2))),
            'heads' => [['label' => self::s('an_outcome_empid')], ['label' => self::s('an_outcome_kpi')],
                ['label' => self::s('an_outcome_periodcol')], ['label' => self::s('an_outcome_value')],
                ['label' => self::s('an_outcome_source')]],
            'rows' => $rows];
    }

    /**
     * Training outcome popup.
     *
     * @param string $id
     * @param array $path
     * @param string $key
     * @return array
     */
    public function popup(string $id, array $path, string $key): array {
        $uid = (int)$key;
        $d = $this->data();
        $x = $d[$uid] ?? null;
        if (!$x) {
            throw new \moodle_exception('invaliduser');
        }
        $profile = $this->profilecard($uid);
        $emp = $this->idnumbers([$uid])[$uid] ?? '';
        $profile['initials'] = '';
        $mgr = $this->managernames([$uid])[$uid] ?? '';
        $profile['meta'] = implode(' · ', array_filter([$emp, $profile['meta'],
            $mgr ? self::s('lp_reportingmanager', $mgr) : '']));
        $profile['lines'] = $x['course'] ? [['label' => self::s('an_outcome_coursecolon'), 'value' => $x['course'],
            'after' => $x['ctime'] ? '· ' . self::s('an_outcome_completedon', userdate($x['ctime'], '%Y-%m-%d', 99, false)) : '']] : [];
        $profile['email'] = '';
        $profile['manager'] = '';
        $weights = self::weights();
        $comprows = [];
        foreach (self::COMPONENTS as $c) {
            $w = $weights[$c];
            $sc = $x['scores'][$c] ?? null;
            $contribution = ($w > 0 && $sc !== null && array_sum($weights) > 0) ? round($sc * $w / 100, 1) : null;
            $comprows[] = [self::s('an_outcome_comp_' . $c), self::s('an_outcome_src_' . $c),
                $w > 0 ? round($w) . '%' : self::s('an_outcome_disabled'), $sc === null ? '—' : (string)round($sc),
                $contribution === null ? '—' : (string)$contribution];
        }
        $kpirows = [];
        foreach ($x['kpis'] as $k => $kv) {
            $kpirows[] = [['t' => $k, 'bold' => true], $kv['unit'] ?: '—', $kv['source'] ?: '—',
                $kv['pre'] === null ? '—' : self::fmtvalue($kv['pre'], $kv['unit']),
                $kv['post'] === null ? '—' : self::fmtvalue($kv['post'], $kv['unit']),
                $kv['change'] === null ? '—' : ['t' => ($kv['change'] > 0 ? '+' : '') . number_format($kv['change'], 1) . '%',
                    'tone' => $kv['change'] > 0 ? 'up' : ($kv['change'] < 0 ? 'down' : '')]];
        }
        $outcome = $x['outcome'];
        return [
            'title' => self::s('an_outcome_popup', $profile['name']),
            'desc' => implode(' · ', array_filter([$x['course'], $x['ctime'] ? userdate($x['ctime'], '%b %Y') : ''])),
            'profile' => $profile,
            'stats' => [
                ['label' => self::s('an_outcome_col_lscore'), 'value' => $x['score'] === null ? '–' : (string)round($x['score']),
                    'icon' => 'score'],
                ['label' => self::s('an_outcome_col_change'), 'value' => $x['change'] === null ? '–' :
                    (($x['change'] > 0 ? '+' : '') . number_format($x['change'], 1) . '%'), 'icon' => 'trend',
                    'tone' => $x['change'] === null ? 'muted' : ($x['change'] > 0 ? 'success' : 'destructive')],
                ['label' => self::s('an_outcome_kpistracked'), 'value' => (string)count($x['kpis']), 'icon' => 'pct',
                    'tone' => 'info'],
                ['label' => self::s('an_outcome_col_outcome'), 'value' => self::s('an_outcome_o_' . $outcome),
                    'icon' => $outcome === 'positive' ? 'passed' : ($outcome === 'negative' ? 'failed' : 'inprogress'),
                    'tone' => $outcome === 'positive' ? 'success' : ($outcome === 'negative' ? 'destructive' : 'accent')],
            ],
            'sections' => [
                ['title' => self::s('an_outcome_band_lms'), 'mark' => '▤', 'tone' => 'lms',
                    'lead' => ['value' => $x['score'] === null ? '–' : (string)round($x['score']),
                        'label' => self::s('an_outcome_overall'), 'info' => self::s('an_outcome_overall_info')],
                    'table' => ['heads' => [['label' => self::s('an_outcome_component')], ['label' => self::s('an_outcome_source')],
                        ['label' => self::s('an_outcome_weightage')], ['label' => self::s('an_roleplay_score')],
                        ['label' => self::s('an_outcome_contribution'), 'info' => self::s('an_outcome_contribution_info')]],
                        'rows' => $comprows]],
                ['title' => self::s('an_outcome_band_ext'), 'mark' => '◆', 'tone' => 'ext',
                    'table' => ['heads' => [['label' => self::s('an_outcome_kpi')], ['label' => self::s('an_outcome_unit')],
                        ['label' => self::s('an_outcome_source')], ['label' => self::s('an_outcome_pretraining')],
                        ['label' => self::s('an_outcome_posttraining')],
                        ['label' => self::s('an_outcome_col_change'), 'info' => self::s('an_outcome_col_change_info')]],
                        'rows' => $kpirows],
                    'note' => $kpirows ? '' : self::s('an_outcome_nokpi')],
            ],
        ];
    }
}
