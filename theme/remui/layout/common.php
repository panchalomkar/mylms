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
 * A two column layout for the remui theme.
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_remui\utility;

defined('MOODLE_INTERNAL') || die();

require_once ($CFG->libdir . '/behat/lib.php');
require_once ($CFG->dirroot . '/course/lib.php');

global $PAGE, $USER, $DB;

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();



//set_user_preference('dark-mode', 'false');
if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
    // Always pinned for quiz and book activity.
    $activities = array("book", "quiz");
    if (isset($PAGE->cm->id) && in_array($PAGE->cm->modname, $activities)) {
        $blockdraweropen = true;
    }
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING')) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}
$hasCompanyClass = rap_has_company_id();
if ($hasCompanyClass != "") {
    $extraclasses[] = $hasCompanyClass;
}
if($PAGE->pagelayout == 'mydashboard' && $PAGE->pagetype == 'my-index') {
  $isdashboard=true;
}else{$isdashboard=false;}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$extraclasses[] = \theme_remui\utility::get_main_bg_class();

// Focus data.
$coursehandler = new \theme_remui_coursehandler();
$focusdata = $coursehandler->get_focus_context_data();
if (isset($focusdata['on']) && $focusdata['on']) {
    $extraclasses[] = 'focusmode';
}

$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);

// Recent Courses Menu.
if (isloggedin()) {
    $primarymenu = \theme_remui\utility::get_recent_courses_menu($primarymenu);
}

// Course Categories Menu.
$primarymenu = \theme_remui\utility::get_coursecategory_menu($primarymenu);

// Login Menu Addition.
if (!isloggedin() && \theme_remui\toolbox::get_setting('navlogin_popup')) {
    $primarymenu = \theme_remui\utility::get_login_menu_data($primarymenu);
}
// Here we Add extra icons to profile dropdown menu.
if (isloggedin() && !isguestuser()) {
    $primarymenu = \theme_remui\utility::add_profile_dropdown_icons($primarymenu);
}

// Init product notification configuration.
if ($notification = \theme_remui\utility::get_inproduct_notification()) {
    $templatecontext['notification'] = $notification;
}

// Customizer fonts.
$customizer = \theme_remui\customizer\customizer::instance();
$fonts = $customizer->get_fonts_to_load();

$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);
$lcontroller = new \theme_remui\controller\LicenseController();

$homepagedepricationmodal = '';
if (isloggedin() && is_siteadmin() && is_plugin_available('local_remuihomepage')) {
    if (!get_user_preferences('homepagedepricatedseen')) {
        // $homepagedepricationmodal = \theme_remui\utility::get_homepage_depriation_modal();
         $homepagedepricationmodal = ''; // fallback empty
    }
}
if (isloggedin()) {
    $islogdin = 1;
} else {
    $islogdin = 1;
}

if (is_siteadmin()) {
    $admin = true;
    $mycourseurl = $CFG->wwwroot . "/course";
} else {
    $admin = false;
    $mycourseurl = $CFG->wwwroot . "/course";
}
$roles = $DB->get_record_sql("SELECT * FROM mdl_role_assignments WHERE userid = $USER->id");
$getmanager = $DB->get_record('company_users', array('userid' => $USER->id));
$CFG->wwwroot . '/login/logout.php?sesskey=' . sesskey();
global $CFG, $USER;

$templatecontext = [
    'logouturl' => $CFG->wwwroot . '/login/logout.php?sesskey=' . sesskey(),
    // ... other context vars
];

