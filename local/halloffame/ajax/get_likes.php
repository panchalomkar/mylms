<?php
global $DB, $PAGE;

require_once('../../../config.php');
require_login();

header('Content-Type: application/json');

$id   = required_param('id', PARAM_INT);
$type = required_param('type', PARAM_ALPHA);

$sql = "SELECT u.id, u.firstname, u.lastname, l.timecreated
        FROM {halloffame_likes} l
        JOIN {user} u ON u.id = l.userid
        WHERE l.itemid = :id AND l.itemtype = :type
        ORDER BY l.timecreated DESC";

$users = $DB->get_records_sql($sql, [
    'id' => $id,
    'type' => $type
]);

$result = [];

foreach ($users as $u) {
    $user = \core_user::get_user($u->id);

    $userpic = new \user_picture($user);
    $userpic->size = 40;

    $result[] = [
        'fullname'   => fullname($user),
        'profilepic' => $userpic->get_url($PAGE)->out(false),
        'time'       => format_time(time() - $u->timecreated) // ✅ "2 mins ago"
    ];
}

echo json_encode(array_values($result));
exit;