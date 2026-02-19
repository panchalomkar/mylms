<?php
require_once('../../config.php');
require_once('lib.php');

global $USER, $DB, $CFG;
require_login();

$requesttype = optional_param('requesttype', '', PARAM_TEXT);

switch($requesttype) {
    case "update": 
        update();
    break;
} 


function update() {
    global $USER, $DB, $CFG;
 
    try { 
        $id = required_param('id', PARAM_INT);
        $remarks = required_param('remarks', PARAM_TEXT);
        $approval_status = required_param('approval_status', PARAM_TEXT);

        $req = $DB->get_record_sql("SELECT * FROM `glms_hrms_approvals` WHERE id = ? ", [$id]);
          
        $inslog = new stdClass();
        $inslog->id = $id;
        $inslog->remarks = $remarks;
        $inslog->approved_by_id = $USER->id;
        $inslog->approved_by_name = $USER->firstname . " " . $USER->lastname; 
        $inslog->updated_at = date("Y-m-d H:i:s");

        if($approval_status == "approve") {
            $approval_status = 'approved';
        } else if($approval_status == "reject") {
            $approval_status = 'rejected';
        }

        $inslog->approval_status = $approval_status;
 
        $dRES = $DB->update_record("hrms_approvals", $inslog, false);

        if($dRES) {
            /// check if admin no 3 ==============
            $myadmin_no = getAdminNoByIdHrms($USER->id);

            if($myadmin_no == 3) {
                if($req->module == "airticket-request") {
                    $updlog = new stdClass();
                    $updlog->id = $req->ref_id; 
                    $updlog->approval_status = $approval_status; 
                    $updlog->admin_remarks = $remarks; 
                    $updlog->updated_at = date("Y-m-d H:i:s"); 
                    $DB->update_record("user_airticket_request", $updlog, false); 
                } else if($req->module == "leave-application") {
                    $updlog = new stdClass();
                    $updlog->id = $req->ref_id; 
                    $updlog->approval_status = $approval_status; 
                    $updlog->remarks = $remarks; 
                    $updlog->updated_at = date("Y-m-d H:i:s"); 
                    $DB->update_record("user_leaves", $updlog, false); 
                } else if($req->module == "passport-withdrawal") {
                    $updlog = new stdClass();
                    $updlog->id = $req->ref_id; 
                    $updlog->approval_status = $approval_status; 
                    $updlog->remarks = $remarks; 
                    $updlog->issued_date = date("Y-m-d"); 
                    $updlog->updated_at = date("Y-m-d H:i:s"); 
                    $DB->update_record("user_passport_withdrawal", $updlog, false); 
                } else if($req->module == "permission-to-leave-station") {
                    $updlog = new stdClass();
                    $updlog->id = $req->ref_id; 
                    $updlog->approval_status = $approval_status; 
                    $updlog->remarks = $remarks;  
                    $updlog->updated_at = date("Y-m-d H:i:s"); 
                    $DB->update_record("user_permission_to_leave_station", $updlog, false); 
                } else if($req->module == "salary-certificate-application") {
                    $updlog = new stdClass();
                    $updlog->id = $req->ref_id; 
                    $updlog->approval_status = $approval_status; 
                    $updlog->remarks = $remarks;  
                    $updlog->updated_at = date("Y-m-d H:i:s"); 
                    $DB->update_record("user_salary_certificate_application", $updlog, false); 
                }
            } else {
                if($approval_status == "approved") {
                    addAdminDataHrms(($req->admin_no + 1), $req->module, $req->ref_id);
                }
            }

            echo json_encode([
                "success" => 1,
                "message" => ($approval_status == "approved" ? "Successfully Approved!": "Successfully Rejected!") 
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
}