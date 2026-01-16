<?php
namespace block_edwiser_dashboard;

defined('MOODLE_INTERNAL') || die();

use local_edwiserreports\blocks\courseprogressblock;
use local_edwiserreports\blocks\inactiveusersblock;

class helper {

    /* =========================
       DASHBOARD DATA
    ========================= */
    public static function get_dashboard_data(): array {
        return [
            'siteoverview'    => self::get_site_overview(),
            'courseprogress' => self::get_course_progress_from_edwiser(),
            'certificates'   => self::get_certificates(),
            'inactiveusers'  => self::get_inactive_users()
        ];
    }

    /* =========================
       COURSE PROGRESS (REAL DATA)
    ========================= */
    private static function get_course_progress_from_edwiser(): array {
        global $USER;

        $block = new courseprogressblock();

        // Pick first enrolled course (same logic Edwiser uses)
        $courses = enrol_get_users_courses($USER->id, true);
        if (empty($courses)) {
            return self::empty_course_progress();
        }

        $course = reset($courses);

        $params = (object)[
            'course' => $course->id,
            'cohort' => 0,
            'group'  => 0
        ];

        $response = $block->get_data($params);

        if (empty($response->data)) {
            return self::empty_course_progress();
        }

        // Map Edwiser output to your UI format
        $labels = ['0-20%', '21-40%', '41-60%', '61-80%', '81-100%'];
        $distribution = [];
        $totallearners = array_sum($response->data);

      $map = [
    '0-20%'   => '0to20',
    '21-40%'  => '21to40',
    '41-60%'  => '41to60',
    '61-80%'  => '61to80',
    '81-100%' => '81to100'
];

foreach ($labels as $i => $label) {
    $distribution[] = [
        'label' => $label,
        'value' => $response->data[$i] ?? 0,
        'range' => $map[$label]
    ];
}


       return [
    'average'       => round($response->average),
    'distribution'  => $distribution,
    'totallearners' => $totallearners,
    'courseid'      => $course->id
];

    }

    private static function empty_course_progress(): array {
        return [
            'average' => 0,
            'distribution' => [
                ['label' => '0-20%', 'value' => 0],
                ['label' => '21-40%', 'value' => 0],
                ['label' => '41-60%', 'value' => 0],
                ['label' => '61-80%', 'value' => 0],
                ['label' => '81-100%', 'value' => 0],
            ],
            'totallearners' => 0
        ];
    }

    /* =========================
       INACTIVE USERS
    ========================= */
    private static function get_inactive_users(): array {
        $block = new inactiveusersblock();

        $params = (object)[
            'filter' => '3month'
        ];

        $response = $block->get_data($params);

        return [
            'list' => $response->data ?? []
        ];
    }

    /* =========================
       SITE OVERVIEW
    ========================= */
    private static function get_site_overview(): array {
        global $DB;

        $since = time() - (30 * DAYSECS);

        return [
            'activeusers' => $DB->count_records_sql(
                "SELECT COUNT(DISTINCT userid)
                   FROM {logstore_standard_log}
                  WHERE timecreated >= :since",
                ['since' => $since]
            ),
            'enrollments' => $DB->count_records('user_enrolments'),
            'completions' => $DB->count_records_select(
                'course_completions', 'timecompleted > 0'
            ),
            'trendActiveUsers' => [120,145,130,170,155,195,190],
            'trendEnrollments' => [85,90,110,135,125,145,160],
            'trendCompletions' => [45,50,65,80,95,105,120]
        ];
    }

    /* =========================
       CERTIFICATES
    ========================= */
    private static function get_certificates(): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('customcert_issues')) {
            return ['issued' => 0];
        }

        return ['issued' => $DB->count_records('customcert_issues')];
    }
}
