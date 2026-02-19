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
 * Plugin strings are defined here.
 *
 * @package     local_people
 * @category    string
 * @copyright   2018 Daniel Carmona <daniel.carmona@paradisosolutions.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Manage Users';

/*
* add new strings for people.php screen
* @author Jorge Botero
* @since Jun 16 of 2016
* @paradiso
*/
$string['people'] = 'People';
$string['createnewuser'] = 'Create New User';
$string['peopledesc'] = 'An admin can perform various tasks relating to user accounts.';
$string['edtcatdesc'] = 'Fill the required fields to create new course categories. Use the parent category dropdown to organize them in main and subcategories.';
$string['firstname'] = 'First name';
$string['fullname'] = 'Full Name';
$string['lastname'] = 'Last name';
$string['userfullname'] = 'Full name';
$string['email'] = 'Email address';
$string['city'] = 'City/town';
$string['skill'] = 'Skill';
$string['position'] = 'Position';
$string['level'] = 'Level';
$string['country'] = 'Country';
$string['confirmed'] = 'Confirmed';
$string['suspended'] = 'Suspended account';
$string['systemrole'] = 'System role';
$string['courserole'] = 'Course';
$string['firstaccessd'] = 'First access';
$string['lastaccessed'] = 'Last access';
$string['neveraccess'] = 'Never access';
$string['lastmodified'] = 'Last Modified';
$string['nevermodified'] = 'Never Modified';
$string['username'] = 'Username';
$string['auth'] = 'Authentication';
$string['selectyes'] = 'Yes';
$string['selectno'] = 'No';
$string['search'] = 'Apply';
$string['dateisafter'] = 'Between';
$string['dateisbefore'] = 'and';
$string['anyvalue'] = 'Any value...';
$string['require_fields'] = 'Fill the required fields to continue.';
$string['company_choose'] = 'Entire LMS Peoples';
$string['company_without'] = 'Main tenant only';
$string['company_all'] = 'Tenants';
$string['company_all_excluding_main'] = 'All tenants excluding main Tenant';
$string['require_fields'] = 'Fill the required fields to continue.';
$string['field_required'] = 'Required';
$string['totalrecords'] = 'Total Record Found: {$a}';
$string['loginas'] = 'Login as';
$string['edit'] = 'Edit';
$string['courseenrolled'] = 'Course Enrolled';
$string['dept'] = 'Department';
$string['coursecompleted'] = 'Course Completed';
$string['bulk_delete'] = 'Delete';
$string['bulk_download'] = 'Export';
$string['bulk_send_message'] = 'Send a Message';
$string['bulk_upload'] = 'Upload';
$string['bulk_add_cohort'] = 'Add to Cohort';
$string['lastaccess'] = 'Last access';
$string['no_users'] = 'Nothing to display.';
$string['new_user'] = 'New User';
$string['export_user_description'] = 'Click on following links to download';
$string['people_filters'] = 'Filters';

$string['Search...'] = 'Search...';
$string['enrol_users']= 'Enroll users';
/**
* Strings for handle suspend and unsuspend page
* @author Esteban E.
* @since September 30 of 2016
* @paradiso
*/
$string['bulk_users_suspend'] = 'Suspend users ';
$string['bulk_users_unlock'] = 'Enable users ';
$string['bulk_user_suspend'] = 'Suspend user ';
$string['bulk_user_unlock'] = 'Unsuspend user ';
$string['suspend'] = 'Suspend ';
$string['enable'] = 'Enable';
$string['users_unsuspended'] = 'Users unsuspended: ';
$string['users_suspended'] = 'The {$a} user is successfully suspended.';
$string['users_suspended1'] = 'The {$a} users are successfully suspended.';
$string['users_cant_be_suspended'] = 'The {$a} User cant be suspended: ';

$string['recordsperpage'] = 'Records Per Page: ' ;
$string['messages'] = 'Messages' ;

$string['Clear_all_filter'] = 'Clear filters' ;

/**
* String for invite people
* @author C Alcaraz
* @since  March 27/2018
*/
$string['invite']           = 'Invite';
$string['invite_title']     = 'Message';
$string['invite_message']   = 'Send invitation to ';
$string['invitation_sent']  = 'Invitation sent';
$string['send_invite']      = 'Send';
$string['users_not_found']  = 'User not found. Please enter a user name, first name, last name or a valid email address.';
$string['mail_subject_invite']  = 'You have an invite from {platform_name}';
$string['mail_template_invite'] = 'Hello! <br> You have been invited to join to {platform_name}!<br /><br /> Accept the invitation clicking on the following link: {register_url} <br />Username: {email}<br>Password: {password}<br> ';
$string['invite_error']     = 'There was an error while sending the invitation. Try again later.';

$string['clear'] = 'clear';

$string['cohort_title']= 'Cohorts, or site-wide groups, enable all members of a cohort to be enrolled in a course in one action, either manually or synchronised automatically';
$string['enrol_users']= 'Enroll users';
$string['custom_profile_fields']= 'Custom  profile fields';
$string['custom_profile_fields_title']= 'Administrators can create new Custom profile categories and Custom profile fields may be a menu of choices, text area, text input or a checkbox and may be required or not.!' ;
$string['enrol_title']= 'Please select user(s) from the list';
$string['create_user_title']= 'An administrator or manager (or any other user with the capability to create user ) can create new user accounts ';
$string['enrol_error'] = 'Unable to process the enrollment request this moment';
$string['role'] = 'Role';
$string['course_select_help'] = ' You can select the multiple course with help of shift key.';
$string['courses'] = 'Courses';
$string['startdate'] = 'Start date';
$string['enddate'] = 'End date';
$string['enroll'] = 'Enroll';
$string['success_message'] = 'The selected user(s) have been succesfully enrolled in the respective course(s)';
$string['main'] = 'Main';
$string['enrol_alert_message'] = 'Please select user(s) from the list';
$string['plms_support'] = 'LMS Support';


$string['invalid_users'] = 'The users selected are not longer supported';
$string['invalid_cohorts'] = 'There are no available cohorts to select';
$string['cohortlist'] = 'Cohort list';
$string['error_cohortadd'] = 'Error ocurred while adding members to the Cohort';
$string['success_cohortadd'] = 'Users added to the Cohort Sucessfully.';
$string['error_message'] = 'Error ocurred while sending the message';
$string['success_message_users'] = 'Message sent Sucessfully.';
$string['warning_fields'] = 'You must fill the inputs before applying the action.';
$string['people:suspenduser'] = 'Suspend User';
$string['people:viewallusers'] = 'User can view all users page';
$string['cannotviewallusers'] = 'Sorry, you can access people page!';
$string['info_confirm_user'] = 'Confirmed!';
$string['send_email_confirmation_again'] = 'Send Confirmation email again!';
$string['multitenant'] = 'Multitenant';
$string['multitenant_record'] = 'Show Multitenant records';
$string['invalidsesskey'] = "Invalid Sesskey";
$string['space'] = "  ";
$string['filter'] = "Filter";
$string['apply_filter'] = "Apply Filter";
$string['deleteconfirm'] = 'The {$a} user is successfully deleted.';
$string['deleteconfirm1'] = 'The {$a} users are successfully deleted.';
$string['status'] = "Status";