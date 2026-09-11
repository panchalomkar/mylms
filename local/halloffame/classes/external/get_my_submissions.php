<?php
// local/halloffame/classes/external/get_my_submissions.php
namespace local_halloffame\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use context_system;

/**
 * Web service: get_my_submissions
 *
 * Returns the current user's own certificate submissions ordered newest first.
 * Mirrors the data built in pages/my_submissions.php exactly.
 *
 * Status values (stored as VARCHAR in halloffame_submissions):
 *   'pending'  – awaiting admin review
 *   'approved' – accepted and published to the Gallery
 *   'rejected' – declined by admin
 *
 * caneditdelete is true only for 'pending' rows – same rule as the web page.
 */
class get_my_submissions extends external_api {

    // ── Parameter definition ──────────────────────────────────────────────────

    public static function execute_parameters(): external_function_parameters {
        // No input parameters – the function always returns the current user's rows.
        return new external_function_parameters([]);
    }

    // ── Main logic ────────────────────────────────────────────────────────────

   public static function execute(): array {
    global $DB, $USER;

    // 1. Validate (no params, but call is still required by the API contract).
    self::validate_parameters(self::execute_parameters(), []);

    // 2. Auth & capability check.
    $context = context_system::instance();
    self::validate_context($context);
    require_login();
    require_capability('local/halloffame:submit', $context);

    // Grab the wstoken used for this call so we can build authenticated file URLs.
    $wstoken = optional_param('wstoken', '', PARAM_ALPHANUM);

    // 3. Fetch this user's submissions, newest first.
    $records = $DB->get_records(
        'halloffame_submissions',
        ['userid' => (int) $USER->id],
        'timecreated DESC'
    );

    // 4. Build the response array.
    $rows = [];
    foreach ($records as $r) {

        $ext     = $r->fileurl ? strtolower(pathinfo($r->fileurl, PATHINFO_EXTENSION)) : '';
        $isimage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);

        $caneditdelete = ($r->status === 'pending');

        $statuslabels = [
            'pending'  => get_string('pending',  'local_halloffame'),
            'approved' => get_string('approved', 'local_halloffame'),
            'rejected' => get_string('rejected', 'local_halloffame'),
        ];
        $statuslabel = $statuslabels[$r->status] ?? $r->status;

        // Convert the plain pluginfile.php URL into an authenticated
        // webservice/pluginfile.php URL with the caller's wstoken appended.
        $fileurl = (string) ($r->fileurl ?? '');
        if ($fileurl !== '' && $wstoken !== '') {
            $fileurl = str_replace('/pluginfile.php/', '/webservice/pluginfile.php/', $fileurl);
            $fileurl .= (strpos($fileurl, '?') === false ? '?' : '&') . 'token=' . $wstoken;
        }

        $rows[] = [
            'id'            => (int)    $r->id,
            'title'         => (string) $r->title,
            'issuer'        => (string) ($r->issuer ?? ''),
            'issuedate'     => (int)    ($r->issuedate ?? 0),
            'type'          => (string) ($r->type   ?? ''),
            'notes'         => (string) ($r->notes  ?? ''),
            'fileurl'       => $fileurl,
            'fileisimage'   => (bool)   $isimage,
            'status'        => (string) $r->status,
            'statuslabel'   => (string) $statuslabel,
            'timecreated'   => (int)    $r->timecreated,
            'caneditdelete' => (bool)  $caneditdelete,
        ];
    }

    return $rows;
}

    // ── Return type definition ────────────────────────────────────────────────

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id'            => new external_value(PARAM_INT,  'Submission record ID'),
                'title'         => new external_value(PARAM_TEXT, 'Certificate title'),
                'issuer'        => new external_value(PARAM_TEXT, 'Issuing organisation'),
                'issuedate'     => new external_value(PARAM_INT,  'Issue date as Unix timestamp (0 if not set)'),
                'type'          => new external_value(PARAM_TEXT, 'Certificate type'),
                'notes'         => new external_value(PARAM_TEXT, 'Additional notes'),
                'fileurl'       => new external_value(PARAM_TEXT, 'URL of the uploaded certificate file (empty string if none)'),
                'fileisimage'   => new external_value(PARAM_BOOL, 'True if fileurl points to an image (jpg/jpeg/png/gif/webp)'),
                'status'        => new external_value(PARAM_TEXT, 'Raw status string: pending | approved | rejected'),
                'statuslabel'   => new external_value(PARAM_TEXT, 'Localised status label for display'),
                'timecreated'   => new external_value(PARAM_INT,  'Submission timestamp (Unix)'),
                'caneditdelete' => new external_value(PARAM_BOOL, 'True only for pending submissions – user may edit or delete'),
            ])
        );
    }
}
