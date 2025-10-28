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
 * classroom course format. Display the whole course as "classroom" made of modules.
 *
 * @package local_competency
 * @copyright 2020 Nilesh Pathade
 * @author Nilesh Pathade
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/local/competency/pagination.php');
require_once($CFG->dirroot.'/local/competency/lib.php');

global $USER, $CFG, $DB, $OUTPUT, $SESSION;
if (!empty($SESSION->currenteditingcompany)) {
    $selectedcompany = $SESSION->currenteditingcompany;
} else if (!empty($USER->profile->company)) {
    $usercompany = company::by_userid($USER->id);
    $selectedcompany = $usercompany->id;
} else {
    $selectedcompany = "";
}
$activepage = 'roiwisereport';
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_title(get_string('userselfrating', 'local_competency'));
$PAGE->set_url($CFG->wwwroot.'/local/competency/roiwisereport.php');
$PAGE->set_heading(get_string('userselfrating', 'local_competency'));
$PAGE->navbar->add(get_string('userselfrating', 'local_competency'));
$PAGE->requires->css( new moodle_url($CFG->wwwroot . '/local/competency/customtablelayout.css?v=1'));
echo $OUTPUT->header();
//header added
require_once($CFG->dirroot.'/local/competency/header.php');
require_once($CFG->dirroot.'/local/competency/tabs.php');
if (is_siteadmin()) {
    if ($selectedcompany) {
        $getcomp = $DB->get_records('competency_title', array('isdeleted' => 0,'companyid' => $selectedcompany));
    }else{
        $getcomp = $DB->get_records('competency_title', array('isdeleted' => 0));
    }

    $dataget = array();
    foreach ($getcomp as $keyvalue) {
        $dataget[] = array(
            'title' => $keyvalue->title,
            'id' => $keyvalue->id
        );
    }
    
   $getubd = getdepartment();
   $getbu = array();
   foreach ($getubd as $keyvalues) {
       $getbu[] = array(
           'department' => $keyvalues->department,
       );
   }

   if ($selectedcompany) {
    $sql = "SELECT cc.* FROM {competency_title} ct INNER JOIN {competency_category} cc ON cc.ctid = ct.id WHERE ct.companyid = $selectedcompany AND ct.isdeleted = 0";
    }else{
    $sql = "SELECT cc.* FROM {competency_title} ct INNER JOIN {competency_category} cc ON cc.ctid = ct.id WHERE ct.isdeleted = 0";
    }
   $getsubcom = $DB->get_records_sql($sql);
   $getsubcomdet = array();
   foreach ($getsubcom as $keyvalu) {
       $getsubcomdet[] = array(
           'comname' => $keyvalu->name,
           'comcid' => $keyvalu->id,
       );
   }


    $defaultvariables = [
        'studentdetails' => $dataget,
        'getbudetail' => $getbu,
        'subcompentancy' => $getsubcomdet,
    ];
    
    echo $OUTPUT->render_from_template('local_competency/roiwisereport', $defaultvariables);
}else{

    $getusername = $DB->get_records_sql("SELECT cu.* FROM {competency_category} cc INNER JOIN {competency_users} cu ON cu.subcompetencyid = cc.id WHERE cu.userid = $USER->id");

    $c=1;
    if ($getusername) {
        $getuserreport = array();
        foreach ($getusername as $kevalue) {
            $getuserdetail = $DB->get_record('user', array('id' => $kevalue->userid));
            $subcompetancy = $DB->get_record('competency_category', array('id' => $kevalue->subcompetencyid));
            $managerrating = $DB->get_record('manager_rating', array('subcomptencyid' => $kevalue->subcompetencyid));
            $achiverating = $managerrating->finalrating - $managerrating->rating;
            $totalroi = ($achiverating / $managerrating->rating * 100);
    
            if ($managerrating->finalrating >= 5 && $managerrating->finalrating <= 10) {
                $ratingstatus = '<i class="fa fa-thumbs-up text-success" aria-hidden="true"></i>';
            }else{
                $ratingstatus = '<i class="fa fa-thumbs-down text-danger" aria-hidden="true"></i>';
            }
    
            $getavgsalary = $DB->get_record_sql("SELECT ui.* FROM {user_info_field} uif 
            INNER JOIN {user_info_data} ui ON ui.fieldid = uif.id WHERE uif.shortname = 'salary' AND ui.userid = $kevalue->userid");
           
            $subcalcom = $DB->get_record('roi_calculate_form', array('maincomid' => $kevalue->subcompetencyid));
    
            $getavgtoimp = $getavgsalary->data * $totalroi/100;
            $totalsum = $getavgtoimp - $subcalcom->trainingbudegt;
            $totalroivalue = $totalsum/$subcalcom->trainingbudegt;
            $FinalROI =  $totalroivalue*100;

            $getuserreport[] = array(
                'counts' => $c++,
                'subcompetancy' => $subcompetancy->name,
                'subcompetancybuid' => $subcompetancy->buid,
                'totalroi' => $totalroi.'%',
                'FinalROI' => $FinalROI.'%',
                'ratingstatus' => $ratingstatus,
            );
        }
    }

    $userreport = [
        'userreport' => $getuserreport,
    ];
    echo $OUTPUT->render_from_template('local_competency/roiwisereportforuser', $userreport);
}

echo $OUTPUT->footer(); 
