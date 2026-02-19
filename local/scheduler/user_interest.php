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

echo ' <a href="report.php" class="btn btn-dark" style="float:right;">Slot Report</a>';
echo '<div style="margin-bottom:50px;"></div>';

echo '<table id="example" style="width: 100%;text-align: center;overflow:auto;" border="1">
    <thead>
        <tr>
            <th>User Name</th>
            <th>Course Name</th>
            <th>Time Zone</th>
            <th>Interest Date</th>
            <th>Interest Slot</th>
            <th>Created Date</th>
        </tr>
    </thead>
    <tbody>';

$SQL = "SELECT b.*, u.firstname, u.lastname, c.fullname FROM {scheduler_slot_book} b
        INNER JOIN {user} u ON u.id = b.userid
        INNER JOIN {course} c ON c.id = b.courseid
         WHERE sch_slotid IS NULL ORDER BY id DESC";
$records = $DB->get_records_sql($SQL);


foreach ($records as $row) {

    echo '<tr>';
    echo '<td>' . $row->firstname . ' ' . $row->lastname . '</td>';
    echo '<td>' . $row->fullname . '</td>';
    echo '<td>' . $row->u_timezone . '</td>';
    echo '<td>' . $row->slot_date . '</td>';
    echo '<td>' . $row->own_start . ' - ' . $row->own_end . '</td>';
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

