<?php
namespace local_halloffame\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_system;
use local_halloffame\manager;

class like_item extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'itemid'   => new external_value(PARAM_INT,  'Item ID'),
            'itemtype' => new external_value(PARAM_TEXT, 'award or achievement'),
        ]);
    }

    public static function execute(int $itemid, string $itemtype): array {
        $p = self::validate_parameters(self::execute_parameters(),
            ['itemid' => $itemid, 'itemtype' => $itemtype]);

        require_login();
        require_capability('local/halloffame:view', context_system::instance());

        if (!in_array($p['itemtype'], ['award', 'achievement'], true)) {
            throw new \invalid_parameter_exception('itemtype must be award or achievement');
        }

        $r = manager::toggle_like($p['itemid'], $p['itemtype']);
        return ['liked' => (int) $r['liked'], 'count' => (int) $r['count']];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'liked' => new external_value(PARAM_INT, '1 if now liked, 0 if unliked'),
            'count' => new external_value(PARAM_INT, 'New like count'),
        ]);
    }
}
