<?php
use core_completion\progress;
require_once(__DIR__.'/../../config.php');
global $PAGE, $CFG,$USER, $SESSION;
require_once($CFG->dirroot.'/local/dashboard/lib.php');
require_once(__DIR__.'/../../enrol/externallib.php');
// Security Validations.
require_login();
$userid = optional_param('id', '', PARAM_INT);
$context = context_system::instance();
$PAGE->set_context($context);
$url = new moodle_url('/local/dashboard/child_dashboard.php');
$PAGE->set_url($url);
$title = 'My Dashboard';
//$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('local_dashboard/dashboard', 'init');

$PAGE->requires->css(new moodle_url('/local/dashboard/assets/app-style.css'));

$PAGE->requires->js(new moodle_url('/local/dashboard/assets/js/app-script.js'), true);
$user = $DB->get_record("user", array("id" => $userid));
echo $OUTPUT->header();
$enrolled_courses= count(getCourseEnrolledCount($userid));
 $total_courses = $DB->get_record_sql('SELECT count(course) as total_course FROM {course_completion_crit_compl}   WHERE userid = ?', array($userid));
            $total_courses = $total_courses->total_course;
            $count_complete_courses = $total_courses;
            // End completed courses.
            // Course criteria not set.
            // $course_criteria_not_set=count_course_criteria($USER->id);
            $count = 0;
            $courses = enrol_get_users_courses($userid, false, 'id, shortname, showgrades');
            if ($courses) {
                $course_criteria_ns = array();
                static $undefined_courses;
                foreach ($courses as $course) {
                    $exist = $DB->record_exists('course_completion_criteria', array('course' => $course->id));
                    if (!$exist) {
                        $count++;
                        $course_criteria_ns[] = $course->id;
                        $undefined_courses .= $course->id . ",";
                    }
                }
            }
            $course_criteria_not_set = $count;
