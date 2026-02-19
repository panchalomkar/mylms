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
 * Process ajax requests
 *
 * @copyright Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_feedback
 */

/*if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}*/

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('lib.php');

$compact = required_param('compact', PARAM_TEXT);

if($compact == "compact"){
	set_user_preference('leftcompact', TRUE);
} else {
	set_user_preference('leftcompact', FALSE);
}

echo get_user_preferences('leftcompact');

die;
