<?php
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

require_login();
require_capability('local/skillgap:admin', context_system::instance());
global $PAGE, $SESSION, $DB, $CFG,$USER;
$PAGE->set_url(new moodle_url('/local/skillgap/admin/assessments.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('assessments', 'local_skillgap'));
$PAGE->set_heading(get_string('pluginname', 'local_skillgap'));
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('pluginname', 'local_skillgap'), new moodle_url('/local/skillgap/admin/index.php'));
$PAGE->navbar->add(get_string('assessments', 'local_skillgap'));

$PAGE->requires->js_call_amd('local_skillgap/skillgap', 'init');

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
if ($action && confirm_sesskey()) {

    if ($action === 'save') {
        $deptid        = required_param('deptid',    PARAM_INT);
        $desigid       = required_param('desigid',   PARAM_INT);
        $title         = required_param('title',     PARAM_TEXT);
        $frequency     = required_param('frequency', PARAM_ALPHANUMEXT);
        $startdate_str = required_param('startdate', PARAM_TEXT);
        $enddate_str   = required_param('enddate',   PARAM_TEXT);
        $startdate     = strtotime($startdate_str) ?: time();
        $enddate       = strtotime($enddate_str)   ?: time() + DAYSECS * 90;
        $compids_raw   = optional_param('compids', '', PARAM_RAW);
        // Sanitise: allow only alphanumeric, comma, dot. Strip everything else.
        $compids_raw   = preg_replace('/[^a-zA-Z0-9.,]/', '', $compids_raw);

        // Validate
        $errors = \local_skillgap\local\data_validator::validate_assessment([
            'deptid'  => $deptid,
            'desigid' => $desigid,
            'compids' => $compids_raw,
        ]);
        if (!empty($errors)) {
            redirect(new moodle_url('/local/skillgap/admin/assessments.php'),
                implode(' | ', $errors), null, \core\output\notification::NOTIFY_ERROR);
        }

        $comp_arr = array_filter(array_map('intval', explode(',', $compids_raw)));

        // Parse hierarchical format: "main:compid", "sub:compid:subcompid", "subsub:compid:subcompid"
        // Also support legacy plain integer format.
        $comp_entries = [];
        foreach (explode(',', $compids_raw) as $token) {
            $token = trim($token);
            if (empty($token)) { continue; }
            // Format: "main.compid" or "sub.compid.subcompid" or "subsub.compid.subcompid"
            // Also supports legacy plain integer.
            $parts = explode('.', $token);
            if (count($parts) === 1 && is_numeric($parts[0])) {
                // Legacy plain integer.
                $comp_entries[] = ['compid' => (int)$parts[0], 'comp_type' => 'main', 'subcompid' => null];
            } elseif (count($parts) >= 2) {
                $type        = in_array($parts[0], ['main', 'sub', 'subsub']) ? $parts[0] : 'main';
                $compid_v    = (int)($parts[1] ?? 0);
                $subcompid_v = isset($parts[2]) && $parts[2] ? (int)$parts[2] : 0;
                if ($compid_v > 0) {
                    $comp_entries[] = ['compid' => $compid_v, 'comp_type' => $type, 'subcompid' => $subcompid_v];
                }
            }
        }
        // Fallback to legacy comp_arr if new format yielded nothing.
        // Fallback to legacy comp_arr if new format yielded nothing.
if (empty($comp_entries) && !empty($comp_arr)) {
    foreach ($comp_arr as $cid) {
       $comp_entries[] = ['compid' => $cid, 'comp_type' => 'main', 'subcompid' => 0];
    }
}

// Deduplicate entries by (compid, comp_type, subcompid) to prevent DB unique-key violations
// when the same compid appears as both a parent and child selection.
$seen_keys      = [];
$unique_entries = [];
foreach ($comp_entries as $entry) {
    $key = $entry['compid'] . '|' . $entry['comp_type'] . '|' . ($entry['subcompid'] ?? '');
    if (!isset($seen_keys[$key])) {
        $seen_keys[$key]  = true;
        $unique_entries[] = $entry;
    }
}
$comp_entries = $unique_entries;

$aid = $DB->insert_record('local_skillgap_assessment', [
            'deptid'       => $deptid,
            'desigid'      => $desigid,
            'title'        => $title,
            'frequency'    => $frequency,
            'startdate'    => $startdate,
            'enddate'      => $enddate,
            'status'       => 'draft',
            'timecreated'  => time(),
            'timemodified' => time(),
            'usermodified' => $USER->id,
        ]);
        foreach ($comp_entries as $entry) {
            // Build insert record — comp_type and subcompid added in v4.0 upgrade.
            // Check column existence defensively so older DB schemas don't fail silently.
            static $has_comp_type = null;
            if ($has_comp_type === null) {
                $dbman = $DB->get_manager();
                $t     = new xmldb_table('local_skillgap_assess_comp');
                $f     = new xmldb_field('comp_type');
                $has_comp_type = $dbman->field_exists($t, $f);
            }
            $rec = ['assessmentid' => $aid, 'compid' => $entry['compid']];
            if ($has_comp_type) {
                $rec['comp_type'] = $entry['comp_type'];
                $rec['subcompid'] = $entry['subcompid'] ?? 0;
            }
            $DB->insert_record('local_skillgap_assess_comp', $rec);
        }
        redirect(new moodle_url('/local/skillgap/admin/assessments.php'),
            get_string('save_success', 'local_skillgap') . ' Assessment saved as Draft. Review it and Publish when ready.',
            null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'edit') {
        $id            = required_param('id',        PARAM_INT);
        $deptid        = required_param('deptid',    PARAM_INT);
        $desigid       = required_param('desigid',   PARAM_INT);
        $title         = required_param('title',     PARAM_TEXT);
        $frequency     = required_param('frequency', PARAM_ALPHANUMEXT);
        $startdate_str = required_param('startdate', PARAM_TEXT);
        $enddate_str   = required_param('enddate',   PARAM_TEXT);
        $startdate     = strtotime($startdate_str) ?: time();
        $enddate       = strtotime($enddate_str)   ?: time() + DAYSECS * 90;
        $compids_raw   = optional_param('compids', '', PARAM_RAW);
        $compids_raw   = preg_replace('/[^a-zA-Z0-9.,]/', '', $compids_raw);

        $DB->update_record('local_skillgap_assessment', [
            'id'           => $id,
            'deptid'       => $deptid,
            'desigid'      => $desigid,
            'title'        => $title,
            'frequency'    => $frequency,
            'startdate'    => $startdate,
            'enddate'      => $enddate,
            'timemodified' => time(),
            'usermodified' => $USER->id,
        ]);

        // If competencies changed, update them.
        if (!empty($compids_raw)) {
            $comp_entries = [];
            foreach (explode(',', $compids_raw) as $token) {
                $token = trim($token);
                if (empty($token)) { continue; }
                $parts = explode('.', $token);
                if (count($parts) === 1 && is_numeric($parts[0])) {
                    $comp_entries[] = ['compid' => (int)$parts[0], 'comp_type' => 'main', 'subcompid' => 0];
                } elseif (count($parts) >= 2) {
                    $type      = in_array($parts[0], ['main', 'sub', 'subsub']) ? $parts[0] : 'main';
                    $compid_v  = (int)($parts[1] ?? 0);
                    $subcomp_v = isset($parts[2]) && $parts[2] ? (int)$parts[2] : 0;
                    if ($compid_v > 0) {
                        $comp_entries[] = ['compid' => $compid_v, 'comp_type' => $type, 'subcompid' => $subcomp_v];
                    }
                }
            }
            // Deduplicate.
            $seen = []; $unique = [];
            foreach ($comp_entries as $e) {
                $k = $e['compid'] . '|' . $e['comp_type'] . '|' . $e['subcompid'];
                if (!isset($seen[$k])) { $seen[$k] = true; $unique[] = $e; }
            }
            if (!empty($unique)) {
                $DB->delete_records('local_skillgap_assess_comp', ['assessmentid' => $id]);
                static $has_comp_type = null;
                if ($has_comp_type === null) {
                    $dbman = $DB->get_manager();
                    $t = new xmldb_table('local_skillgap_assess_comp');
                    $f = new xmldb_field('comp_type');
                    $has_comp_type = $dbman->field_exists($t, $f);
                }
                foreach ($unique as $entry) {
                    $rec = ['assessmentid' => $id, 'compid' => $entry['compid']];
                    if ($has_comp_type) {
                        $rec['comp_type'] = $entry['comp_type'];
                        $rec['subcompid'] = $entry['subcompid'] ?? 0;
                    }
                    $DB->insert_record('local_skillgap_assess_comp', $rec);
                }
            }
        }
        redirect(new moodle_url('/local/skillgap/admin/assessments.php'),
            'Assessment updated successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'publish') {
        $id         = required_param('id', PARAM_INT);
        $assessment = $DB->get_record('local_skillgap_assessment', ['id' => $id], '*', MUST_EXIST);
        $DB->update_record('local_skillgap_assessment', [
            'id'           => $id,
            'status'       => 'active',
            'timemodified' => time(),
        ]);

        // Notify assigned users that a new assessment with competencies has been assigned.
        $dept_rec  = $DB->get_record('local_skillgap_dept', ['id' => $assessment->deptid]);
        $desig_rec = $assessment->desigid
            ? $DB->get_record('local_skillgap_desig_hierarchy', ['id' => $assessment->desigid])
            : null;
        if ($dept_rec) {
            $uparams = ['dept' => $dept_rec->name];
            if ($desig_rec) {
                $notify_users = $DB->get_records_sql(
                    "SELECT DISTINCT u.id FROM {user} u
                       JOIN {user_info_data} uid2 ON uid2.userid = u.id
                       JOIN {user_info_field} uif  ON uif.id = uid2.fieldid AND uif.shortname = 'designation'
                      WHERE u.deleted=0 AND u.suspended=0 AND u.department=:dept
                        AND " . $DB->sql_compare_text('uid2.data') . " = :desig",
                    array_merge($uparams, ['desig' => $desig_rec->designation_name])
                );
            } else {
                $notify_users = $DB->get_records_sql(
                    "SELECT DISTINCT u.id FROM {user} u
                      WHERE u.deleted=0 AND u.suspended=0 AND u.department=:dept",
                    $uparams
                );
            }
            $comp_names_arr = array_filter(array_map(function($ac) use ($DB) {
                return $DB->get_field('local_skillgap_comp', 'name', ['id' => $ac->compid]);
            }, array_values($DB->get_records('local_skillgap_assess_comp', ['assessmentid' => $id]))));
            $comp_names_str = implode(', ', $comp_names_arr);
            foreach ($notify_users as $nu) {
                local_skillgap_send_notification(
                    $nu->id,
                    'competency_assigned',
                    get_string('notify_competency_assigned_subject', 'local_skillgap', $assessment->title),
                    get_string('notify_competency_assigned_body', 'local_skillgap',
                        (object)['title' => $assessment->title, 'comps' => $comp_names_str])
                );
            }
        }

        redirect(new moodle_url('/local/skillgap/admin/assessments.php'),
            get_string('publish_success', 'local_skillgap'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'unpublish') {
        $id = required_param('id', PARAM_INT);
        $DB->update_record('local_skillgap_assessment', [
            'id'           => $id,
            'status'       => 'draft',
            'timemodified' => time(),
        ]);
        redirect(new moodle_url('/local/skillgap/admin/assessments.php'),
            'Assessment moved back to Draft.', null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'delete') {
        $id = required_param('id', PARAM_INT);
        $DB->delete_records('local_skillgap_assess_comp', ['assessmentid' => $id]);
        $DB->delete_records('local_skillgap_response',    ['attemptid' => $DB->get_field('local_skillgap_attempt', 'id', ['assessmentid' => $id])]);
        $DB->delete_records('local_skillgap_attempt',     ['assessmentid' => $id]);
        $DB->delete_records('local_skillgap_assessment',  ['id'           => $id]);
        redirect(new moodle_url('/local/skillgap/admin/assessments.php'),
            get_string('delete_success', 'local_skillgap'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

$data     = \local_skillgap\local\helper::assessments_data();
$renderer = $PAGE->get_renderer('local_skillgap');

echo $OUTPUT->header();
echo $renderer->render_assessments($data);
echo $OUTPUT->footer();
