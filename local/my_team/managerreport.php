<?php
/**
 * Displays information about all the assignment modules in the requested course
 *
 * @package   local_my_team
 * @author    Jayesh
 */
require_once("../../config.php");
require_once($CFG->dirroot . '/local/my_team/lib.php');
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');
require_login();

$context = context_system::instance();
$access = false;

global $CFG, $OUTPUT, $PAGE, $USER;
$PAGE->requires->css(new moodle_url("/local/my_team/styles.css"));

$PAGE->set_context($context);

$PAGE->set_url(new moodle_url('/local/my_team/managerreport', $params));

$pluginname = get_string("pluginname", "local_my_team");
$PAGE->set_title('Manager Report');
$PAGE->set_heading('Manager Report');

echo $OUTPUT->header();

$userid = optional_param('uid', 0, PARAM_INT);

$data = get_manager_team_data($USER->username, []);
?>
<!DOCTYPE html>

<!-- DataTables -->
<link rel="stylesheet" href="../mydashboard/external/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../mydashboard/external/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="../mydashboard/external/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="../mydashboard/external/dist/css/adminlte.min.css">
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Manager Report</h3>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <!--<th>User Pic</th>-->
                    <th>User Code</th>
                    <th>Fullname</th>
                    <th>Payroll</th>
                    <th>Location</th>
                    <th>Designation</th>

                    <th>Course Name</th>
                    <th>Course Status</th>
                    <th>Course Enrol Date</th>
                    <th>Course Due Date</th>
                    <th>Course Completed Date</th>

                    <th>Course Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($data as $d) {
                    profile_load_data($d);
//                    $usercontext = context_user::instance($d->id);
//                    $image = '<img src="' . $CFG->wwwroot . '/pluginfile.php/' . $usercontext->id . '/user/icon/f3" width="50">';

                    $courses = get_user_enrolled_courses($d->id);
                    foreach ($courses as $course) {
//                        $grade = grade_get_course_grade($d->id, $course->id);
                        $completion = $DB->get_record('course_completions', ['userid' => $d->id, 'course' => $course->id]);
                        $tat = $DB->get_record('custom_enrollment', ['userid' => $d->id, 'courseid' => $course->id]);
                        $startdate = ($tat) ? date('d-m-Y', $tat->timeenrol) : '';
                        $duedate = ($tat) ? date('d-m-Y', $tat->timeend) : '';
                        $completiondate = ($completion->timecompleted > 0) ? date('d-m-Y', $completion->timecompleted) : '';
                        if ($completion) {
                            if ($completion->timecompleted > 0) {
                                if ($tat) {
                                    if (($tat->timeend >= $completion->timecompleted)) {
                                        $completionstatus = '<label style="color:#30EA60;">Completed on time</label>';
                                    } else {
                                        $completionstatus = '<label style="color:#FF5733;">Completed after due date</label>';
                                    }
                                } else {
                                    $completionstatus = '<label style="color:#30EA60;">Completed on time</label>';
                                }
                            } else {
                                if ($tat && $tat->timeend < time()) {
                                    $completionstatus = '<label style="color:#1a0000;">Course Due</label>';
                                } else if ($completion->timestarted > 0) {
                                    $completionstatus = '<label style="color:#334FFF;">In Progress</label>';
                                } else {
                                    $completionstatus = '<label style="color:#DB1026;">Not started</label>';
                                }
                            }
                        } else {
                            if ($tat && $tat->timeend < time()) {
                                $completionstatus = '<label style="color:#1a0000;">Course Due</label>';
                            } else {
                                $completionstatus = '<label style="color:#DB1026;">Not started</label>';
                            }
                        }

                        echo '<tr>';
//                        echo '<td>' . $image . '</td>';
                        echo '<td>' . $d->username . '</td>';
                        echo '<td id="' . $d->id . '">' . $d->firstname . ' ' . $d->lastname . '</td>';
                        echo '<td>' . $d->profile_field_payroll . '</td>';
                        echo '<td>' . $d->profile_field_location . '</td>';
                        echo '<td>' . $d->profile_field_designation . '</td>';

                        echo '<td>' . $course->fullname . '</td>';
                        echo '<td>' . $completionstatus . '</td>';
                        echo '<td>' . $startdate . '</td>';
                        echo '<td>' . $duedate . '</td>';
                        echo '<td>' . $completiondate . '</td>';
                        echo '<td>' . get_course_quizzess_grade($course->courseid, $d->id) . '</td>';
                        echo '</tr>';
                    }
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
            data: {action: "coursesstatus", userid: userid},
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
