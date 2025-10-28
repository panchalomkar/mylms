<?php
defined('MOODLE_INTERNAL') || die(); 

function xmldb_local_mt_dashboard_upgrade( $oldversion = 0 ){
    
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2019010803) {
        
        // Define social_wall_log table scheme.
        $table = new xmldb_table('company_cohorts');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('companyid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        
        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2019010803, 'local', 'mt_dashboard');
    }
    if ($oldversion < 2019010804) {
        upgrade_plugin_savepoint(true, 2019010804, 'local', 'mt_dashboard');
    }
    
    if ($oldversion < 2019010805) {
        upgrade_plugin_savepoint(true, 2019010805, 'local', 'mt_dashboard');
    }
    // Upgrade capability
    if ($oldversion < 2019112900 ) {
        upgrade_plugin_savepoint(true, 2019112900, 'local', 'mt_dashboard');
    }
    
    if ($oldversion < 2019112901 ) {
        $systemcontext = context_system::instance();
        foreach (array('clientadministrator', 'companymanager') as $rolename) {
            if ($role = $DB->get_record('role', array('shortname' => $rolename), '*')) {
                assign_capability(
                    'local/mt_dashboard:companyapperance_view',
                    CAP_ALLOW,
                    $role->id,
                    $systemcontext->id
                );
            }
        }
        upgrade_plugin_savepoint(true, 2019112901, 'local', 'mt_dashboard');
    }

    if ($oldversion < 2019123100) {
        $systemcontext = context_system::instance();
        foreach (array('clientadministrator', 'companymanager') as $rolename) {
            if ($role = $DB->get_record('role', array('shortname' => $rolename), '*')) {
                assign_capability(
                    'local/mt_dashboard:local/mt_dashboard:viewcohortsync',
                    CAP_ALLOW,
                    $role->id,
                    $systemcontext->id
                );
                assign_capability(
                    'local/mt_dashboard:local/mt_dashboard:addcohortsyncenrol',
                    CAP_ALLOW,
                    $role->id,
                    $systemcontext->id
                );
                assign_capability(
                    'local/mt_dashboard:local/mt_dashboard:deletecohortsync',
                    CAP_ALLOW,
                    $role->id,
                    $systemcontext->id
                );
            }
        }

        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2019123100, 'local', 'mt_dashboard');
    }

    if ($oldversion < 2019123101) {
        $companyData = $DB->get_records_sql('SELECT id,shortname FROM {company}');
        if ($companyData) {
            foreach($companyData as $data){
                $companyid = $data->id;
                $shortname = preg_replace(array('/[^a-z0-9]/i', '/[-]+/') , '', $data->shortname);
                $DB->execute("UPDATE {company} SET shortname = '$shortname' WHERE id=".$companyid);    
            }
        }
        $infoCate = $DB->get_records_sql('SELECT id,name FROM {user_info_category}');
        if ($infoCate) {
            foreach($infoCate as $catdata){
                $catshortname = preg_replace(array('/[^a-z0-9]/i', '/[-]+/') , '', $catdata->name);
                $cateid = $catdata->id;
                $DB->execute("UPDATE {user_info_category} SET name = '$catshortname' WHERE id=".$cateid);
                }
        }
 
        $user_fieldData = $DB->get_records_sql('SELECT id,shortname FROM {user_info_field}');
        if ($user_fieldData) {
            foreach($user_fieldData as $data){
                $id = $data->id;
                $shortname = preg_replace(array('/[^a-z0-9]/i', '/[-]+/') , '', $data->shortname);
                $DB->execute("UPDATE {user_info_field} SET shortname = '$shortname' WHERE id=".$id);    
            }
        }
 
        // Iomad savepoint reached.
        upgrade_plugin_savepoint(true, 2019123101, 'local', 'mt_dashboard');
    }

    if($oldversion < 2019123102) {
       $coursql = "ALTER TABLE {company} CHANGE `shortname` `shortname` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''";
       $DB->execute($coursql);

       $coursql1 = "ALTER TABLE {department} CHANGE `shortname` `shortname` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''";
       $DB->execute($coursql1);

        //Savepoint reached.
        upgrade_plugin_savepoint(true, 2019123102,'local', 'mt_dashboard');
    }

    if($oldversion < 2019123104) {
      
        upgrade_plugin_savepoint(true, 2019123104,'local', 'mt_dashboard');
    }

}