<?php
defined('MOODLE_INTERNAL') || die;

/**
 * EnrollByProfileLP
 *
 * @package local_enroll_by_profile
 * @author  Ajinkya D
 */
class EnrollByProfileLP {

    protected $db;
    protected $page;

    /*
     * Contruct Enroll by profile LP Base object
     */
    public function __construct() {
        global $DB, $PAGE;
        $this->db = $DB;
        $this->page = $PAGE;
    }

    /** 
    * This function will return all available LPs with name and checkbox. 
    * @return $output html content with LP full name and checkbox <label> <span> LP name </span> <input value="LPid" type="checkbox" /> </label>. 
    */  
    public function GetLP($elements) 
    { 
        global $DB,$CFG ;  
        require_once($CFG->dirroot . '/local/enroll_by_profile/lib.php'); 
        $lps = $DB->get_records('learningpaths',array('deleted' => 0)); 
        $la_lps = array();  
        foreach ($lps as $lpsid => $lp) { 
            $la_lps[$lp->id] = $lp->name ;  
        } 
         
            /*  
            * @author VaibhavG  
            * @since 19th Feb 2021  
            * @desc 509 Rules Engine issues fixes. Applied Searchbar  
            */  
            
            $result = GetCheckBoxHTML($la_lps,$elements); 

        return $result ;  
    }

    /** 
    * This function will assign/unassign given user from given list of LPs. 
    * @param $userid User id to be assigned 
    * @param $couresid array of LP id to be used to assign the user 
    * @param $method action to be executed add or remove  
    */  
    public function LPAssignment($userid,$lpid,$method){ 
        global $DB; 
          
        if($method=='add'){ 
            $data = new stdClass(); 
              foreach ($lpid as $learnPid => $lp) { 
            if (!$DB->get_record('learningpath_users', array('learningpathid'=>$lp, 'userid'=>$userid))) {  
              $data->userid = $userid ; 
              $data->learningpathid = $lp ; 
              $data->enrollment_date = time();  
              $DB->insert_record('learningpath_users',$data); 
            } 
              
            } 
        }elseif($method=='remove'){ 
            $data = array();  
            foreach ($lpid as $learnPid => $lp) { 
                $data['userid'] = $userid ; 
                $data['learningpathid'] = $lp ; 
                $DB->delete_records('learningpath_users',$data) ; 
            } 
        } 
    }

}