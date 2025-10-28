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
 * Reports block external apis
 *
 * @package     local_corporate_api
 * @copyright   2019 wisdmlabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_corporate_api\external;

defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use context_system;
use stdClass;
use core_user;
use core_completion\progress;
require_once("{$CFG->libdir}/completionlib.php");
/**
 * Trait implementing the external function local_corporate_api_complete_edwiserreports_installation.
 */
trait get_profile_detail {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function get_profile_detail_parameters() {
        return new external_function_parameters(
            array (
                'userid' => new external_value(PARAM_INT, 'User id')
            )
        );
    }

    /**
     * Complete edwiser report installation
     *
     * @return object Configuration
     */
    public static function get_profile_detail($userid) {
        global $DB,$CFG;
        require_once($CFG->dirroot."/local/corporate_api/lib.php");
        $get_userprofile = get_user_profile($userid);
        $get_courseprogress = get_course_progress($userid);
        $response = array(
            "user_info" => $get_userprofile['userinfo'],
            "achievements" => $get_userprofile['achievement'],
            "course_progress" => $get_courseprogress,
        );

        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8"); 
        echo json_encode($response);
        die;
        return $response;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function get_profile_detail_returns() {
        return new external_single_structure(
            array(
                'user_info' => new external_value(PARAM_RAW, 'Status', null),
                'achievements' => new external_value(PARAM_RAW, 'Status', null),
                'course_progress' => new external_value(PARAM_RAW, 'Status', null)
            )
        );
    }
}
