<?php
class lp_profile_field_base {

    // These 2 variables are really what we're interested in.
    // Everything else can be extracted from them.

    /** @var int */
    public $fieldid;

    /** @var int */
    public $lpid;

    /** @var stdClass */
    public $field;

    /** @var string */
    public $inputname;

    /** @var mixed */
    public $data;

    /** @var string */
    public $dataformat;

    /**
     * Constructor method.
     * @param int $fieldid id of the profile from the user_info_field table
     * @param int $userid id of the user for whom we are displaying data
     */
    public function lp_profile_field_base($fieldid=0, $lpid=0) {
        global $USER;

        $this->set_fieldid($fieldid);
        $this->set_lpid($lpid);
        $this->load_data();
    }

    /**
     * Abstract method: Adds the profile field to the moodle form class
     * @abstract The following methods must be overwritten by child classes
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_add($mform) {
        print_error('mustbeoveride', 'debug', '', 'edit_field_add');
    }

    /**
     * Display the data for this field
     * @return string
     */
    public function display_data() {
        $options = new stdClass();
        $options->para = false;
        return format_text($this->data, FORMAT_MOODLE, $options);
    }

    /**
     * Print out the form field in the edit profile page
     * @param moodleform $mform instance of the moodleform class
     * @return bool
     */
    public function edit_field($mform) {
        if ($this->field->visible != PROFILE_VISIBLE_NONE
          or has_capability('moodle/user:update', context_system::instance())) {
            
            $this->edit_field_add($mform);
            $this->edit_field_set_default($mform);
            $this->edit_field_set_required($mform);
            return true;
        }
        return false;
    }

    /**
     * Tweaks the edit form
     * @param moodleform $mform instance of the moodleform class
     * @return bool
     */
    public function edit_after_data($mform) {
        if ($this->field->visible != PROFILE_VISIBLE_NONE
          or has_capability('moodle/user:update', context_system::instance())) {
            $this->edit_field_set_locked($mform);
            return true;
        }
        return false;
    }

    /**
     * Saves the data coming from form
     * @param stdClass $usernew data coming from the form
     * @return mixed returns data id if success of db insert/update, false on fail, 0 if not permitted
     */
    public function edit_save_data($learningpath) {
        global $DB;

        if (!isset($learningpath->{$this->inputname})) {
            // Field not present in form, probably locked and invisible - skip it.
            return;
        }

        $data = new stdClass();

        $learningpath->{$this->inputname} = $this->edit_save_data_preprocess($learningpath->{$this->inputname}, $data);

        $data->lpid  = $learningpath->id;
        $data->fieldid = $this->field->id;
        $data->data    = $learningpath->{$this->inputname};
        $data->dataformat = 0;
   

        if ($dataid = $DB->get_field('lp_info_data', 'id', array('lpid' => $data->lpid, 'fieldid' => $data->fieldid))) {
            $data->id = $dataid;
        
            if($DB->update_record('lp_info_data', $data)){
           
            }
        } else {
          
            $DB->insert_record('lp_info_data', $data);
        }
    }

    /**
     * Validate the form field from profile page
     *
     * @param stdClass $usernew
     * @return  string  contains error message otherwise null
     */
    public function edit_validate_field($learningpath) {
        global $DB;

        $errors = array();
        // Get input value.
        if (isset($learningpath->{$this->inputname})) {
            if (is_array($learningpath->{$this->inputname}) && isset($learningpath->{$this->inputname}['text'])) {
                $value = $learningpath->{$this->inputname}['text'];
            } else {
                $value = $learningpath->{$this->inputname};
            }
        } else {
            $value = '';
        }

        // Check for uniqueness of data if required.
        if ($this->is_unique() && (($value !== '') || $this->is_required())) {
            $data = $DB->get_records_sql('
                    SELECT id, lpid
                      FROM {lp_info_data}
                     WHERE fieldid = ?
                       AND ' . $DB->sql_compare_text('data', 255) . ' = ' . $DB->sql_compare_text('?', 255),
                    array($this->field->id, $value));
            if ($data) {
                $existing = false;
                foreach ($data as $v) {
                    if ($v->userid == $learningpath->id) {
                        $existing = true;
                        break;
                    }
                }
                if (!$existing) {
                    $errors[$this->inputname] = get_string('valuealreadyused');
                }
            }
        }
        return $errors;
    }

