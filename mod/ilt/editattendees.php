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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * ILT - Add / remove attendees (custom UI).
 *
 * Keeps the original add / remove / cancel behaviour (enrol + ilt_user_signup /
 * ilt_user_cancel) but renders a custom card UI with per-row checkboxes,
 * stat cards and cohort / system / participants filters.
 *
 * @package    mod_ilt
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');

$s                 = required_param('s', PARAM_INT);              // ILT session ID.
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT);
$suppressemail     = optional_param('suppressemail', false, PARAM_BOOL);

// Filters.
$filtercohort = optional_param('filtercohort', 0, PARAM_INT);
$filteruser   = optional_param('filteruser', 'all', PARAM_ALPHA); // 'all' | 'system'.

// Which action was submitted. "Save" performs both remove and add.
$dosave   = optional_param('save', false, PARAM_BOOL);
$doadd    = optional_param('add', false, PARAM_BOOL) || $dosave;
$doremove = optional_param('remove', false, PARAM_BOOL) || $dosave;

if (!$session = ilt_get_session($s)) {
    print_error('error:incorrectcoursemodulesession', 'ilt');
}
if (!$ilt = $DB->get_record('ilt', array('id' => $session->ilt))) {
    print_error('error:incorrectiltid', 'ilt');
}
if (!$course = $DB->get_record('course', array('id' => $ilt->course))) {
    print_error('error:coursemisconfigured', 'ilt');
}
if (!$cm = get_coursemodule_from_instance('ilt', $ilt->id, $course->id)) {
    print_error('error:incorrectcoursemodule', 'ilt');
}

require_course_login($course);
$context = context_course::instance($course->id);
$contextmodule = context_module::instance($cm->id);
require_capability('mod/ilt:viewattendees', $context);

$errors = array();
$added = array();        // Signed up + email sent (or email suppressed).
$addednomail = array();  // Signed up, but the confirmation email could not be sent.
$removed = array();      // Successfully cancelled (for the result popup).

// Default role for users enrolled via ILT: the course's manual-enrol default role,
// falling back to the first 'student' archetype role.
$studentroleid = 0;
if ($manual = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'manual'), 'id, roleid', IGNORE_MULTIPLE)) {
    $studentroleid = (int)$manual->roleid;
}
if (!$studentroleid) {
    $archetyperoles = get_archetype_roles('student');
    if ($archetyperoles) {
        $studentroleid = (int)reset($archetyperoles)->id;
    }
}
/* ---------------------------------------------------------------------------
 * REMOVE selected attendees (same logic as before, ids read from checkboxes).
 * ------------------------------------------------------------------------- */
if ($doremove && confirm_sesskey()) {
    require_capability('mod/ilt:removeattendees', $context);
    $removeids = optional_param_array('removeselect', array(), PARAM_INT);
    foreach ($removeids as $removeuser) {
        if (!$removeuser = clean_param($removeuser, PARAM_INT)) {
            continue;
        }
        if (ilt_user_cancel($session, $removeuser, true, $cancelerr)) {
            // Record the name for the result popup.
            if ($removeduserobj = $DB->get_record('user', array('id' => $removeuser), '*')) {
                $removed[] = fullname($removeduserobj);
            }
            $timenow = time();
            if (!$suppressemail && !ilt_has_session_started($session, $timenow)) {
                ilt_send_cancellation_notice($ilt, $session, $removeuser);
            }
        } else {
            $errors[] = $cancelerr;
        }
    }
    ilt_update_attendees($session);
}

/* ---------------------------------------------------------------------------
 * ADD selected users (enrol if needed, then sign up) — original logic.
 * ------------------------------------------------------------------------- */
