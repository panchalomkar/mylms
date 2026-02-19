<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contains the mobile output class for the custom certificate.
 *
 * @package   mod_customcert
 * @copyright 2018 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_videofile\output;

defined('MOODLE_INTERNAL') || die();

use context_module;
use mod_videofile;
use moodle_url;
//use videofile;
use completion_info;

header("Access-Control-Allow-Origin: *");
// require_once(dirname(__FILE__) . '/../../../../config.php');
//require_once($CFG->dirroot.'/mod/videofile/locallib.php');
/**
 * Mobile output class for the custom certificate.
 *
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the initial page when viewing the activity for the mobile app.
     *
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and other data
     */
    public static function mobile_video_view($args) {
    global $OUTPUT, $DB, $USER, $PAGE, $CFG;

    require_once($CFG->dirroot.'/mod/videofile/locallib.php');

        $args = (object) $args;

        $id = $args->cmid;

        $cm = get_coursemodule_from_id('videofile', $id, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $context = context_module::instance($cm->id);
        $videofile = new \videofile($context, $cm, $course);

        require_login($course, true, $cm);
        require_capability('mod/videofile:view', $context);


        $PAGE->set_pagelayout('incourse');

        $url = new moodle_url('/mod/videofile/view.php', array('id' => $id));
        $PAGE->set_url('/mod/videofile/view.php', array('id' => $cm->id));

        // Update 'viewed' state if required by completion system.
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);

        $renderer = $PAGE->get_renderer('mod_videofile');

        $videoData = $videofile->get_instance();
        if (trim($videoData->video_url)) {
            $videoData = $renderer->video_external_mobile($videofile);
        } else {
            $videoData = $renderer->video($videofile);
        }

        $js = $PAGE->requires->js_call_amd('mod_videofile/attempts', 'init');

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $videoData . $js,
                ],
            ],
            'javascript' => "setTimeout(function(){ document.getElementsByTagName('video')[0].load() }, 1000);",
            'otherdata' => ''
        ];
    }
}
