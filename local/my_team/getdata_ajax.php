<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/my_team/lib.php');
require_login();

$userid = required_param('userid', PARAM_INT);
$courses = get_user_enrolled_courses($userid);

if (!$courses) {
    echo "<p>No enrolled courses found.</p>";
    exit;
}

echo "<ul class='list-group'>";
foreach ($courses as $course) {
   echo "<li class='list-group-item' style='border-left: 4px solid #ec9707;'>{$course->fullname}</li>";

}
echo "</ul>";
