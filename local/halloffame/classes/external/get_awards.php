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

class get_awards extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'filters' => new external_single_structure([
                'month'      => new external_value(PARAM_INT,  'Month (1–12)', VALUE_OPTIONAL, 0),
                'year'       => new external_value(PARAM_INT,  'Year',         VALUE_OPTIONAL, 0),
                'quarter'    => new external_value(PARAM_INT,  'Quarter 1–4',  VALUE_OPTIONAL, 0),
                'department' => new external_value(PARAM_TEXT, 'Department',   VALUE_OPTIONAL, ''),
                'category'   => new external_value(PARAM_TEXT, 'Category',     VALUE_OPTIONAL, ''),
            ]),
        ]);
    }

    public static function execute(array $filters): array {
        $params = self::validate_parameters(self::execute_parameters(), ['filters' => $filters]);
        require_capability('local/halloffame:view', context_system::instance());

        $result = [];
        foreach (manager::get_awards($params['filters']) as $a) {
            $result[] = [
                'id'         => (int) $a->id,
                'userid'     => (int) $a->userid,
                'fullname'   => (string) $a->fullname,
                'title'      => (string) $a->title,
                'department' => (string) ($a->department ?? ''),
                'category'   => (string) ($a->category   ?? ''),
                'month'      => (int) $a->month,
                'year'       => (int) $a->year,
                'message'    => (string) ($a->message ?? ''),
                'image'      => (string) ($a->image   ?? ''),
                'likecount'  => (int) $a->likecount,
                'userliked'  => (int) $a->userliked,
            ];
        }
        return $result;
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'id'         => new external_value(PARAM_INT,  'Award ID'),
            'userid'     => new external_value(PARAM_INT,  'User ID'),
            'fullname'   => new external_value(PARAM_TEXT, 'Full name'),
            'title'      => new external_value(PARAM_TEXT, 'Title'),
            'department' => new external_value(PARAM_TEXT, 'Department'),
            'category'   => new external_value(PARAM_TEXT, 'Category'),
            'month'      => new external_value(PARAM_INT,  'Month'),
            'year'       => new external_value(PARAM_INT,  'Year'),
            'message'    => new external_value(PARAM_TEXT, 'Message'),
            'image'      => new external_value(PARAM_TEXT, 'Image URL'),
            'likecount'  => new external_value(PARAM_INT,  'Like count'),
            'userliked'  => new external_value(PARAM_INT,  '1 if user liked'),
        ]));
    }
}
