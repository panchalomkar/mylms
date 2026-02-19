<?php
/**
 * Displays information about all the assignment modules in the requested course
 *
 * @package   local_my_team
 * @author    Jayesh
 */
require_once("../../config.php");
require_once($CFG->dirroot . '/local/my_team/lib.php');


require_login();

$context = context_system::instance();

global $CFG, $OUTPUT, $PAGE, $USER;
$PAGE->requires->css(new moodle_url("/local/my_team/styles.css"));

$PAGE->set_context($context);

$PAGE->set_url(new moodle_url('/local/my_team/', $params));

$pluginname = get_string("pluginname", "local_my_team");
$PAGE->set_title($pluginname);
$PAGE->set_heading($pluginname);

echo $OUTPUT->header();

$cid = optional_param('cid', 0, PARAM_INT);
$catid = optional_param('catid', 0, PARAM_INT);
if ($cid) {
    $coursecontext = context_course::instance($cid);
    $courses = $DB->get_records_sql("select * from {course} where id > 1 and category = $catid");
}
$categories = $DB->get_records('course_categories');


$myusers = get_my_team_data($USER->id);
?>
<!DOCTYPE html>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<link rel="stylesheet" type="text/css" href="asset/bootstrap-duallistbox.css">
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="asset/jquery.bootstrap-duallistbox.js"></script>
<div class="form-inline p-3 shadow-sm mb-3 rounded" style="
    background: linear-gradient(271deg,#3F51B5,#009688);">
<select name="catid" id="catid" class="form-control mr-2">
    <option value>Select category</option>
    <?php
    foreach ($categories as $cat) {
        $selected = ($cat->id == $catid) ? 'selected' : '';
        echo '<option value="' . $cat->id . '" ' . $selected . '>' . $cat->name . '</option>';
    }
    ?>
</select>
<select name="cid" id="cid"  class="form-control">
    <option value>Select course</option>
    <?php
    foreach ($courses as $c) {
        $selected1 = ($c->id == $cid) ? 'selected' : '';
        echo '<option value="' . $c->id . '" ' . $selected1 . '>' . $c->fullname . '</option>';
    }
    ?>
</select>
</div>
<div class="row">

    <div class="col-md-12 _custom-local-style">
        <select multiple="multiple" size="10" name="duallistbox_demo2" class="demo2 form-control" title="duallistbox_demo2">
            <?php
            foreach ($myusers as $user) {
                if (!empty($coursecontext)) {
                    $userselected = (is_enrolled($coursecontext, $user)) ? 'selected' : '';
                }
                echo '<option value="' . $user->id . '" ' . $userselected . '>' . fullname($user) . '</option>';
            }
            ?>
        </select>
    </div>

    <div class="col-12 my-4">
        <button id="enrolusers" class="btn btn-primary">Save Changes</button>
    </div>
</div>


<!--<div class="ajaxmessage"></div>-->

    <div id="ajaxMessageModal" class="modal fade _custom-video-modal">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content alert alert-success">
                <button typemodal-header="button" class="close _change-close-style" data-dismiss="modal" aria-hidden="true" style="right: 16px;z-index: 1;top: 6px; position: absolute; display: flex; justify-content: center; align-items: center; font-size: 1rem;">×</button>
                <div class="modal-body pop-content ajaxmessage  h3 text-center font-weight-bold">

                </div>
            </div>
        </div>
    </div>

<script>
    var demo2 = $('.demo2').bootstrapDualListbox({
        nonSelectedListLabel: 'Potential Users',
        selectedListLabel: 'Enrolled Users',
        preserveSelectionOnMove: 'moved',
        moveOnSelect: false,
        //        nonSelectedFilter: 'ion ([7-9]|[1][0-2])'
    });

    $('#catid').change(function () {

        var catid = $(this).val();
        $.ajax({
            url: "<?php echo $CFG->wwwroot; ?>/local/my_team/ajax/my_team_ajax.php",
            type: "post",
            dataType: "html",
            data: {action: "get_cat_courses", catid: catid},
            success: function (res) {
                if (res != '') {
                    $('#cid').html(res);
                }
            }
        });

    })

    $('#cid').change(function () {
        var catid = $('#catid').val();
        var cid = $(this).val();
        if (catid > 0 && cid > 0) {
            window.location.href = 'enrol.php?catid=' + catid + '&cid=' + cid;
        }
    })

    $('#enrolusers').click(function () {
        var userids = [];
        var cid = $('#cid').val();
        $('#bootstrap-duallistbox-selected-list_duallistbox_demo2 option').each(function (e, v) {

            userids.push(v.value);

        });
        $.ajax({
            url: "<?php echo $CFG->wwwroot; ?>/local/my_team/ajax/my_team_ajax.php",
            type: "post",
            dataType: "html",
            data: {action: "enrolusers", cid: cid, userids: userids},
            success: function (res) {
                $("#ajaxMessageModal").modal('show');
                $('.ajaxmessage').html('User enrolled successfully')
            }
        });
    })
</script>

<?php
echo $OUTPUT->footer();
