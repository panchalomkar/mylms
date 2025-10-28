<?php
/**
 * This is local People plugin file to manage upgrade things
 * 
 * @author Sandeep B
 * @since 26-11-2019
 * @paradiso
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_people_upgrade($oldversion = 0) {
    global $DB, $CFG;

    $result = true;

    $dbman = $DB->get_manager();

    /**
     * Added new capability
     * 
     * @author Sandeep B
     * @since 26-11-2019
     * @ticket #770
     * 
     */ 
    if ($oldversion < 2019112600) {        

        upgrade_plugin_savepoint(true, 2019112600, 'local', 'people');
    }

    if ($oldversion < 2019112611) {

        $systemcontext = context_system::instance();
        // They do not exist.
        
         $companymanager = $DB->get_record('role', array('shortname' => 'companymanager'), '*', MUST_EXIST);
            if(!empty($companymanager)){
            unassign_capability('local/people:viewallusers', $companymanager->id);
            unassign_capability('moodle/user:loginas', $companymanager->id);
            unassign_capability('local/people:suspenduser', $companymanager->id);
            unassign_capability('block/iomad_company_admin:editusers', $companymanager->id);
        
            assign_capability('local/people:viewallusers', CAP_ALLOW, $companymanager->id, $systemcontext->id);
            assign_capability('moodle/user:loginas', CAP_ALLOW, $companymanager->id, $systemcontext->id);
            assign_capability('local/people:suspenduser', CAP_ALLOW, $companymanager->id, $systemcontext->id);
            assign_capability('block/iomad_company_admin:editusers', CAP_ALLOW, $companymanager->id, $systemcontext->id);
    }
        // local savepoint reached.
        upgrade_plugin_savepoint(true, 2019112611, 'local', 'people');
    }
    
    return true;
}