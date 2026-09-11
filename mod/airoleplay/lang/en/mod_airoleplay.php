<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Core required strings (Moodle mandatory).
$string['pluginname']       = 'AI Roleplay';
$string['modulename']       = 'AI Roleplay';
$string['modulenameplural'] = 'AI Roleplays';
$string['modulename_help']  = 'AI Roleplay enables learners to practice professional communication through AI-powered conversational simulations with intelligent evaluation and coaching.';
$string['pluginadministration'] = 'AI Roleplay administration';

// Scenario settings.
$string['scenariotitle']         = 'Scenario title';
$string['scenariotitle_help']    = 'Enter a descriptive title for this roleplay scenario.';
$string['industry']              = 'Industry / Domain';
$string['industry_help']         = 'Select the industry context for this scenario.';
$string['difficulty']            = 'Difficulty level';
$string['difficulty_help']       = 'Sets AI persona behaviour.';
$string['scenario_type']         = 'Simulation type';
$string['scenario_type_help']    = 'Chat: text-only. Voice: audio conversation. Both: learner chooses.';
$string['learning_objective']    = 'Learning objective';
$string['learning_objective_help'] = 'Describe what learners should demonstrate. The AI will automatically infer evaluation criteria from this.';
$string['mandatory_phrases']     = 'Mandatory phrases (one per line)';
$string['mandatory_phrases_help'] = 'Learners must use these phrases or close equivalents.';
$string['forbidden_phrases']     = 'Forbidden phrases (one per line)';
$string['forbidden_phrases_help'] = 'Learners will be marked down for using these phrases.';
$string['system_instructions']   = 'Additional AI instructions';
$string['system_instructions_help'] = 'Optional extra instructions for the AI persona.';
$string['pass_score']            = 'Pass score (%)';
$string['max_attempts']          = 'Maximum attempts';
$string['max_duration']          = 'Maximum duration (seconds)';
$string['max_turns']             = 'Maximum conversation turns';
$string['persona']               = 'AI Persona';
$string['persona_help']          = 'Select the AI character the learner will converse with.';
$string['enable_voice']          = 'Enable voice mode';
$string['enable_avatar']         = 'Enable AI avatar';
$string['enable_transcript']     = 'Show live transcript';
$string['enable_coaching']       = 'Enable AI coaching feedback';
$string['enable_hints']          = 'Enable contextual hints';
$string['language']              = 'Conversation language';
$string['ai_provider']           = 'AI provider (override)';
$string['ai_model']              = 'AI model (override)';

// Difficulty levels.
$string['difficulty_easy']   = 'Easy';
$string['difficulty_medium'] = 'Medium';
$string['difficulty_hard']   = 'Hard';
$string['difficulty_expert'] = 'Expert';

// Industries.
$string['industry_sales']      = 'Sales';
$string['industry_support']    = 'Customer Support';
$string['industry_hr']         = 'Human Resources';
$string['industry_compliance'] = 'Compliance / POSH';
$string['industry_banking']    = 'Banking and Finance';
$string['industry_pharma']     = 'Pharmaceutical';
$string['industry_retail']     = 'Retail';
$string['industry_leadership'] = 'Leadership';
$string['industry_callcentre'] = 'Call Centre';
$string['industry_general']    = 'General';

// Conversation UI.
$string['endsession']          = 'End Session';
$string['startvoice']          = 'Start Voice';
$string['stopvoice']           = 'Stop Voice';
$string['typeyourmessage']     = 'Type your response...';
$string['aiconversationnote']  = 'AI conversations are recorded and evaluated for learning purposes.';
$string['sessionalreadycompleted'] = 'This session has already been completed.';
$string['invalidsessiontoken'] = 'Invalid session token. Please start a new attempt.';
$string['maxattemptsreached']  = 'You have reached the maximum number of attempts ({$a}).';
$string['scenarionotopenyet']  = 'This scenario is not open yet. It opens on {$a}.';
$string['scenarioclosed']      = 'This scenario closed on {$a}.';
$string['sessionnotcomplete']  = 'Session is not yet complete.';
$string['noaiprovideravailable'] = 'No AI provider is available. Please contact your administrator.';

// Results.
$string['yourscore']           = 'Your Score';
$string['passed']              = 'Passed';
$string['failed']              = 'Failed';
$string['needspractice']       = 'Needs Practice';
$string['overallscore']        = 'Overall Score';
$string['aisummary']           = 'AI Coaching Summary';
$string['strengths']           = 'Strengths';
$string['areasforimprovement'] = 'Areas for Improvement';
$string['competencyresults']   = 'Competency Results';
$string['idealresponses']      = 'Ideal Response Examples';
$string['recommendations']     = 'Learning Recommendations';
$string['evaluationpending']   = 'Your session is being evaluated by AI. This usually takes 1-2 minutes.';
$string['tryagain']            = 'Try Again';
$string['viewfeedback']        = 'View Feedback';
$string['noattempts']          = 'No attempts yet.';
$string['useroutline']         = '{$a->attempts} attempt(s), best score: {$a->best}/100';
$string['myroleplaystems']     = 'My Roleplay Sessions';

