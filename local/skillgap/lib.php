<?php
// This file is part of Moodle - http://moodle.org/
defined('MOODLE_INTERNAL') || die();

/**
 * Add navigation items to the Moodle navigation tree.
 */
function local_skillgap_extend_navigation(global_navigation $nav): void {
    // Navigation is handled via settings.php menu items.
}

/**
 * Get all direct reportees of a manager.
 * Uses the custom profile field shortname 'repotingto' whose value = manager's username.
 * Uses parameterized query — NO raw SQL concatenation.
 *
 * @param int $manageruserid  The manager's Moodle user ID.
 * @return array  Array of stdClass user records.
 */
function local_skillgap_get_team($manageruserid): array {
    global $DB;

    // Fetch full user record including department.
    $manager = $DB->get_record('user', ['id' => $manageruserid], 'id,username,department', MUST_EXIST);

    // 1. Direct reports via custom profile field "repotingto".
    $direct_sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.department
                     FROM {user_info_data}  uid
                     JOIN {user_info_field} uif ON uif.id = uid.fieldid
                     JOIN {user}            u   ON u.id   = uid.userid
                    WHERE uif.shortname = :fieldname
                      AND " . $DB->sql_compare_text('uid.data') . " = " . $DB->sql_compare_text(':musername') . "
                      AND u.suspended = 0 AND u.deleted = 0";
    $direct = $DB->get_records_sql($direct_sql, [
        'fieldname' => 'repotingto',
        'musername' => $manager->username,
    ]);

    // 2. Visibility-mapped users via local_skillgap_analytics_vis.
    $desig_field = $DB->get_record('user_info_field', ['shortname' => 'designation'], 'id');
    $mapped      = [];
    if ($desig_field) {
        $mgr_desig_row = $DB->get_record('user_info_data', [
            'userid'  => $manageruserid,
            'fieldid' => $desig_field->id,
        ], 'data');
        $mgr_desig = $mgr_desig_row ? trim($mgr_desig_row->data) : '';
        $mgr_dept  = trim($manager->department ?? '');

        if ($mgr_desig) {
            // Match viewer by designation; optionally by department too if set.
            if ($mgr_dept) {
                $vis_maps = $DB->get_records_sql(
                    "SELECT * FROM {local_skillgap_analytics_vis}
                      WHERE viewer_desig = :desig
                        AND (viewer_dept = :dept OR viewer_dept = '' OR viewer_dept = 'All')",
                    ['desig' => $mgr_desig, 'dept' => $mgr_dept]
                );
            } else {
                $vis_maps = $DB->get_records('local_skillgap_analytics_vis',
                    ['viewer_desig' => $mgr_desig]);
            }

            foreach ($vis_maps as $vm) {
                // Fetch users matching target designation (and optionally department).
                if (!empty($vm->target_dept) && $vm->target_dept !== 'All') {
                    $t_sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.department
                                FROM {user} u
                                JOIN {user_info_data}  uid2 ON uid2.userid = u.id
                                JOIN {user_info_field} uif2 ON uif2.id     = uid2.fieldid
                               WHERE uif2.shortname = 'designation'
                                 AND " . $DB->sql_compare_text('uid2.data') . " = " . $DB->sql_compare_text(':tdesig') . "
                                 AND u.department = :tdept
                                 AND u.suspended = 0 AND u.deleted = 0";
                    $t_params = ['tdesig' => $vm->target_desig, 'tdept' => $vm->target_dept];
                } else {
                    $t_sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.department
                                FROM {user} u
                                JOIN {user_info_data}  uid2 ON uid2.userid = u.id
                                JOIN {user_info_field} uif2 ON uif2.id     = uid2.fieldid
                               WHERE uif2.shortname = 'designation'
                                 AND " . $DB->sql_compare_text('uid2.data') . " = " . $DB->sql_compare_text(':tdesig') . "
                                 AND u.suspended = 0 AND u.deleted = 0";
                    $t_params = ['tdesig' => $vm->target_desig];
                }
                foreach ($DB->get_records_sql($t_sql, $t_params) as $tu) {
                    $mapped[$tu->id] = $tu;
                }
            }
        }
    }

    // Merge: direct reports + visibility-mapped users, no duplicates.
    return $direct + $mapped;
}

/**
 * Check if a user can view team analytics for another user based on visibility mapping.
 */
