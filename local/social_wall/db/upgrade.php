<?php
defined('MOODLE_INTERNAL') || die(); 

/**
 * 
 * 
 */
function xmldb_local_social_wall_upgrade( $oldversion = 0 ){
    
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2018030113) {
        // Code here
        upgrade_plugin_savepoint(true, 2018030113, 'local', 'social_wall');
    }

    if ($oldversion < 2018030114) {
        
        // Define social_wall_log table scheme.
        $table = new xmldb_table('social_wall_log');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '50', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '');
        $table->add_field('action', XMLDB_TYPE_CHAR, '100', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '');
        $table->add_field('crud', XMLDB_TYPE_CHAR, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('postid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('contextlevel', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('datecreated', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1269249260');
        $table->add_field('ip', XMLDB_TYPE_CHAR, '45', null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        
        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2018030114, 'local', 'social_wall');
    }

    if ($oldversion < 2018030115) {
        
        // Define social_wall_messages table scheme.
        $table = new xmldb_table('social_wall_messages');
        $field = new xmldb_field('companyid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, '0');
        
        // Conditionally launch add field location.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2018030115, 'local', 'social_wall');

        $sql = "ALTER TABLE {social_wall_messages} CHANGE companyid companyid BIGINT(10) NULL DEFAULT '0'";
		$update =$DB->execute($sql);
    }

    if ($oldversion < 2018030116) {
        
        // Define social_wall_comments table scheme.
        $social_wall_comments_table = new xmldb_table('social_wall_comments');
        $social_wall_comments_field = new xmldb_field('viewed', XMLDB_TYPE_INTEGER, '1', null, null, null, null, null);

        // Define social_wall_ratings table scheme.
        $social_wall_ratings_table = new xmldb_table('social_wall_ratings');
        $social_wall_ratings_field = new xmldb_field('viewed', XMLDB_TYPE_INTEGER, '1', null, null, null, null, null);
        
        // Conditionally launch add field location.
        if (!$dbman->field_exists($social_wall_comments_table, $social_wall_comments_field)) {
            $dbman->add_field($social_wall_comments_table, $social_wall_comments_field);
        }
        // Conditionally launch add field location.
        if (!$dbman->field_exists($social_wall_ratings_table, $social_wall_ratings_field)) {
            $dbman->add_field($social_wall_ratings_table, $social_wall_ratings_field);
        }

        upgrade_plugin_savepoint(true, 2018030116, 'local', 'social_wall');
    }
}