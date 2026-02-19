<?php
defined('MOODLE_INTERNAL') || die();

class block_recentaccesscourses extends block_base {
 
    public function init() {
        global $CFG;
        require_once("{$CFG->libdir}/completionlib.php");
        $this->title = get_string('pluginname', 'block_recentaccesscourses');
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

        $renderable = new \block_recentaccesscourses\output\main();
        $renderer = $this->page->get_renderer('block_recentaccesscourses');

        $this->content = new stdClass();
        $this->content->text = $renderer->render($renderable);
       

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
