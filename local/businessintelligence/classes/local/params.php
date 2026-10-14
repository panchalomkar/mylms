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
 * Collects uniquely-named SQL parameters (Moodle forbids repeating a named placeholder).
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class params {
    /** @var int global counter so names stay unique even across nested builders */
    private static $counter = 0;

    /** @var array name => value */
    public $values = [];

    /**
     * Register a value and return its placeholder (":lbiN").
     *
     * @param mixed $value
     * @return string
     */
    public function add($value): string {
        $name = 'lbi' . (++self::$counter);
        $this->values[$name] = $value;
        return ':' . $name;
    }

    /**
     * Build an IN (...) clause for a list of values.
     *
     * @param array $values
     * @param bool $equal false for NOT IN
     * @return string SQL fragment starting with IN / NOT IN / = / <>
     */
    public function in(array $values, bool $equal = true): string {
        global $DB;
        if (empty($values)) {
            return $equal ? '= ' . $this->add(-1) : '<> ' . $this->add(-1);
        }
        $prefix = 'lbin' . (++self::$counter) . 'x';
        [$sql, $p] = $DB->get_in_or_equal(array_values($values), SQL_PARAMS_NAMED, $prefix, $equal);
        $this->values += $p;
        return $sql;
    }
}
