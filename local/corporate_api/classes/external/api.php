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
 * Reports ablock external apis
 *
 * @package     local_corporate_api
 * @copyright   2019 rajveer <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_corporate_api\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');
use external_api;

/**
 * Provides an external API of the block.
 *
 * Each external function is implemented in its own trait. This class
 * aggregates them all.
 */
class api extends external_api {
    use get_courses_detail;
    use get_learningpath_detail;
    use get_livesession_detail;
    use get_profile_detail;
    use get_course_content_detail;
    use get_course_report_detail;
    use get_user_report_detail;
}
