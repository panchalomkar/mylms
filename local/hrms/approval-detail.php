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
$PAGE->set_url('/local/hrms/approval-detail.php', $params);
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

$id = intval($_REQUEST['id']);
$req = $DB->get_record_sql("SELECT * FROM `glms_hrms_approvals` WHERE id = ? ", [$id]);

$alladminapprovals = $DB->get_records_sql("SELECT * FROM `glms_hrms_approvals` WHERE ref_id = ? AND module = ? ", [$req->ref_id, $req->module]);
// print_r($alladminapprovals);
// print_r($myrequest);
$approval_for = getModuleNameHrms($req->module);
if($req->module == "airticket-request") {
    $requestDetail = $DB->get_record_sql("SELECT * FROM `glms_user_airticket_request` WHERE id = ? ", [$req->ref_id]); 
    $subrequestDetails = $DB->get_records_sql("SELECT * FROM `glms_user_airticket_detail` WHERE ref_id = ? ", [$requestDetail->id]); 
} else if($req->module == "leave-application") {
    $requestDetail = $DB->get_record_sql("SELECT ul.*, IF(ul.half_day = 1, 0.5, DATEDIFF(ul.leave_to, ul.leave_from)+1) as diff, (SELECT `name` FROM glms_leave_type WHERE `key` = ul.leave_type) as leave_type_name FROM `glms_user_leaves` as ul WHERE ul.id = ? ", [$req->ref_id]);
} else if($req->module == "passport-withdrawal") {
    $requestDetail = $DB->get_record_sql("SELECT * FROM `glms_user_passport_withdrawal` WHERE id = ? ", [$req->ref_id]);  
} else if($req->module == "permission-to-leave-station") {
    $requestDetail = $DB->get_record_sql("SELECT * FROM `glms_user_permission_to_leave_station` WHERE id = ? ", [$req->ref_id]);  
} else if($req->module == "salary-certificate-application") {
    $requestDetail = $DB->get_record_sql("SELECT * FROM `glms_user_salary_certificate_application` WHERE id = ? ", [$req->ref_id]);  
} 

$created_at = date("d/m/Y", strtotime($requestDetail->created_at));
$userDetail = $DB->get_record_sql("SELECT * FROM `glms_user` WHERE id = ? ", [$requestDetail->user_id]);
 

echo $OUTPUT->header();
  
?>
<style>
.error {
    color: red;
    font-size: 12px;
}

