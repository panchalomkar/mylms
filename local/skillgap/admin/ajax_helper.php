<?php
/**
 * AJAX helper endpoint for dynamic dropdowns and AI question generation.
 * All responses are JSON.
 */
define('AJAX_SCRIPT', true);
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');

require_login();
require_sesskey();

header('Content-Type: application/json; charset=utf-8');

$action = required_param('action', PARAM_ALPHANUMEXT);

// ── get_designations ────────────────────────────────────────────────────────
// Uses designation_service: names come from user profiles, hierarchy from DB.
if ($action === 'get_designations') {
    require_capability('local/skillgap:admin', context_system::instance());
    $deptid = required_param('deptid', PARAM_INT);

    // Get dept name from id.
    $dept_name = $DB->get_field('local_skillgap_dept', 'name', ['id' => $deptid]) ?: '';

    // Auto-sync from profiles first (adds any new designations not yet stored).
    if ($dept_name) {
        \local_skillgap\local\designation_service::sync_from_profiles($dept_name);
    }

    $hierarchy = $dept_name
        ? \local_skillgap\local\designation_service::get_hierarchy($dept_name)
        : [];

    $result = [];
    foreach ($hierarchy as $h) {
        $result[] = [
            'id'     => (int)$h->id,
            'name'   => $h->designation_name,
            'parent' => $h->parent_designation ?: '',
        ];
    }
    echo json_encode(['status' => 'ok', 'designations' => $result]);
    exit;
}

// ── get_competencies ────────────────────────────────────────────────────────
if ($action === 'get_competencies') {
    require_capability('local/skillgap:admin', context_system::instance());
    $deptid  = required_param('deptid',  PARAM_INT);
    $desigid = optional_param('desigid', 0, PARAM_INT);

    $where  = 'deptid = :deptid';
    $params = ['deptid' => $deptid];
    if ($desigid) {
        $where             .= ' AND desigid = :desigid';
        $params['desigid']  = $desigid;
    }
    $comps  = $DB->get_records_select('local_skillgap_comp', $where, $params, 'name ASC', 'id,name');
    $result = [];
    foreach ($comps as $c) {
        // Count ONLY main-level questions (subcompid IS NULL or 0).
        $qcount = (int)$DB->count_records_select('local_skillgap_question',
            'compid = :cid AND (subcompid IS NULL OR subcompid = 0)', ['cid' => $c->id]);
        $result[] = [
            'id'            => (int)$c->id,
            'name'          => $c->name,
            'has_questions' => $qcount >= 5,
            'q_count'       => $qcount,
        ];
    }
    echo json_encode(['status' => 'ok', 'competencies' => $result]);
    exit;
}

