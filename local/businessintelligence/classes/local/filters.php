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
 * Dashboard filter state: date range + learner population (profile fields, manager, competency).
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filters {
    /** Allowed date ranges. */
    const RANGES = ['30d', '3m', '6m', '12m', 'ytd', 'all'];

    /** Default date range. */
    const DEFAULT_RANGE = '6m';

    /** @var string */
    public $range = self::DEFAULT_RANGE;
    /** @var string */
    public $department = '';
    /** @var string */
    public $designation = '';
    /** @var string */
    public $branch = '';
    /** @var string reporting manager e-mail (lower case) */
    public $manager = '';
    /** @var int local_skillgap_comp id */
    public $competency = 0;
    /** @var int reference "now" */
    public $now;

    /**
     * Build from raw (already PARAM-cleaned) values.
     *
     * @param array $a
     * @return self
     */
    public static function from_array(array $a): self {
        $f = new self();
        $f->now = time();
        $range = (string)($a['range'] ?? self::DEFAULT_RANGE);
        $f->range = in_array($range, self::RANGES, true) ? $range : self::DEFAULT_RANGE;
        $f->department = trim((string)($a['department'] ?? ''));
        $f->designation = trim((string)($a['designation'] ?? ''));
        $f->branch = trim((string)($a['branch'] ?? ''));
        $f->manager = profile::normalise_manager((string)($a['manager'] ?? ''));
        $f->competency = (int)($a['competency'] ?? 0);
        return $f;
    }

    /**
     * Read filters from the current request (page load).
     *
     * @return self
     */
    public static function from_request(): self {
        return self::from_array([
            'range'       => optional_param('range', self::DEFAULT_RANGE, PARAM_ALPHANUM),
            'department'  => optional_param('department', '', PARAM_TEXT),
            'designation' => optional_param('designation', '', PARAM_TEXT),
            'branch'      => optional_param('branch', '', PARAM_TEXT),
            'manager'     => optional_param('manager', '', PARAM_TEXT),
            'competency'  => optional_param('competency', 0, PARAM_INT),
        ]);
    }

    /**
     * Plain array of the state.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'range'       => $this->range,
            'department'  => $this->department,
            'designation' => $this->designation,
            'branch'      => $this->branch,
            'manager'     => $this->manager,
            'competency'  => $this->competency,
        ];
    }

    /**
     * Cache key for this filter state.
     *
     * @param string $suffix
     * @return string
     */
    public function cache_key(string $suffix = ''): string {
        $a = $this->to_array();
        $a['company'] = self::company_id();
        $a['lang'] = current_language();
        // Round "now" to 5 minutes so keys are stable inside the TTL window.
        $a['t'] = (int)floor($this->now / 300);
        return sha1(json_encode($a) . $suffix);
    }

    /**
     * Number of active filters (for the badge). The date range counts unless it is "All time".
     *
     * @return int
     */
    public function active_count(): int {
        $n = $this->range !== 'all' ? 1 : 0;
        foreach (['department', 'designation', 'branch', 'manager'] as $k) {
            if ($this->$k !== '') {
                $n++;
            }
        }
        if ($this->competency > 0) {
            $n++;
        }
        return $n;
    }

    /**
     * True when the learner population is narrowed (anything other than the date range).
     *
     * @return bool
     */
    public function has_user_filters(): bool {
        return $this->department !== '' || $this->designation !== '' || $this->branch !== ''
            || $this->manager !== '' || $this->competency > 0;
    }

    /**
     * Start timestamp of the selected window (0 = all time).
     *
     * @return int
     */
    public function start(): int {
        $now = $this->now;
        switch ($this->range) {
            case '30d':
                return $now - 30 * DAYSECS;
            case '3m':
                return strtotime('-3 months', $now);
            case '6m':
                return strtotime('-6 months', $now);
            case '12m':
                return strtotime('-12 months', $now);
            case 'ytd':
                return make_timestamp((int)userdate($now, '%Y'), 1, 1);
            default:
                return 0;
        }
    }

    /**
     * Current window [start, end].
     *
     * @return int[]
     */
    public function window(): array {
        return [$this->start(), $this->now];
    }

    /**
     * Previous window of the same length (null for all time).
     *
     * @return int[]|null
     */
    public function previous_window(): ?array {
        $s = $this->start();
        if ($s <= 0) {
            return null;
        }
        $len = $this->now - $s;
        return [$s - $len, $s - 1];
    }

    /**
     * Time buckets used for sparklines and trend charts.
     *
     * @return array list of ['start' => int, 'end' => int, 'label' => string]
     */
    public function buckets(): array {
        $now = $this->now;
        $buckets = [];
        if ($this->range === '30d') {
            // Six 5-day buckets.
            $start = $now - 30 * DAYSECS;
            for ($i = 0; $i < 6; $i++) {
                $bs = $start + $i * 5 * DAYSECS;
                $be = ($i === 5) ? $now : $bs + 5 * DAYSECS - 1;
                $buckets[] = ['start' => $bs, 'end' => $be, 'label' => userdate($be, '%d %b')];
            }
            return $buckets;
        }
        $months = ['3m' => 3, '6m' => 6, '12m' => 12, 'all' => 12][$this->range] ?? 0;
        if ($this->range === 'ytd') {
            $months = (int)userdate($now, '%m');
        } else {
            $months++; // Include the current (partial) month as the last point.
        }
        $months = max(2, $months);
        $y = (int)userdate($now, '%Y');
        $m = (int)userdate($now, '%m');
        for ($i = $months - 1; $i >= 0; $i--) {
            $mm = $m - $i;
            $yy = $y;
            while ($mm < 1) {
                $mm += 12;
                $yy--;
            }
            $bs = make_timestamp($yy, $mm, 1);
            $be = ($i === 0) ? $now : strtotime('+1 month', $bs) - 1;
            $buckets[] = ['start' => $bs, 'end' => $be, 'label' => \core_text::substr(userdate($bs, '%b'), 0, 3)];
        }
        return $buckets;
    }

    /**
     * Current IOMAD company id (0 when IOMAD is not installed or no company is selected).
     *
     * @return int
     */
    public static function company_id(): int {
        global $DB;
        static $cid = null;
        if ($cid !== null) {
            return $cid;
        }
        $cid = 0;
        try {
            if (class_exists('\iomad') && $DB->get_manager()->table_exists('company_users')) {
                $cid = (int)\iomad::get_my_companyid(\context_system::instance(), false);
            }
        } catch (\Throwable $e) {
            $cid = 0;
        }
        return $cid;
    }

    /**
     * SQL condition restricting a user-id column to the filtered learner population.
     *
     * @param string $column e.g. "ue.userid"
     * @param params $p
     * @return string
     */
    public function user_condition(string $column, params $p): string {
        global $CFG, $DB;
        static $aliasn = 0;
        $a = 'pu' . (++$aliasn);
        $where = [
            "{$a}.deleted = 0",
            "{$a}.suspended = 0",
            "{$a}.confirmed = 1",
            "{$a}.id <> " . $p->add((int)$CFG->siteguest),
        ];
        $admins = array_filter(array_map('intval', explode(',', (string)$CFG->siteadmins)));
        if ($admins) {
            $where[] = "{$a}.id " . $p->in($admins, false);
        }
        $profilefilters = ['department' => $this->department, 'designation' => $this->designation, 'branch' => $this->branch];
        foreach ($profilefilters as $key => $value) {
            if ($value === '') {
                continue;
            }
            $fid = profile::field_id($key);
            if (!$fid) {
                $where[] = '1 = 0';
                continue;
            }
            $d = $a . substr($key, 0, 3);
            $text = 'TRIM(' . $DB->sql_compare_text("{$d}.data", 255) . ')';
            $where[] = "EXISTS (SELECT 1 FROM {user_info_data} {$d}
                                 WHERE {$d}.userid = {$a}.id AND {$d}.fieldid = " . $p->add($fid) . "
                                   AND {$text} = " . $p->add($value) . ")";
        }
        if ($this->manager !== '') {
            $fid = profile::field_id('manager');
            if (!$fid) {
                $where[] = '1 = 0';
            } else {
                $d = $a . 'rm';
                $text = 'LOWER(TRIM(' . $DB->sql_compare_text("{$d}.data", 255) . '))';
                $where[] = "EXISTS (SELECT 1 FROM {user_info_data} {$d}
                                     WHERE {$d}.userid = {$a}.id AND {$d}.fieldid = " . $p->add($fid) . "
                                       AND ({$text} = " . $p->add($this->manager) . "
                                            OR {$text} = " . $p->add(profile::MANAGER_PREFIX . $this->manager) . "))";
            }
        }
        if ($this->competency > 0) {
            if (source::has_table('local_skillgap_score')) {
                $where[] = "EXISTS (SELECT 1 FROM {local_skillgap_score} {$a}sg
                                     WHERE {$a}sg.userid = {$a}.id AND {$a}sg.compid = " . $p->add($this->competency) . ")";
            } else {
                $where[] = '1 = 0';
            }
        }
        $company = self::company_id();
        if ($company) {
            $where[] = "EXISTS (SELECT 1 FROM {company_users} {$a}cu
                                 WHERE {$a}cu.userid = {$a}.id AND {$a}cu.companyid = " . $p->add($company) . ")";
        }
        return "{$column} IN (SELECT {$a}.id FROM {user} {$a} WHERE " . implode(' AND ', $where) . ")";
    }

    /**
     * The filtered population with name and hierarchy fields.
     *
     * @return array userid => stdClass(id, fullname, email, department, designation, branch)
     */
    public function population(): array {
        global $DB;
        static $cache = [];
        $key = $this->cache_key('pop');
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        $p = new params();
        $cond = $this->user_condition('u.id', $p);
        $names = implode(', ', array_map(function($f) {
            return 'u.' . $f;
        }, \core_user\fields::get_name_fields()));
        $users = [];
        $rs = $DB->get_recordset_sql("SELECT u.id, u.email, {$names} FROM {user} u WHERE {$cond}", $p->values);
        foreach ($rs as $u) {
            $users[(int)$u->id] = (object)[
                'id' => (int)$u->id,
                'fullname' => fullname($u),
                'email' => $u->email,
                'department' => '',
                'designation' => '',
                'branch' => '',
            ];
        }
        $rs->close();
        $ids = array_keys($users);
        foreach (['department', 'designation', 'branch'] as $k) {
            foreach (profile::values_for($k, $ids) as $uid => $v) {
                if (isset($users[$uid])) {
                    $users[$uid]->$k = $v;
                }
            }
        }
        $cache[$key] = $users;
        return $users;
    }
}
