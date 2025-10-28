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
 * Quiz external functions (Student)
 *
 * @package    student_quiz_apis
 * @category   external
 * @copyright  2021 Sumit Negi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */

namespace local_mod_webservices\external;

use external_value;
use external_single_structure;
use external_multiple_structure;
use external_function_parameters;
use external_warnings;
use context_module;
use external_util;
use quiz;
use quiz_attempt;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/question/editlib.php');

/**
 * Quiz external functions (Student)
 *
 * @package    quiz_attempt_wrong_questions
 * @category   external
 * @copyright  2023 Sumit Negi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.9
 */
class quiz_attempt_wrong_questions extends \external_api
{
    /**
     * Describes the parameters for get_quizzes_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.9
     */
    public static function quiz_attempt_wrong_questions_parameters()
    {
        return new external_function_parameters(
            ['attemptid' => new external_value(PARAM_INT, 'attempt id')]

        );
    }

    public static function quiz_attempt_wrong_questions($attemptid)
    {
        $params = self::validate_parameters(
            self::quiz_attempt_wrong_questions_parameters(),
            [
                'attemptid' => $attemptid
            ]
        );

        list($attemptobj, $displayoptions) = self::validate_attempt_review($params);

        if ($params['page'] !== -1) {
            $page = $attemptobj->force_page_number_into_range($params['page']);
        } else {
            $page = 'all';
        }

        // Prepare the output.
        $result = array();
        $result['attempt'] = $attemptobj->get_attempt();
        //custom get quiz point if any
        $result['quiz_points'] = self::get_quiz_points($result['attempt']->sumgrades, $result['attempt']->quiz);
        $result['questions'] = self::get_attempt_questions_data($attemptobj, true, 'all', true);

        $uniqueid = $result['attempt']->uniqueid;
        $result['new_questions'] = self::get_attempt_questions_data_new($attemptobj, false, 'all', $uniqueid);
        $result['attempted_questions'] = self::get_attempted_questions_serial($attemptobj, false, 'all', $uniqueid);

        $result['additionaldata'] = array();
        // Summary data (from behaviours).
        $summarydata = $attemptobj->get_additional_summary_data($displayoptions);
        foreach ($summarydata as $key => $data) {
            // This text does not need formatting (no need for external_format_[string|text]).
            $result['additionaldata'][] = array(
                'id' => $key,
                'title' => $data['title'], $attemptobj->get_quizobj()->get_context()->id,
                'content' => $data['content'],
            );
        }

        // Feedback if there is any, and the user is allowed to see it now.
        $grade = quiz_rescale_grade($attemptobj->get_attempt()->sumgrades, $attemptobj->get_quiz(), false);

        $feedback = $attemptobj->get_overall_feedback($grade);
        if ($displayoptions->overallfeedback && $feedback) {
            $result['additionaldata'][] = array(
                'id' => 'feedback',
                'title' => get_string('feedback', 'quiz'),
                'content' => $feedback,
            );
        }

        $result['grade'] = $grade;
        $result['warnings'] = $warnings;

        echo json_encode($result);
        die;
        return $result;
    }

    public static function quiz_attempt_wrong_questions_returns()
    {
    }

    protected static function validate_attempt_review($params)
    {

        $attemptobj = \quiz_attempt::create($params['attemptid']);
        $attemptobj->check_review_capability();

        $displayoptions = $attemptobj->get_display_options(true);
        if ($attemptobj->is_own_attempt()) {
            if (!$attemptobj->is_finished()) {
                throw new \moodle_quiz_exception($attemptobj->get_quizobj(), 'attemptclosed');
            } else if (!$displayoptions->attempt) {
                throw new \moodle_quiz_exception($attemptobj->get_quizobj(), 'noreview', null, '', $attemptobj->cannot_review_message());
            }
        } else if (!$attemptobj->is_review_allowed()) {
            throw new \moodle_quiz_exception($attemptobj->get_quizobj(), 'noreviewattempt');
        }
        return array($attemptobj, $displayoptions);
    }

