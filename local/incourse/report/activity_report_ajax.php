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
 * =====================
 * HELPERS
 * =====================
 */
function get_activity_grade(cm_info $cm, int $userid) {
    global $DB;

    switch ($cm->modname) {

        case 'quiz':

            // User grade
            $grade = $DB->get_field('quiz_grades', 'grade', [
                'quiz'   => $cm->instance,
                'userid' => $userid
            ]);

            // Quiz max grade
            $maxgrade = $DB->get_field('quiz', 'grade', [
                'id' => $cm->instance
            ]);

            if ($grade === false || $grade === null || $maxgrade <= 0) {
                return '-';
            }

            // ✅ If max = 10 → show percentage
            if ((int)$maxgrade === 10) {
                return round(($grade / 10) * 100);
            }

            // Otherwise show raw grade
            return round($grade, 2);


        case 'assign':

            // User grade
            $grade = $DB->get_field_sql("
                SELECT ag.grade
                FROM {assign_grades} ag
                WHERE ag.assignment = :assignid
                  AND ag.userid = :userid
                  AND ag.grade >= 0
            ", [
                'assignid' => $cm->instance,
                'userid'   => $userid
            ]);

            // Assignment max grade
            $maxgrade = $DB->get_field('assign', 'grade', [
                'id' => $cm->instance
            ]);

            if ($grade === false || $grade === null || $maxgrade <= 0) {
                return '-';
            }

            // ✅ If max = 10 → show percentage
            if ((int)$maxgrade === 10) {
                return round(($grade / 10) * 100);
            }

            // Otherwise raw grade
            return round($grade, 2);

        default:
            return '-';
    }
}


function get_activity_status(cm_info $cm, completion_info $completion, int $userid, array $logs): array {

    // ✅ Normal completion check
    if ($completion->is_enabled($cm)) {
        $cdata = $completion->get_data($cm, true, $userid);

        if (in_array($cdata->completionstate, [
            COMPLETION_COMPLETE,
            COMPLETION_COMPLETE_PASS
        ])) {
            return ['Completed',
                '<span class="text-green-600 flex items-center justify-center gap-1">
                    <span class="material-icons text-sm">check_circle</span>Completed
                 </span>'
            ];
        }
    }

    // ✅ PAGE fallback: viewed = completed
    if ($cm->modname === 'page' && !empty($logs)) {
        return ['Completed',
            '<span class="text-green-600 flex items-center justify-center gap-1">
                <span class="material-icons text-sm">check_circle</span>Completed
             </span>'
        ];
    }

    // In progress
    if (!empty($logs)) {
        return ['In Progress',
            '<span class="text-yellow-600 flex items-center justify-center gap-1">
                <span class="material-icons text-sm">schedule</span>In Progress
             </span>'
        ];
    }

    return ['Not Started',
        '<span class="text-red-500 flex items-center justify-center gap-1">
            <span class="material-icons text-sm">radio_button_unchecked</span>Not Started
         </span>'
    ];
}


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
    return $time;
}

function get_quiz_time_spent(int $quizid, int $userid): int {
    global $DB;
    $attempts = $DB->get_records('quiz_attempts', ['quiz'=>$quizid,'userid'=>$userid,'state'=>'finished']);
    $time = 0;
    foreach ($attempts as $a) {
        if ($a->timestart && $a->timefinish) {
            $time += ($a->timefinish - $a->timestart);
        }
    }
    return $time;
}

function scorm_time_to_seconds(string $time): int {
    if (strpos($time,'PT')===0) {
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/',$time,$m);
        return ($m[1]??0)*3600 + ($m[2]??0)*60 + ($m[3]??0);
    }
    if (preg_match('/(\d+):(\d+):(\d+)/',$time,$m)) {
        return $m[1]*3600 + $m[2]*60 + $m[3];
    }
    return 0;
}

function get_scorm_time_spent(int $scormid, int $userid): int {
    global $DB;
    $tracks = $DB->get_records_sql("SELECT value FROM {scorm_scoes_track} WHERE userid=:userid AND scormid=:scormid AND element IN ('cmi.core.total_time','cmi.session_time')", ['userid'=>$userid,'scormid'=>$scormid]);
    $time = 0;
    foreach ($tracks as $t) {
        $time += scorm_time_to_seconds($t->value);
    }
    return $time;
}

function get_h5p_time_spent(int $cmid,int $userid): int {
    global $DB;
    return (int)$DB->get_field_sql("SELECT SUM(duration) FROM {h5pactivity_attempts} WHERE userid=:userid AND cmid=:cmid", ['userid'=>$userid,'cmid'=>$cmid]) ?: 0;
}

function get_supervideo_time_spent(int $cmid,int $userid): int {
    global $DB;
    if (!$DB->get_manager()->table_exists('supervideo_view')) return 0;
    $sql = "SELECT SUM(session_time) FROM (SELECT MAX(currenttime) AS session_time FROM {supervideo_view} WHERE user_id=:userid AND cm_id=:cmid AND currenttime IS NOT NULL GROUP BY timecreated) t";
    return (int)$DB->get_field_sql($sql,['userid'=>$userid,'cmid'=>$cmid]) ?: 0;
}

