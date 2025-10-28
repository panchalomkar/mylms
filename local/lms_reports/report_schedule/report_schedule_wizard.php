<?php

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/lms_reports/class/slms_form.php');
$form_writer = new slms_form();
echo html_writer::tag('script', 'var today_date = "'.date('m/d/Y').'" ;');
require_once($CFG->dirroot . '/local/lms_reports/report_schedule/lib/locallib.php');


//Get report date
$reportid = optional_param('rid','',PARAM_INT) ;
$reportdata = $DB->get_records('block_configurable_reports', array('id'=>$reportid));

//get report name and replace spaces with _ and make it lowercase
$reponame = $reportdata[$reportid]->name;

echo html_writer::start_tag('div', array('class' => 'content-title'));

  $url_report = new moodle_url('/local/lms_reports/');

  echo html_writer::start_tag('a', array('class' => 'nav-item', 'href' => $url_report));


  echo html_writer::start_tag('a', array('class' => 'nav-item reportschedule', 'href' => $url_report));

    echo html_writer::tag('i','', array('class' => 'wid wid-icon-phback-to btn-back'));
  echo html_writer::end_tag('a');

  $title = get_string('schedule_report', 'local_lms_reports');
  echo html_writer::tag('h2', $title. $reponame, array('class' => 'd-inline-block mb-0'));

  echo html_writer::tag('span', '', array('class' => 'descrp-title'));
echo html_writer::end_tag('div');

