<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * People plugin main page - Updated for Moodle 5.0.2 + REMUI Theme 5
 *
 * @package    local_people
 * @copyright  2024 People Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG, $SESSION, $PAGE, $USER, $DB, $OUTPUT;

require_once('../../config.php');
require_once('classes/user_message_form.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/user/filters/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/enrol/locallib.php');
require_once($CFG->dirroot . '/local/people/lib.php');

require_login();

$PAGE->requires->css(new moodle_url('/local/people/css/styles.css'));

$sitecontext  = context_system::instance();
$viewallusers = has_capability('local/people:viewallusers', $sitecontext);

if (false == $viewallusers) {
    throw new moodle_exception('cannotviewallusers', 'local_people');
}

// Build enrolment options without REMUI widget dependency.
$title_role   = get_string('role', 'local_people');
$options      = enroll_display_roles_bulk();
$list         = get_courses_display();
$title_course = get_string('courses', 'local_people');

$select_options_html = '';
foreach ($options as $k => $v) {
    $select_options_html .= '<option value="' . s($k) . '">' . s($v) . '</option>';
}
$select_html = '<select id="selectroles" name="role" class="form-control people-select">' . $select_options_html . '</select>';

$course_options_html = '';
foreach ($list as $k => $v) {
    $course_options_html .= '<option value="' . s($k) . '">' . s($v) . '</option>';
}
$multiselect_html = '<select id="selectcourses" name="course[]" multiple class="form-control people-select" size="8">' . $course_options_html . '</select>';

$inputs = sesskey();
$hash['inputs']      = $inputs;
$hash['select']      = $select_html;
$hash['multiselect'] = $multiselect_html;

// Load lang strings for JS.
$stringman = get_string_manager();
$strings   = $stringman->load_component_strings('local_people', 'en');
$PAGE->requires->strings_for_js(array_keys($strings), 'local_people');

// Load plain JS file.
$PAGE->requires->js(new moodle_url('/local/people/js/people.js'), false);

// Page parameters.
$page      = optional_param('page', 0, PARAM_INT);
$sortfield = optional_param('sort', '', PARAM_TEXT);
$order     = optional_param('order', 'ASC', PARAM_TEXT);

$confirmuser     = optional_param('confirmuser', 0, PARAM_INT);
$sendconfirmuser = optional_param('sendconfirmuser', 0, PARAM_INT);
$delete          = optional_param('delete', 0, PARAM_INT);
$suspend         = optional_param('suspend', 0, PARAM_INT);
$unsuspend       = optional_param('unsuspend', 0, PARAM_INT);
$companyid       = optional_param('company', '', PARAM_ALPHANUM);
$confirm         = optional_param('confirm', '', PARAM_ALPHANUM);

if (isset($_REQUEST['userperpage'])) {
    $SESSION->userperpage = clean_param($_REQUEST['userperpage'], PARAM_INT);
}

$return    = array('order' => $order, 'company' => $companyid, 'sort' => $sortfield);
$return    = array_filter($return);
$returnurl = new moodle_url('/local/people/index.php', array_filter($return));
$sesskey   = optional_param('sesskey', null, PARAM_RAW);
if ($sesskey && isset($SESSION->return)) {
    $returnurl = $SESSION->return;
}

// ========================== ACTION DELETE ==========================
if ($delete and confirm_sesskey()) {
    require_capability('moodle/user:delete', $sitecontext);
    $user = $DB->get_record('user', array('id' => $delete, 'mnethostid' => $CFG->mnet_localhost_id), '*', MUST_EXIST);
    if (is_siteadmin($user->id)) {
        throw new moodle_exception('useradminodelete', 'error');
    }
    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        $fullname   = fullname($user, true);
        echo $OUTPUT->heading(get_string('deleteuser', 'admin'));
        $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$fullname'"),
            new moodle_url($returnurl, $optionsyes), $returnurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted() and !$user->deleted) {
        if (delete_user($user)) {
            \core\session\manager::gc();
            redirect($returnurl);
        } else {
            \core\session\manager::gc();
            echo $OUTPUT->header();
            echo $OUTPUT->notification(get_string('deletednot', '', fullname($user, true)));
        }
    }
}