$adminurl = [
    'dashboard' => $CFG->wwwroot . "/my",
    'inbox' => $CFG->wwwroot . "/message/index.php",
    'mycourse' => $mycourseurl,
    'mygrade' => $CFG->wwwroot . "/grade/report/overview/index.php",
    'managecohort' => $CFG->wwwroot . "/cohort/index.php",
    'leaderboard' => $CFG->wwwroot . "/blocks/xp/index.php/ladder/1",

    'myteam' => $CFG->wwwroot . "/local/my_team/index_1.php",
    'learningpath' => $CFG->wwwroot . "/local/learningpaths",
    'adminsettings' => $CFG->wwwroot . "/admin/search.php",
    'multitenant' => $CFG->wwwroot . "/local/mt_dashboard/index.php?company=0&tabid=&showsuspendedcompanies=/",
    'logouturl' => $CFG->wwwroot . "/login/logout.php?sesskey=" . $USER->sesskey,
    'tenantcontrol' => $CFG->wwwroot . "/local/tenant_control/index.php",
    //sub menu url
    'createcompany' => $CFG->wwwroot . "/blocks/iomad_company_admin/company_edit_form.php?createnew=1",
    'editcompany' => $CFG->wwwroot . "/blocks/iomad_company_admin/company_edit_form.php",
    'managecompanies' => $CFG->wwwroot . "/blocks/iomad_company_admin/editcompanies.php",
    'managedepartments' => $CFG->wwwroot . "/blocks/iomad_company_admin/company_departments.php",
    'createcourses' => $CFG->wwwroot . "/local/coursewizard/createcourse.php?cid=1&category=0",
    'managecourses' => $CFG->wwwroot . "/course/management.php",
    'addcourse' => $CFG->wwwroot . "/course/edit.php?category=2&returnto=category",
    'coursecatalog' => $CFG->wwwroot . "/course/index.php",
    'addcategories' => $CFG->wwwroot . "/course/editcategory.php?parent=0",
    'addcompetency' => $CFG->wwwroot . "/admin/tool/lp/editcompetencyframework.php?pagecontextid=1",
    'addlearningplan' => $CFG->wwwroot . "/admin/tool/lp/edittemplate.php?pagecontextid=1",
    'manageusers' => $CFG->wwwroot . "/local/people/",
    'managecohort' => $CFG->wwwroot . "/cohort/index.php",
    'learningplan' => $CFG->wwwroot . "/admin/tool/lp/learningplans.php?pagecontextid=1",
    'report&analytics' => $CFG->wwwroot . "/local/edwiserreports/index.php",
    'builder' => $CFG->wwwroot . "/reportbuilder/index.php",
    'sandileader' => $CFG->wwwroot . "/local/mydashboard/index.php",
    'smeleaders' => $CFG->wwwroot . "/local/mydashboard/smeleader_report.php",
    'automationhub' => $CFG->wwwroot . "/local/enroll_by_profile/",
    'branding' => $CFG->wwwroot . "/theme/remui/customizer.php",
    'editmenu' => $CFG->wwwroot . "/blocks/customnavigation/structure.php",
    'contentvault' => $CFG->wwwroot . "/local/content_structure/",
    'performance' => $CFG->wwwroot . "/local/competency/viewcompetency.php",
    'calender' => $CFG->wwwroot . "/calendar/view.php",
];
$userurl = [
    'dashboard' => $CFG->wwwroot . "/my",
    'mycourse' => $mycourseurl,
    'mygrade' => $CFG->wwwroot . "/grade/report/overview/index.php",
    'calender' => $CFG->wwwroot . "/calendar/view.php",
    'sandileader' => $CFG->wwwroot . "/local/mydashboard/index.php",
    'smeleaders' => $CFG->wwwroot . "/local/mydashboard/smeleader_report.php",
    'leaderboard' => $CFG->wwwroot . "/blocks/xp/index.php/ladder/1",
    'logouturl' => $CFG->wwwroot . "/login/logout.php?sesskey=" . $USER->sesskey,
    'performance' => $CFG->wwwroot . "/local/competency/userselfrating.php",
    'report&analytics' => $CFG->wwwroot . "/local/edwiserreports/index.php",
    // 'mycourse' => $CFG->wwwroot . "/my/courses.php",
];

$managerurl = [
    'dashboard' => $CFG->wwwroot . "/my",
    'mycourse' => $mycourseurl,
    'leaderboard' => $CFG->wwwroot . "/blocks/xp/index.php/ladder/1",
    'learningpath' => $CFG->wwwroot . "/local/learningpaths",
    'sandileader' => $CFG->wwwroot . "/local/mydashboard/index.php",
    'smeleaders' => $CFG->wwwroot . "/local/mydashboard/smeleader_report.php",
    'report&analytics' => $CFG->wwwroot . "/local/edwiserreports/index.php",
    'calender' => $CFG->wwwroot . "/calendar/view.php",
    'multitenant' => $CFG->wwwroot . "/local/mt_dashboard/index.php?company=0&tabid=&showsuspendedcompanies=/",
    'createuser' => $CFG->wwwroot . "/blocks/iomad_company_admin/company_user_create_form.php",
    'edituser' => $CFG->wwwroot . "/blocks/iomad_company_admin/editusers.php",
    'createcourse' => $CFG->wwwroot . "/blocks/iomad_company_admin/company_course_create_form.php",
    'users_Assign_To_Company' => $CFG->wwwroot . "/blocks/iomad_company_admin/company_users_form.php",
    'Userenrollments' => $CFG->wwwroot . "/blocks/iomad_company_admin/company_course_users_form.php",
    'ManageCourses' => $CFG->wwwroot . "/blocks/iomad_company_admin/iomad_courses_form.php",
    'Course_Assign_To_Company' => $CFG->wwwroot . "/blocks/iomad_company_admin/company_courses_form.php",
    'bulkuploaduser' => $CFG->wwwroot . "/blocks/iomad_company_admin/uploaduser.php",
    'Reports' => $CFG->wwwroot . "/local/report_users/index.php",
    'performance' => $CFG->wwwroot . "/local/competency/mainheading.php",
    'logouturl' => $CFG->wwwroot . "/login/logout.php?sesskey=" . $USER->sesskey,
];

