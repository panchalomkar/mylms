<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for local mydashboard.
 */
class local_trigger_observer {

    /**
     * Triggered via user login event.
     *
     * @param \core\event\user_loggedin $event
     */
    public static function addloginpoints(\core\event\user_loggedin $event) {
        global $DB, $CFG;
        include_once $CFG->dirroot . '/local/mydashboard/lib.php';

	$eventdata = (object) $event->get_data();
        $userid = $eventdata->objectid;
        //check if user login today
        $SQL = "SELECT * FROM {user_points_log} WHERE userid = $userid AND point_type = 'login'
                AND DATE_FORMAT(FROM_UNIXTIME(`timecreated`), '%Y-%m-%d') = CURDATE()";
        if (!$DB->record_exists_sql($SQL)) {

            //get the login points
            $points = self::getloginpoints($userid);

            if (add_point_log($userid, 'login', 'added', $points)) {
                
            }
        }

      

    }

    public static function getloginpoints($userid) {
        global $USER, $CFG, $DB, $OUTPUT, $SESSION;
        if (!empty($SESSION->currenteditingcompany)) {
            $selectedcompany = $SESSION->currenteditingcompany;
        } else if (!empty($USER->profile->company)) {
            $usercompany = company::by_userid($USER->id);
            $selectedcompany = $usercompany->id;
        } else {
            $selectedcompany = "";
        }

        $SQL = "SELECT * FROM {user_points_log} WHERE userid = $userid AND point_type = 'login' 
                AND DATE_FORMAT(FROM_UNIXTIME(`timecreated`), '%Y-%m-%d') = subdate(current_date, 1)";

        $record = $DB->get_record_sql($SQL);
        $loginpoint_day1 = get_config('local_mydashboard', 'loginpoint_day1'.'_'.$selectedcompany);
        $loginpoint_day2 = get_config('local_mydashboard', 'loginpoint_day2'.'_'.$selectedcompany);
        $loginpoint_day3 = get_config('local_mydashboard', 'loginpoint_day3'.'_'.$selectedcompany);
        $loginpoint_day4 = get_config('local_mydashboard', 'loginpoint_day4'.'_'.$selectedcompany);
        $loginpoint_day5 = get_config('local_mydashboard', 'loginpoint_day5'.'_'.$selectedcompany);


        if ($record) {
            if ($record->points < $loginpoint_day2) {
                return $loginpoint_day2;
            } else if ($record->points < $loginpoint_day3) {
                return $loginpoint_day3;
            } else if ($record->points < $loginpoint_day4) {
                return $loginpoint_day4;
            } else if ($record->points < $loginpoint_day5) {
                return $loginpoint_day5;
            } else {
                return $loginpoint_day5;
            }
	}
	
        return $loginpoint_day1;
    }

    public static function dailyquizsubmitted1(\mod_quiz\event\attempt_submitted $event) {
        global $USER, $CFG, $DB, $OUTPUT, $SESSION;
        if (!empty($SESSION->currenteditingcompany)) {
            $selectedcompany = $SESSION->currenteditingcompany;
        } else if (!empty($USER->profile->company)) {
            $usercompany = company::by_userid($USER->id);
            $selectedcompany = $usercompany->id;
        } else {
            $selectedcompany = "";
        }
        include_once $CFG->dirroot . '/local/mydashboard/lib.php';
        $eventdata = (object) $event->get_data();
//        print_object($eventdata);die;
        $userid = $eventdata->userid;
        //get the single activity daily quiz course
        $course = $DB->get_record('course', array('shortname' => 'dailyquiz'));
        $attempt = $DB->get_record('quiz_attempts', array('id' => $eventdata->objectid));
        $quiz = $DB->get_record('quiz', array('id' => $attempt->quiz, 'course' => $course->id));

        if ($quiz) {

            $gradeitem = $DB->get_record('grade_items', array('courseid' => $course->id, 'itemmodule' => 'quiz', 'iteminstance' => $attempt->quiz));


            $marks = ($quiz->grade / $quiz->sumgrades * $attempt->sumgrades);
            if ($marks >= $gradeitem->gradepass) {
                //check if user get daily quiz points today
                $SQL = "SELECT * FROM {user_points_log} WHERE userid = $userid AND point_type = 'quiz'
                AND DATE_FORMAT(FROM_UNIXTIME(`timecreated`), '%Y-%m-%d') = CURDATE()";
                if (!$DB->record_exists_sql($SQL)) {


                    //get the daily quiz points
                    $dailyquiz_point = get_config('local_mydashboard', 'dailyquiz_point'.'_'.$selectedcompany);

                    if (add_point_log($userid, 'quiz', 'added', $dailyquiz_point)) {
                        
                    }
                }
            }
        }
    }

