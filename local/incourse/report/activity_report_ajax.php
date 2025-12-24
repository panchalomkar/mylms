<?php
require_once("../../../config.php");
require_login();

global $DB, $CFG;

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/excellib.class.php');

$userid    = required_param('userid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);

$user    = $DB->get_record('user', ['id'=>$userid], '*', MUST_EXIST);
$section = $DB->get_record('course_sections', ['id'=>$sectionid], '*', MUST_EXIST);
$course  = $DB->get_record('course', ['id'=>$section->course], '*', MUST_EXIST);

$completion = new completion_info($course);
$modinfo    = get_fast_modinfo($course);

/**
 * Normalize module type
 */
function normalize_module_name(string $modname, string $label): string {
    $map = [
        'videotime'        => 'Video',
        'pdfjsloader'      => 'PDF',
        'pdf'              => 'PDF',
        'iomadcertificate' => 'Certificate',
        'customcert'       => 'Certificate',
        'googlemeet'       => 'GoogleMeet',
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

$data  = [];
$index = 1;

foreach ($modinfo->get_cms() as $cm) {

    // Only this section
    if ($cm->sectionnum != $section->section) {
        continue;
    }

    // Skip hidden / deleted
    if (!$cm->uservisible || $cm->deletioninprogress) {
        continue;
    }

    /** --------------------
     * COMPLETION STATUS
     * -------------------- */
    if ($completion->is_enabled($cm)) {
        $cdata = $completion->get_data($cm, true, $userid);

        $statusicon = (
            $cdata->completionstate == COMPLETION_COMPLETE ||
            $cdata->completionstate == COMPLETION_COMPLETE_PASS
        )
            ? '<span class="text-success"><i class="fa fa-check"></i></span> Completed'
            : '<span class="text-danger"><i class="fa fa-times"></i></span> Not Completed';
    } else {
        $statusicon = '<span class="text-danger"><i class="fa fa-times"></i></span> Not Completed';
    }

    /** --------------------
     * MODULE TYPE
     * -------------------- */
    $rawlabel   = get_string('modulename', $cm->modname);
    $modulename = normalize_module_name($cm->modname, $rawlabel);

    /** --------------------
     * TIME SPENT (PER ACTIVITY)
     * -------------------- */
    $logs = $DB->get_records_sql("
        SELECT timecreated
        FROM {logstore_standard_log}
        WHERE userid = :userid
          AND contextinstanceid = :cmid
          AND contextlevel = :contextlevel
        ORDER BY timecreated ASC
    ", [
        'userid'       => $userid,
        'cmid'         => $cm->id,
        'contextlevel' => CONTEXT_MODULE
    ]);

    $time    = 0;
    $prev    = 0;
    $timeout = 30 * 60; // 30 min idle

    foreach ($logs as $log) {
        if ($prev) {
            $gap = $log->timecreated - $prev;
            if ($gap > 0 && $gap <= $timeout) {
                $time += $gap;
            }
        }
        $prev = $log->timecreated;
    }

    /** --------------------
     * ROW DATA
     * -------------------- */
    $data[] = [
        'srno'         => $index++,
        'activityname' => $cm->name,
        'moduletype'   => $modulename,
        'status'       => $statusicon,
        'timespent'    => $time   // seconds (JS formats it)
    ];
}

/**
 * EXCEL DOWNLOAD (unchanged)
 */
$download = optional_param('download', '', PARAM_ALPHA);
if ($download === 'excel') {
    $filename = clean_filename("ActivityReport_{$user->username}_{$course->shortname}.xlsx");
    $workbook = new MoodleExcelWorkbook("-");
    $sheet = $workbook->add_worksheet('Activity Report');

    $sheet->write_string(0, 0, 'Student Name');
    $sheet->write_string(0, 1, fullname($user));
    $sheet->write_string(1, 0, 'Course Name');
    $sheet->write_string(1, 1, $course->fullname);
    $sheet->write_string(2, 0, 'Section Name');
    $sheet->write_string(2, 1, format_string($section->name ?: "Section {$section->section}"));

    $sheet->write_string(4, 0, 'Sr. No');
    $sheet->write_string(4, 1, 'Activity Name');
    $sheet->write_string(4, 2, 'Module Type');
    $sheet->write_string(4, 3, 'Status');
    $sheet->write_string(4, 4, 'Time Spent');

    $row = 5;
    foreach ($data as $d) {
        $hrs  = floor($d['timespent'] / 3600);
        $mins = floor(($d['timespent'] % 3600) / 60);

        $timestr = '';
        if ($hrs > 0)  $timestr .= $hrs . ' hr ';
        if ($mins > 0) $timestr .= $mins . ' min';

        $sheet->write_string($row, 0, $d['srno']);
        $sheet->write_string($row, 1, $d['activityname']);
        $sheet->write_string($row, 2, $d['moduletype']);
        $sheet->write_string($row, 3, strip_tags($d['status']));
        $sheet->write_string($row, 4, trim($timestr));
        $row++;
    }

    $workbook->close();
    exit;
}

echo json_encode($data);
exit;
