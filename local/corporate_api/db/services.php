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
 * Declare web services
 *
 * @package    local_corporate_api
 * @copyright  2018 Rajveer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$functions = array(

    'local_corporate_api_create_coursesapi' => array (

        'classname' => 'local_corporate_api\external\api',
        'methodname' => 'get_courses_detail',
        'classpath' => '',
        'description' => 'Creates new courses detail api.',
        'type'        => 'read',
        'ajax'        => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'local_corporate_api_create_profileapi' => array(

        'classname' => 'local_corporate_api\external\api',
        'methodname' => 'get_profile_detail',
        'classpath' => '',
        'description' => 'Creates new profile api.',
        'type'        => 'read',
        'ajax'        => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'local_corporate_api_create_learningpathapi' => array(
        // The name of the namespaced class that the function is located in.
        'classname' => 'local_corporate_api\external\api',
        'methodname' => 'get_learningpath_detail',
        'classpath' => '',
        'description' => 'Creates new learningpath api.',
        'type'        => 'read',
        'ajax'        => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'local_corporate_api_create_livesessionapi' => array(
        // The name of the namespaced class that the function is located in.
        'classname' => 'local_corporate_api\external\api',
        'methodname' => 'get_livesession_detail',
        'classpath' => '',
        'description' => 'Create new live session api.',
        'type'        => 'read',
        'ajax'        => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'local_corporate_api_course_contentapi' => array (
        // The name of the namespaced class that the function is located in.
        'classname' => 'local_corporate_api\external\api',
        'methodname' => 'get_course_content_detail',
        'classpath' => '',
        // A brief, human-readable, description of the web service function.
        'description' => 'Create course content api.',
        'type'        => 'write',
        'ajax'        => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),

    ),

    'local_corporate_api_course_reportapi' => array (
        // The name of the namespaced class that the function is located in.
        'classname' => 'local_corporate_api\external\api',
        'methodname' => 'get_course_report_detail',
        'classpath' => '',
        // A brief, human-readable, description of the web service function.
        'description' => 'Create course report api.',
        'type'        => 'write',
        'ajax'        => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),

    ),

    'local_corporate_api_user_reportapi' => array (
        // The name of the namespaced class that the function is located in.
        'classname' => 'local_corporate_api\external\api',
        'methodname' => 'get_user_report_detail',
        'classpath' => '',
        // A brief, human-readable, description of the web service function.
        'description' => 'Create user report api.',
        'type'        => 'write',
        'ajax'        => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),

    ),

);
  


