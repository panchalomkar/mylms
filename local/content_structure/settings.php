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
 * This file contains plugin settings.
 *
 * @package    local_question_grade
 * @author     Uvais
 * @copyright  2012 Isuru Madushanka Weerarathna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once 'lib.php';
if ($hassiteconfig) {

    require_once($CFG->dirroot.'/local/content_structure/lib.php');

    $settings = new admin_settingpage('local_question_grade', get_string('pluginname', 'local_content_structure'));
    $ADMIN->add('localplugins', $settings);
    
$setting = new admin_setting_configtext('gallerynumberofboxes', get_string('displaynumber', 'local_content_structure'),
            new lang_string('settingdescription', 'local_content_structure'), 10, PARAM_INT, 100);

}