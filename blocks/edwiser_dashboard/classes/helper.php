<?php
namespace block_edwiser_dashboard;

defined('MOODLE_INTERNAL') || die();

use local_edwiserreports\blocks\courseprogressblock;
use local_edwiserreports\blocks\inactiveusersblock;
use local_edwiserreports\blocks\customreportsblock;
use local_edwiserreports\blocks\certificatesblock;


class helper {

    public static function get_dashboard_data(): array {
        global $DB;
        $selectedcourseid = optional_param('courseid', 0, PARAM_INT);

        $courses = self::get_user_courses();
        foreach ($courses as &$c) {
            $c['selected'] = ($c['id'] == $selectedcourseid);
        }
$customreport = self::get_custom_report_block(1);

// 🔥 REQUIRED for export
$customreport['params'] = json_decode(
    $DB->get_field('edwreports_custom_reports', 'data', ['id' => 1])
);
        return [
            'courses'        => $courses,
            'cohort'         => self::get_cohorts_for_course($selectedcourseid), 
            'siteoverview'   => self::get_site_overview(),
            'customreport' => $customreport,
            'courseprogress' => self::get_course_progress_from_edwiser($selectedcourseid),
            'certificates'   => self::get_certificates(),
            'inactiveusers'  => self::get_inactive_users(),
            'activeusers'    => self::get_active_users(),
            'certificates1' => self::get_certificates1(),

        ];
    }

/* ================= CERTIFICATES ================= */
private static function get_certificates1(): array {
    $block = new certificatesblock();

    // Same params Edwiser uses
    $params = (object)[
        'cohort' => optional_param('cohortid', 0, PARAM_INT)
    ];

    $response = $block->get_data($params);

    return [
        'blockid' => 'certificatesblock',
        'rows'    => $response->data ?? []
    ];
}
/* ================= ANNOUNCEMENTS ================= */
public static function get_announcements(): array {
    global $DB, $COURSE;

    // Course or site
    $courseid = (!empty($COURSE->id) && $COURSE->id > 1)
        ? $COURSE->id
        : SITEID;

    // Find News forum
    $forum = $DB->get_record('forum', [
        'course' => $courseid,
        'type'   => 'news'
    ]);

    if (!$forum) {
        return [
            'forumid' => 0,
            'items'   => []
        ];
    }

    $sql = "
        SELECT d.id AS discussionid, d.name AS title, p.modified,
               u.firstname, u.lastname
        FROM {forum_discussions} d
        JOIN {forum_posts} p ON p.discussion = d.id
        JOIN {user} u ON u.id = p.userid
        WHERE d.forum = :forumid
          AND p.parent = 0
        ORDER BY p.modified DESC
    ";

    $posts = $DB->get_records_sql($sql, ['forumid' => $forum->id], 0, 10);

    $items = [];
    foreach ($posts as $p) {
        $items[] = [
            'title'  => format_string($p->title),
            'author' => fullname($p),
            'date'   => userdate($p->modified, '%d %b %Y'),
            'url'    => (new \moodle_url(
                '/mod/forum/discuss.php',
                ['d' => $p->discussionid]
            ))->out(false)
        ];
    }

    return [
        'forumid' => $forum->id,
        'items'   => $items
    ];
}


    /* ================= COURSE PROGRESS ================= */
    private static function get_course_progress_from_edwiser(int $courseid = 0): array {
        global $USER;

        $block = new courseprogressblock();
        $courses = enrol_get_users_courses($USER->id, true);

        if (empty($courses)) {
            return self::empty_course_progress();
        }

        $course = ($courseid && isset($courses[$courseid])) ? $courses[$courseid] : reset($courses);

        $params = (object)[
            'course' => $course->id,
            'cohort' => 0,
            'group'  => 0
        ];

        $response = $block->get_data($params);

        if (empty($response->data)) {
            return self::empty_course_progress();
        }

        $ranges = [
            ['label' => '0–20%',   'range' => '0to20'],
            ['label' => '21–40%',  'range' => '21to40'],
            ['label' => '41–60%',  'range' => '41to60'],
            ['label' => '61–80%',  'range' => '61to80'],
            ['label' => '81–100%', 'range' => '81to100']
        ];

        $distribution = [];
        foreach ($ranges as $i => $range) {
            $distribution[] = [
                'label'    => $range['label'],
                'value'    => (int)($response->data[$i] ?? 0),
                'range'    => $range['range'],
                'courseid' => $course->id
            ];
        }

        // Create dynamic trend for chart (e.g., last 7 days of course progress)
        $trend = array_map(function($val) { return (int)$val; }, $response->data ?? []);

        return [
            'average'       => round($response->average, 1),
            'distribution'  => $distribution,
            'totallearners' => array_sum($response->data),
            'courseid'      => $course->id,
            'trend'         => $trend
        ];
    }

