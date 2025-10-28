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

require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

global $CFG;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/sitesync/overview.php'));
$PAGE->set_title(get_string('pluginname', 'local_sitesync'));
// $PAGE->set_heading(get_string('pluginname', 'local_sitesync'));
$PAGE->set_pagelayout('mydashboard');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_sitesync'));

$context = [];
// Connection Data.
$context['moodleurl'] = get_config('local_sitesync', 'moodleurl');
$context['accesstoken'] = get_config('local_sitesync', 'accesstoken');
$context['connection'] = get_config('local_sitesync', 'connection');

// Fetch existing keys.
$encryptor = new \local_sitesync\JsonEncryptor();
$context['publickey'] = $encryptor->getPublicKey();

// Generate a checkbox list to select config options.
$synchronizer = new \local_sitesync\Synchronizer("theme_remui");
$context['configoptions'] = $synchronizer->get_config_names_with_keys($synchronizer->get_all_setting_config(true));

$context['remuiexist'] = true;
if (count($context['configoptions']) == 0) {
    $context['remuiexist'] = false;
}
$PAGE->requires->data_for_js('master_public_key', $encryptor->isMasterPublicKeyAvailable());
$PAGE->requires->data_for_js('connectionstatus', $context['connection']);

$context['feedbackcollected'] = get_config('local_sitesync', 'feedbackcollected');

$backuphandler = new \local_sitesync\JsonIncrementalBackup();
$backupdata = $backuphandler->get_backup_context_data();

if (count($backupdata) > 0) {
    $context['backupcontext'] = $backupdata;
    $context['backupexist'] = count($backupdata);
}

$PAGE->requires->strings_for_js(array(
    'restoreinprogress',
    'restorecompleted',
    'syncinprogress',
    'synccompleted',
    'noconfigurationselected',
    'mastersitenotavailable',
    'keygeneratedsuccessfully',
    'textcopied',
    'is_secure_site_fail',
    'is_remui_active_fail',
    'reset',
    'connectedstatus',
    'chekingconnection',
    'connectioncheckdone',
    'chekingcompatibility',
    'compatibilitycheckdone',
    'checkingservervalidity',
    'servalidatated',
    'addpublickeyinfo',
    'addpublickeyinfo2',
    'remuinotactivenotification',
    'startsyncbtntext'

), 'local_sitesync');

// Add your page content here.
echo $OUTPUT->render_from_template('local_sitesync/overview', $context);
$PAGE->requires->js_call_amd('local_sitesync/connection', 'init');

echo $OUTPUT->footer();
