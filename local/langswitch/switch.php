<?php
require_once('../../config.php');
require_login();

header('Content-Type: application/json');

$current = optional_param('current', 0, PARAM_INT);
if ($current) {
    global $SESSION;
    echo json_encode(['lang' => $SESSION->lang ?? 'en']);
    exit;
}

require_sesskey();

$lang    = required_param('lang', PARAM_ALPHA);
$allowed = ['en', 'hi', 'mr', 'gu'];

if (!in_array($lang, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid language']);
    exit;
}

// Save to USER preference (permanent) — Moodle's preference layer is
// already MUC-cache-backed, this is not a bottleneck and does not need
// to be "optimized away".
set_user_preference('local_langswitch_lang', $lang);

// Save to SESSION (current session)
$SESSION->lang = $lang;

// Save to COOKIE (available before session on next load)
setcookie(
    'moodle_langswitch',
    $lang,
    [
        'expires'  => time() + (365 * 24 * 60 * 60),
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]
);

echo json_encode(['status' => 'ok', 'lang' => $lang]);
