<?php
/**
 * Session Manager - handles lifecycle of roleplay sessions.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_airoleplay\session;

defined('MOODLE_INTERNAL') || die();

class session_manager {

    /**
     * Create a new attempt session.
     */
    public function create_session(
        object $scenario,
        int $userid,
        string $scenario_type,
        string $language
    ): object {
        global $DB;

        // Determine attempt number.
        $attempt = (int)$DB->count_records('airoleplay_session', [
            'airoleplay' => $scenario->id,
            'userid'     => $userid,
        ]) + 1;

        // Snapshot user org profile fields at time of attempt.
        $profile = [];
        if (class_exists('\\mod_airoleplay\\profile\\profile_field_mapper')) {
            $profile = \mod_airoleplay\profile\profile_field_mapper::get_user_profile($userid);
        }

        $record                  = new \stdClass();
        $record->airoleplay      = $scenario->id;
        $record->userid          = $userid;
        $record->attempt         = $attempt;
        $record->session_token   = $this->generate_session_token();
        $record->status          = 'active';
        $record->scenario_type   = $scenario_type;
        $record->language        = $language;
        $record->turn_count      = 0;
        $record->duration_secs   = 0;
        $record->user_dept       = $profile['department']  ?? '';
        $record->user_designation = $profile['designation'] ?? '';
        $record->user_region     = $profile['region']      ?? '';
        $record->timestarted     = time();
        $record->timemodified    = time();

        $record->id = $DB->insert_record('airoleplay_session', $record);

        // Attach persona for caller convenience.
        if ($scenario->persona_id) {
            $record->persona = $DB->get_record('airoleplay_persona', ['id' => $scenario->persona_id]);
        } else {
            $record->persona = null;
        }

        return $record;
    }

    /**
     * End a session and mark as completed.
     */
    public function end_session(object $session): object {
        global $DB;

        $now = time();

        $DB->set_field('airoleplay_session', 'status',        'completed',                  ['id' => $session->id]);
        $DB->set_field('airoleplay_session', 'duration_secs', $now - $session->timestarted, ['id' => $session->id]);
        $DB->set_field('airoleplay_session', 'timecompleted', $now,                         ['id' => $session->id]);
        $DB->set_field('airoleplay_session', 'timemodified',  $now,                         ['id' => $session->id]);

        return $DB->get_record('airoleplay_session', ['id' => $session->id], '*', MUST_EXIST);
    }

    /**
     * Apply AI evaluation results to session record.
     */
    public function apply_evaluation(int $session_id, array $evaluation, object $scenario): void {
        global $DB;

        $overall_score = (float)($evaluation['overall_score'] ?? 0);
        $passed        = $overall_score >= (float)$scenario->pass_score;
        $grade         = ($overall_score / 100) * (float)$scenario->grademax;

        $update                      = new \stdClass();
        $update->id                  = $session_id;
        $update->score_overall       = $overall_score;
        $update->passed              = (int)$passed;
        $update->grade               = $grade;
        $update->evaluated_by_ai     = 1;
        $update->evaluation_time     = time();
        $update->ai_summary          = $evaluation['summary']                              ?? '';
        $update->ai_strengths        = json_encode($evaluation['strengths']               ?? []);
        $update->ai_improvements     = json_encode($evaluation['improvements']            ?? []);
        $update->ai_recommendations  = json_encode($evaluation['recommended_next_steps']  ?? []);
        $update->ai_ideal_responses  = json_encode($evaluation['ideal_response_examples'] ?? []);
        $update->sentiment_trend     = json_encode(['trend' => $evaluation['sentiment_trend'] ?? 'steady']);
        $update->scores_detail       = json_encode($evaluation);
        $update->timemodified        = time();

        // Per-dimension scores.
        $comp_map = [];
        foreach ($evaluation['competencies'] ?? [] as $comp) {
            $comp_map[$comp['key'] ?? ''] = $comp['score'] ?? 0;
        }
        $update->score_communication   = $comp_map['communication']       ?? null;
        $update->score_empathy         = $comp_map['empathy']             ?? null;
        $update->score_confidence      = $comp_map['confidence']          ?? null;
        $update->score_clarity         = $comp_map['clarity']             ?? null;
        $update->score_professionalism = $comp_map['professionalism']     ?? null;
        $update->score_compliance      = $comp_map['compliance_adherence'] ?? $comp_map['compliance'] ?? null;
        $update->score_product_knowledge = $comp_map['product_knowledge'] ?? null;
        $update->score_objection_handling = $comp_map['objection_handling'] ?? null;

        $DB->update_record('airoleplay_session', $update);

        // Store competency results.
        $DB->delete_records('airoleplay_competency_result', ['session' => $session_id]);
        foreach ($evaluation['competencies'] ?? [] as $comp) {
            $cr                   = new \stdClass();
            $cr->session          = $session_id;
            $cr->competency_key   = clean_param($comp['key']   ?? '', PARAM_ALPHANUMEXT);
            $cr->competency_label = clean_param($comp['label'] ?? '', PARAM_TEXT);
            $cr->score            = max(0, min(100, (float)($comp['score'] ?? 0)));
            $cr->max_score        = 100;
            $cr->evidence         = $comp['evidence']     ?? '';
            $cr->ai_rationale     = $comp['rationale']    ?? '';
            $cr->timecreated      = time();
            $DB->insert_record('airoleplay_competency_result', $cr);
        }
    }

    /**
     * Generate cryptographically secure session token.
     */
    private function generate_session_token(): string {
        return bin2hex(random_bytes(32));
    }
}
