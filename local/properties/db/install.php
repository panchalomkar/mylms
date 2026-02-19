<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_properties_install() {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    // Define cohort_info_category table scheme.
    $table = new xmldb_table('cohort_info_category');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_NOTNULL, null);
    $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null,'0');
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

    // Conditionally launch create table.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Define cohort_info_data table scheme.
    $table = new xmldb_table('cohort_info_data');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,'0'); 
    $table->add_field('data', XMLDB_TYPE_TEXT, 'long', XMLDB_NOTNULL, null,'0');
    $table->add_field('dataformat', XMLDB_TYPE_INTEGER, '2', XMLDB_NOTNULL,null,'0');
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

    // Conditionally launch create table.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Define cohort_info_field table scheme.
    $table = new xmldb_table('cohort_info_field');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 'shortname');
    $table->add_field('name', XMLDB_TYPE_TEXT, 'long', XMLDB_UNSIGNED, XMLDB_NOTNULL, null); 
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

    // Conditionally launch create table.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Define course_info_category table scheme.
    $table = new xmldb_table('course_info_category');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10',XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_NOTNULL, null);
    $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null,'0');
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

    // Conditionally launch create table.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Define course_info_data table scheme.
    $table = new xmldb_table('course_info_data');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,'0'); 
    $table->add_field('data', XMLDB_TYPE_TEXT, 'long', XMLDB_NOTNULL, null,'0');
    $table->add_field('dataformat', XMLDB_TYPE_INTEGER, '2', XMLDB_NOTNULL,null,'0');
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_index('courseid_fieldid_unique', XMLDB_INDEX_UNIQUE, array('courseid','fieldid'));
    
    // Conditionally launch create table.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Define course_info_field table scheme.
    $table = new xmldb_table('course_info_field');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 'shortname');
    $table->add_field('name', XMLDB_TYPE_TEXT, 'long', XMLDB_UNSIGNED, XMLDB_NOTNULL, null); 
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

    // Conditionally launch create table.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Learning paths info category table
    $table = new xmldb_table('lp_info_category');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_NOTNULL, null);
    $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null,'0'); 
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

    // Conditionally launch create table.
     if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }
    
    // Learning paths info data table
    $table = new xmldb_table('lp_info_data');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('lpid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,'0'); 
    $table->add_field('data', XMLDB_TYPE_TEXT, 'long', XMLDB_NOTNULL, null,'0');
    $table->add_field('dataformat', XMLDB_TYPE_INTEGER, '2', XMLDB_NOTNULL,null,'0');  
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

    // Conditionally launch create table.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Learning paths info field table.
    $table = new xmldb_table('lp_info_field');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 'shortname');
    $table->add_field('name', XMLDB_TYPE_TEXT, 'long', XMLDB_UNSIGNED, XMLDB_NOTNULL, null); 
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

    // Conditionally launch create table.
     if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }
}

