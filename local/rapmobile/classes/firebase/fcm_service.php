<?php
// This file is part of Moodle - http://moodle.org/
namespace local_rapmobile\firebase;

defined('MOODLE_INTERNAL') || die();

/**
 * Firebase Cloud Messaging HTTP v1 sender.
 *
 * Firebase project : rap-lms-9d219
 * Sender ID        : 1007856334196
 * Android package  : com.raptechsolutions.rapmobile
 *
 * Admin settings:
 *   Site admin → Plugins → Local plugins → RAP Mobile → Firebase Push Settings
 *   rapmobile_firebase_project_id  → rap-lms-9d219
 *   rapmobile_service_account_json → paste full JSON from Firebase Console
 *   rapmobile_fcm_timeout          → seconds (default 10)
 *
 * @package local_rapmobile
 */
class fcm_service {

    private const FCM_URL   = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';
    private const OAUTH_URL = 'https://oauth2.googleapis.com/token';
    private const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /** In-process OAuth2 token cache — one fetch per PHP request cycle */
    private static ?string $cached_token   = null;
    private static int     $token_valid_to = 0;

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Send push to ALL active devices of a user.
     *
     * notification_type must match the types used in get_rapmobile_notifications()
     * so the Flutter app can reuse its existing deep-link navigation code.
     *
     * @param int    $userid
     * @param string $title             Shown in device status bar.
     * @param string $body              HTML stripped automatically.
     * @param string $notification_type e.g. 'new_course_assigned', 'course_completion'
     * @param int    $courseid          0 if not applicable.
     * @param int    $cmid              0 if not applicable.
     */
    public static function send_to_user(
        int    $userid,
        string $title,
        string $body,
        string $notification_type = 'general',
        int    $courseid = 0,
        int    $cmid = 0
    ): void {
        global $DB;

        // Silently skip if Firebase not configured yet.
        $project = get_config('local_rapmobile', 'rapmobile_firebase_project_id');
        if (empty($project)) {
            return;
        }

        $devices = $DB->get_records(
            'rapmobile_devices',
            ['userid' => $userid, 'active' => 1],
            '',
            'id, fcm_token'
        );

        if (empty($devices)) {
            return; // User has no registered devices.
        }

        $plain = strip_tags(html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        foreach ($devices as $device) {
            self::send_one(
                (int)$device->id,
                $device->fcm_token,
                $title,
                $plain,
                $notification_type,
                $courseid,
                $cmid
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE
    // ─────────────────────────────────────────────────────────────────────────

    private static function send_one(
        int    $device_id,
        string $token,
        string $title,
        string $body,
        string $notification_type,
        int    $courseid,
        int    $cmid
    ): void {
        global $DB;

        $project = get_config('local_rapmobile', 'rapmobile_firebase_project_id');
        $bearer  = self::get_access_token();

        if (empty($bearer)) {
            return;
        }

        /*
         * PAYLOAD RULES (FCM HTTP v1):
         *
         * 1. notification block  → OS draws the banner + plays sound even when
         *    the app is fully closed. WITHOUT this block, nothing shows on screen.
         *
         * 2. data block          → Flutter reads this for deep-link navigation.
         *    Field names match get_rapmobile_notifications() exactly so the app
         *    reuses existing code with zero changes.
         *
         * 3. All data values MUST be strings (FCM v1 requirement).
         *    Never pass raw int/bool — always cast to (string).
         */
        $payload = [
            'message' => [
                'token'        => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => [
                    'notification_type' => (string)$notification_type,
                    'courseid'          => (string)$courseid,
                    'cmid'              => (string)$cmid,
                ],
                'android' => [
                    'priority'     => 'high',
                    'notification' => [
                        'sound'        => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ],
                'apns' => [
                    'headers' => ['apns-priority' => '10'],
                    'payload' => [
                        'aps' => [
                            'sound'             => 'default',
                            'badge'             => 1,
                            'content-available' => 1,
                        ],
                    ],
                ],
            ],
        ];

        $timeout = (int)(get_config('local_rapmobile', 'rapmobile_fcm_timeout') ?: 10);

        $ch = curl_init(sprintf(self::FCM_URL, $project));
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $bearer,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw      = curl_exec($ch);
        $httpcode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerr  = curl_error($ch);
        curl_close($ch);

        if ($curlerr) {
            debugging("[rapmobile FCM] cURL error device {$device_id}: {$curlerr}", DEBUG_DEVELOPER);
            return;
        }

        if ($httpcode === 200) {
            return; // Delivered successfully.
        }

        debugging("[rapmobile FCM] HTTP {$httpcode} device {$device_id}: {$raw}", DEBUG_DEVELOPER);

        // Dead-token cleanup — app uninstalled or token rotated.
        // This is the ONLY place we auto-deactivate on FCM error.
        if (self::is_dead_token($raw)) {
            $DB->execute(
                "UPDATE {rapmobile_devices} SET active=0, lastupdated=:now WHERE id=:id",
                ['now' => time(), 'id' => $device_id]
            );
        }
    }

    /** FCM v1 dead-token error: status UNREGISTERED or NOT_FOUND */
    private static function is_dead_token(string $raw): bool {
        $r = json_decode($raw, true);
        if (empty($r['error'])) {
            return false;
        }
        $dead = ['UNREGISTERED', 'NOT_FOUND'];
        if (in_array($r['error']['status'] ?? '', $dead, true)) {
            return true;
        }
        foreach ($r['error']['details'] ?? [] as $d) {
            if (in_array($d['errorCode'] ?? '', $dead, true)) {
                return true;
            }
        }
        return false;
    }

    /** Fetch OAuth2 Bearer token from Google, cached per PHP process. */
    private static function get_access_token(): string {
        $now = time();
        if (self::$cached_token !== null && self::$token_valid_to > $now + 60) {
            return self::$cached_token;
        }

        $json = get_config('local_rapmobile', 'rapmobile_service_account_json');
        if (empty($json)) {
            debugging('[rapmobile FCM] rapmobile_service_account_json not set in admin settings.', DEBUG_DEVELOPER);
            return '';
        }

        $creds = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('[rapmobile FCM] service_account_json is not valid JSON: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            return '';
        }
        if (empty($creds['client_email']) || empty($creds['private_key'])) {
            debugging('[rapmobile FCM] service_account_json missing client_email or private_key.', DEBUG_DEVELOPER);
            return '';
        }

        // ── FIX: private key stored via Moodle textarea may have literal \n
        // instead of real newline characters. Restore them before passing
        // to openssl — without this the key fails to load and JWT signing fails.
        $private_key = $creds['private_key'];
        if (strpos($private_key, "\\n") !== false && strpos($private_key, "\n") === false) {
            $private_key = str_replace('\\n', "\n", $private_key);
        }

        // ── Build RS256-signed JWT ──────────────────────────────────────────
        // aud MUST be the token endpoint URL (string), not a JSON array.
        // scope goes in claims, NOT in the header.
        $hdr    = self::b64u(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = self::b64u(json_encode([
            'iss'   => $creds['client_email'],
            'sub'   => $creds['client_email'],
            'aud'   => 'https://oauth2.googleapis.com/token',   // must be exact string
            'scope' => self::FCM_SCOPE,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $input = $hdr . '.' . $claims;

        $pkey = openssl_pkey_get_private($private_key);
        if ($pkey === false) {
            debugging(
                '[rapmobile FCM] openssl cannot load private key. '
                . 'openssl error: ' . openssl_error_string() . '. '
                . 'Check the service account JSON was not corrupted when pasted.',
                DEBUG_DEVELOPER
            );
            return '';
        }

        if (!openssl_sign($input, $sig, $pkey, OPENSSL_ALGO_SHA256)) {
            debugging('[rapmobile FCM] openssl_sign failed: ' . openssl_error_string(), DEBUG_DEVELOPER);
            return '';
        }

        $jwt = $input . '.' . self::b64u($sig);

        // ── Exchange JWT for OAuth2 Bearer token ───────────────────────────
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw     = curl_exec($ch);
        $code    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerr = curl_error($ch);
        curl_close($ch);

        if ($curlerr) {
            debugging("[rapmobile FCM] OAuth2 cURL error: {$curlerr}", DEBUG_DEVELOPER);
            return '';
        }

        if ($code !== 200) {
            debugging("[rapmobile FCM] OAuth2 failed HTTP {$code}: {$raw}", DEBUG_DEVELOPER);
            return '';
        }

        $data = json_decode($raw, true);
        if (empty($data['access_token'])) {
            debugging("[rapmobile FCM] OAuth2 response missing access_token: {$raw}", DEBUG_DEVELOPER);
            return '';
        }

        self::$cached_token   = $data['access_token'];
        self::$token_valid_to = $now + (int)($data['expires_in'] ?? 3600);

        return self::$cached_token;
    }

    private static function b64u(string $d): string {
        return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
    }
}
