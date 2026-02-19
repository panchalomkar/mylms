defined('MOODLE_INTERNAL') || die();

function xmldb_local_goone_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Example: Add new column to existing table
    if ($oldversion < 20250923) {

        // Table: goone_learning_sync_limits
        $table = new xmldb_table('goone_learning_sync_limits');

        // Field: sync_offset
        $field = new xmldb_field('sync_offset', XMLDB_TYPE_INTEGER, '10', null, null, null, 0, 'total_courses');

        // Add the field if it doesn't exist
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Savepoint
        upgrade_plugin_savepoint(true, 20250923, 'local', 'goone');
    }

    return true;
}
