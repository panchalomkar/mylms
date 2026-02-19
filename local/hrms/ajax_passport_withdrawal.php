<?php
require_once('../../config.php');
require_once('lib.php');

global $USER, $DB, $CFG;
require_login();

$requesttype = optional_param('requesttype', '', PARAM_TEXT);

switch($requesttype) {
    case "add": 
        add();
    break;
} 

function add() {
    global $USER, $DB, $CFG;
    try {
        $contact_local_person = required_param('contact_local_person', PARAM_TEXT); 
        $contact_local_no = required_param('contact_local_no', PARAM_INT); 
        $contact_international_person = required_param('contact_international_person', PARAM_TEXT); 
        $contact_international_no = required_param('contact_international_no', PARAM_INT); 
        $date_to_return = required_param('date_to_return', PARAM_TEXT); 
        $reason = required_param('reason', PARAM_TEXT); 
        
        $inslog = new stdClass();
        $inslog->user_id = $USER->id; 
        $inslog->contact_local_person = $contact_local_person;
        $inslog->contact_local_no = $contact_local_no;
        $inslog->contact_international_person = $contact_international_person;
        $inslog->contact_international_no = $contact_international_no;
        $inslog->date_to_return = $date_to_return;
        $inslog->reason = $reason;
        $inslog->created_at = date("Y-m-d H:i:s");

        $dRES = $DB->insert_record("user_passport_withdrawal", $inslog, true);

        if($dRES) {
            addAdminDataHrms(1, 'passport-withdrawal', $dRES);

            echo json_encode([
                "success" => 1,
                "message" => "Successfully applied"
            ]); 
        } else {
            throw new \Exception("Sorry cannot process your request");
        }

    } catch(\Exception $e) {

        echo json_encode([
            "success" => 0,
            "message" => $e->getMessage()
        ]); 
    }

    // print_r($_REQUEST);

    /**
     * id
     * user_id
     * contact_local_person
     * contact_local_no
     * contact_international_person
     * contact_international_no
     * date_to_return
     * reason 
     * 
     * approval_status - pending/approved/rejected
     * remarks
     * issued_date
     * created_at
     * updated_at
     */
    

}