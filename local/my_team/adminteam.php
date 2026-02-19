<?php
/**
 * Displays information about all the assignment modules in the requested course
 *
 * @package   local_my_team
 * @author    Jayesh
 */
require_once("../../config.php");
require_once($CFG->dirroot.'/local/my_team/lib.php');

require_login();

$context = context_system::instance();
$access = false;

// print_object($access); die;
//if (!($access || is_siteadmin())) {
//    print_error('Access denied');
//}

global $CFG, $OUTPUT, $PAGE, $USER;
$PAGE->requires->css(new moodle_url("/local/my_team/styles.css"));

$PAGE->set_context($context);

$PAGE->set_url(new moodle_url('/local/my_team/', $params));

$pluginname = get_string("pluginname", "local_my_team");
$PAGE->set_title('Users Report');
$PAGE->set_heading('Users Report');

echo $OUTPUT->header();

if(!is_siteadmin()) {
    redirect($CFG->wwwroot);
}
$userid = 0;
if($userid == 0) {
    $userid = $USER->id;
}


$data = $DB->get_records_sql("SELECT * FROM mdl_user where id > 2 and suspended = 0 and deleted = 0");

?>
<!DOCTYPE html>

<!-- DataTables -->
<link rel="stylesheet" href="../mydashboard/external/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../mydashboard/external/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="../mydashboard/external/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="../mydashboard/external/dist/css/adminlte.min.css">

<a href="index.php?uid=<?php echo $USER->id ?>" class="btn btn-primary">Back to My Team</a>
<hr>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Users Report</h3>

    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped small">
            <thead>
                <tr>
                    <th>User Pic</th>
                    <th>User Code</th>
                    <th>Fullname</th>
                    <th>Payroll</th>
                    <th>Location</th>
                    <th>Designation</th>
                    <!--                    <th>Email</th>-->
                    <!--<th>Department</th>-->
                    <!--<th>Sub Division</th>-->
                    <th>Course Enrolled</th>
                    <th>Course Not Started</th>
                    <th>Course in-progress</th>
                    <th>Course Completed</th>
                    <th>Course Completed After Due Date</th>
                    <th>Course Due</th>
                    <th>Course Avg Grade</th>
                    <!--<th>Course Status</th>-->
                    <!--<th>Achieved Certificate</th>-->
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($data as $d) {
                    profile_load_data($d);
                    $enrolled = get_user_enrolled_courses($d->id);
                    list($ip, $ns, $cids) = get_user_inprogress_courses($d->id, $enrolled);
                    list($comp, $afterdue, $due) = get_user_completed_courses($d->id, $enrolled);
                    $usercontext = context_user::instance($d->id);
                    $image = '<img src="'.$CFG->wwwroot.'/pluginfile.php/'.$usercontext->id.'/user/icon/f3" width="50">';
                    echo '<tr>';
                    echo '<td>'.$image.'</td>';
                    echo '<td>'.$d->username.'</td>';
                    echo '<td id="'.$d->id.'">'.$d->firstname.' '.$d->lastname.'</td>';
                    echo '<td>'.$d->profile_field_payroll.'</td>';
                    echo '<td>'.$d->profile_field_location.'</td>';
                    echo '<td>'.$d->profile_field_designation.'</td>';
                    echo '<td>'.get_progress_bar(count($enrolled), count($enrolled), 1).'</td>';
                    echo '<td>'.get_progress_bar($ns, count($enrolled), 1).'</td>';
                    echo '<td>'.get_progress_bar($ip, count($enrolled), 2).'</td>';
                    echo '<td>'.get_progress_bar($comp, count($enrolled), 2).'</td>'; //course completed
                    echo '<td>'.get_progress_bar($afterdue, count($enrolled), 3).'</td>'; //course due
                    echo '<td>'.get_progress_bar($due, count($enrolled), 3).'</td>'; //course due
                
                    echo '<td>'.get_course_quizzess_grade($cids, $d->id).'</td>';
                    //                    echo '<td><a href="#myModal" userid="' . $d->id . '" class="viewcoursestatus" data-toggle="modal" data-target="#myModal">VIEW</a></td>';
//                    echo '<td><a href="" class="btn btn-primary">My Certificate</a></td>';
                    echo '</tr>';
                }
                ?>
            </tbody>

        </table>
    </div>
    <!-- /.card-body -->
</div>


<!-- /.content -->
<!-- jQuery -->
<script src="../mydashboard/external/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->

<script src="../mydashboard/external/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../mydashboard/external/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../mydashboard/external/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../mydashboard/external/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../mydashboard/external/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../mydashboard/external/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../mydashboard/external/plugins/jszip/jszip.min.js"></script>
<script src="../mydashboard/external/plugins/pdfmake/pdfmake.min.js"></script>
<script src="../mydashboard/external/plugins/pdfmake/vfs_fonts.js"></script>
<script src="../mydashboard/external/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../mydashboard/external/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../mydashboard/external/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

<!-- Page specific script -->
<script>
    $(function () {
        $("#example1").DataTable({
            "scrollX": true,
            //            "responsive": false, 
            "lengthChange": false, "autoWidth": false,
            "buttons": ["csv", "excel", "colvis"]
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');


    });

    $('.viewcoursestatus').click(function (e) {
        e.preventDefault();
        var userid = $(this).attr('userid');
        $.ajax({
            url: "<?php echo $CFG->wwwroot; ?>/local/my_team/ajax/my_team_ajax.php",
            type: "post",
            dataType: "html",
            data: { action: "coursesstatus", userid: userid },
            success: function (res) {
                if (res != '') {
                    $('.pop-content').html(res);
                }
            }
        });

    })
</script>
<div id="myModal" class="modal fade _custom-video-modal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Course Status</h5>
                <button typemodal-header="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body pop-content bg-white">

            </div>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
