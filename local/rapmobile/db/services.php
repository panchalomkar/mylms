<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * RAP Mobile API — All Web Service Definitions
 * Created by Omkar
 *
 * @package    local_rapmobile
 * @copyright  2024 RAP Tech Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
      'local_rapmobile_get_user_courses' => [   // ← ADD THIS BLOCK
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_user_courses',
        'description' => 'Get user courses with fixed image URLs',
        'type'        => 'read',
        'ajax'        => true,
    ],

    // =========================================================
    // HALL OF FAME — UPLOAD CERTIFICATE (local_rapmobile wrapper)
    // =========================================================

    'local_rapmobile_get_halloffame_categories' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_halloffame_categories',
        'description' => 'Get active Hall of Fame categories for upload dropdown',
        'type'        => 'read',
        'ajax'        => true,
    ],

    'local_rapmobile_get_my_halloffame_submissions' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_my_halloffame_submissions',
        'description' => 'Get the current user\'s Hall of Fame certificate submissions',
        'type'        => 'read',
        'ajax'        => true,
    ],

    'local_rapmobile_upload_halloffame_certificate' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'upload_halloffame_certificate',
        'description' => 'Upload a certificate to Hall of Fame (base64 file, creates pending submission)',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'rapmobile_get_course_contents' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_course_contents',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Course contents with file download URLs per module',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],

    'rapmobile_get_livesession_detail' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_livesession_detail',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Upcoming live session details (zoom/googlemeet/facetoface/ilt) for a user',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_rapmobile_delete_halloffame_submission' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'delete_halloffame_submission',
        'description' => 'Delete a pending (not yet approved) Hall of Fame submission',
        'type'        => 'write',
        'ajax'        => true,
    ],
    // =========================================================
// HALL OF FAME — FILE UPLOAD SUBMIT
// =========================================================

'rapmobile_halloffame_submit_certificate' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'halloffame_submit_certificate',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Submit Hall of Fame certificate with file upload via draftitemid',
    'type'        => 'write',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],

