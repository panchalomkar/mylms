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
 * @package   local_mydashboard
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
function xmldb_local_mydashboard_upgrade($oldversion) {
    global $CFG, $DB;
    $result = true;
    $dbman = $DB->get_manager();
    if ($oldversion < 2024010105) {
        // Define the SQL to create your new table
        $table = new xmldb_table('send_request');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);
        $table->add_field('for_userid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('from_userid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('to_userid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        
        // Add other fields as needed
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Create the table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Update the old version number
        upgrade_plugin_savepoint(true, 2024010105, 'local', 'mydashboard');

        // Add any other necessary upgrade steps

    }

    if ($oldversion < 2024010106) {

        // Define field status to be added to mydashboard.
        $table = new xmldb_table('send_request');
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', null);

        // Conditionally launch add field licenseid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // mydashboard savepoint reached.
        upgrade_plugin_savepoint(true, 2024010106, 'local', 'mydashboard');
    }

    if ($oldversion < 2024010108) {

        // Define field status to be added to mydashboard.
        $table = new xmldb_table('send_request');
        $field = new xmldb_field('purpose', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', null);

        // Conditionally launch add field licenseid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // mydashboard savepoint reached.
        upgrade_plugin_savepoint(true, 2024010108, 'local', 'mydashboard');
    }

    return $result;
}
