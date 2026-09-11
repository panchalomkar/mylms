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

/**
 * Web service to edit (update) a comment's message.
 * Only the comment author (or an admin) may edit a comment.
 */
class edit_comment extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'commentid' => new external_value(PARAM_INT,  'Comment ID to edit'),
            'message'   => new external_value(PARAM_TEXT, 'New comment text'),
        ]);
    }

    public static function execute(int $commentid, string $message): array {

        $params = self::validate_parameters(self::execute_parameters(), [
            'commentid' => $commentid,
            'message'   => $message,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/halloffame:view', $context);

        return manager::edit_comment($params['commentid'], $params['message']);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether update succeeded'),
            'message' => new external_value(PARAM_RAW,  'Updated formatted message'),
        ]);
    }
}
