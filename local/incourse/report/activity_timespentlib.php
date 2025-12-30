<?php
defined('MOODLE_INTERNAL') || die();

/**
 * =====================
 * LOG BASED TIME
 * =====================
 */
function calculate_activity_time(array $logs, int $timeout = 1800): int {
    if (empty($logs)) {
        return 0;
    }

    $time = 0;
    $prev = $logs[0]->timecreated;

    for ($i = 1; $i < count($logs); $i++) {
        $gap = $logs[$i]->timecreated - $prev;
        if ($gap > 0 && $gap <= $timeout) {
            $time += $gap;
        }
        $prev = $logs[$i]->timecreated;
    }

    return ($time === 0) ? 120 : $time;
}

/**
 * =====================
 * QUIZ TIME
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
 * SCORM TIME
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
 * H5P TIME
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
