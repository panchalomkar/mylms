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
 * Library functions.
 *
 * @package    report_custom_report
 * @author     Uvais
 * @copyright  2012 Isuru Madushanka Weerarathna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $CFG;
/**
 * ======== CONSTANTS ==========================================
 */
define('BASEURL', '/local/content_structure');
define('LANGFILE', 'local_content_structure');
define('LOCALFILEPATH', '/local/content_structure/images');
define('DATAFILEPATH', $CFG->dataroot . '/repository/content');

function get_programs()
{
    global $DB;

    return $DB->get_records('local_content_structure', array('parent' => 0));
}

//get file type 
function get_file_display_metadata($file, $CFG)
{
    $isfolder = (isset($file->parent) && $file->parent === '0');
    $name = pathinfo($file->name, PATHINFO_FILENAME);
    $fileurl = '';
    $type = 'file';
    $icon = 'file';
    $ext = 'file';
    $viewtext = 'View';
    $viewicon = 'fa fa-eye mr-2';

    if ($isfolder) {
        $fileurl = $CFG->wwwroot . '/local/content_structure/gallery/index.php?cid=' . $file->itemid;
        $type = 'folder';
        $icon = 'folder';
        $ext = 'folder';
        $viewtext = 'Open';
        $viewicon = 'fa fa-folder-open mr-2';
    } else {
        if (!empty($file->link)) {
            $fileurl = $file->link;
            if (
                strpos($fileurl, 'youtube.com') !== false ||
                strpos($fileurl, 'youtu.be') !== false ||
                strpos($fileurl, 'drive.google.com') !== false ||
                preg_match('/\.(mp4|mov|avi)$/i', $fileurl)
            ) {
                $type = 'video_link';
                $icon = 'url';
                $ext = 'url';
                $viewtext = 'Open';
                $viewicon = 'fa fa-external-link mr-2';
            } else {
                $type = 'url';
                $icon = 'url';
                $ext = 'url';
                $viewtext = 'Open';
                $viewicon = 'fa fa-external-link mr-2';
            }
        } else {
            $fileurl = $CFG->wwwroot . '/local/content_structure/images/media/' . $file->image;
            $ext = strtolower(pathinfo($file->image, PATHINFO_EXTENSION));
            $type = $ext;
            $icon = $ext;

            if ($ext === 'pdf') {
                $viewtext = 'View';
                $viewicon = 'fa fa-file-pdf-o mr-2';
            } elseif (in_array($ext, ['doc', 'docx'])) {
                $viewtext = 'View';
                $viewicon = 'fa fa-file-word-o mr-2';
            } elseif (in_array($ext, ['ppt', 'pptx'])) {
                $viewtext = 'View';
                $viewicon = 'fa fa-file-powerpoint-o mr-2';
            } elseif (in_array($ext, ['mp4', 'mov', 'avi'])) {
                $type = 'video';
                $viewtext = 'Play';
                $viewicon = 'fa fa-file-video-o mr-2';
            }
        }
    }

    return [
        'fileurl' => $fileurl,
        'type' => $type,
        'icon' => $icon,
        'ext' => $ext,
        'viewtext' => $viewtext,
        'viewicon' => $viewicon
    ];
}



