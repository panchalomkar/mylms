<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//Points type
define('POINT_TYPE_LOGIN', 'login');
define('POINT_TYPE_QUIZ', 'quiz');
define('POINT_TYPE_SPINWHEEL', 'spinwheel');
use badge;

function local_mydashboard_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course)
{
    $userid = optional_param('id', 0, PARAM_INT);
    if ($userid && is_siteadmin()) {

        // $url = new moodle_url('/local/mydashboard/add_admin_point.php', array('userid' => $user->id, 'id' => $course->id));
        // $node = new core_user\output\myprofile\node('contact', 'mydashboard', get_string('enterpoint', 'local_mydashboard'),
        //         null, $url);
        // $tree->add_node($node);
        $maildisplay = '
        <input type="number" id="addpointadmin" class="form-control" name="addpointadmin" placeholder="Please enter point" style="width:250px;float:left"><button type="button" class="btn btn-primary" id="addpointid"style="height:48px">Add</button>
<script>
    $("#addpointid").click(function(){
        var daturl = $(location).attr("href");
        var pointv = $("#addpointadmin").val();
        $.ajax({
            url: M.cfg.wwwroot + "/local/mydashboard/addpoint.php",
            type: "post",
            data: {pointv: pointv, daturl:daturl},
            success: function (data) {
                   alert(data);
            }
        });
    }) ;
</script>
        ';
        $node = new core_user\output\myprofile\node(
            'contact',
            'mydashboard',
            get_string('enterpoint', 'local_mydashboard'),
            null,
            null,
            $maildisplay
        );
        $tree->add_node($node);
    }
}



function get_available_points($userid)
{
    global $DB;

    $record = $DB->get_record('user_points', array('userid' => $userid));

    return ($record->available_points > 0) ? $record->available_points : 0;
}

function set_my_rank($userid)
{
    global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }

    $record = $DB->get_record('user_points', array('userid' => $userid));

    $total_points = ($record->total_points > 0) ? $record->total_points : 0;

    $usergrade = get_user_grade($userid);
    $currentrank = $record->userrank;
    $newrank = get_new_rank($userid, $total_points, $usergrade);
    if ($currentrank != $newrank) {
        //get config
        $rank_promote = get_config('local_mydashboard', 'rank_promote' . '_' . $selectedcompany);
        $update = "UPDATE {user_points} SET userrank = '$newrank' WHERE userid = $userid";
        if ($DB->execute($update)) {
            $count = $rank_promote;
            //            if ($newrank != 'Team Ceasefire Cadet') {
            if (strpos($newrank, 'Cadet') == false) {
                for ($i = 0; $i < $count; $i++) {
                    $items = array(5, 0, 5, 0, 10, 10, 0, 15, 15, 0, 20, 10, 0, 20, 10, 0, 25, 5, 15, 0, 25, 15, 0, 30, 10, 0, 35, 0, 0, 10, 40, 5, 45, 5, 50);
                    $scratch = new stdClass();

                    $scratch->userid = $userid;
                    $scratch->itemid = 0;
                    $scratch->card_type = 'rank';
                    $scratch->point = $items[rand(0, count($items) - 1)];
                    $scratch->redeemed = 0;
                    $scratch->timecreated = time();

                    $DB->insert_record('user_scratchcard', $scratch);
                }
            }
        }
    }
}

function get_my_rank($userid)
{
    global $DB, $CFG;

    $record = $DB->get_record('user_points', array('userid' => $userid));

    return $record->userrank;
}

function get_new_rank($userid, $total_points, $usergrade)
{
    global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }
    if ($selectedcompany) {
        $SQL = "SELECT * FROM {custom_level} WHERE point <= $total_points AND grade <= $usergrade AND companyid = $selectedcompany
            ORDER BY point DESC LIMIT 1";
    } else {
        $SQL = "SELECT * FROM {custom_level} WHERE point <= $total_points AND grade <= $usergrade
            ORDER BY point DESC LIMIT 1";
    }

    if ($record = $DB->get_record_sql($SQL)) {
        return $record->level;
    }
    return '-';
}


function get_new_rank1($userid, $total_points, $usergrade)
{
    global $DB;

    if ($total_points >= 500000 && $usergrade >= 95) {
        return 'Fire Chief';
    } else if ($total_points >= 300000 && $usergrade >= 95) {
        return 'Assistant Chief';
    } else if ($total_points >= 100000 && $usergrade >= 92) {
        return 'Battalion Chief';
    } else if ($total_points >= 90000 && $usergrade >= 92) {
        return 'Assistant Battalion Chief';
    } else if ($total_points >= 70000 && $usergrade >= 90) {
        return 'Senior Captain';
    } else if ($total_points >= 60000 && $usergrade >= 90) {
        return 'Captain';
    } else if ($total_points >= 50000 && $usergrade >= 90) {
        return 'Junior Captain';
    } else if ($total_points >= 30000 && $usergrade >= 87) {
        return 'Senior Lieutenant';
    } else if ($total_points >= 25000 && $usergrade >= 87) {
        return 'Lieutenant';
    } else if ($total_points >= 20000 && $usergrade >= 87) {
        return 'Junior Lieutenant';
    } else if ($total_points >= 15000 && $usergrade >= 85) {
        return 'Senior Firefighter';
    } else if ($total_points >= 10000 && $usergrade >= 85) {
        return 'Firefighter';
    } else if ($total_points >= 2100 && $usergrade >= 75) {
        return 'Probationary firefighter';
    } else if ($total_points >= 2000) {
        return 'Cadet';
    } else if ($total_points < 2000) {
        return '-';
    }
}

