<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

defined('MOODLE_INTERNAL') || die;
/*
 * @param object $data  - all the data needed for an entry in the 'venue' table
 * @return object new venuemanangements_mapping instance
 */


function create_bu($data){
   global $DB;
   $id = $DB->insert_record("local_bu", $data); 
   return $id;
}

function update_bu($data){
   global $DB;
   $id = $DB->update_record("local_bu", $data); 
}

function create_venuemanangement($data) {
    global $DB;

    $classroom = new stdClass();

    $classroom->locationid = $data->locationid;
    $classroom->classroom =       $data->classroom;
    $classroom->capacity = ( is_numeric( $data->capacity ) ) ? $data->capacity : 0;

    $id = $DB->insert_record("local_classroom", $classroom);
    return $id;
}

/*
 * Author VaibhavG
 * 18Dec2018
 * @return void
 */
function create_venuemanangement_resource($data) {
    global $DB;

    $resource = new stdClass();

    $resource->classroomid = $data->classroomid;
    $resource->resource =       $data->resource;
    $resource->resourceqty = ( is_numeric( $data->resourceqty ) ) ? $data->resourceqty : 0;

    $id = $DB->insert_record("local_resource", $resource);
    return $id;
}

function update_venuemanangement_resource($data) {
    global $DB;

    $resource = new stdClass();

    $resource->id = $data->id;
    $resource->classroomid = $data->classroomid;
    $resource->resource = $data->resource;
    $resource->resourceqty = ( is_numeric( $data->resourceqty ) ) ? $data->resourceqty : 0;
    
    return $DB->update_record("local_resource", $resource);
}
/*
 * vaibhavG
 * @param object $data  - all the data needed for an entry in the 'venue' table
 * @End Code
 */

function update_venuemanangement($data) {
    global $DB;

    $classroom = new stdClass();

    $classroom->id = $data->id;
    $classroom->locationid = $data->locationid;
    $classroom->classroom = $data->classroom;
    $classroom->capacity = ( is_numeric( $data->capacity ) ) ? $data->capacity : 0;
    
    return $DB->update_record("local_classroom", $classroom);
}

/*
 * @param
 * @return void
 */

//function get_business_unit_count( $data ) {
//    global $DB;
//
//    $sql = 'SELECT count(id) as count FROM mdl_venue_business_units WHERE name LIKE \'' . $data->name . '\'';
//
//    $result = $DB->get_record_sql( $sql );
//    return $result->count;
//}
//
//function get_venue_location_count( $data ) {
//    global $DB;
//
//    $sql = 'SELECT count(id) as count FROM mdl_venue_locations WHERE name LIKE \'' . $data->name . '\' AND businessunitid = ' . ( int ) $data->businessunitid;
//    $result = $DB->get_record_sql( $sql );
//
//    return $result->count;
//}
//
//function get_business_unit_id( $data ) {
//    global $DB;
//
//    $sql = 'SELECT id FROM mdl_venue_business_units WHERE name LIKE \'' . $data->name . '\'';
//    $result = $DB->get_record_sql( $sql );
//    return $result->id;
//}
//
//function get_venue_location_id( $data ) {
//    global $DB;
//
//    $sql = 'SELECT id FROM mdl_venue_locations WHERE name LIKE \'' . $data->name . '\'';
//    $result = $DB->get_record_sql( $sql );
//
//    return $result->id;
//}

