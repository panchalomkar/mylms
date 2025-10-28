<?php
/**
 * enroll_by_profile renderer
 *
 * @package    local_enroll_by_profile
 * @copyright  2021 Ajinkya D
 * @license    Paradiso
 */

namespace local_enroll_by_profile\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;

/**
 * enroll_by_profile renderer
 *
 * @package    local_enroll_by_profile
 * @copyright  2021 Ajinkya D
 * @license    Paradiso
 */
class renderer extends plugin_renderer_base {
    /**
     * Return the main content for the enroll_by_profile.
     *
     * @param enroll_by_profile $enroll_by_profile The enroll_by_profile renderable
     * @return string HTML string
     */
    public function render_enrollbyprofile(enrollbyprofile $enrollbyprofile) {
        return $this->render_from_template('local_enroll_by_profile/enrollbyprofile', $enrollbyprofile->export_for_template($this));
    }

   
}
