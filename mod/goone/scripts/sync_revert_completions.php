<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A one-off script to revert GO1 activity completions in Moodle that have been awarded by error.
 *
 * @package   mod_goone
 * @author    2024 Kirill Astashov <kirill.astashov@androgogic.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__file__)))) . '/config.php');

if (!(is_siteadmin() || !isset($_SERVER['REMOTE_ADDR']))) {
    die("Site admin or CLI only, sorry.\n");
}

require_once($CFG->dirroot . '/mod/goone/lib.php');
require_once($CFG->libdir . '/completionlib.php');

$eol = CLI_SCRIPT ? "\n" : '<br />';

$counter = 0;
$gooneactivities = $DB->get_records('goone');
$totalgoone = count($gooneactivities);
if (empty($gooneactivities)) {
    die("No GO1 activities. Bye.\n");
}
$module = $DB->get_record('modules', ['name'=>'goone']);
foreach ($gooneactivities as $goone) {
    $counter++;
    $goonecompletions = bulk_mod_goone_api_custom_api_request("enrollments" , "", ['lo_ids' => $goone->loid, 'status' => 'completed', '']);
    $cm = $DB->get_record('course_modules', ['course' => $goone->course, 'module' => $module->id, 'instance' => $goone->id]);
    $course = $DB->get_record('course', ['id' => $goone->course]);
    $completion = new \completion_info($course);

    if (!$completion->is_enabled($cm)) {
        mtrace("Skipping activity: course $course->id cm $cm->id goone $goone->id : completion is disabled (activity $counter of $totalgoone)", $eol);
        delete_orphaned_goone_completions($goone->id, []);
        continue;
    }

    if (!($cm->completion == 2 && $goone->completionsubmit)) {
        mtrace("Skipping activity: course $course->id cm $cm->id goone $goone->id : completion conditions are not set as expected (activity $counter of $totalgoone)", $eol);
        delete_orphaned_goone_completions($goone->id, []);
        continue;
    }

    // Get Moodle activity completions.
    $sql = "SELECT *
        FROM {course_modules_completion} cmc
        WHERE 1 = 1
            AND coursemoduleid = :cmid
            AND completionstate IN (" . implode(',', [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS]) . ")
    ";
    $cmcs = $DB->get_records_sql($sql, ['cmid' => $cm->id]);
    if (empty($cmcs)) {
        // Nothing to do with this activity.
        mtrace("Skipping activity: course $course->id cm $cm->id goone $goone->id : no cm completions (activity $counter of $totalgoone)", $eol);
        delete_orphaned_goone_completions($goone->id, []);
        continue;
    }
    $lmsuserids = [];
    foreach ($cmcs as $cmc) {
        $lmsuserids[$cmc->userid] = $cmc->userid;
    }

    // Get GO1 completions for the given lo_id.
    $gooneuserids = [];
    if (!empty($goonecompletions)) {
        foreach ($goonecompletions['hits'] as $hit) {
            if (($user = goone_get_moodle_user($hit['user_id'])) == false) {
                continue;
            }
            $gooneuserids[$user->id] = $user->id;
        }
    }
    $userids = array_diff($lmsuserids, $gooneuserids);
    if (sizeof($userids)) {
        foreach ($userids as $userid) {
            mtrace("Reverting: course $course->id cm $cm->id goone $goone->id user $userid (activity $counter of $totalgoone)", $eol);
            $completion->update_state($cm, COMPLETION_INCOMPLETE, $userid, $override = true);
        }
    } else {
        mtrace("Skipping activity: course $course->id cm $cm->id goone $goone->id : no difference (activity $counter of $totalgoone)", $eol);
    }

    delete_orphaned_goone_completions($goone->id, $lmsuserids);
}
mtrace("Bye.");
die(0);


/***************************************  Functions **************************************************** */

/**
 * Delete orphaned completion records from {goone_completion} - those that are not accompanied by course module completions.
 *
 * @param int $gooneid
 * @param array $lmsuserids - userids whose completions are not to be deleted
 *
 * @return void
 */
function delete_orphaned_goone_completions(int $gooneid, array $lmsuserids) :void {
    global $DB;

    $sql = "DELETE FROM {goone_completion}
        WHERE 1 = 1
        AND gooneid = :gooneid
        AND completed = 2
    ";
    if (!empty($lmsuserids)) {
        $lmsuseridsstr = implode(',', $lmsuserids);
        $sql .= "AND userid NOT IN ({$lmsuseridsstr})";
    }
    $DB->execute($sql, ['gooneid' => $gooneid]);
}