<?php
/**
 * Debug page — shows raw assess_comp records for an assessment.
 * Used to verify that comp_type and subcompid are stored correctly.
 * Access: /local/skillgap/admin/debug_assessment.php?id=X
 * Remove this file from production after debugging.
 */
require_once(dirname(__DIR__, 3) . '/config.php');
require_login();
require_capability('local/skillgap:admin', context_system::instance());

$aid = required_param('id', PARAM_INT);
$PAGE->set_url(new moodle_url('/local/skillgap/admin/debug_assessment.php', ['id' => $aid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Debug Assessment #' . $aid);
echo $OUTPUT->header();

$a = $DB->get_record('local_skillgap_assessment', ['id' => $aid]);
if (!$a) { echo '<p class="alert alert-danger">Assessment not found.</p>'; }
else {
    echo '<h4>Assessment: ' . s($a->title) . ' (ID=' . $aid . ', status=' . $a->status . ')</h4>';
    $acs = $DB->get_records('local_skillgap_assess_comp', ['assessmentid' => $aid]);
    echo '<p><strong>' . count($acs) . ' competency entries:</strong></p>';
    echo '<table class="generaltable"><thead><tr><th>ID</th><th>compid</th><th>comp_type</th><th>subcompid</th><th>Comp Name</th><th>Q Count</th></tr></thead><tbody>';
    foreach ($acs as $ac) {
        $comp_type = $ac->comp_type ?? 'NULL';
        $subcompid = $ac->subcompid ?? 'NULL';
        if ($comp_type === 'main' || !$ac->subcompid) {
            $name = $DB->get_field('local_skillgap_comp', 'name', ['id' => $ac->compid]) ?: '?';
            $qc   = $DB->count_records_select('local_skillgap_question',
                'compid = :cid AND (subcompid IS NULL OR subcompid = 0)', ['cid' => $ac->compid]);
        } else {
            $name = $DB->get_field('local_skillgap_subcomp', 'name', ['id' => $ac->subcompid]) ?: '?';
            $qc   = $DB->count_records('local_skillgap_question',
                ['compid' => $ac->compid, 'subcompid' => $ac->subcompid]);
        }
        echo "<tr><td>{$ac->id}</td><td>{$ac->compid}</td><td>{$comp_type}</td><td>{$subcompid}</td><td>" . s($name) . "</td><td>{$qc}</td></tr>";
    }
    echo '</tbody></table>';
    echo '<p class="text-muted small">Expected: each row should have comp_type=main/sub/subsub and correct Q Count >= 5 for published assessments.</p>';
}
echo $OUTPUT->footer();
