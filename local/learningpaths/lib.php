<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Get learning paths list in the database.
 * List depends of user capabilities
 */
function get_learningpaths($lp_name = null, $onlySelfEnroll = false) {
    // Global variables to use in this function.
    global $DB;
    $search = optional_param('search_lp', '', PARAM_TEXT);
    $sql = 'SELECT * FROM {learningpaths} WHERE deleted = ?';
    if ($lp_name != null) {
        $search = $lp_name;
    }

    if ($onlySelfEnroll) {
        $sql .= " AND self_enrollment = 1 ";
    }
    // Depending of user capabilities, get learningpaths list.
    if (has_capability('local/learningpaths:managecompanylearningpaths', context_system::instance())) {
        if ($company = lms_get_current_editing_company()) {
            $sql .= ' AND companyid = ? ';
            if ($search) {
                $sql .= ' AND  ( name like ("%' . $search . '}%") or description LIKE ("%' . $search . '%"))';
            }
            return $DB->get_records_sql($sql, [0, $company->id]);
        } else {
            if ($search) {
                $sql .= ' AND  ( name like ("%' . $search . '%") or description LIKE ("%' . $search . '%")) ';
            }
            return $DB->get_records_sql($sql, [0]);
        }
    } else if (has_capability('local/learningpaths:managealllearningpaths', context_system::instance()) || has_capability('local/learningpaths:viewlearningpaths', context_system::instance())) {
        if ($search) {
            $sql .= ' AND  ( name like ("%' . $search . '%") or description LIKE ("%' . $search . '%")) ';
        }
        return $DB->get_records_sql($sql, [0]);
    }
    throw new moodle_exception(get_string('access_denied', 'local_learningpaths'));
}

/**
 * Show learning paths
 */
function learningpaths_view($lps = []) {
    // Global objects.
    global $USER, $CFG, $PAGE;
    /**
     * String needed for the Javascript section
     * @author Daniel
     * @since 2018-03-07
     * @paradiso
     */
    $PAGE->requires->string_for_js('delete_msg', 'local_learningpaths');
    
    // Get Learning Paths.
    if (count($lps) == 0) {
        $learningpaths = get_learningpaths();
    }

    $renderer = $PAGE->get_renderer('local_learningpaths');
    return $renderer->dashboard($learningpaths);
}

if ( !function_exists('lms_get_current_editing_company')) {
    function lms_get_current_editing_company() {

        global $SESSION, $DB, $USER;

        $context = context_system::instance();
        $roles = get_user_roles($context, $USER->id);
        $companymanager = false;

        foreach ($roles as $role) {
            if ($role->shortname == 'companymanager') {
                $manager = true;
                break;
            }
        }
        $company = null;
        if ((is_siteadmin() || $companymanager == true ) || (isset($SESSION->currenteditingcompany) && !empty($SESSION->currenteditingcompany) )) {
            if (isset($SESSION->currenteditingcompany) && !empty($SESSION->currenteditingcompany)) {
                $company = $DB->get_record('company', array(
                    'id' => (int) $SESSION->currenteditingcompany
                ));
            }
        } else {
            if (isset($USER->company)) {
                $company = $DB->get_record('company', array('id' => (int) $USER->company->id));
            }
        }
        return $company;
    }
}

/**
 * Get a list of active learningpaths and returns it
 */
function get_active_learningpaths() {
    global $DB;
    $date = date('Y-m-d');
    $timestamp = strtotime($date);
    return $DB->get_records_sql('SELECT id FROM {learningpaths} WHERE deleted = ?', [0]);
}

function lp_profile_save_data($usernew) {
    global $CFG, $DB;

    if ($fields = $DB->get_records('lp_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot . '/local/properties/lpfield/' . $field->datatype . '/field.class.php');
            $newfield = 'lp_field_' . $field->datatype;
            $formfield = new $newfield($field->id, $usernew->id);
            $formfield->edit_save_data($usernew);
        }
    }
}

