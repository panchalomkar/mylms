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

$string['pluginname'] = 'Site Sync';
$string['overview'] = 'Site sync overview';
$string['tab_connection'] = 'Connection';
$string['site_url'] = 'Site Url';
$string['access_token'] = 'Access Token';
$string['check_connection'] = 'Check Connection';
$string['reset_connection'] = 'Reset';
$string['tab_sync'] = 'Synchronization Settings';

$string['tab_backup'] = 'Backup';
$string['deactivate'] = "Deactivate";
$string['activate'] = "Activate";
$string['backupdata'] = "Backup Data";
$string['restore'] = "Start Restore";
$string['restoreinfo'] = "<strong>Note:</Strong> Restoring this backup will delete all next backups";
$string['restoreinprogress'] = "Restore in progress";
$string['restorecompleted'] = "Restore successful";
$string['syncinprogress'] = "Sync in progress";
$string['synccompleted'] = "Sync successful";
$string['noconfigurationselected'] = "No configuration selected";
$string['mastersitenotavailable'] = "Please connect your site to production site";
$string['keygeneratedsuccessfully'] = "Key generated successfully";
$string['textcopied'] = "Secret public key copied";
$string['sitesync_exists_fail'] = 'Sitesync plugin not available';
$string['sitesync_version_fail'] = 'Edwiser Sitesync version is not same';
$string['current_theme_fail'] = 'Current theme is not same';
$string['current_theme_version_fail'] = 'Current theme version is not same';
$string['is_remui_active_fail'] = 'Remui theme is not active';
$string['is_secure_site_fail'] = 'Connection is not secured cannot connect over http';
$string["is_remui_exist_fail"] = "Remui not found";
$string["remui_version_fail"] = "RemUI version is not same";
$string['nocompatiblesites'] = 'Site are not compatible';
$string['reset'] = 'Reset';
$string['connectedstatus'] = 'Connected';
$string['noconnectinwarning'] = "Site sync criteria are not satisfied. Go to the Connection tab and check your connection.";
$string['remuinotfound'] = "Edwiser RemUI theme was not found on one of the sites.";
$string['showchanges'] = "View changes";
$string['invalidmasterkey'] = "Invalid secret public key sync stopped";

$string['secretkey'] = "Secret public key";
$string['secretkeyaddinfo'] = "Generate the key on the production site and add it to the popup during settings synchronization.";
$string['addpublickeyinfo'] = 'Add secret public key from production site.';
$string['addpublickeyinfo2'] = 'Add secret public key';

$string['chekingconnection'] = 'Checking connection...';
$string['connectioncheckdone'] = 'Connected successfully.';
$string['chekingcompatibility'] = 'Checking Compatibility...';
$string['compatibilitycheckdone'] = 'Compatibility check done.';
$string['checkingservervalidity'] = 'Validating server connection ... ';
$string['servalidatated'] = 'Server connection validated successfully.';
$string['remuinotactivenotification'] = '"RemUI theme is not active. The settings are synced. Change the theme to RemUI to see the changes."';
$string['backuptablehead_version'] = 'Version';
$string['backuptablehead_time'] = 'Time';
$string['backuptablehead_action'] = 'Action';
$string['startsyncbtntext'] = 'Start sync';

$string['user_syncinfo'] = 'The feature is currently available as an experimental setting for users to test and provide feedback.
The "Site Sync" feature allows you to seamlessly transfer all RemUI theme-related settings including Visual Personalizer (Colour, branding, header footer, etc.) configurations from a staging Moodle site to a live Moodle site.
<br>For more details, please refer to the official <a href="https://remui-docs.edwiser.org/site-sync" target="_blank">documentation</a>';
