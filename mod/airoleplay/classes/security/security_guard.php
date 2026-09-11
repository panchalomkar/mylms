<?php
/**
 * Security Guard - input validation and security checks.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_airoleplay\security;

defined('MOODLE_INTERNAL') || die();

class security_guard {

    /** Maximum characters per user message */
    const MAX_MESSAGE_LENGTH = 4000;

    /** Patterns that indicate prompt injection attempts */
    private const INJECTION_PATTERNS = [
        '/ignore (all )?(previous|above|prior) instructions/i',
        '/system prompt/i',
        '/you are now/i',
        '/forget (everything|all)/i',
        '/act as (a )?(?!the character|your character)/i',
        '/\[INST\]/i',
        '/<\|.*?\|>/i',
    ];

    /**
     * Sanitize and validate user input for roleplay messages.
     */
    public static function sanitize_user_input(string $input): string {
        // Strip HTML.
        $input = strip_tags($input);

        // Decode HTML entities.
        $input = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Trim and limit length.
        $input = mb_substr(trim($input), 0, self::MAX_MESSAGE_LENGTH);

        // Detect and neutralize prompt injection attempts.
        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                debugging("Possible prompt injection detected, sanitizing.", DEBUG_DEVELOPER);
                // Replace with neutral message rather than blocking - better UX.
                $input = preg_replace($pattern, '[message]', $input);
            }
        }

        return $input;
    }

    /**
     * Check if the scenario is open for attempts (time gates).
     */
    public static function check_time_limits(object $scenario): void {
        $now = time();

        if ($scenario->timeopen && $now < $scenario->timeopen) {
            throw new \moodle_exception('scenarionotopenyet', 'mod_airoleplay', '',
                userdate($scenario->timeopen));
        }

        if ($scenario->timeclose && $now > $scenario->timeclose) {
            throw new \moodle_exception('scenarioclosed', 'mod_airoleplay', '',
                userdate($scenario->timeclose));
        }
    }

    /**
     * Check if user has remaining attempts.
     */
    public static function check_attempt_limits(object $scenario, int $userid): void {
        global $DB;

        if (!$scenario->max_attempts) {
            return; // Unlimited attempts.
        }

        $used = $DB->count_records('airoleplay_session', [
            'airoleplay' => $scenario->id,
            'userid'     => $userid,
        ]);

        if ($used >= $scenario->max_attempts) {
            throw new \moodle_exception('maxattemptsreached', 'mod_airoleplay', '',
                $scenario->max_attempts);
        }
    }

    /**
     * Validate audio file upload for STT processing.
     */
    public static function validate_audio_upload(array $file): void {
        $allowed_types = [
            'audio/webm', 'audio/webm;codecs=opus',
            'audio/ogg',  'audio/ogg;codecs=opus',
            'audio/wav',  'audio/wave',
            'audio/mp4',  'audio/mpeg',
        ];

        $max_size_bytes = 10 * 1024 * 1024; // 10MB.

        if (!in_array($file['type'] ?? '', $allowed_types, true)) {
            throw new \moodle_exception('invalidaudiotype', 'mod_airoleplay');
        }

        if (($file['size'] ?? 0) > $max_size_bytes) {
            throw new \moodle_exception('audiofiletoolarge', 'mod_airoleplay');
        }
    }
}
