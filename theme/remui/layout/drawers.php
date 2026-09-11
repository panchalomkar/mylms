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

$coursecontext = context_course::instance($COURSE->id);
global $PAGE, $SESSION, $DB, $CFG;
require_once($CFG->dirroot . '/local/iomad/lib/iomad.php');
 
$layout_company_id = 0;
if (isset($SESSION->currenteditingcompany)) {
    $layout_company_id = $SESSION->currenteditingcompany;
} else if (iomad::is_company_user() || iomad::is_company_admin()) {
    $layout_company_id = iomad::is_company_user();
} else if (function_exists('get_company_by_host') && get_company_by_host() > 0) {
    $layout_company_id = get_company_by_host();
}
 
// ── STEP 2: Get tenant favicon URL ─────────────────────────────────────────
$tenant_favicon_url = '';
if ($layout_company_id) {
    $context = context_system::instance();
    $fs = get_file_storage();
 
    $fav_files = $fs->get_area_files(
        $context->id,
        'theme_remui',
        'tenant_favicon_' . $layout_company_id,
        0,
        'itemid, filepath, filename',
        false
    );
 
    if (!empty($fav_files)) {
        $fav_file = reset($fav_files);
        $tenant_favicon_url = moodle_url::make_pluginfile_url(
            $fav_file->get_contextid(),
            $fav_file->get_component(),
            $fav_file->get_filearea(),
            $fav_file->get_itemid(),
            $fav_file->get_filepath(),
            $fav_file->get_filename()
        )->out(false);
    }
}
 
if (!is_guest($coursecontext, $USER) &&
    \theme_remui\toolbox::get_setting('enabledashboardcoursestats') &&
    $PAGE->pagelayout == 'mydashboard' && $PAGE->pagetype == 'my-index') {
    $templatecontext['isdashboardstatsshow'] = true;
    $setupstatus = get_config("theme_remui","setupstatus");
    if(get_config("theme_remui","dashboardpersonalizerinfo") == "show" && ( $setupstatus == "final" || $setupstatus == 'finished' )) {
        $templatecontext['showpersonlizerinfo'] = true;
    }
}

// Must be called before rendering the template.
// This will ease us to add body classes directly to the array.
require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
echo $OUTPUT->render_from_template('theme_remui/drawers', $templatecontext);
