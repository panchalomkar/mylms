<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/enroll_by_profile/lib.php');
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileCourses.php";
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileTenant.php";
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileLP.php";
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileCohort.php";
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileRole.php";
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileLearningPlans.php";
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileRule.php";
/**
 * EnrollByProfileUser
 *
 * @package local_enroll_by_profile
 * @author  Ajinkya D
 */
class EnrollByProfileUser {
    protected $db;
    protected $page;

    /*
     * Contruct Enroll by profile user Base object
     */
    public function __construct() {
        global $DB, $PAGE;
        $this->db = $DB;
        $this->page = $PAGE;
    }

    /** 
     * This function will execute each rule to given user accordingly running the functions 
     * CourseAssignment ,TenantAssignment, LPAssignment, CohortAssignment with method add 
     *  
     * @param $datauser event object ( user created/updated data )  
     * @author vaibhavg 
     * @desc sent null to AddUser() 
     */ 
    public function AddUserConditional($datauser,$blk,$uid){ 
        /** 
        * 1 = 'CourseAssignment'  
        * 2 = 'TenantAssignment'  
        * 3 = 'LPAssignment'  
        * 4 = 'CohortAssignment'  
        * 5 = 'RoleAssignment'  
        */  
        global $DB,$CFG ;  
        
        $rulesclass = new EnrollByProfileRule();
        $learningplansclass = new EnrollByProfileLearningPlans();
        $roleclass = new EnrollByProfileRole();
        $cohortclass = new EnrollByProfileCohort();
        $LPclass = new EnrollByProfileLP();
        $tenantclass = new EnrollByProfileTenant();
        $courseclass = new EnrollByProfileCourses();
        $debug = DebugSyncProcess(); 
        $rules = $rulesclass->GetRulesObject(null, null, null); 
        $userid = $uid; 
        $userrecord = $DB->get_record('user',array('id'=>$userid)); 
        profile_load_data($userrecord); 
        if($debug){ 
          var_dump($userrecord);  
        } 
        
        if(!$blk){  
            if(!is_null($datauser)) 
            $userdata = $datauser->get_data();  
            $userid = $userdata['relateduserid'] ;  
        }else{  
            $userdata = new stdClass; 
            $userdata->action = 'assigned'; 
            $userdata->objecttable = 'role';  
            $userid = $uid; 
        } 
        if($rules){ 
          foreach ($rules as $ruleid => $rule) {      
            if(($rule->disable_rule == 0) && ($rule->unenroll_rule == 0)) 
            {               
              $conditions = json_decode($rule->profile_field);                    
              $answers = array(); 
              switch ($rule->category) {  
                case 1: 
                  $method = 'CourseAssignment' ;  
                  $class = $courseclass;
                  break;  
                case 2: 
                  $method = 'TenantAssignment' ;  
                  $class = $tenantclass;
                  break;  
                case 3: 
                  $method = 'LPAssignment' ;  
                  $class = $LPclass;
                  break;  
                case 4: 
                  $method = 'CohortAssignment' ; 
                  $class = $cohortclass; 
                  break;  
                case 5: 
                  $method = 'RoleAssignment' ;  
                  $class = $roleclass;
                  break; 
                case 6: 
                  $method = 'LearningplansAssignment' ;
                  $class = $learningplansclass;  
                  break;  
              } 
              foreach ($conditions as $condid => $condition) {  
                if($debug){ 
                  var_dump($condition); 
                } 
                $userfield = 'profile_field_'.$condition->field ; 
                $normalfield = $condition->field ;  
                if(isset( $userrecord->$normalfield )) $field = $normalfield; 
                if(isset( $userrecord->$userfield ))  $field = $userfield;  
                if($debug){ 
                  var_dump($field); 
                } 
                /*  
                * @author VaibhavG  
                * @since 6th April 2021 
                * @desc modify code for tenant specific insert OR update custom fields values for user if that custom field has default data. 
                */  
                $company_user = $DB->get_record('company_users', array('userid' => $userid)); 
                if(!empty($company_user)){  
                  $sql = "SELECT uf.*   
                    FROM {user_info_field} uf   
                    JOIN {company} c ON uf.categoryid = c.profileid   
                    JOIN {user_info_category} uc ON c.profileid = uc.id   
                    WHERE uf.shortname = '".$condition->field."' AND c.id = $company_user->companyid";  
                  $defaultdata = $DB->get_record_sql($sql); 
                }else{  
                  $field_cat = $DB->get_record('user_info_field', array('shortname' => $condition->field)); 
                  $field_comp = $DB->get_record('company', array('profileid' => $field_cat->categoryid)); 
                  if(empty($field_comp)){ 
                    $defaultdata = $DB->get_record('user_info_field', array('shortname' => $condition->field)); 
                  } 
                } 
                /*  
                * @author VaibhavG  
                * @since 31 March 2021  
                * @desc insert OR update custom fields values for user if that custom field has default data. 
                */  
                if(!empty($defaultdata->defaultdata)){  
                  $data = new stdClass(); 
                  $data->userid  = $userid; 
                  $data->fieldid = $defaultdata->id;  
                  $data->data    = $defaultdata->defaultdata; 
                  if ($dataid = $DB->get_field('user_info_data', 'id', array('userid' => $userid, 'fieldid' => $defaultdata->id))) {  
                      $data->id = $dataid;  
                      $DB->update_record('user_info_data', $data);  
                  } else {  
                      $DB->insert_record('user_info_data', $data);  
                  } 
                } 
                $answer = new stdClass(); 
                /*  
                * @author VaibhavG  
                * @since 11th Feb 2021  
                * @desc 509 Rules Engine issues fixes. For checkbox, dropdown. multiselect fields 
                */  
                /*  
                * @author VaibhavG  
                * @since 20 Oct 2020  
                * @desc #265: Rule engine not enrolling users in the cohort (Empty Cohorts). This issue was raised for role type field & resolved by added below if() condition only. 
                */  
                if($condition->field == 'role'){  
                  if(($condition->rule === "contains")){  
                    $query = " SELECT ra.userid, r.shortname  
                           FROM {role_assignments} AS ra , {role} r 
                           WHERE ra.roleid = r.id and ra.userid = $userid AND r.shortname like '%".$condition->value[0]."%'"; 
                  }else if(($condition->rule === "exactmatch")){  
                    $query = " SELECT ra.userid, r.shortname  
                           FROM {role_assignments} AS ra , {role} r 
                           WHERE ra.roleid = r.id and ra.userid = $userid AND r.shortname = '".$condition->value[0]."'";  
                  } 
                    $userroles = $DB->get_record_sql($query); 
                    if ($condition->negated === "true" || $condition->negated === true ){ 
                       $answer->result = evalConditional($condition->negatedrule, $condition->value, $userroles->shortname);  
                    }else if($condition->negated === "false" || $condition->negated === false ){  
                       $answer->result = evalConditional($condition->rule, $condition->value, $userroles->shortname); 
                    } 
                } 
                /*  
                * @author VaibhavG  
                * @since 22 March 2021  
                * @desc 509 Rules Engine issues fixes. Added tenant field 
                */  
                else if($condition->field == 'tenant'){ 
                  if ($condition->negated === "true" || $condition->negated === true ){ 
                      if(($condition->negatedrule === "notisselected")){  
                        $company_id = $DB->get_record('company', array('name' => $condition->value[0]));  
                        $companyusers = $DB->get_record_sql("select c.name as companyname from {company} c JOIN {company_users} cu ON c.id = cu.companyid where cu.companyid != $company_id->id AND cu.userid = $userid");  
                         $answer->result = evalConditional($condition->negatedrule, $condition->value, $companyusers->companyname); 
                        } 
                      }else if($condition->negated === "false" || $condition->negated === false ){  
                        if(($condition->rule === "isselected")){  
                          $company_id = $DB->get_record('company', array('name' => $condition->value[0]));  
                          $companyusers = $DB->get_record_sql("select c.name as companyname from {company} c JOIN {company_users} cu ON c.id = cu.companyid where cu.companyid = $company_id->id AND cu.userid = $userid"); 
                          $answer->result = evalConditional($condition->rule, $condition->value, $companyusers->companyname); 
                      } 
                    } 
                }else{  
                  if ($condition->negated === "true" || $condition->negated === true ){ 
                       if($debug){  
                         echo "negated!!";  
                       }  
                      if(($condition->negatedrule === "notisselected")){  
                        $isselected_val = $DB->get_record_sql("SELECT uid.data FROM {user_info_field} uif JOIN {user_info_data} uid ON uif.id = uid.fieldid WHERE uif.shortname = '$condition->field' AND uid.userid = $userid"); 
                        $userrecord->$field = $isselected_val->data;  
                        $condition->value[0] = $condition->value[0];  
                      } 
                      if(($condition->negatedrule === "nothasselected") || ($condition->negatedrule === "nothasanyselected")){  
                          $hasselected_val = $DB->get_record_sql("SELECT uid.data FROM {user_info_field} uif JOIN {user_info_data} uid ON uif.id = uid.fieldid WHERE uif.shortname = '$condition->field' AND uid.userid = $userid");  
                          $userrecord->$field = !empty($hasselected_val->data) ? explode(PHP_EOL, $hasselected_val->data) : ''; 
                      } 
                      if($debug){ 
                        echo '$condition->rule='.$condition->negatedrule;  
                        echo '$condition->value=';  
                        print_object($condition->value);  
                        echo '$userrecord->$field'; 
                        print_object($userrecord->$field);  
                      } 
                      $answer->result = evalConditional($condition->negatedrule, $condition->value, $userrecord->$field); 
                  }else if($condition->negated === "false" || $condition->negated === false ){  
                      if($debug){ 
                        echo "straight!!";  
                      } 
                      if(($condition->rule === "ischecked") || ($condition->rule === "isnotchecked")){                        
                        $ischecked_val = $DB->get_record_sql("SELECT uid.data FROM {user_info_field} uif JOIN {user_info_data} uid ON uif.id = uid.fieldid WHERE uif.shortname = '$condition->field' AND uid.userid = $userid");  
                        $condition->value[0] = !empty($ischecked_val->data) ? $ischecked_val->data : '';  
                      } 
                      if(($condition->rule === "isselected")){  
                        $condition->value[0] = $condition->value[0];  
                        $isselected_val = $DB->get_record_sql("SELECT uid.data FROM {user_info_field} uif JOIN {user_info_data} uid ON uif.id = uid.fieldid WHERE uif.shortname = '$condition->field' AND uid.userid = $userid"); 
                        $userrecord->$field = $isselected_val->data;  
                      } 
                      if(($condition->rule === "hasselected") || ($condition->rule === "hasanyselected")){  
                          $hasselected_val = $DB->get_record_sql("SELECT uid.data FROM {user_info_field} uif JOIN {user_info_data} uid ON uif.id = uid.fieldid WHERE uif.shortname = '$condition->field' AND uid.userid = $userid");  
                          $userrecord->$field = explode(PHP_EOL, $hasselected_val->data); 
                      } 
                      if($debug){ 
                        echo '$condition->rule='.$condition->rule; 
                        echo '$condition->value=';  
                        print_object($condition->value);  
                        echo '$userrecord->$field'; 
                        print_object($userrecord->$field);  
                        echo '$condition->field='.$condition->field;  
                      } 
                      $answer->result = evalConditional($condition->rule, $condition->value, $userrecord->$field);  
                  } 
                } 
                $answer->boolop = $condition->boolop; 
                $answer->debug = $debug;  
                array_push($answers, $answer);  
                if($debug){ 
                 var_dump($answers);  
                } 
              } 
              if(!empty($answers))  
                $total = evalConditionsBool($answers);  
              else  
                $total = 0; 
              if($debug){ 
                echo "HERE!!!"; 
                var_dump($total); 
              } 
              $elementid = array(); 
              $ruleElements = ''; 
              $ruleElements = str_replace('[', '', $rule->selected_elements); 
              $ruleElements = str_replace(']', '', $ruleElements);  
              $ruleElements = str_replace('"', '', $ruleElements);  
              $elementid = explode(',', $ruleElements); 
              if($total>0){ 
                $class->$method($userid,$elementid,'add');
                if(CLI_SCRIPT){ 
                    mtrace("<div class='applied_rule'><br><b>:::Applied Rule ".$ruleid." For User ".$userid.":::</b><br></div>");
                }  
                EnrollByProfileUser::AddUserRule($userid,$ruleid);  
              }         
          } 
        } 
      } 
    }

