<?php
defined('MOODLE_INTERNAL') || die;

/**
 * EnrollByProfileRule
 *
 * @package local_enroll_by_profile
 * @author  Ajinkya D
 */
class EnrollByProfileRule {
    protected $db;
    protected $page;

    /*
     * Contruct Enroll by profile rule object
     */
    public function __construct() {
        global $DB, $PAGE;
        $this->db = $DB;
        $this->page = $PAGE;
    }

    //count the rules 
    public function CountRule(){ 
        global $DB; 
        $rulescount = $DB->count_records('local_enroll_by_profile'); 

        return $rulescount;
    } 
      
    public function GetRulesCount(){ 
        global $DB; 
        return $rulescount = $DB->count_records('local_enroll_by_profile'); 
    } 
    
    /** 
    * This function will fetch all rules inserted on local_enroll_by_profile and will return html content with all the rules created. 
    * @return $output HTML content table plus edit and delete icons on each rule on a html table  
    */  
    public function GetRulesFromDB($search_value, $tags, $search_page){  
        global $DB, $CFG, $OUTPUT;  
        $page = optional_param('page', 0, PARAM_INT); 
        $search_page = !empty($page) ? $page : $search_page;  
        $perpage = optional_param('perpage', 10, PARAM_INT);                  
        $allrulescount = EnrollByProfileRule::GetRulesCount();  
        $rules = EnrollByProfileRule::GetRulesObject(null,$page,$perpage,$search_value, $search_page);  
        /*  
        *@author vaibhavG 
        *@desc sent null to GetRulesObject()  
        */  
         
        $output ='' ; 
        $is_tags = '';
        if($tags != 1){ 
          $is_tags = 1; 
        }else{
          $is_tags = '';  
        } 
        $rulescount = $DB->count_records('local_enroll_by_profile');  

        if($rules){ 
            $t_head_f = '';  
              
            $rulestable = new stdClass(); 
            $id = 1 + $perpage * $page; 
              
            foreach ($rules as $ruleid => $rule) {  
                $courses = '';  
                $companys ='';  
                $lps='';  
                $cohorts='';  
                $roles='';  
                $checkboxes = ''; 
                $keep_unenroll = '';  
                $coursescontent=''; 
                $coursefullname = '';
                $checkboxes .= $ruleid; 
                if($rule->unenroll_rule == 0){  
                  $keep_unenroll .= 'checked';  
                } 
                else if($rule->unenroll_rule == 1){ 
                  $keep_unenroll .= 'unchecked'; 
                }
                 
                $iconsdisable = '';  
                if($rule->disable_rule == 0)  
                { 
                  $icons .=   get_string('disable','local_enroll_by_profile');  
                } 
                if($rule->disable_rule == 1)  
                { 
                  $icons .=   get_string('enable','local_enroll_by_profile');

                  $iconsdisable = get_string('disabledrule','local_enroll_by_profile');          
                } 
             
                $ruleElements = str_replace('[', '', $rule->selected_elements); 
                $ruleElements = str_replace(']', '', $ruleElements);  
                $ruleElements = str_replace('"', '', $ruleElements);  
                $la_element = explode(',', $ruleElements);  
          
                if(intval($rule->category) == 1) {  
                    $i = 0; 
                    foreach ($la_element as $key => $value) { 
                        $course = $DB->get_record('course',array('id'=>$value));  
                        $i++; 
                        $selectedelements = $course->fullname;
                        if($i==1){  
                          $courses='';
                          $courses = $CFG->wwwroot.'/course/view.php?id='.$course->id;  
                        } 
                        if($i>1){ 
                            $coursescontent .= $CFG->wwwroot.'/course/view.php?id='.$course->id;    
                        } 
                    } 
                           
                
                }else if(intval($rule->category) == 2){ 
                    $i = 0; 
                    foreach ($la_element as $key => $value) { 
                        $company = $DB->get_record('company',array('id'=>$value));  
                        $i++; 
                        $selectedelements = $company->name;
                        if($i==1){  
                          $companys = $CFG->wwwroot.'/local/mt_dashboard/index.php?company='.$course->id;  
                        } 
                        if($i>1){ 
                            $companys_content .= $CFG->wwwroot.'/local/mt_dashboard/index.php?company='.$course->id;   
                        } 
                          
                    } 
                    
                }else if(intval($rule->category) == 3){ 
                    $i = 0; 
                    foreach ($la_element as $key => $value) { 
                        $lp = $DB->get_record('learningpaths',array('id'=>$value)); 
                        $i++; 
                        $selectedelements = $lp->name;
                        if($i==1){  
                          $lps = $CFG->wwwroot.'/local/learningpaths/view.php?id='.$lp->id;   
                        } 
                        if($i>1){ 
                            $lps_content .= $CFG->wwwroot.'/local/learningpaths/view.php?id='.$lp->id;    
                        }     
                    } 
                    
                }else if(intval($rule->category) == 4){ 
                  $i = 0; 
                    foreach ($la_element as $key => $value) { 
                        $cohort = $DB->get_record('cohort',array('id'=>$value));  
                        $i++; 
                        $selectedelements = $cohort->name;
                        if($i==1){  
                          $cohorts = $CFG->wwwroot.'/cohort/index.php?contextid='.$cohort->contextid;  
                        } 
                        if($i>1){ 
                            $cohorts_content .= $CFG->wwwroot.'/cohort/index.php?contextid='.$cohort->contextid;
                        } 
                          
                    } 
                    
                }else if(intval($rule->category) == 5){ 
                    $i = 0; 
                    foreach ($la_element as $key => $value) { 
                        $role = $DB->get_record('role',array('id'=>$value));  
                        $rolename = role_fix_names(array($role), $systemcontext, ROLENAME_ORIGINAL);  
                        $i++; 
                        $selectedelements = $rolename[0]->localname;
                        if($i==1){  
                          $roles .= $rolename[0]->localname; 
                        } 
                        if($i>1){ 
                            $role_content .= $rolename[0]->localname;  
                        }     
                    } 
                     
                } 
                  
                if($courses) $elements_content = $courses ; 
                if($companys) $elements_content = $companys ; 
                if($lps) $elements_content = $lps ; 
                if($cohorts) $elements_content = $cohorts ; 
                if($roles) $elements_content = $roles ; 
                $content = $rule->content ; 
                $content = str_replace('[{', '',  $rule->content);  
                $content = str_replace('}]', '',  $content);  
                if(strpos($rule->content, '_date:')) {  
                    $content = str_replace(':', '',  $content); 
                    $content = str_replace('start_date', get_string('from','local_enroll_by_profile'),  $content);  
                    $content = str_replace('end_date', get_string('to','local_enroll_by_profile'),  $content);  
                    $content =  trim($content,','); 
                }elseif( strpos($rule->content, 'checked:') ){  
                    $content =  trim($content,','); 
                    $content = str_replace('content:', '',  $content);  
                    $content = str_replace('checked:', '',  $content);  
                    $content = str_replace('0', get_string('checked0','local_enroll_by_profile'),  $content); 
                    $content = str_replace('1', get_string('checked1','local_enroll_by_profile'),  $content); 
                }else{  
                    $content =  trim($content,','); 
                    $content = str_replace('content:', '',  $content);  
                } 
                $la_content = $rule->rulename ; 
                  
                /*  
                * @author VaibhavG  
                * @since 3rd March 2021 
                * @desc 509 Rules Engine issues fixes. Got the error at development debug mode. So, changed the structure.  
                */  
                $category = ''; 
                switch ($rule->category){ 
                  case 1: 
                    $category = get_string('one', 'local_enroll_by_profile'); 
                  break;  
                  case 2: 
                    $category = get_string('two', 'local_enroll_by_profile'); 
                  break;  
                  case 3: 
                    $category = get_string('three', 'local_enroll_by_profile'); 
                  break;  
                  case 4: 
                    $category = get_string('four', 'local_enroll_by_profile');  
                  break;  
                  case 5: 
                    $category = get_string('five', 'local_enroll_by_profile');  
                  break;
                  case 6: 
                    $category = get_string('six', 'local_enroll_by_profile');  
                  break;   
                } 
                  
                $arrow =   $id;  
                $row = array( 
                    $checkboxes,  
                    $rule->name,  
                    $la_content,  
                    $elements_content,  
                    $category,  
                    $keep_unenroll, 
                    $selectedelements,
                    $iconsdisable ,
                    $icons,  
                    $arrow
                );  
                $coursese_link = '' ; 
                $ruleElements = '' ;  
                $rulestable->rules[] = $row; 
                $id++;  
            } 
            $rulestable->is_tags = $is_tags;
            $rulestable->allrulescount = $allrulescount;
            $totalpages = ceil($allrulescount/$perpage);
            $pages = [];
            $lastpage = '';
            for ($i=0; $i < $totalpages; $i++) { 
                if($i == $page){
                    $active = 1;
                }else{
                    $active = 0;
                }
                $pages[] = ['url'=>$CFG->wwwroot.'/local/enroll_by_profile/index.php?page='.$i,'page'=>$i+1,'active'=>$active];
                $lastpage = $i;
            }
            
            $rulestable->pages = $pages;
            $rulestable->totalpages = $totalpages;

            if($page == 0){
                $rulestable->previous = 0;
            }else{
                $rulestable->previous = $CFG->wwwroot.'/local/enroll_by_profile/index.php?page='.($page-1);
            }
            if($lastpage == $page){
                $rulestable->next = 0;
            }else{
                $rulestable->next = $CFG->wwwroot.'/local/enroll_by_profile/index.php?page='.($page+1);
            }   
            $icons_all = ''; 
            return $rulestable;
                    
              
              
        }
         
        return $output ;  
    } 
    