// ── generate_ai_questions ───────────────────────────────────────────────────
if ($action === 'generate_ai_questions') {
    require_capability('local/skillgap:admin', context_system::instance());
    $compid = required_param('compid', PARAM_INT);

    $existing = $DB->count_records('local_skillgap_question', ['compid' => $compid]);
    if ($existing >= 5) {
        echo json_encode([
            'status'  => 'error',
            'message' => get_string('error_max_questions', 'local_skillgap'),
            'added'   => 0,
        ]);
        exit;
    }

    $apikey = get_config('local_skillgap', 'openai_apikey');
    if (empty($apikey)) {
        echo json_encode([
            'status'  => 'error',
            'message' => get_string('error_api_key', 'local_skillgap'),
            'added'   => 0,
        ]);
        exit;
    }

    $comp   = $DB->get_record('local_skillgap_comp', ['id' => $compid], 'id,name', MUST_EXIST);
    $needed = 5 - $existing;

    $prompt = "Generate exactly {$needed} multiple-choice assessment questions for the workplace competency: \"{$comp->name}\".
Rules:
- Each question must have exactly 4 answer options.
- Exactly 1 option must be correct.
- Questions must be professional and workplace-relevant.
Return ONLY a valid JSON array (no markdown, no extra text):
[{\"question\":\"...\",\"options\":[\"Option A\",\"Option B\",\"Option C\",\"Option D\"],\"correct\":0}]
where correct is the 0-based index of the correct option.";

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey,
        ],
        CURLOPT_POSTFIELDS     => json_encode([
            'model'      => 'gpt-4o',
            'messages'   => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 2000,
        ]),
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $result   = curl_exec($ch);
    $curl_err = curl_errno($ch);
    curl_close($ch);

    if ($curl_err || !$result) {
        echo json_encode([
            'status'  => 'error',
            'message' => get_string('error_ai_failed', 'local_skillgap'),
            'added'   => 0,
        ]);
        exit;
    }

    $decoded   = json_decode($result, true);
    $raw       = $decoded['choices'][0]['message']['content'] ?? '[]';
    $raw       = preg_replace('/^```(?:json)?\s*/s', '', trim($raw));
    $raw       = preg_replace('/\s*```\s*$/s', '',          $raw);
    $questions = json_decode(trim($raw), true);

    if (!is_array($questions)) {
        echo json_encode([
            'status'  => 'error',
            'message' => get_string('error_ai_failed', 'local_skillgap'),
            'added'   => 0,
        ]);
        exit;
    }

    $now   = time();
    $added = 0;
    foreach (array_slice($questions, 0, $needed) as $q) {
        if (empty($q['question']) || !isset($q['options']) || count($q['options']) < 4) {
            continue;
        }
        $qid = $DB->insert_record('local_skillgap_question', [
            'compid'       => $compid,
            'questiontext' => clean_param($q['question'], PARAM_TEXT),
            'source'       => 'ai',
            'timecreated'  => $now,
        ]);
        foreach (array_slice($q['options'], 0, 4) as $idx => $opt) {
            $DB->insert_record('local_skillgap_qoption', [
                'questionid' => $qid,
                'optiontext' => clean_param($opt, PARAM_TEXT),
                'iscorrect'  => (int)($idx === (int)$q['correct']),
            ]);
        }
        $added++;
    }

    echo json_encode([
        'status'  => 'success',
        'message' => get_string('ai_questions_generated', 'local_skillgap', $added),
        'added'   => $added,
    ]);
    exit;
}

// ── get_competencies_tree ───────────────────────────────────────────────────
if ($action === 'get_competencies_tree') {
    require_capability('local/skillgap:admin', context_system::instance());
    $deptid  = optional_param('deptid',  0, PARAM_INT);
    $desigid = optional_param('desigid', 0, PARAM_INT);

    $where  = [];
    $params = [];
    if ($deptid)  { $where[] = 'c.deptid = :deptid';   $params['deptid']  = $deptid; }
    if ($desigid) { $where[] = 'c.desigid = :desigid';  $params['desigid'] = $desigid; }
    $sql = "SELECT c.id, c.name FROM {local_skillgap_comp} c"
         . ($where ? " WHERE " . implode(" AND ", $where) : "")
         . " ORDER BY c.name ASC";
    $comps = $DB->get_records_sql($sql, $params);

    $tree = [];
    foreach ($comps as $c) {
        $main_q = (int)$DB->count_records_select('local_skillgap_question',
            'compid = :cid AND (subcompid IS NULL OR subcompid = 0)', ['cid' => $c->id]);

        $all_subs  = $DB->get_records('local_skillgap_subcomp', ['compid' => $c->id], 'name ASC');
        $top_subs  = [];
        $child_map = [];
        foreach ($all_subs as $s) {
            if (empty($s->parentsubid)) { $top_subs[] = $s; }
            else { $child_map[$s->parentsubid][] = $s; }
        }
        $subs_tree = [];
        foreach ($top_subs as $s) {
            $sub_q = (int)$DB->count_records('local_skillgap_question',
                ['compid' => $c->id, 'subcompid' => $s->id]);
            $sub_subs_tree = [];
            foreach ($child_map[$s->id] ?? [] as $ss) {
                $ss_q = (int)$DB->count_records('local_skillgap_question',
                    ['compid' => $c->id, 'subcompid' => $ss->id]);
                $sub_subs_tree[] = ['id' => $ss->id, 'name' => $ss->name, 'q_count' => $ss_q, 'ready' => $ss_q >= 5];
            }
            $subs_tree[] = ['id' => $s->id, 'name' => $s->name, 'q_count' => $sub_q, 'ready' => $sub_q >= 5, 'subs' => $sub_subs_tree];
        }
        $tree[] = ['id' => $c->id, 'name' => $c->name, 'q_count' => $main_q, 'ready' => $main_q >= 5, 'subs' => $subs_tree];
    }
    echo json_encode(['status' => 'ok', 'competencies' => $tree]);
    exit;
}

// ── subcomp_question_counts ─────────────────────────────────────────────────
// live question badges without a full page reload.
if ($action === 'subcomp_question_counts') {
    require_capability('local/skillgap:admin', context_system::instance());
    $compid = required_param('compid', PARAM_INT);

    $subs = $DB->get_records('local_skillgap_subcomp', ['compid' => $compid], '', 'id');
    $counts = [];
    foreach ($subs as $s) {
        $counts[$s->id] = (int)$DB->count_records('local_skillgap_question',
            ['compid' => $compid, 'subcompid' => $s->id]);
    }
    echo json_encode(['status' => 'ok', 'counts' => $counts]);
    exit;
}

// ── preview_assessment ──────────────────────────────────────────────────────
if ($action === 'preview_assessment') {
    require_capability('local/skillgap:admin', context_system::instance());
    $aid = required_param('assessmentid', PARAM_INT);

    $assessment = $DB->get_record('local_skillgap_assessment', ['id' => $aid]);
    if (!$assessment) {
        echo json_encode(['status' => 'error', 'message' => 'Assessment not found']);
        exit;
    }

    // Fetch all assess_comp entries (supports main/sub/subsub).
    $acs = $DB->get_records('local_skillgap_assess_comp', ['assessmentid' => $aid]);

    $comp_blocks = [];
    $total_q     = 0;
    $errors      = [];

    foreach ($acs as $ac) {
        $comp_type = (!empty($ac->comp_type) && $ac->comp_type !== '')
            ? $ac->comp_type : 'main';
        $subcompid = (!empty($ac->subcompid) && (int)$ac->subcompid > 0)
            ? (int)$ac->subcompid : null;

        // Resolve display name and fetch questions.
        if ($comp_type === 'main' || !$subcompid) {
            $name      = $DB->get_field('local_skillgap_comp', 'name', ['id' => $ac->compid]) ?: '?';
            $questions = $DB->get_records_select('local_skillgap_question',
                'compid = :cid AND (subcompid IS NULL OR subcompid = 0)',
                ['cid' => $ac->compid], 'id ASC');
        } else {
            $name      = $DB->get_field('local_skillgap_subcomp', 'name', ['id' => $subcompid]) ?: '?';
            $questions = $DB->get_records('local_skillgap_question',
                ['compid' => $ac->compid, 'subcompid' => $subcompid], 'id ASC');
        }

        $level_labels = ['main' => 'Main', 'sub' => 'Sub', 'subsub' => 'Sub-Sub'];
        $level = $level_labels[$comp_type] ?? 'Main';

        // Validation: must have exactly 5 questions.
        $qc = count($questions);
        if ($qc < 5) {
            $errors[] = '"' . $name . '" has only ' . $qc . '/5 questions.';
        }

        // Build question rows.
        $q_rows = [];
        $qnum   = 1;
        foreach ($questions as $q) {
            $options = $DB->get_records('local_skillgap_qoption', ['questionid' => $q->id]);
            $opts    = [];
            foreach ($options as $o) {
                $opts[] = ['text' => $o->optiontext, 'iscorrect' => (bool)$o->iscorrect];
            }
            $q_rows[] = ['num' => $qnum++, 'text' => $q->questiontext, 'options' => $opts];
        }

        $total_q += $qc;
        $comp_blocks[] = [
            'name'      => $name,
            'level'     => $level,
            'q_count'   => $qc,
            'ready'     => $qc >= 5,
            'questions' => $q_rows,
        ];
    }

    echo json_encode([
        'status'       => 'ok',
        'title'        => $assessment->title,
        'total_q'      => $total_q,
        'comp_blocks'  => $comp_blocks,
        'is_published' => $assessment->status === 'active',
        'errors'       => $errors,
        'has_errors'   => !empty($errors),
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
exit;
