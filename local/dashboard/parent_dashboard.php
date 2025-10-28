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
$url = new moodle_url('/local/dashboard/child_dashboard.php');

$PAGE->set_url($url);
$title = 'My Dashboard';
//$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->requires->jquery();
$PAGE->requires->css(new moodle_url('/local/dashboard/assets/app-style.css'));

$PAGE->requires->js(new moodle_url('/local/dashboard/assets/js/app-script.js'), true);

echo $OUTPUT->header();

?>
    <div class="content-wrapper">
        <div class="container-fluid">
        <?php 
        $allusernames = get_all_user_name_fields(true, 'u');
        $usercontexts = $DB->get_records_sql("SELECT c.instanceid, c.instanceid, $allusernames
                                                    FROM {role_assignments} ra, {context} c, {user} u
                                                   WHERE ra.userid = ?
                                                         AND ra.contextid = c.id
                                                         AND c.instanceid = u.id
                                                         AND c.contextlevel = ".CONTEXT_USER, array($USER->id));
       // print_object($usercontexts);
        if ($usercontexts) {

            $text = '<select id="my-child">';
             $text .= '<option value="0">My Dashboard</option>';
            foreach ($usercontexts as $usercontext) {
                $text .= '<option value='.$usercontext->instanceid.'>'.fullname($usercontext).'</option>';
            } 
            $text .='</select>';
            echo $text;
        }
            ?>
            <div class="row mt-3">
                <div class="col-12 ">
                    <div class="col-12" style="height: 2.5rem;background-color: #2b78e4; color:#fff;margin-bottom:10px; padding-top: 10px;">
                        <h6 style=" color:#fff;">Welcome,<?php echo $USER->firstname;?></h3>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="card" style="height: 8rem;background-color: #0066b2; color:#fff;">
                                <div class="card-body" style="margin-top:18px">
                                    <center>
                                        <h2 style="color:#fff;"><?php echo count($usercontexts);?></h2>
                                        <h6 style="color:#fff;">Total Child</h6>
                                    </center>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="card"  style="height: 8rem;background-color: #fdbd10; color:#fff;">
                                <div class="card-body" style="margin-top:18px">
                                    <center>
                                        <h2 style="color:#fff;">Rs. 5,000</h2>
                                        <h6 style="color:#fff;">Total Budget</h6>
                                    </center>
                                </div>
                            </div>
                        </div>

                        

                    </div><!--end row-->
                </div>

            </div>

            <div class="row mt-3">

                <div class="col-12 ">
                    <div class="row">
                        <div class="col-12 col-lg-12 col-xl-12">
                            <div class="card" >
                                <div class="card-body" style="margin-top: 18px;">
                                    <h5 style="color:black !important; text-align: center;">
                                        Child's Classes
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
                                  foreach ($usercontexts as $usercontext) {
                                         $mychild[] = $usercontext->instanceid;
                                  }
                                
                                $sessionList = getchildliveClasses($mychild,"today");

                                ?>
                                    <div>
                                        <table class="table table-sm mycourse table-responsive" >
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
                                     
                                    $nextsessionList = getchildliveClasses($mychild,"upcoming");

                                    ?>
                                    <div>
                                        <table class="table table-sm mycourse table-responsive" >
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
                                     
                                    $allsessionList = getchildliveClasses($mychild,"all");

                                    ?>
                                    <div>
                                        <table class="table table-sm mycourse table-responsive" >
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
                                                
                                                <th class=" text-dark"  >
                                                    Status
                                                </th>
                                            </tr>
                                            <?php foreach ($allsessionList as $session) { 
                                                $liveurl = new moodle_url("/mod/bigbluebuttonbn/bbb_view.php?action=join&id=$USER->id&bn=$session->id");
                                                
                                                ?>
                                            <tr >
                                               <!-- <td class=" text-dark"  >
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
                                                        <a href='#' >Completed</a>
                                                    <?php } else { ?>
                                                        <a href='#' >Not Started</a>
                                                  <?php   } ?>
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
            <div class="row mt-3">

                <div class="col-12 col-lg-8 col-xl-8">
                    <div class="row">
                        <div class="col-12 col-lg-12 col-xl-12">
                            <div class="card" >
                                <div class="card-body" style="margin-top:18px">
                                    <h5 style="color:black !important; text-align: center;">
                                        Budget Trasaction
                                        <hr style="background-color:black;">
                                    </h5>
                                    
                                    <div>
                                        <table class="table table-sm mycourse table-responsive" >
                                            <tr>
                                                <th class=" text-dark">
                                                    Child Name
                                                </th>
                                                <th class=" text-dark">
                                                    Paid Amt
                                                </th>
                                                <th class=" text-dark">
                                                    Paid Date
                                                </th>
                                                <th class=" text-dark"  >
                                                    Status
                                                </th>
                                                
                                                
                                            </tr>
                                            
                                            <tr >
                                                <td class=" text-dark"  >
                                                    Durva K
                                                </td>
                                                <td  >
                                                     Rs. 2,500
                                                </td >
                                                <td  >
                                                   15 Aug 2020
                                                </td >
                                                <td  >
                                                 Paid
                                                </td >
                                               
                                               
                                                
                                            </tr>
                                            <tr >
                                                <td class=" text-dark"  >
                                                    Raj K
                                                </td>
                                                <td  >
                                                     Rs. 2,500
                                                </td >
                                                <td  >
                                                   15 Aug 2020
                                                </td >
                                                <td  >
                                                 Paid
                                                </td >
                                               
                                               
                                                
                                            </tr>
                                           
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!--end row-->
                </div>


                <div class="col-12 col-lg-4 col-xl-4">
                    <div class="row">
                        <div class="col-12 col-lg-12 col-xl-12">
                            <div class="card">
                                <div class="card-body" style="margin-top:18px">
                                    <h5 style="color:black !important; text-align: center;">
                                        My Child's
                                        <hr style="background-color:black;">
                                    </h5>
                                    <div>
                                         <table class="table table-sm table-responsive" style="border:1px solid black;">
                                            <?php 
                                  foreach ($usercontexts as $usercontext) {
                                    ?>
                                            <tr>
                                                <th class=" text-dark" style="background-color:#175D77;color:#FDFFEA !important;">
                                                    <?php echo fullname($usercontext); ?>
                                                </th>
                                                <!--<td style="background-color:#175D77;color:#FFF !important;">
                                                    5th Class
                                                </td >-->
                                                 <td style="background-color:#04b962;color:#FFF !important;">
                                                    Active
                                                </td >
                                            </tr>
                                           <?php } ?>
                                            
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!--end row-->
                </div>
            </div><!--end row-->

                        <div class="row mt-3">

                <div class="col-12 col-lg-8 col-xl-8">
                    <div class="row">
                        <div class="col-12 col-lg-12 col-xl-12">
                            <div class="card" >
                                <div class="card-body" style="margin-top:18px">
                                    <h5 style="color:black !important; text-align: center;">
                                        My Child Progress
                                        <hr style="background-color:black;">
                                    </h5>

                                    <div>
                                        <table class="table table-sm mycourse table-responsive" >
                                            <tr>
                                                <th class=" text-dark"  >
                                                    Child Name 
                                                </th>
                                                <th class=" text-dark"  >
                                                    Course Name 
                                                </th>
                                                
                                                <th class=" text-dark"  >
                                                    Grade
                                                </th>
                                                
                                                <th class=" text-dark"  >
                                                    Progress
                                                </th>
                                            </tr>
                                            <?php foreach ($usercontexts as $usercontext) {
                                            $usercourses = enrol_get_users_courses($usercontext->instanceid);
                                            //echo "<pre>";print_r($usercourses);
                                            //echo "</pre>";
                                            foreach($usercourses as $progress){ ?>
                                            <tr>
                                                <td class=" text-dark"  >
                                                    <?=fullname($usercontext);?>
                                                </td>
                                                <td class=" text-dark"  >
                                                    <?=$progress->fullname;?>
                                                </td>
                                                
                                                <td  >
                                                    <?=get_final_grade($progress->id, $usercontext->instanceid);?>
                                                </td >
                                                 <td  >
                                                    <?php
                                                    $percentage = progress::get_course_progress_percentage($progress, $usercontext->instanceid);
                                                    
                                                ?>
                                                <div class="d-flex">
                                                
                                                   <div class="progress">

                                                  <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage;?>%;" aria-valuenow="<?php echo $percentage;?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>&nbsp;
                                                 <small class="text-right"><?php echo round($percentage);?>%</small>
                                            </div>
                                                </td >
                                            </tr> 
                                         <?php } } ?>       
                                           
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!--end row-->
                </div>


                <div class="col-12 col-lg-4 col-xl-4">
                    <div class="row">
                        <div class="col-12 col-lg-12 col-xl-12">
                            <div class="card">
                                <div class="card-body" style="margin-top:18px">
                                    <h5 style="color:black !important; text-align: center;">
                                        Notice Board
                                        <hr style="background-color:black;">
                                    </h5>
                                    <div>
                                      <table class="table table-sm table-bordered table-responsive" >
                                            <?php
                                         foreach ($usercontexts as $usercontext) {
                                            $allcourses = enrol_get_users_courses($usercontext->instanceid);
                                            
                                             foreach ($allcourses as $course) {
                                                $newsarr = get_course_news_parent($course);

                                                foreach ($newsarr as $key=>$news) { 
                                                    //echo $news->name;
//print_r($news);
                                                    ?>
                                                   
                                                
                                                <tr>
                                                    <td><i class="fa fa-calendar fa-sm"></i>&nbsp;<a href="" data-toggle='tooltip' title='<?php 
                                                    echo strip_tags($news->message);
                                                    ?>'><?php 
                                                    echo $news->subject;
                                                    ?></a><br>
                                                    <small class="float-left"><?php 
                                                    echo $course->fullname;
                                                    ?></small>
                                                    <small class="float-right"><?php 
                                                    echo userdate($news->userdate);
                                                    ?></small></td>
                                                </tr>
                                               <?php } 
                                                } 
                                            }?>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!--end row-->
                </div>
            </div><!--end row-->

            
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
<?php $childurl = new moodle_url('/local/dashboard/child.php');?>
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
