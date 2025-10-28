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
$PAGE->navbar->add(get_string('pluginname', 'local_scheduler'));

// Display page header.
echo $OUTPUT->header();
$userid = $USER->id;

$success = optional_param('success', 0, PARAM_INT);
if ($success == 1) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
  Slots successfully submitted
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>';
}

echo '<a href="index.php" class="btn btn-primary">Add New Slot</a>';
echo '<div style="margin-bottom:10px;"></div>';

echo '<table id="example" style="width: 100%;text-align: center;overflow:auto;" border="1">
    <thead>
        <tr>
            <th>Course Name</th>
            <th>Time Zone</th>
            <th>Slot Date</th>
            <th>Time Slot</th>
            <th>Created Date</th>
        </tr>
    </thead>
    <tbody>';

$SQL = "SELECT * FROM {scheduler_slot} WHERE userid = $userid GROUP BY courseid, slot_date ORDER BY id DESC";
$records = $DB->get_records_sql($SQL);


foreach ($records as $row) {
    $course = get_course($row->courseid);

    $slotSQL = "SELECT * FROM {scheduler_slot} WHERE userid = $userid 
                AND courseid = $row->courseid AND slot_date = '$row->slot_date'
                 ORDER BY slot_start";
    $slots = $DB->get_records_sql($slotSQL);
    $sdisplay = '<ul>';
    foreach ($slots as $slot) {
        $sdisplay .= '<li>' . $slot->slot_start . ' - ' . $slot->slot_end . ' (<i>Max allowed users - '.$slot->max_user.'</i>)</li>';
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
<?php
echo $OUTPUT->footer();
?>
<script>

    $(document).ready(function () {
        $('#example').DataTable();

        $('.infotext').popover({trigger: "hover"});
    });
</script>

