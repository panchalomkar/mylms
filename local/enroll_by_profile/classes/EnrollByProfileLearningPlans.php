<?php
defined('MOODLE_INTERNAL') || die;

/**
 * EnrollByProfileLearningPlans
 *
 * @package local_enroll_by_profile
 * @author  Ajinkya D
 */
class EnrollByProfileLearningPlans {

    protected $db;
    protected $page;

    /*
     * Contruct Enroll by profile LearningPlans object
     */
    public function __construct() {
        global $DB, $PAGE;
        $this->db = $DB;
        $this->page = $PAGE;
    }

    /**
    * @desc   : #922 Add Learning Plan as a new category in the Automation Hub plugin.
    * @author : Abhishek V
    * @since  : 27 July 2021
    */
    public function GetLearningPlans($elements) 
    { 
      global $DB,$CFG;
      require_once($CFG->dirroot . '/local/enroll_by_profile/lib.php'); 
      $learning_plans = $DB->get_records('competency_template',array('visible'=>1));  
      $plans = array(); 
      foreach ($learning_plans as $key => $allplans) { 
         $plans[$key] = $allplans->shortname ; 
      } 
      
      $result = GetCheckBoxHTML($plans,$elements);  
       
      return $result ;
    }

    /**
     * @ticket : #922 Add Learning Plan as a new category in the Automation Hub plugin.
     * @author : Abhishek V
     * @since  : 27 July 2021
     */
    public function LearningplansAssignment($userid,$lpid,$method){ 
      global $DB,$USER; 
      
      if($method=='add'){ 
          $data = new stdClass(); 
            foreach ($lpid as $learnPid => $lp) { 
              if (!$DB->get_record('competency_plan', array('templateid'=>$lp, 'userid'=>$userid))) { 
                
                $getplan_name = $DB->get_record('competency_template', array('id'=>$lp));
                $data->name = $getplan_name->shortname;
                $data->userid = $userid; 
                $data->descriptionformat = 1;   
                $data->templateid = $lp; 
                $data->status = 1; 
                $data->timecreated = time();
                $data->usermodified  = $USER->id;
                $DB->insert_record('competency_plan',$data); 
              } 
            } 
      }elseif($method=='remove'){ 
          $data = array();  
          foreach ($lpid as $learnPid => $lp) { 
              $data['userid'] = $userid ; 
              $data['templateid'] = $lp ; 
              $DB->delete_records('competency_plan',$data) ; 
          } 
      } 
    }
}