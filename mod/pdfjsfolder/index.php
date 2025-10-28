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
 * @package    mod_pdfjsfolder
 * @copyright  2013 Jonas Nockert
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

// add_to_log($course->id,
//            'pdfjsfolder',
//            'view all',
//            "index.php?id=$course->id",
//            '');

$strpdf          = get_string('modulename', 'pdfjsfolder');
$strpdfs         = get_string('modulenameplural', 'pdfjsfolder');
$strsectionname  = get_string('sectionname', 'format_'.$course->format);
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/pdfjsfolder/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strpdfs);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strpdfs);
echo $OUTPUT->header();

if (!$pdffolders = get_all_instances_in_course('pdfjsfolder', $course)) {
    notice(get_string('thereareno',
                      'moodle',
                      $strpdfs),
           "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($pdffolders as $pdffolder) {
    $cm = $modinfo->cms[$pdffolder->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($pdffolder->section !== $currentsection) {
            if ($pdffolder->section) {
                $printsection = get_section_name($course, $pdffolder->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $pdffolder->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($pdffolder->timemodified)."</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        $icon = '<img src="' . $OUTPUT->pix_url($cm->icon) .
                '" class="activityicon" alt="' .
                get_string('modulename', $cm->modname) . '" /> ';
    }
    // Dim hidden modules.
    $class = $pdffolder->visible ? '' : 'class="dimmed"';
    $table->data[] = array (
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">" .
            $icon . format_string($pdffolder->name) . "</a>",
        format_module_intro('pdfjsfolder', $pdffolder, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