    /** 
    * This function will return all available user profile fields.  
    * @return $output array profile fields $array['shortname']='profile string'.  
    */
    public function get_user_fields_all(){ 
        global $DB,$CFG;  
        $fields = $DB->get_records_sql('select * from {user_info_field} where categoryid <> 1 ') ;  
        $user = $DB->get_records_sql("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'mdl_user' AND COLUMN_NAME IN ('Username','firstname','lastname','email','City','Country','Firstaccess','moodlenetprofile','Company','Subordinates','Marketing','Sales','Flag','Designation','role','tenant')"); 
         
        $user = array_keys($user);  
        for ($i=0; $i < count($user); $i++) { 
            if ( preg_match('/\[/', get_string($user[$i]) ) ) 
            { 
                $user[$user[$i]] = array('type'=>'text','name'=>$user[$i]) ;  
            } 
            else  
            { 
                $user[$user[$i]]  = array('type'=>'text','name'=>get_string($user[$i])) ; 
            } 
            unset($user[$i]) ;  
        } 
        foreach ($fields as $key => $value) { 
            if($value->shortname){  
                $user[$value->shortname] = array('type'=>$value->datatype,'name'=>$value->name);  
            } 
        } 
        $user['role'] =  array('type'=>'text','name'=>'role') ; 
        /*  
        * @author : VaibhavG  
        * @since  : 22 March 2021 
        * @desc   : adding new field tenant.  
        */  
        $user['tenant'] =  array('type'=>'tenant','name'=>'tenant') ;
        return $user ;  
    } 