/**
 * Cron function - This will do the enrollments
 */
function local_learningpaths_cron() {
    mtrace("Running learning paths cron job");

    // Load learningpath class, for that we need to have available moodle $CFG.
    global $CFG;
    require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPath.php");

    // Get learningpaths working.
    $learningpaths = get_active_learningpaths();
    $total = count($learningpaths);
    foreach ($learningpaths as $learningpathdb) {
        $learningpath = new LearningPath($learningpathdb->id);
        // If learningpath has courses, proceed with enrollments.
        if (count($learningpath->data->courses > 0)) {
            // Enrol users if learningpath has it.
            if (count($learningpath->data->users) > 0) {
                $learningpath->process_users($learningpath->get_all_users_not_completed());
            }
            // Enrol cohorts if learningpath has it.
            if (count($learningpath->data->cohorts) > 0) {
                $learningpath->enroll_cohorts();
            }
        }
    }
}

/**
 * Display learningpath image using learningpath id
 * @param (id)
 */
function get_learningpath_image($file) {
    global $CFG;
    require_once("{$CFG->dirroot}/lib/filelib.php");
    send_stored_file($file, 10 * 60, 0, false, ['preview' => false]);
}

/**
 * Load learningpath image into draft area
 * @param (id)
 */
function load_image_to_draft($file) {
    global $CFG;
    require_once("{$CFG->libdir}/filelib.php");
    $draftitemid = 0;
    if ($file) {
        file_prepare_draft_area($draftitemid, $file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid()
        );
    }
    return $draftitemid;
}

function get_learningpath_custom_properties_html($lpid = null) {
    global $COURSE, $CFG, $DB,$OUTPUT;
    $output = '';
    if ((int) $lpid <= 0) {
        return $output;
    }
    // Get the data of the lp
    $customproperties = $DB->get_records('lp_info_data', ['lpid' => $lpid]);
    $_categories_id = [];
    if ($categories = $DB->get_records('lp_info_category', null, 'sortorder ASC')) {
        foreach ($customproperties as $key => $value) {
            // Get the field info
            $coursepropertydata = $DB->get_record('lp_info_field', array('id' => $value->fieldid));
            if (!in_array($coursepropertydata->categoryid, $_categories_id)) {
                /* Get the category name */
                $cat = $categories[$coursepropertydata->categoryid];
                $_categories_id[] = $coursepropertydata->categoryid;
                $category[]= array('catname'=>$cat->name);
            }
            // If the property is visible we print the value
            if ($coursepropertydata->visible != 0) {
                $coursepropertydata->value = $value->data;
                // Get the value formated 
                $value = get_field_value((array) $coursepropertydata);
                // Print the text and its value
                $text_property = html_writer::tag('strong', $coursepropertydata->name . ': ') . $value;
                $property[]= array('text_property'=>$text_property);
            }
            
        }
    }
    $templatecontext = array(
      'site_url' => $CFG->wwwroot,
      'property' => $property,
      'category'=>$category
  );
    return $OUTPUT->render_from_template('local_learningpaths/learningpath_custom_properties_html', $templatecontext);
}

/**
 * Depending of the datatype of the field, this function transforms de data into a string
 * @author Daniel Carmona <daniel.carmona@paradisosolutions.com>
 * @param array $_field data with the field info and its value
 * @return string Data converted to show the info
 */
