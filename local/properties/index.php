<?php

require('../../config.php');

//Globalized required vars
global $CFG, $OUTPUT, $PAGE, $DB;

include($CFG->libdir.'/adminlib.php');
require_once('commonbaseclass.php');
require_once('usercohort.php');
require_once('coursemetadata.php');
require_once('userprofile.php');
require_once('lpproperties.php');

$action   = optional_param('action', '', PARAM_ALPHA);
require_login();
$PAGE->set_title(get_string('pluginname', 'local_properties'));
$PAGE->set_heading(get_string('pluginname', 'local_properties'));
admin_externalpage_setup('profilefields');

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('pluginname', 'local_properties'), new moodle_url('#'));

$PAGE->requires->css(new moodle_url("/local/properties/css/style.css"));
$messagecategory = get_string('deletecategory', 'local_properties');
$messagefield = get_string('profileconfirmfielddeletion', 'admin');
$header1 =$messagefield." ".$messagecategory;
$params = array('stringfield' => $messagefield, 'stringcategory' => $messagecategory);
$PAGE->requires->js_call_amd('local_properties/main', 'init', $params);

$redirect = $CFG->wwwroot.'/local/properties/index.php#user_tab';

if($action)
{
    switch ($action) {
    
    case 'deletecategory':
        $id      = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', 0, PARAM_BOOL);
        $sesskey = required_param('sesskey', PARAM_RAW);
        if ($sesskey && $confirm) {
            profile_delete_category($id);
            echo json_encode($redirect);
        }
        die;
        break;
    case 'deletefield':
        $id      = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', 0, PARAM_BOOL);
        $sesskey = required_param('sesskey', PARAM_RAW);
       
        if ((data_submitted() && $confirm) && $sesskey) {
            profile_delete_field($id);
            echo json_encode($redirect);
        }
        die;
        break;
    case 'editfield':
        $id       = optional_param('id', 0, PARAM_INT);
        $catid    = optional_param('catid', 0, PARAM_INT);
        $datatype = optional_param('datatype', '', PARAM_ALPHA);
        profile_edit_field($id, $datatype, $redirect,'editfield', $catid);
        die;
        break;
    case 'editcategory':
       
        $id = optional_param('id', 0, PARAM_INT);
        profile_edit_category($id, $redirect);
        die;
        break;
    default:
  
    case 'editcohortcategory':
        $id = optional_param('id', 0, PARAM_INT);
        $redirect = $CFG->wwwroot.'/local/properties/index.php#cohort_tab';
        cohort_field_edit_category($id, $redirect);
        die;
        break;
       
    case 'deletecohortcategory':
       
        $id      = optional_param('id',0, PARAM_INT);
        $confirm = optional_param('confirm', 0, PARAM_BOOL);
        $sesskey = required_param('sesskey', PARAM_RAW);
        $redirect = $CFG->wwwroot.'/local/properties/index.php#cohort_tab';
        if ($sesskey && $confirm) {
           cohort_field_delete_category($id);
           echo json_encode($redirect);
        }
        die;
        break;
     case 'deletecohortfield':
        $id      = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', 0, PARAM_BOOL);
        $sesskey = required_param('sesskey', PARAM_RAW);
        $redirect = $CFG->wwwroot.'/local/properties/index.php#cohort_tab';
        
        if ((data_submitted() && $confirm) && $sesskey) {
            cohort_field_delete_field($id);
            echo json_encode($redirect);
        }
        die;
        break;
    case 'editcohortfield':
        $id       = optional_param('id', 0, PARAM_INT);
        $catid    = optional_param('catid', 0, PARAM_INT);
        $datatype = optional_param('datatype', '', PARAM_ALPHA);
        $redirect = $CFG->wwwroot.'/local/properties/index.php#cohort_tab';
        cohort_field_edit_field($id, $datatype, $redirect, $catid);
        die;
        break; 
    
     case 'editcoursecategory':
        $id = optional_param('id', 0, PARAM_INT);
         $redirect = $CFG->wwwroot.'/local/properties/index.php#course_tab';
        course_field_edit_category($id, $redirect);
        die;
        break;
    
    case 'editcoursefield':
        $id       = optional_param('id', 0, PARAM_INT);
        $catid    = optional_param('catid', 0, PARAM_INT);
        $datatype = optional_param('datatype', '', PARAM_ALPHA);
        $redirect = $CFG->wwwroot.'/local/properties/index.php#course_tab';
        course_field_edit_field($id, $datatype, $redirect,'', $catid);
        die;
        break;
     
    case 'deletecoursecategory':
        $id      = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', 0, PARAM_BOOL);
        $sesskey = required_param('sesskey', PARAM_RAW);
        $redirect = $CFG->wwwroot.'/local/properties/index.php#course_tab';
        if ($sesskey && $confirm) {
            course_field_delete_category($id);
            echo json_encode($redirect);
        }
        die;
        break;
    case 'deletecoursefield':
        $id      = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', 0, PARAM_BOOL);
        $sesskey = required_param('sesskey', PARAM_RAW);
        $redirect = $CFG->wwwroot.'/local/properties/index.php#course_tab';
        
        if ((data_submitted() && $confirm) && $sesskey) {
            course_field_delete_field($id);
            echo json_encode($redirect);
        }
        die;
        break;
        //lP STARTS
    case 'editlpcategory':
        $id = optional_param('id', 0, PARAM_INT);
        $redirect = $CFG->wwwroot.'/local/properties/index.php#learningpath_tab';
        lp_field_edit_category($id, $redirect);
        die;
        break;
    
    case 'editlpfield':
        $id       = optional_param('id', 0, PARAM_INT);
        $catid    = optional_param('catid', 0, PARAM_INT);
        $datatype = optional_param('datatype', '', PARAM_ALPHA);
        $redirect = $CFG->wwwroot.'/local/properties/index.php#learningpath_tab';
        lp_field_edit_field($id, $datatype, $redirect, $catid);
        die;
        break;
     
    case 'deletelpcategory':
        $id      = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', 0, PARAM_BOOL);
        $sesskey = required_param('sesskey', PARAM_RAW);
        $redirect = $CFG->wwwroot.'/local/properties/index.php#learningpath_tab';
        if ($sesskey && $confirm) {
            lp_field_delete_category($id);
            echo json_encode($redirect);
        }
        die;
        break;
    case 'deletelpfield':
        $id      = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', 0, PARAM_BOOL);
        $sesskey = required_param('sesskey', PARAM_RAW);
        $redirect = $CFG->wwwroot.'/local/properties/index.php#learningpath_tab';

        if ((data_submitted() && $confirm) && $sesskey) {
            lp_field_delete_field($id);
            echo json_encode($redirect);
        }
        die;
    break;
            
    }    
}

