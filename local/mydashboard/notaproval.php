<?php
require_once('../../config.php');
global $USER, $DB, $SITE, $PAGE;
$smeuserid=$_POST['smeuserid'];
$fdid = $_POST['fdid'];
$purposeid = $_POST['purposeid'];
$getfeedbackdata = $DB->get_record('send_request', array('from_userid' => $smeuserid, 'for_userid' => $fdid, 'status' => 1, 'purpose' => $purposeid));
    
if ($getfeedbackdata) {
    $insert = new stdClass();
    $insert->id = $getfeedbackdata->id;
    $insert->status = 0;
    $insert->timecreated = time();
    $messageid = $DB->update_record('send_request', $insert);
}

return true;
?>