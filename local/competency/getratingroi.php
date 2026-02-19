<?php
require_once('../../config.php');
global $USER, $DB, $SITE, $PAGE;
$comid = $_POST['maincom_id'];
$getusername = $DB->get_records_sql("SELECT cu.* FROM {competency_category} cc INNER JOIN {competency_users} cu ON cu.subcompetencyid = cc.id WHERE cu.ctid = $comid");

$c=1;
if ($getusername) {
   
    foreach ($getusername as $kevalue) {
        $getuserdetail = $DB->get_record('user', array('id' => $kevalue->userid));
        $subcompetancy = $DB->get_record('competency_category', array('id' => $kevalue->subcompetencyid));
        $managerrating = $DB->get_record('manager_rating', array('subcomptencyid' => $kevalue->subcompetencyid, 'userid' => $kevalue->userid));
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
        ///period salary cal code
        $periodsalary = $getavgsalary->data /12 * $subcalcom->duration;

        $getavgtoimp = $periodsalary * $totalroi/100;
        // $totalsum = $getavgtoimp - $subcalcom->trainingbudegt;
        // $totalroivalue = $totalsum/$subcalcom->trainingbudegt;
        // $FinalROI =  $totalroivalue*100;





        $tablerow = '<tr>
        <th scope="row">'.$c++.'</th>
        <td>'.$getuserdetail->firstname.' '.$getuserdetail->lastname.'</td>
        <td>'.$subcompetancy->name.'</td>
        <td>'.$subcompetancy->buid.'</td>
        <td>'.$managerrating->rating.'</td>
        <td>'.$managerrating->finalrating.'</td>
        <td>'.round($totalroi).'%</td>
        <td>$ ' .$getavgsalary->data.'</td>
        <td>$ ' .$periodsalary.'</td>
        <td>'.round($getavgtoimp).'</td>
        <td>'.$ratingstatus.'</td>
        </tr>';
        
    echo $tablerow;
    }

}

?>