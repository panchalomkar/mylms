<?php
/**
 * Manage questions for sub-competencies and sub-sub-competencies.
 * Route: /local/skillgap/admin/subcomp_questions.php?subcompid=X&compid=Y
 */
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

require_login();
require_capability('local/skillgap:admin', context_system::instance());

$subcompid    = required_param('subcompid',    PARAM_INT);
$compid       = required_param('compid',       PARAM_INT);
$assessmentid = optional_param('assessmentid', 0, PARAM_INT);
$deptid       = optional_param('deptid',       0, PARAM_INT);
$desigid      = optional_param('desigid',      0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/skillgap/admin/subcomp_questions.php',
    ['subcompid' => $subcompid, 'compid' => $compid, 'assessmentid' => $assessmentid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('questions', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('pluginname',    'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add(get_string('competencies',  'local_skillgap'), new moodle_url('/local/skillgap/admin/competencies.php',
    ['deptid' => $deptid, 'desigid' => $desigid]));
$PAGE->navbar->add(get_string('questions',     'local_skillgap'));
$PAGE->requires->js_call_amd('local_skillgap/skillgap', 'init');

// Load sub-comp record.
$subcomp = $DB->get_record('local_skillgap_subcomp', ['id' => $subcompid], '*', MUST_EXIST);
$comp    = $DB->get_record('local_skillgap_comp',    ['id' => $compid],    '*', MUST_EXIST);
$is_subsub = !empty($subcomp->parentsubid);
$parent_name = '';
if ($is_subsub) {
    $parent = $DB->get_record('local_skillgap_subcomp', ['id' => $subcomp->parentsubid], 'name');
    $parent_name = $parent ? $parent->name : '';
}

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
if ($action && confirm_sesskey()) {

    $back_url = new moodle_url('/local/skillgap/admin/subcomp_questions.php',
        ['subcompid' => $subcompid, 'compid' => $compid,
         'assessmentid' => $assessmentid, 'deptid' => $deptid, 'desigid' => $desigid]);

    if ($action === 'save_question') {
        $existing = $DB->count_records('local_skillgap_question',
            ['compid' => $compid, 'subcompid' => $subcompid]);
        if ($existing >= 5) {
            redirect($back_url, get_string('error_max_questions', 'local_skillgap'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
        $qtext   = required_param('questiontext', PARAM_TEXT);
        $opt_a   = required_param('option_a',     PARAM_TEXT);
        $opt_b   = required_param('option_b',     PARAM_TEXT);
        $opt_c   = required_param('option_c',     PARAM_TEXT);
        $opt_d   = required_param('option_d',     PARAM_TEXT);
        $correct = required_param('correct',      PARAM_INT);
        $qid = $DB->insert_record('local_skillgap_question', [
            'compid'       => $compid,
            'subcompid'    => $subcompid,
            'questiontext' => $qtext,
            'source'       => 'manual',
            'timecreated'  => time(),
        ]);
        foreach ([$opt_a, $opt_b, $opt_c, $opt_d] as $i => $opt) {
            $DB->insert_record('local_skillgap_qoption',
                ['questionid' => $qid, 'optiontext' => $opt, 'iscorrect' => (int)($i === $correct)]);
        }
        redirect($back_url, get_string('save_success', 'local_skillgap'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'update_question') {
        $id     = required_param('id',           PARAM_INT);
        $qtext  = required_param('questiontext', PARAM_TEXT);
        $opt_a  = required_param('option_a',     PARAM_TEXT);
        $opt_b  = required_param('option_b',     PARAM_TEXT);
        $opt_c  = required_param('option_c',     PARAM_TEXT);
        $opt_d  = required_param('option_d',     PARAM_TEXT);
        $correct= required_param('correct',      PARAM_INT);
        $DB->update_record('local_skillgap_question', ['id' => $id, 'questiontext' => $qtext]);
        $DB->delete_records('local_skillgap_qoption', ['questionid' => $id]);
        foreach ([$opt_a, $opt_b, $opt_c, $opt_d] as $i => $opt) {
            $DB->insert_record('local_skillgap_qoption',
                ['questionid' => $id, 'optiontext' => $opt, 'iscorrect' => (int)($i === $correct)]);
        }
        redirect($back_url, get_string('save_success', 'local_skillgap'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'delete_question') {
        $id = required_param('id', PARAM_INT);
        $DB->delete_records('local_skillgap_qoption',  ['questionid' => $id]);
        $DB->delete_records('local_skillgap_question', ['id'         => $id]);
        redirect($back_url, get_string('delete_success', 'local_skillgap'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'upload_csv') {
        $csvfile = $_FILES['csvfile'] ?? null;
        if ($csvfile && $csvfile['error'] === UPLOAD_ERR_OK) {
            $rows  = array_map('str_getcsv', file($csvfile['tmp_name']));
            $count = 0;
            array_shift($rows); // remove header
            foreach ($rows as $row) {
                if ($DB->count_records('local_skillgap_question',
                    ['compid' => $compid, 'subcompid' => $subcompid]) >= 5) { break; }
                if (empty(trim($row[0] ?? ''))) { continue; }
                $qtext = clean_param(trim($row[0]), PARAM_TEXT);
                $qid   = $DB->insert_record('local_skillgap_question', [
                    'compid'       => $compid,
                    'subcompid'    => $subcompid,
                    'questiontext' => $qtext,
                    'source'       => 'csv',
                    'timecreated'  => time(),
                ]);
                for ($i = 1; $i <= 4; $i++) {
                    $opt = isset($row[$i]) ? clean_param(trim($row[$i]), PARAM_TEXT) : 'Option ' . $i;
                    $DB->insert_record('local_skillgap_qoption',
                        ['questionid' => $qid, 'optiontext' => $opt, 'iscorrect' => ($i === 1 ? 1 : 0)]);
                }
                $count++;
            }
            redirect($back_url, get_string('csv_import_success', 'local_skillgap', $count), null,
                \core\output\notification::NOTIFY_SUCCESS);
        }
    }
}

// Build question list.
$questions = $DB->get_records('local_skillgap_question',
    ['compid' => $compid, 'subcompid' => $subcompid], 'timecreated ASC');
$q_count   = count($questions);
$q_rows    = [];
$num = 1;
foreach ($questions as $q) {
    $options  = $DB->get_records('local_skillgap_qoption', ['questionid' => $q->id]);
    $opt_rows = [];
    foreach ($options as $o) {
        $opt_rows[] = ['id' => $o->id, 'text' => $o->optiontext, 'iscorrect' => (bool)$o->iscorrect];
    }
    $q_rows[] = [
        'id'           => $q->id,
        'num'          => $num++,
        'questiontext' => $q->questiontext,
        'source'       => strtoupper($q->source),
        'source_badge' => $q->source === 'ai' ? 'info' : ($q->source === 'csv' ? 'secondary' : 'primary'),
        'options'      => $opt_rows,
        'opt_a'        => $opt_rows[0]['text'] ?? '',
        'opt_b'        => $opt_rows[1]['text'] ?? '',
        'opt_c'        => $opt_rows[2]['text'] ?? '',
        'opt_d'        => $opt_rows[3]['text'] ?? '',
        'correct_0'    => !empty($opt_rows[0]['iscorrect']),
        'correct_1'    => !empty($opt_rows[1]['iscorrect']),
        'correct_2'    => !empty($opt_rows[2]['iscorrect']),
        'correct_3'    => !empty($opt_rows[3]['iscorrect']),
    ];
}

$data = [
    'subcompid'       => $subcompid,
    'compid'          => $compid,
    'assessmentid'    => $assessmentid,
    'deptid'          => $deptid,
    'desigid'         => $desigid,
    'subcomp_name'    => $subcomp->name,
    'comp_name'       => $comp->name,
    'is_subsub'       => $is_subsub,
    'parent_name'     => $parent_name,
    'q_count'         => $q_count,
    'q_pct'           => intval($q_count / 5 * 100),
    'has_5_questions' => $q_count >= 5,
    'can_add_more'    => $q_count < 5,
    'remaining'       => 5 - $q_count,
    'question_rows'   => $q_rows,
    'wwwroot'         => $CFG->wwwroot,
    'sesskey'         => sesskey(),
];

$renderer = $PAGE->get_renderer('local_skillgap');
echo $OUTPUT->header();
echo $renderer->render_from_template('local_skillgap/subcomp_questions', $data);
echo $OUTPUT->footer();
