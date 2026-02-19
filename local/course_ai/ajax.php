<?php

require_once('../../config.php');

$action = optional_param('action', '', PARAM_TEXT);

switch ($action) {
    case 'fetch_courses':
        local_fetch_courses_ajax_handler();
        break;

    case 'fetch_sections':
        local_fetch_sections_ajax_handler();
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

// Fetch just course id and name (no sections)
function local_fetch_courses_ajax_handler()
{
    global $DB, $USER;

    // First, get IDs of courses where the is_ai_course custom field is set to 1
    $aicourseids = $DB->get_fieldset_sql("
        SELECT d.instanceid
        FROM {customfield_data} d
        JOIN {customfield_field} f ON f.id = d.fieldid
        WHERE f.shortname = 'is_ai_course' AND d.intvalue = 1
    ");

    if (empty($aicourseids)) {
        echo json_encode([]);
        return;
    }

    // Fetch only AI courses from the course table
    list($in_sql, $params) = $DB->get_in_or_equal($aicourseids, SQL_PARAMS_QM);
    // $courses = $DB->get_records_select('course', "id $in_sql", $params, '', 'id, fullname, summary');
$courses = $DB->get_records_select(
    'course',
    "id $in_sql",
    $params,
    'id DESC', // Sorting: newest courses first
    'id, fullname, summary'
);

    if (!$courses) {
        echo json_encode([]);
        return;
    }

    $course_list = [];

    foreach ($courses as $course) {
        $context = context_course::instance($course->id);

        // Skip courses the user isn't enrolled in
        if (!is_enrolled($context, $USER)) {
            continue;
        }

        // Get course image
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'itemid, filepath, filename', false);

        $courseimageurl = '';
        foreach ($files as $file) {
            if (!$file->is_directory()) {
                $courseimageurl = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                )->out();
                break;
            }
        }

        // Fallback image
        if (empty($courseimageurl)) {
            $courseimageurl = 'https://cdn.disco.co/courses/covers/2025/02/18/99d5af99-cf57-4ea6-9484-8fe3d8b8c4b2.png';
        }

        $courseimageurl = str_replace('/0/', '/', $courseimageurl);

        $course_list[] = [
            'id' => $course->id,
            'fullname' => $course->fullname,
            'summary' => format_text($course->summary, FORMAT_HTML),
            'image' => $courseimageurl
        ];
    }

    echo json_encode($course_list);
    exit;
}




// Fetch sections of a specific course
function local_fetch_sections_ajax_handler()
{
    global $DB;
    $courseid = required_param('courseid', PARAM_INT);
    $sections = $DB->get_records('course_sections', ['course' => $courseid]);

    $section_list = [];
    foreach ($sections as $section) {
        $section_list[] = [
            'section_name' => format_string($section->name ?? ''),
            'section_summary' => format_text($section->summary ?? '', FORMAT_HTML)
        ];
    }

    echo json_encode($section_list);
}

