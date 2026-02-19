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
 * @package    get_clt_wrong_questions
 * @category   external
 * @copyright  2023 Sumit Negi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.9
 */
class get_clt_wrong_questions extends \external_api {

    /**
     * Describes the parameters for get_clt_wrong_questions.
     *
     * @return external_function_parameters
     * @since Moodle 3.9
     */
    public static function get_clt_wrong_questions_parameters() {
        return new external_function_parameters(
                [
            'userid' => new external_value(PARAM_INT, 'userid id'),
            'chapterid' => new external_value(PARAM_INT, 'userid id', VALUE_DEFAULT, 0)
                ]
        );
    }

    public static function get_clt_wrong_questions($userid, $chapterid) {
        global $USER, $DB;
        $params = self::validate_parameters(
                        self::get_clt_wrong_questions_parameters(),
                        [
                            'userid' => $userid,
                            'chapterid' => $chapterid
                        ]
        );
        $result = [];
        $courses = enrol_get_users_courses($params['userid']);
        foreach ($courses as $course) {
            $quizattempt = self::get_quiz($course->id, $params['userid'], 'clt', $chapterid);

            if ($quizattempt) {
                list($attemptobj, $displayoptions) = self::validate_attempt_review(['attemptid' => $quizattempt->attemptid]);

                if ($params['page'] !== -1) {
                    $page = $attemptobj->force_page_number_into_range($params['page']);
                } else {
                    $page = 'all';
                }

                // Prepare the output.
                //$result['attempt'] = $attemptobj->get_attempt();
                $uniqueid = $result['attempt']->uniqueid;
                /* $result['CLTs'][] = [
                  'courseshortname' => $course->shortname,
                  'coursefullname' => $course->fullname,
                  'chapter' => $quizattempt->sectionname,
                  'quiz' => $quizattempt->name,
                  "cltquestions" => self::get_attempt_questions_data_new($attemptobj, false, 'all', $uniqueid)
                  ]; */
                $key = $quizattempt->coursemodule;

                $losdata = self::get_attempt_questions_data_new($attemptobj, false, 'all', $uniqueid);

                $result['CLTs'][$key]['cltquestions'] = $losdata;
                $result['CLTs'][$key]['courseshortname'] = $course->shortname;
                $result['CLTs'][$key]['coursefullname'] = $course->fullname;
                $result['CLTs'][$key]['chapter'] = $quizattempt->sectionname;
                $result['CLTs'][$key]['chapterid'] = $quizattempt->chapterid;
                $result['CLTs'][$key]['quiz'] = $quizattempt->name;
                /* foreach ($losdata as $loid => $lodata) {
                  $result['CLTs'][] = $lodata;
                  } */
            }
        }
        return $result;
    }

    /* public static function get_clt_wrong_questions_returns()
      {
      return new external_single_structure(
      [

      'lovideos' => new external_multiple_structure(
      new external_single_structure(
      [
      'courseshortname' => new external_value(PARAM_TEXT, 'courseshortname title'),
      'coursefullname' => new external_value(PARAM_TEXT, 'coursefullname title'),
      'chapter' => new external_value(PARAM_TEXT, 'chapter title'),
      'quiz' => new external_value(PARAM_TEXT, 'quiz title'),
      'name' => new external_value(PARAM_TEXT, 'question name'),
      'lovideo' => new external_value(PARAM_TEXT, 'Hint video', VALUE_OPTIONAL),
      ],
      '',
      VALUE_OPTIONAL
      ),
      '',
      VALUE_OPTIONAL

      ),
      ]
      );
      } */

