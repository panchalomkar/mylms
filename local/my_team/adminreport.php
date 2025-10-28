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
require_once($CFG->dirroot . '/blocks/edwiser_grader/lib.php');

require_login();

$context = context_system::instance();

global $CFG, $OUTPUT, $PAGE, $USER;
$PAGE->requires->css(new moodle_url("/local/my_team/styles.css"));

$PAGE->set_context($context);

$PAGE->set_url(new moodle_url('/local/my_team/', $params));

$pluginname = get_string("pluginname", "local_my_team");
$PAGE->set_title('Over All Report');
$PAGE->set_heading('Over All Report');

echo $OUTPUT->header();
$users = $DB->get_records_sql("SELECT * FROM mdl_user where id > 2 and suspended = 0 and deleted = 0");
?>


<!-- DataTables -->
<link rel="stylesheet" href="../mydashboard/external/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../mydashboard/external/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="../mydashboard/external/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="../mydashboard/external/dist/css/adminlte.min.css">

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Overall Report</h3>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped small">
            <thead>
                <tr>
                    <th>User Code</th>
                    <th>Fullname</th>
                    <th>Payroll</th>
                    <th>Location</th>
                    <th>Designation</th>
                    <th>Course category</th>
                    <th>Course Name</th>
                    <th>Course Enrol Date</th>
                    <th>Course Progress</th>
                    <th>Course Completion Status</th>
                    <th>Course Completed Date</th>
                    <th>Course Start Date</th>
                    <th>Course End Date</th>
                    <th>Assessment Attempt</th>
                    <th>Course Avg Grade</th>
                    <th>Reporting Manager Code</th>
                    <th>Reporting Manager Name</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($users as $user) {
                    profile_load_data($user);
                    $enrolledcourses = get_user_enrolled_courses($user->id);
                    foreach ($enrolledcourses as $course) {
                        echo '<tr>';
                        echo '<td id="' . $user->id . '">' . $user->username . '</td>';
                        echo '<td>' . $user->firstname . ' ' . $user->lastname . '</td>';
                        echo '<td>' . $user->profile_field_payroll . '</td>';
                        echo '<td>' . $user->profile_field_location . '</td>';
                        echo '<td>' . $user->profile_field_designation . '</td>';
                        echo '<td>' . $course->name . '</td>';
                        echo '<td id="c' . $course->courseid . '">' . $course->fullname . '</td>';
                        $tat = $DB->get_record('custom_enrollment', ['userid' => $user->id, 'courseid' => $course->courseid]);
                        $startdate = ($tat) ? date('d-m-Y', $tat->timeenrol) : '';
                        $duedate = ($tat) ? date('d-m-Y', $tat->timeend) : '';
                        echo '<td>' . $startdate . '</td>';

                        $progress = \core_completion\progress::get_course_progress_percentage($course, $user->id);

                        echo '<td>' . round($progress, 2) . '</td>';
                        $completion = $DB->get_record('course_completions', ['userid' => $user->id, 'course' => $course->courseid]);
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
                        $completiondate = ($completion->timecompleted) ? date('d-m-Y', $completion->timecompleted) : '';
                        $coursestarted = ($completion->timestarted) ? date('d-m-Y', $completion->timestarted) : '';
                        echo '<td>' . $completionstatus . '</td>';
                        echo '<td>' . $completiondate . '</td>';
                        echo '<td>' . $coursestarted . '</td>';
                        echo '<td>' . $duedate . '</td>';
                        //                        $grade = grade_get_course_grade($user->id, $course->courseid);
                        //get the quizzes in the course
                        $qsql = "SELECT id, GROUP_CONCAT(id) as quizids FROM {quiz} WHERE course = $course->courseid";
                        $quizid = $DB->get_record_sql($qsql);
                        if ($quizid->quizids != '') {
                            $attemptsql = $DB->get_records_sql("SELECT id FROM {quiz_attempts} WHERE userid = $user->id AND quiz IN ($quizid->quizids)");
                        }

                        echo '<td>' . @count($attemptsql) . '</td>';
                        echo '<td>' . get_course_quizzess_grade($course->courseid, $user->id) . '</td>';
                        echo '<td>' . $user->profile_field_repotingto . '</td>';
                        $reportingmanager = $DB->get_record('user', ['username' => $user->profile_field_repotingto]);
                        echo '<td>' . $reportingmanager->firstname . ' ' . $reportingmanager->lastname . '</td>';
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
            // "scrollX": true,
            // "responsive": true,
            "lengthChange": false, "autoWidth": true,
            "buttons": ["csv", "excel", "colvis"]
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');


    });

</script>
<style>
    .path-local-my_team .table thead th {
        font-size: 0.90rem !important;
        font-weight: 400 !important;
    }

    .path-local-my_team .table thead th {
        font-size: 0.90rem !important;
        font-weight: 500 !important;
    }

    .path-local-my_team table.dataTable tbody td,
    table.dataTable tbody th,
    table.generaltable tbody td,
    table.generaltable tbody th,
    table.rolecap tbody td,
    table.rolecap tbody th {
        font-size: 0.80rem !important;
    }

    .main-area-bg:not(.pagelayout-login) div[role="main"] {
        padding: 6px !important;
    }

    .path-local-my_team .dataTables_filter {
        font-size: 0.85rem !important;
    }

    .path-local-my_team .buttons-csv,
    .buttons-excel,
    .buttons-colvis span {
        font-size: 0.80rem !important;
    }

    #page-header .dashboard-bar-wrapper .page-header-headings .header-heading {
        font-weight: 500;
        font-size: 1.30rem !important;
        line-height: 30px;
        margin: 0;
    }

    .path-local-my_team #topofscroll {
        margin-top: 0px !important;
    }
</style>

<?php
echo $OUTPUT->footer();