function get_next_level($userid)
{
    global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }

    $record = $DB->get_record('user_points', array('userid' => $userid));

    $total_points = ($record->total_points > 0) ? $record->total_points : 0;
    $usergrade = get_user_grade($userid);
    if ($selectedcompany) {
        $SQL = "SELECT * FROM `mdl_custom_level` WHERE point >= $total_points AND grade >= $usergrade AND companyid = $selectedcompany
            ORDER BY point LIMIT 1";
    } else {
        $SQL = "SELECT * FROM `mdl_custom_level` WHERE point >= $total_points AND grade >= $usergrade
            ORDER BY point LIMIT 1";
    }

    if ($record = $DB->get_record_sql($SQL)) {

        return array(($record->point - $total_points), $record->grade . '%', $record->level);
    }
    return array('-', '-', '-');

    //
//    if ($total_points >= 500000) {
//        return array('', 'Top Level');
//    } else if ($total_points >= 300000 && $total_points < 500000) {
//        return array((500000 - $total_points), 'Fire Chief');
//    } else if ($total_points >= 100000 && $total_points < 300000) {
//        return array((300000 - $total_points), 'Assistant Chief');
//    } else if ($total_points >= 90000 && $total_points < 100000) {
//        return array((100000 - $total_points), 'Battalion Chief');
//    } else if ($total_points >= 70000 && $total_points < 90000) {
//        return array((90000 - $total_points), 'Assistant Battalion Chief');
//    } else if ($total_points >= 60000 && $total_points < 70000) {
//        return array((70000 - $total_points), 'Senior Captain');
//    } else if ($total_points >= 50000 && $total_points < 60000) {
//        return array((60000 - $total_points), 'Captain');
//    } else if ($total_points >= 30000 && $total_points < 50000) {
//        return array((50000 - $total_points), 'Junior Captain');
//    } else if ($total_points >= 25000 && $total_points < 30000) {
//        return array((30000 - $total_points), 'Senior Lieutenant');
//    } else if ($total_points >= 20000 && $total_points < 25000) {
//        return array((25000 - $total_points), 'Lieutenant');
//    } else if ($total_points >= 15000 && $total_points < 20000) {
//        return array((20000 - $total_points), 'Junior Lieutenant');
//    } else if ($total_points >= 10000 && $total_points < 15000) {
//        return array((15000 - $total_points), 'Senior Firefighter');
//    } else if ($total_points >= 2100 && $total_points < 10000) {
//        return array((10000 - $total_points), 'Firefighter');
//    } else if ($total_points >= 2000 && $total_points < 2100) {
//        return array((2100 - $total_points), 'Probationary firefighter');
//    } else if ($total_points < 2000) {
//        return array((2000 - $total_points), 'Cadet');
//    }
}

function get_user_grade($userid)
{
    global $DB, $CFG;
    include_once $CFG->dirroot . '/grade/querylib.php';

    $courses = $DB->get_records('course');
    $sum = 0;
    $maxsum = 0;

    foreach ($courses as $course) {
        try {
            $grade = grade_get_course_grade($userid, $course->id);
            if ($grade && isset($grade->grade)) {
                $sum += $grade->grade;
                $maxsum += $grade->item->grademax;
            }
        } catch (moodle_exception $e) {
            debugging("Skipping course {$course->id} due to grade fetch error: " . $e->getMessage(), DEBUG_DEVELOPER);
            // Skip this course gracefully
        }
    }

    if ($maxsum === 0) {
        return 0; // avoid division by zero
    }

    return ($sum / $maxsum) * 100;
}

// function get_user_grade($userid)
// {
//     global $DB, $CFG;
//     include_once $CFG->dirroot . '/grade/querylib.php';

//     $courses = $DB->get_records('course');
//     foreach ($courses as $course) {
//         $carray[] = $course->id;
//     }
//     $grades = grade_get_course_grade($userid, $carray);

//     $count = 0;
//     $sum = 0;
//     $maxsum = 0;
//     foreach ($grades as $grade) {
//         $sum = $sum + $grade->grade;
//         $maxsum = $maxsum + $grade->item->grademax;
//         $count++;
//     }

//     return ($sum / $maxsum) * 100;
// }

