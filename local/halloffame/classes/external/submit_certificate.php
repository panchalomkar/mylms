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

class submit_certificate extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'title'     => new external_value(PARAM_TEXT, 'Certificate title'),
            'issuer'    => new external_value(PARAM_TEXT, 'Issuing organisation', VALUE_DEFAULT, ''),
            'issuedate' => new external_value(PARAM_INT,  'Issue date (unix ts)', VALUE_DEFAULT, 0),
            'type'      => new external_value(PARAM_TEXT, 'Certificate type',     VALUE_DEFAULT, ''),
            'notes'     => new external_value(PARAM_TEXT, 'Additional notes',     VALUE_DEFAULT, ''),
            'fileurl'   => new external_value(PARAM_URL,  'Uploaded file URL',    VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(
        string $title, string $issuer, int $issuedate,
        string $type, string $notes, string $fileurl
    ): array {
        $p = self::validate_parameters(self::execute_parameters(), compact(
            'title', 'issuer', 'issuedate', 'type', 'notes', 'fileurl'
        ));

        require_login();
        require_capability('local/halloffame:submit', context_system::instance());

        if (trim($p['title']) === '') {
            throw new \invalid_parameter_exception('Certificate title is required.');
        }

        $id = manager::submit_achievement($p);
        return ['success' => true, 'id' => (int) $id];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Submission succeeded'),
            'id'      => new external_value(PARAM_INT,  'New submission ID'),
        ]);
    }
}
