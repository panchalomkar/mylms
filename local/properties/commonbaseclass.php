<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

define ('COURSE_FIELD_VISIBLE_ALL',     2); // Only visible for users with local/cohort_meta_data:view capability.
define ('COURSE_FIELD_VISIBLE_PRIVATE', 1); // Either we are viewing our own profile or we have local/cohort_meta_data:view capability.
define ('COURSE_FIELD_VISIBLE_NONE',    0); // Only visible for local/cohort_meta_data:view capability.
//include($CFG->dirroot.'/user/profile/definelib.php');
class profile_define_base {

    /**
     * Prints out the form snippet for creating or editing a profile field
     * @param moodleform $form instance of the moodleform class
     */
    public function define_form(&$form) {
        $form->addElement('header', '_commonsettings', get_string('cohortcommonsettings', 'local_properties'));
        $this->define_form_common($form);
        
        if(optional_param('datatype' ,'',PARAM_TEXT) <> 'certificate')
        {
            $form->addElement('header', '_specificsettings', get_string('cohortspecificsettings', 'local_properties'));
            $this->define_form_specific($form);
        }
    }

    /**
     * Prints out the form snippet for the part of creating or editing a profile field common to all data types.
     *
     * @param moodleform $form instance of the moodleform class
     */
    public function define_form_common(&$form) {

        $strrequired = get_string('required');
        if(optional_param('datatype' ,'',PARAM_TEXT) <> 'certificate')
        {
            $form->addElement('text', 'shortname', get_string('cohortshortname', 'local_properties'), 'maxlength="100" size="25"');
            $form->addRule('shortname', $strrequired, 'required', null, 'client');
            $form->setType('shortname', PARAM_ALPHANUM);
        }else
        {
            $form->addElement('text', 'shortname', get_string('cohortshortname', 'local_properties'), ' disabled=disabled readonly=readonly maxlength="100" size="25"');
            //$form->addRule('shortname', $strrequired, 'required', null, 'client');
            $form->setType('shortname', PARAM_ALPHANUM);
            $form->setDefault('shortname', 'certificate');
        }
        

        $form->addElement('text', 'name', get_string('cohortname', 'local_properties'), 'size="50"');
        $form->addRule('name', $strrequired, 'required', null, 'client');
        $form->setType('name', PARAM_TEXT);

        $form->addElement('editor', 'description', get_string('cohortdescription', 'local_properties'), null, null);

        $form->addElement('selectyesno', 'required', get_string('cohortrequired', 'local_properties'));

        $form->addElement('selectyesno', 'locked', get_string('cohortlocked', 'local_properties'));

        $form->addElement('selectyesno', 'forceunique', get_string('cohortforceunique', 'local_properties'));

        //$form->addElement('selectyesno', 'signup', get_string('cohortsignup', 'local_properties_meta_data'));

        $choices = array();
        $choices[COURSE_FIELD_VISIBLE_NONE]    = get_string('cohortvisiblenone', 'local_properties');
        $choices[COURSE_FIELD_VISIBLE_PRIVATE] = get_string('cohortvisibleprivate', 'local_properties');
        $choices[COURSE_FIELD_VISIBLE_ALL]     = get_string('cohortvisibleall', 'local_properties');
        $form->addElement('select', 'visible', get_string('cohortvisible', 'local_properties'), $choices);
        $form->addHelpButton('visible', 'profilevisible', 'cohort');
        $form->setDefault('visible', COURSE_FIELD_VISIBLE_ALL);
        if(optional_param('action','',PARAM_TEXT)=='editfield'){
            $choices = profile_list_categories();
       
        }else if(optional_param('action','',PARAM_TEXT)=='editcohortfield'){
             $choices = cohort_field_list_categories();
             
        }else if(optional_param('action','',PARAM_TEXT)=='editcoursefield'){
       $choices = course_field_list_categories();
        } 
        else if(optional_param('action','',PARAM_TEXT)=='editlpfield'){
       $choices = lp_field_list_categories();
        } 
       $form->addElement('select', 'categoryid', get_string('cohortcategory', 'local_properties'), $choices);
    }