function get_next_levelold($userid)
{
    global $DB;
    $op = array();
    $record = $DB->get_record('user_points', array('userid' => $userid));

    $total_points = ($record->total_points > 0) ? $record->total_points : 0;

    //get level up config
    $level = $DB->get_record('block_xp_config', array('enabled' => 1));
    $p_config = (array) (json_decode($level->levelsdata));
    $p_config['name'] = (array) $p_config['name'];
    $p_config['xp'] = (array) $p_config['xp'];
    if ($total_points == 0) {
        $op[0] = $p_config['xp'][1];
        $op[1] = $p_config['name'][1];
    } else {
        foreach ((array) $p_config['xp'] as $key => $point) {
            if ($total_points < $point) {
                $op[0] = $point - $total_points;
                $op[1] = $p_config['name'][$key];
                break;
            }
        }
    }
    return $op;
}

function get_total_points($userid)
{
    global $DB;

    $record = $DB->get_record('user_points', array('userid' => $userid));

    return ($record->total_points > 0) ? $record->total_points : 0;
}

function get_points($userid, $type)
{
    global $DB;

    $SQL = "SELECT id, SUM(points) AS sumpoints FROM {user_points_log} WHERE userid = $userid AND point_type = '$type'";
    $record = $DB->get_record_sql($SQL);

    return ($record->sumpoints > 0) ? $record->sumpoints : 0;
}

function get_rewards_points_received($userid)
{
    global $DB;

    $SQL = "SELECT id, SUM(points) AS sumpoints FROM {user_points_share} 
    WHERE touserid = $userid";
    $record = $DB->get_record_sql($SQL);

    return ($record->sumpoints > 0) ? $record->sumpoints : 0;
}

function get_admin_points_received($userid)
{
    global $DB;

    $SQL = "SELECT id, SUM(points) AS sumpoints FROM {user_points_log} WHERE userid = $userid AND point_type = 'admin'";
    $record = $DB->get_record_sql($SQL);

    return ($record->sumpoints > 0) ? $record->sumpoints : 0;
}

function get_user_available_points()
{
    global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }
    if ($selectedcompany) {
        $SQL = "SELECT u.*, up.available_points FROM {user_points} up 
    INNER JOIN {user} u ON u.id = up.userid
    INNER JOIN {company_users} cu ON cu.userid = up.userid
    WHERE cu.companyid = $selectedcompany
    ";
    } else {
        $SQL = "SELECT u.*, up.available_points FROM {user_points} up 
    INNER JOIN {user} u ON u.id = up.userid
    ";
    }


    return $DB->get_records_sql($SQL);
}

function get_user_points_log()
{
    global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }
    if ($selectedcompany) {
        $SQL = "SELECT up.*, u.username, u.firstname, u.lastname, u.email  FROM {user_points_log} up 
        INNER JOIN {user} u ON u.id = up.userid
        INNER JOIN {company_users} cu ON cu.userid = up.userid
        WHERE cu.companyid = $selectedcompany
        ";
    } else {
        $SQL = "SELECT up.*, u.username, u.firstname, u.lastname, u.email  FROM {user_points_log} up 
            INNER JOIN {user} u ON u.id = up.userid
            ";
    }

    return $DB->get_records_sql($SQL);
}

function get_my_points_log($userid)
{
    global $DB;

    $SQL = "SELECT up.*, u.username, u.firstname, u.lastname, u.email  FROM {user_points_log} up 
            INNER JOIN {user} u ON u.id = up.userid WHERE u.id = $userid";
    return $DB->get_records_sql($SQL);
}

function get_user_points_share()
{
    global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }
    if ($selectedcompany) {
        $SQL = "SELECT us.*, u.username, u1.username  AS tousername FROM {user_points_share} us 
        INNER JOIN {user} u ON u.id = us.fromuserid
        INNER JOIN {user} u1 ON u1.id = us.touserid
        INNER JOIN {company_users} cu ON cu.userid = us.fromuserid
        WHERE cu.companyid = $selectedcompany
        ";
    } else {
        $SQL = "SELECT us.*, u.username, u1.username  AS tousername FROM {user_points_share} us 
            INNER JOIN {user} u ON u.id = us.fromuserid
            INNER JOIN {user} u1 ON u1.id = us.touserid
            ";
    }

    return $DB->get_records_sql($SQL);
}

function get_user_points_redeem()
{
    global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }
    if ($selectedcompany) {
        $SQL = "SELECT us.*, u.username, u.firstname, u.lastname, u.email FROM {user_points_log} us 
        INNER JOIN {user} u ON u.id = us.userid 
        INNER JOIN {company_users} cu ON cu.userid = us.userid
        WHERE us.point_type = 'redeem' AND cu.companyid = $selectedcompany
        ";
    } else {
        $SQL = "SELECT us.*, u.username, u.firstname, u.lastname, u.email FROM {user_points_log} us 
        INNER JOIN {user} u ON u.id = us.userid 
        WHERE us.point_type = 'redeem' GROUP BY us.points
        ";
    }

    return $DB->get_records_sql($SQL);
}

