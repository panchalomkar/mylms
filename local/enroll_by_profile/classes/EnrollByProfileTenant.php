<?php
defined('MOODLE_INTERNAL') || die;

/**
 * EnrollByProfileTenant
 *
 * @package local_enroll_by_profile
 * @author  Ajinkya D
 */
class EnrollByProfileTenant {

    protected $db;
    protected $page;

    /*
     * Contruct Enroll by profile course Base object
     */
    public function __construct() {
        global $DB, $PAGE;
        $this->db = $DB;
        $this->page = $PAGE;
    }

    /** 
    * This function will return all available tenant on a dropdown element. 
    * @return $output html content with tenant name like <select> <option value="tenant id">tenant name</option> </select> .  
    */  
    public function GetTenant($elements) 
    { 
        global $DB,$CFG;
        require_once($CFG->dirroot . '/local/enroll_by_profile/lib.php');  
        $tenants = $DB->get_records('company',array('suspended'=>'0')); 
        $la_tenant = array(); 
        foreach ($tenants as $tenantid => $tenant) {  
            $la_tenant[$tenant->id] = $tenant->name ; 
        } 
        
        $result = GetDropDownHTML($la_tenant,$elements);
        return $result ;  
    } 
    /*  
    * @author : VaibhavG  
    * @since  : 22 March 2021 
    * @desc   : adding new field tenant.  
    */  
    public function GetFieldTenant() 
    { 
        global $DB,$CFG;
        require_once($CFG->dirroot . '/local/enroll_by_profile/lib.php');  
        $tenants = $DB->get_records('company',array('suspended'=>'0')); 
        $la_tenant = array(); 
        foreach ($tenants as $tenantid => $tenant) {  
            $la_tenant[$tenant->id] = $tenant->name ; 
        } 
        return $la_tenant ; 
    } 

    /** 
    * This function will assign/unassign given user from given tenant.  
    * @param $userid User id to be assigned 
    * @param $companyid Tenant id to be used to assign the user 
    * @param $method action to be executed add or remove  
    */  
    public function TenantAssignment($userid,$companyid,$method){  
        global $DB, $CFG; 
        require_once($CFG->dirroot.'/local/iomad/lib/user.php');
        require_once($CFG->dirroot . '/local/enroll_by_profile/lib.php');
        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileCourses.php";
        $courseclass = new EnrollByProfileCourses();  
        if($method=='add'){ 
          foreach($companyid as $comp => $cid)  
          {           
            if(!$DB->get_record('company_users', array('userid' => $userid,'companyid' => $cid))){  
              //search for tenant main department 
              $departmentinfo = $DB->get_record('department', array('company' => $cid, 'parent' => 0)); 
                  // Create the user association. 
                  $DB->insert_record('company_users', array('userid' => $userid,'companyid' => $cid,'managertype' => 0,'departmentid' => $departmentinfo->id)); 
            }   
          }             
        }elseif($method=='remove'){ 
          foreach($companyid as $comp => $cid)  
          { 
            $sql = "SELECT cc.courseid from {company_course} as cc JOIN {company_users} as cu ON cc.companyid = cu.companyid WHERE cu.userid = $userid AND cu.companyid = $cid";  
              $courseid = $DB->get_records_sql($sql); 
              if(!empty($courseid)){  
                $la_courseid = array(); 
                foreach ($courseid as $key => $id) {  
                    $la_courseid[$id] = $id;  
                } 
                //Remove user from courses assigned to previous companies               
                $courseclass->CourseAssignment($userid,$la_courseid,'remove','removeuser'); 
              } 
              // Remove the user from the company.  
              $DB->delete_records('company_users', array('userid' => $userid, 'companyid' => $cid));  
               
          } 
        } 
    }
}