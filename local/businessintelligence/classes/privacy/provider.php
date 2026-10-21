<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_businessintelligence\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider.
 *
 * The plugin stores uploaded business KPI values of employees and training feedback that names internal trainers
 * (both in the system context). External trainers are not Moodle users.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Stored data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_bi_kpi_value', [
            'userid' => 'privacy:metadata:kpi:userid',
            'employeeid' => 'privacy:metadata:kpi:employeeid',
            'kpi' => 'privacy:metadata:kpi:kpi',
            'period' => 'privacy:metadata:kpi:period',
            'value' => 'privacy:metadata:kpi:value',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:kpi');
        $collection->add_database_table('local_bi_feedback', [
            'traineruserid' => 'privacy:metadata:feedback:traineruserid',
            'trainingname' => 'privacy:metadata:feedback:trainingname',
            'rating' => 'privacy:metadata:feedback:rating',
            'feedback' => 'privacy:metadata:feedback:feedback',
            'usermodified' => 'privacy:metadata:usermodified',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:feedback');
        $collection->add_database_table('local_bi_ext_trainer', [
            'fullname' => 'privacy:metadata:ext:fullname',
            'email' => 'privacy:metadata:ext:email',
            'phone' => 'privacy:metadata:ext:phone',
            'usermodified' => 'privacy:metadata:usermodified',
        ], 'privacy:metadata:ext');
        return $collection;
    }

    /**
     * Contexts with data of a user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $list = new contextlist();
        if ($DB->record_exists('local_bi_kpi_value', ['userid' => $userid])
                || $DB->record_exists('local_bi_feedback', ['traineruserid' => $userid])) {
            $list->add_system_context();
        }
        return $list;
    }

    /**
     * Users with data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        if (!$userlist->get_context() instanceof \context_system) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {local_bi_kpi_value} WHERE userid > 0', []);
        $userlist->add_from_sql('traineruserid', 'SELECT traineruserid FROM {local_bi_feedback} WHERE traineruserid > 0', []);
    }

    /**
     * Export the data of a user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }
            $kpis = [];
            foreach ($DB->get_records('local_bi_kpi_value', ['userid' => $userid], 'kpi, period') as $r) {
                $kpis[] = ['kpi' => $r->kpi, 'period' => $r->period, 'value' => $r->value, 'unit' => $r->unit,
                    'source' => $r->source, 'timecreated' => transform::datetime($r->timecreated)];
            }
            $feedback = [];
            foreach ($DB->get_records('local_bi_feedback', ['traineruserid' => $userid], 'trainingdate') as $r) {
                $feedback[] = ['trainingname' => $r->trainingname, 'mode' => $r->mode,
                    'trainingdate' => transform::datetime($r->trainingdate), 'attendees' => $r->attendees,
                    'rating' => $r->rating, 'feedback' => $r->feedback];
            }
            $sub = [get_string('pluginname', 'local_businessintelligence')];
            if ($kpis) {
                writer::with_context($context)->export_data($sub, (object)['kpivalues' => $kpis]);
            }
            if ($feedback) {
                writer::with_context($context)->export_related_data($sub, 'trainingfeedback', (object)['feedback' => $feedback]);
            }
        }
    }

    /**
     * Delete all data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context instanceof \context_system) {
            $DB->delete_records_select('local_bi_kpi_value', 'userid > 0');
            $DB->set_field_select('local_bi_feedback', 'traineruserid', 0, 'traineruserid > 0');
        }
    }

    /**
     * Delete the data of one user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_system) {
                $DB->delete_records('local_bi_kpi_value', ['userid' => $userid]);
                $DB->set_field('local_bi_feedback', 'traineruserid', 0, ['traineruserid' => $userid]);
            }
        }
    }

    /**
     * Delete the data of several users.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        if (!$userlist->get_context() instanceof \context_system || !$userlist->get_userids()) {
            return;
        }
        [$in, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_bi_kpi_value', "userid $in", $params);
        $DB->set_field_select('local_bi_feedback', 'traineruserid', 0, "traineruserid $in", $params);
    }
}
