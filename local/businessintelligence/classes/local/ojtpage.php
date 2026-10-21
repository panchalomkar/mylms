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
 * Competency Assessment Analytics (OJT, local_competency): Department -> Designation -> Competency -> Users,
 * with the per-learner OJT competency report popup.
 *
 * Hierarchy: competency_title (Main) -> competency_category (Sub) -> competencies (Sub-Sub).
 * A learner's assignment is a competency_users row (one per Sub-Sub, or per Sub when it has no Sub-Sub) for a year.
 * Final Rating of an assignment, latest term of the period: the approved L&D rating (landd_rating, landdstatus = 1),
 * else the manager final rating, else the manager rating. Sub = average of its Sub-Subs, Main = average of its Subs.
 * Zone: Green from the "OJT green from" setting, Yellow from the "OJT yellow from" setting, Red below.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ojtpage extends analysis {
    /** @var array|null memo */
    protected $data = null;

    /**
     * KPI cards.
     *
     * @return array
     */
    public static function kpidefs(): array {
        return ['total' => 'target', 'users' => 'users', 'assessed' => 'shield-check', 'competent' => 'circle-check'];
    }

    /**
     * Zone of a rating.
     *
     * @param float|null $r
     * @return string red|yellow|green|''
     */
    public static function zone(?float $r): string {
        if ($r === null) {
            return '';
        }
        $green = (float)source::config('ojtgreen', 3);
        $yellow = (float)source::config('ojtyellow', 2);
        return $r >= $green ? 'green' : ($r >= $yellow ? 'yellow' : 'red');
    }

    /**
     * Years covered by a time range.
     *
     * @param int $from
     * @param int $to
     * @return array|null null = all years
     */
    protected static function years(int $from, int $to): ?array {
        if ($from <= 0) {
            return null;
        }
        return range((int)date('Y', $from), (int)date('Y', $to));
    }

    /**
     * Competency tree and every rated assignment (memoised).
     *
     * @return array ['mains', 'subs', 'subsubs', 'rows' => [[uid, year, term, mainid, subid, subsubid, rating]]]
     */
    public function data(): array {
        global $DB;
        if ($this->data !== null) {
            return $this->data;
        }
        $this->data = ['mains' => [], 'subs' => [], 'subsubs' => [], 'rows' => []];
        foreach (['competency_title', 'competency_category', 'competencies', 'competency_users'] as $t) {
            if (!source::has_table($t)) {
                return $this->data;
            }
        }
        $mains = $subs = $subsubs = [];
        foreach ($DB->get_records('competency_title', ['isdeleted' => 0], 'id', 'id, title, timecreated') as $r) {
            $mains[(int)$r->id] = ['name' => self::txt($r->title), 'created' => (int)$r->timecreated];
        }
        foreach ($DB->get_records('competency_category', ['isdeleted' => 0], 'id', 'id, name, ctid') as $r) {
            if (isset($mains[(int)$r->ctid])) {
                $subs[(int)$r->id] = ['name' => self::txt($r->name), 'main' => (int)$r->ctid];
            }
        }
        foreach ($DB->get_records('competencies', ['isdeleted' => 0], 'id', 'id, comptencyname, ccid') as $r) {
            if (isset($subs[(int)$r->ccid])) {
                $subsubs[(int)$r->id] = ['name' => self::txt($r->comptencyname), 'sub' => (int)$r->ccid];
            }
        }
        // Ratings per assignment (master id) and term.
        $ratings = [];
        if (source::has_table('manager_rating')) {
            foreach ($DB->get_recordset_sql("SELECT id, master_competencyid AS mid, tearms, rating, finalrating
                                               FROM {manager_rating}") as $r) {
                $v = (float)$r->finalrating > 0 ? (float)$r->finalrating : (float)$r->rating;
                if ($v > 0) {
                    $ratings[(int)$r->mid][(int)$r->tearms]['m'] = $v;
                }
            }
        }
        if (source::has_table('landd_rating')) {
            foreach ($DB->get_recordset_sql("SELECT id, master_competencyid AS mid, tearms, rating, landdstatus
                                               FROM {landd_rating} WHERE rating > 0") as $r) {
                if ((int)$r->landdstatus === 1) {
                    $ratings[(int)$r->mid][(int)$r->tearms]['l'] = (float)$r->rating;
                }
            }
        }
        $pop = $this->population();
        $rows = [];
        $rs = $DB->get_recordset_sql("SELECT id, userid, ctid, competencyid, subcompetencyid, year FROM {competency_users}");
        foreach ($rs as $r) {
            $uid = (int)$r->userid;
            if (!isset($pop[$uid]) || empty($ratings[(int)$r->id]) || !isset($mains[(int)$r->ctid])) {
                continue;
            }
            $terms = $ratings[(int)$r->id];
            krsort($terms);
            foreach ($terms as $term => $v) {
                $rows[] = [$uid, (int)$r->year, (int)$term, (int)$r->ctid, (int)$r->competencyid, (int)$r->subcompetencyid,
                    $v['l'] ?? $v['m']];
                break;
            }
        }
        $rs->close();
        $this->data = ['mains' => $mains, 'subs' => $subs, 'subsubs' => $subsubs, 'rows' => $rows];
        return $this->data;
    }

    /**
     * Final ratings per learner and competency node for a range of years.
     *
     * @param array|null $years
     * @param array|null $members userid => x
     * @return array uid => ['m:ID' => rating, 's:ID' => rating, 'x:ID' => rating]
     */
    protected function ratings(?array $years, ?array $members = null): array {
        $d = $this->data();
        // Latest (year, term) per user and leaf.
        $leaf = [];
        foreach ($d['rows'] as [$uid, $year, $term, $main, $sub, $subsub, $rating]) {
            if (($years !== null && !in_array($year, $years, true)) || ($members !== null && !isset($members[$uid]))) {
                continue;
            }
            $k = $subsub && isset($d['subsubs'][$subsub]) ? 'x:' . $subsub : 's:' . $sub;
            $stamp = $year * 10 + $term;
            if (!isset($leaf[$uid][$k]) || $leaf[$uid][$k][0] <= $stamp) {
                $leaf[$uid][$k] = [$stamp, $rating, $main, $sub];
            }
        }
        $out = [];
        foreach ($leaf as $uid => $items) {
            $subvals = $mainvals = [];
            foreach ($items as $k => [$stamp, $rating, $main, $sub]) {
                $out[$uid][$k] = $rating;
                if ($k[0] === 'x') {
                    $subvals[$sub][] = $rating;
                } else {
                    $subvals[$sub] = [$rating];
                }
            }
            foreach ($subvals as $sub => $vals) {
                $avg = round(array_sum($vals) / count($vals), 1);
                $out[$uid]['s:' . $sub] = $avg;
                $main = $this->data()['subs'][$sub]['main'] ?? null;
                if ($main) {
                    $mainvals[$main][] = $avg;
                }
            }
            foreach ($mainvals as $main => $vals) {
                $out[$uid]['m:' . $main] = round(array_sum($vals) / count($vals), 1);
            }
        }
        return $out;
    }

    /**
     * Average OJT final rating per learner as a percentage of the rating scale (for the learning score).
     *
     * @param array $uids
     * @return array uid => pct
     */
    public function userpct(array $uids): array {
        $max = max(1, (float)source::config('ojtmax', 4));
        $out = [];
        foreach ($this->ratings(self::years($this->ws, $this->we), array_flip($uids)) as $uid => $nodes) {
            $vals = array_filter($nodes, function($k) {
                return $k[0] === 'm';
            }, ARRAY_FILTER_USE_KEY);
            if ($vals) {
                $out[$uid] = round(array_sum($vals) / count($vals) * 100 / $max, 1);
            }
        }
        return $out;
    }

    /**
     * Node info.
     *
     * @param string $k m:ID|s:ID|x:ID
     * @return array name, type, depth, main
     */
    protected function node(string $k): array {
        $d = $this->data();
        [$t, $id] = explode(':', $k) + [1 => 0];
        $id = (int)$id;
        if ($t === 'm') {
            return ['name' => $d['mains'][$id]['name'] ?? $k, 'type' => self::s('an_ojt_main'), 'depth' => 0,
                'main' => $d['mains'][$id]['name'] ?? '', 'mainid' => $id, 'order' => [$id, 0, 0]];
        }
        if ($t === 's') {
            $main = $d['subs'][$id]['main'] ?? 0;
            return ['name' => $d['subs'][$id]['name'] ?? $k, 'type' => self::s('an_ojt_sub'), 'depth' => 1,
                'main' => $d['mains'][$main]['name'] ?? '', 'mainid' => $main, 'order' => [$main, $id, 0]];
        }
        $sub = $d['subsubs'][$id]['sub'] ?? 0;
        $main = $d['subs'][$sub]['main'] ?? 0;
        return ['name' => $d['subsubs'][$id]['name'] ?? $k, 'type' => self::s('an_ojt_subsub'), 'depth' => 2,
            'main' => $d['mains'][$main]['name'] ?? '', 'mainid' => $main, 'order' => [$main, $sub, $id]];
    }

    /**
     * Sort node keys in tree order.
     *
     * @param array $keys
     * @return array
     */
    protected function treeorder(array $keys): array {
        usort($keys, function($a, $b) {
            return $this->node($a)['order'] <=> $this->node($b)['order'];
        });
        return $keys;
    }

    /**
     * Zone counts per node.
     *
     * @param array $ratings uid => node => rating
     * @param string $prefix only nodes starting with this ('' = all)
     * @return array node => [red, yellow, green, users]
     */
    protected function zonecounts(array $ratings, string $prefix = ''): array {
        $out = [];
        foreach ($ratings as $uid => $nodes) {
            foreach ($nodes as $k => $r) {
                if ($prefix !== '' && strpos($k, $prefix) !== 0) {
                    continue;
                }
                if (!isset($out[$k])) {
                    $out[$k] = ['red' => 0, 'yellow' => 0, 'green' => 0, 'users' => 0];
                }
                $out[$k][self::zone($r)]++;
                $out[$k]['users']++;
            }
        }
        return $out;
    }

    /**
     * Share of green ratings (all nodes) of a rating set.
     *
     * @param array $ratings
     * @return float|null
     */
    protected static function greenshare(array $ratings): ?float {
        $g = $n = 0;
        foreach ($ratings as $nodes) {
            foreach ($nodes as $r) {
                $n++;
                $g += self::zone($r) === 'green' ? 1 : 0;
            }
        }
        return $n ? round($g * 100 / $n, 1) : null;
    }

    /**
     * Headline numbers.
     *
     * @param array|null $years
     * @return array
     */
    protected function headline(?array $years): array {
        $r = $this->ratings($years);
        $z = $this->zonecounts($r, 'm:');
        $competent = 0;
        foreach ($z as $c) {
            if ($c['users'] && $c['green'] * 2 >= $c['users']) {
                $competent++;
            }
        }
        return ['users' => count($r), 'assessed' => count($z), 'competent' => $competent];
    }

    /**
     * KPIs and overview chart.
     *
     * @return array
     */
    public function overview(): array {
        $d = $this->data();
        $years = self::years($this->ws, $this->we);
        $cur = $this->headline($years);
        $prev = $this->prev ? $this->headline(self::years($this->prev[0], $this->prev[1])) : null;
        $spark = ['total' => [], 'users' => [], 'assessed' => [], 'competent' => []];
        $cache = [];
        foreach ($this->buckets as $b) {
            $y = (int)date('Y', $b['end']);
            $cache[$y] = $cache[$y] ?? $this->headline([$y]);
            $spark['total'][] = count(array_filter($d['mains'], function($m) use ($b) {
                return $m['created'] <= $b['end'];
            }));
            foreach (['users', 'assessed', 'competent'] as $k) {
                $spark[$k][] = $cache[$y][$k];
            }
        }
        $kpis = [
            'total' => $this->kpi(count($d['mains']), null, $spark['total']),
            'users' => $this->kpi($cur['users'], $prev ? $prev['users'] : null, $spark['users']),
            'assessed' => $this->kpi($cur['assessed'], $prev ? $prev['assessed'] : null, $spark['assessed']),
            'competent' => $this->kpi($cur['competent'], $prev ? $prev['competent'] : null, $spark['competent']),
        ];
        $z = $this->zonecounts($this->ratings($years), 'm:');
        $chart = ['categories' => [], 'series' => [
            ['label' => self::s('zone_red'), 'color' => 'destructive', 'values' => []],
            ['label' => self::s('zone_yellow'), 'color' => 'accent', 'values' => []],
            ['label' => self::s('zone_green'), 'color' => 'success', 'values' => []],
        ]];
        foreach ($z as $k => $c) {
            $chart['categories'][] = $this->node($k)['name'];
            $chart['series'][0]['values'][] = $c['red'];
            $chart['series'][1]['values'][] = $c['yellow'];
            $chart['series'][2]['values'][] = $c['green'];
        }
        return [
            'kpis' => $kpis,
            'charts' => [[
                'title' => self::s('an_ojt_chart_zones'), 'info' => self::s('an_ojt_chart_zones_info'), 'chart' => 'hbar',
                'height' => max(240, count($z) * 36 + 70), 'data' => $chart, 'opts' => ['labelw' => 160],
                'sorts' => [['label' => self::s('zone_red'), 'series' => 0], ['label' => self::s('zone_yellow'), 'series' => 1],
                    ['label' => self::s('zone_green'), 'series' => 2], ['label' => self::s('an_ojt_totalassessed'),
                    'series' => -1]],
            ]],
            'refreshed' => userdate(time(), get_string('strftimedatetimeshort', 'core_langconfig')),
        ];
    }

    /**
     * Drill-down: Department -> Designation -> Competency -> Users.
     *
     * @param array $path
     * @return array
     */
    public function level(array $path): array {
        $years = self::years($this->ws, $this->we);
        $path = array_slice($path, 0, 3);
        $levels = [self::s('an_lvl_department'), self::s('an_lvl_designation'), self::s('an_lvl_competency'),
            self::s('an_lvl_userdetail')];
        $crumbs = [];
        foreach ($path as $i => $p) {
            $crumbs[] = $i < 2 ? self::keyname($p) : $this->crumbname($p);
        }
        $info = $this->levelinfo($levels, $path, $crumbs);
        $zonemeta = function($users, $comps) {
            return [['label' => self::s('an_ojt_users'), 'value' => $users],
                ['label' => self::s('an_ojt_competencies'), 'value' => $comps]];
        };
        $greenlabel = self::s('an_ojt_greenshare');

        if (!$path) {
            $groups = [];
            foreach ($this->members() as $uid => $u) {
                $groups[self::gkey($u, 'department')][$uid] = $u;
            }
            $rows = [];
            foreach ($groups as $g => $m) {
                $r = $this->ratings($years, $m);
                if (!$r) {
                    continue;
                }
                $desigs = [];
                foreach (array_keys($r) as $uid) {
                    $desigs[self::gkey($m[$uid], 'designation')] = true;
                }
                $rows[] = ['key' => (string)$g, 'name' => self::keyname($g), 'designations' => count($desigs),
                    'competencies' => count($this->zonecounts($r, 'm:')), 'users' => count($r),
                    'green' => self::greenshare($r)];
            }
            usort($rows, function($a, $b) {
                return $b['users'] <=> $a['users'];
            });
            $chart = array_map(function($r) use ($zonemeta) {
                return ['label' => $r['name'], 'value' => $r['green'], 'meta' => $zonemeta($r['users'], $r['competencies'])];
            }, $rows);
            return $info + [
                'blocks' => [self::zonechart(self::s('an_ojt_bydept_overview'), self::s('an_ojt_zone_info'), $chart,
                    $greenlabel)],
                'table' => [
                    'title' => self::s('an_ojt_bydept'), 'info' => self::s('an_ojt_bydept_info'),
                    'columns' => [
                        self::col('name', 'lp_col_department', 'name'),
                        self::col('designations', 'an_ojt_col_designations', 'num', ['tone' => 'link']),
                        self::col('competencies', 'an_ojt_col_competencies'),
                        self::col('users', 'an_ojt_col_users'),
                        self::col('green', 'an_ojt_greenshare', 'pct'),
                        self::actioncol('next', 'lp_viewmore'),
                    ],
                    'rows' => $rows,
                ],
            ];
        }

        $dept = $path[0];
        if (count($path) === 1) {
            $members = $this->members($dept);
            $all = $this->ratings($years, $members);
            // Department competency analysis (main competencies).
            $brows = [];
            foreach ($this->treeorder(array_keys($this->zonecounts($all, 'm:'))) as $k) {
                $c = $this->zonecounts($all, 'm:')[$k];
                $brows[] = ['c' => [$this->node($k)['name'], $this->node($k)['type'], ['t' => (string)$c['red'], 'tone' => 'red'],
                    ['t' => (string)$c['yellow'], 'tone' => 'yellow'], ['t' => (string)$c['green'], 'tone' => 'green']]];
            }
            $groups = [];
            foreach ($members as $uid => $u) {
                $groups[self::gkey($u, 'designation')][$uid] = $u;
            }
            $rows = [];
            foreach ($groups as $g => $m) {
                $r = $this->ratings($years, $m);
                if (!$r) {
                    continue;
                }
                $nodes = [];
                foreach ($r as $items) {
                    $nodes += $items;
                }
                $count = ['m' => 0, 's' => 0, 'x' => 0];
                foreach (array_keys($nodes) as $k) {
                    $count[$k[0]]++;
                }
                $rows[] = ['key' => (string)$g, 'name' => self::keyname($g), 'main' => $count['m'], 'sub' => $count['s'],
                    'subsub' => $count['x'], 'users' => count($r), 'green' => self::greenshare($r)];
            }
            usort($rows, function($a, $b) {
                return $b['users'] <=> $a['users'];
            });
            $chart = array_map(function($r) use ($zonemeta) {
                return ['label' => $r['name'], 'value' => $r['green'], 'meta' => $zonemeta($r['users'], $r['main'])];
            }, $rows);
            return $info + [
                'blocks' => [
                    ['type' => 'table', 'title' => self::s('an_ojt_deptanalysis', self::keyname($dept)),
                        'info' => self::s('an_ojt_deptanalysis_info'),
                        'heads' => [['label' => self::s('an_ojt_col_competency')], ['label' => self::s('an_ojt_col_type')],
                            ['label' => self::s('zone_red'), 'cls' => 'is-num'], ['label' => self::s('zone_yellow'), 'cls' => 'is-num'],
                            ['label' => self::s('zone_green'), 'cls' => 'is-num']],
                        'rows' => $brows],
                    self::zonechart(self::s('an_ojt_bydesig_overview', self::keyname($dept)), self::s('an_ojt_zone_info'),
                        $chart, $greenlabel),
                ],
                'table' => [
                    'title' => self::s('lp_bydesig', self::keyname($dept)), 'info' => self::s('an_ojt_bydesig_info'),
                    'columns' => [
                        self::col('name', 'lp_col_designation', 'name'),
                        self::col('main', 'an_ojt_col_main'),
                        self::col('sub', 'an_ojt_col_sub'),
                        self::col('subsub', 'an_ojt_col_subsub'),
                        self::col('users', 'an_ojt_col_users'),
                        self::col('green', 'an_ojt_greenshare', 'pct'),
                        self::actioncol('next', 'lp_viewmore'),
                    ],
                    'rows' => $rows,
                ],
            ];
        }

        $desig = $path[1];
        $members = $this->members($dept, $desig);
        $all = $this->ratings($years, $members);
        $z = $this->zonecounts($all);
        if (count($path) === 2) {
            $rows = [];
            $chart = ['categories' => [], 'series' => [
                ['label' => self::s('zone_red'), 'color' => 'destructive', 'values' => []],
                ['label' => self::s('zone_yellow'), 'color' => 'accent', 'values' => []],
                ['label' => self::s('zone_green'), 'color' => 'success', 'values' => []],
            ]];
            foreach ($this->treeorder(array_keys($z)) as $k) {
                $n = $this->node($k);
                $c = $z[$k];
                $rows[] = ['key' => $k, 'name' => $n['name'], 'depth' => $n['depth'], 'type' => $n['type'],
                    'main' => $n['main'], 'users' => $c['users'], 'red' => $c['red'], 'yellow' => $c['yellow'],
                    'green' => $c['green']];
                $chart['categories'][] = $n['name'];
                $chart['series'][0]['values'][] = $c['red'];
                $chart['series'][1]['values'][] = $c['yellow'];
                $chart['series'][2]['values'][] = $c['green'];
            }
            return $info + [
                'blocks' => [['type' => 'chart', 'title' => self::s('an_ojt_zonebycomp'), 'info' => self::s('an_ojt_zonebycomp_info'),
                    'chart' => 'hbar', 'height' => max(220, count($rows) * 26 + 70), 'data' => $chart,
                    'opts' => ['labelw' => 170]]],
                'table' => [
                    'title' => self::s('an_ojt_companalysis', self::keyname($dept) . ' · ' . self::keyname($desig)),
                    'info' => self::s('an_ojt_companalysis_info'),
                    'columns' => [
                        self::col('name', 'an_ojt_col_competency', 'name', ['indent' => 'depth']),
                        self::col('type', 'an_ojt_col_type', 'text'),
                        self::col('main', 'an_ojt_col_maincomp', 'text'),
                        self::col('users', 'an_ojt_col_users'),
                        self::col('red', 'zone_red', 'num', ['tone' => 'red']),
                        self::col('yellow', 'zone_yellow', 'num', ['tone' => 'yellow']),
                        self::col('green', 'zone_green', 'num', ['tone' => 'green']),
                        self::actioncol('next', 'an_viewdetail'),
                    ],
                    'rows' => $rows,
                    'filters' => [['field' => 'type', 'label' => self::s('an_ojt_col_type')],
                        ['field' => 'main', 'label' => self::s('an_ojt_col_maincomp')]],
                    'searchkeys' => ['name', 'main'],
                ],
            ];
        }

        // Users of one competency node.
        $node = $path[2];
        $n = $this->node($node);
        $rows = [];
        $counts = ['red' => 0, 'yellow' => 0, 'green' => 0];
        foreach ($all as $uid => $items) {
            if (!isset($items[$node])) {
                continue;
            }
            $zone = self::zone($items[$node]);
            $counts[$zone]++;
            $rows[] = ['key' => (string)$uid, 'name' => $members[$uid]->fullname, 'email' => $members[$uid]->email,
                'designation' => $members[$uid]->designation, 'rating' => $items[$node],
                'zone' => $zone, 'zonelabel' => self::s('zone_' . $zone), 'zonelabel_tone' => 'zone-' . $zone];
        }
        usort($rows, function($a, $b) {
            return $b['rating'] <=> $a['rating'];
        });
        $title = ($n['depth'] ? $n['main'] . ' › ' : '') . $n['name'];
        return $info + [
            'blocks' => [['type' => 'chart', 'title' => self::s('an_ojt_userzones', $n['name']), 'info' => self::s('an_ojt_userzones_info'),
                'chart' => 'donut', 'height' => 230, 'data' => ['items' => [
                    ['label' => self::s('zone_red'), 'value' => $counts['red'], 'color' => 'destructive'],
                    ['label' => self::s('zone_yellow'), 'value' => $counts['yellow'], 'color' => 'accent'],
                    ['label' => self::s('zone_green'), 'value' => $counts['green'], 'color' => 'success'],
                ]], 'opts' => ['center' => (string)count($rows), 'centerlabel' => self::s('an_ojt_usersupper'), 'inner' => 0.74]]],
            'table' => [
                'title' => self::s('an_ojt_usersof', $title),
                'subtitle' => $n['type'] . ' · ' . self::keyname($dept) . ' · ' . self::keyname($desig),
                'info' => self::s('an_ojt_users_info'),
                'columns' => [
                    self::col('name', 'lp_col_user', 'name'),
                    self::col('designation', 'lp_col_designation', 'text'),
                    self::col('rating', 'an_ojt_col_rating', 'dec'),
                    self::col('zonelabel', 'an_ojt_col_zone', 'badge', ['sortk' => 'rating']),
                    self::actioncol('detail', 'lp_viewdetail'),
                ],
                'rows' => $rows,
                'chips' => ['field' => 'zone', 'items' => [
                    ['value' => 'red', 'tone' => 'red', 'label' => self::s('an_ojt_redzone')],
                    ['value' => 'yellow', 'tone' => 'yellow', 'label' => self::s('an_ojt_yellowzone')],
                    ['value' => 'green', 'tone' => 'green', 'label' => self::s('an_ojt_greenzone')],
                ]],
                'searchkeys' => ['name', 'email'],
            ],
        ];
    }

    /**
     * Crumb name of a competency node key.
     *
     * @param string $k
     * @return string
     */
    protected function crumbname(string $k): string {
        $n = $this->node($k);
        return $n['depth'] ? $n['main'] . ' › ' . $n['name'] : $n['name'];
    }

    /**
     * Per-learner OJT competency report popup.
     *
     * @param string $id
     * @param array $path
     * @param string $key
     * @return array
     */
    public function popup(string $id, array $path, string $key): array {
        global $DB;
        $uid = (int)$key;
        $r = $this->ratings(self::years($this->ws, $this->we), [$uid => true])[$uid] ?? [];
        $counts = ['red' => 0, 'yellow' => 0, 'green' => 0];
        $rows = [];
        foreach ($this->treeorder(array_keys($r)) as $k) {
            $n = $this->node($k);
            $zone = self::zone($r[$k]);
            $counts[$zone]++;
            $rows[] = ['c' => [['t' => $n['name'], 'indent' => $n['depth'], 'bold' => true], $n['type'],
                number_format($r[$k], 1), self::badge(self::s('zone_' . $zone), 'zone-' . $zone)], 'f' => ['zone' => $zone]];
        }
        $profile = $this->profilecard($uid);
        $lines = [];
        // The reporting manager is already on the profile card; add the L&D manager.
        $ld = $this->landdmanager($uid);
        if ($ld !== '') {
            $lines[] = ['label' => self::s('an_ojt_landdmanager'), 'value' => $ld];
        }
        $profile['lines'] = $lines;
        return [
            'title' => self::s('an_ojt_popup', $profile['name']),
            'desc' => self::s('an_ojt_popup_desc'),
            'profile' => $profile,
            'stats' => [
                ['label' => self::s('an_ojt_totalassessedcomp'), 'value' => (string)count($rows), 'icon' => 'competencies'],
                ['label' => self::s('zone_green'), 'value' => (string)$counts['green'], 'icon' => 'green', 'tone' => 'success'],
                ['label' => self::s('zone_yellow'), 'value' => (string)$counts['yellow'], 'icon' => 'yellow', 'tone' => 'accent'],
                ['label' => self::s('zone_red'), 'value' => (string)$counts['red'], 'icon' => 'red', 'tone' => 'destructive'],
            ],
            'chips' => ['field' => 'zone', 'items' => [
                ['value' => 'red', 'tone' => 'red', 'label' => self::s('an_ojt_redzone')],
                ['value' => 'yellow', 'tone' => 'yellow', 'label' => self::s('an_ojt_yellowzone')],
                ['value' => 'green', 'tone' => 'green', 'label' => self::s('an_ojt_greenzone')],
            ]],
            'table' => ['heads' => [
                ['label' => self::s('an_ojt_col_competency'), 'info' => self::s('an_ojt_col_competency_info')],
                ['label' => self::s('an_ojt_col_type'), 'info' => self::s('an_ojt_col_type_info')],
                ['label' => self::s('an_ojt_col_rating'), 'info' => self::s('an_ojt_col_rating_info')],
                ['label' => self::s('an_ojt_col_zone'), 'info' => self::s('an_ojt_col_zone_info')],
            ], 'rows' => $rows],
        ];
    }

    /**
     * L&D manager of a learner (profile field "landdmanager", value "userid-Name" or a name / email).
     *
     * @param int $uid
     * @return string
     */
    protected function landdmanager(int $uid): string {
        global $DB;
        $v = $DB->get_field_sql("SELECT d.data FROM {user_info_data} d
                                   JOIN {user_info_field} f ON f.id = d.fieldid
                                  WHERE f.shortname = :s AND d.userid = :u",
            ['s' => (string)source::config('landdfield', 'landdmanager'), 'u' => $uid]);
        $v = trim((string)$v);
        if ($v === '') {
            return '';
        }
        if (preg_match('/^(\d+)-(.*)$/', $v, $m)) {
            $u = $DB->get_record('user', ['id' => (int)$m[1]], 'id, firstname, lastname, firstnamephonetic, lastnamephonetic,
                middlename, alternatename');
            return $u ? fullname($u) : trim($m[2]);
        }
        $managers = profile::managers();
        return $managers[\core_text::strtolower($v)] ?? $v;
    }
}
