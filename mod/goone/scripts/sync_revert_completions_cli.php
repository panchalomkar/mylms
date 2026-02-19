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
 * A one-off script to revert GO1 activity completions in Moodle that have been awarded by error.
 *
 * @package   mod_goone
 * @author    2024 Kirill Astashov <kirill.astashov@androgogic.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__file__)))) . '/config.php');

// Siteadmin user is needed for the completion override param.
$admins = explode(',', get_config('moodle', 'siteadmins'));
$USER = $DB->get_record('user', array('id' => $admins[0]));

require_once(dirname(__file__) . '/sync_revert_completions.php');

