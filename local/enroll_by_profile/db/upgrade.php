<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_enroll_by_profile_upgrade($oldversion) {
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2017100402) {

        // Define table email to be created.
        $table = new xmldb_table('local_enroll_by_profile_user_rule');

        // Adding fields to table email.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL,XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ruleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL,null, null);

        // Adding keys to table email.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for email.
        if (!$dbman->table_exists($table)) {
            $result = $dbman->create_table($table);
        }

        // Email savepoint reached.
        upgrade_plugin_savepoint(true, 2017100402, 'local', 'enroll_by_profile');
    }
      if ($oldversion < 2019061902) {
            
            $table = 'local_enroll_by_profile';
            $field1 = 'rulename';
            if (!$dbman->field_exists($table, $field1)) {
                $sql="ALTER TABLE mdl_local_enroll_by_profile MODIFY profile_field TEXT, MODIFY content TEXT, ADD rulename VARCHAR(100) AFTER profile_field";
                $DB->execute($sql, null);
            }
          }
          
          if ($oldversion < 2019062504) {
            
            $table = 'local_enroll_by_profile';
            $field1 = 'rulename';
            if ($dbman->field_exists($table, $field1)) {
                $sql="ALTER TABLE mdl_local_enroll_by_profile MODIFY rulename TEXT AFTER profile_field";
                $DB->execute($sql, null);
            }
          }
          
          if ($oldversion < 2019062505) {
              //update after adding index in install.xml
                           
                $table = new xmldb_table('local_enrol_prof_user_rule');
                $index = new xmldb_index('user_rule_userid');
                $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));
                if (!$dbman->index_exists($table, $index)) {
                   
                    $dbman->add_index($table, $index);
                }
                $index1 = new xmldb_index('user_rule_ruleid');
                 $index1->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('ruleid'));
                if (!$dbman->index_exists($table, $index1)) {
                   
                    $dbman->add_index($table, $index1);
                }
              
              upgrade_plugin_savepoint(true, 2019062505, 'local', 'enroll_by_profile');
          }

            /*
            * @author VaibhavG
            * @since 11th Feb 2021
            * @desc 509 Rules Engine issues fixes. Applied disable & enable rule feature
            */
            if ($oldversion < 2019062506) {
                $table = new xmldb_table('local_enroll_by_profile');
                $field1 = new xmldb_field('disable_rule', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'selected_elements');
                if (!$dbman->field_exists($table, $field1)) {
                    $dbman->add_field($table, $field1);
                }
                upgrade_plugin_savepoint(true, 2019062506, 'local', 'enroll_by_profile');
            }

            /*
            * @author VaibhavG
            * @since 2nd March 2021
            * @desc 509 Rules Engine issues fixes. Unenroll users from rule category
            */
            if ($oldversion < 2019062510) {
                $table = new xmldb_table('local_enroll_by_profile');
                $field1 = new xmldb_field('unenroll_rule', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'disable_rule');
                if (!$dbman->field_exists($table, $field1)) {
                    $dbman->add_field($table, $field1);
                }
                upgrade_plugin_savepoint(true, 2019062510, 'local', 'enroll_by_profile');
            }
            if ($oldversion < 2019062513) {
                $table = new xmldb_table('local_enroll_by_profile');
                $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '255',
                                          null, XMLDB_NOTNULL, null, null);
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                }
                upgrade_plugin_savepoint(true, 2019062513, 'local', 'enroll_by_profile');
            } 
      
    return $result ;
}
