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
 * Prints a particular instance of videofile
 *
 * @package    mod_videofile
 * @copyright  2013 Jonas Nockert <jonasnockert@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$PAGE->requires->js(new moodle_url("/lib/xhprof/xhprof_html/jquery/jquery-1.2.6.js"));
$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('videofile', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$videofile = new videofile($context, $cm, $course);

require_login($course, true, $cm);
require_capability('mod/videofile:view', $context);

$PAGE->set_pagelayout('incourse');

$url = new moodle_url('/mod/videofile/view.php', array('id' => $id));
$PAGE->set_url('/mod/videofile/view.php', array('id' => $cm->id));

$event = \mod_videofile\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' =>  $PAGE->context
));
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$renderer = $PAGE->get_renderer('mod_videofile');

$videoData = $videofile->get_instance();

if ($videoData->forward == 0) {
	$PAGE->requires->js_call_amd('mod_videofile/disableForward', 'init');
}
$PAGE->requires->js_call_amd('mod_videofile/videocontroll', 'init');


if (trim($videoData->video_url)) {
	echo $renderer->video_url($videofile);
} else {
	echo $renderer->video_page($videofile);
}

$PAGE->requires->js_call_amd('mod_videofile/attempts', 'init');
    