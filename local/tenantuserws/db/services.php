<?php

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
 * Web service local plugin template external functions and service definitions.
 *
 * @author     VaibhavG
 * @package    tenant api
 * @since      22 July 2019
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'tenent_users_data' => array(
            'classname' => 'local_tenantws_external',
            'methodname' => 'get_tenant_users',
            'description' => 'get tenant user data',
            'type'        => 'read',
	         'classpath'   => 'local/tenantuserws/externallib.php',
          'services'     => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        ),
        'tenent_cat_data' => array(
            'classname' => 'local_tenantws_external',
            'methodname' => 'get_tenant_cat',
            'description' => 'get tenant categories data',
            'type'        => 'read',
	         'classpath'   => 'local/tenantuserws/externallib.php',
           'services'     => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        ),
        'tenent_course_data' => array(
         'classname' => 'local_tenantws_external',
            'methodname' => 'get_tenant_course',
            'description' => 'get tenant course data',
            'type'        => 'read',
	        'classpath'   => 'local/tenantuserws/externallib.php',
          'services'     => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        ),
);      

//We define the services to install as pre-build services. A pre-build service is not editable by administrator.
// $services = array(
//        'tenant_service' => array(
//                'functions' => array ('tenent_users_data','tenent_cat_data','tenent_course_data'),
//                'restrictedusers' => 0,
//                'enabled'=>1,
//         )
// );
