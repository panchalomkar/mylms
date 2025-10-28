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
 * Library functions.
 *
 * @package    ads_management
 * @author     Uvais
 * @copyright  2012 Isuru Madushanka Weerarathna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Function to render settings customization per course.
 *
 * @param object $settingsnav settings navigation.
 * @param object $context current context.
 * @return void.
 */
function local_scheduler_extend_navigation(global_navigation $nav) {
    global $PAGE, $CFG, $DB, $USER;

    $teacher = false;
    if (has_capability('local/scheduler:view', context_system::instance())) {

        $SQL = "SELECT * FROM {role} r INNER JOIN {role_assignments} ra ON ra.roleid = r.id 
                 WHERE r.shortname IN ('teacher', 'editingteacher') AND ra.userid = $USER->id";
        if ($DB->record_exists_sql($SQL)) {
            $teacher = true;
        }

        if (is_siteadmin()) {
            $url = new moodle_url('/local/scheduler/report.php');
            $node = $nav->add(get_string('slotscheduler', 'local_scheduler'), $url, 15, 'ads', 'ads',new pix_icon('i/calendar', ''));
        } else if ($teacher) {
            $url = new moodle_url('/local/scheduler/table.php');
            $node = $nav->add(get_string('addscheduler', 'local_scheduler'), $url, 15, 'ads', 'ads',new pix_icon('i/calendar', ''));
        } else {
            $url = new moodle_url('/local/scheduler/book.php');
            $node = $nav->add(get_string('bookscheduler', 'local_scheduler'), $url, 15, 'ads', 'ads',new pix_icon('i/calendar', ''));
        }
        $node->showinflatnavigation = false;
    }
}
