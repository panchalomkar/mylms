<?php
require_once("../../../config.php");
require_login();

global $DB;

$courseid  = required_param('courseid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);

$course  = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);
$section = $DB->get_record('course_sections', ['id'=>$sectionid], '*', MUST_EXIST);

$context = context_course::instance($courseid);
require_capability('moodle/course:update', $context);

$users      = get_enrolled_users($context);
$modinfo    = get_fast_modinfo($course);
$completion = new completion_info($course);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="activity_report_'.$courseid.'_section_'.$sectionid.'.csv"');

$out = fopen('php://output', 'w');

/* CSV HEADER */
fputcsv($out, [
    'Course',
    'Section',
    'User ID',
    'User Name',
    'Email',
    'Activity',
    'Module Type',
    'Status',
    'Time Spent (Minutes)'
]);

foreach ($users as $u) {

    foreach ($modinfo->get_cms() as $cm) {

        if ($cm->section != $sectionid) continue;
        if (!$cm->uservisible || $cm->deletioninprogress) continue;

        /* COMPLETION */
        $status = 'Not Completed';
        if ($completion->is_enabled($cm)) {
            $cdata = $completion->get_data($cm, true, $u->id);
            if (in_array($cdata->completionstate, [
                COMPLETION_COMPLETE,
                COMPLETION_COMPLETE_PASS
            ])) {
                $status = 'Completed';
            }
        }

        /* TIME SPENT */
        $logs = $DB->get_records_sql("
            SELECT timecreated
            FROM {logstore_standard_log}
            WHERE userid = ?
              AND contextinstanceid = ?
              AND contextlevel = ?
            ORDER BY timecreated ASC
        ", [$u->id, $cm->id, CONTEXT_MODULE]);

        $time = 0;
        $prev = 0;
        foreach ($logs as $l) {
            if ($prev && ($l->timecreated - $prev) < 1800) {
                $time += ($l->timecreated - $prev);
            }
            $prev = $l->timecreated;
        }

        fputcsv($out, [
            $course->fullname,
            format_string($section->name ?: "Section {$section->section}"),
            $u->id,
            fullname($u),
            $u->email,
            $cm->name,
            ucfirst($cm->modname),
            $status,
            round($time / 60)
        ]);
    }
}

fclose($out);
exit;