    /**
     * Sets the default data for the field in the form object
     * @param  moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_default($mform) {
        if (!empty($default)) {
            $mform->setDefault($this->inputname, $this->field->defaultdata);
        }else
        {
            $mform->setDefault($this->inputname, $this->data); 
        }
    }

    /**
     * Sets the required flag for the field in the form object
     *
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_required($mform) {
        global $USER;
        if ($this->is_required() && ($this->userid == $USER->id)) {
            $mform->addRule($this->inputname, get_string('required'), 'required', null, 'client');
        }
    }

    /**
     * HardFreeze the field if locked.
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_locked($mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() and !has_capability('moodle/user:update', context_system::instance())) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, $this->data);
        }
    }

    /**
     * Hook for child classess to process the data before it gets saved in database
     * @param stdClass $data
     * @param stdClass $datarecord The object that will be used to save the record
     * @return  mixed
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        return $data;
    }

    /**
     * Loads a user object with data for this field ready for the edit profile
     * form
     * @param stdClass $user a user object
     */
    public function edit_load_user_data($user) {
  
        if ($this->data !== null) {
            $user->{$this->inputname} = $this->data;
        }
    }

    /**
     * Check if the field data should be loaded into the user object
     * By default it is, but for field types where the data may be potentially
     * large, the child class should override this and return false
     * @return bool
     */
    public function is_user_object_data() {
        return true;
    }

    /**
     * Accessor method: set the userid for this instance
     * @internal This method should not generally be overwritten by child classes.
     * @param integer $userid id from the user table
     */
    public function set_lpid($lpid) {
        $this->lpid = $lpid;
    }

    /**
     * Accessor method: set the fieldid for this instance
     * @internal This method should not generally be overwritten by child classes.
     * @param integer $fieldid id from the user_info_field table
     */
    public function set_fieldid($fieldid) {
        $this->fieldid = $fieldid;
    }

    /**
     * Accessor method: Load the field record and user data associated with the
     * object's fieldid and userid
     * @internal This method should not generally be overwritten by child classes.
     */
    public function load_data() {
        global $DB;

        // Load the field object.
        if (($this->fieldid == 0) or (!($field = $DB->get_record('lp_info_field', array('id' => $this->fieldid))))) {
            $this->field = null;
            $this->inputname = '';
        } else {
            $this->field = $field;
            $this->inputname = 'lp_field_'.$field->shortname;
        }

  
        if (!empty($this->field)) {
            $params = array('lpid' => $this->lpid, 'fieldid' => $this->fieldid);
            if ($data = $DB->get_record('lp_info_data', $params, 'data, dataformat')) {
                $this->data = $data->data;
                $this->dataformat = $data->dataformat;
            } else {
                $this->data = $this->field->defaultdata;
                $this->dataformat = FORMAT_HTML;
            }
        } else {
            $this->data = null;
        }
       
    }

    /**
     * Check if the field data is visible to the current user
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_visible() {
        global $USER;

        switch ($this->field->visible) {
            case PROFILE_VISIBLE_ALL:
                return true;
            case PROFILE_VISIBLE_PRIVATE:
                if ($this->userid == $USER->id) {
                    return true;
                } else {
                    return has_capability('moodle/user:viewalldetails',
                            context_user::instance($this->userid));
                }
            default:
                return has_capability('moodle/user:viewalldetails',
                        context_user::instance($this->userid));
        }
    }

    /**
     * Check if the field data is considered empty
     * @internal This method should not generally be overwritten by child classes.
     * @return boolean
     */
    public function is_empty() {
        return ( ($this->data != '0') and empty($this->data));
    }

