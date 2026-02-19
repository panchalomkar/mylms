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

class Synchronizer {

    private $plugin = "theme_remui";

    private $filebasedsettings = [];

    private $excludesettings = [];

    public function __construct($pluginname="") {

        $this->plugin = $pluginname;

        if ($pluginname == "theme_remui") {
            $this->filebasedsettings = ['faviconurl', 'logo', 'logomini', 'loaderimage',
                'slideimage1', 'slideimage2', 'slideimage3', 'slideimage4', 'slideimage5',
                'testimonialimage1', 'testimonialimage2', 'testimonialimage3', 'testimonialimage4', 'testimonialimage5',
                'loginpanellogo', 'loginsettingpic', 'global-colors-pagebackgroundimage'
            ];
            $this->excludesettings = ['course_completion_history_2024', 'course_completion_history_2024', 'course_enrollment_history_2024',
            'course_completion_history_2025', 'course_completion_history_2025', 'course_enrollment_history_2025',
            'edwcoursestats', 'edwdashboardstats', 'edd_remui_license_key', 'edd_remui_license_status', 'edd_remui_purchase_from', 'edd_remui_setup_license_data',
            'wdm_remui_license_trans', 'wdm_remui_product_site', 'version', 'cache_reset_time', 'dashboardpersonalizerinfo', 'plugin_update_transient', 'setupinstallcheck',
            'setupstatus', 'setupuserinfo', 'version', 'submited_feedbacks', 'googleanalytics'];
        }
    }

    /**
     * Retrieves the configuration settings for a given setting name.
     *
     * If the setting name is in the $filebasedsettings array, it retrieves the file
     * associated with the setting and returns an array containing the file details.
     * Otherwise, it returns the configuration value for the setting.
     *
     * @param string $settingname The name of the setting to retrieve the configuration for.
     * @return mixed The configuration value or an array of file details.
     */
    public function get_setting_config($settingname) {

        $config = '';

        if (in_array($settingname, $this->filebasedsettings)) {

            $context = \context_system::instance();

            // Get file
            $fs = get_file_storage();

            $files = $fs->get_area_files($context->id, "theme_remui", $settingname);

            foreach ($files as $file) {

                if ($file->get_filename() != '.') {
                    $config = [

                        'type' => 'img',
                        'settingname' => $settingname,
                        'component' => $file->get_component(),
                        'filearea' => $file->get_filearea(),
                        'itemid' => $file->get_itemid(),
                        'filename' => $file->get_filename(),
                        'filepath' => $file->get_filepath(),
                        'filecontent' => base64_encode($file->get_content()),

                    ];
                    break;
                }

            }

            return $config;

        } else {
            return  get_config($this->plugin, $settingname);
        }
    }

    /**
     * Retrieves the configuration settings for all settings associated with the plugin.
     *
     * This method iterates through all the settings for the plugin and calls the `get_setting_config()`
     * method to retrieve the configuration value or file details for each setting. The results are
     * returned as an associative array, where the keys are the setting names and the values are
     * either the configuration value or an array of file details.
     *
     * @return array An associative array of configuration settings for the plugin.
     */
    public function get_all_setting_config($excludenoncofigurable = false) {

        $configs = array_keys((array)get_config($this->plugin));

        $configdata = [];

        foreach ($configs as $config) {
            if ($excludenoncofigurable && in_array($config, $this->excludesettings)) {
                continue;
            } else {
                $configdata[$config] = $this->get_setting_config($config);
            }
        }

        return $configdata;
    }

    /**
     * Sets the configuration for a specific setting in the plugin.
     *
     * This method handles the logic for setting the configuration value or file details for a
     * specific setting in the plugin. If the provided `$value` is an object, it is assumed to
     * be file details and the method will create a new file in the file storage system. Otherwise,
     * the method will simply set the configuration value for the specified setting.
     *
     * @param string $setting The name of the setting to configure.
     * @param mixed $value The value or file details to set for the configuration.
     * @return bool True if the configuration was successfully set, false otherwise.
     */
    public function set_setting_config($setting, $value) {

        $context = \context_system::instance();

        $fs = get_file_storage();

        if ($this->not_configurable_setting($setting)) {
            return false;
        }

        if (is_object($value)) {
            // Get file
            $fs = get_file_storage();

            $filerecord = new \stdClass();

            $filerecord->contextid = $context->id;
            $filerecord->component = $value->component;
            $filerecord->filearea = $value->filearea;
            $filerecord->itemid = $value->itemid;
            $filerecord->filepath = $value->filepath;
            $filerecord->filename = $value->filename;

            if (!$fs->file_exists($filerecord->contextid, $filerecord->component, $filerecord->filearea, $filerecord->itemid, $filerecord->filepath, $filerecord->filename)) {
                $fs->create_file_from_string($filerecord, base64_decode($value->filecontent));

            }

            $filepath = $filerecord->filepath.$value->filename;


            return set_config($setting, $filepath , $this->plugin);

        } else {
            return  set_config($setting, $value, $this->plugin);

        }
    }


    /**
     * Checks if the given setting name is not configurable.
     *
     * This method checks if the provided setting name is in the list of excluded settings that
     * are not configurable. This is used to prevent certain settings from being modified through
     * the plugin's configuration interface.
     *
     * @param string $settingname The name of the setting to check.
     * @return bool True if the setting is not configurable, false otherwise.
     */
    public function not_configurable_setting($settingname) {
        return in_array($settingname, $this->excludesettings);
    }
    /**
     * Gets the configuration name for the given setting name.
     *
     * This method attempts to retrieve the configuration name for the given setting name using the
     * get_string() function. If the configuration name cannot be found, it returns the setting name
     * concatenated with the plugin name.
     *
     * @param string $settingname The name of the configuration setting.
     * @return string The configuration name for the given setting name.
     */
    public function get_config_name($settingname) {
        $stringmanager = get_string_manager();

        if ($stringmanager->string_exists($settingname, $this->plugin)) {
            return get_string($settingname, $this->plugin);
        } else {
            return $settingname.", ".$this->plugin;
        }
    }


    /**
     * Gets an array of configuration setting names and their corresponding keys.
     *
     * This method takes an array of configuration settings and returns an associative array
     * where the keys are the setting names and the values are the corresponding setting keys.
     *
     * @param array $settingarray The array of configuration settings to process.
     * @return array An associative array where the keys are the configuration setting names
     *               and the values are the corresponding setting keys.
     */
    public function get_config_names_all($settingarray) {

        $resultantarray = [];
        foreach ($settingarray as $key => $value) {
            $resultantarray[$key] = $this->get_config_name($key);
        }
        return $resultantarray;
    }

    /**
     * Gets an array of configuration settings with their keys and names.
     *
     * This method takes an array of configuration settings and returns an array of associative
     * arrays, where each inner array contains the key and the corresponding configuration name
     * for that setting.
     *
     * @param array $settingarray The array of configuration settings to process.
     * @return array An array of associative arrays, where each inner array contains the key and name of a configuration setting.
     */
    public function get_config_names_with_keys($settingarray) {

        $resultantarray = [];

        foreach ($settingarray as $key => $value) {
            $resultantarray[] = [
                "key" => $key,
                "name" => $this->get_config_name($key)
            ];
        }
        return $resultantarray;
    }
}
