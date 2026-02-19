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
 * Edwiser Usage Tracking.
 *
 * JSON incremental backup.
 * @package   theme_remui
 * @copyright (c) 2024 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @author Gourav G
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sitesync;

defined('MOODLE_INTERNAL') || die();

class JsonIncrementalBackup {
    private $lastBackupData;

    private $masterbackup;

    private $maxbackuplimit  = 10;
    private $initialstring = "config_backup_v";
    public function __construct($initialBackupData = []) {
        // Initialize the backup data with an optional initial backup
        $this->lastBackupData = $initialBackupData;
    }

    /**
     * Compares two arrays and returns the differences (changes).
     * @param array $oldData The old JSON data.
     * @param array $newData The new JSON data.
     * @return array The differences between oldData and newData.
     */
    private function getDifferences($oldData, $newData) {
        $differences = [];

        // Find updated and new keys
        foreach ($newData as $key => $value) {
            if (!array_key_exists($key, $oldData) || $oldData[$key] !== $value) {
                $differences[$key] = $value;
            }
        }

        // Find removed keys
        foreach ($oldData as $key => $value) {
            if (!array_key_exists($key, $newData)) {
                $differences[$key] = null; // Use null to represent a deletion
            }
        }

        return $differences;
    }

    /**
     * Applies the differences to the old data to recreate the full new version.
     * @param array $oldData The old JSON data.
     * @param array $differences The differences (changes) to apply.
     * @return array The updated full version of the data.
     */
    private function applyDifferences($oldData, $differences) {
        foreach ($differences as $key => $value) {
            if ($value === null) {
                unset($oldData[$key]); // Handle deletion
            } else {
                $oldData[$key] = $value; // Handle update or addition
            }
        }

        return $oldData;
    }

    /**
     * Creates an incremental backup by comparing the new JSON data with the last backup data.
     * @param array $currentData The current JSON data.
     * @return array The differences that represent the incremental backup.
     */
    public function createBackup($currentData) {
        // Find the differences between current data and last backup
        $differences = $this->getDifferences($this->lastBackupData, $currentData);

        // If there are differences, save them as an incremental backup
        if (!empty($differences)) {
            $this->lastBackupData = $currentData; // Update the last backup with the current data
            return $differences; // Return the differences for this incremental backup
        }

        return []; // No changes detected
    }

    /**
     * Restores the full configuration by applying all incremental backups.
     * @param array $initialData The initial backup data.
     * @param array $incrementalBackups List of incremental backups (each one is an array of differences).
     * @return array The fully restored configuration data.
     */
    public function restoreFullData($initialData, $incrementalBackups) {
        $restoredData = $initialData;

        foreach ($incrementalBackups as $backup) {
            $restoredData = $this->applyDifferences($restoredData, $backup);
        }

        return $restoredData;
    }

    /**
     * Gets the master backup data.
     * @return array The master backup data decoded from JSON.
     */

    public function getMasterBackup() {

        $this->masterbackup = get_config('local_sitesync', 'master_backup');

        return json_decode($this->masterbackup,true);
    }

    /**
     * Checks if a master backup exists.
     * @return bool True if a master backup exists, false otherwise.
     */
    public function masterBackupExist(){

        if(get_config('local_sitesync', 'master_backup')){
            return true;
        }

        return false;
    }

    /**
     * Sets the master backup data.
     *
     * @param array $backup The backup data to be set as the master backup.
     */
    public function setMasterBackup($backup) {

        $backup = json_encode($backup);

        set_config('master_backup', $backup,'local_sitesync');
    }

    /**
     * Retrieves the configuration records for the 'local_sitesync' plugin.
     *
     * This function queries the 'config_plugins' table to fetch all records where the
     * plugin name is 'local_sitesync' and the name starts with 'config_backup_v'.
     * The records are ordered by the 'id' column in ascending order.
     *
     * @return array The configuration records matching the criteria.
     */
    public function get_config_records(){
        global $DB;

        $sql = "SELECT * FROM {config_plugins}  WHERE plugin = :plugin  AND name LIKE :pattern ORDER BY id ASC";

        $params = [
            'plugin' => 'local_sitesync',
            'pattern' => 'config_backup_v%'
        ];

        $configrecords = $DB->get_records_sql($sql, $params);

        return $configrecords;
    }


    /**
     * Retrieves the current and last configuration backup names.
     *
     * This function iterates through the backup configuration records up to the
     * `$this->maxbackuplimit` and finds the first empty backup record. It then
     * sets the `$configkey` to the initial string plus the index of the empty
     * record, and the `$lastconfigkey` to the initial string plus the index of
     * the previous record.
     *
     * @return array An associative array with the following keys:
     *   - 'configkey': The name of the current backup configuration which can be added.
     *   - 'lastconfigkey': The name of the last backup configuration.
     */
    public function get_backup_current_n_last_confignames() {

        $configkey = false;
        $lastconfigkey = false;

        for ($i = 1; $i <= $this->maxbackuplimit; $i++) {

            $backup = get_config('local_sitesync', 'config_backup_v'.$i);

            if(!$backup){
                $configkey =  $this->initialstring.$i;
                $lastconfigkey  = $this->initialstring.($i-1);
                break;
            }
        }

        return [
            'configkey' => $configkey,
            'lastconfigkey' => $lastconfigkey
        ];
    }