if ($doadd && confirm_sesskey()) {
    require_capability('mod/ilt:addattendees', $context);
    $addids = optional_param_array('addselect', array(), PARAM_INT);
    foreach ($addids as $adduser) {
        if (!$adduser = clean_param($adduser, PARAM_INT)) {
            continue;
        }

        // Make sure the user is enrolled in the course AND has a role.
        if (!has_capability('moodle/course:view', $context, $adduser)) {
            $user = $DB->get_record('user', array('id' => $adduser));
            if ($user) {
                if (!is_enrolled($context, $user)) {
                    if (!enrol_try_internal_enrol($course->id, $user->id, $studentroleid)) {
                        $errors[] = get_string('error:enrolmentfailed', 'ilt', fullname($user));
                        continue;
                    }
                } else if ($studentroleid && !get_user_roles($context, $user->id, false)) {
                    // Enrolled earlier without any role (old bug), so assign student now.
                    role_assign($studentroleid, $user->id, $context->id);
                }
            }
        }

        if (ilt_get_session_user($ilt->id, $session->id, $adduser)) {
            $erruser = $DB->get_record('user', array('id' => $adduser), '*');
            $errors[] = get_string('error:addalreadysignedupattendee', 'ilt', fullname($erruser));
        } else {
            if (!ilt_session_has_capacity($session, $context) && !$session->allowoverbook) {
                $errors[] = get_string('full', 'ilt');
                break;
            }
            $status = $session->datetimeknown ? MDL_ILT_STATUS_BOOKED : MDL_ILT_STATUS_WAITLISTED;

            // ilt_user_signup() echoes raw notification errors straight to the page and
            // returns false when an email step fails (even though the signup was made).
            // Capture that output so we can show a clean message instead.
            ob_start();
            $signedup = ilt_user_signup($session, $ilt, $course, '', MDL_ILT_BOTH, $status, $adduser, !$suppressemail);
            $signupnotice = trim(ob_get_clean());

            $adduserobj  = $DB->get_record('user', array('id' => $adduser), '*');
            $isattending = (bool) ilt_get_session_user($ilt->id, $session->id, $adduser);

            if ($isattending) {
                // The user is signed up. If signup returned false or printed a notice,
                // the booking succeeded but the confirmation email failed.
                if (!$signedup || $signupnotice !== '') {
                    $addednomail[] = fullname($adduserobj);
                } else {
                    $added[] = fullname($adduserobj);
                }
            } else {
                // Genuinely not signed up.
                $errors[] = get_string('error:addattendee', 'ilt', fullname($adduserobj));
            }
        }
    }
    ilt_update_attendees($session);
}

/* ---------------------------------------------------------------------------
 * Data for rendering (fetched AFTER processing so the UI is up to date).
 * ------------------------------------------------------------------------- */
$attendees = ilt_get_attendees($session->id);
$existingids = array();
foreach ($attendees as $a) {
    $existingids[$a->id] = true;
}

