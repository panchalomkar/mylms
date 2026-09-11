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
use moodle_url;

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
    global $USER;

    $params = self::validate_parameters(
        self::execute_parameters(),
        ['filters' => $filters]
    );

    $context = context_system::instance();
    require_capability('local/halloffame:view', $context);

    $canmanage = has_capability('local/halloffame:manageawards', $context);

    $wstoken = optional_param('wstoken', '', PARAM_ALPHANUM);

    $tokenize = function (string $rawurl) use ($wstoken): array {
        if ($rawurl === '' || $wstoken === '') {
            return [$rawurl, ''];
        }
        $wsurl = str_replace('/pluginfile.php/', '/webservice/pluginfile.php/', $rawurl);
        $view  = $wsurl . (strpos($wsurl, '?') === false ? '?' : '&') . 'token=' . $wstoken;
        $download = $view . '&forcedownload=1';
        return [$view, $download];
    };

    $result = [];

    foreach (manager::get_awards($params['filters']) as $a) {

        [$image, $imagedownloadurl] = $tokenize((string) ($a->image ?? ''));
        [$userpictureurl, ] = $tokenize((string) ($a->userpictureurl ?? ''));

        // Liked users for the popup — capped, tokenized pics.
        $likedusers = [];
        foreach (manager::get_likers((int) $a->id, 'award', 10) as $lu) {
            [$likerpic, ] = $tokenize($lu['userpictureurl']);
            $likedusers[] = [
                'userid'         => $lu['userid'],
                'fullname'       => $lu['fullname'],
                'userpictureurl' => $likerpic,
                'timeago'        => format_time(time() - $lu['timecreated']),
                'timecreated'    => $lu['timecreated'],
            ];
        }

        $result[] = [
            'id'               => (int) $a->id,
            'userid'           => (int) $a->userid,
            'fullname'         => (string) $a->fullname,
            'userpictureurl'   => $userpictureurl,
            'title'            => (string) $a->title,
            'department'       => (string) ($a->department ?? ''),
            'category'         => (string) ($a->category   ?? ''),
            'month'            => (int) $a->month,
            'year'             => (int) $a->year,
            'monthname'        => (string) ($a->monthname ?? ''),
            'message'          => (string) ($a->message ?? ''),
            'image'            => $image,
            'imagedownloadurl' => $imagedownloadurl,

            'likecount'    => (int) $a->likecount,
            'userliked'    => (int) $a->userliked,
            'commentcount' => (int) $a->commentcount,
            'canmanage'    => $canmanage,
            'likedusers'   => $likedusers,

            'editurl' => $canmanage
                ? (new moodle_url('/local/halloffame/pages/edit_award.php', [
                    'id' => $a->id
                ]))->out(false)
                : '',

            'deleteurl' => $canmanage
                ? (new moodle_url('/local/halloffame/pages/delete_award.php', [
                    'id' => $a->id,
                    'sesskey' => sesskey()
                ]))->out(false)
                : '',
        ];
    }

    return $result;
}

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([

                'id'             => new external_value(PARAM_INT,  'Award ID'),
                'userid'         => new external_value(PARAM_INT,  'User ID'),
                'fullname'       => new external_value(PARAM_TEXT, 'Full name'),
               'userpictureurl' => new external_value(PARAM_TEXT, 'Authenticated user profile picture URL'),
                'title'          => new external_value(PARAM_TEXT, 'Title'),

                'department' => new external_value(PARAM_TEXT, 'Department'),
                'category'   => new external_value(PARAM_TEXT, 'Category'),

                'month'      => new external_value(PARAM_INT,  'Month'),
                'year'       => new external_value(PARAM_INT,  'Year'),
                'monthname'  => new external_value(PARAM_TEXT, 'Month name'),

                'message'    => new external_value(PARAM_TEXT, 'Message'),
                'image'      => new external_value(PARAM_TEXT, 'Authenticated view/inline image URL (empty string if none)'),
                'imagedownloadurl' => new external_value(PARAM_TEXT, 'Authenticated download URL (forcedownload=1) for the image (empty string if none)'),

                // ✅ Likes
                'likecount'  => new external_value(PARAM_INT, 'Like count'),
                'likedusers' => new external_multiple_structure(
    new external_single_structure([
        'userid'         => new external_value(PARAM_INT,  'User ID'),
        'fullname'       => new external_value(PARAM_TEXT, 'Full name'),
        'userpictureurl' => new external_value(PARAM_TEXT, 'Authenticated profile picture URL'),
        'timeago'        => new external_value(PARAM_TEXT, 'Human readable, e.g. "2 mins ago"'),
        'timecreated'    => new external_value(PARAM_INT,  'Unix timestamp liked'),
    ]),
    'Users who liked this award (most recent first, capped)'
),
                'userliked'  => new external_value(PARAM_INT, '1 if user liked'),

                // ✅ Comments
                'commentcount' => new external_value(PARAM_INT, 'Comment count'),

                // ✅ Permissions
                'canmanage' => new external_value(PARAM_BOOL, 'Can manage awards'),

                // ✅ Action URLs
                'editurl'   => new external_value(PARAM_URL, 'Edit URL'),
                'deleteurl' => new external_value(PARAM_URL, 'Delete URL'),
            ])
        );
    }
}