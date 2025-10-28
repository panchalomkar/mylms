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
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 *
 * @package    local_team
 * @copyright  2019 Cohort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// We defined the web service functions to install.
$functions = array(
    'local_reward_points_user' => array(
        'classname' => 'local_mydashboard_external',
        'methodname' => 'user_reward_points',
        'classpath' => 'local/mydashboard/externallib.php',
        'description' => 'Users reward points',
        'type' => 'read',
        'capabilities' => '',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'local_reward_get_leaderboard' => array(
        'classname' => 'local_mydashboard_external',
        'methodname' => 'get_leaderboard',
        'classpath' => 'local/mydashboard/externallib.php',
        'description' => 'Users leaderboard',
        'type' => 'read',
        'capabilities' => '',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'local_reward_add_point' => array(
        'classname' => 'local_mydashboard_external',
        'methodname' => 'add_point',
        'classpath' => 'local/mydashboard/externallib.php',
        'description' => 'Add point',
        'type' => 'read',
        'capabilities' => '',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'local_reward_scratch_card' => array(
        'classname' => 'local_mydashboard_external',
        'methodname' => 'scratch_card',
        'classpath' => 'local/mydashboard/externallib.php',
        'description' => 'Get user scratch cards',
        'type' => 'read',
        'capabilities' => '',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
//$services = array(
//    'reward service' => array(
//        'functions' => array(
//            'local_reward_points_user',
//        ),
//        'restrictedusers' => 1,
//        'enabled' => 1,
//    )
//);