echo html_writer::start_tag('div',array('class'=>'wizard card', 'id' => 'slms-course-wizard'));
  echo html_writer::start_tag('div',array('class'=>'col-sm-12 px-0'));
    echo html_writer::start_tag('ul', array('class' => 'nav nav-pills px-0','style'=> 'padding: 15px'));

      // Wizard S01
      $label = get_string('label_repo_schedule','local_lms_reports') ;
      echo html_writer::start_tag('li', array('class' => 'nav-item col-sm-3'));
        echo html_writer::tag('a',$label, array('class' => 'nav-link active', 'data-toggle' => 'tab', 'href' => '#slms-course-wizard1'));
      echo html_writer::end_tag('li');

      // Wizard S02
      $label =  get_string('when_repo_schedule','local_lms_reports');
      echo html_writer::start_tag('li', array('class' => 'nav-item col-sm-2'));
        echo html_writer::tag('a',$label, array('class' => 'nav-link', 'data-toggle' => 'tab', 'href' => '#slms-course-wizard2'));
      echo html_writer::end_tag('li');

      // Wizard S04
      $laberun_l = get_string('format_repo_schedule','local_lms_reports');
      echo html_writer::start_tag('li', array('class' => 'nav-item col-sm-2'));
        echo html_writer::tag('a',$laberun_l, array('class' => 'nav-link', 'data-toggle' => 'tab', 'href' => '#slms-course-wizard4'));
      echo html_writer::end_tag('li');
      
      

      // Wizard S05
      $label = get_string('send_repo_schedule','local_lms_reports');
      echo html_writer::start_tag('li', array('class' => 'nav-item col-sm-2'));
        echo html_writer::tag('a',$label, array('class' => 'nav-link', 'data-toggle' => 'tab', 'href' => '#slms-course-wizard5'));
      echo html_writer::end_tag('li');

      // Wizard S06
      $label = get_string('finish_repo_schedule','local_lms_reports');
      $help_text = get_string('label_repo_scd_help', 'local_lms_reports');
      echo html_writer::start_tag('li', array('class' => 'nav-item col-sm-3'));
        echo html_writer::tag('a',$label, array('class' => 'nav-link', 'data-toggle' => 'tab', 'href' => '#slms-course-wizard6'));
      echo html_writer::end_tag('li');

    echo html_writer::end_tag('ul');
  echo html_writer::end_tag('div');

  echo html_writer::start_tag('form', array('class' => 'form-horizontal mform col-sm-12','action' => $CFG->wwwroot . '/local/lms_reports/report_schedule/', 'method' => 'POST','name'=>'form-wizard-reports','id'=>'form-wizard-reports'));
      
    echo html_writer::tag('input','',array('value'=>optional_param('rid', '', PARAM_INT),'type'=>'hidden','name'=>'reportid'));

    echo html_writer::start_tag('div', array('class' => 'tab-content '));

      // Content wizard1
      echo html_writer::start_tag('div', array('id' => 'slms-course-wizard1','class' => 'tab-pane active'));
        echo $form_writer->fieldTextGroupRequired(get_string('label_repo_scd', 'local_lms_reports'),true, array('type' => 'text', 'id'=>'label_id','name' => 'label', 'class' => 'form-control','style'=>'width:100%;'),$help_text);
   
        echo $form_writer->fieldTextareaGroupCourseWizard(get_string('description', 'local_lms_reports'), array('name' => 'description', 'class' => 'form-control schedule_report','id'=>'description_id' ,'value'=>''));
        $categories = report_schreport_category();
        echo $form_writer->fieldSelectGroup(get_string('reportcategoty','local_lms_reports'), array('class'=>'form-control','name'=>'category' ), $categories);
      echo html_writer::end_tag('div');

      // Content wizard2
        echo html_writer::start_tag('div', array('id' => 'slms-course-wizard2','class' => 'tab-pane fade'));
        echo html_writer::start_tag('div',array('id'=>'custom_elements_send','class'=>''));
        $runnableoptions = report_schreport_runable_options();
        echo $form_writer->fieldSelectGroup(get_string('run','local_lms_reports'), array('class'=>'form-control','name'=>'run','id'=>'runid' ), $runnableoptions,'reportschedule');
          
        $dailyoptions = report_schreport_daily_at_options();
        echo $form_writer->fieldSelectGroup(get_string('at','local_lms_reports'), array('class'=>'form-control','name'=>'at','id'=>'atid' ), $dailyoptions,'reportschedule');
        echo html_writer::end_tag('div');
              
          echo html_writer::start_tag('div', array('class'=>'form-group'));
            echo html_writer::start_tag('div', array('class'=>'col-lg-12'));
            echo html_writer::end_tag('div');
          echo html_writer::end_tag('div');

      echo html_writer::end_tag('div');

      // Content wizard4
      echo html_writer::start_tag('div', array('id' => 'slms-course-wizard4','class' => 'tab-pane fade'));
        
        echo html_writer::start_tag('div',array('class'=>'form-group row fitem'));
          echo html_writer::start_tag('div',array('class'=>'contentlabel col-sm-12 col-md-4 col-lg-4 col-xl-4'));
            echo html_writer::start_tag('label', array('class' => 'form-normal form-slms col-form-label  d-inline-block '));
             //echo html_writer::tag('span',get_string('format_repo_schedule_input','local_lms_reports'));
     
              echo  html_writer::end_tag('span');
            echo html_writer::end_tag('label');
          echo html_writer::end_tag('div');
        
          echo html_writer::start_tag('div',array('class'=>'col-sm-12 col-md-8 col-lg-8 col-xl-8 felement wiz_fourtab'));
          echo html_writer::tag('span',get_string('format_repo_schedule_input','local_lms_reports'));
            echo $form_writer->fieldSimpleRadioButton('format', 'checked',' CSV','xls');
            echo html_writer::tag('div', '', array('class' => 'clearfix'));
          echo html_writer::end_tag('div');

        echo html_writer::end_tag('div');
      echo html_writer::end_tag('div');

      // Content wizard5
      echo html_writer::start_tag('div', array('id' => 'slms-course-wizard5','class' => 'tab-pane fade'));
      $help_text = get_string('recipients_repo_schedule_help', 'local_lms_reports');
      
     
      echo $form_writer->fieldTextGroupRequired(get_string('recipients', 'local_lms_reports'),true, array('type' => 'text', 'name' => 'recipients','id'=>'receipent_id', 'class' => 'form-control','style'=>'width:100%;'),$help_text);   
        

          
        echo $form_writer->fieldTextareaGroupCourseWizard(get_string('message', 'local_lms_reports'), array('name' => 'message', 'class' => 'form-control schedule_report','id'=>'message_id'));
         
      echo html_writer::end_tag('div');
      
      // Content wizard6
      echo html_writer::start_tag('div', array('id' => 'slms-course-wizard6','class' => 'tab-pane fade'));
        echo html_writer::start_tag('div',array('class'=>'form-group'));
          echo html_writer::start_tag('div',array('class'=>'col-lg-12'));
            echo html_writer::start_tag('div',array('id'=>'final-data-form','style'=>'text-align:center'));
            echo html_writer::end_tag('div');
          echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

        echo html_writer::start_tag('div', array('class'=>'form-group'));
          echo html_writer::start_tag('div', array('class'=>'col-lg-12'));
          echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
      echo html_writer::end_tag('div');

      echo html_writer::start_tag('div',array('class'=>'panel-footer text-right','style'=>'background-color: #fff !important;border: none; padding-top:0px'));
        
        echo html_writer::start_tag('div',array('class'=>'box-inline schedule-buttons'));
          
          echo html_writer::tag('button', get_string('previous_button','local_lms_reports'), array('class' => 'previous btn bg-slms-foreground-forced btn-round btn-primary','type'=>'button'));
          
          echo html_writer::tag('button', get_string('next_button','local_lms_reports'), array('class' => 'next btn bg-slms-foreground-forced btn-round btn-primary','type'=>'button','style'=>'display: inline-block;margin-left:5px'));

          echo html_writer::tag('input','',array('name'=>'submit_wizard_sched','value'=>get_string('finish_button','local_lms_reports'),'type'=>'submit','class' => 'finish btn bg-slms-foreground-forced btn-round btn-primary','style'=>'display: none;margin-left:5px'));

        echo html_writer::end_tag('div');

        echo html_writer::tag('div', get_string('required_fields','local_lms_reports') ,array('class'=>'text-danger text-bold hidden','id'=>'text-danger-mandatory-fields'));
      echo html_writer::end_tag('div');
    
    echo html_writer::end_tag('div');

  echo html_writer::end_tag('form');
echo html_writer::end_tag('div');