function add_point_log($userid, $pointtype, $action, $points)
{
    global $DB;
    $insert = new stdClass();
    //    if ($points > 0) {
    $insert->userid = $userid;
    $insert->point_type = $pointtype;
    $insert->action = $action;
    $insert->points = $points;
    $insert->timecreated = time();
    $insert->ip_addr = get_client_ip();
    if ($DB->insert_record('user_points_log', $insert)) {
        $operator = ($action == 'added') ? '+' : '-';
        $UPDATE = "UPDATE {user_points} SET available_points = (available_points $operator $insert->points) WHERE userid = $userid ";
        $DB->execute($UPDATE);

        //add total points
        if ($action == 'added') {
            $UPDATE = "UPDATE {user_points} SET total_points = (total_points + $insert->points) WHERE userid =$userid ";
            $DB->execute($UPDATE);
        }
    }

    //        return TRUE;
//    }
    return TRUE;
}

function add_category_point($categoryid, $assignpoint)
{
    global $DB;

    $getdata = $DB->get_record('assign_cat_point', array('categoryid' => $categoryid));
    if (empty($getdata)) {
        $insert = new stdClass();
        $insert->categoryid = $categoryid;
        $insert->assignpoint = $assignpoint;
        $insert->timecreated = time();
        $DB->insert_record('assign_cat_point', $insert);
        $message = "Assign point successfully";
        \core\notification::success($message);
    } else {
        $insert = new stdClass();
        $insert->id = $getdata->id;
        $insert->categoryid = $categoryid;
        $insert->assignpoint = $assignpoint;
        $insert->timecreated = time();
        $DB->update_record('assign_cat_point', $insert);
        $message = "Update point successfully";
        \core\notification::success($message);
    }

    return TRUE;
}


function get_spinwheel_button($userid)
{
    global $DB;

    //check if spin wheel points alloted to user for today
    $SQL = "SELECT * FROM {user_points_log} WHERE userid = $userid AND point_type = 'spinwheel'
                AND DATE_FORMAT(FROM_UNIXTIME(`timecreated`), '%Y-%m-%d') = CURDATE()";
    if (!$DB->record_exists_sql($SQL)) {
        return '<img id="spin_button" src="spin_off.png" alt="Spin" height="20" onClick="startSpin();"/>';
    }
    return '<i>You have won today\'s luck on wheel, try next day.</i>';
}

//leaderboard top three
function get_leaderboard_top()
{
    global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }
    if ($selectedcompany) {
        $sql = "SELECT u.*, p.available_points, p.userrank FROM {user_points} p 
    INNER JOIN {user} u ON u.id = p.userid 
    INNER JOIN {company_users} cu ON cu.userid = p.userid
    WHERE u.deleted = 0 AND u.suspended = 0 AND cu.companyid = $selectedcompany AND cu.managertype != 1 ORDER BY  p.available_points DESC LIMIT 0,10";
    } else {
        $sql = "SELECT u.*, p.available_points, p.userrank FROM {user_points} p 
        INNER JOIN {user} u ON u.id = p.userid
        WHERE u.deleted = 0 AND u.suspended = 0 ORDER BY  p.available_points DESC LIMIT 0,10";
    }
    //  include $CFG->dirroot . '/lib/badgeslib.php';
    require_once($CFG->libdir . '/badgeslib.php');


    $records = $DB->get_records_sql($sql);
    // Sort the records by available_points in descending order
    usort($records, function ($a, $b) {
        return $b->available_points <=> $a->available_points;
    });

    $i = 1;
    $table = '<div class="d-flex justify-content-center leader_card_c">'; // Start a flex container for centering

    foreach ($records as $row) {

        $usercontext = context_user::instance($row->id);
        $src = $CFG->wwwroot . "/pluginfile.php/$usercontext->id/user/icon/f1";
        $badges = $DB->get_records('badge_issued', array('userid' => $row->id));
        $customlevel = $DB->get_record('custom_level', array('level' => $row->userrank));
        if ($customlevel->id != '' && $customlevel->id < 10) {
            $requestbtn = '<a href="' . $CFG->wwwroot . '/local/mydashboard/sme_request.php?id=' . $row->id . '" type="button" class="btn btn-primary question">Send Request</a>';
        } else {
            $requestbtn = '';
        }

        // Assign the order based on the counter
        if ($i == 1) {
            $specialClass = 'order-2 mx-3'; // Highest points in the middle
        } elseif ($i == 2) {
            $specialClass = 'order-1 pt-5'; // Second highest on the left
        } else {
            $specialClass = 'order-3 pt-5'; // Third highest on the right
        }

        $table .= '<div class="card pt-3 pb-3 ' . $specialClass . '" style="width: 12rem;">
        <span class="cap"></span>
      
                  <img class="card-img-top rounded-circle align-self-center" src="' . $src . '" alt="User icon">
                  <span class="circle_l">' . $i . '</span>
                  <div class="leader-name mt-5">
                    <h5 class="card-title text-center">' . $row->firstname . ' ' . $row->lastname . '</h5>
                    <h2 class="card-text coins text-center">' . $row->available_points . '</h2>
                  </div>
                </div>';

        if ($i >= 3) {
            break; // Break the loop after processing 3 records
        }
        $i++;
    }
    $table .= '</div>'; // Close the flex container

    return $table;
}


