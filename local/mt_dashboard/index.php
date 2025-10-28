<?php
// This file is part of Moodle - http://moodle.org/
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
// require_once($CFG->libdir . '/outputcomponents.php');
require_once($CFG->dirroot.'/local/tenant_control/locallib.php');
// We always require users to be logged in for this page.
require_login();
global $USER, $CFG, $DB, $OUTPUT, $SESSION;
// Check if Disable tenant from setting then below condition working 
// $theme = theme_config::load('paradiso');
// $tenanthide = $theme->settings->enabletenantinfo;
// if($tenanthide) {
//     redirect(new moodle_url('/index.php'));
// }
// END
// Get parameters.
$edit = optional_param( 'edit', null, PARAM_BOOL );
$company = optional_param('company', 0, PARAM_INT);
$companyss = optional_param('companyss', 0, PARAM_INT);
$showsuspendedcompanies = optional_param('showsuspendedcompanies', 0, PARAM_INT);
$noticeok = optional_param('noticeok', '', PARAM_CLEAN);
$noticefail = optional_param('noticefail', '', PARAM_CLEAN);
$selectedtab = optional_param('tabid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 11, PARAM_INT);
       
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
$linkurl = new moodle_url('/local/iomad_dashboard/index.php');
$linktext = get_string('name', 'local_mt_dashboard');
// Print the page header.
$PAGE->set_context($systemcontext);
$PAGE->set_url($linkurl);
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);
$PAGE->navbar->add(get_string('pluginname', 'local_mt_dashboard'), '/local/mt_dashboard/index.php');
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
 * Added Page Heading, Subheading : Sandeep
 * @author Sandeep Baikare
 * @since 2018122800
 * @paradiso 
 */
// if site admin, show manage Companies link
if(is_siteadmin()){
    $data['is_admin'] = true;
    if( has_capability( 'local/report_companies:view', $systemcontext, $USER->id ) ){
            $data['report_companies'] = true;
            $report_companies_url = new moodle_url('/local/report_companies/index.php');
            $data['report_companies_url'] = $report_companies_url;
    }
    $edit_companies_url = new moodle_url('/blocks/iomad_company_admin/editcompanies.php');
    $data['edit_companies_url'] = $edit_companies_url;
    $company_edit_form = new moodle_url('/blocks/iomad_company_admin/company_edit_form.php?createnew=1');
    $data['company_edit_form'] = $company_edit_form;
}
// Only display if you have the correct capability.
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
$startfrom = $page * $perpage;
$companylist = get_all_companies($showsuspendedcompanies , $startfrom , $perpage);
// Start of form
if( $selectedcompany >0 && iomad::has_capability('block/iomad_company_admin:company_add', $systemcontext)){
        $data['selectedcompany'] = true;
        $backlink = new moodle_url("/local/mt_dashboard/index.php?companyss=0&company=0");
        $data['backlink'] = $backlink;
}
if(iomad::has_capability('block/iomad_company_admin:company_add', $systemcontext)){
    $data['company_add'] = true;
    $action = new moodle_url('/local/mt_dashboard/index.php');
    $data['action'] = $action;

    if($showsuspendedcompanies){
        $data['showsuspended'] = true;
    }
    else{
        $data['showsuspended'] = false;
    }

}
if($page == 0){
    $data['page'] = true;
    // Check if user can create company
    if( iomad::has_capability('block/iomad_company_admin:company_add', $systemcontext)  ){
        $data['company_add'] = true;
        $createnew = new moodle_url('/blocks/iomad_company_admin/company_edit_form.php?createnew=1');
        $data['createnew'] = $createnew;
    }
}
$tt= array();
foreach ($companylist as $key => $companymt) {
    $logo_url = get_tenant_logo_url($key);
    $new_array['logo_url'] = $logo_url;
    $new_array['alt'] = $companymt;
    $new_array['cname'] = $companymt;
    $new_array['company_url'] = new moodle_url('edit.php?companyss='.$key.'&company='.$key);
    $tt[]=$new_array;
}
$data['companylisting'] = $tt;

$total_count = get_tenant_count($showsuspendedcompanies);
if($total_count>10){
    $url = new moodle_url('/local/mt_dashboard/index.php');
    if(!empty($showsuspendedcompanies)){
        $url->param('showsuspendedcompanies',$showsuspendedcompanies);
    }
    $pagination =  new paging_bar($total_count, $page, $perpage, $url , 'page');
    //$output .= $OUTPUT->render($pagination);
    $data['pagination_data']= $OUTPUT->render($pagination);
    //$data['pagination'] = $course_form->display(); // do not delete this
}
$output .= $OUTPUT->render_from_template('local_mt_dashboard/mt_dashboard', $data);
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
            new moodle_url('/local/mt_dashboard/index.php', array(
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
