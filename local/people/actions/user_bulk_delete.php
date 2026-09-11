<?php
/**
 * Bulk user delete — handles both JSON AJAX (modal confirm) and direct POST.
 * Does NOT redirect to another page for confirmation — that is handled by JS modal.
 */
require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/user/lib.php');

require_login();
require_capability('moodle/user:delete', context_system::instance());

global $SESSION, $DB, $OUTPUT, $PAGE, $USER;

$returnurl = !empty($SESSION->return)
    ? new moodle_url($SESSION->return)
    : new moodle_url('/local/people/index.php');

// ── Resolve user IDs ────────────────────────────────────────────────────────
// AJAX path: JSON body {"name":"delete","data":[1,2,3]}
$rawbody = file_get_contents('php://input');
if (!empty($rawbody)) {
    $decoded = json_decode($rawbody, true);
    if (!empty($decoded['data']) && is_array($decoded['data'])) {
        $SESSION->bulk_users = array_map('intval', $decoded['data']);
    }
}

// Form POST path
$bulkjson = optional_param('bulk_action', '', PARAM_RAW);
if (!empty($bulkjson)) {
    $bulk = json_decode($bulkjson, true);
    if (!empty($bulk['data']) && is_array($bulk['data'])) {
        $SESSION->bulk_users = array_map('intval', $bulk['data']);
    }
}

$userids_param = optional_param('userids', '', PARAM_TEXT);
if (!empty($userids_param)) {
    $SESSION->bulk_users = array_filter(array_map('intval', explode(',', $userids_param)));
}

// ── Detect if caller expects JSON (AJAX) ────────────────────────────────────
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

// ── Perform deletion ─────────────────────────────────────────────────────────
list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
$rs = $DB->get_recordset_select('user', "id $in", $params);

$deleted = [];
$skipped = [];

foreach ($rs as $user) {
    if (is_siteadmin($user) || $USER->id == $user->id) {
        $skipped[] = fullname($user);
        continue;
    }
    if (delete_user($user)) {
        $deleted[] = fullname($user);
        unset($SESSION->bulk_users[$user->id]);
    } else {
        $skipped[] = fullname($user) . ' (failed)';
    }
}
$rs->close();
\core\session\manager::gc();
unset($SESSION->bulk_users);

// ── Respond ──────────────────────────────────────────────────────────────────
if ($wantsJson) {
    header('Content-Type: application/json');
    echo json_encode([
        'success'  => !empty($deleted),
        'deleted'  => $deleted,
        'skipped'  => $skipped,
        'message'  => !empty($deleted)
            ? get_string('deleteconfirm', 'local_people', implode(', ', $deleted))
            : 'No users were deleted.',
    ]);
    exit;
}

// Non-AJAX fallback: stay on same page, show notice via redirect
$msg = !empty($deleted)
    ? get_string('deleteconfirm', 'local_people', implode(', ', $deleted))
    : 'No users were deleted.';
redirect($returnurl, $msg, 3);
