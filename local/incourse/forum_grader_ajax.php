<?php
require_once("../../config.php");
require_login();
global $DB;

$courseid = required_param('courseid', PARAM_INT);
$section  = optional_param('section', 0, PARAM_INT);
$forumid  = optional_param('forumid', 0, PARAM_INT);

$modinfo = get_fast_modinfo($courseid);

// -------------------------------------
// 1) RETURN FORUM LIST BY SECTION
// -------------------------------------
if ($section > 0) {
    $forums = [];

    foreach ($modinfo->get_section_info_all() as $sec) {
        if ((int)$sec->section !== (int)$section) continue;

        // Get all modules in this section
        $mods = $modinfo->sections[$section] ?? [];

        foreach ($mods as $cmid) {
            $cm = $modinfo->cms[$cmid];

            if ($cm->modname === 'forum' && !$cm->deletioninprogress) {
                $forums[] = [
                    'id' => $cm->instance,
                    'name' => $cm->name
                ];
            }
        }
        break;
    }

    echo json_encode($forums);
    exit;
}

// -------------------------------------
// 2) RETURN POSTS + GRADES FOR A FORUM
// -------------------------------------
if ($forumid > 0) {
    $sql = "
        SELECT 
            u.id,
            CONCAT(u.firstname, ' ', u.lastname) AS student,
            fp.message AS response,
            fg.grade
        FROM {forum_posts} fp
        JOIN {forum_discussions} fd ON fd.id = fp.discussion
        JOIN {user} u ON u.id = fp.userid
        LEFT JOIN {forum_grades} fg 
               ON fg.forum = fd.forum AND fg.userid = fp.userid
        WHERE fd.forum = ?
        ORDER BY fp.created ASC
    ";

    $records = $DB->get_records_sql($sql, [$forumid]);

    $output = [];
    foreach ($records as $r) {
        $output[] = [
            'student'  => $r->student,
            'response' => strip_tags($r->response),
            'grade'    => ($r->grade === null ? '-' : rtrim(rtrim($r->grade, '0'), '.'))
        ];
    }

    echo json_encode($output);
    exit;
}

echo json_encode([]);
exit;
