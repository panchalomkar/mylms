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


namespace local_custom_notification\task;
use core\task\scheduled_task;
use local_custom_notification\helper;
/**
 * An example of a scheduled task.
 */
class course_expiration_notification extends \core\task\scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_custom_notification');
    }
    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB, $USER;
        
        helper::course_expiration_noti();

    }
}
