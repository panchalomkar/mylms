<?php

namespace block_learningpathview\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    public function render_main(main $main) {
        return $this->render_from_template('block_learningpathview/main', $main->export_for_template($this));
    }

    public function render_lpviewcourse(lpviewcourse $lpviewcourse) {
        return $this->render_from_template('block_learningpathview/lpviewcourse', $lpviewcourse->export_for_template($this));
    }
}
