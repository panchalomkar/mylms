<?php
require_once('../../config.php');

require_once 'lib.php';
global $DB, $CFG, $USER;

require_login();
$PAGE->set_url('/local/mydashboard/mypoint.php');
$PAGE->set_title('My Points');
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add('My Points');
echo $OUTPUT->header();

$points_log = get_my_points_log($USER->id);
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
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <a href="index.php" class="btn btn-primary"> S & I Points </a>
                        <hr/>

                       
                        <!--Point LOG-->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">My points logs</h3>
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
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>

                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!--Point LOG END-->
                        <!-- /.card -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
 



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

        $("#example2").DataTable({
            "responsive": true, "lengthChange": false, "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('#example2_wrapper .col-md-6:eq(0)');
    });
</script>
<style>
    #page-local-mydashboard-mypoint .has-blocks {
        display: none;
    }

</style>
<?php
echo $OUTPUT->footer();
