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

require_once($CFG->dirroot . '/local/sitesync/lib.php');

class CompatibilityCheck {
    private $syncPluginName;
    private $siteSyncExists;
    private $siteSyncVersion;
    private $currentTheme;
    private $currentThemeVersion;
    private $isRemuiActive;
    private $pluginManager;
    private $isSecureSite;
    private $isRemUiExist;
    private $themeComponent;
    private $remuiVersion;

    public function __construct() {
        global $CFG;
        $this->syncPluginName = 'local_sitesync';
        $this->themeComponent = 'theme_remui';
        $this->pluginManager = \core_plugin_manager::instance();
        $this->initialize();
    }

    private function initialize() {
        $this->checkSiteSyncExists();
        $this->fetchSiteSyncVersion();
        $this->fetchCurrentTheme();
        $this->fetchCurrentThemeVersion();
        $this->checkIsRemuiActive();
        $this->checkSiteSecurityStatus();
        $this->checkIsRemuiExist();
        $this->fetchRemuiVersion();
    }

    /**
     * Checks the security status of the site by determining if the site URL starts with 'https://'.
     * This information is stored in the $isSecureSite property of the class.
     */
    private function checkSiteSecurityStatus() {
        global $CFG;
        $this->isSecureSite = (strpos($CFG->wwwroot, 'https://') === 0);
    }

    /**
     * Checks if the Site Sync plugin is available.
     * The result is stored in the $siteSyncExists property of the class.
     */
    private function checkSiteSyncExists() {
        $this->siteSyncExists = local_sitesync_is_plugin_available($this->syncPluginName);
    }

    /**
     * Fetches the version information for the Site Sync plugin.
     * The version information is stored in the $siteSyncVersion property of the class.
     */
    private function fetchSiteSyncVersion() {
        $siteSyncInfo = $this->pluginManager->get_plugin_info($this->syncPluginName);
        $this->siteSyncVersion = $siteSyncInfo->release;
    }

    /**
     * Fetches the current theme name and stores it in the $currentTheme property.
     */
    private function fetchCurrentTheme() {
        global $CFG;
        $this->currentTheme = $CFG->theme;
    }

    /**
     * Fetches the version information for the current theme and stores it in the $currentThemeVersion property.
     */
    private function fetchCurrentThemeVersion() {
        global $CFG;
        $themeInfo = $this->pluginManager->get_plugin_info('theme_' . $CFG->theme);
        $this->currentThemeVersion = $themeInfo->release;
    }

    /**
     * Checks if the Remui theme is active on the current site.
     * The result is stored in the $isRemuiActive property of the class.
     */
    private function checkIsRemuiActive() {
        global $CFG, $PAGE;

        $this->isRemuiActive = ($CFG->theme === 'remui');
    }

    private function checkIsRemuiExist(){
        $this->isRemUiExist = local_sitesync_is_plugin_available($this->themeComponent);
    }

    public function fetchRemuiVersion(){
        $themeInfo = $this->pluginManager->get_plugin_info($this->themeComponent);
        $this->remuiVersion = $themeInfo->release;
        return $this->remuiVersion;
    }

    public function isRemUiExist() {
        return $this->isRemUiExist;
    }
    /**
     * Checks if the Site Sync plugin is available.
     *
     * @return bool True if the Site Sync plugin is available, false otherwise.
     */
    public function isSiteSyncAvailable() {
        return $this->siteSyncExists;
    }

    /**
     * Returns the version of the Site Sync plugin.
     *
     * @return string The version of the Site Sync plugin.
     */
    public function getSiteSyncVersion() {
        return $this->siteSyncVersion;
    }

    /**
     * Returns the current theme name.
     *
     * @return string The current theme name.
     */
    public function getCurrentTheme() {
        return $this->currentTheme;
    }

    /**
     * Returns the version of the current theme.
     *
     * @return string The version of the current theme.
     */
    public function getCurrentThemeVersion() {
        return $this->currentThemeVersion;
    }

    /**
     * Returns a boolean indicating whether the Remui theme is active on the current site.
     *
     * @return bool True if the Remui theme is active, false otherwise.
     */
    public function isRemuiActive() {
        return $this->isRemuiActive;
    }

    /**
     * Returns a boolean indicating whether the current site is secure.
     *
     * @return bool True if the site is secure, false otherwise.
     */
    public function isSecureSite() {
        return $this->isSecureSite;
    }

    /**
     * Returns an array containing various compatibility information.
     *
     * The returned array includes the following keys:
     * - 'sitesync_exists': boolean indicating if the Site Sync plugin is available
     * - 'sitesync_version': the version of the Site Sync plugin
     * - 'is_remui_active': boolean indicating if the Remui theme is active
     * - 'current_theme_version': the version of the current theme
     * - 'current_theme': the name of the current theme
     * - 'is_secure_site': boolean indicating if the site is secure (always true)
     *
     * @return array An array of compatibility information.
     */
    public function getAllCompatibilityInfo() {
        return [
            'sitesync_exists' => $this->siteSyncExists,
            'sitesync_version' => $this->siteSyncVersion,
            "is_remui_exist" => $this->isRemUiExist,
            "remui_version" => $this->remuiVersion,
            // 'current_theme_version' => $this->currentThemeVersion,
            // 'current_theme' => $this->currentTheme,
            // 'is_secure_site' => $this->isSecureSite,
            'is_secure_site' => true,
            'is_remui_active' => $this->isRemuiActive,
        ];
    }
}