function local_skillgap_can_view_analytics(int $viewerid, int $targetid): bool {
    global $DB;

    // Admin always can.
    if (has_capability('local/skillgap:admin', context_system::instance(), $viewerid)) {
        return true;
    }

    // Direct report check.
    $viewer  = $DB->get_record('user', ['id' => $viewerid],  'id,username,department');
    $direct  = local_skillgap_get_team($viewerid);
    if (isset($direct[$targetid])) {
        return true;
    }

    return false;
}

/**
 * Derive competency status from a numeric score (out of 5).
 */
function local_skillgap_get_status(float $score): string {
    if ($score >= 5)    return 'competent';
    if ($score >= 3)    return 'moderate';
    return 'needs_improvement';
}

/**
 * Get the badge CSS class for a status string.
 */
function local_skillgap_status_badge(string $status): string {
    return match($status) {
        'competent'        => 'badge-success',
        'moderate'         => 'badge-warning',
        'needs_improvement'=> 'badge-danger',
        default            => 'badge-secondary',
    };
}

/**
 * Auto-enrol a user in all courses mapped to a competency (manual enrol).
 */
function local_skillgap_auto_enrol(int $userid, int $compid): void {
    global $DB;

    $mappings = $DB->get_records('local_skillgap_comp_course', ['compid' => $compid]);
    if (empty($mappings)) {
        return;
    }

    $enrolplugin = enrol_get_plugin('manual');
    if (!$enrolplugin) {
        return;
    }

    foreach ($mappings as $m) {
        $context = context_course::instance($m->courseid, IGNORE_MISSING);
        if (!$context) {
            continue;
        }
        if (is_enrolled($context, $userid)) {
            continue;
        }
        $instance = $DB->get_record('enrol', [
            'courseid' => $m->courseid,
            'enrol'    => 'manual',
            'status'   => ENROL_INSTANCE_ENABLED,
        ]);
        if ($instance) {
            $enrolplugin->enrol_user($instance, $userid, null, time());
            local_skillgap_send_notification(
                $userid,
                'course_assigned',
                get_string('notify_course_assigned_subject', 'local_skillgap'),
                get_string('notify_course_assigned_body', 'local_skillgap',
                    $DB->get_field('local_skillgap_comp', 'name', ['id' => $compid]))
            );
        }
    }
}

/**
 * Send a Moodle message notification to a user.
 */
function local_skillgap_send_notification(int $userid, string $type, string $subject, string $body): void {
    global $DB;

    $user = $DB->get_record('user', ['id' => $userid]);
    if (!$user || $user->deleted || $user->suspended) {
        return;
    }

    $message                      = new \core\message\message();
    $message->component           = 'local_skillgap';
    // Map type to registered message provider name.
    $provider_map = [
        'assessment_due'       => 'assessment_due',
        'competency_assigned'  => 'competency_assigned',
        'promoted'             => 'promoted',
    ];
    $message->name = $provider_map[$type] ?? 'assessment_due';
    $message->userfrom            = core_user::get_noreply_user();
    $message->userto              = $user;
    $message->subject             = $subject;
    $message->fullmessage         = $body;
    $message->fullmessageformat   = FORMAT_PLAIN;
    $message->fullmessagehtml     = '<p>' . $body . '</p>';
    $message->smallmessage        = $body;
    $message->notification        = 1;

    message_send($message);
}

/**
 * Compute and store competency scores for a completed attempt.
 * Returns array of [compid => score_record].
 */
function local_skillgap_compute_scores(int $attemptid): array {
    global $DB;

    $attempt = $DB->get_record('local_skillgap_attempt', ['id' => $attemptid], '*', MUST_EXIST);

    // Get all responses for this attempt, grouped by compid.
    $responses = $DB->get_records('local_skillgap_response', ['attemptid' => $attemptid]);

    // Group by competency.
    $bycomp = [];
    foreach ($responses as $r) {
        if (!isset($bycomp[$r->compid])) {
            $bycomp[$r->compid] = ['correct' => 0, 'total' => 0];
        }
        $bycomp[$r->compid]['total']++;
        if ($r->iscorrect) {
            $bycomp[$r->compid]['correct']++;
        }
    }

    $now    = time();
    $scores = [];

    foreach ($bycomp as $compid => $counts) {
        // Use actual number of questions answered as maxscore (not hardcoded 5).
        // This handles assessments with main + sub + subsub entries for the same compid.
        $actual_total = max(1, (int)$counts['total']);
        $actual_correct = min((int)$counts['correct'], $actual_total);
        // Normalise score to 0–5 scale (5 = fully correct).
        $score  = round(($actual_correct / $actual_total) * 5, 2);
        $status = local_skillgap_get_status($score);

        $record = (object) [
            'userid'       => $attempt->userid,
            'compid'       => $compid,
            'assessmentid' => $attempt->assessmentid,
            'attemptid'    => $attemptid,
            'score'        => $score,
            'maxscore'     => 5,
            'status'       => $status,
            'timecreated'  => $now,
        ];

        $record->id = $DB->insert_record('local_skillgap_score', $record);
        $scores[$compid] = $record;

        // Auto-enrol if not competent.
        if ($status !== 'competent') {
            local_skillgap_auto_enrol($attempt->userid, $compid);
        }
    }

    return $scores;
}

