<?php
require_once('../../config.php');
require_login();

header('Content-Type: application/json');

// ✅ Accept from JS
$cmid = required_param('courseid', PARAM_INT);

try {
    global $DB, $CFG;

    // Get course module
    $cm = get_coursemodule_from_id('goone', $cmid, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    require_capability('mod/goone:view', $context);

    // Get Go1 activity record
    $goone = $DB->get_record('goone', ['id' => $cm->instance], '*', MUST_EXIST);

    if (empty($goone->loid) || empty($goone->token)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'GO1 LOID or Token not configured'
        ]);
        exit;
    }

    $loid  = trim($goone->loid);
    $token = trim($goone->token);
    $sitehost = $CFG->wwwroot;

    // ✅ Correct Go1 wrapper URL for local & prod
    $wrapper = "https://rap.mygo1.com";

    // ✅ Convert Moodle URL to UTM
    $utm = urlencode($sitehost);

    // ✅ One-time token handshake (avoid login screen)
    @file_get_contents("https://api.mygo1.com/v3/one-time-token/{$token}");

    // ✅ Build Direct Launch URL (No Moodle wrapper)
    $launchurl = "{$wrapper}/play/{$loid}"
        . "?oneTimeToken={$token}"
        . "&recommendations=0"
        . "&hideReenrolOption=1"
        . "&utm_source={$utm}"
        . "&utm_medium=moodle"
        . "&wrapperOrigin={$wrapper}"
        . "&top_lms_host={$sitehost}";

    echo json_encode([
        'status'     => 'success',
        'name'       => format_string($goone->name),
        'launchurl'  => $launchurl,
        'loid'       => $loid,
        'token'      => $token
    ]);

    exit;

} catch (Exception $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
