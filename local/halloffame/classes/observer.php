<?php
namespace local_halloffame;

defined('MOODLE_INTERNAL') || die();

class observer {

    public static function user_deleted(\core\event\user_deleted $event): void {
        global $DB;
        $uid = (int) $event->objectid;
        $DB->delete_records('halloffame_likes',        ['userid' => $uid]);
        $DB->delete_records('halloffame_submissions',  ['userid' => $uid]);
        $DB->delete_records('halloffame_achievements', ['userid' => $uid]);
        $DB->delete_records('halloffame_awards',       ['userid' => $uid]);
    }
}
