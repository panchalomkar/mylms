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

echo '<a href="book.php" class="btn btn-primary">Book Slot</a>';
echo '<div style="margin-bottom:10px;"></div>';

echo '<table id="example" style="width: 100%;text-align: center;overflow:auto;" border="1">
    <thead>
        <tr>
            <th>Course Name</th>
            <th>Time Zone</th>
            <th>Slot Date</th>
            <th>Time Slot</th>
            <th>Own Interest Slot</th>
            <th>Created Date</th>
        </tr>
    </thead>
    <tbody>';

$SQL = "SELECT b.*, s.courseid, s.slot_date FROM {scheduler_slot} s 
            INNER JOIN {scheduler_slot_book} b  ON b.sch_slotid = s.id
         WHERE b.userid = $USER->id GROUP BY s.courseid, s.slot_date ORDER BY b.timecreated ASC";
$records = $DB->get_records_sql($SQL);


foreach ($records as $row) {
    $course = get_course($row->courseid);

    $slotSQL = "SELECT b.*, s.userid As tutor FROM {scheduler_slot} s
                INNER JOIN {scheduler_slot_book} b ON b.sch_slotid = s.id
                WHERE b.userid = $USER->id 
                AND s.courseid = $row->courseid AND s.slot_date = '$row->slot_date'
                 ORDER BY b.slot_start";
    $slots = $DB->get_records_sql($slotSQL);
    $sdisplay = '<ul>';
    foreach ($slots as $slot) {
        $user = $DB->get_record('user', array('id' => $slot->tutor));
        $teacher = $user->firstname . ' ' . $user->lastname;
        $sdisplay .= '<li>' . $slot->slot_start . ' - ' . $slot->slot_end . '  <i>(<b>Teacher</b> - ' . $teacher . ')</i></li></li>';
    }
    $sdisplay .= '</ul>';
    echo '<tr>';
    echo '<td>' . $course->fullname . '</td>';
    echo '<td>' . $row->u_timezone . '</td>';
    echo '<td>' . $row->slot_date . '</td>';
    echo '<td>' . $sdisplay . '</td>';
    echo '<td></td>';
    echo '<td>' . date('Y-m-d', $row->timecreated) . '</td>';
    echo '</tr>';
}



$SQL1 = "SELECT s.*, c.fullname FROM {scheduler_slot_book} s 
            INNER JOIN {course} c  ON c.id = s.courseid
         WHERE s.userid = $USER->id AND s.sch_slotid IS NULL ORDER BY s.timecreated ASC";
$records1 = $DB->get_records_sql($SQL1);


foreach ($records1 as $row1) {

    echo '<tr>';
    echo '<td>' . $row1->fullname . '</td>';
    echo '<td>' . $row1->u_timezone . '</td>';
    echo '<td>' . $row1->slot_date . '</td>';
    echo '<td></td>';
    echo '<td>' . $row1->own_start . ' - '.$row1->own_end.'</td>';
    echo '<td>' . date('Y-m-d', $row1->timecreated) . '</td>';
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
        $('#example').DataTable({
            "order": [[2, "desc"]]
        });

        $('.infotext').popover({trigger: "hover"});
    });
</script>

