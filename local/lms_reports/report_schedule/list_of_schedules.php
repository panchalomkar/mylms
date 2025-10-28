<?php

require_once("../../../config.php");
?>
<style>
.table-striped, .table-striped td
{
  border: 1px solid #333 !important;

}
#mainnav-container ,#navbar
{
  display: none;
}
#content-container
{
  padding: 0 !important;
  margin: 0 !important;
  background-color:#fff !important;
}

#page-local-lms_reports-report_schedule-list_of_schedules #container div#content-container { padding-left:0px !important; }
#page-local-lms_reports-report_schedule-list_of_schedules #page-content #region-main-wrap { padding-left:07px; }
#page-local-lms_reports-report_schedule-list_of_schedules table td a { margin:0px auto;display:block;width: 20px; }

</style>
<script>
<?php

?>
</script>
<?php

$action = optional_param('action','',PARAM_TEXT);
$idtasksche = optional_param('id','',PARAM_INT);

if($action =='del' && $idtasksche > 0 )
{
  $condtion = "scheduled_".$idtasksche ;

  $deletedsched = $DB->delete_records('local_elisreports_schedule',array('id'=>$idtasksche));
  $deletedtasksched = $DB->delete_records('local_eliscore_sched_tasks',array('taskname'=>$condtion));
  $class = 'alert ' ;
  if($deletedtasksched && $deletedsched )
  {
    $textmessage = get_string('delete_schedule_report','block_configurable_reports');
    $class .='alert-success' ;
  }else
  {
    $textmessage =  get_string('error_delete_schedule_report','block_configurable_reports');
    $class .='alert-warning';
  }

  $messaje = html_writer::start_tag('div',array('class'=>'panel-alert'));
    $messaje .= html_writer::start_tag('div',array('class'=>'alert-wrap in'));
      $messaje .= html_writer::start_tag('div',array('class'=>$class));
        /*
	$messaje .= html_writer::start_tag('button',array('class'=>'close', 'type'=>'button'));
          $messaje .= html_writer::tag('i','',array('class'=>'fa fa-times-circle'));
        $messaje .= html_writer::end_tag('button');
	*/
        $messaje .= html_writer::start_tag('div',array('class'=>'media'));
          $messaje .= html_writer::tag('strong',$textmessage,array());
        $messaje .= html_writer::end_tag('div');
      $messaje .= html_writer::end_tag('div');
    $messaje .= html_writer::end_tag('div');
  $messaje .= html_writer::end_tag('div');

  echo html_writer::start_tag('div',array('class'=>'pad-all'));
    echo $messaje ;
  echo html_writer::end_tag('div');

}


$_report_name = $_GET['name'];
$result = $DB->get_records('local_elisreports_schedule',array('report'=>$_report_name)) ;

//
$table = new html_table();


$context = context_system::instance();

if ( has_capability('block/configurable_reports:deleteschedule', $context) ) {
	$table->head = array(get_string('label_repo_schedule','local_lms_reports'),
	get_string('description_repo_schedule','local_lms_reports'),
	get_string('table_next_schedule_table_task','local_lms_reports'),
	get_string('end_run_time_schedule_table_task','local_lms_reports'),
	get_string('options_repo_schedule','local_lms_reports'));

} else {
        $table->head = array(get_string('label_repo_schedule','local_lms_reports'),
        get_string('description_repo_schedule','local_lms_reports'),
        get_string('table_next_schedule_table_task','local_lms_reports'),
        get_string('end_run_time_schedule_table_task','local_lms_reports'));
}

foreach ($result as $schedule => $value) {
  $arrayconfig[] = $config = unserialize($value->config);
  $arrayconfigid[] = $schedule;
}

for($i=0;$i<=count($arrayconfig)-1;$i++)
{
  $finalarray[$i]['lable'] =  $arrayconfig[$i]['label'];
  $finalarray[$i]['description'] =  $arrayconfig[$i]['description'];
  $results = $DB->get_records_sql("SELECT nextruntime FROM {local_eliscore_sched_tasks} WHERE taskname=?", array("scheduled_".$arrayconfigid[$i]));

  foreach ($results as $key => $value) {
    $finalarray[$i]['next_run_time'] = date('d/m/Y',$key);
  }

  if($arrayconfig[$i]['schedule']['enddate']=='')
  {
    $finalarray[$i]['enddate'] = 'Indefinitely' ;
  }
  else
  {
    $finalarray[$i]['enddate'] = date('d/m/Y', $arrayconfig[$i]['schedule']['enddate'] ) ; 
  }


  if (has_capability('block/configurable_reports:deleteschedule', $context) ) {
	$finalarray[$i]['options']="<a href='list_of_schedules.php?id=".$arrayconfigid[$i]."&action=del&name=".$_report_name."'><i class='fa fa-trash'></i></a>";
  }

  $table->data[] = $finalarray[$i];

}
echo $OUTPUT->header();

//  echo html_writer::start_tag('div',array('style'=>'padding:15px;left: -219px; position: relative;'));
echo html_writer::start_tag('div',array('style'=>'padding:15px;position: relative;'));
if($result)
{
  echo html_writer::table($table);
}else
{
  echo html_writer::tag('span',get_string('not_schedule_report','block_configurable_reports'),array());
}
  echo html_writer::end_tag('div');
