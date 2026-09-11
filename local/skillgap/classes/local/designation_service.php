<?php
namespace local_skillgap\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Designation Service — handles all designation-related operations.
 *
 * DESIGN PRINCIPLE:
 *   - Designation NAMES come from Moodle user profile field `designation`
 *   - Hierarchy is determined purely by sort_order (position 1 = top/highest rank)
 *   - No parent-child relationships — ordering alone defines the ladder
 */
class designation_service {

    /**
     * Fetch all UNIQUE designation values from users in a given department.
     * Reads the custom profile field with shortname 'designation'.
     */
    public static function fetch_from_profiles(string $department): array {
        global $DB;

        $department = trim($department);
        if (empty($department)) {
            return [];
        }

        $field = $DB->get_record('user_info_field', ['shortname' => 'designation'], 'id', IGNORE_MISSING);
        if (!$field) {
            return self::fetch_from_profiles_fallback($department);
        }

        $sql = "SELECT DISTINCT TRIM(uid.data) AS desig_name
                  FROM {user_info_data}  uid
                  JOIN {user}            u   ON u.id   = uid.userid
                 WHERE uid.fieldid    = :fieldid
                   AND u.department   = :dept
                   AND u.deleted      = 0
                   AND u.suspended    = 0
                   AND uid.data       != ''
                   AND uid.data       IS NOT NULL
              ORDER BY TRIM(uid.data) ASC";

        $rows   = $DB->get_records_sql($sql, ['fieldid' => $field->id, 'dept' => $department]);
        $result = [];
        foreach ($rows as $row) {
            $name = trim($row->desig_name);
            if ($name !== '') {
                $result[] = $name;
            }
        }
        return array_values(array_unique($result));
    }

    private static function fetch_from_profiles_fallback(string $department): array {
        return [];
    }

    /**
     * Get all stored hierarchy entries for a department, ordered by sort_order ASC.
     * Position 1 (lowest sort_order) = highest rank (e.g. HOD).
     */
    public static function get_hierarchy(string $department): array {
        global $DB;

        if (empty($department)) {
            return [];
        }

        $sql = "SELECT * FROM {local_skillgap_desig_hierarchy}
                 WHERE department = :dept
                   AND (deleted IS NULL OR deleted = 0)
                 ORDER BY sort_order ASC, id ASC";
        return array_values($DB->get_records_sql($sql, ['dept' => $department]));
    }

