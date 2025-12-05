<?php
/**
 * Displays information about all the assignment modules in the requested course
 *
 * @package   local_my_team
 * @author    Jayesh
 */
require_once("../../config.php");
require_once($CFG->dirroot . '/local/my_team/lib.php');
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 5, PARAM_INT);
$m = optional_param('m', 0, PARAM_INT);
$s = optional_param('s', null, PARAM_TEXT);
require_login();

$context = context_system::instance();
$access = false;
if ($roles = get_user_roles($context, $USER->id)) {
    foreach ($roles as $role) {
        if ($role->shortname == 'manager') {
            $access = true;
            break;
        }
    }
}
// print_object($access); die;
//if (!($access || is_siteadmin())) {
//    print_error('Access denied');
//}

global $CFG, $OUTPUT, $PAGE, $USER;
$PAGE->requires->css(new moodle_url("/local/my_team/styles.css"));

$PAGE->set_context($context);

$PAGE->set_url(new moodle_url('/local/my_team/', $params));

$pluginname = get_string("pluginname", "local_my_team");
$PAGE->set_title($pluginname);
$PAGE->set_heading($pluginname);

echo $OUTPUT->header();

$userid = optional_param('uid', 0, PARAM_INT);

if ($userid == 0) {
    $userid = $USER->id;
}

