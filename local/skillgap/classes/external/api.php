<?php
namespace local_skillgap\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use context_system;

/**
 * External API for AJAX calls from AMD modules.
 */
class api extends external_api {

    // ─── Generate AI Questions ────────────────────────────────────────

    public static function generate_ai_questions_parameters(): external_function_parameters {
        return new external_function_parameters([
            'compid' => new external_value(PARAM_INT, 'Competency ID'),
        ]);
    }

    public static function generate_ai_questions(int $compid): array {
        global $DB, $CFG;

        self::validate_parameters(self::generate_ai_questions_parameters(), ['compid' => $compid]);
        require_capability('local/skillgap:admin', context_system::instance());

        $existing = $DB->count_records('local_skillgap_question', ['compid' => $compid]);
        if ($existing >= 5) {
            return ['status' => 'error', 'message' => get_string('error_max_questions', 'local_skillgap'), 'added' => 0];
        }

        $apikey = get_config('local_skillgap', 'openai_apikey');
        if (empty($apikey)) {
            return ['status' => 'error', 'message' => get_string('error_api_key', 'local_skillgap'), 'added' => 0];
        }

        $comp   = $DB->get_record('local_skillgap_comp', ['id' => $compid], 'id,name', MUST_EXIST);
        $needed = 5 - $existing;

        $prompt = "Generate exactly {$needed} multiple-choice assessment questions for the workplace competency: \"{$comp->name}\".
Rules:
- Each question must have exactly 4 answer options labelled A, B, C, D.
- Exactly 1 option must be correct.
- Questions must be professional and relevant to workplace assessment.
Return ONLY a valid JSON array like this (no markdown, no extra text):
[{\"question\":\"...\",\"options\":[\"Option A text\",\"Option B text\",\"Option C text\",\"Option D text\"],\"correct\":0}]
where correct is the 0-based index of the correct option.";

        $payload = json_encode([
            'model'      => 'gpt-4o',
            'messages'   => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 2000,
        ]);

        $curl = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apikey,
            ],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $result   = curl_exec($curl);
        $curl_err = curl_errno($curl);
        curl_close($curl);

        if ($curl_err || !$result) {
            return ['status' => 'error', 'message' => get_string('error_ai_failed', 'local_skillgap'), 'added' => 0];
        }

        $decoded   = json_decode($result, true);
        $raw       = $decoded['choices'][0]['message']['content'] ?? '[]';
        $raw       = preg_replace('/^```json\s*/s', '', $raw);
        $raw       = preg_replace('/\s*```$/s', '', $raw);
        $questions = json_decode(trim($raw), true);

        if (!is_array($questions)) {
            return ['status' => 'error', 'message' => get_string('error_ai_failed', 'local_skillgap'), 'added' => 0];
        }

        $now   = time();
        $added = 0;
        foreach (array_slice($questions, 0, $needed) as $q) {
            if (empty($q['question']) || !isset($q['options']) || !isset($q['correct'])) {
                continue;
            }
            $qid = $DB->insert_record('local_skillgap_question', [
                'compid'       => $compid,
                'questiontext' => clean_param($q['question'], PARAM_TEXT),
                'source'       => 'ai',
                'timecreated'  => $now,
            ]);
            foreach ($q['options'] as $idx => $opt) {
                $DB->insert_record('local_skillgap_qoption', [
                    'questionid' => $qid,
                    'optiontext' => clean_param($opt, PARAM_TEXT),
                    'iscorrect'  => (int)($idx === (int)$q['correct']),
                ]);
            }
            $added++;
        }

        return [
            'status'  => 'success',
            'message' => "Added {$added} AI-generated questions.",
            'added'   => $added,
        ];
    }

    public static function generate_ai_questions_returns(): external_single_structure {
        return new external_single_structure([
            'status'  => new external_value(PARAM_TEXT, 'Result status'),
            'message' => new external_value(PARAM_TEXT, 'Message'),
            'added'   => new external_value(PARAM_INT,  'Questions added'),
        ]);
    }

    // ─── Get Progress Detail (for popup) ─────────────────────────────

    public static function get_progress_detail_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'compid' => new external_value(PARAM_INT, 'Competency ID'),
        ]);
    }

    public static function get_progress_detail(int $userid, int $compid): array {
        global $DB;

        self::validate_parameters(self::get_progress_detail_parameters(), ['userid' => $userid, 'compid' => $compid]);
        require_capability('local/skillgap:viewown', context_system::instance());

        $sql = "SELECT s.id, s.score, s.maxscore, s.status, s.timecreated, c.name AS compname
                  FROM {local_skillgap_score} s
                  JOIN {local_skillgap_comp} c ON c.id = s.compid
                 WHERE s.userid = :uid AND s.compid = :cid
                 ORDER BY s.timecreated DESC LIMIT 2";
        $rows = array_values($DB->get_records_sql($sql, ['uid' => $userid, 'cid' => $compid]));

        $current_score = isset($rows[0]) ? round((float)$rows[0]->score, 1) : null;
        $prev_score    = isset($rows[1]) ? round((float)$rows[1]->score, 1) : null;
        $progress      = ($current_score !== null && $prev_score !== null)
                       ? round($current_score - $prev_score, 1)
                       : null;
        $comp_name     = isset($rows[0]) ? $rows[0]->compname : '';

        return [
            'compname'      => $comp_name,
            'current_score' => $current_score !== null ? number_format($current_score, 1) . '/5' : '-',
            'prev_score'    => $prev_score    !== null ? number_format($prev_score, 1)    . '/5' : get_string('first_attempt', 'local_skillgap'),
            'progress'      => $progress !== null ? ($progress >= 0 ? '+' : '') . number_format($progress, 1) : '-',
            'trend_up'      => $progress !== null && $progress > 0,
            'trend_down'    => $progress !== null && $progress < 0,
            'is_first'      => $prev_score === null,
        ];
    }

    public static function get_progress_detail_returns(): external_single_structure {
        return new external_single_structure([
            'compname'      => new external_value(PARAM_TEXT, 'Competency name'),
            'current_score' => new external_value(PARAM_TEXT, 'Current score string'),
            'prev_score'    => new external_value(PARAM_TEXT, 'Previous score string'),
            'progress'      => new external_value(PARAM_TEXT, 'Progress delta string'),
            'trend_up'      => new external_value(PARAM_BOOL, 'Trend is upward'),
            'trend_down'    => new external_value(PARAM_BOOL, 'Trend is downward'),
            'is_first'      => new external_value(PARAM_BOOL, 'First attempt'),
        ]);
    }

    // ─── Get Team Scores ─────────────────────────────────────────────

    public static function get_team_scores_parameters(): external_function_parameters {
        return new external_function_parameters([
            'managerid' => new external_value(PARAM_INT, 'Manager user ID'),
            'deptid'    => new external_value(PARAM_INT, 'Department filter', VALUE_DEFAULT, 0),
            'compid'    => new external_value(PARAM_INT, 'Competency filter', VALUE_DEFAULT, 0),
        ]);
    }

    public static function get_team_scores(int $managerid, int $deptid = 0, int $compid = 0): array {
        global $DB;

        self::validate_parameters(self::get_team_scores_parameters(), [
            'managerid' => $managerid, 'deptid' => $deptid, 'compid' => $compid,
        ]);
        require_capability('local/skillgap:managerviewteam', context_system::instance());

        $team = \local_skillgap_get_team($managerid);
        if (empty($team)) {
            return [];
        }

        $result = [];
        foreach ($team as $u) {
            $sql = "SELECT c.name AS compname, AVG(s.score) AS avg_score, s.status
                      FROM {local_skillgap_score} s
                      JOIN {local_skillgap_comp} c ON c.id = s.compid
                     WHERE s.userid = :uid";
            $params = ['uid' => $u->id];
            if ($compid) {
                $sql            .= " AND s.compid = :compid";
                $params['compid'] = $compid;
            }
            $sql .= " GROUP BY c.name, s.status ORDER BY c.name ASC";

            $scores = $DB->get_records_sql($sql, $params);
            foreach ($scores as $s) {
                $result[] = [
                    'userid'   => $u->id,
                    'fullname' => fullname($u),
                    'compname' => $s->compname,
                    'score'    => round((float)$s->avg_score, 1),
                    'status'   => $s->status,
                ];
            }
        }
        return $result;
    }

    public static function get_team_scores_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'userid'   => new external_value(PARAM_INT,  'User ID'),
            'fullname' => new external_value(PARAM_TEXT, 'Full name'),
            'compname' => new external_value(PARAM_TEXT, 'Competency'),
            'score'    => new external_value(PARAM_FLOAT,'Score'),
            'status'   => new external_value(PARAM_TEXT, 'Status'),
        ]));
    }

    // ─── Save Response ────────────────────────────────────────────────

    public static function save_response_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid'  => new external_value(PARAM_INT, 'Attempt ID'),
            'questionid' => new external_value(PARAM_INT, 'Question ID'),
            'optionid'   => new external_value(PARAM_INT, 'Selected option ID'),
        ]);
    }

    public static function save_response(int $attemptid, int $questionid, int $optionid): array {
        global $DB;

        self::validate_parameters(self::save_response_parameters(), [
            'attemptid' => $attemptid, 'questionid' => $questionid, 'optionid' => $optionid,
        ]);
        require_capability('local/skillgap:takeassessment', context_system::instance());

        $attempt  = $DB->get_record('local_skillgap_attempt', ['id' => $attemptid], '*', MUST_EXIST);
        $question = $DB->get_record('local_skillgap_question', ['id' => $questionid], '*', MUST_EXIST);
        $option   = $DB->get_record('local_skillgap_qoption', ['id' => $optionid], '*', MUST_EXIST);

        // Upsert: delete previous answer for this question in this attempt.
        $DB->delete_records('local_skillgap_response', [
            'attemptid' => $attemptid, 'questionid' => $questionid,
        ]);

        $DB->insert_record('local_skillgap_response', [
            'attemptid'   => $attemptid,
            'questionid'  => $questionid,
            'optionid'    => $optionid,
            'iscorrect'   => (int)(bool)$option->iscorrect,
            'compid'      => $question->compid,
            'timecreated' => time(),
        ]);

        return ['status' => 'saved', 'iscorrect' => (bool)$option->iscorrect];
    }

    public static function save_response_returns(): external_single_structure {
        return new external_single_structure([
            'status'    => new external_value(PARAM_TEXT, 'Status'),
            'iscorrect' => new external_value(PARAM_BOOL, 'Was answer correct'),
        ]);
    }
}
