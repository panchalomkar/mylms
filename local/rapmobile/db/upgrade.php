<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_rapmobile_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024060301) {

        // Table 1: rapmobile_devices
        $t = new xmldb_table('rapmobile_devices');
        if (!$dbman->table_exists($t)) {
            $t->add_field('id',          XMLDB_TYPE_INTEGER, '10', true,  XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $t->add_field('userid',      XMLDB_TYPE_INTEGER, '10', true,  XMLDB_NOTNULL);
            $t->add_field('fcm_token',   XMLDB_TYPE_CHAR,   '255', null,  XMLDB_NOTNULL);
            $t->add_field('platform',    XMLDB_TYPE_CHAR,    '10', null,  XMLDB_NOTNULL, null, 'android');
            $t->add_field('app_version', XMLDB_TYPE_CHAR,    '20');
            $t->add_field('active',      XMLDB_TYPE_INTEGER,  '1', true,  XMLDB_NOTNULL, null, '1');
            $t->add_field('created',     XMLDB_TYPE_INTEGER, '10', true,  XMLDB_NOTNULL, null, '0');
            $t->add_field('lastupdated', XMLDB_TYPE_INTEGER, '10', true,  XMLDB_NOTNULL, null, '0');
            $t->add_key('primary',             XMLDB_KEY_PRIMARY, ['id']);
            $t->add_key('fk_userid',           XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $t->add_index('uq_userid_token',   XMLDB_INDEX_UNIQUE,    ['userid', 'fcm_token']);
            $t->add_index('idx_userid_active', XMLDB_INDEX_NOTUNIQUE, ['userid', 'active']);
            $dbman->create_table($t);
        }

        // Table 2: rapmobile_push_sent
        $t = new xmldb_table('rapmobile_push_sent');
        if (!$dbman->table_exists($t)) {
            $t->add_field('id',                XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $t->add_field('userid',            XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $t->add_field('notification_id',   XMLDB_TYPE_CHAR,    '64', null, XMLDB_NOTNULL);
            $t->add_field('notification_type', XMLDB_TYPE_CHAR,    '60');
            $t->add_field('timesent',          XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $t->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $t->add_index('uq_userid_notifid', XMLDB_INDEX_UNIQUE,    ['userid', 'notification_id']);
            $t->add_index('idx_timesent',      XMLDB_INDEX_NOTUNIQUE, ['timesent']);
            $dbman->create_table($t);
        }

        upgrade_plugin_savepoint(true, 2024060301, 'local', 'rapmobile');
    }

    return true;
}
