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


defined('MOODLE_INTERNAL') || die();
$tasks = [
    [
        'classname' => 'local_custom_notification\task\course_not_completed_notification',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '0',
        'day' => '1',
        'month' => '0',
        'dayofweek' => '0',
    ],
    [
        'classname' => 'local_custom_notification\task\course_in_progress_notification',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '0',
        'day' => '1',
        'month' => '0',
        'dayofweek' => '0',
    ],
    [
        'classname' => 'local_custom_notification\task\course_expiration_notification',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '0',
        'day' => '1',
        'month' => '0',
        'dayofweek' => '0',
    ],
    [
        'classname' => 'local_custom_notification\task\not_loggedin_notification',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '0',
        'day' => '1',
        'month' => '0',
        'dayofweek' => '0',
    ],
];