function create_program($data, $file)
{
    global $DB, $USER;

    $data->parent = 0;
    $data->timecreated = time();

    $path = DATAFILEPATH;
    if (isset($data->id) && $data->id != '' && $data->id > 0) {
        $record = $DB->get_record('local_content_structure', array('id' => $data->id));
        if ($filename = upload_file($file, $data)) {
            $data->image = $filename;
            //delete old file
            if ($record->image) {
                @unlink(LOCALFILEPATH . '/media/' . $record->image);
            }
        }

        if ($record->name != $data->name) {
            if (!file_exists($path . '/' . $record->name)) {

                mkdir($path . '/' . $data->name, 0777, true);
            } else {
                rename($path . '/' . $record->name, $path . '/' . $data->name);
            }
        }
        $DB->update_record('local_content_structure', $data);
    } else {
        // Check if the shortname already exists.
        // if (!empty($data->name)) {
        //     if ($DB->record_exists('local_content_structure', array('name' => $data->name, 'parent' => 0))) {
        //         throw new moodle_exception('nametaken', LANGFILE, 'index.php', $data->name);
        //     }
        // }


        if (!file_exists($path . '/' . $data->name)) {

            mkdir($path . '/' . $data->name, 0777, true);
        }
        $data->image = upload_file($file, $data);
        $id = $DB->insert_record('local_content_structure', $data);
        upload_files($file, $data, $id);
    }
}

function create_course_content($data, $file)
{
    global $DB, $USER;
    // Set parent depending on archtype
    if ($data->archtype == 'module') {
        $data->parent = $data->course;
    } else if ($data->archtype == 'lesson') {
        $data->parent = $data->module;
    } else if ($data->archtype == 'learning') {
        $data->parent = $data->lesson;
    } else {
        $data->parent = $data->parent;
    }

    $data->timecreated = time();
    $path = DATAFILEPATH . '/' . get_folder_path($data, '');

    // Prepare link column for insert/update
    // Make sure $data->link is set if link tab used, otherwise null or empty string
    if (!isset($data->link)) {
        $data->link = null;  // or ''
    }

    if (isset($data->id) && $data->id != '' && $data->id > 0) {
        // Existing record update
        $record = $DB->get_record('local_content_structure', array('id' => $data->id));

        // Handle uploaded file
        if ($filename = upload_file($file, $data)) {
            $data->image = $filename;
            // Delete old file if exists
            if (!empty($record->image)) {
                @unlink(LOCALFILEPATH . '/media/' . $record->image);
            }
        }

        // Rename folder if name changed
        if ($record->name != $data->name) {
            if (!file_exists($path . '/' . $record->name)) {
                mkdir($path . '/' . $data->name, 0777, true);
            } else {
                rename($path . '/' . $record->name, $path . '/' . $data->name);
            }
        }

        upload_files($file, $data, $record->id);

        // Update record with link included
        $DB->update_record('local_content_structure', $data);

    } else {
    
        $data->image = upload_file($file, $data);

        $filename = time() . str_replace(' ', '', basename($file['file']["name"]));
         $id = $DB->insert_record('local_content_structure', $data);
         $obj = new stdClass();
         $obj->contentid = $id;
         $obj->timecreated = time();
         $obj->filename = $filename;
         $obj->path = str_replace(DATAFILEPATH, '', $path . '/' . $filename);
         $DB->insert_record('content_structure_files', $obj);
      //  upload_files($file, $data, $id);
    }

    return '1';
}

/*function upload_file($file, $data)
{
    global $DB, $CFG;

    if (isset($file['file'])) {
        if ($file['file']['error'] == 0 && $file['file']['size'] > 0) {
            $filename1 = time() . str_replace(' ', '', basename($file['file']["name"]));
            if (move_uploaded_file($file['file']["tmp_name"], 'images/media/' . $filename1)) {
            }
        }
    }
    return false;
}*/
// function upload_file($file, $data)
// {
//     global $DB, $CFG;

//     if (isset($file['file'])) {
//         if ($file['file']['error'] == 0 && $file['file']['size'] > 0) {
//             $filename = time() . str_replace(' ', '', basename($file['file']["name"]));
//             $mediaPath = 'images/media/' . $filename;
//             $dataPath = DATAFILEPATH . '/' . get_folder_path($data, '') . '/' . $data->name;

//             // Ensure target data folder exists
//             if (!file_exists($dataPath)) {
//                 mkdir($dataPath, 0777, true);
//             }

