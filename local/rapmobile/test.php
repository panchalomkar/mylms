<?php
// RAP Mobile — FCM Direct Diagnostic
// Does NOT use fcm_service class — tests JWT directly step by step.
require_once('../../config.php');

echo '<pre style="background:#111;color:#eee;padding:20px;font-size:13px">';
echo "=== RAP Mobile FCM Diagnostic ===\n\n";

// ── 1. Read JSON from DB ──────────────────────────────────────────
echo "STEP 1: Reading service_account_json from DB\n";
echo str_repeat('-', 50) . "\n";
$json_raw = get_config('local_rapmobile', 'rapmobile_service_account_json');
echo "Length in DB : " . strlen($json_raw) . " chars\n";
echo "Starts with  : " . substr($json_raw, 0, 30) . "\n";
echo "Ends with    : " . substr($json_raw, -30) . "\n\n";

$creds = json_decode($json_raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON DECODE ERROR: " . json_last_error_msg() . "\n";
    die("</pre>");
}
echo "project_id   : " . ($creds['project_id']   ?? 'MISSING') . "\n";
echo "client_email : " . ($creds['client_email'] ?? 'MISSING') . "\n";
echo "private_key  : " . (!empty($creds['private_key']) ? "Present (" . strlen($creds['private_key']) . " chars)" : "MISSING") . "\n\n";

// ── 2. Inspect the private key ────────────────────────────────────
echo "STEP 2: Private Key Inspection\n";
echo str_repeat('-', 50) . "\n";
$pk_raw = $creds['private_key'] ?? '';
echo "Key length          : " . strlen($pk_raw) . " chars\n";
echo "Has real newlines   : " . (strpos($pk_raw, "\n") !== false   ? "YES" : "NO  <- PROBLEM") . "\n";
echo "Has literal backslash-n : " . (strpos($pk_raw, '\\n') !== false ? "YES <- needs fix" : "NO") . "\n";
$after_header = strpos($pk_raw, "-----BEGIN PRIVATE KEY-----");
if ($after_header !== false) {
    $chars_after = substr($pk_raw, $after_header + 27, 5);
    echo "Chars after header  : " . json_encode($chars_after) . " (should contain real newline \\n)\n";
}
echo "\n";

// ── 3. Fix & load the key ─────────────────────────────────────────
echo "STEP 3: Fix and Load Private Key\n";
echo str_repeat('-', 50) . "\n";
$pk = $pk_raw;

// Fix: literal \n -> real newline
if (strpos($pk, '\\n') !== false && strpos($pk, "\n") === false) {
    $pk = str_replace('\\n', "\n", $pk);
    echo "Applied fix: replaced literal backslash-n with real newlines\n";
} else {
    echo "No newline fix needed\n";
}

$pkey = openssl_pkey_get_private($pk);
if ($pkey === false) {
    echo "OPENSSL FAILED: " . openssl_error_string() . "\n\n";
    // Try PEM rewrap
    echo "Trying PEM rewrap...\n";
    $b64only  = preg_replace('/-----[^-]+-----|\s/', '', $pk);
    $rewrapped = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($b64only, 64, "\n") . "-----END PRIVATE KEY-----\n";
    $pkey2 = openssl_pkey_get_private($rewrapped);
    if ($pkey2 === false) {
        echo "PEM rewrap also failed: " . openssl_error_string() . "\n";
        echo "\nCONCLUSION: Private key is corrupted.\n";
        echo "ACTION NEEDED: Re-download service account JSON from Firebase Console\n";
        echo "and re-paste the FULL file content in admin settings.\n";
        die("</pre>");
    }
    echo "PEM rewrap worked!\n\n";
    $pk   = $rewrapped;
    $pkey = $pkey2;
} else {
    $info = openssl_pkey_get_details($pkey);
    echo "Key loaded OK — RSA " . ($info['bits'] ?? '?') . " bit\n\n";
}

// ── 4. Build JWT ──────────────────────────────────────────────────
echo "STEP 4: Build and Sign JWT\n";
echo str_repeat('-', 50) . "\n";
$now          = time();
$client_email = $creds['client_email'];
$project_id   = $creds['project_id'];

