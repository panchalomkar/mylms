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

$functions = [
    'local_sitesync_check_connection' => [
        'classname' => 'local_sitesync\external\check_connection',
        'methodname' => 'execute',
        'description' => 'Check if we can connect with the site',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_sitesync_save_config' => [
        'classname' => 'local_sitesync\external\save_config',
        'methodname' => 'execute',
        'description' => 'Save multiple plugin configurations',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/site:config'
    ],
    'local_sitesync_generate_keys' => [
        'classname' => 'local_sitesync\external\generate_keys',
        'methodname' => 'execute',
        'description' => 'Generate encryption key pair',
        'type' => 'write',
        'ajax' => true
    ],
    'local_sitesync_do_sync_action' => [
        'classname' => 'local_sitesync\external\do_sync_action',
        'methodname' => 'execute',
        'description' => 'Perform sync related actions',
        'type' => 'write',
        'ajax' => true
    ],
    'local_sitesync_compatibility_checker' => [
        'classname' => 'local_sitesync\external\compatibility_checker',
        'methodname' => 'execute',
        'description' => 'Check compatibility of both sites',
        'type' => 'write',
        'ajax' => true
    ]
];
