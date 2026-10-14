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
use local_businessintelligence\local\drilldown;
use local_businessintelligence\local\filters;

/**
 * AJAX: Organization > Department > Designation > Learner drill-down rows.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_drilldown extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'report' => new external_value(PARAM_ALPHA, 'Report key'),
            'level'  => new external_value(PARAM_ALPHA, 'org|department|designation', VALUE_DEFAULT, 'org'),
            'dept'   => new external_value(PARAM_TEXT, 'Department being drilled', VALUE_DEFAULT, ''),
            'desig'  => new external_value(PARAM_TEXT, 'Designation being drilled', VALUE_DEFAULT, ''),
        ] + get_dashboard::filter_params());
    }

    /**
     * Execute.
     *
     * @param string $report
     * @param string $level
     * @param string $dept
     * @param string $desig
     * @param string $range
     * @param string $department
     * @param string $designation
     * @param string $branch
     * @param string $manager
     * @param int $competency
     * @return array
     */
    public static function execute($report, $level = 'org', $dept = '', $desig = '', $range = filters::DEFAULT_RANGE,
            $department = '', $designation = '', $branch = '', $manager = '', $competency = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('report', 'level', 'dept', 'desig',
            'range', 'department', 'designation', 'branch', 'manager', 'competency'));
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/businessintelligence:view', $context);
        if (!in_array($params['report'], drilldown::REPORTS, true)) {
            throw new \invalid_parameter_exception('Unknown report');
        }
        if (!in_array($params['level'], ['org', 'department', 'designation'], true)) {
            throw new \invalid_parameter_exception('Unknown level');
        }
        \core_php_time_limit::raise(300);

        $filters = filters::from_array($params);
        $dd = new drilldown($filters);
        $result = $dd->get($params['report'], $params['level'], $params['dept'], $params['desig']);
        $dash = $dd->metrics()->dashboard();
        $result['stats'] = $dash['reports'][$params['report']]['stats'] ?? [];
        $result['filtercount'] = $filters->active_count();
        return ['data' => json_encode($result)];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'data' => new external_value(PARAM_RAW, 'JSON encoded drill-down payload'),
        ]);
    }
}
