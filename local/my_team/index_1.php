<?php
/**
 * Displays information about all the assignment modules in the requested course
 *
 * @package   local_my_team
 * @author    Jayesh
 */

require_once("../../config.php");
require_once($CFG->dirroot.'/local/my_team/lib.php');

require_login();

$context = context_system::instance();
$access = false;
if ($roles = get_user_roles($context, $USER->id)) {
    foreach ($roles as $role) {
        if($role->shortname == 'manager'){
            $access = true;
            break;
        }
    }
}
// print_object($access); die;
if(!($access || is_siteadmin() || $role->shortname == 'superadmin')){
    print_error('Access denied');
}

global $CFG, $OUTPUT, $PAGE;
$PAGE->requires->css(new moodle_url("/local/my_team/styles.css"));

$PAGE->set_context($context);

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 5, PARAM_INT);
$m = optional_param('m', 0, PARAM_INT);
$s = optional_param('s', null, PARAM_TEXT);

$params = array();
if(!empty($page)){
    $params['page'] = $page;
}
if(!empty($m)){
    $params['m'] = $m;
}
if(!empty($s)){
    $params['s'] = $s;
}

$PAGE->set_url(new moodle_url('/local/my_team/index_1.php', $params));

$pluginname = get_string("pluginname", "local_my_team");
$PAGE->set_title($pluginname);
$PAGE->set_heading($pluginname);

echo $OUTPUT->header();

$manage = new moodle_url("/local/my_team/manage.php");

if ($page < 0) {
    $page = 0;
}

$managers = get_managers();

echo '<div class="header-btns d-flex py-3 justify-content-between">';
    if(is_siteadmin()){
        echo '<div class="d-flex align-items-center">';
            echo get_string('manager', 'local_my_team');
            echo ':&nbsp;<form class="mx-2">
                <select name="m" class="form-control pull-left"  onchange="this.form.submit()">
                    <option value="0">'.get_string('select').'</option>';
                    foreach($managers as $manager){
                        $selected = '';
                        if($m == $manager->id){
                            $selected = 'selected';
                        }
                        echo '<option value="'.$manager->id.'" '.$selected.'>'.$manager->firstname.' '.$manager->lastname.'</option>';
                    }
            echo '</select>
                <noscript><input type="submit" value="Submit"></noscript>
            </div>
        </form>';
    }
    echo '<div class="d-flex align-items-center">
            <form action="'.$PAGE->url.'" class="d-flex">
                <input type="search" name="s" class="form-control pull-left search-team-user mr-2" placeholder="'.get_string('search').'" />
                <input type="submit" class="btn btn-primary mx-2" value="'.get_string('submit').'" />
            </form>
        </div>';
    if(is_siteadmin()){
        echo '<div class="d-flex align-items-center">
                <a href="'.$manage.'" class="btn btn-primary pull-right"><i class="fa fa-cog" aria-hidden="true"></i> '.get_string('manage', 'local_my_team').'</a>
            </div>';
    }
    echo '</div>';

$teamusers = get_teamusers($page, $perpage, $m, $s);
$resultcount = get_teamusers(0, 0, $m, $s);

$table = new html_table();
$table->head = array('User Pic', 'Fullname', 'Email','Department','Designation','Course Enrolled','Course Inprogress', 'Course Completed');
$table->align = array( 'center', 'center', 'center', 'center', 'center', 'center' );
foreach ($teamusers as $record) {
    $id = $record['userid'];
    $pic = $record['profilepic'];
    $fullname = $record['firstname'] .' '.$record['lastname'];
    $email = $record['email'];
    $department = $record['department'];
    $institution = $record['institution'];
    $courseinprogress = $record['courseinprogress'];
    $coursecompleted = $record['coursecompleted'];
    $courseenrolled = $record['courseenrolled'];
  //  $image = '<img src="' . $CFG->wwwroot . '/pluginfile.php/' . $record['id'] . '/user/icon/f3" width="50">';
    /**
     * $status values
     * Course in progress 1
     * Course completed 2
     * Course enrolled 3
     */
    $courseinprogress = get_progress_bar($courseinprogress, $courseenrolled, 1);
    $coursecompleted = get_progress_bar($coursecompleted, $courseenrolled, 2);
    $courseenrolled = get_progress_bar($courseenrolled, $courseenrolled, 3);

    $table->data[] = array('<img src="'.$pic.'" />', $fullname, $email,$department,$institution,$courseenrolled,$courseinprogress, $coursecompleted);
}

echo '<div class="team-users-table">';
echo html_writer::table($table);
if (!$teamusers) {
    echo '<center>No record found</center>';
}
echo '</div>';
echo $OUTPUT->paging_bar(count($resultcount), $page, $perpage, $PAGE->url);

echo $OUTPUT->footer();