$teacherrurl = [
    'dashboard' => $CFG->wwwroot . "/my",
    'mycourse' => $mycourseurl,
    'mygrade' => $CFG->wwwroot . "/grade/report/overview/index.php",
    'calender' => $CFG->wwwroot . "/calendar/view.php",
    'sandileader' => $CFG->wwwroot . "/local/mydashboard/index.php",
    'smeleaders' => $CFG->wwwroot . "/local/mydashboard/smeleader_report.php",
    'leaderboard' => $CFG->wwwroot . "/blocks/xp/index.php/ladder/1",
    'logouturl' => $CFG->wwwroot . "/login/logout.php?sesskey=" . $USER->sesskey,
    'report&analytics' => $CFG->wwwroot . "/local/edwiserreports/index.php",
];


if (is_siteadmin()) {
    $sidebarurldata = [
        'admin' => $adminurl,
    ];

} else if ($getmanager->managertype == 1) {
    $manager = true;
    $sidebarurldata = [
        'manager' => $managerurl,
    ];
} else if ($roles->roleid == 3) {
    $teacher = true;
    $sidebarurldata = [
        'teacher' => $teacherrurl,

    ];
} else {
    $sidebarurldata = [
        'student' => $userurl,
    ];
}
$showpointonheader = get_config('local_mydashboard', 'showpointonheader');


$getsql = " SELECT lxl.*, SUM(points) as pointsss FROM {local_xp_log} lxl WHERE lxl.userid = $USER->id Group by lxl.userid";
$get_details = $DB->get_record_sql($getsql);
if ($showpointonheader == 1) {
    if ($get_details->pointsss) {
        $point = $get_details->pointsss;
    } else {
        $point = 0;
    }
    $showarra = ['userxppoint' => $point];
}


$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'fonts' => $fonts,
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'isdashboard'=>$isdashboard,
    'show_license_notice' => \theme_remui\utility::show_license_notice(),
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'usernamedis' => $USER->firstname . ' ' . $USER->lastname,
    'sidebarurl' => $sidebarurldata,
    'showstatus' => $showarra,
    'showadmin' => $admin,
    'manager' => $manager,
    'createcourses' => $CFG->wwwroot . "/local/coursewizard/createcourse.php?cid=1&category=0",
    'managecourses' => $CFG->wwwroot . "/course/management.php",
    'addlearningplan' => $CFG->wwwroot . "/admin/tool/lp/edittemplate.php?pagecontextid=1",
    'addcompetency' => $CFG->wwwroot . "/admin/tool/lp/editcompetencyframework.php?pagecontextid=1",
    'addcategories' => $CFG->wwwroot . "/course/editcategory.php?parent=0",
    'assignskill' => $CFG->wwwroot . "/blocks/skilladd/add_by_admin.php",
    'skilladdurl' => $CFG->wwwroot . "/local/edwiserform/preview.php?id=12",
    'edwiserform' => $CFG->wwwroot . "/local/edwiserform/view.php?page=listforms",
    'goone' => $CFG->wwwroot . "/local/goone/",
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
    'isloggedin' => isloggedin(),
    'footerdata' => \theme_remui\utility::get_footer_data(),
    'cansendfeedback' => (is_siteadmin()) ? true : false,
    'feedbacksender_emailid' => isset($USER->email) ? $USER->email : '',
    'feedback_loading_image' => $OUTPUT->image_url('a/loading', 'core'),
    'licensestatus_forfeedback' => ($lcontroller->get_data_from_db() == 'available') ? 1 : 0,
    'homepagedepricationmodal' => $homepagedepricationmodal,
    'activetenant' => show_active_tenant_menu()
];

if (isloggedin() && isset($primarymenu['edwisermenu'])) {
    $templatecontext['edwisermenu'] = $primarymenu['edwisermenu'];
}

$templatecontext['sections'] = $templatecontext['footerdata']['sections'];
$templatecontext['focusdata'] = $focusdata;

if (\theme_remui\toolbox::get_setting('enableannouncement') && !get_user_preferences('remui_dismised_announcement')) {
    $extraclasses[] = 'remui-notification';
    $templatecontext['sitenotification'] = \theme_remui\utility::render_site_announcement();
}

if (\theme_remui\toolbox::get_setting('enabledictionary') && !$PAGE->user_is_editing()) {
    // Enable dictionary only when editing is off.
    $templatecontext['enabledictionary'] = true;
}
