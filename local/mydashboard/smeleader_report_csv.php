<?php

// This file is part of Moodle - https://moodle.org/

//

// Moodle is free software: you can redistribute it and/or modify

// it under the terms of the GNU General Public License as published by

// the Free Software Foundation, either version 3 of the License, or

// (at your option) any later version.

//

// Moodle is distributed in the hope that it will be useful,

// but WITHOUT ANY WARRANTY; without even the implied warranty of

// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the

// GNU General Public License for more details.

//

// You should have received a copy of the GNU General Public License

// along with Moodle. If not, see <https://www.gnu.org/licenses/>.



/**

* @package local_mydashboard

* @category local

* @copyright  ELS <admin@elearningstack.com>

* @author eLearningstack

*/

require_once(__DIR__ . '/../../config.php');

require_once($CFG->libdir."/csvlib.class.php");
require_once('lib.php');

$categoryid = optional_param('categoryid', 0, PARAM_RAW);

global $CFG, $DB, $OUTPUT,$PAGE;  

$report = array();

$report[] =  array(get_string('smeleaderreport', 'local_mydashboard'));

$currenttime = time();

$url = "$CFG->wwwroot";

function remove_http($url) {

   $disallowed = array('http://', 'https://');

   foreach($disallowed as $d) {

      if(strpos($url, $d) === 0) {

         return str_replace($d, '', $url);

      }

   }

   return $url;

}

$report[] = array(remove_http($url));
$report[] =  array(get_string('Report_generated_date', 'local_mydashboard') . date('l, d F Y', $currenttime)

);


$table = new html_table();

    $report[]= $table->head = array(get_string('serialno', 'local_mydashboard'), 
    get_string('username', 'local_mydashboard'), 
    get_string('catname', 'local_mydashboard'),
    get_string('points', 'local_mydashboard'),
    get_string('pendingpoint', 'local_mydashboard'),
    get_string('gradeaverage', 'local_mydashboard'),
    get_string('role', 'local_mydashboard')
  ); 

$count=1;
    $get_alltime = get_user_point($categoryid);
    

foreach ($get_alltime['name'] as $keyvalue) {
    $userdata = $DB->get_record('user', array('id' => $keyvalue->userid));
    $catname = $DB->get_record('course_categories', array('id' => $categoryid));
    $assignsmeleader = $DB->get_record('assign_smeleader', array('smeleader_id' => $keyvalue->userid));
    $catdata = $DB->get_record('assign_cat_point', array('categoryid' => $categoryid));
    if ($keyvalue->pointsss >= $catdata->assignpoint) {
      $role = "SME leader";
    }else{
      $role = "Student";
    }
    $report[] = $table->data[] = array(
      'serialno' => $count++,
      'username' =>  $person_profile_pic .' '. $userdata->firstname.' '.$userdata->lastname,
      'catname' =>  $catname->name,
      'points' => $keyvalue->pointsss,
      'pendingpoints' => $pendingpoint,
      'gradeaverage' => round($average),
      'role' => $role,
      );
  } 
   $myfile = new csv_export_writer();

   $dlfile = $myfile->download_array(get_string('smeleaderreport', 'local_mydashboard'),$report); 

