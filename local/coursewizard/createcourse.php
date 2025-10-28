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
 * @author Matthias Schwabe <support@eledia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package eledia_coursewizardF
 */
require_once('../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/coursewizard/createcourse_form.php');
require_once($CFG->dirroot . '/local/coursewizard/classes/course_form.php');
require_once($CFG->libdir . '/blocklib.php');

require_login();
defined('MOODLE_INTERNAL') || die();
/**
 * No show errors in this - Removing error_reporting(E_ALL);
 * @author Andres Ag.
 * @since date
 * @paradiso
 */
//error_reporting(E_ALL);
$id = optional_param('id', 0, PARAM_INT);  // Course id.
$cid = required_param('cid', PARAM_INT);  // Origin course id.
$categoryid = optional_param('category', 0, PARAM_INT);  // Course category - can be changed in edit form.
$returnto = optional_param('returnto', 0, PARAM_ALPHANUM);  // Generic navigation return page switch.
$selectedimage = optional_param('image_selected', '', PARAM_RAW);  // Generic navigation return page switch.
$sesskey = optional_param('sesskey', 0, PARAM_ALPHANUM);  // Session key
$pageparams = array('id' => $id);
if (empty($id)) {
    $pageparams = array('category' => $categoryid);
}
/**
* Fix the url of the course create and validate capabilities
* @author Andres Ag.
* @since March 09 of 2016
* @paradiso
*/
$pageparams['cid'] = $cid;
$PAGE->set_url('/local/coursewizard/createcourse.php', $pageparams);
$PAGE->set_pagelayout('default_plugins');
/**
* Add context by category if we get the category ID over the URL
* if not the validate in a system level
* @author Esteban E.
* @since August 24 of 2016
* @paradiso
*/
if($categoryid == 0 )
{
    require_capability('moodle/course:create', context_system::instance());
}else
{
    $categorycontext = context_coursecat::instance($categoryid) ;
    require_capability('moodle/course:create', $categorycontext);
}
$PAGE->requires->js_call_amd('local_coursewizard/main', 'init');
$course = null;
require_login();
if ($categoryid != 0) {
    $catcontext = context_coursecat::instance($categoryid);
}else{
    $catcontext = context_system::instance();
}
if (!empty($cid) && $cid > 1) {
    $course = get_course($cid);
    if ($course->format == 'paradisotabs') {
        $PAGE->set_pagelayout('format-tabs');
    }
}
$PAGE->set_context($catcontext);
// Prepare course and the editor.
$editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES,
                       'maxbytes' => $CFG->maxbytes,
                       'trusttext' => false,
                       'noclean' => true);
// Editor should respect category context if course context is not set.
$editoroptions['context'] = $catcontext;
$course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
//Add category and custom data for the form default  category selection
$objcategoty = \core_course_category::get($categoryid, IGNORE_MISSING);
$custom_data = ['category' => $objcategoty];
$course_form = new course_edit_form_cw(null, $custom_data, $editoroptions);
if ($data = $course_form->get_data()){
/*
* @author VaibhavG.
* @since 22 Dec 2020
* @desc VAPT Prevention. Applied funstion s()
*/
$data->fullname = s($data->fullname, $strip=false); 
$data->summary = s($data->summary_editor, $strip=false);
// Process data if submitted.
$data_selected = json_decode($selectedimage,true);
if (empty($course->id)) {
// In creating the course.
//$data->shortname = $data->fullname;
if (!empty($data->fullname)) {
    if ($DB->record_exists('course', array('shortname' => $data->fullname))) {
        $data->shortname = $data->fullname.rand(10,99);
    } else {
        $data->shortname = $data->fullname;
    }
}

$data->category = $categoryid;
$course = create_course($data, $editoroptions);
// Get the context of the newly created course.
$context = context_course::instance($course->id, MUST_EXIST);
if (!empty($CFG->creatornewroleid) and !is_viewing($context, NULL, 'moodle/role:assign') and !is_enrolled($context, NULL, 'moodle/role:assign')) {
// Deal with course creators - enrol them internally with default role.
    enrol_try_internal_enrol($course->id, $USER->id, $CFG->creatornewroleid);
}
// Validate if the user selected an image
if(!empty($data_selected)){
    $fs = get_file_storage();
    $file = $fs->get_file($data_selected['contextid'], $data_selected['component'], $data_selected['filearea'], $data_selected['itemid'], $data_selected['filepath'],$data_selected['filename']); 
    if($file){
        $file_record = array('contextid' => $context->id, 'component'=>'course', 'filearea'=>'overviewfiles',
        'itemid'=>0, 'filepath'=>'/', 'filename'=> $data_selected['filename']);
        $fs->create_file_from_storedfile($file_record, $file);
    }
}
// The URL to take them to if they chose save and display.
$courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
}
if (isset($data->saveanddisplay)) {
// Redirect user to newly created/updated course.
    $courseurl = new moodle_url('/course/changenumsections.php', array('courseid'=>$course->id,'insertsection'=>0,'sesskey'=>$sesskey,'sectionreturn'=>0,'numsections'=>1));
    redirect($courseurl);
} else {
    redirect($returnurl);
}
}
$site = get_site();
$streditcoursesettings = get_string("editcoursesettings");
$straddnewcourse = get_string("addnewcourse");
$stradministration = get_string("administration");
$strcategories = get_string("categories");
if ($cid <= 1) {
    $PAGE->navbar->add($stradministration, new moodle_url('/admin/index.php'));
    $PAGE->navbar->add($strcategories, new moodle_url('/course/index.php'));
    $PAGE->navbar->add($straddnewcourse);
} else {
    $course = get_course($cid);
    $PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php?id=' . $cid));
    $PAGE->navbar->add($streditcoursesettings);
}
$title = "$site->shortname: $straddnewcourse";
$fullname = $site->fullname;
$PAGE->set_title($title);
$PAGE->set_heading($fullname);
$capabilities = array('moodle/course:create', 'moodle/category:manage');
// Populate usercatlist with list of category id's with course:create and category:manage capabilities.
echo $OUTPUT->header();
require_once($CFG->dirroot . '/local/coursewizard/lib.php');
$courese_heading=get_string('courese_heading', 'coursewizard');
$coursecontext = context_course::instance(optional_param('cid',null, PARAM_INT));
if (has_capability('local/coursewizard:create_course', $coursecontext) OR has_capability('moodle/course:create', $catcontext)) {
    $data['has_capability'] = true;
    $data['val1'] = isset($allCourses)?$allCourses:'';
    $data['val3'] = optional_param('cid', null, PARAM_INT);
    //$data['formaa'] = $course_form->display();
}
echo  $OUTPUT->render_from_template('local_coursewizard/coursewizard', $data);
$data['form'] = $course_form->display();
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo $OUTPUT->footer();