function get_leaderboard()
{
    global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }
    if ($selectedcompany) {
        $sql = "SELECT u.*, p.available_points, p.userrank FROM {user_points} p 
    INNER JOIN {user} u ON u.id = p.userid 
    INNER JOIN {company_users} cu ON cu.userid = p.userid
    WHERE u.deleted = 0 AND u.suspended = 0 AND cu.companyid = $selectedcompany AND cu.managertype != 1 ORDER BY  p.available_points DESC LIMIT 0,10";
    } else {
        $sql = "SELECT u.*, p.available_points, p.userrank FROM {user_points} p 
        INNER JOIN {user} u ON u.id = p.userid
        WHERE u.deleted = 0 AND u.suspended = 0 ORDER BY  p.available_points DESC LIMIT 0,10";
    }
    //  include $CFG->dirroot . '/lib/badgeslib.php';
    require_once($CFG->libdir . '/badgeslib.php');


    $records = $DB->get_records_sql($sql);
    $i = 1;
    $table = '';
    foreach ($records as $row) {

        $usercontext = context_user::instance($row->id);
        $src = $CFG->wwwroot . "/pluginfile.php/$usercontext->id/user/icon/f1";
        $badges = $DB->get_records('badge_issued', array('userid' => $row->id));
        $customlevel = $DB->get_record('custom_level', array('level' => $row->userrank));
        if ($customlevel->id != '' && $customlevel->id < 10) {
            $requestbtn = '<a href="' . $CFG->wwwroot . '/local/mydashboard/sme_request.php?id=' . $row->id . '" type="button" class="btn btn-primary question">Send Request</a>';
        } else {
            $requestbtn = '';
        }

        $table .= '<tr>
                      <th scope="row"> <span>' . $i . '</span></th>
                      <td>
                        <div class="d-flex align-items-center">
                          <img class="rounded-circle" src="' . $src . '" width="30">
                          <div class="ms-2">' . $row->firstname . ' ' . $row->lastname . '</div>
                          <i class="fa fa-paper-plane ml-2 sendmsg" value="' . $row->id . '"></i>
                        </div>
                      </td>
                      <td>' . $row->userrank . '</td>
                      <td>' . $row->department . '</td>
                      <td>' . $row->available_points . '</td>
                      <td class="badgesData">
                        <div class="d-flex align-items-center">';
        foreach ($badges as $badge) {

            $badgeObj = new badge($badge->badgeid);

            $badge_context = $badgeObj->get_context();

            $table .= print_badge_image($badgeObj, $badge_context, 'small');  //  size parameter could be 'small' or 'large'
        }



        $table .= '</div>
                      </td>
                      <td>' . $requestbtn . '</td>
                    </tr>
                    ';
        $i++;
    }
    return $table;
}

function get_lifetime_points($userid)
{
    global $DB;

    $SQL = "SELECT id, SUM(points) AS points FROM {user_points_log} WHERE userid = $userid AND action = 'added' ";
    $record = $DB->get_record_sql($SQL);

    return ($record->points > 0) ? $record->points : 0;
}

function get_redeemed_points($userid)
{
    global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }
    if ($selectedcompany) {
        $SQL = "SELECT upl.id, SUM(upl.points) AS points FROM {user_points_log} upl
        INNER JOIN {company_users} cu ON cu.userid = upl.userid
        WHERE upl.userid = $userid AND upl.point_type = 'redeem' AND cu.companyid = $selectedcompany";
    } else {
        $SQL = "SELECT id, SUM(points) AS points FROM {user_points_log} WHERE userid = $userid AND point_type = 'redeem' ";
    }

    $record = $DB->get_record_sql($SQL);

    return ($record->points > 0) ? $record->points : 0;
}

function get_lastfive_spin($userid)
{
    global $DB;
    //[0, 0], [1, 10], [2, 23], [3, 17], [4, 18], [5, 9],
    $SQL = "SELECT * FROM {user_points_log} WHERE userid = $userid AND point_type = 'spinwheel'
             ORDER BY timecreated DESC LIMIT 5";
    $array = "[0,0],";
    $records = $DB->get_records_sql($SQL);
    $records = array_reverse($records);
    $i = 1;
    foreach ($records as $row) {
        $array .= "[$i, $row->points],";
        $i++;
    }
    return $array;
}

