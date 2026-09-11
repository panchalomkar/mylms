<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_skillgap_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // ── v2 → v3: desig_hierarchy migration ────────────────────────────────────
    if ($oldversion < 2025062301) {
        // (existing migration code kept intact)
        $table = new xmldb_table('local_skillgap_desig_hierarchy');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id',                 XMLDB_TYPE_INTEGER,  '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('department',         XMLDB_TYPE_CHAR,     '255', null, XMLDB_NOTNULL);
            $table->add_field('designation_name',   XMLDB_TYPE_CHAR,     '255', null, XMLDB_NOTNULL);
            $table->add_field('parent_designation', XMLDB_TYPE_CHAR,     '255');
            $table->add_field('sort_order',         XMLDB_TYPE_INTEGER,  '5',   null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated',        XMLDB_TYPE_INTEGER,  '10',  null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified',       XMLDB_TYPE_INTEGER,  '10',  null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('idx_dept',       XMLDB_INDEX_NOTUNIQUE, ['department']);
            $table->add_index('idx_dept_desig', XMLDB_INDEX_UNIQUE,    ['department', 'designation_name']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2025062301, 'local', 'skillgap');
    }

    // ── v3.2: Hierarchy tree, edit sub-competencies, edit questions ────────────
    if ($oldversion < 2025062302) {
        upgrade_plugin_savepoint(true, 2025062302, 'local', 'skillgap');
    }

    // ── v4.0: Sub-competency question mapping ──────────────────────────────────
    // Add subcompid to local_skillgap_question so questions can belong to
    // main competency OR sub/sub-sub competency.
    if ($oldversion < 2025062400) {
        $table = new xmldb_table('local_skillgap_question');

        // Add subcompid column (nullable — NULL means question belongs to main comp only)
        $field = new xmldb_field('subcompid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'compid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index for fast lookup
        $index = new xmldb_index('idx_subcompid', XMLDB_INDEX_NOTUNIQUE, ['subcompid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add assess_comp_type column to local_skillgap_assess_comp
        // to support sub/sub-sub competency selection in assessments.
        $assess_comp_table = new xmldb_table('local_skillgap_assess_comp');
        $type_field = new xmldb_field('comp_type', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'main', 'compid');
        if (!$dbman->field_exists($assess_comp_table, $type_field)) {
            $dbman->add_field($assess_comp_table, $type_field);
        }
        $subcompid_field = new xmldb_field('subcompid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'comp_type');
        if (!$dbman->field_exists($assess_comp_table, $subcompid_field)) {
            $dbman->add_field($assess_comp_table, $subcompid_field);
        }

        // Add subcompid to local_skillgap_response for competency-level scoring
        $response_table = new xmldb_table('local_skillgap_response');
        $resp_subcompid = new xmldb_field('subcompid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'compid');
        if (!$dbman->field_exists($response_table, $resp_subcompid)) {
            $dbman->add_field($response_table, $resp_subcompid);
        }

        // Add subcompid + comp_type to local_skillgap_score for hierarchical scoring
        $score_table = new xmldb_table('local_skillgap_score');
        $score_subcompid = new xmldb_field('subcompid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'compid');
        if (!$dbman->field_exists($score_table, $score_subcompid)) {
            $dbman->add_field($score_table, $score_subcompid);
        }
        $score_type = new xmldb_field('comp_type', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'main', 'subcompid');
        if (!$dbman->field_exists($score_table, $score_type)) {
            $dbman->add_field($score_table, $score_type);
        }

        // Add soft_deleted to local_skillgap_desig_hierarchy for designation delete
        $dh_table = new xmldb_table('local_skillgap_desig_hierarchy');
        $deleted_field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timemodified');
        if (!$dbman->field_exists($dh_table, $deleted_field)) {
            $dbman->add_field($dh_table, $deleted_field);
        }

        upgrade_plugin_savepoint(true, 2025062400, 'local', 'skillgap');
    }

    // ── v4.1: Analytics dashboard, quiz metadata, designation fix ──────────────
    if ($oldversion < 2025062500) {
        // No schema changes — all improvements are PHP/template/JS only.
        upgrade_plugin_savepoint(true, 2025062500, 'local', 'skillgap');
    }

    // ── v4.2: JS fixes, sub-comp questions dropdown ───────────────────────────
    if ($oldversion < 2025062600) {
        upgrade_plugin_savepoint(true, 2025062600, 'local', 'skillgap');
    }

    // ── v4.3: Team analytics visibility mapping, weak zones dept column, banner fix ──
    if ($oldversion < 2025062700) {
        $table = new xmldb_table('local_skillgap_analytics_vis');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id',           XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('viewer_desig', XMLDB_TYPE_CHAR,    '100', null, XMLDB_NOTNULL, null, '');
            $table->add_field('viewer_dept',  XMLDB_TYPE_CHAR,    '100', null, XMLDB_NOTNULL, null, '');
            $table->add_field('target_desig', XMLDB_TYPE_CHAR,    '100', null, XMLDB_NOTNULL, null, '');
            $table->add_field('target_dept',  XMLDB_TYPE_CHAR,    '100', null, XMLDB_NOTNULL, null, '');
            $table->add_field('timecreated',  XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, '0');
            $table->add_field('createdby',    XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            // Use individual single-column indexes to stay within MySQL's 999-byte composite limit.
            $table->add_index('idx_viewer_dept',  XMLDB_INDEX_NOTUNIQUE, ['viewer_dept']);
            $table->add_index('idx_viewer_desig', XMLDB_INDEX_NOTUNIQUE, ['viewer_desig']);
            $table->add_index('idx_target_dept',  XMLDB_INDEX_NOTUNIQUE, ['target_dept']);
            $table->add_index('idx_target_desig', XMLDB_INDEX_NOTUNIQUE, ['target_desig']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2025062700, 'local', 'skillgap');
    }

    // ── v4.4: validator fix, manager dashboard visibility ─────────────────────
    if ($oldversion < 2025062800) {
        upgrade_plugin_savepoint(true, 2025062800, 'local', 'skillgap');
    }

    // ── v4.5: PARAM_RAW fix for compids, dot-separator, updated messages ────────
    if ($oldversion < 2025062900) {
        upgrade_plugin_savepoint(true, 2025062900, 'local', 'skillgap');
    }

    // ── v4.6: fix duplicate radar, q_count badge, NULL comp_type, preview sesskey ─
    if ($oldversion < 2025063000) {
        upgrade_plugin_savepoint(true, 2025063000, 'local', 'skillgap');
    }

    // ── v4.7: CSV fix, visibility multi-select, get_team robust, preview fix ─────
    if ($oldversion < 2025063100) {
        // Ensure comp_type defaults to 'main' for any rows that have empty/null comp_type
        // (rows inserted before v4.0 columns existed).
        try {
            $DB->execute(
                "UPDATE {local_skillgap_assess_comp}
                    SET comp_type = 'main'
                  WHERE comp_type IS NULL OR comp_type = ''"
            );
        } catch (Exception $e) {
            // Column may not exist yet — that's fine, upgrade.php handles it.
        }
        upgrade_plugin_savepoint(true, 2025063100, 'local', 'skillgap');
    }

    // ── v4.8: Fix preview wwwroot, debug page, assessments.php defensive insert ──
    if ($oldversion < 2025070100) {
        upgrade_plugin_savepoint(true, 2025070100, 'local', 'skillgap');
    }

    // ── v4.9: subcomp_course table, promotion history, user history panel ────────
    if ($oldversion < 2025070200) {
        $dbman = $DB->get_manager();

        // New table: sub/sub-sub competency course mapping.
        $table = new xmldb_table('local_skillgap_subcomp_course');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id',        XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('subcompid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('courseid',  XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary',   XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025070200, 'local', 'skillgap');
    }
// ── v5.0: Fix assess_comp unique index to include comp_type + subcompid ──────
if ($oldversion < 2025070300) {
    $table = new xmldb_table('local_skillgap_assess_comp');

    // Step 1: Change subcompid from nullable to NOT NULL DEFAULT 0
    // so it can be part of a unique index (NULLs are never equal in MySQL unique indexes).
    $field = new xmldb_field('subcompid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'comp_type');
    if ($dbman->field_exists($table, $field)) {
        // Fill existing NULLs before changing to NOT NULL.
        $DB->execute("UPDATE {local_skillgap_assess_comp} SET subcompid = 0 WHERE subcompid IS NULL");
        $dbman->change_field_default($table, $field);
        $dbman->change_field_notnull($table, $field);
    }

    // Step 2: Drop the old (assessmentid, compid) unique index.
    $old_index = new xmldb_index('mdl_locaskilassecomp_assco_uix', XMLDB_INDEX_UNIQUE, ['assessmentid', 'compid']);
    if ($dbman->index_exists($table, $old_index)) {
        $dbman->drop_index($table, $old_index);
    }

    // Step 3: Add new 4-column unique index.
    $new_index = new xmldb_index('mdl_locaskilassecomp_assco_uix', XMLDB_INDEX_UNIQUE,
        ['assessmentid', 'compid', 'comp_type', 'subcompid']);
    if (!$dbman->index_exists($table, $new_index)) {
        $dbman->add_index($table, $new_index);
    }

    upgrade_plugin_savepoint(true, 2025070300, 'local', 'skillgap');
}

// ── v5.1: Add notification log table + enable bell notifications ──────────
if ($oldversion < 2025070302) {
    $table = new xmldb_table('local_skillgap_notif_log');
    if (!$dbman->table_exists($table)) {
        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('assessmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('type',         XMLDB_TYPE_CHAR,    '64', null, XMLDB_NOTNULL);
        $table->add_field('timesent',     XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table);
    }
    upgrade_plugin_savepoint(true, 2025070302, 'local', 'skillgap');
}
    return true;
}
