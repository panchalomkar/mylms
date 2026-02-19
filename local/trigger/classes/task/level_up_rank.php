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
 * A scheduled task for CAS user sync.
 *
 * @package    task
 * @copyright  2015 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trigger\task;

use \stdClass;

class level_up_rank extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('rankup', 'local_trigger');
    }

    /**
     * Run users sync.
     */
    public function execute() {
        global $DB, $CFG;

        //get current rank
//        $record = $DB->get_record('user_points', array('userid' => $userid));
//
//        $total_points = ($record->total_points > 0) ? $record->total_points : 0;
//
//        //get level up config
//        $level = $DB->get_record('block_xp_config', array('enabled' => 1));
//
//
//        include_once $CFG->dirroot . '/local/mydashboard/lib.php';
//        $eventdata = (object) $event->get_data();
//        $userid = $eventdata->userid;
//        $r_points = array(1, 2, 4, 0, 5, 7, 0, 10, 0, 12, 15, 0, 17, 18, 0, 20, 22, 25, 0, 27, 0, 30, 0);
//        //get the welcome points
//        $setting = get_config('local_mydashboard');
//        //number of scratch card added
//        $nos = $setting->rank_promote;
//        for ($i = 0; $i < $nos; $i++) {
//            //add scratch card
//            $k = array_rand($r_points);
//            $point = $r_points[$k];
//
//            $insert = new stdClass();
//
//            $insert->userid = $userid;
//            $insert->card_type = 'level_up';
//            $insert->point = $point;
//            $insert->redeemed = 0;
//            $insert->timecreated = time();
//
//            $DB->insert_record('user_scratchcard', $insert);
//        }
    }

}
