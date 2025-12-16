<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

define('AJAX_SCRIPT', true);
header('Content-Type: application/json');

include_once '../../config.php';
include_once 'lib.php';
global $DB, $USER, $CFG;

$action = $_REQUEST['action'];

switch ($action) {
  case 'SEARCHUSERS':

    global $DB, $USER, $CFG;

    $searchtext = trim(optional_param('search', '', PARAM_TEXT));
    $av_poiints = optional_param('av_poiints', 0, PARAM_INT);

    $params = [
        'userid' => $USER->id
    ];

    $searchsql = '';

    if ($searchtext !== '') {
        $searchsql = " AND (
            u.firstname LIKE :search1
            OR u.lastname  LIKE :search2
            OR u.email     LIKE :search3
        )";

        $params['search1'] = '%' . $searchtext . '%';
        $params['search2'] = '%' . $searchtext . '%';
        $params['search3'] = '%' . $searchtext . '%';
    }

  $sql = "SELECT u.id, u.firstname, u.lastname
        FROM {user} u
        WHERE u.deleted = 0
          AND u.suspended = 0
          AND u.id <> :userid
          AND u.id <> 2               
          AND u.username <> 'guest'   
          $searchsql
        ORDER BY u.firstname ASC";


    $records = $DB->get_records_sql($sql, $params);

    if (!$records) {
        echo json_encode([
            'status' => 0,
            'html'   => '<div class="text-center text-muted p-3">No match found</div>'
        ]);
        exit;
    }

    $html  = '<form id="sharepointsform">';
    $html .= '<input type="hidden" name="av_poiints" value="'.$av_poiints.'">';

    foreach ($records as $user) {

        $usercontext = context_user::instance($user->id);
        $avatar = $CFG->wwwroot.'/pluginfile.php/'.$usercontext->id.'/user/icon/f3';

        $html .= '
        <div class="d-flex align-items-center justify-content-between p-2 mb-2"
             style="border:1px solid #e6ebf2; border-radius:14px; background:#f9fbfd;">

            <div class="d-flex align-items-center gap-3">
                <img src="'.$avatar.'" class="rounded-circle" width="30" height="30">
                <div style="font-weight:600;">'.$user->firstname.' '.$user->lastname.'</div>
            </div>

            <input type="hidden" name="userids[]" value="'.$user->id.'">
            <input type="number"
                   name="points[]"
                   min="1"
                   class="form-control text-center"
                   placeholder="Points"
                   style="width:110px; border-radius:10px;">
        </div>';
    }

    $html .= '</form>';

    echo json_encode([
        'status' => 1,
        'html'   => $html
    ]);
    exit;
    break;


    case 'SHAREPOINTS':
        $points = $_POST['points'];
        $sum = array_sum($points);
        $av_points = $_POST['av_poiints'];

        if ($av_points >= $sum) {
            //check if daily share point limit available
            $SQL = "SELECT id, SUM(points) AS sum_share FROM {user_points_share} WHERE fromuserid = $USER->id 
                     AND DATE_FORMAT(FROM_UNIXTIME(`timecreated`), '%Y-%m-%d') = CURDATE()";
            $srec = $DB->get_record_sql($SQL);
            if ((100 - $srec->sum_share) >= $sum) {
                for ($i = 0; $i < count($points); $i++) {
                    if ($_POST['points'][$i] > 0) {
                        $share = new stdClass();

                        $share->fromuserid = $USER->id;
                        $share->touserid = $_POST['userids'][$i];
                        $share->points = $_POST['points'][$i];
                        $share->timecreated = time();

                        if ($DB->insert_record('user_points_share', $share)) {
                            //from user
                            add_point_log($USER->id, 'share', 'deducted', $share->points);

                            // to user
                            add_point_log($share->touserid, 'gift reward', 'added', $share->points);
                        }
                    }
                }
                echo 1;
            } else {
                echo 2;
            }
        } else {
            echo 0;
        }

        break;

case 'GETREDEEMPOINTS':

    $av_points = isset($_POST['av_poiints']) ? (int)$_POST['av_poiints'] : 0;

    $lifetime   = (int) get_lifetime_points($USER->id);
    $burnout    = (int) get_redeemed_points($USER->id);

    $redeemable = ($av_points >= 5000) ? 5000 : $av_points;

    header('Content-Type: application/json');

    echo json_encode([
        'lifetime'   => $lifetime,
        'burnout'    => $burnout,
        'total'      => $av_points,
        'redeemable' => $redeemable
    ]);
    exit;



    case 'REDEEMNOW';
        $point = $_POST['point'];
        if (add_point_log($USER->id, 'redeem', 'deducted', $_POST['point'])) {
            //send an email to admin
            $touser = get_admin();
            $subject = 'Ponts Redeem | ' . $USER->username;
            $messagehtml = '<html>
                            <body>
                                Hi ' . $touser->firstnae . ' ' . $touser->lasttname . ',<br><br>
                                ' . $USER->firstnae . ' ' . $USER->lasttname . ' has requested to redeem ' . $point . ' points.<br>
                                    Kindly take neccessary action.<br><br>
                                    
                                    Regards,<br>Team Ceasefire.
                                </body>
                            </html>';

            email_to_user($touser, $USER, $subject, '', $messagehtml);
            echo 1;
        } else {
            echo 2;
        }
        break;

    case 'SCRATCHCARD';
        $scid = $_POST['scid'];
        if ($record = $DB->get_record('user_scratchcard', array('userid' => $USER->id, 'id' => $scid))) {
            if (add_point_log($USER->id, 'scratchcard', 'added', $_POST['spoint'])) {
                $update = new stdClass();
                $update->id = $scid;
                $update->redeemed = 1;
                $DB->update_record('user_scratchcard', $update);
                echo $_POST['spoint'];
            }
        }
        break;

    case 'SPINWHEELPOINT':
        add_point_log($USER->id, 'spinwheel', 'added', $_POST['point']);
        echo $_POST['point'];
        break;

}
