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
* @package block_learningpathview
* @category local
* @copyright  ELS <admin@elearningstack.com>
* @author eLearningstack
*/
require_once('../../config.php');
require_login(); 
global $DB, $PAGE;
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/blocks/learningpathview/lp_view_course.php');
$PAGE->set_heading(get_string('lp_view_course', 'block_learningpathview'));
$PAGE->set_title(get_string('lp_view_course', 'block_learningpathview'));
$PAGE->navbar->add('Home', "/my");
$PAGE->navbar->add(get_string('lp_view_course', 'block_learningpathview'));
echo $OUTPUT->header();
$lpid = optional_param('id', 0, PARAM_INT);
$renderable = new \block_learningpathview\output\lpviewcourse($lpid);
$renderer = $PAGE->get_renderer('block_learningpathview');
echo $renderer->render($renderable);
echo $OUTPUT->footer();