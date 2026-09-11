<?php
// local/rapmobile/forumimage.php
//
// Resolves a core pluginfile.php URL (session-authenticated) into a
// token-authenticated webservice/pluginfile.php URL and redirects there.
// Used for mod_forum author profile images in the mobile app.

define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/webservice/lib.php');

$token  = required_param('token', PARAM_ALPHANUM);
$rawurl = required_param('url', PARAM_URL);

$webservicelib = new webservice();
$webservicelib->authenticate_user($token); // Throws webservice_access_exception if invalid.

// Already public (theme avatar) or already tokened — pass through unchanged.
if (strpos($rawurl, '/pluginfile.php/') === false
        || strpos($rawurl, '/webservice/pluginfile.php/') !== false) {
    redirect($rawurl);
}

$tokened = str_replace('/pluginfile.php/', '/webservice/pluginfile.php/', $rawurl);
$sep = (strpos($tokened, '?') !== false) ? '&' : '?';
redirect($tokened . $sep . 'token=' . $token);