    /**
     * Retrieves the backup context data, including the master backup and any incremental backups.
     *
     * This function iterates through the backup configuration records up to the `$this->maxbackuplimit`
     * and collects the backup data for each version. It returns an array of backup records, where each
     * record contains the version and the keys of the backup data.
     *
     * @return array An array of backup records, where each record is an associative array with the
     *               following keys:
     *               - 'version': The version of the backup, either 'master_backup' or 'config_backup_v{index}'.
     *               - 'backup': An array of the keys of the backup data.
     */
    public function get_backup_context_data() {
        $backuprecords = [];
        $masterbackup = $this->getMasterBackup();
        $synchronizer = new \local_sitesync\Synchronizer("theme_remui");

        if ($masterbackup) {
            $backuprecords[] = [
                'version' => 'master_backup',
                'tstamp' => get_config('local_sitesync', 'master_backup_tstamp'),
                'backup' => $synchronizer->get_config_names_with_keys($masterbackup)
            ];
        }

        for ($i = 1; $i <= $this->maxbackuplimit; $i++) {

            $configname = 'config_backup_v'.$i;
            $backup = get_config('local_sitesync', $configname);

            if ($backup) {

                $formattedabackup = $synchronizer->get_config_names_with_keys(json_decode($backup, true));
                $backuprecords[] = [
                    'version' => $configname,
                    'tstamp' => get_config('local_sitesync', $configname.'_tstamp'),
                    'backup' => $formattedabackup,
                ];

            }else{
                break;
            }
        }

        return  $backuprecords;

    }


    public function get_all_backups_without_master_backup($backupname=false){
        // $backuprecords =[];
        // $masterbackup = $this->getMasterBackup();

        // if($masterbackup){
        //     $backuprecords[] = $masterbackup;
        // }
        $limit = $this->maxbackuplimit;

        if($backupname){
            $pattern = '/\d+/';
            preg_match($pattern, $backupname, $matches);

             $limit = $matches[0];
        }
        for ($i = 1; $i <= $limit; $i++) {

            $configname =  'config_backup_v'.$i;
            $backup = get_config('local_sitesync', $configname);

            if($backup){
                $backuprecords[] = Json_decode($backup,true);

            }else{
                break;
            }
        }

        return  $backuprecords;
    }

    /**
     * Removes any incremental backups that are beyond the current backup being processed.
     *
     * This function first determines the index of the current backup being processed. If the current
     * backup is the "master_backup", the index is set to 0. Otherwise, the index is extracted from
     * the backup name using a regular expression.
     *
     * Once the index is determined, the function loops through any remaining backup configurations
     * (up to the `$this->maxbackuplimit`) and removes them using the `unset_config()` function.
     *
     * @param string $currentbackup The name of the current backup being processed.
     */
    public function remove_backups($currentbackup){

        if($currentbackup == 'master_backup'){
            $count = 0;
        }else{
            $pattern = '/\d+/';
            preg_match($pattern, $currentbackup, $matches);
            $count = $matches[0];
        }
        for ($i = $count+1; $i <= $this->maxbackuplimit; $i++) {
            $name = 'config_backup_v'.$i;
            unset_config($name, 'local_sitesync');
            unset_config($name."_tstamp", 'local_sitesync');
        }
    }

    /**
     * Reduces the number of incremental backups by making the 1st backup as master backup and decrementing the other backups.
     *
     * This function first retrieves all the existing backup configurations using the `get_config_records()`
     * method. It then converts the backup objects into an array of arrays using `array_map()`.
     *
     * If the number of backups is equal to the `$this->maxbackuplimit`, the function performs the following
     * steps:
     * 1. Shifts the first backup from the `$arrayOfArrays` array and sets it as the "master_backup" using
     *    the `setMasterBackup()` method.
     * 2. Iterates through the remaining backups in `$arrayOfArrays` and sets them as individual backup
     *    configurations using the `set_config()` function.
     * 3. Removes the backup configuration for the last backup (the one that exceeded the limit) using the
     *    `unset_config()` function.
     *
     * Finally, the function returns `true`.
     */
    public function reduce_maxlimit_reached_backups(){

        $allbackups = $this->get_config_records();

        $arrayOfArrays = array_map(function($object) {
            return (array)$object;
        }, $allbackups);



        if(count($allbackups) == $this->maxbackuplimit){

            $firstbackup = array_shift($arrayOfArrays);

            $initialdata = $this->getMasterBackup();

            $restoreabledata = $this->restoreFullData($initialdata, $firstbackup);

            $this->setMasterBackup($restoreabledata);

            $count= 1;
            foreach($arrayOfArrays as $backup){

                set_config($this->initialstring.$count, $backup['value'],'local_sitesync');

                $count= $count+1;
            }

            unset_config($this->initialstring.$this->maxbackuplimit, 'local_sitesync');
            unset_config($this->initialstring.$this->maxbackuplimit.'_tstamp', 'local_sitesync');

        }

        return true;
    }
}