    /**
     * Check if the field is required on the edit profile page
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_required() {
        return (boolean)$this->field->required;
    }

    /**
     * Check if the field is locked on the edit profile page
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_locked() {
        return (boolean)$this->field->locked;
    }

    /**
     * Check if the field data should be unique
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_unique() {
        return (boolean)$this->field->forceunique;
    }

    /**
     * Check if the field should appear on the signup page
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_signup_field() {
        return (boolean)$this->field->signup;
    }
}

function getLPdata(){
    
global $CFG, $OUTPUT, $PAGE, $DB;
$strnofields = get_string('lpnofieldsdefined','local_properties');
$categories = $DB->get_records('lp_info_category', null, 'sortorder ASC');
// Check that we have at least one category defined.
if (empty($categories)) {
        echo html_writer::start_tag('div', array('class' => 'col-sm-12 content-categories'));
        echo $OUTPUT->notification($strnofields);
        echo html_writer::end_tag('div');
}


// Print the header.
//echo $OUTPUT->header();
//echo $OUTPUT->heading(get_string('lpfields', 'local_properties'));

foreach ($categories as $category) {

    $table = new html_table();
    $table->align = array('left', 'right');
    $table->width = '95%';
    $table->attributes['class'] = 'generaltable profilefield mt-table card-box';
    $table->data = array();

    if ($fields = $DB->get_records('lp_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
        foreach ($fields as $field) {
            $table->data[] = array(format_string($field->name), lp_field_field_icons($field));
        }
    }
        $fields_butoon = get_string('addfields','local_properties');
        $editstrlp =  html_writer::start_tag('a', array('class' => 'add', 'title' => $add_btn, 'href' => '#', 'alt' => $add_btn, 'data-toggle' => 'modal', 'data-target' => '#myModalLP', 'data-catid' => $category->id));
            $editstrlp .=  html_writer::tag('i','', array('class'=>'men men-icon-add', 'data-placement' => 'bottom', 'data-toggle' => 'tooltip', 'aria-hidden' => 'true'));
            $editstrlp .=  $fields_butoon;
        $editstrlp .=  html_writer::end_tag('a');

        echo html_writer::start_tag('div', array('class' => 'col-sm-12 content-categories card-box pl-0 pr-0'));
            echo $OUTPUT->heading(format_string($category->name) .' '.lp_field_category_icons($category).' '.$editstrlp);
            echo html_writer::start_tag('div', array('class' => 'content-table'));
                if (count($table->data)) {
                    echo html_writer::table($table);
                } else {
                    echo html_writer::start_tag('div', array('class' => 'nofields'));
                    echo $strnofields;
                    echo html_writer::end_tag('div'); 
                }
            echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

} // End of $categories foreach.

}


/**
 * Create a string containing the editing icons for the user profile categories
 * @param stdClass $category the category object
 * @return string the icon string
 */
function lp_field_category_icons($category) {
    global $CFG, $USER, $DB, $OUTPUT;

    $strdelete   = get_string('delete');
    $strmoveup   = get_string('moveup');
    $strmovedown = get_string('movedown');
    $stredit     = get_string('edit');

    $categorycount = $DB->count_records('lp_info_category');
    $fieldcount    = $DB->count_records('lp_info_field', array('categoryid' => $category->id));

    // Edit.
    $editstr = '<a title="'.$stredit.'" href="index.php?id='.$category->id.'&amp;action=editlpcategory"><i class="wid wid-editicon"></i></a> ';

    // Delete.
    // Can only delete the last category if there are no fields in it.
    $editstr .= '<a title="'.$strdelete.'" href="#" data-id="'.$category->id.'" data-action="deletelpcategory" data-sesskey='.sesskey().'"><i class="wid wid-deleteicon"></i></a>';

    return $editstr;
}

/**
 * Create a string containing the editing icons for the user profile fields
 * @param stdClass $field the field object
 * @return string the icon string
 */
function lp_field_field_icons($field) {
    global $CFG, $USER, $DB, $OUTPUT;

    $strdelete   = get_string('delete');
    $strmoveup   = get_string('moveup');
    $strmovedown = get_string('movedown');
    $stredit     = get_string('edit');

    $fieldcount = $DB->count_records('lp_info_field', array('categoryid' => $field->categoryid));
    $datacount  = $DB->count_records('lp_info_data', array('fieldid' => $field->id));

    $editstr = html_writer::start_tag('div', array('class' => 'actions'));
    // Edit.
    $editstr .= '<a title="'.$stredit.'" href="index.php?id='.$field->id.'&amp;action=editlpfield"><i class="wid wid-editicon"></i>'.$stredit.'</a> ';

    // Delete.
    $datacount = $DB->count_records('lp_info_data', array('fieldid' => $field->id));
    $editstr .= '<a title="'.$strdelete.'" href="#" data-count="'.$datacount.'" data-id="'.$field->id.'" data-action="deletelpfield" data-sesskey='.sesskey().'"><i class="wid wid-deleteicon"></i>'.$strdelete.'</a>';
    $editstr .= html_writer::end_tag('div');

    return $editstr;
}

/**
 * Delete a profile category
 * @param int $id of the category to be deleted
 * @return bool success of operation
 */
function lp_field_delete_category($id) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/local/properties/lib/lib.php');
    
