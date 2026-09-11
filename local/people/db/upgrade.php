<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Upgrade script for local_people.
 *
 * @package    local_people
 * @copyright  2024 People Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_people_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026041500) {
        // Moodle 5.0.2 compatibility update.
        // No schema changes needed - this is a code-only update.
        upgrade_plugin_savepoint(true, 2026041500, 'local', 'people');
    }

    return true;
}