'rapmobile_get_my_certificates' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_my_certificates',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Get all certificates (earned + pending) for user',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],

    // =========================================================
    // AUTH & USER
    // =========================================================
    'rapmobile_get_course_status_counts' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_course_status_counts',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Course status counts — Not Started, In Progress, Completed',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_get_user_stats' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_user_stats',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Dashboard stat cards: Courses/Activities/Certificates',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
    'rapmobile_get_user_profile' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_user_profile',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Get full user profile — name, email, avatar, department',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_change_password' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'change_password',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Change user password',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // =========================================================
    // DASHBOARD
    // =========================================================

    'rapmobile_get_dashboard' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_dashboard',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Dashboard: past due, due soon, due later counts + upcoming events',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // =========================================================
    // LEARNING — COURSES
    // =========================================================

    'rapmobile_get_my_courses' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_my_courses',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'My Courses screen — all enrolled with progress, due date, thumbnail',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_inprogress_courses' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_inprogress_courses',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Continue Learning — in-progress courses only (progress 1-99)',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_course_activities' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_course_activities',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Total activity count and completed count for My Learning screen',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_search_courses' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'search_courses',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Search courses by keyword — My Learning search bar',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // =========================================================
    // LIVE SESSIONS — ILT
    // =========================================================

    'rapmobile_get_ilt_sessions' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_ilt_sessions',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Live screen — ILT sessions with date, instructor, capacity, join URL',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_ilt_session_detail' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_ilt_session_detail',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Single ILT session full detail',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_signup_ilt_session' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'signup_ilt_session',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Book / sign up for ILT session',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_cancel_ilt_signup' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'cancel_ilt_signup',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Cancel ILT session booking',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // =========================================================
    // GAMIFICATION — POINTS / SPIN / SCRATCH / LEADERBOARD
    // =========================================================

    'rapmobile_search_gift_users' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'search_gift_users',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Search employees to gift points to',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_send_gift_points' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'send_gift_points',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Send gift reward points to another user',
    'type'        => 'write',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_redeem_scratch_card' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'redeem_scratch_card',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Mark scratch card as redeemed and award points',
    'type'        => 'write',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
    'rapmobile_get_game_dashboard' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_game_dashboard',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Game screen — points, badges, level, spin status, leaderboard',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_spin_wheel' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'spin_wheel',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Spin the wheel — add won points',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_scratch_cards' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_scratch_cards',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Scratch & Win — get user cards with remaining count',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_leaderboard' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_leaderboard',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Leaderboard — ranked users with points, avatar, rank icon',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_redeem_points' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'redeem_points',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Redeem available points',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_add_point' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'add_point',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Add points — login, quiz, course complete events',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // =========================================================
    // POLLS
    // =========================================================

    'rapmobile_get_polls' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_polls',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Take a Poll — active polls for user',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_submit_poll' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'submit_poll',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Submit poll answer',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // =========================================================
    // HALL OF FAME — registered by local_halloffame plugin
    // (DO NOT move to $functions — they belong to another plugin)
    // =========================================================
    'local_halloffame_get_awards' => [
        'classname'     => 'local_halloffame\external\get_awards',
        'methodname'    => 'execute',
        'description'   => 'Get awards with optional filters.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:view',
    ],
    'local_halloffame_get_achievements' => [
        'classname'     => 'local_halloffame\external\get_achievements',
        'methodname'    => 'execute',
        'description'   => 'Get approved achievements with optional filters.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:view',
    ],
    'local_halloffame_submit_certificate' => [
        'classname'     => 'local_halloffame\external\submit_certificate',
        'methodname'    => 'execute',
        'description'   => 'Submit an external certificate for admin review.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:submit',
    ],
    'local_halloffame_like_item' => [
        'classname'     => 'local_halloffame\external\like_item',
        'methodname'    => 'execute',
        'description'   => 'Toggle like on an award or achievement.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:view',
    ],
    'local_halloffame_get_comments' => [
        'classname'     => 'local_halloffame\external\get_comments',
        'methodname'    => 'execute',
        'description'   => 'Get comments for an award or achievement.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:view',
    ],
    'local_halloffame_add_comment' => [
        'classname'     => 'local_halloffame\external\add_comment',
        'methodname'    => 'execute',
        'description'   => 'Add a comment to an award or achievement.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:view',
    ],
    'local_halloffame_edit_comment' => [
        'classname'     => 'local_halloffame\external\edit_comment',
        'methodname'    => 'execute',
        'description'   => 'Edit (update) the text of a comment. Only the comment author may edit.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:view',
    ],
    'local_halloffame_delete_comment' => [
        'classname'     => 'local_halloffame\external\delete_comment',
        'methodname'    => 'execute',
        'description'   => 'Delete (soft-delete) a comment.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:view',
    ],
    'local_halloffame_get_my_submissions' => [
        'classname'     => 'local_halloffame\external\get_my_submissions',
        'methodname'    => 'execute',
        'description'   => 'Get the current user\'s own certificate submissions (all statuses, newest first).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/halloffame:submit',
    ],

    // =========================================================
    // PDFJSFOLDER
    // =========================================================

    'rapmobile_get_pdfjsfolder_list' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_pdfjsfolder_list',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Get all PDF folder activities for user across enrolled courses',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_pdfjsfolder_detail' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_pdfjsfolder_detail',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Get single PDF folder detail with all files',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_mark_pdfjsfolder_complete' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'mark_pdfjsfolder_complete',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Mark PDF folder activity as complete for user',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // =========================================================
    // PAGE, URL, ZOOM, GOOGLE MEET, GO1
    // =========================================================

    'rapmobile_get_page_activities' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_page_activities',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Get all Page activities for user with content and completion',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_url_activities' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_url_activities',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Get all URL activities for user',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_zoom_meetings' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_zoom_meetings',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Get all Zoom meeting activities — join URL, status, duration',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_googlemeet_activities' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_googlemeet_activities',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Get all Google Meet activities — join URL, status',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_go1_content' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_go1_content',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Get all GO1 content activities — launch URL, type, completion',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_ilt_activities' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_ilt_activities',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Get all ILT activities for user with sessions and completion',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // =========================================================
    // LEARNING PATHS
    // =========================================================

    'rapmobile_get_learning_paths' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_learning_paths',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Get learning paths list for user — LP list screen',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_lp_courses' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_lp_courses',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Get courses inside a learning path — LP detail screen',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_finish_lp_course' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'finish_lp_course',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Mark a course complete inside a learning path',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // =========================================================
    // ATTENDANCE
    // =========================================================

    'rapmobile_get_my_attendance' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_my_attendance',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'User attendance screen — sessions with status, date, time, method, points',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_session_qr_info' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_session_qr_info',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Get session info before QR scan — checks if active and scannable',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_mark_attendance_by_qr' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'mark_attendance_by_qr',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Mark attendance via QR scan — writes to attendance_log',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // =========================================================
    // AUTH — LOGOUT
    // =========================================================

    'rapmobile_logout' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'logout',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Logout user and invalidate token',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // =========================================================
    // SKILLGAP
    // =========================================================

    'rapmobile_get_my_skills' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_my_skills',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'My Skills dashboard — stats, competencies, radar, history, promotion',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_assessment_quiz' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_assessment_quiz',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Start/resume assessment — returns all questions with options',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_skillgap_save_response' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'skillgap_save_response',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Save single quiz answer during assessment',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_skillgap_submit_assessment' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'skillgap_submit_assessment',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Final submit — computes scores and returns results',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_get_team_analytics' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'get_team_analytics',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Team Analytics tab — readiness, competency performance, member drilldown',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
