<?php
namespace local_halloffame;

defined('MOODLE_INTERNAL') || die();

/**
 * Dynamic department helper.
 *
 * BEFORE: departments were stored in a static halloffame_departments table.
 * AFTER:  departments are read live from Moodle custom user profile fields
 *         (profile_field with shortname = 'department', configurable via settings).
 *
 * Falls back to the static table when the profile field is not found.
 */
class department_helper {

    /**
     * Return the shortname of the custom profile field used for department.
     * Configurable in plugin settings; defaults to 'department'.
     */
    public static function field_shortname(): string {
        return trim(get_config('local_halloffame', 'dept_profile_field') ?: 'department');
    }

    /**
     * Get the profile_field definition record for the configured department field.
     * Returns false if the field does not exist.
     */
    public static function get_field_definition(): ?\stdClass {
        global $DB;
        static $cache = null;

        if ($cache === null) {
            $shortname = self::field_shortname();
            $cache     = $DB->get_record('user_info_field', ['shortname' => $shortname])
                         ?: false;
        }
        return $cache ?: null;
    }

    /**
     * Return an array of distinct department values actually used by users.
     *
     * When $companyid > 0 (IOMAD active), restrict to users in that company.
     *
     * Returns: [['value' => 'Sales', 'label' => 'Sales'], ...]
     */
    public static function get_departments_for_filter(int $companyid = 0): array {
        global $DB;

        $fielddef = self::get_field_definition();

        if (!$fielddef) {
            // Fallback: static table.
            return self::get_from_static_table($companyid);
        }

        // Pull distinct values from profile_field_data for the configured field.
        if ($companyid > 0 && iomad_helper::is_iomad()) {
            $sql = "SELECT DISTINCT uid.data AS dept
                      FROM {user_info_data}  uid
                      JOIN {user_info_field} uif ON uif.id = uid.fieldid
                      JOIN {company_users}   cu  ON cu.userid = uid.userid
                     WHERE uif.shortname = :shortname
                       AND cu.companyid  = :cid
                       AND uid.data     <> ''
                  ORDER BY uid.data ASC";
            $params = ['shortname' => $fielddef->shortname, 'cid' => $companyid];
        } else {
            $sql = "SELECT DISTINCT uid.data AS dept
                      FROM {user_info_data}  uid
                      JOIN {user_info_field} uif ON uif.id = uid.fieldid
                     WHERE uif.shortname = :shortname
                       AND uid.data     <> ''
                  ORDER BY uid.data ASC";
            $params = ['shortname' => $fielddef->shortname];
        }

        $rows = $DB->get_records_sql($sql, $params);
        return array_values(array_map(
            fn($r) => ['value' => $r->dept, 'label' => $r->dept],
            $rows
        ));
    }

    /**
     * Return the department value for a specific user.
     */
    public static function get_user_department(int $userid): string {
        global $DB;

        $fielddef = self::get_field_definition();
        if (!$fielddef) {
            // Fallback: use Moodle core user.department if the profile field is absent.
            return (string) ($DB->get_field('user', 'department', ['id' => $userid]) ?: '');
        }

        $val = $DB->get_field('user_info_data', 'data', [
            'userid'  => $userid,
            'fieldid' => $fielddef->id,
        ]);
        return (string) ($val ?: '');
    }

    /**
     * Bulk-load departments for a set of userids. Returns [userid => dept].
     */
    public static function get_departments_for_users(array $userids): array {
        global $DB;

        if (empty($userids)) {
            return [];
        }

        $fielddef = self::get_field_definition();
        if (!$fielddef) {
            // Fallback: user.department core field.
            [$in, $p] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
            $rows = $DB->get_records_select('user', "id $in", $p, '', 'id, department');
            $out  = [];
            foreach ($rows as $r) {
                $out[$r->id] = $r->department ?? '';
            }
            return $out;
        }

        [$in, $p] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        $sql = "SELECT uid.userid, uid.data AS dept
                  FROM {user_info_data}  uid
                 WHERE uid.fieldid = :fieldid
                   AND uid.userid $in";
        $p['fieldid'] = $fielddef->id;
        $rows = $DB->get_records_sql($sql, $p);
        $out  = [];
        foreach ($rows as $r) {
            $out[$r->userid] = $r->dept ?? '';
        }
        return $out;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private static function get_from_static_table(int $companyid): array {
        global $DB;

        // If IOMAD and companyid given, try company-scoped static table.
        if ($companyid > 0) {
            $cols = $DB->get_columns('halloffame_departments');
            if (array_key_exists('companyid', $cols)) {
                $rows = $DB->get_records_select(
                    'halloffame_departments',
                    'companyid = 0 OR companyid = :cid',
                    ['cid' => $companyid],
                    'name ASC'
                );
                return array_values(array_map(
                    fn($r) => ['value' => $r->name, 'label' => $r->name],
                    $rows
                ));
            }
        }

        $rows = $DB->get_records('halloffame_departments', null, 'name ASC');
        return array_values(array_map(
            fn($r) => ['value' => $r->name, 'label' => $r->name],
            $rows
        ));
    }
}