    // Retrieve the category.
    if (!$category = $DB->get_record('lp_info_category', array('id' => $id))) {
        print_error('invalidcategoryid');
    }

    if (!$categories = $DB->get_records('lp_info_category', null, 'sortorder ASC')) {
        print_error('nocate', 'debug');
    }

    unset($categories[$category->id]);

    $fields = $DB->get_records('lp_info_field', array('categoryid' => $category->id));
    if($fields)
    {
        delete_fields_lp($fields);

    }
    
    // Finally we get to delete the category.
    $DB->delete_records('lp_info_category', array('id' => $category->id));
  
    return true;
}

/**
 * Deletes a profile field.
 * @param int $id
 */
function lp_field_delete_field($id) {
    global $DB;

    // Remove any user data associated with this field.
    if (!$DB->delete_records('lp_info_data', array('fieldid' => $id))) {
        print_error('cannotdeletecustomfield');
    }

    // Note: Any availability conditions that depend on this field will remain,
    // but show the field as missing until manually corrected to something else.

    // Need to rebuild cohort cache to update the info.
    //rebuild_cohort_cache();

    // Try to remove the record from the database.
    $DB->delete_records('lp_info_field', array('id' => $id));

   
}

/**
 * Retrieve a list of all the available data types
 * @return   array   a list of the datatypes suitable to use in a select statement
 */
function lp_field_list_datatypes() {
   $datatypes = array();

    $plugins = core_component::get_plugin_list('profilefield');
    foreach ($plugins as $type => $unused) {
        $datatypes[$type] = get_string('pluginname', 'profilefield_'.$type);
    }
    unset($datatypes['certificate']);
    asort($datatypes);

    return $datatypes;
}

/**
 * Retrieve a list of categories and ids suitable for use in a form
 * @return   array
 */
function lp_field_list_categories() {
    global $DB;
    if (!$categories = $DB->get_records_menu('lp_info_category', null, 'sortorder ASC', 'id, name')) {
        $categories = array();
    }
    return $categories;
}


/**
 * Edit a category
 *
 * @param int $id
 * @param string $redirect
 */
function lp_field_edit_category($id, $redirect) {
    global $DB, $OUTPUT, $CFG;

    require_once($CFG->dirroot.'/local/properties/index_lp_category_form.php');
    $categoryform = new category_form();

    if ($category = $DB->get_record('lp_info_category', array('id' => $id))) {
        $categoryform->set_data($category);
    }

    if ($categoryform->is_cancelled()) {
        redirect($redirect);
    } else {
        if ($data = $categoryform->get_data()) {
            if (empty($data->id)) {
                unset($data->id);
                $data->sortorder = $DB->count_records('lp_info_category') + 1;
                $DB->insert_record('lp_info_category', $data, false);
            } else {
                $DB->update_record('lp_info_category', $data);
            }
          
            redirect($redirect);

        }

        if (empty($id)) {
            $strheading = get_string('lpdefaultcategory', 'local_properties');
        } else {
            $strheading = get_string('lpeditcategory', 'local_properties', format_string($category->name));
        }

        // Print the page.
        echo $OUTPUT->header();
        echo html_writer::start_tag('div', array('class' => 'pad-all creating-category'));
        echo $OUTPUT->heading($strheading);
        $categoryform->display();
        echo html_writer::end_tag('div');
        echo $OUTPUT->footer();
        die;
    }

}

/**
 * Edit a profile field.
 *
 * @param int $id
 * @param string $datatype
 * @param string $redirect
 */
