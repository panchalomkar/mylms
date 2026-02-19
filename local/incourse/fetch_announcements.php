<?php
require_once(__DIR__ . '/../../config.php');
require_login();

global $DB, $USER;

$courseid = required_param('id', PARAM_INT);
$page = optional_param('page', 1, PARAM_INT);
$search = optional_param('search', '', PARAM_RAW);
$datefilter = optional_param('date', '', PARAM_ALPHA);

$context = context_course::instance($courseid);
require_capability('mod/forum:viewdiscussion', $context);

// Get News forum
$forum = $DB->get_record('forum', ['course' => $courseid, 'type' => 'news']);
if (!$forum) {
    echo json_encode(['forums' => []]);
    exit;
}

$perpage = 4;
$offset = ($page - 1) * $perpage;

$sql = "SELECT d.*, u.firstname, u.lastname
          FROM {forum_discussions} d
          JOIN {user} u ON u.id = d.userid
         WHERE d.forum = :forumid";
$params = ['forumid' => $forum->id];

// Search filter
if (!empty($search)) {
    $sql .= " AND (d.name LIKE :search OR d.firstpost IN (
                    SELECT p.id FROM {forum_posts} p WHERE p.message LIKE :search2
                ))";
    $params['search'] = "%$search%";
    $params['search2'] = "%$search%";
}

// Date filter
$now = time();
if ($datefilter === 'today') {
    $sql .= " AND d.timemodified >= :today";
    $params['today'] = $now - 86400;
} elseif ($datefilter === 'week') {
    $sql .= " AND d.timemodified >= :week";
    $params['week'] = $now - (7 * 86400);
} elseif ($datefilter === 'month') {
    $sql .= " AND d.timemodified >= :month";
    $params['month'] = $now - (30 * 86400);
}

$sql .= " ORDER BY d.timemodified DESC";
$totalcount = $DB->count_records_sql("SELECT COUNT(*) FROM ($sql) subquery", $params);

$sql .= " LIMIT $perpage OFFSET $offset";
$discussions = $DB->get_records_sql($sql, $params);

$forums = [];

foreach ($discussions as $d) {
    // Get reply count
    $replycount = $DB->count_records('forum_posts', ['discussion' => $d->id]) - 1;
    if ($replycount < 0) $replycount = 0;

    // Get last post info
    $lastpost = $DB->get_record_sql("
        SELECT p.*, u.firstname, u.lastname
          FROM {forum_posts} p
          JOIN {user} u ON u.id = p.userid
         WHERE p.discussion = :did
      ORDER BY p.created DESC LIMIT 1", ['did' => $d->id]
    );

    $forums[] = [
        'discussionid' => $d->id,
        'name' => format_string($d->name),
        'author' => fullname($d),
        'created' => userdate($d->timemodified, get_string('strftimedatetime', 'core_langconfig')),
        'replies' => $replycount,
        'lastpostauthor' => $lastpost ? fullname($lastpost) : fullname($d),
        'lastposttimestamp' => $lastpost ? $lastpost->created : $d->timemodified
    ];
}

echo json_encode([
    'forums' => $forums,
    'page' => $page,
    'perpage' => $perpage,
    'total' => $totalcount
]);
exit;
