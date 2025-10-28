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
 * Web service declarations
 *
 * @package    local_sitesetting
 * @copyright  2020 Akash Uphade (akash.u@paradisosolutions.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');


class local_mt_dashboard_external extends external_api {

    public static function searchtenant_parameters() {
        return new external_function_parameters(
            array(
                 'searchdata' => new external_value(PARAM_TEXT, 'attempt id',VALUE_OPTIONAL),
                'suspend' => new external_value(PARAM_INT, 'ID of user role',VALUE_OPTIONAL)
            )
        );
    }



    /**
     * Get saved settings
     * @param int $roleid 
     * @return settings array
     * @throws invalid_parameter_exception
     */
    public static function searchtenant($searchdata,$suspend) {
        global $SESSION , $CFG , $DB;
        //require_once( '../../../config.php');
        require_once($CFG->dirroot.'/local/mt_dashboard/lib.php');


        // Validate params
        $params = self::validate_parameters(self::searchtenant_parameters(), ['searchdata' => $searchdata,'suspend' => $suspend]);

        $total_tenants = get_all_companies( $params['suspend'] , $startfrom=0, $RECORD_PER_PAGE=0 , $params['searchdata'], $add_limit = 0 );

        $t1 = array_values($total_tenants);
        $t2 = array_keys($total_tenants);
        
        $return['val'] = $t1[0];
        $return['id'] = $t2[0];
        
     //   print_r($return); die;
         return $return;
    }

        public static function searchtenant_returns() {
        
            return new external_single_structure(
                array(
                      'id' => new external_value(PARAM_INT, 'ID of user role',VALUE_OPTIONAL),
                      'val' => new external_value(PARAM_TEXT, 'ID of user role',VALUE_OPTIONAL),

                )
            );
        
    }
}