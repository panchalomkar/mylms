<?php
namespace local_halloffame\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_user;
use context_system;
use local_halloffame\manager;

class submit_certificate extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'title'       => new external_value(PARAM_TEXT, 'Certificate title'),
            'issuer'      => new external_value(PARAM_TEXT, 'Issuing organisation', VALUE_DEFAULT, ''),
            'issuedate'   => new external_value(PARAM_INT,  'Issue date (unix ts)', VALUE_DEFAULT, 0),
            'type'        => new external_value(PARAM_TEXT, 'Certificate type',     VALUE_DEFAULT, ''),
            'notes'       => new external_value(PARAM_TEXT, 'Additional notes',     VALUE_DEFAULT, ''),
            // ---- New file upload params ----
            'filename'    => new external_value(PARAM_TEXT, 'File name e.g. certificate.pdf', VALUE_DEFAULT, ''),
            'filedata'    => new external_value(PARAM_TEXT, 'Base64 encoded file content',     VALUE_DEFAULT, ''),
            'filetype'    => new external_value(PARAM_TEXT, 'MIME type', VALUE_DEFAULT, 'application/pdf'),
            // ---- Optional: still accept a pre-hosted URL as a fallback ----
            'fileurl'     => new external_value(PARAM_URL,  'Pre-uploaded file URL (optional)', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(
        string $title, string $issuer, int $issuedate,
        string $type, string $notes,
        string $filename, string $filedata, string $filetype,
        string $fileurl
    ): array {
        global $DB, $USER;

        $p = self::validate_parameters(self::execute_parameters(), compact(
            'title', 'issuer', 'issuedate', 'type', 'notes',
            'filename', 'filedata', 'filetype', 'fileurl'
        ));

        require_login();
        require_capability('local/halloffame:submit', context_system::instance());

        if (trim($p['title']) === '') {
            throw new \invalid_parameter_exception('Certificate title is required.');
        }

        $finalfileurl = $p['fileurl']; // fallback if no filedata sent

        // ---- If base64 file data was sent, upload it and build the URL ourselves ----
        if (!empty($p['filedata'])) {

            $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($p['filetype'], $allowed_types)) {
                throw new \invalid_parameter_exception('Invalid file type. Allowed: PDF, JPG, PNG');
            }

            // Strip data URI prefix if present.
            $rawdata = $p['filedata'];
            if (strpos($rawdata, ';base64,') !== false) {
                $rawdata = substr($rawdata, strpos($rawdata, ';base64,') + 8);
            }
            $decoded = base64_decode($rawdata);

            if ($decoded === false || strlen($decoded) === 0) {
                throw new \invalid_parameter_exception('Invalid file data — could not decode base64.');
            }

            $maxsize = 10 * 1024 * 1024;
            if (strlen($decoded) > $maxsize) {
                throw new \invalid_parameter_exception('File too large. Maximum 10MB allowed.');
            }

            $context = context_user::instance($USER->id);
            $fs      = get_file_storage();
            $itemid  = time();

            $cleanfilename = clean_filename($p['filename']);
            if (empty($cleanfilename)) {
                $ext = self::get_extension_from_mime($p['filetype']);
                $cleanfilename = 'certificate_' . time() . '.' . $ext;
            }

            $fs->delete_area_files($context->id, 'local_rapmobile', 'submission', $itemid);

            $fileinfo = [
                'contextid'    => $context->id,
                'component'    => 'local_rapmobile',
                'filearea'     => 'submission',
                'itemid'       => $itemid,
                'filepath'     => '/',
                'filename'     => $cleanfilename,
                'mimetype'     => $p['filetype'],
                'userid'       => $USER->id,
                'timecreated'  => time(),
                'timemodified' => time(),
            ];

            $storedfile = $fs->create_file_from_string($fileinfo, $decoded);

            if (!$storedfile) {
                throw new \moodle_exception('Failed to save uploaded file.');
            }

            // Build the URL to this exact stored file — component matches what we just saved under.
            $finalfileurl = \moodle_url::make_pluginfile_url(
                $context->id,
                'local_rapmobile',
                'submission',
                $itemid,
                '/',
                $cleanfilename
            )->out(false);
        }

        // ---- Save metadata + fileurl into the halloffame record ----
        $p['fileurl'] = $finalfileurl;
        $id = manager::submit_achievement($p);

        return [
            'success' => true,
            'id'      => (int) $id,
            'fileurl' => (string) $finalfileurl,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Submission succeeded'),
            'id'      => new external_value(PARAM_INT,  'New submission ID'),
            'fileurl' => new external_value(PARAM_RAW,  'Uploaded/registered file URL'),
        ]);
    }

    private static function get_extension_from_mime($mime) {
        $map = [
            'application/pdf' => 'pdf',
            'image/jpeg'       => 'jpg',
            'image/jpg'        => 'jpg',
            'image/png'        => 'png',
        ];
        return $map[$mime] ?? 'pdf';
    }
}