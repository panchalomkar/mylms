<?php

define ('COURSE_FIELD_VISIBLE_ALL',     2);
define ('COURSE_FIELD_VISIBLE_PRIVATE', 1);
define ('COURSE_FIELD_VISIBLE_NONE',    0);

class course_field_base {

    // These 2 variables are really what we're interested in.
    // Everything else can be extracted from them.

    /** @var int */
    public $fieldid;

    /** @var int */
    public $courseid;

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
     * @param int $fieldid id of the profile from the course_info_field table
     * @param int $courseid id of the user for whom we are displaying data
     */
    public function course_field_base($fieldid=0, $courseid=0) {
        global $COURSE;

        $this->set_fieldid($fieldid);
        $this->set_courseid($courseid);
        $this->load_data();
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
    public function edit_field($mform,$formfield,$coursefieldbase) {
        
       if ($this->field->visible != COURSE_FIELD_VISIBLE_NONE or has_capability('moodle/course:view', context_system::instance())) 
        {
            $formfield->edit_field_add($mform);
            $coursefieldbase->edit_field_set_default($mform,$coursefieldbase);
            $formfield->edit_field_set_required($mform);
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
        if ($this->field->visible != COURSE_FIELD_VISIBLE_NONE
          or has_capability('moodle/course:view', context_system::instance())) {
            $this->edit_field_set_locked($mform);
            return true;
        }
        return false;
    }

    /**
     * Saves the data coming from form
     * @param stdClass $coursenew data coming from the form
     * @return mixed returns data id if success of db insert/update, false on fail, 0 if not permitted
     */
    public function edit_save_data($coursenew) {
        global $DB,$COURSE;
        
        if (!isset($coursenew->{$this->inputname})) {
            // Field not present in form, probably locked and invisible - skip it.
            return;
        }

        $data = new stdClass();

        $coursenew->{$this->inputname} = $this->edit_save_data_preprocess($coursenew->{$this->inputname}, $data);

        $data->courseid  = $coursenew->id;
        $data->fieldid = $this->field->id;
        $data->data    = $coursenew->{$this->inputname};
        $data->dataformat = 0;
        
        if ($dataid = $DB->get_field('course_info_data', 'id', array('courseid' => $data->courseid, 'fieldid' => $data->fieldid))) {
            $data->id = $dataid;
            $DB->update_record('course_info_data', $data);
        } else {
            $DB->insert_record('course_info_data', $data);
        }
    }

    /**
     * Validate the form field from profile page
     *
     * @param stdClass $coursenew
     * @return  string  contains error message otherwise null
     */
    public function edit_validate_field($coursenew) {
        global $DB;

        $errors = array();
        // Get input value.
        if (isset($coursenew->{$this->inputname})) {
            if (is_array($coursenew->{$this->inputname}) && isset($coursenew->{$this->inputname}['text'])) {
                $value = $coursenew->{$this->inputname}['text'];
            } else {
                $value = $coursenew->{$this->inputname};
            }
        } else {
            $value = '';
        }

        // Check for uniqueness of data if required.
        if ($this->is_unique() && (($value !== '') || $this->is_required())) {
            $data = $DB->get_records_sql('
                    SELECT id, courseid
                      FROM {course_info_data}
                     WHERE fieldid = ?
                       AND ' . $DB->sql_compare_text('data', 255) . ' = ' . $DB->sql_compare_text('?', 255),
                    array($this->field->id, $value));
            if ($data) {
                $existing = false;
                foreach ($data as $v) {
                    if ($v->courseid == $coursenew->id) {
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
    public function edit_field_set_default($mform,$coursefieldbase) {
        global $COURSE,$DB ;
        if($this->field->defaultdata)
        {
            if (!empty($default)) {
                $mform->setDefault($this->inputname, $this->field->defaultdata);
            }
        }
        if(isset($coursefieldbase->field) && $coursefieldbase->field->datatype == 'textarea')
        {   
            $data = $DB->get_record('course_info_data',array('fieldid'=>$coursefieldbase->field->id,'courseid'=>$COURSE->id));
            $la_data = array('text'=>$data->data);
                $mform->setDefault($coursefieldbase->inputname,  $la_data);

        }
     
    }

    /**
     * Sets the required flag for the field in the form object
     *
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_required($mform) {
        global $COURSE;
        if ($this->is_required() && ($this->courseid == $COURSE->id)) {
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
        if ($this->is_locked() and !has_capability('moodle/course:view', context_system::instance())) {
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
     * Loads a course object with data for this field ready for the edit profile
     * form
     * @param stdClass $course a course object
     */
    public function edit_load_user_data($course) {

     if ($this->data !== null) {
            $course->{$this->inputname} = $this->data;
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
     * Accessor method: set the courseid for this instance
     * @internal This method should not generally be overwritten by child classes.
     * @param integer $courseid id from the user table
     */
    public function set_courseid($courseid) {
        $this->courseid = $courseid;
    }

    /**
     * Accessor method: set the fieldid for this instance
     * @internal This method should not generally be overwritten by child classes.
     * @param integer $fieldid id from the course_info_field table
     */
    public function set_fieldid($fieldid) {
        $this->fieldid = $fieldid;
    }

    /**
     * Accessor method: Load the field record and user data associated with the
     * object's fieldid and courseid
     * @internal This method should not generally be overwritten by child classes.
     */
    public function load_data() {
        global $DB;

        // Load the field object.
        if (($this->fieldid == 0) or (!($field = $DB->get_record('course_info_field', array('id' => $this->fieldid))))) {
            $this->field = null;
            $this->inputname = '';
        } else {
            $this->field = $field;
            $this->inputname = 'course_field_'.$field->shortname;
        }

        if (!empty($this->field)) {
            $params = array('courseid' => $this->courseid, 'fieldid' => $this->fieldid);
            if ($data = $DB->get_record('course_info_data', $params, 'data, dataformat')) {
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
        global $COURSE;

        switch ($this->field->visible) {
            case COURSE_FIELD_VISIBLE_ALL:
                return true;
            case COURSE_FIELD_VISIBLE_PRIVATE:
                if ($this->courseid == $COURSE->id) {
                    return true;
                } else {
                    return has_capability('moodle/course:view',
                            context_user::instance($this->courseid));
                }
            default:
                return has_capability('moodle/course:view',
                        context_user::instance($this->courseid));
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

/**
 * Loads user profile field data into the user object.
 * @param stdClass $user
 */
function course_field_load_data($course) {
    global $CFG, $DB ;
    if ($fields = $DB->get_records_sql('SELECT * FROM {course_info_field} ORDER BY categoryid ASC')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/local/properties/coursefield/'.$field->datatype.'/field.class.php');
            $newfield = 'course_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $course->id);
            $formfield->edit_load_user_data($course);
        }
    }
}

/**
 * Print out the customisable categories and fields for a users profile
 *
 * @param moodleform $mform instance of the moodleform class
 * @param int $courseid id of user whose profile is being edited.
 */
function course_field_save_data($coursenew) {
    global $CFG, $DB;

    if ($fields = $DB->get_records('course_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/local/properties/coursefield/'.$field->datatype.'/field.class.php');
            $newfield = 'course_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $coursenew->id);
            $formfield->edit_save_data($coursenew);
        }
    }
}

function course_field_definition($mform, $courseid = 0) {
    global $CFG, $DB;

    // If user is "admin" fields are displayed regardless.
    $update = has_capability('moodle/course:update', context_system::instance());
    //return true ;
    if ($categories = $DB->get_records('course_info_category', null, 'sortorder ASC')) {
        require_once($CFG->dirroot.'/user/profile/lib.php');
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('course_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {

                // Check first if *any* fields will be displayed.
                $display = false;
                foreach ($fields as $field) {
                    if ($field->visible != COURSE_FIELD_VISIBLE_NONE) {
                        $display = true;
                    }
                }

                // Display the header and the fields.
                if ($display or $update) {
                    $mform->addElement('header', 'category_'.$category->id, format_string($category->name));
                        
                    foreach ($fields as $field) {
                        require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                        $newfield = 'profile_field_'.$field->datatype;
                        $formfield = new $newfield($field->id, $courseid, $field ,'course_field_'.$field->shortname);
                        $formfield->field = $field;
                        $formfield->inputname = 'course_field_'.$field->shortname;  

                        $coursefieldbase = new course_field_base($field->id, $courseid, $field ,'course_field_'.$field->shortname);
            
                        $coursefieldbase->field = $field;
                        $coursefieldbase->inputname = 'course_field_'.$field->shortname;  

                        if($field->datatype=='multiselectlist' || $field->datatype=='menu' ) $formfield->options = explode("\n", $field->param1);
             
                        $coursefieldbase->edit_field($mform,$formfield,$coursefieldbase);
                    }
                }
            }
        }
    }
}

function getCourseMetadata(){
// Show all categories.
global $CFG, $OUTPUT, $PAGE, $DB;
$action   = optional_param('action', '', PARAM_ALPHA);
$redirect = $CFG->wwwroot.'/local/properties/index.php';
$strnofields = get_string('lpnofieldsdefined','local_properties');    
$categories = $DB->get_records('course_info_category', null, 'sortorder ASC');

// Check that we have at least one category defined.
if (empty($categories)) {
    echo html_writer::start_tag('div', array('class' => 'col-sm-12 content-categories'));
    echo $OUTPUT->notification($strnofields);
    echo html_writer::end_tag('div');
}

foreach ($categories as $category) {
    $table1 = new html_table();
    //$table1->head  = array(get_string('coursefield', 'local_properties'), get_string('edit'));
    $table1->align = array('left', 'right');
    $table1->width = '95%';
    $table1->attributes['class'] = 'generaltable profilefield mt-table card-box';
    $table1->data = array();

    if ($fields = $DB->get_records('course_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
        foreach ($fields as $field) {
            $table1->data[] = array(format_string($field->name), course_field_field_icons($field));
        }
    }
        
    $fields_butoon = get_string('addfields','local_properties'); 
    $editstrcourse =  html_writer::start_tag('a', array('class' => 'add', 'title' => $add_btn, 'href' => '#?id='.$category->id, 'data-catid' => $category->id, 'alt' => $add_btn, 'data-toggle' => 'modal', 'data-target' => '#myModalcourse', 'data-catid' => $category->id));
        $editstrcourse .=  html_writer::tag('i','', array('class'=>'men men-icon-add add-tooltip','aria-hidden' => 'true'));
        $editstrcourse .= $fields_butoon;
    $editstrcourse .=  html_writer::end_tag('a');

        echo html_writer::start_tag('div', array('class' => 'content-categories card-box'));
            echo $OUTPUT->heading(format_string($category->name) .' '.course_field_category_icons($category).' '.$editstrcourse);
            echo html_writer::start_tag('div', array('class' => 'content-table'));
                if (count($table1->data)) {
                    echo html_writer::table($table1);
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
function course_field_category_icons($category) {
    global $CFG, $USER, $DB, $OUTPUT;

    $strdelete   = get_string('delete');
    $strmoveup   = get_string('moveup');
    $strmovedown = get_string('movedown');
    $stredit     = get_string('edit');

    $categorycount = $DB->count_records('course_info_category');
    $fieldcount    = $DB->count_records('course_info_field', array('categoryid' => $category->id));

    // Edit.
    $editstr = '<a title="'.$stredit.'" href="index.php?id='.$category->id.'&amp;action=editcoursecategory"><i class="wid wid-editicon"></i></a> ';

    // Delete.
    // Can only delete the last category if there are no fields in it.
    $editstr .= '<a title="'.$strdelete.'" href="#" data-id="'.$category->id.'" data-action="deletecoursecategory" data-sesskey='.sesskey().'"><i class="wid wid-deleteicon"></i></a>';

    return $editstr;
}

/**
 * Create a string containing the editing icons for the user profile fields
 * @param stdClass $field the field object
 * @return string the icon string
 */
function course_field_field_icons($field) {
    global $CFG, $USER, $DB, $OUTPUT;

    $strdelete   = get_string('delete');
    $strmoveup   = get_string('moveup');
    $strmovedown = get_string('movedown');
    $stredit     = get_string('edit');

    $fieldcount = $DB->count_records('course_info_field', array('categoryid' => $field->categoryid));
    $datacount  = $DB->count_records('user_info_data', array('fieldid' => $field->id));

    $editstr = html_writer::start_tag('div', array('class' => 'actions'));
        // Edit.
        $editstr .= '<a href="index.php?id='.$field->id.'&amp;action=editcoursefield"><i class="wid wid-editicon"></i>'.$stredit.'</a> ';

        // Delete.
        $datacount = $DB->count_records('course_info_data', array('fieldid' => $field->id));
        $editstr .= '<a title="'.$strdelete.'" href="#" data-count="'.$datacount.'" data-id="'.$field->id.'" data-action="deletecoursefield" data-sesskey='.sesskey().'"><i class="wid wid-deleteicon"></i>'.$strdelete.'</a>';
    $editstr .= html_writer::end_tag('div');

    return $editstr;
}

/**
 * Reorder the profile fields within a given category starting at the field at the given startorder.
 */
function course_field_reorder_fields() {
    global $DB;

    if ($categories = $DB->get_records('course_info_category')) {
        foreach ($categories as $category) {
            $i = 1;
            if ($fields = $DB->get_records('course_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
                foreach ($fields as $field) {
                    $f = new stdClass();
                    $f->id = $field->id;
                    $f->sortorder = $i++;
                    $DB->update_record('course_info_field', $f);
                }
            }
        }
    }
}

/**
 * Reorder the profile categoriess starting at the category at the given startorder.
 */
function course_field_reorder_categories() {
    global $DB;

    $i = 1;
    if ($categories = $DB->get_records('course_info_category', null, 'sortorder ASC')) {
        foreach ($categories as $cat) {
            $c = new stdClass();
            $c->id = $cat->id;
            $c->sortorder = $i++;
            $DB->update_record('course_info_category', $c);
        }
    }
}

/**
 * Delete a profile category
 * @param int $id of the category to be deleted
 * @return bool success of operation
 */
function course_field_delete_category($id) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/local/properties/lib/lib.php');
    
    // Retrieve the category.
    if (!$category = $DB->get_record('course_info_category', array('id' => $id))) {
        print_error('invalidcategoryid');
    }

    if (!$categories = $DB->get_records('course_info_category', null, 'sortorder ASC')) {
        print_error('nocate', 'debug');
    }

    unset($categories[$category->id]);

    $fields = $DB->get_records('course_info_field', array('categoryid' => $category->id));
    if($fields)
    {
        delete_fields_course($fields);

    }

    // Finally we get to delete the category.
    $DB->delete_records('course_info_category', array('id' => $category->id));
    course_field_reorder_categories();
    return true;
}

/**
 * Deletes a profile field.
 * @param int $id
 */
function course_field_delete_field($id) {
    global $DB;

    // Remove any user data associated with this field.
    if (!$DB->delete_records('course_info_data', array('fieldid' => $id))) {
        print_error('cannotdeletecustomfield');
    }

    // Note: Any availability conditions that depend on this field will remain,
    // but show the field as missing until manually corrected to something else.

    // Need to rebuild course cache to update the info.
    rebuild_course_cache();

    // Try to remove the record from the database.
    $DB->delete_records('course_info_field', array('id' => $id));

    // Reorder the remaining fields in the same category.
    course_field_reorder_fields();
}

/**
 * Change the sort order of a field
 *
 * @param int $id of the field
 * @param string $move direction of move
 * @return bool success of operation
 */
function course_field_move_field($id, $move) {
    global $DB;

    // Get the field object.
    if (!$field = $DB->get_record('course_info_field', array('id' => $id), 'id, sortorder, categoryid')) {
        return false;
    }
    // Count the number of fields in this category.
    $fieldcount = $DB->count_records('course_info_field', array('categoryid' => $field->categoryid));

    // Calculate the new sortorder.
    if ( ($move == 'up') and ($field->sortorder > 1)) {
        $neworder = $field->sortorder - 1;
    } else if (($move == 'down') and ($field->sortorder < $fieldcount)) {
        $neworder = $field->sortorder + 1;
    } else {
        return false;
    }

    // Retrieve the field object that is currently residing in the new position.
    $params = array('categoryid' => $field->categoryid, 'sortorder' => $neworder);
    if ($swapfield = $DB->get_record('course_info_field', $params, 'id, sortorder')) {

        // Swap the sortorders.
        $swapfield->sortorder = $field->sortorder;
        $field->sortorder     = $neworder;

        // Update the field records.
        $DB->update_record('course_info_field', $field);
        $DB->update_record('course_info_field', $swapfield);
    }

    //course_field_reorder_fields();
    return true;
}

/**
 * Change the sort order of a category.
 *
 * @param int $id of the category
 * @param string $move direction of move
 * @return bool success of operation
 */
function course_field_move_category($id, $move) {
    global $DB;
    // Get the category object.
    if (!($category = $DB->get_record('course_info_category', array('id' => $id), 'id, sortorder'))) {
        return false;
    }

    // Count the number of categories.
    $categorycount = $DB->count_records('course_info_category');

    // Calculate the new sortorder.
    if (($move == 'up') and ($category->sortorder > 1)) {
        $neworder = $category->sortorder - 1;
    } else if (($move == 'down') and ($category->sortorder < $categorycount)) {
        $neworder = $category->sortorder + 1;
    } else {
        return false;
    }

    // Retrieve the category object that is currently residing in the new position.
    if ($swapcategory = $DB->get_record('course_info_category', array('sortorder' => $neworder), 'id, sortorder')) {

        // Swap the sortorders.
        $swapcategory->sortorder = $category->sortorder;
        $category->sortorder     = $neworder;

        // Update the category records.
        $DB->update_record('course_info_category', $category) and $DB->update_record('course_info_category', $swapcategory);
        return true;
    }

    return false;
}

/**
 * Retrieve a list of all the available data types
 * @return   array   a list of the datatypes suitable to use in a select statement
 */
function course_field_list_datatypes() {
    global $DB ;
    $datatypes = array();

    $plugins = core_component::get_plugin_list('profilefield');
    foreach ($plugins as $type => $unused) {
        $datatypes[$type] = get_string('pluginname', 'profilefield_'.$type);
    }
    $certificateexist = $DB->get_record('course_info_field',array('shortname'=>'certificate'));
    if($certificateexist)
    {
        unset($datatypes['certificate']);
    }
    asort($datatypes);

    return $datatypes;
}


/**
 * Edit a category
 *
 * @param int $id
 * @param string $redirect
 */
function course_field_edit_category($id, $redirect) {
    global $DB, $OUTPUT, $CFG;

    require_once($CFG->dirroot.'/local/properties/index_course_category_form.php');
    $categoryform = new category_form();

    if ($category = $DB->get_record('course_info_category', array('id' => $id))) {
        $categoryform->set_data($category);
    }

    if ($categoryform->is_cancelled()) {
        redirect($redirect);
    } else {
        if ($data = $categoryform->get_data()) {
            if (empty($data->id)) {
                unset($data->id);
                $data->sortorder = $DB->count_records('course_info_category') + 1;
                $DB->insert_record('course_info_category', $data, false);
            } else {
                $DB->update_record('course_info_category', $data);
            }
            //course_field_reorder_categories();
            redirect($redirect);

        }

        if (empty($id)) {
            $strheading = get_string('coursecreatenewcategory', 'local_properties');
        } else {
            $strheading = get_string('courseeditcategory', 'local_properties', format_string($category->name));
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
function course_field_edit_field($id, $datatype, $redirect,$type, $catid) {
    global $CFG, $DB, $OUTPUT, $PAGE;

    if (!$field = $DB->get_record('course_info_field', array('id' => $id))) {
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
    require_once($CFG->dirroot.'/local/properties/index_course_field_form.php');

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
            $type='editcoursefield';
            $formfield->define_save($data,$type);
            redirect($redirect);
        }

        $datatypes = course_field_list_datatypes();

        if (empty($id)) {
            $strheading = get_string('coursecreatenewfield', 'local_properties', $datatypes[$datatype]);
        } else {
            $strheading = get_string('courseeditfield', 'local_properties', $field->name);
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

/**
 * Retrieve a list of categories and ids suitable for use in a form
 * @return   array
 */
function course_field_list_categories() {
    global $DB;
    if (!$categories = $DB->get_records_menu('course_info_category', null, 'sortorder ASC', 'id, name')) {
        $categories = array();
    }
    return $categories;
}