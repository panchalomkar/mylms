  <?php 


require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileCourses.php";
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileTenant.php";
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileLP.php";
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileCohort.php";
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileRole.php";
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileLearningPlans.php";
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileRule.php";

defined('MOODLE_INTERNAL') || die();  
 
    /*  
    * @author Ajinkya D  
    * @since 4th Oct 2021  
    * @desc The following function will return html only for AND & OR buttons and json data for add/edit rules form
    */  
    function RowConditionContent($id, $params=false){ 
      global $CFG;
      require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileUser.php";
      $userclass = new EnrollByProfileUser();
      $fields = $userclass->get_user_fields_all(); 
      $row="";  
      $boolop = "";       
      $rowdata = [];
      
      $params = json_decode($params); 
      if($id > 1){          
        if($params->boolop == "OR"){   
          $boolop = "OR"; 
        }else{ 
          $boolop = "AND";  
        } 
      } 
      if($params){ 
        $fieldsarray = [];
        foreach($fields as $field => $value){
          $value['value'] = $field;
          $fieldsarray[] = $value;
        }     
        $negated = $params->negated;  
        $params->values = json_encode($params->value);  
        $rowdata['editrowdata'] = array("class"=>"row condition-item",  
            "data-id"=>$params->id, 
            "data-boolop"=>$params->boolop, 
            "data-field"=>$params->field, 
            "data-value"=>$params->values,  
            "data-rule"=>$params->rule, 
            "data-statement"=>$params->statement, 
            "data-negatedrule"=>$params->negatedrule, 
            "data-negatedstatement"=>$params->negatedstatement, 
            "data-negated"=>$params->negated, 
            "data-text"=>$params->text ,
            "fields"=>$fieldsarray,
            "boolop_html"=> $row ,
            $boolop => $boolop
          );
        $rowdata['rowdata'] = [];  
         
      }else { 
        $fieldsarray = [];
        foreach($fields as $field => $value){
          $value['value'] = $field;
          $fieldsarray[] = $value;
        } 
        $negated = false; 
        $values = json_encode(array()); 
        $rowdata['rowdata'] = array( 
            "data-id"=>$id, 
            "data-boolop"=> $boolop,  
            "data-value"=>$values,
            "fields"=>$fieldsarray,
            "boolop_html"=> $row 
        ); 
        $rowdata['editrowdata'] = [];
      } 
        
      return $rowdata;  
    } 
    

     

    

    

    

    

    

   

    /** 
    * This function will execute the save process of each rule. 
    * @param $elements Selected elements on the view ( Courses, cohorts,tenant or LP) 
    * @param $category Selected category type 1 = 'Course' 2 = 'Tenant' 3 = 'LP' 4 = 'Cohort' 
    * @param $profile Selected Profile field  
    * @param $content 
    * @return json object like $array[msg] = 'message' ( success or fail ) , $array[table] ( rules table content )  
    */  
    function SaveElements($elements,$category,$profile,$content,$rid,$rulename,$name) 
    { 
        global $DB ;  
      
        $object = new stdClass(); 
        if(!$rid) 
        { 
            $object->profile_field = $profile ; 
            $object->content = $content ; 
            $object->category = $category ; 
            $object->rulename = $rulename ; 
            $object->selected_elements = json_encode($elements) ; 
            $object->disable_rule = 0;  
            $object->unenroll_rule = 0;
            $object->name = $name ; 
        
            $result = $DB->insert_record('local_enroll_by_profile',$object);  
            if($result){  
                return array( 
                    'msg'=>get_string('datasaved','local_enroll_by_profile')
                );  
            }else{  
                return array( 
                    'msg'=>get_string('errorsaveding','local_enroll_by_profile')
                );  
            } 
        }elseif($rid > 0){  
            $object->id = $rid ;  
            $object->profile_field = $profile ; 
            $object->content = $content ; 
            $object->category = $category ; 
            $object->rulename = $rulename ;
            $object->name = $name ; 
            $object->selected_elements = json_encode($elements) ; 
            if($DB->update_record('local_enroll_by_profile', $object)) {  
                return array('msg'=>get_string('updating_success','local_enroll_by_profile') );  
            }else{  
                return array('msg'=>get_string('errorupdating','local_enroll_by_profile'));  
            } 
        } 
    } 

    function GetBoolOpDropdown(){ 
      $select[] = 'boolopdropdown';   
      return $select; 
    } 

    function GetActionButtons($id,$negated){ 
      $buttons = []; 
      $buttons['id'] = $id;
      $buttons['negated'] = $negated;

      $returndata['GetActionButtons'] = $buttons;
      return $returndata;  
    } 
 
    
     
    
  function evalConditionsBool($conditionsvalues){ 
    $debug = DebugSyncProcess(); 
    if($debug){ 
      var_dump($conditionsvalues);  
    } 
    $result = true; 
      foreach($conditionsvalues as $value){ 
        if($value->boolop === "AND"){ 
          if($value->debug){  
            echo "AND"; 
          } 
          if(!$value->result) return false; 
        }elseif ($value->boolop === "OR") { 
          if($debug){ 
            echo "OR";  
          } 
          if($value->result) return true; 
        }else{  
          if(!$value->result) $result = false;  
          if($debug){ 
            echo "NONE";  
            } 
          continue; 
        } 
      } 
      return $result; 
  } 
  function evalConditional($rule, $value, $field){  
    $debug = DebugSyncProcess(); 
    $array = $value;      
    $value = $value[0]; 
    switch($rule){  
        case "contains":  
          $field = transliterateString($field); 
          $value = transliterateString($value); 
          $answer = strpos( $field, $value ) !== false; 
          if($debug){ 
            echo "contains \n"; 
            var_dump($value); 
            var_dump($field); 
          } 
          break;  
        case "beginswith":  
          $field = transliterateString($field); 
          $value = transliterateString($value); 
          $answer = substr_compare($field, $value, 0, strlen($value)) === 0;  
          break;  
        case "endswith":  
          $field = transliterateString($field); 
          $value = transliterateString($value); 
          $answer = substr_compare($field, $value, 0, -strlen($value)) === 0; 
          break;  
        case "isempty": 
          $answer = strpos( $field, "" ) !== false; 
          break;  
        case "exactmatch":  
          $field = transliterateString($field); 
          $value = transliterateString($value); 
          if( strlen($field) === strlen($value)){ 
            $answer = substr_compare($field, $value, 0, strlen($value)) === 0;  
          }else { 
            $answer = false;  
          } 
          break;  
        case "equalsto":  
          $answer = (intval($field) === intval($value));  
          break;  
        case "notlessthan": 
        case "greaterorequalthan":  
          $answer = (intval($field) >= intval($value)); 
          break;  
        case "notlessorequalthan":  
        case "greaterthan": 
          $answer = (intval($field) > intval($value));  
          break;  
        case "notgreaterthan":  
        case "lessorequalthan": 
          $answer = (intval($field) <= intval($value)); 
          break;  
        case "notgreaterorequalthan": 
        case "lessthan":  
          if(!empty($field) && !empty($value)){ 
            $answer = (intval($field) < intval($value));  
          } 
          break;  
        case "isbefore":  
          if(!empty($field) && !empty($value)){ 
            $answer = (intval($field) < intval(strtotime($value))); 
          } 
          break;  
        case "isafter": 
          $answer = (intval($field) > intval(strtotime($value))); 
          break;  
        case "isbetween": 
          $answer = (intval($field) > intval(strtotime($array[0])) && intval($field) < intval(strtotime($array[1]))); 
           break; 
        case "itson": 
          $answer = intval($field) === intval(strtotime($value)); 
          break;  
        case "insideinterval":  
          $answer = (intval($field) > intval($array[0]) && intval($field) < intval($array[1])); 
          break;  
        /*  
        * @author VaibhavG  
        * @since 11th Feb 2021  
        * @desc 509 Rules Engine issues fixes. Checkbox type rule 
        */  
        case "ischecked": 
          $answer = (($field) === ($value));  
          break;  
        /*  
        * @author VaibhavG  
        * @since 11th Feb 2021  
        * @desc 509 Rules Engine issues fixes. Checkbox type rule 
        */  
        case "isnotchecked":  
          $answer = (($field) !== ($value));  
          break;    
        /*  
        * @author VaibhavG  
        * @since 11th Feb 2021  
        * @desc 509 Rules Engine issues fixes. Dropdown type rule 
        */  
        case "isselected":  
          $field = transliterateString($field); 
          $value = transliterateString($value); 
          if(!empty($field) && !empty($value)){ 
            if( strlen($field) === strlen($value)){ 
              $answer = substr_compare($field, $value, 0, strlen($value)) === 0;  
            }else { 
              $answer = false;  
            } 
          } 
          break;  
        case "notisselected": 
            
          if(isset($field) && isset($value) && !empty($field) && !empty($value)){ 
            $answer = (($field) !== ($value));  
          } 
          break;  
        
        /*  
        * @author VaibhavG  
        * @since 11th Feb 2021  
        * @desc 509 Rules Engine issues fixes. Multiselect type rule  
        */  
        case "hasselected": 
          $field = array_map('trim', $field); 
          $value = array_map('trim', $value); 
          if(isset($field) && isset($value) && !empty($field) && !empty($value)){ 
            $answer = (($field) === ($value));  
          } 
          break;  
        case "nothasselected":  
          $field = array_map('trim', $field); 
          $value = array_map('trim', $value); 
          if(isset($field) && isset($value) && !empty($field) && !empty($value)){ 
            $answer = (($field) !== ($value));  
          } 
          break;  
        /*  
        * @author VaibhavG  
        * @since 26th Feb 2021  
        * @desc 509 Rules Engine issues fixes. Multiselect type rule  
        */  
        case "hasanyselected":  
          $field = array_map('trim', $field); 
          $value = array_map('trim', $value); 
          if(isset($field) && isset($value) && !empty($field) && !empty($value)){ 
            $answer = (($field) === ($value));  
            if(empty($answer)){ 
              if(array_intersect($field, $value)) 
                $answer = 1;  
            } 
          } 
          break;  
        case "nothasanyselected": 
          $field = array_map('trim', $field); 
          $value = array_map('trim', $value); 
          if(isset($field) && isset($value) && !empty($field) && !empty($value)){ 
            $answer = (($field) !== ($value));  
          } 
          break;  
        case "notcontains": 
          $field = transliterateString($field); 
          $value = transliterateString($value); 
          $answer = strpos( $field, $value ) === false; 
          break;  
        case "notbeginswith": 
          $field = transliterateString($field); 
          $value = transliterateString($value); 
          $answer = !(substr_compare($field, $value, 0, strlen($value)) === 0); 
          break;  
        case "notendswith": 
          $field = transliterateString($field); 
          $value = transliterateString($value); 
          $answer = !(substr_compare($field, $value, 0, strlen($value)) === 0); 
          break;  
        case "notisempty":  
          $answer = strpos( $field, "" ) === false; 
          break;  
        case "notequalsto": 
          $answer = !(intval($field) === intval($value)); 
          break;  
        case "notisbefore": 
          $answer = !(intval($field) < intval(strtotime($value)));  
          break;  
        case "notisafter":  
          $answer = !(intval($field) > intval(strtotime($value)));  
          break;  
        case "notisbetween":  
          $answer = !(intval($field) > intval(strtotime($value[0])) && intval($field) < intval(strtotime($value[1])));  
          break;  
        case "notitson":  
          $answer = !(intval($field) == intval(strtotime($value))); 
          break;  
          
        default:  
        $answer = false;  
    } 
    return $answer; 
  } 
  function evalConditionaltoSQL($rule, $value, $field){ 
    switch($rule){  
        case "contains":  
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " LIKE '%".$value."%' ";  
          $str .= ")";  
          break;  
        case "beginswith":  
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " LIKE '".$value."%' "; 
          $str .= ")";  
          break;  
        case "endswith":  
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " LIKE '%".$value."' "; 
          $str .= ")";  
          break;  
        case "isempty": 
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " =  '' ";  
          $str .= ")";  
          break;  
        case "exactmatch":  
        case "equalsto":  
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " = '".$value."'";  
          $str .= ")";  
          break;  
        case "notlessthan": 
        case "greaterorequalthan":  
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " >= '".$value."'"; 
          $str .= ")";  
          break;  
        case "notlessorequalthan":  
        case "greaterthan": 
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " > '".$value."'";  
          $str .= ")";  
          break;  
        case "notgreaterthan":  
        case "lessorequalthan": 
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " <= '".$value."'"; 
          $str .= ")";  
          break;  
        case "notgreaterorequalthan": 
        case "lessthan":  
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " < '".$value."'";  
          $str .= ")";  
          break;  
        case "isbefore":  
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " < date('".$value."')";  
          $str .= ")";  
          break;  
        case "isafter": 
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " > date('".$value."')";  
          $str .= ")";  
          break;  
        case "isbetween": 
        $str .= "(";  
        $str .= "`".$field."`"; 
        $str .= " > date('".$value[0]."')"; 
        $str .= " AND `".$field."`";  
        $str .= " < date('".$value[1]."')"; 
        $str .= ")";  
        break;  
        case "itson": 
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " = date('".$value."')";  
          $str .= ")";  
          break;  
        /*  
        * @author VaibhavG  
        * @since 11th Feb 2021  
        * @desc 509 Rules Engine issues fixes. Checkbox type rule 
        */  
        case "ischecked": 
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " = '".$value."'";  
          $str .= ")";  
          break;  
        /*  
        * @author VaibhavG  
        * @since 11th Feb 2021  
        * @desc 509 Rules Engine issues fixes. Checkbox type rule 
        */  
        case "isnotchecked":  
        $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " != '".$value."'"; 
          $str .= ")";  
          break;  
        /*  
        * @author VaibhavG  
        * @since 11th Feb 2021  
        * @desc 509 Rules Engine issues fixes. Dropdown type rule 
        */  
        case "isselected":  
            $str .= "(";  
            $str .= "`".$field."`"; 
            $str .= " = '".$value."'";  
            $str .= ")";  
            break;  
        case "notisselected": 
            $str .= "(";  
            $str .= "`".$field."`"; 
            $str .= " != '".$value."'"; 
            $str .= ")";  
            break;  
        /*  
        * @author VaibhavG  
        * @since 11th Feb 2021  
        * @desc 509 Rules Engine issues fixes. Multiselect type rule  
        */  
        case "hasselected": 
            $str .= "(";  
            $str .= "`".$field."`"; 
            $str .= " = '".$value."'";  
            $str .= ")";  
            break;  
        case "nothasselected":  
            $str .= "(";  
            $str .= "`".$field."`"; 
            $str .= " != '".$value."'"; 
            $str .= ")";  
            break;  
        /*  
        * @author VaibhavG  
        * @since 26th Feb 2021  
        * @desc 509 Rules Engine issues fixes. Multiselect type rule  
        */  
        case "hasanyselected":  
            $str .= "(";  
            $str .= "`".$field."`"; 
            $str .= " = '".$value."'";  
            $str .= ")";  
            break;  
        case "nothasanyselected": 
            $str .= "(";  
            $str .= "`".$field."`"; 
            $str .= " != '".$value."'"; 
            $str .= ")";  
            break;  
        case "notcontains": 
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " NOT LIKE '%".$value."%'"; 
          $str .= ")";  
          break;  
        case "notbeginswith": 
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " NOT LIKE '".$value."%'";  
          $str .= ")";  
          break;  
        case "notendswith": 
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " = '".$value."'";  
          $str .= ")";  
          break;  
        case "notisempty":  
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " != ''"; 
          $str .= ")";  
          break;  
        case "notequalsto": 
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " != '".$value."'"; 
          $str .= ")";  
          break;  
        case "notisbefore": 
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " >= date('".$value."')"; 
          $str .= ")";  
          break;  
        case "notisafter":  
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " >= date('".$value."')"; 
          $str .= ")";  
          break;  
        case "notisbetween":  
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " < date('".$value[0]."')"; 
          $str .= " AND `".$field."`";  
          $str .= " > date('".$value[1]."')"; 
          $str .= ")";  
          break;  
        case "notitson":  
          $str .= "(";  
          $str .= "`".$field."`"; 
          $str .= " != date('".$value."')"; 
          $str .= ")";  
          break;  
        default:  
    } 
  } 
    /** 
     * This function will execute each rule to given user accordingly running the functions 
     * CourseAssignment ,TenantAssignment, LPAssignment, CohortAssignment with method add 
     *  
     * @param $datauser event object ( user created/updated data )  
     * @author vaibhavg 
     * @desc sent null to AddUser() 
     */ 
    function DebugSyncProcess(){  
        $debug = false; 
        return $debug;  
    } 
    
    /** 
    * User creation / edition listener to process assign / enroll and un-assign / un-enroll 
    * @param $eventdata user event opbjet with user data  
    */  
    function observer_enroll_by_profile($eventdata,$blk=null,$userid=null){ 
        global $CFG;  
        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileUser.php";
         
        $userclass = new EnrollByProfileUser();
        $userclass->RemoveUser($eventdata,$blk,$userid);
        $userclass->AddUserConditional($eventdata,$blk,$userid); 
    } 

    /** 
    * This function will return html content with element name and checkbox.  
    * @param $la_elements array of elements to create html content like <label><span>element name</span><input value="elementid" type="checkbox" /></label> 
    * @return $returndata Array 
    */  
    function GetCheckBoxHTML($la_elements,$selected){ 
        $output = [];
        $returndata = [];
        foreach ($la_elements as $elementid => $elementname) {  
            $classactive =''; 
            if($selected[$elementid]) $classactive = 'active' ; 
            $output['classactive'] = $classactive; 
            $checked='';  
            $output['elementname'] = $elementname; 
            if($selected[$elementid]){  
                $checked='checked'; 
            } 
            $output['type'] = 'checkbox';
            $output['name'] = 'elements[]';
            $output['value'] = $elementid;
            $output['checked'] = $checked;
            $output['elementid'] = $elementid;
            $returndata['data'][] = $output;
        } 
        
        return $returndata ;  
    } 
    /** 
    * This function will return html content with element name on a dropdown element. 
    * @param $la_elements array of elements to create html content like <select><option value="elementid">element name</option></select>  
    * @return $output HTML content  
    */  
    function GetDropDownHTML($la_elements,$selected){ 
        $returndata = [];
        $output = [];
        foreach ($la_elements as $la_elementid => $la_elementname) {  
            $active = '' ;  
            if($selected[$la_elementid]) $active = 'selected' ; 
            $output['active'] = ($active)?$active:NULL;
            $output['la_elementname'] = $la_elementname;
            $output['la_elementid'] = $la_elementid;
            $returndata['data'][] = $output;
        } 
        return $returndata ;  
    } 
    
    /** 
    * This funciton will return HTML per field type with its respective values ( menu , multiselect and multiselectlist)  
    * @param $fieldtype field type  
    * @param $fieldsn field shortname 
    * @return $output HTML field  
    */  
    function GetInputHtml($conditional,$fieldtype,$fieldsn,$id){  
        global $CFG,$DB ; 
        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileTenant.php";
        $tenantclass = new EnrollByProfileTenant();
        
        $fielddata = $DB->get_record('user_info_field',array('shortname'=>$fieldsn)); 
       
        if($fieldtype === 'tenant') 
          $options = $tenantclass->GetFieldTenant();           
        else  
          $options = explode("\n", $fielddata->param1); 
        $output = [] ;  
        
        $negatedconditional = "not".$conditional; 
        $negatedconditionalstring =  get_string($negatedconditional,'local_enroll_by_profile',null,"true"); 
        $output['negatedconditional'] = $negatedconditional;
        $output['negatedconditionalstring'] = $negatedconditionalstring;
        
        switch ($conditional) { 
          case "contains":  
          case "beginswith":  
          case "endswith":  
          case "exactmatch":  
          $output['id'] = $id;
            break;  
          case 'greaterthan': 
          case 'lessorequalthan': 
          case 'greaterorequalthan':  
          case 'lessthan':  
          case 'equalsto':  
            $output['id'] = $id; 
            break;  
          case 'insideinterval':  
              $output['id'] = $id;
              break;  
          case 'isbetween': 
              $output['id'] = $id;
              $output['fields'][] = ['label'=>get_string('start_date','local_enroll_by_profile'),'value'=>'','id'=>'start_date','required'=>false,'extradata'=>array('id'=>'id_content'.$id,'name'=>'start_date'.$id,'style' => 'width: 60% !important;','class'=>'datepicker start_date form-control','readonly'=>'readonly')];
              $output['fields'][] = ['label'=>get_string('end_date','local_enroll_by_profile'),'value'=>'','id'=>'start_date','required'=>false,'extradata'=>array('id'=>'id_subcontent'.$id,'name'=>'end_date'.$id,'style' => 'width: 60% !important;','class'=>'datepicker end_date form-control','readonly'=>'readonly')];
              break;  
          case 'isbefore': 
              $output['id'] = $id;
              $output['fields'][] = ['label'=>get_string('date','local_enroll_by_profile'),'value'=>'','id'=>'start_date','required'=>false,'extradata'=>array('id'=>'id_content'.$id,'name'=>'start_date'.$id,'style' => 'width: 60% !important;','class'=>'datepicker start_date form-control','readonly'=>'readonly')];
              break;  
          case 'isafter': 
              $output['id'] = $id;
              $output['fields'][] = ['label'=>get_string('date','local_enroll_by_profile'),'value'=>'','id'=>'start_date','required'=>false,'extradata'=>array('id'=>'id_content'.$id,'name'=>'start_date'.$id,'style' => 'width: 60% !important;','class'=>'datepicker start_date form-control','readonly'=>'readonly')];
              break;  
          case 'itson': 
              $output['id'] = $id;
              $output['fields'][] = ['label'=>get_string('date','local_enroll_by_profile'),'value'=>'','id'=>'start_date','required'=>false,'extradata'=>array('id'=>'id_content'.$id,'name'=>'start_date'.$id,'style' => 'width: 60% !important;','class'=>'datepicker start_date form-control','readonly'=>'readonly')]; 
              break;  
          /*  
          * @author VaibhavG  
          * @since 11th Feb 2021  
          * @desc 509 Rules Engine issues fixes.  
          */  
          case 'isselected':  
          $output['id'] = $id; 
          $optionsarray = [];
          foreach ($options as $key => $value) {
            $optionsarray[] = ['name'=>$value,'value'=>$key];
          }
          $output['options'] = $optionsarray;
          break;  
          /*  
          * @author VaibhavG  
          * @since 11th Feb 2021  
          * @desc 509 Rules Engine issues fixes.  
          */  
          case 'hasanyselected':  
          case 'hasselected': 
            $output['id'] = $id; 
            $optionsarray = [];
            foreach ($options as $key => $value) {
              $optionsarray[] = ['name'=>$value,'value'=>$key];
            }
            $output['options'] = $optionsarray; 
          break;  
          /*  
          * @author VaibhavG  
          * @since 11th Feb 2021  
          * @desc 509 Rules Engine issues fixes.  
          */  
          case 'ischecked': 
             $output['id'] = $id;
             $output['multiple_conditions'] = 'ischecked';
             break; 
          /*  
          * @author VaibhavG  
          * @since 11th Feb 2021  
          * @desc 509 Rules Engine issues fixes.  
          */  
          case 'isnotchecked':
              $output['id'] = $id;
              $output['multiple_conditions'] = 'isnotchecked';  
              break;  
          case 'isempty': 
              $output['id'] = $id;
              $output['multiple_conditions'] = 'isempty';
              break;  
          /*  
          * @author VaibhavG  
          * @since 11th Feb 2021  
          * @desc 509 Rules Engine issues fixes.  
          */  
          case 'menu':  
            $output['id'] = $id; 
            $optionsarray = [];
            foreach ($options as $key => $value) {
              $optionsarray[] = ['name'=>$value,'value'=>$key];
            }
            $output['options'] = $optionsarray;
            break;  
          default:  
            
            break;  
        } 
          
      return $output; 
    } 
  
    /** 
    * This funciton will return HTML per field type with its respective values ( menu , multiselect and multiselectlist)  
    * @param $fieldtype field type  
    * @param $fieldsn field shortname 
    * @return $output array  
    */  
    function GetConditionalHtml($fieldtype,$fieldsn,$id){ 
        global $CFG,$DB ; 
          
        $fielddata = $DB->get_record('user_info_field',array('shortname'=>$fieldsn)); 
        $options = explode("\n", $fielddata->param1); 
        $output = [] ;  
        $output['id'] = $id;
      
        switch ($fieldtype) { 
            case 'text':  
               $output['text'] = 'text';
            break;  
            case 'numeric': 
              $output['numeric'] = 'numeric';  
            break;  
            case 'datetime':  
              $output['datetime'] = 'datetime'; 
            break;  
            /*  
            * @author VaibhavG  
            * @since 11th Feb 2021  
            * @desc 509 Rules Engine issues fixes.  
            */  
            case 'menu':  
              $output['menu'] = 'menu';  
            break;  
            case 'multiselect': 
            case 'multiselectlist': 
              $output['multiselect'] = 'multiselect';  
            break;  
            case 'checkbox':  
              $output['checkbox'] = 'checkbox';  
            break;  
            /*  
            * @author : VaibhavG  
            * @since  : 22 March 2021 
            * @desc   : adding new field tenant.  
            */  
            case 'tenant':  
              $output['tenant'] = 'tenant'; 
            break;      
          default:  
            $output['default'] = 'default';   
          break;  
        } 
        return $output ;  
    } 
    /** 
    * This function will process the add when a user now match any rule base on each profile field type ( multiselect, text, textarea, checkbox, multiselectlist, datetime, menu )  
    * @param $rule rule object sabed on local_enroll_by_profile 
    * @param $varfl value saved on user profile fields  
    * @param $method method avalable to execute ( CourseAssignment,TenantAssignment,LPAssignment,CohortAssignment ) 
    * @param $userid User id  
    * @param $elementid elements saved on rule  
    * @param $ruleid rule id  
    * @param $fielddata profiel field data sabed on user_profile_field  
    */  
    function ProcessAddProfileFields($rule,$varfl,$method,$userid,$elementid,$ruleid,$fielddata){ 
        global $CFG;
        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileUser.php";
        $userclass = new EnrollByProfileUser();
        $learningplansclass = new EnrollByProfileLearningPlans();
        $roleclass = new EnrollByProfileRole();
        $cohortclass = new EnrollByProfileCohort();
        $LPclass = new EnrollByProfileLP();
        $tenantclass = new EnrollByProfileTenant();
        $courseclass = new EnrollByProfileCourses();

        $content = str_replace('[{', '',  $rule->content);  
        $content = str_replace('}]', '',  $content);  
        $useradded = false ;  

        switch ($method) {  
            case CourseAssignment: 
              $class = $courseclass;
              break;  
            case TenantAssignment: 
              $class = $tenantclass;
              break;  
            case LPAssignment:  
              $class = $LPclass;
              break;  
            case CohortAssignment: 
              $class = $cohortclass; 
              break;  
            case RoleAssignment:  
              $class = $roleclass;
              break; 
            case LearningplansAssignment: 
              $class = $learningplansclass;  
              break;    
        }

        //Lets process custom profile fields add  
        switch ($fielddata->datatype) { 
            case 'menu':  
            case 'multiselect': 
                $content = str_replace('content:', '',  $content);  
                $content = trim( $content , ','); 
                //rule index saved  
                $la_r_content = explode(',', $content); 
                // lets get the difference between rule array and user array indexes  
                $result = array_diff($la_r_content,$varfl); 
                //if there is not any difference then it means user match the rule perfectly  
                if( count($result) == 0 ) { 
                    $method($userid,$elementid,'add'); 
                    $useradded = true ; 
                } 
                break;  
            /** 
             * Validate if the value of the custom profile field ($varfl) is not empty  
             * when type of field is text and textarea to prevent errors on the 
             * enrolment method.  
             * @author Yesid V. 
             * @since April 17, 2018  
             * @paradiso  
            */  
            case 'text':  
                //lets search the rule value, on any place of the user profile fiels value  
                $content = str_replace('content:', '',  $content);  
                $content =  trim($content,','); 
                if( $content == $varfl && $varfl != "" ){ 
                    $method($userid,$elementid,'add'); 
                    $useradded = true ; 
                } 
                break;  
            case 'textarea':  
                $content = str_replace('content:', '',  $content);  
                $content =  trim($content,','); 
                //lets search the rule value, on any place of the user profile fiels value  
                if( is_numeric( strpos( strip_tags($varfl['text']) , $content  ) ) && strpos( strip_tags($varfl['text']) , $content  )  >= 0 && $varfl['text'] != "" ){ 
                    $method($userid,$elementid,'add'); 
                    $useradded = true ; 
                } 
                break;  
            case 'checkbox':  
                $content = str_replace('checked:', '',  $content);  
                // lets validate if 1 or 0 ( 1 = checked - 0 =unchecked)  
                if( $content == $varfl ){ 
                    $method($userid,$elementid,'add'); 
                    $useradded = true ; 
                } 
                break;  
            case 'multiselectlist': 
                $content = str_replace('content:', '',  $content);  
                $content = trim( $content , ','); 
                //rule index saved  
                $la_r_content = explode(',', $content); 
                // lets get the difference between rule array and user array indexes  
                $result = array_diff($la_r_content,$varfl); 
                //if there is not any difference then it means user match the rule perfectly  
                if( count($result) == 0 ) { 
                    $method($userid,$elementid,'add'); 
                    $useradded = true ; 
                } 
                break;  
            case 'datetime':  
                $content = str_replace('start_date:', '',  $content); 
                $content = str_replace('end_date:', '',  $content); 
                $content =  trim($content,','); 
                $la_content = explode(',', $content) ;  
                //Lets do between start date and end date to evalute user profile field date  
                if( ( $varfl >= strtotime($la_content[0]) ) && ( $varfl <= strtotime($la_content[1]) ) ){ 
                    $method($userid,$elementid,'add'); 
                    $useradded = true ; 
                } 
                break;  
            case 'menu':  
                $content = str_replace('content:', '',  $content);  
                $content =  trim($content,','); 
                //lets evaluate that the selected content is equal to the rule  
                if( $content == $varfl ){ 
                    $method($userid,$elementid,'add'); 
                    $useradded = true ; 
                } 
                break;  
        } 
        //lets add the user to relation table where indicates which user is tied to which rule  
        if($useradded)  
        $userclass->AddUserRule($userid,$ruleid);  
    } 
    /** 
    * This function will process the remove when a user now dosen't match any rule base on each profile field type ( multiselect, text, textarea, checkbox, multiselectlist, datetime, menu ) 
    * @param $rule rule object sabed on local_enroll_by_profile 
    * @param $varfl value saved on user profile fields  
    * @param $method method avalable to execute ( CourseAssignment,TenantAssignment,LPAssignment,CohortAssignment ) 
    * @param $userid User id  
    * @param $elementid elements saved on rule  
    * @param $ruleid rule id  
    * @param $fielddata profiel field data sabed on user_profile_field  
    */  
    function ProcessRemoveProfileFields($rule,$varfl,$method,$userid,$elementid,$ruleid,$fielddata,$value,$rule_){ 
        global $CFG;
        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileUser.php";
         
        $userclass = new EnrollByProfileUser(); 
        $learningplansclass = new EnrollByProfileLearningPlans();
        $roleclass = new EnrollByProfileRole();
        $cohortclass = new EnrollByProfileCohort();
        $LPclass = new EnrollByProfileLP();
        $tenantclass = new EnrollByProfileTenant();
        $courseclass = new EnrollByProfileCourses();
      $content = str_replace('[{', '',  $rule->content);  
      $content = str_replace('}]', '',  $content);  
        $removed = false ;  

        switch ($method) {  
            case CourseAssignment: 
              $class = $courseclass;
              break;  
            case TenantAssignment: 
              $class = $tenantclass;
              break;  
            case LPAssignment:  
              $class = $LPclass;
              break;  
            case CohortAssignment: 
              $class = $cohortclass; 
              break;  
            case RoleAssignment:  
              $class = $roleclass;
              break; 
            case LearningplansAssignment: 
              $class = $learningplansclass;  
              break;    
        } 
          
        //Lets process custom profile fields add  
        switch ($fielddata->datatype) { 
            /*  
            * @author VaibhavG  
            * @since 23rd Feb 2021  
            * @desc 509 Rules Engine issues fixes 
            */  
            
            case 'menu':  
                $content = str_replace('content:', '',  $content);  
                $content =  trim($content,','); 
                //lets evaluate that the selected content is equal to the rule  
                if( $value[0] <> $varfl ){  
                    $class->$method($userid,$elementid,'remove');  
                    $removed =  true ;  
                } 
                break;  
            /*  
            * @author VaibhavG  
            * @since 23rd Feb 2021  
            * @desc 509 Rules Engine issues fixes 
            */  
            case 'numeric': 
                $content = str_replace('content:', '',  $content);  
                $content =  trim($content,','); 
                //lets evaluate that the selected content is equal to the rule  
                if( $value[0] <> $varfl ){  
                    $class->$method($userid,$elementid,'remove');  
                    $removed =  true ;  
                } 
                break;  
            case 'multiselect': 
                $content = str_replace('content:', '',  $content);  
                $content = trim( $content , ','); 
                //rule index saved  
                $la_r_content = explode(',', $content); 
                // lets get the difference between rule array and user array indexes  
                $result = array_diff($la_r_content,$varfl); 
                //if user have differences then we remove user from rule  
                if( count($result) > 0 ) {  
                    $class->$method($userid,$elementid,'remove');  
                    $removed =  true ;  
                } 
                break;  
            case 'text':  
                //lets search the rule value, on any place of the user profile fiels value  
                if( $content != $varfl ){ 
                    $class->$method($userid,$elementid,'remove');  
                    $removed =  true ;  
                } 
                break;  
            case 'textarea':  
                $content = str_replace('content:', '',  $content);  
                $content =  trim($content,','); 
                //lets search the rule value, on any place of the user profile fiels value  
                if( strpos( strip_tags($varfl['text']) , $content  ) < 0 ){ 
                    $class->$method($userid,$elementid,'remove');  
                    $removed =  true ;  
                } 
                break;  
            case 'checkbox':  
                $content = str_replace('checked:', '',  $content);  
                // lets validate if 1 or 0 ( 1 = checked - 0 =unchecked)  
                if( $content <> $varfl ){ 
                    $class->$method($userid,$elementid,'remove');  
                    $removed =  true ;  
                } 
                break;  
            case 'multiselectlist': 
                $content = str_replace('content:', '',  $content);  
                $content = trim( $content , ','); 
                //rule index saved  
                $la_r_content = explode(',', $content); 
                // lets get the difference between rule array and user array indexes  
                $result = array_diff($la_r_content,$varfl); 
                //if user have differences then we remove user from rule  
                if( count($result) > 0 ) {  
                    $class->$method($userid,$elementid,'remove');  
                    $removed =  true ;  
                } 
                break;  
            case 'datetime':  
                $content = str_replace('start_date:', '',  $content); 
                $content = str_replace('end_date:', '',  $content); 
                $content =  trim($content,','); 
                  
                $la_content = explode(',', $content) ;  
                /*  
                * @author VaibhavG  
                * @since 24th Feb 2021  
                * @desc 509 Rules Engine issues fixes 
                */  
                if(($rule_ == 'itson') && !empty($value[0])){ 
                  if( $varfl != strtotime($value[0])){  
                    $class->$method($userid,$elementid,'remove');  
                    $removed =  true ;  
                  } 
                }else if(($rule_ == 'isbefore') && !empty($value[0])){  
                  if( $varfl > strtotime($value[0])){ 
                    $class->$method($userid,$elementid,'remove');  
                    $removed =  true ;  
                  } 
                }else if(($rule_ == 'isafter') && !empty($value[0])){ 
                  if( $varfl < strtotime($value[0])){ 
                    $class->$method($userid,$elementid,'remove');  
                    $removed =  true ;  
                  } 
                }else if(($rule_ == 'isbetween') && (!empty($value[0])) && (!empty($value[1]))){  
                  if((strtotime($value[0]) < $varfl ) && ($varfl > strtotime($value[1]))){  
                    $class->$method($userid,$elementid,'remove');  
                    $removed =  true ;  
                  }else if((strtotime($value[0]) > $varfl ) && ($varfl < strtotime($value[1]))){  
                    $class->$method($userid,$elementid,'remove');  
                    $removed =  true ;  
                  } 
                } 
                break; 
        } 
        //if user is removed from any rule then we delete the relation between user and rule  
        if($removed){ 
            $userclass->RemoveUserRule($userid,$ruleid);
            if(CLI_SCRIPT){ 
                mtrace("<br><b>:::Removed Rule ".$ruleid." For User ".$userid.":::</b><br>");
            }  
        } 
    } 
    /** 
    * This function will execute the role add process 
    * @param $userid user id  
    * @param $elementid elements saved on rule creation (Courses, Lps, Cohors or tenant)  
    * @param $ruleid rule id  
    * @param $method all methods availables to execute ( CourseAssignment,TenantAssignment,LPAssignment,CohortAssignment )  
    */  
    function ProcessRoleAddField($userid,$elementid,$ruleid,$method,$rule){ 
        global $DB,$CFG; 
        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileUser.php";
        $userclass = new EnrollByProfileUser(); 
        $roleclass = new EnrollByProfileRole();
        $query = " SELECT r.id,r.shortname  
           FROM {role_assignments} AS ra , {role} r 
           WHERE ra.roleid = r.id and ra.userid = :userid 
           GROUP BY r.id " ;  
        //get user roles  
        $userroles = $DB->get_records_sql($query ,array('userid'=>$userid));  
        foreach ($userroles as $key => $userrole) { 
            $la_roles[$userrole->shortname] = $userrole->shortname ;  
        } 
        $rulecontent = str_replace('[{content:', '',$rule->content ); 
        $rulecontent = str_replace(',}]', '',$rulecontent );  
        $rulecontent = trim($rulecontent);  
        //if its true then lets process rule  
        if($la_roles[$rulecontent]){  
            if($roleclass->RoleAssignment($userid,$elementid,'add')){ 
                $userclass->AddUserRule($userid,$ruleid);  
            } 
        } 
    } 
    function ProcessRoleRemoveField($userid,$elementid,$ruleid,$method,$rule,$value){ 
        /*  
        * @author VaibhavG  
        * @since 24th Feb 2021  
        * @desc 509 Rules Engine issues fixes 
        */  
        global $DB,$CFG; 
        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileUser.php";
        $userclass = new EnrollByProfileUser();
        $roleclass = new EnrollByProfileRole();
        $query = " SELECT ra.userid, r.shortname  
                   FROM {role_assignments} AS ra , {role} r 
                   WHERE ra.roleid = r.id and ra.userid = $userid"; 
        $userroles = $DB->get_record_sql($query); 
        $userhasrole = false; 
        foreach ($userroles as $key => $userrole) { 
            $la_roles[] = $userrole->shortname ;  
        } 
        if(!$la_roles[$value[0]]){  
            $roleclass->RoleAssignment($userid,$elementid,'remove');  
            $userclass->RemoveUserRule($userid,$ruleid);
            if(CLI_SCRIPT){ 
                mtrace("<br><b>:::Removed Rule ".$ruleid." For User ".$userid.":::</b><br>");
            }              
        } 
    } 
    /** 
    * Function to normalize strings 
    * @author Daniel Carmona  
    * @params $txt string String to normalize 
    * @return (string)  
    */  
    function transliterateString($txt) {  
        $transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'oe', 'Ö' => 'OE', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'e', 'ё' => 'e', 'Ё' => 'e', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja'); 
        $txt = str_replace(array_keys($transliterationTable),           array_values($transliterationTable), $txt); 
        return trim(strtolower($txt));  
    } 

function enroll_by_profile_enrollment_allusers() {  
    global $DB; 
    $users = $DB->get_records('user',array('deleted'=>0,'suspended'=>0)); 
    mtrace('<b>Started Rules Engine Cron:</b>');  
    $eventdata = array();  
    mtrace('Rule Gngine Cron Started At '. date('d/m/Y h:i:s'));  
    $count = 1; 
    foreach ($users as $id => $user) {  
        mtrace("<b>$count]</b>Started Processing user -> ".$id);  
                observer_enroll_by_profile($eventdata,true,$id); 
        mtrace("Processing User -> ".$id." Finished");  
        $count++; 
    }
    mtrace("<b>Ended Rules Engine Cron</b>"); 
} 

function get_rule_renderable(){
    global $CFG;
    require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileRule.php";
    $rulesclass = new EnrollByProfileRule();

    $rules = $rulesclass->GetRulesFromDB(null, null, null);

    return $rules;
}