function lp_field_edit_field($id, $datatype, $redirect, $catid) {
    global $CFG, $DB, $OUTPUT, $PAGE;

    if (!$field = $DB->get_record('lp_info_field', array('id' => $id))) {
        $field = new stdClass();
        $field->datatype = $datatype;
        $field->description = '';
        $field->descriptionformat = FORMAT_HTML;
        $field->defaultdata = '';
        $field->defaultdataformat = FORMAT_HTML;
    }

    // Clean and prepare description for the editor.
    $field->description = clean_text($field->description, $field->descriptionformat);
    $field->description = array('text' => $field->description, 'format' => $field->descriptionformat, 'itemid' => 0);
    if($catid)
    {
        $field->categoryid = $catid;
    }
    require_once($CFG->dirroot.'/local/properties/index_lp_field_form.php');
 
    $fieldform = new field_form(null, $field->datatype);

    // Convert the data format for.
    if (is_array($fieldform->editors())) {
        foreach ($fieldform->editors() as $editor) {
            if (isset($field->$editor)) {
                $field->$editor = clean_text($field->$editor, $field->{$editor.'format'});
                $field->$editor = array('text' => $field->$editor, 'format' => $field->{$editor.'format'}, 'itemid' => 0);
            }
        }
    }

    $fieldform->set_data($field);

    if ($fieldform->is_cancelled()) {
        redirect($redirect);

    } else {
        if ($data = $fieldform->get_data()) {
            require_once($CFG->dirroot.'/user/profile/field/'.$datatype.'/define.class.php');
            $newfield = 'profile_define_'.$datatype;
            $formfield = new $newfield();

            // Collect the description and format back into the proper data structure from the editor.
            // Note: This field will ALWAYS be an editor.
            $data->descriptionformat = $data->description['format'];
            $data->description = $data->description['text'];

            // Check whether the default data is an editor, this is (currently) only the textarea field type.
            if (is_array($data->defaultdata) && array_key_exists('text', $data->defaultdata)) {
                // Collect the default data and format back into the proper data structure from the editor.
                $data->defaultdataformat = $data->defaultdata['format'];
                $data->defaultdata = $data->defaultdata['text'];
            }

            // Convert the data format for.
            if (is_array($fieldform->editors())) {
                foreach ($fieldform->editors() as $editor) {
                    if (isset($field->$editor)) {
                        $field->{$editor.'format'} = $field->{$editor}['format'];
                        $field->$editor = $field->{$editor}['text'];
                    }
                }
            }

            $type='editlpfield';
            $formfield->define_save($data,$type);
            redirect($redirect);
        }

        $datatypes = lp_field_list_datatypes();

        if (empty($id)) {
            $strheading = get_string('lpcreatenewfield', 'local_properties', $datatypes[$datatype]);
        } else {
            $strheading = get_string('lpeditfield', 'local_properties', $field->name);
        }

        // Print the page.
        $PAGE->navbar->add($strheading);
        echo $OUTPUT->header();
        echo html_writer::start_tag('div', array('class' => 'pad-all creating-category'));
        echo $OUTPUT->heading($strheading);
        $fieldform->display();
        echo html_writer::end_tag('div');
        echo $OUTPUT->footer();
        die;
    }
}

function lp_profile_load_data($learningpath) {
    
    global $CFG, $DB;

if ($fields = $DB->get_records('lp_info_field')) {
    
    foreach ($fields as $field) {
            require_once($CFG->dirroot.'/local/properties/lpfield/'.$field->datatype.'/field.class.php');
            $newfield = 'lp_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $learningpath->id);
         
            $formfield->edit_load_user_data($learningpath);
        }
    }
}

/**
 * Print out the customisable categories and fields for a users profile
 *
 * @param moodleform $mform instance of the moodleform class
 * @param int $userid id of user whose profile is being edited.
 */
function lp_profile_definition($mform, $lpid = 0) {
    global $CFG, $DB;

    // If user is "admin" fields are displayed regardless.
    $update = has_capability('moodle/user:update', context_system::instance());

    if ($categories = $DB->get_records('lp_info_category', null, 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('lp_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {

                // Check first if *any* fields will be displayed.
                $display = false;
                foreach ($fields as $field) {
                    if ($field->visible != PROFILE_VISIBLE_NONE) {
                        $display = true;
                    }
                }

                // Display the header and the fields.
                if ($display or $update) {
               
                    $mform->addElement('header', 'category_'.$category->id, format_string($category->name));
                    foreach ($fields as $field) {
                        require_once($CFG->dirroot.'/local/properties/lpfield/'.$field->datatype.'/field.class.php');
                        $newfield = 'lp_field_'.$field->datatype;
                        $formfield = new $newfield($field->id, $lpid);
                        $formfield->edit_field($mform);
                    }
                }
            }
        }
    }
}