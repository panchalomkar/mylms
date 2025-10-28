<?php
/**
* Return the active tenant logo url 
* 
* @author Sandeep B
* @since 26-02-2019
* @paradiso
*/
function get_tenant_logo_url( $company_id  ){
    global $SESSION, $CFG,$DB;
    if( 0 == $company_id || empty($company_id) )
        return $CFG->wwwroot.'/local/mt_dashboard/pix/company_logo.png';
    $tenant_logo = 'tenant_logo_'.$company_id; 
    if( $company_id != false ){
        $theme = \theme_config::load('remui');
       // $tenant_logo_url = $theme->setting_file_serve( $tenant_logo, $tenant_logo );
        $tenant_logo_url = \theme_remui\toolbox::setting_file_url($tenant_logo, $tenant_logo);
        if (!empty($tenant_logo_url)) {
            return $tenant_logo_url;
        }else{
            return $CFG->wwwroot.'/local/mt_dashboard/pix/company_logo.png';
        }
    }
    return false;
}
/**
* Return the tenant count 
* @author Bhagyavant S Panhalkar
* @since 17-july-2019
* @paradiso
*/
function get_tenant_count($showsuspended=false){
    global $SESSION , $CFG , $DB;
    if($showsuspended == 1){
            $total_tenants = $DB->count_records('company');
        }else{
            $total_tenants = $DB->count_records('company', array('suspended' => 0), 'name', '*');
        }
    if(iomad::is_company_admin()){
      $id = iomad::is_company_user();
      $total_tenants = $DB->count_records('company', array('suspended' => 0,'id'=>$id), 'name', '*');
    }
    return $total_tenants;
}

/**
* Return all compines with pagination and search  
* @author Bhagyavant S Panhalkar
* @since 17-july-2019
* @paradiso
*/
function get_all_companies($showsuspended = 0 , $startfrom=0, $record_per_page=0 , $search = '', $add_limit = 1 ){
    global $DB, $USER;
    // Is this an admin, or a normal user?
    $systemcontext = context_system::instance();
    $limit = $where = $like = '';
    if( 1 == $add_limit ){
        $limit = "ORDER BY name ASC LIMIT $startfrom , $record_per_page ";
    }
    if( !empty($search) )
        $like = "name LIKE '%$search%'";
    $compantId = array();
    $company_assign = "";
    $sqlList = $DB->get_records_sql("SELECT * FROM {company_users} WHERE userid = $USER->id and managertype = 1");
    foreach ($sqlList as $companyList) {
        $compantId[]= $companyList->companyid;
    }
    $strCompany = "'" . implode( "','", $compantId ) . "'";
    if($showsuspended == 1){
        if( !empty($like) )
           // $company_assign = " AND id IN($strCompany) ";
            $where = " WHERE suspended = $showsuspended".$like ;
            $companies = $DB->get_records_sql("SELECT * FROM {company} $where $limit");
    }else{
        if( !iomad::has_capability('block/iomad_company_admin:company_add', $systemcontext)  ){
            $company_assign = " AND id IN($strCompany) ";
        }
        $where = " WHERE suspended = $showsuspended.$company_assign";
        if( !empty($like) )
        $where .= " && ".$like;
        $companies = $DB->get_records_sql("SELECT * FROM {company}  $where $limit");
    }
    $companyselect = array();
    foreach ($companies as $company) {
        if (empty($company->suspended)) {
            $companyselect[$company->id] = $company->name;
        } else {
            $companyselect[$company->id] = $company->name . '(S)';
        }
    }
    return $companyselect;
}

function get_tenant_courses($companyid){
    global $DB;
    $coursearray = array();
    $companycourses = $DB->get_records_sql("SELECT c.id,c.fullname FROM {company_course} cc LEFT JOIN {course} c ON cc.courseid = c.id WHERE cc.companyid = $companyid");
    foreach($companycourses AS $course){
        $coursearray[$course->id] = $course->fullname;
    }
    return $coursearray;
}