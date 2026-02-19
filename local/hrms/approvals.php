<?php
require_once('../../config.php');
require_once('lib.php');

global $USER, $DB, $CFG;
require_login();

$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$params = array();
$pagetitle = 'Requests';
$PAGE->set_context($context);
// $PAGE->set_url('/my/passport_withdrawal.php', $params);
$PAGE->set_url('/local/hrms/approvals.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
  
$myadmin_no = getAdminNoByIdHrms($USER->id);

if($myadmin_no == 0) {
    echo "<h1>Unauthorized Access</h1>";
    exit;
}

$myrequests = $DB->get_records_sql("SELECT * FROM `glms_hrms_approvals` WHERE admin_no = ? ORDER BY created_at DESC", [$myadmin_no]);

// print_r($myrequests);


echo $OUTPUT->header();




?>
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="//cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
<style>
table.dataTable {
    font-size: 11px;
}
</style>

<table id="myTable">
    <thead>
        <tr>
            <th>Approval For</th>
            <th>Created At</th> 
            <th>Approval Status</th>
            <th>Staff Detail</th>
            <th data-orderable="false">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        foreach($myrequests as $req) {
            $approval_for = getModuleNameHrms($req->module);
            if($req->module == "airticket-request") {
                $requestDetail = $DB->get_record_sql("SELECT * FROM `glms_user_airticket_request` WHERE id = ? ", [$req->ref_id]); 
            } else if($req->module == "leave-application") {
                $requestDetail = $DB->get_record_sql("SELECT * FROM `glms_user_leaves` WHERE id = ? ", [$req->ref_id]); 
            } else if($req->module == "passport-withdrawal") {
                $requestDetail = $DB->get_record_sql("SELECT * FROM `glms_user_passport_withdrawal` WHERE id = ? ", [$req->ref_id]); 
            } else if($req->module == "permission-to-leave-station") {
                $requestDetail = $DB->get_record_sql("SELECT * FROM `glms_user_permission_to_leave_station` WHERE id = ? ", [$req->ref_id]); 
            } else if($req->module == "salary-certificate-application") {
                $requestDetail = $DB->get_record_sql("SELECT * FROM `glms_user_salary_certificate_application` WHERE id = ? ", [$req->ref_id]); 
            }

            $created_at = date("d/m/Y", strtotime($requestDetail->created_at));

            $userDetail = $DB->get_record_sql("SELECT * FROM `glms_user` WHERE id = ? ", [$requestDetail->user_id]);
        ?>
        <tr>
            <td><?php echo $approval_for; ?></td>
            <td><?php echo $created_at; ?></td> 
            <td><?php echo ucwords($requestDetail->approval_status); ?></td>
            <td>
                <?php echo $userDetail->username; ?> ( <?php echo $userDetail->firstname; ?> <?php echo $userDetail->lastname; ?> )<br />
                <?php echo $userDetail->email; ?>
            </td>
            <td>
                <a href="approval-detail.php?id=<?php echo $req->id; ?>" class="btn btn-primary btn-sm">View</a>
            </td>
        </tr>
        <?php
        }
        ?>
    </tbody>
</table>


<script>
$(document).ready( function () {
    $('#myTable').DataTable();
});
</script>
<?php
echo $OUTPUT->footer();