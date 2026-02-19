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
 * Strings for component 'local_competency', language 'en'     
 *
 * @package   local_competency
 * @copyright Daniel Neis <danielneis@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['localtring'] = 'local string';
$string['descconfig'] = 'Description of the config section';
$string['descfoo'] = 'Config description';
$string['headerconfig'] = 'Config section header';
$string['labelfoo'] = 'Config label';
$string['competency:addinstance'] = 'Add a competency local';
$string['competency:managemainheading'] = 'Manage a main heading competency';
$string['competency:uploadcompetency'] = 'Manage a Upload competency';
$string['competency:managesubcompetency'] = 'Manage a Sub competency';
$string['competency:managesubsubcompetency'] = 'Manage a Sub-Sub competency';
$string['competency:viewcompetency'] = 'View Performance';
$string['competency:competencyapproval'] = 'Approval competency for Users';
$string['competency:managerrating'] = 'Manage Manager rating for competency';
$string['competency:userselfrating'] = 'Manage User\'s rating for competency';
$string['competency:landdrating'] = 'Manage L and D Manager\'s rating for competency';
$string['competency:landdreport'] = 'Access L and D Manager\'s Report';
$string['competency:maangerreport'] = 'Access Manager\'s Report';
$string['pluginname'] = 'competency';
$string['competency_title'] = 'Main Competency';
$string['competency_category'] = 'Competency';
$string['competency_list'] = 'Competency List';
$string['sub_competency'] = 'Sub Competency';
$string['sub_sub_competency'] = 'Sub Sub Competency';
$string['approval'] = 'Approval';
$string['roles'] = 'Roles';
$string['bumaster'] = 'Business Unit';
$string['bumaster_help'] = 'Business Unit as users deparment';
$string['roles_help'] = 'Select Roles';
$string['competency_title_help'] = 'Select multiple competency title';
$string['competency_category_help'] = 'Select multiple competency name';
$string['competencies'] = 'Competency';
$string['competencies_help'] = 'Select multiple Competency name.';
$string['user'] = 'Users';
$string['user_help'] = 'Select Users to approval';
$string['managerrating'] = 'Manager Rating';
$string['upload_competency'] = 'Upload Competency';
$string['managerrating'] = 'Manager Rating';
$string['userselfrating'] = 'Users self Rating';
$string['managersrating'] = 'Managers Rating';
$string['studentsrating'] = 'Users Rating';
$string['managerfinalrating'] = 'Managers Final Rating';
$string['viewcompetency'] = 'View Performance';
$string['managerrating_subject'] = 'New Competency Assigned.';
$string['managerrating_body'] = 'Hi  {$a->firstname}, <br>
	Greetings<br> <br>

	Your manager {$a->managername} has aligned competencies and also shared the interim score of your competencies, request you to please go through the aligned competencies and rate yourself ASAP. <br> <br>

	For any query please connect with Team L&D. <br> <br>

	Best Regards, <br>
	Neha Sarswat Sharma <br>
	Sr. Manager – L&D <br>
';
$string['userselfrating_subject'] = 'Self Rating done by {$a->firstname}';
$string['userselfrating_body'] = 'Hi {$a->firstname}, <br>
		Greetings<br> <br>
	Your Team member had submitted their self-rating for the aligned competencies, request you to please go through the score and share the final scores ASAP. <br> <br>
	For any query please connect with Team L&D. <br> <br>
	

	Best Regards, <br>
	Neha Sarswat Sharma <br>
	Sr. Manager – L&D <br>
';

$string['managerfinalrating_subject'] = 'Final Rating';
$string['managerfinalrating_body'] = 'Hi {$a->firstname}, <br>
	Greetings<br> <br>
	Your manager had submitted the final rating for the aligned competencies, request you to please go through the scores shared.<br> <br>

	In case of any concern or for any query please connect with Team L&D.<br> <br>


	Best Regards, <br>
	Neha Sarswat Sharma <br>
	Sr. Manager – L&D <br>
';

$string['landdrating_subject'] = 'Final L & D Rating.';
$string['landdrating_body'] = 'Hi {$a->firstname}, <br>
	Greetings<br> <br>

	You have been aligned with a new training course by your BU Head/L&D Head, request you to please start with the program. <br> <br>
	For any query please connect with Team L&D. <br> <br>

	Best Regards, <br>
	Neha Sarswat Sharma <br>
	Sr. Manager – L&D <br>
';

$string['tolandd_subject'] = 'Final L & D Rating.';
$string['tolandd_body'] = 'Hi {$a->firstname}, <br>
	Greetings<br> <br>

	{$a->managername} of {$a->department} has given Final rating for his Team member {$a->userselected}. You may give your final approval on the system upon which users will be able to see the approved score and rejected scores will be re-rated by the Manger. <br> <br>

	Best Regards, <br>
	Team – L&D   <br>
';

$string['finalscorerejected_subject'] = 'Final Score of User’s Rejected by L&D Team';
$string['finalscorerejected_body'] = 'Hi {$a->firstname}, <br>

	The L&D Team has rejected the rating for your Team member {$a->userself}. Please revisit the LMS and give your rating for the rejected competency. <br> <br>

	Best Regards, <br>
	Team – L&D  <br>
';

$string['rerating_subject'] = 'Re-Rating Given by Manager Name of {$a->department}';
$string['rerating_body'] = 'Hi {$a->firstname}, <br>

	{$a->managername} of {$a->department} has re-rated the competency of his Team member {$a->userselected}. Please give your final approval for users to see their final scores.<br> <br>

	Best Regards, <br>
	System Admin<br>
';

$string['finalrating'] = 'Final Rating';
$string['viewcompetency'] = 'View Performance';
$string['landdrating'] = 'L and D Rating';
$string['landdratingstatus'] = 'Status';
$string['userwisereport'] = 'Users Reports';
$string['landdpreviousrating'] = 'Previous Rating';
$string['landdratingcompletingstatus'] = 'Course Completion Status';
$string['userreport'] = 'User Report';
$string['managerwisereport'] = 'Manager Report';
$string['landdstatuslabel'] = 'L and D Status';
$string['managerratestate'] = 'Re-rated Status';
