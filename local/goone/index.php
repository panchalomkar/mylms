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
 * Page for goone  integration to sync courses from go1 hub. *
 * when loads page get one time login url for embeding
 * 
 * @package    local_goone
 * @copyright  2022 Kalpana Patil
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->dirroot."/theme/remui/lib.php");
require_once(dirname(__FILE__).'/locallib.php');
require_login();
// Set the url.
$url = new moodle_url('/local/goone/go1courses.php');
$strtitle = get_string('goonecourses','local_goone');
$itemid = null; // Set this explicitly, so files for parent category should not get loaded in draft area.
$title = "$SITE->shortname: ".get_string('goonecourses','local_goone');
$fullname = $SITE->fullname;
$data=array();
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($fullname);

$goone_config 	= get_config('local_goone'); 
$trial_config 	= get_config('local_trial'); 
$a->settingurl =  $CFG->wwwroot.'/admin/settings.php?section=local_goone';
echo $OUTPUT->header();
echo $OUTPUT->heading($strtitle);
if(!empty($goone_config) && !empty($goone_config->enable_go1) && !empty($goone_config->go1clientid) && !empty($goone_config->go1clientsecretkey)){
	$urldata=get_go1_onetime_loginurl(); // curl call to get one time login url of go1 portal
	$data['login_url']=''; $data['show_sync']=1;
	$data['sync_url']=new moodle_url('/local/goone/import_courses.php');
	$data['download_url']=new moodle_url('/local/goone/import_courses.php?action=download');
	$data['redirect_url']=new moodle_url('/course');
	if(!empty($trial_config) && $trial_config->enable_trial==1){
		if($trial_config->local_trial_enable_goone_sync == 0)
			$data['show_sync'] =0;
	}
	if($urldata['status']=='ok'){
		$data['login_url']=$urldata['url'];
		echo $OUTPUT->render_from_template('local_goone/goone_courses', $data);
	}
	else{
		echo html_writer::start_div('',array('class' => 'alert alert-danger'));
		echo html_writer::label(get_string('error_text', 'local_goone',$a),array('style'=>'font-size:22px;'));
		echo html_writer::end_div();
	}
}else{
	echo html_writer::start_div('',array('class' => 'alert alert-danger'));
	echo html_writer::label(get_string('error_text', 'local_goone',$a),array('style'=>'font-size:22px;'));
	echo html_writer::end_div();
}

echo $OUTPUT->footer();