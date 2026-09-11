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
 * RAP Mobile API Plugin
 * Complete REST API for Mobile App
 * Created by Omkar
 *
 * @package    local_rapmobile
 * @copyright  2024 RAP Tech Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_rapmobile';
$plugin->version   = 2024060302; // 2024060302: FCM push for all 22 notification types
$plugin->requires  = 2022041900; // Moodle 4.0+
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';