// ========================== ACTION SUSPEND ==========================
if ($suspend and confirm_sesskey()) {
    require_capability('moodle/user:update', $sitecontext);
    if ($user = $DB->get_record('user', array('id' => $suspend, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0))) {
        if (!is_siteadmin($user) and $USER->id != $user->id and $user->suspended != 1) {
            $user->suspended = 1;
            \core\session\manager::kill_user_sessions($user->id);
            user_update_user($user, false);
        }
    }
    redirect($returnurl);
}

// ========================== ACTION UNSUSPEND ==========================
if ($unsuspend and confirm_sesskey()) {
    require_capability('moodle/user:update', $sitecontext);
    if ($user = $DB->get_record('user', array('id' => $unsuspend, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0))) {
        if ($user->suspended != 0) {
            $user->suspended = 0;
            user_update_user($user, false);
        }
    }
    redirect($returnurl);
}

if (isset($_POST['bulk_action'])) {
    $json   = $_POST['bulk_action'];
    $params = json_decode($json);
    if ($params && isset($params->name) && isset($params->data)) {
        bulk_action($params->name, $params->data);
    }
}

// ========================== ACTION ENROLL ==========================
$action = optional_param('action', null, PARAM_ALPHA);
if ($action == 'enroll' and confirm_sesskey()) {
    $roleid           = required_param('role', PARAM_INT);
    $selected_users   = required_param_array('id', PARAM_INT);
    $selected_courses = required_param_array('course', PARAM_INT);
    if ($selected_users && $selected_courses && $roleid) {
        foreach ($selected_courses as $courseid) {
            $course    = get_course($courseid);
            $startdate = optional_param('startdate', '', PARAM_RAW);
            $enddate   = optional_param('enddate', '', PARAM_RAW);
            $recovergrades = 0;
            if (empty($roleid)) { $roleid = null; }
            if ($startdate) {
                $timestart = strtotime($startdate);
            } else {
                $today     = time();
                $timestart = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);
            }
            $timeend   = $enddate ? strtotime($enddate) : 0;
            $manager   = new course_enrolment_manager($PAGE, $course);
            $plugins   = $manager->get_enrolment_plugins(true);
            $instances = $manager->get_enrolment_instances();
            $enrolid   = null;
            foreach ($instances as $instance) {
                if ($instance->enrol == 'manual') { $enrolid = $instance->id; break; }
            }
            if ($enrolid === null) { continue; }
            $instance = $instances[$enrolid];
            if (!isset($plugins[$instance->enrol])) { continue; }
            $plugin  = $plugins[$instance->enrol];
            $context = context_course::instance($course->id);
            foreach ($selected_users as $userid) {
                $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
                if ($plugin->allow_enrol($instance) && has_capability('enrol/' . $plugin->get_name() . ':enrol', $context)) {
                    $plugin->enrol_user($instance, $user->id, $roleid, $timestart, $timeend, null, $recovergrades);
                }
            }
        }
    }
}

// ========================== ACTION CONFIRM ==========================
if ($confirmuser and confirm_sesskey()) {
    require_capability('moodle/user:update', $sitecontext);
    if (!$user = $DB->get_record('user', array('id' => $confirmuser))) {
        throw new moodle_exception('nousers');
    }
    $auth   = get_auth_plugin($user->auth);
    $result = $auth->user_confirm($user->username, $user->secret);
    if ($result == AUTH_CONFIRM_OK or $result == AUTH_CONFIRM_ALREADY) {
        redirect($returnurl);
    } else {
        echo $OUTPUT->header();
        redirect($returnurl, get_string('usernotconfirmed', '', fullname($user, true)));
    }
}

