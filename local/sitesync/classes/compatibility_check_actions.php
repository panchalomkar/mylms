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
namespace local_sitesync;

defined('MOODLE_INTERNAL') || die();

class compatibility_check_actions {

    /**
     * Performs the specified action by calling the corresponding method.
     *
     * @param string $action The name of the action to perform.
     * @param mixed $config The configuration data required for the action.
     * @return mixed The result of the action, or a string indicating that the function does not exist.
     */
    public function perform_action($action, $config) {
        $functionname = "action_".$action;

        // Check if the function exists before calling it.
        if (method_exists($this, $functionname)) {
            // Call the function dynamically.
            return call_user_func(array($this, $functionname), $config);
        } else {
            // Handle the case when the function doesn't exist.
            return "Function $functionname does not exist.";
        }
    }

    /**
     * Retrieves the compatibility information for the slave site.
     *
     * @param mixed $config The configuration data required for the action.
     * @return array The compatibility information of slave site.
     */
    public function action_get_slave_validation_data($config) {

        $compatibilitychecker = new \local_sitesync\CompatibilityCheck();

        $compatibiltyinfo = $compatibilitychecker->getAllCompatibilityInfo();

        return $compatibiltyinfo;
    }

    /**
     * Checks the compatibility of the master server with the configuration provided.
     *
     * @param mixed $config The configuration data required for the action.
     * @return array The compatibility information, with a status flag and a message.
     */
    public function action_check_master_server_compaibility($config) {

        $config = json_decode($config, true);

        $compatibilitychecker = new \local_sitesync\CompatibilityCheck();

        $compatibiltyinfo = $compatibilitychecker->getAllCompatibilityInfo();

        $response = [];

        $response["slaveinfo"] = $config;
        $response["msterinfo"] = $compatibiltyinfo;
        foreach ($config as $key => $value) {

            if ($value != $compatibiltyinfo[$key]) {
                $response["status"] = false;
                $response["failedkey"] = $key;
                try {
                    $response["message"] = get_string($key."_fail", 'local_sitesync');
                } catch (\Exception $e) {
                    $response["message"] = get_string("nocompatiblesites", 'local_sitesync');
                }

                break;
            }
            $response["status"] = true;
        }

        return $response;
    }
}