.loader {
  border: 4px solid #f3f3f3; /* Light grey */
  border-top: 4px solid #3498db; /* Blue */
  border-radius: 50%;
  width: 30px;
  height: 30px;
  animation: spin 2s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
 
</style>
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<form name="frmMyRemarks" id="frmMyRemarks" method="post">
<table class="table table-striped table-bordered">
    <tr>
        <td><strong>Staff Detail</strong></td>
        <td>
            <?php echo $userDetail->username; ?> ( <?php echo $userDetail->firstname; ?> <?php echo $userDetail->lastname; ?> )<br />
            <?php echo $userDetail->email; ?>
        </td>
    </tr>
</table>
<?php if($req->module == "airticket-request") { ?>
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <td colspan="8"><strong>Request Detail</strong></td> 
        </tr>
        <tr>
            <td>Apply Date</td>
            <td><?php echo date("m/d/Y", strtotime($requestDetail->created_at)); ?></td>
            <td colspan="6"></td>
        </tr>
        <tr>    
            <th>ITENERARY FOR</th>
            <th>ITENERARY TYPE</th>
            <th>NAME</th>
            <th>FROM/CITY</th>
            <th>TO/CITY</th>
            <th>DAY(FROM)/DAY(TO)</th>
            <th>DATE</th>
            <th>TIME</th> 
        </tr> 
    </thead>
    <?php foreach($subrequestDetails as $subdetail) { ?>
    <tr>    
        <td><?php echo $subdetail->itinerary_for; ?></td>
        <td><?php echo $subdetail->itinerary_type; ?></td>
        <td><?php echo $subdetail->name; ?></td>
        <td><?php echo $subdetail->from_city; ?></td>
        <td><?php echo $subdetail->to_city; ?></td>
        <td><?php echo $subdetail->travel_day; ?></td>
        <td><?php echo date("d/m/Y", strtotime($subdetail->travel_date)); ?></td>
        <td><?php echo $subdetail->travel_time; ?></td> 
    </tr> 
    <?php } ?>
    <tr>
        <td colspan="8"><strong>Remarks</strong></td> 
    </tr>
    <tr>
        <td colspan="8"><?php echo $requestDetail->remarks; ?></td> 
    </tr>
</table>
<?php } else if($req->module == "leave-application") { ?>
<table class="table table-bordered table-striped">
    <tr>
        <td>Leave Type</td>
        <td colspan="3"><?php echo $requestDetail->leave_type_name; ?></td>
    </tr>
    <tr>
        <td>Address While On Leave</td>
        <td><?php echo $requestDetail->address_while_on_leave; ?></td> 
        <td>Contact Local Person (Mob No.)</td>
        <td><?php echo $requestDetail->contact_local_person; ?></td>
    </tr>
    <tr>
        <td>From Date</td>
        <td><?php echo date("m/d/Y", strtotime($requestDetail->leave_from)); ?></td>
        <td>To Date</td>
        <td><?php echo date("m/d/Y", strtotime($requestDetail->leave_to)); ?></td>
    </tr>
    <tr>
        <td>No. of Days</td>
        <td><?php echo $requestDetail->diff; ?></td>
        <td>Session</td>
        <td>
            <?php 
                if($requestDetail->half_day == 1) {
                    if($requestDetail->session == 1) {
                        echo "Morning";
                    } else {
                        echo "Evening";
                    }
                } else {
                    echo "--";
                }
                    
            ?>
        </td>
    </tr>
    <tr>
        <td>Apply Date</td>
        <td><?php echo date("m/d/Y", strtotime($requestDetail->created_at)); ?></td>
        <td>LWP</td>
        <td><?php echo $requestDetail->lwp; ?></td>
    </tr>
    <tr>  
        <td>Reason of Leave</td>
        <td colspan="3"><?php echo ucwords($requestDetail->leave_reason); ?></td>
    </tr>
    
</table>
<?php } else if($req->module == "passport-withdrawal") { ?>
<table class="table table-bordered table-striped">
    <tr>
        <td><strong>Contact Local Person</strong></td>
        <td><?php echo $requestDetail->contact_local_person; ?></td>
        <td><strong>Contact Local No</strong></td>
        <td><?php echo $requestDetail->contact_local_no; ?></td>
    </tr>
    <tr>
        <td><strong>Contact International Person</strong></td>
        <td><?php echo $requestDetail->contact_international_person; ?></td>
        <td><strong>Contact International No</strong></td>
        <td><?php echo $requestDetail->contact_international_no; ?></td>
    </tr>
    <tr>
        <td><strong>Date To Return</strong></td>
        <td><?php echo date("d/m/Y", strtotime($requestDetail->date_to_return)); ?></td>
        <td>Applied Date</td>
        <td><?php echo date("m/d/Y", strtotime($requestDetail->created_at)); ?></td> 
    </tr>
    <tr>
        <td colspan="4"><strong>Reason</strong></td>
    </tr>
    <tr>
        <td colspan="4"><?php echo $requestDetail->reason; ?></td>
    </tr> 
</table>

<?php } else if($req->module == "permission-to-leave-station") { ?>

<table class="table table-bordered table-striped">
    <tr>
        <td><strong>Applied Date</strong></td>
        <td colspan="3"><?php echo date("m/d/Y", strtotime($requestDetail->created_at)); ?></td>
    </tr>
    <tr>
        <td><strong>Leave From</strong></td>
        <td><?php echo date("m/d/Y", strtotime($requestDetail->leave_from)); ?></td>
        <td><strong>Leave To</strong></td>
        <td><?php echo date("m/d/Y", strtotime($requestDetail->leave_to)); ?></td>
    </tr>  
    <tr>
        <td><strong>Address While On Leave</strong></td>
        <td><?php echo $requestDetail->address_while_leaving; ?></td>
        <td><strong>Reason To Travel</strong></td>
        <td><?php echo $requestDetail->reason_to_travel; ?></td>
    </tr>  
    <tr>
        <td colspan="4"><code>PERSON TO BE CONTACTED IN-CASE OF EMERGENCY:</code></td>
    </tr>
    <tr>
        <td><strong>Name</strong></td>
        <td><?php echo $requestDetail->name; ?></td>
        <td><strong>Relationship</strong></td>
        <td><?php echo $requestDetail->relationship; ?></td>
    </tr> 
    <tr>
        <td><strong>Email</strong></td>
        <td><?php echo $requestDetail->email; ?></td>
        <td><strong>Mobile No</strong></td>
        <td><?php echo $requestDetail->mobile_no; ?></td>
    </tr> 
    <tr>
        <td><strong>Residence No</strong></td>
        <td colspan="3"><?php echo $requestDetail->residence_no; ?></td> 
    </tr> 
    <tr>
        <td><strong>Address</strong></td>
        <td colspan="3"><?php echo $requestDetail->address; ?></td> 
    </tr> 
</table>
<?php } else if($req->module == "salary-certificate-application") { ?>
<table class="table table-bordered table-striped">
    
    <tr>
        <td><strong>Applied Date</strong></td>
        <td colspan="3"><?php echo date("m/d/Y", strtotime($requestDetail->created_at)); ?></td>
    </tr>
    <tr>
        <td><strong>Purpose Of The Application</strong></td>
        <td colspan="3"><?php echo $requestDetail->purpose_of_application; ?></td>
    </tr>
    <tr>
        <td><strong>Company & Address</strong></td>
        <td><?php echo $requestDetail->company_address; ?></td>
        <td><strong>Address</strong></td>
        <td><?php echo $requestDetail->address; ?></td>
    </tr> 
    <tr>
        <td><strong>City</strong></td>
        <td><?php echo $requestDetail->city; ?></td>
        <td><strong>Country</strong></td>
        <td><?php echo $requestDetail->country; ?></td>
    </tr> 
</table>

<?php } ?>







<?php if(count($alladminapprovals) > 0) { ?>
<table class="table table-bordered table-striped">
    <tr>
        <td colspan="3"><strong>Other Admin Details</strong></td>
    </tr>
    <tr>
        <td><strong>Admin</strong></td>
        <td><strong>Status</strong></td>
        <td><strong>Remarks</strong></td>
    </tr>
    <?php foreach($alladminapprovals as $admapproval) { ?>
    <tr>
        <td>Admin <?php echo $admapproval->admin_no; ?></td>
        <td><?php echo ucwords($admapproval->approval_status); ?></td>
        <td><?php echo $admapproval->remarks; ?></td>
    </tr>
    <?php } ?>
</table>
<?php } ?>


    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>"> 
    <input type="hidden" name="requesttype" id="requesttype" value="update"> 
    
    <table class="table table-striped table-bordered">
        <tr>
            <td ><strong>My Remarks</strong></td> 
        </tr>
        <tr> 
            <td>
                <textarea name="remarks" id="remarks" class="form-control"><?php echo $req->remarks; ?></textarea>
            </td>
        </tr>
        <tr>
            <td>
                <?php if($req->approval_status == "pending") { ?>
                <div id="loaderdisp" style="display: none;"><div class="loader"></div></div> 
                <div id="btndisp"> 
                    <button class="btn btn-primary" type="button" id="approve">Approve</button>
                    <button class="btn btn-warning" type="button" id="reject">Reject</button>
                </div>
                <?php } else { ?>
                    <div class="alert alert-<?php if($req->approval_status == "approved") { echo "success"; } else { echo "danger"; }  ?>">You <?php echo ucwords($req->approval_status); ?> this</div>
                <?php } ?>
                
            </td> 
        </tr>
    </table>
</form>

<script>
$(document).ready(function(){

    let submitData = function(approval_status) {
        if($("#remarks").val() == "") {
            alert("Please enter remarks");
            return false;
        }


        $("#loaderdisp").show();
        $("#btndisp").hide();

        let frmdata = $("#frmMyRemarks").serialize() + "&approval_status=" + approval_status;
        $.ajax({
            method: "post",
            url: 'ajax_approvals.php',
            data: frmdata,
            beforeSend() {

            },
            success: function(response) {
                let parseResp = $.parseJSON(response);
                console.log(parseResp);
                if(parseResp.success == 1) {
                    Swal.fire({
                        title: 'Success',
                        text: parseResp.message,
                        icon: 'success'
                    });

                    setTimeout(() => {
                        window.location.reload();
                    }, 5000);
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: parseResp.message,
                        icon: 'error'
                    });

                    $("#loaderdisp").hide();
                    $("#btndisp").show();
                }
                
            }
        });
    }

    $("#approve").click(function(){
        // alert("approve")

        submitData('approve')
        
    });

    $("#reject").click(function(){
        // alert("reject")
        submitData('reject')
    });
});
</script>
<?php
echo $OUTPUT->footer();