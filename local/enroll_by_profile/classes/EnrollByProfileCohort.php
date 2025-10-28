<?php
defined('MOODLE_INTERNAL') || die;

/**
 * EnrollByProfileCohort
 *
 * @package local_enroll_by_profile
 * @author  Ajinkya D
 */
class EnrollByProfileCohort {

    protected $db;
    protected $page;

    /*
     * Contruct Enroll by profile Cohort Base object
     */
    public function __construct() {
        global $DB, $PAGE;
        $this->db = $DB;
        $this->page = $PAGE;
    }

    /** 
    * This function will return all available Cohorts with name and checkbox. 
    * @return $output html content with cohort full name and checkbox <label> <span> cohort name </span> <input value="cohortid" type="checkbox" /> </label>. 
    */  
    public function GetCohort($elements) 
    { 
        global $DB,$CFG ;  
        require_once($CFG->dirroot . '/local/enroll_by_profile/lib.php');  
        $cohorts = $DB->get_records('cohort',array('visible' => 1));  
        $la_cohort = array(); 
        foreach ($cohorts as $cohortid => $cohort) {  
            /** 
            * This condition will ignore Admin cohort to be display in the list 
            * Checked $cohorts object by the id - '6' and name - 'Admin' which used for the Admin 
            */  
            if($cohort->id != 6 || $cohort->name != 'Admin'){ 
                $la_cohort[$cohort->id] = $cohort->name ; 
            } 
        } 
          
            /*  
            * @author VaibhavG  
            * @since 19th Feb 2021  
            * @desc 509 Rules Engine issues fixes. Applied Searchbar  
            */  
            
            $result = GetCheckBoxHTML($la_cohort,$elements);  
        
        return $result ;  
    } 

    /** 
    * This function will assign/unassign given user from given list of Cohorts. 
    * @param $userid User id to be assigned 
    * @param $couresid array of Cohort id to be used to assign the user 
    * @param $method action to be executed add or remove  
    */  
    public function CohortAssignment($userid,$cohortid,$method)  
    { 
        global $DB, $CFG; 
      
        include_once($CFG->dirroot.'/cohort/lib.php');  
        if($method=='add'){ 
          foreach ($cohortid as $chid => $ch) { 
            if (!$DB->get_record('cohort_members', array('cohortid'=>$ch, 'userid'=>$userid))) {  
                cohort_add_member($ch, $userid);  
            }                 
          } 
        }elseif($method=='remove'){ 
            foreach ($cohortid as $chid => $ch) { 
                
                cohort_remove_member($ch, $userid); 
            } 
        } 
    } 
}