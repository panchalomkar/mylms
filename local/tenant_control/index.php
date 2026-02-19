<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once('control_form.php');
require_once($CFG->dirroot . '/local/iomad/lib/company.php');
require_once('locallib.php');

$companyid = optional_param('companyid', 0, PARAM_INT);
require_login();
iomad::require_capability('block/iomad_company_admin:company_add', context_system::instance());
global $PAGE, $DB;
$context = context_system::instance();
$PAGE->set_context($context);
//$PAGE->set_url($linkurl);
$heading = get_string('tenantcontrol','local_tenant_control');
$PAGE->set_heading($heading);
$PAGE->set_title($heading);
$PAGE->set_pagetype('admincontrol');
$PAGE->set_title($heading);

// Set the url.
$linkurl = new moodle_url('/local/tenant_control/index.php');
$PAGE->set_url($linkurl);
$PAGE->navbar->add($heading);

require_login();
echo $OUTPUT->header();
$companies = company::get_companies_select($showsuspendedcompanies);
$companylist = [''=> get_string('select')];
foreach ($companies as $id=> $company) {
    if(is_array($companies[$id])) {
        
        foreach ($companies[$id]['Colleges'] as $sid => $subcompany) {
            $id = str_replace('-children', '', $id);
            $companylist[$sid] = $companies[$id].'-'.$subcompany;
        }
    }else {
    $companylist[$id] = $company;
    }
}
$select = new single_select(new moodle_url('/local/tenant_control/index.php'), 'companyid', $companylist, $companyid);
$select->set_label(get_string('selectorg','local_tenant_control'));
echo $OUTPUT->render($select);
if($companyid) {
$permissions = $DB->get_records('local_tenant_control', ['companyid' => $companyid]);

$data = new stdClass();
foreach ($permissions as $permission) {
 $data->{$permission->permission} = !$permission->access;
}
$mform = new control_form('', ['companyid' => $companyid, 'permissions' => $data]);
if($data) {
$mform->set_data($data);
}
if($data = $mform->get_data()) {
    $possiblepermissions = get_possible_permissions();
    if($data) {
        $companyids = [$companyid];
        //unset($data->companyids);
      
        foreach($data as $permission =>$access) {
            $access = $access === 'on' ? 1 : 0;
            // Check permission is valid or not, skip in case it is invalid.
            if(!in_array($permission, $possiblepermissions)) {
                continue;
            }

            foreach ($companyids as $companyid) {
                
                //die;
                
                if($companypermission = $DB->get_record('local_tenant_control', ['companyid' => $companyid, 'permission' => "$permission"])) {
    
                        $companypermission->timemodified = time();
                        $companypermission->access = ($data->{$companypermission->permission} === 'on') ? 1 : 0;;
                        $DB->update_record('local_tenant_control', $companypermission);
                    
                   
                } else {
 
                        $companypermission = new stdClass();
                        $companypermission->companyid = $companyid;
                        $companypermission->permission = $permission ;
                        $companypermission->access = $access;
                        $companypermission->timecreated = time();
                        $companypermission->timemodified = time();
                    $DB->insert_record('local_tenant_control', $companypermission);
                }
                
            }
        }
    }
}
$mform->display();
}
echo $OUTPUT->footer();