    private static function empty_course_progress(): array {
        return [
            'average' => 0,
            'distribution' => [],
            'totallearners' => 0,
            'courseid' => 0,
            'trend' => []
        ];
    }


    /* ================= INACTIVE USERS ================= */
    private static function get_inactive_users(): array {
        $block = new inactiveusersblock();

        $params = (object)[
            'filter' => '3month'
        ];

        $response = $block->get_data($params);

        return [
            'list' => $response->data ?? [],
            'count' => count($response->data ?? [])
        ];
    }

    /* ================= COHORTS ================= */
    private static function get_cohorts_for_course(int $courseid): array {
        global $DB, $USER;

        if (!$courseid) return [];

        $cohorts = $DB->get_records_sql("
            SELECT c.id, c.name
            FROM {cohort} c
            JOIN {cohort_members} cm ON cm.cohortid = c.id
            WHERE cm.userid = :userid
        ", ['userid' => $USER->id]);

        return array_map(fn($c) => ['id'=>$c->id,'name'=>$c->name], $cohorts);
    }

    /* ================= USER COURSES ================= */
    private static function get_user_courses(): array {
        global $DB, $USER;

        $courses = is_siteadmin()
            ? $DB->get_records('course', ['visible' => 1])
            : enrol_get_users_courses($USER->id, true);

        $list = [];
        foreach ($courses as $course) {
            if ($course->id == SITEID) continue;
            $list[] = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
            ];
        }
        return $list;
    }



public static function get_custom_report_block(int $reportid): array {
    global $DB;

    // Fetch custom report definition
    $report = $DB->get_record('edwreports_custom_reports', ['id' => $reportid]);
    if (!$report) {
        return [
            'title' => 'Report not found',
            'columns' => [],
            'rows' => []
        ];
    }

    // Decode saved report config
    $params = json_decode($report->data);
    $params->fields = $params->selectedfield ?? [];
    unset($params->selectedfield);

    // Load Edwiser custom report block
    $block = new \local_edwiserreports\blocks\customreportsblock();

    // IMPORTANT: this maps to `customreportsblock-1`
    $block->blockid = 'customreportsblock-' . $reportid;

    // Fetch data
    $data = $block->get_data($params);

    $rows = [];
    if (!empty($data->reportsdata)) {
        foreach ($data->reportsdata as $row) {
            $rows[] = array_values((array)$row);
        }
    }

    return [
        'blockid' => 'customreportsblock-' . $reportid,
        'title'   => format_string($report->fullname),
        'columns' => $data->columns ?? [],
        'rows'    => $rows
    ];
}


    /* ================= SITE OVERVIEW ================= */
  private static function get_site_overview(): array {
    global $DB;

    // Instantiate active users block
    $activeusersblock = new \local_edwiserreports\blocks\activeusersblock();

    // Get last 7 days data (or filter dynamically)
    $params = (object)[
        'filter' => 'last7days',
        'cohortid' => 0
    ];
    $data = $activeusersblock->get_data($params);

    // Extract totals
    $totalActiveUsers = array_sum($data->data->activeUsers ?? []);
    $totalEnrollments = array_sum($data->data->enrolments ?? []);
    $totalCompletions = array_sum($data->data->completionRate ?? []);

    return [
        'activeusers'       => $totalActiveUsers,
        'enrollments'       => $totalEnrollments,
        'completions'       => $totalCompletions,
        'trendActiveUsers'  => $data->data->activeUsers ?? [],
        'trendEnrollments'  => $data->data->enrolments ?? [],
        'trendCompletions'  => $data->data->completionRate ?? [],
        'trendDates'        => $data->dates ?? []
    ];
}



    /* ================= ACTIVE USERS ================= */
    private static function get_active_users(): array {
        $block = new \local_edwiserreports\blocks\activeusersblock();

        $params = (object)[
            'filter' => 'last7days',
            'cohortid' => 0
        ];

        $response = $block->get_data($params);

        return [
            'dates' => $response->dates ?? [],
            'data'  => $response->data ?? [],
            'insight' => $response->insight ?? []
        ];
    }

    /* ================= CERTIFICATES ================= */
    private static function get_certificates(): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('customcert_issues')) {
            return ['issued' => 0];
        }

        return ['issued' => $DB->count_records('customcert_issues')];
    }

    

}