/**
 * Get the latest score record for a user + competency.
 */
function local_skillgap_latest_score(int $userid, int $compid): ?stdClass {
    global $DB;
    $sql = "SELECT * FROM {local_skillgap_score}
             WHERE userid = :uid AND compid = :cid
             ORDER BY timecreated DESC LIMIT 1";
    return $DB->get_record_sql($sql, ['uid' => $userid, 'cid' => $compid]) ?: null;
}

/**
 * Get progress score: current - previous attempt for a user + competency.
 * Returns null if only one attempt (first attempt).
 */
function local_skillgap_progress_score(int $userid, int $compid): ?float {
    global $DB;
    $sql = "SELECT score FROM {local_skillgap_score}
             WHERE userid = :uid AND compid = :cid
             ORDER BY timecreated DESC LIMIT 2";
    $rows = array_values($DB->get_records_sql($sql, ['uid' => $userid, 'cid' => $compid]));
    if (count($rows) < 2) {
        return null;
    }
    return (float)$rows[0]->score - (float)$rows[1]->score;
}

/**
 * Validate that a competency has exactly 5 questions before it can be included in an assessment.
 */
function local_skillgap_validate_question_count(int $compid): bool {
    global $DB;
    return $DB->count_records('local_skillgap_question', ['compid' => $compid]) >= 5;
}

/**
 * Get departments as an associative array [id => name] for select menus.
 */
function local_skillgap_get_dept_menu(): array {
    global $DB;
    return $DB->get_records_menu('local_skillgap_dept', null, 'name ASC', 'id,name');
}

/**
 * Get designations as an associative array [id => name] for a department.
 */
function local_skillgap_get_desig_menu(int $deptid): array {
    global $DB;
    $dept_name = $DB->get_field('local_skillgap_dept', 'name', ['id' => $deptid]) ?: '';
    $rows      = $DB->get_records('local_skillgap_desig_hierarchy',
                    ['department' => $dept_name], 'sort_order ASC, designation_name ASC', 'id,designation_name');
    $menu = [];
    foreach ($rows as $r) { $menu[$r->id] = $r->designation_name; }
    return $menu;
}

/**
 * Get competencies as an associative array [id => name] for a dept+desig.
 */
function local_skillgap_get_comp_menu(int $deptid, int $desigid): array {
    global $DB;
    return $DB->get_records_menu('local_skillgap_comp',
        ['deptid' => $deptid, 'desigid' => $desigid], 'name ASC', 'id,name');
}

/**
 * Sync departments from Moodle user profile "department" field.
 * Called during admin page load or cron.
 */
function local_skillgap_sync_departments(): void {
    global $DB;
    $depts = $DB->get_fieldset_sql(
        "SELECT DISTINCT department FROM {user} WHERE department != '' AND deleted = 0 AND suspended = 0"
    );
    foreach ($depts as $dept) {
        $dept = trim($dept);
        if (!$dept) { continue; }
        if (!$DB->record_exists('local_skillgap_dept', ['name' => $dept])) {
            $DB->insert_record('local_skillgap_dept', ['name' => $dept, 'timecreated' => time()]);
        }
        // Also auto-sync designations from profile field for each dept.
        \local_skillgap\local\designation_service::sync_from_profiles($dept);
    }
}

/**
 * Render a Bootstrap badge HTML string for a status.
 */
function local_skillgap_badge(string $status, string $label): string {
    $class = local_skillgap_status_badge($status);
    return '<span class="badge ' . $class . '">' . s($label) . '</span>';
}
