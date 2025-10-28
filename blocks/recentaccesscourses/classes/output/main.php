<?php

namespace block_recentaccesscourses\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

class main implements renderable, templatable {

    public function export_for_template(renderer_base $output) {
        global $USER, $OUTPUT, $DB, $CFG;

        $data = self::get_activity_data();

        $defaultvariables = [
            'recentaccesscoursedata' => $data,
        ];
        return $defaultvariables;
    }

    public static function get_activity_data() {
        global $CFG, $DB, $USER, $PAGE, $SESSION, $OUTPUT;

        if (!empty($SESSION->currenteditingcompany)) {
            $selectedcompany = $SESSION->currenteditingcompany;
        } else if (!empty($USER->profile->company)) {
            $usercompany = company::by_userid($USER->id);
            $selectedcompany = $usercompany->id;
        } else {
            $selectedcompany = "";
        }

        $getallaray = array();

        if ($selectedcompany) {
            $getusers = $DB->get_records_sql("SELECT ul.* FROM {user_lastaccess} ul 
                INNER JOIN {company_course} cc ON ul.courseid = cc.courseid 
                WHERE ul.userid = $USER->id AND cc.companyid = $selectedcompany 
                ORDER BY ul.timeaccess DESC LIMIT 10");
        } else {
            $getusers = $DB->get_records_sql("SELECT * FROM {user_lastaccess} 
                WHERE userid = $USER->id ORDER BY timeaccess DESC LIMIT 10");
        }

        foreach ($getusers as $keyvalue) {
            $course = $DB->get_record('course', ['id' => $keyvalue->courseid]);

            // ✅ Fetch course image (standard Moodle way)
            $courseimage = '';
            $context = \context_course::instance($course->id, IGNORE_MISSING);

            if ($context) {
                $fs = get_file_storage();
                $files = $fs->get_area_files(
                    $context->id,
                    'course',
                    'overviewfiles',
                    0,
                    'itemid, filepath, filename',
                    false
                );

                if (!empty($files)) {
                    $file = reset($files);
                    $courseimage = file_encode_url(
                        "$CFG->wwwroot/pluginfile.php",
                        '/' . $file->get_contextid() .
                        '/' . $file->get_component() .
                        '/' . $file->get_filearea() .
                        $file->get_filepath() .
                        $file->get_filename()
                    );
                }
            }

            if (empty($courseimage)) {
                // fallback image
                $courseimage = 'https://img.icons8.com/stickers/100/education.png';
            }

            // ✅ Progress calculation
            $progressdata = \core_completion\progress::get_course_progress_percentage($course, $keyvalue->userid);
            $percentage = floor($progressdata);

            // ✅ Dynamic color selection
            if ($percentage == 100) {
                $barcolor = '#16a34a'; // completed
            } else if ($percentage > 0) {
                $barcolor = '#ec9707'; // in progress
            } else {
                $barcolor = 'red'; // not started
            }

            // ✅ Updated progress bar with dynamic color
            $progress = '
            <div class="w-100 d-flex align-items-center gap-1" style="background:#e6e6f5; border-radius:8px; padding:2px;">
                <div class="progress" style="height:1.2rem; width:100%;">
                    <div class="progress-bar" role="progressbar"
                        style="background:' . $barcolor . '; width:' . $percentage . '%"
                        aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div> 
                <span class="progress-label" style="font-size:12px; color:'.$barcolor.';">' . $percentage . '%</span>
            </div>';

            $getallaray[] = [
                'lastaccessdate' => date('M d, Y', $keyvalue->timeaccess),
                'coursename' => $course->fullname,
                'progress' => $progress,
                'courseurl' => $CFG->wwwroot . "/course/view.php?id=" . $course->id,
                'courseimage' => $courseimage,
            ];
        }

        return $getallaray;
    }
}
