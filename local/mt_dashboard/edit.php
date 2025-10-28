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
    if (has_capability('local/mt_dashboard:report_view', $systemcontext) && check_tenant_permission ('reports', $selectedcompany)) {
        $tabs[7] = get_string('reporttitle', 'local_mt_dashboard');
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
            $icon = '"><img src="'.$imgsrc.'" alt="'.$menu['name'].'" /></br';
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

        $new_array['url_new'] = $url;
        $new_array['menu_style'] = $menu['style'];
        $new_array['icon'] = $icon;
        $new_array['icon_small'] = $iconsmall;
        $new_array['action'] = $action;
        $new_array['imgsrc'] = $imgsrc;
        $new_array['imgsrcalt'] = $menu['name'];
        $tt[]=$new_array;
    }
        $data['tenant_menus'] = $tt;
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