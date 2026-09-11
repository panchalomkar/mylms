<?php
/**
 * Profile Field Mapper - maps Moodle custom profile fields to platform concepts.
 *
 * DOES NOT duplicate organisational hierarchy data. Reads directly from
 * Moodle custom profile fields as configured by admin.
 *
 * @package    mod_airoleplay
 * @copyright  2024 Enterprise AI Learning Platform
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_airoleplay\profile;

defined('MOODLE_INTERNAL') || die();

class profile_field_mapper {

    /** @var array In-memory cache of field mappings */
    private static ?array $mappings = null;

    /** @var array In-memory cache of user profiles [userid => profile] */
    private static array $user_cache = [];

    /**
     * Supported field purposes.
     */
    public const FIELD_PURPOSES = [
        'department',
        'designation',
        'manager',
        'team',
        'region',
        'businessunit',
        'grade',
        'employeeid',
        'joiningdate',
        'location',
    ];

    /**
     * Get the Moodle profile field shortname mapped to a purpose.
     *
     * @param  string $purpose One of FIELD_PURPOSES
     * @return string|null     Moodle profile field shortname, or null if unmapped
     */
    public static function get_field(string $purpose): ?string {
        $mappings = self::load_mappings();
        return $mappings[$purpose] ?? null;
    }

    /**
     * Get all available field mappings.
     *
     * @return array ['purpose' => 'shortname']
     */
    public static function get_all_mappings(): array {
        return self::load_mappings();
    }

    /**
     * Get resolved profile data for a user.
     *
     * Returns an associative array keyed by purpose:
     * ['department' => 'Engineering', 'designation' => 'Engineer', ...]
     *
     * @param  int   $userid
     * @return array
     */
    public static function get_user_profile(int $userid): array {
        if (isset(self::$user_cache[$userid])) {
            return self::$user_cache[$userid];
        }

        global $DB;

        $mappings = self::load_mappings();
        $profile  = [];

        if (empty($mappings)) {
            return $profile;
        }

        // Load Moodle custom profile field data for this user.
        // profile_load_custom_fields requires the user object.
        $user = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname');
        if (!$user) {
            return $profile;
        }

        profile_load_custom_fields($user);
        $custom = $user->profile ?? [];

        foreach ($mappings as $purpose => $shortname) {
            if ($shortname && isset($custom[$shortname])) {
                $profile[$purpose] = (string)$custom[$shortname];
            }
        }

        self::$user_cache[$userid] = $profile;
        return $profile;
    }

    /**
     * Get all users who report to a given manager.
     * Uses the 'manager' profile field which stores manager name or employee ID.
     *
     * @param  int   $manager_userid
     * @return int[] Array of user IDs
     */
    public static function get_team_members(int $manager_userid): array {
        global $DB;

        $manager_field = self::get_field('manager');
        if (!$manager_field) {
            return [];
        }

        // Get manager's identifier (name or employee ID).
        $manager_profile = self::get_user_profile($manager_userid);
        $emp_id_field    = self::get_field('employeeid');

        // Try employee ID first, fall back to username/name.
        $manager_identifier = null;
        if ($emp_id_field && isset($manager_profile['employeeid'])) {
            $manager_identifier = $manager_profile['employeeid'];
        } else {
            $mgr = $DB->get_record('user', ['id' => $manager_userid], 'firstname, lastname, username');
            if ($mgr) {
                $manager_identifier = fullname($mgr);
            }
        }

        if (!$manager_identifier) {
            return [];
        }

        // Find the profile field ID.
        $field = $DB->get_record('user_info_field', ['shortname' => $manager_field]);
        if (!$field) {
            return [];
        }

        // Get all users whose manager field matches.
        $rows = $DB->get_records_select(
            'user_info_data',
            "fieldid = :fieldid AND " . $DB->sql_like('data', ':value'),
            ['fieldid' => $field->id, 'value' => $DB->sql_like_escape($manager_identifier)],
            '',
            'userid'
        );

        return array_column((array)$rows, 'userid');
    }

    /**
     * Get all users in a specific department.
     *
     * @param  string $department
     * @return int[]
     */
    public static function get_department_members(string $department): array {
        global $DB;

        $dept_field = self::get_field('department');
        if (!$dept_field) {
            return [];
        }

        $field = $DB->get_record('user_info_field', ['shortname' => $dept_field]);
        if (!$field) {
            return [];
        }

        $rows = $DB->get_records(
            'user_info_data',
            ['fieldid' => $field->id, 'data' => $department],
            '',
            'userid'
        );

        return array_column((array)$rows, 'userid');
    }

    /**
     * Save field mapping configuration.
     *
     * @param array $mappings ['purpose' => 'shortname']
     */
    public static function save_mappings(array $mappings): void {
        global $DB;

        foreach ($mappings as $purpose => $shortname) {
            if (!in_array($purpose, self::FIELD_PURPOSES, true)) {
                continue;
            }

            $existing = $DB->get_record('airoleplay_profile_map', ['field_purpose' => $purpose]);

            if ($existing) {
                $existing->profile_field_shortname = clean_param($shortname, PARAM_ALPHANUMEXT);
                $DB->update_record('airoleplay_profile_map', $existing);
            } else {
                $record                        = new \stdClass();
                $record->field_purpose         = $purpose;
                $record->profile_field_shortname = clean_param($shortname, PARAM_ALPHANUMEXT);
                $record->is_active             = 1;
                $record->timecreated           = time();
                $DB->insert_record('airoleplay_profile_map', $record);
            }
        }

        // Invalidate cache.
        self::$mappings   = null;
        self::$user_cache = [];
    }

    /**
     * Get available Moodle custom profile fields for admin mapping UI.
     *
     * @return array [['shortname' => ..., 'name' => ..., 'datatype' => ...]]
     */
    public static function get_available_profile_fields(): array {
        global $DB;

        $fields = $DB->get_records('user_info_field', [], 'sortorder ASC', 'id, shortname, name, datatype, categoryid');

        return array_values(array_map(fn($f) => [
            'shortname' => $f->shortname,
            'name'      => format_string($f->name),
            'datatype'  => $f->datatype,
        ], (array)$fields));
    }

    /**
     * Load mappings from DB, with static cache.
     */
    private static function load_mappings(): array {
        if (self::$mappings !== null) {
            return self::$mappings;
        }

        global $DB;

        $rows = $DB->get_records('airoleplay_profile_map', ['is_active' => 1], '', 'field_purpose, profile_field_shortname');

        self::$mappings = [];
        foreach ($rows as $row) {
            self::$mappings[$row->field_purpose] = $row->profile_field_shortname;
        }

        return self::$mappings;
    }

    /**
     * Invalidate caches (call after user profile updates).
     */
    public static function invalidate_cache(?int $userid = null): void {
        if ($userid !== null) {
            unset(self::$user_cache[$userid]);
        } else {
            self::$mappings   = null;
            self::$user_cache = [];
        }
    }
}
