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
 * @package    mod_videofile
 * @copyright  2013 Jonas Nockert
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

//Trigger instances list viewed event.
$event = \mod_videofile\event\course_module_viewed::create(array(
    'objectid' => $this->cm->instance,
    'context'  => \context_module::instance($this->cm->id)
));
$event->trigger();

$strvideofile    = get_string('modulename', 'videofile');
$strvideofiles   = get_string('modulenameplural', 'videofile');
$strsectionname  = get_string('sectionname', 'format_'.$course->format);
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/videofile/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strvideofiles);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strvideofiles);
echo $OUTPUT->header();

if (!$videofiles = get_all_instances_in_course('videofile', $course)) {
    notice(get_string('thereareno', 'moodle', $strvideofiles), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

if ($usesections) {
    $contextdata['usesections'] = true;
    $contextdata['strsectionname'] = $strsectionname;
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
$contextdata = [];
$tabledata = array();
foreach ($videofiles as $videofile) {
    $cm = $modinfo->cms[$videofile->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($videofile->section !== $currentsection) {
            if ($videofile->section) {
                $printsection = get_section_name($course, $videofile->section);
            }
            if ($currentsection !== '') {
                $t['hr'] = 'hr';
            }
            $currentsection = $videofile->section;
        }
    } else {
        $printsection = userdate($videofile->timemodified);
    }
    $t['printsection'] = $printsection;
    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        // Each videofile has an icon in 2.0.
        $icon = $OUTPUT->pix_url($cm->icon);
        $t['alt'] = get_string('modulename', $cm->modname);
    }
    $t['icon'] = $icon;
    // Dim hidden modules.
    $class = $videofile->visible ? '' : 'class="dimmed"';
    $t['class'] = $class;
    $t['extra'] = $extra;
    $t['cmid'] = $cm->id;
    $t['videofilename'] = format_string($videofile->name);
    $t['moduleintro'] = format_module_intro('videofile', $videofile, $cm->id);
    $tabledata[] = $t;

}
$contextdata['data'] = $tabledata;
echo $OUTPUT->render_from_template('mod_videofile/table', $contextdata);

echo $OUTPUT->footer();
