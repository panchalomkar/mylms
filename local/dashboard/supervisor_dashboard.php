<?php
use core_completion\progress;
require_once(__DIR__.'/../../config.php');
global $PAGE, $CFG,$USER, $SESSION;
require_once($CFG->dirroot.'/local/dashboard/lib.php');
require_once(__DIR__.'/../../enrol/externallib.php');
// Security Validations.
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$url = new moodle_url('/local/dashboard/supervisor_dashboard.php');

$PAGE->set_url($url);
$title = 'My Dashboard';
//$PAGE->set_heading($title);
$PAGE->set_title($title);

$PAGE->requires->css(new moodle_url('/local/dashboard/assets/app-style.css'));

$PAGE->requires->js(new moodle_url('/local/dashboard/assets/js/app-script.js'), true);

echo $OUTPUT->header();

?>
    <div class="content-wrapper">
        <div class="container-fluid">
        
            <div class="row mt-3">
                <div class="col-12 ">
                    <div class="col-12" style="height: 2.5rem;background-color: #2b78e4; color:#fff;margin-bottom:10px; padding-top: 10px;">
                        <h6 style=" color:#fff;">Welcome,<?php echo $USER->firstname;?></h3>
                    </div>
                    
                </div>

            </div>

            <div class="row mt-3">

                <div class="col-12 ">
                    <div class="row">
                        <div class="col-12 col-lg-12 col-xl-12">
                            <div class="card" >
                                <div class="card-body" style="margin-top:18px">
                                    <h5 style="color:black !important; text-align: center;">
                                        Courses Live Classes
                                        <hr style="background-color:black;">
                                    </h5>
                                    <ul class="nav nav-tabs" id="myTab" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="today-tab" data-toggle="tab" href="#today" role="tab" aria-controls="today" aria-selected="true">Today Sessions</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="upcoming-tab" data-toggle="tab" href="#upcoming" role="tab" aria-controls="upcoming" aria-selected="false">Upcoming</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="completed-tab" data-toggle="tab" href="#Completed" role="tab" aria-controls="Completed" aria-selected="false">Completed</a>
      </li>
    </ul>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="today" role="tabpanel" aria-labelledby="today-tab">
                                 <?php 
                                 
                                $sessionList = getCousesClasses($COURSE->id, "today");

                                ?>
                                    <div>
                                        <table class="table table-sm timeline" >
                                            <tr>
                                               <!-- <th class=" text-dark">
                                                    Child Name
                                                </th>-->
                                                <th class=" text-dark">
                                                    Course
                                                </th>
                                                <th class=" text-dark">
                                                    Activity
                                                </th>
                                                <th class=" text-dark">
                                                    Teacher
                                                </th>
                                                <th class=" text-dark"  >
                                                    Time
                                                </th>
                                                
                                                <th class=" text-dark"  >
                                                    Status
                                                </th>
                                            </tr>
                                            <?php foreach ($sessionList as $session) { 
                                                $liveurl = new moodle_url("/mod/bigbluebuttonbn/bbb_view.php?action=join&id=$USER->id&bn=$session->id");
                                                $date = new DateTime("now");
        
                                                $da = $date->getTimestamp();
                                                 $now = date('H',$da);
                                                    $start = date("H", $session->openingtime);
                                                    $endtime = date("H", $session->closingtime);
                                                $course = $DB->get_record('course',array('id' =>  $session->course), '*', MUST_EXIST);
                                                $trbg = "";
                                                if ($now >= $start && $now <= $endtime) {
                                                     $trbg = "#abe2c7";
                                                }

                                                ?>
                                            <tr style="background-color: <?=$trbg?>">
                                                <!--<td class=" text-dark"  >
                                                    Durva K
                                                </td>-->
                                                <td class=" text-dark"  >
                                                    <?=$course->fullname;?>
                                                </td>
                                                <td  >
                                                     <?=$session->name;?>
                                                </td >
                                                <td  >
                                                   <?php $participants = json_decode($session->participants);
                                                        foreach($participants as $user){
                                                            if($user->role == 'moderator' &&  $user->selectiontype == 'user'){
                                                                $teacher = $DB->get_record('user',array('id' =>  $user->selectionid), '*', MUST_EXIST);
                                                                    echo $teacher->firstname." ".$teacher->lastname;
                                                            }
                                                        }?>
                                                </td >
                                                <td  >
                                                  <?=date("H:m A",$session->openingtime);?> To<br> <?=date("H:m A",$session->closingtime);?>
                                                </td >
                                               
                                                <td  >
                                                   <?php 

                                                   
                                                   
                                                    if ($now >= $start && $now <= $endtime) { ?>
                                                        <a href='#' >Not Joined</a>
                                                    <?php } else { ?>
                                                        <a href='#' >Not Started</a>
                                                  <?php   } ?>
                                                </td >
                                                
                                            </tr>
                                           <?php } ?>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                                         <?php 
                                     
                                    $nextsessionList = getCousesClasses($COURSE->id, "upcoming");

                                    ?>
                                    <div>
                                        <table class="table table-sm timeline" >
                                            <tr>
                                                <!--<th class=" text-dark">
                                                    Child Name
                                                </th>-->
                                                <th class=" text-dark">
                                                    Course
                                                </th>
                                                <th class=" text-dark">
                                                    Activity
                                                </th>
                                                <th class=" text-dark">
                                                    Teacher
                                                </th>
                                                <th class=" text-dark"  >
                                                    Session Date
                                                </th>
                                                <th class=" text-dark"  >
                                                    Time
                                                </th>
                                                
                                                <th class=" text-dark"  >
                                                    Status
                                                </th>
                                            </tr>
                                            <?php foreach ($nextsessionList as $session) { 

                                                $liveurl = new moodle_url("/mod/bigbluebuttonbn/bbb_view.php?action=join&id=$USER->id&bn=$session->id");
                                                $date = new DateTime("now");
        
                                                $da = $date->getTimestamp();
                                                 $now = date('H',$da);
                                                    $start = date("H", $session->openingtime);
                                                    $endtime = date("H", $session->closingtime);
                                                $course = $DB->get_record('course',array('id' =>  $session->course), '*', MUST_EXIST);
                                                $trbg = "";
                                                if ($now >= $start && $now <= $endtime) {
                                                     $trbg = "#abe2c7";
                                                }
                                                ?>
                                            <tr style="background-color: <?=$trbg?>">
                                                <!--<td class=" text-dark"  >
                                                    Durva K
                                                </td>-->
                                                <td class=" text-dark"  >
                                                    <?=$course->fullname;?>
                                                </td>
                                                <td  >
                                                     <?=$session->name;?>
                                                </td >
                                                <td  >
                                                   <?php $participants = json_decode($session->participants);
                                                        foreach($participants as $user){
                                                            if($user->role == 'moderator' &&  $user->selectiontype == 'user'){
                                                                $teacher = $DB->get_record('user',array('id' =>  $user->selectionid), '*', MUST_EXIST);
                                                                    echo $teacher->firstname." ".$teacher->lastname;
                                                            }
                                                        }?>
                                                </td >
                                                <td>
                                                    <?=date("Y-m-d",$session->openingtime);?>
                                                </td>
                                                <td  >
                                                  <?=date("H:m A",$session->openingtime);?> To<br> <?=date("H:m A",$session->closingtime);?>
                                                </td >
                                               
                                                <td  >
                                                   <?php 

                                                   
                                                   
                                                    if ($now >= $start && $now <= $endtime) { ?>
                                                        <a href='#' >Not Joined</a>
                                                    <?php } else { ?>
                                                        <a href='#' >Not Started</a>
                                                  <?php   } ?>
                                                </td >
                                                
                                            </tr>
                                           <?php } ?>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="Completed" role="tabpanel" aria-labelledby="completed-tab">
                                         <?php 
                                     
                                    $allsessionList = getCousesClasses($COURSE->id, "all");

                                    ?>
                                    <div>
                                        <table class="table table-sm timeline" >
                                            <tr>
                                                <!--<th class=" text-dark">
                                                    Child Name
                                                </th>-->
                                                <th class=" text-dark">
                                                    Course
                                                </th>
                                                <th class=" text-dark">
                                                    Activity
                                                </th>
                                                <th class=" text-dark">
                                                    Teacher
                                                </th>
                                                <th>
                                                    Session Date
                                                </th>
                                                <th class=" text-dark"  >
                                                    Time
                                                </th>
                                                
                                                
                                            </tr>
                                            <?php foreach ($allsessionList as $session) { 
                                                $date = new DateTime("now");
        
                                                $da = $date->getTimestamp();
                                                 $now = date('H',$da);
                                                    $start = date("H", $session->openingtime);
                                                    $endtime = date("H", $session->closingtime);
                                                
                                                ?>
                                            <tr >
                                               <!-- <td class=" text-dark"  >
                                                    Durva K
                                                </td>-->
                                                <td class=" text-dark"  >
                                                    <?=$session->fullname;?>
                                                </td>
                                                <td  >
                                                     <?=$session->name;?>
                                                </td >
                                                <td  >
                                                   <?php $participants = json_decode($session->participants);
                                                        foreach($participants as $user){
                                                            if($user->role == 'moderator' &&  $user->selectiontype == 'user'){
                                                                $teacher = $DB->get_record('user',array('id' =>  $user->selectionid), '*', MUST_EXIST);
                                                                    echo $teacher->firstname." ".$teacher->lastname;
                                                            }
                                                        }?>
                                                </td >
                                               
                                                <td>
                                                    <?=date("Y-m-d",$session->openingtime);?>
                                                </td>
                                                
                                                <td  >
                                                  <?=date("H:m A",$session->openingtime);?> To<br> <?=date("H:m A",$session->closingtime);?>
                                                </td >
                                               
                                               
                                                
                                            </tr>
                                           <?php } ?>
                                        </table>
                                    </div>
                                </div>
                            </div>
                         </div>
                    </div>
                </div>
            </div><!--end row-->
        </div>
    </div>
           

            
        </div>
    </div>
</div>
<!-- End container-fluid-->
</div>
    <style>
        #region-main{
            background: none !important;
            border: none !important;
            padding: 0.25rem !important;
        }
        #page-header{
            display: none !important;
        }
        .nav-tabs .nav-link.active, .nav-tabs .nav-item.show .nav-link{
            border-left: 1px solid;
    border-top: 1px solid;
    border-right: 1px solid;
        }
    </style>
<?php $childurl = new moodle_url('/local/dashboard/supervisor_dashboard.php');?>
    <script type="text/javascript">
        $(document).ready(function($) {
            $("#my-child").on("change", function(event){
                    var userid =  $(this).val();
                    window.location.href = "<?=$childurl?>?id="+userid;
                });
        });
    </script>
<?php
echo $OUTPUT->footer();
