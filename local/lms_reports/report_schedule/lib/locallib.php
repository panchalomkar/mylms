<?php
$form_data = $form_writer->get_data_post_slms();

/*
* first validate if the form do post
* and get the values into an array
*
*/
if($form_data && $_POST['submit_wizard_sched'])
{
  
 CreateSchedulingReport($form_data);
}

function CreateSchedulingReport($form_data=array())
{

  global $DB, $USER,$CFG;
    /* gets actual server date time */
    $now = date('d/m/Y');

      /*
      * Objects and arrays declaration
      */
      $obj = new stdClass();
      $objDATA = array();
      $objSCHEDULE = array();
      $objscherepo = new stdClass();
      $objtask = new stdClass();
     
  
      //Get report date
      $reportid = $form_data['reportid'] ;
      $reportdata = $DB->get_records('block_configurable_reports', array('id'=>$reportid));

      //get report name and replace spaces with _ and make it lowercase
      $reponame = $reportdata[$reportid]->name;

      $reponame = strtolower(str_replace(' ','_',$reponame)) ;

        //object for workflow table
      $obj->type = 'scheduling_workflow' ;
      $obj->subtype = NUll ;
      $obj->userid = $USER->id ;
      $objDATA['report']= $reponame;
      $objDATA['label']= $form_data['label'];
      $objDATA['description']= $form_data['description'] ;
      if(!empty($form_data['runsremaining'])) $objSCHEDULE['runsremaining']=$form_data['runsremaining'];
      if(!empty($form_data['frequency']))
      {
        $objSCHEDULE['frequency']=$form_data['frequency'];
      }
      else
      {
        $objSCHEDULE['frequency']='1';
      }
      if(!empty($form_data['frequencytype']))
      {
        $objSCHEDULE['frequencytype']=$form_data['frequencytype'];
      } else
      {
        $objSCHEDULE['frequencytype']='day';
      }
      $objDATA['recurrencetype']='simple';

      if(!empty($form_data['hidden_end_date']))
      {
        $objSCHEDULE['enddate']=strtotime($form_data['hidden_end_date']);
      } 
      $objDATA['schedule']=$objSCHEDULE ;
      $objDATA['format']= $form_data['format'];
      $objDATA['recipients']= $form_data['recipients'];
      $objDATA['message']= $form_data['message'];
      $objDATA['reportid'] = $reportid;
     
      $objDATA['sql']     = ( isset($_SESSION['query_report']) ) ? urlencode($_SESSION['query_report']) : "";
      
       //$obj->data = serialize($objDATA);
       $objcustomsqlp->displayname = $form_data['label'];
       $objcustomsqlp->description = $objDATA['description'];
       $objcustomsqlp->descriptionformat = 1;
       $objcustomsqlp->querysql = urldecode($objDATA['sql']);
      
       $objcustomsqlp->querylimit = 5000;
       $objcustomsqlp->capability = 'moodle/site:config';
       $objcustomsqlp->lastrun = 0;
       $objcustomsqlp->runable = $form_data['run'];
       $objcustomsqlp->singlerow = 0;
       $objcustomsqlp->at = $form_data['at'];
       $objcustomsqlp->emailto = $objDATA['recipients'];
       $objcustomsqlp->emailwhat = 'emailresults';
       $objcustomsqlp->categoryid = $form_data['category'];
       $objcustomsqlp->customdir =  $CFG->dataroot;
       $objcustomsqlp->timemodified = mktime($now) ;
      
     
       $lastinsertidcp = $DB->insert_record('report_customsqlp_queries', $objcustomsqlp);


      //object for scheduling table
      $objscherepo->customsqlid = $lastinsertidcp ;
      $objscherepo->reportid = $reportid ;
      $objscherepo->userid = $USER->id ;

      
      $DB->insert_record('local_reports_schedule', $objscherepo);

  if($lastinsertidcp)
        {

        $message = html_writer::start_tag('div',array('id'=>'floating-top-right','class'=>'floating-container'));
          $message .= html_writer::start_tag('div',array('class'=>'alert-wrap in animated jellyIn'));
              $message .= html_writer::start_tag('div',array('class'=>'alert alert-success','role'=>'alert'));
              $message .= html_writer::start_tag('div',array('class'=>'media'));
                $message .= html_writer::tag('span',get_string('success_scheduling_task_creation','local_lms_reports'));
              $message .= html_writer::end_tag('div');
            $message .= html_writer::end_tag('div');
          $message .= html_writer::end_tag('div');
        $message .= html_writer::end_tag('div');

        echo $message ;
        echo "<script>
          if($('#TimerRedirect'))
          {
            var seconds_left = 5;
            var interval = setInterval(function() {
              document.getElementById('TimerRedirect').innerHTML = --seconds_left;

              if (seconds_left <= 0)
              {
                location.href=M.cfg.wwwroot+'/blocks/configurable_reports/viewreport.php?id=".$reportid."';
                //location.href = M.cfg.wwwroot+'/report/customsqlrap/';
                clearInterval(interval);
              }
            }, 1000);

          }
          </script>";
        die();
        }    

  }

  function report_schreport_runable_options($type = null) {
    if ($type === 'manual') {
        return array('manual' => get_string('manual', 'report_customsqlrap'));
    }
    return array('daily' => get_string('automaticallydaily', 'local_lms_reports'),
                 'weekly' => get_string('automaticallyweekly', 'local_lms_reports'),
                 'monthly' => get_string('automaticallymonthly', 'local_lms_reports')
    );
}


function report_schreport_daily_at_options() {
    $time = array();
    for ($h = 0; $h < 24; $h++) {
        $hour = ($h < 10) ? "0$h" : $h;
        $time[$h] = "$hour:00";
    }
    return $time;
}


function report_schreport_category() {
    
    global $DB;
    $rec = $DB->get_records_menu('report_customsqlp_categories',array());
      $rec[1] = "Miscellaneous";
    return $rec;
}
