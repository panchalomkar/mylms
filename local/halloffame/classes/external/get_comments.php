<?php
namespace local_halloffame\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use local_halloffame\manager;

class get_comments extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'itemid'   => new external_value(PARAM_INT,   'Item ID'),
            'itemtype' => new external_value(PARAM_ALPHA, 'award or achievement'),
        ]);
    }

    public static function execute(int $itemid, string $itemtype): array {

        $params = self::validate_parameters(self::execute_parameters(), [
            'itemid'   => $itemid,
            'itemtype' => $itemtype
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/halloffame:view', $context);

        return manager::get_comments($params['itemid'], $params['itemtype']);
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id'              => new external_value(PARAM_INT,  'Comment ID'),
                'userid'          => new external_value(PARAM_INT,  'Author user ID'),
                'fullname'        => new external_value(PARAM_TEXT, 'Author full name'),
                'userpictureurl' => new external_value(PARAM_URL,
    'Author avatar URL for web'
),

'userpicturemobileurl' => new external_value( PARAM_URL, 'Author avatar URL for mobile'),
                'message'         => new external_value(PARAM_RAW,  'Comment message (formatted)'),
                'rawmessage'      => new external_value(PARAM_TEXT, 'Raw comment message for editing'),
                'timecreated'     => new external_value(PARAM_INT,  'Unix timestamp'),
                'timecreatedstr'  => new external_value(PARAM_TEXT, 'Formatted time'),
                'canbedeleted'    => new external_value(PARAM_BOOL, 'Can delete'),
                'canbeedited'     => new external_value(PARAM_BOOL, 'Can edit (only own comments)'),
            ])
        );
    }
}
