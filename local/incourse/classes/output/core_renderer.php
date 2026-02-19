<?php
namespace local_incourse\output;

defined('MOODLE_INTERNAL') || die();

use moodle_page;
use renderer_base;
use html_writer;

class core_renderer extends \theme_remui\output\core_renderer {

    /**
     * Override course content header.
     */
    public function course_content_header($course) {
        global $USER, $OUTPUT;

        $html = html_writer::start_div('local-incourse-header card shadow-sm p-3 mb-3');
        $html .= html_writer::tag('h2', '📘 ' . format_string($course->fullname), ['class' => 'mb-1']);
        $html .= html_writer::tag('p', 'Welcome, ' . fullname($USER) . '!', ['class' => 'text-muted']);
        $html .= html_writer::end_div();

        // Add a custom action button.
        $html .= html_writer::div(
            html_writer::link(
                new \moodle_url('/local/incourse/index.php', ['id' => $course->id]),
                'My Course Dashboard',
                ['class' => 'btn btn-primary mb-3']
            ),
            'text-center'
        );

        // Call parent for default UI.
        return $html . parent::course_content_header($course);
    }
}