    /**
     * Return questions information for a given attempt.
     *
     * @param  quiz_attempt  $attemptobj  the quiz attempt object
     * @param  bool  $review  whether if we are in review mode or not
     * @param  mixed  $page  string 'all' or integer page number
     * @return array array of questions including data
     */
    private static function get_attempt_questions_data(\quiz_attempt $attemptobj, $review, $page = 'all')
    {
        global $PAGE;

        $questions = array();
        $contextid = $attemptobj->get_quizobj()->get_context()->id;
        $displayoptions = $attemptobj->get_display_options($review);
        $renderer = $PAGE->get_renderer('mod_quiz');
        $contextid = $attemptobj->get_quizobj()->get_context()->id;

        foreach ($attemptobj->get_slots($page) as $slot) {
            $qtype = $attemptobj->get_question_type_name($slot);
            $qattempt = $attemptobj->get_question_attempt($slot);
            $questiondef = $qattempt->get_question(true);

            // Get response files (for questions like essay that allows attachments).
            $responsefileareas = [];
            foreach (\question_bank::get_qtype($qtype)->response_file_areas() as $area) {
                if ($files = $attemptobj->get_question_attempt($slot)->get_last_qt_files($area, $contextid)) {
                    $responsefileareas[$area]['area'] = $area;
                    $responsefileareas[$area]['files'] = [];

                    foreach ($files as $file) {
                        $responsefileareas[$area]['files'][] = array(
                            'filename' => $file->get_filename(),
                            'fileurl' => $qattempt->get_response_file_url($file),
                            'filesize' => $file->get_filesize(),
                            'filepath' => $file->get_filepath(),
                            'mimetype' => $file->get_mimetype(),
                            'timemodified' => $file->get_timemodified(),
                        );
                    }
                }
            }

            // Check display settings for question.
            $settings = $questiondef->get_question_definition_for_external_rendering($qattempt, $displayoptions);

            $question = array(
                'slot' => $slot,
                'type' => $qtype,
                'page' => $attemptobj->get_question_page($slot),
                'flagged' => $attemptobj->is_question_flagged($slot),
                'html' => $attemptobj->render_question($slot, $review, $renderer) . $PAGE->requires->get_end_code(),
                'responsefileareas' => $responsefileareas,
                'sequencecheck' => $qattempt->get_sequence_check_count(),
                'lastactiontime' => $qattempt->get_last_step()->get_timecreated(),
                'hasautosavedstep' => $qattempt->has_autosaved_step(),
                'settings' => !empty($settings) ? json_encode($settings) : null,
            );

            if ($attemptobj->is_real_question($slot)) {
                $question['number'] = $attemptobj->get_question_number($slot);
                $showcorrectness = $displayoptions->correctness && $qattempt->has_marks();
                if ($showcorrectness) {
                    $question['state'] = (string) $attemptobj->get_question_state($slot);
                }
                $question['status'] = $attemptobj->get_question_status($slot, $displayoptions->correctness);
                $question['blockedbyprevious'] = $attemptobj->is_blocked_by_previous_question($slot);
            }
            if ($displayoptions->marks >= \question_display_options::MAX_ONLY) {
                $question['maxmark'] = $qattempt->get_max_mark();
            }
            if ($displayoptions->marks >= \question_display_options::MARK_AND_MAX) {
                $question['mark'] = $attemptobj->get_question_mark($slot);
            }

            $questions[] = $question;
        }
        return $questions;
    }

