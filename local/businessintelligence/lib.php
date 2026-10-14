<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library callbacks.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the dashboard to the flat/custom navigation for users who can view it.
 *
 * @param global_navigation $navigation
 */
function local_businessintelligence_extend_navigation(global_navigation $navigation) {
    if (!isloggedin() || isguestuser()) {
        return;
    }
    if (!has_capability('local/businessintelligence:view', context_system::instance())) {
        return;
    }
    $node = $navigation->add(
        get_string('pluginname', 'local_businessintelligence'),
        new moodle_url('/local/businessintelligence/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_businessintelligence',
        new pix_icon('i/report', '')
    );
    $node->showinflatnavigation = true;
}
