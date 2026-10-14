<?php
namespace local_skillgap\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Data helper — builds context arrays for Mustache templates.
 * All methods return plain arrays (no objects) so Mustache can render them.
 */
class helper {

    // ─── ADMIN DASHBOARD ─────────────────────────────────────────────

    public static function admin_dashboard_data(): array {
        global $DB, $CFG;

        $total_users       = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]) - 1;
        $total_comps       = $DB->count_records('local_skillgap_comp');
        $active_assessments= $DB->count_records('local_skillgap_assessment', ['status' => 'active']);
        $needs_improvement = (int)$DB->count_records_sql(
            "SELECT COUNT(*) FROM {local_skillgap_score}
              WHERE status = 'needs_improvement'
                AND userid IN (SELECT id FROM {user} WHERE deleted = 0 AND suspended = 0)");

        // Competency average scores.
        $comps = $DB->get_records('local_skillgap_comp', null, 'name ASC', 'id,name', 0, 8);
        $comp_bars = [];
        foreach ($comps as $c) {
            $avg = (float)($DB->get_field_sql(
                "SELECT AVG(score) FROM {local_skillgap_score}
                  WHERE compid = ?
                    AND userid IN (SELECT id FROM {user} WHERE deleted = 0 AND suspended = 0)", [$c->id]
            ) ?? 0);
            $pct    = $avg > 0 ? min(100, round(($avg / 5) * 100)) : 0;
            $status = $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
            $comp_bars[] = [
                'name'    => $c->name,
                'pct'     => $pct,
                'status'  => $status,
                'bar_class' => 'bg-' . $status,
            ];
        }

        // Recent assessments (last 5).
        $assessments = $DB->get_records_sql(
            "SELECT a.id, a.title, a.frequency, a.status,
                    d.name AS dept_name
               FROM {local_skillgap_assessment} a
               JOIN {local_skillgap_dept} d ON d.id = a.deptid
              ORDER BY a.timecreated DESC LIMIT 5"
        );
        $recent = [];
        foreach ($assessments as $a) {
            $status_map = [
                'active'    => ['label' => 'Active',    'class' => 'success'],
                'pending'   => ['label' => 'Pending',   'class' => 'warning'],
                'scheduled' => ['label' => 'Scheduled', 'class' => 'info'],
                'overdue'   => ['label' => 'Overdue',   'class' => 'danger'],
            ];
            $sm = $status_map[$a->status] ?? ['label' => ucfirst($a->status), 'class' => 'secondary'];
            $recent[] = [
                'title'       => $a->title,
                'dept_name'   => $a->dept_name,
                'frequency'   => ucfirst(str_replace('_', '-', $a->frequency)),
                'status_label'=> $sm['label'],
                'status_class'=> $sm['class'],
            ];
        }

        // Promotion history (last 10 promotions site-wide).
        $promo_history_raw = $DB->get_records_sql(
            "SELECT p.id, p.userid, p.timepromoted, p.promoted_by,
                    hf.designation_name AS from_desig,
                    ht.designation_name AS to_desig
               FROM {local_skillgap_promotion} p
               JOIN {user} u ON u.id = p.userid AND u.deleted = 0 AND u.suspended = 0
               LEFT JOIN {local_skillgap_desig_hierarchy} hf ON hf.id = p.from_desigid
               LEFT JOIN {local_skillgap_desig_hierarchy} ht ON ht.id = p.to_desigid
              WHERE p.status = 'promoted'
              ORDER BY p.timepromoted DESC LIMIT 10"
        );
        $promo_history = [];
        foreach ($promo_history_raw as $ph) {
            $u_rec    = $DB->get_record('user', ['id' => $ph->userid], 'firstname,lastname,department', IGNORE_MISSING);
            $by_rec   = $DB->get_record('user', ['id' => $ph->promoted_by], 'firstname,lastname', IGNORE_MISSING);
            // Fallback: if designation names not in hierarchy join, read from profile at time.
            $from_desig = $ph->from_desig ?: '—';
            $to_desig   = $ph->to_desig   ?: '—';
            $promo_history[] = [
                'fullname'    => $u_rec   ? fullname($u_rec)  : 'User #' . $ph->userid,
                'department'  => $u_rec   ? $u_rec->department : '—',
                'promoted_by' => $by_rec  ? fullname($by_rec) : 'System',
                'from_desig'  => $from_desig,
                'to_desig'    => $to_desig,
                'date'        => userdate($ph->timepromoted, get_string('strftimedatefullshort', 'core_langconfig')),
            ];
        }

        // Promotion candidates.
        $promotions = self::promotion_data(true);

        return [
            'total_users'          => $total_users,
            'total_competencies'   => $total_comps,
            'active_assessments'   => $active_assessments,
            'needs_improvement_count' => $needs_improvement,
            'comp_bars'            => array_values($comp_bars),
            'recent_assessments'   => $recent,
            'promotion_users'      => array_slice($promotions['promotion_users'], 0, 5),
            'promo_history'        => $promo_history,
            'has_promo_history'    => !empty($promo_history),
            'wwwroot'              => $CFG->wwwroot,
            'sesskey'              => sesskey(),
        ];
    }

    // ─── USER DASHBOARD ──────────────────────────────────────────────

    public static function user_dashboard_data(int $userid): array {
        global $DB, $CFG;

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        // Get active assessment for this user's dept+desig.
        $dept  = $user->department ?? '';
        $deptid= $DB->get_field('local_skillgap_dept', 'id', ['name' => $dept]);

        // Resolve user's designation hierarchy ID for all filtering in this function.
        $ud_desig_name = \local_skillgap\local\designation_service::get_user_designation($userid);
        $ud_desig_rec  = ($ud_desig_name && !empty($user->department))
            ? $DB->get_record('local_skillgap_desig_hierarchy',
                ['designation_name' => $ud_desig_name, 'department' => $user->department], 'id', IGNORE_MISSING)
            : null;
        $ud_desigid = $ud_desig_rec ? (int)$ud_desig_rec->id : 0;

        // Latest scores per competency — restricted to comps mapped to user's dept+desig assessments.
        $score_desig_join = '';
        $score_params     = ['uid' => $userid];
        if ($deptid && $ud_desigid) {
            $score_desig_join = "AND EXISTS (
                SELECT 1 FROM {local_skillgap_assess_comp} ac2
                  JOIN {local_skillgap_assessment} a2 ON a2.id = ac2.assessmentid
                 WHERE ac2.compid = s.compid AND a2.deptid = :deptid AND a2.desigid = :desigid AND a2.status = 'active'
            )";
            $score_params['deptid']  = $deptid;
            $score_params['desigid'] = $ud_desigid;
        }
        $sql = "SELECT s.compid, c.name AS compname,
                       s.score, s.maxscore, s.status, s.timecreated,
                       s.attemptid
                  FROM {local_skillgap_score} s
                  JOIN {local_skillgap_comp} c ON c.id = s.compid
                 WHERE s.userid = :uid
                   AND s.timecreated = (
                       SELECT MAX(s2.timecreated)
                         FROM {local_skillgap_score} s2
                        WHERE s2.userid = s.userid AND s2.compid = s.compid
                   )
                   $score_desig_join
                 ORDER BY c.name ASC";
        $latest_scores = $DB->get_records_sql($sql, $score_params);

        $rows          = [];
        $total_score   = 0;
        $competent_count = 0;
        $weakest_name  = '';
        $weakest_score = 999;

        foreach ($latest_scores as $s) {
            $pct = ($s->maxscore > 0)
    ? min(100, round(($s->score / $s->maxscore) * 100))
    : 0;
            $progress = \local_skillgap_progress_score($userid, $s->compid);
            $prog_str = $progress === null ? get_string('first_attempt', 'local_skillgap')
                      : ($progress >= 0 ? '+' . number_format($progress, 1) : number_format($progress, 1));
            $trend_up = $progress !== null && $progress > 0;
            $trend_dn = $progress !== null && $progress < 0;

            $status_labels = [
                'competent'         => get_string('status_competent', 'local_skillgap'),
                'moderate'          => get_string('status_moderate', 'local_skillgap'),
                'needs_improvement' => get_string('status_needs_improvement', 'local_skillgap'),
            ];
            $badge_class = [
                'competent'         => 'success',
                'moderate'          => 'warning',
                'needs_improvement' => 'danger',
            ];

            // Sub-competencies — with actual score, status, progress per sub.
            $subs = $DB->get_records('local_skillgap_subcomp',
                ['compid' => $s->compid, 'parentsubid' => null], 'name ASC');
            $sub_rows = [];
            foreach ($subs as $sub) {
                // Try score from score table (stored with subcompid if available).
                $sub_score_rec = $DB->get_record_sql(
                    "SELECT score, maxscore, status FROM {local_skillgap_score}
                      WHERE userid = :uid AND compid = :cid AND subcompid = :sid
                      ORDER BY timecreated DESC LIMIT 1",
                    ['uid' => $userid, 'cid' => $s->compid, 'sid' => $sub->id]
                );
                // Fallback: compute from responses for this subcomp.
                if (!$sub_score_rec) {
                    $resp = $DB->get_record_sql(
                        "SELECT COUNT(*) AS total, SUM(r.iscorrect) AS correct
                           FROM {local_skillgap_response} r
                           JOIN {local_skillgap_attempt} att ON att.id = r.attemptid
                           JOIN {local_skillgap_question} q ON q.id = r.questionid
                          WHERE att.userid = :uid AND q.compid = :cid
                            AND q.subcompid = :sid AND att.status = 'completed'",
                        ['uid' => $userid, 'cid' => $s->compid, 'sid' => $sub->id]
                    );
                    if ($resp && $resp->total > 0) {
                        $raw_correct  = (int)$resp->correct;
                        $raw_total    = (int)$resp->total;
                        $sub_score_val= round(($raw_correct / $raw_total) * 5, 1);
                        $sub_maxscore = 5;
                        $sub_status   = \local_skillgap_get_status($sub_score_val);
                    } else {
                        $sub_score_val = null;
                        $sub_maxscore  = 5;
                        $sub_status    = null;
                    }
                } else {
                    $sub_score_val = round((float)$sub_score_rec->score, 1);
                    $sub_maxscore  = (int)$sub_score_rec->maxscore ?: 5;
                    $sub_status    = $sub_score_rec->status;
                }

                $sub_score_disp = $sub_score_val !== null
                    ? min(5, $sub_score_val) . '/5'
                    : '-';
                $sub_pct  = $sub_score_val !== null
                    ? min(100, round(($sub_score_val / 5) * 100))
                    : 0;
                $sub_badge = [
                    'competent'         => 'success',
                    'moderate'          => 'warning',
                    'needs_improvement' => 'danger',
                ][$sub_status ?? ''] ?? 'secondary';
                $sub_status_label = [
                    'competent'         => get_string('status_competent',         'local_skillgap'),
                    'moderate'          => get_string('status_moderate',          'local_skillgap'),
                    'needs_improvement' => get_string('status_needs_improvement', 'local_skillgap'),
                ][$sub_status ?? ''] ?? get_string('status_sub_competency', 'local_skillgap');

                $sub_rows[] = [
                    'name'         => $sub->name,
                    'score'        => $sub_score_disp,
                    'pct'          => $sub_pct,
                    'has_score'    => $sub_score_val !== null,
                    'status_label' => $sub_status_label,
                    'badge_class'  => $sub_badge,
                    'is_competent' => ($sub_status === 'competent'),
                    'is_needs_imp' => ($sub_status === 'needs_improvement'),
                    'is_moderate'  => ($sub_status === 'moderate'),
                    'is_na'        => ($sub_score_val === null),
                ];
            }

            $rows[] = [
                'compid'        => $s->compid,
                'userid'        => $userid,
                'compname'      => $s->compname,
                'score'         => min(5, number_format($s->score, 1)),
                'maxscore'      => $s->maxscore,
                'pct'           => $pct,
                'status'        => $s->status,
                'status_label'  => $status_labels[$s->status] ?? $s->status,
                'badge_class'   => $badge_class[$s->status] ?? 'secondary',
                'progress_str'  => $prog_str,
                'trend_up'      => $trend_up,
                'trend_down'    => $trend_dn,
                'has_subs'      => !empty($sub_rows),
                'sub_rows'      => $sub_rows,
            ];

            $total_score += $pct;
            if ($s->status === 'competent') {
                $competent_count++;
            }
            if ($pct < $weakest_score) {
                $weakest_score = $pct;
                $weakest_name  = $s->compname;
            }
        }

        $total_comps   = count($latest_scores);
        $avg_score     = $total_comps > 0 ? round($total_score / $total_comps) : 0;

        // Overall progress (average of progress scores).
        $overall_progress = 0;
        if ($total_comps > 0) {
            $prog_sum = 0;
            $prog_cnt = 0;
            foreach ($latest_scores as $s) {
                $p = \local_skillgap_progress_score($userid, $s->compid);
                if ($p !== null) {
                    $prog_sum += $p;
                    $prog_cnt++;
                }
            }
            $overall_progress = $prog_cnt > 0 ? round(($prog_sum / $prog_cnt / 5) * 100) : 0;
        }

        // Next designation for promotion track.
        $next_desig = null;
        if ($deptid) {
            // Get user's current designation from profile field 'designation'.
            $user_desig = \local_skillgap\local\designation_service::get_user_designation($userid);
            if ($user_desig && $user->department) {
                $next_desig = \local_skillgap\local\designation_service::get_next_designation(
                    $user->department, $user_desig
                );
            }
        }

        // All pending assessments for this user.
        $pending_assessments = [];
        $completed_assessments = [];
        $inprogress_assessments = [];
        if ($deptid) {
            $assmt_params = ['deptid' => $deptid];
            $assmt_desig_clause = '';
            if ($ud_desigid) {
                $assmt_desig_clause = ' AND a.desigid = :desigid';
                $assmt_params['desigid'] = $ud_desigid;
            }
            $all_active = $DB->get_records_sql(
                "SELECT a.id, a.title, a.frequency
                   FROM {local_skillgap_assessment} a
                  WHERE a.deptid = :deptid AND a.status = 'active'$assmt_desig_clause
                  ORDER BY a.timecreated DESC",
                $assmt_params
            );
            foreach ($all_active as $a) {
                $attempt = $DB->get_record_sql(
                    "SELECT id, status FROM {local_skillgap_attempt}
                      WHERE userid = :uid AND assessmentid = :aid
                      ORDER BY timecreated DESC LIMIT 1",
                    ['uid' => $userid, 'aid' => $a->id]
                );
                // Count competencies and total questions for this assessment.
                $acomps = $DB->get_records_sql(
                    "SELECT c.id, c.name FROM {local_skillgap_comp} c
                       JOIN {local_skillgap_assess_comp} ac ON ac.compid = c.id
                      WHERE ac.assessmentid = :aid ORDER BY c.name ASC",
                    ['aid' => $a->id]
                );
                $acomp_names  = implode(', ', array_column((array)$acomps, 'name'));
                $acomp_count  = count($acomps);
                $total_q_count= (int)$DB->count_records_sql(
                    "SELECT COUNT(*) FROM {local_skillgap_question} q
                       JOIN {local_skillgap_assess_comp} ac ON ac.compid = q.compid
                      WHERE ac.assessmentid = :aid", ['aid' => $a->id]
                );
                $entry = [
                    'id'          => $a->id,
                    'title'       => $a->title,
                    'frequency'   => ucfirst(str_replace('_', '-', $a->frequency)),
                    'comp_names'  => $acomp_names,
                    'comp_count'  => $acomp_count,
                    'total_q'     => $total_q_count,
                    'url'         => (new \moodle_url('/local/skillgap/user/assessment.php',
                                        ['id' => $a->id]))->out(false),
                ];
                if (!$attempt) {
                    $pending_assessments[] = $entry;
                } elseif ($attempt->status === 'completed') {
                    $completed_assessments[] = $entry;
                } else {
                    $entry['attempt_id'] = $attempt->id;
                    $inprogress_assessments[] = $entry;
                }
            }
        }
        $pending_assessment = !empty($pending_assessments) ? $pending_assessments[0] : null;

        // Assigned competencies for this user (from active assessments).
        $assigned_comps = [];
        if ($deptid) {
            $acomp_params = ['deptid' => $deptid];
            $acomp_desig_clause = '';
            if ($ud_desigid) {
                $acomp_desig_clause = ' AND a.desigid = :desigid';
                $acomp_params['desigid'] = $ud_desigid;
            }
            $comps_sql = "SELECT DISTINCT c.id, c.name, c.description
                            FROM {local_skillgap_comp} c
                            JOIN {local_skillgap_assess_comp} ac ON ac.compid = c.id
                            JOIN {local_skillgap_assessment}  a  ON a.id = ac.assessmentid
                           WHERE a.deptid = :deptid AND a.status = 'active'$acomp_desig_clause
                           ORDER BY c.name ASC";
            $assigned_comps_raw = $DB->get_records_sql($comps_sql, $acomp_params);
            foreach ($assigned_comps_raw as $c) {
                $latest = \local_skillgap_latest_score($userid, $c->id);
                $assigned_comps[] = [
                    'id'           => $c->id,
                    'name'         => $c->name,
                    'has_score'    => !empty($latest),
                    'score'        => $latest ? number_format((float)$latest->score, 1) . '/5' : '-',
                    'status_label' => $latest ? get_string('status_' . $latest->status, 'local_skillgap') : 'Not Attempted',
                    'badge_class'  => $latest
                        ? (['competent' => 'success', 'moderate' => 'warning', 'needs_improvement' => 'danger'][$latest->status] ?? 'secondary')
                        : 'secondary',
                ];
            }
        }

        // ─── Assigned Assessments (pending / in-progress / completed) ─────────
        $assigned_pending   = [];
        $assigned_completed = [];
        $assigned_running   = [];

        if ($deptid) {
            $all_asmt_params = ['deptid' => $deptid];
            $all_asmt_desig_clause = '';
            if ($ud_desigid) {
                $all_asmt_desig_clause = ' AND a.desigid = :desigid';
                $all_asmt_params['desigid'] = $ud_desigid;
            }
            $all_assessments = $DB->get_records_sql(
                "SELECT a.id, a.title, a.frequency, a.status,
                        d.name AS dept_name,
                        des.designation_name AS desig_name
                   FROM {local_skillgap_assessment} a
                   JOIN {local_skillgap_dept} d ON d.id = a.deptid
                   LEFT JOIN {local_skillgap_desig_hierarchy} des ON des.id = a.desigid
                  WHERE a.deptid = :deptid AND a.status = 'active'$all_asmt_desig_clause
                  ORDER BY a.timecreated DESC",
                $all_asmt_params
            );

            foreach ($all_assessments as $asmt) {
                // Count competencies and questions.
                // Each competency contributes exactly 5 questions to the assessment.
                $comp_info_sql = "SELECT c.id, c.name,
                                         (SELECT COUNT(*) FROM {local_skillgap_question} q WHERE q.compid = c.id) AS q_count
                                    FROM {local_skillgap_comp} c
                                    JOIN {local_skillgap_assess_comp} ac ON ac.compid = c.id
                                   WHERE ac.assessmentid = :aid ORDER BY c.name ASC";
                $asmt_comps = $DB->get_records_sql($comp_info_sql, ['aid' => $asmt->id]);
                // 5 questions per competency (as per assessment rule).
                $total_q    = count($asmt_comps) * 5;

                // Check attempt status.
                $attempt = $DB->get_record_sql(
                    "SELECT * FROM {local_skillgap_attempt}
                      WHERE userid = :uid AND assessmentid = :aid
                      ORDER BY timecreated DESC LIMIT 1",
                    ['uid' => $userid, 'aid' => $asmt->id]
                );

                $comp_names_list = array_map(fn($c) => ['cname' => $c->name], array_values((array)$asmt_comps));

                $row = [
                    'id'          => $asmt->id,
                    'title'       => $asmt->title,
                    'dept_name'   => $asmt->dept_name,
                    'desig_name'  => $asmt->desig_name ?? '',
                    'frequency'   => ucfirst(str_replace('_', '-', $asmt->frequency)),
                    'comp_count'  => count($asmt_comps),
                    'total_q'     => $total_q,
                    'comp_names'  => $comp_names_list,
                    'url'         => (new \moodle_url('/local/skillgap/user/assessment.php', ['id' => $asmt->id]))->out(false),
                ];

                if (!$attempt) {
                    $assigned_pending[] = $row;
                } elseif ($attempt->status === 'completed') {
                    $row['timecompleted'] = $attempt->timecompleted
                        ? userdate($attempt->timecompleted, '%d %b %Y') : '-';
                    // Compute overall score % for completed assessment.
                    $responses = $DB->get_records('local_skillgap_response', ['attemptid' => $attempt->id]);
                    $resp_correct = 0; $resp_total = 0;
                    foreach ($responses as $r) {
                        $resp_total++;
                        if ($r->iscorrect) { $resp_correct++; }
                    }
                    $overall_pct = $resp_total > 0 ? round(($resp_correct / $resp_total) * 100) : 0;
                    $row['overall_pct']   = $overall_pct;
                    $row['overall_badge'] = $overall_pct >= 80 ? 'success' : ($overall_pct >= 60 ? 'warning' : 'danger');
                    $assigned_completed[] = $row;
                } else {
                    $row['attemptid'] = $attempt->id;
                    $assigned_running[] = $row;
                }
            }
        }

        // ─── Assigned Competencies (from active assessments) ──────────────────
        $assigned_competencies = [];
        if ($deptid) {
            $ac_params = ['deptid' => $deptid];
            $ac_desig_clause = '';
            if ($ud_desigid) {
                $ac_desig_clause = ' AND a.desigid = :desigid';
                $ac_params['desigid'] = $ud_desigid;
            }
            $ac_sql = "SELECT DISTINCT c.id, c.name, c.description
                         FROM {local_skillgap_comp} c
                         JOIN {local_skillgap_assess_comp} ac ON ac.compid = c.id
                         JOIN {local_skillgap_assessment}  a  ON a.id = ac.assessmentid
                        WHERE a.deptid = :deptid AND a.status = 'active'$ac_desig_clause
                        ORDER BY c.name ASC";
            $acomps = $DB->get_records_sql($ac_sql, $ac_params);
            foreach ($acomps as $ac) {
                $latest = \local_skillgap_latest_score($userid, $ac->id);
                $status = $latest ? $latest->status : 'not_attempted';
                $badge  = [
                    'competent'          => 'success',
                    'moderate'           => 'warning',
                    'needs_improvement'  => 'danger',
                    'not_attempted'      => 'secondary',
                ][$status] ?? 'secondary';
                $assigned_competencies[] = [
                    'id'          => $ac->id,
                    'name'        => $ac->name,
                    'description' => $ac->description,
                    'status'      => $status,
                    'status_label'=> ucwords(str_replace('_', ' ', $status)),
                    'badge_class' => $badge,
                   'score' => ($latest && $latest->maxscore > 0)
    ? min(100, round(($latest->score / $latest->maxscore) * 100)) . '%'
    : '—',
                ];
            }
        }


        // Chart data (labels + scores for spider chart).
        $chart_labels = [];
        $chart_scores = [];
        foreach ($rows as $r) {
            $chart_labels[] = $r['compname'];
            $chart_scores[] = (float)$r['score'];
        }

        // Promoted recently? (within 7 days) → show promoted badge.
        $last_promo_rec = $DB->get_record_sql(
            "SELECT p.timepromoted, p.to_desigid
               FROM {local_skillgap_promotion} p
              WHERE p.userid = :uid ORDER BY p.timepromoted DESC LIMIT 1",
            ['uid' => $userid]
        );
        $recently_promoted   = $last_promo_rec && (time() - (int)$last_promo_rec->timepromoted < 7 * 86400);
        $current_designation = \local_skillgap\local\designation_service::get_user_designation($userid);

        // For the promoted badge: try to get name from to_desigid join; if empty,
        // use current_designation (which is already updated to the new one).
        $promoted_designation = '';
        if ($recently_promoted) {
            if (!empty($last_promo_rec->to_desigid)) {
                $to_h = $DB->get_field('local_skillgap_desig_hierarchy', 'designation_name',
                    ['id' => (int)$last_promo_rec->to_desigid]);
                $promoted_designation = $to_h ?: $current_designation;
            } else {
                $promoted_designation = $current_designation;
            }
        }

        return [
            'userid'               => $userid,
            'fullname'             => fullname($user),
            'user_initials'        => strtoupper(substr($user->firstname, 0, 1) . substr($user->lastname, 0, 1)),
            'department'           => $user->department,
            'current_designation'  => $current_designation,
            'recently_promoted'    => $recently_promoted,
            'promoted_designation' => $promoted_designation,
            'avg_score'            => $avg_score,
            'weakest_competency'   => $weakest_name ?: '-',
            'overall_progress'     => ($overall_progress >= 0 ? '+' : '') . $overall_progress,
            'competent_count'      => $competent_count,
            'total_count'          => $total_comps,
            'competency_rows'      => $rows,
            'has_scores'           => !empty($rows),  // guard for radar+results section (avoids N duplicates)
            'has_pending'             => !empty($pending_assessment),
            'assessment_id'           => $pending_assessment ? $pending_assessment['id'] : 0,
            'pending_assessments'     => $pending_assessments,
            'has_pending_list'        => !empty($pending_assessments),
            'pending_count'           => count($pending_assessments),
            'completed_assessments'   => $completed_assessments,
            'has_completed'           => !empty($completed_assessments),
            'completed_count'         => count($completed_assessments),
            'inprogress_assessments'  => $inprogress_assessments,
            'has_inprogress'          => !empty($inprogress_assessments),
            'assigned_comps'          => $assigned_comps,
            'has_assigned_comps'      => !empty($assigned_comps),
            'assigned_comp_count'     => count($assigned_comps),
            'next_designation'        => $next_desig,
            'show_promotion'          => !empty($next_desig),
            'chart_labels_json'       => json_encode($chart_labels),
            'chart_scores_json'       => json_encode($chart_scores),
            // Assigned assessments by status
            'assigned_pending'          => $assigned_pending,
            'assigned_running'          => $assigned_running,
            'assigned_completed'        => $assigned_completed,
            'has_pending_assessments'   => !empty($assigned_pending),
            'has_running_assessments'   => !empty($assigned_running),
            'has_completed_assessments' => !empty($assigned_completed),
            'pending_count'             => count($assigned_pending),
            'running_count'             => count($assigned_running),
            'completed_count'           => count($assigned_completed),
            // Banner button logic: 1 pending = direct button; >1 = dropdown
            'single_pending'            => count($assigned_pending) === 1,
            'multi_pending'             => count($assigned_pending) > 1,
            'pending_url'               => !empty($assigned_pending) ? $assigned_pending[0]['url'] : '',
            'pending_title'             => !empty($assigned_pending) ? $assigned_pending[0]['title'] : '',
            // Banner button logic: 1 running = direct button; >1 = dropdown
            'single_running'            => count($assigned_running) === 1,
            'multi_running'             => count($assigned_running) > 1,
            'running_url'               => !empty($assigned_running) ? $assigned_running[0]['url'] : '',
            'running_title'             => !empty($assigned_running) ? $assigned_running[0]['title'] : '',
            // Manager access — show Team Analytics button if user has team members
            'is_manager'                => has_capability('local/skillgap:managerviewteam',
                                               \context_system::instance(), $userid)
                                           && count(local_skillgap_get_team($userid)) > 0,
            'manager_url'               => (new \moodle_url('/local/skillgap/manager/dashboard.php'))->out(false),
            'team_member_count'         => count(local_skillgap_get_team($userid)),
            // Assigned competencies
            'assigned_competencies'     => $assigned_competencies,
            'has_assigned_comps'        => !empty($assigned_competencies),
            'assigned_comp_count'       => count($assigned_competencies),
            'wwwroot'                 => $CFG->wwwroot,
            'sesskey'                 => sesskey(),
            // ── Promotion history for this user ──────────────────────────
            'promotion_history'       => (function() use ($DB, $userid) {
                $rows = $DB->get_records_sql(
                    "SELECT p.timepromoted,
                            hf.designation_name AS from_desig,
                            ht.designation_name AS to_desig
                       FROM {local_skillgap_promotion} p
                       LEFT JOIN {local_skillgap_desig_hierarchy} hf ON hf.id = p.from_desigid
                       LEFT JOIN {local_skillgap_desig_hierarchy} ht ON ht.id = p.to_desigid
                      WHERE p.userid = :uid AND p.status = 'promoted'
                      ORDER BY p.timepromoted DESC",
                    ['uid' => $userid]
                );
                $history = [];
                foreach ($rows as $r) {
                    $history[] = [
                        'from_desig' => $r->from_desig ?: '—',
                        'to_desig'   => $r->to_desig   ?: '—',
                        'date'       => userdate($r->timepromoted,
                            get_string('strftimedatefullshort', 'core_langconfig')),
                    ];
                }
                return $history;
            })(),
            'has_promotion_history'   => (bool)$DB->record_exists('local_skillgap_promotion',
                                            ['userid' => $userid, 'status' => 'promoted']),
            // ── Past competency snapshots (one per completed attempt) ─────
            'past_competency_snapshots' => (function() use ($DB, $userid) {
                // Get all completed attempts, newest first.
                $attempts = $DB->get_records_sql(
                    "SELECT a.id, a.timecompleted, ass.title AS assessment_title
                       FROM {local_skillgap_attempt} a
                       JOIN {local_skillgap_assessment} ass ON ass.id = a.assessmentid
                      WHERE a.userid = :uid AND a.status = 'completed'
                      ORDER BY a.timecompleted DESC LIMIT 10",
                    ['uid' => $userid]
                );
                $snapshots = [];
                foreach ($attempts as $att) {
                    $scores = $DB->get_records_sql(
                        "SELECT s.score, s.maxscore, s.status, c.name AS compname
                           FROM {local_skillgap_score} s
                           JOIN {local_skillgap_comp} c ON c.id = s.compid
                          WHERE s.attemptid = :aid
                          ORDER BY c.name ASC",
                        ['aid' => $att->id]
                    );
                    $comp_snaps = [];
                    $total_pct  = 0;
                    $cnt        = 0;
                   foreach ($scores as $sc) {
    // ✅ FIX: clamp pct to 100 max; score may exceed maxscore if
    // multiple questions aggregate but maxscore = single question max
    $raw_pct = $sc->maxscore > 0 ? ($sc->score / $sc->maxscore) * 100 : 0;
    $pct = min(100, (int) round($raw_pct));  // ← clamp to 100

    $badge = ['competent' => 'success', 'moderate' => 'warning',
              'needs_improvement' => 'danger'][$sc->status] ?? 'secondary';
    $comp_snaps[] = [
        'compname'     => $sc->compname,
        'score_pct'    => $pct,
        'status_label' => ucwords(str_replace('_', ' ', $sc->status)),
        'badge_class'  => $badge,
    ];
    $total_pct += $pct;
    $cnt++;
}
                    if (empty($comp_snaps)) {
                        continue;
                    }
                    $snapshots[] = [
                        'assessment_title' => $att->assessment_title,
                        'date'             => userdate($att->timecompleted,
                            get_string('strftimedatefullshort', 'core_langconfig')),
                        'avg_pct'          => $cnt ? round($total_pct / $cnt) : 0,
                        'comp_snaps'       => $comp_snaps,
                    ];
                }
                return $snapshots;
            })(),
            'has_snapshots'           => (bool)$DB->record_exists('local_skillgap_attempt',
                                            ['userid' => $userid, 'status' => 'completed']),
        ];
    }

    // ─── MANAGER DASHBOARD ───────────────────────────────────────────

    public static function manager_dashboard_data(int $managerid, array $filters = []): array {
        global $DB, $CFG;

        $team = \local_skillgap_get_team($managerid);

        if (empty($team)) {
            return [
                'fullname'         => fullname($DB->get_record('user', ['id' => $managerid])),
                'team_count'       => 0,
                'team_members'     => [],
                'comp_report'      => [],
                'metrics'          => [],
                'no_team'          => true,
                'departments'      => self::get_dept_options(),
                'designations'     => [],
                'competencies'     => [],
                'wwwroot'          => $CFG->wwwroot,
            ];
        }

        $team_ids = array_keys($team);

        // Filter support.
        $deptid  = (int)($filters['deptid']  ?? 0);
        $desigid = (int)($filters['desigid'] ?? 0);
        $compid  = (int)($filters['compid']  ?? 0);

        if ($deptid && $desigid) {
            // Filter team to users in that dept/desig.
            $filtered_ids = [];
            foreach ($team as $u) {
                $dept_rec = $DB->get_record('local_skillgap_dept', ['name' => $u->department]);
                if ($dept_rec && $dept_rec->id == $deptid) {
                    $filtered_ids[] = $u->id;
                }
            }
            $team_ids = $filtered_ids ?: $team_ids;
        }

        list($in_sql, $in_params) = $DB->get_in_or_equal($team_ids, SQL_PARAMS_NAMED, 'uid');

        // Per-competency aggregates.
        $comp_sql = "SELECT c.id AS compid, c.name AS compname,
                            COUNT(DISTINCT s.userid) AS total_users,
                            SUM(CASE WHEN s.status='competent' THEN 1 ELSE 0 END) AS competent,
                            SUM(CASE WHEN s.status!='competent' THEN 1 ELSE 0 END) AS non_competent,
                            AVG(s.score) AS avg_score
                       FROM {local_skillgap_comp} c
                       JOIN {local_skillgap_score} s ON s.compid = c.id
                       JOIN {user} mu ON mu.id = s.userid AND mu.deleted = 0 AND mu.suspended = 0
                      WHERE s.userid $in_sql
                        AND s.timecreated = (
                            SELECT MAX(s2.timecreated)
                              FROM {local_skillgap_score} s2
                             WHERE s2.userid = s.userid AND s2.compid = s.compid
                        )";
        if ($compid) {
            $comp_sql .= " AND c.id = :compid";
            $in_params['compid'] = $compid;
        }
        $comp_sql .= " GROUP BY c.id, c.name ORDER BY c.name ASC";

        $comp_rows_raw = $DB->get_records_sql($comp_sql, $in_params);
        $comp_report   = [];
        $most_failing  = null;
        $most_failing_pct = 999;
        $most_improved    = null;
        $most_improved_delta = -999;

        foreach ($comp_rows_raw as $r) {
            $avg_pct   = min(100, round((float)$r->avg_score / 5 * 100));
            $fail_pct  = $r->total_users > 0 ? round($r->non_competent / $r->total_users * 100) : 0;
            $bar_class = $avg_pct >= 80 ? 'bg-success' : ($avg_pct >= 60 ? 'bg-warning' : 'bg-danger');

            if ($fail_pct > 50 && $fail_pct < $most_failing_pct) {
                $most_failing     = $r->compname;
                $most_failing_pct = $fail_pct;
            }

            // Fetch per-user details for drilldown popups.
            list($in2, $p2) = $DB->get_in_or_equal($team_ids, SQL_PARAMS_NAMED, 'up');
            $user_detail_sql = "SELECT s.userid, u.firstname, u.lastname, s.score, s.status
                                  FROM {local_skillgap_score} s
                                  JOIN {user} u ON u.id = s.userid
                                 WHERE s.compid = :cid AND s.userid $in2
                                   AND u.deleted = 0 AND u.suspended = 0
                                   AND s.timecreated = (
                                       SELECT MAX(s3.timecreated) FROM {local_skillgap_score} s3
                                        WHERE s3.userid = s.userid AND s3.compid = s.compid
                                   )
                                 ORDER BY u.lastname ASC, u.firstname ASC";
            $p2['cid'] = $r->compid;
            $user_details_raw = $DB->get_records_sql($user_detail_sql, $p2);

            $competent_users     = [];
            $non_competent_users = [];
            $total_users_list    = [];
            foreach ($user_details_raw as $ud) {
                $badge_u = ['competent' => 'success', 'moderate' => 'warning', 'needs_improvement' => 'danger'][$ud->status] ?? 'secondary';
                $entry = [
                    'fullname'     => fullname((object)['firstname' => $ud->firstname, 'lastname' => $ud->lastname]),
                    'score'        => min(5, round($ud->score, 1)) . '/5',
                    'status_label' => ucwords(str_replace('_', ' ', $ud->status)),
                    'badge_class'  => $badge_u,
                ];
                $total_users_list[] = $entry;
                if ($ud->status === 'competent') {
                    $competent_users[] = $entry;
                } else {
                    $non_competent_users[] = $entry;
                }
            }

            $comp_report[] = [
                'compid'                  => $r->compid,
                'compname'                => $r->compname,
                'total_users'             => (int)$r->total_users,
                'competent'               => (int)$r->competent,
                'non_competent'           => (int)$r->non_competent,
                'avg_score'               => round((float)$r->avg_score, 1),
                'avg_pct'                 => $avg_pct,
                'bar_class'               => $bar_class,
                'total_users_json'        => base64_encode(json_encode($total_users_list)),
                'competent_users_json'    => base64_encode(json_encode($competent_users)),
                'non_competent_users_json'=> base64_encode(json_encode($non_competent_users)),
            ];
        }

        // Per-member summary with per-competency breakdown.
        $member_rows = [];
        $total_readiness = 0;
        foreach ($team as $u) {
            if (!in_array($u->id, $team_ids)) {
                continue;
            }
            $sql_m = "SELECT AVG(s.score) AS avg_score
                        FROM {local_skillgap_score} s
                       WHERE s.userid = :uid
                         AND s.timecreated = (
                             SELECT MAX(s2.timecreated) FROM {local_skillgap_score} s2
                              WHERE s2.userid = s.userid AND s2.compid = s.compid
                         )";
            $avg   = (float)($DB->get_field_sql($sql_m, ['uid' => $u->id]) ?? 0);
            $pct   = min(100, round(($avg / 5) * 100));
            $stat  = \local_skillgap_get_status($avg);
            $badge = ['competent' => 'success', 'moderate' => 'warning', 'needs_improvement' => 'danger'][$stat] ?? 'secondary';
            $total_readiness += $pct;

            // Per-competency scores for this member.
            $comp_scores_sql = "SELECT c.name AS compname, s.score, s.status
                                  FROM {local_skillgap_score} s
                                  JOIN {local_skillgap_comp} c ON c.id = s.compid
                                 WHERE s.userid = :uid
                                   AND s.timecreated = (
                                       SELECT MAX(s2.timecreated) FROM {local_skillgap_score} s2
                                        WHERE s2.userid = s.userid AND s2.compid = s.compid
                                   )
                                 ORDER BY c.name ASC";
            $comp_scores_raw = $DB->get_records_sql($comp_scores_sql, ['uid' => $u->id]);
            $member_comps = [];
            foreach ($comp_scores_raw as $cs) {
                $cb = ['competent' => 'success', 'moderate' => 'warning', 'needs_improvement' => 'danger'][$cs->status] ?? 'secondary';
                $member_comps[] = [
                    'compname'     => $cs->compname,
                    'score'        => min(5, round($cs->score, 1)) . '/5',
                    'status_label' => ucwords(str_replace('_', ' ', $cs->status)),
                    'badge_class'  => $cb,
                ];
            }

            $member_rows[] = [
                'userid'       => $u->id,
                'fullname'     => fullname($u),
                'email'        => $u->email,
                'avg_score'    => $pct,
                'status_label' => get_string('status_' . $stat, 'local_skillgap'),
                'badge_class'  => $badge,
                'member_comps' => $member_comps,
                'has_comps'    => !empty($member_comps),
                'comp_count'   => count($member_comps),
            ];
        }

        $team_count = count($member_rows);
        $team_readiness = $team_count > 0 ? round($total_readiness / $team_count) : 0;

        return [
            'fullname'         => fullname($DB->get_record('user', ['id' => $managerid])),
            'team_count'       => $team_count,
            'team_readiness'   => $team_readiness,
            'most_failing'     => $most_failing ?? '-',
            'most_improved'    => $most_improved ?? '-',
            'comp_report'      => $comp_report,
            'team_members'     => $member_rows,
            'no_team'          => false,
            'departments'      => self::get_dept_options($deptid),
            'designations'     => $deptid ? self::get_desig_options($deptid, $desigid) : [],
            'competencies'     => self::get_comp_options(0, 0, $compid),
            'wwwroot'          => $CFG->wwwroot,
            'sesskey'          => sesskey(),
            'userid'           => $managerid,
        ];
    }

    // ─── DEPARTMENTS ─────────────────────────────────────────────────

    public static function departments_data(): array {
        global $DB, $CFG;
        \local_skillgap_sync_departments();
        $depts = $DB->get_records('local_skillgap_dept', null, 'name ASC');
        $rows  = [];
        foreach ($depts as $d) {
            $user_count = $DB->count_records_sql(
                "SELECT COUNT(*) FROM {user} WHERE department = ? AND deleted = 0 AND suspended = 0",
                [$d->name]
            );
            $rows[] = [
                'id'         => $d->id,
                'name'       => $d->name,
                'user_count' => $user_count,
            ];
        }
        return ['departments' => $rows, 'wwwroot' => $CFG->wwwroot, 'sesskey' => sesskey()];
    }

    // ─── DESIGNATIONS ────────────────────────────────────────────────

    // ─── DESIGNATIONS (new: from user profiles) ─────────────────────────────

    /**
     * Designation data for the admin page.
     * Designation NAMES come from user profile field 'designation'.
     * Hierarchy configuration is stored in local_skillgap_desig_hierarchy.
     */
    public static function designations_data(int $deptid = 0): array {
        global $DB, $CFG;

        $depts     = $DB->get_records('local_skillgap_dept', null, 'name ASC', 'id,name');
        $dept_opts = [['id' => '', 'name' => '-- Select Department --', 'selected' => !$deptid]];
        foreach ($depts as $d) {
            $dept_opts[] = ['id' => $d->id, 'name' => $d->name, 'selected' => ($d->id == $deptid)];
        }

        $dept_name      = $deptid ? ($DB->get_field('local_skillgap_dept', 'name', ['id' => $deptid]) ?: '') : '';
        $hierarchy_rows = [];
        $tree_items     = [];
        $profile_names  = [];
        $available_names= [];

        if ($deptid && $dept_name) {
            $profile_names   = \local_skillgap\local\designation_service::fetch_from_profiles($dept_name);
            $flat_hierarchy  = \local_skillgap\local\designation_service::get_hierarchy($dept_name);
            $stored_names    = array_column((array)$flat_hierarchy, 'designation_name');
            $available_names = array_values(array_diff($profile_names, $stored_names));

            $total = count($flat_hierarchy);
            foreach ($flat_hierarchy as $idx => $h) {
                $parent_opts = [['val' => '', 'label' => '-- No parent (top level) --', 'selected' => empty($h->parent_designation)]];
                foreach ($flat_hierarchy as $opt) {
                    if ($opt->id === $h->id) { continue; }
                    $parent_opts[] = [
                        'val'      => $opt->designation_name,
                        'label'    => $opt->designation_name,
                        'selected' => ($opt->designation_name === $h->parent_designation),
                    ];
                }
                $hierarchy_rows[] = [
                    'id'               => $h->id,
                    'designation_name' => $h->designation_name,
                    'parent_display'   => $h->parent_designation ?: '— (Top Level)',
                    'has_parent'       => !empty($h->parent_designation),
                    'sort_order'       => $h->sort_order,
                    'position'         => $idx + 1,
                    'can_move_up'      => $idx > 0,
                    'can_move_down'    => $idx < $total - 1,
                    'parent_opts'      => $parent_opts,
                    'sesskey'          => sesskey(),
                    'deptid'           => $deptid,
                ];
            }

            $tree  = \local_skillgap\local\designation_service::build_tree($dept_name);
            $flat  = \local_skillgap\local\designation_service::flatten_tree($tree);
            foreach ($flat as $item) {
                $tree_items[] = [
                    'id'       => $item['id'],
                    'name'     => $item['designation_name'],
                    'depth'    => $item['depth'],
                    'is_child' => $item['depth'] > 0,
                    'prefix'   => str_repeat('&nbsp;&nbsp;&nbsp;', $item['depth']) . ($item['depth'] > 0 ? '&#x2514; ' : ''),
                ];
            }
        }

        return [
            'dept_options'    => $dept_opts,
            'selected_deptid' => $deptid,
            'dept_name'       => $dept_name,
            'has_dept'        => $deptid > 0,
            'hierarchy_rows'  => $hierarchy_rows,
            'has_rows'        => !empty($hierarchy_rows),
            'tree_items'      => $tree_items,
            'has_tree'        => !empty($tree_items),
            'profile_count'   => count($profile_names),
            'profile_names'   => array_map(fn($n) => ['name' => $n], $profile_names),
            'has_unsynced'    => !empty($available_names),
            'unsynced_count'  => count($available_names),
            'available_names' => array_map(fn($n) => ['name' => $n], $available_names),
            'wwwroot'         => $CFG->wwwroot,
            'sesskey'         => sesskey(),
        ];
    }

    public static function competencies_data(int $deptid = 0, int $desigid = 0): array {
        global $DB, $CFG;

        $dept_options  = self::get_dept_options($deptid);
        $desig_options = $deptid ? self::get_desig_options($deptid, $desigid) : [];

        $comp_rows = [];
        if ($deptid && $desigid) {
            $comps = $DB->get_records('local_skillgap_comp',
                ['deptid' => $deptid, 'desigid' => $desigid], 'name ASC');

            // All courses for mapping dropdown (fetched once).
            $all_courses = $DB->get_records('course', ['visible' => 1], 'fullname ASC', 'id,fullname');
            $course_options_shared = [];
            foreach ($all_courses as $co) {
                if ($co->id == SITEID) { continue; }
                $course_options_shared[] = ['id' => $co->id, 'name' => $co->fullname];
            }

            $sesskey_val = sesskey();

            foreach ($comps as $c) {
                // Count ONLY main-level questions for the badge (subcompid IS NULL or 0).
                $q_count   = (int)$DB->count_records_select('local_skillgap_question',
                    'compid = :cid AND (subcompid IS NULL OR subcompid = 0)', ['cid' => $c->id]);
                $sub_count = $DB->count_records('local_skillgap_subcomp',  ['compid' => $c->id]);

                // Mapped courses.
                $courses = $DB->get_records_sql(
                    "SELECT co.id, co.fullname FROM {course} co
                      JOIN {local_skillgap_comp_course} cc ON cc.courseid = co.id
                     WHERE cc.compid = :cid", ['cid' => $c->id]
                );
                $mapped_courses = [];
                foreach ($courses as $co) {
                    $mapped_courses[] = [
                        'courseid'        => $co->id,
                        'coursename'      => $co->fullname,
                        'compid_val'      => $c->id,
                        'sesskey_val'     => $sesskey_val,
                        'selected_deptid' => $deptid,
                        'selected_desigid'=> $desigid,
                    ];
                }

                // ── Build full sub/sub-sub hierarchy tree ──────────────────
                // Load all sub-competencies for this comp at once.
                $all_subs = $DB->get_records('local_skillgap_subcomp',
                    ['compid' => $c->id], 'name ASC');

                // Separate top-level (parentsubid IS NULL) and children.
                $top_subs  = [];
                $child_map = []; // parentsubid => [sub records]
                foreach ($all_subs as $s) {
                    if (empty($s->parentsubid)) {
                        $top_subs[] = $s;
                    } else {
                        $child_map[$s->parentsubid][] = $s;
                    }
                }

                $sub_trees = [];
                foreach ($top_subs as $s) {
                    // Mapped courses for this sub-competency.
                    $sub_courses_raw = $DB->get_records_sql(
                        "SELECT co.id, co.fullname FROM {course} co
                          JOIN {local_skillgap_subcomp_course} sc ON sc.courseid = co.id
                         WHERE sc.subcompid = :sid", ['sid' => $s->id]
                    );
                    $sub_mapped_courses = [];
                    foreach ($sub_courses_raw as $sco) {
                        $sub_mapped_courses[] = [
                            'courseid'        => $sco->id,
                            'coursename'      => $sco->fullname,
                            'subcompid_val'   => $s->id,
                            'sesskey_val'     => $sesskey_val,
                            'selected_deptid' => $deptid,
                            'selected_desigid'=> $desigid,
                        ];
                    }
                    $sub_q_count = (int)$DB->count_records('local_skillgap_question',
                        ['compid' => $c->id, 'subcompid' => $s->id]);

                    // Sub-sub list.
                    $sub_subs = [];
                    foreach ($child_map[$s->id] ?? [] as $ss) {
                        // Mapped courses for this sub-sub-competency.
                        $subsub_courses_raw = $DB->get_records_sql(
                            "SELECT co.id, co.fullname FROM {course} co
                              JOIN {local_skillgap_subcomp_course} sc ON sc.courseid = co.id
                             WHERE sc.subcompid = :sid", ['sid' => $ss->id]
                        );
                        $subsub_mapped_courses = [];
                        foreach ($subsub_courses_raw as $ssco) {
                            $subsub_mapped_courses[] = [
                                'courseid'        => $ssco->id,
                                'coursename'      => $ssco->fullname,
                                'subcompid_val'   => $ss->id,
                                'sesskey_val'     => $sesskey_val,
                                'selected_deptid' => $deptid,
                                'selected_desigid'=> $desigid,
                            ];
                        }
                        $subsub_q_count = (int)$DB->count_records('local_skillgap_question',
                            ['compid' => $c->id, 'subcompid' => $ss->id]);

                        $sub_subs[] = [
                            'subsub_id'             => $ss->id,
                            'subsub_name'           => $ss->name,
                            'parent_sub_id'         => $s->id,
                            'comp_id'               => $c->id,
                            'deptid'                => $deptid,
                            'desigid'               => $desigid,
                            'sesskey'               => $sesskey_val,
                            'subsub_q_count'        => $subsub_q_count,
                            'subsub_mapped_courses' => $subsub_mapped_courses,
                            'subsub_has_courses'    => !empty($subsub_mapped_courses),
                            'course_options'        => $course_options_shared,
                        ];
                    }
                    $sub_trees[] = [
                        'sub_id'            => $s->id,
                        'sub_name'          => $s->name,
                        'comp_id'           => $c->id,
                        'deptid'            => $deptid,
                        'desigid'           => $desigid,
                        'sesskey'           => $sesskey_val,
                        'sub_subs'          => $sub_subs,
                        'has_subs'          => !empty($sub_subs),
                        'sub_q_count'       => $sub_q_count,
                        'sub_mapped_courses'=> $sub_mapped_courses,
                        'sub_has_courses'   => !empty($sub_mapped_courses),
                        'course_options'    => $course_options_shared,
                    ];
                }
                // ──────────────────────────────────────────────────────────

                $comp_rows[] = [
                    'id'              => $c->id,
                    'name'            => $c->name,
                    'description'     => $c->description ?? '',
                    'q_count'         => $q_count,
                    'sub_count'       => $sub_count,
                    'has_5_questions' => $q_count >= 5,
                    'mapped_courses'  => $mapped_courses,
                    'has_courses'     => !empty($mapped_courses),
                    'sub_trees'       => $sub_trees,
                    'has_sub_trees'   => !empty($sub_trees),
                    'course_options'  => $course_options_shared,
                    'selected_deptid' => $deptid,
                    'selected_desigid'=> $desigid,
                    'sesskey'         => $sesskey_val,
                ];
            }
        }

        // All courses for mapping dropdown (needed at top level too).
        $all_courses = $DB->get_records('course', ['visible' => 1], 'fullname ASC', 'id,fullname');
        $course_options = [];
        foreach ($all_courses as $co) {
            if ($co->id == SITEID) { continue; }
            $course_options[] = ['id' => $co->id, 'name' => $co->fullname];
        }

        return [
            'dept_options'    => $dept_options,
            'desig_options'   => $desig_options,
            'selected_deptid' => $deptid,
            'selected_desigid'=> $desigid,
            'has_filter'      => $deptid > 0 && $desigid > 0,
            'comp_rows'       => $comp_rows,
            'course_options'  => $course_options,
            'wwwroot'         => $CFG->wwwroot,
            'sesskey'         => sesskey(),
        ];
    }

    // ─── QUESTIONS ───────────────────────────────────────────────────

    public static function questions_data(int $compid = 0, int $assessmentid = 0, int $subcompid_sel = 0): array {
        global $DB, $CFG;

        // All competencies for dropdown — includes main + sub + sub-sub with visual indentation.
        $comps     = $DB->get_records('local_skillgap_comp', null, 'name ASC', 'id,name');
        $comp_opts = [['id' => '', 'name' => '-- Select Competency --', 'selected' => (!$compid && !$subcompid_sel)]];
        foreach ($comps as $c) {
            $comp_opts[] = ['id' => $c->id, 'name' => $c->name, 'selected' => ($c->id == $compid && !$subcompid_sel)];

            // Sub-competencies under each main comp.
            $all_subs  = $DB->get_records('local_skillgap_subcomp', ['compid' => $c->id], 'name ASC');
            $top_subs  = [];
            $child_map = [];
            foreach ($all_subs as $s) {
                if (empty($s->parentsubid)) { $top_subs[] = $s; }
                else { $child_map[$s->parentsubid][] = $s; }
            }
            foreach ($top_subs as $s) {
                $comp_opts[] = [
                    'id'     => $c->id . ':' . $s->id,
                    'name'   => '  ↳ ' . $s->name,
                    'selected' => ($c->id == $compid && $subcompid_sel == $s->id),
                ];
                foreach ($child_map[$s->id] ?? [] as $ss) {
                    $comp_opts[] = [
                        'id'     => $c->id . ':' . $ss->id,
                        'name'   => '    ↳↳ ' . $ss->name,
                        'selected' => ($c->id == $compid && $subcompid_sel == $ss->id),
                    ];
                }
            }
        }

        $question_rows = [];
        $q_count       = 0;
        if ($compid) {
            // Only fetch questions that belong to main competency (subcompid IS NULL or 0).
            $questions = $DB->get_records_select('local_skillgap_question',
                'compid = :cid AND (subcompid IS NULL OR subcompid = 0)',
                ['cid' => $compid], 'timecreated ASC');
            $q_count   = count($questions);
            $num       = 1;
            foreach ($questions as $q) {
                $options = $DB->get_records('local_skillgap_qoption', ['questionid' => $q->id]);
                $opt_rows = [];
                foreach ($options as $o) {
                    $opt_rows[] = [
                        'id'        => $o->id,
                        'text'      => $o->optiontext,
                        'iscorrect' => (bool)$o->iscorrect,
                    ];
                }
                $opt_letters  = ['A', 'B', 'C', 'D'];
                $question_rows[] = [
                    'id'            => $q->id,
                    'num'           => $num++,
                    'questiontext'  => $q->questiontext,
                    'source'        => strtoupper($q->source),
                    'source_badge'  => $q->source === 'ai' ? 'info' : ($q->source === 'csv' ? 'secondary' : 'primary'),
                    'options'       => $opt_rows,
                    'can_delete'    => true,
                    // For edit form: individual option texts and correct index.
                    'opt_a'         => $opt_rows[0]['text'] ?? '',
                    'opt_b'         => $opt_rows[1]['text'] ?? '',
                    'opt_c'         => $opt_rows[2]['text'] ?? '',
                    'opt_d'         => $opt_rows[3]['text'] ?? '',
                    'correct_0'     => isset($opt_rows[0]['iscorrect']) && $opt_rows[0]['iscorrect'],
                    'correct_1'     => isset($opt_rows[1]['iscorrect']) && $opt_rows[1]['iscorrect'],
                    'correct_2'     => isset($opt_rows[2]['iscorrect']) && $opt_rows[2]['iscorrect'],
                    'correct_3'     => isset($opt_rows[3]['iscorrect']) && $opt_rows[3]['iscorrect'],
                ];
            }
        }

        // Build list of competencies for this assessment (if assessmentid given).
        // Now supports main/sub/subsub entries.
        $assess_comps = [];
        if ($assessmentid) {
            $acs = $DB->get_records('local_skillgap_assess_comp', ['assessmentid' => $assessmentid]);
            foreach ($acs as $ac) {
                $comp_type = $ac->comp_type ?? 'main';
                $subcompid = !empty($ac->subcompid) ? (int)$ac->subcompid : null;

                if ($comp_type === 'main' || !$subcompid) {
                    $name  = $DB->get_field('local_skillgap_comp', 'name', ['id' => $ac->compid]) ?: '?';
                    $qc    = (int)$DB->count_records_select('local_skillgap_question',
                        'compid = :cid AND (subcompid IS NULL OR subcompid = 0)', ['cid' => $ac->compid]);
                    $url   = (new \moodle_url('/local/skillgap/admin/questions.php',
                        ['assessmentid' => $assessmentid, 'compid' => $ac->compid]))->out(false);
                    $is_sel = ($ac->compid == $compid && !$subcompid);
                } else {
                    $name  = $DB->get_field('local_skillgap_subcomp', 'name', ['id' => $subcompid]) ?: '?';
                    $qc    = (int)$DB->count_records('local_skillgap_question',
                        ['compid' => $ac->compid, 'subcompid' => $subcompid]);
                    $url   = (new \moodle_url('/local/skillgap/admin/subcomp_questions.php',
                        ['assessmentid' => $assessmentid, 'compid' => $ac->compid,
                         'subcompid' => $subcompid]))->out(false);
                    $is_sel = false; // sub-comp questions have their own page
                }

                $level_labels = ['main' => '', 'sub' => '↳ ', 'subsub' => '&nbsp;&nbsp;↳ '];
                $assess_comps[] = [
                    'id'              => $ac->compid,
                    'name'            => $name,
                    'level_prefix'    => $level_labels[$comp_type] ?? '',
                    'comp_type'       => $comp_type,
                    'is_sub'          => $comp_type !== 'main',
                    'q_count'         => $qc,
                    'q_pct'           => intval($qc / 5 * 100),
                    'has_5_questions' => $qc >= 5,
                    'is_selected'     => $is_sel,
                    'url'             => $url,
                ];
            }
        }
        // Assessment title for breadcrumb context
        $assess_title = '';
        if ($assessmentid) {
            $assess_title = $DB->get_field('local_skillgap_assessment', 'title', ['id' => $assessmentid]) ?: '';
        }

        return [
            'comp_options'      => $comp_opts,
            'selected_compid'   => $compid,
            'selected_assessmentid' => $assessmentid,
            'has_comp'          => $compid > 0,
            'has_assessment'    => $assessmentid > 0,
            'question_rows'     => $question_rows,
            'q_count'           => $q_count,
            'can_add_more'      => $q_count < 5,
            'remaining'         => 5 - $q_count,
            'q_pct'             => intval($q_count / 5 * 100),  // e.g. 5→100, 3→60, 0→0
            'comp_name'         => $compid ? $DB->get_field('local_skillgap_comp', 'name', ['id' => $compid]) : '',
            'assess_title'      => $assess_title,
            'assess_comps'      => $assess_comps,
            'has_assess_comps'  => !empty($assess_comps),
            'wwwroot'           => $CFG->wwwroot,
            'sesskey'           => sesskey(),
        ];
    }

    // ─── ASSESSMENTS ─────────────────────────────────────────────────

    public static function assessments_data(): array {
        global $DB, $CFG;

        $list = $DB->get_records_sql(
            "SELECT a.id, a.title, a.frequency, a.status, a.startdate, a.enddate,
                    d.name AS dept_name, des.designation_name AS desig_name
               FROM {local_skillgap_assessment} a
               JOIN {local_skillgap_dept}              d   ON d.id   = a.deptid
               JOIN {local_skillgap_desig_hierarchy}   des ON des.id = a.desigid
              ORDER BY a.timecreated DESC"
        );

        $rows = [];
        foreach ($list as $a) {
            // Fetch all assess_comp entries — may be main, sub, or subsub.
            $acs = $DB->get_records('local_skillgap_assess_comp', ['assessmentid' => $a->id]);

            $comp_names   = [];
            $comp_details = [];
            $total_q      = 0;
            $all_have_5q  = true;

            foreach ($acs as $ac) {
                $comp_type  = (!empty($ac->comp_type) && $ac->comp_type !== '')
                    ? $ac->comp_type : 'main';
                $subcompid  = (!empty($ac->subcompid) && (int)$ac->subcompid > 0)
                    ? (int)$ac->subcompid : null;

                // Resolve display name.
                if ($comp_type === 'main') {
                    $name  = $DB->get_field('local_skillgap_comp', 'name', ['id' => $ac->compid]) ?: '?';
                    $qc    = (int)$DB->count_records_select('local_skillgap_question',
                        'compid = :cid AND (subcompid IS NULL OR subcompid = 0)',
                        ['cid' => $ac->compid]);
                } else {
                    // sub or subsub — resolve name from subcomp table.
                    $name  = $subcompid
                        ? ($DB->get_field('local_skillgap_subcomp', 'name', ['id' => $subcompid]) ?: '?')
                        : '?';
                    $qc    = $subcompid
                        ? (int)$DB->count_records('local_skillgap_question',
                            ['compid' => $ac->compid, 'subcompid' => $subcompid])
                        : 0;
                }

                $level_label = ['main' => 'Main', 'sub' => 'Sub', 'subsub' => 'Sub-Sub'][$comp_type] ?? 'Main';
                $comp_names[]   = $name;
                $total_q       += $qc;
                if ($qc < 5) { $all_have_5q = false; }
                $comp_details[] = [
                    'cname'      => $name,
                    'q_count'    => $qc,
                    'has_5'      => $qc >= 5,
                    'level'      => $level_label,
                    'level_color'=> ['Main' => '#033252', 'Sub' => '#eb980d', 'Sub-Sub' => '#ffc107'][$level_label] ?? '#333',
                    // Used for edit pre-selection.
                    'compid'     => $ac->compid,
                    'subcompid'  => $subcompid ?? 0,
                    'comp_type'  => $comp_type,
                ];
            }
            $comp_count = count($acs);

            $rows[] = [
                'id'               => $a->id,
                'title'            => $a->title,
                'dept_name'        => $a->dept_name,
                'desig_name'       => $a->desig_name,
                'frequency'        => ucfirst(str_replace('_', '-', $a->frequency)),
                'comp_count'       => $comp_count,
                'comp_names_str'   => implode(', ', $comp_names),
                'comp_details'     => $comp_details,
                'total_questions'  => $total_q,
                'all_have_5q'      => $all_have_5q,
                'status'           => $a->status,
                'status_label'     => $a->status === 'draft' ? 'Draft' : ucfirst($a->status),
                'status_badge'     => ['active' => 'success', 'draft' => 'secondary', 'pending' => 'warning', 'overdue' => 'danger'][$a->status] ?? 'secondary',
                'is_draft'         => $a->status === 'draft',
                'is_active'        => $a->status === 'active',
                'can_publish'      => $a->status !== 'active' && $all_have_5q,
                'startdate'        => $a->startdate ? userdate($a->startdate, '%d %b %Y') : '-',
                'enddate'          => $a->enddate   ? userdate($a->enddate,   '%d %b %Y') : '-',
                // Edit form fields.
                'startdate_val'    => $a->startdate ? date('Y-m-d', $a->startdate) : '',
                'enddate_val'      => $a->enddate   ? date('Y-m-d', $a->enddate)   : '',
                'deptid_val'       => $a->deptid    ?? 0,
                'desigid_val'      => $a->desigid   ?? 0,
                'frequency_val'    => $a->frequency ?? 'quarterly',
                'title_val'        => $a->title,
                // JSON blob used by Edit button JS to pre-check competencies.
                'compids_json'     => json_encode(array_map(function($cd) {
                    return ['compid' => $cd['compid'], 'subcompid' => $cd['subcompid'], 'type' => $cd['comp_type']];
                }, $comp_details)),
                'sesskey'          => sesskey(),
            ];
        }

        return [
            'assessments'     => $rows,
            'dept_options'    => self::get_dept_options(),
            'desig_options'   => [],
            'wwwroot'         => $CFG->wwwroot,
            'sesskey'         => sesskey(),
        ];
    }

    // ─── TAKE ASSESSMENT ─────────────────────────────────────────────

    public static function take_assessment_data(int $assessmentid, int $userid): array {
        global $DB, $CFG;

        $assessment = $DB->get_record('local_skillgap_assessment', ['id' => $assessmentid], '*', MUST_EXIST);

        // Get or create an attempt.
        $attempt = $DB->get_record('local_skillgap_attempt', [
            'userid'       => $userid,
            'assessmentid' => $assessmentid,
            'status'       => 'inprogress',
        ]);
        if (!$attempt) {
            $attempt_num = $DB->count_records('local_skillgap_attempt', [
                'userid'       => $userid,
                'assessmentid' => $assessmentid,
            ]) + 1;
            $attempt_id = $DB->insert_record('local_skillgap_attempt', [
                'userid'       => $userid,
                'assessmentid' => $assessmentid,
                'attemptnum'   => $attempt_num,
                'status'       => 'inprogress',
                'timecreated'  => time(),
            ]);
            $attempt = $DB->get_record('local_skillgap_attempt', ['id' => $attempt_id]);
        }

        // Determine which competencies to show (adaptive: only weak ones for retry).
        $is_adaptive = $attempt->attemptnum > 1;
        // Fetch all assess_comp entries (supports main/sub/subsub since v4.0).
        $acs = $DB->get_records('local_skillgap_assess_comp', ['assessmentid' => $assessmentid]);

        // For adaptive: still use compid-based filtering for now (legacy).
        $all_comp_ids = array_column((array)$acs, 'compid');
        if ($is_adaptive) {
            $weak_acs = [];
            foreach ($acs as $ac) {
                $latest = \local_skillgap_latest_score($userid, $ac->compid);
                if (!$latest || $latest->status !== 'competent') {
                    $weak_acs[] = $ac;
                }
            }
            $active_acs = $weak_acs ?: array_values($acs);
        } else {
            $active_acs = array_values($acs);
        }

        // Build question blocks per assess_comp entry.
        // comp_type may be NULL for assessments created before v4.0 — treat as 'main'.
        $comp_blocks = [];
        $total_q     = 0;
        foreach ($active_acs as $ac) {
            $comp_type = (!empty($ac->comp_type) && $ac->comp_type !== '')
                ? $ac->comp_type : 'main';
            $subcompid = (!empty($ac->subcompid) && (int)$ac->subcompid > 0)
                ? (int)$ac->subcompid : null;

            if ($comp_type === 'main' || !$subcompid) {
                $comp_name = $DB->get_field('local_skillgap_comp', 'name', ['id' => $ac->compid]) ?: '?';
                $questions = $DB->get_records_select('local_skillgap_question',
                    'compid = :cid AND (subcompid IS NULL OR subcompid = 0)',
                    ['cid' => $ac->compid], 'RAND()');
            } else {
                $comp_name = $DB->get_field('local_skillgap_subcomp', 'name', ['id' => $subcompid]) ?: '?';
                $questions = $DB->get_records('local_skillgap_question',
                    ['compid' => $ac->compid, 'subcompid' => $subcompid], 'RAND()');
            }

            $q_rows  = [];
            $letters = ['A', 'B', 'C', 'D'];
            foreach ($questions as $q) {
                $options  = $DB->get_records('local_skillgap_qoption', ['questionid' => $q->id]);
                // Shuffle options so the correct answer is not always in position A.
                $options_arr = array_values($options);
                shuffle($options_arr);
                $opt_rows = [];
                $i        = 0;
                foreach ($options_arr as $o) {
                    $opt_rows[] = [
                        'id'         => $o->id,
                        'letter'     => $letters[$i] ?? chr(65 + $i),
                        'text'       => $o->optiontext,
                        'questionid' => $q->id,
                        'attemptid'  => $attempt->id,
                    ];
                    $i++;
                }
                $level_labels = ['main' => 'Main Competency', 'sub' => 'Sub-Competency', 'subsub' => 'Sub-Sub Competency'];
                $q_rows[] = [
                    'id'            => $q->id,
                    'text'          => $q->questiontext,
                    'options'       => $opt_rows,
                    'compid'        => $ac->compid,
                    'subcompid'     => $subcompid,
                    'comp_name'     => $comp_name,
                    'comp_type'     => $level_labels[$comp_type] ?? 'Main Competency',
                    'has_comp_type' => $comp_type !== 'main',
                    'attemptid'     => $attempt->id,
                ];
                $total_q++;
            }

            if (!empty($q_rows)) {
                $type_label = ['main' => 'Main Competency', 'sub' => 'Sub-Competency', 'subsub' => 'Sub-Sub Competency'][$comp_type] ?? 'Main';
                $comp_blocks[] = [
                    'compid'          => $ac->compid,
                    'subcompid'       => $subcompid,
                    'compname'        => $comp_name,
                    'comp_type'       => $comp_type,
                    'comp_type_label' => $type_label,
                    'is_sub_level'    => $comp_type !== 'main',
                    'questions'       => $q_rows,
                ];
            }
        }

        return [
            'attemptid'         => $attempt->id,
            'assessmentid'      => $assessmentid,
            'assessment_title'  => $assessment->title,
            'userid'            => $userid,
            'is_adaptive'       => $is_adaptive,
            'comp_blocks'       => $comp_blocks,
            'total_q'           => $total_q,
            'wwwroot'           => $CFG->wwwroot,
            'sesskey'           => sesskey(),
        ];
    }

    // ─── COMPETENCY ANALYTICS DASHBOARD ─────────────────────────────

    public static function competency_analytics_data(int $compid = 0): array {
        global $DB, $CFG;

        // ── Summary table of all main competencies ──────────────────────
        $comps = $DB->get_records('local_skillgap_comp', null, 'name ASC');
        $comp_rows = [];
        foreach ($comps as $c) {
            $q_main  = (int)$DB->count_records_select('local_skillgap_question',
                'compid = :cid AND (subcompid IS NULL OR subcompid = 0)', ['cid' => $c->id]);
            $q_sub   = (int)$DB->count_records_select('local_skillgap_question',
                'compid = :cid AND subcompid IS NOT NULL AND subcompid > 0', ['cid' => $c->id]);
            $q_total = $q_main + $q_sub;
            $sub_count = (int)$DB->count_records('local_skillgap_subcomp', ['compid' => $c->id]);

            // Assessments that include this competency.
            $assess_count = (int)$DB->count_records('local_skillgap_assess_comp', ['compid' => $c->id]);

            // Unique users who attempted assessments with this competency.
                   $user_count = (int)$DB->count_records_sql(
            "SELECT COUNT(DISTINCT att.userid)
               FROM {local_skillgap_attempt} att
               JOIN {local_skillgap_assessment} a ON a.id = att.assessmentid
               JOIN {local_skillgap_assess_comp} ac ON ac.assessmentid = a.id
               JOIN {user} u ON u.id = att.userid AND u.deleted = 0 AND u.suspended = 0
              WHERE ac.compid = :cid AND att.status = 'completed'",
            ['cid' => $c->id]
        );

        // Average score for this competency.
        $avg_score = (float)($DB->get_field_sql(
            "SELECT AVG(score) FROM {local_skillgap_score}
              WHERE compid = ?
                AND userid IN (SELECT id FROM {user} WHERE deleted = 0 AND suspended = 0)", [$c->id]
        ) ?? 0);
            $avg_pct    = $avg_score > 0 ? min(100, round(($avg_score / 5) * 100)) : 0;
            $score_class = $avg_pct >= 80 ? 'success' : ($avg_pct >= 60 ? 'warning' : 'danger');

            $comp_rows[] = [
                'id'           => $c->id,
                'name'         => $c->name,
                'type'         => 'Main',
                'status'       => $q_main >= 5 ? 'Ready' : 'Incomplete',
                'status_badge' => $q_main >= 5 ? 'success' : 'warning',
                'is_ready'     => ($q_main >= 5),
                'q_total'      => $q_total,
                'q_main'       => $q_main,
                'sub_count'    => $sub_count,
                'assess_count' => $assess_count,
                'user_count'   => $user_count,
                'avg_pct'      => $avg_pct,
                'score_class'  => $score_class,
                'score_green'  => ($avg_pct >= 80),
                'score_yellow' => ($avg_pct >= 60 && $avg_pct < 80),
                'score_red'    => ($avg_pct < 60),
                'is_selected'  => ($c->id === $compid),
            ];
        }

        // ── Detail view for selected competency ─────────────────────────
        $detail   = null;
        $user_rows = [];
        if ($compid) {
            $comp = $DB->get_record('local_skillgap_comp', ['id' => $compid]);
            if ($comp) {
                $q_main  = (int)$DB->count_records_select('local_skillgap_question',
                    'compid = :cid AND (subcompid IS NULL OR subcompid = 0)', ['cid' => $compid]);
                $q_sub   = (int)$DB->count_records_select('local_skillgap_question',
                    'compid = :cid AND subcompid IS NOT NULL AND subcompid > 0', ['cid' => $compid]);
                $assess_count = (int)$DB->count_records('local_skillgap_assess_comp', ['compid' => $compid]);
                           $user_count   = (int)$DB->count_records_sql(
                "SELECT COUNT(DISTINCT att.userid)
                   FROM {local_skillgap_attempt} att
                   JOIN {local_skillgap_assessment} a ON a.id = att.assessmentid
                   JOIN {local_skillgap_assess_comp} ac ON ac.assessmentid = a.id
                   JOIN {user} u ON u.id = att.userid AND u.deleted = 0 AND u.suspended = 0
                  WHERE ac.compid = :cid AND att.status = 'completed'",
                ['cid' => $compid]
            );
                $completion_pct = $user_count > 0
                    ? round(($user_count / max(1, $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]) - 1)) * 100)
                    : 0;

                // Sub-competency breakdown.
                $subs    = $DB->get_records('local_skillgap_subcomp', ['compid' => $compid, 'parentsubid' => null], 'name ASC');
                $sub_analytics = [];
                foreach ($subs as $s) {
                    $sq = (int)$DB->count_records('local_skillgap_question', ['compid' => $compid, 'subcompid' => $s->id]);
                    $sub_analytics[] = ['name' => $s->name, 'q_count' => $sq, 'ready' => $sq >= 5];
                }

                $detail = [
                    'id'             => $comp->id,
                    'name'           => $comp->name,
                    'q_main'         => $q_main,
                    'q_sub'          => $q_sub,
                    'q_total'        => $q_main + $q_sub,
                    'assess_count'   => $assess_count,
                    'user_count'     => $user_count,
                    'completion_pct' => $completion_pct,
                    'sub_analytics'  => $sub_analytics,
                ];

                // Per-user performance for this competency.
                $attempts = $DB->get_records_sql(
                    "SELECT DISTINCT att.userid, u.firstname, u.lastname, u.email,
                            COUNT(att.id) AS attempt_count,
                            MAX(CASE WHEN att.status = 'completed' THEN 1 ELSE 0 END) AS has_completed
                                         FROM {local_skillgap_attempt} att
                   JOIN {user} u ON u.id = att.userid AND u.deleted = 0 AND u.suspended = 0
                   JOIN {local_skillgap_assessment} a ON a.id = att.assessmentid
                   JOIN {local_skillgap_assess_comp} ac ON ac.assessmentid = a.id
                  WHERE ac.compid = :cid
                  GROUP BY att.userid, u.firstname, u.lastname, u.email
                      ORDER BY u.lastname ASC, u.firstname ASC
                      LIMIT 50",
                    ['cid' => $compid]
                );
                $rnum = 1;
                foreach ($attempts as $att) {
                    $score = $DB->get_record_sql(
                        "SELECT score, status FROM {local_skillgap_score}
                          WHERE userid = :uid AND compid = :cid
                          ORDER BY id DESC LIMIT 1",
                        ['uid' => $att->userid, 'cid' => $compid]
                    );
                    $score_pct = $score ? round(($score->score / 5) * 100) : 0;
                    $user_rows[] = [
                        'num'          => $rnum++,
                        'fullname'     => fullname((object)['firstname' => $att->firstname, 'lastname' => $att->lastname]),
                        'email'        => $att->email,
                        'attempts'     => (int)$att->attempt_count,
                        'completed'    => (bool)$att->has_completed,
                        'score_pct'    => $score_pct,
                        'score_label'  => $score ? round($score->score, 1) . '/5' : 'N/A',
                        'score_class'  => $score_pct >= 80 ? 'success' : ($score_pct >= 60 ? 'warning' : 'danger'),
                        'status_label' => $score ? ucfirst(str_replace('_', ' ', $score->status)) : 'Not attempted',
                        'has_score'    => !empty($score),
                    ];
                }
            }
        }

        // ── Summary stat cards (new UI) ────────────────────────────────
        $total_comps_count = count($comp_rows);
        $ready_count = count(array_filter($comp_rows, fn($r) => $r['status'] === 'Ready'));
           $active_users_count = (int)$DB->count_records_sql(
        "SELECT COUNT(DISTINCT userid) FROM {local_skillgap_score}
          WHERE userid IN (SELECT id FROM {user} WHERE deleted = 0 AND suspended = 0)"
    );
        $all_avg = 0;
        $all_avg_cnt = 0;
        foreach ($comp_rows as $cr) {
            if ($cr['avg_pct'] > 0) { $all_avg += $cr['avg_pct']; $all_avg_cnt++; }
        }
        $site_avg_pct = $all_avg_cnt > 0 ? round($all_avg / $all_avg_cnt) : 0;

        return [
            'comp_rows'          => $comp_rows,
            'has_comps'          => !empty($comp_rows),
            'detail'             => $detail,
            'has_detail'         => !empty($detail),
            'user_rows'          => $user_rows,
            'has_users'          => !empty($user_rows),
            'selected_compid'    => $compid,
            'total_comps_count'  => $total_comps_count,
            'ready_count'        => $ready_count,
            'active_users_count' => $active_users_count,
            'site_avg_pct'       => $site_avg_pct,
            'wwwroot'            => $CFG->wwwroot,
            'sesskey'            => sesskey(),
        ];
    }

    // ─── CLUSTERS ────────────────────────────────────────────────────

    public static function clusters_data(): array {
        global $DB, $CFG;

        $clusters = $DB->get_records('local_skillgap_cluster', null, 'name ASC');
        $rows     = [];
        foreach ($clusters as $cl) {
            $comp_ids = $DB->get_fieldset_select(
                'local_skillgap_cluster_comp', 'compid', 'clusterid = :cid', ['cid' => $cl->id]
            );
            // Cluster score = avg of latest scores for all competencies.
            $score_sum = 0;
            $score_cnt = 0;
            $comp_names = [];
            foreach ($comp_ids as $cid) {
                $comp_names[] = $DB->get_field('local_skillgap_comp', 'name', ['id' => $cid]);
                             $avg_cid = (float)($DB->get_field_sql(
                    "SELECT AVG(score) FROM {local_skillgap_score}
                      WHERE compid = ?
                        AND userid IN (SELECT id FROM {user} WHERE deleted = 0 AND suspended = 0)", [$cid]
                ) ?? 0);
                if ($avg_cid > 0) {
                    $score_sum += ($avg_cid / 5) * 100;
                    $score_cnt++;
                }
            }
            $cluster_score = $score_cnt > 0 ? round($score_sum / $score_cnt) : 0;
            $cluster_status = $cluster_score >= 80 ? 'Strong' : ($cluster_score >= 60 ? 'Moderate' : 'Weak');
            $cluster_badge  = $cluster_score >= 80 ? 'success' : ($cluster_score >= 60 ? 'warning' : 'danger');

            $rows[] = [
                'id'           => $cl->id,
                'name'         => $cl->name,
                'description'  => $cl->description,
                'comp_count'   => count($comp_ids),
                'comp_names'   => implode(', ', array_filter($comp_names)),
                'cluster_score'=> $cluster_score,
                'cluster_status'=> $cluster_status,
                'cluster_badge'=> $cluster_badge,
            ];
        }

        // All competencies for mapping.
        $all_comps = $DB->get_records('local_skillgap_comp', null, 'name ASC', 'id,name');
        $comp_checkboxes = [];
        foreach ($all_comps as $c) {
            $comp_checkboxes[] = ['id' => $c->id, 'name' => $c->name];
        }

        return [
            'clusters'         => $rows,
            'comp_checkboxes'  => $comp_checkboxes,
            'wwwroot'          => $CFG->wwwroot,
            'sesskey'          => sesskey(),
        ];
    }

    // ─── PROMOTION ───────────────────────────────────────────────────

    public static function promotion_data(bool $short = false): array {
        global $DB, $CFG;

        // Find users who are competent in all competencies for their dept/desig.
        $users = $DB->get_records_sql(
            "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.department
               FROM {user} u
               JOIN {local_skillgap_score} s ON s.userid = u.id
              WHERE u.deleted = 0 AND u.suspended = 0
              ORDER BY u.lastname ASC"
        );

        $rows = [];
        foreach ($users as $u) {
            $dept_rec = $DB->get_record('local_skillgap_dept', ['name' => $u->department]);
            if (!$dept_rec) {
                continue;
            }

            // Resolve user's designation hierarchy ID.
            $p_desig_name = \local_skillgap\local\designation_service::get_user_designation($u->id);
            $p_desig_rec  = ($p_desig_name && $u->department)
                ? $DB->get_record('local_skillgap_desig_hierarchy',
                    ['designation_name' => $p_desig_name, 'department' => $u->department], 'id', IGNORE_MISSING)
                : null;
            $p_desigid = $p_desig_rec ? (int)$p_desig_rec->id : 0;

            // Scores for competencies mapped to this user's dept+desig active assessments only.
            $score_params = ['uid' => $u->id, 'deptid' => $dept_rec->id];
            $desig_clause = $p_desigid
                ? "AND EXISTS (
                       SELECT 1 FROM {local_skillgap_assess_comp} ac2
                         JOIN {local_skillgap_assessment} a2 ON a2.id = ac2.assessmentid
                        WHERE ac2.compid = s.compid AND a2.deptid = :deptid
                          AND a2.desigid = :desigid AND a2.status = 'active'
                   )"
                : "AND EXISTS (
                       SELECT 1 FROM {local_skillgap_assess_comp} ac2
                         JOIN {local_skillgap_assessment} a2 ON a2.id = ac2.assessmentid
                        WHERE ac2.compid = s.compid AND a2.deptid = :deptid AND a2.status = 'active'
                   )";
            if ($p_desigid) {
                $score_params['desigid'] = $p_desigid;
            }

            $scores = $DB->get_records_sql(
                "SELECT s.compid, s.status
                   FROM {local_skillgap_score} s
                  WHERE s.userid = :uid
                    AND s.timecreated = (
                        SELECT MAX(s2.timecreated) FROM {local_skillgap_score} s2
                         WHERE s2.userid = s.userid AND s2.compid = s.compid
                    )
                    $desig_clause", $score_params
            );

            if (empty($scores)) {
                continue;
            }

            // Fix 6: Check that the user has COMPLETED all active assessments for their dept/desig.
            $promo_desig_clause = $p_desigid
                ? "AND a.desigid = :desigid"
                : "";
            $promo_asmt_params = ['deptid' => $dept_rec->id];
            if ($p_desigid) { $promo_asmt_params['desigid'] = $p_desigid; }
            $total_active_assessments = (int)$DB->count_records_sql(
                "SELECT COUNT(*) FROM {local_skillgap_assessment} a
                  WHERE a.deptid = :deptid AND a.status = 'active' $promo_desig_clause",
                $promo_asmt_params
            );
            $completed_assessments_count = (int)$DB->count_records_sql(
                "SELECT COUNT(DISTINCT att.assessmentid)
                   FROM {local_skillgap_attempt} att
                   JOIN {local_skillgap_assessment} a ON a.id = att.assessmentid
                  WHERE att.userid = :uid AND att.status = 'completed'
                    AND a.deptid = :deptid AND a.status = 'active' $promo_desig_clause",
                array_merge(['uid' => $u->id], $promo_asmt_params)
            );
            $all_assessments_completed = ($total_active_assessments > 0 &&
                $completed_assessments_count >= $total_active_assessments);

            $total_comps      = count($scores);
            $competent_comps  = 0;
            foreach ($scores as $s) {
                if ($s->status === 'competent') {
                    $competent_comps++;
                }
            }
            $readiness_pct = round($competent_comps / $total_comps * 100);
            // Must have completed all assessments AND be competent in >= 80% of competencies.
            $is_ready      = $all_assessments_completed && $readiness_pct >= 80;
            $badge         = $is_ready ? 'success' : ($readiness_pct >= 50 ? 'warning' : 'danger');
            $label         = $is_ready
                ? 'Ready (' . $readiness_pct . '%)'
                : ($all_assessments_completed
                    ? 'Not Ready (' . $readiness_pct . '%)'
                    : 'Incomplete (' . $completed_assessments_count . '/' . $total_active_assessments . ' assessments)');

            if ($short && !$is_ready) {
                continue;
            }

            // Resolve current and next designation for this user.
            $cur_desig  = \local_skillgap\local\designation_service::get_user_designation($u->id);
            $nxt_desig  = ($cur_desig && $u->department)
                ? \local_skillgap\local\designation_service::get_next_designation($u->department, $cur_desig)
                : null;

            // A user can only be promoted if there IS a next designation AND all assessments completed.
            $can_promote = $is_ready && !empty($nxt_desig);

            // Check if already promoted recently (within 7 days) — suppress duplicate button.
            $last_promo = $DB->get_record_sql(
                "SELECT timepromoted FROM {local_skillgap_promotion}
                  WHERE userid = :uid ORDER BY timepromoted DESC LIMIT 1",
                ['uid' => $u->id]
            );
            $already_promoted_recently = $last_promo && (time() - (int)$last_promo->timepromoted < 7 * 86400);

            $rows[] = [
                'userid'          => $u->id,
                'fullname'        => fullname($u),
                'department'      => $u->department,
                'current_desig'   => $cur_desig ?: '—',
                'next_desig'      => $nxt_desig  ?: '—',
                'has_next_desig'  => !empty($nxt_desig),
                'readiness_pct'   => $readiness_pct,
                'readiness_label' => $label,
                'badge_class'     => $badge,
                'can_promote'     => $can_promote && !$already_promoted_recently,
                'already_promoted'=> $already_promoted_recently,
                'sesskey'         => sesskey(),
            ];
        }

        return [
            'promotion_users' => $rows,
            'has_promotions'  => !empty($rows),
            'wwwroot'         => $CFG->wwwroot,
            'sesskey'         => sesskey(),
        ];
    }

    // ─── WEAK ZONES ──────────────────────────────────────────────────


    // ─── ANALYTICS REPORT: per-competency user list (image 2) ──────────────────

    public static function analytics_report_data(int $compid): array {
        global $DB, $CFG;

        $comp = $DB->get_record('local_skillgap_comp', ['id' => $compid], '*', MUST_EXIST);

        // Summary stats.
           $scores = $DB->get_records_sql(
        "SELECT s.userid, MAX(s.score) AS score, MAX(s.status) AS status
           FROM {local_skillgap_score} s
           JOIN {user} u ON u.id = s.userid AND u.deleted = 0 AND u.suspended = 0
          WHERE s.compid = :cid
          GROUP BY s.userid",
        ['cid' => $compid]
    );

        $total_users       = count($scores);
        $score_sum         = 0;
        $competent_count   = 0;
        $needs_imp_count   = 0;
        foreach ($scores as $s) {
            $pct = round(($s->score / 5) * 100);
            $score_sum += $pct;
            if ($s->status === 'competent')         { $competent_count++; }
            if ($s->status === 'needs_improvement') { $needs_imp_count++; }
        }
        $avg_pct = $total_users > 0 ? round($score_sum / $total_users) : 0;

        // Per-user rows.
        $user_rows = [];
        $rnum = 1;
        foreach ($scores as $s) {
            $u = $DB->get_record('user', ['id' => $s->userid], 'id,firstname,lastname,email,picture', IGNORE_MISSING);
            if (!$u) { continue; }
            $score_pct   = round(($s->score / 5) * 100);
            $score_class = $score_pct >= 80 ? 'success' : ($score_pct >= 60 ? 'warning' : 'danger');
            $status_map  = [
                'competent'         => ['label' => get_string('status_competent',         'local_skillgap'), 'class' => 'success'],
                'moderate'          => ['label' => get_string('status_moderate',          'local_skillgap'), 'class' => 'warning'],
                'needs_improvement' => ['label' => get_string('status_needs_improvement', 'local_skillgap'), 'class' => 'danger'],
            ];
            $sm = $status_map[$s->status] ?? ['label' => ucfirst($s->status), 'class' => 'secondary'];

            // Initials for avatar.
            $initials = strtoupper(substr($u->firstname, 0, 1) . substr($u->lastname, 0, 1));

            $user_rows[] = [
                'num'           => $rnum++,
                'userid'        => $u->id,
                'fullname'      => fullname($u),
                'email'         => $u->email,
                'initials'      => $initials,
                'score_pct'     => $score_pct,
                'score_class'   => $score_class,
                'score_green_u' => ($score_pct >= 80),
                'score_yellow_u'=> ($score_pct >= 60 && $score_pct < 80),
                'score_red_u'   => ($score_pct < 60),
                'status_label'  => $sm['label'],
                'status_class'  => $sm['class'],
                'compid'        => $compid,
            ];
        }
        // Sort by score desc.
        usort($user_rows, fn($a, $b) => $b['score_pct'] - $a['score_pct']);
        $rnum = 1;
        foreach ($user_rows as &$r) { $r['num'] = $rnum++; }
        unset($r);

        $avg_class = $avg_pct >= 80 ? 'success' : ($avg_pct >= 60 ? 'warning' : 'danger');

        // Sub-comps count.
        $sub_count = (int)$DB->count_records('local_skillgap_subcomp', ['compid' => $compid]);

        return [
            'compid'           => $compid,
            'comp_name'        => $comp->name,
            'total_users'      => $total_users,
            'avg_pct'          => $avg_pct,
            'avg_class'        => $avg_class,
            'competent_count'  => $competent_count,
            'needs_imp_count'  => $needs_imp_count,
            'sub_count'        => $sub_count,
            'user_rows'        => $user_rows,
            'has_users'        => !empty($user_rows),
            'wwwroot'          => $CFG->wwwroot,
            'sesskey'          => sesskey(),
        ];
    }

    // ─── ANALYTICS USER DETAIL: per-user sub-competency breakdown (image 3) ────

    public static function analytics_user_detail_data(int $compid, int $userid): array {
        global $DB, $CFG;

        $comp = $DB->get_record('local_skillgap_comp', ['id' => $compid], '*', MUST_EXIST);
        $u    = $DB->get_record('user', ['id' => $userid], 'id,firstname,lastname,email', MUST_EXIST);

        // Main competency score for this user.
        $score_rec = $DB->get_record_sql(
            "SELECT score, status FROM {local_skillgap_score}
              WHERE userid = :uid AND compid = :cid ORDER BY id DESC LIMIT 1",
            ['uid' => $userid, 'cid' => $compid]
        );
        $overall_pct   = $score_rec ? round(($score_rec->score / 5) * 100) : 0;
        $overall_class = $overall_pct >= 80 ? 'success' : ($overall_pct >= 60 ? 'warning' : 'danger');
        $status_map = [
            'competent'         => ['label' => get_string('status_competent',         'local_skillgap'), 'class' => 'success'],
            'moderate'          => ['label' => get_string('status_moderate',          'local_skillgap'), 'class' => 'warning'],
            'needs_improvement' => ['label' => get_string('status_needs_improvement', 'local_skillgap'), 'class' => 'danger'],
        ];
        $sm = $score_rec ? ($status_map[$score_rec->status] ?? ['label' => ucfirst($score_rec->status), 'class' => 'secondary'])
                         : ['label' => 'Not Attempted', 'class' => 'secondary'];

        // Sub-competencies of this main competency.
        $subs = $DB->get_records('local_skillgap_subcomp',
            ['compid' => $compid, 'parentsubid' => null], 'name ASC');

        $sub_rows  = [];
        $completed_subs = 0;
        foreach ($subs as $sub) {
            // Score for this sub-competency (approximated from attempt responses on sub questions).
            $sub_score_rec = $DB->get_record_sql(
                "SELECT score, status FROM {local_skillgap_score}
                  WHERE userid = :uid AND compid = :cid AND subcompid = :sid ORDER BY id DESC LIMIT 1",
                ['uid' => $userid, 'cid' => $compid, 'sid' => $sub->id]
            );
            // Fallback: compute from responses.
            if (!$sub_score_rec) {
                $resp = $DB->get_record_sql(
                    "SELECT COUNT(*) AS total,
                            SUM(r.iscorrect) AS correct
                       FROM {local_skillgap_response} r
                       JOIN {local_skillgap_attempt} att ON att.id = r.attemptid
                       JOIN {local_skillgap_question} q  ON q.id  = r.questionid
                      WHERE att.userid = :uid AND q.compid = :cid AND q.subcompid = :sid
                        AND att.status = 'completed'",
                    ['uid' => $userid, 'cid' => $compid, 'sid' => $sub->id]
                );
                $sub_pct = ($resp && $resp->total > 0) ? round(($resp->correct / $resp->total) * 100) : 0;
            } else {
                $sub_pct = round(($sub_score_rec->score / 5) * 100);
            }
            $sub_class = $sub_pct >= 80 ? 'success' : ($sub_pct >= 60 ? 'warning' : 'danger');
            $sub_status_key = $sub_pct >= 80 ? 'competent' : ($sub_pct >= 60 ? 'moderate' : 'needs_improvement');
            $sub_sm = $status_map[$sub_status_key];
            if ($sub_pct > 0) { $completed_subs++; }
            $sub_rows[] = [
                'name'         => $sub->name,
                'score_pct'    => $sub_pct,
                'score_class'  => $sub_class,
                'score_green'  => ($sub_pct >= 80),
                'score_yellow' => ($sub_pct >= 60 && $sub_pct < 80),
                'score_red'    => ($sub_pct < 60),
                'status_label' => $sub_sm['label'],
                'status_class' => $sub_class,
            ];
        }
        $sub_count      = count($subs);
        $completion_pct = $sub_count > 0 ? round(($completed_subs / $sub_count) * 100) : 0;
        $initials       = strtoupper(substr($u->firstname, 0, 1) . substr($u->lastname, 0, 1));

        return [
            'compid'         => $compid,
            'comp_name'      => $comp->name,
            'userid'         => $userid,
            'fullname'       => fullname($u),
            'email'          => $u->email,
            'initials'       => $initials,
            'overall_pct'    => $overall_pct,
            'overall_class'  => $overall_class,
            'status_label'   => $sm['label'],
            'status_class'   => $sm['class'],
            'sub_count'      => $sub_count,
            'completion_pct' => $completion_pct,
            'sub_rows'       => $sub_rows,
            'has_subs'       => !empty($sub_rows),
            'wwwroot'        => $CFG->wwwroot,
            'sesskey'        => sesskey(),
        ];
    }

        // ─── ASSESSMENT COMPETENCY REPORT (Fix 7) ────────────────────────

    public static function assessment_competency_report(int $assessmentid): array {
        global $DB, $CFG;

        $assessment = $DB->get_record('local_skillgap_assessment', ['id' => $assessmentid], '*', MUST_EXIST);
        $dept_name  = $DB->get_field('local_skillgap_dept', 'name', ['id' => $assessment->deptid]) ?: '';
        $desig_name = $DB->get_field('local_skillgap_desig_hierarchy', 'designation_name', ['id' => $assessment->desigid]) ?: '';

        // All completed attempts for this assessment.
        $attempts = $DB->get_records_sql(
            "SELECT att.id, att.userid, att.timecompleted
               FROM {local_skillgap_attempt} att
              WHERE att.assessmentid = :aid AND att.status = 'completed'
              ORDER BY att.timecompleted DESC",
            ['aid' => $assessmentid]
        );

        // All competencies mapped to this assessment.
        $acs = $DB->get_records('local_skillgap_assess_comp', ['assessmentid' => $assessmentid]);

        // Build competency metadata list.
        $comp_meta = [];
        foreach ($acs as $ac) {
            $comp_type = (!empty($ac->comp_type) && $ac->comp_type !== '') ? $ac->comp_type : 'main';
            $subcompid = (!empty($ac->subcompid) && (int)$ac->subcompid > 0) ? (int)$ac->subcompid : null;
            if ($comp_type === 'main' || !$subcompid) {
                $name = $DB->get_field('local_skillgap_comp', 'name', ['id' => $ac->compid]) ?: '?';
            } else {
                $name = $DB->get_field('local_skillgap_subcomp', 'name', ['id' => $subcompid]) ?: '?';
            }
            $comp_meta[$ac->compid] = ['name' => $name, 'compid' => $ac->compid];
        }

        // Per-user, per-competency rows.
        $user_rows = [];
        $comp_totals = []; // for summary
        foreach ($attempts as $att) {
            $u = $DB->get_record('user', ['id' => $att->userid], 'id,firstname,lastname', IGNORE_MISSING);
            if (!$u) { continue; }

            // Get responses for this attempt grouped by compid.
            $responses = $DB->get_records('local_skillgap_response', ['attemptid' => $att->id]);
            $by_comp = [];
            foreach ($responses as $r) {
                if (!isset($by_comp[$r->compid])) {
                    $by_comp[$r->compid] = ['correct' => 0, 'total' => 0];
                }
                $by_comp[$r->compid]['total']++;
                if ($r->iscorrect) { $by_comp[$r->compid]['correct']++; }
            }

            $comp_scores = [];
            $user_total_correct = 0;
            $user_total_q = 0;
            foreach ($comp_meta as $compid => $meta) {
                $correct = $by_comp[$compid]['correct'] ?? 0;
                $total   = $by_comp[$compid]['total']   ?? 5;
                $pct     = $total > 0 ? round(($correct / $total) * 100) : 0;
                $status_badge = $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
                $comp_scores[] = [
                    'compname'     => $meta['name'],
                    'correct'      => $correct,
                    'total'        => $total,
                    'pct'          => $pct,
                    'status_badge' => $status_badge,
                ];
                $user_total_correct += $correct;
                $user_total_q       += $total;
                // Accumulate for summary.
                if (!isset($comp_totals[$compid])) {
                    $comp_totals[$compid] = ['name' => $meta['name'], 'pct_sum' => 0, 'count' => 0];
                }
                $comp_totals[$compid]['pct_sum'] += $pct;
                $comp_totals[$compid]['count']++;
            }
            $overall_pct = $user_total_q > 0 ? round(($user_total_correct / $user_total_q) * 100) : 0;
            $overall_badge = $overall_pct >= 80 ? 'success' : ($overall_pct >= 60 ? 'warning' : 'danger');

            $user_rows[] = [
                'fullname'      => fullname($u),
                'comp_scores'   => $comp_scores,
                'overall_pct'   => $overall_pct,
                'overall_badge' => $overall_badge,
                'date'          => $att->timecompleted ? userdate($att->timecompleted, '%d %b %Y') : '-',
            ];
        }

        // Build summary row (average per competency).
        $summary_comps = [];
        foreach ($comp_totals as $compid => $ct) {
            $avg_pct = $ct['count'] > 0 ? round($ct['pct_sum'] / $ct['count']) : 0;
            $summary_comps[] = [
                'compname'     => $ct['name'],
                'avg_pct'      => $avg_pct,
                'status_badge' => $avg_pct >= 80 ? 'success' : ($avg_pct >= 60 ? 'warning' : 'danger'),
            ];
        }

        // Build column headers from comp_meta.
        $comp_headers = array_map(fn($m) => ['compname' => $m['name']], array_values($comp_meta));

        return [
            'assessmentid'   => $assessmentid,
            'title'          => $assessment->title,
            'dept_name'      => $dept_name,
            'desig_name'     => $desig_name,
            'frequency'      => ucfirst(str_replace('_', '-', $assessment->frequency)),
            'user_rows'      => $user_rows,
            'summary_comps'  => $summary_comps,
            'comp_headers'   => $comp_headers,
            'has_data'       => !empty($user_rows),
            'total_attempts' => count($user_rows),
            'wwwroot'        => $CFG->wwwroot,
            'sesskey'        => sesskey(),
        ];
    }

        public static function weakzones_data(): array {
        global $DB, $CFG;

        $comps  = $DB->get_records('local_skillgap_comp', null, 'name ASC');
        $rows   = [];

        foreach ($comps as $c) {
            // Get all latest scores for this competency.
            $sql = "SELECT s.userid, s.score, s.status
                      FROM {local_skillgap_score} s
                      JOIN {user} u ON u.id = s.userid AND u.deleted = 0 AND u.suspended = 0
                     WHERE s.compid = :cid
                       AND s.timecreated = (
                           SELECT MAX(s2.timecreated) FROM {local_skillgap_score} s2
                            WHERE s2.userid = s.userid AND s2.compid = s.compid
                       )";
            $scores = $DB->get_records_sql($sql, ['cid' => $c->id]);

            if (empty($scores)) {
                continue;
            }

            $total      = count($scores);
            $red_count  = 0;
            $score_sum  = 0;
            foreach ($scores as $s) {
                $score_sum += (float)$s->score;
                if ($s->status === 'needs_improvement') {
                    $red_count++;
                }
            }
            $avg_score  = round(($score_sum / $total / 5) * 100);
            $red_pct    = round($red_count / $total * 100);

            // Flag if > 50% red OR avg < 90%.
            if ($red_pct > 50 || $avg_score < 90) {
                // Determine level (organisation, department, designation).
                $dept_rec = $DB->get_record('local_skillgap_dept', ['id' => $c->deptid]);
                $des_rec  = $DB->get_record('local_skillgap_desig_hierarchy', ['id' => $c->desigid]);

                $level  = 'Organisation';
                $entity = 'Company-wide';
                if ($dept_rec) {
                    $level  = 'Department';
                    $entity = $dept_rec->name;
                    if ($des_rec) {
                        $level  = 'Designation';
                        $entity = $des_rec->designation_name;
                    }
                }

                $rows[] = [
                    'compname'    => $c->name,
                    'level'       => $level,
                    'entity'      => $entity,
                    'dept_name'   => $dept_rec ? $dept_rec->name : '',
                    'has_dept'    => !empty($dept_rec),
                    'avg_score'   => $avg_score . '%',
                    'red_pct'     => $red_pct . '%',
                    'users_red'   => $red_count,
                    'is_critical' => $red_pct > 50,
                    'badge_class' => $red_pct > 50 ? 'danger' : 'warning',
                ];
            }
        }

        return [
            'weak_zones' => $rows,
            'has_zones'  => !empty($rows),
            'zone_count' => count($rows),
            'wwwroot'    => $CFG->wwwroot,
        ];
    }

    // ─── HELPERS ─────────────────────────────────────────────────────

    private static function get_dept_options(int $selected = 0): array {
        global $DB;
        $depts = $DB->get_records('local_skillgap_dept', null, 'name ASC', 'id,name');
        $opts  = [['id' => '', 'name' => '-- Select Department --', 'selected' => !$selected]];
        foreach ($depts as $d) {
            $opts[] = ['id' => $d->id, 'name' => $d->name, 'selected' => ($d->id == $selected)];
        }
        return $opts;
    }

    private static function get_desig_options(int $deptid, int $selected = 0): array {
        global $DB;
        $dept_name = $deptid ? ($DB->get_field('local_skillgap_dept', 'name', ['id' => $deptid]) ?: '') : '';
        if (!$dept_name) {
            return [['id' => '', 'name' => '-- Select Designation --', 'selected' => true]];
        }
        // Auto-sync before building options so new designations are visible.
        \local_skillgap\local\designation_service::sync_from_profiles($dept_name);
        return \local_skillgap\local\designation_service::get_select_options($dept_name, $selected);
    }

    private static function get_comp_options(int $deptid, int $desigid, int $selected = 0): array {
        global $DB;
        $where  = [];
        $params = [];
        if ($deptid) {
            $where[]         = 'deptid = :deptid';
            $params['deptid']= $deptid;
        }
        if ($desigid) {
            $where[]          = 'desigid = :desigid';
            $params['desigid']= $desigid;
        }
        $sql  = 'SELECT id, name FROM {local_skillgap_comp}';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY name ASC';
        $comps = $DB->get_records_sql($sql, $params);
        $opts  = [['id' => '', 'name' => '-- All Competencies --', 'selected' => !$selected]];
        foreach ($comps as $c) {
            $opts[] = ['id' => $c->id, 'name' => $c->name, 'selected' => ($c->id == $selected)];
        }
        return $opts;
    }
}
