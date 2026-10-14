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
use renderable;
use renderer_base;
use templatable;

/**
 * Drill-down ("View Analysis") page for one report.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class drilldown_page implements renderable, templatable {
    /** @var filters */
    protected $filters;
    /** @var string */
    protected $report;

    /**
     * Constructor.
     *
     * @param filters $filters
     * @param string $report
     */
    public function __construct(filters $filters, string $report) {
        $this->filters = $filters;
        $this->report = $report;
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $defs = catalog::reports();
        $header = catalog::report_header($this->report, $defs[$this->report]);
        $others = [];
        foreach (array_keys($defs) as $key) {
            $others[] = [
                'key' => $key,
                'label' => get_string('report_' . $key, 'local_businessintelligence'),
                'selected' => $key === $this->report,
            ];
        }
        return [
            'filters' => dashboard::filter_context($this->filters),
            'report' => $header,
            'reports' => $others,
            'backurl' => (new \moodle_url('/local/businessintelligence/index.php', array_filter($this->filters->to_array())))->out(false),
            'iconinfo' => icons::svg('info'),
            'iconback' => icons::svg('arrow-left'),
            'iconchevron' => icons::svg('chevron-right'),
            'iconselect' => icons::svg('chevron-down'),
            'iconsearch' => icons::svg('search'),
            'icondownload' => icons::svg('download'),
            'iconorg' => icons::svg('building'),
        ];
    }
}
