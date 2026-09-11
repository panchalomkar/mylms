<?php
namespace local_halloffame\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use local_halloffame\manager;

class delete_comment extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'commentid' => new external_value(PARAM_INT, 'Comment ID to delete'),
        ]);
    }

    public static function execute(int $commentid): array {

        $params = self::validate_parameters(self::execute_parameters(), [
            'commentid' => $commentid
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/halloffame:view', $context);

        $success = manager::delete_comment($params['commentid']);

        return ['success' => $success];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Deletion status'),
        ]);
    }
}