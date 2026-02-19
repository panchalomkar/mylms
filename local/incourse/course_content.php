<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Renders the course layout: left panel + main content area
 *
 * @param string $indexoutput HTML of the left panel (course index)
 * @param int $moduleid Module ID to load
 */
function course_content($indexoutput, $moduleid = 0) {
    global $OUTPUT, $COURSE;

echo html_writer::start_div('d-flex');

// Left course index container
echo html_writer::div($indexoutput, 'courseindex-container me-3', ['id' => 'course-index']);

// Main area (activity content or overview)
echo html_writer::start_div('flex-grow-1', ['id' => 'main-content']);

if ($moduleid) {
    echo html_writer::div('Loading activity...', 'text-muted', ['id' => 'loading-msg']);
} else {
    echo html_writer::div(
        html_writer::tag('h4', get_string('selectactivity', 'local_incourse')),
        'text-center text-muted py-5'
    );
}

echo html_writer::end_div(); // main-content
echo html_writer::end_div(); // d-flex
}
