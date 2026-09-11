<?php
defined('MOODLE_INTERNAL') || die();

class local_langswitch_observer {

    /**
     * On logout — clear cookie and session lang.
     */
    public static function user_logout(\core\event\user_loggedout $event) {
        global $SESSION;

        // Clear cookie
        setcookie(
            'moodle_langswitch',
            '',
            [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
        unset($_COOKIE['moodle_langswitch']);

        // Reset session lang to English
        $SESSION->lang = 'en';
    }

    /**
     * On login — load user's saved language preference from DB.
     */
    public static function user_login(\core\event\user_loggedin $event) {
        global $SESSION;

        $userid  = $event->userid;
        $allowed = ['en', 'hi', 'mr', 'gu'];

        // Load from DB preference
        $lang = get_user_preferences('local_langswitch_lang', 'en', $userid);

        if (!in_array($lang, $allowed)) {
            $lang = 'en';
        }

        // Set session
        $SESSION->lang = $lang;

        // Sync cookie
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
    }
}