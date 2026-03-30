<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_learningpaths_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    if ($oldversion < 2018011704) {
        // Define learningpaths table scheme.
        $table = new xmldb_table('learningpaths');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null, '');
        $table->add_field('startdate', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('companyid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('image', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('credits', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define learningpath_course_groups table scheme.
        $table = new xmldb_table('learningpath_course_groups');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('learningpath_courseid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define learningpath_course_prereq table scheme.
        $table = new xmldb_table('learningpath_course_prereq');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('learningpath_courseid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('prerequisite', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define learningpath_courses table scheme.
        $table = new xmldb_table('learningpath_courses');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('learningpathid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('required', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('position', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define learningpath_image table scheme.
        $table = new xmldb_table('learningpath_image');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('learningpathid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define learningpath_users table scheme.
        $table = new xmldb_table('learningpath_users');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('learningpathid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('enrollment_date', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2018011704, 'local', 'learningpaths');
    }

    // Define learningpath_cohorts table scheme.
    if ($oldversion < 2018011800) {
        $table = new xmldb_table('learningpath_cohorts');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('learningpathid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('enrollment_date', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2018011800, 'local', 'learningpaths');
    }

    // Define learningpath_enrollments table scheme.
    if ($oldversion < 2018011902) {
        $table = new xmldb_table('learningpath_enrollments');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '255', null, null, null);
        $table->add_field('user_relationid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('learningpath_courseid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('enrollment_date', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2018011902, 'local', 'learningpaths');
    }

    // Define learningpath_completion table scheme.
    if ($oldversion < 2018021500) {
        $table = new xmldb_table('learningpath_completion');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('learningpathid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('completion_date', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2018021500, 'local', 'learningpaths');
    }
    
    if ($oldversion < 2018022000) {
    	$table = new xmldb_table('learningpaths');
    	$self_enrollment = new xmldb_field('self_enrollment', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null,'0');
    	 // Add field if it doesn't already exist.
        if (!$dbman->field_exists($table, $self_enrollment)) {
            $dbman->add_field($table, $self_enrollment);
        }

        upgrade_plugin_savepoint(true, 2018022000, 'local', 'learningpaths');
    }

    if($oldversion < 2018030106) {

        $table = new xmldb_table('lp_info_field');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 'shortname');
        $table->add_field('name', XMLDB_TYPE_TEXT, 'long', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0'); 
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('description', XMLDB_TYPE_TEXT, 'long');  
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0'); 
        $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0'); 
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('required', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('locked', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0'); 
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0'); 
        $table->add_field('forceunique', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0'); 
        $table->add_field('defaultdata', XMLDB_TYPE_TEXT, 'long'); 
        $table->add_field('defaultdataformat', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('param1', XMLDB_TYPE_TEXT, 'long');
        $table->add_field('param2', XMLDB_TYPE_TEXT, 'long');
        $table->add_field('param3', XMLDB_TYPE_TEXT, 'long');
        $table->add_field('param4', XMLDB_TYPE_TEXT, 'long');
        $table->add_field('param5', XMLDB_TYPE_TEXT, 'long');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Add field if it doesn't already exist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    
        $table = new xmldb_table('lp_info_data');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('lpid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,'0'); 
        $table->add_field('data', XMLDB_TYPE_TEXT, 'long', XMLDB_NOTNULL, null,'0');
        $table->add_field('dataformat', XMLDB_TYPE_INTEGER, '2', XMLDB_NOTNULL,null,'0');  
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
         
        // Add field if it doesn't already exist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
   
        $table = new xmldb_table('lp_info_category');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_NOTNULL, null,'0');
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null,'0'); 
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
         
        // Add field if it doesn't already exist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2018030106, 'local', 'learningpaths');
    }  
    
    if ($oldversion < 2018030107) {
        $table = new xmldb_table('learningpath_notifications');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('learningpathid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('config', XMLDB_TYPE_TEXT);
        $table->add_field('type', XMLDB_TYPE_CHAR,'100');
        $table->add_field('cron', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        
        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        $table = new xmldb_table('learningpath_noti_log');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('notificationid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('sent', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('datesent', XMLDB_TYPE_CHAR,'100');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        
        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        $table = new xmldb_table('learningpath_cron_log');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('learningpathid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('config', XMLDB_TYPE_TEXT);
        $table->add_field('type', XMLDB_TYPE_CHAR,'100');
        $table->add_field('cron', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2018030107, 'local', 'learningpaths');
    }
    // update the new version 2018030108
    if ($oldversion < 2018030108) {

        upgrade_plugin_savepoint(true, 2018030108, 'local', 'learningpaths');
    }
    if ($oldversion < 2018030110) {

        upgrade_plugin_savepoint(true, 2018030110, 'local', 'learningpaths');
    }
    //updated 
    if ($oldversion < 2019121300) {

        upgrade_plugin_savepoint(true, 2019121300, 'local', 'learningpaths');
    }

    //course_active field id in lp course table 
    if ($oldversion < 2019121301) {

        // Add the submarket field
        $table = new xmldb_table('learningpath_courses');
        $field = new xmldb_field('course_active', XMLDB_TYPE_INTEGER, '2', null, null, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2019121301, 'local', 'learningpaths');
    }
    if ($oldversion < 2019121302) {
      upgrade_plugin_savepoint(true, 2019121302, 'local', 'learningpaths');
    }
    if ($oldversion < 2019121303) {
      upgrade_plugin_savepoint(true, 2019121303, 'local', 'learningpaths');
    }

    if ($oldversion < 2019121305) {
      upgrade_plugin_savepoint(true, 2019121305, 'local', 'learningpaths');
    }

    // Commented following code as it is already added above
    // if ($oldversion < 2019121305) {
    //     upgrade_plugin_savepoint(true, 2019121305, 'local', 'learningpaths');
    // }

    if ($oldversion < 2019121306) {
        if(!$DB->get_record_sql('SELECT id FROM {external_functions} WHERE name = ?', array('local_learningpath_duplicatecheck'))){
            $data       = new stdClass();
            
            $data->name             =   "local_learningpath_duplicatecheck";
            $data->classname        =   "local_learningpath_external";
            $data->methodname       =   "duplicatecheck";
            $data->classpath        =   "local/learningpaths/externallib.php";
            $data->component        =   "local_learningpaths";
            $data->capabilities     =   "";
            $data->services         =   "";
     
            $DB->insert_record('external_functions', $data);
        }
        upgrade_plugin_savepoint(true, 2019121306, 'local', 'learningpaths');
    }
    if ($oldversion < 2019121307) {
        upgrade_plugin_savepoint(true, 2019121307, 'local', 'learningpaths');
    }
    if ($oldversion < 2019121308) {
        upgrade_plugin_savepoint(true, 2019121308, 'local', 'learningpaths');
    }


    if ($oldversion < 2019121309) {

        // Define field status to be added to mydashboard.
        $table = new xmldb_table('learningpaths');
        $field = new xmldb_field('publish', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', null);

        // Conditionally launch add field licenseid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // mydashboard savepoint reached.
        upgrade_plugin_savepoint(true, 2019121309, 'local', 'learningpaths');
    }
 if ($oldversion < 2026032700) {

        $table = new xmldb_table('learningpaths');
        $field = new xmldb_field('publish', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026032700, 'local', 'learningpaths');
    }

    return true;
}
