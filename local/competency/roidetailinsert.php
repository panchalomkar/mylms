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
* @package local_mydashboard
* @category local
* @copyright  ELS <admin@elearningstack.com>
* @author eLearningstack
*/
require_once('../../config.php');
require_once($CFG->dirroot.'/user/lib.php');
global $USER, $DB, $SITE, $PAGE, $SESSION;

$department = $_POST['department'];
$totalbudegt = $_POST['totalbudegt'];
$trainingbudegt = $_POST['trainingbudegt'];
$maincomid = $_POST['subcomid'];
$noemployeeid = $_POST['noemployeeid'];
$budegtperempt = $_POST['budegtperempt'];
$ROIoverperiodid = $_POST['ROIoverperiodid'];
$roiperiodstartdate = $_POST['roiperiodstartdate'];
$roiperiodenddate = $_POST['roiperiodenddate'];
$getuserdata = $DB->get_record('roi_calculate_form', array('maincomid' => $maincomid));
if ($getuserdata) {
    $record = new stdClass();
    $record->id = $getuserdata->id;
    $record->maincomid = $maincomid;
    $record->trainingbudegt = $trainingbudegt;
    $record->department = $department;

    $record->totalbudegt = $totalbudegt;
    $record->noemployeeid = $noemployeeid;
    $record->budegtperempt = $budegtperempt;
    $record->duration = $ROIoverperiodid;
    $record->startdate = $roiperiodstartdate;
    $record->enddate = $roiperiodenddate;
    $record->timecreated = time();
    $getuserid = $DB->update_record('roi_calculate_form', $record);
    $message = '<span class="text-success">User updated Successfully</span>';
}else{
    $record = new stdClass();
    $record->maincomid = $maincomid;
    $record->trainingbudegt = $trainingbudegt;
    $record->department = $department;

    $record->totalbudegt = $totalbudegt;
    $record->noemployeeid = $noemployeeid;
    $record->budegtperempt = $budegtperempt;
    $record->duration = $ROIoverperiodid;
    $record->startdate = $roiperiodstartdate;
    $record->enddate = $roiperiodenddate;
    $record->timecreated = time();
    $getuserid = $DB->insert_record('roi_calculate_form', $record);
    
    $message = '<span class="text-success">User created Successfully</span>';
}

    echo $message;