//             // Upload to both paths
//             if (move_uploaded_file($file['file']["tmp_name"], $mediaPath)) {
//                 // Copy to DATAFILEPATH as well
//                 copy($mediaPath, $dataPath . '/' . $filename);
//                 return $filename;
//             }
//         }
//     }
//     return false;
// }
function upload_file($file, $data)
{
    global $DB, $CFG;

    if (isset($file['file'])) {
        if ($file['file']['error'] == 0 && $file['file']['size'] > 0) {
            $filename = time() . str_replace(' ', '', basename($file['file']["name"]));
            $mediaPath = 'images/media/' . $filename;

            // This path now goes only to the parent folder (not into $data->name)
            $dataPath = DATAFILEPATH . '/' . get_folder_path($data, '');

            // Ensure target folder exists
            if (!file_exists($dataPath)) {
                mkdir($dataPath, 0777, true);
            }

            // Upload to both paths
            if (move_uploaded_file($file['file']["tmp_name"], $mediaPath)) {
                // Copy to DATAFILEPATH as well (just the parent folder)
                copy($mediaPath, $dataPath . '/' . $filename);
                return $filename;
            }
        }
    }
    return false;
}


// function upload_files($file, $data, $id)
// {
//     global $DB, $CFG;
//     //    $parent = $DB->get_record('local_content_structure', array('id' => $data->parent));
//     // get folder path
//     $path = DATAFILEPATH . '/' . get_folder_path($data, '');

//     if (!file_exists($path . '/' . $data->name)) {

//         mkdir($path . '/' . $data->name, 0777, true);
//     }
//     if (isset($file['files'])) {
//         for ($i = 0; $i < count($file['files']['name']); $i++) {
//             if ($file['files']['error'][$i] == 0 && $file['files']['size'][$i] > 0) {
//                 $filename = time() . str_replace(' ', '', basename($file['files']["name"][$i]));
//                 if (move_uploaded_file($file['files']["tmp_name"][$i], $path . '/' . $data->name . '/' . $filename)) {
//                     $obj = new stdClass();
//                     $obj->contentid = $id;
//                     $obj->timecreated = time();
//                     $obj->filename = $filename;
//                     $obj->path = str_replace(DATAFILEPATH, '', $path . '/' . $data->name . '/' . $filename);
//                     $DB->insert_record('content_structure_files', $obj);
//                 }
//             }
//         }
//     }
// }
function upload_files($file, $data, $id)
{
    global $DB, $CFG;

    // Get parent folder path
    $path = DATAFILEPATH . '/' . get_folder_path($data, '');

    // Make sure the parent folder exists
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
    if (isset($file['file'])) {
      //  for ($i = 0; $i < count($file['files']['name']); $i++) {
            if ($file['file']['error'][$i] == 0 && $file['file']['size'][$i] > 0) {
                $filename = time() . str_replace(' ', '', basename($file['file']["name"]));

                // Upload file directly into parent folder (no subfolder)
                if (move_uploaded_file($file['file']["tmp_name"][$i], $path . '/' . $filename)) {
                    $obj = new stdClass();
                    $obj->contentid = $id;
                    $obj->timecreated = time();
                    $obj->filename = $filename;
                    $obj->path = str_replace(DATAFILEPATH, '', $path . '/' . $filename);
                    $DB->insert_record('content_structure_files', $obj);
                }
            }
      //  }
    }
}


function get_folder_path($structure, $path)
{
    global $DB;

    if ($structure->parent == 0) {

        return $path;
    } else {
        //get parent
        $parent = $DB->get_record('local_content_structure', array('id' => $structure->parent));
        $path = $parent->name . '/' . $path;
        return rtrim(get_folder_path($parent, $path), '/');
    }
}

function delete_program($id)
{
    global $DB, $USER;
    // Check if the shortname already exists.
    $DB->delete_records('local_content_structure', array('id' => $id));
}

function delete_course_content($id)
{
    global $DB, $USER;
    // Check if the shortname already exists.
    $DB->delete_records('local_content_structure', array('id' => $id));
}

