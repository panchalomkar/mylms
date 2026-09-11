<?php
/**
 * evaluation_api external API functions.
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_airoleplay\external;
defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use local_airoleplay_ai\orchestrator\ai_orchestrator;

class evaluation_api extends external_api {

    // ----------------------------------------------------------------
    // get_evaluation
    // ----------------------------------------------------------------

    public static function get_evaluation_parameters(): external_function_parameters {
        return new external_function_parameters([
            'session_id' => new external_value(PARAM_INT, 'Session ID'),
        ]);
    }

    public static function get_evaluation(int $session_id): array {
        global $DB, $USER;

        ['session_id' => $session_id] = self::validate_parameters(
            self::get_evaluation_parameters(), compact('session_id')
        );

        $session = $DB->get_record('airoleplay_session', ['id' => $session_id], '*', MUST_EXIST);

        // Access check.
        if ($session->userid !== $USER->id) {
            $cm = get_coursemodule_from_instance('airoleplay', $session->airoleplay);
            require_capability('mod/airoleplay:viewallattempts', \context_module::instance($cm->id));
        }

        // Build competency results.
        $competencies = $DB->get_records(
            'airoleplay_competency_result',
            ['session' => $session_id],
            'score DESC'
        );

        $comp_data = array_values(array_map(fn($c) => [
            'key'          => $c->competency_key,
            'label'        => $c->competency_label,
            'score'        => (float)$c->score,
            'max_score'    => (float)$c->max_score,
            'ai_rationale' => $c->ai_rationale ?? '',
            'evidence'     => $c->evidence ?? '',
        ], $competencies));

        // Decode JSON fields safely.
        $strengths      = json_decode($session->ai_strengths ?? '[]', true) ?: [];
        $improvements   = json_decode($session->ai_improvements ?? '[]', true) ?: [];
        $recommendations = json_decode($session->ai_recommendations ?? '[]', true) ?: [];
        $ideal_responses = json_decode($session->ai_ideal_responses ?? '[]', true) ?: [];
        $scores_detail  = json_decode($session->scores_detail ?? '{}', true) ?: [];
        $voice_analytics = json_decode($session->voice_analytics ?? '{}', true) ?: [];

        return [
            'session_id'       => $session->id,
            'status'           => $session->status,
            'evaluated'        => (bool)$session->evaluated_by_ai,
            'score_overall'    => (float)($session->score_overall ?? 0),
            'passed'           => (bool)$session->passed,
            'grade'            => (float)($session->grade ?? 0),
            'turn_count'       => (int)$session->turn_count,
            'duration_secs'    => (int)$session->duration_secs,
            'ai_summary'       => $session->ai_summary ?? '',
            'strengths'        => array_values(array_map('strval', $strengths)),
            'improvements'     => $improvements,
            'recommendations'  => array_values(array_map('strval', $recommendations)),
            'ideal_responses'  => $ideal_responses,
            'competencies'     => $comp_data,
            'scores_detail'    => $scores_detail,
            'voice_analytics'  => $voice_analytics,
            'pronunciation_score' => (float)($session->pronunciation_score ?? 0),
            'evaluation_time'  => (int)($session->evaluation_time ?? 0),
        ];
    }

    public static function get_evaluation_returns(): external_single_structure {
        return new external_single_structure([
            'session_id'      => new external_value(PARAM_INT,   'Session ID'),
            'status'          => new external_value(PARAM_TEXT,  'Session status'),
            'evaluated'       => new external_value(PARAM_BOOL,  'Whether AI evaluation is complete'),
            'score_overall'   => new external_value(PARAM_FLOAT, 'Overall score 0-100'),
            'passed'          => new external_value(PARAM_BOOL,  'Whether learner passed'),
            'grade'           => new external_value(PARAM_FLOAT, 'Gradebook grade'),
            'turn_count'      => new external_value(PARAM_INT,   'Total conversation turns'),
            'duration_secs'   => new external_value(PARAM_INT,   'Duration in seconds'),
            'ai_summary'      => new external_value(PARAM_TEXT,  'AI coaching summary'),
            'strengths'       => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Strength description')
            ),
            'improvements'    => new external_multiple_structure(
                new external_single_structure([
                    'area'         => new external_value(PARAM_TEXT, 'Improvement area'),
                    'specific_gap' => new external_value(PARAM_TEXT, 'Specific gap', VALUE_OPTIONAL),
                    'suggestion'   => new external_value(PARAM_TEXT, 'Suggestion'),
                ]), 'Areas for improvement', VALUE_OPTIONAL
            ),
            'recommendations' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Learning recommendation'), 'Recommendations', VALUE_OPTIONAL
            ),
            'ideal_responses' => new external_multiple_structure(
                new external_single_structure([
                    'context'       => new external_value(PARAM_TEXT, 'Context'),
                    'learner_said'  => new external_value(PARAM_TEXT, 'What learner said'),
                    'better_response' => new external_value(PARAM_TEXT, 'Ideal response'),
                ]), 'Ideal response examples', VALUE_OPTIONAL
            ),
            'competencies'    => new external_multiple_structure(
                new external_single_structure([
                    'key'          => new external_value(PARAM_TEXT,  'Competency key'),
                    'label'        => new external_value(PARAM_TEXT,  'Competency label'),
                    'score'        => new external_value(PARAM_FLOAT, 'Score 0-100'),
                    'max_score'    => new external_value(PARAM_FLOAT, 'Max score'),
                    'ai_rationale' => new external_value(PARAM_TEXT,  'AI rationale'),
                    'evidence'     => new external_value(PARAM_TEXT,  'Evidence from transcript'),
                ])
            ),
            'scores_detail'    => new external_single_structure([], 'Detailed scores', VALUE_OPTIONAL),
            'voice_analytics'  => new external_single_structure([], 'Voice analytics', VALUE_OPTIONAL),
            'pronunciation_score' => new external_value(PARAM_FLOAT, 'Pronunciation score', VALUE_OPTIONAL),
            'evaluation_time'  => new external_value(PARAM_INT, 'Evaluation timestamp'),
        ]);
    }

    // ----------------------------------------------------------------
    // request_evaluation (manual trigger)
    // ----------------------------------------------------------------

    public static function request_evaluation_parameters(): external_function_parameters {
        return new external_function_parameters([
            'session_id' => new external_value(PARAM_INT, 'Session ID to evaluate'),
        ]);
    }

    public static function request_evaluation(int $session_id): array {
        global $DB, $USER;

        ['session_id' => $session_id] = self::validate_parameters(
            self::request_evaluation_parameters(), compact('session_id')
        );

        $session = $DB->get_record('airoleplay_session', ['id' => $session_id], '*', MUST_EXIST);

        // Only own sessions or managers can trigger.
        if ($session->userid !== $USER->id) {
            $cm = get_coursemodule_from_instance('airoleplay', $session->airoleplay);
            require_capability('mod/airoleplay:grade', \context_module::instance($cm->id));
        }

        if ($session->status !== 'completed') {
            throw new \moodle_exception('sessionnotcomplete', 'mod_airoleplay');
        }

        if ($session->evaluated_by_ai) {
            return ['queued' => false, 'message' => 'Already evaluated'];
        }

        // Queue for background evaluation.
        \mod_airoleplay\task\process_ai_evaluation_queue::queue_session($session_id);

        return ['queued' => true, 'message' => 'Evaluation queued'];
    }

    public static function request_evaluation_returns(): external_single_structure {
        return new external_single_structure([
            'queued'  => new external_value(PARAM_BOOL, 'Whether queued'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }
}
