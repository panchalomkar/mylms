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
 * Executive Business Intelligence dashboard.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_businessintelligence\local\filters;

require_login(null, false);
$context = context_system::instance();
require_capability('local/businessintelligence:view', $context);

$filters = filters::from_request();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/businessintelligence/index.php'));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('dashboardtitle', 'local_businessintelligence'));
$PAGE->set_heading(get_string('pluginname', 'local_businessintelligence'));
$PAGE->add_body_class('lbi-page');
$PAGE->requires->js_call_amd('local_businessintelligence/dashboard', 'init', [$filters->to_array()]);

$output = $PAGE->get_renderer('core');
echo $OUTPUT->header();
echo $output->render_from_template('local_businessintelligence/dashboard',
    (new \local_businessintelligence\output\dashboard($filters))->export_for_template($output));
echo $OUTPUT->footer();
