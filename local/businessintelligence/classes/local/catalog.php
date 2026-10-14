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
 * Static description of the KPI tiles and report cards (labels, icons, info text, chart layout).
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalog {
    /** KPI key => icon. */
    const KPIS = [
        'totallearners'   => 'users',
        'activelearners'  => 'user-check',
        'completionrate'  => 'graduation-cap',
        'overduelearners' => 'timer',
        'lpcompleted'     => 'route',
        'sessions'        => 'layers',
        'traininghours'   => 'clock',
        'trainers'        => 'users-round',
        'competencies'    => 'target',
        'roleplay'        => 'bot',
    ];

    /**
     * Report definitions.
     *
     * @return array
     */
    public static function reports(): array {
        return [
            'learning' => [
                'icon' => 'graduation-cap', 'grid' => 'lbi-grid-5',
                'stats' => [['learners', 'users'], ['assigned', 'book-open'], ['completedlearners', 'circle-check'],
                    ['overduelearners', 'timer']],
                'charts' => [
                    ['key' => 'funnel', 'type' => 'funnel', 'col' => 'lbi-span-3', 'height' => 190],
                    ['key' => 'trend', 'type' => 'line', 'col' => 'lbi-span-2', 'height' => 170, 'title' => 'chart_overduetrend'],
                ],
            ],
            'sessions' => [
                'icon' => 'layers', 'grid' => 'lbi-grid-2',
                'stats' => [['total', 'layers'], ['attendance', 'calendar-check'], ['hours', 'clock'], ['noshow', 'user-x']],
                'charts' => [
                    ['key' => 'donut', 'type' => 'donut', 'col' => '', 'height' => 190],
                    ['key' => 'trend', 'type' => 'line', 'col' => '', 'height' => 170, 'title' => 'chart_attendance'],
                ],
            ],
            'learningpaths' => [
                'icon' => 'route', 'grid' => 'lbi-grid-1',
                'stats' => [['total', 'route'], ['completed', 'circle-check'], ['inprogress', 'hourglass'],
                    ['notstarted', 'circle-pause']],
                'charts' => [['key' => 'bars', 'type' => 'hbar', 'col' => '', 'height' => 200]],
            ],
            'trainer' => [
                'icon' => 'chart-column', 'grid' => 'lbi-grid-5',
                'stats' => [['inhouse', 'users-round'], ['guest', 'user-cog'], ['hours', 'clock'], ['sessions', 'layers']],
                'charts' => [
                    ['key' => 'bars', 'type' => 'vbar', 'col' => 'lbi-span-3', 'height' => 170, 'title' => 'chart_trainertype'],
                    ['key' => 'ratings', 'type' => 'ratings', 'col' => 'lbi-span-2', 'height' => 0, 'title' => 'chart_avgrating'],
                ],
            ],
            'skillgap' => [
                'icon' => 'target', 'grid' => 'lbi-grid-1',
                'stats' => [['total', 'target'], ['users', 'users'], ['weak', 'triangle-alert'], ['needimprovement', 'shield-alert']],
                'charts' => [['key' => 'list', 'type' => 'weaklist', 'col' => '', 'height' => 0, 'title' => 'chart_weaklist']],
            ],
            'ojt' => [
                'icon' => 'shield-check', 'grid' => 'lbi-grid-1',
                'stats' => [['total', 'target'], ['learners', 'users'], ['assessed', 'shield-check'], ['competent', 'circle-check']],
                'charts' => [['key' => 'bars', 'type' => 'hbar', 'col' => '', 'height' => 220]],
            ],
            'roleplay' => [
                'icon' => 'bot', 'grid' => 'lbi-grid-1',
                'stats' => [['scenarios', 'layers'], ['learners', 'users'], ['avgscore', 'gauge'], ['passrate', 'circle-check']],
                'charts' => [['key' => 'bars', 'type' => 'hbar', 'col' => '', 'height' => 220]],
            ],
            'outcome' => [
                'icon' => 'chart-line', 'grid' => 'lbi-grid-1',
                'stats' => [['trained', 'users'], ['withdata', 'clipboard-check'], ['avgchange', 'chart-line'],
                    ['positive', 'circle-check']],
                'charts' => [['key' => 'funnel', 'type' => 'funnel', 'col' => '', 'height' => 190]],
            ],
        ];
    }

    /**
     * Template context for the KPI tiles.
     *
     * @return array
     */
    public static function kpi_context(): array {
        $out = [];
        foreach (self::KPIS as $key => $icon) {
            $out[] = [
                'key' => $key,
                'icon' => icons::svg($icon),
                'label' => get_string('kpi_' . $key, 'local_businessintelligence'),
                'info' => get_string('kpi_' . $key . '_info', 'local_businessintelligence'),
            ];
        }
        return $out;
    }

    /**
     * Template context for one report header (shared by dashboard card and drill-down page).
     *
     * @param string $key
     * @param array $def
     * @return array
     */
    public static function report_header(string $key, array $def): array {
        $stats = [];
        foreach ($def['stats'] as [$skey, $sicon]) {
            $stats[] = [
                'key' => $skey,
                'icon' => icons::svg($sicon),
                'label' => get_string('stat_' . $key . '_' . $skey, 'local_businessintelligence'),
            ];
        }
        return [
            'key' => $key,
            'icon' => icons::svg($def['icon']),
            'title' => get_string('report_' . $key, 'local_businessintelligence'),
            'desc' => get_string('report_' . $key . '_desc', 'local_businessintelligence'),
            'info' => get_string('report_' . $key . '_info', 'local_businessintelligence'),
            'stats' => $stats,
        ];
    }

    /**
     * Template context for the report cards.
     *
     * @param filters $f
     * @return array
     */
    public static function report_context(filters $f): array {
        $out = [];
        foreach (self::reports() as $key => $def) {
            $ctx = self::report_header($key, $def);
            $charts = [];
            foreach ($def['charts'] as $c) {
                $charts[] = [
                    'key' => $c['key'],
                    'type' => $c['type'],
                    'col' => $c['col'],
                    'height' => $c['height'],
                    'hasheight' => $c['height'] > 0,
                    'title' => !empty($c['title']) ? get_string($c['title'], 'local_businessintelligence') : '',
                ];
            }
            $ctx['grid'] = $def['grid'];
            $ctx['charts'] = $charts;
            $ctx['url'] = (new \moodle_url('/local/businessintelligence/report.php',
                ['report' => $key] + array_filter($f->to_array())))->out(false);
            $out[] = $ctx;
        }
        return $out;
    }
}
