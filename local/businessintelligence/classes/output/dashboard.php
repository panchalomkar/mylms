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

namespace local_businessintelligence\output;

use local_businessintelligence\local\catalog;
use local_businessintelligence\local\filters;
use local_businessintelligence\local\icons;
use local_businessintelligence\local\profile;
use local_businessintelligence\local\source;
use renderable;
use renderer_base;
use templatable;

/**
 * Executive dashboard page.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard implements renderable, templatable {
    /** @var filters */
    protected $filters;

    /**
     * Constructor.
     *
     * @param filters $filters
     */
    public function __construct(filters $filters) {
        $this->filters = $filters;
    }

    /**
     * Filter bar context (shared with the drill-down page).
     *
     * @param filters $f
     * @return array
     */
    public static function filter_context(filters $f): array {
        global $DB;
        $p = 'local_businessintelligence';
        $selects = [];

        $ranges = [];
        foreach (filters::RANGES as $r) {
            $ranges[] = ['value' => $r, 'label' => get_string('range_' . $r, $p), 'selected' => $f->range === $r];
        }
        $selects[] = ['name' => 'range', 'label' => get_string('filter_range', $p), 'options' => $ranges,
            'active' => $f->range !== 'all', 'noall' => true];

        $fromlist = function(string $name, string $alllabel, array $values, string $current) {
            $opts = [];
            foreach ($values as $value => $label) {
                $opts[] = ['value' => (string)$value, 'label' => $label, 'selected' => (string)$value === $current];
            }
            if ($current !== '' && !array_key_exists($current, $values)) {
                $opts[] = ['value' => $current, 'label' => $current, 'selected' => true];
            }
            return ['name' => $name, 'alllabel' => $alllabel, 'label' => $alllabel, 'options' => $opts, 'active' => $current !== ''];
        };
        foreach (['department' => 'department', 'designation' => 'designation', 'branch' => 'branch'] as $name => $key) {
            $vals = profile::distinct_values($key);
            $selects[] = $fromlist($name, get_string('filter_all_' . $name, $p), array_combine($vals, $vals) ?: [], $f->$name);
        }
        $selects[] = $fromlist('manager', get_string('filter_all_manager', $p), profile::managers(), $f->manager);

        $comps = [];
        if (source::has_table('local_skillgap_comp')) {
            foreach ($DB->get_records('local_skillgap_comp', null, 'name ASC', 'id, name') as $c) {
                $comps[(string)$c->id] = format_string($c->name);
            }
        }
        $selects[] = $fromlist('competency', get_string('filter_all_competency', $p), $comps,
            $f->competency ? (string)$f->competency : '');

        return [
            'selects' => $selects,
            'count' => $f->active_count(),
            'iconfilter' => icons::svg('sliders-horizontal'),
            'iconchevron' => icons::svg('chevron-down'),
            'iconreset' => icons::svg('rotate-ccw'),
        ];
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'filters' => self::filter_context($this->filters),
            'kpis' => catalog::kpi_context(),
            'reports' => catalog::report_context($this->filters),
            'iconinfo' => icons::svg('info'),
            'icondownload' => icons::svg('download'),
            'iconpdf' => icons::svg('file-text'),
            'iconarrow' => icons::svg('arrow-right'),
            'icontrend' => icons::svg('trending-up'),
            'company' => format_string(get_site()->fullname),
        ];
    }
}
