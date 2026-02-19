<?php
require_once('../../config.php');
global $USER, $DB, $SITE, $PAGE;
$smeuserid = $_POST['smeuserid'];
$fdid = $_POST['fdid'];
$getusername = $DB->get_record('user', array('id' =>$fdid));
$getfeedbackdata = $DB->get_record('sme_leader_feedback', array('feedback_userid' => $fdid, 'smeleader_id' => $smeuserid));
$getrate = '';
for ($i=1; $i <= $getfeedbackdata->rate; $i++) { 
    $getrate .=  '<div class="fedbacksec">
  <span class="fa fa-star checked" style="color:orange"></span>
  </div>';
    }

$data = array(
    array(
    'username' => $getusername->firstname.' '.$getusername->firstname,
    'email' => $getusername->email,
    'feedback' => $getfeedbackdata->feedback,
    'rating' => $getrate,
    'feedbackdate' => date('d-M-Y', $getfeedbackdata->timecreated)
    )
);

// Send the response as JSON
header('Content-Type: application/json');

echo json_encode($data);

?>