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
 * Custom user profile field helpers (department, designation, branchname, reportingmanager...).
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile {
    /** @var array shortname => field record|false */
    private static $fields = [];

    /** Prefix some sites store in front of the manager e-mail. */
    const MANAGER_PREFIX = 'reportingmanager:';

    /**
     * Configured shortname for a logical field key.
     *
     * @param string $key department|designation|branch|manager|trainertype
     * @return string
     */
    public static function shortname(string $key): string {
        $defaults = [
            'department'  => 'department',
            'designation' => 'designation',
            'branch'      => 'branchname',
            'manager'     => 'reportingmanager',
            'trainertype' => 'trainertype',
        ];
        $value = get_config('local_businessintelligence', 'field_' . $key);
        return $value ? (string)$value : ($defaults[$key] ?? $key);
    }

    /**
     * Field record for a logical key.
     *
     * @param string $key
     * @return \stdClass|null
     */
    public static function field(string $key): ?\stdClass {
        global $DB;
        $short = self::shortname($key);
        if (!array_key_exists($short, self::$fields)) {
            self::$fields[$short] = $DB->get_record('user_info_field', ['shortname' => $short],
                'id, shortname, datatype, param1') ?: false;
        }
        return self::$fields[$short] ?: null;
    }

    /**
     * Field id for a logical key (0 when the field does not exist).
     *
     * @param string $key
     * @return int
     */
    public static function field_id(string $key): int {
        $f = self::field($key);
        return $f ? (int)$f->id : 0;
    }

    /**
     * Normalise a reporting-manager value to a lower-case e-mail.
     *
     * @param string|null $value
     * @return string
     */
    public static function normalise_manager(?string $value): string {
        $value = \core_text::strtolower(trim((string)$value));
        if (strpos($value, self::MANAGER_PREFIX) === 0) {
            $value = trim(substr($value, strlen(self::MANAGER_PREFIX)));
        }
        return $value;
    }

    /**
     * Distinct non-empty values of a profile field (plus menu options).
     *
     * @param string $key
     * @return string[]
     */
    public static function distinct_values(string $key): array {
        global $DB;
        $field = self::field($key);
        if (!$field) {
            return [];
        }
        $values = [];
        if ($field->datatype === 'menu' && !empty($field->param1)) {
            foreach (preg_split('/\R/', $field->param1) as $opt) {
                $opt = trim($opt);
                if ($opt !== '') {
                    $values[$opt] = $opt;
                }
            }
        }
        $text = $DB->sql_compare_text('d.data', 255);
        $rs = $DB->get_recordset_sql("SELECT DISTINCT {$text} AS val
                                        FROM {user_info_data} d
                                       WHERE d.fieldid = :fid", ['fid' => $field->id]);
        foreach ($rs as $r) {
            $v = trim((string)$r->val);
            if ($v !== '') {
                $values[$v] = $v;
            }
        }
        $rs->close();
        \core_collator::asort($values);
        return array_values($values);
    }

    /**
     * Reporting managers: e-mail => display name.
     *
     * @return array
     */
    public static function managers(): array {
        global $DB;
        $emails = [];
        foreach (self::distinct_values('manager') as $raw) {
            $email = self::normalise_manager($raw);
            if ($email !== '') {
                $emails[$email] = $email;
            }
        }
        if (!$emails) {
            return [];
        }
        $out = $emails;
        foreach (array_chunk(array_values($emails), 500) as $chunk) {
            $p = new params();
            $in = $p->in($chunk);
            $users = $DB->get_records_sql("SELECT u.id, u.email, " . implode(', ', array_map(function($f) {
                    return 'u.' . $f;
                }, \core_user\fields::get_name_fields())) . "
                                             FROM {user} u
                                            WHERE LOWER(u.email) {$in} AND u.deleted = 0", $p->values);
            foreach ($users as $u) {
                $out[\core_text::strtolower($u->email)] = fullname($u);
            }
        }
        \core_collator::asort($out);
        return $out;
    }

    /**
     * Values of a field for a set of users.
     *
     * @param string $key
     * @param int[] $userids
     * @return array userid => value
     */
    public static function values_for(string $key, array $userids): array {
        global $DB;
        $fid = self::field_id($key);
        if (!$fid || !$userids) {
            return [];
        }
        $out = [];
        foreach (array_chunk(array_values($userids), 1000) as $chunk) {
            $p = new params();
            $in = $p->in($chunk);
            $rs = $DB->get_recordset_sql("SELECT d.userid, d.data FROM {user_info_data} d
                                           WHERE d.fieldid = " . $p->add($fid) . " AND d.userid {$in}", $p->values);
            foreach ($rs as $r) {
                $out[(int)$r->userid] = trim((string)$r->data);
            }
            $rs->close();
        }
        return $out;
    }
}
