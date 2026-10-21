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

use local_businessintelligence\local\analysis;
use local_businessintelligence\local\filters;
use local_businessintelligence\local\icons;
use renderable;
use renderer_base;
use templatable;

/**
 * Generic analysis page (Trainer, AI Role-Play, Learning & Performance, Competency).
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analysis_page implements renderable, templatable {
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
        $p = 'local_businessintelligence';
        $class = analysis::PROVIDERS[$this->report];
        $kpis = [];
        foreach ($class::kpidefs() as $key => $icon) {
            $kpis[] = [
                'key' => $key,
                'icon' => icons::svg($icon),
                'label' => get_string('an_' . $this->report . '_kpi_' . $key, $p),
                'info' => get_string('an_' . $this->report . '_kpi_' . $key . '_info', $p),
            ];
        }
        $links = [];
        foreach ($class::headerlinks() as $l) {
            $links[] = ['url' => $l['url'], 'label' => $l['label'], 'icon' => icons::svg($l['icon'])];
        }
        $iconset = [];
        foreach (['sort' => 'arrow-up-down', 'info' => 'info', 'home' => 'house', 'back' => 'arrow-left',
                'next' => 'chevron-right', 'chevron' => 'chevron-down', 'play' => 'circle-play'] as $k => $i) {
            $iconset[] = ['key' => $k, 'svg' => icons::svg($i)];
        }
        return [
            'report' => $this->report,
            'title' => get_string('report_' . $this->report, $p),
            'titleinfo' => get_string('an_' . $this->report . '_info', $p),
            'intro' => get_string('an_' . $this->report . '_intro', $p),
            'filters' => dashboard::filter_context($this->filters),
            'kpis' => $kpis,
            'kpicols' => count($kpis),
            'links' => $links,
            'haslinks' => !empty($links),
            'iconset' => array_merge($iconset, icons::stat_icons()),
            'iconinfo' => icons::svg('info'),
            'icondownload' => icons::svg('download'),
            'iconpdf' => icons::svg('file-text'),
            'iconrefresh' => icons::svg('refresh-cw'),
            'iconclose' => icons::svg('x'),
            'iconprev' => icons::svg('chevron-left'),
            'iconnext' => icons::svg('chevron-right'),
            'iconsearch' => icons::svg('search'),
            'iconchevron' => icons::svg('chevron-down'),
        ];
    }
}