    /** 
     * This function will execute each rule to given user accordingly running the functions 
     * CourseAssignment ,TenantAssignment, LPAssignment, CohortAssignment with method add 
     *  
     * @param $datauser event object ( user created/updated data )  
     * @author vaibhavg 
     * @desc sent null to AddUser() 
     */ 
    //DEPRECATED THIS FUNCTION  
    public function AddUser($datauser,$blk=null,$uid){ 
        /** 
        * 1 = 'CourseAssignment'  
        * 2 = 'TenantAssignment'  
        * 3 = 'LPAssignment'  
        * 4 = 'CohortAssignment'  
        */  
        global $DB,$CFG;
        $rulesclass = new EnrollByProfileRule();
        $learningplansclass = new EnrollByProfileLearningPlans();
        $roleclass = new EnrollByProfileRole();
        $cohortclass = new EnrollByProfileCohort();
        $LPclass = new EnrollByProfileLP();
        $tenantclass = new EnrollByProfileTenant();
        $courseclass = new EnrollByProfileCourses(); 
        $rules = $rulesclass->GetRulesObject(null, null, null); 
        if(!$blk){ 
            if(!is_null($datauser)) 
            $userdata = $datauser->get_data();  
            $userid = $userdata['relateduserid'] ;  
        }else{  
            $userdata = new stdClass; 
            $userdata->action = 'assigned'; 
            $userdata->objecttable = 'role';  
            $userid = $uid; 
        } 
        $userrecord = $DB->get_record('user',array('id'=>$userid)); 
        profile_load_data($userrecord); 
        if($rules){ 
            foreach ($rules as $ruleid => $rule) {  
                $elementid = array(); 
                $ruleElements = ''; 
                $userfield = 'profile_field_'.$rule->profile_field ;  
                $normalfield = $rule->profile_field ; 
                $field =''; 
                if(isset( $userrecord->$normalfield )) $field = $normalfield; 
                if(isset( $userrecord->$userfield ))  $field = $userfield;  
                if($rule->profile_field <> 'role' ){  
                    if( !isset($userrecord->$field)) continue ; 
                    $varfl = $userrecord->$field; 
                } 
                $ruleElements = str_replace('[', '', $rule->selected_elements); 
                $ruleElements = str_replace(']', '', $ruleElements);  
                $ruleElements = str_replace('"', '', $ruleElements);  
                $elementid = explode(',', $ruleElements); 
                switch ($rule->category) {  
                    case 1: 
                      $method = 'CourseAssignment' ;  
                      $class = $courseclass;
                      break;  
                    case 2: 
                      $method = 'TenantAssignment' ;  
                      $class = $tenantclass;
                      break;  
                    case 3: 
                      $method = 'LPAssignment' ;  
                      $class = $LPclass;
                      break;  
                    case 4: 
                      $method = 'CohortAssignment' ; 
                      $class = $cohortclass; 
                      break;  
                    case 5: 
                      $method = 'RoleAssignment' ;  
                      $class = $roleclass;
                      break; 
                    case 6: 
                      $method = 'LearningplansAssignment' ;
                      $class = $learningplansclass;  
                      break;     
                } 
                if($rule->profile_field <> 'role' ){  
                    $fielddata = $DB->get_record('user_info_field',array('shortname'=>$rule->profile_field)); 
                    if($fielddata){ 
                        /* Lets process custom profile fields */  
                        ProcessAddProfileFields($rule,$varfl,$method,$userid,$elementid,$ruleid,$fielddata) ;  
                    }else{  
                        $rulecontent = str_replace('[{content:', '', $rule->content); 
                        $rulecontent = str_replace(',}]', '', $rulecontent);  
                        /* Lets process a non custom profile field */ 
                        if(strcmp(transliterateString($rulecontent),transliterateString($userrecord->$field)) == 0){  
                            $class->$method($userid,$elementid,'add'); 
                            EnrollByProfileUser::AddUserRule($userid,$ruleid);  
                        } 
                    } 
                }else{  
                    if( $userdata->action=='assigned' && $userdata->objecttable=='role' ){  
                        //lets process role 
                        ProcessRoleAddField($userid,$elementid,$ruleid,$method,$rule) ;  
                    } 
                } 
            } 
        } 
    } 
     
