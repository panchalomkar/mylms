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
 * @author Matthias Schwabe <support@eledia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package coursewizard
 */

$string['addusers'] = 'Add users to this course';
$string['addusers_button'] = 'Add users';
$string['addusers_desc'] = 'You can enroll users to your course by adding their e-mail addresses here.<br>
							For e-mail adresses which ar not known in your LMS system,
							a new user will be created and automatically enroled into the course.<br>
							New users will get an e-mail with login data.';
$string['backbutton'] = 'Back to course';
$string['backbutton_cancel'] = 'Cancel and back to course';
$string['backbutton_create2'] = 'Skip user enrollment and go to new course';
$string['backbutton_form'] = 'Back to course wizard';
$string['coursewizard_desc'] = 'Here you can edit the e-mail, which a new created user will get.';
$string['coursewizard_mailcontent'] = 'Someone has created a new LMS account for you.';
$string['coursewizard_mailcontent_desc'] = 'Content of e-mail to new user';
$string['coursewizard_mailcontent_notnew'] = 'Someone has enrolled you into a new LMS course.';
$string['coursewizard_mailcontent_notnew_desc'] = 'Content of e-mail to existing user';
$string['coursewizard_mailsubject'] = 'Your new LMS account';
$string['coursewizard_mailsubject_desc'] = 'Subject of e-mail to new user';
$string['coursewizard_mailsubject_notnew'] = 'New course at LMS';
$string['coursewizard_mailsubject_notnew_desc'] = 'Subject of e-mail to existing user';
$string['createcourse'] = 'Create a<br> course';
$string['createuser_button'] = 'Add users and go to new course';
$string['createuser_desc'] = 'You can enroll users to your new course by adding their e-mail addresses here.<br>
							  For e-mail adresses which ar not known in your LMS system,
							  a new user will be created and automatically enroled into the new course.<br>
							  New users will get an e-mail with login data.';
$string['coursewizard:addinstance'] = 'Add new eLeDia Course wizard block';
$string['coursewizard:change_category'] = 'Change the category for new course created with the course wizard block';
$string['coursewizard:create_course'] = 'Create a course with the course wizard block';
$string['coursewizard:create_user'] = 'Create users with the course wizard block';
$string['emailuser'] = 'E-mail addresses of new users:<br>(separated by comma)';
$string['invalidemail'] = '{$a} is not a valid e-mail address.<br>';
$string['norights'] = 'You have no rights to do this.';

$string['pluginname'] = 'Course wizard';
$string['drag_drop_images'] = 'Drag & Drop Images Here';
$string['or'] = '- Or -';
$string['click_open_file_browser'] = 'Click to open the file Browser';
$string['selected_file'] = 'Selected file';
$string['status'] = 'Status: <span class="status">Waiting</span>';
$string['uploading'] = 'Uploading...';
$string['upload_complete'] = 'Upload Complete';
$string['has_not_allowed_extencion'] = 'has a Not Allowed Extension';
$string['file'] = 'File';
$string['browser_not_supported'] = 'Browser not supported(do something else here!): ';
$string['next_button'] = 'Next';
$string['save_changes_button'] = 'Save changes';
$string['advanced_settings'] = 'Advanced settings';


$string['course_category'] = 'Course Category';
$string['course_name'] = 'Course Name';
$string['course_description'] = 'Course Description';
$string['course_image'] = 'Course Image';

$string['previous'] = 'Previous';
$string['next'] = 'Next';
$string['finish'] = 'Finish';


/**
* Add string for default activities
* @author Andres Ag.
* @since October 19 of 2015
* @paradiso
*/
$string['default_advanced_forum'] = 'Discussion  Forum';
$string['default_additional_materials'] = 'Reading Materials';

/**
* Add string for validation
* @author Andres Ag.
* @since October 28 of 2015
* @paradiso
*/
$string['name_required_text'] = 'The Course Name is required';
$string['name_is_already'] = 'The name is already being used by another course';

$string['upload'] = 'Upload';


$string['course_format'] = 'Course Format';
$string['paradisotabs'] = 'Format Tabs';
$string['topics'] = 'Topics';
$string['grid'] = 'Grid';
$string['weeks'] = 'Weekly';
$string['social'] = 'Social';
$string['singleactivity'] = 'One activity';

/**
* Add string for Coures type creartion Wizard
* @author Alok Kumar
* @since November 30 of 2015
* @paradiso
*/
$string['blended'] = 'Blended';
$string['selfpaced'] = 'Self Paced';
$string['classroom'] = 'Classroom Training';
$string['virtualclass'] = 'Virtual Class';
$string['coursetype'] = 'Course Type';
$string['default_facetoface'] = 'ILT/Classroom';

/**
* Add string for maximum_size
* @author Andres Ag.
* @since Dic 30 of 2015
* @paradiso
*/
$string['maximum_size'] = 'Upload image file formats: .JPG, .GIF, .PNG, the recommended size is 300 x 168 px.';


$string['blended_help'] = 'Use mix of Instructor Led Training (ILT) or Live Virtual<br> Classroom along with online Learning to create<br> complete learning experiences.';
$string['virtualclass_help'] = 'Conduct training in Virtual/online classrooms<br> and handle registration, cancellation, attendance,<br> single sign on(SSO) etc in the LMS';
$string['classroom_help'] = 'Conduct training only in classrooms but use <br>LMS to handle registration, cancellation, attendance,<br> room reservations etc';
$string['selfpaced_help'] = 'Use videos, scorm courses, pdf, quizzes and<br> many other activities to offer completely online <br>learning experiences.';


//Ayaj mulani string added for image validation
$string['head_val'] = 'Wrong File';
$string['dis_val'] = 'Only are allowed images jpg, png or gif files.';
//Ayaj Mulani String aaded For Heading Create a Course
$string['courese_heading'] = 'Create a Course';
$string['maximum_size_file_upload'] = 'Maximum image size: ';
$string['more_options'] = 'More options';
$string['less_options'] = 'Less options';

$string['file_size_exceed'] = 'Please upload image less than {$a} size';
/**
* Add string for default_feedback 
* @author Alok Kumar
* @since June 16 of 2016
* @paradiso
*/
$string['default_feedback'] = 'Feedback Survey';

/**
* 1273 Create a restriction characters in courses
* @author Jonatan Uribe
* @since oct 24 of 2017
* @paradiso
*/
$string['maximum_character_desc'] = '(Maximum characters {$a})';
$string['maximum_character_course_name'] = '(Maximum characters {$a})';
$string['maximum_character_course_name_message'] = '(Maximum characters {$a})';
$string['maximum_character_course_name_message'] = 'Only {$a} characters are allowed for the course name.';

$string['requiredfield'] = 'The field {$a} is required to continue.';
$string['course_overview'] = 'Course Overview';
$string['course_overview_general_info'] = 'Add general information';
$string['course_overview_image'] = 'Add Course Image';
$string['course_overview_image_text'] = 'Upload or select an image for your course';
$string['select_image'] = 'Select an image';
$string['select_category'] = 'Choose a category';
$string['missingcoursecategory'] = 'Please select category';

$string['created_newcourse'] = 'Create New Course';
$string['description_newcourse'] = 'To create a course, please go through each step and complete the required fields.';
$string['add_image'] = 'Add an image';
$string['name_example'] = 'Name Example';
$string['dndenabledtheme_inbox'] =  'Image file formats: JPG, GIF, PNG, </br>
Recommended size  300 x 198 px. </br>
Maximum image size: 5.00 MB';