// Dashboard.
$string['dashboard']           = 'Dashboard';
$string['learnerdashboard']    = 'My Learning';
$string['managerdashboard']    = 'Team Performance';
$string['lddashboard']         = 'L&D Analytics';
$string['admindashboard']      = 'Platform Overview';
$string['sessionscompleted']   = 'Sessions Completed';
$string['passrate']            = 'Pass Rate';
$string['avgscore']            = 'Average Score';
$string['pendingtasks']        = 'Pending Tasks';
$string['teamsize']            = 'Team Size';
$string['overduetasks']        = 'Overdue Tasks';
$string['uniquelearners']      = 'Unique Learners';
$string['platformavg']         = 'Platform Average';
$string['scoreoertime']        = 'Score Over Time';
$string['competencyradar']     = 'Competency Profile';
$string['recentactivity']      = 'Recent Activity';
$string['teamleaderboard']     = 'Team Leaderboard';
$string['deptperformance']     = 'Department Performance';
$string['aicosttracking']      = 'AI Cost Tracking';

// Assignments.
$string['assignments']         = 'Assignments';
$string['newassignment']       = 'New Assignment';
$string['assigntarget']        = 'Assign to';
$string['duedate']             = 'Due date';
$string['opendate']            = 'Available from';
$string['ismandatory']         = 'Mandatory';
$string['isrecurring']         = 'Recurring';
$string['recurrencerule']      = 'Recurrence';
$string['compliancerequired']  = 'Compliance required';
$string['status_assigned']     = 'Assigned';
$string['status_inprogress']   = 'In Progress';
$string['status_completed']    = 'Completed';
$string['status_failed']       = 'Failed';
$string['status_expired']      = 'Expired';
$string['status_overdue']      = 'Overdue';
$string['assigntarget_user']   = 'Specific users';
$string['assigntarget_cohort'] = 'Cohort';
$string['assigntarget_department'] = 'Department';
$string['assigntarget_designation'] = 'Designation';
$string['assigntarget_region'] = 'Region';
$string['assigntarget_manager_report'] = 'Manager\'s team';

// Personas.
$string['personas']            = 'AI Personas';
$string['newpersona']          = 'New Persona';
$string['persona_name']        = 'Persona name';
$string['persona_type']        = 'Persona type';
$string['persona_tone']        = 'Default tone';
$string['persona_aggression']  = 'Aggression level';
$string['persona_empathy']     = 'Empathy level';
$string['persona_background']  = 'Background story';
$string['persona_goals']       = 'Goals and motivations';
$string['persona_objections']  = 'Common objections';
$string['persona_voice_id']    = 'TTS voice ID';

// Scheduled tasks.
$string['task_process_evaluation_queue'] = 'AI Roleplay: Process evaluation queue';
$string['task_send_reminders']           = 'AI Roleplay: Send assignment reminders';
$string['task_mark_overdue']             = 'AI Roleplay: Mark overdue assignments';
$string['task_generate_analytics']       = 'AI Roleplay: Generate analytics cache';
$string['task_cleanup_sessions']         = 'AI Roleplay: Cleanup abandoned sessions';
$string['task_sync_competencies']        = 'AI Roleplay: Sync Moodle competencies';
$string['task_recurring_assignments']    = 'AI Roleplay: Process recurring assignments';

// Admin settings.
$string['settings']                      = 'AI Roleplay Settings';
$string['settings_ai']                   = 'AI Configuration';
$string['settings_voice']                = 'Voice Engine';
$string['settings_profile_fields']       = 'Organisation Profile Fields';
$string['settings_profile_fields_desc']  = 'Map Moodle custom profile fields to organisational hierarchy concepts.';
$string['primary_provider']              = 'Primary AI provider';
$string['primary_provider_desc']         = 'The main AI provider for chat and evaluation.';
$string['fallback_providers']            = 'Fallback providers';
$string['fallback_providers_desc']       = 'Comma-separated list of fallback providers if primary fails.';
$string['ollama_url']                    = 'Ollama server URL';
$string['ollama_model']                  = 'Ollama model';
$string['openai_api_key']                = 'OpenAI API key';
$string['openai_model']                  = 'OpenAI model';
$string['anthropic_api_key']             = 'Anthropic API key';
$string['anthropic_model']               = 'Anthropic model';
$string['gemini_api_key']                = 'Google Gemini API key';
$string['azure_api_key']                 = 'Azure OpenAI API key';
$string['azure_endpoint']                = 'Azure OpenAI endpoint URL';
$string['azure_deployment']              = 'Azure deployment name';
$string['stt_provider']                  = 'Speech-to-Text provider';
$string['tts_provider']                  = 'Text-to-Speech provider';
$string['whisper_url']                   = 'Whisper server URL';
$string['piper_url']                     = 'Piper TTS server URL';
$string['azure_speech_key']              = 'Azure Speech API key';
$string['azure_speech_region']           = 'Azure Speech region';
$string['enable_cache']                  = 'Enable AI response cache';
$string['max_response_tokens']           = 'Max response tokens';
$string['max_history_turns']             = 'Max history turns in context';