$courses_inprogress = abs(($enrolled_courses) - ($count_complete_courses + $course_criteria_not_set));
$courses_notstarted= $course_criteria_not_set;
$numcoursescompleted = $count_complete_courses ;

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
        if ($usercontexts) {
             $selected = "";
            
            $text = '<select id="my-child">';
             $text .= '<option value="0">My Dashboard</option>';
            foreach ($usercontexts as $usercontext) {
                if($usercontext->instanceid == $userid){
                    $selected = "selected" ;
                    $text .= '<option value='.$usercontext->instanceid.' selected='.$selected.'>'.fullname($usercontext).'</option>';
                } else {
                    $text .= '<option value='.$usercontext->instanceid.'>'.fullname($usercontext).'</option>';
                }
                
            } 
            $text .='</select>';
            echo $text;
        }
            ?>
            <div class="row mt-3">
                <div class="col-12 ">
                    <div class="col-12" style="height: 2.5rem;background-color: #2b78e4; color:#fff;margin-bottom:10px; padding-top: 10px;">
                        <h6 style=" color:#fff;">Welcome To , <?=$user->firstname;?> Dashboard</h3>
                    </div>
                    <div class="row">
                        <div class="col-3">
                            <div class="card" style="height: 8rem;background-color: #0066b2; color:#fff;">
                                <div class="card-body" style="margin-top:18px">
                                    <center>
                                        <h2 style="color:#fff;"><?=$enrolled_courses;?></h2>
                                        <h6 style="color:#fff;">Total Enrolled Courses</h6>
                                    </center>
                                </div>
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="card"  style="height: 8rem;background-color: #fdbd10; color:#fff;">
                                <div class="card-body" style="margin-top:18px">
                                    <center>
                                        <h2 style="color:#fff;"><?=count($courses_inprogress);?></h2>
                                        <h6 style="color:#fff;">Total inprogress</h6>
                                    </center>
                                </div>
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="card"  style="height: 8rem;background-color: #ec1c24; color:#fff;">
                                <div class="card-body" style="margin-top:18px">                                    
                                    <center>
                                        <h2 style="color:#fff;"><?=$courses_notstarted?></h2>
                                        <h6 style="color:#fff;">Not Started</h6>
                                    </center>
                                </div>
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="card"  style="height: 8rem;background-color: #00796b; color:#fff;">
                                <div class="card-body" style="margin-top:18px">
                                    <center>
                                        <h2 style="color:#fff;"><?=$numcoursescompleted?></h2>
                                        <h6 style="color:#fff;">Total Course Completed</h6>
                                    </center>
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
                                        <?=ucfirst($user->firstname);?> Today's Timeline
                                        <hr style="background-color:black;">
                                    </h5>
                                    <?php 
                                    $sessionList = getliveClasses($userid,"today");

                                    ?>
                                    <div>
                                        <table class="table table-sm mycourse table-responsive" >
                                            <tr>
                                                <th class=" text-dark">
                                                    Course
                                                </th>
                                                <th class=" text-dark">
                                                    Session
                                                </th>
                                                <th class=" text-dark">
                                                    Teacher
                                                </th>
                                                <th class=" text-dark"  >
                                                    Time
                                                </th>
                                                
                                                <th class=" text-dark"  >
                                                    Access
                                                </th>
                                            </tr>
                                            <?php foreach ($sessionList as $session) { 
                                                $liveurl = "";
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
                                                        <a href='<?=$liveurl;?>' class="btn btn-sm btn-success">Join Session</a>
                                                    <?php
                                                    } elseif ($now > $endtime ) { 
                                                        echo "Completed";
                                                    } else { ?>
                                                        <a href='<?=$liveurl;?>' class="btn btn-sm btn-success disabled">Not Started</a>
                                                  <?php   } ?>
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


                <div class="col-12 col-lg-4 col-xl-4">
                    <div class="row">
                        <div class="col-12 col-lg-12 col-xl-12">
                            <div class="card">
                                <div class="card-body" style="margin-top:18px">
                                    <h5 style="color:black !important; text-align: center;">
                                        <?=ucfirst($user->firstname);?> Attendance
                                        <hr style="background-color:black;">
                                    </h5>
                                    <div>
                                        <?php $attendance = get_user_courses_attendances($userid);
                                            
                                                $html = "<select class='form-control' id='attend_course' data-user=".$userid.">";
                                                $html .="<option value='0'>Select Course</option>";
                                                foreach($attendance as $att){
                                                   $html .="<option value=".$att->attid.">".$att->coursefullname."</option>";
                                                }
                                                $html .= "</select>";
                                                echo $html;
                                            ?>
                                         
                                            <div id="attend"></div>
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
                                        <?=ucfirst($user->firstname);?> Progress
                                        <hr style="background-color:black;">
                                    </h5>

                                    <div>
                                        <table class="table table-sm mycourse table-responsive" >
                                            
                                            <tr>
                                                <th class=" text-dark"  >
                                                    Course
                                                </th>
                                                <th>
                                                    Live Sessions
                                                </th>
                                                <th class=" text-dark"  >
                                                    Activity
                                                </th>
                                                <th class=" text-dark"  >
                                                    Grade
                                                </th>
                                                
                                                <th class=" text-dark"  >
                                                    Progress
                                                </th>
                                            </tr>
                                            <?php 
                                            $usercourses = enrol_get_users_courses($userid);
                                            //echo "<pre>";print_r($usercourses);
                                            //echo "</pre>";
                                            foreach($usercourses as $progress){ ?>
                                            <tr>
                                                <td class=" text-dark"  >
                                                     <?php echo $progress->fullname;?>
                                                </td>
                                                <td>
                                                    <?php echo gettotalCoursemod($progress->id, 'bigbluebuttonbn');?>
                                                </td>
                                                <td  >
                                                    <?php echo activityprogress($progress,$userid); ?>
                                                </td >
                                                <td  >
                                                <?=get_final_grade($progress->id, $userid);?>
                                                </td >
                                                <td  >
                                                    <?php
                                                    $percentage = progress::get_course_progress_percentage($progress, $userid);
                                                    
                                                ?>
                                                <div class="d-flex">
                                                
                                                   <div class="progress">

                                                  <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage;?>%;" aria-valuenow="<?php echo $percentage;?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                 <small class="text-right"><?php echo round($percentage);?>%</small>
                                            </div>
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


                <div class="col-12 col-lg-4 col-xl-4">
                    <div class="row">
                        <div class="col-12 col-lg-12 col-xl-12">
                            <div class="card">
                                <div class="card-body"style="margin-top:18px">
                                    <h5 style="color:black !important; text-align: center;">
                                        <?=ucfirst($user->firstname);?> Report
                                        <hr style="background-color:black;">
                                    </h5>
                                    <div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="card" style="height: 5rem;background-color: #0066b2; color:#fff;">
                                                    <div class="card-body p-1">
                                                        <center>
                                                            <strong style="color:#fff;">Current Login</strong>
                                                            <p style="color:#fff;"><?=userdate($user->currentlogin);?></p>
                                                            
                                                        </center>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-6">
                                                <div class="card"  style="height: 5rem;background-color: #fdbd10; color:#fff;">
                                                    <div class="card-body p-1">
                                                        <center>
                                                            <strong style="color:#fff;">Last Login</strong> 
                                                            <p style="color:#fff;"><?=userdate($user->lastlogin);?></p>
                                                            
                                                        </center>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <table class="table table-sm table-bordered table-responsive" >
                                            <?php 
                                            $gradeoverview = new moodle_url("/grade/report/overview/index.php?userid=$userid&id=1");
                                            $grade = new moodle_url("/course/user.php?mode=grade&id=1&user=$userid");
                                            ?>
                                                
                                                <tr>
                                                    <td><a href="<?=$gradeoverview;?>">Grades overview</a></td>
                                                </tr>
                                                 <tr>
                                                    <td><a href="<?=$grade;?>">Grade Report</a></td>
                                                </tr>
                                               
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
    </style>
    <?php $childurl = new moodle_url('/local/dashboard/child.php?');
    $parenturl = new moodle_url('/local/dashboard/parent_dashboard.php');?>
    <script type="text/javascript">
        $(document).ready(function($) {
            $("#my-child").on("change", function(event){
                    var userid =  $(this).val();
                    if(userid > 0){
                        window.location.href = "<?=$childurl?>?id="+userid;
                    } else {
                        window.location.href = "<?=$parenturl?>";
                    }
                    
                });
        });
    </script>
<?php
echo $OUTPUT->footer();
