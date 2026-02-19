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
 * Page for goone integration to sync courses from go1 hub.
 * when loads page get one time login url for embeding
 * 
 * @package    local_goone
 * @copyright  2022 Kalpana Patil
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->dirroot."/theme/paradiso/lib.php");
require_once(dirname(__FILE__).'/locallib.php');
require_login();
// Set the url.
$url = new moodle_url('/local/goone/import_courses.php');
$urldata=array();
$goone_config 	= get_config('local_goone'); 
$a->settingurl =  $CFG->wwwroot.'/admin/settings.php?section=local_goone'; 
$action= optional_param('action', 0, PARAM_INT);

if(!empty($goone_config) && !empty($goone_config->enable_go1) && !empty($goone_config->go1clientid) && !empty($goone_config->go1clientsecretkey)){
	if($action){
		$urldata=download_single_course_scorm();
	}
	else{
		$urldata=get_go1_learning_object_mylib();
	} 
}
echo json_encode($urldata);
exit();
//echo $OUTPUT->footer();