    /** 
    * This function will delete a given rule by id. 
    * @param $rid rule id to be deleted 
    * @return json object like $array[msg] = 'message' ( success or fail ) , $array[table] ( rules table content )  
    */  
    public function DeleteRule($rid){  
        global $DB ;  
        $DB->delete_records('local_enroll_by_profile',array('id'=>$rid)); 
        return array('msg'=>get_string('deleterecord','local_enroll_by_profile'));  
    } 

    public function DeleteAllRule($rid){ 
        global $DB ;  
        foreach($rid as $rule){
          $DB->delete_records('local_enroll_by_profile',array('id'=>$rule));  
        } 
        return array('msg'=>get_string('deleterecord','local_enroll_by_profile'));  
    }

    public function AllDeleteRule(){ 
      global $DB ;
      $DB->delete_records('local_enroll_by_profile', array());
      return array('msg'=>get_string('deleterecord','local_enroll_by_profile'));
    } 
    /*  
    * @author VaibhavG  
    * @since 2nd March 2021 
    * @desc 509 Rules Engine issues fixes. Applied unenroll users from rule categories  
    */  
    public function unenrollRule($rule)  
    { 
        global $DB; 
        $record = new stdClass();
        $record->id = $rule;
        $record->unenroll_rule = 0;
        
        $enabled = $DB->update_record('local_enroll_by_profile',$record);  
        return $enabled;  
    } 

