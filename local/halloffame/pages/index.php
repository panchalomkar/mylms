<?php
// local/halloffame/pages/index.php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

use local_halloffame\manager;
use local_halloffame\output\index_page;

require_login();
$context = context_system::instance();
require_capability('local/halloffame:view', $context);

$tab = optional_param('tab', 'awards', PARAM_ALPHA);
if (!in_array($tab, ['awards', 'achievements'], true)) {
    $tab = 'awards';
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/halloffame/pages/index.php', ['tab' => $tab]));
$PAGE->set_title(get_string('halloffame', 'local_halloffame'));
$PAGE->set_heading(get_string('halloffame', 'local_halloffame'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/halloffame/styles.css'));
$PAGE->requires->js_call_amd('local_halloffame/main', 'init', [['tab' => $tab]]);

$PAGE->navbar->add(
    get_string('halloffame', 'local_halloffame'),
    new moodle_url('/local/halloffame/pages/index.php')
);

echo $OUTPUT->header();

$page = new index_page($tab);
/** @var \local_halloffame\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_halloffame');
echo $renderer->render_from_template('local_halloffame/index', $page->export_for_template($OUTPUT));

echo $OUTPUT->footer();