function get_attendance_time_spent(int $attendanceid,int $userid): array {
    global $DB;
    $status = 'Not Attended';
    $timespent = 0;
    $logs = $DB->get_records_sql("
        SELECT al.statusid, s.duration
        FROM {attendance_log} al
        JOIN {attendance_sessions} s ON s.id = al.sessionid
        WHERE al.studentid = :userid
          AND s.attendanceid = :attid
    ", ['userid'=>$userid,'attid'=>$attendanceid]);

    $present_statuses = [1,3]; // P + Late
    foreach ($logs as $log) {
        if (in_array((int)$log->statusid,$present_statuses)) {
            $status = 'Attended';
            $timespent += (int)$log->duration;
        }
    }
    return [$status,$timespent];
}

function normalize_module_name(string $modname): string {
    $map = ['quiz'=>'Quiz','assign'=>'Assignment','scorm'=>'SCORM','forum'=>'Forum','page'=>'Page','url'=>'URL','h5pactivity'=>'H5P','supervideo'=>'Video','attendance'=>'Attendance','page'=>'PPT'];
    return $map[$modname] ?? ucfirst($modname);
}

/**
 * COURSE / SECTION
 */
if($sectionid>0){
    $section = $DB->get_record('course_sections',['id'=>$sectionid],'*',MUST_EXIST);
    $course = get_course($section->course);
    $format = course_get_format($course);
    $sectionname = $format->get_section_name($section);
    $sectionnum = $section->section;
}else{
    $courseid = required_param('courseid', PARAM_INT);
    $course = get_course($courseid);
    $sectionname = "All Sections";
    $sectionnum = null;
}

$completion = new completion_info($course);
$modinfo = get_fast_modinfo($course);

/**
 * FETCH ALL LOGS
 */
$alllogs = $DB->get_records_sql("
    SELECT contextinstanceid AS cmid,timecreated
    FROM {logstore_standard_log}
    WHERE userid=:userid AND courseid=:courseid AND contextlevel=:contextlevel AND action IN ('viewed','submitted','attempted','answered','completed')
    ORDER BY timecreated ASC
", ['userid'=>$userid,'courseid'=>$course->id,'contextlevel'=>CONTEXT_MODULE]);

$logsbycm = [];
foreach($alllogs as $log){
    $logsbycm[$log->cmid][] = $log;
}

/**
 * =====================
 * PROCESS ACTIVITIES
 * =====================
 */
$activities = [];
$index = 1;

foreach($modinfo->get_cms() as $cm){
    if($sectionnum!==null && $cm->sectionnum != $sectionnum) continue;
    if(!$cm->uservisible || $cm->deletioninprogress) continue;

    $logs = $logsbycm[$cm->id] ?? [];
    $timespent = 0;
    $statustext = 'Not Started';
    $statushtml = '<span class="text-red-500 flex items-center justify-center gap-1"><span class="material-icons text-sm">radio_button_unchecked</span><span class="text-red-500">Not Started</span>';
    $grade = get_activity_grade($cm, $userid);
    try{
        switch($cm->modname){
            case 'attendance':
                [$statustext,$timespent] = get_attendance_time_spent($cm->instance,$userid);
                $statushtml = $statustext==='Attended'
                    ? '<span class="text-green-600 flex items-center justify-center gap-1"><span class="material-icons text-sm">check_circle</span>Attended</span>'
                    : '<span class="text-red-500 flex items-center justify-center gap-1"><span class="material-icons text-sm">radio_button_unchecked</span>Not Attended</span>';
                break;

            case 'quiz':
                $timespent = get_quiz_time_spent($cm->instance,$userid);
                [$statustext,$statushtml] = get_activity_status($cm,$completion,$userid,$logs);
                break;

            case 'scorm':
                $timespent = get_scorm_time_spent($cm->instance,$userid);
                [$statustext,$statushtml] = get_activity_status($cm,$completion,$userid,$logs);
                break;

            case 'h5pactivity':
                $timespent = get_h5p_time_spent($cm->id,$userid);
                [$statustext,$statushtml] = get_activity_status($cm,$completion,$userid,$logs);
                break;

            case 'supervideo':
                $timespent = get_supervideo_time_spent($cm->id,$userid);
                [$statustext,$statushtml] = get_activity_status($cm,$completion,$userid,$logs);
                break;
                case 'page':
    $timespent = calculate_activity_time($logs);
    [$statustext, $statushtml] = get_activity_status($cm, $completion, $userid, $logs);
    break;

        }
    }catch(Exception $e){
        $timespent = 0;
    }

    // fallback for modules except supervideo & attendance
    if($timespent<=0 && !in_array($cm->modname,['supervideo','attendance'])){
        $timespent = calculate_activity_time($logs);
    }

    $activities[] = [
        'srno'=>$index++,
        'activityname'=>format_string($cm->name),
        'moduletype'=>normalize_module_name($cm->modname),
        'status'=>$statustext,
        'status_text'=>$statustext,
        'status_html'=>$statushtml,
         'grade'        => $grade,
        'timespent'=>$timespent
    ];
}

/**
 * JSON OUTPUT
 */
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'meta'=>['username'=>fullname($user),'course'=>$course->fullname,'section'=>$sectionname],
    'activities'=>$activities
]);
exit;