$b64u = fn(string $d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');

$hdr    = $b64u(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
$claims = $b64u(json_encode([
    'iss'   => $client_email,
    'sub'   => $client_email,
    'aud'   => 'https://oauth2.googleapis.com/token',
    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
    'iat'   => $now,
    'exp'   => $now + 3600,
]));
$input = $hdr . '.' . $claims;

if (!openssl_sign($input, $sig, $pkey, OPENSSL_ALGO_SHA256)) {
    echo "SIGNING FAILED: " . openssl_error_string() . "\n";
    die("</pre>");
}
$jwt = $input . '.' . $b64u($sig);
echo "JWT built OK — " . strlen($jwt) . " chars\n";
echo "aud = https://oauth2.googleapis.com/token\n";
echo "iss = " . $client_email . "\n\n";

// ── 5. OAuth2 exchange ────────────────────────────────────────────
echo "STEP 5: OAuth2 Token Exchange\n";
echo str_repeat('-', 50) . "\n";
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS     => 'grant_type=' . urlencode('urn:ietf:params:oauth:grant-type:jwt-bearer')
                            . '&assertion=' . urlencode($jwt),
]);
$oauth_raw  = curl_exec($ch);
$oauth_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP : {$oauth_code}\n";
echo "Body : {$oauth_raw}\n\n";

if ($oauth_code !== 200) {
    if (strpos($oauth_raw, 'invalid_grant') !== false) {
        echo "CAUSE: invalid_grant\n";
        echo "The service account does not belong to project rap-lms-9d219.\n";
        echo "Re-download the key from the correct Firebase project.\n";
    } else {
        echo "CAUSE: JWT is malformed. The private key is still corrupted.\n";
        echo "ACTION: Go to Firebase Console -> Project Settings -> Service Accounts\n";
        echo "Click 'Generate new private key', download the JSON file,\n";
        echo "open it in a text editor, select ALL (Ctrl+A), copy and paste\n";
        echo "into Moodle admin settings. Save.\n";
    }
    die("</pre>");
}

$bearer = json_decode($oauth_raw, true)['access_token'];
echo "Bearer token obtained OK\n\n";

// ── 6. Send FCM push ──────────────────────────────────────────────
echo "STEP 6: Send FCM Push\n";
echo str_repeat('-', 50) . "\n";

$fcm_token = 'c8fElkCZTTmb9vXlDcwuI2:APA91bFwfPqsZBV52alNMuxajhcPEY-zQJ0HL7ZeOJmPbpA5VYA8Nx0AdbpNdnFJZXNoplcKeQ1LfmaEJyeZsOOZ3e2Ayr2wkUqqwvObeP41hnEQonz2HnE';
echo "Token : " . substr($fcm_token, 0, 40) . "...\n\n";

$payload = json_encode([
    'message' => [
        'token'        => $fcm_token,
        'notification' => [
            'title' => 'RAP LMS Test Push',
            'body'  => 'Working! ' . date('H:i:s'),
        ],
        'data' => [
            'notification_type' => 'new_course_assigned',
            'courseid'          => '1',
            'cmid'              => '0',
        ],
        'android' => [
            'priority'     => 'high',
            'notification' => ['sound' => 'default', 'click_action' => 'FLUTTER_NOTIFICATION_CLICK'],
        ],
        'apns' => [
            'headers' => ['apns-priority' => '10'],
            'payload' => ['aps' => ['sound' => 'default', 'badge' => 1]],
        ],
    ],
]);

$ch = curl_init("https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $bearer,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => $payload,
]);
$fcm_raw  = curl_exec($ch);
$fcm_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "FCM HTTP : {$fcm_code}\n";
echo "FCM Body : {$fcm_raw}\n\n";

if ($fcm_code === 200) {
    echo "=== SUCCESS - Push sent! Check device. ===\n";
} else {
    $status = json_decode($fcm_raw, true)['error']['status'] ?? 'UNKNOWN';
    echo "=== FAILED: {$status} ===\n";
    if (in_array($status, ['UNREGISTERED', 'NOT_FOUND'])) {
        echo "This FCM token is expired. Ask Flutter dev to re-login and get a fresh token.\n";
    }
}
echo "\n=== Done. DELETE this file when finished. ===\n";
echo '</pre>';
