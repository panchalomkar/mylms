<?php
// run: php delete_courses.php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/moodlelib.php');

$categoryids = [2, 65, 66];

foreach ($categoryids as $catid) {
    $courses = $DB->get_records('course', ['category' => $catid]);
    foreach ($courses as $course) {
        if ($course->id != 1) {
            echo "Deleting course: {$course->fullname} (ID: {$course->id})\n";
            delete_course($course, false);
        }
    }
}

echo "✅ Done deleting courses from categories: " . implode(', ', $categoryids) . "\n";

