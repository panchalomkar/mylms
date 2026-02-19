<?php
require_once(dirname(__FILE__) . "/../../config.php");
$courseid = required_param("courseid", PARAM_INT);
$quizzes = $DB->get_records("quiz", ["course" => $courseid]);
echo json_encode(array_values($quizzes));
?>