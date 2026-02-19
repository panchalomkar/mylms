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

 //  Check users session and profile settings to get the current editing company.
 if (!empty($SESSION->currenteditingcompany)) {
    $selectedcompany = $SESSION->currenteditingcompany;
} else if (!empty($USER->profile->company)) {
    $usercompany = company::by_userid($USER->id);
    $selectedcompany = $usercompany->id;
} else {
    $selectedcompany = "";
}

$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$email = $_POST['email'];
$username = $_POST['username'];
$pswd = $_POST['pswd'];
$getuserdata = $DB->get_record('user', array('username' => $username));
if ($getuserdata) {
    $message = "Username already exit";
}else{
    $record = new stdClass();
    $record->confirmed = '1';
    $record->mnethostid = '1';
    $record->timecreated = time();

    $record->firstname = $firstname;
    $record->lastname = $lastname;
    $record->username = $username;
    $record->email = $email;
    $record->password = hash_internal_user_password($pswd);
    $getuserid = user_create_user($record, false);
    if ($getuserid) {
      if ($selectedcompany) {
        $comrecord = new stdClass();
        $comrecord->companyid = $selectedcompany;
        $comrecord->userid = $getuserid;
        $comrecord->managertype = 0;
        $comrecord->departmentid = 1;
        $comrecord->suspended = 0;
        $comrecord->educator = 0;
        $DB->insert_record('company_users', $comrecord);
      }
    }
    $message = '<span class="text-success">User created Successfully</span>';
}

    echo $message;