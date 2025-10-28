<?php
// This file is part of the Contact Form plugin for Moodle - http://moodle.org/
//
// Contact Form is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Contact Form is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Contact Form.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This plugin for Moodle is used to send emails through a web form.
 *
 * @package    local_contact
 * @copyright  2016-2019 TNG Consulting Inc. - www.tngconsulting.ca
 * @author     Michael Milette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('locallib.php');

global $DB;
require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_heading(get_string('studentbook', 'local_scheduler'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_scheduler'));
$PAGE->navbar->add(get_string('studentbook', 'local_scheduler'));

$cids = optional_param('courseids', '', PARAM_RAW);

$localObj = new scheduler();
echo $OUTPUT->header();
$PAGE->requires->js_call_amd('local_scheduler/local', 'load');

$coursesql = "SELECT c.* FROM {course} c INNER JOIN {scheduler_slot} s ON s.courseid = c.id";
$courses = $DB->get_records_sql($coursesql);
$courseoptions = '';
foreach ($courses as $course) {
    if (in_array($course->id, $cids)) {
        $courseoptions .= '<option value="' . $course->id . '" selected>' . $course->fullname . '</option>';
    } else {
        $courseoptions .= '<option value="' . $course->id . '">' . $course->fullname . '</option>';
    }
}
?>

<!--<link rel="stylesheet" href="multiselect/bootstrap.min.css"  crossorigin="anonymous">
<link rel="stylesheet" href="multiselect/bootstrap-theme.min.css"  crossorigin="anonymous">
<link rel="stylesheet" href="multiselect/bootstrap-multiselect.css" type="text/css"  crossorigin="anonymous"/>
<script src="multiselect/jquery-3.2.1.min.js"  crossorigin="anonymous"></script>
<script src="multiselect/bootstrap.min.js"  crossorigin="anonymous"></script>
<script src="multiselect/bootstrap-multiselect.js"  crossorigin="anonymous"></script>-->


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css" />

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.full.min.js"></script>

<script type="text/javascript">


//        $('#multiselectwithsearch').multiselect({
//            includeSelectAllOption: true,
//            enableFiltering: true,
//            enableCaseInsensitiveFiltering: true,
//            filterPlaceholder: 'Search for course...'
//        });

    $(document).ready(function () {
        $.noConflict();
        $(function () {
            $("select").select2();
        });
    });


</script>

<?php
echo ' <a href="mybooking.php" class="btn btn-dark" style="float:right; margin:8px">My Slots</a>';
echo ' <a href="myineterest.php" class="btn btn-dark" style="float:right;margin:8px">My Interest</a>';
echo '<div style="margin-bottom:50px;"></div>';
if (isset($_POST['bookslot']) && $_POST['bookslot'] == 'Book Slot') {
//     print_object($_POST);
    if ($localObj->save_slot_booking($_POST)) {

        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
     <?php
// This file is part of the Contact Form plugin for Moodle - http://moodle.org/
//
// Contact Form is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Contact Form is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Contact Form.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This plugin for Moodle is used to send emails through a web form.
 *
 * @package    local_contact
 * @copyright  2016-2019 TNG Consulting Inc. - www.tngconsulting.ca
 * @author     Michael Milette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('locallib.php');

global $DB;
require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_heading(get_string('studentbook', 'local_scheduler'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_scheduler'));
$PAGE->navbar->add(get_string('studentbook', 'local_scheduler'));

$cids = optional_param('courseids', '', PARAM_RAW);

$localObj = new scheduler();
echo $OUTPUT->header();
$PAGE->requires->js_call_amd('local_scheduler/local', 'load');

//$coursesql = "SELECT c.* FROM {course} c INNER JOIN {scheduler_slot} s ON s.courseid = c.id";

$coursesql = "SELECT c.*
FROM glms_user u
INNER JOIN glms_role_assignments ra ON ra.userid = u.id
INNER JOIN glms_context ct ON ct.id = ra.contextid
INNER JOIN glms_course c ON c.id = ct.instanceid
INNER JOIN glms_scheduler_slot s ON s.courseid = c.id
INNER JOIN glms_role r ON r.id = ra.roleid
WHERE r.shortname IN ('student') AND ra.userid = $USER->id AND c.visible = 1";

$courses = $DB->get_records_sql($coursesql);
$courseoptions = '';
foreach ($courses as $course) {
    if (in_array($course->id, $cids)) {
        $courseoptions .= '<option value="' . $course->id . '" selected>' . $course->fullname . '</option>';
    } else {
        $courseoptions .= '<option value="' . $course->id . '">' . $course->fullname . '</option>';
    }
}
?>

<!--<link rel="stylesheet" href="multiselect/bootstrap.min.css"  crossorigin="anonymous">
<link rel="stylesheet" href="multiselect/bootstrap-theme.min.css"  crossorigin="anonymous">
<link rel="stylesheet" href="multiselect/bootstrap-multiselect.css" type="text/css"  crossorigin="anonymous"/>
<script src="multiselect/jquery-3.2.1.min.js"  crossorigin="anonymous"></script>
<script src="multiselect/bootstrap.min.js"  crossorigin="anonymous"></script>
<script src="multiselect/bootstrap-multiselect.js"  crossorigin="anonymous"></script>-->


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css" />

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.full.min.js"></script>

<script type="text/javascript">


//        $('#multiselectwithsearch').multiselect({
//            includeSelectAllOption: true,
//            enableFiltering: true,
//            enableCaseInsensitiveFiltering: true,
//            filterPlaceholder: 'Search for course...'
//        });

    $(document).ready(function () {
        $.noConflict();
        $(function () {
            $("select").select2();
        });
    });


</script>

<?php
echo ' <a href="mybooking.php" class="btn btn-dark" style="float:right; margin:6px;">My Slots</a>';
echo ' <a href="myineterest.php" class="btn btn-dark" style="float:right;margin:6px;">My Interest</a>';
echo '<div style="margin-bottom:50px;"></div>';
if (isset($_POST['bookslot']) && $_POST['bookslot'] == 'Book Slot') {
//     print_object($_POST);
    if ($localObj->save_slot_booking($_POST)) {

        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            Slots booked successfully
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    } else {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
            Kindly select the slot
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    }
}
?>
<h5>You can select multiple courses to book your slot at once</h5>



<form autocomplete="off" action="" method="post" accept-charset="utf-8" id="mform1" class="mform" enctype="multipart/form-data">



    <div id="fitem_id_parent" class="form-group row  fitem   ">
        <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">

            <label class="d-inline word-break " for="id_parent">
                Select Courses
            </label>

        </div>
        <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="select">

            <select class="custom-select"  name="courseids[]" id="selectprogram" style="width: 70%;" multiple>

                <?php echo $courseoptions; ?>
            </select>  

            <div class="form-control-feedback invalid-feedback" id="id_error_parent">

            </div>
        </div>
    </div>


    <div class="form-group">
        <input type="submit" class="btn btn-primary" value="Get Slots" name="submitslot">
    </div>
</form>


<?php
if (isset($_POST['submitslot']) && $_POST['submitslot'] == 'Get Slots' && $cids) {

    echo '<form action="" method="POST">';
    echo '<table id="example" class="admintable generaltable allusers" style="width: 100%;text-align: center;overflow:auto;" border="1">
    <thead>
        <tr>
            <th>Course Name</th>
            <!--<th>Time Zone</th>-->
            <th>Slot Date</th>
            <th>Book Slot</th>
          <!--  <th>Converted Slot</th> -->
        </tr>
    </thead>
    <tbody>';

    $cids = implode(',', $cids);
    $SQL = "SELECT * FROM {scheduler_slot} WHERE courseid IN ($cids) GROUP BY courseid, slot_date ORDER BY id DESC";
    $records = $DB->get_records_sql($SQL);


    foreach ($records as $row) {
        $course = get_course($row->courseid);

        $slotSQL = "SELECT * FROM {scheduler_slot}"
                . " WHERE  courseid = $row->courseid AND slot_date = '$row->slot_date'
                 ORDER BY slot_start";
        $slots = $DB->get_records_sql($slotSQL);
        $sdisplay = '<ul>';
        $con = '<ul>';
        foreach ($slots as $slot) {
            $user = $DB->get_record('user', array('id' => $slot->userid));
            $teacher = $user->firstname . ' ' . $user->lastname;

            $bookedcount = $DB->count_records('scheduler_slot_book', array('sch_slotid' => $slot->id));
            $availablecount = $slot->max_user - $bookedcount;
            //check if already booked
            $checked = '';
            $disabled = '';
            if ($DB->record_exists('scheduler_slot_book', array('userid' => $USER->id, 'sch_slotid' => $slot->id))) {
                $checked = 'checked';
                $disabled = 'disabled';
            } else if ($availablecount <= 0) {
                $disabled = 'disabled';
            }
//            $sdisplay .= '<li><input type="checkbox">&nbsp;&nbsp;' . $slot->slot_start . ' - ' . $slot->slot_end . '</li>';
            $con .= '<li><input type="checkbox" ' . $checked . ' ' . $disabled . ' name="booking[]" value="' . $slot->id . '@@' . $localObj->converToTz($slot->slot_start, usertimezone(), $slot->u_timezone) . ' - ' . $localObj->converToTz($slot->slot_end, usertimezone(), $slot->u_timezone) . '">&nbsp;&nbsp;' . $localObj->converToTz($slot->slot_start, usertimezone(), $slot->u_timezone) . ' - ' . $localObj->converToTz($slot->slot_end, 'America/New_York', $slot->u_timezone) . ' <i>(<b>Teacher</b> - ' . $teacher . ', <b>Available slot</b> - ' . $availablecount . ')</i></li>';
        }
        $sdisplay .= '</ul>';
        echo '<tr>';
        echo '<td>' . $course->fullname . '</td>';
//        echo '<td>' . $row->u_timezone . '</td>';
        echo '<td>' . $localObj->converToTzDate($row->slot_date, usertimezone(), $row->u_timezone) . '</td>';
//        echo '<td>' . $sdisplay . '</td>';
        echo '<td>' . $con . '</td>';
        echo '</tr>';
    }

    echo '</tbody>
        </table>';
    echo ' <input type="submit" class="btn btn-primary" value="Book Slot" name="bookslot">';
    echo '</form>';
    //  redirect('table.php');
}
?>
<script src="js/jquery-3.5.1.js"></script>
<?php
// Display page header.
echo $OUTPUT->footer();
       Slots booked successfully
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    } else {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
            Kindly select the slot
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    }
}
?>
<h5>You can select multiple courses to book your slot at once</h5>



<form autocomplete="off" action="" method="post" accept-charset="utf-8" id="mform1" class="mform" enctype="multipart/form-data">



    <div id="fitem_id_parent" class="form-group row  fitem   ">
        <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">

            <label class="d-inline word-break " for="id_parent">
                Select Courses
            </label>

        </div>
        <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="select">

            <select class="custom-select"  name="courseids[]" id="selectprogram" style="width: 70%;" multiple>

                <?php echo $courseoptions; ?>
            </select>  

            <div class="form-control-feedback invalid-feedback" id="id_error_parent">

            </div>
        </div>
    </div>


    <div class="form-group">
        <input type="submit" class="btn btn-primary" value="Get Slots" name="submitslot">
    </div>
</form>


<?php
if (isset($_POST['submitslot']) && $_POST['submitslot'] == 'Get Slots' && $cids) {

    echo '<form action="" method="POST">';
    echo '<table id="example" class="admintable generaltable allusers" style="width: 100%;text-align: center;overflow:auto;" border="1">
    <thead>
        <tr>
            <th>Course Name</th>
            <!--<th>Time Zone</th>-->
            <th>Slot Date</th>
            <th>Book Slot</th>
          <!--  <th>Converted Slot</th> -->
        </tr>
    </thead>
    <tbody>';

    $cids = implode(',', $cids);
    $SQL = "SELECT * FROM {scheduler_slot} WHERE courseid IN ($cids) GROUP BY courseid, slot_date ORDER BY id DESC";
    $records = $DB->get_records_sql($SQL);


    foreach ($records as $row) {
        $course = get_course($row->courseid);

        $slotSQL = "SELECT * FROM {scheduler_slot}"
                . " WHERE  courseid = $row->courseid AND slot_date = '$row->slot_date'
                 ORDER BY slot_start";
        $slots = $DB->get_records_sql($slotSQL);
        $sdisplay = '<ul>';
        $con = '<ul>';
        foreach ($slots as $slot) {
            $user = $DB->get_record('user', array('id' => $slot->userid));
            $teacher = $user->firstname . ' ' . $user->lastname;

            $bookedcount = $DB->count_records('scheduler_slot_book', array('sch_slotid' => $slot->id));
            $availablecount = $slot->max_user - $bookedcount;
            //check if already booked
            $checked = '';
            $disabled = '';
            if ($DB->record_exists('scheduler_slot_book', array('userid' => $USER->id, 'sch_slotid' => $slot->id))) {
                $checked = 'checked';
                $disabled = 'disabled';
            } else if ($availablecount <= 0) {
                $disabled = 'disabled';
            }
//            $sdisplay .= '<li><input type="checkbox">&nbsp;&nbsp;' . $slot->slot_start . ' - ' . $slot->slot_end . '</li>';
            $con .= '<li><input type="checkbox" ' . $checked . ' ' . $disabled . ' name="booking[]" value="' . $slot->id . '@@' . $localObj->converToTz($slot->slot_start, usertimezone(), $slot->u_timezone) . ' - ' . $localObj->converToTz($slot->slot_end, usertimezone(), $slot->u_timezone) . '">&nbsp;&nbsp;' . $localObj->converToTz($slot->slot_start, usertimezone(), $slot->u_timezone) . ' - ' . $localObj->converToTz($slot->slot_end, 'America/New_York', $slot->u_timezone) . ' <i>(<b>Teacher</b> - ' . $teacher . ', <b>Available slot</b> - ' . $availablecount . ')</i></li>';
        }
        $sdisplay .= '</ul>';
        echo '<tr>';
        echo '<td>' . $course->fullname . '</td>';
//        echo '<td>' . $row->u_timezone . '</td>';
        echo '<td>' . $localObj->converToTzDate($row->slot_date, usertimezone(), $row->u_timezone) . '</td>';
//        echo '<td>' . $sdisplay . '</td>';
        echo '<td>' . $con . '</td>';
        echo '</tr>';
    }

    echo '</tbody>
        </table>';
    echo ' <input type="submit" class="btn btn-primary" value="Book Slot" name="bookslot">';
    echo '</form>';
    //  redirect('table.php');
}
?>
<script src="js/jquery-3.5.1.js"></script>
<?php
// Display page header.
echo $OUTPUT->footer();

