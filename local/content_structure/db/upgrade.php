<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade script for local_content_structure
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_content_structure_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // ---- Add 'link' field + create playlist table ----
    if ($oldversion < 2020091006) {

        // Add link field
        $table = new xmldb_table('local_content_structure');
        $field = new xmldb_field(
            'link',
            XMLDB_TYPE_CHAR,
            '255',
            null,
            null,
            null,
            null,
            'image'
        );

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Create local_content_playlist table
        $playlist = new xmldb_table('local_content_playlist');

        $playlist->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $playlist->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $playlist->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $playlist->add_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
        $playlist->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL);

        $playlist->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $playlist->add_index('userid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        $playlist->add_index('itemid_idx', XMLDB_INDEX_NOTUNIQUE, ['itemid']);

        if (!$dbman->table_exists($playlist)) {
            $dbman->create_table($playlist);
        }

        // Savepoint
        upgrade_plugin_savepoint(true, 2020091006, 'local', 'content_structure');
    }

    return true;
}
