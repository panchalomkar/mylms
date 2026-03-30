<?php
defined('MOODLE_INTERNAL') || die();

class block_learningpathview extends block_base {
 
    public function init() {
        global $CFG;
        require_once("{$CFG->libdir}/completionlib.php");
        $this->title = get_string('pluginname', 'block_learningpathview');
    }

    public function get_content() {
        global $OUTPUT,$USER,$DB;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $course = $this->page->course;
        
        $context = context_course::instance($course->id);
        $courses = $DB->get_record('course', array('id' => $course->id));
        $info = new completion_info($courses);

        $renderable = new \block_learningpathview\output\main($course->id, $USER->id,$info);
        $renderer = $this->page->get_renderer('block_learningpathview');

        $this->content = new stdClass();
        $this->content->text = $renderer->render($renderable);
        $this->content->footer = '';

        return $this->content;
    }
  
    public function applicable_formats() {
        return ['all' => true];
    }
   
    public function instance_allow_multiple() {
        return false;
    }

    function has_config() {
        return false;
    }
}
