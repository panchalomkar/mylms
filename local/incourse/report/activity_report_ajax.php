<?php
require_once("../../../config.php");
require_login();


global $DB, $CFG;

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * PARAMS
 */
$userid    = required_param('userid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);

$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

/**
 * COURSE / SECTION
 */
if ($sectionid > 0) {
    $section = $DB->get_record('course_sections', ['id' => $sectionid], '*', MUST_EXIST);
    $course  = get_course($section->course);

    $format = course_get_format($course);
    $sectionname = $format->get_section_name($section);

    $sectionnum = $section->section;
} else {
    $courseid = required_param('courseid', PARAM_INT);
    $course   = get_course($courseid);

    $sectionnum  = null;
    $sectionname = "All Sections";
}

$completion = new completion_info($course);
$modinfo    = get_fast_modinfo($course);

/**
 * FETCH ALL LOGS (FAST)
 */
$alllogs = $DB->get_records_sql("
    SELECT contextinstanceid AS cmid, timecreated
    FROM {logstore_standard_log}
    WHERE userid = :userid
      AND courseid = :courseid
      AND contextlevel = :contextlevel
      AND action IN ('viewed','submitted','attempted','answered','completed')
    ORDER BY timecreated ASC
", [
    'userid' => $userid,
    'courseid' => $course->id,
    'contextlevel' => CONTEXT_MODULE
]);


$logsbycm = [];
foreach ($alllogs as $log) {
    $logsbycm[$log->cmid][] = $log;
}

/**
 * =====================
 * HELPERS (UNCHANGED)
 * =====================
 */
function calculate_activity_time(array $logs, int $timeout = 1800): int {
    if (empty($logs)) return 0;

    $time = 0;
    $prev = $logs[0]->timecreated;

    for ($i = 1; $i < count($logs); $i++) {
        $gap = $logs[$i]->timecreated - $prev;
        if ($gap > 0 && $gap <= $timeout) {
            $time += $gap;
        }
        $prev = $logs[$i]->timecreated;
    }

    // if ($time === 0) {
    //     $time = "-"; // minimum 2 minutes
    // }

    return $time;
}

/**
 * =====================
 * REAL QUIZ TIME
 * =====================
 */
function get_quiz_time_spent(int $quizid, int $userid): int {
    global $DB;

    $attempts = $DB->get_records('quiz_attempts', [
        'quiz' => $quizid,
        'userid' => $userid,
        'state' => 'finished'
    ]);

    $time = 0;
    foreach ($attempts as $a) {
        if ($a->timestart && $a->timefinish) {
            $time += ($a->timefinish - $a->timestart);
        }
    }
    return $time;
}

/**
 * =====================
 * REAL SCORM TIME
 * =====================
 */
function scorm_time_to_seconds(string $time): int {
    if (strpos($time, 'PT') === 0) {
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $time, $m);
        return ($m[1] ?? 0) * 3600 + ($m[2] ?? 0) * 60 + ($m[3] ?? 0);
    }
    if (preg_match('/(\d+):(\d+):(\d+)/', $time, $m)) {
        return $m[1] * 3600 + $m[2] * 60 + $m[3];
    }
    return 0;
}

function get_scorm_time_spent(int $scormid, int $userid): int {
    global $DB;

    $tracks = $DB->get_records_sql("
        SELECT value
        FROM {scorm_scoes_track}
        WHERE userid = :userid
          AND scormid = :scormid
          AND element IN ('cmi.core.total_time','cmi.session_time')
    ", [
        'userid' => $userid,
        'scormid' => $scormid
    ]);

    $time = 0;
    foreach ($tracks as $t) {
        $time += scorm_time_to_seconds($t->value);
    }
    return $time;
}

/**
 * =====================
 * REAL H5P TIME
 * =====================
 */
function get_h5p_time_spent(int $cmid, int $userid): int {
    global $DB;

    return (int)$DB->get_field_sql("
        SELECT SUM(duration)
        FROM {h5pactivity_attempts}
        WHERE userid = :userid
          AND cmid = :cmid
    ", [
        'userid' => $userid,
        'cmid' => $cmid
    ]) ?: 0;
}

function normalize_module_name(string $modname): string {
    $map = [
        'quiz' => 'Quiz',
        'assign' => 'Assignment',
        'scorm' => 'SCORM',
        'forum' => 'Forum',
        'page' => 'Page',
        'url' => 'URL',
        'h5pactivity' => 'H5P'
    ];
    return $map[$modname] ?? ucfirst($modname);
}

function get_activity_status(cm_info $cm, completion_info $completion, int $userid, array $logs): array {

    if ($completion->is_enabled($cm)) {
        $cdata = $completion->get_data($cm, true, $userid);

        if (in_array($cdata->completionstate, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS])) {
            return [
                'Completed',
                '<span class="text-green-600 flex items-center justify-center gap-1">
                    <span class="material-icons text-sm">check_circle</span>
                    Completed
                 </span>'
            ];
        }
    }

    if (!empty($logs)) {
        return [
            'In Progress',
            '<span class="text-yellow-600 flex items-center justify-center gap-1">
                <span class="material-icons text-sm">schedule</span>
                In Progress
             </span>'
        ];
    }

    return [
        'Not Started',
        '<span class="text-red-500 flex items-center justify-center gap-1">
            <span class="material-icons text-sm">radio_button_unchecked</span>
            Not Started
         </span>'
    ];
}

/**
 * =====================
 * DATA (LOGIC SAME)
 * =====================
 */
$activities = [];
$index = 1;

foreach ($modinfo->get_cms() as $cm) {

    if ($sectionnum !== null && $cm->sectionnum != $sectionnum) continue;
    if (!$cm->uservisible || $cm->deletioninprogress) continue;

    $logs = $logsbycm[$cm->id] ?? [];
    [$statustext, $statushtml] = get_activity_status($cm, $completion, $userid, $logs);

 $timespent = 0;

try {

    switch ($cm->modname) {

        case 'quiz':
            if ($DB->record_exists('quiz_attempts', [
                'quiz' => $cm->instance,
                'userid' => $userid
            ])) {
                $timespent = get_quiz_time_spent($cm->instance, $userid);
            }
            break;

        case 'scorm':
            if ($DB->record_exists('scorm_scoes_track', [
                'scormid' => $cm->instance,
                'userid' => $userid
            ])) {
                $timespent = get_scorm_time_spent($cm->instance, $userid);
            }
            break;

        case 'h5pactivity':
            if ($DB->get_manager()->table_exists('h5pactivity_attempts')) {
                $timespent = get_h5p_time_spent($cm->id, $userid);
            }
            break;
    }

} catch (Exception $e) {
    // fallback
    $timespent = 0;
}

// 🔁 fallback to logs if module gave nothing
if ($timespent <= 0) {
    $timespent = calculate_activity_time($logs);
}

    $activities[] = [
        'srno' => $index++,
        'activityname' => format_string($cm->name),
        'moduletype' => normalize_module_name($cm->modname),
        'status' => $statustext,
        'status_text' => $statustext,
        'status_html' => $statushtml,
        'timespent' => $timespent
    ];
}

/**
 * JSON OUTPUT
 */
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'meta' => [
        'username' => fullname($user),
        'course' => $course->fullname,
        'section' => $sectionname
    ],
    'activities' => $activities
]);
exit;
