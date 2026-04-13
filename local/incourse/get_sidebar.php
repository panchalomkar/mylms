<?php
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);
require_login($courseid);
require_once(__DIR__ . '/lib.php');

$course = get_course($courseid);

// Return only the sidebar course index HTML
echo local_incourse_render_course_index($course);