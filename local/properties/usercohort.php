<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Profile field API library file.
 *
 * @package core_user
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define ('PROFILE_VISIBLE_ALL',     '2'); // Only visible for users with moodle/user:update capability.
define ('PROFILE_VISIBLE_PRIVATE', '1'); // Either we are viewing our own profile or we have moodle/user:update capability.
define ('PROFILE_VISIBLE_NONE',    '0'); // Only visible for moodle/user:update capability.

/**
 * Base class for the customisable profile fields.
 *
 * @package core_user
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort_profile_field_base {

    // These 2 variables are really what we're interested in.
    // Everything else can be extracted from them.

    /** @var int */
    public $fieldid;

    /** @var int */
    public $cohortid;

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
    public function cohort_profile_field_base($fieldid=0, $cohortid=0) {
        global $USER;

        $this->set_fieldid($fieldid);
        $this->set_userid($cohortid);
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
            //$this->edit_field_set_default($mform);
            //$this->edit_field_set_required($mform);
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
    public function edit_save_data($usernew) {
        global $DB;

        if (!isset($usernew->{$this->inputname})) {
            // Field not present in form, probably locked and invisible - skip it.
            return;
        }

        $data = new stdClass();

        $usernew->{$this->inputname} = $this->edit_save_data_preprocess($usernew->{$this->inputname}, $data);

        $data->cohortid  = $usernew->id;
        $data->fieldid = $this->field->id;
        $data->data    = $usernew->{$this->inputname};
        $data->dataformat = 0;
   

        if ($dataid = $DB->get_field('cohort_info_data', 'id', array('cohortid' => $data->cohortid, 'fieldid' => $data->fieldid))) {
            $data->id = $dataid;
        
            if($DB->update_record('cohort_info_data', $data)){
           
            }else{
           
                mysql_erro();
            }
        } else {
          
            $DB->insert_record('cohort_info_data', $data);
        }
    }

    /**
     * Validate the form field from profile page
     *
     * @param stdClass $usernew
     * @return  string  contains error message otherwise null
     */
    public function edit_validate_field($usernew) {
        global $DB;

        $errors = array();
        // Get input value.
        if (isset($usernew->{$this->inputname})) {
            if (is_array($usernew->{$this->inputname}) && isset($usernew->{$this->inputname}['text'])) {
                $value = $usernew->{$this->inputname}['text'];
            } else {
                $value = $usernew->{$this->inputname};
            }
        } else {
            $value = '';
        }

        // Check for uniqueness of data if required.
        if ($this->is_unique() && (($value !== '') || $this->is_required())) {
            $data = $DB->get_records_sql('
                    SELECT id, cohortid
                      FROM {cohort_info_data}
                     WHERE fieldid = ?
                       AND ' . $DB->sql_compare_text('data', 255) . ' = ' . $DB->sql_compare_text('?', 255),
                    array($this->field->id, $value));
            if ($data) {
                $existing = false;
                foreach ($data as $v) {
                    if ($v->userid == $usernew->id) {
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
    public function set_userid($cohortid) {
        $this->userid = $cohortid;
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
        if (($this->fieldid == 0) or (!($field = $DB->get_record('cohort_info_field', array('id' => $this->fieldid))))) {
            $this->field = null;
            $this->inputname = '';
        } else {
            $this->field = $field;
            $this->inputname = 'profile_field_'.$field->shortname;
        }

        if (!empty($this->field)) {
            $params = array('cohortid' => $this->userid, 'fieldid' => $this->fieldid);
            if ($data = $DB->get_record('cohort_info_data', $params, 'data, dataformat')) {
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

/**
 * Loads user profile field data into the user object.
 * @param stdClass $user
 */
function cohort_profile_load_data($cohort) {
    global $CFG, $DB;

    if ($fields = $DB->get_records('cohort_info_field')) {
     
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/local/properties/cohortfield/'.$field->datatype.'/field.class.php');
            $newfield = 'cohort_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $cohort->id);
            $formfield->edit_load_user_data($cohort);
        }
    }
}

/**
 * Print out the customisable categories and fields for a users profile
 *
 * @param moodleform $mform instance of the moodleform class
 * @param int $userid id of user whose profile is being edited.
 */
function cohort_profile_definition($mform, $cohortid = 0) {
    global $CFG, $DB;

    // If user is "admin" fields are displayed regardless.
    $update = has_capability('moodle/user:update', context_system::instance());

    if ($categories = $DB->get_records('cohort_info_category', null, 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('cohort_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {

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
                        require_once($CFG->dirroot.'/local/properties/cohortfield/'.$field->datatype.'/field.class.php');
                        $newfield = 'cohort_field_'.$field->datatype;
                        $formfield = new $newfield($field->id, $cohortid);
                  
                        $formfield->edit_field($mform);
                    }
                }
            }
        }
    }
}

/**
 * Adds profile fields to user edit forms.
 * @param moodleform $mform
 * @param int $userid
 */
function cohort_profile_definition_after_data($mform, $cohortid) {
    global $CFG, $DB;

    $cohortid = ($cohortid < 0) ? 0 : (int)$cohortid;

    if ($fields = $DB->get_records('cohort_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/local/properties/cohortfield/'.$field->datatype.'/field.class.php');
            $newfield = 'cohort_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $cohortid);
            $formfield->edit_after_data($mform);
        }
    }
}

/**
 * Validates profile data.
 * @param stdClass $usernew
 * @param array $files
 * @return array
 */
function cohort_profile_validation($usernew, $files) {
    global $CFG, $DB;

    $err = array();
    if ($fields = $DB->get_records('cohort_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/local/properties/cohortfield/'.$field->datatype.'/field.class.php');
            $newfield = 'cohort_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $usernew->id);
            $err += $formfield->edit_validate_field($usernew, $files);
        }
    }
    return $err;
}

/**
 * Saves profile data for a user.
 * @param stdClass $usernew
 */
function cohort_profile_save_data($usernew) {
    global $CFG, $DB;

    if ($fields = $DB->get_records('cohort_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/local/properties/cohortfield/'.$field->datatype.'/field.class.php');
            $newfield = 'cohort_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $usernew->id);
            $formfield->edit_save_data($usernew);
        }
    }
}

/**
 * Display profile fields.
 * @param int $cohortid
 */
function cohort_profile_display_fields($cohortid) {
    global $CFG, $USER, $DB;

    if ($categories = $DB->get_records('cohort_info_category', null, 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('cohort_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
               
                foreach ($fields as $field) {
                    require_once($CFG->dirroot.'/local/properties/cohortfield/'.$field->datatype.'/field.class.php');
                    $newfield = 'cohort_field_'.$field->datatype;
                    $formfield = new $newfield($field->id, $cohortid);
                    if ($formfield->is_visible() and !$formfield->is_empty()) {
                        echo html_writer::tag('dt', format_string($formfield->field->name));
                        echo html_writer::tag('dd', $formfield->display_data());
                    }
                }
            }
        }
    }
}

/**
 * Adds code snippet to a moodle form object for custom profile fields that
 * should appear on the signup page
 * @param moodleform $mform moodle form object
 */
function cohort_profile_signup_fields($mform) {
    global $CFG, $DB;

    // Only retrieve required custom fields (with category information)
    // results are sort by categories, then by fields.
    $sql = "SELECT uf.id as fieldid, ic.id as categoryid, ic.name as categoryname, uf.datatype
                FROM {cohort_info_field} uf
                JOIN {cohort_info_category} ic
                ON uf.categoryid = ic.id AND uf.signup = 1 AND uf.visible<>0
                ORDER BY ic.sortorder ASC, uf.sortorder ASC";

    if ( $fields = $DB->get_records_sql($sql)) {
        foreach ($fields as $field) {
            // Check if we change the categories.
            if (!isset($currentcat) || $currentcat != $field->categoryid) {
                 $currentcat = $field->categoryid;
                 $mform->addElement('header', 'category_'.$field->categoryid, format_string($field->categoryname));
            }
            require_once($CFG->dirroot.'/local/properties/cohortfield/'.$field->datatype.'/field.class.php');
            $newfield = 'cohort_field_'.$field->datatype;
            $formfield = new $newfield($field->fieldid);
            $formfield->edit_field($mform);
        }
    }
}

function cohort_profile_user_record($cohortid) {
    
    global $CFG, $DB;
     $usercustomfields = new stdClass();
     if ($fields = $DB->get_records('cohort_info_field')) {
         foreach ($fields as $field) {
             require_once($CFG->dirroot.'/local/properties/cohortfield/'.$field->datatype.'/field.class.php');
             $newfield = 'cohort_field_'.$field->datatype;
             $formfield = new $newfield($field->id, $cohortid);
             
             if ($formfield->is_user_object_data()) {
                $usercustomfields->{$field->shortname} = $formfield->description;
             }
         }
     }

     return $usercustomfields;
}

/**
 * Obtains a list of all available custom profile fields, indexed by id.
 *
 * Some profile fields are not included in the user object data (see
 * profile_user_record function above). Optionally, you can obtain only those
 * fields that are included in the user object.
 *
 * To be clear, this function returns the available fields, and does not
 * return the field values for a particular user.
 *
 * @param bool $onlyinuserobject True if you only want the ones in $USER
 * @return array Array of field objects from database (indexed by id)
 * @since Moodle 2.7.1
 */
function cohort_profile_get_custom_fields($onlyinuserobject = false) {
    global $DB, $CFG;

    // Get all the fields.
    $fields = $DB->get_records('cohort_info_field', null, 'id ASC');

    // If only doing the user object ones, unset the rest.
    if ($onlyinuserobject) {
        foreach ($fields as $id => $field) {
            require_once($CFG->dirroot . '/local/properties/cohortfield/' .
                    $field->datatype . '/field.class.php');
            $newfield = 'cohort_field_' . $field->datatype;
            $formfield = new $newfield();
            if (!$formfield->is_user_object_data()) {
                unset($fields[$id]);
            }
        }
    }

    return $fields;
}

/**
 * Load custom profile fields into user object
 *
 * Please note originally in 1.9 we were using the custom field names directly,
 * but it was causing unexpected collisions when adding new fields to user table,
 * so instead we now use 'profile_' prefix.
 *
 * @param stdClass $user user object
 */
function cohort_profile_load_custom_fields($cohort) {
    $user->profile = (array)cohort_profile_user_record($cohort->cohortid);
}
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function getCohorts(){
   
global $CFG, $OUTPUT, $PAGE, $DB;
$action   = optional_param('action', '', PARAM_ALPHA);

$redirect = $CFG->wwwroot.'/local/properties/index.php';
$strchangessaved    = get_string('changessaved');
$strcancelled       = get_string('cancelled');
$strdefaultcategory = get_string('cohortdefaultcategory', 'local_properties');
$strnofields        = get_string('cohortnofieldsdefined', 'local_properties');
$strcreatefield     = get_string('cohortcreatefield', 'local_properties');

$strnofields = get_string('lpnofieldsdefined','local_properties');
$categories = $DB->get_records('cohort_info_category', null, 'sortorder ASC');

// Check that we have at least one category defined.
if (empty($categories)) {
    echo html_writer::start_tag('div', array('class' => 'content-categories'));
    echo $OUTPUT->notification($strnofields);
    echo html_writer::end_tag('div');
}


foreach ($categories as $category) {

    $table = new html_table();
    //$table->head  = array(get_string('cohortfields', 'local_properties'), get_string('edit'));
    $table->align = array('left', 'right');
    $table->width = '95%';
    $table->attributes['class'] = 'generaltable profilefield mt-table card-box';
    $table->data = array();

    if ($fields = $DB->get_records('cohort_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
        foreach ($fields as $field) {

            $table->data[] = array(format_string($field->name), cohort_field_field_icons($field));
        }
    }
    $fields =get_string('addfields','local_properties');
    $editstrcohort =  html_writer::start_tag('a', array('class' => 'add', 'title' => $add_btn, 'href' => '#', 'alt' => $add_btn, 'data-toggle' => 'modal', 'data-target' => '#myModalcohort', 'data-catid' => $category->id));
            $editstrcohort .=  html_writer::tag('i','', array('class'=>'men men-icon-add', 'aria-hidden' => 'true'));
            $editstrcohort .= $fields;
        $editstrcohort .=  html_writer::end_tag('a');

        echo html_writer::start_tag('div', array('class' => 'content-categories card-box'));
            echo $OUTPUT->heading(format_string($category->name) .' '.cohort_field_category_icons($category).' '.$editstrcohort);
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

}
}

function cohort_field_reorder_fields() {
    global $DB;

    if ($categories = $DB->get_records('cohort_info_category')) {
        foreach ($categories as $category) {
            $i = 1;
            if ($fields = $DB->get_records('cohort_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
                foreach ($fields as $field) {
                    $f = new stdClass();
                    $f->id = $field->id;
                    $f->sortorder = $i++;
                    $DB->update_record('cohort_info_field', $f);
                }
            }
        }
    }
}

/**
 * Reorder the profile categoriess starting at the category at the given startorder.
 */
function cohort_field_reorder_categories() {
    global $DB;

    $i = 1;
    if ($categories = $DB->get_records('cohort_info_category', null, 'sortorder ASC')) {
        foreach ($categories as $cat) {
            $c = new stdClass();
            $c->id = $cat->id;
            $c->sortorder = $i++;
            $DB->update_record('cohort_info_category', $c);
        }
    }
}

/**
 * Delete a profile category
 * @param int $id of the category to be deleted
 * @return bool success of operation
 */
function cohort_field_delete_category($id) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/local/properties/lib/lib.php');
    
    // Retrieve the category.
    if (!$category = $DB->get_record('cohort_info_category', array('id' => $id))) {
        print_error('invalidcategoryid');
    }

    if (!$categories = $DB->get_records('cohort_info_category', null, 'sortorder ASC')) {
        print_error('nocate', 'debug');
    }

    unset($categories[$category->id]);
  
    $fields = $DB->get_records('cohort_info_field', array('categoryid' => $category->id));
    if($fields)
    {
        delete_fields_cohort($fields);

    }
   
    // Finally we get to delete the category.
    $DB->delete_records('cohort_info_category', array('id' => $category->id));
    cohort_field_reorder_categories();
    return true;
}

/**
 * Deletes a profile field.
 * @param int $id
 */
function cohort_field_delete_field($id) {
    global $DB;

    // Remove any user data associated with this field.
    if (!$DB->delete_records('cohort_info_data', array('fieldid' => $id))) {
        print_error('cannotdeletecustomfield');
    }

    // Note: Any availability conditions that depend on this field will remain,
    // but show the field as missing until manually corrected to something else.

    // Need to rebuild cohort cache to update the info.
    //rebuild_cohort_cache();

    // Try to remove the record from the database.
    $DB->delete_records('cohort_info_field', array('id' => $id));

    // Reorder the remaining fields in the same category.
    cohort_field_reorder_fields();
}

/**
 * Change the sort order of a field
 *
 * @param int $id of the field
 * @param string $move direction of move
 * @return bool success of operation
 */
function cohort_field_move_field($id, $move) {
    global $DB;

    // Get the field object.
    if (!$field = $DB->get_record('cohort_info_field', array('id' => $id), 'id, sortorder, categoryid')) {
        return false;
    }
    // Count the number of fields in this category.
    $fieldcount = $DB->count_records('cohort_info_field', array('categoryid' => $field->categoryid));

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
    if ($swapfield = $DB->get_record('cohort_info_field', $params, 'id, sortorder')) {

        // Swap the sortorders.
        $swapfield->sortorder = $field->sortorder;
        $field->sortorder     = $neworder;

        // Update the field records.
        $DB->update_record('cohort_info_field', $field);
        $DB->update_record('cohort_info_field', $swapfield);
    }

    cohort_field_reorder_fields();
    return true;
}

/**
 * Change the sort order of a category.
 *
 * @param int $id of the category
 * @param string $move direction of move
 * @return bool success of operation
 */
function cohort_field_move_category($id, $move) {
    global $DB;
    // Get the category object.
    if (!($category = $DB->get_record('cohort_info_category', array('id' => $id), 'id, sortorder'))) {
        return false;
    }

    // Count the number of categories.
    $categorycount = $DB->count_records('cohort_info_category');

    // Calculate the new sortorder.
    if (($move == 'up') and ($category->sortorder > 1)) {
        $neworder = $category->sortorder - 1;
    } else if (($move == 'down') and ($category->sortorder < $categorycount)) {
        $neworder = $category->sortorder + 1;
    } else {
        return false;
    }

    // Retrieve the category object that is currently residing in the new position.
    if ($swapcategory = $DB->get_record('cohort_info_category', array('sortorder' => $neworder), 'id, sortorder')) {

        // Swap the sortorders.
        $swapcategory->sortorder = $category->sortorder;
        $category->sortorder     = $neworder;

        // Update the category records.
        $DB->update_record('cohort_info_category', $category) and $DB->update_record('cohort_info_category', $swapcategory);
        return true;
    }

    return false;
}

/**
 * Retrieve a list of all the available data types
 * @return   array   a list of the datatypes suitable to use in a select statement
 */
function cohort_field_list_datatypes() {
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
function cohort_field_list_categories() {
    global $DB;
    if (!$categories = $DB->get_records_menu('cohort_info_category', null, 'sortorder ASC', 'id, name')) {
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
function cohort_field_edit_category($id, $redirect) {
    global $DB, $OUTPUT, $CFG;

    require_once($CFG->dirroot.'/local/properties/index_cohort_category_form.php');
    $categoryform = new category_form();

    if ($category = $DB->get_record('cohort_info_category', array('id' => $id))) {
        $categoryform->set_data($category);
    }

    if ($categoryform->is_cancelled()) {
        redirect($redirect);
    } else {
       
        if ($data = $categoryform->get_data()) {
    
            if (empty($data->id)) {
                unset($data->id);
                $data->sortorder = $DB->count_records('cohort_info_category') + 1;
                $DB->insert_record('cohort_info_category', $data, false);
            } else {
                $DB->update_record('cohort_info_category', $data);
            }
            cohort_field_reorder_categories();
            redirect($redirect);

        }

        if (empty($id)) {
            $strheading = get_string('cohortdefaultcategory', 'local_properties');
        } else {
            $strheading = get_string('cohorteditcategory', 'local_properties', format_string($category->name));
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
function cohort_field_edit_field($id, $datatype, $redirect, $catid) {
    global $CFG, $DB, $OUTPUT, $PAGE;

    if (!$field = $DB->get_record('cohort_info_field', array('id' => $id))) {
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
    require_once($CFG->dirroot.'/local/properties/index_cohort_field_form.php');
 
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

            $type = 'editcohortfield';
            $formfield->define_save($data,$type);
            //cohort_field_reorder_fields();
            //cohort_field_reorder_categories();

            redirect($redirect);
        }

        $datatypes = cohort_field_list_datatypes();

        if (empty($id)) {
            $strheading = get_string('cohortcreatenewfield', 'local_properties', $datatypes[$datatype]);
        } else {
            $strheading = get_string('cohorteditfield', 'local_properties', $field->name);
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
 * Create a string containing the editing icons for the user profile categories
 * @param stdClass $category the category object
 * @return string the icon string
 */
function cohort_field_category_icons($category) {
    global $CFG, $USER, $DB, $OUTPUT;

    $strdelete   = get_string('delete');
    $strmoveup   = get_string('moveup');
    $strmovedown = get_string('movedown');
    $stredit     = get_string('edit');
    $stradd     = get_string('add');

    $categorycount = $DB->count_records('cohort_info_category');
    $fieldcount    = $DB->count_records('cohort_info_field', array('categoryid' => $category->id));

    // Edit.
    //$editstr = '<a title="'.$stradd.'" href="index.php?action=editcohortfield"><img src="'.$OUTPUT->pix_url('t/add') . '" alt="'.$stradd.'" class="iconsmall" /></a> ';
    //$editstr  .=  '<a title="'.$streditc.'" href="index.php?action=editcohort&contextid=1"><img src="'.$OUTPUT->pix_url('t/add') . '" alt="'.$straddcohort.'" class="iconsmall" /></a> ';
     $editstr .= '<a title="'.$stredit.'" href="index.php?id='.$category->id.'&amp;action=editcohortcategory"><i class="wid wid-editicon"></i></a> ';

    // Delete.
    // Can only delete the last category if there are no fields in it.
    $editstr .= '<a title="'.$strdelete.'" href="#" data-id="'.$category->id.'" data-action="deletecohortcategory" data-sesskey='.sesskey().'"><i class="wid wid-deleteicon"></i></a>';
   
    return $editstr;
}

/**
 * Create a string containing the editing icons for the user profile fields
 * @param stdClass $field the field object
 * @return string the icon string
 */
function cohort_field_field_icons($field) {
    global $CFG, $USER, $DB, $OUTPUT;
   
    $strdelete   = get_string('delete');
    $strmoveup   = get_string('moveup');
    $strmovedown = get_string('movedown');
    $stredit     = get_string('edit');

    $fieldcount = $DB->count_records('cohort_info_field', array('categoryid' => $field->categoryid));
    $datacount  = $DB->count_records('user_info_data', array('fieldid' => $field->id));

    // Edit.
    $editstr = html_writer::start_tag('div', array('class' => 'actions'));
        $editstr .= '<a href="index.php?id='.$field->id.'&amp;action=editcohortfield"><i class="wid wid-editicon"></i>'.$stredit.'</a> ';

        $datacount = $DB->count_records('cohort_info_data', array('fieldid' => $field->id));
        $editstr .= '<a title="'.$strdelete.'" href="#" data-count="'.$datacount.'" data-id="'.$field->id.'" data-action="deletecohortfield" data-sesskey='.sesskey().'"><i class="wid wid-deleteicon"></i>'.$strdelete.'</a>';
    $editstr .= html_writer::end_tag('div');

    return $editstr;
}