    /**
     * Sync designations from user profiles into the hierarchy table.
     * New designations are appended at the bottom (highest sort_order + 1).
     */
    public static function sync_from_profiles(string $department): int {
        global $DB;

        $names = self::fetch_from_profiles($department);
        $count = 0;
        $now   = time();

        // Find current max sort_order for this department.
        $max_order = (int)$DB->get_field_sql(
            "SELECT COALESCE(MAX(sort_order), 0) FROM {local_skillgap_desig_hierarchy}
              WHERE department = :dept AND (deleted IS NULL OR deleted = 0)",
            ['dept' => $department]
        );

        foreach ($names as $name) {
            if (!$DB->record_exists('local_skillgap_desig_hierarchy', [
                'department'       => $department,
                'designation_name' => $name,
            ])) {
                $max_order++;
                $DB->insert_record('local_skillgap_desig_hierarchy', [
                    'department'         => $department,
                    'designation_name'   => $name,
                    'parent_designation' => null,
                    'sort_order'         => $max_order,
                    'timecreated'        => $now,
                    'timemodified'       => $now,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Reorder designations for a department based on a given ordered array of IDs.
     * Each ID gets sort_order = its 1-based index position.
     */
    public static function reorder(array $ordered_ids): void {
        global $DB;

        foreach ($ordered_ids as $position => $id) {
            $DB->set_field('local_skillgap_desig_hierarchy', 'sort_order',    $position + 1, ['id' => (int)$id]);
            $DB->set_field('local_skillgap_desig_hierarchy', 'timemodified',  time(),        ['id' => (int)$id]);
        }
    }

    /**
     * Get a flat ordered list for display — ordered by sort_order ASC.
     * Position 1 = top of ladder (highest rank).
     * Returns same structure as flatten_tree() used to, so templates work unchanged.
     */
    public static function build_tree(string $department): array {
        $flat = self::get_hierarchy($department);
        $result = [];
        foreach ($flat as $item) {
            $result[] = [
                'id'               => $item->id,
                'designation_name' => $item->designation_name,
                'sort_order'       => $item->sort_order,
                'depth'            => 0,
                'children'         => [],
            ];
        }
        return $result;
    }

    /**
     * Flatten — now just returns the flat list since there are no children.
     */
    public static function flatten_tree(array $tree, int $depth = 0): array {
        $result = [];
        foreach ($tree as $node) {
            $item           = $node;
            $item['depth']  = 0;
            $item['indent'] = '';
            unset($item['children']);
            $result[] = $item;
        }
        return $result;
    }

    /**
     * Get a designation hierarchy record by id.
     */
    public static function get_by_id(int $id): ?object {
        global $DB;
        return $DB->get_record('local_skillgap_desig_hierarchy', ['id' => $id]) ?: null;
    }

    /**
     * Get designation hierarchy id by department + name.
     * Creates the record if it does not exist yet (appended at bottom).
     */
    public static function get_or_create_id(string $department, string $designation_name): int {
        global $DB;

        $existing = $DB->get_record('local_skillgap_desig_hierarchy', [
            'department'       => $department,
            'designation_name' => $designation_name,
        ]);
        if ($existing) {
            return (int)$existing->id;
        }

        $max_order = (int)$DB->get_field_sql(
            "SELECT COALESCE(MAX(sort_order), 0) FROM {local_skillgap_desig_hierarchy}
              WHERE department = :dept AND (deleted IS NULL OR deleted = 0)",
            ['dept' => $department]
        );

        return (int)$DB->insert_record('local_skillgap_desig_hierarchy', [
            'department'         => $department,
            'designation_name'   => $designation_name,
            'parent_designation' => null,
            'sort_order'         => $max_order + 1,
            'timecreated'        => time(),
            'timemodified'       => time(),
        ]);
    }

    /**
     * Get the next designation up the ladder (for promotion).
     *
     * Hierarchy is defined by sort_order ASC:
     *   sort_order 1 = highest rank (e.g. HOD / CEO)
     *   sort_order N = lowest rank  (e.g. Assistant / Intern)
     *
     * Promotion = moving UP = finding the record with the next LOWER sort_order.
     * Example: Assistant (5) → Assistant Manager (4) → Manager (3) → HOD (1).
     *
     * Returns null if the user is already at the top (sort_order = 1).
     */
    public static function get_next_designation(string $department, string $current_designation): ?string {
        global $DB;

        // Find current record's sort_order.
        $current = $DB->get_record_sql(
            "SELECT id, sort_order FROM {local_skillgap_desig_hierarchy}
              WHERE department = :dept
                AND designation_name = :name
                AND (deleted IS NULL OR deleted = 0)
              LIMIT 1",
            ['dept' => $department, 'name' => $current_designation]
        );

        if (!$current) {
            return null;
        }

        if ((int)$current->sort_order <= 1) {
            // Already at the top of the ladder.
            return null;
        }

        // The next designation up = closest sort_order BELOW current (strictly less).
        $next = $DB->get_record_sql(
            "SELECT designation_name FROM {local_skillgap_desig_hierarchy}
              WHERE department = :dept
                AND sort_order < :cur_order
                AND (deleted IS NULL OR deleted = 0)
              ORDER BY sort_order DESC
              LIMIT 1",
            ['dept' => $department, 'cur_order' => (int)$current->sort_order]
        );

        return $next ? $next->designation_name : null;
    }

    /**
     * Get the user's current designation from their profile field.
     */
    public static function get_user_designation(int $userid): string {
        global $DB;

        $field = $DB->get_record('user_info_field', ['shortname' => 'designation'], 'id', IGNORE_MISSING);
        if (!$field) {
            return '';
        }

        $data = $DB->get_field('user_info_data', 'data', [
            'userid'  => $userid,
            'fieldid' => $field->id,
        ]);

        return $data ? trim($data) : '';
    }

    /**
     * Build <select> options ordered by sort_order (position 1 = top).
     */
    public static function get_select_options(string $department, int $selected_id = 0): array {
        $flat    = self::get_hierarchy($department);
        $options = [['id' => '', 'name' => '-- Select Designation --', 'selected' => !$selected_id, 'indent' => '']];
        foreach ($flat as $item) {
            $options[] = [
                'id'       => $item->id,
                'name'     => $item->designation_name,
                'selected' => ((int)$item->id === $selected_id),
                'indent'   => '',
            ];
        }
        return $options;
    }

    /**
     * Update a user's designation profile field to a new value.
     */
    public static function set_user_designation(int $userid, string $new_designation): bool {
        global $DB;

        $field = $DB->get_record('user_info_field', ['shortname' => 'designation'], 'id', IGNORE_MISSING);
        if (!$field) {
            return false;
        }

        $existing = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $field->id]);
        if ($existing) {
            $DB->set_field('user_info_data', 'data', $new_designation, ['id' => $existing->id]);
        } else {
            $DB->insert_record('user_info_data', [
                'userid'     => $userid,
                'fieldid'    => $field->id,
                'data'       => $new_designation,
                'dataformat' => 0,
            ]);
        }
        return true;
    }
}
