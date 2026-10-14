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

use local_businessintelligence\local\filters;
use local_businessintelligence\local\icons;
use renderable;
use renderer_base;
use templatable;

/**
 * Training Sessions Analytics page.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sessions_page implements renderable, templatable {
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
     * Export for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $p = 'local_businessintelligence';
        $kpis = [];
        foreach (['sessions' => 'layers', 'enrolled' => 'users', 'attendance' => 'calendar-check', 'hours' => 'clock',
                'noshows' => 'user-x'] as $key => $icon) {
            $kpis[] = [
                'key' => $key,
                'icon' => icons::svg($icon),
                'label' => get_string('ts_kpi_' . $key, $p),
                'info' => get_string('ts_kpi_' . $key . '_info', $p),
            ];
        }
        $charts = [];
        foreach (['mix' => 270, 'attendancetrend' => 270, 'hourstrend' => 270] as $key => $h) {
            $charts[] = [
                'key' => $key,
                'title' => get_string('ts_chart_' . $key, $p),
                'info' => get_string('ts_chart_' . $key . '_info', $p),
                'height' => $h,
            ];
        }
        return [
            'filters' => dashboard::filter_context($this->filters),
            'company' => format_string(get_site()->fullname),
            'kpis' => $kpis,
            'row1' => $charts,
            'backurl' => (new \moodle_url('/local/businessintelligence/index.php', array_filter($this->filters->to_array())))
                ->out(false),
            'iconinfo' => icons::svg('info'),
            'icondownload' => icons::svg('download'),
            'iconpdf' => icons::svg('file-text'),
            'iconrefresh' => icons::svg('refresh-cw'),
            'iconclose' => icons::svg('x'),
            'iconsort' => icons::svg('arrow-up-down'),
            'iconprev' => icons::svg('chevron-left'),
            'iconnext' => icons::svg('chevron-right'),
            'iconsearch' => icons::svg('search'),
            'iconback' => icons::svg('arrow-left'),
            'iconchevron' => icons::svg('chevron-down'),
            'iconhome' => icons::svg('house'),
            'staticons' => icons::stat_icons(),
        ];
    }
}
