<?php
require_once('../../config.php');
global $USER, $DB, $SITE, $PAGE;

$smeuserid = $_POST['smeuserid'];
$fdid = $_POST['fdid'];
$purposeid = $_POST['purposeid'];

$getfeedbackdata = $DB->get_record('send_request', array('for_userid' => $fdid, 'from_userid' => $smeuserid, 'purpose' => $purposeid));
// print_r($getfeedbackdata);
if ($getfeedbackdata->purpose == 0) {
    $purpose = "Student";
}else{
    $purpose = "Mentor";
}
$data = array(
    array(
    'purpose' => $purpose,
    'subject' => $getfeedbackdata->subject,
    'messages' => $getfeedbackdata->message,
    'feedbackdate' => date('d-M-Y', $getfeedbackdata->timecreated)
    )
);

// Send the response as JSON
header('Content-Type: application/json');

echo json_encode($data);

?>