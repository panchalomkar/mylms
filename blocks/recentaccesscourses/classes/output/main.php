<?php

namespace block_recentaccesscourses\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

class main implements renderable, templatable {

    public function export_for_template(renderer_base $output) {
        global $USER;

        $data = self::get_activity_data();

        return [
            'recentaccesscoursedata' => $data
        ];
    }

    public static function get_activity_data() {
        global $CFG, $DB, $USER, $SESSION;

        // -----------------------------------------------------
        // ✅ FORCE MOODLE TO UPDATE USER LAST ACCESS IMMEDIATELY
        // -----------------------------------------------------
        require_once($CFG->dirroot . '/course/lib.php');
        user_accesstime_log($USER->id);     // forces accurate user_lastaccess updates
        $DB->reset_caches();
        \core\session\manager::write_close();
        // -----------------------------------------------------

        // Company-based filtering handling
        if (!empty($SESSION->currenteditingcompany)) {
            $selectedcompany = $SESSION->currenteditingcompany;
        } else if (!empty($USER->profile->company)) {
            $usercompany = company::by_userid($USER->id);
            $selectedcompany = $usercompany->id ?? "";
        } else {
            $selectedcompany = "";
        }

        $getallaray = [];

        // SQL retrieval
      // -----------------------------------------------------
// SHOW ONLY ENROLLED COURSES
// -----------------------------------------------------

if ($selectedcompany) {

    // Company + Enrolled + Last Access
    $sql = "SELECT ul.*
            FROM {user_lastaccess} ul
            INNER JOIN {company_course} cc ON ul.courseid = cc.courseid
            INNER JOIN {enrol} e ON e.courseid = ul.courseid
            INNER JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = ul.userid
            WHERE ul.userid = :userid AND cc.companyid = :companyid
            ORDER BY ul.timeaccess DESC
            LIMIT 10";

    $params = [
        'userid' => $USER->id,
        'companyid' => $selectedcompany
    ];

} else {

    // Enrolled + Last Access
    $sql = "SELECT ul.*
            FROM {user_lastaccess} ul
            INNER JOIN {enrol} e ON e.courseid = ul.courseid
            INNER JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = ul.userid
            WHERE ul.userid = :userid
            ORDER BY ul.timeaccess DESC
            LIMIT 10";

    $params = [
        'userid' => $USER->id
    ];
}

$getusers = $DB->get_records_sql($sql, $params);


        foreach ($getusers as $keyvalue) {

            // Fetch course
            $course = $DB->get_record('course', ['id' => $keyvalue->courseid]);
            if (!$course) {
                continue;
            }

            // -----------------------------------------------------
            // ✅ Get Course Image (Moodle standard method)
            // -----------------------------------------------------
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

            // Fallback image
            if (empty($courseimage)) {
                $courseimage = 'https://img.icons8.com/stickers/100/education.png';
            }

            // -----------------------------------------------------
            // ✅ Course Progress
            // -----------------------------------------------------
            $progressdata = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
            $percentage = floor($progressdata);

            // Progress color logic
           if ($percentage > 75) {
    $barcolor = '#16a34a';   // 76–100
} elseif ($percentage > 50) {
    $barcolor = '#ec9707';   // 51–75
} else {
    $barcolor = 'red';       // 0–50
}
// -----------------------------------------------------
// ✅ Determine Course URL (Admin sees normal course view)
// -----------------------------------------------------
if (is_siteadmin($USER)) {
    // Admin → normal course page.
    $courseurl = $CFG->wwwroot . "/course/view.php?id=" . $course->id;
} else {
    // Student → redirect to custom incourse page.
    $courseurl = $CFG->wwwroot . "/local/incourse/index.php?id=" . $course->id;
}

            // HTML progress bar
            $progress = '
            <div class="w-100 d-flex align-items-center gap-1">
                <div class="progress" style="    min-width: 100%;
    height: 0.75rem;
    border-radius: 10px !important;">
                    <div class="progress-bar" role="progressbar"
                        style="background:' . $barcolor . '; width:' . $percentage . '%"
                        aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div> 
                <span class="progress-label" style="font-size:12px; color:' . $barcolor . ';">' . $percentage . '%</span>
            </div>';

            // Prepare final array item
          $getallaray[] = [
    'lastaccessdate' => date('M d, Y', $keyvalue->timeaccess),
    'coursename'     => $course->fullname,
    'progress'       => $progress,
    'courseurl'      => $courseurl,
    'courseimage'    => $courseimage,
                 ];

        }

        return $getallaray;
    }
}