    /**
     * Prints out the form snippet for the part of creating or editing a profile field specific to the current data type.
     * @param moodleform $form instance of the moodleform class
     */
    public function define_form_specific($form) {
        // Do nothing - overwrite if necessary.
    }

    /**
     * Validate the data from the add/edit profile field form.
     *
     * Generally this method should not be overwritten by child classes.
     *
     * @param stdClass|array $data from the add/edit profile field form
     * @param array $files
     * @return array associative array of error messages
     */
    public function define_validate($data, $files) {

        $data = (object)$data;
        $err = array();

        $err += $this->define_validate_common($data, $files);
        $err += $this->define_validate_specific($data, $files);

        return $err;
    }

    /**
     * Validate the data from the add/edit profile field form that is common to all data types.
     *
     * Generally this method should not be overwritten by child classes.
     *
     * @param stdClass|array $data from the add/edit profile field form
     * @param array $files
     * @return  array    associative array of error messages
     */
    public function define_validate_common($data, $files) {
        global $DB;

        $err = array();
 if(optional_param('action','',PARAM_TEXT)=='editfield'){
        if (empty($data->shortname)) {
            $err['shortname'] = get_string('required');

        } else {
            // Fetch field-record from DB.
            $field = $DB->get_record('user_info_field', array('shortname' => $data->shortname));
            // Check the shortname is unique.
            if ($field and $field->id <> $data->id) {
                $err['shortname'] = get_string('profileshortnamenotunique', 'admin');
            }
            // NOTE: since 2.0 the shortname may collide with existing fields in $USER because we load these fields into
            // $USER->profile array instead.
        }
 }
 else if(optional_param('action','',PARAM_TEXT)=='editcohortfield'){    
        // Check the shortname was not truncated by cleaning.
        if (empty($data->shortname)) {
            $err['shortname'] = get_string('required');

        } else {
            // Fetch field-record from DB.
            $field = $DB->get_record('cohort_info_field', array('shortname' => $data->shortname));
            // Check the shortname is unique.
            if ($field and $field->id <> $data->id) {
                $err['shortname'] = get_string('cohortshortnamenotunique', 'local_properties');
            }
            // NOTE: since 2.0 the shortname may collide with existing fields in $USER because we load these fields into
            // $USER->profile array instead.
        }
 }
 
  else if(optional_param('action','',PARAM_TEXT)=='editcourseield'){    
        if (empty($data->shortname)) {
            $err['shortname'] = get_string('required');

        } else {
            // Fetch field-record from DB.
            $field = $DB->get_record('course_info_field', array('shortname' => $data->shortname));
            // Check the shortname is unique.
            if ($field and $field->id <> $data->id) {
                $err['shortname'] = get_string('courseshortnamenotunique', 'local_properties');
            }
            // NOTE: since 2.0 the shortname may collide with existing fields in $USER because we load these fields into
            // $USER->profile array instead.
        }
 }
   else if(optional_param('action','',PARAM_TEXT)=='editlpld'){    
        // Check the shortname was not truncated by cleaning.
        if (empty($data->shortname)) {
            $err['shortname'] = get_string('required');

        } else {
            // Fetch field-record from DB.
            $field = $DB->get_record('lp_info_field', array('shortname' => $data->shortname));
            // Check the shortname is unique.
            if ($field and $field->id <> $data->id) {
                $err['shortname'] = get_string('lpshortnamenotunique', 'local_properties');
            }
            // NOTE: since 2.0 the shortname may collide with existing fields in $USER because we load these fields into
            // $USER->profile array instead.
        }
 }
 
        // No further checks necessary as the form class will take care of it.
        return $err;
    }

