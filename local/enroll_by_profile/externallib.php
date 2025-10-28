<?php
/**
 * External Web Service Template
 *
 * @package    local_enroll_by_profile
 * @copyright  2021 Paradiso LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/local/enroll_by_profile/lib.php");
require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileRule.php";

class local_enroll_by_profile_external extends external_api {
	/**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function search_parameters() {
        return new external_function_parameters(
                array(
                	'search_value' => new external_value(PARAM_RAW, 'Search value'),
                	'search_page' =>new external_value(PARAM_RAW, 'Search on page',VALUE_OPTIONAL)
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function search($search_value,$search_page) {
        global $CFG,$USER, $DB, $OUTPUT, $PAGE;

        
        $rulesclass = new EnrollByProfileRule();

        if(!empty($search_value))
		{
			return $rulesclass->GetRulesFromDB($search_value, $tags = 1, $search_page);	
		}else if(empty($search_value))
		{
			return $rulesclass->GetRulesFromDB(null, $tags = 1, $search_page);	
		}
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function search_returns() {
        return new external_single_structure(
            array(
            	'allrulescount' => new external_value(PARAM_INT, 'allrulescount'),
                'is_tags' => new external_value(PARAM_RAW, 'is_tags'),
                'previous' => new external_value(PARAM_RAW, 'Is previous page',VALUE_OPTIONAL),
                'next' => new external_value(PARAM_RAW, 'Is next page',VALUE_OPTIONAL),
                'totalpages' => new external_value(PARAM_RAW, 'total pages'),
                'pages' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'active' => new external_value(PARAM_RAW, 'Is active page'),
                            'page' => new external_value(PARAM_RAW, 'Page No'),
                            'url' => new external_value(PARAM_RAW, 'Page URL')
                            
                        ), 'pages'
                    ), 'pages array', VALUE_OPTIONAL
                ),
                'rules' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            '0' => new external_value(PARAM_RAW, 'id'),
                            '1' => new external_value(PARAM_RAW, 'Rule name'),
                            '2' => new external_value(PARAM_RAW, 'rule statement'),
                            '3' => new external_value(PARAM_RAW, 'course url'),
                            '4' => new external_value(PARAM_RAW, 'Category name'),
                            '5' => new external_value(PARAM_RAW, 'is checked'),
                            '6' => new external_value(PARAM_RAW, 'selected element'),
                            '7' => new external_value(PARAM_RAW, 'Is icons disable'),
                            '8' => new external_value(PARAM_RAW, 'is disabled'),
                            '9' => new external_value(PARAM_RAW, 'arrows')
                        ), 'rules'
                    ), 'rules array', VALUE_OPTIONAL
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function delete_rule_btn_parameters() {
        return new external_function_parameters(
            array(
            	'allselect' => new external_value(PARAM_RAW, 'Search value',VALUE_OPTIONAL),
            	'selectedrule' =>new external_value(PARAM_RAW, 'Search on page',VALUE_OPTIONAL)
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function delete_rule_btn($allselect,$selectedrule) {
        global $CFG,$USER, $DB, $OUTPUT, $PAGE;

        $rulesclass = new EnrollByProfileRule();

        if($allselect>0) {
			return $rulesclass->AllDeleteRule();
		}else{
		 	$ruleid = explode(',',$selectedrule);
	     	return $rulesclass->DeleteAllRule($ruleid);
		}
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function delete_rule_btn_returns() {
        return new external_single_structure(
            array(
            	'msg' => new external_value(PARAM_RAW, 'status message')
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function keep_unenroll_all_parameters() {
        return new external_function_parameters(
            array(
            	'rid' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'the value to match'))
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function keep_unenroll_all($rid = array()) {
        global $CFG,$USER, $DB, $OUTPUT, $PAGE;

        $rulesclass = new EnrollByProfileRule();

        if( $rid <> 0 )
        $unenrollall = $rulesclass->unenrollAllRule($rid);
        if(!empty($enrollall)){
            $data['message'] = 1;
        }else{
            $data['message'] = 0;
        }
        return $data;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function keep_unenroll_all_returns() {
        return new external_single_structure(
            array(
            	'message' => new external_value(PARAM_RAW, 'status message')
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function keep_enroll_all_parameters() {
        return new external_function_parameters(
            array(
            	'rid' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'the value to match'))
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function keep_enroll_all($rid = array()) {
        global $CFG,$USER, $DB, $OUTPUT, $PAGE;

       
        $rulesclass = new EnrollByProfileRule();

        if( $rid <> 0 )
		$enrollall = $rulesclass->enrollAllRule($rid);
        if(!empty($enrollall)){
             $data['message'] = 1;
        }else{
             $data['message'] = 0;
        }
        return $data;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function keep_enroll_all_returns() {
        return new external_single_structure(
            array(
            	'message' => new external_value(PARAM_RAW, 'status message')
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function keep_enroll_parameters() {
        return new external_function_parameters(
            array(
            	'rid' => new external_value(PARAM_RAW, 'Search value')
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function keep_enroll($rid) {
        global $CFG,$USER, $DB, $OUTPUT, $PAGE;

        $rulesclass = new EnrollByProfileRule();

        if( $rid <> 0 ){
            $enrolled = $rulesclass->enrollRule($rid);
            if(!empty($enrolled)){
                $data['message'] = 1;
            }else{
               $data['message'] = 0;
            }
        }
		return $data; 
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function keep_enroll_returns() {
        return new external_single_structure(
            array(
            	'message' => new external_value(PARAM_RAW, 'allrulescount')
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function keep_unenroll_parameters() {
        return new external_function_parameters(
            array(
            	'rid' => new external_value(PARAM_RAW, 'Search value')
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function keep_unenroll($rid) {
        global $CFG,$USER, $DB, $OUTPUT, $PAGE;

        $rulesclass = new EnrollByProfileRule();

        if( $rid <> 0 ){
            $enrolled = $rulesclass->unenrollRule($rid);
            if(!empty($enrolled)){
                $data['message'] = 1;
            }else{
               $data['message'] = 0;
            }
        }
        return $data;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function keep_unenroll_returns() {
        return new external_single_structure(
            array(
            	'message' => new external_value(PARAM_RAW, 'allrulescount')
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function addcond_parameters() {
        return new external_function_parameters(
            array(
            	'condid' => new external_value(PARAM_RAW, 'Condition Id'),
                'params' => new external_value(PARAM_RAW, 'Condition params',VALUE_OPTIONAL)
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function addcond($condid,$params=null) {
        global $CFG;

        $data = RowConditionContent($condid,$params);
        
        return $data;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function addcond_returns() {
        return new external_single_structure(
            array(
            	'rowdata' => 
                    new external_single_structure(
                        array(
                            'boolop_html' => new external_value(PARAM_RAW, 'Bool OP Html',VALUE_OPTIONAL),
                            'data-boolop' => new external_value(PARAM_RAW, 'Bool Op data',VALUE_OPTIONAL),
                            'data-id' => new external_value(PARAM_RAW, 'Id',VALUE_OPTIONAL),
                            'data-value' => new external_value(PARAM_RAW, 'value',VALUE_OPTIONAL),
                            'fields' => new external_multiple_structure(
			                    new external_single_structure(
			                        array(
			                            'name' => new external_value(PARAM_RAW, 'name',VALUE_OPTIONAL),
			                            'type' => new external_value(PARAM_RAW, 'Rule type',VALUE_OPTIONAL),
			                            'value' => new external_value(PARAM_RAW, 'rule value',VALUE_OPTIONAL)
			                        ), 'fields'
			                    ), 'fields array', VALUE_OPTIONAL
			                )
                        )
                    ), 'rowdata array', VALUE_OPTIONAL,
                'editrowdata' => 
                    new external_single_structure(
                        array(
                            'boolop_html' => new external_value(PARAM_RAW, 'Bool OP Html',VALUE_OPTIONAL),
                            'data-boolop' => new external_value(PARAM_RAW, 'Bool Op data',VALUE_OPTIONAL),
                            'data-id' => new external_value(PARAM_RAW, 'Id',VALUE_OPTIONAL),
                            'data-value' => new external_value(PARAM_RAW, 'value',VALUE_OPTIONAL),
                            'class' => new external_value(PARAM_RAW, 'class',VALUE_OPTIONAL),
                            'data-field' => new external_value(PARAM_RAW, 'selected field',VALUE_OPTIONAL),
                            'data-rule' => new external_value(PARAM_RAW, 'rule type',VALUE_OPTIONAL),
                            'data-statement' => new external_value(PARAM_RAW, 'rule statement',VALUE_OPTIONAL),
                            'data-negatedrule' => new external_value(PARAM_RAW, 'negatedrule',VALUE_OPTIONAL),
                            'data-negatedstatement' => new external_value(PARAM_RAW, 'negatedstatement',VALUE_OPTIONAL),
                            'data-negated' => new external_value(PARAM_RAW, 'negated value',VALUE_OPTIONAL),
                            'data-text' => new external_value(PARAM_RAW, 'rule text',VALUE_OPTIONAL),
                            'fields' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'name' => new external_value(PARAM_RAW, 'name',VALUE_OPTIONAL),
                                        'type' => new external_value(PARAM_RAW, 'Rule type',VALUE_OPTIONAL),
                                        'value' => new external_value(PARAM_RAW, 'rule value',VALUE_OPTIONAL)
                                    ), 'fields'
                                ), 'fields array', VALUE_OPTIONAL
                            )
                        ), 'rowdata'
                    ), 'rowdata array', VALUE_OPTIONAL
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_category_parameters() {
        return new external_function_parameters(
            array(
            	'category' => new external_value(PARAM_RAW, 'category Id'),
            	'selected' => new external_value(PARAM_RAW, 'selected category Id',VALUE_OPTIONAL)
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function get_category($category,$selected=null) {
        global $CFG,$USER, $DB, $OUTPUT, $PAGE;

        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileCourses.php";
        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileTenant.php";
        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileLP.php";
        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileCohort.php";
        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileRole.php";
        require_once "{$CFG->dirroot}/local/enroll_by_profile/classes/EnrollByProfileLearningPlans.php";
        $learningplansclass = new EnrollByProfileLearningPlans();
        $roleclass = new EnrollByProfileRole();
        $cohortclass = new EnrollByProfileCohort();
        $LPclass = new EnrollByProfileLP();
        $tenantclass = new EnrollByProfileTenant();
        $courseclass = new EnrollByProfileCourses();

        if($selected){
			$la_selected = json_decode($selected);
			foreach ($la_selected as $key => $value) {
				$la_select[$value] = $value ;
			}
		}

        switch ('get-'.$category) {
			case 'get-1':
					return $courseclass->GetCourses($la_select);
				break;
			case 'get-2':
					return $tenantclass->GetTenant($la_select);
				break;
			case 'get-3':
					return $LPclass->GetLP($la_select);
				break;
			case 'get-4':
					return $cohortclass->GetCohort($la_select);
				break;
			case 'get-5':
					return $roleclass->GetRole($la_select);
				break;
			case 'get-6':
					return $learningplansclass->GetLearningPlans($la_select);
				break;
		}
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_category_returns() {
        return new external_single_structure(
            array(
            	'data' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'active' => new external_value(PARAM_RAW, 'Is active',VALUE_OPTIONAL),
                            'la_elementname' => new external_value(PARAM_RAW, 'tenant name',VALUE_OPTIONAL),
                            'la_elementid' => new external_value(PARAM_RAW, 'tenant id',VALUE_OPTIONAL),
                            'checked' => new external_value(PARAM_RAW, 'Is checked',VALUE_OPTIONAL),
                            'classactive' => new external_value(PARAM_RAW, 'Is class active',VALUE_OPTIONAL),
                            'elementid' => new external_value(PARAM_RAW, 'elementid',VALUE_OPTIONAL),
                            'elementname' => new external_value(PARAM_RAW, 'elementname',VALUE_OPTIONAL),
                            'name' => new external_value(PARAM_RAW, 'name',VALUE_OPTIONAL),
                            'type' => new external_value(PARAM_RAW, 'type',VALUE_OPTIONAL),
                            'value' => new external_value(PARAM_RAW, 'value',VALUE_OPTIONAL)
                        ), 'rowdata'
                    ), 'category data array'
                ),
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function action_count_parameters() {
        return new external_function_parameters(
            array()
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function action_count() {
        global $CFG;

        $rulesclass = new EnrollByProfileRule();

        $data['count'] = $rulesclass->CountRule();
        return $data;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function action_count_returns() {
        return new external_single_structure(
            array(
            	'count' => new external_value(PARAM_RAW, 'Rules count')
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function save_category_parameters() {
        return new external_function_parameters(
            array(
            	'category' => new external_value(PARAM_RAW, 'category Id'),
            	'elements' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'the value to match',VALUE_OPTIONAL)),
            	'prof_field' => new external_value(PARAM_RAW, 'prof_field',VALUE_OPTIONAL),
            	'content' => new external_value(PARAM_RAW, 'content',VALUE_OPTIONAL),
            	'rid' => new external_value(PARAM_RAW, 'Rule Id',VALUE_OPTIONAL),
            	'rulename' => new external_value(PARAM_RAW, 'Rule name',VALUE_OPTIONAL),
            	'name' => new external_value(PARAM_RAW, 'name',VALUE_OPTIONAL)
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function save_category($category,$elements,$prof_field,$content,$rid,$rulename,$name) {
        global $CFG,$USER, $DB, $OUTPUT, $PAGE;

        switch ('save-'.$category) {
			case 'save-1':
			case 'save-2':
			case 'save-3':
			case 'save-4':
			case 'save-5':
			case 'save-6':	
                ob_end_clean();
				return SaveElements($elements,$category,$prof_field,$content,$rid,$rulename,$name);
			break;
		}
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function save_category_returns() {
        return new external_single_structure(
            array(
            	'msg' => new external_value(PARAM_RAW, 'status message'),
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function disable_rule_parameters() {
        return new external_function_parameters(
            array(
            	'rid' => new external_value(PARAM_RAW, 'Search value')
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function disable_rule($rid) {
        global $CFG,$USER, $DB, $OUTPUT, $PAGE;

        $rulesclass = new EnrollByProfileRule();
        $disablerule = $rulesclass->disableRule($rid);
        if( !empty($disablerule) ){
        	$data['msg'] = 1;
        }else{
        	$data['msg'] = 0;
        }
        return $data;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function disable_rule_returns() {
        return new external_single_structure(
            array(
            	'msg' => new external_value(PARAM_RAW, 'status message')
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function disable_all_rule_parameters() {
        return new external_function_parameters(
            array(
            	'allselect' => new external_value(PARAM_RAW, 'Search value'),
            	'selectedrule' => new external_value(PARAM_RAW, 'Search value')
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function disable_all_rule($allselect,$selectedrule) {
        global $CFG,$USER, $DB, $OUTPUT, $PAGE;

        $rulesclass = new EnrollByProfileRule();
        if($allselect>0) {
			return $rulesclass->allDisableRule();
		}else{
			$ruleid = explode(',',$selectedrule);
		    $response = $rulesclass->disableAllRule($ruleid);
            if(!empty($response)){
                $data['msg'] = 1;
            }else{
                $data['msg'] = 0;
            }
            return $data;
		}
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function disable_all_rule_returns() {
        return new external_single_structure(
            array(
            	'msg' => new external_value(PARAM_RAW, 'status message')
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function enable_rule_parameters() {
        return new external_function_parameters(
            array(
            	'rid' => new external_value(PARAM_RAW, 'Search value')
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function enable_rule($rid) {
        global $CFG,$USER, $DB, $OUTPUT, $PAGE;

        $rulesclass = new EnrollByProfileRule();
        
        $enablerule = $rulesclass->enableRule($rid);
        if( !empty($disablerule) ){
        	$data['msg'] = 1;
        }else{
        	$data['msg'] = 0;
        }

        return $data;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function enable_rule_returns() {
        return new external_single_structure(
            array(
            	'msg' => new external_value(PARAM_RAW, 'status message')
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function delete_rule_parameters() {
        return new external_function_parameters(
            array(
            	'rid' => new external_value(PARAM_RAW, 'Rule ids')
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function delete_rule($rid) {
        global $CFG,$USER, $DB, $OUTPUT, $PAGE;

        $rulesclass = new EnrollByProfileRule();
        
        if( $rid <> 0 )
		return $rulesclass->DeleteRule($rid);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function delete_rule_returns() {
        return new external_single_structure(
            array(
            	'msg' => new external_value(PARAM_RAW, 'status message')
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function edit_rule_parameters() {
        return new external_function_parameters(
            array(
            	'rid' => new external_value(PARAM_RAW, 'Rule ids')
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function edit_rule($rid) {
        global $CFG;

        $rulesclass = new EnrollByProfileRule();
        
        if( $rid <> 0 )
	        ob_end_clean();
		return $rulesclass->GetRulesObject($rid, $page = null, $perpage = null);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function edit_rule_returns() {
        return new external_single_structure(
            array(
            	'msg' => new external_value(PARAM_RAW, 'status message'),
            	'fieldtype' => new external_value(PARAM_RAW, 'fieldtype'),
            	'html' => new external_value(PARAM_RAW, 'html'),
            	'table' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'category' => new external_value(PARAM_RAW, 'category id'),
                            'content' => new external_value(PARAM_RAW, 'rule content'),
                            'disable_rule' => new external_value(PARAM_RAW, 'is rule disabled'),
                            'id' => new external_value(PARAM_RAW, 'rule id'),
                            'name' => new external_value(PARAM_RAW, 'rule name'),
                            'profile_field' => new external_value(PARAM_RAW, 'profile_field'),
                            'rulename' => new external_value(PARAM_RAW, 'rulename'),
                            'selected_elements' => new external_value(PARAM_RAW, 'selected_elements'),
                            'unenroll_rule' => new external_value(PARAM_RAW, 'unenroll_rule')
                        ), 'rowdata'
                    ), 'table data array'
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function conditional_html_parameters() {
        return new external_function_parameters(
            array(
            	'fieldselected' => new external_value(PARAM_RAW, 'Is field selected'),
            	'fieldsn' => new external_value(PARAM_RAW, 'field no'),
            	'fieldtype' => new external_value(PARAM_RAW, 'field type'),
            	'condid' => new external_value(PARAM_RAW, 'field id')
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function conditional_html($fieldselected,$fieldsn,$fieldtype,$condid) {
        global $CFG;
        
		return GetConditionalHtml($fieldtype,$fieldsn,$condid);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function conditional_html_returns() {
        return new external_single_structure(
            array(
            	'id' => new external_value(PARAM_RAW, 'field id'),
            	'text' => new external_value(PARAM_RAW, 'text field',VALUE_OPTIONAL),
            	'numeric' => new external_value(PARAM_RAW, 'numeric field',VALUE_OPTIONAL),
            	'datetime' => new external_value(PARAM_RAW, 'datetime field',VALUE_OPTIONAL),
            	'menu' => new external_value(PARAM_RAW, 'menu field',VALUE_OPTIONAL),
            	'multiselect' => new external_value(PARAM_RAW, 'multiselect field',VALUE_OPTIONAL),
            	'checkbox' => new external_value(PARAM_RAW, 'checkbox field',VALUE_OPTIONAL),
            	'tenant' => new external_value(PARAM_RAW, 'tenant field',VALUE_OPTIONAL),
            	'default' => new external_value(PARAM_RAW, 'default field',VALUE_OPTIONAL)
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function input_html_parameters() {
        return new external_function_parameters(
            array(
                'conditional' => new external_value(PARAM_RAW, 'conditional option'),
                'fieldsn' => new external_value(PARAM_RAW, 'field no'),
                'fieldtype' => new external_value(PARAM_RAW, 'field type'),
                'condid' => new external_value(PARAM_RAW, 'field id')
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function input_html($conditional,$fieldsn,$fieldtype,$condid) {
        global $CFG;
        
        return GetInputHtml($conditional,$fieldtype,$fieldsn,$condid);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function input_html_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_RAW, 'field id'),
                'negatedconditional' => new external_value(PARAM_RAW, 'Negated condition keyword',VALUE_OPTIONAL),
                'negatedconditionalstring' => new external_value(PARAM_RAW, 'Negated condition string',VALUE_OPTIONAL),
                'fields' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'label' => new external_value(PARAM_RAW, 'label'),
                            'value' => new external_value(PARAM_RAW, 'value'),
                            'id' => new external_value(PARAM_RAW, 'rule id'),
                            'required' => new external_value(PARAM_RAW, 'required'),
                            'extradata' => 
                                    new external_single_structure(
                                        array(
                                            'id' => new external_value(PARAM_RAW, 'id'),
                                            'name' => new external_value(PARAM_RAW, 'name'),
                                            'style' => new external_value(PARAM_RAW, 'style'),
                                            'class' => new external_value(PARAM_RAW, 'class'),
                                            'readonly' => new external_value(PARAM_RAW, 'readonly')
                                        ), 'extradata',VALUE_OPTIONAL
                                    ),
                                
                        ), 'fieldsdata'
                    ), 'fields data array',VALUE_OPTIONAL
                ),
                'options' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'name' => new external_value(PARAM_RAW, 'name'),
                                'value' => new external_value(PARAM_RAW, 'option value')
                            ), 'options'
                        ), 'options data array',VALUE_OPTIONAL
                    ),
                'multiple_conditions' => new external_value(PARAM_RAW, 'multiple_conditions',VALUE_OPTIONAL)
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function conditional_buttons_parameters() {
        return new external_function_parameters(
            array(
                'buttons' => new external_value(PARAM_RAW, 'Is field selected'),
                'condid' => new external_value(PARAM_RAW, 'field id')
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function conditional_buttons($buttons,$condid) {
        global $CFG;
        
        $data = GetActionButtons($condid,false);
        
        return $data;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function conditional_buttons_returns() {
        return new external_single_structure(
            array(
                'GetActionButtons' => 
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'id'),
                            'negated' => new external_value(PARAM_RAW, 'negated value',VALUE_OPTIONAL)
                        ), 'options'
                    ), 'buttons data array',VALUE_OPTIONAL
                
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function bool_opdropdown_parameters() {
        return new external_function_parameters(
            array(
                'dropdown' => new external_value(PARAM_RAW, 'Is dropdown field selected')
            )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function bool_opdropdown($dropdown) {
        global $CFG;
        
        return GetBoolOpDropdown();
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function bool_opdropdown_returns() {
        return new external_single_structure(
            array(
                'GetActionButtons' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_RAW, 'id'),
                            'negated' => new external_value(PARAM_RAW, 'negated value')
                        ), 'options'
                    ), 'buttons data array',VALUE_OPTIONAL
                ),
            )
        );
    }
}