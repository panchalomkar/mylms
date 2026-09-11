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
    'type'       => new external_value(PARAM_TEXT, 'Certificate type', VALUE_OPTIONAL, ''),
    'year'       => new external_value(PARAM_INT,  'Year', VALUE_OPTIONAL, 0),
    'month'      => new external_value(PARAM_INT,  'Month', VALUE_OPTIONAL, 0),
    'quarter'    => new external_value(PARAM_INT,  'Quarter', VALUE_OPTIONAL, 0),
    'department' => new external_value(PARAM_TEXT, 'Department', VALUE_OPTIONAL, ''),
]),
        ]);
    }

    public static function execute(array $filters): array {
        $params = self::validate_parameters(self::execute_parameters(), ['filters' => $filters]);
        require_capability('local/halloffame:view', context_system::instance());
        $context    = \context_system::instance();
        $canmanage  = has_capability('local/halloffame:manageawards', $context);

        // Grab the wstoken used for this call so we can build authenticated file URLs.
        $wstoken = optional_param('wstoken', '', PARAM_ALPHANUM);

        // Converts a plain pluginfile.php URL into an authenticated
        // webservice/pluginfile.php URL ONLY (no forcedownload) — used for
        // things that should just be viewed/rendered, like profile pictures.
        $tokenizeview = function (string $rawurl) use ($wstoken): string {
            if ($rawurl === '' || $wstoken === '' || strpos($rawurl, '/pluginfile.php/') === false) {
                return $rawurl;
            }
            $wsurl = str_replace('/pluginfile.php/', '/webservice/pluginfile.php/', $rawurl);
            return $wsurl . (strpos($wsurl, '?') === false ? '?' : '&') . 'token=' . $wstoken;
        };

        // Same as above, but also returns a forcedownload=1 variant —
        // used for things the user should be able to download, like
        // certificate/achievement files.
        $tokenizewithdownload = function (string $rawurl) use ($wstoken, $tokenizeview): array {
            $view = $tokenizeview($rawurl);
            if ($view === $rawurl) {
                // tokenizeview() no-op'd (empty url, no token, or not a pluginfile url).
                return [$view, ''];
            }
            return [$view, $view . '&forcedownload=1'];
        };

        $result = [];
        foreach (manager::get_achievements($params['filters']) as $a) {

            [$fileurl, $filedownloadurl] = $tokenizewithdownload((string) ($a->fileurl ?? ''));
            $userpictureurl               = $tokenizeview((string) ($a->userpictureurl ?? ''));

            $result[] = [
                'id'              => (int)    $a->id,
                'userid'          => (int)    $a->userid,
                'fullname'        => (string) $a->fullname,
                'userpictureurl'  => $userpictureurl,
                'title'           => (string) $a->title,
                'issuer'          => (string) ($a->issuer ?? ''),
                'issuedate'       => (int)    ($a->issuedate ?? 0),
                'issuedatefmt'    => (string) ($a->issuedateformatted ?? ''),
                'type'            => (string) ($a->type    ?? ''),
                'fileurl'         => $fileurl,
                'filedownloadurl' => $filedownloadurl,
                'likecount'       => (int)    $a->likecount,
                'commentcount'    => (int)    $a->commentcount,
                'notes'          => (string) ($a->notes ?? ''),
                'department'      => (string) ($a->department ?? ''),
                'userliked'       => (int)    $a->userliked,
                'canmanage'       => $canmanage,
                'editurl'         => $canmanage
                    ? (new \moodle_url('/local/halloffame/pages/edit_achievement.php',
                        ['id' => $a->id]))->out(false)
                    : '',
                'deleteurl'       => $canmanage
                    ? (new \moodle_url('/local/halloffame/pages/delete_achievement.php',
                        ['id' => $a->id, 'sesskey' => sesskey()]))->out(false)
                    : '',
            ];
        }
        return $result;
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'id'              => new external_value(PARAM_INT,  'Achievement ID'),
            'userid'          => new external_value(PARAM_INT,  'User ID'),
            'fullname'        => new external_value(PARAM_TEXT, 'Full name'),
            'userpictureurl'  => new external_value(PARAM_TEXT, 'Authenticated user profile picture URL (view only, no forcedownload)'),
            'title'           => new external_value(PARAM_TEXT, 'Title'),
            'issuer'          => new external_value(PARAM_TEXT, 'Issuer'),
            'issuedate'       => new external_value(PARAM_INT,  'Issue timestamp'),
            'issuedatefmt'    => new external_value(PARAM_TEXT, 'Formatted issue date'),
            'type'            => new external_value(PARAM_TEXT, 'Certificate type'),
            'fileurl'         => new external_value(PARAM_TEXT, 'Authenticated view/inline file URL'),
            'filedownloadurl' => new external_value(PARAM_TEXT, 'Authenticated download URL (forcedownload=1) for the file'),
            'likecount'       => new external_value(PARAM_INT,  'Like count'),
            'userliked'       => new external_value(PARAM_INT,  '1 if user liked'),
            'commentcount'    => new external_value(PARAM_INT,  'Comment count'),
            'notes'    => new external_value(PARAM_TEXT, 'notes'),
            'canmanage'       => new external_value(PARAM_BOOL, 'Can manage achievements'),
            'editurl'         => new external_value(PARAM_URL,  'Edit URL'),
            'deleteurl'       => new external_value(PARAM_URL,  'Delete URL'),
            'department'      => new external_value(PARAM_TEXT, 'Department'),
        ]));
    }
}