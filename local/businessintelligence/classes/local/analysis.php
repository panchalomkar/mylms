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
 * Base class of the generic "View Analysis" pages (Trainer, AI Role-Play, Learning & Performance, Competency).
 *
 * A provider returns plain arrays the AMD module local_businessintelligence/analysis renders:
 *  - overview(): ['kpis' => [key => kpi], 'charts' => [chart spec]]
 *  - level(path): ['levels', 'depth', 'levelname', 'crumbs', 'backlabel', 'blocks' => [...], 'table' => [...]]
 *  - popup(id, path, key): ['title', 'desc', 'profile', 'stats', 'chips', 'sections' | 'table']
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class analysis extends metrics {
    /** Empty profile value marker. */
    const NONE = '__none__';

    /** Reports served by this engine => provider class. */
    const PROVIDERS = [
        'trainer' => trainerpage::class,
        'roleplay' => roleplaypage::class,
        'outcome' => outcomepage::class,
        'ojt' => ojtpage::class,
    ];

    /**
     * Provider for a report.
     *
     * @param string $report
     * @param filters $f
     * @return analysis
     */
    public static function create(string $report, filters $f): analysis {
        if (!isset(self::PROVIDERS[$report])) {
            throw new \invalid_parameter_exception('Unknown report');
        }
        $class = self::PROVIDERS[$report];
        return new $class($f);
    }

    /**
     * KPI cards of the page: key => icon.
     *
     * @return array
     */
    abstract public static function kpidefs(): array;

    /**
     * KPIs and overview charts.
     *
     * @return array
     */
    abstract public function overview(): array;

    /**
     * One drill-down level.
     *
     * @param array $path keys chosen so far
     * @return array
     */
    abstract public function level(array $path): array;

    /**
     * A popup.
     *
     * @param string $id
     * @param array $path
     * @param string $key
     * @return array
     */
    abstract public function popup(string $id, array $path, string $key): array;

    /**
     * Extra header links of the page (e.g. data upload), [['url', 'label', 'icon']].
     *
     * @return array
     */
    public static function headerlinks(): array {
        return [];
    }

    /**
     * Formatted plain text (the AMD module inserts text, never HTML).
     *
     * @param mixed $text
     * @return string
     */
    protected static function txt($text): string {
        return format_string((string)$text, true, ['escape' => false]);
    }

    /**
     * Shortcut for a lang string.
     *
     * @param string $k
     * @param mixed $a
     * @return string
     */
    protected static function s(string $k, $a = null): string {
        return get_string($k, 'local_businessintelligence', $a);
    }

    /**
     * Display name of a group key.
     *
     * @param string $key
     * @return string
     */
    protected static function keyname(string $key): string {
        return $key === self::NONE ? self::s('notset') : $key;
    }

    /**
     * Department / designation key of a population record.
     *
     * @param \stdClass $u
     * @param string $field department|designation
     * @return string
     */
    protected static function gkey(\stdClass $u, string $field): string {
        return $u->$field !== '' ? $u->$field : self::NONE;
    }

    /**
     * Members of a department / designation.
     *
     * @param string $dept '' = all
     * @param string $desig '' = all
     * @return array
     */
    protected function members(string $dept = '', string $desig = ''): array {
        $out = [];
        foreach ($this->population() as $uid => $u) {
            if (($dept !== '' && self::gkey($u, 'department') !== $dept) || ($desig !== '' && self::gkey($u, 'designation') !== $desig)) {
                continue;
            }
            $out[$uid] = $u;
        }
        return $out;
    }

    /**
     * Profile header data of a learner.
     *
     * @param int $userid
     * @return array
     */
    protected function profilecard(int $userid): array {
        $pop = $this->population();
        if (!isset($pop[$userid])) {
            throw new \moodle_exception('invaliduser');
        }
        $u = $pop[$userid];
        $memail = profile::normalise_manager(profile::values_for('manager', [$userid])[$userid] ?? '');
        $manager = '';
        if ($memail !== '') {
            $managers = profile::managers();
            $manager = $managers[$memail] ?? $memail;
        }
        return [
            'name' => $u->fullname,
            'initials' => self::initials($u->fullname),
            'email' => $u->email,
            'meta' => implode(' · ', array_filter([$u->designation, $u->department, $u->branch])),
            'manager' => $manager === '' ? '' : self::s('lp_reportingmanager', $manager),
        ];
    }

    /**
     * Manager display names of users.
     *
     * @param int[] $userids
     * @return array userid => name
     */
    protected function managernames(array $userids): array {
        $managers = profile::managers();
        $out = [];
        foreach (profile::values_for('manager', $userids) as $uid => $v) {
            $m = profile::normalise_manager($v);
            $out[$uid] = $m === '' ? '' : ($managers[$m] ?? $m);
        }
        return $out;
    }

    /**
     * Initials of a name.
     *
     * @param string $name
     * @return string
     */
    public static function initials(string $name): string {
        $initials = '';
        foreach (preg_split('/\s+/', trim($name)) as $part) {
            if ($part !== '' && \core_text::strlen($initials) < 2) {
                $initials .= \core_text::strtoupper(\core_text::substr($part, 0, 1));
            }
        }
        return $initials;
    }

    /**
     * Breadcrumb / level bookkeeping.
     *
     * @param array $levelnames names of every level ("Department Report", ...)
     * @param array $path
     * @param array $crumbs display names of the path items
     * @return array
     */
    protected function levelinfo(array $levelnames, array $path, array $crumbs): array {
        $depth = count($path);
        return [
            'levels' => count($levelnames),
            'depth' => $depth,
            'levelname' => $levelnames[$depth],
            'crumbs' => $crumbs,
            'backlabel' => $depth ? self::s('an_backto', $levelnames[$depth - 1]) : '',
        ];
    }

    /**
     * Column definition helper.
     *
     * @param string $k
     * @param string $label lang key
     * @param string $type text|num|pct|dec|signed|badge|name
     * @param array $extra
     * @return array
     */
    protected static function col(string $k, string $label, string $type = 'num', array $extra = []): array {
        $info = get_string_manager()->string_exists($label . '_info', 'local_businessintelligence') ? self::s($label . '_info') : '';
        return ['k' => $k, 'label' => self::s($label), 'info' => $info, 'type' => $type] + $extra;
    }

    /**
     * Action column.
     *
     * @param string $action next|detail
     * @param string $btn lang key of the button
     * @param string $popup popup id for detail
     * @return array
     */
    protected static function actioncol(string $action, string $btn, string $popup = 'detail'): array {
        return ['k' => 'action', 'label' => self::s('lp_col_action'), 'type' => 'action', 'action' => $action,
            'btn' => self::s($btn), 'popup' => $popup];
    }

    /**
     * Zone bar chart block.
     *
     * @param string $title
     * @param string $info
     * @param array $rows [label, value 0-100, meta]
     * @param string $pctlabel
     * @param array $opts
     * @return array
     */
    protected static function zonechart(string $title, string $info, array $rows, string $pctlabel, array $opts = []): array {
        return ['type' => 'chart', 'title' => $title, 'info' => $info, 'chart' => 'bullet',
            'height' => max(190, count($rows) * 50 + 112), 'data' => ['rows' => $rows],
            'opts' => ['low' => 40, 'goal' => 70, 'pctlabel' => $pctlabel] + $opts];
    }

    /**
     * Badge cell.
     *
     * @param string $text
     * @param string $tone CSS tone (completed, overdue, notstarted, yes, no, ...)
     * @return array
     */
    protected static function badge(string $text, string $tone): array {
        return ['t' => $text, 'badge' => $tone];
    }

    /**
     * Percent display.
     *
     * @param float|null $v
     * @param int $dec
     * @return string
     */
    protected static function p($v, int $dec = 1): string {
        if ($v === null) {
            return '—';
        }
        $r = round((float)$v, $dec);
        return ($r == (int)$r ? (string)(int)$r : (string)$r) . '%';
    }
}
