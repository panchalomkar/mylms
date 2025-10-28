<?php

use core_completion\progress;

function get_teamusers($page = 0, $perpage = 5, $m = 0, $s = null) {
    global $CFG, $DB, $USER, $PAGE;

    $whereusername = '';
    $wheremanager = '';
    if ($m > 0) {
        $wheremanager = 'u.id IN (
                            SELECT
                                mt.userid
                            FROM
                                {my_team} mt
                            WHERE
                                mt.managerid = ' . $m . '
                        )
                        AND ';
    } else if (is_siteadmin()) {
        $wheremanager = '';
    } else {
        $wheremanager = 'u.id IN (
                            SELECT
                                mt.userid
                            FROM
                                {my_team} mt
                            WHERE
                                mt.managerid = ' . $USER->id . '
                        )
                        AND ';
    }
    if (!empty(trim($s))) {
        $params['s'] = $s;
        $whereusername = ' AND (u.firstname LIKE "%' . $s . '%" OR u.lastname LIKE "%' . $s . '%" )';
    }
    $sql = 'SELECT
                u.id,
                u.firstname,
                u.lastname,
                u.email,
                u.department,
                u.institution
            FROM
                {user} u
            WHERE
                ' . $wheremanager . '
                u.id <> 1
                AND u.deleted = 0
                ' . $whereusername . '
            ORDER BY u.firstname';

    $params = array();
    $teamusers = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

    $coursessummery = [];
    foreach ($teamusers as $value) {
        $courses = enrol_get_all_users_courses($value->id);

        $user_picture = new user_picture($value);
        $src = $user_picture->get_url($PAGE);

        $coursessummery[$value->id]['userid'] = $value->id;
        $coursessummery[$value->id]['firstname'] = $value->firstname;
        $coursessummery[$value->id]['lastname'] = $value->lastname;
        $coursessummery[$value->id]['email'] = $value->email;
        $coursessummery[$value->id]['department'] = $value->department;
        $coursessummery[$value->id]['institution'] = $value->institution;
        $coursessummery[$value->id]['profilepic'] = $src;
        $coursessummery[$value->id]['courseenrolled'] = count($courses);
        $completed = 0;
        $courseinprogress = 0;
        $coursesprogress = [];
        foreach ($courses as $course) {
            $completion = new \completion_info($course);

            // First, let's make sure completion is enabled.
            if (!$completion->is_enabled()) {
                continue;
            }

            $percentage = progress::get_course_progress_percentage($course, $value->id);
            if (!is_null($percentage)) {
                $percentage = floor($percentage);
            }

            if ($completion->is_course_complete($value->id)) {
                $completed++;
            }
            if ($percentage > 0 && !($completion->is_course_complete($value->id))) {
                $courseinprogress++;
            }
        }
        $coursessummery[$value->id]['courseinprogress'] = $courseinprogress;
        $coursessummery[$value->id]['coursecompleted'] = $completed;
    }

    return $coursessummery;
}

function get_managers() {
    global $DB;

    $sql = 'SELECT
            u.id as id,
            u.firstname as firstname,
            u.lastname as lastname
        FROM mdl_user u
            JOIN mdl_role_assignments ra ON ra.userid = u.id
            JOIN mdl_role r ON r.id = ra.roleid
        WHERE
            r.shortname = :shortname';
    $managers = $DB->get_records_sql($sql, array('shortname' => 'manager'));

    return $managers;
}

function show_team_users() {
    global $DB;

    $managerid = required_param('managerid', PARAM_INT);

    $assignedusers = get_assigned_users($managerid);
    $availableusers = get_available_users($managerid);

    return array(
        'status' => 'success',
        'assignedusers' => $assignedusers,
        'availableusers' => $availableusers,
    );
}

function get_assigned_users($managerid) {
    global $DB;

    $sql = 'SELECT
                u.id,
                u.firstname,
                u.lastname,
                u.email
            FROM
                {user} u
                JOIN {my_team} mt ON mt.userid = u.id
            WHERE
                mt.managerid = :managerid
            ORDER BY u.firstname';

    $assignedusers = $DB->get_records_sql($sql, array('managerid' => $managerid));

    return $assignedusers;
}

function get_available_users($managerid) {
    global $DB;

    $sql = 'SELECT
                u.id,
                u.firstname,
                u.lastname,
                u.email
            FROM
                mdl_user u
            WHERE
                u.id NOT IN (
                    SELECT
                        mt.userid
                    FROM
                        mdl_my_team mt
                    WHERE
                        mt.managerid = ' . $managerid . '
                )
                AND u.id <> 1
                AND u.id <> ' . $managerid . '
            ORDER BY u.firstname
            LIMIT 200';

    $availableusers = $DB->get_records_sql($sql);

    return $availableusers;
}

