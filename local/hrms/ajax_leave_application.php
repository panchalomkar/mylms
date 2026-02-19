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
        $leave_type = required_param('leave_type', PARAM_TEXT); //ACADEMIC_LEAVE
        $leave_balance = optional_param('leave_balance', 0, PARAM_INT); //5
        $address_while_on_leave = optional_param('address_while_on_leave', '', PARAM_TEXT);
        $contact_local_person = optional_param('contact_local_person', '', PARAM_TEXT);
        $leave_from_to = required_param('leave_from_to', PARAM_TEXT); // 07/10/2022 - 11/10/2022
        $half_day = optional_param('half_day', null, PARAM_TEXT);  

        if(isset($half_day)) {
            $half_day = 1;
        } else {
            $half_day = 0;
        }

        if($half_day == 1) {
            $session = required_param('session', PARAM_INT); // morning - 1/evening - 2
        } else {
            $session = 0; 
        }

        $no_of_days_leave_view = optional_param('no_of_days_leave_view', 0, PARAM_INT);// 5
        $lwp = optional_param('lwp', 0, PARAM_INT); // 0
        $leave_reason = optional_param('reason', '', PARAM_TEXT);

        $leave_from_to_split = explode("-", $leave_from_to);
        $leave_from_arr = explode("/", trim($leave_from_to_split[0]));
        $leave_to_arr = explode("/", trim($leave_from_to_split[1]));

        $leave_from = $leave_from_arr[2] . "-" . $leave_from_arr[1] . "-" . $leave_from_arr[0];
        $leave_to = $leave_to_arr[2] . "-" . $leave_to_arr[1] . "-" . $leave_to_arr[0];

        if(strtotime($leave_from) < strtotime(date("Y-m-d"))) {
            throw new \Exception("From date should be greater than todays date");
        }

        $leavelog = new stdClass();
        $leavelog->user_id = $USER->id;
        $leavelog->leave_type = $leave_type;
        $leavelog->leave_balance = $leave_balance;
        $leavelog->address_while_on_leave = $address_while_on_leave;
        $leavelog->contact_local_person = $contact_local_person;
        
        $leavelog->leave_from = $leave_from;
        $leavelog->leave_to = $leave_to;
         
        $leavelog->half_day = $half_day;
        $leavelog->session = $session;
        $leavelog->no_of_days_leave_view = $no_of_days_leave_view;
        $leavelog->lwp = $lwp;
        $leavelog->leave_reason = $leave_reason;
        $leavelog->created_at = date("Y-m-d H:i:s");

        $dRES = $DB->insert_record("user_leaves", $leavelog, true);

        if($dRES) {

            addAdminDataHrms(1, 'leave-application', $dRES);

            echo json_encode([
                "success" => 1,
                "message" => "Leave successfully applied"
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
     * leave_type
     * leave_balance
     * address_while_on_leave
     * contact_local_person
     * leave_from
     * leave_to
     * half_day
     * session
     * no_of_days_leave_view
     * allowed_leaves
     * lwp
     * leave_reason
     * 
     * approval_status - pending/approved/rejected
     * remarks
     * created_at
     * updated_at
     */
    

}