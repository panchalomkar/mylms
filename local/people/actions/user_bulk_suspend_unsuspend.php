<?php
/**
 * Bulk suspend / unsuspend — supports AJAX JSON (called from JS modal confirm).
 * Does NOT redirect to another page; returns JSON or redirects back.
 */
require_once('../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir  . '/authlib.php');

require_login();

global $SESSION, $DB, $OUTPUT, $PAGE, $USER;

$sitecontext = context_system::instance();
require_capability('moodle/user:update', $sitecontext);

$returnurl = !empty($SESSION->return)
    ? new moodle_url($SESSION->return)
    : new moodle_url('/local/people/index.php');

$suspend = optional_param('suspend', 0, PARAM_BOOL);
$unlock  = optional_param('unlock',  0, PARAM_BOOL);

// ── Resolve user IDs ─────────────────────────────────────────────────────────
$rawbody = file_get_contents('php://input');
if (!empty($rawbody)) {
    $decoded = json_decode($rawbody, true);
    if (!empty($decoded['data']) && is_array($decoded['data'])) {
        $SESSION->bulk_users = array_map('intval', $decoded['data']);
    }
}

$bulkjson = optional_param('bulk_action', '', PARAM_RAW);
if (!empty($bulkjson)) {
    $bulk = json_decode($bulkjson, true);
    if (!empty($bulk['data']) && is_array($bulk['data'])) {
        $SESSION->bulk_users = array_map('intval', $bulk['data']);
    }
}

// ── Detect AJAX ──────────────────────────────────────────────────────────────
$wantsJson = (
    !empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
) || (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || optional_param('ajax', 0, PARAM_BOOL);

if (empty($SESSION->bulk_users) || !is_array($SESSION->bulk_users)) {
    if ($wantsJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No users selected']);
        exit;
    }
    redirect($returnurl, 'No users selected', 2);
}

require_sesskey();

list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
$rs = $DB->get_recordset_select('user', "id $in", $params);

$affected = [];
$skipped  = [];

foreach ($rs as $user) {
    if (is_siteadmin($user) || $USER->id == $user->id) {
        $skipped[] = fullname($user);
        continue;
    }

    if ($suspend && $user->suspended != 1) {
        $user->suspended = 1;
        \core\session\manager::kill_user_sessions($user->id);
        user_update_user($user, false);
        $affected[] = fullname($user);
    } elseif ($unlock && $user->suspended == 1) {
        $user->suspended = 0;
        user_update_user($user, false);
        login_unlock_account($user);
        $affected[] = fullname($user);
    } else {
        $skipped[] = fullname($user);
    }
}
$rs->close();
unset($SESSION->bulk_users);

// ── Build message ─────────────────────────────────────────────────────────────
$action  = $suspend ? 'suspended' : 'unsuspended';
$names   = implode(', ', $affected);
$langkey = $suspend
    ? (count($affected) > 1 ? 'users_suspended1'  : 'users_suspended')
    : 'users_unsuspended';
$message = !empty($affected)
    ? get_string($langkey, 'local_people', $names)
    : 'No users were ' . $action . '.';

// ── Respond ───────────────────────────────────────────────────────────────────
if ($wantsJson) {
    header('Content-Type: application/json');
    echo json_encode([
        'success'  => !empty($affected),
        'affected' => $affected,
        'skipped'  => $skipped,
        'message'  => $message,
    ]);
    exit;
}

redirect($returnurl, $message, 3);