    private static function get_attempt_questions_data_new(quiz_attempt $attemptobj, $review, $page = 'all', $uniqueid)
    {
        global $PAGE, $CFG;

        $questions = array();
        $contextid = $attemptobj->get_quizobj()->get_context()->id;
        $displayoptions = $attemptobj->get_display_options($review);
        $renderer = $PAGE->get_renderer('mod_quiz');


        foreach ($attemptobj->get_slots($page) as $slot) {
            $qtype = $attemptobj->get_question_type_name($slot);


            $qattempt = $attemptobj->get_question_attempt($slot);
            $questiondef = $qattempt->get_question(true);
            // Get response files (for questions like essay that allows attachments).
            $responsefileareas = [];
            foreach (\question_bank::get_qtype($qtype)->response_file_areas() as $area) {
                if ($files = $attemptobj->get_question_attempt($slot)->get_last_qt_files($area, $contextid)) {
                    $responsefileareas[$area]['area'] = $area;
                    $responsefileareas[$area]['files'] = [];

                    foreach ($files as $file) {
                        $responsefileareas[$area]['files'][] = array(
                            'filename' => $file->get_filename(),
                            'fileurl' => $qattempt->get_response_file_url($file),
                            'filesize' => $file->get_filesize(),
                            'filepath' => $file->get_filepath(),
                            'mimetype' => $file->get_mimetype(),
                            'timemodified' => $file->get_timemodified(),
                        );
                    }
                }
            }

            // Check display settings for question.
            $settings = $questiondef->get_question_definition_for_external_rendering($qattempt, $displayoptions);

            //echo json_encode($questiondef);
            $hints = [];
            foreach ($questiondef->hints as $hint) {
                $temp = $hint;
                $temp->hint = strip_tags($hint->hint);
                $hints[] = $temp;
            }

            $questiondef->hints = $hints;

            /*if ($questiondef->id == 170) {

                echo json_encode($questiondef);
                die;
            }*/
            /*if (isset($questiondef->hints[1]) && $questiondef->hints[1]->hint != '') {
                $fs = get_file_storage();
                $files = $fs->get_area_files($questiondef->contextid, 'question', 'hint', $questiondef->hints[1]->id);
                foreach ($files as $file) {
                    if ($file->get_mimetype() !== NULL) {
                        $filename = $file->get_filename();
                        $url = $CFG->wwwroot . '/pluginfile.php/' . $questiondef->contextid . '/question/hint/' . $uniqueid . '/' . $slot . '/' . $questiondef->hints[1]->id . '/' . $filename;
                        $questiondef->hints[1]->hint = $url;
                    }
                }
            }*/



            $submitresponse = '';
            if ($qtype == 'match') {
                require_once "$CFG->dirroot/question/type/match/renderer.php";
                $class = new \qtype_match_renderer($PAGE, '');
                $submitresponse = $class->get_match_response($qattempt);
                if (count($submitresponse) > 0) {
                    $serial[] = '1';
                } else {
                    $serial[] = '0';
                }
            }
            if ($qtype == 'multichoice') {
                require_once "$CFG->dirroot/question/type/multichoice/question.php";
                $class = new \qtype_multichoice_single_question();
                $submitresponse = $class->get_response($qattempt);
                if ($submitresponse != -1) {
                    $serial[] = '1';
                } else {
                    $serial[] = '0';
                }
            }
            if ($qtype == 'truefalse') {
                require_once "$CFG->dirroot/question/type/truefalse/renderer.php";
                $class = new \qtype_truefalse_renderer($PAGE, '');
                $submitresponse = $class->get_response($qattempt);
                if ($submitresponse != '') {
                    $serial[] = '1';
                } else {
                    $serial[] = '0';
                }
            }

            //replace actual image path
            // $coursecontext = context_course::instance($courseid);
            $questiondef->questiontext = file_rewrite_pluginfile_urls($questiondef->questiontext, 'questionfile.php', $questiondef->contextid, 'question', 'questiontext', $questiondef->id);
            foreach ($questiondef->answers as $key => $answer) {
                $questiondef->answers[$key]->answer = file_rewrite_pluginfile_urls($answer->answer, 'questionfile.php', $questiondef->contextid, 'question', 'answer', $answer->id);
            }
            // if ($questiondef->id == 170) {

            $questiondata = \question_bank::load_question_data($questiondef->id);
            if ($questiondata) {
                $questiondef->questiontext = file_rewrite_pluginfile_urls($questiondata->questiontext, 'questionfile.php', $questiondata->contextid, 'question', 'questiontext', $questiondata->id);
                //$questiondef->hints = $questiondata->hints;
            }

            //}
            $question = array(
                'slot' => $slot,
                'submitresponse' => $submitresponse,
                'type' => $qtype,
                'prefix' => 'q' . $uniqueid,
                'page' => $attemptobj->get_question_page($slot),
                'flagged' => $attemptobj->is_question_flagged($slot),
                'html' => $attemptobj->render_question($slot, $review, $renderer) . $PAGE->requires->get_end_code(),
                'custom_question' => $questiondef,
                'responsefileareas' => $responsefileareas,
                'sequencecheck' => $qattempt->get_sequence_check_count(),
                'lastactiontime' => $qattempt->get_last_step()->get_timecreated(),
                'hasautosavedstep' => $qattempt->has_autosaved_step(),
                'settings' => !empty($settings) ? json_encode($settings) : null,
            );

            if ($attemptobj->is_real_question($slot)) {
                $question['number'] = $attemptobj->get_question_number($slot);
                $showcorrectness = $displayoptions->correctness && $qattempt->has_marks();
                if ($showcorrectness) {
                    $question['state'] = (string) $attemptobj->get_question_state($slot);
                }
                $question['status'] = $attemptobj->get_question_status($slot, $displayoptions->correctness);
                $question['blockedbyprevious'] = $attemptobj->is_blocked_by_previous_question($slot);
            }
            if ($displayoptions->marks >= \question_display_options::MAX_ONLY) {
                $question['maxmark'] = $qattempt->get_max_mark();
            }
            if ($displayoptions->marks >= \question_display_options::MARK_AND_MAX) {
                $question['mark'] = $attemptobj->get_question_mark($slot);
            }
            $qa = $attemptobj->get_question_attempt($slot);
            $correct = (int)$qa->format_mark(4);
            if (!$correct) {
                //get_question()
                $qubaids = new \mod_quiz\question\qubaids_for_users_attempts(
                    $attemptobj->get_quizid(),
                    $attemptobj->get_userid(),
                    'all',
                    true
                );
                $randomloader = new \core_question\bank\random_question_loader($qubaids, array());
                $newqusetionid = $randomloader->get_next_question_id(
                    $questiondef->category,
                    true
                );

                $newquestion = \question_bank::load_question_data($newqusetionid);
                $newquestion->questiontext = file_rewrite_pluginfile_urls($newquestion->questiontext, 'questionfile.php', $newquestion->contextid, 'question', 'questiontext', $newquestion->id);
                if ($newquestion) {
                    $hints = [];
                    foreach ($newquestion->hints as $hint) {
                        $hints[] = $hint;
                    }
                    $newquestion->hints =  $hints;
                    $question['extra_question'] =   $newquestion;
                }
                $questions[] = $question;
            }
            // $questions['serial_attempted'] = $serial;
        }
        return $questions;
    }

