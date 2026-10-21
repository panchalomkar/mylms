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
 * "View Analysis" drill-down page: Organization -> Department -> Designation -> Learner.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_businessintelligence\local\drilldown;
use local_businessintelligence\local\filters;

require_login(null, false);
$context = context_system::instance();
require_capability('local/businessintelligence:view', $context);

$report = optional_param('report', 'learning', PARAM_ALPHA);
if (!in_array($report, drilldown::REPORTS, true)) {
    $report = 'learning';
}
$filters = filters::from_request();
$title = get_string('report_' . $report, 'local_businessintelligence');

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/businessintelligence/report.php', ['report' => $report]));
$PAGE->set_pagelayout('report');
$PAGE->set_title($title);
$PAGE->set_heading(get_string('pluginname', 'local_businessintelligence'));
$PAGE->add_body_class('lbi-page');
$PAGE->navbar->add(get_string('pluginname', 'local_businessintelligence'), new moodle_url('/local/businessintelligence/index.php'));
$PAGE->navbar->add($title);
$start = [
    'level' => optional_param('level', 'org', PARAM_ALPHA),
    'dept' => optional_param('dept', '', PARAM_TEXT),
    'desig' => optional_param('desig', '', PARAM_TEXT),
];
$output = $PAGE->get_renderer('core');

if ($report === 'learning') {
    // Learning Progress Analytics has its own page.
    $PAGE->add_body_class('lbi-page-learning');
    $PAGE->requires->js_call_amd('local_businessintelligence/learning', 'init', [$filters->to_array(), $start]);
    echo $OUTPUT->header();
    echo $output->render_from_template('local_businessintelligence/learning',
        (new \local_businessintelligence\output\learning_page($filters))->export_for_template($output));
    echo $OUTPUT->footer();
    die;
}

if ($report === 'learningpaths') {
    // Learning Path Analytics has its own page.
    $PAGE->add_body_class('lbi-page-learning');
    $PAGE->requires->js_call_amd('local_businessintelligence/lppage', 'init', [$filters->to_array(), $start]);
    echo $OUTPUT->header();
    echo $output->render_from_template('local_businessintelligence/lppage',
        (new \local_businessintelligence\output\lp_page($filters))->export_for_template($output));
    echo $OUTPUT->footer();
    die;
}

if (isset(\local_businessintelligence\local\analysis::PROVIDERS[$report])) {
    // Trainer, AI Role-Play, Learning & Performance Intelligence and Competency Assessment pages.
    $path = json_decode(optional_param('path', '[]', PARAM_RAW), true);
    $path = is_array($path) ? array_values(array_map(function($v) {
        return clean_param((string)$v, PARAM_TEXT);
    }, array_slice($path, 0, 5))) : [];
    $PAGE->add_body_class('lbi-page-learning');
    $PAGE->requires->js_call_amd('local_businessintelligence/analysis', 'init', [$report, $filters->to_array(), $path]);
    echo $OUTPUT->header();
    echo $output->render_from_template('local_businessintelligence/analysis',
        (new \local_businessintelligence\output\analysis_page($filters, $report))->export_for_template($output));
    echo $OUTPUT->footer();
    die;
}

if ($report === 'skillgap') {
    // Skill Gap Analytics has its own page.
    $PAGE->add_body_class('lbi-page-learning');
    $PAGE->requires->js_call_amd('local_businessintelligence/sgpage', 'init', [$filters->to_array(), $start]);
    echo $OUTPUT->header();
    echo $output->render_from_template('local_businessintelligence/sgpage',
        (new \local_businessintelligence\output\sg_page($filters))->export_for_template($output));
    echo $OUTPUT->footer();
    die;
}

if ($report === 'sessions') {
    // Training Sessions Analytics has its own page.
    $PAGE->add_body_class('lbi-page-learning');
    $PAGE->requires->js_call_amd('local_businessintelligence/sessionspage', 'init', [$filters->to_array(), $start]);
    echo $OUTPUT->header();
    echo $output->render_from_template('local_businessintelligence/sessionspage',
        (new \local_businessintelligence\output\sessions_page($filters))->export_for_template($output));
    echo $OUTPUT->footer();
    die;
}

$PAGE->requires->js_call_amd('local_businessintelligence/drilldown', 'init', [$report, $filters->to_array(), [
    'level' => optional_param('level', 'org', PARAM_ALPHA),
    'dept' => optional_param('dept', '', PARAM_TEXT),
    'desig' => optional_param('desig', '', PARAM_TEXT),
]]);

$output = $PAGE->get_renderer('core');
echo $OUTPUT->header();
echo $output->render_from_template('local_businessintelligence/drilldown',
    (new \local_businessintelligence\output\drilldown_page($filters, $report))->export_for_template($output));
echo $OUTPUT->footer();
