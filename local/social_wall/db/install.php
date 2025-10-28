<?php
/**
 * Social wall plugin installation.
 * @package local_social_wall
 * @author Manisha M
 * @paradiso
*/
defined('MOODLE_INTERNAL') || die();

function xmldb_local_social_wall_install() {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    // Define social_wall_comments table scheme.
    $table = new xmldb_table('social_wall_comments');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('datecreated', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1269249260');
    $table->add_field('datemodified', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1269249260');
    $table->add_field('activity_id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('course_id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('comment', XMLDB_TYPE_TEXT, 'medium', null, null, null);
    $table->add_field('msg_id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
    $table->add_field('uid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
    $table->add_field('ip', XMLDB_TYPE_CHAR, '45', null, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('foreignkey1', XMLDB_KEY_FOREIGN, array('msg_id'),'social_wall_messages',array('msg_id'));
    $table->add_key('foreignkey2', XMLDB_KEY_FOREIGN, array('uid'),'user',array('id'));

    // Conditionally launch create table.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Define social_wall_messages table scheme.
    $table = new xmldb_table('social_wall_messages');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('datecreated', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1269249260');
    $table->add_field('datemodified', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1269249260');
    $table->add_field('course_id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0);
    $table->add_field('uid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
    $table->add_field('activity_id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('message', XMLDB_TYPE_TEXT, 'medium', null, null, null);
    $table->add_field('ip', XMLDB_TYPE_CHAR, '45', null, null, null);
    $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('companyid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, '0');
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('foreignkey1', XMLDB_KEY_FOREIGN, array('uid'),'user',array('id'));

    // Conditionally launch create table.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Define social_wall_content table scheme.
    $table = new xmldb_table('social_wall_content');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('content', XMLDB_TYPE_TEXT, 'long');
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

    // Conditionally launch create table.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Define social_wall_ratings table scheme.
    $table = new xmldb_table('social_wall_ratings');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('datecreated', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1269249260');
    $table->add_field('datemodified', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1269249260');
    $table->add_field('rating', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
    $table->add_field('ip', XMLDB_TYPE_CHAR, '45', null, null, null);
    $table->add_field('msg_id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    
    // Conditionally launch create table.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    
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
}

