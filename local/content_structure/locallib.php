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
 * Local helper functions.
 *
 * @package    local_question_grade
 * @author     Uvais
 * @copyright  2012 Isuru Madushanka Weerarathna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once 'lib.php';

class local_content_structure
{

    public function save_structure($data, $file)
    {
        global $DB, $CFG;
        $path = $CFG->dataroot . '/repository/content';

        if (isset($data['submitbutton']) && $data['submitbutton'] == 'Create Folder') {


            if ($data['parent'] > 0) {
                $parent = $DB->get_record('local_content_structure', array('id' => $data['parent']));
                // get folder path
                $path = $path . '/' . $this->get_folder_path($parent, '');
            }

            //check if folder already exists

            if ($data['name'] != '') {
                if (!file_exists($path . '/' . $data['name'])) {

                    mkdir($path . '/' . $data['name'], 0777, true);

                    $object = new stdClass();
                    $object->parent = $data['parent'];
                    $object->name = $data['name'];
                    $object->idnumber = $data['idnumber'];
                    $object->timecreated = time();
                    if ($id = $DB->insert_record('local_content_structure', $object)) {
                        $this->save_files($id, $file, $path . '/' . $data['name']);
                    }
                }
            } else {
                $this->save_files($data['parent'], $file, $path);
            }
            //            echo $path;
        }
    }

    protected function save_files($id, $file, $path)
    {
        global $DB;
        $filearray = $file["files"];
        for ($i = 0; $i < count($filearray['name']); $i++) {
            $filename = time() . str_replace(' ', '', basename($filearray["name"][$i]));
            if ($filearray["error"][$i] == 0 && $filearray["size"][$i] > 0) {

                if (move_uploaded_file($filearray["tmp_name"][$i], $path . '/' . $filename)) {
                    //save entry
                    $object = new stdClass();
                    $object->contentid = $id;
                    $object->filename = $filename;
                    $object->timecreated = time();

                    $DB->insert_record('content_structure_files', $object);
                }
            }
        }
    }

    protected function get_folder_path($structure, $path)
    {
        global $DB;

        if ($structure->parent == 0) {

            $path = $structure->name . '/' . $path;
        } else {
            //get parent
            $parent = $DB->get_record('local_content_structure', array('id' => $structure->parent));
            $path = $parent->name . '/' . $structure->name;
            $this->get_folder_path($parent, $path);
        }
        return rtrim($path, '/');
    }

    public function display_list($nested_categories)
    {
        global $DB;

        $records = $DB->get_records('local_content_structure', array());
        $list = '<ul>';
        foreach ($nested_categories as $nested) {

            $list .= '<li>' . $nested['name'] . '</li>';

            if (!empty($nested['nested_categories'])) {
                $list .= display_list($nested['nested_categories']);
            }
        }
        $list .= '</ul>';

        return $list;
    }

    public function display_option($nested_categories, $mark = ' ')
    {
        global $DB;
        $option = '';
        foreach ($nested_categories as $nested) {

            $option .= '<option value="' . $nested['id'] . '">' . $mark . $nested['name'] . '</option>';

            if (!empty($nested['nested_categories'])) {
                $option .= $this->display_option($nested['nested_categories'], $mark . '&nbsp;&nbsp;&nbsp;');
            }
        }
        return $option;
    }

    public function multilevel_categories($parent_id = 0)
    {
        global $DB;

        $records = $DB->get_records('local_content_structure', array('parent' => $parent_id));
        $catData = [];
        if ($records) {

            foreach ($records as $record) {
                $catData[] = [
                    'id' => $record->id,
                    'parent' => $record->parent,
                    'name' => $record->name,
                    'nested_categories' => $this->multilevel_categories($record->id)
                ];
            }

            return $catData;
        } else {
            return $catData = [];
        }
    }

    //Dsiplay add module form