function get_course_content($parent, $archtype)
{
    global $DB;

    return $DB->get_records('local_content_structure', array('parent' => $parent, 'archtype' => $archtype));
}

/**
 * Function to save custom report columns.
 *
 * @param object $data report column form data.
 * @return void.
 */
function save_column($data)
{
    global $DB, $USER;
    // Delete column already exists.
    $DB->delete_records('custom_report_columns', array('reportid' => $data->id));

    $data->userid = $USER->id;
    $data->timecreated = time();
    foreach ($data as $key => $value) {
        if ($value == 1 && $key != 'id') {
            $object = new stdClass();
            $object->reportid = $data->id;
            $object->column_name = $key;
            $object->timecreated = time();
            $DB->insert_record('custom_report_columns', $object);
        }
    }
}

/**
 * Function to get course module name.
 *
 * @param int $cmid course module id.
 * @return String module item name.
 */
function get_module_item_name($cmid)
{
    global $DB;
    $cm = $DB->get_record('course_modules', array('id' => $cmid));
    $module = $DB->get_record('modules', array('id' => $cm->module));
    $activity = $DB->get_record($module->name, array('id' => $cm->instance));
    return $activity->name;
}

/**
 * Function to get module name.
 *
 * @param int $cmid course module id.
 * @return String module name.
 */
function get_module_name($cmid)
{
    global $DB;
    $cm = $DB->get_record('course_modules', array('id' => $cmid));
    $module = $DB->get_record('modules', array('id' => $cm->module));
    return $module;
}

/**
 * Function to get course by course module id.
 *
 * @param int $cmid course module id.
 * @return Object course object.
 */
function get_course_from_cmid($cmid)
{
    global $DB;
    $SQL = "SELECT c.* FROM {course_modules} cm JOIN {course} c ON c.id = cm.course
            WHERE cm.id = $cmid";
    return $DB->get_record_sql($SQL);
}

/**
 * Function to get user grade on course graded activity.
 *
 * @param int $userid user id.
 * @param int $cmid course module id.
 * @return String user grade for a particular course module.
 */
function get_user_module_grade($userid, $cmid)
{
    global $DB, $CFG;

    require_once $CFG->dirroot . '/lib/gradelib.php';
    $course = get_course_from_cmid($cmid);

    $cm = $DB->get_record('course_modules', array('id' => $cmid));
    $modname = get_module_name($cmid);
    $grade = grade_get_grades($course->id, 'mod', $modname->name, $cm->instance, $userid);

    return $grade->items[0]->grades[$userid]->str_long_grade;
}

/**
 * Function to get user course grade.
 *
 * @param int $courseid course  id.
 * @param int $userid user id.
 * @return String user course grade.
 */
function get_course_grade($courseid, $userid)
{
    global $DB, $CFG;

    require_once $CFG->dirroot . '/grade/querylib.php';

    $grade = grade_get_course_grade($userid, $courseid);
    return $grade->str_long_grade;
}

/**
 * Function to get activity name array which have completion criteria set.
 *
 * @param int $courseid course  id.
 * @return Array course modules name.
 */
function get_completion_mods($courseid)
{
    global $DB;
    $array = array();
    $records = $DB->get_records_select('course_modules', 'course = ? AND completion IN(1,2)', array($courseid));

    foreach ($records as $record) {
        $array[] = $record->id;
    }
    return $array;
}