if ($sendconfirmuser and confirm_sesskey()) {
    require_capability('moodle/user:update', $sitecontext);
    if (!$user = $DB->get_record('user', array('id' => $sendconfirmuser))) {
        throw new moodle_exception('nousers');
    }
    \core\event\user_created::create_from_userid($user->id)->trigger();
    if (!send_confirmation_email($user)) {
        redirect($returnurl, get_string('auth_emailnoemail', 'auth_email'), null, \core\output\notification::NOTIFY_ERROR);
        die();
    }
}

// ========================== FILTER PROCESSING ==========================
$filter      = new stdClass();
$json        = isset($_POST['filters']) ? $_POST['filters'] : '';
$params      = json_decode($json);
$multifilter = array();

if (!empty($params)) {
    foreach ($params as $param) {
        $filter = new stdClass();
        if (isset($param->textfilter)) {
            if ($param->{$param->textfilter} != "") {
                $filter->field = $param->textfilter;
                $filter->value = $param->{$param->textfilter};
                $filter->type  = 'textfilter';
                $filter->page  = 0;
            }
        } else if (isset($param->selectfilter)) {
            if ($param->{$param->selectfilter . 'op'} != "") {
                $filter->field = $param->selectfilter;
                $filter->op    = $param->{$param->selectfilter . 'op'};
                $filter->page  = 0;
                $filter->type  = 'selectfilter';
            }
        } else if (isset($param->datefilter)) {
            if ($param->{$param->datefilter . 'gt'} != "") {
                $filter->field  = $param->datefilter;
                $filter->gt     = $param->{$param->datefilter . 'gt'};
                $filter->lt     = $param->{$param->datefilter . 'lt'};
                $filter->access = isset($param->neveraccess) ? $param->neveraccess : null;
                $filter->edited = isset($param->nevermodified) ? $param->nevermodified : null;
                $filter->type   = 'datefilter';
                $filter->page   = $page;
            }
            if (isset($param->neveraccess)) {
                $filter->field  = $param->datefilter;
                $filter->edited = $param->neveraccess;
                $filter->type   = 'datefilterr';
                $filter->sm     = 'date';
                $filter->page   = $page;
            }
            if (isset($param->nevermodified)) {
                $filter->field  = $param->datefilter;
                $filter->edited = $param->nevermodified;
                $filter->type   = 'datefilterr';
                $filter->sm     = 'date';
                $filter->page   = $page;
            }
        } else if (isset($param->multipleselectfilter)) {
            if (!empty($param->{$param->multipleselectfilter . 'op[]'})) {
                $filter->field = $param->multipleselectfilter;
                $filter->ar    = $param->{$param->multipleselectfilter . 'op[]'};
                $filter->op    = "'" . implode("','", $filter->ar) . "'";
                $filter->type  = 'multipleselectfilter';
                $filter->page  = $page;
            }
        } else if (isset($param->systemrole)) {
            if ($param->{$param->systemrole . 'op'} != "") {
                $filter->field = $param->systemrole;
                $filter->op    = $param->{$param->systemrole . 'op'};
                $filter->type  = 'systemrole';
                $filter->page  = $page;
            }
        } else if (isset($param->courserole)) {
            if ($param->{"courserolename[]"} != "") {
                $filter->field = $param->courserole;
                $filter->ar    = $param->{"courserolename[]"};
                $filter->op    = "'" . implode("','", $filter->ar) . "'";
                $filter->role  = $param->courseroleop;
                $filter->type  = 'courserole';
                $filter->page  = $page;
            }
        } else {
            $la_attribute = get_object_vars($param);
            $lc_attribute = array_keys($la_attribute)[0];
            if ($lc_attribute != "" && $lc_attribute != "clearall") {
                $filter->field = $lc_attribute;
                $filter->value = $param->{$lc_attribute};
                $filter->type  = 'textfilter';
                $filter->page  = 0;
            }
        }
        if (isset($filter->field) && $filter->field != "clearall") {
            $multifilter[$filter->field] = $filter;
        }
    }
}

