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
 * @package local_competency
 * @copyright  Nilesh
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();
 function xmldb_local_competency_upgrade($oldversion) {
     global $CFG, $DB;
     $result = true;
     $dbman = $DB->get_manager();
 
     if ($oldversion < 2017011301) {
 
         // Define field status to be added to competency.
         $table = new xmldb_table('competency_title');
         $field = new xmldb_field('companyid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', null);
 
         // Conditionally launch add field licenseid.
         if (!$dbman->field_exists($table, $field)) {
             $dbman->add_field($table, $field);
         }
 
         // competency savepoint reached.
         upgrade_plugin_savepoint(true, 2017011301, 'local', 'competency');
     }

     if ($oldversion < 2017011302) {
        // Define the SQL to create your new table
        $table = new xmldb_table('roi_calculate_form');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('maincomid', XMLDB_TYPE_INTEGER, '20', XMLDB_NOTNULL, XMLDB_NOTNULL, null, null);
        $table->add_field('trainingbudegt', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('department', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('totalbudegt', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('noemployeeid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('budegtperempt', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('startdate', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enddate', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        
        // Add other fields as needed
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Create the table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Update the old version number
        upgrade_plugin_savepoint(true, 2017011302, 'local', 'competency');

        // Add any other necessary upgrade steps

    }

    
    if ($oldversion < 2017011304) {
 
        // Define field status to be added to competency.
        $table = new xmldb_table('roi_calculate_form');
        $field = new xmldb_field('duration', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', null);
        // Conditionally launch add field licenseid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // competency savepoint reached.
        upgrade_plugin_savepoint(true, 2017011304, 'local', 'competency');
    }
    if ($oldversion < 2017011305) {
 
        // Define field status to be added to competency.
        $table = new xmldb_table('manager_rating');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', null);
        // Conditionally launch add field licenseid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // competency savepoint reached.
        upgrade_plugin_savepoint(true, 2017011305, 'local', 'competency');
    }

     return $result;
 }