    public static function newusercreated1(\core\event\user_created $event) {
        global $USER, $CFG, $DB, $OUTPUT, $SESSION;
        if (!empty($SESSION->currenteditingcompany)) {
            $selectedcompany = $SESSION->currenteditingcompany;
        } else if (!empty($USER->profile->company)) {
            $usercompany = company::by_userid($USER->id);
            $selectedcompany = $usercompany->id;
        } else {
            $selectedcompany = "";
        }
        include_once $CFG->dirroot . '/local/mydashboard/lib.php';
        $eventdata = (object) $event->get_data();

        $userid = $eventdata->objectid;
        //make first time user point entry
        if (!$DB->record_exists('user_points', array('userid' => $userid))) {
            $first = new stdClass();

            $first->userid = $userid;
            $first->available_points = 0;
            $first->total_points = 0;
            $first->timecreated = time();
            $first->userrank = '-';
            $first->timemodified = 0;

            $DB->insert_record('user_points', $first);
        }

        //get the welcome points
        $welcome_point = get_config('local_mydashboard', 'welcome_point'.'_'.$selectedcompany);
        //add welcome point to user

        add_point_log($userid, 'welcome', 'added', $welcome_point);
    }

    public static function checklevelup(\block_xp\event\user_leveledup $event) {
//        global $DB, $CFG;
//        include_once $CFG->dirroot . '/local/mydashboard/lib.php';
//        $eventdata = (object) $event->get_data();
//        $userid = $eventdata->userid;
//        $r_points = array(1, 2, 4, 0, 5, 7, 0, 10, 0, 12, 15, 0, 17, 18, 0, 20, 22, 25, 0, 27, 0, 30, 0);
//        //get the welcome points
//        $setting = get_config('local_mydashboard');
//        //number of scratch card added
//        $nos = $setting->rank_promote;
//        for ($i = 0; $i < $nos; $i++) {
//            //add scratch card
//            $k = array_rand($r_points);
//            $point = $r_points[$k];
//
//            $insert = new stdClass();
//
//            $insert->userid = $userid;
//            $insert->card_type = 'level_up';
//            $insert->point = $point;
//            $insert->redeemed = 0;
//            $insert->timecreated = time();
//
//            $DB->insert_record('user_scratchcard', $insert);
//        }
    }

    //course completion points
    public static function course_completion_points(\core\event\course_completed $event) {
        global $DB, $CFG;
        include_once $CFG->dirroot . '/local/mydashboard/lib.php';

        $eventdata = (object) $event->get_data();
        $courseid = $eventdata->courseid;
        $userid = $eventdata->userid;

        $field = $DB->get_record('customfield_field', array('shortname' => 'coursepoint'));
        if ($field) {
            $cpoint = $DB->get_record('customfield_field', array('fieldid' => $field->id, 'instanceid' => $courseid));

            if ($cpoint && $cpoint->value > 0) {
                add_point_log($userid, 'course_completion', 'added', $cpoint->value);
            }
        }
    }

    public static function coursequizsubmitted1(\mod_quiz\event\attempt_submitted $event) {
        global $USER, $CFG, $DB, $OUTPUT, $SESSION;
        if (!empty($SESSION->currenteditingcompany)) {
            $selectedcompany = $SESSION->currenteditingcompany;
        } else if (!empty($USER->profile->company)) {
            $usercompany = company::by_userid($USER->id);
            $selectedcompany = $usercompany->id;
        } else {
            $selectedcompany = "";
        }
        include_once $CFG->dirroot . '/local/mydashboard/lib.php';
        $eventdata = (object) $event->get_data();
//        print_object($eventdata);die;
        $userid = $eventdata->userid;
        //get the single activity daily quiz course

        $attempt = $DB->get_record('quiz_attempts', array('id' => $eventdata->objectid));
        $quiz = $DB->get_record('quiz', array('id' => $attempt->quiz));

        $sql = "SELECT * FROM {course} WHERE shortname != 'dailyquiz' AND id = $quiz->course";

        $course = $DB->get_record_sql($sql);
        if ($course) {

            $gradeitem = $DB->get_record('grade_items', array('courseid' => $course->id, 'itemmodule' => 'quiz', 'iteminstance' => $attempt->quiz));


            $marks = ($quiz->grade / $quiz->sumgrades * $attempt->sumgrades);
            if ($marks >= $gradeitem->gradepass) {
                //check if user get daily quiz points today

                if (!$DB->record_exists('user_scratchcard', array('userid' => $userid, 'itemid' => $quiz->id, 'card_type' => 'quiz'))) {

                    $quiz_scratch_card = get_config('local_mydashboard', 'quiz_scratch_card'.'_'.$selectedcompany);
                    $count = $quiz_scratch_card;
                    for ($i = 0; $i < $count; $i++) {
                        $items = array(5, 0, 5, 0, 10, 10, 0, 15, 15, 0, 20, 10, 0, 20, 10, 0, 25, 5, 15, 0, 25, 15, 0, 30, 10, 0, 35, 0, 0, 10, 40, 5, 45, 5, 50);
                        $scratch = new stdClass();

                        $scratch->userid = $userid;
                        $scratch->itemid = $quiz->id;
                        $scratch->card_type = 'quiz';
                        $scratch->point = $items[rand(0, count($items) - 1)];
                        $scratch->redeemed = 0;
                        $scratch->timecreated = time();

                        $DB->insert_record('user_scratchcard', $scratch);
                    }

                    $qp_point = get_quiz_passed($marks);
                    add_point_log($userid, 'quiz_passed', 'added', $qp_point);
                }
            }
        }
    }

}
