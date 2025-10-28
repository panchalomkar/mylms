<?php
//include simplehtml_form.php
require_once('../../config.php');
global $CFG, $PAGE, $OUTPUT;
require_once($CFG->dirroot.'/local/competency/lib.php');
require_once($CFG->dirroot.'/local/competency/uploadcompetency_form.php');

$activepage = 'uploadcsv';
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_title(get_string('competency_title', 'local_competency'));
$PAGE->set_url($CFG->wwwroot.'/local/competency/uploadcompetency.php');
$PAGE->set_heading(get_string('competency_title', 'local_competency'));
$PAGE->navbar->add(get_string('upload_competency', 'local_competency'));
echo $OUTPUT->header();
//header added
require_once($CFG->dirroot.'/local/competency/header.php');
require_once($CFG->dirroot.'/local/competency/tabs.php');
if (!has_capability('local/competency:uploadcompetency', $context)) {
    redirect($CFG->wwwroot. '/my/', \core\notification::error('No access...'));
    exit();
}
$errormessage1='';
//Instantiate simplehtml_form 
$mform = new uploadcompetency_form();
//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
  //In this case you process validated data. $mform->get_data() returns data posted in form.
 // print_object($fromform);
  if (!has_capability('local/competency:uploadcompetency', $context)) {
      redirect($CFG->wwwroot. '/my/', \core\notification::error('No access...'));
      exit();
  }
  $content = $mform->get_file_content('userfile');
  $pst_role = $fromform->roles;
  $buid = $fromform->bumaster;
  $pst_competency_title = $fromform->competency_title;
 // print_object($content);
  $text = preg_replace('!\r\n?!',"\n",$content);
  $rawlines = explode("\n", $text);
 // print_object($rawlines);
  foreach($rawlines as $k=>$v){
      if($k!=0){
	     if($v!=""){
              //echo $k.'K -'.$v.'V';
			  $time = time();
			  $ev = explode(',',$v);
			  $subcompetency = $ev[0];
			  $sub_subcompetency = $ev[1];
        
        if(!empty($subcompetency)){
    			  
            $checksubcompetency = $DB->get_record('competency_category', array('name' => $subcompetency, 'ctid' => $pst_competency_title, 'buid'=>$buid));
           // print_object($checksubcompetency);
            if(empty($checksubcompetency)){
               $subcompetency = $DB->insert_record('competency_category', array('name' => $subcompetency, 'ctid' => $pst_competency_title, 'buid'=>$buid,'roleid'=>$pst_role, 'timemodified'=>$time, 'timecreated'=>$time)); 
            } else {
              $subcompetency = $checksubcompetency->id;
            }

        }
        $checkcompetencies = $DB->get_records('competencies', array('comptencyname' => $sub_subcompetency, 'ccid' => $subcompetency));
        if(empty($checkcompetencies)){
          $subsubcompetency = $DB->insert_record('competencies', array('comptencyname' => $sub_subcompetency, 'ccid' => $subcompetency, 'timemodified'=>$time, 'timecreated'=>$time));
        }
		  }
      }
  }
  //exit();
	$errormessage ='<p style="color:green;text-align:center;">You have successfully uploaded csv</p>';
 }

if(!empty($errormessage)){ ?> 
    <br/>
    <div class="alert alert-success successmessgae">
    <?php echo $errormessage; ?>
</div> <?php }
  // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
  // or on the first display of the form.
 
  //Set default data (if any)
  $sql = "Select * from {competency_title} where isdeleted!=0";
  $completencyTitles = $DB->get_records_sql($sql, array());
  $mform->set_data($completencyTitles);
  //displays the form
  $mform->display();

$PAGE->requires->js('/local/competency/js/competency.js');
echo $OUTPUT->footer();