function get_venuemanangement_listing($filter_params = array(), $page = 0, $perpage = 10) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            $select_params[] = $field . '=:' . $field;
            $params[$field] = $value;
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $accesscards = $DB->get_records_sql("SELECT * ,(@i:=@i+1) sno FROM {local_classroom} join (select @i:=0) serial
                                  WHERE 1 $select
                                  $sort", $params, $limit, $perpage);
    return $accesscards;
}
function get_venuemanangement_counts($filter_params = array()) {
    global $DB;
    $count = 0;
    $select = '';
    $sort = '';
    $params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            $select_params[] = $field . '=:' . $field;
            $params[$field] = $value;
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $count = $DB->count_records_sql("SELECT COUNT(*) FROM {local_classroom}
                                  WHERE 1 $select
                                  $sort", $params);
    return $count;
}
function get_bu_listing($filter_params = array(), $page = 0, $perpage = 10) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            $select_params[] = $field . '=:' . $field;
            $params[$field] = $value;
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $accesscards = $DB->get_records_sql("SELECT * ,(@i:=@i+1) sno FROM {local_bu} join (select @i:=0) serial
                                  WHERE 1 $select
                                  $sort", $params, $limit, $perpage);
    return $accesscards;
}

// add code for getting count of location to pagination by VaibhavG dated on 24Jan2019
function get_bu_count($filter_params = array()) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            $select_params[] = $field . '=:' . $field;
            $params[$field] = $value;
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $count = $DB->count_records_sql("SELECT COUNT(*) FROM {local_bu}
                                  WHERE 1 $select
                                  $sort", $params);
    return $count;
}

/*
 *Author Vaibhav G
 * added new function to fetch resources of classroom 
 * 20Dec 2018
*/
function get_resource_listing($filter_params = array(), $page = 0, $perpage = 10) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            $select_params[] = $field . '=:' . $field;
            $params[$field] = $value;
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $accesscards = $DB->get_records_sql("SELECT * ,(@i:=@i+1) sno FROM {local_resource} join (select @i:=0) serial
                                  WHERE 1 $select
                                  $sort", $params, $limit, $perpage);
    return $accesscards;
}

function get_resource_counts($filter_params = array()) {
    global $DB;
    $count = 0;
    $select = '';
    $sort = '';
    $params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            $select_params[] = $field . '=:' . $field;
            $params[$field] = $value;
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $count = $DB->count_records_sql("SELECT COUNT(*) FROM {local_resource}
                                  WHERE 1 $select
                                  $sort", $params);
    return $count;
}
/*
 *End by VaibhavG
 * 
 */

/*
 *
 */

function delete_venuemanangement($id = 0) {
    global $DB;
    // delete
    $DB->delete_records('venuemanangements_mapping', array('id' => $id));
}

class progress11_tracker {

    private $_row;
    public $columns = array('status', 'line', 'id', 'username', 'firstname', 'lastname', 'email', 'password', 'auth', 'enrolments', 'suspended', 'deleted');

    /**
     * Print table header.
     * @return void
     */
    public function start() {
        $ci = 0;
        echo '<table id="uuresults" class="generaltable boxaligncenter flexible-wrap" summary="' . get_string('uploadusersresult', 'tool_uploaduser') . '">';
        echo '<tr class="heading r0">';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('status') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('uucsvline', 'tool_uploaduser') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">ID</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('username') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('firstname') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('lastname') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('email') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('password') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('authentication') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('enrolments', 'enrol') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('suspended', 'auth') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('delete') . '</th>';
        echo '</tr>';
        $this->_row = null;
    }

    /**
     * Flush previous line and start a new one.
     * @return void
     */
    public function flush() {
        if (empty($this->_row) or empty($this->_row['line']['normal'])) {
            // Nothing to print - each line has to have at least number
            $this->_row = array();
            foreach ($this->columns as $col) {
                $this->_row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
            }
            return;
        }
        $ci = 0;
        $ri = 1;
        echo '<tr class="r' . $ri . '">';
        foreach ($this->_row as $key => $field) {
            foreach ($field as $type => $content) {
                if ($field[$type] !== '') {
                    $field[$type] = '<span class="uu' . $type . '">' . $field[$type] . '</span>';
                } else {
                    unset($field[$type]);
                }
            }
            echo '<td class="cell c' . $ci++ . '">';
            if (!empty($field)) {
                echo implode('<br />', $field);
            } else {
                echo '&nbsp;';
            }
            echo '</td>';
        }
        echo '</tr>';
        foreach ($this->columns as $col) {
            $this->_row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
        }
    }

    /**
     * Add tracking info
     * @param string $col name of column
     * @param string $msg message
     * @param string $level 'normal', 'warning' or 'error'
     * @param bool $merge true means add as new line, false means override all previous text of the same type
     * @return void
     */
    public function track($col, $msg, $level = 'normal', $merge = true) {
        if (empty($this->_row)) {
            $this->flush(); //init arrays
        }
        if (!in_array($col, $this->columns)) {
            debugging('Incorrect column:' . $col);
            return;
        }
        if ($merge) {
            if ($this->_row[$col][$level] != '') {
                $this->_row[$col][$level] .='<br />';
            }
            $this->_row[$col][$level] .= $msg;
        } else {
            $this->_row[$col][$level] = $msg;
        }
    }

    /**
     * Print the table end
     * @return void
     */
    public function close() {
        $this->flush();
        echo '</table>';
    }

}

/**
 * Validation callback function - verified the column line of csv file.
 * Converts standard column names to lowercase.
 * @param csv_import_reader $cir
 * @param array $stdfields standard user fields
 * @param array $profilefields custom profile fields
 * @param moodle_url $returnurl return url in case of any error
 * @return array list of fields
 */
function validate_venuemanangement_upload_columns(csv_import_reader $cir, $stdfields, moodle_url $returnurl) {
    $columns = $cir->get_columns();

    if (empty($columns)) {
        $cir->close();
        $cir->cleanup();
        print_error('cannotreadtmpfile', 'error', $returnurl);
    }
    if (count($columns) < 2) {
        $cir->close();
        $cir->cleanup();
        print_error('csvfewcolumns', 'error', $returnurl);
    }

    // test columns
    $processed = array();
    foreach ($columns as $key => $unused) {
        $field = $columns[$key];
        $lcfield = core_text::strtolower($field);
        if (in_array($field, $stdfields) or in_array($lcfield, $stdfields)) {
            // standard fields are only lowercase
            $newfield = $lcfield;
        } else if (in_array($field, $profilefields)) {
            // exact profile field name match - these are case sensitive
            $newfield = $field;
        } else if (in_array($lcfield, $profilefields)) {
            // hack: somebody wrote uppercase in csv file, but the system knows only lowercase profile field
            $newfield = $lcfield;
        } else if (preg_match('/^(cohort|course|group|type|role|enrolperiod|enrolstatus)\d+$/', $lcfield)) {
            // special fields for enrolments
            $newfield = $lcfield;
        } else {
            $cir->close();
            $cir->cleanup();
            print_error('invalidfieldname', 'error', $returnurl, $field);
        }
        if (in_array($newfield, $processed)) {
            $cir->close();
            $cir->cleanup();
            print_error('duplicatefieldname', 'error', $returnurl, $newfield);
        }
        $processed[$key] = $newfield;
    }

    return $processed;
}

/**
 * Increments username - increments trailing number or adds it if not present.
 * Varifies that the new username does not exist yet
 * @param string $username
 * @return incremented username which does not exist yet
 */
//function uu_increment_username($username) {
//    global $DB, $CFG;
//
//    if (!preg_match_all('/(.*?)([0-9]+)$/', $username, $matches)) {
//        $username = $username.'2';
//    } else {
//        $username = $matches[1][0].($matches[2][0]+1);
//    }
//
//    if ($DB->record_exists('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
//        return uu_increment_username($username);
//    } else {
//        return $username;
//    }
//}

/**
 * Check if default field contains templates and apply them.
 * @param string template - potential tempalte string
 * @param object user object- we need username, firstname and lastname
 * @return string field value
 */
function process_template($template, $user) {
    if (is_array($template)) {
        // hack for for support of text editors with format
        $t = $template['text'];
    } else {
        $t = $template;
    }
    if (strpos($t, '%') === false) {
        return $template;
    }

    $username = isset($user->username) ? $user->username : '';
    $firstname = isset($user->firstname) ? $user->firstname : '';
    $lastname = isset($user->lastname) ? $user->lastname : '';

    $callback = partial('process_template_callback', $username, $firstname, $lastname);

    $result = preg_replace_callback('/(?<!%)%([+-~])?(\d)*([flu])/', $callback, $t);

    if (is_null($result)) {
        return $template; //error during regex processing??
    }

    if (is_array($template)) {
        $template['text'] = $result;
        return $t;
    } else {
        return $result;
    }
}

/**
 * Internal callback function.
 */
function process_template_callback($username, $firstname, $lastname, $block) {
    switch ($block[3]) {
        case 'u':
            $repl = $username;
            break;
        case 'f':
            $repl = $firstname;
            break;
        case 'l':
            $repl = $lastname;
            break;
        default:
            return $block[0];
    }

    switch ($block[1]) {
        case '+':
            $repl = core_text::strtoupper($repl);
            break;
        case '-':
            $repl = core_text::strtolower($repl);
            break;
        case '~':
            $repl = core_text::strtotitle($repl);
            break;
    }

    if (!empty($block[2])) {
        $repl = core_text::substr($repl, 0, $block[2]);
    }

    return $repl;
}

/**
 * Returns list of auth plugins that are enabled and known to work.
 *
 * If ppl want to use some other auth type they have to include it
 * in the CSV file next on each line.
 *
 * @return array type=>name
 */
function supported_auths() {
    // Get all the enabled plugins.
    $plugins = get_enabled_auth_plugins();
    $choices = array();
    foreach ($plugins as $plugin) {
        $objplugin = get_auth_plugin($plugin);
        // If the plugin can not be manually set skip it.
        if (!$objplugin->can_be_manually_set()) {
            continue;
        }
        $choices[$plugin] = get_string('pluginname', "auth_{$plugin}");
    }

    return $choices;
}

/**
 * Returns list of roles that are assignable in courses
 * @return array
 */
function allowed_roles() {
    // let's cheat a bit, frontpage is guaranteed to exist and has the same list of roles ;-)
    $roles = get_assignable_roles(context_course::instance(SITEID), ROLENAME_ORIGINALANDSHORT);
    return array_reverse($roles, true);
}

/**
 * Returns mapping of all roles using short role name as index.
 * @return array
 */

//function allowed_roles_cache() {
//    $allowedroles = get_assignable_roles(context_course::instance(SITEID), ROLENAME_SHORT);
//    foreach ($allowedroles as $rid => $rname) {
//        $rolecache[$rid] = new stdClass();
//        $rolecache[$rid]->id = $rid;
//        $rolecache[$rid]->name = $rname;
//        if (!is_numeric($rname)) { // only non-numeric shortnames are supported!!!
//            $rolecache[$rname] = new stdClass();
//            $rolecache[$rname]->id = $rid;
//            $rolecache[$rname]->name = $rname;
//        }
//    }
//    return $rolecache;
//}

/**
 * Pre process custom profile data, and update it with corrected value
 *
 * @param stdClass $data user profile data
 * @return stdClass pre-processed custom profile data
 */
function process_custom_profile_data($data) {
    global $CFG, $DB;
    // find custom profile fields and check if data needs to converted.
    foreach ($data as $key => $value) {
        if (preg_match('/^profile_field_/', $key)) {
            $shortname = str_replace('profile_field_', '', $key);
            if ($fields = $DB->get_records('user_info_field', array('shortname' => $shortname))) {
                foreach ($fields as $field) {
                    require_once($CFG->dirroot . '/user/profile/field/' . $field->datatype . '/field.class.php');
                    $newfield = 'profile_field_' . $field->datatype;
                    $formfield = new $newfield($field->id, $data->id);
                    if (method_exists($formfield, 'convert_external_data')) {
                        $data->$key = $formfield->convert_external_data($value);
                    }
                }
            }
        }
    }
    return $data;
}

/**
 * Checks if data provided for custom fields is correct
 * Currently checking for custom profile field or type menu
 *
 * @param array $data user profile data
 * @return bool true if no error else false
 */
function check_custom_profile_data(&$data) {
    global $CFG, $DB;
    $noerror = true;

    // find custom profile fields and check if data needs to converted.
    foreach ($data as $key => $value) {
        if (preg_match('/^profile_field_/', $key)) {
            $shortname = str_replace('profile_field_', '', $key);
            if ($fields = $DB->get_records('user_info_field', array('shortname' => $shortname))) {
                foreach ($fields as $field) {
                    require_once($CFG->dirroot . '/user/profile/field/' . $field->datatype . '/field.class.php');
                    $newfield = 'profile_field_' . $field->datatype;
                    $formfield = new $newfield($field->id, 0);
                    if (method_exists($formfield, 'convert_external_data') &&
                            is_null($formfield->convert_external_data($value))) {
                        $data['status'][] = get_string('invaliduserfield', 'error', $shortname);
                        $noerror = false;
                    }
                }
            }
        }
    }
    return $noerror;
}

function get_locations_by_business_unit( $data ) {

    global $DB;

    $strsql = 'SELECT * FROM venue_locations WHERE businessunitid = ' . ( int ) $data->businessunitid;
    return $DB->get_record_sql( $sql );
}

function get_venue_locations(){
    global $DB;
    $page = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 10, PARAM_INT);
    if($page > 0){
        $limit = $page * $perpage . ', '. $perpage;
    }
    else{
        $limit = $perpage;
    }
    print_r( 'SELECT * FROM mdl_local_bu LIMIT '. $limit, $perpage ); 
    $locations = $DB->get_records_sql( 'SELECT * FROM mdl_local_bu LIMIT '. $limit );
    return $locations; 
}