'local_rapmobile_get_notifications' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_rapmobile_notifications',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Get all push notifications for a user.',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],

'local_rapmobile_get_livesessions' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_rapmobile_livesessions',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Get upcoming live sessions.',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],

'local_rapmobile_get_upcoming_sessions' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_rapmobile_upcoming_sessions',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Get all upcoming bookable ILT sessions.',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_get_app_config' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_app_config',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Get tab visibility + version control + feature flags',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'local_rapmobile_remove_assign_submission' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'remove_assign_submission',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Remove a student\'s assignment submission',
    'type'        => 'write',
    'capabilities'=> 'mod/assign:submit',
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_save_app_config' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'save_app_config',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Admin saves tab/version config',
    'type'        => 'write',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'local_rapcourse_activity_view' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'activity_view',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Mark activity as viewed — triggers view-based completion',
    'type'        => 'write',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_get_questionnaire_list' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_questionnaire_list',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'List questionnaires visible to the user across their courses',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_get_questionnaire_structure' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_questionnaire_structure',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Full question set (all types, sections, choices) for one questionnaire',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_submit_questionnaire' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'submit_questionnaire',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Submit a completed questionnaire response, all question types',
    'type'        => 'write',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_get_questionnaire_report' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_questionnaire_report',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Aggregated report for teachers/admins',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_get_my_questionnaire_response' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_my_questionnaire_response',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Current users own past response for review/edit',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_get_my_questionnaire_responses' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_my_questionnaire_responses',
    'classpath'   => '',
    'description' => 'Get all attempts submitted by this user for a questionnaire',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_get_submission_comments' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'get_submission_comments',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Get assignment submission comments',
    'type'        => 'read',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_add_submission_comment' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'add_submission_comment',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Add an assignment submission comment',
    'type'        => 'write',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],
'rapmobile_delete_submission_comment' => [
    'classname'   => 'local_rapmobile_external',
    'methodname'  => 'delete_submission_comment',
    'classpath'   => 'local/rapmobile/externallib.php',
    'description' => 'Delete an assignment submission comment',
    'type'        => 'write',
    'ajax'        => true,
    'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
],

    // =========================================================
    // PUSH NOTIFICATIONS — FCM device token management
    // =========================================================

    'rapmobile_register_device' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'register_device',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Register or refresh an FCM device token. '
                       . 'Call on login AND on FirebaseMessaging.onTokenRefresh. '
                       . 'Atomic upsert — never creates duplicate rows.',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'rapmobile_unregister_device' => [
        'classname'   => 'local_rapmobile_external',
        'methodname'  => 'unregister_device',
        'classpath'   => 'local/rapmobile/externallib.php',
        'description' => 'Deactivate a specific FCM token on logout. '
                       . 'Sets active=0 — never deletes. No-op if token not found.',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

];