function assign_team_users() {
    global $DB;

    $managerid = required_param('managerid', PARAM_INT);
    $users = required_param('users', PARAM_ALPHANUMEXT);

    $entries = array();
    $time = time();

    $entry = array(
        'managerid' => $managerid,
        'timemodified' => $time
    );

    foreach ($users as $user) {

        $entry['userid'] = $user;

        if ($id = $DB->get_field('my_team', 'id', array('managerid' => $managerid, 'userid' => $user))) {
            $entry['id'] = $id;
            unset($entry['timecreated']);
            $DB->update_record('my_team', $entry);
        } else {
            unset($entry['id']);
            $entry['timecreated'] = $time;
            $newentries[] = $entry;
        }
    }

    if (count($newentries) > 0) {
        $DB->insert_records('my_team', $newentries);
    }

    $response = array(
        'status' => 'success',
        'message' => get_string('userassigned', 'local_my_team')
    );
    return $response;
}

function remove_team_users() {
    global $DB;

    $managerid = required_param('managerid', PARAM_INT);
    $users = required_param('users', PARAM_ALPHANUMEXT);

    $entries = array();

    $entry = array(
        'managerid' => $managerid
    );

    foreach ($users as $user) {
        $entry['userid'] = $user;
        $DB->delete_records('my_team', $entry);
    }

    $response = array(
        'status' => 'success',
        'message' => get_string('userremoved', 'local_my_team')
    );
    return $response;
}

function search_team_users() {
    global $DB;

    $managerid = required_param('managerid', PARAM_INT);
    $searchin = required_param('searchin', PARAM_TEXT);
    $search = optional_param('search', null, PARAM_TEXT);

    if ($searchin == 'assigned') {
        $users = search_assigned_users($managerid, $search);
    } else if ($searchin == 'available') {
        $users = search_available_users($managerid, $search);
    }

    return array(
        'status' => 'success',
        'users' => $users
    );
}

function search_assigned_users($managerid, $search = null) {
    global $DB;

    $where = '';
    if ($search) {
        $where = ' AND ( u.firstname LIKE "%' . $search . '%" OR u.lastname LIKE "%' . $search . '%" )';
    }

    $sql = 'SELECT
                u.id,
                u.firstname,
                u.lastname,
                u.email
            FROM
                {user} u
                JOIN {my_team} mt ON mt.userid = u.id
            WHERE
                mt.managerid = :managerid
                ' . $where . '
            ORDER BY u.firstname';

    $assignedusers = $DB->get_records_sql($sql, ['managerid' => $managerid]);

    return $assignedusers;
}

function search_available_users($managerid, $search = null) {
    global $DB;

    $where = '';
    if ($search) {
        $where = ' AND ( u.firstname LIKE "%' . $search . '%" OR u.lastname LIKE "%' . $search . '%" )';
    }

    $sql = 'SELECT
                u.id,
                u.firstname,
                u.lastname,
                u.email
            FROM
                mdl_user u
            WHERE
                u.id NOT IN (
                    SELECT
                        mt.userid
                    FROM
                        mdl_my_team mt
                    WHERE
                        mt.managerid = ' . $managerid . '
                )
                AND u.id <> 1
                AND u.id <> ' . $managerid . '
                ' . $where . '
            ORDER BY u.firstname';

    $availableusers = $DB->get_records_sql($sql);

    return $availableusers;
}
function get_progress_bar($x = 0, $y = 0, $status = 0) {
    switch ($status) {
        case 1: $class = 'course-enrolled'; break;
        case 2: $class = 'course-in-progress'; break;
        case 3: $class = 'course-completed'; break;
        case 4: $class = 'course-not-started'; break;
        default: $class = ''; break;
    }

    if ($x == 0 || $y == 0) {
        $percentage = 0;
    } else {
        $percent = $x / $y;
        $percentage = min(100, number_format($percent * 100, 0)); // ensure max 100
    }

    $over50 = ($percentage > 50) ? 'over50' : '';
    $extra = ($percentage == 100) ? ' full-complete' : ''; // New special case

    return '<div class="progress-circle ' . $over50 . ' p' . $percentage . $extra . '" title="' . $percentage . '%">
                <span class="' . $class . '">' . $x . '</span>
                <div class="left-half-clipper">
                    <div class="first50-bar ' . $class . '"></div>
                    <div class="value-bar ' . $class . '"></div>
                </div>
            </div>';
}



