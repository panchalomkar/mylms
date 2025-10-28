<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Version details.
 *
 * @package    local_sitesync
 * @copyright  2023 WisdmLabs <support@wisdmlabs.com>
 * @author     Gourav G <support@wisdmlabs.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sitesync\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class compatibility_checker extends \external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters(
            array(
                'action' => new \external_value(PARAM_TEXT, 'Action Type'),
                'config' => new \external_value(PARAM_RAW, 'Page data')
            )
        );
    }

    /**
     * Check connection
     * @return bool
     */
    public static function execute($action, $config)  {

        global $PAGE;

        $PAGE->set_context(\context_system::instance());

        $syncActions = new \local_sitesync\compatibility_check_actions();

        $return = $syncActions->perform_action($action, $config);
        return json_encode($return);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function execute_returns() {
        return new \external_value(PARAM_RAW, 'Json');
    }
}