    public function MatchUserCond($uid){ 
        /** 
        * 1 = 'CourseAssignment'  
        * 2 = 'TenantAssignment'  
        * 3 = 'LPAssignment'  
        * 4 = 'CohortAssignment'  
        */  
        global $DB,$CFG;
        $rulesclass = new EnrollByProfileRule();
        $learningplansclass = new EnrollByProfileLearningPlans();
        $roleclass = new EnrollByProfileRole();
        $cohortclass = new EnrollByProfileCohort();
        $LPclass = new EnrollByProfileLP();
        $tenantclass = new EnrollByProfileTenant();
        $courseclass = new EnrollByProfileCourses(); 
        $rules = $rulesclass->GetRulesObject(null, null, null); 
        $blk = true;  
        if(!$blk){  
            if(!is_null($datauser)) 
            $userdata = $datauser->get_data();  
            $userid = $userdata['relateduserid'] ;  
        }else{  
            $userdata = new stdClass; 
            $userdata->action = 'assigned'; 
            $userdata->objecttable = 'role';  
            $userid = $uid; 
        } 
        $userrecord = $DB->get_record('user',array('id'=>$userid)); 
        profile_load_data($userrecord); 
        if($rules){ 
            foreach ($rules as $ruleid => $rule) {  
        $conditionals = array();  
        $conditionals = json_decode($rule->profile_field);  
          
        foreach($conditionals as $conditional){ 
            $userfield = 'profile_field_'.$conditional->field ; 
            $normalfield = $conditional->field ;  
            $field =''; 
            if(isset( $userrecord->$normalfield )) $field = $normalfield; 
            if(isset( $userrecord->$userfield ))  $field = $userfield;  
        } 
                $elementid = array(); 
                $ruleElements = ''; 
                if($rule->profile_field <> 'role' ){  
                    if( !isset($userrecord->$field)) continue ; 
                    $varfl = $userrecord->$field; 
                } 
                $ruleElements = str_replace('[', '', $rule->selected_elements); 
                $ruleElements = str_replace(']', '', $ruleElements);  
                $ruleElements = str_replace('"', '', $ruleElements);  
                $elementid = explode(',', $ruleElements); 
                switch ($rule->category) {  
                    case 1: 
                      $method = 'CourseAssignment' ;  
                      $class = $courseclass;
                      break;  
                    case 2: 
                      $method = 'TenantAssignment' ;  
                      $class = $tenantclass;
                      break;  
                    case 3: 
                      $method = 'LPAssignment' ;  
                      $class = $LPclass;
                      break;  
                    case 4: 
                      $method = 'CohortAssignment' ; 
                      $class = $cohortclass; 
                      break;  
                    case 5: 
                      $method = 'RoleAssignment' ;  
                      $class = $roleclass;
                      break; 
                    case 6: 
                      $method = 'LearningplansAssignment' ;
                      $class = $learningplansclass;  
                      break;    
                } 
                if($rule->profile_field <> 'role' ){  
                    $fielddata = $DB->get_record('user_info_field',array('shortname'=>$rule->profile_field)); 
                    if($fielddata){ 
                        /* Lets process custom profile fields */  
                        ProcessAddProfileFields($rule,$varfl,$method,$userid,$elementid,$ruleid,$fielddata) ;  
                    }else{  
                        $rulecontent = str_replace('[{content:', '', $rule->content); 
                        $rulecontent = str_replace(',}]', '', $rulecontent);  
                        /* Lets process a non custom profile field */ 
                        if(strcmp(transliterateString($rulecontent),transliterateString($userrecord->$field)) == 0){  
                            $class->$method($userid,$elementid,'add'); 
                            EnrollByProfileUser::AddUserRule($userid,$ruleid);  
                        } 
                    } 
                }else{  
                    if( $userdata->action=='assigned' && $userdata->objecttable=='role' ){  
                        //lets process role 
                        ProcessRoleAddField($userid,$elementid,$ruleid,$method,$rule) ;  
                    } 
                } 
            } 
        } 
    } 


     
    /** 
    * This function will execute each rule to given user accordingly running the functions  
    * CourseAssignment ,TenantAssignment, LPAssignment, CohortAssignment with method remove 
    * @param $datauser event object ( user created/updated data ) 
    */  
    public function RemoveUser($datauser,$blk,$uid){ 
        global $DB,$CFG;
        $learningplansclass = new EnrollByProfileLearningPlans();
        $roleclass = new EnrollByProfileRole();
        $cohortclass = new EnrollByProfileCohort();
        $LPclass = new EnrollByProfileLP();
        $tenantclass = new EnrollByProfileTenant();
        $courseclass = new EnrollByProfileCourses();
        //query modified by ShivkumarY on 03092019 because of performance 
        $sql = " SELECT lel.*   
            FROM {local_enroll_by_profile} as lel 
            INNER JOIN {local_enrol_prof_user_rule} as lelr 
            ON lel.id = lelr.ruleid 
            AND lelr.userid = ? ";  
      
        if(!$blk){  
            if(!is_null($datauser)) 
            $userdata = $datauser->get_data();  
               $userid = $userdata['relateduserid'] ; 
        }else{  
            $userdata = new stdClass; 
            $userdata->action = 'assigned'; 
            $userdata->objecttable = 'role';  
             $userid = $uid;  
        } 
        
        $rules = $DB->get_records_sql($sql,array($userid)); 
        $userrecord = $DB->get_record('user',array('id'=>$userid)); 
        profile_load_data($userrecord); 
          if($rules){ 
            foreach ($rules as $ruleid => $rule) {  
              //if(($rule->disable_rule == 0) && ($rule->unenroll_rule == 0))
              if(($rule->disable_rule == 0)) 
              { 
                $conditions = json_decode($rule->profile_field);  
                switch ($rule->category) {  
                    case 1: 
                      $method = 'CourseAssignment' ;  
                      $class = $courseclass;
                      break;  
                    case 2: 
                      $method = 'TenantAssignment' ;  
                      $class = $tenantclass;
                      break;  
                    case 3: 
                      $method = 'LPAssignment' ;  
                      $class = $LPclass;
                      break;  
                    case 4: 
                      $method = 'CohortAssignment' ; 
                      $class = $cohortclass; 
                      break;  
                    case 5: 
                      $method = 'RoleAssignment' ;  
                      $class = $roleclass;
                      break; 
                    case 6: 
                      $method = 'LearningplansAssignment' ;
                      $class = $learningplansclass;  
                      break;    
                } 
                $elementid = array(); 
                foreach ($conditions as $condid => $condition)  
                { 
                  $ruleElements = ''; 
                  $userfield = 'profile_field_'.$condition->field ; 
                  $normalfield = $condition->field ;  
                  $field =''; 
                  if(isset( $userrecord->$normalfield )) $field = $normalfield; 
                  if(isset( $userrecord->$userfield ))  $field = $userfield;  
                  $varfl = !empty($userrecord->$field)?$userrecord->$field:'';  
                  $ruleElements = str_replace('[', '', $rule->selected_elements); 
                  $ruleElements = str_replace(']', '', $ruleElements);  
                  $ruleElements = str_replace('"', '', $ruleElements);  
                  $elementid = explode(',', $ruleElements); 
                    
                  if( $condition->field !== 'role' ){ 
                      $fielddata = $DB->get_record('user_info_field',array('shortname'=>$condition->field));  
                      if($fielddata){ 
                          /* Lets process custom profile fields */  
                          ProcessRemoveProfileFields($rule,$varfl,$method,$userid,$elementid,$ruleid,$fielddata,$condition->value,$condition->rule); 
                      }else{  
                          /* Lets process a non custom profile field */ 
                             
                                if( strtolower($condition->value[0]) != strtolower($varfl) ) {  
                                  $class->$method($userid,$elementid,'remove','removeuser');
                                   if(CLI_SCRIPT){ 
                                        mtrace("<div class='removed_rule'><br><b>:::Removed Rule ".$ruleid." For User ".$userid.":::</b><br></div>");
                                   }  
                                  EnrollByProfileUser::RemoveUserRule($userid,$ruleid); 
                                } 
                              
                            } 
                  }else{  
                      ProcessRoleRemoveField($userid,$elementid,$ruleid,$method,$rule,$condition->value);  
                        
                  } 
                } 
              } 
            } 
          } 
    } 
    /** 
    * This function will create the relation between user and rule ( where user has been assigned / enrolled )  
    * @param $userid user id  
    * @param $ruleid rule id  
    */  
    public function AddUserRule($userid,$ruleid){  
        global $DB; 
        $data = new stdClass(); 
        $data->userid = $userid;  
        $data->ruleid = $ruleid;  
        $DB->insert_record('local_enrol_prof_user_rule',$data); 
    } 
    /** 
    * This function will remove the relation between user and rule ( where user has been assigned / enrolled )  
    * @param $userid user id to be removed  
    * @param $ruleid rule to be removed 
    */  
    public function RemoveUserRule($userid,$ruleid){ 
        global $DB; 
        $data = array();  
        $data['userid'] = $userid;  
        $data['ruleid']= $ruleid; 
        $DB->delete_records('local_enrol_prof_user_rule',$data);  
    }
}