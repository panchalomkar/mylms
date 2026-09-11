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

class add_comment extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'itemid'   => new external_value(PARAM_INT,  'Item ID'),
            'itemtype' => new external_value(PARAM_ALPHA,'award or achievement'),
            'message'  => new external_value(PARAM_TEXT, 'Comment text'),
        ]);
    }

    public static function execute(int $itemid, string $itemtype, string $message): array {

        $params = self::validate_parameters(self::execute_parameters(), [
            'itemid'   => $itemid,
            'itemtype' => $itemtype,
            'message'  => $message
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/halloffame:view', $context);

        $comment = manager::add_comment(
            $params['itemid'],
            $params['itemtype'],
            $params['message']
        );

        return $comment;
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id'              => new external_value(PARAM_INT,  'Comment ID'),
            'userid'          => new external_value(PARAM_INT,  'Author user ID'),
            'fullname'        => new external_value(PARAM_TEXT, 'Author full name'),
            'userpictureurl'  => new external_value(PARAM_URL,  'Author avatar URL'),
            'message'         => new external_value(PARAM_RAW,  'Comment message (formatted)'),
            'rawmessage'      => new external_value(PARAM_TEXT, 'Raw comment message for editing'),
            'timecreated'     => new external_value(PARAM_INT,  'Unix timestamp'),
            'timecreatedstr'  => new external_value(PARAM_TEXT, 'Formatted time'),
            'canbedeleted'    => new external_value(PARAM_BOOL, 'Can delete'),
            'canbeedited'     => new external_value(PARAM_BOOL, 'Can edit (only own comments)'),
        ]);
    }
}
