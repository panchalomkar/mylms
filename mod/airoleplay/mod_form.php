<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Module form for AI Roleplay activity.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 */
class mod_airoleplay_mod_form extends moodleform_mod {

    /**
     * Defines forms elements.
     */
    public function definition(): void {
        global $CFG, $DB;

        $mform = $this->_form;

        // -------------------------------------------------------
        // General section.
        // -------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('scenariotitle', 'mod_airoleplay'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // -------------------------------------------------------
        // Scenario settings section.
        // -------------------------------------------------------
        $mform->addElement('header', 'header_scenario', get_string('pluginname', 'mod_airoleplay') . ' Settings');

        // Industry.
        $industries = [
            ''           => get_string('choosedots'),
            'sales'      => get_string('industry_sales',      'mod_airoleplay'),
            'support'    => get_string('industry_support',    'mod_airoleplay'),
            'hr'         => get_string('industry_hr',         'mod_airoleplay'),
            'compliance' => get_string('industry_compliance', 'mod_airoleplay'),
            'banking'    => get_string('industry_banking',    'mod_airoleplay'),
            'pharma'     => get_string('industry_pharma',     'mod_airoleplay'),
            'retail'     => get_string('industry_retail',     'mod_airoleplay'),
            'leadership' => get_string('industry_leadership', 'mod_airoleplay'),
            'callcentre' => get_string('industry_callcentre', 'mod_airoleplay'),
            'general'    => get_string('industry_general',    'mod_airoleplay'),
        ];
        $mform->addElement('select', 'industry', get_string('industry', 'mod_airoleplay'), $industries);
        $mform->setDefault('industry', 'general');

        // Difficulty.
        $difficulties = [
            'easy'   => get_string('difficulty_easy',   'mod_airoleplay'),
            'medium' => get_string('difficulty_medium', 'mod_airoleplay'),
            'hard'   => get_string('difficulty_hard',   'mod_airoleplay'),
            'expert' => get_string('difficulty_expert', 'mod_airoleplay'),
        ];
        $mform->addElement('select', 'difficulty', get_string('difficulty', 'mod_airoleplay'), $difficulties);
        $mform->setDefault('difficulty', 'medium');

        // Scenario type.
        $types = [
            'chat'  => 'Chat only (text)',
            'voice' => 'Voice only (audio)',
            'both'  => 'Both (learner chooses)',
        ];
        $mform->addElement('select', 'scenario_type', get_string('scenario_type', 'mod_airoleplay'), $types);
        $mform->setDefault('scenario_type', 'chat');

        // Language.
        $languages = [
            'en' => 'English', 'hi' => 'Hindi', 'ta' => 'Tamil',
            'te' => 'Telugu',  'kn' => 'Kannada', 'ml' => 'Malayalam',
            'mr' => 'Marathi', 'bn' => 'Bengali', 'gu' => 'Gujarati',
            'pa' => 'Punjabi', 'ar' => 'Arabic',  'fr' => 'French',
            'de' => 'German',  'es' => 'Spanish', 'ja' => 'Japanese',
            'zh' => 'Chinese (Simplified)', 'pt' => 'Portuguese', 'ru' => 'Russian',
        ];
        $mform->addElement('select', 'language', get_string('language', 'mod_airoleplay'), $languages);
        $mform->setDefault('language', 'en');

        // -------------------------------------------------------
        // AI configuration section.
        // -------------------------------------------------------
        $mform->addElement('header', 'header_ai', 'AI Configuration');

        $mform->addElement('textarea', 'learning_objective',
            get_string('learning_objective', 'mod_airoleplay'),
            ['rows' => 4, 'cols' => 64]);
        $mform->setType('learning_objective', PARAM_TEXT);

        $mform->addElement('static', 'ai_note', '',
            '<div style="background:#ede9fe;border-radius:6px;padding:10px 14px;font-size:13px;color:#5b21b6">' .
            '<strong>AI-First:</strong> The AI automatically infers competencies and evaluation criteria from your learning objective.' .
            '</div>');

        // Persona selector - safe DB query.
        $personas = [0 => 'Default AI persona'];
        try {
            $dbman = $DB->get_manager();
            if ($dbman->table_exists('airoleplay_persona')) {
                $persona_records = $DB->get_records_select(
                    'airoleplay_persona', '', [], 'name ASC', 'id, name, persona_type, industry'
                );
                foreach ($persona_records as $p) {
                    $label = $p->name . ' (' . $p->persona_type;
                    if (!empty($p->industry)) {
                        $label .= ' - ' . $p->industry;
                    }
                    $label .= ')';
                    $personas[$p->id] = $label;
                }
            }
        } catch (Exception $e) {
            // Table may not exist during install.
        }
        $mform->addElement('select', 'persona_id', get_string('persona', 'mod_airoleplay'), $personas);

        // Mandatory phrases.
        $mform->addElement('textarea', 'mandatory_phrases_raw',
            get_string('mandatory_phrases', 'mod_airoleplay'), ['rows' => 3, 'cols' => 64]);
        $mform->setType('mandatory_phrases_raw', PARAM_TEXT);

        // System instructions.
        $mform->addElement('textarea', 'system_instructions',
            get_string('system_instructions', 'mod_airoleplay'), ['rows' => 3, 'cols' => 64]);
        $mform->setType('system_instructions', PARAM_TEXT);

        // AI provider override.
        $providers = [
            '' => 'Use global default',
            'ollama'    => 'Ollama (Self-hosted)',
            'openai'    => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'gemini'    => 'Google Gemini',
            'azure'     => 'Azure OpenAI',
            'deepseek'  => 'DeepSeek',
        ];
        $mform->addElement('select', 'ai_provider', get_string('ai_provider', 'mod_airoleplay'), $providers);

        // -------------------------------------------------------
        // Attempt rules section.
        // -------------------------------------------------------
        $mform->addElement('header', 'header_attempts', 'Attempt Rules');

        $mform->addElement('text', 'pass_score', get_string('pass_score', 'mod_airoleplay'), ['size' => 5]);
        $mform->setType('pass_score', PARAM_FLOAT);
        $mform->setDefault('pass_score', 70);
        $mform->addRule('pass_score', null, 'required', null, 'client');

        $attempt_opts = [0 => 'Unlimited', 1 => '1', 2 => '2', 3 => '3',
                         4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9', 10 => '10'];
        $mform->addElement('select', 'max_attempts', get_string('max_attempts', 'mod_airoleplay'), $attempt_opts);
        $mform->setDefault('max_attempts', 3);

        $mform->addElement('text', 'max_duration', get_string('max_duration', 'mod_airoleplay'), ['size' => 8]);
        $mform->setType('max_duration', PARAM_INT);
        $mform->addElement('static', 'max_duration_note', '', 'Seconds. Leave blank for no time limit.');

        $mform->addElement('text', 'max_turns', get_string('max_turns', 'mod_airoleplay'), ['size' => 5]);
        $mform->setType('max_turns', PARAM_INT);
        $mform->addElement('static', 'max_turns_note', '', 'Leave blank for no turn limit.');

        // -------------------------------------------------------
        // Features section.
        // -------------------------------------------------------
        $mform->addElement('header', 'header_features', 'Features');

        $mform->addElement('advcheckbox', 'enable_voice',      get_string('enable_voice',      'mod_airoleplay'));
        $mform->addElement('advcheckbox', 'enable_transcript', get_string('enable_transcript', 'mod_airoleplay'));
        $mform->setDefault('enable_transcript', 1);
        $mform->addElement('advcheckbox', 'enable_coaching',   get_string('enable_coaching',   'mod_airoleplay'));
        $mform->setDefault('enable_coaching', 1);
        $mform->addElement('advcheckbox', 'enable_hints',      get_string('enable_hints',      'mod_airoleplay'));

        // -------------------------------------------------------
        // Availability section.
        // -------------------------------------------------------
        $mform->addElement('header', 'header_timing', 'Availability');

        $mform->addElement('date_time_selector', 'timeopen', 'Available from', ['optional' => true]);
        $mform->addElement('date_time_selector', 'timeclose', 'Available until', ['optional' => true]);

        // -------------------------------------------------------
        // Standard Moodle elements.
        // -------------------------------------------------------
        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Pre-process form data before display.
     *
     * @param array $default_values Default values
     */
   public function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        if (!empty($default_values['mandatory_phrases'])) {
            $phrases = json_decode($default_values['mandatory_phrases'], true);
            if (is_array($phrases)) {
                $default_values['mandatory_phrases_raw'] = implode("\n", $phrases);
            }
        }
        if (!empty($default_values['forbidden_phrases'])) {
            $phrases = json_decode($default_values['forbidden_phrases'], true);
            if (is_array($phrases)) {
                $default_values['forbidden_phrases_raw'] = implode("\n", $phrases);
            }
        }
    }

    /**
     * Validate form values.
     *
     * @param array $data Form data
     * @param array $files Files
     * @return array Validation errors
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (isset($data['pass_score'])) {
            if ($data['pass_score'] < 0 || $data['pass_score'] > 100) {
                $errors['pass_score'] = 'Pass score must be between 0 and 100.';
            }
        }

        if (!empty($data['max_duration']) && $data['max_duration'] < 30) {
            $errors['max_duration'] = 'Minimum duration is 30 seconds.';
        }

        if (!empty($data['timeopen']) && !empty($data['timeclose'])) {
            if ($data['timeclose'] <= $data['timeopen']) {
                $errors['timeclose'] = 'Close date must be after open date.';
            }
        }

        return $errors;
    }
}
