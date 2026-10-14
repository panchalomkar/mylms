<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_businessintelligence\local;

/**
 * Detects which optional data sources (plugins/tables/columns) exist on this site.
 *
 * Every report degrades gracefully: if a source plugin is missing its numbers are 0 instead of erroring.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class source {
    /** @var array */
    private static $tables = [];
    /** @var array */
    private static $fields = [];

    /**
     * Does a table exist?
     *
     * @param string $table without prefix
     * @return bool
     */
    public static function has_table(string $table): bool {
        global $DB;
        if (!array_key_exists($table, self::$tables)) {
            try {
                self::$tables[$table] = $DB->get_manager()->table_exists($table);
            } catch (\Throwable $e) {
                self::$tables[$table] = false;
            }
        }
        return self::$tables[$table];
    }

    /**
     * Does a column exist?
     *
     * @param string $table
     * @param string $field
     * @return bool
     */
    public static function has_field(string $table, string $field): bool {
        global $DB;
        $k = $table . '.' . $field;
        if (!array_key_exists($k, self::$fields)) {
            if (!self::has_table($table)) {
                self::$fields[$k] = false;
            } else {
                $cols = $DB->get_columns($table);
                self::$fields[$k] = isset($cols[$field]);
            }
        }
        return self::$fields[$k];
    }

    /**
     * Plugin setting with default.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function config(string $name, $default) {
        $v = get_config('local_businessintelligence', $name);
        return ($v === false || $v === null || $v === '') ? $default : $v;
    }

    /**
     * Comma separated integers setting.
     *
     * @param string $name
     * @param string $default
     * @return int[]
     */
    public static function config_ints(string $name, string $default): array {
        return array_values(array_filter(array_map('intval', explode(',', (string)self::config($name, $default))),
            function($v) {
                return $v > 0;
            }));
    }

    /**
     * Is the standard log store usable for activity counts?
     *
     * @return bool
     */
    public static function has_log(): bool {
        $enabled = get_config('tool_log', 'enabled_stores');
        return self::has_table('logstore_standard_log')
            && ($enabled === false || strpos((string)$enabled, 'logstore_standard') !== false);
    }

    /**
     * Percentage expression of a skill-gap score row.
     *
     * @param string $alias
     * @return string
     */
    public static function skillgap_pct(string $alias = 's'): string {
        return "(CASE WHEN {$alias}.maxscore > 0 THEN {$alias}.score * 100.0 / {$alias}.maxscore ELSE {$alias}.score END)";
    }
}
