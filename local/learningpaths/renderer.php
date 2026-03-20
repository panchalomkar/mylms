<?php

/**
* Rendere for learning paths local plugin
*
* @package    local_learningpaths_renderer
* @author     Andres
*
*/
defined('MOODLE_INTERNAL') || die;

require_once "{$CFG->dirroot}/local/learningpaths/classes/forms/LearningPathForm.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/forms/AddCoursesForm.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/forms/ManageCoursesForm.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/forms/ManageCoursesPositionForm.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/forms/ManageCohortsForm.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/plms_form.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/forms/notifications_form.php";
require_once "{$CFG->dirroot}/local/learningpaths/lib.php";


class local_learningpaths_renderer extends plugin_renderer_base
{
    /*
    * Build a html with the general data of learning path like name, description, etc
    *
    * @param (data) general learning path record data from database
    */
    public function learningpath_view_tab($data)
    {
        // Require file library, this will used for get the learningpath image if exist
        global $CFG,$PAGE,$DB,$OUTPUT;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        if (is_null($data->startdate))
            $data->startdate = get_string('startdate_undefined', 'local_learningpaths');

        if (is_null($data->enddate))
            $data->enddate = get_string('enddate_undefined', 'local_learningpaths');

        // Get files related with current learningpath
        $fs = get_file_storage();
        $files = $fs->get_area_files(1, 'local_learningpaths', 'image', $data->id);

        $startdate = ((int)$data->startdate)?date('m/d/Y', $data->startdate):get_string('notset', 'local_learningpaths') ;
        $endate = ((int)$data->enddate)?date('m/d/Y', $data->enddate):get_string('notset', 'local_learningpaths');
                      // get publish button for LP added by ShivkumarY
        if (has_capability('local/learningpaths:add_courses_learning_path', context_system::instance())) {
            $total_courses = $DB->count_records_sql("SELECT count(id) FROM {learningpath_courses} WHERE learningpathid=$data->id");
            $active_courses = $DB->count_records_sql("SELECT count(id)  FROM {learningpath_courses} WHERE learningpathid=$data->id AND course_active = 1");
                if($total_courses != $active_courses){
                  $publish_btn = '<input type="submit" class="btn btn-round btn-primary" id="btn-lp_publish" title="'.get_string('publish_title','local_learningpaths').'" value="'.get_string('publish','local_learningpaths').'">';
                }
                else if($total_courses == 0) {
                  $publish_btn = '<input type="submit" class="btn btn-round btn-primary" id="btn-lp_publish" title="'.get_string('publish_title','local_learningpaths').'" value="'.get_string('publish','local_learningpaths').'" disabled="disabled">';
                }
                else{
                  $publish_btn = '<input type="submit" class="btn btn-round btn-primary" id="btn-lp_publish" title="'.get_string('publish_title','local_learningpaths').'" value="'.get_string('published','local_learningpaths').'" disabled="disabled">';
                }
        }

            $startdate = ((int)$data->startdate)?date('m/d/Y', $data->startdate):get_string('notset', 'local_learningpaths') ;
            $endate = ((int)$data->enddate)?date('m/d/Y', $data->enddate):get_string('notset', 'local_learningpaths');

            // Learningpath stats
                $description = json_decode($data->description);
                /*
                * @author VaibhavG.
                * @since 8th Feb 2021
                * @desc #492 VAPT Fixes. 
                * @desc #527 When new LP is created Description is unreadable.
                */
                $desc = htmlspecialchars_decode($description->text);
                $lp_custom_properties_html = get_learningpath_custom_properties_html($data->id);
            
            // Create an instance of learningpath block
            $blockinstance = block_instance('lpd');
            $blockinstance->config = new stdClass();
            $blockinstance->config->learningpath = required_param('id', PARAM_INT);

            $lpd_text_html = $blockinstance->get_content()->text;

            $total_users = $data->total_users;
            $cohots = $DB->get_records('learningpath_cohorts', ['learningpathid' => $data->id]);
            $totalcohortsusrs = 0;
            if(count($cohots) > 0) {
              foreach ($cohots as $row) {
                $totalcohortsusrs += $DB->count_records_sql("SELECT count(id) FROM {cohort_members} WHERE cohortid=$row->cohortid");
              }
            }
            $total_users = $total_users + $totalcohortsusrs;
         //   print_object($data);
        $lp_view_tab[] = array('dataid'=>$data->id,'dataimage' =>$data->image,'dataimg'=>$data->image->get_timemodified(),
                        'startdate'=>$startdate,
                        'endate'=>$endate,'data_total_users'=>$data->total_users,'datacredits'=>$data->credits,
                        'data_total_courses'=>$data->total_courses,'data_total_cohorts'=>$data->total_cohorts,
                        'data_total_courses_required'=>$data->total_courses_required,'desc'=>$desc,'publish_btn'=>$publish_btn,
                        'lp_custom_properties_html'=>$lp_custom_properties_html,'lpd_text_html'=>$lpd_text_html,'total_users'=>$total_users);

        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'lp_view_tab' => $lp_view_tab
        );
        return $OUTPUT->render_from_template('local_learningpaths/learningpath_view_tab',$template_data);
    }

    /**
    * Edit learningpath tab form
    *
    * @param (data) general data of learning path
    */
    public function learningpath_edit_tab($data)
    {
        // Create learning path form
        $mform = new LearningPathForm(null, (array) $data);
        $form_data = [];
        $form_data['learningpath_image'] = load_image_to_draft($data->image);
        $mform->set_data($form_data);
        return $mform->render();
    }

    /**
    * Learning paths course administration tab
    *
    * @param (data) general data with learning paths courses
    */
    public function learningpath_courses_tab($data)
    {       
      global $OUTPUT;
        $has_add_course_lp = false;
        if (has_capability('local/learningpaths:add_courses_learning_path', context_system::instance())) {
            $has_add_course_lp = true;
        }
        $courselist = $this->courses_list($data->courses, $data->id);
        //print_r($courselist);
        //die;
        $lp_course_tab[] = array('has_add_course_lp'=>$has_add_course_lp,'courselist'=>$courselist,'coursename'=>optional_param('coursename', '', PARAM_TEXT));

        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'lp_course_tab' => $lp_course_tab
        );
        return $OUTPUT->render_from_template('local_learningpaths/learningpath_courses_tab',$template_data);
    }

    public function learningpath_notifications_tab($data)
    {
        global $DB,$OUTPUT;
        $object =  new lp_notifications1 ();
        if ($object->is_cancelled()) {
            redirect($returnurl);
        } else if ($data1 = $object->get_data()) {
            $system_config =  (array)$data1;

            //enrol
            $enrol = array();
            $enrol['cron'] = $system_config['cron_lp_enrollment'];
            $enrol['enrollment_editor_checkbox1'] = $system_config['enrollment_editor_checkbox1'];
            $enrollment_editor = array();
            $enrollment_editor['text'] = $system_config['enrollment_editor']['text'];
            $enrollment_editor['format'] = $system_config['enrollment_editor']['format'];
            $enrol['enrollment_editor'] = $enrollment_editor;

            $enrolconfig = new stdClass();
            $enrolconfig->config = json_encode($enrol);
            $enrolconfig->learningpathid = $data->id;
            $enrolconfig->type = 'enrollment';
            $enrolconfig->cron = $data1->cron_lp_enrollment;
            save_notification($enrolconfig);
            //end enrol

            //expiration
            $expiration = array(); 
            $expiration['cron'] = $system_config['cron_lp_expiration'];
            $expiration['expiration_editor_checkbox1'] = $system_config['expiration_editor_checkbox1'];
            $expiration_editor= array();
            $expiration_editor['text'] = $system_config['expiration_editor']['text'];
            $expiration['expiration_editor'] = $expiration_editor;

            $expirationconfig = new stdClass();
            $expirationconfig->config = json_encode($expiration);
            $expirationconfig->learningpathid = $data->id;
            $expirationconfig->type = 'expiration';
            $expirationconfig->cron = $data1->cron_lp_expiration;
            save_notification($expirationconfig);
            //end expiration
           
            //enreminder
            $enreminder = array(); 
            $enreminder['cron'] = $system_config['cron_lp_enreminder'];
            $enreminder['enreminder_editor_checkbox1'] = $system_config['enreminder_editor_checkbox1'];
            $enreminder['enreminder_editor_text'] = $system_config['enreminder_editor_text'];
            $enreminder_editor= array();
            $enreminder_editor['text'] = $system_config['enreminder_editor']['text'];
            $enreminder['enreminder_editor'] = $enreminder_editor;

            $enreminderconfig = new stdClass();
            $enreminderconfig->config = json_encode($enreminder);
            $enreminderconfig->learningpathid = $data->id;
            $enreminderconfig->type = 'enreminder';
            $enreminderconfig->cron = $data1->cron_lp_enreminder;
            save_notification($enreminderconfig);
            //end enreminder

            //exreminder
            $exreminder = array();
            $exreminder['cron'] = $system_config['cron_lp_exreminder'];
            $exreminder['exreminder_editor_checkbox1'] = $system_config['exreminder_editor_checkbox1'];
            $exreminder['exreminder_editor_text'] = $system_config['exreminder_editor_text'];
            $exreminder_editor = array();
            $exreminder_editor['text'] = $system_config['exreminder_editor']['text'];
            $exreminder_editor['format'] = $system_config['exreminder_editor']['format'];
            $exreminder['exreminder_editor'] = $exreminder_editor;

            $exreminderconfig = new stdClass();
            $exreminderconfig->config = json_encode($exreminder);
            $exreminderconfig->learningpathid = $data->id;
            $exreminderconfig->type = 'exreminder';
            $exreminderconfig->cron = $data1->cron_lp_exreminder;
            save_notification($exreminderconfig);
            //end nexreminder

            //completion_reminder
            $completion_reminder = array();
            $completion_reminder['cron'] = $system_config['cron_lp_completion_reminder'];
            $completion_reminder['completion_reminder_editor_checkbox'] = $system_config['completion_reminder_editor_checkbox'];
            $completion_reminder['completion_reminder_editor_text'] = $system_config['completion_reminder_editor_text'];
            $completion_reminder_editor = array();
            $completion_reminder_editor['text'] = $system_config['completion_reminder_editor']['text'];
            $completion_reminder_editor['format'] = $system_config['completion_reminder_editor']['format'];
            $completion_reminder['completion_reminder_editor'] = $completion_reminder_editor;

            $completion_reminderconfig = new stdClass();
            $completion_reminderconfig->config = json_encode($completion_reminder);
            $completion_reminderconfig->learningpathid = $data->id;
            $completion_reminderconfig->type = 'completion_reminder';
            $completion_reminderconfig->cron = $data1->cron_lp_completion_reminder;
            save_notification($completion_reminderconfig);
            //end completion_reminder

            //notifications
            $notifications = array();
            $notifications['cron'] = $system_config['cron_lp_notifications'];
            $notifications['notifications_editor_checkbox1'] = $system_config['notifications_editor_checkbox1'];
            $notifications_editor = array();
            $notifications_editor['text'] = $system_config['notifications_editor']['text'];
            $notifications_editor['format'] = $system_config['notifications_editor']['format'];
            $notifications['notifications_editor'] = $notifications_editor;

            $notificationsconfig = new stdClass();
            $notificationsconfig->config = json_encode($notifications);
            $notificationsconfig->learningpathid = $data->id;
            $notificationsconfig->type = 'path_com';
            $notificationsconfig->cron = $data1->cron_lp_notifications;
            save_notification($notificationsconfig);
            //end notifications

        }else{
            $types = get_types($data->id);
            foreach ($types as $type) {
                $config = get_config_notification($data->id, $type);
                $object->set_data($config);
            }
        }
        $var = $object->render();

        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'var' => $var
        );
        return $OUTPUT->render_from_template('local_learningpaths/learningpath_notifications_tab',$template_data);
    }

    /**
    * Learning path popup standart to use in popup without write all necesarry html again
    *
    * @param (name) popup name
    * @param (title) popup header
    * @param (content) popup content
    */
    public function learningpath_popup_standart($name, $title = "", $content = "", $class = '') {
        global $OUTPUT,$CFG;
        $user_popup_search = '';
        if($name == "users-popup") {
          $user_popup_search = '<div id="searchbox" class="searchbox" role="search">
                                  <div class="mt-search input-group custom-search-form" >
                                    <span class="input-group-btn">
                                      <button class="text-muted btn" type="button">
                                        <i class="men men-search-phx fa fa-search header-txtmen i-search" aria-hidden="true"></i>
                                      </button>
                                    </span>
                                    <input class="add-users-search form-control" type="text" data-target="available-users-list" placeholder="Search">
                                  </div>
                                </div>';
        }
        $lp_popup_standart[] = array('name'=>$name,'title'=>$title,'content'=>$content,'class'=>$class,'user_popup_search' => $user_popup_search);
        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'lp_popup_standart' => $lp_popup_standart
        );
        return $OUTPUT->render_from_template('local_learningpaths/learningpath_popup_standart',$template_data);
    }

    /**
    * Function to get learning paths popups like add courses, add users, etc
    * @param (data) learning path general data
    */
    public function popups($data) {
        // Popup for add new courses to learning path
        $output = $this->learningpath_popup_standart("courses-popup", get_string('courses', 'local_learningpaths'), $this->add_courses_form($data));

        // Popup for add new users to learning path
        $users_form = new ManageUsersForm(null, ['users' => $data->available_users, 'learningpath' => $data->id]);
        $output .= $this->learningpath_popup_standart("users-popup", get_string('users'), $users_form->render());

        // Popup for add new cohort to learning path
        $cohorts_form = new ManageCohortsForm(null, ['cohorts' => $data->available_cohorts, 'learningpath' => $data->id]);
        $output .= $this->learningpath_popup_standart('cohorts-popup', get_string('add_cohorts', 'local_learningpaths'), $cohorts_form->render());

        return $output;
    }

    /**
    * Return html to add courses in a learningpath
    * @param (data) object with (available_courses) which is a learningpath courses list and (id) which is learningpath id
    */
    public function add_courses_form($data) {
        $courses_form = new AddCoursesForm(null, ['courses' => $data->available_courses, 'learningpath' => $data->id]);
        return $courses_form->render();
    }

    /**
    * Learning path users administration tab
    *
    * @param (data) general data with learning path users
    */
    public function learningpath_users_tab($data) {
        global $OUTPUT;
          
        $has_delete_lp = false;
        if (has_capability('local/learningpaths:delete_learning_path', context_system::instance()) && count($data->users) > 0 ) {
            $has_delete_lp = true;
        }
        $userlist = $this->users_list($data->users, $data->id, $data->total_users, optional_param('items', 10, PARAM_INT), $data->users_page);
        
        $learningpath_users_tab[] = array('userlist'=>$userlist ,'user'=>optional_param('user', '', PARAM_TEXT),'has_delete_lp'=>$has_delete_lp);
        
        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'learningpath_users_tab' => $learningpath_users_tab
        );
        return $OUTPUT->render_from_template('local_learningpaths/learningpath_users_tab',$template_data);
    }   
    
    /*
    * Build a html with courses list of a learning path
    * @param (courses) learning paths courses array
    */
    public function courses_list($learningpath, $courses = []) {   
        global $USER , $OUTPUT, $PAGE;
        $page = optional_param('page_course', 0, PARAM_INT);
        $dashboard_per_page = optional_param('courseperpage', 10, PARAM_INT);
        $params['courseperpage'] = $dashboard_per_page;
        $params['page_course'] = $page;
        $params['tab'] = 'courses';
        
        $url = new moodle_url($PAGE->url, $params);
       $coursestmp = getCoursesInfo($courses, false, $USER->id, null, null);

// ✅ FIX
if (!is_array($coursestmp)) {
    $coursestmp = [];
}

$courseKey = array_keys($coursestmp);
        $html = "";
if (!is_array($courses)) {
    $courses = [];
}
       if (!empty($courses) && is_array($courses)) {

          $showsortable = '';
          if (has_capability('local/learningpaths:add_courses_learning_path', context_system::instance())) {
              $showsortable = ' showsortable';
          }
            
         $li_totals = is_countable($courses) ? count($courses) : 0;
          $la_index  = array_keys($courses);

          $la_pag_learninpath = array();

          if( $dashboard_per_page >10) $page = 0;

          for( $record=($page * $dashboard_per_page); $record < (( $page * $dashboard_per_page ) + $dashboard_per_page) ; $record++ ) {
              if (isset($la_index[$record]) && isset($courses[$la_index[$record]])) {
    $la_pag_learninpath[$la_index[$record]] = $courses[$la_index[$record]];
} $la_pag_learninpath[ $la_index[$record] ] = $courses[ $la_index[$record] ];
          }
          $pages = count($courses) / $dashboard_per_page;
          $active_page = 1;

          foreach ($la_pag_learninpath as $course) {
              if(in_array($course->data->courseid, $courseKey)){
                  $credits = ($coursestmp[$course->data->courseid]->credits > 0 )?$coursestmp[$course->data->courseid]->credits:0;
              }
              $checked = ($course->data->required == 1) ? 'checked' : '';
              $required = ($course->data->required) ? get_string('is_required', 'local_learningpaths') : get_string('is_not_required', 'local_learningpaths');
          
              // Action buttons for edit prerequisites and remove course
              $edit_icon = html_writer::tag('i', '', array('class' => 'fa fa-pencil string_class', 'data-placement' => 'bottom',  'title' => get_string('settings_lpcourse','local_learningpaths'),'aria-hidden' => 'true'));
              $delete_icon = html_writer::tag('i', '', array('class' => 'wid wid-deleteicon fa fa-trash', 'data-placement' => 'bottom',  'title' => get_string('delete','local_learningpaths'),'aria-hidden' => 'true','data-item' => $learningpath->id));
              $attrs = ['class' => 'edit-course', 'data-toggle' => 'modal', 'data-toggle' => 'modal', 'data-target' => "#prerequisites-popup-{$course->data->id}"];
              
              if (has_capability('local/learningpaths:delete_courses_learning_path', context_system::instance())) {
                  $deletecourse = html_writer::link('#', $delete_icon,['class' => 'delete-course-learning-path']);
              }else{
                  $deletecourse = '';
              }
              
              if (has_capability('local/learningpaths:add_courses_learning_path', context_system::instance())) {
                  $course->data->actions = html_writer::link('#', $edit_icon, $attrs) . $deletecourse;
              }
              
              // Icon
              $prerequisites = $course->get_prerequisites();
              if (count($prerequisites) > 0) {
                  $title = "";
                  foreach ($prerequisites as $prerequisite) {
                      $title .= "• "."{$prerequisite->coursename} ";
                  }
                  $title .= "";
                  $icon = html_writer::tag('i', '', array('class' => 'men men-icon-phcircle fa fa-circle-thin icons_lp','title'=> $title));
                  
                  $icon = "<i class=\"wid wid-icon-phprerquisites fa fa-lock icons_lp\" title=\"{$title}\" ></i>";
              } else {
                  $icon = '<i class="men men-icon-phcircle fa fa-circle-thin icons_lp"></i>';   
              }

              $coursename = (strlen($course->data->coursename) >= 40)?substr($course->data->coursename, 0, 40).'...':$course->data->coursename;

              if (has_capability('local/learningpaths:add_courses_learning_path', context_system::instance())) {
                  $has_add_course_lp = true;
              }

              $addpre_popup = $this->add_prerequisites_popup($course);

              $course_list[] = array('coursename'=>$coursename,'icon'=>$icon,
                                'dataid'=>$course->data->id,'checked'=>$checked,
                                'dataaction'=>$course->data->actions,'addpre_popup'=>$addpre_popup,
                              'credits'=>$credits,'has_add_course_lp'=>$has_add_course_lp);
          }
              if ($li_totals > 10){
                  $paging = true;
              }
              $hascourses = true;
        } else {
          $hascourses = false;
        }
          $pagination = '';
          $select = '';
          if($li_totals>10){
              $lpid = $course->data->learningpathid;
              $select .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'tab', 'value'=>'courses'));
              $select .= html_writer::end_tag('input');
              $select .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'id', 'value'=>$lpid));
              $select .= html_writer::end_tag('input');
              $select .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'page_course', 'value'=>$page));
              $select .= html_writer::end_tag('input');
              $select .= html_writer::start_tag('select',array('type'=>'text','id'=>'id_courseperpage','name'=>'courseperpage','class'=>'form-control','style'=>'width: 100% !important;height: 30px !important;'));
                  $vals = array(10,20,30,40,50,60,70,80,90,100);
              foreach ($vals  as $key) {
                  $selectedperpage = '';
                  if($dashboard_per_page == $key ) $selectedperpage = 'selected' ;
                  $select .= html_writer::tag('option',$key, array($selectedperpage=>$selectedperpage));
              }

              $select .= html_writer::end_tag('select');
        }
        if ($pages > 1) {
          $pagination.= $OUTPUT->paging_bar(count($courses), $page, $dashboard_per_page, $url.'&id='.$lpid, 'page_course');
        }
        
        $learningpath_course_list[] = array('showsortable'=>$showsortable,'course'=>$hascourses,'paging'=>$paging,'pagination'=>$pagination);
        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'learningpath_course_list' => $learningpath_course_list,
          'course_list' => $course_list,
          'select' =>$select,
        );
        $html .=$OUTPUT->render_from_template('local_learningpaths/learningpath_courses_list',$template_data);
        return $html;
        
    }

    public function learningpath_cohorts_tab($data) {
        global $OUTPUT;
        if (has_capability('local/learningpaths:add_courses_learning_path', context_system::instance()) && count($data->cohorts) > 0 ) {
            $has_add_course_lp = true;
        }
        if (has_capability('local/learningpaths:add_courses_learning_path', context_system::instance())) {
            $has_add_course_lp_other = true;
        }
        $cohorts_list = $this->cohorts_list($data->cohorts, $data->id, $data->total_cohorts);
        $learningpath_cohorts_tab[] = array('cohorts_list'=>$cohorts_list,'cohort'=>optional_param('cohort', '', PARAM_TEXT),'has_add_course_lp'=>$has_add_course_lp,'has_add_course_lp_other'=>$has_add_course_lp_other);

        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'learningpath_cohorts_tab' => $learningpath_cohorts_tab,
        );
        return $OUTPUT->render_from_template('local_learningpaths/learningpath_cohorts_tab',$template_data);
    }

    /**
    * Build html for a course add prerequisites popup
    * @param (course) learningpath course object
    */
    public function add_prerequisites_popup($course) {
        return $this->learningpath_popup_standart("prerequisites-popup-{$course->data->id}", get_string('add_prerequisites', 'local_learningpaths'), $this->prerequistes_form($course, $course->data->learningpathid), 'prerequisites-popup');
    }

    /*
    * Learning path prerequisites form, also this allows mark course as requirement for learning path completion
    *
    * @param (courses) learning paths courses array
    * @param (course) current course
    * @param (learningPath) learning path id
    */
    public function prerequistes_form($course, $learningpath) {   
        $prerequisites = [];
        foreach ($course->get_prerequisites() as $prerequisite) {
            $prerequisites[] = $prerequisite->prerequisite;
        }

        $mform = new ManageCoursesForm(null, [
            'learningpath_courses' => $course->get_learningpath_courses(),
            'learningpath' => $learningpath,
            'courseid' => $course->data->id,
            'prerequisites' => $prerequisites,
            'does_not_prerequisites' => array_keys($course->get_does_not_as_prerequisites()),
            'required' => $course->data->required,
            'already_added_prerequisites' => array_keys($course->learningpath_courses_added_as_prerequisite($learningpath))
        ]);
        return $mform->render();
    }

    /*
    * Build html code for users list of a learning path
    *
    * @param (courses) learning path courses array
    */
    public function users_list($users, $learningPath, $total_users, $users_per_page = 10, $active_page = 1) {
        global $USER , $OUTPUT,$DB, $PAGE;
        
        $page = optional_param('page', 0, PARAM_INT);
        $dashboard_per_page = optional_param('userperpage', 10, PARAM_INT);
        $params['id'] = $learningPath;
        $params['tab'] = 'users';
        $params['userperpage'] = $dashboard_per_page;
        $params['page'] = $page;
        $url = new moodle_url($PAGE->url, $params);
        $paging_html = "";
        $select ="";

        if($users){
            $has_users = true;
            $plmsform = new plms_form();
            $checkbox_head = $plmsform->fieldGeneralCheckbox('all_users', '', '', '');
            $checkbox_head_html = $checkbox_head ;   
            // List of users
            $fieldcredits = $DB->get_record('customfield_field',array('shortname'=>'credits'));
            foreach ($users as $user) {
                  $credits = 0;
                  $percentage = '';
                 $courses_completed = $user->completed_courses();
                  $creditslp = $DB->get_record('learningpaths', ['id'=>$learningPath]);
                  $creditsvallp = $creditslp->credits;
                  $courses_lp = $DB->get_records_sql('select id,courseid from {learningpath_courses} where learningpathid=? AND required != 1',[$learningPath]);
                  $required_courses = $DB->get_records_sql('select id,courseid from {learningpath_courses} where learningpathid=? AND required = 1',[$learningPath]); 

                  // If Required_courses Available
                  if(count($required_courses) > 0 && empty($creditsvallp)) {
                    $reqcount = 0;
                    foreach ($required_courses as $reqlpcourse) {
                      $reqprogress = $this->getCourseProgress($reqlpcourse->courseid,$user->data->userid);
                      if($reqprogress == 100) {
                        $reqcount += 1;
                      }
                    }
                    $percent = (100 * $reqcount) / count($required_courses);
                  }
                  // END Required Courses
                  // If Credits Available
                  $comcount = 0;
                  if($creditsvallp && empty(count($required_courses))) {
                    foreach ($courses_lp as $lpcourse) {
                      $progress = $this->getCourseProgress($lpcourse->courseid,$user->data->userid);
                      $creditsvalue = $DB->get_record('customfield_data',array('fieldid'=>$lpcourse->courseid));
                    //  $creditsvalue = $DB->get_record('course_info_data',array('courseid'=>$lpcourse->courseid ,'fieldid'=>$fieldcredits->id));
                      if($progress == 100) {
                        $credits += $creditsvalue->value;
                        $comcount += 1;
                      }
                    }
                    if(((100 * $credits) / $creditsvallp) >= 100) {
                      $percent = 100;
                    } else {
                      $percent = (100 * $credits) / $creditsvallp;
                    }
                    
                  }
                  // END Credits
                  
                  // Both Condition If Credits & Required Courses
                  if($creditsvallp && count($required_courses) > 0 ) {
                    $reqcount = 0;
                    foreach ($required_courses as $reqlpcourse) {
                      $reqprogress = $this->getCourseProgress($reqlpcourse->courseid,$user->data->userid);
                      $creditsvalue = $DB->get_record('customfield_data',array('fieldid'=>$reqlpcourse->courseid));
                     // $creditsvalue = $DB->get_record('course_info_data',array('courseid'=>$reqlpcourse->courseid ,'fieldid'=>$fieldcredits->id));
                      if($reqprogress == 100) {
                        $reqcount += 1;
                      }
                      if($creditsvalue->value && $reqprogress == 100) {
                        $credits += $creditsvalue->value;
                      }
                    }
                    foreach ($courses_lp as $lpcourse) {
                      $progress = $this->getCourseProgress($lpcourse->courseid,$user->data->userid);
                      $creditsvalue = $DB->get_record('customfield_data',array('fieldid'=>$lpcourse->courseid));
                     // $creditsvalue = $DB->get_record('course_info_data',array('courseid'=>$lpcourse->courseid ,'fieldid'=>$fieldcredits->id));
                      if($progress == 100) {
                        $credits += $creditsvalue->value;
                        $comcount += 1;
                      }
                    }
                    $reqpercent = (100 * $reqcount) / count($required_courses);
                   
                    $crepercent = (100 * $credits) / $creditsvallp;
                    if($crepercent >= 100) {
                      $crepercent = 100;
                    }
                    if($reqpercent >= 100) {
                      $reqpercent = 100;
                    }
                    if($reqpercent >= 100) {
                      $percent = $crepercent;
                    } else if($crepercent >= 100) {
                      if($crepercent == 100 && $reqpercent == 0) {
                        $percent = 50;
                      } else {
                        $percent = $reqpercent;
                      }
                    } else {
                      $percent = $reqpercent + $crepercent;
                      if($percent > 100) {
                        if($reqpercent > $crepercent) {
                          $percent = $reqpercent;
                        } else {
                          $percent = $crepercent;
                        }
                      }
                    } 
                  }
                  if(empty($creditsvallp) && empty(count($required_courses))) {
                    $percent = $courses_completed->percentage;
                  }
                  // END
                  $percent = round($percent);
                  $percentage = $percent;
                  // $completed = $DB->get_record('learningpath_completion', ['userid' => $user->data->userid,'learningpathid'=>$learningPath]);
                  // if($completed) {
                  //   $percentage = 100;
                  // } else {
                    //$percentage = $courses_completed->percentage;
                  //}
                  $user_array[]= array('userdataid'=>$user->data->id,'courses_completed_percentage'=>$percentage,
                                'checkbox'=>$checkbox,'progress'=>$progress,'userfirstname'=>$user->data->firstname,'userlastname'=>$user->data->lastname,
                                'enrolldate'=>date('M - d - Y', $user->data->enrollment_date));
            }
        } else {
            $has_users = false;
            $message = get_string('no_records', 'local_learningpaths');
        }
            $pages = $total_users / $dashboard_per_page;
            if($total_users > 10){
              $total_users_has = true;
              $select .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'tab', 'value'=>'users'));
              $select .= html_writer::end_tag('input');
              $select .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'id', 'value'=>$learningPath));
              $select .= html_writer::end_tag('input');
              $select .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'page', 'value'=>$page));
              $select .= html_writer::end_tag('input');
              $select .= html_writer::start_tag('select',array('type'=>'text','id'=>'id_userperpage','name'=>'userperpage','class'=>'form-control','style'=>'width:70px;'));
                  $vals = array(10,20,30,40,50,60,70,80,90,100);
                  foreach ($vals  as $key) {
                      $selectedperpage = '';
                      if($dashboard_per_page == $key ) $selectedperpage = 'selected' ;
                      $select .= html_writer::tag('option',$key, array($selectedperpage=>$selectedperpage));
                  }

              $select .= html_writer::end_tag('select');
                  if ($pages > 1) {
                  $paging_html .= $OUTPUT->paging_bar($total_users, $page, $dashboard_per_page, $url);
                  }
            } 
           
        $show_cohort_user_list_user_tab = $this->show_cohort_user_list_user_tab($learningPath);

        $user_list[] = array('show_cohort_user_list_user_tab'=>$show_cohort_user_list_user_tab,'checkbox_head_html'=>$checkbox_head_html
                        ,'has_users'=>$has_users,'total_users_has'=>$total_users_has,'select'=>$select,'paging_html'=>$paging_html);

       
        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'user_list' => $user_list,
          'user_array' => $user_array
        );
      
        return $OUTPUT->render_from_template('local_learningpaths/users_list',$template_data);
    }

    public function show_cohort_user_list_user_tab($learningPath)
    {
        global $USER , $OUTPUT , $DB;
        $paging_html = "";
        $select = "";
        $page = optional_param('cohortpage', 0, PARAM_INT);
        $dashboard_per_page = optional_param('cohortuserperpage', 10, PARAM_INT);
        $offset = $dashboard_per_page * ($page);
        $param = array();
        $where='';
        //Is searching an cohort user by name?
       $cohort_user = optional_param('cohortuser', '', PARAM_TEXT);
       $where .= ($cohort_user != '') ? " AND (u.firstname LIKE '%{$cohort_user}%' OR u.lastname LIKE '%{$cohort_user}%')" : "";
        
      
      $query ="SELECT u.id,CONCAT(u.firstname, ' ', u.lastname) AS userfullname,ca.name FROM {learningpath_cohorts} lc 
        LEFT JOIN {cohort} ca ON ca.id = lc.cohortid 
        LEFT JOIN {cohort_members} cm ON cm.cohortid = lc.cohortid 
        LEFT JOIN {user} u ON cm.userid = u.id WHERE lc.learningpathid = $learningPath {$where}";//exit;

        $cohort_user_list=$DB->get_records_sql($query,$param,$offset, $dashboard_per_page);

        $query2 ="SELECT count(cm.id) FROM {learningpath_cohorts} lc  LEFT JOIN {cohort_members} cm ON cm.cohortid=lc.cohortid WHERE lc.learningpathid=$learningPath";
        $count_user=$DB->count_records_sql($query2);
        $total_users = $count_user;

        if($cohort_user_list){
          $has_cohort_user_list = true;
          // List of users

          foreach ($cohort_user_list as $user) {
              if (empty($user->id) && empty($user -> userfullname)){
                $user_array[]=array('userfullname' => 'No Users', 'username'=>$user->name);
              } else {
                  $user_array[]=array('userid'=>$user->id,'userfullname'=>$user->userfullname,'username'=>$user->name);
              }
          }
        }else{
          $has_cohort_user_list = false;
        }    
        $pages = ceil($total_users / $dashboard_per_page);
            if($total_users > 10){
                  $select .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'tab', 'value'=>'users'));
                  $select .= html_writer::end_tag('input');
                  $select .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'id', 'value'=>$learningPath));
                  $select .= html_writer::end_tag('input');
                  $select .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'page', 'value'=>$page));
                  $select .= html_writer::end_tag('input');
                  $select .= html_writer::start_tag('select',array('type'=>'text','id'=>'id_cohortuserperpage','name'=>'cohortuserperpage','class'=>'form-control','style'=>'width:100% !important;height:30px !important'));
                      $vals = array(10,20,30,40,50,60,70,80,90,100);
                      foreach ($vals  as $key) {
                          $selectedperpage = '';
                          if($dashboard_per_page == $key ) $selectedperpage = 'selected' ;
                          $select .= html_writer::tag('option',$key, array($selectedperpage=>$selectedperpage));
                      }

                  $select .= html_writer::end_tag('select');
                if ($pages > 1) {
                    $page_html .= $OUTPUT->paging_bar($total_users, $page, $dashboard_per_page,'?id='.$learningPath.'&tab=users&cohortuserperpage='.$dashboard_per_page,'cohortpage');
                }
            } 

        $data[] = array('has_cohort_user_list'=>$has_cohort_user_list,'page_html'=>$page_html,'select'=>$select);

        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'data' => $data,
          'user_array' => $user_array
        );
        return $OUTPUT->render_from_template('local_learningpaths/show_cohort_user_list_user_tab',$template_data);
    }

    /*
    * Build html code for users list of a learning path
    *
    * @param (courses) learning path courses array
    */
    public function cohorts_list($cohorts, $learningpath,$total_cohort=null) {
        global $USER , $OUTPUT,$DB, $PAGE;
        $page = optional_param('page', 0, PARAM_INT);
        $dashboard_per_page = optional_param('cohortsperpage', 10, PARAM_INT);
        $params['id'] = $learningpath;
        $params['tab'] = 'cohorts';
        $params['cohortsperpage'] = $dashboard_per_page;
        $params['page'] = $page;
        $pageurl = new moodle_url($PAGE->url, $params);
        $paging_html = "";
        $select ="";
        
        if( count($cohorts) > 0){
          $has_cohorts= true;
          $plmsform = new plms_form();
          $checkbox = $plmsform->fieldGeneralCheckbox('all_cohorts', '', '', '');
          $checkbox_html = $checkbox;
          foreach ($cohorts as $cohort) {
              $url = new moodle_url("/local/learningpaths/actions.php?action=remove_cohort&item={$cohort->data->id}&learningpath={$learningpath}&sesskey={$USER->sesskey}");
              $cohort->actions = html_writer::link($url, "remove");
              $user_array[]= array('cohortdataid'=>$cohort->data->id,'cohortdataname'=>$cohort->data->name,
                            'enrolldate'=>date('M - d - Y', $cohort->data->enrollment_date),'totalusers'=>$cohort->data->total_users);
          }
        }else{
          $has_cohorts= false;
        }

        $pages = $total_cohort / $dashboard_per_page;
        if($total_cohort > 10){
          $total_cohort_has = true;
          $select .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'tab', 'value'=>'cohorts'));
          $select .= html_writer::end_tag('input');
          $select .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'id', 'value'=>$learningpath));
          $select .= html_writer::end_tag('input');
          $select .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'page', 'value'=>$page));
          $select .= html_writer::end_tag('input');
          $select .= html_writer::start_tag('select',array('type'=>'text','id'=>'id_cohortsperpage','name'=>'cohortsperpage','class'=>'form-control','style'=>'width:100% !important;height:30px !important;'));
              $vals = array(10,20,30,40,50,60,70,80,90,100);
              foreach ($vals  as $key) {
                  $selectedperpage = '';
                  if($dashboard_per_page == $key ) $selectedperpage = 'selected' ;
                  $select .= html_writer::tag('option',$key, array($selectedperpage=>$selectedperpage));
              }

          $select .= html_writer::end_tag('select');
              if ($pages > 1) {
              $paging_html .= $OUTPUT->paging_bar($total_cohort, $page, $dashboard_per_page, $pageurl);
              }
        }

        $data[] = array('has_cohorts'=>$has_cohorts,'checkbox_html'=>$checkbox_html, 'paging_html'=>$paging_html, 'select'=>$select,'total_cohort_has'=>$total_cohort_has);

        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'data' => $data,
          'user_array' => $user_array
        );
        return $OUTPUT->render_from_template('local_learningpaths/cohorts_list',$template_data);
    }

    /**
    * Return a list of tabs for learning path
    */
    private function get_tabs_list()
    {
        $tabs = [];
        $tabs[] = 'view';
        $tabs[] = 'courses';
        $tabs[] = 'users';
        $tabs[] = 'cohorts';

        $tabs[] = 'notifications';
        return $tabs;
    }

    
    // Build navigation tab code for learning path
    
    public function navigation_tabs()
    {
      global $OUTPUT;
        $active = optional_param('tab', 'view', PARAM_TEXT);
        // Getting tabs list
        $tabs = $this->get_tabs_list();
        // Open tabs list
        // Build tabs
            foreach ($tabs as $tab) {
                $itemClasses = ($active == $tab) ? 'active' : '';
                $tabs_list[] = array('tab'=>$tab,'itemclasses'=>$itemClasses,'tabname'=>get_string($tab, 'local_learningpaths'));
            }
        $templatecontext = array(
          'site_url' => $CFG->wwwroot,
          'tabs' => $tabs_list,
      );
        return $OUTPUT->render_from_template('local_learningpaths/navigation_tabs', $templatecontext);
    }

    /*
    * Build tabs code for learning path
    */
    public function tabs($data) {
      global $OUTPUT;
        $active = optional_param('tab', 'view', PARAM_TEXT);
        // Getting tabs list
        $tabs = $this->get_tabs_list();
        // Open tabs container
        foreach ($tabs as $tab) {
            // Define current tab classes
            $itemClasses = ($active == $tab) ? 'tab-pane fade active in' : 'tab-pane';
            // Build tab
                $tabFullName = "learningpath_{$tab}_tab";
                if (method_exists($this, $tabFullName)) {
                    $class[]= array('itemclasses'=>$itemClasses,'tab'=>$tab,'tabfullname'=>$this->$tabFullName($data));
                } else {
                    // throw new Exception("Coding Error detected. An undefined tab was called", 1);
                }
        }
        // print popups
        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'class' => $class,
          'popups'=>$this->popups($data)

        );
        return $OUTPUT->render_from_template('local_learningpaths/learningpaths_tabs',$template_data);
    }

    /**
    * Printing Learningpaths Dashboard where those are going to be listed for management
    * @param (learningpaths) objects list with learning paths
    */
    function dashboard($learningpaths)
    {       
        global $OUTPUT, $PAGE;
        $page = optional_param('page', 0, PARAM_INT);
        $search = optional_param('search_lp', '', PARAM_TEXT);
        $dashboard_per_page = optional_param('userperpage', 10, PARAM_INT);
        $params['userperpage'] = $dashboard_per_page;
        $params['page'] = $page;
        
        $url = new moodle_url($PAGE->url, $params);
        $selectoutput = "";
        $pageli = "";
        $return_not_records = "";

        if (has_capability('local/learningpaths:create_learning_path', context_system::instance())) {
            $add_learningpath_popup = $this->add_learningpath_popup();
        }
        if( count($learningpaths) <= 0 ) {
          $return_not_records =   get_string('no_records', 'local_learningpaths');
        }
          $li_totals = count($learningpaths);
          $la_index  = array_keys($learningpaths);
          $la_pag_learninpath = array();

          for( $record=($page * $dashboard_per_page); $record < (( $page * $dashboard_per_page ) + $dashboard_per_page) ; $record++ ) {
              if($learningpaths[ $la_index[$record] ]) $la_pag_learninpath[ $la_index[$record] ] = $learningpaths[ $la_index[$record] ];
          }
        
            $learningpaths_list = $this->learningpaths_list($la_pag_learninpath);
            $pages = count($learningpaths) / $dashboard_per_page;
            $active_page = 1;
            if ($li_totals > 10){
              $selectoutput .= html_writer::start_tag('input',array('type'=>'hidden','name'=>'page', 'value'=>$page));
              $selectoutput .= html_writer::end_tag('input');  
              $selectoutput .= html_writer::start_tag('select',array('type'=>'text','id'=>'id_userperpage','name'=>'userperpage','class'=>'form-control','style'=>'width:70px;'));
                $vals = array(10,20,30,40,50,60,70,80,90,100);
                foreach ($vals  as $key) {
                $selectedperpage = '';
                if($dashboard_per_page == $key ) $selectedperpage = 'selected' ;
                    $selectoutput .= html_writer::tag('option',$key, array($selectedperpage=>$selectedperpage));
                }
                $selectoutput .= html_writer::end_tag('select');
            }
            if($search){ 
                $url = new moodle_url($url, array('search_lp' => $search));
            }  
            if ($pages > 1) {
                $pageli .= $OUTPUT->paging_bar(count($learningpaths), $page, $dashboard_per_page,$url);
            }       
        $dashboard[] = array('self_server'=>$_SERVER['PHP_SELF'],'add_learningpath_popup'=>$add_learningpath_popup,'selectoutput'=>$selectoutput,'learningpaths_list'=>$learningpaths_list,'pageli'=>$pageli);

        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'dashboard' => $dashboard,
          'return_not_records' => $return_not_records
        );
        return $OUTPUT->render_from_template('local_learningpaths/dashboard',$template_data);
    }

    public function add_learningpath_popup(){
        // Create a new learning path form object for that, we need to have a learningpath object
        global $CFG,$OUTPUT;
        require_once "{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPath.php";
        $learningpath = new LearningPath();
        $render_form = $learningpath->render_form();

        $add_learningpath_popup[] = array('render_form'=>$render_form);

        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'add_learningpath_popup' => $add_learningpath_popup,
        );
        return $OUTPUT->render_from_template('local_learningpaths/add_learningpath_popup',$template_data);
    }

    /**
    * Return HTML list of learningpath
    */
    public function learningpaths_list($learningpaths)
    {
        // Global objects
        global $USER, $CFG, $DB, $SESSION,$OUTPUT;

        $output = "";
        $has_delete_learning_path = "";
        foreach ($learningpaths as $learningpath) {
          $companylabel = '';
          if($learningpath->companyid > 0 &&  is_siteadmin() && empty($SESSION->currenteditingcompany)){
              $companyname = $DB->get_record('company', ['id' => $learningpath->companyid]);
              $companylabel = '<span class="badge badge-primary companylabel">'.$companyname->name.'</span>';
          }

          $startdate = ($learningpath->startdate)?date('m/d/Y', $learningpath->startdate):get_string('notset', 'local_learningpaths');
          $endate = ($learningpath->enddate)?date('m/d/Y', $learningpath->enddate):get_string('notset', 'local_learningpaths');
                
          if (has_capability('local/learningpaths:delete_learning_path', context_system::instance())) {
            $has_delete_learning_path = true;

          }
            $learningpaths_list[] = array('companylabel'=>$companylabel,
                                    'learningpathname'=>htmlspecialchars_decode($learningpath->name),'learningpathid'=>$learningpath->id,
                                    'startdate'=>$startdate,'endate'=>$endate,'has_delete_learning_path'=>$has_delete_learning_path,'session'=>$USER->sesskey);
        }
        $template_data = array(
          'site_url' => $CFG->wwwroot,
          'learningpaths_list' => $learningpaths_list,
        );
        return $OUTPUT->render_from_template('local_learningpaths/learningpaths_list',$template_data);
    }
    public function getCourseProgress($courseid,$userid)
{
    global $DB, $USER;
    $total_progress = 0 ;

    $course = $DB->get_record('course',array('id'=>$courseid));
    if(empty($course)) {
      return;
    }

    //Get mod info
    $modinfo = get_fast_modinfo($course);
    //Get the completion info of the course
    $info = new completion_info($course);
    $complete = $info->is_course_complete($userid);
    if($complete){
        $total_progress = 100;
    } else{
        //check if the current user is enrolled in the current course
        $my_courses = enrol_get_my_courses();
        $is_enrollled = false;
        if (isset($my_courses[$course->id]->id))
            $is_enrollled = true;

        //If eht completion info is enabled for the site and for the course
        if (completion_info::is_enabled_for_site() && $info->is_enabled()) {
            //Get the completions for current user
            $completions = $info->get_completions($userid);
             // For aggregating activity completion.
            $activities = array();
            $activities_complete = 0;
             // Loop through course criteria.
            foreach ($completions as $completion) {
                //If is a videofile get the progress of the user in the video
                $criteria = $completion->get_criteria();
                $complete = $completion->is_complete();
                // Activities are a special case, so cache them and leave them till last.
                if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                    $activities[$criteria->moduleinstance] = $complete;
                    if ($complete) {
                        $activities_complete++;
                    } else if($completion->get_criteria()->module == 'videofile' ){
                    }
                }
                else if($complete)
                {
                    $activities_complete++;
                }
            }

            if ( count($activities) > 0 ) {
                $total_progress = ($activities_complete / count($activities));
                $total_progress = $total_progress * 100;
            } else {
               $total_progress = 0 ; 
            }
        }
    }

    return $total_progress ;
}
}
