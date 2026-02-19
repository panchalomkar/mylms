<?php
require_once('../../config.php');
global $USER, $DB, $SITE, $PAGE;
$comid = $_POST['maincom_id'];

$getusername = $DB->get_record_sql("SELECT cu.* FROM {competency_category} cc INNER JOIN {competency_users} cu ON cu.subcompetencyid = cc.id WHERE cu.ctid = $comid");


 $subcalcom = $DB->get_record('roi_calculate_form', array('maincomid' => $getusername->subcompetencyid));
 if ($subcalcom->duration == 12) {
  $durationvalue = $subcalcom->duration.' year';
 }else{
  $durationvalue = $subcalcom->duration.' month';
 }


///////////////////////////////////

$getcompetency = $DB->get_records_sql("SELECT cu.* FROM {competency_category} cc INNER JOIN {competency_users} cu ON cu.subcompetencyid = cc.id WHERE cu.ctid = $comid");

$c=1;
if ($getcompetency) {
   
    foreach ($getcompetency as $kevalue) {
        $managerrating = $DB->get_record('manager_rating', array('subcomptencyid' => $kevalue->subcompetencyid, 'userid' => $kevalue->userid));
        $achiverating = $managerrating->finalrating - $managerrating->rating;
        $totalroi = ($achiverating / $managerrating->rating * 100);
        $getavgsalary = $DB->get_record_sql("SELECT ui.* FROM {user_info_field} uif 
        INNER JOIN {user_info_data} ui ON ui.fieldid = uif.id WHERE uif.shortname = 'salary' AND ui.userid = $kevalue->userid");
       
        $subcalcom = $DB->get_record('roi_calculate_form', array('maincomid' => $kevalue->subcompetencyid));
        ///period salary cal code
        $periodsalary = $getavgsalary->data /12 * $subcalcom->duration;

        $getavgtoimp = $periodsalary * $totalroi/100;
    }

}

/////////////////////////////////
 $data['result'] = $durationvalue;
 $data['fileroi'] = 100;
echo json_encode($data);
?>