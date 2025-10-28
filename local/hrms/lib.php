<?php
function getAlreadyTakenLeaves($USER_ID, $leave_type, $year = "") {
    global $DB;
    if($year == "") {
        $year = date("Y");
    }
 
    $SQL  = "";
    $SQL .= " SELECT SUM(IF(half_day = 1, 0.5, DATEDIFF(IF(leave_to > '" . $year . "-12-31', '" . $year . "-12-31', leave_to), IF(leave_from < '" . $year . "-01-01', '" . $year . "-01-01', leave_from))+1)) AS total_leaves_taken_this_year "; #, leave_from, leave_to 
    $SQL .= " FROM `glms_user_leaves` WHERE ( YEAR(leave_from) = " . $year . " OR YEAR(leave_to) = " . $year . " ) ";
    $SQL .= " AND approval_status = 'approved' and user_id = ? AND leave_type = ? AND leave_type <> 'LEAVE_WITHOUT_PAY' ";
    $total_leave_taken = $DB->get_record_sql($SQL, [$USER_ID, $leave_type]);
 
    return floatval($total_leave_taken->total_leaves_taken_this_year);
}

function getAdminNoByIdHrms($admin_id) {
    global $DB;
    $admin_users = $DB->get_record_sql("SELECT admin1_id, admin2_id, admin3_id FROM `glms_hrms_admin_user`");

    $admin_no = 0;
    if($admin_id == $admin_users->admin1_id) {
        $admin_no = 1;
    } else if($admin_id == $admin_users->admin2_id) {
        $admin_no = 2;
    } else if($admin_id == $admin_users->admin3_id) {
        $admin_no = 3;
    } 

    return $admin_no;
}

function getModuleNameHrms($module_key) {
    if($module_key == "airticket-request") {
        return 'Airticket Request';
    } else if($module_key == "leave-application") {
        return 'Leave Application';
    } else if($module_key == "passport-withdrawal") {
        return 'Passport Withdrawal';
    } else if($module_key == "permission-to-leave-station") {
        return 'Permission To Leave Station';
    } else if($module_key == "salary-certificate-application") {
        return 'Salary Certificate Application';
    } 
}

function addAdminDataHrms($admin_no = 1, $module, $ref_id) {
    //modules
    // airticket-request
    // leave-application
    // passport-withdrawal
    // permission-to-leave-station
    // salary-certificate-application

    global $DB;
    $admin_users = $DB->get_record_sql("SELECT admin1_id, admin2_id, admin3_id FROM `glms_hrms_admin_user`");

    $admin_id = 0;
    if($admin_no == 1) {
        $admin_id = $admin_users->admin1_id;
    } else if($admin_no == 2) {
        $admin_id = $admin_users->admin2_id;
    } else if($admin_no == 3) {
        $admin_id = $admin_users->admin3_id;
    }

    $inslog = new stdClass();
    $inslog->admin_no = $admin_no;
    $inslog->admin_id = $admin_id;
    $inslog->module = $module;
    $inslog->ref_id = $ref_id;
    $inslog->created_at = date("Y-m-d H:i:s");

    $DB->insert_record("hrms_approvals", $inslog, true);
}