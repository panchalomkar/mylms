<?php

class local_people_external extends external_api{

	public static function get_people_parameters(){
		return new external_function_parameters(
			array(
				'form' => new external_value(PARAM_TEXT, 'form'),
				'message' => new external_value(PARAM_TEXT, 'number of message'),
				'q' => new external_value(PARAM_BOOL, 'q exist'),
				'title' => new external_value(PARAM_TEXT, 'title'),
				'user' => new external_value(PARAM_TEXT, 'user'),
				'getter' => new external_value(PARAM_BOOL, 'getter'),
				'action' => new external_value(PARAM_TEXT, 'action'),
				'modaldata' => new external_value(PARAM_RAW, 'modaldata'),
			)
		);
	}

	public static function get_people($form, $message, $q, $title, $user, $getter, $action, $modaldata){
		global $CFG, $DB,$PAGE,$USER;

		$context = context_system::instance();
		$PAGE->set_context($context);

		require_once($CFG->dirroot . '/local/people/lib.php');
        // Validate params
		$params = self::validate_parameters(self::get_people_parameters(), ['form' => $form, 'message' => $message, 'q' => $q, 'title' => $title, 'user' => $user, 'getter' => $getter, 'action' => $action, 'modaldata' => $modaldata]);
		$response['form'] = '';
		$main_array = stripslashes($params['user']);

		$str = preg_replace( '/[^\d]/', ',', $main_array );
		$ex_values =  explode(',,', $str);
		$users = array();

		foreach ($ex_values as $ex_value) {

			if ($ex_value != '') {
				$users[] = str_replace( ",", '', $ex_value);
				
			}
		}
		$im_user = implode(',', $users);
		$ex_values_int =  explode(',', $im_user);
		// print_r($ex_values_int);
		if($params['getter'] && !empty($params['form']) && is_array($ex_values_int) && !empty($ex_values_int) && function_exists('get_'.$params['form'].'_form')){
			$response = call_user_func('get_'.$params['form'].'_form', $ex_values_int);

		}elseif($params['action'] !='na'){
			$data = json_decode($params['modaldata'],true);
			$response = call_user_func('save_'.$params['action'], $data);
		}

		return $response;
	}

	public static function get_people_returns(){
		return  new external_single_structure(
			array(
				'form' => new external_value(PARAM_RAW, 'page number',VALUE_OPTIONAL),
				'message' => new external_value(PARAM_RAW, 'number of message',VALUE_OPTIONAL),
				'q' => new external_value(PARAM_BOOL, 'q exist',VALUE_OPTIONAL),
				'title' => new external_value(PARAM_RAW, 'title',VALUE_OPTIONAL),
				'user' => new external_value(PARAM_RAW, 'user',VALUE_OPTIONAL),
				'getter' => new external_value(PARAM_BOOL, 'getter',VALUE_OPTIONAL),
				'action' => new external_value(PARAM_RAW, 'action',VALUE_OPTIONAL),
				'modaldata' => new external_value(PARAM_RAW, 'modaldata',VALUE_OPTIONAL),
			)
		);
	}

}

class external extends external_api{
	public static function get_local_people_parameters(){
		return new external_function_parameters(
			array(
				'tenant_val' => new external_value(PARAM_INT, 'form'),
				'activity' => new external_value(PARAM_TEXT, 'number of message',VALUE_OPTIONAL),
				'status' => new external_value(PARAM_TEXT, 'q exist'),
				'message' => new external_value(PARAM_TEXT, 'title'),
			)
		);
	}

	public static function get_local_people($tenantval, $activity, $status, $message){
		include_once( '../../../config.php');
		global $CFG, $PAGE, $USER, $SITE, $COURSE, $OUTPUT, $DB;
		$params = self::validate_parameters(self::get_local_people_parameters(), ['tenant_val' => $tenantval, 'activity' => $activity, 'status' => $status, 'message' => $message]);
		$userid = $USER->id;

		$multi_tenet_peple = $DB->get_record('user_preferences', array('name'=>'people_multitenant'));
		if (!confirm_sesskey()) {
		    // throw new moodle_exception('invalidsesskey', 'error');
			$response = array('status'=>'error','message'=>get_string('invalidsesskey','local_people'));
		}
		if(false == $multi_tenet_peple) {
			$multi_tenet_peple = new stdClass();
			$multi_tenet_peple->name = 'people_multitenant';
			$multi_tenet_peple->userid = $userid;
			$multi_tenet_peple->value = $params['tenant_val'];
			$multi_tenet_peple->id = $DB->insert_record('user_preferences', $multi_tenet_peple);
			$response = array('status'=>'insert');
		}else{
			$multi_tenet_peple->userid = $userid;
			$multi_tenet_peple->value = $params['tenant_val'];
			$DB->update_record('user_preferences', $multi_tenet_peple);
			$response = array('status'=>'update');
		}
		return $response;
	}

	public static function get_local_people_returns(){
		return  new external_single_structure(
			array(
				'tenant_val' => new external_value(PARAM_INT, 'form', VALUE_OPTIONAL),
				'activity' => new external_value(PARAM_TEXT, 'number of message',VALUE_OPTIONAL),
				'status' => new external_value(PARAM_TEXT, 'q exist', VALUE_OPTIONAL),
				'message' => new external_value(PARAM_TEXT, 'title', VALUE_OPTIONAL),
			)
		);
	}
}