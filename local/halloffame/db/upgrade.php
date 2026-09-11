<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_halloffame_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    // v2: add companyid to awards, submissions, achievements, categories, departments.
    if ($oldversion < 2025040200) {
        $tables = [
            'halloffame_awards',
            'halloffame_submissions',
            'halloffame_achievements',
            'halloffame_categories',
            'halloffame_departments',
        ];
        foreach ($tables as $table) {
            $xmltable = new xmldb_table($table);
            $field    = new xmldb_field('companyid', XMLDB_TYPE_INTEGER, '10', null,
                            XMLDB_NOTNULL, null, '0', 'id');
            if (!$dbman->field_exists($xmltable, $field)) {
                $dbman->add_field($xmltable, $field);
            }
            // Add index for performance.
            $index = new xmldb_index('idx_companyid', XMLDB_INDEX_NOTUNIQUE, ['companyid']);
            if (!$dbman->index_exists($xmltable, $index)) {
                $dbman->add_index($xmltable, $index);
            }
        }
        upgrade_plugin_savepoint(true, 2025040200, 'local', 'halloffame');
    }

    // v3: add comments table.
    if ($oldversion < 2025041000) {
        $table = new xmldb_table('halloffame_comments');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id',          XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('itemid',      XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('itemtype',    XMLDB_TYPE_CHAR,    '50', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid',      XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('message',     XMLDB_TYPE_TEXT,    null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('deleted',     XMLDB_TYPE_INTEGER,  '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('fk_user', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_index('idx_item',   XMLDB_INDEX_NOTUNIQUE, ['itemid', 'itemtype']);
            // $table->add_index('idx_userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2025041000, 'local', 'halloffame');
    }

    return true;
}
