<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function getCategories(){
   global $CFG, $OUTPUT, $PAGE, $DB;
    $strnofields = get_string('lpnofieldsdefined','local_properties');
    // Show all categories.
    $categories = $DB->get_records('user_info_category', null, 'sortorder ASC');

    // Check that we have at least one category defined.
    if (empty($categories)) {
        echo html_writer::start_tag('div', array('class' => 'col-sm-12 content-categories'));
        echo $OUTPUT->notification($strnofields);
        echo html_writer::end_tag('div');
    }

    // Print the header.

    foreach ($categories as $category) {
        $table = new html_table();
        //$table->head  = array(get_string('profilefield', 'admin'), get_string('edit'));
        $table->align = array('left', 'right');
        $table->width = '95%';
        $table->attributes['class'] = 'profilefield table mt-table card-box';
        $table->data = array();
     
        if ($fields = $DB->get_records('user_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
            foreach ($fields as $field) {
                $table->data[] = array(format_string($field->name), profile_field_icons($field));
            }
        }

        $fields_butoon = get_string('addfields','local_properties');
        $addfieldsbutton =  html_writer::start_tag('a', array('class' => 'add', 'title' => $add_btn, 'href' => '#', 'alt' => $add_btn, 'data-toggle' => 'modal', 'data-target' => '#myModal', 'data-catid' => $category->id));
            $addfieldsbutton .=  html_writer::tag('i','', array('class'=>'men men-icon-add', 'aria-hidden' => 'true'));
            $addfieldsbutton .=  $fields_butoon;
        $addfieldsbutton .=  html_writer::end_tag('a');

        echo html_writer::start_tag('div', array('class' => 'content-categories card-box'));
            echo $OUTPUT->heading(format_string($category->name) .' '.profile_category_icons($category).' '.$addfieldsbutton);
        echo html_writer::start_tag('div', array('class' => 'content-table'));
       if (count($table->data)) {
            echo html_writer::table($table);
        } else {
            //echo $OUTPUT->notification($strnofields);
            $nofields = get_string('nofields','local_properties');
            echo html_writer::start_tag('div', array('class' => 'nofields'));
                echo $nofields;
            echo html_writer::end_tag('div');
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

    }   
}

function profile_field_icons($field) {
    global $CFG, $USER, $DB, $OUTPUT;

    $strdelete   = get_string('delete');
    $strmoveup   = get_string('moveup');
    $strmovedown = get_string('movedown');
    $stredit     = get_string('edit');

    $fieldcount = $DB->count_records('user_info_field', array('categoryid' => $field->categoryid));
    $datacount  = $DB->count_records('user_info_data', array('fieldid' => $field->id));
        $editstr = html_writer::start_tag('div', array('class' => 'actions'));
        // Edit.
        $editstr .= '<a href="index.php?id='.$field->id.'&amp;action=editfield"><i class="wid wid-editicon"></i>'.$stredit.'</a> ';
        
         // Delete.
        $datacount = $DB->count_records('user_info_data', array('fieldid' => $field->id));
        $editstr .= '<a title="'.$strdelete.'" href="#" data-count="'.$datacount.'" data-id="'.$field->id.'" data-action="deletefield" data-sesskey='.sesskey().'"><i class="wid wid-deleteicon"></i>'.$strdelete.'</a>';
        
        $editstr .= html_writer::end_tag('div');

    return $editstr;
}

/***** Some functions relevant to this script *****/

/**
 * Create a string containing the editing icons for the user profile categories
 * @param stdClass $category the category object
 * @return string the icon string
 */
function profile_category_icons($category) {
    global $CFG, $USER, $DB, $OUTPUT;

    $strdelete   = get_string('delete');
    $strmoveup   = get_string('moveup');
    $strmovedown = get_string('movedown');
    $stredit     = get_string('edit');

    $categorycount = $DB->count_records('user_info_category');
    $fieldcount    = $DB->count_records('user_info_field', array('categoryid' => $category->id));

    // Edit.
    $editstr = '<a title="'.$stredit.'" href="index.php?id='.$category->id.'&amp;action=editcategory"><i class="wid wid-editicon"></i></a> ';

    // Delete.
    // Can only delete the last category if there are no fields in it.
    $editstr .= '<a title="'.$strdelete.'" href="#" data-id="'.$category->id.'" data-action="deletecategory" data-sesskey='.sesskey().'"><i class="wid wid-deleteicon"></i></a>';

    return $editstr;
}

function profile_list_datatypes() {
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
function profile_list_categories() {
    global $DB;
    if (!$categories = $DB->get_records_menu('user_info_category', null, 'sortorder ASC', 'id, name')) {
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
function profile_edit_category($id, $redirect) {
    global $DB, $OUTPUT, $CFG;

    require_once($CFG->dirroot.'/user/profile/index_category_form.php');
    $categoryform = new category_form();

    if ($category = $DB->get_record('user_info_category', array('id' => $id))) {
        $categoryform->set_data($category);
    }

    if ($categoryform->is_cancelled()) {
        redirect($redirect);
    } else {
        if ($data = $categoryform->get_data()) {
            if (empty($data->id)) {
                unset($data->id);
                $data->sortorder = $DB->count_records('user_info_category') + 1;
                $DB->insert_record('user_info_category', $data, false);
            } else {
                $DB->update_record('user_info_category', $data);
            }
            
            redirect($redirect);

        }

        if (empty($id)) {
            $strheading = get_string('profilecreatenewcategory', 'admin');
        } else {
            $strheading = get_string('profileeditcategory', 'admin', format_string($category->name));
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
function profile_edit_field($id, $datatype, $redirect,$type, $catid) {
    global $CFG, $DB, $OUTPUT, $PAGE;

    if (!$field = $DB->get_record('user_info_field', array('id' => $id))) {
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
    require_once($CFG->dirroot.'/user/profile/index_field_form.php');
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

            $formfield->define_save($data,$type);
            //profile_reorder_fields();
           // profile_reorder_categories();
            redirect($redirect);
        }

        $datatypes = profile_list_datatypes();

        if (empty($id)) {
            $strheading = get_string('profilecreatenewfield', 'admin', $datatypes[$datatype]);
        } else {
            $strheading = get_string('profileeditfield', 'admin', $field->name);
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


function profile_delete_category($id) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/local/properties/lib/lib.php');

    // Retrieve the category.
    if (!$category = $DB->get_record('user_info_category', array('id' => $id))) {
        print_error('invalidcategoryid');
    }

    if (!$categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
        print_error('nocate', 'debug');
    }

    unset($categories[$category->id]);

    $fields = $DB->get_records('user_info_field', array('categoryid' => $category->id));
    if($fields)
    {
        delete_fields_user($fields);

    }

    // Finally we get to delete the category!
    $DB->delete_records('user_info_category', array('id' => $category->id));
   
    return true;
}

/**
 * Deletes a profile field.
 * @param int $id
 */
function profile_delete_field($id) {
    global $DB;

    // Remove any user data associated with this field.
    if (!$DB->delete_records('user_info_data', array('fieldid' => $id))) {
        print_error('cannotdeletecustomfield');
    }

    // Note: Any availability conditions that depend on this field will remain,
    // but show the field as missing until manually corrected to something else.

    // Need to rebuild course cache to update the info.
    rebuild_course_cache();

    // Try to remove the record from the database.
    $DB->delete_records('user_info_field', array('id' => $id));

}