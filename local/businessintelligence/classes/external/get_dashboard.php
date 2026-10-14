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
use local_businessintelligence\local\filters;
use local_businessintelligence\local\metrics;

/**
 * AJAX: KPI ribbon + report card data for a filter state.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_dashboard extends external_api {
    /**
     * Filter parameters shared by all functions.
     *
     * @return array
     */
    public static function filter_params(): array {
        return [
            'range'       => new external_value(PARAM_ALPHANUM, 'Date range key', VALUE_DEFAULT, filters::DEFAULT_RANGE),
            'department'  => new external_value(PARAM_TEXT, 'Department profile value', VALUE_DEFAULT, ''),
            'designation' => new external_value(PARAM_TEXT, 'Designation profile value', VALUE_DEFAULT, ''),
            'branch'      => new external_value(PARAM_TEXT, 'Branch profile value', VALUE_DEFAULT, ''),
            'manager'     => new external_value(PARAM_TEXT, 'Reporting manager e-mail', VALUE_DEFAULT, ''),
            'competency'  => new external_value(PARAM_INT, 'Skill gap competency id', VALUE_DEFAULT, 0),
        ];
    }

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(self::filter_params());
    }

    /**
     * Execute.
     *
     * @param string $range
     * @param string $department
     * @param string $designation
     * @param string $branch
     * @param string $manager
     * @param int $competency
     * @return array
     */
    public static function execute($range = filters::DEFAULT_RANGE, $department = '', $designation = '', $branch = '',
            $manager = '', $competency = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('range', 'department', 'designation',
            'branch', 'manager', 'competency'));
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/businessintelligence:view', $context);
        \core_php_time_limit::raise(300);

        $metrics = new metrics(filters::from_array($params));
        return ['data' => json_encode($metrics->dashboard())];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'data' => new external_value(PARAM_RAW, 'JSON encoded dashboard payload'),
        ]);
    }
}
