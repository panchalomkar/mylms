<?php
require_once("../../../config.php");
require_login();

global $DB, $CFG;

require_once($CFG->libdir . '/completionlib.php');

$courseid  = required_param('courseid', PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);

$course  = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_capability('moodle/course:update', $context);

$sectionnum = 0;
$sectionname = 'All Sections';

if ($sectionid > 0) {
    $sectionrec = $DB->get_record(
        'course_sections',
        ['id'=>$sectionid, 'course'=>$courseid],
        '*',
        MUST_EXIST
    );
    $sectionnum  = $sectionrec->section;
    $sectionname = format_string($sectionrec->name ?: "Section {$sectionrec->section}");
}

$users      = get_enrolled_users($context);
$modinfo    = get_fast_modinfo($course);
$completion = new completion_info($course);

/* ===============================
   HELPERS (SAME AS AJAX FILE)
================================ */

function normalize_module_name(string $modname): string {
    $map = [
        'videotime'        => 'Video',
        'pdfjsloader'      => 'PDF',
        'pdf'              => 'PDF',
        'iomadcertificate' => 'Certificate',
        'customcert'       => 'Certificate',
        'googlemeet'       => 'Google Meet',
        'h5pactivity'      => 'H5P',
        'scorm'            => 'SCORM',
        'quiz'             => 'Quiz',
        'assign'           => 'Assignment',
        'forum'            => 'Forum',
        'page'             => 'Page',
        'url'              => 'URL',
        'ilt'              => 'ILT',
    ];
    return $map[$modname] ?? ucfirst($modname);
}

function calculate_activity_time(array $logs, int $timeout = 1800): int {
    $time = 0;
    $prev = 0;

    foreach ($logs as $log) {
        if ($prev) {
            $gap = $log->timecreated - $prev;
            if ($gap > 0 && $gap <= $timeout) {
                $time += $gap;
            }
        }
        $prev = $log->timecreated;
    }

    // 🔧 Section-wise fallback
    if ($time === 0 && count($logs) === 1) {
        $time = 120; // 2 minutes
    }

    return $time;
}

function get_activity_status(
    cm_info $cm,
    completion_info $completion,
    int $userid,
    array $logs
): string {

    if ($completion->is_enabled($cm)) {
        $cdata = $completion->get_data($cm, true, $userid);
        if (
            $cdata->completionstate == COMPLETION_COMPLETE ||
            $cdata->completionstate == COMPLETION_COMPLETE_PASS
        ) {
            return 'Completed';
        }
    }

    if (!empty($logs)) {
        return 'In Progress';
    }

    return 'Not Started';
}

/* ===============================
   CSV OUTPUT
================================ */

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="activity_report_'.$courseid.'.csv"');

$out = fopen('php://output', 'w');

/* COURSE INFO */
fputcsv($out, ['Course', format_string($course->fullname)]);
fputcsv($out, ['Section', $sectionname]);
fputcsv($out, []);

/* HEADER */
fputcsv($out, [
    'User Name',
    'Email',
    'Activity',
    'Module Type',
    'Status',
    'Time Spent'
]);

foreach ($users as $u) {
    foreach ($modinfo->get_cms() as $cm) {

        if (!$cm->uservisible || $cm->deletioninprogress) {
            continue;
        }

        if ($sectionid > 0 && $cm->sectionnum != $sectionnum) {
            continue;
        }

        // Logs
        $logs = $DB->get_records_sql("
            SELECT timecreated
            FROM {logstore_standard_log}
            WHERE userid = :userid
              AND contextlevel = :contextlevel
              AND contextinstanceid = :cmid
              AND action IN ('viewed','submitted','attempted','answered','completed')
            ORDER BY timecreated ASC
        ", [
            'userid'       => $u->id,
            'cmid'         => $cm->id,
            'contextlevel' => CONTEXT_MODULE
        ]);

        $time   = calculate_activity_time($logs);
        $status = get_activity_status($cm, $completion, $u->id, $logs);

        $hrs  = floor($time / 3600);
        $mins = floor(($time % 3600) / 60);

        $timestr = '-';
        if ($hrs || $mins) {
            $timestr = trim(($hrs ? $hrs.' hr ' : '') . ($mins ? $mins.' min' : ''));
        }

        fputcsv($out, [
            fullname($u),
            $u->email,
            $cm->name,
            normalize_module_name($cm->modname),
            $status,
            $timestr
        ]);
    }
}

fclose($out);
exit;
