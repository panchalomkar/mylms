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

namespace local_businessintelligence\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_businessintelligence\local\analysis;
use local_businessintelligence\local\filters;

/**
 * AJAX: generic analysis pages (Trainer, AI Role-Play, Learning & Performance, Competency).
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_analysis extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'report' => new external_value(PARAM_ALPHA, 'trainer|roleplay|outcome|ojt'),
            'mode'   => new external_value(PARAM_ALPHA, 'page|level|popup', VALUE_DEFAULT, 'page'),
            'path'   => new external_value(PARAM_RAW, 'JSON array of drill-down keys', VALUE_DEFAULT, '[]'),
            'popup'  => new external_value(PARAM_ALPHANUMEXT, 'Popup id', VALUE_DEFAULT, ''),
            'key'    => new external_value(PARAM_TEXT, 'Row key of the popup', VALUE_DEFAULT, ''),
        ] + get_dashboard::filter_params());
    }

    /**
     * Execute.
     *
     * @param string $report
     * @param string $mode
     * @param string $path
     * @param string $popup
     * @param string $key
     * @param string $range
     * @param string $department
     * @param string $designation
     * @param string $branch
     * @param string $manager
     * @param int $competency
     * @return array
     */
    public static function execute($report, $mode = 'page', $path = '[]', $popup = '', $key = '',
            $range = filters::DEFAULT_RANGE, $department = '', $designation = '', $branch = '', $manager = '',
            $competency = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('report', 'mode', 'path', 'popup', 'key',
            'range', 'department', 'designation', 'branch', 'manager', 'competency'));
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/businessintelligence:view', $context);
        \core_php_time_limit::raise(300);

        $p = json_decode($params['path'], true);
        $p = is_array($p) ? array_values(array_map(function($v) {
            return clean_param((string)$v, PARAM_TEXT);
        }, array_slice($p, 0, 5))) : [];

        $filters = filters::from_array($params);
        $provider = analysis::create($params['report'], $filters);
        switch ($params['mode']) {
            case 'page':
                $out = $provider->overview() + ['level' => $provider->level($p)];
                $out['filtercount'] = $filters->active_count();
                break;
            case 'level':
                $out = ['level' => $provider->level($p)];
                break;
            case 'popup':
                $out = ['popup' => $provider->popup($params['popup'], $p, $params['key'])];
                break;
            default:
                throw new \invalid_parameter_exception('Unknown mode');
        }
        return ['data' => json_encode($out)];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'data' => new external_value(PARAM_RAW, 'JSON encoded payload'),
        ]);
    }
}