    public function display_module_form($id, $archtype)
    {
        global $DB, $CFG;

        $programs = get_programs();
        $modules = get_course_content($id, 'module');
        $lessons = get_course_content($id, 'lesson');
        $record = $DB->get_record('local_content_structure', array('id' => $id));
        $get_modules = $DB->get_records('local_content_structure', array('parent' => $record->id, 'archtype' => 'module'));
        $firstmod = current($get_modules);
        $get_lessons = $DB->get_records('local_content_structure', array('parent' => $firstmod->id, 'archtype' => 'lesson'));

        $html = '<form autocomplete="off" action="container.php" method="post" accept-charset="utf-8" id="addmoduleform" class="mform" enctype="multipart/form-data">
    <div style="display: none;">
        <input name="parent" type="hidden" value="' . $record->parent . '">
        <input name="archtype" type="hidden" value="' . $archtype . '">
        <input name="id" type="hidden" id="hiddenid" value="">
    </div>
    <div id="fitem_id_parent" class="form-group row  fitem   ">
        <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">
            <label class="d-inline word-break " for="id_parent">
                Program Name
            </label>
        </div>
        <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="select">
            <select class="custom-select" name="parent" id="selectprogram" style="width: 30%;" disabled>';
        foreach ($programs as $program) {
            if ($record->parent == $program->id) {
                $html .= '<option value="' . $program->id . '" selected>' . $program->name . '</option>';
            }
        }
        $html .= '</select>  
        </div>
    </div>
    <div class="form-group row  fitem">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="id_name">
                Course Name
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="text" class="form-control " name="name" id="id_name" value="' . @$record->name . '" size="" disabled>
            <input type="hidden" name="course" id="id_name" value="' . @$record->id . '">
        </div>
    </div>';

        if ($archtype == 'lesson') {

            $html .= ' <div class="form-group row  fitem">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="id_name">
                Module Name
            </label>
        </div>
        <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="select">
            <select class="custom-select" name="module" id="moduleid" style="width: 30%;" onchange="getLesson(this);">';
            foreach ($modules as $module) {

                $html .= '<option value="' . $module->id . '">' . $module->name . '</option>';
            }
            $html .= '</select>  
        </div>
    </div>';
            $html .= '<div class="form-group row  fitem">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="id_name">
                Lesson Name
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="text" class="form-control " name="name" id="less_name" value="" size="" required>
        </div>
    </div>';
            $list = '<ol>';
            foreach ($get_lessons as $less) {
                $list .= '<li>'
                    . '<a href="#" class="editless" image="' . $less->image . '" path="' . $CFG->wwwroot . '" id="' . $less->id . '">' . $less->name . '</a></li>';
            }
            $list .= '</ol>';
        }
        if ($archtype == 'module') {
            $html .= ' <div class="form-group row  fitem">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="id_name">
                Module Name
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="text" class="form-control " name="name" id="cid_name" value="" size="" >
        </div>
    </div>';

            $list = '<ol>';
            foreach ($get_modules as $mod) {
                $list .= '<li>'
                    . '<a href="#" class="editmod" image="' . $mod->image . '" path="' . $CFG->wwwroot . '" id="' . $mod->id . '">' . $mod->name . '</a></li>';
            }
            $list .= '</ol>';
        }

        if ($archtype == 'learning') {
            $html .= ' <div class="form-group row  fitem">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="id_name">
                Module Name
            </label>
        </div>
        <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="select">
            <select class="custom-select" name="module" id="moduleid" style="width: 30%;" onchange="getLesson(this);">';
            $html .= '<option value="">Select Module</option>';
            foreach ($modules as $module) {

                $html .= '<option value="' . $module->id . '">' . $module->name . '</option>';
            }
            $html .= '</select>  
        </div>
    </div>';
            $html .= '<div class="form-group row  fitem">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="id_name">
                Lesson Name
            </label>
        </div>
        <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="select">
            <select class="custom-select" name="lesson" id="lessonid" style="width: 30%;" onchange="getlearning(this);">';
            //            foreach ($lessons as $lesson) {
//
//                $html .= '<option value="' . $lesson->id . '">' . $lesson->name . '</option>';
//            }
            $html .= '</select>  
        </div>
    </div>';


            $html .= '<div class="form-group row  fitem">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="id_name">
                Learning Objective Name
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="text" class="form-control " name="name" id="loid_name" value="" size="" required>
        </div>
    </div>';
        }

        $html .= '<div class="form-group row  fitem   ">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="id_name">
                Icon
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="file" class="form-control " name="file" id="id_file" onchange="readURLmodule(this);">';

        $path = '';
        if (@$record->image != '') {
            $path = 'images / media / ' . $record->image;
        }

        $html .= '
        </div>
    </div>


<div class="form-group row  fitem">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="id_name">
                Upload files
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="file" class="form-control " name="files[]" id="id_files" multiple>
     
        </div>


    </div>
    

    <div class="form-group row  fitem femptylabel  " data-groupname="buttonar">
        <div class="col-md-3">
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="group">
            <div class="form-group  fitem  ">
                <span data-fieldtype="submit">
                     <button type="button" class="btn btn-primary add-module">' . get_string('save', LANGFILE) . '</button>
                </span>
            </div>
            <div class="form-group  fitem   btn-cancel">
                <span data-fieldtype="submit">
                    <button type="button" class="btn btn-secondary closemodal" data-dismiss="modal">' . get_string('close', LANGFILE) . '</button>
                </span>
            </div>
        </div>
    </div>

</form>';
        $html .= '<div class="listing">';
        $html .= $list;
        $html .= '</div>';
        return $html;
    }

    public function get_lesson_options($id)
    {
        global $DB, $CFG;
        $options = '';
        $list = '<ol>';
        $lessons = $DB->get_records('local_content_structure', array('parent' => $id, 'archtype' => 'lesson'));
        $options .= '<option value="">Select Lesson</option>';
        foreach ($lessons as $lesson) {
            $options .= '<option value="' . $lesson->id . '">' . $lesson->name . '</option>';
            $list .= '<li>'
                . '<a href="#" class="editless" image="' . $lesson->image . '" path="' . $CFG->wwwroot . '" id="' . $lesson->id . '">' . $lesson->name . '</a></li>';
        }
        $list .= '</ol>';

        return json_encode(array($options, $list));
    }

    public function get_learning_list($id)
    {
        global $DB, $CFG;
        $options = '';
        $list = '<ol>';
        $learning = $DB->get_records('local_content_structure', array('parent' => $id, 'archtype' => 'learning'));

        foreach ($learning as $le) {
            $list .= '<li>'
                . '<a href="#" class="editlo" image="' . $le->image . '" path="' . $CFG->wwwroot . '" id="' . $le->id . '">' . $le->name . '</a></li>';
        }
        $list .= '</ol>';

        return $list;
    }

    public function get_gallerybreadcrumb($id, $path)
    {
        global $DB, $CFG;

        $record = $DB->get_record('local_content_structure', array('id' => $id));

        if ($record->parent == 0) {

            return $path = '<a href="' . $CFG->wwwroot . '/local/content_structure/gallery/index.php?cid=' . $record->id . '">' . $record->name . '</a>' . $path;
        } else {
            //get parent
//            $parent = $DB->get_record('local_content_structure', array('id' => $record->id));
            $path = ' > <a href="' . $CFG->wwwroot . '/local/content_structure/gallery/index.php?cid=' . $record->id . '">' . $record->name . '</a>' . $path;
            return $this->get_gallerybreadcrumb($record->parent, $path);
        }
    }

}
