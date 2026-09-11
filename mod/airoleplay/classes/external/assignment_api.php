<?php
namespace mod_airoleplay\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

class assignment_api extends external_api {

    public static function get_my_assignments_parameters(): external_function_parameters {
        return new external_function_parameters([
            'status' => new external_value(PARAM_TEXT, 'Filter by status', VALUE_DEFAULT, ''),
        ]);
    }

    public static function get_my_assignments(string $status = ''): array {
        global $DB, $USER;
        self::validate_parameters(self::get_my_assignments_parameters(), compact('status'));
        require_login();
        $where = 'au.userid = :uid';
        $params = ['uid' => $USER->id];
        if ($status) { $where .= ' AND au.status = :status'; $params['status'] = $status; }
        $assignments = $DB->get_records_sql(
            "SELECT au.*, a.title, a.due_date, a.is_mandatory, r.name AS scenario_name, r.id AS scenario_id
               FROM {airoleplay_assignment_user} au
               JOIN {airoleplay_assignment} a ON a.id = au.assignment
               JOIN {airoleplay} r ON r.id = a.airoleplay
              WHERE $where ORDER BY a.due_date ASC",
            $params
        );
        return array_values(array_map(fn($a) => [
            'id' => (int)$a->id, 'title' => $a->title, 'scenario_name' => $a->scenario_name,
            'status' => $a->status, 'due_date' => (int)($a->due_date ?? 0),
            'is_mandatory' => (bool)$a->is_mandatory,
        ], (array)$assignments));
    }

    public static function get_my_assignments_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'id'            => new external_value(PARAM_INT,  'Assignment user ID'),
            'title'         => new external_value(PARAM_TEXT, 'Assignment title'),
            'scenario_name' => new external_value(PARAM_TEXT, 'Scenario name'),
            'status'        => new external_value(PARAM_TEXT, 'Status'),
            'due_date'      => new external_value(PARAM_INT,  'Due date timestamp'),
            'is_mandatory'  => new external_value(PARAM_BOOL, 'Mandatory'),
        ]));
    }

    public static function create_assignment_parameters(): external_function_parameters {
        return new external_function_parameters([
            'airoleplay'  => new external_value(PARAM_INT,  'Airoleplay instance ID'),
            'title'       => new external_value(PARAM_TEXT, 'Assignment title'),
            'target_type' => new external_value(PARAM_TEXT, 'Target type'),
            'target_value'=> new external_value(PARAM_TEXT, 'Target value JSON'),
            'due_date'    => new external_value(PARAM_INT,  'Due date', VALUE_DEFAULT, 0),
            'is_mandatory'=> new external_value(PARAM_INT,  'Mandatory', VALUE_DEFAULT, 1),
        ]);
    }

    public static function create_assignment(int $airoleplay, string $title, string $target_type, string $target_value, int $due_date = 0, int $is_mandatory = 1): array {
        global $USER;
        self::validate_parameters(self::create_assignment_parameters(),
            compact('airoleplay','title','target_type','target_value','due_date','is_mandatory'));
        $scenario = get_coursemodule_from_instance('airoleplay', $airoleplay, 0, false, MUST_EXIST);
        require_capability('mod/airoleplay:manageassignments', \context_module::instance($scenario->id));
        $engine = new \local_airoleplay_assignments\engine\assignment_engine();
        $id = $engine->create_assignment([
            'airoleplay' => $airoleplay, 'title' => $title, 'target_type' => $target_type,
            'target_value' => json_decode($target_value, true) ?? [],
            'due_date' => $due_date ?: null, 'is_mandatory' => $is_mandatory,
            'createdby' => $USER->id,
        ]);
        return ['id' => $id, 'success' => true];
    }

    public static function create_assignment_returns(): external_single_structure {
        return new external_single_structure(['id' => new external_value(PARAM_INT, 'Assignment ID'), 'success' => new external_value(PARAM_BOOL, 'Success')]);
    }
}
