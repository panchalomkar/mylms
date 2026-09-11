<?php
namespace mod_airoleplay\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

class scenario_api extends external_api {

    public static function get_scenario_parameters(): external_function_parameters {
        return new external_function_parameters(['cmid' => new external_value(PARAM_INT, 'Course module ID')]);
    }

    public static function get_scenario(int $cmid): array {
        global $DB;
        self::validate_parameters(self::get_scenario_parameters(), compact('cmid'));
        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'airoleplay');
        self::validate_context(\context_module::instance($cm->id));
        $scenario = $DB->get_record('airoleplay', ['id' => $cm->instance], '*', MUST_EXIST);
        return [
            'id' => $scenario->id, 'name' => format_string($scenario->name),
            'difficulty' => $scenario->difficulty, 'industry' => $scenario->industry ?? '',
            'language' => $scenario->language, 'scenario_type' => $scenario->scenario_type,
            'pass_score' => (float)$scenario->pass_score,
            'enable_voice' => (bool)$scenario->enable_voice,
        ];
    }

    public static function get_scenario_returns(): external_single_structure {
        return new external_single_structure([
            'id'            => new external_value(PARAM_INT,   'Scenario ID'),
            'name'          => new external_value(PARAM_TEXT,  'Scenario name'),
            'difficulty'    => new external_value(PARAM_TEXT,  'Difficulty'),
            'industry'      => new external_value(PARAM_TEXT,  'Industry'),
            'language'      => new external_value(PARAM_TEXT,  'Language'),
            'scenario_type' => new external_value(PARAM_TEXT,  'Type'),
            'pass_score'    => new external_value(PARAM_FLOAT, 'Pass score'),
            'enable_voice'  => new external_value(PARAM_BOOL,  'Voice enabled'),
        ]);
    }

    public static function get_personas_parameters(): external_function_parameters {
        return new external_function_parameters([
            'industry' => new external_value(PARAM_TEXT, 'Filter by industry', VALUE_DEFAULT, ''),
        ]);
    }

    public static function get_personas(string $industry = ''): array {
        global $DB;
        self::validate_parameters(self::get_personas_parameters(), compact('industry'));
        require_login();
        $where = $industry ? 'industry = :ind OR industry IS NULL' : '1=1';
        $params = $industry ? ['ind' => $industry] : [];
        $personas = $DB->get_records_select('airoleplay_persona', $where, $params, 'name ASC');
        return array_values(array_map(fn($p) => [
            'id' => (int)$p->id, 'name' => format_string($p->name),
            'persona_type' => $p->persona_type, 'tone' => $p->tone,
            'industry' => $p->industry ?? '', 'avatar_url' => $p->avatar_url ?? '',
        ], (array)$personas));
    }

    public static function get_personas_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'id'           => new external_value(PARAM_INT,  'Persona ID'),
            'name'         => new external_value(PARAM_TEXT, 'Name'),
            'persona_type' => new external_value(PARAM_TEXT, 'Type'),
            'tone'         => new external_value(PARAM_TEXT, 'Tone'),
            'industry'     => new external_value(PARAM_TEXT, 'Industry'),
            'avatar_url'   => new external_value(PARAM_TEXT, 'Avatar URL'),
        ]));
    }

    public static function save_persona_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id'           => new external_value(PARAM_INT,  'Persona ID (0=new)', VALUE_DEFAULT, 0),
            'name'         => new external_value(PARAM_TEXT, 'Name'),
            'persona_type' => new external_value(PARAM_TEXT, 'Type'),
            'tone'         => new external_value(PARAM_TEXT, 'Tone', VALUE_DEFAULT, 'neutral'),
            'industry'     => new external_value(PARAM_TEXT, 'Industry', VALUE_DEFAULT, ''),
            'background'   => new external_value(PARAM_TEXT, 'Background', VALUE_DEFAULT, ''),
            'aggression'   => new external_value(PARAM_INT,  'Aggression 0-100', VALUE_DEFAULT, 50),
        ]);
    }

    public static function save_persona(int $id, string $name, string $persona_type, string $tone = 'neutral', string $industry = '', string $background = '', int $aggression = 50): array {
        global $DB, $USER;
        self::validate_parameters(self::save_persona_parameters(), compact('id','name','persona_type','tone','industry','background','aggression'));
        require_capability('mod/airoleplay:managepersonas', \context_system::instance());
        $record = (object)['name' => clean_param($name, PARAM_TEXT), 'persona_type' => clean_param($persona_type, PARAM_TEXT), 'tone' => clean_param($tone, PARAM_ALPHA), 'industry' => clean_param($industry, PARAM_TEXT), 'background' => clean_param($background, PARAM_TEXT), 'aggression' => max(0, min(100, $aggression)), 'language' => 'en', 'is_template' => 0, 'createdby' => $USER->id, 'timecreated' => time(), 'timemodified' => time()];
        if ($id) { $record->id = $id; $DB->update_record('airoleplay_persona', $record); } 
        else { $id = $DB->insert_record('airoleplay_persona', $record); }
        return ['id' => $id, 'success' => true];
    }

    public static function save_persona_returns(): external_single_structure {
        return new external_single_structure(['id' => new external_value(PARAM_INT, 'Persona ID'), 'success' => new external_value(PARAM_BOOL, 'Success')]);
    }
}