    public function unenrollAllRule($rule) 
    { 
        global $DB; 
        $array = array_unique($rule);
        $emptyRemoved = array_filter($array);
        $rule = array_values($emptyRemoved);

        /*foreach($rule as $id){  
          if(empty($id))
            continue;
        
          $record = new stdClass();
          $record->id = $id;
          $record->unenroll_rule = 0;
          
          $enabled = $DB->update_record('local_enroll_by_profile',$record);   
          return $enabled;  
        }*/
        for($i=0; $i<count($rule); $i++) {
          if($rule[$i]) {
            $record = new stdClass();
            $record->id = $rule[$i];
            $record->unenroll_rule = 0;
            
            $enabled = $DB->update_record('local_enroll_by_profile',$record);    
            //return $enabled;  
          }
        }
    } 

    public function enrollRule($rule)  
    { 
        global $DB; 
        $record = new stdClass();
        $record->id = $rule;
        $record->unenroll_rule = 1;
        
        $enabled = $DB->update_record('local_enroll_by_profile',$record);   
        return $enabled;  
    } 

    public function enrollAllRule($rule) 
    { 
        global $DB;

        $array = array_unique($rule);
        $emptyRemoved = array_filter($array);
        $rule = array_values($emptyRemoved);

        for($i=0; $i<count($rule); $i++) {
          
            $record = new stdClass();
            $record->id = $rule[$i];
            $record->unenroll_rule = 1;
            
            $enabled = $DB->update_record('local_enroll_by_profile',$record);    
            //return $enabled;  
        }
        /*foreach($rule as $id){  
          if(empty($id))
            continue;
            
            $record = new stdClass();
            $record->id = $rule;
            $record->unenroll_rule = 1;
            
            $enabled = $DB->update_record('local_enroll_by_profile',$record);    
            return $enabled;  
        } */
    } 
     
