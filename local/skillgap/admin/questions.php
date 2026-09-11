<?php
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

require_login();
require_capability('local/skillgap:admin', context_system::instance());

$assessmentid = optional_param('assessmentid', 0, PARAM_INT);

// compid may come as "compid:subcompid" from the hierarchical dropdown (POST/GET).
// We parse it here so both the page and the form actions know the correct IDs.
$compid_raw   = optional_param('compid', '', PARAM_RAW);
$subcompid    = 0;
$compid       = 0;
if (strpos($compid_raw, ':') !== false) {
    [$compid, $subcompid] = array_map('intval', explode(':', $compid_raw, 2));
    // Redirect to subcomp_questions.php when a sub/subsub comp is chosen.
    redirect(new moodle_url('/local/skillgap/admin/subcomp_questions.php', [
        'subcompid'    => $subcompid,
        'compid'       => $compid,
        'assessmentid' => $assessmentid,
    ]));
} else {
    $compid = (int)$compid_raw;
}

$PAGE->set_url(new moodle_url('/local/skillgap/admin/questions.php',
    ['assessmentid' => $assessmentid, 'compid' => $compid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('questions', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('pluginname',  'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add(get_string('assessments', 'local_skillgap'), new moodle_url('/local/skillgap/admin/assessments.php'));
$PAGE->navbar->add(get_string('questions',   'local_skillgap'));

$PAGE->requires->js_call_amd('local_skillgap/skillgap', 'init');

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
if ($action && confirm_sesskey()) {

    if ($action === 'save_question') {
        $cid      = required_param('compid', PARAM_INT);
        $aid      = optional_param('assessmentid', 0, PARAM_INT);
        $existing = $DB->count_records('local_skillgap_question', ['compid' => $cid]);
        if ($existing >= 5) {
            redirect(new moodle_url('/local/skillgap/admin/questions.php',
                    ['assessmentid' => $aid, 'compid' => $cid]),
                get_string('error_max_questions', 'local_skillgap'), null,
                \core\output\notification::NOTIFY_ERROR);
        }
        $qtext   = required_param('questiontext', PARAM_TEXT);
        $opt_a   = required_param('option_a', PARAM_TEXT);
        $opt_b   = required_param('option_b', PARAM_TEXT);
        $opt_c   = required_param('option_c', PARAM_TEXT);
        $opt_d   = required_param('option_d', PARAM_TEXT);
        $correct = required_param('correct', PARAM_INT);
        $qid     = $DB->insert_record('local_skillgap_question',
            ['compid' => $cid, 'questiontext' => $qtext, 'source' => 'manual', 'timecreated' => time()]);
        foreach ([$opt_a, $opt_b, $opt_c, $opt_d] as $i => $opt) {
            $DB->insert_record('local_skillgap_qoption',
                ['questionid' => $qid, 'optiontext' => $opt, 'iscorrect' => (int)($i === $correct)]);
        }
        redirect(new moodle_url('/local/skillgap/admin/questions.php',
                ['assessmentid' => $aid, 'compid' => $cid]),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'delete_question') {
        $id  = required_param('id',           PARAM_INT);
        $cid = required_param('compid',       PARAM_INT);
        $aid = optional_param('assessmentid', 0, PARAM_INT);
        $DB->delete_records('local_skillgap_qoption',  ['questionid' => $id]);
        $DB->delete_records('local_skillgap_question', ['id'         => $id]);
        redirect(new moodle_url('/local/skillgap/admin/questions.php',
                ['assessmentid' => $aid, 'compid' => $cid]),
            get_string('delete_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'update_question') {
        $id     = required_param('id',           PARAM_INT);
        $cid    = required_param('compid',       PARAM_INT);
        $aid    = optional_param('assessmentid', 0, PARAM_INT);
        $qtext  = required_param('questiontext', PARAM_TEXT);
        $opt_a  = required_param('option_a',     PARAM_TEXT);
        $opt_b  = required_param('option_b',     PARAM_TEXT);
        $opt_c  = required_param('option_c',     PARAM_TEXT);
        $opt_d  = required_param('option_d',     PARAM_TEXT);
        $correct= required_param('correct',      PARAM_INT);

        // Update question text.
        $DB->update_record('local_skillgap_question',
            ['id' => $id, 'questiontext' => $qtext]);

        // Delete old options and re-insert.
        $DB->delete_records('local_skillgap_qoption', ['questionid' => $id]);
        foreach ([$opt_a, $opt_b, $opt_c, $opt_d] as $i => $opt) {
            $DB->insert_record('local_skillgap_qoption',
                ['questionid' => $id, 'optiontext' => $opt, 'iscorrect' => (int)($i === $correct)]);
        }
        redirect(new moodle_url('/local/skillgap/admin/questions.php',
                ['assessmentid' => $aid, 'compid' => $cid]),
            get_string('save_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'upload_csv') {
        $cid     = required_param('compid',       PARAM_INT);
        $aid     = optional_param('assessmentid', 0, PARAM_INT);
        $csvfile = $_FILES['csvfile'] ?? null;
        if ($csvfile && $csvfile['error'] === UPLOAD_ERR_OK) {
            $rows  = array_map('str_getcsv', file($csvfile['tmp_name']));
            $count = 0;
            array_shift($rows); // remove header row
            foreach ($rows as $row) {
                // Count ONLY main-level questions (subcompid IS NULL or 0) for the limit check.
                $existing = (int)$DB->count_records_select('local_skillgap_question',
                    'compid = :cid AND (subcompid IS NULL OR subcompid = 0)', ['cid' => $cid]);
                if ($existing >= 5) { break; }
                if (empty(trim($row[0] ?? ''))) { continue; }
                $qtext = clean_param(trim($row[0]), PARAM_TEXT);
                $qid   = $DB->insert_record('local_skillgap_question', [
                    'compid'       => $cid,
                    'subcompid'    => null,   // explicitly main-level
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
            redirect(new moodle_url('/local/skillgap/admin/questions.php',
                    ['assessmentid' => $aid, 'compid' => $cid]),
                get_string('csv_import_success', 'local_skillgap', $count), null,
                \core\output\notification::NOTIFY_SUCCESS);
        }
    }
}

$data     = \local_skillgap\local\helper::questions_data($compid, $assessmentid, $subcompid);
$renderer = $PAGE->get_renderer('local_skillgap');

echo $OUTPUT->header();
echo $renderer->render_questions($data);
echo $OUTPUT->footer();