function get_my_team_data($userid) {
    global $DB;

    $manager = $DB->get_record('user', array('id' => $userid));

    $SQL = "SELECT u.id, u.firstname, u.lastname, u.email, u.department FROM mdl_user_info_data d 
            INNER JOIN mdl_user_info_field f ON f.id = d.fieldid 
            INNER JOIN mdl_user u ON u.id = d.userid 
            WHERE f.shortname = 'repotingto' AND d.data = '$manager->username' AND (u.suspended = 0 AND u.deleted = 0)";

    $records = $DB->get_records_sql($SQL);
    return $records;
}


function get_user_sub_division($userid) {
    global $DB;

    $SQL = "SELECT d.id, d.data FROM mdl_user_info_data d 
            INNER JOIN mdl_user_info_field f ON f.id = d.fieldid 
            WHERE f.shortname = 'subdivision' AND d.userid = $userid";

    $records = $DB->get_record_sql($SQL);

    return $records->data;
}

function get_user_enrolled_courses($userid) {
    global $DB;

    $SQL = "SELECT DISTINCT c.id AS courseid, c.fullname
            FROM mdl_user u
            JOIN mdl_user_enrolments ue ON ue.userid = u.id
            JOIN mdl_enrol e ON e.id = ue.enrolid
            JOIN mdl_role_assignments ra ON ra.userid = u.id
            JOIN mdl_context ct ON ct.id = ra.contextid AND ct.contextlevel = 50
            JOIN mdl_course c ON c.id = ct.instanceid AND e.courseid = c.id
            JOIN mdl_role r ON r.id = ra.roleid AND r.shortname = 'student'
            WHERE e.status = 0 AND u.suspended = 0 AND u.deleted = 0
              AND (ue.timeend = 0 OR ue.timeend > UNIX_TIMESTAMP(NOW())) AND ue.status = 0 AND u.id = $userid";

    return $records = $DB->get_records_sql($SQL);
}

function get_user_inprogress_courses($userid) {
    global $DB;

    $enrolled = get_user_enrolled_courses($userid);

    $cids = array_keys($enrolled);
    $cids = implode(',', $cids);
    $SQL = "SELECT id FROM mdl_course_completions WHERE userid = $userid AND
            timestarted > 0 AND timecompleted IS NULL AND FIND_IN_SET(course, '$cids')";

    $records = $DB->get_records_sql($SQL);

    return count($records);
}
function get_user_not_started_course_count($userid) {
    global $CFG;
    require_once($CFG->libdir . '/completionlib.php');

    $count = 0;
    $courses = enrol_get_users_courses($userid, true, 'id, fullname, visible');

    foreach ($courses as $course) {
        if (!$course->visible) {
            continue;
        }

        $completion = new completion_info($course);
        if (!$completion->is_enabled()) {
            continue;
        }

        $modinfo = get_fast_modinfo($course->id, $userid);
        $cms = $modinfo->get_cms();

        $total = 0;
        $completed = 0;

        foreach ($cms as $cm) {
            if (!$cm->uservisible || !$cm->completion || empty($cm->id)) {
                continue;
            }

            $total++;
            $cmcompletion = $completion->get_data($cm, true, $userid);
            if ($cmcompletion->completionstate == COMPLETION_COMPLETE ||
                $cmcompletion->completionstate == COMPLETION_COMPLETE_PASS) {
                $completed++;
            }
        }

        if ($total > 0 && $completed === 0) {
            $count++;
        }
    }

    return $count;
}





function get_user_completed_courses($userid) {
    global $DB;

    $enrolled = get_user_enrolled_courses($userid);

    $cids = array_keys($enrolled);
    $cids = implode(',', $cids);
    $SQL = "SELECT id FROM mdl_course_completions WHERE userid = $userid AND
            timecompleted > 0 AND FIND_IN_SET(course, '$cids')";

    $records = $DB->get_records_sql($SQL);

    return count($records);
}

function get_user_course_grade($userid) {
    global $DB, $CFG;

    include_once $CFG->dirroot . '/grade/querylib.php';

    $enrolled = get_user_enrolled_courses($userid);

    $cids = array_keys($enrolled);
    $grades = grade_get_course_grade($userid, $cids);

    $count = 0;
    $sum = 0;
    $maxsum = 0;
    foreach ($grades as $grade) {
        $sum = $sum + $grade->grade;
        $maxsum = $maxsum + $grade->item->grademax;
        $count++;
    }

    $mygrade = ($sum / $maxsum) * 100;

    if (is_nan($mygrade)) {
        return '';
    }
    return $mygrade;
}