// SQL injection protection on sort.
$filter = new stdClass();
$allowed_sorts  = ['firstname', 'lastname', 'lastaccess'];
$allowed_orders = ['ASC', 'DESC'];
if (in_array($sortfield, $allowed_sorts) && in_array(strtoupper($order), $allowed_orders)) {
    $filter->sort  = $sortfield;
    $filter->order = strtoupper($order);
} else {
    $filter->sort  = 'firstname';
    $filter->order = 'ASC';
}

if (isset($params) && !is_null($params)) {
    $SESSION->multifilter = $multifilter;
    $SESSION->filter      = $filter;
}
if (isset($_POST['clearall'])) {
    unset($SESSION->multifilter);
}
if (isset($SESSION->filter) && !(isset($filter->type) || isset($filter->sort))) {
    $filter = $SESSION->filter;
}

$multifilter = isset($SESSION->multifilter) ? $SESSION->multifilter : array();

if (isset($_GET['filter'])) {
    unset($filter->type, $filter->field, $multifilter);
    unset($SESSION->multifilter);
}

$filter->userperpage = isset($SESSION->userperpage) ? $SESSION->userperpage : 20;
$filter->page        = $page;

// ========================== PAGE SETUP ==========================
$PAGE->set_url(new moodle_url('/local/people/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_people'));
$PAGE->set_heading(get_string('pluginname', 'local_people'));
$PAGE->set_pagelayout('base');

$PAGE->navbar->add('Home', new moodle_url('/my'));
$PAGE->navbar->add(get_string('pluginname', 'local_people'), '/local/people/index.php');

echo $OUTPUT->header();

$enrollmodal_data = [
    'title'       => get_string('enrol_users', 'local_people'),
    'sesskey'     => sesskey(),
    'wwwroot'     => $CFG->wwwroot,
    'enrollform'  => $OUTPUT->render_from_template('local_people/form_enroll', $hash),
    'footerform'  => $OUTPUT->render_from_template('local_people/footer', []),
];

$hash['buttonblock'] = get_first_rowsearch_buttons_block($filter, $multifilter, $companyid);
$hash['filterblock'] = get_filter_block($filter, $multifilter);
$hash['userblock']   = get_user_block($filter, $multifilter, $sortfield, $order);

echo $OUTPUT->render_from_template('local_people/course_wizard', $hash);

if (is_siteadmin()) {
    get_message_form_html()->display();
}

echo '<script>window.PEOPLE_PLUGIN = ' . json_encode($enrollmodal_data) . ';</script>';
?>
<style>
.bulk-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.bulk-modal {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    width: 420px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.bulk-modal h3 {
    color: #003152;
    margin-bottom: 10px;
}
.bulk-modal p {
    margin-bottom: 20px;
    color: #555;
}
.bulk-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
}
.btn-confirm {
    background: #ec9707;
    color: #fff;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
}
.btn-cancel {
    background: #003152;
    color: #fff;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
}
</style>

<div id="bulkModal" class="bulk-modal-overlay">
    <div class="bulk-modal">
        <h3>⚠ Confirm Action</h3>
        <p id="bulkModalText"></p>

        <div class="bulk-actions">
            <button id="bulkConfirmBtn" class="btn-confirm">Yes</button>
            <button onclick="closeBulkModal()" class="btn-cancel">Cancel</button>
        </div>
    </div>
</div>
<script>
    let pendingBulkSubmit = null;

function openBulkModal(message, onConfirm) {
    document.getElementById('bulkModalText').innerText = message;
    document.getElementById('bulkModal').style.display = 'flex';

    pendingBulkSubmit = onConfirm;
}

function closeBulkModal() {
    document.getElementById('bulkModal').style.display = 'none';
    pendingBulkSubmit = null;
}

document.getElementById('bulkConfirmBtn').addEventListener('click', function () {
    if (pendingBulkSubmit) {
        pendingBulkSubmit();
    }
    closeBulkModal();
});
    </script>
<?php
echo $OUTPUT->footer();
$SESSION->return = $returnurl;
