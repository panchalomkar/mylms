<?php
namespace local_halloffame;

defined('MOODLE_INTERNAL') || die();

/**
 * IOMAD multi-tenancy helper for Hall of Fame.
 *
 * Central place for all tenant/company awareness so no other file
 * needs to know IOMAD internals.  Works gracefully even when IOMAD
 * is NOT installed (falls back to site-wide behaviour).
 */
class iomad_helper {

    /** @var bool|null  Cache: is IOMAD installed? */
    private static ?bool $iomad_present = null;

    // ── Detection ─────────────────────────────────────────────────────────────

    /**
     * Returns true when the IOMAD block/local plugin is installed.
     */
    public static function is_iomad(): bool {
        if (self::$iomad_present === null) {
            self::$iomad_present = file_exists(
                \core_component::get_component_directory('block_iomad_company_admin')
                ?? ''
            ) || file_exists(
                \core_component::get_component_directory('local_iomad')
                ?? ''
            );
        }
        return self::$iomad_present;
    }

    // ── Company resolution ────────────────────────────────────────────────────

    /**
     * Return the companyid for the current user, or 0 for site admins / no IOMAD.
     *
     * - Site admin  → 0  (sees everything)
     * - Company admin / user → their company id
     * - No IOMAD → 0
     */
    public static function get_current_companyid(): int {
        global $USER;

        if (!self::is_iomad()) {
            return 0;
        }

        // Site admins see all companies.
        if (is_siteadmin($USER)) {
            return 0;
        }

        return self::get_user_companyid($USER->id);
    }

    /**
     * Return the companyid for any given userid.
     */
    public static function get_user_companyid(int $userid): int {
        global $DB;

        if (!self::is_iomad()) {
            return 0;
        }

        // IOMAD stores the mapping in company_users.
        return (int) $DB->get_field('company_users', 'companyid',
            ['userid' => $userid], IGNORE_MISSING) ?: 0;
    }

    /**
     * Return all userids belonging to a company.
     * When companyid = 0 → return empty array (means "all users").
     */
    public static function get_company_userids(int $companyid): array {
        global $DB;

        if (!self::is_iomad() || $companyid === 0) {
            return [];
        }

        $rows = $DB->get_fieldset_select('company_users', 'userid',
            'companyid = :cid', ['cid' => $companyid]);
        return array_map('intval', $rows);
    }

    /**
     * Build a SQL fragment restricting a user column to a company.
     *
     * Usage:
     *   [$sql_fragment, $params] = iomad_helper::company_sql_fragment('a.userid', 'cu');
     *   // $sql_fragment is '' or 'AND a.userid IN (SELECT userid FROM {company_users} WHERE companyid=:hof_companyid)'
     *
     * @param string $usercol   SQL expression for the user id column (e.g. 'a.userid')
     * @param string $alias     Unique param alias suffix to avoid name collisions
     * @param int    $companyid Pass 0 to skip filtering
     */
    public static function company_sql_fragment(
        string $usercol,
        string $alias = 'hof',
        int $companyid = -1
    ): array {
        if ($companyid === -1) {
            $companyid = self::get_current_companyid();
        }

        if (!self::is_iomad() || $companyid === 0) {
            return ['', []];
        }

        $param = $alias . '_companyid';
        $sql   = " AND {$usercol} IN (
                     SELECT userid FROM {company_users}
                      WHERE companyid = :{$param}
                   )";
        return [$sql, [$param => $companyid]];
    }

    // ── Company user list for admin dropdowns ─────────────────────────────────

    /**
     * Return a list of users scoped to the current user's company (or all users
     * for site admins / non-IOMAD installs).
     *
     * Returns array of stdClass with id, firstname, lastname.
     */
    public static function get_company_users_for_select(): array {
        global $DB;

        $companyid = self::get_current_companyid();

        if (!self::is_iomad() || $companyid === 0) {
            // Site admin or no IOMAD — return all non-deleted, non-guest users.
            return array_values($DB->get_records_select(
                'user',
                'deleted = 0 AND id <> :guestid AND id <> 1',
                ['guestid' => guest_user()->id],
                'firstname ASC',
                'id, firstname, lastname'
            ));
        }

        $sql = "SELECT u.id, u.firstname, u.lastname
                  FROM {user} u
                  JOIN {company_users} cu ON cu.userid = u.id
                 WHERE cu.companyid = :cid
                   AND u.deleted = 0
              ORDER BY u.firstname ASC";
        return array_values($DB->get_records_sql($sql, ['cid' => $companyid]));
    }

    // ── Category scoping ──────────────────────────────────────────────────────

    /**
     * Get award categories scoped to the current company (or global if admin).
     * Falls back gracefully — categories without a companyid column are treated as global.
     */
    public static function get_scoped_categories(int $companyid = -1): array {
        global $DB;

        if ($companyid === -1) {
            $companyid = self::get_current_companyid();
        }

        // If the table has a companyid column (added in upgrade), filter.
        $columns = $DB->get_columns('halloffame_categories');
        if (!array_key_exists('companyid', $columns)) {
            // No companyid column yet — return all.
            return array_values($DB->get_records('halloffame_categories', null, 'name ASC'));
        }

        if ($companyid === 0) {
            return array_values($DB->get_records('halloffame_categories', null, 'name ASC'));
        }

        // Company-scoped OR global (companyid = 0) categories.
        return array_values($DB->get_records_select(
            'halloffame_categories',
            'companyid = 0 OR companyid = :cid',
            ['cid' => $companyid],
            'name ASC'
        ));
    }

    // ── Tenant display name ───────────────────────────────────────────────────

    /**
     * Return the company name for a given companyid, or '' if not found / no IOMAD.
     */
    public static function get_company_name(int $companyid): string {
        global $DB;
        if (!self::is_iomad() || $companyid === 0) {
            return '';
        }
        return (string) $DB->get_field('company', 'name', ['id' => $companyid]) ?: '';
    }
}
