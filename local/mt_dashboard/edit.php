<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
// Display iomad_dashboard.
require_once( '../../config.php');
require_once($CFG->dirroot.'/local/mt_dashboard/lib.php');
require_once($CFG->dirroot.'/local/mt_dashboard/menu.php');
require_once($CFG->dirroot.'/local/iomad/lib/company.php');
require_once($CFG->dirroot.'/local/tenant_control/locallib.php');
// We always require users to be logged in for this page.
require_login();

global $USER, $CFG, $DB, $OUTPUT, $SESSION;
// Get parameters.
$edit = optional_param( 'edit', null, PARAM_BOOL );
$company = optional_param('company', 0, PARAM_INT);
$companyss = optional_param('companyss', 0, PARAM_INT);
$showsuspendedcompanies = optional_param('showsuspendedcompanies', 0, PARAM_INT);
$noticeok = optional_param('noticeok', '', PARAM_CLEAN);
$noticefail = optional_param('noticefail', '', PARAM_CLEAN);
$selectedtab = optional_param('tabid', 0, PARAM_INT);
// Check we are allowed to view this page.
$systemcontext = context_system::instance();
// iomad::require_capability( 'local/iomad_dashboard:view', $systemcontext );

// Set the session to a user if they are editing a company other than their own.
$SESSION->showsuspendedcompanies = $showsuspendedcompanies;
// Set the session to a user if they are editing a company other than their own.
if (!empty($company) && ( iomad::has_capability('block/iomad_company_admin:company_add', $systemcontext) 
                          || $DB->get_record('company_users', array('managertype' => 1, 'companyid' => $company, 'userid' => $USER->id)))) {
    $SESSION->currenteditingcompany = $company;
}
// Default notice class
$noticeclass ="alert alert-warning";
// check if company id exist in database, show error msg
if(!empty($company) && !$DB->record_exists("company",['id'=>$company])){
    unset($SESSION->currenteditingcompany); 
    $noticeclass ="alert alert-danger";
    $noticefail = get_string('companynotfound','local_mt_dashboard', $company);
}
// Check if there are any companies.
if (!$companycount = $DB->count_records('company')) {
    // If not redirect to create form.
    redirect(new moodle_url('/blocks/iomad_company_admin/company_edit_form.php',
                             array('createnew' => 1)));
}
// If there is only one company, make that the current one
if ($companycount == 1) {
     $companies = $DB->get_records('company');
     $firstcompany = reset($companies);
     $SESSION->currenteditingcompany = $firstcompany->id;
     $company = $firstcompany->id;
}
// Unset from session if company is empty
if( ( isset($_GET['companyss']) && 0 == intval($_GET['companyss']) ) ){
    unset($SESSION->currenteditingcompany); // try for non selected companies
    $company = 0;
}
// Set the current tab to stick.
if (!empty($selectedtab)) {
    $SESSION->iomad_company_admin_tab = $selectedtab;
} else if (!empty($SESSION->iomad_company_admin_tab)) {
    $selectedtab = $SESSION->iomad_company_admin_tab;
} else {
    $selectedtab = 1;
}
// Set the url.
$linkurl = new moodle_url('/local/iomad_dashboard/edit.php');
$linktext = get_string('name', 'local_mt_dashboard');
// Page setup stuff.
// The page layout for my moodle does the job here
// as it allows blocks in the centre column.
// Print the page header.
$PAGE->set_context($systemcontext);
$PAGE->set_url($linkurl);
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);
$PAGE->navbar->add(get_string('pluginname', 'local_mt_dashboard'), '/local/mt_dashboard/edit.php');
$PAGE->requires->js_init_call( 'M.local_iomad_dashboard.init');
$PAGE->requires->css('/local/mt_dashboard/styles.css');
$PAGE->blocks->add_region('content');
// Set tye pagetype correctly.
$PAGE->set_pagetype('local-iomad-dashboard-index');
$PAGE->requires->js(new moodle_url('https://cdn.tailwindcss.com'));
$PAGE->requires->css(new moodle_url('https://fonts.googleapis.com/icon?family=Material+Icons'));
$PAGE->requires->css(new moodle_url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined'));
$PAGE->requires->css(new moodle_url('https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded'));
?>
<STYLE>
    .user_tab{
    .input-style {
  padding: 12px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  width: 100%;
}

.btn-primary {
  background: #003152;
  color: #fff;
  padding: 10px 16px;
  border-radius: 10px;
}

.btn-secondary {
  border: 1px solid #e5e7eb;
  padding: 10px 16px;
  border-radius: 10px;
}

.badge-success {
  background: #dcfce7;
  color: #166534;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
}

.badge-warning {
  background: #fef3c7;
  color: #92400e;
  padding: 4px 10px;
  border-radius: 999px;
  font-weight: 500;
}

.badge-danger {
  background: #fee2e2;
  color: #991b1b;
  padding: 4px 10px;
  border-radius: 999px;
  font-weight: 500;
}

.badge-muted {
  background: #f3f4f6;
  color: #374151;
  padding: 4px 10px;
  border-radius: 999px;
}
    }
</STYLE>
<?PHP
//$PAGE->set_pagelayout('mydashboard');

// Now we can display the page.
$output = $companyselectform = '';
$output .= $OUTPUT->header();
// Deal with any notices.
if (!empty($noticeok)) {
    $data['noticeok'] = true;
    $data['noticeok_message'] = $noticeok;
} 
if (!empty($noticefail)) {
    $data['noticefail'] = true;
    $data['noticefail_message'] = $noticefail;
} 

/** 
 * Check if admin user and is in tenant
 * @author Sandeep Baikare
 * @since 24/01/2019
 * 
 */
if( is_siteadmin() && isset($SESSION->currenteditingcompany) ){
    $companyobj = new company($SESSION->currenteditingcompany);
    $compnay_name = $companyobj->get_name();
    $data['is_siteadmin'] = true;
    $data['compnay_name'] = $compnay_name;
}
/**
 * Added Page Heading, Subheading : Sandeep
 * @author Sandeep Baikare
 * @since 2018122800
 * @paradiso 
 */
$backlink = new moodle_url("/local/mt_dashboard/index.php?companyss=0&company=0");
$data['compnay_name'] = $compnay_name;
$data['backlink'] = $backlink;
$company_name = $DB->get_record('company', array('id' => $SESSION->currenteditingcompany));
$gradients = ['avatar-blue', 'avatar-purple', 'avatar-indigo'];
$gradientclass = $gradients[$SESSION->currenteditingcompany % count($gradients)];

$data['avatar_class'] = $gradientclass;
$data['avatar_text']  = strtoupper(substr($compnay_name, 0, 2));

// Only display if you have the correct capability.
// if (!iomad::has_capability('local/iomad_dashboard:view', context_system::instance())) {
//     return;
// }
if (!isloggedin()) {
    $output .= get_string('pleaselogin', 'block_iomad_company_selector');            
    echo $output;
    return;
}
//  Check users session and profile settings to get the current editing company.
if (!empty($SESSION->currenteditingcompany)) {
    $selectedcompany = $SESSION->currenteditingcompany;
} else if (!empty($USER->profile->company)) {
    $usercompany = company::by_userid($USER->id);
    $selectedcompany = $usercompany->id;
} else {
    $selectedcompany = 0;
}

// Get a list of companies.
$companylist = get_all_companies($showsuspendedcompanies);
// Add first select opotion string as custom
if( 0 == $selectedcompany ){
    $companylist[0] = get_string('selectcompanychoose', 'local_mt_dashboard');
}
ksort($companylist); 

// ==============================
// COMPANY INFORMATION
// ==============================
$companyinfo = null;

if (!empty($selectedcompany)) {
    $companyinfo = $DB->get_record('company', ['id' => $selectedcompany], '*', IGNORE_MISSING);
}

$data['company_address'] = $companyinfo->address ;
$data['company_email']   = $companyinfo->email ;
$data['company_phone']   = $companyinfo->telephone;
$data['location']   = $companyinfo->city .', '. $companyinfo->country;

$data['company_status'] = (!empty($companyinfo) && empty($companyinfo->suspended))
    ? 'Active'
    : 'Suspended';

get_company_creator_name($selectedcompany);
$data['company_creator_name'] = get_company_creator_name($selectedcompany);
// If no selected company no point showing tabs.
if (!iomad::get_my_companyid(context_system::instance(), false)) {
    $data['no_company_selected'] = true;
    $output .= '<div class="alert alert-warning">' . get_string('nocompanyselected', 'block_iomad_company_admin') . '</div>';        
}else{
    // Build tabs.
    $tabs = array();
    if (iomad::has_capability('block/iomad_company_admin:companymanagement_view', $systemcontext)) {
        $tabs[1] = get_string('companymanagement', 'block_iomad_company_admin');
    }
    if (iomad::has_capability('block/iomad_company_admin:usermanagement_view', $systemcontext)) {
        $tabs[2] = get_string('usermanagement', 'block_iomad_company_admin');
    }
    if (iomad::has_capability('block/iomad_company_admin:coursemanagement_view', $systemcontext)) {
        $tabs[3] = get_string('coursemanagement', 'block_iomad_company_admin');
    }
    if (iomad::has_capability('block/iomad_company_admin:licensemanagement_view', $systemcontext) && check_tenant_permission ('licenses', $selectedcompany)) {
        $tabs[4] = get_string('licensemanagement', 'block_iomad_company_admin');
    }
    if (iomad::has_capability('block/iomad_company_admin:competencymanagement_view', $systemcontext) && check_tenant_permission ('compentecies', $selectedcompany)) {
        $tabs[5] = get_string('competencymanagement', 'block_iomad_company_admin');
    }
      if (has_capability('block/iomad_commerce:admin_view', $systemcontext) && check_tenant_permission ('ecommerce', $selectedcompany)) {
        $tabs[6] = get_string('E-commerce', 'local_mt_dashboard');
    }
    if (has_capability('local/mt_dashboard:report_view', $systemcontext) && check_tenant_permission ('reports', $selectedcompany)) {
        $tabs[7] = get_string('reporttitle', 'local_mt_dashboard');
    }
       if (has_capability('block/iomad_microlearning:view', $systemcontext) && check_tenant_permission ('MicrolearningAdmin', $selectedcompany)) {
        $tabs[8] = get_string('threads', 'block_iomad_microlearning');
    }
    $tabhtml = mt_gettabs($tabs, $selectedtab);
    // Build content for selected tab (from menu array).
    $adminmenu = new mt_admin_menu();
    $menus = $adminmenu->getmenu();
    $data['tabhtml'] = $tabhtml;
            // Code by sumit: restrict menu for the tenant
        // Check course level permission. 
        if ($selectedcompany){
        if(!check_tenant_permission ('coursecreate', $selectedcompany)) {
            unset($menus['createcourse']);
        }
      
        // Check user level permission. 
       if(!check_tenant_permission ('usercreate', $selectedcompany)) {
           unset($menus['createuser']);
           unset($menus['uploadfromfile']);
       }

       if(!check_tenant_permission ('useredit', $selectedcompany)) {
           unset($menus['edituser']);
       }

  
       if(!check_tenant_permission ('learningpath', $selectedcompany)) {
           unset($menus['learningpath']);
       }

       if(!check_tenant_permission ('program', $selectedcompany)) {
           unset($menus['programview']);
           unset($menus['companyprogram']);
       }
    }
    $tt= array();
    
    foreach ($menus as $key => $menu) {
        //tenant_appearance setting
        if ($menu['name'] == 'Appearance Setting') {
            if (is_siteadmin()){
           $menu['url'] ='/admin/settings.php?section=tenantsettingremui';
            }else{
                $menu['url'] ='/local/tenant_appearance/tenant_appearance.php';
            }
       }
        // If it's the wrong tab then move on.
        if ($menu['tab'] != $selectedtab) {
            continue;
        }

        // If no capability the move on.
        if (!iomad::has_capability($menu['cap'], $systemcontext)) {
            continue;
        }

        // Build correct url.
        if (substr($menu['url'], 0, 1) == '/') {
            $url = new moodle_url($menu['url']);
        } else {
            $url = new moodle_url('/blocks/iomad_company_admin/'.$menu['url']);
        }

        // Get topic image icon
        if (((empty($USER->theme) && (strpos($CFG->theme, 'iomad') !== false)) || (strpos($USER->theme, 'iomad') !== false))  && !empty($menu['icon'])) {
            $icon = $menu['icon'];
        } else if (!empty($menu['icondefault'])) {
            $imgsrc = $OUTPUT->image_url($menu['icondefault'], 'block_iomad_company_admin');
            $icon = '"><img src="'.$imgsrc.'" alt="'.$menu['name'].'" /></br>';
        } else {
            $icon = '';
        }

        // Get topic action icon
        if (!empty($menu['iconsmall'])) {
            $iconsmall = $menu['iconsmall'];
        } else {
            $iconsmall = '';
        }

        // Get Action description
        if (!empty($menu['name'])) {
            $action = $menu['name'];
        } else {
            $action = '';
        }
$iconmap = [
    'Manage departments'           => ['icon' => 'settings', 'bg' => 'bg-blue'],
    'Edit company'                 => ['icon' => 'edit_square', 'bg' => 'bg-sky'],
    'Department users & managers'  => ['icon' => 'groups', 'bg' => 'bg-cyan'],
    'Optional profiles'            => ['icon' => 'person_add', 'bg' => 'bg-purple'],
    'Assign users'                 => ['icon' => 'how_to_reg', 'bg' => 'bg-indigo'],
    'Restrict capabilities'        => ['icon' => 'shield', 'bg' => 'bg-royal'],
    'Email templates'              => ['icon' => 'mail', 'bg' => 'bg-teal'],
    'Appearance Setting'           => ['icon' => 'palette', 'bg' => 'bg-ocean'],
    'Create user'          => ['icon' => 'person_add', 'bg' => 'bg-green'],
    'Edit users'           => ['icon' => 'edit', 'bg' => 'bg-blue'],
    'Upload users'         => ['icon' => 'upload', 'bg' => 'bg-purple'],
    'User bulk download'   => ['icon' => 'download', 'bg' => 'bg-teal'],
    'Bulk user actions'    => ['icon' => 'group_work', 'bg' => 'bg-indigo'],
    'Cohorts'              => ['icon' => 'groups', 'bg' => 'bg-cyan'],
    'Approve training events' => ['icon' => 'check_circle', 'bg' => 'bg-green'],
    'Merge user accounts'      => ['icon' => 'merge_type', 'bg' => 'bg-amber'],
    'Advanced company settings' => ['icon' => 'settings', 'bg' => 'bg-blue'],
    'Import companies'         => ['icon' => 'import_export', 'bg' => 'bg-purple'],
    'Custom pages'             => ['icon' => 'pageview', 'bg' => 'bg-ocean'],
    'Permission Control'       => ['icon' => 'lock', 'bg' => 'bg-indigo'],
     'Assign to company' => [ 'icon' => 'add_box', 'bg'   => 'bg-[#003152]','description' => 'Assign courses'],
    'User enrolments' => ['icon' => 'person_add', 'bg'   => 'bg-[#003152]','description' => 'Assign users '],
    'Create course' => ['icon' => 'menu_book','bg'   => 'bg-[#003152]','description' => 'New course'],
    'Manage course settings' => ['icon' => 'settings', 'bg'   => 'bg-[#003152]','description' => 'Course settings'],
    'Manage company groups' => [ 'icon' => 'school', 'bg'   => 'bg-[#003152]','description' => 'Manage groups'],
    'Assign course groups' => [ 'icon' => 'device_hub','bg'   => 'bg-[#003152]','description' => 'Assign groups'],
    'Teaching locations' => ['icon' => 'location_on', 'bg'   => 'bg-[#003152]','description' => 'Manage locations'],
    'Cohort Sync' => ['icon' => 'link', 'bg'   => 'bg-[#003152]','description' => 'Sync cohorts'],
    'License management' => ['icon' => 'verified_user', 'bg'   => 'bg-[#003152]','description' => 'Manage locations'],
    'User license allocations' => ['icon' => 'assignment_ind', 'bg'   => 'bg-[#003152]','description' => 'Sync cohorts'],
'Assign frameworks' => ['icon' => 'add_box', 'bg'   => 'bg-[#003152]','description' => 'Assign courses'],
'Manage framework settings' => ['icon' => 'settings', 'bg'   => 'bg-[#003152]','description' => 'Manage framework settings'],
'Competency frameworks' => ['icon' => 'account_tree', 'bg'   => 'bg-[#003152]','description' => 'Competency frameworks'],
'Assign LP to company' => ['icon' => 'assignment_turned_in', 'bg'   => 'bg-[#003152]','description' => 'Assign LP to company'],
'Manage template settings' => ['icon' => 'tune', 'bg'   => 'bg-[#003152]','description' => 'Manage template settings'],
'Learning Plan' => ['icon' => 'school', 'bg'   => 'bg-[#003152]','description' => 'Learning Plan'],
'Attendance report by course' => ['icon' => 'event_available', 'bg'   => 'bg-[#003152]','description' => 'Course Attendance Overview'],
'Completion report by course' => ['icon' => 'task_alt', 'bg'   => 'bg-[#003152]','description' => 'Course Completion Status'],
'License Allocations Report' => ['icon' => 'assignment', 'bg'   => 'bg-[#003152]','description' => 'License Allocation Summary'],
'User license allocations report' => ['icon' => 'assignment_ind', 'bg'   => 'bg-[#003152]','description' => 'User License Distribution'],
'User Login Report' => ['icon' => 'login', 'bg'   => 'bg-[#003152]','description' => 'User Login Activity'],
'Users Report' => ['icon' => 'people', 'bg'   => 'bg-[#003152]','description' => 'User Account Overview'],
'Products' => ['icon' => 'inventory_2', 'bg'   => 'bg-[#003152]','description' => 'Manage Products'],
'Orders' => ['icon' => 'shopping_cart', 'bg'   => 'bg-[#003152]','description' => 'Manage Orders']
];

$menuicon = $iconmap[$menu['name']] ?? ['icon' => 'apps', 'bg' => 'bg-default'];

$new_array['icon']     = $menuicon['icon'];
$new_array['icon_bg']  = $menuicon['bg'];
$new_array['description']  = $menuicon['description'] ?? '';


        $new_array['url_new'] = $url;
        $new_array['menu_style'] = $menu['style'];
        // $new_array['icon'] = $icon;
        $new_array['icon_small'] = $iconsmall;
        $new_array['action'] = $action;
        $new_array['imgsrc'] = $imgsrc;
        $new_array['imgsrcalt'] = $menu['name'];
        $tt[]=$new_array;
    }
        $data['tenant_menus'] = $tt;
      
}



$data['hasstats'] = false;
$data['stats'] = [];
$data['has_quick_actions'] = !empty($data['tenant_menus']);
$data['show_company_info'] = in_array($selectedtab, [1]);
$data['selectedtab'] = $selectedtab;

// Helpers
$data['is_tab_2'] = ($selectedtab == 2);
$data['is_tab_1'] = ($selectedtab == 1);
$data['is_tab_3'] = ($selectedtab == 3);
$data['is_tab_4'] = ($selectedtab == 4);    
$data['is_tab_5'] = ($selectedtab == 5);
$data['is_tab_6'] = ($selectedtab == 6);    
$data['is_tab_7'] = ($selectedtab == 7);    
$data['is_tab_8'] = ($selectedtab == 8);

$data['show_quick_actions'] = (($selectedtab == 1) ||($selectedtab == 3||($selectedtab == 5)||($selectedtab == 7)||($selectedtab == 6)));//||($selectedtab == 5)
$data['show_quick_actions_small'] = (($selectedtab == 2) ||($selectedtab == 4));
$data['actions_child'] = (($selectedtab == 3) ||($selectedtab == 5)||($selectedtab == 7)||($selectedtab == 6));//||($selectedtab == 7)
switch ($selectedtab) {

    // TAB 1 – Company overview
    case 1:
        $data['stats'] = get_company_overview_stats($selectedcompany);
        break;

    // TAB 2 – User management
   case 2:
    $data['stats'] = get_company_user_stats($selectedcompany);

    // URLs
    $data['adduserurl']    = new moodle_url('/user/editadvanced.php');
    $data['bulkuploadurl'] = new moodle_url('/admin/tool/uploaduser/index.php');

    // Roles
    $context = context_system::instance();

// $roles = get_assignable_roles($context, ROLENAME_BOTH);

// $data['roles'] = [];
// foreach ($roles as $id => $name) {
//     $data['roles'][] = [
//         'id'   => $id,
//         'name' => $name
//     ];
// }
$roles = $DB->get_records('role', null, '', 'id, shortname');

$data['roles'] = [];
foreach ($roles as $r) {
    $data['roles'][] = [
        'id'   => (string) $r->id,
        'name' => format_string($r->shortname)
    ];
}


    // Users
    $sql = "
      SELECT 
    u.id,
    u.firstname,
    u.lastname,
    u.email,
    u.lastaccess,
    u.suspended,
    r.id AS roleid,
    r.shortname AS role
FROM {user} u
JOIN {company_users} cu ON cu.userid = u.id
LEFT JOIN {role_assignments} ra ON ra.userid = u.id
LEFT JOIN {role} r ON r.id = ra.roleid
WHERE cu.companyid = :companyid
  AND u.deleted = 0

    ";

    $records = $DB->get_records_sql($sql, ['companyid' => $selectedcompany]);

    $users = [];
    foreach ($records as $u) {
$sesskey = sesskey();

$users[] = [
    'id'           => $u->id,
    'fullname'     => fullname($u),
    'email'        => $u->email,
    'role'         => $u->role ?? '—',
     'roleid'    => (string) ($u->roleid ?? ''),
    'active'       => !$u->suspended,
    'status'       => $u->suspended ? 'suspended' : 'active',
    'lastlogin'    => $u->lastaccess ? date('Y-m-d', $u->lastaccess) : '—',
    // EDIT (no sesskey required)
    'editurl' => (new moodle_url(
        '/user/editadvanced.php',
        ['id' => $u->id]
    ))->out(false),

    // SUSPEND / UNSUSPEND (sesskey REQUIRED)
  'suspendurl' => (new moodle_url(
    '/admin/user.php',
    ['suspend' => $u->id, 'sesskey' => sesskey()]
))->out(false),

    'unsuspendurl' => (new moodle_url(
        '/admin/user.php',
        ['unsuspend' => $u->id, 'sesskey' => $sesskey]
    ))->out(false),

    // DELETE (sesskey REQUIRED)
    'deleteurl' => (new moodle_url(
        '/admin/user.php',
        ['delete' => $u->id, 'sesskey' => $sesskey]
    ))->out(false),
];
    }

    $data['users'] = $users;
    break;


    // TAB 3 – Course management
    case 3:
        $data['stats'] = get_company_course_stats($selectedcompany);
        break;

    // TAB 4 – License management
    case 4:
    // License stats cards (optional)
    $data['stats'] = get_company_license_stats($selectedcompany);

    // Fetch licenses
$sql = "
    SELECT
        cl.id,
        cl.name,
        cl.allocation,
        cl.used,
        (cl.allocation - cl.used) AS available,
        cl.expirydate,
        GROUP_CONCAT(c.fullname SEPARATOR ', ') AS coursename
    FROM {companylicense} cl
    LEFT JOIN {companylicense_courses} clc
           ON clc.licenseid = cl.id
    LEFT JOIN {course} c
           ON c.id = clc.courseid
    WHERE cl.companyid = :companyid
    GROUP BY cl.id
    ORDER BY cl.expirydate ASC
";

$records = $DB->get_records_sql($sql, ['companyid' => $selectedcompany]);

$licenses = [];
$now = time();
$todayend = strtotime('today 23:59:59');
$expiringwindow = $now + (30 * DAYSECS);

foreach ($records as $l) {

    $expiry = (int) $l->expirydate;

    // ---- STATUS LOGIC ----
    if (empty($expiry)) {
        $status = 'active';
    } elseif ($expiry <= $todayend) {
        $status = 'expired';
    } elseif ($expiry <= $expiringwindow) {
        $status = 'expiring';
    } else {
        $status = 'active';
    }

    $allocation = (int) $l->allocation;
    $used       = (int) $l->used;
    $available  = max(0, $allocation - $used);

   $licenses[] = [
    'id'        => $l->id,
    'name'      => format_string($l->name),
    'course'    => $l->coursename ? format_string($l->coursename) : 'All Courses',
    'quantity'  => $allocation,
    'assigned'  => $used,
    'available' => $available,
    'expiry'    => $expiry ? date('Y-m-d', $expiry) : '—',
    'status' => [
        'active'   => ($status === 'active'),
        'expired'  => ($status === 'expired'),
        'expiring' => ($status === 'expiring')
    ]
];

}

$data['licenses'] = $licenses;

        break;

  // TAB 5 – Competency management
case 5:
    $data['stats'] = get_company_competency_stats($selectedcompany);
    // TAB 5 – Competency management
case 5:

    $data['stats'] = get_company_competency_stats($selectedcompany);

    $sql = "
        SELECT
            c.id AS uniqid,              -- ✅ MUST be unique (important!)

            cf.id AS frameworkid,
            cf.shortname AS frameworkname,

            c.shortname,
            c.parentid,
            c.path,
            c.sortorder
        FROM {competency_framework} cf
        JOIN {competency} c
            ON c.competencyframeworkid = cf.id
        WHERE cf.visible = 1
        ORDER BY cf.id, c.path
    ";

    $records = $DB->get_records_sql($sql);

    $frameworks = [];

    foreach ($records as $r) {

        // ✅ Calculate depth from path (Moodle standard)
        $depth = substr_count(trim($r->path, '/'), '/') + 1;

        // Init framework
        if (!isset($frameworks[$r->frameworkid])) {
            $frameworks[$r->frameworkid] = [
                'frameworkname' => format_string($r->frameworkname),
                'categories'    => []
            ];
        }

        // ✅ Depth 1 = Category
        if ($depth === 1) {
            $frameworks[$r->frameworkid]['categories'][$r->uniqid] = [
                'categoryname' => format_string($r->shortname),
                'competencies' => []
            ];
            continue;
        }

        // ✅ Depth > 1 = Competency
        if (!empty($r->parentid)) {
            if (isset($frameworks[$r->frameworkid]['categories'][$r->parentid])) {
                $frameworks[$r->frameworkid]['categories'][$r->parentid]['competencies'][] = [
                    'name'        => format_string($r->shortname),
                    'proficiency' => ''
                ];
            }
        }
    }

    // ✅ Normalize for Mustache
    $data['competencyframeworks'] = array_values(array_map(function ($fw) {
        $fw['categories'] = array_values($fw['categories']);
        return $fw;
    }, $frameworks));

    break;
  
         case 6:
        // $data['stats'] = get_company_ecommerce_stats($selectedcompany);
        break;
        case 7:
        // $data['stats'] = get_company_report_stats($selectedcompany);
        
        break;
        case 8:
        // $data['stats'] = get_company_microlearning_stats($selectedcompany);
        break;  
}

if (!empty($data['stats'])) {
    $data['hasstats'] = true;
}

$output .= $OUTPUT->render_from_template('local_mt_dashboard/edit', $data);

//echo $OUTPUT->blocks_for_region('content');
$PAGE->requires->js_call_amd('local_mt_dashboard/formsubmit', 'init');
$output .= $OUTPUT->footer();
echo $output;

function mt_gettabs($tabs, $selected) {
    global $OUTPUT, $SESSION;
    $showsuspendedcompanies = optional_param('showsuspendedcompanies', false, PARAM_BOOL);
    $company = $SESSION->currenteditingcompany;
    $row = array();
    // Build list.
    foreach ($tabs as $key => $tab) {
        $row[] = new tabobject(
            $key,
            new moodle_url('/local/mt_dashboard/edit.php', array(
                                                                    'tabid'                 => $key, 
                                                                    'company'               => $company,
                                                                    'showsuspendedcompanies' => $showsuspendedcompanies)
                                                                ),
            $tab
        );
    }
    $html = $OUTPUT->tabtree($row, $selected);
    return $html;
}
?>
<script>
document.addEventListener('click', function (e) {

  // Close all dropdowns
  document.querySelectorAll('.action-menu').forEach(m => m.classList.add('hidden'));

  // Toggle clicked dropdown
  const btn = e.target.closest('.action-btn');
  if (btn) {
    e.stopPropagation();
    const menu = btn.nextElementSibling;
    menu.classList.toggle('hidden');
  }
});

// -----------------------------
// SEARCH + FILTER LOGIC
// -----------------------------
const searchInput = document.getElementById('userSearch');
const roleFilter  = document.getElementById('roleFilter');
const statusFilter = document.getElementById('statusFilter');

function filterUsers() {
  const search = searchInput.value.toLowerCase();
  const role   = roleFilter.value;
  const status = statusFilter.value;

  document.querySelectorAll('.user-row').forEach(row => {
    const name   = row.dataset.name.toLowerCase();
    const r      = row.dataset.role;
    const s      = row.dataset.status;

    const matchSearch = name.includes(search);
    const matchRole   = !role || r === role;
    const matchStatus = !status || s === status;

    row.style.display = (matchSearch && matchRole && matchStatus)
      ? ''
      : 'none';
  });
}

searchInput.addEventListener('input', filterUsers);
roleFilter.addEventListener('change', filterUsers);
statusFilter.addEventListener('change', filterUsers);

const modal = document.getElementById('userActionModal');
const icon  = document.getElementById('modalIcon');
const title = document.getElementById('modalTitle');
const text  = document.getElementById('modalText');
const confirmBtn = document.getElementById('modalConfirm');
const cancelBtn  = document.getElementById('modalCancel');

let currentUserId = null;
let currentAction = null;

document.querySelectorAll('.js-user-action').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault();

    currentUserId = btn.dataset.userid;
    currentAction = btn.dataset.action;

    // Reset styles
    confirmBtn.className = 'px-4 py-2 rounded-lg text-white';

    if (currentAction === 'suspend') {
      icon.textContent = 'pause_circle';
      icon.className = 'material-icons text-orange-500 text-4xl mb-3';
      title.textContent = 'Suspend user?';
      text.textContent = 'This user will not be able to log in.';
      confirmBtn.classList.add('bg-orange-600');
    }

    if (currentAction === 'activate') {
      icon.textContent = 'check_circle';
      icon.className = 'material-icons text-green-600 text-4xl mb-3';
      title.textContent = 'Activate user?';
      text.textContent = 'This user will regain access.';
      confirmBtn.classList.add('bg-green-600');
    }

    if (currentAction === 'delete') {
      icon.textContent = 'delete_forever';
      icon.className = 'material-icons text-red-600 text-4xl mb-3';
      title.textContent = 'Delete user permanently?';
      text.textContent = 'This action cannot be undone.';
      confirmBtn.classList.add('bg-red-600');
    }

    modal.classList.remove('hidden');
  });
});

cancelBtn.onclick = () => modal.classList.add('hidden');

confirmBtn.onclick = async () => {
  confirmBtn.disabled = true;

  const res = await fetch(
    M.cfg.wwwroot + '/local/mt_dashboard/ajax/user_status.php',
    {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        userid: currentUserId,
        action: currentAction,
        sesskey: M.cfg.sesskey
      })
    }
  );

  const json = await res.json();

  if (json.success) {
    modal.classList.add('hidden');
    location.reload();
  } else {
    alert(json.message || 'Action failed');
  }
};
</script>
