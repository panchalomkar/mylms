<?php

require_once('../../config.php');
require_login();
global $PAGE;
// $courseid = required_param('courseid', PARAM_INT);
$courseid = 1;
$page = optional_param('page', 'view', PARAM_TEXT);

$title = get_string('viewcourse', 'local_course_ai');
$context = context_course::instance($courseid);

$PAGE->set_url(new moodle_url('/local/course_ai/index.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');
// $PAGE->requires->js_call_amd('local_course_section/addform', 'addmoreform');

$PAGE->requires->jquery();
// Validate the course
// if (!$course = $DB->get_record('course', ['id' => $courseid])) {
//     throw new moodle_exception('invalidcourseid', 'error');
// }


switch ($page) {
    case 'view':
        redirect(new moodle_url('/local/course_ai/view.php', ['courseid' => $courseid]));
        break;

    // case 'view':
    //     redirect(new moodle_url('/local/course_section/tasks.php', ['courseid' => $courseid]));
    //     break;

    // case 'addtask':
    //     redirect(new moodle_url('/local/course_section/task_detail.php', ['courseid' => $courseid]));
    //     break;

    default:
        echo $OUTPUT->header();
        echo '<p>Invalid page parameter.</p>';
        echo $OUTPUT->footer();
        break;
}