function get_icon($filename)
{
    global $CFG;
    $arr = explode('.', $filename);
    $ext = end($arr);

    if ($ext == 'mp3') {
        $image = $CFG->wwwroot . '/local/content_structure/images/audio.svg';
    } else if ($ext == 'mp4' || $ext == 'mkv' || $ext == 'mov' || $ext == 'wmv' || $ext == 'avi') {
        $image = $CFG->wwwroot . '/local/content_structure/images/video.svg';
    } else if ($ext == 'xls' || $ext == 'xlsx') {
        $image = $CFG->wwwroot . '/local/content_structure/images/excel.svg';
    } else if ($ext == 'pdf') {
        $image = $CFG->wwwroot . '/local/content_structure/images/pdf.svg';
    } else if ($ext == 'ppt') {
        $image = $CFG->wwwroot . '/local/content_structure/images/ppt.svg';
    } else if ($ext == 'doc' || $ext == 'docx') {
        $image = $CFG->wwwroot . '/local/content_structure/images/word.svg';
    } else if ($ext == 'zip') {
        $image = $CFG->wwwroot . '/local/content_structure/images/compressed.svg';
    } else if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'svg') {
        $image = $CFG->wwwroot . '/local/content_structure/images/image.svg';
    } else if ($ext == 'h5p') {
        $image = $CFG->wwwroot . '/local/content_structure/images/hvp.svg';
    } else {
        $image = $CFG->wwwroot . '/local/content_structure/images/word.svg';
    }
    return $image;
}

function get_file_type($filename)
{
    global $CFG;
    $arr = explode('.', $filename);
    $ext = end($arr);

    if ($ext == 'mp3') {
        $type = 'Audio';
    } else if ($ext == 'mp4') {
        $type = 'Video';
    } else if ($ext == 'xls') {
        $type = 'Excel';
    } else if ($ext == 'pdf') {
        $type = 'PDF';
    } else if ($ext == 'ppt') {
        $type = 'PPT';
    } else if ($ext == 'word') {
        $type = 'Word';
    } else if ($ext == 'zip') {
        $type = 'Compressed';
    } else if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'svg') {
        $type = 'Image';
    }
    return $type;
}

function truncatel($string, $length = 20, $append = "...")
{
    if (strlen($string) <= intval($length)) {
        return $string;
    }

    return substr($string, 0, $length) . $append;
}

function delete_content($id, $type)
{
    global $DB, $CFG;

    $record = $DB->get_record('local_content_structure', array('id' => $id));
    @unlink($CFG->dirroot . '/local/content_structure/images/media/' . $record->image);

    $DB->delete_records('local_content_structure', array('id' => $id));

    //get child ids
    $sql = "SELECT GROUP_CONCAT(Level SEPARATOR ',') AS idss FROM (
                    SELECT @Ids := (
                        SELECT GROUP_CONCAT(`id` SEPARATOR ',')
                        FROM `mdl_local_content_structure`
                        WHERE FIND_IN_SET(`parent`, @Ids)
                    ) Level
                    FROM {local_content_structure}
                    JOIN (SELECT @Ids := $id) temp1
                 ) temp2";
    $result = $DB->get_record_sql($sql);
    $child = $result->idss;

    $del = "DELETE FROM {local_content_structure} WHERE FIND_IN_SET(id, '$child')";
    $DB->execute($del);

    //delete folder/file
    $path = get_folder_path($record, '');

    @rrmdir(DATAFILEPATH . '/' . $path . '/' . $record->name);

    $fsql = "SELECT * FROM {content_structure_files} WHERE FIND_IN_SET(contentid, '$child')";

    $files = $DB->get_records_sql($fsql);
    foreach ($files as $file) {
        $DB->delete_records('content_structure_files', array('id' => $file->id));
        rrmdir(DATAFILEPATH . $file->path);
    }
    $redirecturl = $CFG->wwwroot . "/local/content_structure/index.php";
    $redirecturl;
}

function rrmdir($dir)
{
    chmod($dir, 0777);
    if (is_dir($dir)) {
        $objects = scandir($dir);

        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (filetype($dir . '/' . $object) == 'dir') {
                    rrmdir($dir . '/' . $object);
                } else {
                    unlink($dir . '/' . $object);
                }
            }
        }

        reset($objects);
        rmdir($dir);
    }
}
