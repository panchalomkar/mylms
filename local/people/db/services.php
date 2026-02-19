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
 * Report insights webservice definitions.
 *
 * @package    block_currentcourses
 * @copyright  Daniel Neis Araujo <danielneis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$functions = array(
    'local_people_get_people' => array(
        'classname'   => 'local_people_external',
        'methodname'  => 'get_people',
        'classpath' => 'local/people/externallib.php',
        'description' => 'Return data as JSON for given report ID.',
        'type'        => 'read',
        'ajax'        => true,
        'moodlewsrestformat' => 'json',
    ),
    'local_people_get_local_people' => array(
        'classname'   => 'external',
        'methodname'  => 'get_local_people',
        'classpath' => 'local/people/externallib.php',
        'description' => 'Return data as JSON for given report ID.',
        'type'        => 'read',
        'ajax'        => true,
        'moodlewsrestformat' => 'json',
    ),
    
);
// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'Local People Services' => array(
        'functions' => array(
            'local_people_get_people',
            'local_people_get_local_people'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);
