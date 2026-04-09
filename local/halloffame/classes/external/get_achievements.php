<?php
namespace local_halloffame\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_system;
use local_halloffame\manager;

class get_achievements extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'filters' => new external_single_structure([
                'type' => new external_value(PARAM_TEXT, 'Certificate type', VALUE_OPTIONAL, ''),
                'year' => new external_value(PARAM_INT,  'Year',             VALUE_OPTIONAL, 0),
            ]),
        ]);
    }

    public static function execute(array $filters): array {
        $params = self::validate_parameters(self::execute_parameters(), ['filters' => $filters]);
        require_capability('local/halloffame:view', context_system::instance());

        $result = [];
        foreach (manager::get_achievements($params['filters']) as $a) {
            $result[] = [
                'id'           => (int)    $a->id,
                'userid'       => (int)    $a->userid,
                'fullname'     => (string) $a->fullname,
                'title'        => (string) $a->title,
                'issuer'       => (string) ($a->issuer ?? ''),
                'issuedate'    => (int)    ($a->issuedate ?? 0),
                'issuedatefmt' => (string) ($a->issuedateformatted ?? ''),
                'type'         => (string) ($a->type    ?? ''),
                'fileurl'      => (string) ($a->fileurl ?? ''),
                'likecount'    => (int)    $a->likecount,
                'userliked'    => (int)    $a->userliked,
            ];
        }
        return $result;
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'id'           => new external_value(PARAM_INT,  'Achievement ID'),
            'userid'       => new external_value(PARAM_INT,  'User ID'),
            'fullname'     => new external_value(PARAM_TEXT, 'Full name'),
            'title'        => new external_value(PARAM_TEXT, 'Title'),
            'issuer'       => new external_value(PARAM_TEXT, 'Issuer'),
            'issuedate'    => new external_value(PARAM_INT,  'Issue timestamp'),
            'issuedatefmt' => new external_value(PARAM_TEXT, 'Formatted issue date'),
            'type'         => new external_value(PARAM_TEXT, 'Certificate type'),
            'fileurl'      => new external_value(PARAM_TEXT, 'File URL'),
            'likecount'    => new external_value(PARAM_INT,  'Like count'),
            'userliked'    => new external_value(PARAM_INT,  '1 if user liked'),
        ]));
    }
}
