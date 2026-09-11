<?php
/**
 * Post-installation hook for local_skillgap.
 * Runs once after the database tables are created on first install.
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Called by Moodle after install.xml tables have been created.
 */
function xmldb_local_skillgap_install(): bool {
    // Sync existing Moodle departments on first install so admins
    // immediately see their departments without a manual sync step.
    local_skillgap_sync_departments();
    return true;
}
