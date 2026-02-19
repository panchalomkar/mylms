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
$PAGE->set_heading(get_string('showlist', 'local_scheduler'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('showlist', 'local_scheduler'));
$PAGE->navbar->add(get_string('report', 'local_scheduler'));

// Display page header.
echo $OUTPUT->header();
$PAGE->requires->js_call_amd('local_scheduler/local', 'load');

$userid = $USER->id;

$success = optional_param('success', 0, PARAM_INT);
echo ' <a href="user_interest.php" class="btn btn-dark" style="float:right;">User Interest Report</a>';
echo '<div style="margin-bottom:50px;"></div>';
echo '<table id="example" style="width: 100%;text-align: center;overflow:auto;" border="1">
    <thead>
        <tr>
            <th>Course Name</th>
            <th>Time Zone</th>
            <th>Slot Date</th>
            <th>Slot Detail</th>
            <th>Created Date</th>
        </tr>
    </thead>
    <tbody>';

$SQL = "SELECT * FROM {scheduler_slot} GROUP BY courseid, slot_date ORDER BY id DESC";
$records = $DB->get_records_sql($SQL);


foreach ($records as $row) {
    $course = get_course($row->courseid);

    $slotSQL = "SELECT * FROM {scheduler_slot} WHERE "
            . " courseid = $row->courseid AND slot_date = '$row->slot_date'
                 ORDER BY slot_start";
    $slots = $DB->get_records_sql($slotSQL);
    $sdisplay = '<ul>';
    foreach ($slots as $slot) {
        $user = $DB->get_record('user', array('id' => $slot->userid));
        $teacher = $user->firstname . ' ' . $user->lastname;

        $bookedcount = $DB->count_records('scheduler_slot_book', array('sch_slotid' => $slot->id));
        $availablecount = $slot->max_user - $bookedcount;

        $usercount = $DB->count_records('scheduler_slot_book', array('sch_slotid' => $slot->id));
        $sdisplay .= '<li>' . $slot->slot_start . ' - ' . $slot->slot_end . ' (<i><a href="#" schid="' . $slot->id . '" class="getplist" data-toggle="modal" data-target="#exampleModal">' . $usercount . ' participants view</a>)<br><b>Teacher</b> - ' . $teacher . ', <b>Available slot</b> - '.$availablecount.'</i></li>';
    }
    $sdisplay .= '</ul>';
    echo '<tr>';
    echo '<td>' . $course->fullname . '</td>';
    echo '<td>' . $row->u_timezone . '</td>';
    echo '<td>' . $row->slot_date . '</td>';
    echo '<td>' . $sdisplay . '</td>';
    echo '<td>' . date('Y-m-d', $row->timecreated) . '</td>';
    echo '</tr>';
}

echo '</tbody>
</table>';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css" type="text/css">
<script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>


<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.0.1/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.print.min.js"></script>
<?php
echo $OUTPUT->footer();
?>

<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Participant List</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body participantlist">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>

    $(document).ready(function () {
        $('#example').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'csv', 'excel', 'pdf', 'print'
            ]
        });


    });
</script>

