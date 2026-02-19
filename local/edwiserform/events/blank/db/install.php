<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Install hook
 * @package   edwiserformevents_blank
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

/**
 * Install hook for blank event
 * @return bool true
 */
function xmldb_edwiserformevents_blank_install() {
    global $DB;
    $record = $DB->get_record('efb_form_templates', array('name' => 'blank'));
    $new  = false;
    if (!$record) {
        $new = true;
        $record = new stdClass;
        $record->name = 'blank';
    }

    // @codingStandardsIgnoreLine
    $record->definition = '';
    if ($new) {
        $DB->insert_record('efb_form_templates', $record, false);
        return;
    }
    $DB->update_record('efb_form_templates', $record, false);
    return;
}
