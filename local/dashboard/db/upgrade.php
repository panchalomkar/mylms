<?php

function xmldb_local_dashboard_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    
	
     if ($oldversion < 2020080901) {
		upgrade_plugin_savepoint(true, 2020080901, 'local', 'dashboard');
    }
    if ($oldversion < 2020080902) {
		upgrade_plugin_savepoint(true, 2020080902, 'local', 'dashboard');
    }
    if ($oldversion < 2020080903) {
		upgrade_plugin_savepoint(true, 2020080903, 'local', 'dashboard');
    }
}