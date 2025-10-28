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
 * A drawer based layout for the remui theme.
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $PAGE, $COURSE;

require_once($CFG->dirroot . '/theme/remui/layout/common.php');
$templatecontext['slider'] = \theme_remui\Sitehomehandler::get_slider_data();
 
$coursecontext = context_course::instance($COURSE->id);
if (!is_guest($coursecontext, $USER) &&
    \theme_remui\toolbox::get_setting('enabledashboardcoursestats') &&
    $PAGE->pagelayout == 'mydashboard' && $PAGE->pagetype == 'my-index') {
      if (is_siteadmin($USER)) {
        $templatecontext['isdashboardstatsshow'] = true;
          $templatecontext['isuserdashboardstatsshow'] = true;
        
    }
    $setupstatus = get_config("theme_remui","setupstatus");
    if(get_config("theme_remui","dashboardpersonalizerinfo") == "show" && ( $setupstatus == "final" || $setupstatus == 'finished' )) {
        $templatecontext['showpersonlizerinfo'] = true;
    }
if (!is_siteadmin($USER)) {
    $templatecontext['userdashboard_stats'] = $OUTPUT->userdashboard_stats($USER);
}
}
// Only set slider on Dashboard (pagetype my-index).
if ($PAGE->pagetype === 'my-index') {
    $templatecontext['showslider'] = true;
    $templatecontext['slider'] = \theme_remui\Sitehomehandler::get_slider_data();
}


// Must be called before rendering the template.
// This will ease us to add body classes directly to the array.
require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
echo $OUTPUT->render_from_template('theme_remui/newdashboard', $templatecontext);
