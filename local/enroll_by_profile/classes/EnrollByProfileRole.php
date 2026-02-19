<?php
defined('MOODLE_INTERNAL') || die;

/**
 * EnrollByProfileRole
 *
 * @package local_enroll_by_profile
 * @author  Ajinkya D
 */
class EnrollByProfileRole {

    protected $db;
    protected $page;

    /*
     * Contruct Enroll by profile role Base object
     */
    public function __construct() {
        global $DB, $PAGE;
        $this->db = $DB;
        $this->page = $PAGE;
    }

    /** 
    * This function will return all available Roles with name and checkbox. 
    * @return $output html content with cohort full name and checkbox <label> <span> cohort name </span> <input value="cohortid" type="checkbox" /> </label>. 
    */  
    public function GetRole($elements) 
    { 
      global $DB,$CFG ;  
      require_once($CFG->dirroot . '/local/enroll_by_profile/lib.php');
      $systemcontext = context_system::instance();  
      $roles = get_all_roles(); 
      $assignableroles = get_assignable_roles($systemcontext, ROLENAME_BOTH, true); 
      $finalroles = $assignableroles[0];  
      $rolenames = role_fix_names($roles, $systemcontext, ROLENAME_ORIGINAL); 
      $la_role = array(); 
        foreach ($finalroles as $roleid => $role) { 
            /** 
            * This condition will ignore Admin cohort to be display in the list 
            * Checked $cohorts object by the id - '6' and name - 'Admin' which used for the Admin 
            */  
            $la_role[$roleid] = $role;              
        } 
         
        /*  
        * @author VaibhavG  
        * @since 19th Feb 2021  
        * @desc 509 Rules Engine issues fixes. Applied Searchbar  
        */  
        
        $result = GetCheckBoxHTML($la_role,$elements);  
         
        return $result ;  
    } 

    /** 
    * This function will assign/unassign given user from given list of Cohorts. 
    * @param $userid User id to be assigned 
    * @param $couresid array of Cohort id to be used to assign the user 
    * @param $method action to be executed add or remove  
    */  
    public function RoleAssignment($userid,$roles,$method) 
    { 
      global $DB, $CFG; 
      $systemcontext = context_system::instance();  
      if($method=='add'){ 
        foreach ($roles as $key => $roleid) { 
          if ($systemcontext instanceof context) {  
              $context = $systemcontext;  
          } else {  
              $context = context::instance_by_id($systemcontext, MUST_EXIST); 
          } 
          if ($DB->get_record('role_assignments', array('roleid'=>$roleid, 'contextid'=>$context->id, 'userid'=>$userid, 'component'=> '', 'itemid'=> 0), 'id'))  
          { 
            return 0; 
          } 
          role_assign($roleid, $userid, $systemcontext);  
        } 
      }elseif($method=='remove'){ 
        foreach ($roles as $key => $roleid) { 
            if ($systemcontext instanceof context) {  
              $context = $systemcontext;  
            } else {  
              $context = context::instance_by_id($systemcontext, MUST_EXIST); 
            } 
            role_unassign($roleid, $userid, $context->id);  
        } 
      }     
    }
}