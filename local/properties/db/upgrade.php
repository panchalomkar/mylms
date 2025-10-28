<?php

defined('MOODLE_INTERNAL') || die();


function xmldb_local_properties_upgrade($oldversion = 0) {
    global $DB,$CFG;

    $dbman = $DB->get_manager();
    
    if ($oldversion < 2018101000) {
    	$coursql = "ALTER TABLE {course_info_data} MODIFY dataformat tinyint(2) NOT NULL DEFAULt 0";
    	$DB->execute($coursql);
    	$cohorsql = "ALTER TABLE {cohort_info_data} MODIFY dataformat tinyint(2) NOT NULL DEFAULt 0";
    	$DB->execute($cohorsql);
        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2018101000,'local', 'properties');
    }
    
    if ($oldversion < 2019010901) {
        $table = new xmldb_table('course_info_data');
        $index = new xmldb_index('courseid_fieldid_unique');
         $index->set_attributes(XMLDB_INDEX_UNIQUE, array('courseid','fieldid'));
        if (!$dbman->index_exists($table, $index)) {
           $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2019010901, 'local','properties');
    }

    return true;
}