function get_field_value($_field) {
    global $CFG, $DB;
    $data = $_field['value'];

    switch ($_field['datatype']) {
        case 'checkbox':
            if ($_field['value']) {
                $data = get_string('yes');
            } else {
                $data = get_string('no');
            }
            break;
        case 'datetime':
            $data = date('Y/m/d', $_field['value']);
            break;
        case 'menu':
            /* Get the possible options */
            $options = explode("\n", $_field['param1']);
            /* Get the values selected */
            $values = explode(',', $_field['value']);
            $data = [];
            foreach ($options as $key => $option) {
                if (in_array($key, $values)) {
                    $data[] = $option;
                }
            }
            if (!empty($data)) {
                $data = implode(', ', $data);
            }

        case 'multiselectlist':
            /* Get the possible options */
            $options = explode("\n", $_field['param1']);

            /* Get the values selected */
            $values = explode(',', $_field['value']);
            $data = [];
            foreach ($options as $key => $option) {
                if (in_array($key, $values)) {
                    $data[] = $option;
                }
            }
            if (!empty($data)) {
                $data = implode(', ', $data);
            }
            break;
        case 'multiselect':
            /* Users */
            require_once("{$CFG->dirroot}/local/iomad/lib/iomad.php");
            $context = context_system::instance();
            $companyid = iomad::get_my_companyid($context, false);
            # If we have chosen a tenant then the variable shold have the ID
            # in so case we only have to pull users tied to that tenant
            if ($companyid) {
                $sql = "
                SELECT 
                u.id, u.firstname, u.lastname
                FROM {user} AS u 
                INNER JOIN  {company_users} AS cu ON cu.userid = u.id
                WHERE cu.companyid = {$companyid}
                AND u.deleted = 0
                ORDER BY u.firstname";
                $users = $DB->get_records_sql($sql);
            } else {
               // $users = get_users(true);
                $users = $DB->get_records_sql("SELECT id,firstname,lastname FROM {user} WHERE id <> '1' AND deleted = 0 ORDER BY firstname ASC");
            }
            $options = [];
            $i = 1;
            $values = explode(',', $_field['value']);
            $valuesArr = explode(PHP_EOL, $values[0]);
            /**
             * changes to fix wrong instructor showing for learningpath
             * @author Manisha M
             * @since 24-04-2019
             * @paradiso
            */
            foreach ($valuesArr As $val){
                $i++;
                $instructorname = explode("-",$val);
                $options[$i] =  format_string($instructorname[1]);
            }
            $data = implode(",",$options);
            break;
    }
    return $data;
}

function save_notification($record) {
    global $DB;
    if (is_object($record) && property_exists($record, 'type') && property_exists($record, 'learningpathid')) {
        /* Search if the config already exists */
        $config = $DB->get_record('learningpath_notifications', ['type' => $record->type, 'learningpathid' => $record->learningpathid]);
        if (!$config) {
            $DB->insert_record('learningpath_notifications', $record, false);
        } else {
            /* If not update the record */
            $record->id = $config->id;
            $DB->update_record('learningpath_notifications', $record);
        }
    }
}

function get_config_notification($learningpathid, $type) {
    global $DB;
    $config = [];
    $data = $DB->get_record('learningpath_notifications', ['type' => $type, 'learningpathid' => $learningpathid]);
    if ($data && $data->config != '') {
        $config = json_decode($data->config, true);
    }
    return $config;
}

function get_types($learningpathid) {
    global $DB;
    $config = [];
    $data = $DB->get_records('learningpath_notifications', ['learningpathid' => $learningpathid]);
    foreach ($data as $v) {
        if ($v && $v->config != '') {
            $config[] = $v->type;
        }
    }
    return $config;
}

/**
 * Sends the notification according to the configuration in the learning path notification tab.
 * @author Daniel Carmona
 * @since 05-04-2018
 * @param object $record StdClass
 * @paradiso
 */
