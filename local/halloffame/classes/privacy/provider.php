<?php
namespace local_halloffame\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $c): collection {
        $c->add_database_table('halloffame_awards', [
            'userid'      => 'privacy:metadata:halloffame_awards',
            'department'  => 'privacy:metadata:halloffame_awards',
            'title'       => 'privacy:metadata:halloffame_awards',
            'message'     => 'privacy:metadata:halloffame_awards',
            'timecreated' => 'privacy:metadata:halloffame_awards',
        ], 'privacy:metadata:halloffame_awards');

        $c->add_database_table('halloffame_achievements', [
            'userid'      => 'privacy:metadata:halloffame_achievements',
            'title'       => 'privacy:metadata:halloffame_achievements',
            'issuer'      => 'privacy:metadata:halloffame_achievements',
            'timecreated' => 'privacy:metadata:halloffame_achievements',
        ], 'privacy:metadata:halloffame_achievements');

        $c->add_database_table('halloffame_submissions', [
            'userid'      => 'privacy:metadata:halloffame_submissions',
            'title'       => 'privacy:metadata:halloffame_submissions',
            'timecreated' => 'privacy:metadata:halloffame_submissions',
        ], 'privacy:metadata:halloffame_submissions');

        $c->add_database_table('halloffame_likes', [
            'userid'      => 'privacy:metadata:halloffame_likes',
            'itemid'      => 'privacy:metadata:halloffame_likes',
            'itemtype'    => 'privacy:metadata:halloffame_likes',
            'timecreated' => 'privacy:metadata:halloffame_likes',
        ], 'privacy:metadata:halloffame_likes');

        return $c;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $cl = new contextlist();
        $cl->add_system_context();
        return $cl;
    }

    public static function get_users_in_context(userlist $ul): void {
        if (!$ul->get_context() instanceof \context_system) {
            return;
        }
        $sql = 'SELECT userid FROM {halloffame_awards}
                UNION SELECT userid FROM {halloffame_achievements}
                UNION SELECT userid FROM {halloffame_submissions}
                UNION SELECT userid FROM {halloffame_likes}';
        $ul->add_from_sql('userid', $sql, []);
    }

    public static function export_user_data(approved_contextlist $cl): void {
        global $DB;
        $uid = $cl->get_user()->id;
        $ctx = \context_system::instance();

        writer::with_context($ctx)->export_data(
            ['Hall of Fame', 'Awards'],
            (object)['awards' => array_values($DB->get_records('halloffame_awards', ['userid' => $uid]))]
        );
        writer::with_context($ctx)->export_data(
            ['Hall of Fame', 'Achievements'],
            (object)['achievements' => array_values($DB->get_records('halloffame_achievements', ['userid' => $uid]))]
        );
        writer::with_context($ctx)->export_data(
            ['Hall of Fame', 'Submissions'],
            (object)['submissions' => array_values($DB->get_records('halloffame_submissions', ['userid' => $uid]))]
        );
        writer::with_context($ctx)->export_data(
            ['Hall of Fame', 'Likes'],
            (object)['likes' => array_values($DB->get_records('halloffame_likes', ['userid' => $uid]))]
        );
    }

    public static function delete_data_for_all_users_in_context(\context $ctx): void {
        global $DB;
        if (!$ctx instanceof \context_system) {
            return;
        }
        $DB->delete_records('halloffame_likes');
        $DB->delete_records('halloffame_submissions');
        $DB->delete_records('halloffame_achievements');
        $DB->delete_records('halloffame_awards');
    }

    public static function delete_data_for_user(approved_contextlist $cl): void {
        global $DB;
        $uid = $cl->get_user()->id;
        $DB->delete_records('halloffame_likes',        ['userid' => $uid]);
        $DB->delete_records('halloffame_submissions',  ['userid' => $uid]);
        $DB->delete_records('halloffame_achievements', ['userid' => $uid]);
        $DB->delete_records('halloffame_awards',       ['userid' => $uid]);
    }

    public static function delete_data_for_users(approved_userlist $ul): void {
        global $DB;
        $ids = $ul->get_userids();
        if (empty($ids)) {
            return;
        }
        [$in, $p] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        foreach (['halloffame_likes','halloffame_submissions',
                  'halloffame_achievements','halloffame_awards'] as $t) {
            $DB->delete_records_select($t, "userid $in", $p);
        }
    }
}
