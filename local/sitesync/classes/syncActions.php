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
namespace local_sitesync;

defined('MOODLE_INTERNAL') || die();

class syncActions {

    /**
     * Performs the specified action by calling the corresponding method.
     *
     * This method checks if the requested action has a corresponding method in the
     * class, and if so, calls that method with the provided configuration.
     * If the method does not exist, it returns an error message.
     *
     * @param string $action The name of the action to perform.
     * @param mixed $config The configuration data required for the action.
     * @return mixed The result of the action method, or an error message.
     */
    public function perform_action($action, $config) {
        $functionname = "action_".$action;

        // Check if the function exists before calling it.
        if (method_exists($this, $functionname)) {
            // Call the function dynamically.
            return call_user_func(array($this, $functionname), $config);
        } else {
            // Handle the case when the function doesn't exist.
            return "Function $functionname does not exist.";
        }
    }

    /**
     * Retrieves the sync configuration data.
     *
     * This method returns an array containing the site URL and access token
     * from the local_sitesync plugin configuration.
     *
     * @param mixed $config The configuration data required for the action.
     * @return array The sync configuration data.
     */
    public function action_get_sync_configs($config) {

        $encryptor = new \local_sitesync\JsonEncryptor();

        $configdata = [
            "siteurl" => get_config('local_sitesync', 'moodleurl'),
            "accesstoken" => get_config('local_sitesync', 'accesstoken'),
            "masterkey" => $encryptor->isMasterPublicKeyAvailable(),
        ];

        return $configdata;
    }

    /**
     * Validates the master key provided in the configuration.
     *
     * This method takes the master public key from the configuration, compares it
     * with the public key of the JsonEncryptor, and returns the status and a
     * message indicating whether the master key is valid or not.
     *
     * @param mixed $config The configuration data required for the action.
     * @return array An array containing the status and message of the master key validation.
     */
    public function action_validate_master_key($config) {
        $config = json_decode($config);

        $vaidekey = 'invalid';
        $message  = "Invalid master key";
        $encryptor = new \local_sitesync\JsonEncryptor();
        $publickey = $encryptor->getPublicKey();
        $masterkey = $encryptor->separatePublicnEpoch($config->master_public_key);

        if ($publickey == $masterkey) {
            $vaidekey = 'valid';
            $message = "Valid master key";

        }
        $configdata = [
            "status" => $vaidekey,
            "message" => $message
        ];
        return $configdata;
    }

    /**
     * Prepares the sync data for the specified plugin.
     *
     * This method retrieves the configuration settings for the specified plugin
     * and returns them as an array. The configuration settings are obtained
     * using the Synchronizer class.
     *
     * @param mixed $config The configuration data required for the action.
     * @return array The prepared sync data.
     */
    public function action_prepare_sync_data($config) {

        $config = json_decode($config);

        $synchronizer = new \local_sitesync\Synchronizer($config->pluginname);

        $configuration = [];

        foreach ($config->themeselectedConfigs as $setting) {
            $configuration[$setting] = $synchronizer->get_setting_config($setting);
        }

        // $encryptor = new \local_sitesync\JsonEncryptor();

        // $configuration =  $encryptor->encryptJson($configuration, $config->masterkey);

        return $configuration;
    }

    /**
     * Synchronizes the settings for the specified plugin on the master site.
     *
     * This method performs the following actions:
     * 1. Checks if a master backup of the theme configuration exists, and creates one if it doesn't.
     * 2. Retrieves the configuration settings for the specified plugin using the Synchronizer class.
     * 3. Updates the configuration settings on the master site.
     * 4. Creates a backup of the current theme configuration and stores it in the local_sitesync plugin.
     * 5. Purges all caches.
     *
     * @param mixed $config The configuration data required for the action.
     * @return array The status of the configuration updates.
     */
    public function action_sync_settings_on_master($config) {

        $config = json_decode($config);

        $encryptor = new \local_sitesync\JsonEncryptor();
        $publickey = $encryptor->getPublicKey();

        $key1 = str_replace(["\r", "\n", " "], '', $config->masterkey);
        $key2 = str_replace(["\r", "\n", " "], '', $publickey);

        if ($key1 != $key2) {
            return [
                "status" => "error",
                "message" => get_string('invalidmasterkey', 'local_sitesync')
            ];
        }

        $backuphandler = new  \local_sitesync\JsonIncrementalBackup();

        $backup = (array)get_config('theme_remui');

        if (!$backuphandler->masterBackupExist()) {

            $backuphandler->setMasterBackup($backup);
            set_config("master_backup_tstamp", date("Y-m-d H:i:s"), 'local_sitesync');

        }

        $synchronizer = new \local_sitesync\Synchronizer($config->pluginname);

        $configurationstatus = [];

        foreach ($config->themeselectedConfigs as $key => $value) {
            $configurationstatus[$key] = $synchronizer->set_setting_config($key, $value);
        }

        $backuphandler->reduce_maxlimit_reached_backups();

        $confignames = $backuphandler->get_backup_current_n_last_confignames();

        // $lastbackup = json_decode(get_config('local_sitesync', $confignames['lastconfigkey']),true) ?: $backuphandler->getMasterBackup();


        $backuphandler = new  \local_sitesync\JsonIncrementalBackup($backup);

        $currentthemeconfig = (array)get_config('theme_remui');

        $configbackup = $backuphandler->createBackup($currentthemeconfig);

        if (count($configbackup) > 0) {
            set_config($confignames['configkey'], json_encode($configbackup), 'local_sitesync');
            set_config($confignames['configkey']."_tstamp", date("Y-m-d H:i:s"), 'local_sitesync');
        }
        purge_all_caches();
        return [
            "status" => "success",
            "message" => "sync complete"
        ];;

    }

    /**
     * Restores a backup of the plugin configuration.
     *
     * This method:
     * 1. Decodes the provided configuration data.
     * 2. Retrieves the backup from the local_sitesync plugin.
     * 3. Restores the backup configuration using the Synchronizer class.
     * 4. Removes the backup from the local_sitesync plugin.
     * 5. Purges all caches.
     *
     * @param mixed $config The configuration data required for the restore action.
     * @return bool The status of the restore operation.
     */
    public function action_restore_backup($config) {
        $config = json_decode($config);

        $backup = json_decode(get_config('local_sitesync', $config->restoreconfig), true);

        $backuphandler = new \local_sitesync\JsonIncrementalBackup();

        $all_incremental_backups_upto_selected_config = $backuphandler->get_all_backups_without_master_backup($config->restoreconfig);

        $initialdata = $backuphandler->getMasterBackup();

        $restoreabledata = $backuphandler->restoreFullData($initialdata, $all_incremental_backups_upto_selected_config);

        $restorestatus = false;

        if ($restoreabledata) {

            $synchronizer = new \local_sitesync\Synchronizer($config->pluginname);

            foreach ($restoreabledata as $key => $value) {
                $configurationstatus[$key] = $synchronizer->set_setting_config($key, $value);
            }
            $restorestatus = true;
        }

        $backuphandler->remove_backups($config->restoreconfig);

        purge_all_caches();

        return  $restorestatus;
    }
}