// =========================================================
// Pre-built service — ONE dedicated service for the mobile app
// All rapmobile_* functions are auto-included via array_keys($functions)
// All inbuilt/third-party functions are added via array_merge below
// =========================================================
$services = [
    'RAP Mobile App API' => [
        'functions' => array_merge(array_keys($functions), [

            // =========================================================
            // SITE / AUTH
            // =========================================================
            'core_webservice_get_site_info',           // logged-in user profile, userid, avatar
            'tool_mobile_get_public_config',           // app config on launch

            // =========================================================
            // USER PROFILE — UPDATE
            // =========================================================
            'core_user_get_users',
            'core_user_get_users_by_field',
            'core_user_get_course_user_profiles',
            'core_user_update_users',                  // update phone, name, country
            'core_user_update_picture',                // update profile picture

            // =========================================================
            // CALENDAR / EVENTS
            // =========================================================
            'core_calendar_get_action_events_by_timesort', // upcoming events on dashboard

            // =========================================================
            // ENROLMENT / COURSES
            // =========================================================
            'core_enrol_get_users_courses',            // enrolled courses list
            'core_course_get_course_module',           // single CM detail by cmid
            'core_course_get_contents',   
            'core_completion_override_activity_completion_status',             // course sections + activities

            // =========================================================
            // COMPLETION
            // =========================================================
            'core_completion_update_activity_completion_status_manually', // mark complete

            // =========================================================
            // BADGES
            // =========================================================
            'core_badges_get_user_badges',             // user badges list

            // =========================================================
            // IOMAD CERTIFICATE
            // =========================================================
            'mod_iomadcertificate_get_issued_iomadcertificates', // issued certs
            'mod_iomadcertificate_issue_iomadcertificate',       // issue cert
            'mod_iomadcertificate_view_iomadcertificate',        // view cert (triggers completion)

            // =========================================================
            // RESOURCE / FILE
            // =========================================================
            'mod_resource_get_resources_by_courses',   // file resources in course

            // =========================================================
            // FORUM
            // =========================================================
            'mod_forum_get_forums_by_courses',
            'mod_forum_get_forum_discussions',
            'mod_forum_get_discussion_posts',
            'mod_forum_get_discussion_post',
            'mod_forum_add_discussion',
            'mod_forum_add_discussion_post',
            'mod_forum_update_discussion_post',
            'mod_forum_delete_post',
            'mod_forum_view_forum',
            'mod_forum_view_forum_discussion',
            'mod_forum_set_subscription_state',
            'mod_forum_toggle_favourite_state',
            'mod_forum_get_forum_access_information',
            'mod_forum_get_discussion_posts_by_userid',

            // =========================================================
            // ASSIGNMENT
            // =========================================================
            'mod_assign_get_assignments',
            'mod_assign_get_submission_status',
            'mod_assign_save_submission',
            'mod_assign_submit_for_grading',

            // =========================================================
            // SCORM
            // =========================================================
            'mod_scorm_get_scorms_by_courses',
            'mod_scorm_get_scorm_access_information',
            'mod_scorm_get_scorm_sco_tracks',
            'mod_scorm_get_scorm_user_data',
            'mod_scorm_insert_scorm_tracks',
            'mod_scorm_view_scorm',
            'mod_scorm_launch_sco',
// comments for assignment 
'core_comment_get_comments',
'core_comment_add_comments',
'core_comment_delete_comments',
            // =========================================================
            // QUIZ
            // =========================================================
            'mod_quiz_get_quizzes_by_courses',
            'mod_quiz_get_quiz_access_information',
            'mod_quiz_get_user_attempts',
            'mod_quiz_start_attempt',
            'mod_quiz_get_attempt_data',
            'mod_quiz_save_attempt',
            'mod_quiz_process_attempt',
            'mod_quiz_get_attempt_review',
            'mod_quiz_get_user_best_grade',

            // =========================================================
            // H5P ACTIVITY
            // =========================================================
            'mod_h5pactivity_get_h5pactivities_by_courses',
            'mod_h5pactivity_get_h5pactivity_access_information',
            'mod_h5pactivity_get_attempts',

            // =========================================================
            // VIDEO — SuperVideo / VideoTime
            // =========================================================
            'mod_supervideo_view_supervideo',
            'mod_videotime_get_videotime',
            'mod_videotime_view_videotime',

            // =========================================================
            // FEEDBACK
            // =========================================================
            'mod_feedback_get_feedbacks_by_courses',
            'mod_feedback_get_feedback_access_information',
            'mod_feedback_get_items',
            'mod_feedback_launch_feedback',
            'mod_feedback_get_page_items',
            'mod_feedback_process_page',
            'mod_feedback_get_finished_responses',
            'mod_feedback_get_current_completed_tmp',
            'mod_feedback_view_feedback',

            // =========================================================
            // PAGE / URL (inbuilt — view triggers completion)
            // =========================================================
            'mod_page_view_page',
            'mod_url_view_url',

            // =========================================================
            // ATTENDANCE (mod_attendance plugin)
            // =========================================================
            'mod_attendance_get_sessions',
            'mod_attendance_get_session',
            'mod_attendance_get_courses_with_today_sessions',
            'mod_attendance_update_user_status',

            // =========================================================
            // NOTIFICATIONS
            // =========================================================
            'core_message_get_unread_notification_count',
            'core_message_get_user_notification_preferences',
            'core_message_mark_notification_read',
            'core_message_mark_all_notifications_as_read',

            // =========================================================
            // MESSAGES / CONVERSATIONS
            // =========================================================
            'core_message_get_messages',
            'core_message_get_conversations',
            'core_message_get_conversation',
            'core_message_get_conversation_messages',
            'core_message_get_conversation_between_users',
            'core_message_get_conversation_members',
            'core_message_get_unread_conversations_count',
            'core_message_get_unread_conversation_counts',
            'core_message_get_conversation_counts',
            'core_message_get_member_info',
            'core_message_get_self_conversation',
            'core_message_send_instant_messages',

            // =========================================================
            // CONTACTS
            // =========================================================
            'core_message_get_user_contacts',
            'core_message_get_contact_requests',
            'core_message_get_received_contact_requests_count',
            'core_message_get_blocked_users',

            // =========================================================
            // MESSAGE PREFERENCES / READ STATUS
            // =========================================================
            'core_message_get_user_message_preferences',
            'core_message_mark_message_read',
            'core_message_mark_all_messages_as_read',
            'core_message_mark_all_conversation_messages_as_read',
            'core_files_upload',

            // =========================================================
            // REWARD / GAMIFICATION (local_reward plugin)
            // =========================================================
            'local_reward_points_user',
            'local_reward_get_leaderboard',
            'local_reward_add_point',
            'local_reward_scratch_card',
            'local_coursefiles_get_course_downloads',
            // =========================================================
            // AI ROLEPLAY (mod_airoleplay plugin — separate db/services.php)
            // =========================================================
            'mod_airoleplay_create_session',
            'mod_airoleplay_get_opening_message',
            'mod_airoleplay_send_message',
            'mod_airoleplay_end_session',
            'mod_airoleplay_get_evaluation',
            'mod_airoleplay_upload_session_audio',
            'core_auth_request_password_reset',

            // =========================================================
            // PUSH NOTIFICATIONS — FCM device registration
            // =========================================================
            'rapmobile_register_device',
            'rapmobile_unregister_device',

        ]),
        'restrictedusers' => 0,
        'enabled'         => 1,
        'shortname'       => 'rap_mobile_api',
        'downloadfiles'   => 1,
        'uploadfiles'     => 0,
    ],
];