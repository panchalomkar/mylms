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
 * Web service declarations
 *
 * @package    block_currentcourses
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
 require_once($CFG->dirroot . '/lib/completionlib.php');
class mod_videofile_external extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function data_for_video_attempt_parameters() {
        return new external_function_parameters(
            array(
                'attempt_id' => new external_value(PARAM_INT, 'attempt id',VALUE_OPTIONAL),
                'countstatus' => new external_value(PARAM_INT, 'count status',VALUE_OPTIONAL),
                'cmid' => new external_value(PARAM_INT, 'cm id', VALUE_OPTIONAL),
                'seekval' => new external_value(PARAM_INT, 'seek value', VALUE_OPTIONAL),
                'percentage' => new external_value(PARAM_INT, 'percent', VALUE_OPTIONAL),
                'second' => new external_value(PARAM_INT, 'second', VALUE_OPTIONAL),
                'course' => new external_value(PARAM_INT, 'course', VALUE_OPTIONAL),
                'id' => new external_value(PARAM_INT, 'id', VALUE_OPTIONAL),
            )
        );

    }

    
    public static function data_for_video_attempt($attempt_id, $countstatus, $cmid, $seekval, $percentage, $second, $course, $id) {
        global $DB, $USER, $PAGE;
        $params = self::validate_parameters(self::data_for_video_attempt_parameters(), ['attempt_id' => $attempt_id, 'countstatus' => $countstatus, 'cmid' => $cmid, 'seekval' => $seekval, 'percentage' => $percentage, 'second' => $second, 'course' => $course, 'id' => $id]); 
        //Add the seek query
        if($params['seekval'] > 0){
            $query = $DB->get_records_sql('SELECT second FROM {video_attempts} 
                WHERE userid = :userid AND cmid = :cmid 
                ORDER BY second 
                DESC', ['userid' => $USER->id, 'cmid' => $params['cmid'] ], 0, 1);

            
            $return['last'][] = array(
                'id' => 0,
                'userid' => $USER->id,
                'cmid' => $params['cmid'],
                'second' => $query->second,
                'timecreated' => '',
                'timemodified' => ''
            );
            if(intval($params['seekval']) > $query->second){
                $return['result'] = $query->second;
            } else {
                $return['result'] = $params['seekval'];
            }

            $return['current_id'] = 0;
            $return['status'] = true;
            $return['img'] = '';
            $return['return'] = true;
            return $return;

        } else { 
            if($params['attempt_id'] == 0){
                //Create one new record in the database
                $return = array();
                $last_attempt = $DB->get_records_sql('SELECT * FROM {video_attempts} 
                    WHERE cmid = ? AND userid = ? 
                    ORDER BY second 
                    DESC', array($params['cmid'], $USER->id), 0, 1);

                $li_second = 0;
                if(count($last_attempt) > 0){
                    $return['last'] = array();
                    foreach ($last_attempt as $key => $value) {
                        if ($last_attempt[ $key ]->second > $li_second ) { 
                            $li_second = $last_attempt[ $key ]->second; 
                        }
                        $return['last'][] = array(
                            'id' => $value->id,
                            'userid' => $value->userid,
                            'cmid' => $value->cmid,
                            'second' => $value->second,
                            'timecreated' => $value->timecreated,
                            'timemodified' => $value->timemodified,
                        );

                    }
                }

                $attempt = new stdClass();
                $attempt->userid = $USER->id;
                $attempt->cmid = $params['cmid'];
                $attempt->second = $li_second;
                $attempt->percentage = 0;
                $attempt->timecreated = date('U');
                $attempt->timemodified = date('U'); //print_R($attempt);
                $record_attempt = $DB->insert_record('video_attempts', $attempt);

                $return['current_id'] = $record_attempt;
                $return['status'] = true;
                $return['img'] = '';
                $return['return'] = true;
                $return['result'] = 0;
                return $return;
            } 
            else{
                //Get the attempt
                $attempt = $DB->get_record('video_attempts', array('id' => $params['id']));
                //Completion of the video
                
                //Get the cm
                $cm = get_coursemodule_from_id('videofile', $params['cmid'], 0, true);
                $videofile = $DB->get_record('videofile', array('id' => $cm->instance));
                
                $status = false;
                $imgUrl = false;
                $count = 0 ;
                //If the current percentage is > to percentage configure, complete the activity
                if($params['percentage'] > 0 && $videofile->videoprogress <= $params['percentage'] && $params['countstatus']){
                    //Get the completion library, Get the course of the activity and Get the completion info for the course
                    $course = get_course($params['course']);
                    $completioninfo = new completion_info($course);
                    $completion = $completioninfo->is_enabled($cm);
                    if($completion == COMPLETION_TRACKING_AUTOMATIC){

                       global $USER;
                       $completioninfo->update_state($cm, COMPLETION_COMPLETE, $USER->id);
                       $status = "completed";
                       $imgUrl = 'theme/image.php/paradiso/core/1564492370/i/completion-manual-y';
                   }
                }

                //Update the percetage of the attemp
               $attempt->second = $params['second'];
               $attempt->percentage = $params['percentage'];

                /**
                 * it creates var for validate response data
                 *
                 * @author Diego P.
                 * @since 2017-07-04
                 * @paradiso
                 */
                $record = $DB->update_record('video_attempts', $attempt);
                $ret ="";
                if( $record ) {
                    $ret = true;
                } else {
                    $ret = false;
                }
                $return['last'][] = array(
                    'id' => 0,
                    'userid' => $USER->id,
                    'cmid' => 0,
                    'second' => 0,
                    'timecreated' => '',
                    'timemodified' => ''
                );
                $return['current_id'] = 0;
                $return['status'] = $status;
                $return['img'] = $imgUrl;
                $return['return'] = $ret;
                $return['result'] = 0;
                return $return;
            }
        }
    }

    public static function data_for_video_attempt_returns() {
        return  new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'status'),
                'img' => new external_value(PARAM_TEXT, 'image'),
                'return' => new external_value(PARAM_BOOL, 'return'),
                'current_id' => new external_value(PARAM_INT, 'current id'),
                'last' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'id', VALUE_OPTIONAL),
                            'userid' => new external_value(PARAM_INT, 'user id', VALUE_OPTIONAL),
                            'cmid' => new external_value(PARAM_INT, 'cm id', VALUE_OPTIONAL),
                            'second' => new external_value(PARAM_INT, 'second', VALUE_OPTIONAL),
                            'timecreated' => new external_value(PARAM_TEXT, 'time create'),
                            'timemodified' => new external_value(PARAM_TEXT, 'time modified'),
                        )),
                ),
                'result' => new external_value(PARAM_INT, 'result if seek')
            )
        );
    }   
}