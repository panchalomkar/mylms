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

    return true;
}
