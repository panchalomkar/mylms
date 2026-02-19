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

class save_config extends \external_api {
    public static function execute_parameters() {
        return new \external_function_parameters([
            'configs' => new \external_multiple_structure(
                new \external_single_structure([
                    'key' => new \external_value(PARAM_TEXT, 'Config key'),
                    'value' => new \external_value(PARAM_RAW, 'Config value'),
                    'plugin' => new \external_value(PARAM_PLUGIN, 'Plugin name')
                ])
            )
        ]);
    }

    public static function execute($configs) {
        $params = self::validate_parameters(self::execute_parameters(), ['configs' => $configs]);

        // Verify user has required capability
        $context = \context_system::instance();
        require_capability('moodle/site:config', $context);

        // Save all configs
        foreach ($params['configs'] as $config) {
            set_config($config['key'], $config['value'], $config['plugin']);
        }

        return true;
    }
    public static function execute_returns() {
        return new \external_value(PARAM_BOOL, 'Success status');
    }
}
