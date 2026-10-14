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
use local_businessintelligence\local\learning;

/**
 * AJAX: Learning Progress Analytics page data (overview, drill-down levels, course popup, user detail).
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_learning extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'mode'   => new external_value(PARAM_ALPHA, 'page|level|courses|user', VALUE_DEFAULT, 'page'),
            'level'  => new external_value(PARAM_ALPHA, 'org|department|designation', VALUE_DEFAULT, 'org'),
            'dept'   => new external_value(PARAM_TEXT, 'Department key', VALUE_DEFAULT, ''),
            'desig'  => new external_value(PARAM_TEXT, 'Designation key', VALUE_DEFAULT, ''),
            'userid' => new external_value(PARAM_INT, 'Learner id (mode=user)', VALUE_DEFAULT, 0),
        ] + get_dashboard::filter_params());
    }

    /**
     * Execute.
     *
     * @param string $mode
     * @param string $level
     * @param string $dept
     * @param string $desig
     * @param int $userid
     * @param string $range
     * @param string $department
     * @param string $designation
     * @param string $branch
     * @param string $manager
     * @param int $competency
     * @return array
     */
    public static function execute($mode = 'page', $level = 'org', $dept = '', $desig = '', $userid = 0,
            $range = filters::DEFAULT_RANGE, $department = '', $designation = '', $branch = '', $manager = '',
            $competency = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('mode', 'level', 'dept', 'desig', 'userid',
            'range', 'department', 'designation', 'branch', 'manager', 'competency'));
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/businessintelligence:view', $context);
        if (!in_array($params['level'], ['org', 'department', 'designation'], true)) {
            throw new \invalid_parameter_exception('Unknown level');
        }
        \core_php_time_limit::raise(300);

        $filters = filters::from_array($params);
        $lp = new learning($filters);
        switch ($params['mode']) {
            case 'page':
                $out = $lp->overview() + ['rows' => $lp->level($params['level'], $params['dept'], $params['desig'])];
                $out['filtercount'] = $filters->active_count();
                break;
            case 'level':
                $out = ['rows' => $lp->level($params['level'], $params['dept'], $params['desig'])];
                break;
            case 'courses':
                $out = ['courses' => $lp->courses($params['dept'], $params['desig'])];
                break;
            case 'user':
                $out = ['user' => $lp->user($params['userid'])];
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
