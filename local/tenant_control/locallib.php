<?php
function get_possible_permissions() {
    global $DB;    
    $permissions = [
    'coursecreate',
    'coursedelete',
    'proctoring',
    'usercreate',
    'useredit',
    'competencies',
    'reports',
    'licenses',
    'ecommerce',
    'learningpath',
    'program'
    ];
    $modules = $DB->get_records('modules', array('visible' => 1), 'name ASC');
            
    foreach ($modules as $module) {
        $permissions[] = $module->name;
    }

    return $permissions;
}

function check_tenant_permission($permission, $companyid = 0) {

    global $DB, $USER;
    if(is_siteadmin()) {
        return true;
    }
    if(!$companyid) {
        $company = company::get_company_byuserid($USER->id);
        $companyid = $company->id;
    }
   
    $permission  = $DB->get_record('local_tenant_control', ['permission' => $permission, 'companyid' => $companyid]);
    
    // It seems permissin is not set for the company, default everything should be allowed.
    if(!$permission) {
        return true;
    }

    if($permission->access) { // Company is allowed for this permission.
     return true;
    }

    return false;
}