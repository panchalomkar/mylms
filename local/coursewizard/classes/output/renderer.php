<?php

/**
 * Official Mission local plugin renderer
 *
 * @package  local_coursewizard
 * @author   NileshJ
 * @since    June 30, 2021
 */
namespace local_coursewizard\output;
defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use renderable;

class renderer extends plugin_renderer_base {

    /**
     * Return the main content for the official mission.
     *
     * @param main $main The main renderable
     * @return string HTML string
     */
    public function render_get_recent_courses_images(main $main) {
        return $this->render_from_template('local_coursewizard/get_recent_courses_images', $main->get_recent_courses_images($this));
    }
}
