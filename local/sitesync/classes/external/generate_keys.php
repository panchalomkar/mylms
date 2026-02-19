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
 * Version details.
 *
 * @package    local_sitesync
 * @copyright  2023 WisdmLabs <support@wisdmlabs.com>
 * @author     Gourav G <support@wisdmlabs.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_sitesync\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class generate_keys extends \external_api {

    public static function execute_parameters() {
        return new \external_function_parameters([]);
    }

    public static function execute() {
        global $DB;

        $encryptor = new \local_sitesync\JsonEncryptor();
        $encryptor->generateKeys();


        return [
            'publickey' => $encryptor->getPublickey().$encryptor->getExpirationAt()
        ];
    }

    public static function execute_returns() {
        return new \external_single_structure([
            'publickey' => new \external_value(PARAM_TEXT, 'Public key')
        ]);
    }
}
