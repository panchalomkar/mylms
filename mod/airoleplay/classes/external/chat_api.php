<?php
// This file is part of mod_airoleplay — delegates to self-contained externallib (no local_airoleplay_ai).

namespace mod_airoleplay\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/airoleplay/externallib.php');

/**
 * Legacy/external alias — use mod_airoleplay_external directly.
 */
class chat_api extends \mod_airoleplay_external {
}