    /**
     * Validate the data from the add/edit profile field form
     * that is specific to the current data type
     * @param array $data
     * @param array $files
     * @return  array    associative array of error messages
     */
    public function define_validate_specific($data, $files) {
        // Do nothing - overwrite if necessary.
        return array();
    }

    /**
     * Alter form based on submitted or existing data
     * @param moodleform $mform
     */
    public function define_after_data(&$mform) {
        // Do nothing - overwrite if necessary.
    }

    /**
     * Add a new profile field or save changes to current field
     * @param array|stdClass $data from the add/edit profile field form
     */
    public function define_save($data,$type) {
        
        global $DB;
          
         if($type=='editfield'){
                $data = $this->define_save_preprocess($data); // Hook for child classes.
                $old = false;
                if (!empty($data->id)) {
                    $old = $DB->get_record('user_info_field', array('id' => (int)$data->id));
                }
                // Check to see if the category has changed.
                if (!$old or $old->categoryid != $data->categoryid) {
                    $data->sortorder = $DB->count_records('user_info_field', array('categoryid' => $data->categoryid)) + 1;
                }
                if (empty($data->id)) {
                    unset($data->id);
                    $data->id = $DB->insert_record('user_info_field', $data);
                } else {
                    $DB->update_record('user_info_field', $data);
                }
         }
         
         else if($type=='editcohortfield'){
                $data = $this->define_save_preprocess($data); // Hook for child classes.
                $old = false;
                if (!empty($data->id)) {
                    $old = $DB->get_record('cohort_info_field', array('id' => (int)$data->id));
                }
                // Check to see if the category has changed.
                if (!$old or $old->categoryid != $data->categoryid) {
                    $data->sortorder = $DB->count_records('cohort_info_field', array('categoryid' => $data->categoryid)) + 1;
                }
                if (empty($data->id)) {
                    unset($data->id);
                    $data->id = $DB->insert_record('cohort_info_field', $data);
                } else {
                    $DB->update_record('cohort_info_field', $data);
                }
       } 
      else if($type=='editcoursefield'){
          
            $data = $this->define_save_preprocess($data); // Hook for child classes.
            $old = false;
            if (!empty($data->id)) {
                $old = $DB->get_record('course_info_field', array('id' => (int)$data->id));
            }
            // Check to see if the category has changed.
            if (!$old or $old->categoryid != $data->categoryid) {
                $data->sortorder = $DB->count_records('course_info_field', array('categoryid' => $data->categoryid)) + 1;
            }
            if (empty($data->id)) {
                unset($data->id);
                $data->id = $DB->insert_record('course_info_field', $data);
            } else {
                $DB->update_record('course_info_field', $data);
            }
       }
       
       else if($type=='editlpfield'){
          
            $data = $this->define_save_preprocess($data); // Hook for child classes.
            $old = false;
            if (!empty($data->id)) {
                $old = $DB->get_record('lp_info_field', array('id' => (int)$data->id));
            }

            // Check to see if the category has changed.
            if (!$old or $old->categoryid != $data->categoryid) {
                $data->sortorder = $DB->count_records('lp_info_field', array('categoryid' => $data->categoryid)) + 1;
            }

            if (empty($data->id)) {
                unset($data->id);
                $data->id = $DB->insert_record('lp_info_field', $data);
            } else {
                $DB->update_record('lp_info_field', $data);
            }
       }
  }

    /**
     * Preprocess data from the add/edit profile field form before it is saved.
     *
     * This method is a hook for the child classes to overwrite.
     *
     * @param array|stdClass $data from the add/edit profile field form
     * @return array|stdClass processed data object
     */
    public function define_save_preprocess($data) {
        // Do nothing - overwrite if necessary.
        return $data;
    }

    /**
     * Provides a method by which we can allow the default data in cohort_field_define_* to use an editor
     *
     * This should return an array of editor names (which will need to be formatted/cleaned)
     *
     * @return array
     */
    public function define_editors() {
        return array();
    }
}