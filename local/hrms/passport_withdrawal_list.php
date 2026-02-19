<?php
require_once('../../config.php');
require_once('lib.php');

global $USER, $DB, $CFG;
require_login();

if($USER->department != 'FACULTY')
{   
    echo $OUTPUT->header();
    echo "<h1>Invalid Access</h1>";
    echo $OUTPUT->footer();
    exit;
}

$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$params = array();
$pagetitle = 'Passport Withdrawal';
$PAGE->set_context($context);
// $PAGE->set_url('/my/leave_application.php', $params);
$PAGE->set_url('/local/hrms/passport_withdrawal_list.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

// 

$myrequests = $DB->get_records_sql("SELECT * FROM `glms_user_passport_withdrawal` WHERE user_id = '" . $USER->id . "' ORDER BY created_at DESC");

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
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link " href="passport_withdrawal.php">Apply</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="<?php echo $PAGE->url; ?>">List</a>
    </li>
</ul>

<table id="myTable" class="table table-striped">
    <thead>
        <tr>
            <th>S.No</th>
            <th>Approval Status</th>
            <th>Issued Date</th>
            <th>Expected Return Date</th> 
            <th>Applied Date</th>
            <th>Pending With/Closed By</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $counter = 0;
        foreach($myrequests as $lidx => $request) {
        ?>
        <tr>
            <td><?php echo (++$counter); ?></td>
            <td><?php echo ucwords($request->approval_status); ?></td>
            <td><?php echo isset($request->issued_date) ? date("m/d/Y", strtotime($request->issued_date)) : "Not Issued Yet"; ?></td>
            <td><?php echo date("m/d/Y", strtotime($request->date_to_return)); ?></td>
            <td><?php echo date("m/d/Y", strtotime($request->created_at)); ?></td> 
            <td>
                <?php
                //check admin approval details ==========
                $adminapproval = $DB->get_record_sql("SELECT * FROM `glms_hrms_approvals` WHERE ref_id = ? AND module = 'passport-withdrawal' ORDER BY id DESC ", [$request->id]);
                if($adminapproval) {
                    echo ucwords($adminapproval->approval_status);
                    echo $adminapproval->approval_status == "pending" ? " with " : " by ";
                    echo "Admin " . $adminapproval->admin_no;
                } else {
                    echo "--";
                }
                ?>
            </td>
            <td><?php echo ucwords($request->remarks); ?></td> 
        </tr>
        <?php
        }
        ?>
    </tbody>
    <tfoot>
        <tr>
            <th>S.No</th>
            <th>Approval Status</th>
            <th>Issued Date</th>
            <th>Expected Return Date</th> 
            <th>Applied Date</th>
            <th>Pending With</th>
            <th>Remarks</th>
        </tr>
    </tfoot>
</table>
<script>
$(document).ready( function () {
    $('#myTable').DataTable();
});
</script>

<?php
echo $OUTPUT->footer();