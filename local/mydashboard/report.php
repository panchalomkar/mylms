<?php
require_once('../../config.php');

require_once 'lib.php';
global $DB, $CFG, $USER;

require_login();
$PAGE->set_url('/local/mydashboard/report.php');
$PAGE->set_title('Reports');
$PAGE->set_pagelayout('standard');
$reporturl = 'Home';
$PAGE->navbar->add($reporturl, new moodle_url('/my'));
$PAGE->navbar->add('Reports');
echo $OUTPUT->header();

//get user available points
$user_points = get_user_available_points();
$points_log = get_user_points_log();
$points_share = get_user_points_share();
$points_redeem = get_user_points_redeem();
?>
<!DOCTYPE html>

<!-- DataTables -->
<link rel="stylesheet" href="external/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="external/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="external/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="external/dist/css/adminlte.min.css">



    <!-- Content Wrapper. Contains page content -->
  

        <!-- Main content -->
        <div class="card bg-white p-3 card-body">
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <a href="level/index.php" class="btn btn-primary">Point Matrix</a>
                        <hr/>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">User Available points</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="example1" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Emp ID</th>
                                            <th>Fullname</th>
                                            <th>Email</th>
                                            <th>Avaialble Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($user_points as $points) {
                                            echo '<tr>';
                                            echo '<td>' . $points->username . '</td>';
                                            echo '<td>' . $points->firstname . ' ' . $points->lastname . '</td>';
                                            echo '<td>' . $points->email . '</td>';
                                            echo '<td>' . $points->available_points . '</td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>

                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>


                        <!--Point LOG-->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">User points logs</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="example2" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Emp ID</th>
                                            <th>Fullname</th>
                                            <th>Email</th>
                                            <th>Point Type</th>
                                            <th>Action</th>
                                            <th>Points</th>
                                            <th>Date Time</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($points_log as $log) {
                                            echo '<tr>';
                                            echo '<td>' . $log->username . '</td>';
                                            echo '<td>' . $log->firstname . ' ' . $log->lastname . '</td>';
                                            echo '<td>' . $log->email . '</td>';
                                            echo '<td>' . ucwords($log->point_type) . '</td>';
                                            echo '<td>' . $log->action . '</td>';
                                            echo '<td>' . $log->points . '</td>';
                                            echo '<td>' . date('d-m-Y H:i', $log->timecreated) . '</td>';
                                            echo '<td>' . $log->ip_addr . '</td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>

                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!--Point LOG END-->

                        <!--POINT SHARE-->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">User Share points</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="example3" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>From Emp ID</th>
                                            <th>To Emp ID</th>
                                            <th>Points</th>
                                            <th>Date Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($points_share as $share) {
                                            echo '<tr>';
                                            echo '<td>' . $share->username . '</td>';
                                            echo '<td>' . $share->tousername . '</td>';
                                            echo '<td>' . $share->points . '</td>';
                                            echo '<td>' . date('d-m-Y H:i', $share->timecreated) . '</td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>

                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>

                        <!--REDEEM-->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Redeem Points</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="example3" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Emp ID</th>
                                            <th>Fullname</th>
                                            <th>Email</th>
                                            <th>Points</th>
                                            <th>Date Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($points_redeem as $redeem) {
                                            echo '<tr>';
                                            echo '<td>' . $redeem->username . '</td>';
                                            echo '<td>' . $redeem->firstname . ' ' . $redeem->lastname . '</td>';
                                            echo '<td>' . $redeem->email . '</td>';
                                            echo '<td>' . $redeem->points . '</td>';
                                            echo '<td>' . date('d-m-Y H:i', $redeem->timecreated) . '</td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>

                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>

                        <!-- /.card -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
        </div>
 



<!-- ./wrapper -->

<!-- jQuery -->
<script src="external/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->

<script src="external/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="external/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="external/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="external/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="external/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="external/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="external/plugins/jszip/jszip.min.js"></script>
<script src="external/plugins/pdfmake/pdfmake.min.js"></script>
<script src="external/plugins/pdfmake/vfs_fonts.js"></script>
<script src="external/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="external/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="external/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

<!-- Page specific script -->
<script>
    $(function () {
        $("#example1").DataTable({
            "responsive": true, "lengthChange": false, "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

        $("#example2").DataTable({
            "responsive": true, "lengthChange": false, "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('#example2_wrapper .col-md-6:eq(0)');

        $("#example3").DataTable({
            "responsive": true, "lengthChange": false, "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('#example3_wrapper .col-md-6:eq(0)');


    });
</script>
<style>
    #page-local-mydashboard-report .has-blocks {
        display: none;
    }

</style>
<?php
echo $OUTPUT->footer();
