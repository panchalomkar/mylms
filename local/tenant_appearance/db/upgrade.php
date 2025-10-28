<?php
defined('MOODLE_INTERNAL') || die(); 

function xmldb_local_tenant_appearance_upgrade( $oldversion = 0 ){
    
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2020093003) {
        
        $table = new xmldb_table('font_upload_setting');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('font_file', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('font_family', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
		$table->add_field('font_style', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
		$table->add_field('font_type', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
		$table->add_field('font_weight', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
		$table->add_field('companyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        
        if ($dbman->table_exists($table)) {
        $dbman->drop_table($table,$continue=true, $feedback=true);
        }
        
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2020093003, 'local', 'tenant_appearance');
    }

    if ($oldversion < 2020093004) {
        
        upgrade_plugin_savepoint(true, 2020093004, 'local', 'tenant_appearance');
    }
    

}