$data =get_my_team_data($userid); //get_teamusers($page, $perpage, $m, $s);// 
echo '<style>
.buttons-csv,.buttons-pdf,.buttons-excel{display:none !important;}
    .progress-circle.full-complete .first50-bar,
    .progress-circle.full-complete .value-bar {
        background-color: #003152 !important;
    }

    .progress-circle.full-complete.over50 .first50-bar {
        background-color: #003152 !important;
    }
      .progress-circle.over50 .first50-bar {
    background-color: #003152 !important;}
    .value-bar{background-color: #003152 !important;border: .45em solid #003152;}
    .dataTables_scrollHead tr{background:#ec9707;
    th{color:#fff}
    }
</style>';

?>
<!DOCTYPE html>

<!-- DataTables -->
<link rel="stylesheet" href="../mydashboard/external/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../mydashboard/external/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="../mydashboard/external/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="../mydashboard/external/dist/css/adminlte.min.css">

<a href="index.php" class="btn btn-primary">Back to My Team</a>
<hr>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">My Team Report</h3>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>User Pic</th>
                    <th>Fullname</th>
                    <th>View Team</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Course Enrolled</th>
                    <th>Course Not Started</th>
                    <th>Course in-progress</th>
                    <th>Course Completed</th>
                    <!--<th>Achieved Certificate</th>-->
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($data as $d) {

                    $enrolled = get_user_enrolled_courses($d->id);
                    $ip = get_user_inprogress_courses($d->id);
                    $comp = get_user_completed_courses($d->id);;
                 $statusCounts= get_user_not_started_course_count($d->id);
                    $usercontext = context_user::instance($d->id);
                    $image = '<img src="' . $CFG->wwwroot . '/pluginfile.php/' . $usercontext->id . '/user/icon/f3" width="50">';
                    echo '<tr>';
                    echo '<td>' . $image . '</td>';
                    echo '<td>' . $d->firstname . ' ' . $d->lastname . '</td>';
                    echo '<td><a href="index.php?uid=' . $d->id . '">View</a></td>';
                    echo '<td>' . $d->email . '</td>';
                    echo '<td>' . $d->department . '</td>';
                  echo '<td>
        <a href="#" class="enrolled-course-link" data-userid="' . $d->id . '" data-username="' . fullname($d) . '">
            ' . get_progress_bar(count($enrolled), count($enrolled), 1) . '
        </a>
      </td>';
        echo '<td>
        <a href="#" class="notstarted-link" data-userid="' . $d->id . '" data-username="' . fullname($d) . '">
            ' . get_progress_bar($statusCounts, count($enrolled), 4) . '
        </a>
    </td>';
      
                    echo '<td>
    <a href="#" class="inprogress-link" 
       data-userid="' . $d->id . '" 
       data-username="' . fullname($d) . '">
       ' . get_progress_bar($ip, count($enrolled), 2) . '
    </a>
</td>';

echo '<td>
    <a href="#" class="completed-link" 
       data-userid="' . $d->id . '" 
       data-username="' . fullname($d) . '">
      ' . get_progress_bar($comp, count($enrolled), 3) . '
    </a>
</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>

        </table>
    </div>
    <!-- /.card-body -->
</div>
<!-- Enrolled Courses Modal -->
<div class="modal fade" id="enrolledCoursesModal" tabindex="-1" role="dialog" aria-labelledby="enrolledCoursesLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document"> <!-- centered here -->
    <div class="modal-content" style="border: 2px solid #003152; border-radius: 10px;">
      <div class="modal-header" style="background-color: #003152; color: #fff;">
        <h5 class="modal-title text-light" id="enrolledCoursesLabel">Enrolled Courses</h5>
        <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close" style="color: #fff;">
          <span aria-hidden="true" class="text-light" >&times;</span>
        </button>
      </div>
      <div class="modal-body" id="enrolledCoursesBody" style="background-color: #f9f9f9; border-radius: 10px;">
        <!-- Course list will load here -->
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" role="dialog" aria-labelledby="progressModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document"> <!-- changed to centered + large -->
    <div class="modal-content" style="border: 2px solid #003152; border-radius: 10px;">
      <div class="modal-header" style="background-color: #003152; color: #fff;">
        <h5 class="modal-title text-light">
          <span id="modalCourseType">In-Progress Courses</span> for <span id="modalUsername"></span>
        </h5>
        <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true" class="text-light">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="background-color: #f9f9f9; border-radius: 10px;">
        <table class="table table-bordered"  style="border-radius: 10px; overflow: hidden; box-shadow: 1px 3px 8px -3px #003152;">
          <thead>
            <tr style="background:#ec9707;">
              <th class="text-light">Sr No</th>
              <th class="text-light">Course Name</th>
              <th class="text-light">Percentage Completed</th>
              <th class="text-light">Total Activities</th>
              <th class="text-light">Completed Activities</th>
            </tr>
          </thead>
          <tbody id="progressModalBody">
            <!-- Filled by JS -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- activity status modal -->
<div class="modal fade" id="activityModal" tabindex="-1" role="dialog" aria-labelledby="activityModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header text-light" style="background:#003152;">
        <h5 class="modal-title text-light" id="activityModalTitle">Activity Details</h5>
        <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Add inside modal-body or modal-header as needed -->
<div class="text-right mb-2 px-3">
    <button id="downloadActivityCSV" class="btn btn-sm btn-success d-none">
        <i class="fa fa-download"></i> Download CSV
    </button>
</div>

        <table class="table table-bordered table-striped"  style="border-radius: 10px; overflow: hidden; box-shadow: 1px 3px 8px -3px #003152;">
          <thead>
            <tr style="background:#ec9707;">
              <th style="color:#fff;">Sr. No.</th>
              <th style="color:#fff;">Activity Name</th>
              <th style="color:#fff;">Activity Type</th>
              <th style="color:#fff;">Status</th>
            </tr>
          </thead>
          <tbody id="activityModalBody">
            <!-- Filled dynamically -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
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
            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');


    });
$(document).ready(function () {
    // Handle "Enrolled Courses" button click
    $('.enrolled-course-link').on('click', function (e) {
        e.preventDefault();
        var userid = $(this).data('userid');
        var username = $(this).data('username');

        $.ajax({
            url: 'getdata_ajax.php',
            method: 'POST',
            data: { userid: userid },
            success: function (response) {
                $('#enrolledCoursesLabel').text('Enrolled Courses for ' + username);
                $('#enrolledCoursesBody').html(response);
                $('#enrolledCoursesModal').modal('show');
            }
        });
    });
// Common handler for in-progress, completed, and not-started
$('.inprogress-link, .completed-link, .notstarted-link').on('click', function (e) {
    e.preventDefault();
    const userid = $(this).data('userid');
    const username = $(this).data('username');

    // Determine course type
    const isCompleted = $(this).hasClass('completed-link');
    const isNotStarted = $(this).hasClass('notstarted-link');
    const typeLabel = isCompleted ? 'Completed Courses' :
                      isNotStarted ? 'Not Started Courses' : 'In-Progress Courses';

    const noDataText = isCompleted ? 'No completed courses.' :
                        isNotStarted ? 'No not-started courses.' : 'No in-progress courses.';

    $('#modalCourseType').text(typeLabel);
    $('#modalUsername').text(username);

    const tbody = $('#progressModalBody');
    tbody.html('<tr><td colspan="5">Loading...</td></tr>');

    $.ajax({
        url: 'get_inprogress_courses_ajax.php', // common file
        method: 'GET',
        data: { userid: userid },
        dataType: 'json',
        success: function (data) {
            tbody.empty();

            //  Filter logic based on clicked button
            const filteredData = data.filter(row => {
                const percent = parseFloat(row.percentage);
                if (isCompleted) return percent === 100;
                if (isNotStarted) return percent === 0;
                return percent > 0 && percent < 100;
            });

            if (filteredData.length === 0) {
                tbody.html(`<tr><td colspan="5">${noDataText}</td></tr>`);
            } else {
                filteredData.forEach((row, index) => {
                    const tr = `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${row.coursename}</td>
                            <td>${row.percentage}%</td>
                            <td>${row.totalactivities}</td>
                            <td>${row.completedactivities}</td>
                        </tr>`;
                    tbody.append(`
    <tr>
        <td>${index + 1}</td>
        <td>${row.coursename}</td>
        <td>${row.percentage}%</td>
        <td>
            ${
                !isCompleted && !isNotStarted
                    ? `<a href="#" class="activity-link" data-courseid="${row.courseid}" data-userid="${userid}" data-coursename="${row.coursename}">
                            ${row.totalactivities}
                       </a>`
                    : row.totalactivities
            }
        </td>
        <td>${row.completedactivities}</td>
    </tr>
`);
;
                });
            }

            $('#progressModal').modal('show');
        },
        error: function () {
            tbody.html(`<tr><td colspan="5">Error loading ${typeLabel.toLowerCase()}.</td></tr>`);
        }
    });
});

// Delegate click to dynamically created .activity-link
$('#progressModalBody').on('click', '.activity-link', function (e) {
    e.preventDefault();
    const courseid = $(this).data('courseid');
    const userid = $(this).data('userid');
    const coursename = $(this).data('coursename');

    $('#activityModalTitle').text(`Activities in ${coursename}`);
    const tbody = $('#activityModalBody');
    tbody.html('<tr><td colspan="4">Loading...</td></tr>');

  let activityDataForExport = []; // store for CSV
let userFirstName = '', userLastName = '', courseName = '';

// Fetch user and course info first (one-time)
function fetchUserAndCourseInfo(userid, courseid) {
    return $.ajax({
        url: 'get_user_course_info.php',
        method: 'GET',
        data: { userid, courseid },
        dataType: 'json'
    });
}

// When loading modal
$.ajax({
    url: 'get_course_activities_ajax.php',
    method: 'GET',
    data: { courseid: courseid, userid: userid },
    dataType: 'json',
    success: function (data) {
        tbody.empty();
        activityDataForExport = data; // store for export

        if (data.length === 0) {
            tbody.html('<tr><td colspan="4">No activities found.</td></tr>');
        } else {
            data.forEach((activity, i) => {
                const row = `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${activity.activityname}</td>
                        <td>${activity.moduletype}</td>
                        <td>${activity.status}</td>
                    </tr>`;
                tbody.append(row);
            });
        }

        // Fetch user/course info before showing modal
        fetchUserAndCourseInfo(userid, courseid).then(function (info) {
            userFirstName = info.firstname;
            userLastName = info.lastname;
            courseName = info.fullname;
            $('#activityModal').modal('show');
        });
    },
    error: function () {
        tbody.html('<tr><td colspan="4">Error loading activities.</td></tr>');
    }
});

// CSV export logic
$('#downloadActivityCSV').on('click', function () {
    if (!activityDataForExport.length) return;

    let csv = 'First Name,Last Name,Course Name,Activity Name,Activity Type,Status\n';

    activityDataForExport.forEach(item => {
        // Strip HTML from status
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = item.status;
        const cleanStatus = tempDiv.textContent || tempDiv.innerText || "";

        csv += `"${userFirstName}","${userLastName}","${courseName}","${item.activityname}","${item.moduletype}","${cleanStatus}"\n`;
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `activity_report_${userFirstName}_${courseName}.csv`);
    link.click();
});

});


});

</script>

<?php
echo $OUTPUT->footer();