// Build the "potential" list depending on the active filter.
$namefields = get_all_user_name_fields(true, 'u');
if ($filtercohort) {
    // Members of the chosen cohort (queried directly).
    $potential = $DB->get_records_sql(
        "SELECT u.id, {$namefields}, u.email, u.picture, u.imagealt
           FROM {cohort_members} cm
           JOIN {user} u ON u.id = cm.userid
          WHERE cm.cohortid = ? AND u.deleted = 0 AND u.suspended = 0
       ORDER BY u.lastname, u.firstname", array($filtercohort));
} else if ($filteruser === 'system') {
    // System users = all active site users (enrolled or not), same idea as cohort.
    // Capped for performance; users already attending are removed below.
    $potential = $DB->get_records_sql(
        "SELECT u.id, {$namefields}, u.email, u.picture, u.imagealt
           FROM {user} u
          WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1 AND u.id > 2
       ORDER BY u.lastname, u.firstname", array(), 0, 500);
} else {
    // All participants = users enrolled in the current course.
    $potential = get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, true);
}

// Drop anyone already an attendee.
foreach ($potential as $k => $u) {
    if (isset($existingids[$u->id])) {
        unset($potential[$k]);
    }
}

// Cohorts for the dropdown.
$cohorts = $DB->get_records('cohort', array('visible' => 1), 'name ASC', 'id, name');

// Stats.
$enrolled  = count($attendees);
$seatsleft = max(0, (int)$session->capacity - $enrolled);

/* ---------------------------------------------------------------------------
 * Page setup + output.
 * ------------------------------------------------------------------------- */
$PAGE->set_cm($cm);
$PAGE->set_url('/mod/ilt/editattendees.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions));
$PAGE->set_title(format_string($ilt->name));
$PAGE->set_heading($course->fullname);

$formurl = new moodle_url('/mod/ilt/editattendees.php',
    array('s' => $s, 'backtoallsessions' => $backtoallsessions));
$backurl = new moodle_url('/mod/ilt/attendees.php',
    array('s' => $session->id, 'backtoallsessions' => $backtoallsessions));

// Small helper to render one user row.
$renderrow = function($u, $name) use ($OUTPUT) {
    $initials = strtoupper(mb_substr($u->firstname, 0, 1) . mb_substr($u->lastname, 0, 1));
    $full = fullname($u);
    $email = isset($u->email) ? $u->email : '';
    $row  = html_writer::start_tag('label', array('class' => 'ilt-ea-row'));
    $row .= html_writer::div($initials, 'ilt-ea-ava');
    $meta  = html_writer::div(s($full), 'ilt-ea-name');
    if ($email !== '') {
        $meta .= html_writer::div(s($email), 'ilt-ea-mail');
    }
    $row .= html_writer::div($meta, 'ilt-ea-meta');
    $row .= html_writer::empty_tag('input', array(
        'type' => 'checkbox', 'name' => $name . '[]', 'value' => $u->id, 'class' => 'ilt-ea-cb'));
    $row .= html_writer::tag('span', '', array('class' => 'ilt-ea-box'));
    $row .= html_writer::end_tag('label');
    return $row;
};

// Initials from a full-name string (used in the result popup).
$initialsof = function($name) {
    $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) {
        return '?';
    }
    $i = mb_substr($parts[0], 0, 1);
    if (count($parts) > 1) {
        $i .= mb_substr(end($parts), 0, 1);
    }
    return mb_strtoupper($i);
};

// Renders one popup row.
$renderresultrow = function($name, $tag = '') use ($initialsof) {
    $row  = html_writer::start_div('ilt-rm-row');
    $row .= html_writer::div(s($initialsof($name)), 'ilt-ea-ava');
    $row .= html_writer::div(s($name), 'ilt-rm-name');
    if ($tag !== '') {
        $row .= html_writer::span(s($tag), 'ilt-rm-tag');
    }
    $row .= html_writer::end_div();
    return $row;
};

$totaladded = count($added) + count($addednomail);
$showresult = ($totaladded > 0 || !empty($removed));
if ($totaladded && !empty($removed)) {
    $resulttitle = 'Attendees updated';
} else if ($totaladded) {
    $resulttitle = 'Attendees added';
} else {
    $resulttitle = 'Attendees removed';
}

echo $OUTPUT->header();
?>
<style>
.ilt-ea,.ilt-ea-layer{--nv:#0e2340;--or:#f5a623;--bd:#e9edf2;--mut:#6b7a8d;--ink:#1f2d3d;}
.ilt-ea *,.ilt-ea-layer *{box-sizing:border-box;}

/* stat cards */
.ilt-ea-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:0 0 18px;}
.ilt-ea-stat{display:flex;align-items:center;gap:14px;background:#fff;border:1px solid var(--bd);border-radius:14px;padding:16px 18px;box-shadow:0 1px 3px rgba(15,23,42,.05);}
.ilt-ea-stat .ic{width:42px;height:42px;border-radius:10px;background:var(--nv);display:flex;align-items:center;justify-content:center;flex:0 0 42px;}
.ilt-ea-stat .ic svg{width:22px;height:22px;stroke:var(--or);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.ilt-ea-stat .n{font-size:22px;font-weight:700;color:var(--ink);line-height:1;}
.ilt-ea-stat .l{font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--mut);margin-top:4px;}

/* filter bar */
.ilt-ea-filters{display:flex;flex-wrap:wrap;gap:26px;background:#fff;border:1px solid var(--bd);border-radius:14px;padding:16px 20px;margin:0 0 18px;box-shadow:0 1px 3px rgba(15,23,42,.05);}
.ilt-ea-filters .ff{display:flex;flex-direction:column;gap:8px;min-width:230px;flex:1;}
.ilt-ea-filters .lbl{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--nv);}
.ilt-ea-filters .lbl svg{width:15px;height:15px;stroke:var(--or);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.ilt-ea-filters select{border:1px solid var(--bd);border-radius:999px;padding:11px 16px;color:var(--ink);background:#fff;font-weight:500;width:100%;}
.ilt-ea-filters select:focus{border-color:var(--or);box-shadow:0 0 0 3px rgba(245,166,35,.18);outline:none;}
.ilt-ea-filters select:disabled{background:#f4f6f9;color:var(--mut);}

/* main card */
.ilt-ea-card{background:#fff;border:1px solid var(--bd);border-radius:16px;box-shadow:0 1px 3px rgba(15,23,42,.06);overflow:hidden;}
.ilt-ea-bar{background:var(--nv);color:#fff;font-size:16px;font-weight:700;padding:16px 22px;}
.ilt-ea-body{display:grid;grid-template-columns:1fr auto 1fr;gap:22px;padding:22px;}
.ilt-ea-panel{background:#f7f9fb;border:1px solid var(--bd);border-radius:14px;padding:16px;}
.ilt-ea-phead{display:flex;align-items:center;justify-content:space-between;font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--nv);margin-bottom:12px;}
.ilt-ea-badge{background:var(--or);color:var(--nv);border-radius:999px;font-size:12px;font-weight:700;padding:3px 10px;text-transform:none;letter-spacing:0;}
.ilt-ea-list{background:#fff;border:1px solid var(--bd);border-radius:12px;max-height:340px;overflow-y:auto;}

/* rows */
.ilt-ea-row{display:flex;align-items:center;gap:12px;padding:12px 14px;border-bottom:1px solid #f1f4f7;cursor:pointer;margin:0;position:relative;}
.ilt-ea-row:last-child{border-bottom:0;}
.ilt-ea-ava{width:36px;height:36px;border-radius:50%;background:var(--nv);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex:0 0 36px;}
.ilt-ea-meta{flex:1;min-width:0;}
.ilt-ea-name{font-weight:700;color:var(--ink);font-size:14px;}
.ilt-ea-mail{font-size:12px;color:var(--mut);margin-top:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ilt-ea-cb{position:absolute;opacity:0;pointer-events:none;}
.ilt-ea-box{width:20px;height:20px;border:2px solid #cdd7e2;border-radius:6px;flex:0 0 20px;position:relative;}
.ilt-ea-cb:checked ~ .ilt-ea-box{background:var(--or);border-color:var(--or);}
.ilt-ea-cb:checked ~ .ilt-ea-box::after{content:"";position:absolute;left:6px;top:2px;width:5px;height:10px;border:solid #fff;border-width:0 2px 2px 0;transform:rotate(45deg);}
.ilt-ea-row:has(.ilt-ea-cb:checked){background:#fdf1dd;border-radius:10px;box-shadow:inset 0 0 0 1px var(--or);}

/* search + clear */
.ilt-ea-search{display:flex;gap:10px;margin-top:12px;}
.ilt-ea-search .box{flex:1;display:flex;align-items:center;gap:8px;border:1px solid var(--bd);border-radius:999px;padding:9px 14px;background:#fff;}
.ilt-ea-search .box svg{width:15px;height:15px;stroke:var(--mut);fill:none;stroke-width:2;}
.ilt-ea-search input{border:0;outline:0;flex:1;font-size:14px;background:transparent;}
.ilt-ea-search .box:focus-within{border-color:var(--or);box-shadow:0 0 0 3px rgba(245,166,35,.18);}
.ilt-ea-clear{background:#fff;color:var(--or);border:1px solid var(--or);border-radius:999px;font-weight:600;padding:8px 16px;cursor:pointer;white-space:nowrap;}

/* mid buttons */
.ilt-ea-mid{display:flex;flex-direction:column;justify-content:center;gap:12px;}
.ilt-ea-mid button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-width:130px;border-radius:999px;font-weight:600;padding:11px 20px;cursor:pointer;border:1px solid var(--bd);background:#fff;color:var(--mut);}
.ilt-ea-mid button.add{background:var(--nv);border-color:var(--nv);color:#fff;}

/* footer */
.ilt-ea-foot{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;border-top:1px solid var(--bd);padding:16px 22px;}
.ilt-ea-foot label{display:inline-flex;align-items:center;gap:9px;color:var(--ink);font-weight:500;}
.ilt-ea-foot .right{display:flex;align-items:center;gap:18px;}
.ilt-ea-save{display:inline-flex;align-items:center;gap:8px;background:var(--nv);color:#fff;border:0;border-radius:10px;font-weight:600;padding:11px 22px;cursor:pointer;}
.ilt-ea-save svg{width:16px;height:16px;stroke:var(--or);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.ilt-ea-back{color:var(--or);font-weight:600;text-decoration:none;}
.ilt-ea .errbox{background:#fbe9e9;color:#b3261e;border:1px solid #f3c9c5;border-radius:12px;padding:12px 16px;margin-bottom:16px;}
.ilt-ea .okbox{background:#e4f5ea;color:#1c7a48;border:1px solid #bfe6ce;border-radius:12px;padding:12px 16px;margin-bottom:12px;font-weight:500;}
.ilt-ea .warnbox{background:#fdf1dd;color:#9a6a12;border:1px solid #f3d9a6;border-radius:12px;padding:12px 16px;margin-bottom:12px;font-weight:500;}
#page-mod-ilt-editattendees #page-wrapper #page {
    padding-left: 8em !important;
    padding-right: 0em !important;
}
#page-mod-ilt-editattendees #page-wrapper #page.show-drawer-left {
    padding-left: 2em !important;
    padding-right: 0em !important;
}
/* search options */
.ilt-ea-opts{border:1px dashed #f0c98a;border-radius:12px;padding:12px 14px;margin-top:12px;}
.ilt-ea-opts .ttl{display:flex;align-items:center;gap:8px;color:var(--or);font-weight:600;font-size:13px;margin-bottom:8px;}
.ilt-ea-opts .ttl svg{width:15px;height:15px;stroke:var(--or);fill:none;stroke-width:2;stroke-linecap:round;}
.ilt-ea-opts label{display:flex;align-items:center;gap:8px;color:var(--ink);font-size:13px;margin:5px 0;font-weight:500;cursor:pointer;}
.ilt-ea-opts input[type=checkbox]{accent-color:var(--or);width:16px;height:16px;flex:0 0 16px;}
.ilt-ea-opts .match{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:8px;}
.ilt-ea-opts .match .lbl{font-weight:700;color:var(--nv);font-size:13px;}
.ilt-ea-opts .match label{border:1px solid var(--bd);border-radius:999px;padding:5px 12px;margin:0;}
.ilt-ea-opts .match input[type=radio]{accent-color:var(--or);}

/* suppress bell */
.ilt-ea-foot .bell{width:16px;height:16px;stroke:var(--or);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
@media (max-width:900px){.ilt-ea-stats{grid-template-columns:1fr;}.ilt-ea-body{grid-template-columns:1fr;}.ilt-ea-mid{flex-direction:row;}}

/* ---------- Loader (blur overlay + steps) ---------- */
.ilt-ld{position:fixed;inset:0;z-index:10000;display:none;align-items:center;justify-content:center;
  background:rgba(14,35,64,.35);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);}
.ilt-ld.show{display:flex;}
.ilt-ld-card{background:#fff;border-radius:18px;padding:28px 30px;width:340px;max-width:calc(100% - 32px);
  box-shadow:0 20px 50px rgba(14,35,64,.25);text-align:center;}
.ilt-ld-spin{width:52px;height:52px;margin:0 auto 16px;border-radius:50%;border:4px solid #eef1f5;border-top-color:var(--or);
  animation:ilt-spin .8s linear infinite;}
@keyframes ilt-spin{to{transform:rotate(360deg);}}
.ilt-ld-title{font-size:16px;font-weight:700;color:var(--nv);margin-bottom:16px;}
.ilt-ld-steps{list-style:none;margin:0;padding:0;text-align:left;}
.ilt-ld-steps li{display:flex;align-items:center;gap:10px;padding:7px 0;font-size:14px;color:var(--mut);font-weight:500;}
.ilt-ld-steps .dot{width:24px;height:24px;border-radius:50%;flex:0 0 24px;display:flex;align-items:center;justify-content:center;
  font-size:12px;font-weight:700;background:#eef1f5;color:var(--mut);}
.ilt-ld-steps li.active{color:var(--ink);font-weight:600;}
.ilt-ld-steps li.active .dot{background:var(--or);color:var(--nv);animation:ilt-pulse 1s ease-in-out infinite;}
.ilt-ld-steps li.done{color:var(--ink);}
.ilt-ld-steps li.done .dot{background:var(--nv);color:#fff;}
@keyframes ilt-pulse{50%{box-shadow:0 0 0 5px rgba(245,166,35,.25);}}

/* ---------- Result popup ---------- */
.ilt-rm{position:fixed;inset:0;z-index:10001;display:none;align-items:center;justify-content:center;
  background:rgba(14,35,64,.35);-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px);}
.ilt-rm.show{display:flex;}
.ilt-rm-card{background:#fff;border-radius:18px;width:440px;max-width:calc(100% - 32px);max-height:calc(100vh - 64px);
  display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 50px rgba(14,35,64,.25);}
.ilt-rm-head{display:flex;align-items:center;gap:12px;background:var(--nv);color:#fff;padding:16px 20px;}
.ilt-rm-head .ic{width:34px;height:34px;border-radius:50%;background:var(--or);display:flex;align-items:center;justify-content:center;flex:0 0 34px;}
.ilt-rm-head .ic svg{width:18px;height:18px;stroke:var(--nv);fill:none;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;}
.ilt-rm-head h3{flex:1;margin:0;font-size:17px;font-weight:700;color:#fff;}
.ilt-rm-x{background:transparent;border:0;color:#fff;font-size:24px;line-height:1;cursor:pointer;opacity:.8;padding:0 4px;}
.ilt-rm-x:hover{opacity:1;}
.ilt-rm-body{padding:16px 20px;overflow-y:auto;}
.ilt-rm-sec + .ilt-rm-sec{margin-top:16px;}
.ilt-rm-sec h4{display:flex;align-items:center;justify-content:space-between;margin:0 0 8px;font-size:12px;font-weight:700;
  letter-spacing:.05em;text-transform:uppercase;color:var(--nv);}
.ilt-rm-sec h4 .cnt{background:var(--or);color:var(--nv);border-radius:999px;padding:2px 10px;letter-spacing:0;}
.ilt-rm-sec.rem h4 .cnt{background:#fbe9e9;color:#b3261e;}
.ilt-rm-list{border:1px solid var(--bd);border-radius:12px;}
.ilt-rm-row{display:flex;align-items:center;gap:12px;padding:10px 14px;border-bottom:1px solid #f1f4f7;}
.ilt-rm-row:last-child{border-bottom:0;}
.ilt-rm-sec.rem .ilt-ea-ava{background:#b3261e;}
.ilt-rm-name{flex:1;font-weight:600;color:var(--ink);font-size:14px;}
.ilt-rm-tag{font-size:11px;font-weight:600;color:#9a6a12;background:#fdf1dd;border:1px solid #f3d9a6;border-radius:999px;padding:2px 8px;white-space:nowrap;}
.ilt-rm-foot{display:flex;justify-content:flex-end;padding:14px 20px;border-top:1px solid var(--bd);}
.ilt-rm-ok{background:var(--nv);color:#fff;border:0;border-radius:10px;font-weight:600;padding:10px 26px;cursor:pointer;}
</style>

<?php echo html_writer::start_div('ilt-ea'); ?>

<?php
if (!empty($addednomail)) {
    // echo html_writer::div('Added successfully, but the confirmation email could not be sent (check the site email configuration): ' . s(implode(', ', $addednomail)), 'warnbox');
}
if (!empty($errors)) {
    // echo html_writer::start_div('errbox');
    // foreach ($errors as $e) { echo html_writer::div($e); }
    // echo html_writer::end_div();
} ?>

  <!-- Stat cards -->
  <div class="ilt-ea-stats">
    <div class="ilt-ea-stat">
      <span class="ic"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
      <span><span class="n"><?php echo $enrolled; ?></span><span class="l"> Enrolled</span></span>
    </div>
    <div class="ilt-ea-stat">
      <span class="ic"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg></span>
      <span><span class="n"><?php echo $seatsleft; ?></span><span class="l"> Seats left</span></span>
    </div>
    <div class="ilt-ea-stat">
      <span class="ic"><svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
      <span><span class="n" id="ilt-ea-selcount">0</span><span class="l"> Selected</span></span>
    </div>
  </div>

  <form id="assignform" method="post" action="<?php echo $formurl->out(false); ?>">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
    <input type="hidden" name="s" value="<?php echo $s; ?>">
    <input type="hidden" name="backtoallsessions" value="<?php echo $backtoallsessions; ?>">

    <!-- Filters -->
    <div class="ilt-ea-filters">
      <div class="ff">
        <div class="lbl"><svg viewBox="0 0 24 24"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>Select cohort</div>
        <select name="filtercohort" onchange="this.form.submit()">
          <option value="0">All cohorts</option>
          <?php foreach ($cohorts as $co) {
              $sel = ($co->id == $filtercohort) ? ' selected' : '';
              echo '<option value="'.$co->id.'"'.$sel.'>'.s($co->name).'</option>';
          } ?>
        </select>
      </div>
      <div class="ff">
        <div class="lbl"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>Select users</div>
        <select name="filteruser" onchange="this.form.submit()" <?php echo $filtercohort ? 'disabled' : ''; ?>>
          <option value="all"<?php echo $filteruser === 'all' ? ' selected' : ''; ?>>All Participants</option>
          <option value="system"<?php echo $filteruser === 'system' ? ' selected' : ''; ?>>System users</option>
        </select>
      </div>
    </div>

    <div class="ilt-ea-card">
      <div class="ilt-ea-bar">Attendees</div>
      <div class="ilt-ea-body">

        <!-- Existing attendees -->
        <div class="ilt-ea-panel">
          <div class="ilt-ea-phead"><span><?php echo $enrolled; ?> existing user<?php echo $enrolled == 1 ? '' : 's'; ?></span></div>
          <div class="ilt-ea-list" data-list="existing">
            <?php foreach ($attendees as $u) { echo $renderrow($u, 'removeselect'); } ?>
          </div>
          <div class="ilt-ea-search">
            <span class="box"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg><input type="text" placeholder="Search" data-search="existing"></span>
            <button type="button" class="ilt-ea-clear" data-clear="existing">&times; Clear</button>
          </div>
          <div class="ilt-ea-opts">
            <div class="ttl"><svg viewBox="0 0 24 24"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>Search options</div>
            <label><input type="checkbox" id="opt-keep" checked> Keep selected users, even if they no longer match the search</label>
            <label><input type="checkbox" id="opt-auto"> If only one user matches the search, select them automatically</label>
            <div class="match"><span class="lbl">Matching:</span>
              <label><input type="radio" name="opt-mode" value="exact"> exact matches only</label>
              <label><input type="radio" name="opt-mode" value="start"> from start</label>
              <label><input type="radio" name="opt-mode" value="anywhere" checked> anywhere</label>
            </div>
          </div>
        </div>

        <!-- Middle buttons -->
        <div class="ilt-ea-mid">
          <button type="submit" name="add" value="1" class="add">&laquo; Add</button>
          <button type="submit" name="remove" value="1">Remove &raquo;</button>
        </div>

        <!-- Potential attendees -->
        <div class="ilt-ea-panel">
          <div class="ilt-ea-phead"><span>Potential attendees</span><span class="ilt-ea-badge" id="ilt-ea-potbadge" style="display:none;"></span></div>
          <div class="ilt-ea-list" data-list="potential">
            <?php foreach ($potential as $u) { echo $renderrow($u, 'addselect'); } ?>
          </div>
          <div class="ilt-ea-search">
            <span class="box"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg><input type="text" placeholder="Search" data-search="potential"></span>
            <button type="button" class="ilt-ea-clear" data-clear="potential">&times; Clear</button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="ilt-ea-foot">
        <label><input type="checkbox" name="suppressemail" value="1" <?php echo $suppressemail ? 'checked' : ''; ?>> <svg class="bell" viewBox="0 0 24 24"><path d="M13.73 21a2 2 0 0 1-3.46 0"/><path d="M18.63 13A17.9 17.9 0 0 1 18 8"/><path d="M6.26 6.26A5.86 5.86 0 0 0 6 8c0 7-3 9-3 9h14"/><path d="M18 8a6 6 0 0 0-9.33-5"/><line x1="1" y1="1" x2="23" y2="23"/></svg> Suppress email notification</label>
        <div class="right">
          <button type="submit" name="save" value="1" class="ilt-ea-save">
            <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save attendees
          </button>
          <a class="ilt-ea-back" href="<?php echo $backurl->out(false); ?>">Go back</a>
        </div>
      </div>
    </div>
  </form>
<?php echo html_writer::end_div(); ?>

<!-- Loader: blurred overlay with step progress -->
<div class="ilt-ea-layer">
  <div class="ilt-ld" id="ilt-ld" role="alert" aria-live="assertive" aria-busy="true">
    <div class="ilt-ld-card">
      <div class="ilt-ld-spin"></div>
      <div class="ilt-ld-title" id="ilt-ld-title">Please wait&hellip;</div>
      <ul class="ilt-ld-steps" id="ilt-ld-steps"></ul>
    </div>
  </div>

<?php if ($showresult) { ?>
  <!-- Result popup -->
  <div class="ilt-rm" id="ilt-rm" role="dialog" aria-modal="true" aria-labelledby="ilt-rm-title">
    <div class="ilt-rm-card">
      <div class="ilt-rm-head">
        <span class="ic"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></span>
        <h3 id="ilt-rm-title"><?php echo s($resulttitle); ?></h3>
        <button type="button" class="ilt-rm-x" data-rm-close aria-label="Close">&times;</button>
      </div>
      <div class="ilt-rm-body">
        <?php if ($totaladded) { ?>
          <div class="ilt-rm-sec add">
            <h4><span>Added successfully</span><span class="cnt"><?php echo $totaladded; ?></span></h4>
            <div class="ilt-rm-list">
              <?php
              foreach ($added as $n) { echo $renderresultrow($n); }
              foreach ($addednomail as $n) { echo $renderresultrow($n, 'Email not sent'); }
              ?>
            </div>
          </div>
        <?php } ?>
        <?php if (!empty($removed)) { ?>
          <div class="ilt-rm-sec rem">
            <h4><span>Removed successfully</span><span class="cnt"><?php echo count($removed); ?></span></h4>
            <div class="ilt-rm-list">
              <?php foreach ($removed as $n) { echo $renderresultrow($n); } ?>
            </div>
          </div>
        <?php } ?>
      </div>
      <div class="ilt-rm-foot">
        <button type="button" class="ilt-rm-ok" data-rm-close>OK</button>
      </div>
    </div>
  </div>
<?php } ?>
</div>

<script>
(function(){
  var root = document.querySelector('.ilt-ea');
  if (!root) return;

  function refresh(){
    document.getElementById('ilt-ea-selcount').textContent = root.querySelectorAll('.ilt-ea-cb:checked').length;
    var pot = root.querySelectorAll('[data-list="potential"] .ilt-ea-cb:checked').length;
    var b = document.getElementById('ilt-ea-potbadge');
    if (pot > 0){ b.style.display=''; b.textContent = pot + ' selected'; } else { b.style.display='none'; }
  }
  root.addEventListener('change', function(e){ if (e.target.classList.contains('ilt-ea-cb')) refresh(); });

  // --- Existing-panel search, driven by the Search options ---
  var eInput = root.querySelector('[data-search="existing"]');
  var optKeep = document.getElementById('opt-keep');
  var optAuto = document.getElementById('opt-auto');
  function mode(){ var r = root.querySelector('input[name="opt-mode"]:checked'); return r ? r.value : 'anywhere'; }
  function filterExisting(){
    var q = (eInput.value||'').toLowerCase(), m = mode();
    var rows = root.querySelectorAll('[data-list="existing"] .ilt-ea-row'), visible = [];
    rows.forEach(function(r){
      var cb = r.querySelector('.ilt-ea-cb');
      var name = (r.querySelector('.ilt-ea-name')||{}).textContent || '';
      name = name.toLowerCase();
      var text = r.textContent.toLowerCase();
      var ok;
      if (!q) ok = true;
      else if (m === 'exact') ok = (name === q);
      else if (m === 'start') ok = (name.indexOf(q) === 0 || text.indexOf(q) === 0);
      else ok = (text.indexOf(q) > -1);
      if (optKeep.checked && cb.checked) ok = true;
      r.style.display = ok ? '' : 'none';
      if (ok) visible.push(r);
    });
    if (optAuto.checked && q && visible.length === 1){
      var cb = visible[0].querySelector('.ilt-ea-cb');
      if (!cb.checked){ cb.checked = true; refresh(); }
    }
  }
  if (eInput){
    eInput.addEventListener('input', filterExisting);
    [optKeep, optAuto].forEach(function(o){ o && o.addEventListener('change', filterExisting); });
    root.querySelectorAll('input[name="opt-mode"]').forEach(function(o){ o.addEventListener('change', filterExisting); });
  }

  // --- Potential-panel search (plain "anywhere") ---
  var pInput = root.querySelector('[data-search="potential"]');
  if (pInput){
    pInput.addEventListener('input', function(){
      var q = pInput.value.toLowerCase();
      root.querySelectorAll('[data-list="potential"] .ilt-ea-row').forEach(function(r){
        r.style.display = r.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
      });
    });
  }

  // Clear buttons.
  root.querySelectorAll('[data-clear]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var inp = root.querySelector('[data-search="'+btn.getAttribute('data-clear')+'"]');
      inp.value=''; inp.dispatchEvent(new Event('input'));
    });
  });
  refresh();

  // --- Loader: shown only for Add / Remove / Save submits (filter changes use
  //     form.submit(), which does not fire the submit event, so no loader there). ---
  var form  = document.getElementById('assignform');
  var ld    = document.getElementById('ilt-ld');
  var ldTtl = document.getElementById('ilt-ld-title');
  var ldUl  = document.getElementById('ilt-ld-steps');
  var timer = null;
  var FLOWS = {
    add:    { title: 'Adding attendees',   steps: ['Validating selected users', 'Enrolling & signing up', 'Sending notifications'] },
    remove: { title: 'Removing attendees', steps: ['Validating selected users', 'Cancelling signups', 'Sending cancellation notices'] },
    save:   { title: 'Saving attendees',   steps: ['Removing selected attendees', 'Adding selected users', 'Updating session'] }
  };
  var lastClicked = null;
  form.querySelectorAll('button[type="submit"]').forEach(function(b){
    b.addEventListener('click', function(){ lastClicked = b; });
  });

  function showLoader(action){
    var flow = FLOWS[action] || FLOWS.save;
    ldTtl.textContent = flow.title + '\u2026';
    ldUl.innerHTML = '';
    flow.steps.forEach(function(txt, i){
      var li = document.createElement('li');
      li.innerHTML = '<span class="dot">' + (i + 1) + '</span><span></span>';
      li.lastChild.textContent = txt;
      ldUl.appendChild(li);
    });
    var items = ldUl.children, cur = 0;
    function paint(){
      for (var i = 0; i < items.length; i++){
        items[i].className = i < cur ? 'done' : (i === cur ? 'active' : '');
        items[i].firstChild.innerHTML = i < cur ? '&#10003;' : String(i + 1);
      }
    }
    paint();
    clearInterval(timer);
    // Advance through the steps while the server works; stay on the last one.
    timer = setInterval(function(){
      if (cur < items.length - 1){ cur++; paint(); } else { clearInterval(timer); }
    }, 1200);
    ld.classList.add('show');
    document.body.style.overflow = 'hidden';
  }

  form.addEventListener('submit', function(e){
    var btn = e.submitter || lastClicked;
    if (!btn || !btn.name) return; // not an add/remove/save submit
    showLoader(btn.name);
    // Buttons are NOT disabled (that would drop their name from the POST);
    // the overlay blocks further clicks instead.
  });

  // Hide loader if the page is restored from the back/forward cache.
  window.addEventListener('pageshow', function(){
    clearInterval(timer);
    ld.classList.remove('show');
    document.body.style.overflow = '';
  });

  // --- Result popup (added / removed users) ---
  var rm = document.getElementById('ilt-rm');
  if (rm){
    function closeRm(){ rm.classList.remove('show'); document.body.style.overflow = ''; }
    rm.querySelectorAll('[data-rm-close]').forEach(function(b){ b.addEventListener('click', closeRm); });
    rm.addEventListener('click', function(e){ if (e.target === rm) closeRm(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && rm.classList.contains('show')) closeRm(); });
    rm.classList.add('show');
    document.body.style.overflow = 'hidden';
    var ok = rm.querySelector('.ilt-rm-ok'); if (ok) ok.focus();
  }
})();
</script>
<?php
echo $OUTPUT->footer($course);
