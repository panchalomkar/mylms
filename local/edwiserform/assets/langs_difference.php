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
 * Check for language differences.
 *
 * This script will help to find difference between in en lang file against other lang file with following condition
 * It will copy newly added string from en to target file. If any string is removed from en then it will mark same id
 * in target file.
 *
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

/**
 * #     # ####### ####### #######
 * ##    # #     #    #    #
 * # #   # #     #    #    #
 * #  #  # #     #    #    #####
 * #   # # #     #    #    #
 * #    ## #     #    #    #
 * #     # #######    #    ####### : Put this file in lang folder and check langs array then run.
 */

define('ROOT', '/var/www/html/m37dev');

define('PLUGIN_TYPE', 'local');

define('PLUGIN_NAME', 'edwiserform');

define('COMPONENT', PLUGIN_TYPE . '_' . PLUGIN_NAME);

define('PATH', ROOT . '/' . PLUGIN_TYPE . '/' . PLUGIN_NAME . '/lang/');

define('FILENAME', COMPONENT . '.php');

define('MOODLE_INTERNAL', true);

defined('MOODLE_INTERNAL') || die;

$langs = ['de', 'es', 'fr', 'pl'];

foreach ($langs as $target) {
    // Get target file string.
    $string = [];
    require(PATH . $target . '/' . FILENAME);
    $targetobj = $string;

    // Get source file string - en.
    $string = [];
    require(PATH . 'en/' . FILENAME);
    $sourceobj = $string;

    // Get missing strings.
    $diff = array_diff_key($sourceobj, $targetobj);
    $filecontent = file_get_contents(PATH . $target.'/'.FILENAME);
    foreach ($diff as $key => $value) {

        // If string is alread added then skip.
        if (isset($targetobj["newstring_".$key])) {
            continue;
        }

        // Append missing string in target file content.
        $filecontent .= "
\$string['newstring_" . $key . "'] = '" . $value . "';";
    }

    // Get those strings which are deleted in en file.
    $diff = array_diff_key($targetobj, $sourceobj);
    foreach ($diff as $key => $value) {

        // If it is already marked the skip.
        if (strpos($key, "newstring_") !== false || strpos($key, "delete-string-id-") !== false) {
            continue;
        }
        $filecontent = str_replace($key, "delete-string-id-".$key, $filecontent);
    }
    file_put_contents(PATH . $target . '/' . FILENAME, $filecontent);
}
echo "Succefully copied";
