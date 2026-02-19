<?php
require_once ('../../config.php');
require_once($CFG->dirroot . '/local/ticketing/lib.php');
require_login();
global $CFG, $USER, $DB;

$title = get_string('report', 'local_ticketing');

$context = context_system::instance();
//require_capability('local/syllabus:view', $context);
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_url('/local/ticketing/report.php');
$PAGE->set_heading($title);
$PAGE->requires->css('/local/ticketing/style.css');
if (!is_siteadmin()) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <strong>Sorry! </strong>You are not authorized to see the report.
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>';
}
$PAGE->requires->jquery();

echo $OUTPUT->header();

$sql = "SELECT t.*, u.firstname, u.lastname FROM {ticketing} t INNER JOIN {user} u ON u.id = t.userid ORDER BY t.id DESC";
$records = $DB->get_records_sql($sql);

$completed = $DB->count_records('ticketing', ['status' => 'Completed']);
$inprogress = $DB->count_records('ticketing', ['status' => 'InProgress']);
$open = $DB->count_records('ticketing', ['status' => 'Open']);
?>
<link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot; ?>/local/lesson_plan/asset/jquery.dataTables.min.css">
<script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/local/lesson_plan/asset/jquery.dataTables.min.js"></script>
<div style="display: flex;"><div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fa fa-ticket"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Completed</span>
                <span class="info-box-number">
                    <?php echo $completed; ?>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>

    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fa fa-ticket"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">In Progress</span>
                <span class="info-box-number">
                    <?php echo $inprogress; ?>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>

    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-danger elevation-1"><i class="fa fa-ticket"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Open</span>
                <span class="info-box-number">
                    <?php echo $open; ?>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
</div>
<div class="mt-3">
    <table class="table table-bordered" id="report">
        <thead>
            <tr>
                <th>ID</th>
                <th>RAISED BY</th>
                <th>TENANT </th>
                <th>TITLE</th>
                <th>DESCRIPTION</th>
                <th>TYPE</th>
                <th>PRIORITY</th>
                <th>ATTACHMENTS</th>
                <th>STATUS</th>
                <th>ASSIGN TO</th>
                <th>CREATED TIME</th>
            </tr>
        </thead>
        <tbody class="tablebody">
            <?php
            foreach ($records as $u) {
                $attachments = get_attachments($u->id);
                echo '<tr>';
                echo '<td>#' . $u->id . '</a></td>';
                echo '<td>' . $u->firstname . ' ' . $u->lastname . '</a></td>';
                echo '<td>' . $u->department . '</a></td>';
                echo '<td>' . $u->title . '</td>';
                echo '<td>' . $u->description . '</td>';
                echo '<td>' . $u->type . '</td>';
                echo '<td>' . $u->priority . '</td>';
                echo '<td>' . $attachments . '</td>';
                echo '<td><label  id="statusval' . $u->id . '">' . $u->status . '</label><br> <a href="" statusid="' . $u->id . '" class="changestatus">Change</a><div class="statuslist' . $u->id . '"></div></td>';
                echo '<td>' . $u->assignto . '</td>';
                echo '<td>' . date('d-m-Y H:i', $u->timecreated) . '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>
<div class="show">
    <div class="overlay"></div>
    <div class="img-show">
        <span>X</span>
        <img src="">
    </div>
</div>
<!--End image popup-->



<script>

    $("#report").DataTable();

    $(function () {
        "use strict";

        $(".popup img").click(function () {
            var $src = $(this).attr("src");
            $(".show").fadeIn();
            $(".img-show img").attr("src", $src);
        });

        $("span, .overlay").click(function () {
            $(".show").fadeOut();
        });

    });

    $('body').on('click', '.changestatus', function (e) {
        e.preventDefault();
        var statusid = $(this).attr('statusid');
        var list = `<select name="statusv" class="updatestatus" id="${statusid}"><option value="InProgress">InProgress</option>
                    <option value="Completed">Completed</option>
                    <option value="Open">Open</option></select>`;
        $('.statuslist' + statusid).html(list)

    })

    $('body').on('change', '.updatestatus', function (e) {
        e.preventDefault();
        var status = $(this).val();
        var id = $(this).attr('id');
        if (status != '') {
            $.ajax({
                url: "ajax.php",
                type: "post",
                dataType: 'html',
                data: {action: "updateticketstatus", status: status, id: id},
                success: function (res) {
                    if (res != '') {
                        $('#statusval' + res).html(status);
                    }
                }
            });
        }
    })
</script>
<?php
echo $OUTPUT->footer();

