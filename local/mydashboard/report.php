<?php
require_once('../../config.php');
require_once('lib.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

require_login();

// ✅ SET CONTEXT FIRST (this fixes your error)
$context = context_system::instance();
$PAGE->set_context($context);

// Page setup
$PAGE->set_url('/local/mydashboard/report.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Reports');
$PAGE->set_heading('Reports');

// Navbar
$PAGE->navbar->add('Home', new moodle_url('/my'));
$PAGE->navbar->add('Reports');

// Output header
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

<div class="gam-report-wrap">

<style>
    .gam-report-wrap {
        --gam-primary: #003152;
        --gam-primary-light: #0a4a78;
        --gam-accent: #ec9707;
        --gam-accent-light: #ffb545;
        --gam-border: #e2e8f0;
        --gam-bg: #f7f9fb;
        --gam-text: #1f2d3d;
        --gam-text-muted: #6b7a8d;
        --gam-radius: 10px;

        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: var(--gam-text);
    }

    .gam-report-wrap * { box-sizing: border-box; }

    /* ---------- PAGE HEADER ---------- */
    .gam-report-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        background: linear-gradient(135deg, var(--gam-primary) 0%, var(--gam-primary-light) 100%);
        padding: 20px 24px;
        border-radius: var(--gam-radius);
        margin-bottom: 20px;
        box-shadow: 0 4px 14px rgba(0, 49, 82, 0.18);
    }

    .gam-report-header h2 {
        color: #ffffff;
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .gam-report-header h2::before {
        content: "";
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--gam-accent);
        box-shadow: 0 0 0 4px rgba(236, 151, 7, 0.25);
    }

    .gam-report-header .btn-primary {
        background: var(--gam-accent) !important;
        border: none !important;
        color: #ffffff !important;
        padding: 10px 20px !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        font-size: 0.88rem !important;
        text-decoration: none !important;
        transition: all 0.2s ease;
        box-shadow: 0 2px 6px rgba(236, 151, 7, 0.35);
    }

    .gam-report-header .btn-primary:hover {
        background: var(--gam-accent-light) !important;
        transform: translateY(-1px);
    }

    /* hide the default <hr/> divider, spacing is handled by header now */
    .gam-report-wrap hr { display: none; }

    /* ---------- OUTER CONTAINER CARD ---------- */
    .gam-report-wrap > .card.bg-white {
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    /* ---------- SECTION CARDS ---------- */
    .gam-report-wrap .card {
        border: 1px solid var(--gam-border);
        border-radius: var(--gam-radius) !important;
        box-shadow: 0 2px 8px rgba(0, 49, 82, 0.06);
        margin-bottom: 22px;
        overflow: hidden;
    }

    .gam-report-wrap .card-header {
        background: var(--gam-primary) !important;
        border-bottom: none;
        padding: 14px 20px;
    }

    .gam-report-wrap .card-header .card-title {
        color: #ffffff !important;
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .gam-report-wrap .card-header .card-title::before {
        content: "";
        width: 4px;
        height: 15px;
        background: var(--gam-accent);
        border-radius: 2px;
        display: inline-block;
    }

    .gam-report-wrap .card-body {
        padding: 18px 20px;
        background: #ffffff;
    }

    /* ---------- TABLE STYLING (kept table-bordered/table-striped classes for DataTables) ---------- */
    .gam-report-wrap table.dataTable {
        border-collapse: collapse !important;
        width: 100% !important;
        font-size: 0.86rem;
    }

    .gam-report-wrap table.dataTable thead th {
        background: var(--gam-bg);
        color: var(--gam-primary);
        font-weight: 700;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-bottom: 2px solid var(--gam-border) !important;
        padding: 11px 12px !important;
        white-space: nowrap;
    }

    .gam-report-wrap table.dataTable tbody td {
        padding: 10px 12px !important;
        vertical-align: middle;
        color: var(--gam-text);
        border-color: var(--gam-border) !important;
    }

    .gam-report-wrap table.dataTable.table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 49, 82, 0.025);
    }

    .gam-report-wrap table.dataTable tbody tr:hover {
        background-color: rgba(236, 151, 7, 0.07) !important;
    }

    /* ---------- DATATABLES CONTROLS (search box, buttons, pagination) ---------- */
    .gam-report-wrap .dataTables_wrapper .dataTables_filter input {
        border: 1.5px solid var(--gam-border);
        border-radius: 7px;
        padding: 6px 10px;
        font-size: 0.85rem;
    }

    .gam-report-wrap .dataTables_wrapper .dataTables_filter input:focus {
        outline: none;
        border-color: var(--gam-accent);
        box-shadow: 0 0 0 3px rgba(236, 151, 7, 0.15);
    }

    .gam-report-wrap .dt-buttons .btn,
    .gam-report-wrap .dataTables_wrapper .btn-secondary {
        background: var(--gam-primary) !important;
        border: none !important;
        color: #ffffff !important;
        font-size: 0.78rem !important;
        font-weight: 600 !important;
        border-radius: 6px !important;
        padding: 6px 12px !important;
        margin-right: 5px !important;
        transition: all 0.2s ease;
    }

    .gam-report-wrap .dt-buttons .btn:hover {
        background: var(--gam-accent) !important;
    }

    .gam-report-wrap .dataTables_wrapper .pagination .page-item.active .page-link {
        background-color: var(--gam-accent) !important;
        border-color: var(--gam-accent) !important;
        color: #ffffff !important;
    }

    .gam-report-wrap .dataTables_wrapper .pagination .page-link {
        color: var(--gam-primary);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid var(--gam-border);
    }

    .gam-report-wrap .dataTables_wrapper .pagination .page-link:hover {
        background-color: rgba(236, 151, 7, 0.12);
    }

    /* horizontal scroll safety on small screens even with DataTables responsive extension */
    .gam-report-wrap .table-responsive-wrap {
        width: 100%;
        overflow-x: auto;
    }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 768px) {
        .gam-report-header {
            padding: 16px 18px;
        }
        .gam-report-header h2 { font-size: 1.1rem; }

        .gam-report-wrap .card-body { padding: 14px; }
    }
</style>

    <!-- Content Wrapper. Contains page content -->


        <!-- Main content -->
        <div class="card bg-white p-3 card-body">
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">

                        <div class="gam-report-header">
                            <h2>Reports</h2>
                            <a href="level/index.php" class="btn btn-primary">Point Matrix</a>
                        </div>

                        <a href="level/index.php" class="btn btn-primary" style="display:none;">Point Matrix</a>
                        <hr/>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">User Available points</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <div class="table-responsive-wrap">
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
                                <div class="table-responsive-wrap">
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
                                <div class="table-responsive-wrap">
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
                                <div class="table-responsive-wrap">
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