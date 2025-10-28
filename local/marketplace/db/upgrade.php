<?php
defined('MOODLE_INTERNAL') || die(); 

function xmldb_local_marketplace_upgrade( $old){
    
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    if($old < 2022010509){
            upgrade_plugin_savepoint(true, 2022010509, 'local', 'marketplace');
    }

}