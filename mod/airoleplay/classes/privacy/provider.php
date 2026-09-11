<?php
/**
 * Privacy provider for mod_airoleplay - GDPR compliance.
 *
 * Implements Moodle Privacy API to handle data export, deletion,
 * and metadata disclosure for GDPR compliance.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_airoleplay\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the fields which contain personal data.
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table('airoleplay_session', [
            'userid'          => 'privacy:metadata:airoleplay_session:userid',
            'score_overall'   => 'privacy:metadata:airoleplay_session:score_overall',
            'ai_summary'      => 'privacy:metadata:airoleplay_session:ai_summary',
            'ai_strengths'    => 'privacy:metadata:airoleplay_session:ai_strengths',
            'ai_improvements' => 'privacy:metadata:airoleplay_session:ai_improvements',
            'user_dept'       => 'privacy:metadata:airoleplay_session:user_dept',
            'user_designation' => 'privacy:metadata:airoleplay_session:user_designation',
            'timestarted'     => 'privacy:metadata:airoleplay_session:timestarted',
            'timecompleted'   => 'privacy:metadata:airoleplay_session:timecompleted',
        ], 'privacy:metadata:airoleplay_session');

        $collection->add_database_table('airoleplay_message', [
            'session'     => 'privacy:metadata:airoleplay_message:session',
            'role'        => 'privacy:metadata:airoleplay_message:role',
            'content'     => 'privacy:metadata:airoleplay_message:content',
            'transcript'  => 'privacy:metadata:airoleplay_message:transcript',
            'sentiment'   => 'privacy:metadata:airoleplay_message:sentiment',
            'timecreated' => 'privacy:metadata:airoleplay_message:timecreated',
        ], 'privacy:metadata:airoleplay_message');

        $collection->add_database_table('airoleplay_assignment_user', [
            'userid'        => 'privacy:metadata:airoleplay_assignment_user:userid',
            'status'        => 'privacy:metadata:airoleplay_assignment_user:status',
            'best_score'    => 'privacy:metadata:airoleplay_assignment_user:best_score',
            'timeassigned'  => 'privacy:metadata:airoleplay_assignment_user:timeassigned',
            'timecompleted' => 'privacy:metadata:airoleplay_assignment_user:timecompleted',
        ], 'privacy:metadata:airoleplay_assignment_user');

        $collection->add_database_table('airoleplay_ai_usage', [
            'userid'       => 'privacy:metadata:airoleplay_ai_usage:userid',
            'provider'     => 'privacy:metadata:airoleplay_ai_usage:provider',
            'total_tokens' => 'privacy:metadata:airoleplay_ai_usage:total_tokens',
            'cost_usd'     => 'privacy:metadata:airoleplay_ai_usage:cost_usd',
            'timecreated'  => 'privacy:metadata:airoleplay_ai_usage:timecreated',
        ], 'privacy:metadata:airoleplay_ai_usage');

        // External AI providers receive message content.
        $collection->add_external_location_link('openai', [
            'message_content' => 'privacy:metadata:external:openai:message_content',
        ], 'privacy:metadata:external:openai');

        $collection->add_external_location_link('anthropic', [
            'message_content' => 'privacy:metadata:external:anthropic:message_content',
        ], 'privacy:metadata:external:anthropic');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Sessions.
        $contextlist->add_from_sql(
            "SELECT ctx.id
               FROM {context} ctx
               JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :modlevel
               JOIN {airoleplay} r ON r.id = cm.instance
               JOIN {airoleplay_session} s ON s.airoleplay = r.id
              WHERE s.userid = :userid",
            ['modlevel' => CONTEXT_MODULE, 'userid' => $userid]
        );

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $userlist->add_from_sql('userid',
            "SELECT s.userid
               FROM {airoleplay_session} s
               JOIN {course_modules} cm ON cm.instance = s.airoleplay
              WHERE cm.id = :cmid",
            ['cmid' => $context->instanceid]
        );
    }

    /**
     * Export all user data for the specified contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (!count($contextlist)) return;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) continue;

            $cm       = get_coursemodule_from_id('airoleplay', $context->instanceid);
            $scenario = $DB->get_record('airoleplay', ['id' => $cm->instance]);
            if (!$scenario) continue;

            $sessions = $DB->get_records_select(
                'airoleplay_session',
                'airoleplay = :rid AND userid = :uid',
                ['rid' => $scenario->id, 'uid' => $userid]
            );

            $export_data = [];
            foreach ($sessions as $session) {
                $messages = $DB->get_records(
                    'airoleplay_message',
                    ['session' => $session->id],
                    'turn ASC',
                    'turn, role, content, sentiment, timecreated'
                );

                $export_data[] = [
                    'attempt'        => $session->attempt,
                    'status'         => $session->status,
                    'score_overall'  => $session->score_overall,
                    'passed'         => $session->passed ? 'Yes' : 'No',
                    'duration'       => $session->duration_secs . ' seconds',
                    'ai_summary'     => $session->ai_summary,
                    'timestarted'    => transform::datetime($session->timestarted),
                    'timecompleted'  => $session->timecompleted ? transform::datetime($session->timecompleted) : null,
                    'messages'       => array_values(array_map(fn($m) => [
                        'turn'      => $m->turn,
                        'role'      => $m->role,
                        'content'   => $m->content,
                        'sentiment' => $m->sentiment,
                        'time'      => transform::datetime($m->timecreated),
                    ], (array)$messages)),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'mod_airoleplay'), get_string('myroleplaystems', 'mod_airoleplay')],
                (object)['sessions' => $export_data]
            );
        }
    }

    /**
     * Delete all user data for the specified context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) return;

        $cm       = get_coursemodule_from_id('airoleplay', $context->instanceid);
        $scenario = $DB->get_record('airoleplay', ['id' => $cm->instance]);
        if (!$scenario) return;

        $session_ids = $DB->get_fieldset_select(
            'airoleplay_session', 'id', 'airoleplay = :rid', ['rid' => $scenario->id]
        );

        if ($session_ids) {
            [$in_sql, $params] = $DB->get_in_or_equal($session_ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('airoleplay_message',           "session $in_sql", $params);
            $DB->delete_records_select('airoleplay_competency_result', "session $in_sql", $params);
            $DB->delete_records_select('airoleplay_ai_usage',          "session $in_sql", $params);
        }
        $DB->delete_records('airoleplay_session', ['airoleplay' => $scenario->id]);
    }

    /**
     * Delete personal data for specified users.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) return;

        $cm       = get_coursemodule_from_id('airoleplay', $context->instanceid);
        $scenario = $DB->get_record('airoleplay', ['id' => $cm->instance]);
        if (!$scenario) return;

        $userids = $userlist->get_userids();

        foreach ($userids as $userid) {
            $session_ids = $DB->get_fieldset_select(
                'airoleplay_session', 'id',
                'airoleplay = :rid AND userid = :uid',
                ['rid' => $scenario->id, 'uid' => $userid]
            );

            if ($session_ids) {
                [$in_sql, $params] = $DB->get_in_or_equal($session_ids, SQL_PARAMS_NAMED);
                $DB->delete_records_select('airoleplay_message',           "session $in_sql", $params);
                $DB->delete_records_select('airoleplay_competency_result', "session $in_sql", $params);
                $DB->delete_records_select('airoleplay_ai_usage',          "session $in_sql", $params);
            }

            $DB->delete_records('airoleplay_session',        ['airoleplay' => $scenario->id, 'userid' => $userid]);
            $DB->delete_records('airoleplay_ai_usage',       ['userid' => $userid]);
            $DB->delete_records('airoleplay_notification_log', ['userid' => $userid]);
        }
    }

    /**
     * Delete all data for a specified user across all contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) continue;

            $cm       = get_coursemodule_from_id('airoleplay', $context->instanceid);
            $scenario = $DB->get_record('airoleplay', ['id' => $cm->instance]);
            if (!$scenario) continue;

            $session_ids = $DB->get_fieldset_select(
                'airoleplay_session', 'id',
                'airoleplay = :rid AND userid = :uid',
                ['rid' => $scenario->id, 'uid' => $userid]
            );

            if ($session_ids) {
                [$in_sql, $params] = $DB->get_in_or_equal($session_ids, SQL_PARAMS_NAMED);
                $DB->delete_records_select('airoleplay_message',           "session $in_sql", $params);
                $DB->delete_records_select('airoleplay_competency_result', "session $in_sql", $params);
            }

            $DB->delete_records('airoleplay_session',         ['airoleplay' => $scenario->id, 'userid' => $userid]);
            $DB->delete_records('airoleplay_assignment_user', ['userid' => $userid]);
        }

        $DB->delete_records('airoleplay_ai_usage',        ['userid' => $userid]);
        $DB->delete_records('airoleplay_notification_log', ['userid' => $userid]);
    }
}