// Calendar.
$string['calendarclose'] = '{$a} closes';

// Privacy.
$string['privacy:metadata:airoleplay_session']               = 'Information about AI roleplay attempts';
$string['privacy:metadata:airoleplay_session:userid']        = 'The user who completed this session';
$string['privacy:metadata:airoleplay_session:score_overall'] = 'The AI-generated overall score';
$string['privacy:metadata:airoleplay_session:ai_summary']    = 'AI-generated coaching summary';
$string['privacy:metadata:airoleplay_session:ai_strengths']  = 'AI-identified strengths';
$string['privacy:metadata:airoleplay_session:ai_improvements'] = 'AI-identified improvement areas';
$string['privacy:metadata:airoleplay_session:user_dept']     = 'User department at time of attempt';
$string['privacy:metadata:airoleplay_session:user_designation'] = 'User designation at time of attempt';
$string['privacy:metadata:airoleplay_session:timestarted']   = 'When the session started';
$string['privacy:metadata:airoleplay_session:timecompleted'] = 'When the session completed';
$string['privacy:metadata:airoleplay_message']               = 'Individual conversation messages';
$string['privacy:metadata:airoleplay_message:session']       = 'The session this message belongs to';
$string['privacy:metadata:airoleplay_message:role']          = 'Whether this is a user or AI message';
$string['privacy:metadata:airoleplay_message:content']       = 'The text content of the message';
$string['privacy:metadata:airoleplay_message:transcript']    = 'Voice transcript of audio message';
$string['privacy:metadata:airoleplay_message:sentiment']     = 'AI-detected sentiment of the message';
$string['privacy:metadata:airoleplay_message:timecreated']   = 'When the message was sent';
$string['privacy:metadata:airoleplay_assignment_user']       = 'Assignment tracking per user';
$string['privacy:metadata:airoleplay_assignment_user:userid'] = 'The assigned user';
$string['privacy:metadata:airoleplay_assignment_user:status'] = 'Assignment completion status';
$string['privacy:metadata:airoleplay_assignment_user:best_score'] = 'Best score achieved';
$string['privacy:metadata:airoleplay_assignment_user:timeassigned'] = 'When assigned';
$string['privacy:metadata:airoleplay_assignment_user:timecompleted'] = 'When completed';
$string['privacy:metadata:airoleplay_ai_usage']              = 'AI provider usage for cost tracking';
$string['privacy:metadata:airoleplay_ai_usage:userid']       = 'User who triggered the AI call';
$string['privacy:metadata:airoleplay_ai_usage:provider']     = 'AI provider used';
$string['privacy:metadata:airoleplay_ai_usage:total_tokens'] = 'Total tokens consumed';
$string['privacy:metadata:airoleplay_ai_usage:cost_usd']     = 'Estimated cost in USD';
$string['privacy:metadata:airoleplay_ai_usage:timecreated']  = 'When the AI call was made';
$string['privacy:metadata:external:openai']                  = 'Conversation content is sent to OpenAI when OpenAI is configured as the AI provider.';
$string['privacy:metadata:external:openai:message_content']  = 'Message content sent for AI processing';
$string['privacy:metadata:external:anthropic']               = 'Conversation content is sent to Anthropic when Claude is configured as the AI provider.';
$string['privacy:metadata:external:anthropic:message_content'] = 'Message content sent for AI processing';

// Errors.
$string['noapikey']         = 'No API key configured for {$a}. Please configure in Admin Settings.';
$string['ttsfailed']        = 'Text-to-speech synthesis failed. Please try again.';
$string['invalidaudiotype'] = 'Invalid audio file type.';
$string['audiofiletoolarge'] = 'Audio file is too large. Maximum 10MB.';
$string['aiproviderhttp']   = 'AI provider returned an error: {$a}';
