<?php
/**
 * mod_airoleplay admin settings.
 *
 * NOTE ON API KEYS:
 * AI provider credentials (OpenAI / Anthropic / Ollama keys and chat models)
 * are managed by the companion "local_airoleplay_ai" plugin and are read
 * automatically by this activity. They are intentionally NOT duplicated here,
 * to avoid having two places to configure the same key.
 *
 * This page only exposes settings that are specific to mod_airoleplay and do
 * not exist in the provider plugin — currently the natural-voice (TTS) options.
 * The TTS feature reuses the OpenAI key from the provider plugin.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // ----- Text-to-Speech (natural voice). ----------------------------
    $settings->add(new admin_setting_heading(
        'mod_airoleplay/tts_heading',
        get_string('settings_tts_heading', 'mod_airoleplay'),
        get_string('settings_tts_heading_desc', 'mod_airoleplay')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_airoleplay/enable_tts',
        get_string('settings_enable_tts', 'mod_airoleplay'),
        get_string('settings_enable_tts_desc', 'mod_airoleplay'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'mod_airoleplay/openai_tts_model',
        get_string('settings_tts_model', 'mod_airoleplay'),
        get_string('settings_tts_model_desc', 'mod_airoleplay'),
        'gpt-4o-mini-tts',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configselect(
        'mod_airoleplay/default_tts_voice',
        get_string('settings_tts_voice', 'mod_airoleplay'),
        get_string('settings_tts_voice_desc', 'mod_airoleplay'),
        'alloy',
        [
            'alloy'   => 'Alloy (neutral)',
            'echo'    => 'Echo (male)',
            'fable'   => 'Fable (warm British)',
            'onyx'    => 'Onyx (deep male)',
            'nova'    => 'Nova (friendly female)',
            'shimmer' => 'Shimmer (soft female)',
        ]
    ));
}