    public static function get_clt_wrong_questions_returns() {
        return new external_single_structure(
                [
            'CLTs' => new external_multiple_structure(
                    new external_single_structure([
                        'courseshortname' => new external_value(PARAM_TEXT, 'courseshortname title'),
                        'coursefullname' => new external_value(PARAM_TEXT, 'coursefullname title'),
                        'chapter' => new external_value(PARAM_TEXT, 'chapter title'),
                        'chapterid' => new external_value(PARAM_TEXT, 'chapter id'),
                        'quiz' => new external_value(PARAM_TEXT, 'quiz title'),
                        'cltquestions' => new external_multiple_structure(
                                new external_single_structure(
                                        [
                                    'loname' => new external_value(PARAM_TEXT, 'question name'),
                                    'lovideo' => new external_value(PARAM_TEXT, 'Hint video', VALUE_OPTIONAL),
                                        ],
                                        '',
                                        VALUE_OPTIONAL
                                ),
                                '',
                                VALUE_OPTIONAL
                        )
                            ]),
                    '',
                    VALUE_OPTIONAL
            )
                ]
        );
    }

    protected function get_quiz($courseid, $userid, $tag, $chapterid = 0) {
        global $DB;
        $params = [
            'modulename' => 'quiz',
            'course' => $courseid,
            'itemtype' => 'course_modules',
            'tagname' => $tag,
            'userid' => $userid
        ];
        $chapterconditon = '';
        if($chapterid > 0) {
            $chapterconditon = " AND cw.id = $chapterid";
        }
        $sql = "SELECT qa.id as attemptid, cm.id AS coursemodule, m.*, cw.id as chapterid, cw.section, cm.visible AS visible,
        cm.groupmode, cm.groupingid, t.name as tagname, cw.name as sectionname
        FROM {course_modules} cm, {course_sections} cw, {modules} md,
        {quiz} m,  {tag} t, {tag_instance}  ti,
        {quiz_attempts} qa
        WHERE cm.instance = m.id AND
        cm.section = cw.id AND
        md.name = :modulename AND
        md.id = cm.module AND
        t.name = :tagname AND 
        t.id = ti.tagid AND 
        ti.itemid = cm.id AND 
        ti.itemtype = :itemtype AND
        m.course = :course AND 
        qa.quiz = m.id AND
        qa.userid = :userid
        $chapterconditon
        ORDER BY qa.id DESC";
        return $DB->get_record_sql($sql, $params);
    }

    protected static function validate_attempt_review($params) {

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

    private static function get_attempt_questions_data_new(quiz_attempt $attemptobj, $review, $page = 'all', $uniqueid) {
        global $PAGE, $CFG, $DB;

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

            if (isset($questiondef->hints[1]) && $questiondef->hints[1]->hint != '') {
                $fs = get_file_storage();
                $files = $fs->get_area_files($questiondef->contextid, 'question', 'hint', $questiondef->hints[1]->id);
                foreach ($files as $file) {
                    if ($file->get_mimetype() !== NULL) {
                        $filename = $file->get_filename();
                        $url = $CFG->wwwroot . '/pluginfile.php/' . $questiondef->contextid . '/question/hint/' . $uniqueid . '/' . $slot . '/' . $questiondef->hints[1]->id . '/' . $filename;
                        $questiondef->hints[1]->hint = $url;
                    }
                }
            }

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
            /* foreach ($questiondef->answers as $key => $answer) {
              $questiondef->answers[$key]->answer = file_rewrite_pluginfile_urls($answer->answer, 'questionfile.php', $questiondef->contextid, 'question', 'answer', $answer->id);
              } */

            $question = array(
                'submitresponse' => $submitresponse,
                'type' => $qtype,
                'custom_question' => $questiondef,
            );
            $qa = $attemptobj->get_question_attempt($slot);
            $correct = (int) $qa->format_mark(4);
            if (!$correct) {
                //get_question()
                $locategory = $DB->get_record('tool_lo_question_categories', ['categoryid' => $questiondef->category]);

                if ($locategory && $lo = \tool_lo\api::get_lo($locategory->lo)) {
                    if (!in_array($lo->id, $lostack)) {
                        $questions[$lo->id] = ['loname' => $lo->name, 'lovideo' => $lo->material];
                        $lostack[] = $lo->id;
                    }
                }
            }
            // $questions['serial_attempted'] = $serial;
        }
        return $questions;
    }

}