function get_lastfive_login($userid)
{
    global $DB;
    //[0, 0], [1, 10], [2, 23], [3, 17], [4, 18], [5, 9],
    $SQL = "SELECT * FROM {user_points_log} WHERE userid = $userid AND point_type = 'login'
             ORDER BY timecreated DESC LIMIT 5";
    $array = "[0,0],";
    $records = $DB->get_records_sql($SQL);
    $records = array_reverse($records);
    $i = 1;
    foreach ($records as $row) {
        $array .= "[$i, $row->points],";
        $i++;
    }
    return $array;
}

function get_lastfive_quiz($userid)
{
    global $DB;
    //[0, 0], [1, 10], [2, 23], [3, 17], [4, 18], [5, 9],
    $SQL = "SELECT * FROM {user_points_log} WHERE userid = $userid AND point_type = 'quiz'
             ORDER BY timecreated DESC LIMIT 5";
    $array = "[0,0],";
    $records = $DB->get_records_sql($SQL);
    $records = array_reverse($records);
    $i = 1;
    foreach ($records as $row) {
        $array .= "[$i, $row->points],";
        $i++;
    }
    return $array;
}

function get_my_scratchcard($userid)
{
    global $DB, $CFG;

    $SQL = "SELECT * FROM {user_scratchcard} WHERE userid = $userid AND redeemed = 0 AND card_type = 'rank' LIMIT 3";
    $records = $DB->get_records_sql($SQL);

    $card = '';
    $i = 1;
    $points = array();
    $number = array();
    foreach ($records as $row) {
        $card .= '&nbsp;&nbsp;&nbsp;<div id="demo' . $i . '" class="scratchpad" scid="' . $row->id . '" point="' . $row->point . '"></div>&nbsp;&nbsp;&nbsp;';
        $i++;
        $points[] = $CFG->wwwroot . '/local/mydashboard/sunil/images/' . $row->point . '.jpg';

        if ($row->point <= 20) {
            $number[] = $CFG->wwwroot . '/local/mydashboard/sunil/images/s1-20.jpg';
        } else if ($row->point > 20 && $row->point <= 35) {
            $number[] = $CFG->wwwroot . '/local/mydashboard/sunil/images/s25-35.jpg';
        } else if ($row->point > 35 && $row->point <= 50) {
            $number[] = $CFG->wwwroot . '/local/mydashboard/sunil/images/s40-50.jpg';
        } else {
            $number[] = $CFG->wwwroot . '/local/mydashboard/sunil/images/s1-20.jpg';
        }
    }
    return array($card, $points, $number);
}

function get_scratch_counter($userid)
{
    global $DB;

    $SQL = "SELECT * FROM {user_scratchcard} WHERE userid = $userid AND redeemed = 0 AND card_type = 'rank'";
    $records = $DB->get_records_sql($SQL);

    $count = count($records);

    if ($count > 3) {
        return "3 / $count";
    }
    return "$count / $count";
}