function send_notification_enroll($record = null) {
    global $DB;
    $type = (property_exists($record, 'userid')) ? 'users' : 'cohorts';
    /* Check if the notification for the type "enrollment" is activated */
    $datanotification = get_config_notification($record->learningpathid, 'enrollment');
    if (!empty($datanotification) && boolval($datanotification['enrollment_editor_checkbox1']) && $datanotification['enrollment_editor']['text']) {
        switch ($type) {
            case 'cohorts':
                /** get cohort users */
                $sql = "SELECT u.*
                        FROM {cohort_members} cm 
                        INNER JOIN {user} u ON cm.userid = u.id
                        WHERE cm.cohortid = ?";
                $cohort_users = $DB->get_records_sql($sql, [$record->cohortid]);
                if (!empty($cohort_users)) {
                    /** foreach user send the notification */
                    foreach ($cohort_users as $user) {
                        send_mail_student(['template' => $datanotification['enrollment_editor']['text'], 'learningpathid' => $record->learningpathid], $user);
                    }
                }
                break;
            case 'users':
                $user = $DB->get_record('user', ['id' => $record->userid]);
                send_mail_student(['template' => $datanotification['enrollment_editor']['text'], 'learningpathid' => $record->learningpathid], $user);
                break;
            default:
                break;
        }
    }
}
/**
 * @description string with tags in format {"course"_tag} will be search in the array given and it will replace the tags for the value
 * @author Daniel Carmona <daniel.carmona@paradisosolutions.com>
 * @since 03-26-2018
 * @param sttring $string String with tags to be replaced
 * @param array $settings Array with the next setup ['course' => (array)$data,...,n]
 * @return string String without tags
 */
function replace_tags($string, $settings = []) {
    global $DB;
    if (!empty($settings)) {
        /* we get all the tags string */
        $data_tags = get_string_between($string, '{', '}');
        foreach ($data_tags as $tag) {
            $pos = strpos($tag, '_');
            $model = '';
            $property = '';
            if ($pos !== false) {
                $property = strip_tags(substr($tag, $pos + 1, strlen($tag)));
                $model = strip_tags(substr($tag, 0, $pos));
                if ($model == 'user' && $property == 'fullname') {
                    $settings[$model][$property] = $settings[$model]['firstname'] . ' ' . $settings[$model]['lastname'];
                } elseif ($model == 'learningpath' && ($property == 'startdate' || $property == 'enddate')) {
                    $settings[$model][$property] = isset($settings[$model][$property]) ? userdate($settings[$model][$property], '%m/%d/%Y') : get_string('notset', 'local_learningpaths');
                } elseif ($model == 'learningpath' && $property == 'link') {
                    $link =new moodle_url('/local/learningpaths/view.php', array('id' => $settings['learningpath']['id']));
                    $settings[$model][$property] = $link;
                } elseif ($model == 'learningpath' && $property == 'coursesrequired') {
                    $settings[$model][$property] = $DB->count_records_sql('SELECT count(*) FROM {course}
                                    INNER JOIN {learningpath_courses}
                                    ON {learningpath_courses}.courseid = {course}.id
                                    WHERE {learningpath_courses}.learningpathid = ? AND {learningpath_courses}.required = 1
                                ', [$settings[$model]['id']]);
                }
            }
            if (!empty($model) && !empty($property) && array_key_exists($model, $settings)) {
                $string = str_replace('{' . $tag . '}', $settings[$model][$property], $string);
            }
        }
    }
    return $string;
}

function get_string_between($string, $start, $end) {
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    $array_tags = [];
    if ($ini == 0)
        return $array_tags;

    $exist_data = $ini;
    while ($exist_data != '') {
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        $str_tmp = substr($string, $ini, $len);
        $array_tags[] = $str_tmp;
        $string = str_replace($start . $str_tmp . $end, '', $string);
        $exist_data = strpos($string, $start);
    }
    return $array_tags;
}

function send_mail_student($data, $user) {
    global $DB;
    if ($user->email) {
        $learningpath = $DB->get_record('learningpaths', ['id' => $data['learningpathid']]);
        $email_subject = get_string('enrollment_subject', 'local_learningpaths', $learningpath);
        // Render template
        $body = replace_tags($data['template'], ['user' => (array) $user,'learningpath' => (array) $learningpath]);
        try {
            $site = get_site();
            $mail = email_to_user($user, $site->shortname, $email_subject, $body, $body);
            $noti_log = new stdClass();
            $noti_log->notificationid = 1;
            $noti_log->userid = $user->id;
            $noti_log->sent = 1;
            $noti_log->datesent = $data['learningpathid'];
            $DB->insert_record('learningpath_noti_log', $noti_log);
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}