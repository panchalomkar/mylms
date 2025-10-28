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
$pagetitle = 'Leave Application';
$PAGE->set_context($context);
// $PAGE->set_url('/my/leave_application.php', $params);
$PAGE->set_url('/local/hrms/leave_applied.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

// 

$myleaves = $DB->get_records_sql("SELECT ul.*, IF(ul.half_day = 1, 0.5, DATEDIFF(ul.leave_to, ul.leave_from)+1) as diff, (SELECT `name` FROM glms_leave_type WHERE `key` = ul.leave_type) as leave_type_name FROM `glms_user_leaves` as ul WHERE ul.user_id = '" . $USER->id . "' ORDER BY ul.created_at DESC");

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
        <a class="nav-link " href="leave_application.php">Apply For Leave</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="<?php echo $PAGE->url; ?>">Leaves Already Applied</a>
    </li>
</ul>

<table id="myTable" class="table table-striped">
    <thead>
        <tr>
            <th>S.No</th>
            <th>Leave Type</th>
            <th>From Date</th>
            <th>To Date</th>
            <th>No. of Days</th>
            <th>Session</th>
            <th>Reason of Leave</th>
            <th>Apply Date</th>
            <th>Approval Status</th>
            <th>Pending With/Closed By</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $counter = 0;
        foreach($myleaves as $lidx => $leave) {
        ?>
        <tr>
            <td><?php echo (++$counter); ?></td>
            <td><?php echo $leave->leave_type_name; ?></td>
            <td><?php echo date("m/d/Y", strtotime($leave->leave_from)); ?></td>
            <td><?php echo date("m/d/Y", strtotime($leave->leave_to)); ?></td>
            <td><?php echo $leave->diff; ?></td>
            <td>
                <?php 
                    if($leave->half_day == 1) {
                        if($leave->session == 1) {
                            echo "Morning";
                        } else {
                            echo "Evening";
                        }
                    } else {
                        echo "--";
                    }
                     
                ?>
            </td>
            <td><?php echo ucwords($leave->leave_reason); ?></td>
            <td><?php echo date("m/d/Y", strtotime($leave->created_at)); ?></td>
            <td><?php echo ucwords($leave->approval_status); ?></td>
            <td>
                <?php
                //check admin approval details ==========
                $adminapproval = $DB->get_record_sql("SELECT * FROM `glms_hrms_approvals` WHERE ref_id = ? AND module = 'leave-application' ORDER BY id DESC ", [$leave->id]);
                if($adminapproval) {
                    echo ucwords($adminapproval->approval_status);
                    echo $adminapproval->approval_status == "pending" ? " with " : " by ";
                    echo "Admin " . $adminapproval->admin_no;
                } else {
                    echo "--";
                }
                ?>

            </td>
        </tr>
        <?php
        }
        ?>
    </tbody>
    <tfoot>
        <tr>
            <th>S.No</th>
            <th>Leave Type</th>
            <th>From Date</th>
            <th>To Date</th>
            <th>No. of Days</th>
            <th>Session</th>
            <th>Reason of Leave</th>
            <th>Apply Date</th>
            <th>Approval Status</th>
            <th>Pending With/Closed By</th>
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