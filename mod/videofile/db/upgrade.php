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
 * This file keeps track of upgrades to the videofile module.
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_videofile
 * @copyright  2013 Jonas Nockert <jonasnockert@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute videofile upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_videofile_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    // Added width and height fields.
    if ($oldversion < 2013071701) {
        $table = new xmldb_table('videofile');
        $widthfield = new xmldb_field('width',
                                      XMLDB_TYPE_INTEGER,
                                      '4',
                                      XMLDB_UNSIGNED,
                                      XMLDB_NOTNULL,
                                      null,
                                      '800',
                                      'introformat');
        $heightfield = new xmldb_field('height',
                                       XMLDB_TYPE_INTEGER,
                                       '4',
                                       XMLDB_UNSIGNED,
                                       XMLDB_NOTNULL,
                                       null,
                                       '500',
                                       'width');


        // Add width field.
        if (!$dbman->field_exists($table, $widthfield)) {
            $dbman->add_field($table, $widthfield);
        }

        // Add height field.
        if (!$dbman->field_exists($table, $heightfield)) {
            $dbman->add_field($table, $heightfield);
        }

        /* Once we reach this point, we can store the new version and
           consider the module upgraded to the version 2013071701 so the
           next time this block is skipped. */
        upgrade_mod_savepoint(true, 2013071701, 'videofile');
    }

    // Added responsive flag field.
    if ($oldversion < 2013092200) {
        $table = new xmldb_table('videofile');
        $responsivefield = new xmldb_field('responsive',
                                           XMLDB_TYPE_INTEGER,
                                           '4',
                                           XMLDB_UNSIGNED,
                                           XMLDB_NOTNULL,
                                           null,
                                           '0',
                                           'height');

        // Add field if it doesn't already exist.
        if (!$dbman->field_exists($table, $responsivefield)) {
            $dbman->add_field($table, $responsivefield);
        }

        /* Once we reach this point, we can store the new version and
           consider the module upgraded to the version 2013092200 so the
           next time this block is skipped. */
        upgrade_mod_savepoint(true, 2013092200, 'videofile');
    }

    /**
     * Add The video url to database table
     * @author Andres Ag.
     * @since 22/05/2015
     * @paradiso
     */
    // Added video url field.
    if ($oldversion < 2015052202) {
        $table = new xmldb_table('videofile');
        $responsivefield = new xmldb_field('video_url',
                                           XMLDB_TYPE_CHAR,
                                           '255',
                                           XMLDB_NOTNULL,
                                           null,
                                           '0',
                                           '');

        // Add field if it doesn't already exist.
        if (!$dbman->field_exists($table, $responsivefield)) {
            $dbman->add_field($table, $responsivefield);
        }

        /* Once we reach this point, we can store the new version and
           consider the module upgraded to the version 2013092200 so the
           next time this block is skipped. */
        upgrade_mod_savepoint(true, 2015052202, 'videofile');
    }

    // Added video url field.
    if ($oldversion < 2015052203) {
        $table = new xmldb_table('videofile');
        $responsivefield = new xmldb_field('video_type',
                                           XMLDB_TYPE_INTEGER,
                                           '4',
                                           XMLDB_UNSIGNED,
                                           XMLDB_NOTNULL,
                                           null,
                                           '0');

        // Add field if it doesn't already exist.
        if (!$dbman->field_exists($table, $responsivefield)) {
            $dbman->add_field($table, $responsivefield);
        }

        /* Once we reach this point, we can store the new version and
           consider the module upgraded to the version 2013092200 so the
           next time this block is skipped. */
        upgrade_mod_savepoint(true, 2015052203, 'videofile');
    }

    /**
     * This new version Add table of the video attempts
     * @author Andres
     * @since 26/05/2015
     * @paradiso
     */
    if ($oldversion < 2015052701) {
        //Create the table xml
        $table = new xmldb_table('video_attempts');

        // Adding fields to table.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL,
                           XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL,
                           null, '0');
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL,
                           null, '0');
        $table->add_field('second', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL,
                           null, '0');
        $table->add_field('percentage', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL,
                           null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL,
                           null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL,
                           null, '0');

        // Adding keys to table.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2015052701, 'videofile');
    }

    /**
     * This new version Fix problems with the video_url field
     * @author Andres
     * @since 12/08/2015
     * @paradiso
     */
    if ($oldversion < 2015081200) {
      $table = new xmldb_table('videofile');
      $field = new xmldb_field('video_url',
                                           XMLDB_TYPE_CHAR,
                                           '255',
                                           null,
                                           null,
                                           null);
      if(!$dbman->field_exists($table, $field)){
        $dbman->add_field($table, $field);
      }
      upgrade_mod_savepoint(true, 2015081200, 'videofile');
    }

    /**
     * Add field for save the minumun progress
     * @author ♦ Andres ♦
     * @since April 18 of 2016
     * @paradiso
     */
    if($oldversion < 2016041800) {
      $table = new xmldb_table('videofile');
      $field = new xmldb_field('videoprogress',
                                           XMLDB_TYPE_INTEGER,
                                           '3',
                                           XMLDB_UNSIGNED,
                                           XMLDB_NOTNULL,
                                           null,
                                           '0');
      if(!$dbman->field_exists($table, $field)){
        $dbman->add_field($table, $field);
      }
      upgrade_mod_savepoint(true, 2016041800, 'videofile');
    }

    /**
     * Add field for save the forward option
     * @author ♦ Andres ♦
     * @since April 19 of 2016
     * @paradiso
     */

    if($oldversion < 2016041904) {
      $table = new xmldb_table('videofile');
      $field = new xmldb_field('forward',
                                           XMLDB_TYPE_INTEGER,
                                           '1',
                                           XMLDB_UNSIGNED,
                                           XMLDB_NOTNULL,
                                           null,
                                           '1');
      if(!$dbman->field_exists($table, $field)){
        $dbman->add_field($table, $field);
      }
      upgrade_mod_savepoint(true, 2016041904, 'videofile');
    }

    if($oldversion < 2018041912) {
      
        if(!$DB->get_record_sql('SELECT id FROM {external_functions} WHERE name = ?', array('mod_videofile_data_for_video_attempt'))){
            $data = new stdClass();
            
            $data->name             =   "mod_videofile_data_for_video_attempt";
            $data->classname        =   "mod_videofile_external";
            $data->methodname       =   "data_for_video_attempt";
            $data->classpath        =   "mod/videofile/externallib.php";
            $data->component        =   "mod_videofile";
            $data->capabilities     =   "";
            $data->services         =   "";
     
            $DB->insert_record('external_functions', $data);
        }
       upgrade_mod_savepoint(true, 2018041912, 'videofile');
    

    }

    // Final return of upgrade result (true, all went good) to Moodle.
    return true;
}
