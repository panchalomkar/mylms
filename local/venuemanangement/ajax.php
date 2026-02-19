<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../../config.php');
require_once("lib.php");
global $CFG, $PAGE;
$PAGE->requires->jquery();
$PAGE->requires->js("/local/venuemanangement/js/venue.js");

global $DB;

//check already created classroom
$classroom = optional_param('classroom', null, PARAM_ALPHANUM); 
$locationid = optional_param('locationid', null, PARAM_ALPHANUM); 
if(!empty($classroom) && !empty($locationid)){
        $sql = "SELECT * FROM mdl_local_classroom WHERE locationid = ? AND classroom = ?";
        $result = $DB->get_records_sql($sql, array($locationid, $classroom));
        
        if(count($result) > 0)
            echo '1';
}

//check already created classroom
$location = optional_param('location', null, PARAM_ALPHANUM); 
if(!empty($location)){
        $sql = "SELECT * FROM mdl_local_bu WHERE location = ?";
        $result = $DB->get_records_sql($sql, array($location));
        
        if(count($result) > 0)
            echo '1';
}


//$action= required_param('action',PARAM_ALPHANUM);
//
//$bucode = optional_param('bucode', '', PARAM_ALPHANUM); 
//
//if(!empty($action)){
//  
//    if($action=='getlocation' && !empty($bucode)) {
//        //echo $courseid;
//        
//        $result = $DB->get_records('local_bu',array('bucode'=> $bucode));
//        $sessionoptions=array();
//        foreach($result as $key => $value){
//
//           $sessionoptions[$value->id]=$value->location;
//           
//        }
//        echo json_encode($sessionoptions);
//    }
//
//
//}


