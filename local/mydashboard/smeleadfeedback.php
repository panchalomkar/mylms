<?php
require_once('../../config.php');
global $USER, $DB, $SITE, $PAGE;

$studentid=$_POST['userid'];
$textareavalue = $_POST['textareavalue'];
$ratingvalue = $_POST['ratingvalue'];
$getfeedbackdata = $DB->get_record('sme_leader_feedback', array('feedback_userid' => $USER->id, 'smeleader_id' => $studentid));
$assignsmeleader = $DB->get_record('assign_smeleader', array('smeleader_id' => $studentid), '*', MUST_EXIST);
if (!$getfeedbackdata) {
    $insert = new stdClass();
    $insert->categoryid = $assignsmeleader->categoryid;
    $insert->smeleader_id = $studentid;
    $insert->feedback_userid = $USER->id;
    $insert->feedback = $textareavalue;
    $insert->rate = $ratingvalue;
    $insert->timecreated = time();
    $messageid = $DB->insert_record('sme_leader_feedback', $insert);
}else {
    $insert = new stdClass();
    $insert->id = $getfeedbackdata->id;
    $insert->categoryid = $assignsmeleader->categoryid;
    $insert->smeleader_id = $studentid;
    $insert->feedback_userid = $USER->id;
    $insert->feedback = $textareavalue;
    $insert->rate = $ratingvalue;
    $insert->timecreated = time();
    $messageid = $DB->update_record('sme_leader_feedback', $insert);
}

echo $messageid;
?>