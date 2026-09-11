<?php
/**
 * Scheduled tasks for mod_airoleplay.
 * 
 * NOTE: Individual task classes are defined in separate files in this directory
 * following Moodle's autoloader conventions. This file is kept for compatibility
 * but contains no class definitions to avoid "Cannot redeclare class" errors.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Task classes are in individual files:
// - process_ai_evaluation_queue.php
// - evaluate_session_adhoc.php
// - send_assignment_reminders.php
// - mark_overdue_assignments.php
// - cleanup_abandoned_sessions.php
// - generate_analytics_cache.php
// - process_recurring_assignments.php
// - sync_moodle_competencies.php
// - infer_competencies_adhoc.php
