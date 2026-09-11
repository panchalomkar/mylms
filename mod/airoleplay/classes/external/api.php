<?php
/**
 * Session API - external web service functions.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_airoleplay\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use mod_airoleplay\session\session_manager;
use mod_airoleplay\security\security_guard;

defined('MOODLE_INTERNAL') || die();

// ====================================================================
// SESSION API
// ====================================================================

// ====================================================================
// CHAT API
// ====================================================================
