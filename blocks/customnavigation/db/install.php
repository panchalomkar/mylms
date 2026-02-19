<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * auth_db installer script.
 *
 * @package    auth_db
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_block_customnavigation_install() {

    global $CFG, $DB, $SESSION;

    $dbman = $DB->get_manager();

// Define learningpaths table scheme.
    $table = new xmldb_table('customnavigation');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('parent_id', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_field('sort', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_field('type', XMLDB_TYPE_CHAR, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null);
    $table->add_field('module', XMLDB_TYPE_CHAR, '120', XMLDB_UNSIGNED, XMLDB_NOTNULL, null);
    $table->add_field('label', XMLDB_TYPE_TEXT, null, null, null, null, null, '');
    $table->add_field('href', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL, null);
    $table->add_field('target', XMLDB_TYPE_CHAR, '10', XMLDB_UNSIGNED, null, null);
    $table->add_field('icon', XMLDB_TYPE_CHAR, '50', null, null, null);
    $table->add_field('asignuserid', XMLDB_TYPE_CHAR, '255', null, null, null);
    $table->add_field('roleid', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, null, null);
    $table->add_field('inst_id', XMLDB_TYPE_CHAR, '4', XMLDB_UNSIGNED, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
// Conditionally launch create table.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);

//        $data = [
//          [96,97,99,103,113,114,130,131,134,139,140,143,144,145,146,147,148,149,150,156,157,158,159,160,161,162,163,164,165,166,167,168,169,170,171,172,173,174,175,177,178],  
//          [0,0,0,146,143,0,0,143,143,0,146,0,0,0,0,0,0,148,148,148,148,149,149,149,149,149,149,149,150,150,156,156,156,156,156,156,157,157,148,148,148],  
//          [1,2,3,38,8,35,40,9,7,36,39,6,4,5,37,41,10,11,19,22,29,12,16,13,15,14,17,18,20,21,23,24,26,27,28,25,31,30,34,32,33],  
//          ['link','link','link','link','link','link','link','link','link','link','link','container','link','link','container','link','container','container','container','container','container','link','link','link','link','link','link','link','link','link','link','link','link','link','link','link','link','link','link','link','link'],  
//          ['','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','',''],  
//          ['Home','Frontpage','My Records','Reports ','Create a Course','PEOPLE','HELPDESK','Interactive Content','Course Catalog','Multitenant','Report Views','Courses','My Courses','Course Catalog','Reports','Ecommerce','Learning Plans','Admin','Users','Learning Paths','User Reports','Bulk User Actions','Default Results Engine Score Settings','Custom Fields','Audiences Classifications','Notifications','Default Course Metadata Settings','Default Class Sessions Settings','Manage Users','Manage Audiences','Certificate List','Manage Paths','Course Sets','Course Metadata','Class Sessions','LP Sessions','Individual Course Progress','Individual Report','Individual Report','Certificate List','Individual Course Progress'],  
//          ['/','/?home_style=alternative','/my','/reports/','/blocks/eledia_coursewizard/createcourse.php?cid=1&category=0','/admin/user.php','/support/','/mod/hvp/list.php','/course','/local/rlmslms/multitenant','/local/download_reports/view.php','javascript:;','/course/explore_courses.php?my_courses=1','/course/explore_courses.php','javascript:;','http://ecommercedemo.rlmslms.net/','/performance/','#','javascript:;','javascript:;','javascript:;','/performance/admin/bulk-user-actions/','/performance/admin/default-results-engine-settings/','/performance/admin/custom-fields/?level=user','/performance/admin/organization-classifications/','/performance/admin/notifications/?section=admn','/performance/admin/default-course-description-settings/?section=admn','/performance/admin/default-class-instance-settings/?section=admn','/local/elisprogram/index.php?s=usr','/local/elisprogram/index.php?s=clst','/local/elisprogram/index.php?s=certlist','/local/elisprogram/index.php?s=cur','/local/elisprogram/index.php?s=crsset','/local/elisprogram/index.php?s=crs','/local/elisprogram/index.php?s=cls','/local/elisprogram/index.php?s=trk','/local/elisreports/render_report_page.php?report=individual_course_progress','/local/elisreports/render_report_page.php?report=individual_user','/local/elisreports/render_report_page.php?report=individual_user','/local/elisprogram/index.php?s=certlist','/local/elisreports/render_report_page.php?report=individual_course_progress'],  
//          ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],  
//          ['men men-icon-home','fa-newspaper-o','men men-icon-my-records','fa-line-chart','fa-book','men men-icon-peo-ple','men men-icon-helpdesk','men men-icon-interective-content','men men-icon-course-catalog','men men-icon-multitenant','fa-download','men men-icon-course','fa-folder-open','men men-icon-course-catalog','men men-icon-reports','fa-cart-plus','men men-icon-advance','fa-university','men men-icon-people','men men-icon-learning','men men-icon-reports','fa-users','fa-asterisk','fa-database','fa-sitemap','fa-info','fa-cubes','fa-graduation-cap','men men-icon-peo-ple','men men-icon-people','fa-certificate','fa-road','fa-folder-open-o','men men-icon-my-records','fa-list','fa-street-view','fa-folder-o','fa-user','fa-user','fa-certificate','fa-folder-o'],  
//          ['admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin','admin'],  
//          ['-1,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,0,','','-1,1,3,4,5,6,7,8,9,10,0,','-1,1','-1,1,9','-1,','-1,1,9,10,','-1,1,9','-1,1,9','-1,','-1,1','-1,1,9,','-1,4,5,','4,5,0,','-1,1,','-1,','-1,','-1','-1','-1','-1','-1','-1','-1','-1','-1','-1','-1','-1,','-1','-1','-1','-1','-1','-1','-1','-1','-1','-1','-1','-1']
//        ];
//        
//        foreach ($data[0] as $key => $value) {
//            $record1 = new stdClass();
//            $record1->id         = $data[0][$key];
//            $record1->parent_id = $data[1][$key];
//            $record1->sort = $data[2][$key];
//            $record1->type = $data[3][$key];
//            $record1->module = $data[4][$key];
//            $record1->label = $data[5][$key];
//            $record1->href = $data[6][$key];
//            $record1->icon = $data[7][$key];
//            $record1->asignuserid = $data[8][$key];
//            $record1->roleid = $data[9][$key];
//            $record1->inst_id = $data[10][$key];
//            $DB->insert_record('customnavigation', $record1);
//        }
    }
    return true;
}
