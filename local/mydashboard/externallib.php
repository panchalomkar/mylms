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
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 *
 * @package    local_vedificboard
 * @copyright  2019 VedificBoard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/mydashboard/lib.php');
include_once $CFG->dirroot . '/grade/querylib.php';
//require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->libdir . '/gradelib.php');

//use grade_item;
class local_mydashboard_external extends external_api {

    /**
     * Returns the description of external function parameters.
     *
     * @return external_function_parameters.
     */
    public static function user_reward_points_parameters() {
        return new external_function_parameters(
                array(
            'username' => new external_value(PARAM_USERNAME, 'Student username')
                )
        );
    }

    /**
     * Search users.
     *
     * @param string $query
     * @param string $capability
     * @param int $limitfrom
     * @param int $limitnum
     * @return array
     */
    public static function user_reward_points($username) {
        require_once 'lib.php';
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::user_reward_points_parameters(), array(
                    'username' => $username
        ));
        $result = [];
        $user = $DB->get_record('user', array('username' => $params['username'], 'deleted' => 0));
        if (!$user) {
            $result['error'] = true;
            $result['errormessage'] = 'Invalid username, not found user with this username';
            return $result;
        }
        //print_object($username);
        $output = array();
        $output['fullname'] = $user->firstname . ' ' . $user->lastname;
        $output['email'] = $user->email;
        $output['department'] = $user->department;
        $output['designation'] = $user->institution;
        $output['empcode'] = $user->username;
        $output['phone'] = $user->phone1;
        $usercontext = context_user::instance($user->id);
        $output['profileimage'] = $src = $CFG->wwwroot . "/pluginfile.php/$usercontext->id/user/icon/f1";
        $output['my_rank'] = get_my_rank($user->id);
        $output['available_points'] = get_available_points($user->id);
        $output['total_points'] = get_total_points($user->id);
        $output['redeem_points'] = get_redeemed_points($user->id);
        $output['average_grade'] = get_user_grade($user->id);

        set_my_rank($user->id);
        $output['my_rank'] = get_my_rank($user->id);
        $output['spinbutton'] = get_spinwheel_button_api($user->id);
        $nextlevel = get_next_level($user->id);
        $output['points_needed'] = $nextlevel[0];
        $output['grade_needed'] = $nextlevel[1];
        $output['nextlevel'] = $nextlevel[2];
        $output['available_points'] = get_available_points($user->id);
        $output['total_points'] = get_total_points($user->id);
        $output['login_points'] = $l = get_points($user->id, 'login');
        $output['quiz_points'] = $q = get_points($user->id, 'quiz');
        $output['spinwheel_points'] = $s = get_points($user->id, 'spinwheel');
        $output['rewards_received_points'] = $r = get_rewards_points_received($user->id);

        $result['reward_data'] = $output;

        echo json_encode($result);
        die;
    }

    /**
     * Returns description of external function result value.
     *
     * @return external_description
     */
    public static function user_reward_points_returns() {

        return new external_single_structure(
                array(
            'count' => new external_value(PARAM_RAW, 'count'),
            'users' => new external_multiple_structure(
                    new external_single_structure(
                            array(
                        'userid' => new external_value(PARAM_RAW, 'userid'),
                        'fullname' => new external_value(PARAM_RAW, 'fullname'),
                            )
                    )
            )
                )
        );
    }

    public static function get_leaderboard_parameters() {
        return new external_function_parameters(
                array(
            'userid' => new external_value(PARAM_INT, 'Student id')
                )
        );
    }

    public static function get_leaderboard($userid) {
        require_once 'lib.php';
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::get_leaderboard_parameters(), array(
                    'userid' => $userid
        ));

        $result = get_leaderboard_api();

        echo json_encode($result);
        die;
    }

    public static function get_leaderboard_returns() {

        return new external_single_structure(
                array(
            'count' => new external_value(PARAM_RAW, 'count'),
            'users' => new external_multiple_structure(
                    new external_single_structure(
                            array(
                        'userid' => new external_value(PARAM_RAW, 'userid'),
                        'fullname' => new external_value(PARAM_RAW, 'fullname'),
                            )
                    )
            )
                )
        );
    }

    public static function add_point_parameters() {
        return new external_function_parameters(
                array(
            'userid' => new external_value(PARAM_INT, 'Student id'),
            'type' => new external_value(PARAM_RAW, 'Student id'),
            'point' => new external_value(PARAM_INT, 'Student id'),
                )
        );
    }

    public static function add_point($userid, $type, $point) {
        require_once 'lib.php';
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::add_point_parameters(), array(
                    'userid' => $userid,
                    'type' => $type,
                    'point' => $point,
        ));

        add_point_log($userid, $type, 'added', $point);

        echo json_encode(array('success' => 'true'));
        die;
    }

    public static function add_point_returns() {

        return new external_single_structure(
                array(
                )
        );
    }

    // GET SCRATCH CARD
    public static function scratch_card_parameters() {
        return new external_function_parameters(
                array(
            'userid' => new external_value(PARAM_INT, 'Student id')
                )
        );
    }

    public static function scratch_card($userid) {
        require_once 'lib.php';
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::scratch_card_parameters(), array(
                    'userid' => $userid
        ));

        $cards = get_my_scratchcard_api($userid);

        echo json_encode($cards);
        die;
    }

    public static function scratch_card_returns() {

        return new external_single_structure(
                array(
                )
        );
    }
        //CHANGE PASSWORD

    public static function change_password_parameters() {
        return new external_function_parameters(
                array(
            'userid' => new external_value(PARAM_TEXT, 'User id'),
            'newpassword' => new external_value(PARAM_TEXT, 'Password'),
            'confirmpassword' => new external_value(PARAM_TEXT, 'Password'),
                )
        );
    }

    /**
     * Returns general information about files in the user private files area.
     *
     * @param int $userid Id of the user, default to current user.
     * @return array of warnings and file area information
     * @since Moodle 3.4
     * @throws moodle_exception
     */
    public static function change_password($userid, $newpassword, $confirmpassword) {
        global $DB;

        $params = self::validate_parameters(self::change_password_parameters(), array('userid' => $userid, 'newpassword' => $newpassword, 'confirmpassword' => $confirmpassword));

        $result = changePassword($userid, $newpassword, $confirmpassword);

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.4
     */
    public static function change_password_returns() {
        return new external_single_structure(
                array(
            'status' => new external_value(PARAM_TEXT, 'Response'),
            'message' => new external_value(PARAM_TEXT, 'Message'),
                )
        );
    }

}