    public function get_quiz_points($usersumgrades, $quizid)
    {
        global $DB;

        $quiz = $DB->get_record('quiz', array('id' => $quizid));

        $cm = get_coursemodule_from_instance('quiz', $quizid);
        $context = context_module::instance($cm->id);

        $gradeitem = $DB->get_record('grade_items', array('courseid' => $quiz->course, 'itemmodule' => 'quiz', 'iteminstance' => $quizid));
        if ($gradeitem->gradepass > 0) {
            $marks = ($quiz->grade / $quiz->sumgrades * $usersumgrades);
            if ($marks >= $gradeitem->gradepass) {
                //get quiz points
                $sql = "SELECT d.id, d.charvalue FROM {customfield_data} d 
                        INNER JOIN {customfield_field} f ON f.id = d.fieldid
                        WHERE f.shortname = 'quiz_points' AND d.contextid = $context->id
                        AND d.instanceid = $cm->id";
                if ($record = $DB->get_record_sql($sql)) {
                    return $record->charvalue;
                }
                return '';
            }
        }
        return '';
    }

    private static function get_attempted_questions_serial(quiz_attempt $attemptobj, $review, $page = 'all', $uniqueid)
    {
        global $PAGE, $CFG;

        $questions = array();
        $contextid = $attemptobj->get_quizobj()->get_context()->id;
        $displayoptions = $attemptobj->get_display_options($review);
        $renderer = $PAGE->get_renderer('mod_quiz');
        $contextid = $attemptobj->get_quizobj()->get_context()->id;

        foreach ($attemptobj->get_slots($page) as $slot) {
            $qtype = $attemptobj->get_question_type_name($slot);
            $qattempt = $attemptobj->get_question_attempt($slot);
            $questiondef = $qattempt->get_question(true);

            $submitresponse = '';
            if ($qtype == 'match') {
                require_once "$CFG->dirroot/question/type/match/renderer.php";
                $class = new \qtype_match_renderer($PAGE, '');
                $submitresponse = $class->get_match_response($qattempt);
                if (count($submitresponse) > 0) {
                    $serial[$questiondef->id] = '1';
                } else {
                    $serial[$questiondef->id] = '0';
                }
            } else if ($qtype == 'multichoice') {
                require_once "$CFG->dirroot/question/type/multichoice/question.php";
                $class = new \qtype_multichoice_single_question();
                $submitresponse = $class->get_response($qattempt);
                if ($submitresponse != -1) {
                    $serial[$questiondef->id] = '1';
                } else {
                    $serial[$questiondef->id] = '0';
                }
            } else if ($qtype == 'truefalse') {
                require_once "$CFG->dirroot/question/type/truefalse/renderer.php";
                $class = new \qtype_truefalse_renderer($PAGE, '');
                $submitresponse = $class->get_response($qattempt);
                if ($submitresponse != '') {
                    $serial[$questiondef->id] = '1';
                } else {
                    $serial[$questiondef->id] = '0';
                }
            } else {
                $serial[$questiondef->id] = '0';
            }
            // $questions['serial_attempted'] = $serial;
        }
        return $serial;
    }
}
