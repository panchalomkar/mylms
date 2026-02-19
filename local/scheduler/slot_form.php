<?php
$SQL = "SELECT c.*
FROM glms_user u
INNER JOIN glms_role_assignments ra ON ra.userid = u.id
INNER JOIN glms_context ct ON ct.id = ra.contextid
INNER JOIN glms_course c ON c.id = ct.instanceid
INNER JOIN glms_role r ON r.id = ra.roleid
WHERE r.shortname IN ('teacher', 'editingteacher') AND ra.userid = $USER->id AND c.visible = 1";

$courses = $DB->get_records_sql($SQL);
$courseoptions = '<option value="">Select Course</option>';
foreach ($courses as $course) {
    $courseoptions .= '<option value="' . $course->id . '">' . $course->fullname . '</option>';
}

$today = date('Y-m-d');
?>

<form action="" method="POST" id="scheduletask_form">
    <div id="slotform">
        <div class="m-4">
            <strong>
                <div class="row g-4">
                    <div class="col-2">
                        Course
                    </div>
                    <div class="col-3">
                        Date
                    </div>
                    <div class="col-4">
                        Time
                    </div>
                    <div class="col-1">
                        Participant
                    </div>
                    <div class="col-1">
                        Action
                    </div>
                </div>
            </strong>
        </div>

        <div class="m-4 formrow" id="1">
            <div class="row g-4">
                <div class="col-2">
                    <select class="form-control singles" id="cid1" name="course[]" required>

                        <?php echo $courseoptions; ?>
                    </select>
                </div>
                <div class="col-3">
                    <input type="date" name="dates[]" class="form-control" min="<?php echo $today; ?>" placeholder="Date" required>
                </div>

                <div class="col-2">
                    <input type="time" name="starttime[]" id="st1" tagid="1" class="form-control starttime" placeholder="Start Time" required>
                </div>
                <div class="col-2">
                    <input type="time" name="endtime[]" id="et1" tagid="1" class="form-control endtime" placeholder="End Time" required>
                </div>
                <div>
                    <input type="number" min="1" name="max_user[]" class="form-control" value="10" required style="width:75px;">
                </div>
                <div class="col-2">
                    <button class="btn btn-primary" id="addrow">Add Row</button>
                </div>
            </div>
        </div>
    </div>
    <div class="actionbutton">
        <input type="submit" name="submitbutton" id="formsubmit" class="btn btn-primary" value="Save">
    </div>
</form>
