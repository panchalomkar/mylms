<?php
/**
 * mod_airoleplay database upgrade.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_airoleplay_upgrade(int $oldversion): bool {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // ----------------------------------------------------------------
    // v2024120101 — Heal any missing tables from install.xml.
    //
    // Some early deployments installed an incomplete schema. This step
    // creates any table that is declared in db/install.xml but missing
    // from the actual database, without disturbing existing data.
    // ----------------------------------------------------------------
    if ($oldversion < 2024120101) {

        $xmlfile = $CFG->dirroot . '/mod/airoleplay/db/install.xml';

        $expected_tables = [
            'airoleplay',
            'airoleplay_persona',
            'airoleplay_session',
            'airoleplay_message',
            'airoleplay_competency_result',
            'airoleplay_assignment',
            'airoleplay_assignment_user',
            'airoleplay_ai_usage',
            'airoleplay_profile_map',
            'airoleplay_notification_log',
        ];

        foreach ($expected_tables as $tablename) {
            if (!$dbman->table_exists($tablename)) {
                try {
                    $dbman->install_one_table_from_xmldb_file($xmlfile, $tablename);
                    mtrace("  mod_airoleplay: created missing table {$tablename}");
                } catch (\Throwable $e) {
                    // Don't abort the upgrade — log and continue so other
                    // tables still get created. Site admin can re-run if needed.
                    mtrace("  mod_airoleplay: could not create {$tablename}: " . $e->getMessage());
                }
            }
        }

        upgrade_mod_savepoint(true, 2024120101, 'airoleplay');
    }

    // ----------------------------------------------------------------
    // v2024120105 — One-time cleanup of historical stale "active" sessions.
    //
    // Earlier versions created an airoleplay_session row every time a learner
    // opened conversation.php, even if they never sent a message. Those rows
    // stayed status='active' forever and inflated attempt counts. This step
    // deletes empty active rows (no learner messages) and abandons the rest.
    // Safe to run multiple times; only affects rows older than 30 minutes
    // (anything more recent is presumed in-progress).
    // ----------------------------------------------------------------
    if ($oldversion < 2024120105) {
        if ($dbman->table_exists('airoleplay_session') && $dbman->table_exists('airoleplay_message')) {
            $cutoff = time() - 1800; // 30 minutes
            $stale = $DB->get_records_select(
                'airoleplay_session',
                "status = 'active' AND timestarted < :cutoff",
                ['cutoff' => $cutoff],
                '',
                'id'
            );
            $deleted = 0;
            $abandoned = 0;
            foreach ($stale as $s) {
                $msgs = (int) $DB->count_records('airoleplay_message', ['session' => $s->id, 'role' => 'user']);
                if ($msgs === 0) {
                    $DB->delete_records('airoleplay_message', ['session' => $s->id]);
                    if ($dbman->table_exists('airoleplay_competency_result')) {
                        $DB->delete_records('airoleplay_competency_result', ['session' => $s->id]);
                    }
                    $DB->delete_records('airoleplay_session', ['id' => $s->id]);
                    $deleted++;
                } else {
                    $DB->update_record('airoleplay_session', (object)[
                        'id'           => $s->id,
                        'status'       => 'abandoned',
                        'timemodified' => time(),
                    ]);
                    $abandoned++;
                }
            }
            mtrace("  mod_airoleplay: cleaned up stale sessions — deleted {$deleted} empty, abandoned {$abandoned} non-empty");
        }
        upgrade_mod_savepoint(true, 2024120105, 'airoleplay');
    }

    // ----------------------------------------------------------------
    // v2024120106 — Mood-o-meter, conversation audio, and evaluation backfill.
    //
    // - Adds `mood_score` (-100..100) and `mood_label` to airoleplay_session
    //   for the Mood-o-meter feature.
    // - Adds `session_audio_url` for the merged conversation recording.
    // - Backfills completed sessions that never received an AI evaluation by
    //   re-queueing them for the evaluation adhoc task. This fixes the blank
    //   Competency Profile on reports.
    // ----------------------------------------------------------------
    if ($oldversion < 2024120106) {
        $table = new xmldb_table('airoleplay_session');

        $defs = [
            'mood_score'        => new xmldb_field('mood_score',        XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'sentiment_trend'),
            'mood_label'        => new xmldb_field('mood_label',        XMLDB_TYPE_CHAR,    '50', null, null, null, null, 'mood_score'),
            'session_audio_url' => new xmldb_field('session_audio_url', XMLDB_TYPE_TEXT,    null, null, null, null, null, 'mood_label'),
        ];
        foreach ($defs as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Backfill — re-queue evaluation for completed sessions that have no competency rows.
        // This fixes the blank Competency Profile in reports for legacy data.
        if ($dbman->table_exists('airoleplay_session') &&
            $dbman->table_exists('airoleplay_competency_result') &&
            class_exists('\\mod_airoleplay\\task\\evaluate_session_adhoc')) {

            $unevaluated = $DB->get_records_sql(
                "SELECT s.id FROM {airoleplay_session} s
                  WHERE s.status = 'completed'
                    AND NOT EXISTS (
                        SELECT 1 FROM {airoleplay_competency_result} cr WHERE cr.session = s.id
                    )"
            );
            $requeued = 0;
            foreach ($unevaluated as $row) {
                try {
                    $task = new \mod_airoleplay\task\evaluate_session_adhoc();
                    $task->set_custom_data(['sessionid' => (int)$row->id]);
                    \core\task\manager::queue_adhoc_task($task, true);
                    $requeued++;
                } catch (\Throwable $e) {
                    // Skip individual failures.
                }
            }
            if ($requeued) {
                mtrace("  mod_airoleplay: re-queued {$requeued} sessions for AI evaluation");
            }
        }

        upgrade_mod_savepoint(true, 2024120106, 'airoleplay');
    }

    // ----------------------------------------------------------------
    // v2024120107 — Widen audio_fileref so it can hold full pluginfile URLs.
    //
    // The TTS feature stores a pluginfile URL on each AI message. These URLs
    // can exceed 255 characters, which on strict-mode MySQL caused a fatal
    // error when writing — surfacing as "Unexpected end of JSON input" on the
    // conversation page. Changing char(255) → text removes the limit.
    // ----------------------------------------------------------------
    if ($oldversion < 2024120107) {
        $table = new xmldb_table('airoleplay_message');
        $field = new xmldb_field('audio_fileref', XMLDB_TYPE_TEXT, null, null, null, null, null, 'content_type');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        } else {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2024120107, 'airoleplay');
    }

    // ----------------------------------------------------------------
    // v2024120111 — Move audio files from system context to module context.
    //
    // Earlier versions stored conversation recordings and TTS audio under
    // the system context, where Moodle's pluginfile router never calls our
    // module's pluginfile callback — so the audio could not be played back.
    // This step relocates each file to its activity's module context and
    // rewrites the stored URL on the session row. Idempotent and safe to
    // re-run.
    // ----------------------------------------------------------------
    if ($oldversion < 2024120111) {
        if ($dbman->table_exists('airoleplay_session')) {
            $fs = get_file_storage();
            $sys = context_system::instance();

            $sessions = $DB->get_records_select('airoleplay_session',
                "session_audio_url IS NOT NULL AND session_audio_url <> ''",
                null, 'id ASC', 'id, airoleplay, session_audio_url');

            $moved = 0;
            foreach ($sessions as $s) {
                try {
                    $cm = get_coursemodule_from_instance('airoleplay', $s->airoleplay, 0, false, IGNORE_MISSING);
                    if (!$cm) { continue; }
                    $modctx = context_module::instance($cm->id);

                    $sysFiles = $fs->get_area_files($sys->id, 'mod_airoleplay', 'session_audio', $s->id, 'id', false);
                    if (empty($sysFiles)) {
                        // Either already moved, or never existed. Still ensure the URL is correct
                        // if a file lives at the module context.
                        $modFiles = $fs->get_area_files($modctx->id, 'mod_airoleplay', 'session_audio', $s->id, 'id', false);
                        if (!empty($modFiles)) {
                            $f = reset($modFiles);
                            $newurl = moodle_url::make_pluginfile_url(
                                $modctx->id, 'mod_airoleplay', 'session_audio', $s->id, '/', $f->get_filename(), false
                            )->out(false);
                            if ($newurl !== $s->session_audio_url) {
                                $DB->set_field('airoleplay_session', 'session_audio_url', $newurl, ['id' => $s->id]);
                            }
                        }
                        continue;
                    }

                    foreach ($sysFiles as $f) {
                        // Skip if already exists at module context.
                        $existing = $fs->get_file($modctx->id, 'mod_airoleplay', 'session_audio', $s->id, '/', $f->get_filename());
                        if (!$existing) {
                            $fs->create_file_from_storedfile([
                                'contextid' => $modctx->id,
                                'component' => 'mod_airoleplay',
                                'filearea'  => 'session_audio',
                                'itemid'    => $s->id,
                                'filepath'  => '/',
                                'filename'  => $f->get_filename(),
                            ], $f);
                        }
                        $newurl = moodle_url::make_pluginfile_url(
                            $modctx->id, 'mod_airoleplay', 'session_audio', $s->id, '/', $f->get_filename(), false
                        )->out(false);
                        $DB->set_field('airoleplay_session', 'session_audio_url', $newurl, ['id' => $s->id]);
                        $moved++;
                    }
                    // Remove old system-context copies.
                    $fs->delete_area_files($sys->id, 'mod_airoleplay', 'session_audio', $s->id);
                } catch (\Throwable $e) {
                    // Per-row failures are non-fatal; continue with the rest.
                }
            }
            if ($moved > 0) {
                mtrace("  mod_airoleplay: relocated {$moved} conversation recording(s) to module context");
            }
        }
        upgrade_mod_savepoint(true, 2024120111, 'airoleplay');
    }

    // ----------------------------------------------------------------
    // v2024120116 — Switch to scenario-level competency rubrics.
    //
    // Previously the eval task asked the AI to invent competencies per
    // session, so two learners attempting the same scenario got different
    // radar axes and the dashboard couldn't aggregate meaningfully.
    //
    // The new design caches a single competency list per *scenario* in
    // airoleplay.evaluation_criteria (JSON). Existing scenarios may have a
    // stale value (the field was previously unused). We clear those so the
    // next evaluation / save regenerates a fresh list.
    //
    // We DO NOT delete existing airoleplay_competency_result rows — those
    // keep old reports populated until each session is re-evaluated.
    // ----------------------------------------------------------------
    if ($oldversion < 2024120116) {
        if ($dbman->table_exists('airoleplay')) {
            // Clear evaluation_criteria on every scenario, except any value
            // that already looks like our new JSON rubric (so we don't blow
            // away a cache that's already valid).
            $rows = $DB->get_records_select('airoleplay',
                "evaluation_criteria IS NOT NULL AND evaluation_criteria <> ''",
                null, 'id ASC', 'id, evaluation_criteria');
            $cleared = 0;
            foreach ($rows as $row) {
                $looks_valid = false;
                $decoded = json_decode($row->evaluation_criteria, true);
                if (is_array($decoded) && !empty($decoded[0]['key']) && !empty($decoded[0]['label'])) {
                    $looks_valid = true;
                }
                if (!$looks_valid) {
                    $DB->set_field('airoleplay', 'evaluation_criteria', '', ['id' => $row->id]);
                    $cleared++;
                }
            }
            if ($cleared > 0) {
                mtrace("  mod_airoleplay: cleared stale evaluation_criteria on {$cleared} scenario(s)");
            }
        }
        upgrade_mod_savepoint(true, 2024120116, 'airoleplay');
    }

    return true;
}
