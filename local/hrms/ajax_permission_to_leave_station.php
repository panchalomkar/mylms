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
        $leave_from_to = required_param('leave_from_to', PARAM_TEXT); 
        $mobile_no = required_param('mobile_no', PARAM_INT); 
        $address_while_leaving = required_param('address_while_leaving', PARAM_TEXT); 
        $residence_no = required_param('residence_no', PARAM_INT); 
        $reason_to_travel = required_param('reason_to_travel', PARAM_TEXT); 
        $name = required_param('name', PARAM_TEXT); 
        $relationship = required_param('relationship', PARAM_TEXT); 
        $email = required_param('email', PARAM_TEXT); 
        $address = required_param('address', PARAM_TEXT); 

        $leave_from_to_split = explode("-", $leave_from_to);
        $leave_from_arr = explode("/", trim($leave_from_to_split[0]));
        $leave_to_arr = explode("/", trim($leave_from_to_split[1]));

        $leave_from = $leave_from_arr[2] . "-" . $leave_from_arr[1] . "-" . $leave_from_arr[0];
        $leave_to = $leave_to_arr[2] . "-" . $leave_to_arr[1] . "-" . $leave_to_arr[0];

        if(strtotime($leave_from) < strtotime(date("Y-m-d"))) {
            throw new \Exception("From date should be greater than todays date");
        }
        
        $inslog = new stdClass();
        $inslog->user_id = $USER->id; 
        $inslog->mobile_no = $mobile_no;
        $inslog->address_while_leaving = $address_while_leaving;
        $inslog->residence_no = $residence_no;
        $inslog->reason_to_travel = $reason_to_travel;
        $inslog->name = $name;
        $inslog->relationship = $relationship;
        $inslog->email = $email;
        $inslog->address = $address;
        $inslog->leave_from = $leave_from;
        $inslog->leave_to = $leave_to;
        $inslog->created_at = date("Y-m-d H:i:s");

        $dRES = $DB->insert_record("user_permission_to_leave_station", $inslog, true);

        if($dRES) {
            addAdminDataHrms(1, 'permission-to-leave-station', $dRES);
            
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
     * 
     * `id` 
     * `user_id` 
     * `leave_from` 
     * `leave_to` 
     *  `mobile_no`  
     * `address_while_leaving`
     * `residence_no` 
     *  `reason_to_travel`  
     * `name`  
     * `relationship` 
     *  `email` 
     * `address` 
     * `approval_status`  - pending/approved/rejected 
     * `remarks`  
     * `created_at` 
     *  `updated_at`  
     */
    

}