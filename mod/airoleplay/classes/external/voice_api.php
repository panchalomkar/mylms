<?php
namespace mod_airoleplay\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;

defined('MOODLE_INTERNAL') || die();

class voice_api extends external_api {

    public static function transcribe_audio_parameters(): external_function_parameters {
        return new external_function_parameters([
            'session_id'    => new external_value(PARAM_INT,  'Session ID'),
            'session_token' => new external_value(PARAM_TEXT, 'Session token'),
            'audio_data'    => new external_value(PARAM_TEXT, 'Base64 encoded audio'),
            'audio_format'  => new external_value(PARAM_TEXT, 'Audio MIME type', VALUE_DEFAULT, 'audio/webm'),
            'language'      => new external_value(PARAM_TEXT, 'Language code', VALUE_DEFAULT, 'en'),
        ]);
    }

    public static function transcribe_audio(int $session_id, string $session_token, string $audio_data, string $audio_format = 'audio/webm', string $language = 'en'): array {
        global $DB, $USER;
        self::validate_parameters(self::transcribe_audio_parameters(),
            compact('session_id', 'session_token', 'audio_data', 'audio_format', 'language'));

        $session = $DB->get_record('airoleplay_session', ['id' => $session_id, 'userid' => $USER->id], '*', MUST_EXIST);
        if (!hash_equals($session->session_token, $session_token)) {
            throw new \moodle_exception('invalidsessiontoken', 'mod_airoleplay');
        }

        try {
            $voice_engine = new \local_airoleplay_ai\voice\voice_engine();
            $audio_bytes  = base64_decode($audio_data);
            $result       = $voice_engine->transcribe($audio_bytes, $language, $audio_format);
            return ['text' => $result['text'] ?? '', 'language' => $result['language'] ?? $language, 'confidence' => $result['confidence'] ?? 0.0];
        } catch (\Throwable $e) {
            throw new \moodle_exception('ttsfailed', 'mod_airoleplay');
        }
    }

    public static function transcribe_audio_returns(): external_single_structure {
        return new external_single_structure([
            'text'       => new external_value(PARAM_TEXT,  'Transcribed text'),
            'language'   => new external_value(PARAM_TEXT,  'Detected language'),
            'confidence' => new external_value(PARAM_FLOAT, 'Confidence score', VALUE_OPTIONAL),
        ]);
    }

    public static function synthesize_speech_parameters(): external_function_parameters {
        return new external_function_parameters([
            'session_id' => new external_value(PARAM_INT,  'Session ID'),
            'text'       => new external_value(PARAM_TEXT, 'Text to synthesize'),
            'language'   => new external_value(PARAM_TEXT, 'Language code', VALUE_DEFAULT, 'en'),
            'voice_id'   => new external_value(PARAM_TEXT, 'Voice ID', VALUE_DEFAULT, ''),
        ]);
    }

    public static function synthesize_speech(int $session_id, string $text, string $language = 'en', string $voice_id = ''): array {
        global $USER;
        self::validate_parameters(self::synthesize_speech_parameters(),
            compact('session_id', 'text', 'language', 'voice_id'));
        require_login();
        try {
            $voice_engine = new \local_airoleplay_ai\voice\voice_engine();
            $result = $voice_engine->synthesize($text, $language, $voice_id ?: null);
            return ['audio_base64' => base64_encode($result['audio_data']), 'format' => $result['format']];
        } catch (\Throwable $e) {
            throw new \moodle_exception('ttsfailed', 'mod_airoleplay');
        }
    }

    public static function synthesize_speech_returns(): external_single_structure {
        return new external_single_structure([
            'audio_base64' => new external_value(PARAM_TEXT, 'Base64 encoded audio'),
            'format'       => new external_value(PARAM_TEXT, 'Audio MIME type'),
        ]);
    }
}