echo $OUTPUT->header();
echo html_writer::start_tag('div', array('class' => 'col-sm-12 content-coursewizard pl-0'));
    echo html_writer::tag('h2',get_string('pagetitle', 'local_properties'), array('class' => 'title-coursecoursewizard'));
    echo html_writer::tag('span', get_string('pagedescription', 'local_properties'), array('class' => 'description_newcourse'));
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', array('class' => 'content-tabsproperties'));
    echo html_writer::start_tag('div', ['id' => 'learning-paths-container', 'class' => 'mar-no']);
        echo html_writer::start_tag('div');
		   // Panel heading 
            echo html_writer::start_tag('div');
                echo html_writer::start_tag('div', array('class' => 'tab-base mar-no'));
                    echo html_writer::start_tag('ul',array('class'=>'nav nav-tabs tbs', 'role'=>'tablist'));
                		    
                        echo html_writer::start_tag('li',array('role'=>'presentation', 'class'=>''));
                            echo html_writer::tag('a',get_string('userprop', 'local_properties'),array('href'=>'#user_tab', 'id'=>'#user_tab', 'aria-controls'=>'previoussessions', 'aria-expanded'=>'false', 'role'=>'tab', 'data-toggle'=>'tab', 'class'=>'nav-link'));
                        echo html_writer::end_tag('li');
                           
                        echo html_writer::start_tag('li',array('role'=>'presentation', 'class'=>''));
                            echo html_writer::tag('a',get_string('cohortprop', 'local_properties'),array('href'=>'#cohort_tab', 'id'=>'#cohort_tab', 'aria-controls'=>'previoussessions', 'aria-expanded'=>'false', 'role'=>'tab', 'data-toggle'=>'tab', 'class'=>'nav-link'));
                        echo html_writer::end_tag('li');
                
                        echo html_writer::start_tag('li',array('role'=>'presentation', 'class'=>''));
                            echo html_writer::tag('a',get_string('coursesprop', 'local_properties'),array('href'=>'#course_tab','id'=>'#course_tab', 'aria-controls'=>'previoussessions', 'aria-expanded'=>'false', 'role'=>'tab', 'data-toggle'=>'tab', 'class'=>'nav-link'));
                        echo html_writer::end_tag('li');
                
                        echo html_writer::start_tag('li',array('role'=>'presentation', 'class'=>''));
                            echo html_writer::tag('a',get_string('learningpathprop', 'local_properties'),array('href'=>'#learningpath_tab','id'=>'#learningpath_tab', 'aria-controls'=>'previoussessions', 'aria-expanded'=>'false', 'role'=>'tab', 'data-toggle'=>'tab', 'class'=>'nav-link'));
                        echo html_writer::end_tag('li');
                                
                    echo html_writer::end_tag('ul');
                echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
                
        $stredit = get_string('edit','local_properties');
        $strdelete   = get_string('delete','local_properties');
        $straddprofile = get_string('addprofilefield','local_properties');
        $add_btn  = get_string('addfield','local_properties');
        
        $streditc = get_string('editcohort','local_properties');
        $straddcategoryc = get_string('addcategoryc','local_properties');
        
        $strdeletec   = get_string('deletecohort','local_properties');
        $straddcohort = get_string('addfieldc','local_properties');
        $stradd_btnc  = get_string('addfieldcbtn','local_properties');
        $exclamation_icon = '<i class="icon fa fa-exclamation-circle text-danger fa-fw"></i>';
        
        // User tab      
        echo html_writer::start_tag('div', array('class' => 'tab-content'));
            echo html_writer::start_tag('div', ['id' => 'user_tab', 'class' => 'tabs-properties tab-pane fade pad-all card-box']);
                echo html_writer::start_tag('div', array('class' => 'row')); 
                    
                    echo html_writer::start_tag('div', array('class' => 'user-categories col-sm-6')); 
                    echo html_writer::end_tag('div');
                    
                    $text_button = 'Add a new Category';
                    echo html_writer::start_tag('div', array('class' => 'add-categories col-sm-6 right pr-0'));  
                        echo html_writer::start_tag('a', array('class' => 'add-category btn new-button-line', 'title' => $add_btn, 'href' => '#', 'alt' => $add_btn, 'data-toggle' => 'modal', 'data-target' => '#myModalUser', 'data-catid' => $category->id));
                            echo html_writer::tag('i','', array('class'=>'men men-icon-phadd fa fa-add', 'aria-hidden' => 'true'));
                            echo $text_button;
                        echo html_writer::end_tag('a');    
                    echo html_writer::end_tag('div');
            
                    echo html_writer::start_tag('div', array('class' => 'categories-content col-sm-12 pl-0 pr-0'));
                        $options = profile_list_datatypes();
                        $popupurl = new moodle_url('/local/properties/index.php?id=0&action=editfield');
                        getCategories();
                        $selectuf = html_writer::select($options,'datatype', 0,'-Select-',array('id' => 'userfields'));
                       
                        echo html_writer::start_tag('div', array('id' => 'myModal', 'class' => 'modal fade', 'role' => 'dialog'));
                            echo html_writer::start_tag('div', array('class' => 'modal-dialog'));
                                //Modal content
                                $modprofile = get_string('addnewfield', 'local_properties');
                                $categoryname = get_string('selectfield', 'local_properties');
                                echo html_writer::start_tag('div', array('class' => 'modal-content'));
                                    
                                    echo html_writer::start_tag('div', array('class' => 'modal-header'));
                                        echo html_writer::start_tag('a', array('id' => 'close_course_popup', 'class' => 'close', 'data-dismiss' => 'modal'));
                                            echo html_writer::tag('i', '', array('class' => 'wid wid-icon-close-x'));
                                        echo html_writer::end_tag('a');
                                        echo html_writer::tag('h4',$modprofile);
                                    echo html_writer::end_tag('div');

                                    echo html_writer::start_tag('div', array('id' => 'parent_scrollable'));
                                        echo html_writer::start_tag('div', array('class' => 'modal-body add_scroll row'));
                                                                                 echo html_writer::start_tag('div', array('class' => 'catname_col col-sm-4 col-md-4 col-lg-4'));
                                       
                                            echo html_writer::tag('h6',$categoryname, array('class' => 'categoryname'));

                                        
                                                echo html_writer::start_tag('div', array('class' => 'btns'));
                                                    echo html_writer::tag('i',$exclamation_icon);
                                                    echo html_writer::start_tag('div', array('class' => 'btn btn-secondary p-a-0 buttonhelpnf'));
                                                        echo html_writer::start_tag('i', array('class' => 'wid wid-icon-helpbutton', 'aria-hidden' => 'true'));
                                                        echo html_writer::end_tag('i');
                                                    echo html_writer::end_tag('div');
                                                echo html_writer::end_tag('div');
                                        echo html_writer::end_tag('div');

                                            echo html_writer::start_tag('form', array('action' => $popupurl, 'method' => 'get' , 'class' => 'col-sm-8 col-md-8 col-lg-8'));
                                                echo html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'id', 'value' => 0));
                                                echo html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'catid', 'value' => 0, 'class' => 'urlclass'));
                                                echo html_writer::start_tag('div', array('class' => ''));
                                                echo html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'action', 'value' => 'editfield'));
                                                echo $selectuf;
                                                echo html_writer::end_tag('div');
                                            echo html_writer::end_tag('form');
                                        echo html_writer::end_tag('div');
                                    echo html_writer::end_tag('div');
                                echo html_writer::end_tag('div');
                            echo html_writer::end_tag('div');
                        echo html_writer::end_tag('div');         
                    echo html_writer::end_tag('div');
                
                echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
            
            // Cohort tab
            echo html_writer::start_tag('div', array('id' => 'cohort_tab', 'class' => 'tabs-properties tab-pane fade pad-all card-box' ));
                echo html_writer::start_tag('div', array('class' => 'row')); 
                    
                    echo html_writer::start_tag('div', array('class' => 'cohorts-categories col-sm-6')); 
                        //echo html_writer::tag('h1', get_string('cohort_properties', 'local_properties'));
                    echo html_writer::end_tag('div');

                    $texts_button = 'Add a new Category';
                    echo html_writer::start_tag('div', array('class' => 'add-categories col-sm-6 right pr-0'));

                        echo html_writer::start_tag('a', array('class' => 'add-category btn new-button-line', 'title' => $add_btn, 'href' => '#', 'alt' => $add_btn, 'data-toggle' => 'modal', 'data-target' => '#myModalCohort', 'data-catid' => $category->id));
                            echo html_writer::tag('i','', array('class'=>'men men-icon-phadd fa fa-add', 'aria-hidden' => 'true'));
                            echo $texts_button;
                        echo html_writer::end_tag('a');
                    echo html_writer::end_tag('div');
                    
                    echo html_writer::start_tag('div', array('class' => 'categories-content col-sm-12 pl-0 pr-0'));
                        $optionscohort = cohort_field_list_datatypes();
                        $popupurlcohort = new moodle_url('/local/properties/index.php?id=0&action=editcohortfield');
                        getCohorts();
                        $selectcf = html_writer::select($optionscohort,'datatype', 0,'-Select-', array('id' => 'cohortfields'));
                        echo html_writer::tag('html', '<div id="myModalcohort" class="modal fade" role="dialog">
                            <div class="modal-dialog">
                                <!-- Modal content-->
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <a id="close_course_popup" clas="close btn-default" data-dismiss="modal">
                                            <i class="wid wid-icon-close-x"></i>
                                        </a>
                                        <h4 class="modal-title">'.get_string('addnewfield', 'local_properties').'</h4>
                                    </div>
                                    <div id="parent_scrollable" >
                                        <div class="modal-body add_scroll">
                                        <div class="catname_col col-sm-4 col-md-4 col-lg-4">
                                        <p class="categoryname">'.get_string('selectfield', 'local_properties').'</p>
                                        <div class="btns">'.$exclamation_icon.'
                                        <div class="btn btn-secondary p-a-0 buttonhelpnf"><i class="wid wid-icon-helpbutton" aria-hidden="true"></i></div>
                                        </div>
                                        </div>
                                            <form action="'.$popupurlcohort.'" method="get">
                                                <input type="hidden" name="id" value="0" >
                                                <input type="hidden" name="catid" value="0" class="urlclass">
                                                 <div class="col-sm-8 col-md-8 col-lg-8"><input type="hidden" name="action" value="editcohortfield">
                                                  '.$selectcf.' 
                                                  </div>                                            
                                            </form> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>');
                    echo html_writer::end_tag('div');
                echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
            
            // Course tab
            echo html_writer::start_tag('div', array('id' => 'course_tab', 'class' => 'tabs-properties tab-pane fade pad-all card-box' ));
                echo html_writer::start_tag('div', array('class' => 'row')); 
                    echo html_writer::start_tag('div', array('class' => 'course-categories col-sm-6'));     
                        //echo html_writer::tag('h1', get_string('course_properties', 'local_properties'));
                    echo html_writer::end_tag('div'); 

                    $texts_button = 'Add a new Category';
                    echo html_writer::start_tag('div', array('class' => 'add-categories col-sm-6 right pr-0'));
                        echo html_writer::start_tag('a', array('class' => 'add-category btn new-button-line', 'title' => $add_btn, 'href' => '#', 'alt' => $add_btn, 'data-toggle' => 'modal', 'data-target' => '#myModalCourse', 'data-catid' => $category->id));
                            echo html_writer::tag('i','', array('class'=>'men men-icon-phadd fa fa-add', 'aria-hidden' => 'true'));
                            echo $texts_button;
                        echo html_writer::end_tag('a');
                    echo html_writer::end_tag('div');
                        
                    echo html_writer::start_tag('div', array('class' => 'categories-content col-sm-12 pl-0 pr-0'));    
                        $optionscourse = course_field_list_datatypes();
                        $popupurlcourse = new moodle_url('/local/properties/index.php?id=0&action=editcoursefield');
                        getCourseMetadata();
                        $selectcsf = html_writer::select($optionscourse,'datatype', 0,'-Select-',array('id' => 'coursefields'));
                        echo html_writer::tag('html', '<div id="myModalcourse" class="modal fade" role="dialog">
                            <div class="modal-dialog">
                                <!-- Modal content-->
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <a id="close_course_popup" clas="close btn-default" data-dismiss="modal">
                                            <i class="wid wid-icon-close-x"></i>
                                        </a>
                                        <h4 class="modal-title">'.get_string('addnewfield', 'local_properties').'</h4>
                                    </div>
                                    <div id="parent_scrollable" >
                                        <div class="modal-body add_scroll">
                                        <div class="catname_col col-sm-4 col-md-4 col-lg-4">
                                        <p class="categoryname">'.get_string('selectfield', 'local_properties').'</p>
                                        <div class="btns">'.$exclamation_icon.'
                                        <div class="btn btn-secondary p-a-0 buttonhelpnf"><i class="wid wid-icon-helpbutton" aria-hidden="true"></i></div>
                                        </div>
                                        </div>
                                            <form action="'.$popupurlcourse.'" method="get">
                                                <input type="hidden" name="id" value="0" >
                                                <input type="hidden" name="catid" value="0" class="urlclass">
                                                 <div class="col-sm-8 col-md-8 col-lg-8"><input type="hidden" name="action" value="editcoursefield">
                                                  '.$selectcsf.'                                             
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>');   
                    
                    echo html_writer::end_tag('div');
                echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
        
            // Learning tab
            echo html_writer::start_tag('div', array('id' => 'learningpath_tab', 'class' => 'tabs-properties tab-pane fade pad-all card-box'));
                echo html_writer::start_tag('div', array('class' => 'row')); 
                    echo html_writer::start_tag('div', array('class' => 'learning-categories col-sm-6')); 
                        //echo html_writer::tag('h1', get_string('learning_properties', 'local_properties'));
                    echo html_writer::end_tag('div'); 

                    $texts_button = 'Add a new Category';
                    echo html_writer::start_tag('div', array('class' => 'add-categories col-sm-6 right pr-0'));
                        echo html_writer::start_tag('a', array('class' => 'add-category btn new-button-line', 'title' => $add_btn, 'href' => '#', 'alt' => $add_btn, 'data-toggle' => 'modal', 'data-target' => '#myModalLearningPath', 'data-catid' => $category->id));
                            echo html_writer::tag('i','', array('class'=>'men men-icon-phadd fa fa-add', 'aria-hidden' => 'true'));
                            echo $texts_button;
                        echo html_writer::end_tag('a');
                    echo html_writer::end_tag('div');
                    
                    echo html_writer::start_tag('div', array('class' => 'categories-content col-sm-12 pl-0 pr-0')); 
                        $optionsLP = lp_field_list_datatypes();
                        $popupurlLP = new moodle_url('/local/properties/index.php?id=0&action=editlpfield');
                        getLPdata();    
                        $selectlpf = html_writer::select($optionsLP,'datatype', 0,'-Select-',array('id' => 'lpfields'));
                        echo html_writer::tag('html', '<div id="myModalLP" class="modal fade" role="dialog">
                            <div class="modal-dialog">
                                <!-- Modal content-->
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <a id="close_course_popup" clas="close btn-default" data-dismiss="modal">
                                            <i class="wid wid-icon-close-x"></i>
                                        </a>
                                        <h4 class="modal-title">'.get_string('addnewfield', 'local_properties').'</h4>
                                    </div>
                                    <div id="parent_scrollable" >
                                        <div class="modal-body add_scroll">
                                        <div class="catname_col col-sm-4 col-md-4 col-lg-4">
                                        <p class="categoryname">'.get_string('selectfield', 'local_properties').'</p>
                                        <div class="btns">'.$exclamation_icon.'
                                        <div class="btn btn-secondary p-a-0 buttonhelpnf"><i class="wid wid-icon-helpbutton" aria-hidden="true"></i></div>
                                        </div>
                                        </div>
                                            <form action="'.$popupurlLP.'" method="get">
                                                <input type="hidden" name="id" value="0" >
                                                <input type="hidden" name="catid" value="0" class="urlclass">
                                                <div class="col-sm-8 col-md-8 col-lg-8"><input type="hidden" name="action" value="editlpfield">
                                                  '.$selectlpf.'                                             
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>');
                    echo html_writer::end_tag('div');
                echo html_writer::end_tag('div');   
            echo html_writer::end_tag('div');        
        echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
echo html_writer::end_tag('div'); 
//User Modal
echo html_writer::tag('html', '<!-- Modal --><div class="modal fade" id="myModalUser" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title">'.get_string('addnewcategory', 'local_properties').'</h4>
            </div>
            <div class="modal-body new-category"><form autocomplete="off" action="'.$CFG->wwwroot.'/local/properties/index.php" method="post" accept-charset="utf-8" id="mform1" class="mform">
    <div style="display: none;"><input name="id" type="hidden" value="">
<input name="action" type="hidden" value="editcategory">
<input name="sesskey" type="hidden" value="'.sesskey().'">
<input name="_qf__category_form" type="hidden" value="1">
</div>
<div class="form-group row  fitem   ">
   <div class="contentlabel col-sm-12 col-md-4">
       <p class="categoryname col-form-label  d-inline-block " for="id_name">
            '.get_string('categoryname', 'local_properties').'
        </p>
        <div class="options d-inline float-right">
            <abbr class="initialism text-danger required-element" data-inputid="id_name" title="Required"><i class="icon fa fa-exclamation-circle text-danger fa-fw " aria-hidden="true" title="Required" aria-label="Required"></i></abbr>
            <div class="btn btn-secondary p-a-0 buttonhelp">
                <i class="wid wid-icon-helpbutton" aria-hidden="true"></i>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-8 form-inline felement" data-fieldtype="text">
        <input type="text" class="form-control " name="name" id="id_name" value="" size="30" maxlength="255" required="true">
        <div class="form-control-feedback" id="id_error_name" style="display: none;">
        </div>
    </div>
</div><div class="form-group row  fitem femptylabel   " data-groupname="buttonar">
   <div class="contentlabel col-sm-12 col-md-4">
       <label class="col-form-label  d-inline-block " for="fgroup_id_buttonar">
        </label>
        <div class="options d-inline float-right">
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12 form-inline felement new_category_buttons" data-fieldtype="group">
    <div class="form-group  fitem   btn-cancel">
    <label class="col-form-label " for="id_cancel">
    </label>
    <span data-fieldtype="submit">
        <input type="submit" class="btn btn-round btn-secondary" name="cancel" id="id_cancel" value="Cancel" onclick="skipClientValidation = true; return true;">
    </span>
    <div class="form-control-feedback" id="id_error_cancel" style="display: none;">
    </div>
</div>
            <div class="form-group  fitem  ">
    <label class="col-form-label " for="id_submitbutton">
    </label>
    <span data-fieldtype="submit">
        <input type="submit" class="btn btn-round btn-primary" name="submitbutton" id="id_submitbutton" value="Add">
    </span>
    <div class="form-control-feedback" id="id_error_submitbutton" style="display: none;">
    </div>
</div>

        <div class="form-control-feedback" id="id_error_" style="display: none;">
        </div>
    </div>
</div>
        <div class="fdescription required">There are required fields in this form marked <i class="icon fa fa-exclamation-circle text-danger fa-fw " aria-hidden="true" title="Required field" aria-label="Required field"></i>.</div>
</form>
            </div>
        </div>
    </div>
</div>');
//Cohort Modal
echo html_writer::tag('html', '<!-- Modal --><div class="modal fade" id="myModalCohort" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title">'.get_string('addnewcategory', 'local_properties').'</h4>
            </div>
            <div class="modal-body new-category">
            <form autocomplete="off" action="'.$CFG->wwwroot.'/local/properties/index.php" method="post" accept-charset="utf-8" id="mform1" class="mform">
    <div style="display: none;"><input name="id" type="hidden" value="">
<input name="action" type="hidden" value="editcohortcategory">
<input name="sesskey" type="hidden" value="'.sesskey().'">
<input name="_qf__category_form" type="hidden" value="1">
</div>
<div class="form-group row fitem has-danger">
   <div class="contentlabel col-sm-12 col-md-4">
       <label class="categoryname col-form-label  d-inline-block " for="id_name">
        '.get_string('categoryname', 'local_properties').'
       </label>
        <div class="options d-inline float-right">
            <abbr class="initialism text-danger required-element" data-inputid="id_name" title="Required"><i class="icon fa fa-exclamation-circle text-danger fa-fw " aria-hidden="true" title="Required" aria-label="Required"></i></abbr>
            <div class="btn btn-secondary p-a-0 buttonhelp">
                <i class="wid wid-icon-helpbutton" aria-hidden="true"></i>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-8 form-inline felement" data-fieldtype="text">
        <input type="text" class="form-control form-control-danger" name="name" id="id_name" value="" size="30" maxlength="255" aria-describedby="id_error_name" aria-invalid="true" required="true">
        <div class="form-control-feedback" id="id_error_name" style="" tabindex="0"></div>
    </div>
</div><div class="form-group row  fitem femptylabel   " data-groupname="buttonar">
   <div class="contentlabel col-sm-12 col-md-4">
       <label class="col-form-label  d-inline-block " for="fgroup_id_buttonar">
        </label>
        <div class="options d-inline float-right">
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12 form-inline felement new_category_buttons" data-fieldtype="group">
            <div class="form-group  fitem  ">
    <label class="col-form-label " for="id_submitbutton">
    </label>
    <span data-fieldtype="submit">
        <input type="submit" class="btn btn-round btn-primary" name="submitbutton" id="id_submitbutton" value="Add">
    </span>
    <div class="form-control-feedback" id="id_error_submitbutton" style="display: none;">
    </div>
</div>
            <div class="form-group  fitem   btn-cancel">
    <label class="col-form-label " for="id_cancelcohort">  
    </label>
    <span data-fieldtype="submit">
        <input type="submit" class="btn btn-round btn-secondary" name="cancel" id="id_cancelcohort" value="Cancel" onclick="skipClientValidation = true; return true;">
    </span>
    <div class="form-control-feedback" id="id_error_cancel" style="display: none;">
    </div>
</div>
        <div class="form-control-feedback" id="id_error_" style="display: none;">   
        </div>
    </div>
</div>
        <div class="fdescription required">There are required fields in this form marked <i class="icon fa fa-exclamation-circle text-danger fa-fw " aria-hidden="true" title="Required field" aria-label="Required field"></i>.</div>
</form>
            </div>
        </div>
    </div>
</div>');
//Course Modal
echo html_writer::tag('html', '<!-- Modal --><div class="modal fade" id="myModalCourse" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title">'.get_string('addnewcategory', 'local_properties').'</h4>
            </div>
            <div class="modal-body new-category"><form autocomplete="off" action="'.$CFG->wwwroot.'/local/properties/index.php" method="post" accept-charset="utf-8" id="mform1" class="mform">
    <div style="display: none;"><input name="id" type="hidden" value="">
<input name="action" type="hidden" value="editcoursecategory">
<input name="sesskey" type="hidden" value="'.sesskey().'">
<input name="_qf__category_form" type="hidden" value="1">
</div>
<div class="form-group row  fitem">
   <div class="contentlabel col-sm-12 col-md-4">
       <label class="categoryname col-form-label  d-inline-block " for="id_name">
            '.get_string('categoryname', 'local_properties').'
        </label>
        <div class="options d-inline float-right">
            <abbr class="initialism text-danger required-element" data-inputid="id_name" title="Required"><i class="icon fa fa-exclamation-circle text-danger fa-fw " aria-hidden="true" title="Required" aria-label="Required"></i></abbr>
            <div class="btn btn-secondary p-a-0 buttonhelp">
                <i class="wid wid-icon-helpbutton" aria-hidden="true"></i>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-8 form-inline felement" data-fieldtype="text">
        <input type="text" class="form-control " name="name" id="id_name" value="" size="30" maxlength="255" required="true">
        <div class="form-control-feedback" id="id_error_name" style="display: none;">
        </div>
    </div>
</div><div class="form-group row  fitem femptylabel   " data-groupname="buttonar">
   <div class="contentlabel col-sm-12 col-md-4">
       <label class="col-form-label  d-inline-block " for="fgroup_id_buttonar">
        </label>
        <div class="options d-inline float-right">
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12 form-inline felement new_category_buttons" data-fieldtype="group">
            <div class="form-group  fitem  ">
    <label class="col-form-label " for="id_submitbutton">
    </label>
    <span data-fieldtype="submit">
        <input type="submit" class="btn btn-round btn-primary" name="submitbutton" id="id_submitbutton" value="Add">
    </span>
    <div class="form-control-feedback" id="id_error_submitbutton" style="display: none;">
    </div>
</div>
            <div class="form-group  fitem   btn-cancel">
    <label class="col-form-label " for="id_cancelcourse">
    </label>
    <span data-fieldtype="submit">
        <input type="submit" class="btn btn-round btn-secondary" name="cancel" id="id_cancelcourse" value="Cancel" onclick="skipClientValidation = true; return true;">
    </span>
    <div class="form-control-feedback" id="id_error_cancel" style="display: none;">
    </div>
</div>
        <div class="form-control-feedback" id="id_error_" style="display: none;">
        </div>
    </div>
</div>
        <div class="fdescription required">There are required fields in this form marked <i class="icon fa fa-exclamation-circle text-danger fa-fw " aria-hidden="true" title="Required field" aria-label="Required field"></i>.</div>
</form></div>
        </div>
    </div>
</div>');
//LP Modal
echo html_writer::tag('html', '<!-- Modal --><div class="modal fade" id="myModalLearningPath" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title">'.get_string('addnewcategory', 'local_properties').'</h4>
            </div>
            <div class="modal-body new-category"><form autocomplete="off" action="'.$CFG->wwwroot.'/local/properties/index.php" method="post" accept-charset="utf-8" id="mform1" class="mform">
    <div style="display: none;"><input name="id" type="hidden" value="">
<input name="action" type="hidden" value="editlpcategory">
<input name="sesskey" type="hidden" value="'.sesskey().'">
<input name="_qf__category_form" type="hidden" value="1">
</div>
<div class="form-group row fitem has-danger">
   <div class="contentlabel col-sm-12 col-md-4">
       <label class="categoryname col-form-label  d-inline-block " for="id_name">
            '.get_string('categoryname', 'local_properties').'
        </label>
        <div class="options d-inline float-right">
            <abbr class="initialism text-danger required-element" data-inputid="id_name" title="Required"><i class="icon fa fa-exclamation-circle text-danger fa-fw " aria-hidden="true" title="Required" aria-label="Required"></i></abbr>
            <div class="btn btn-secondary p-a-0 buttonhelp">
                <i class="wid wid-icon-helpbutton" aria-hidden="true"></i>
            </div>
        </div>

    </div>
    <div class="col-xs-12 col-sm-12 col-md-8 form-inline felement" data-fieldtype="text">
        <input type="text" class="form-control form-control-danger" name="name" id="id_name" value="" size="30" maxlength="255" aria-describedby="id_error_name" aria-invalid="true" required="true">
        <div class="form-control-feedback" id="id_error_name" style="" tabindex="0"></div>
    </div>
</div><div class="form-group row  fitem femptylabel   " data-groupname="buttonar">
   <div class="contentlabel col-sm-12 col-md-4">
       <label class="col-form-label  d-inline-block " for="fgroup_id_buttonar"> 
        </label>
        <div class="options d-inline float-right">
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12 form-inline felement new_category_buttons" data-fieldtype="group">
            <div class="form-group  fitem  ">
    <label class="col-form-label " for="id_submitbutton">
    </label>
    <span data-fieldtype="submit">
        <input type="submit" class="btn btn-round btn-primary" name="submitbutton" id="id_submitbutton" value="Add">
    </span>
    <div class="form-control-feedback" id="id_error_submitbutton" style="display: none;">
    </div>
</div>
<div class="form-group  fitem   btn-cancel">
    <label class="col-form-label " for="id_cancelLP">
    </label>
    <span data-fieldtype="submit">
        <input type="submit" class="btn btn-round btn-secondary" name="cancel" id="id_cancelLP" value="Cancel" onclick="skipClientValidation = true; return true;">
    </span>
    <div class="form-control-feedback" id="id_error_cancel" style="display: none;">
    </div>
</div>
        <div class="form-control-feedback" id="id_error_" style="display: none;">
            
        </div>
    </div>
</div>
        <div class="fdescription required">There are required fields in this form marked <i class="icon fa fa-exclamation-circle text-danger fa-fw " aria-hidden="true" title="Required field" aria-label="Required field"></i>.</div>
</form></div>
            </div>
        </div>
    </div>
</div>');
echo $OUTPUT->footer();