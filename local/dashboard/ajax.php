<?php

require_once('../../config.php');

define('AJAX_SCRIPT', true);

global $CFG, $USER, $DB;
require_login();

$action = optional_param('action', '', PARAM_TEXT);
$attid = optional_param('attdid', '', PARAM_INT);
$userid = optional_param('userid', '', PARAM_INT);
switch ($action) {
    case 'attendance':
    $date = new DateTime("now", core_date::get_server_timezone_object());
        $date->setTime(0, 0, 0);
        $da = $date->getTimestamp();
        $d = date('d',$da);
        $m = date('m',$da);
        $y = date('Y',$da);
        $whereSql  = " AND FROM_UNIXTIME(aas.sessdate , '%Y') = $y AND FROM_UNIXTIME(aas.sessdate , '%d') = $d AND FROM_UNIXTIME(aas.sessdate , '%m') = $m";
        $myattendance = $DB->get_recordset_sql("SELECT aas.description as attenname, al.timetaken, al.studentid ,asr.acronym
            FROM mdl_attendance a 
             LEFT JOIN mdl_attendance_sessions aas ON aas.attendanceid = a.id
             LEFT JOIN mdl_attendance_log al ON   aas.id = al.sessionid
             LEFT JOIN mdl_attendance_statuses asr ON asr.id = al.statusid 
            WHERE a.id = $attid $whereSql");
        $html = '<table class="table table-sm" style="border:1px solid black;">';
            foreach ($myattendance as $attendance) {
            	
                $html .= '<tr>';
		             $html .= '<td class=" text-dark" style="background-color:#175D77;color:#FDFFEA !important;">';
		                     $html .= $attendance->attenname;                   
		                 $html .= '</td>';
                         
                         if($attendance->acronym == "A"){
                            $background = "#d43f3a";
                         } else if($attendance->acronym == "P"){ 
                            $background = "#04b962";
                        } else {
                            $background = "#175D77";
                        }
		                 $html .= '<td style="background-color:'.$background.';color:#FFF !important;">';
                         if($attendance->studentid == $userid){
		                      $html .= $attendance->acronym;
                          } else {
                                $html .= "-";
                          }
		             $html .= '</td ></tr>';
	            
            }
        $html .= '</table>';
        echo $html;
    break;
}