    /*  
    * @author VaibhavG  
    * @since 11th Feb 2021  
    * @desc 509 Rules Engine issues fixes. Applied disbale & enable rule feature  
    */  
    public function disableRule($rule) 
    { 
        global $DB; 
        $record = new stdClass();
        $record->id = $rule;
        $record->disable_rule = 1; 
        $disabled = $DB->update_record('local_enroll_by_profile',$record); 
        return $disabled; 
    } 
    /*  
    * @author VaibhavG  
    * @since 2nd March 2021 
    * @desc 509 Rules Engine issues fixes. Applied disbale & enable rule feature to all selected rule 
    */  
    public function disableAllRule($rule)  
    { 
        global $DB; 
        foreach($rule as $id){  
          $get_existing_res = $DB->get_record('local_enroll_by_profile', array('id' => $id)); 
          if($get_existing_res->disable_rule == 0)  
          { 
            $record = new stdClass();
            $record->id = $id;
            $record->disable_rule = 1; 
            $disabled = $DB->update_record('local_enroll_by_profile',$record);  
          }elseif($get_existing_res->disable_rule == 1) 
          { 
            $record = new stdClass();
            $record->id = $id;
            $record->disable_rule = 0; 
            $disabled = $DB->update_record('local_enroll_by_profile',$record);  
          } 
          
        } 
    }

    public function allDisableRule(){ 
      global $DB ;
       
      $record = new stdClass();
      $record->disable_rule = 1; 
      $disabled = $DB->update_record('local_enroll_by_profile',$record);
      return array('msg'=>get_string('deleterecord','local_enroll_by_profile'));
    } 
    /*  
    * @author VaibhavG  
    * @since 11th Feb 2021  
    * @desc 509 Rules Engine issues fixes. Applied disbale & enable rule feature  
    */  
    public function enableRule($rule)  
    { 
        global $DB; 
        $record = new stdClass();
        $record->id = $rule;
        $record->disable_rule = 0; 
        $enabled = $DB->update_record('local_enroll_by_profile',$record);  
         
        return $enabled;  
    } 
     
    /** 
    * this function will return all the rules saved 
    * @param $ruleid rule id  
    * @return $rules stdObject with all the rules or espesific rule if id given 
    */  
    public function GetRulesObject($ruleid, $page, $perpage, $search_value = null, $search_page = null){ 
        global $DB; 
        if(!empty($search_value)){  
        $data = array();  
        if($ruleid) 
            $data['id'] = $ruleid;  
        $limit = $page * $perpage;  
        switch ($search_value){ 
          case 'course': 
            $search_value_cate = 1; 
          break;  
          case 'tenant': 
            $search_value_cate = 2;
          break;  
          case 'lp': 
            $search_value_cate = 3; 
          break;  
          case 'cohort': 
            $search_value_cate = 4;  
          break;  
          case 'role': 
            $search_value_cate = 5;  
          break;
          case 'learning plans': 
            $search_value_cate = 6;  
          break;   
        }  
        if($search_value_cate) {
          $search_value_cate = " or category = $search_value_cate";
        }
        $sql = "SELECT * FROM {local_enroll_by_profile} WHERE rulename like '%$search_value%' or name like '%$search_value%' $search_value_cate ";  
        $rules = $DB->get_records_sql($sql,array(null),$limit, $perpage);  
        $sql_count = "SELECT count(id) as count FROM {local_enroll_by_profile} WHERE name like '%$search_value%'";  
        $rulescount = $DB->get_record_sql($sql_count);  
        $rulescount = $rulescount->count; 
        if($rules && $ruleid){  
          $fieldtype = $DB->get_record('user_info_field',array('shortname'=>$rules[$ruleid]->profile_field)); 
          if(!$fieldtype) $fieldtype->datatype = 'text';  
          return array('msg' => 'ruleexist','table' => $rules ,'html'=> $inputoutput ,'fieldtype'=>$fieldtype->datatype ); 
        }else{  
                return $rules;  
        }         
      }else{  
        $data = array();  
        $page = $search_page; 
        if($ruleid) 
            $data['id'] = $ruleid;  
        $limit = $page * $perpage;  
        $rulescount = $DB->count_records('local_enroll_by_profile');  
        $rules = $DB->get_records('local_enroll_by_profile' , $data, $sort='', $fields='*', $limit, $perpage);  
        if($rules && $ruleid){  
          $fieldtype = $DB->get_record('user_info_field',array('shortname'=>$rules[$ruleid]->profile_field)); 
          if(!$fieldtype) $fieldtype->datatype = 'text';  
          return array('msg' => 'ruleexist','table' => $rules ,'html'=> $inputoutput ,'fieldtype'=>$fieldtype->datatype ); 
        }else{  
          return $rules;  
        } 
      } 
    } 
}