function get_client_ip()
{
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if (getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if (getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if (getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if (getenv('HTTP_FORWARDED'))
        $ipaddress = getenv('HTTP_FORWARDED');
    else if (getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function get_quiz_passed($marks)
{
    if ($marks > 95) {
        return 200;
    } else if ($marks > 90 && $marks <= 95) {
        return 150;
    } else if ($marks > 85 && $marks <= 90) {
        return 100;
    } else if ($marks > 80 && $marks <= 85) {
        return 80;
    } else if ($marks <= 80) {
        return 50;
    }
}

// smeleader task related code //

function get_user_point($categoryid, $perpage = '', $limit = '')
{
    global $CFG, $DB, $USER, $PAGE, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = 0;
    }

    $get_details = array();
    // $totalcount = "";
    if ($categoryid) {
        $getcourse = $DB->get_records('course', array('category' => $categoryid));

        $contextdata = array();
        foreach ($getcourse as $keyvalue) {
            $context = context_course::instance($keyvalue->id);
            //$contextdata .= $context->id;
            array_push($contextdata, $context->id);
        }
    }
    if ($contextdata) {
        $cxt = implode(",", $contextdata);
    }

    // print_r($cxt);
    $dd = array();
    if ($cxt) {
        if ($selectedcompany) {
            $getsql = " SELECT lxl.*, SUM(points) as pointsss FROM {local_xp_log} lxl 
                INNER JOIN {user} u ON u.id = lxl.userid 
                INNER JOIN {company_users} cu ON u.id = cu.userid 
                WHERE lxl.contextid IN($cxt) AND u.deleted = 0 AND cu.companyid = $selectedcompany Group by lxl.userid";
        } else {
            $getsql = " SELECT lxl.*, SUM(points) as pointsss FROM {local_xp_log} lxl 
                INNER JOIN {user} u ON u.id = lxl.userid 
                INNER JOIN {company_users} cu ON u.id = cu.userid 
                WHERE lxl.contextid IN($cxt) AND u.deleted = 0 Group by lxl.userid";
        }


    } else {
        if ($selectedcompany) {
            $getsql = " SELECT lxl.*, SUM(points) as pointsss FROM {local_xp_log} lxl 
            INNER JOIN {user} u ON u.id = lxl.userid 
            INNER JOIN {company_users} cu ON u.id = cu.userid 
            WHERE lxl.contextid = 0 AND u.deleted = 0 AND cu.companyid = $selectedcompany Group by lxl.userid";
        } else {
            $getsql = " SELECT lxl.*, SUM(points) as pointsss FROM {local_xp_log} lxl 
                INNER JOIN {user} u ON u.id = lxl.userid 
                INNER JOIN {company_users} cu ON u.id = cu.userid 
                WHERE lxl.contextid = 0 AND u.deleted = 0 Group by lxl.userid";
        }
    }

    if ($limit) {
        $get_detail_count = $DB->get_records_sql($getsql);
        $totalcount = count($get_detail_count);
        $getsql .= " LIMIT $perpage ,$limit ";
    }

    $get_details = $DB->get_records_sql($getsql);
    //     echo "<pre>";
    //     print_r($get_details);
    //     echo "</pre>";
    // exit();


    // if(empty($get_details)){
    //     echo get_string('norecordfound', 'local_mydashboard');
    //    // \core\notification::add(get_string('norecordfound', 'local_mydashboard'), \core\notification::ERROR);
    //   }
    $dd = array("name" => $get_details, "count" => $totalcount);
    return $dd;
}

function get_feedback_sme($categoryid, $perpage = '', $limit = '')
{
    global $CFG, $DB, $USER, $PAGE, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = 0;
    }

    $get_details = array();
    // $totalcount = "";


    // print_r($cxt);
    $dd = array();
    if ($categoryid != 0) {
        if ($selectedcompany) {
            $getsql = " SELECT slf.* FROM {sme_leader_feedback} slf
                INNER JOIN {user} u ON u.id = slf.smeleader_id 
                INNER JOIN {company_users} cu ON u.id = cu.userid 
                WHERE slf.categoryid = $categoryid AND u.deleted = 0 AND cu.companyid = $selectedcompany";
        } else {
            $getsql = " SELECT slf.* FROM {sme_leader_feedback} slf
            INNER JOIN {user} u ON u.id = slf.smeleader_id 
            INNER JOIN {company_users} cu ON u.id = cu.userid 
            WHERE slf.categoryid = $categoryid AND u.deleted = 0 ";
        }

    } else {
        if ($selectedcompany) {
            $getsql = " SELECT slf.* FROM {sme_leader_feedback} slf
            INNER JOIN {user} u ON u.id = slf.smeleader_id 
            INNER JOIN {company_users} cu ON u.id = cu.userid 
            WHERE u.deleted = 0 AND cu.companyid = $selectedcompany";
        } else {
            $getsql = " SELECT slf.* FROM {sme_leader_feedback} slf
                INNER JOIN {user} u ON u.id = slf.smeleader_id 
                INNER JOIN {company_users} cu ON u.id = cu.userid 
                WHERE u.deleted = 0 ";
        }
    }
    if ($limit) {
        $get_detail_count = $DB->get_records_sql($getsql);
        $totalcount = count($get_detail_count);
        $getsql .= " LIMIT $perpage ,$limit ";
    }

    $get_details = $DB->get_records_sql($getsql);

    // if(empty($get_details)){
    //     echo get_string('norecordfound', 'local_mydashboard');
    //     //\core\notification::add(get_string('norecordfound', 'local_mydashboard'), \core\notification::ERROR);
    //   }
    $dd = array("name" => $get_details, "count" => $totalcount);
    return $dd;
}
function get_rank_icon($rank)
{
    global $DB, $CFG;

    $level = $DB->get_record('custom_level', array('level' => $rank));

    if ($level) {
        if ($level->icon != NULL) {
            return '<img src="' . $CFG->wwwroot . '/local/mydashboard/images/' . $level->icon . '" width="80">';
        }
    }
    return '';
}
function get_rank_icon_api($rank)
{
    global $DB, $CFG;

    $level = $DB->get_record('custom_level', array('level' => $rank));

    if ($level) {
        if ($level->icon != NULL) {
            return $CFG->wwwroot . '/local/mydashboard/images/' . $level->icon;
        }
    }
    return '';
}
function get_aproval_request($perpage = '', $limit = '')
{
    global $USER, $CFG, $DB, $OUTPUT, $SESSION;
    if (!empty($SESSION->currenteditingcompany)) {
        $selectedcompany = $SESSION->currenteditingcompany;
    } else if (!empty($USER->profile->company)) {
        $usercompany = company::by_userid($USER->id);
        $selectedcompany = $usercompany->id;
    } else {
        $selectedcompany = "";
    }

    $get_details = array();
    // $totalcount = "";


    // print_r($cxt);
    $dd = array();
    if ($selectedcompany) {
        $getsql = " SELECT sr.* FROM {send_request} sr 
            INNER JOIN {company_users} cu ON cu.userid = sr.from_userid 
            WHERE cu.companyid = $selectedcompany ";
    } else {
        $getsql = " SELECT * FROM {send_request} ";
    }


    if ($limit) {
        $get_detail_count = $DB->get_records_sql($getsql);
        $totalcount = count($get_detail_count);
        $getsql .= " LIMIT $perpage ,$limit ";
    }

    $get_details = $DB->get_records_sql($getsql);

    // if(empty($get_details)){
    //     echo get_string('norecordfound', 'local_mydashboard');
    //     //\core\notification::add(get_string('norecordfound', 'local_mydashboard'), \core\notification::ERROR);
    //   }
    $dd = array("name" => $get_details, "count" => $totalcount);
    return $dd;
}
function get_my_scratchcard_api($userid)
{
    global $DB, $CFG;

    $SQL = "SELECT * FROM {user_scratchcard} WHERE userid = $userid AND redeemed = 0 AND card_type = 'rank' LIMIT 3";
    $records = $DB->get_records_sql($SQL);

    $i = 1;
    $output = array();
    foreach ($records as $row) {
        $array = array();
        $array['scid'] = $row->id;
        $array['point'] = $row->point;
        $array['pointimage'] = $CFG->wwwroot . '/local/mydashboard/sunil/images/' . $row->point . '.jpg';

        if ($row->point <= 20) {
            $array['scratchimage'] = $CFG->wwwroot . '/local/mydashboard/sunil/images/s1-20.jpg';
        } else if ($row->point > 20 && $row->point <= 35) {
            $array['scratchimage'] = $CFG->wwwroot . '/local/mydashboard/sunil/images/s25-35.jpg';
        } else if ($row->point > 35 && $row->point <= 50) {
            $array['scratchimage'] = $CFG->wwwroot . '/local/mydashboard/sunil/images/s40-50.jpg';
        } else {
            $array['scratchimage'] = $CFG->wwwroot . '/local/mydashboard/sunil/images/s1-20.jpg';
        }

        $output[] = $array;
    }
    return $output;
}
function get_leaderboard_api()
{
    global $DB, $CFG;
    include $CFG->dirroot . '/lib/badgeslib.php';
    $sql = "SELECT u.*, p.available_points, p.userrank FROM {user_points} p INNER JOIN {user} u ON u.id = p.userid
            ORDER BY available_points DESC LIMIT 0,10";

    $records = $DB->get_records_sql($sql);
    $output = [];
    foreach ($records as $row) {
        $array = array();
        $usercontext = context_user::instance($row->id);
        $array['image'] = $CFG->wwwroot . "/pluginfile.php/$usercontext->id/user/icon/f1";
        $badges = $DB->get_records('badge_issued', array('userid' => $row->id));
        $array['name'] = $row->firstname . ' ' . $row->lastname;
        $array['rank_icon'] = get_rank_icon_api($row->userrank);
        $array['user_rank'] = $row->userrank;
        $array['department'] = $row->department;
        $array['available_points'] = $row->available_points;

        foreach ($badges as $badge) {

            $badgeObj = new badge($badge->badgeid);
            $badge_context = $badgeObj->get_context();

            //  $table .= print_badge_image($badgeObj, $badge_context, 'small');
            $array['badges'][] = print_badge_image($badgeObj, $badge_context, 'small'); //  size parameter could be 'small' or 'large'
        }
        $output[] = $array;
    }
    return $output;
}
function get_spinwheel_button_api($userid)
{
    global $DB;

    //check if spin wheel points alloted to user for today
    $SQL = "SELECT * FROM {user_points_log} WHERE userid = $userid AND point_type = 'spinwheel'
                AND DATE_FORMAT(FROM_UNIXTIME(`timecreated`), '%Y-%m-%d') = CURDATE()";
    if (!$DB->record_exists_sql($SQL)) {
        return 'true';
    }
    return '<i>You have won today\'s luck on wheel, try next day.</i>';
}
function changePassword($userid, $newpass, $cpass)
{
    global $DB;

    if ($newpass == $cpass) {
        //check if the otp is still valid
        if ($user = $DB->get_record('user', array('id' => $userid, 'deleted' => 0, 'suspended' => 0))) {
            //reset password
            $update = new stdClass();
            $update->id = $userid;
            $update->password = hash_internal_user_password($newpass);
            if ($DB->update_record('user', $update)) {
                $return = array('status' => '1', 'message' => 'Password changed successfully');
            }
        } else {
            $return = array('status' => '0', 'message' => 'Invalid user');
        }
    } else {
        $return = array('status' => '0', 'message' => 'Password not matched');
    }
    return $return;
}
