<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

define('AJAX_SCRIPT', true);

include_once '../../config.php';
include_once 'lib.php';
global $DB, $USER, $CFG;

$action = $_REQUEST['action'];

switch ($action) {
    case 'SEARCHUSERS':
        $search = '';
        $searchtext = optional_param('search', '', PARAM_RAW);
        $av_poiints = optional_param('av_poiints', 0, PARAM_INT);
        if ($searchtext) {
            $search = " AND (u.firstname LIKE '%$searchtext%' OR u.lastname LIKE '%$searchtext%' OR u.email LIKE '%$searchtext%') ";
        }
        $echo = 'No match found';

        $SQL = "SELECT u.* FROM mdl_message_contacts c INNER JOIN mdl_user u ON u.id = c.contactid
                    WHERE u.lastaccess > 0 AND c.userid = $USER->id $search
                    UNION
                SELECT u.* FROM mdl_message_contacts c INNER JOIN mdl_user u ON u.id = c.userid
                    WHERE u.lastaccess > 0 AND c.contactid = $USER->id $search";
        $records = $DB->get_records_sql($SQL);

        if ($records) {
            $echo = '<form id="sharepointsform" method="POST"><ul class="list-group">';
            $echo .= '<input type="hidden" value="' . $av_poiints . '" name="av_poiints">';

            foreach ($records as $record) {
                $usercontext = context_user::instance($record->id);
                $image = '<img src="' . $CFG->wwwroot . '/pluginfile.php/' . $usercontext->id . '/user/icon/f3" width="50">';
                $echo .= '<li class="list-group-item d-flex justify-content-between align-items-center">
                            ' . $image . '  ' . $record->firstname . ' ' . $record->lastname . '
                            <span class="badge badge-primary badge-pill">
                            <input type="hidden" name="userids[]" value="' . $record->id . '">
                            <input type="number"  min="1" oninput="validity.valid||(value="");" class="form-control" name="points[]" value="">
                                </span>
                          </li>';
            }
            $echo .= '</ul></form>';
        }


        echo $echo;

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
        $av_points = $_POST['av_poiints'];
        $redeemable = 0;
        if ($av_points >= 5000) {
            $redeemable = 5000;
        }
        echo 'Life time points : ' . get_lifetime_points($USER->id) . '<br>';
        echo 'Burn out points : ' . get_redeemed_points($USER->id) . '<br>';
        echo '<table class="bg-white table table-hover">
            <thead>
            <tr><td>Total Points</td><td>Redeemable Points</td></tr>
            </thead>
            <tbody>
            <tr><td>' . $av_points . '</td>
               <td>' . $redeemable . ' (*<i>5000 points can be redeem at once</i>)</td>
                   <input type="hidden"  id="idredeem-points" value="' . $redeemable . '"></tr>
            </tbody>
        </table>';
        break;

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
