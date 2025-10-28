<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * @author Andreas Grabs <support@eledia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package eledia_coursewizard
 */
function xmldb_local_coursewizard_upgrade($oldversion) {
    global $DB,$CFG;
    $dbman = $DB->get_manager();
    if ($oldversion < 2018072401) {
        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2018072401,'local', 'coursewizard');
    }
    return true;
}
