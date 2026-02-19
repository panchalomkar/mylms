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

defined('MOODLE_INTERNAL') || die();
/**
 * This function check  plugin is available or not.
 *
 * @return boolean
 */

function local_sitesync_extend_navigation(global_navigation $navigation) {
    // Add navigation items here if needed
}

function local_sitesync_is_plugin_available($component) {

    list($type, $name) = core_component::normalize_component($component);

    $dir = \core_component::get_plugin_directory($type, $name);
    if (!file_exists($dir ?? '')) {
        return false;
    }
    return true;
}
function local_sitesync_add_web_services_and_functions() {
    global $DB;

    $service = get_service_obj();

    $serviceid = $DB->insert_record('external_services', $service);

    $functionnames = [
        'local_sitesync_check_connection',
        'local_sitesync_save_config',
        'local_sitesync_generate_keys',
        'local_sitesync_do_sync_action',
        'local_sitesync_compatibility_checker'
    ];

    foreach ($functionnames as $functionname) {

        $addedfunction = new stdClass();
        $addedfunction->externalserviceid = $serviceid;
        $addedfunction->functionname = $functionname;

        if (!$DB->record_exists(
            'external_services_functions',
            [
                'externalserviceid' => $addedfunction->externalserviceid,
                'functionname'      => $addedfunction->functionname,
            ]
        )) {
            $DB->insert_record('external_services_functions', $addedfunction);
        }
    }
}


function local_sitesync_map_remui_strings_with_config_keys() {
    $maparray = [
        'button-common-fontfamily' => '',
        'button-common-text-transform' => '',
        'button-lg-settings-border-radius' => '',
        'button-lg-settings-border-width' => '',
        'button-lg-settings-fontsize' => '',
        'button-lg-settings-fontweight' => '',
        'button-lg-settings-letterspacing' => '',
        'button-lg-settings-lineheight' => '',
        'button-lg-settings-padingbottom' => '',
        'button-lg-settings-padingleft' => '',
        'button-lg-settings-padingright' => '',
        'button-lg-settings-padingtop' => '',
        'button-md-settings-border-radius' => '',
        'button-md-settings-border-width' => '',
        'button-md-settings-fontsize' => '',
        'button-md-settings-fontweight' => '',
        'button-md-settings-letterspacing' => '',
        'button-md-settings-lineheight' => '',
        'button-md-settings-padingbottom' => '',
        'button-md-settings-padingleft' => '',
        'button-md-settings-padingright' => '',
        'button-md-settings-padingtop' => '',
        'button-primary-border-color' => '',
        'button-primary-border-color-hover' => '',
        'button-primary-color-background' => '',
        'button-primary-color-background-hover' => '',
        'button-primary-color-icon' => '',
        'button-primary-color-icon-hover' => '',
        'button-primary-color-text' => '',
        'button-primary-color-text-hover' => '',
        'button-secondary-border-color' => '',
        'button-secondary-border-color-hover' => '',
        'button-secondary-color-background' => '',
        'button-secondary-color-background-hover' => '',
        'button-secondary-color-icon' => '',
        'button-secondary-color-icon-hover' => '',
        'button-secondary-color-text' => '',
        'button-secondary-color-text-hover' => '',
        'button-sm-settings-border-radius' => '',
        'button-sm-settings-border-width' => '',
        'button-sm-settings-fontsize' => '',
        'button-sm-settings-fontweight' => '',
        'button-sm-settings-letterspacing' => '',
        'button-sm-settings-lineheight' => '',
        'button-sm-settings-padingbottom' => '',
        'button-sm-settings-padingleft' => '',
        'button-sm-settings-padingright' => '',
        'button-sm-settings-padingtop' => '',
        'coursedatevisibility' => '',
        'enableaddnotesblock' => '',
        'enablebodysettingslinking' => '',
        'enablecourseanlyticsblock' => '',
        'enablecourseprogressblock' => '',
        'enableenrolledusersblock' => '',
        'enablelatestmembersblock' => '',
        'enablemanagecoursesblock' => '',
        'enablequizattemptsblock' => '',
        'enablerecentfeedbackblock' => '',
        'enablerecentforumsblock' => '',
        'enablescheduletaskblock' => '',
        'enrolleduserscountvisibility' => '',
        'enrolment_page_layout' => '',
        'faviconurl' => '',
        'footer-background-color' => '',
        'footer-divider-color' => '',
        'footer-icon-color' => '',
        'footer-icon-hover-color' => '',
        'footer-link-hover-text' => '',
        'footer-link-text' => '',
        'footer-text-color' => '',
        'footercolumn1customhtml' => '',
        'footercolumn1menu' => '',
        'footercolumn1social' => '',
        'footercolumn1type' => '',
        'footercolumn2menu' => '',
        'footercolumn2social' => '',
        'footercolumn2type' => '',
        'footercolumn3menu' => '',
        'footercolumn3social' => '',
        'footercolumn3type' => '',
        'footercolumn4menu' => '',
        'footercolumn4social' => '',
        'footercolumn4type' => '',
        'frontpageblockdesc' => '',
        'frontpageblockheading' => '',
        'frontpageblockimage1' => '',
        'frontpageblockimage2' => '',
        'frontpageblockimage3' => '',
        'frontpageblockimage4' => '',
        'frontpageheadercolor' => '',
        'global-colors-ascentbackgroundcolor' => '',
        'global-colors-elementbackgroundcolor' => '',
        'global-colors-gradient-angle' => '',
        'global-colors-pagebackground' => '',
        'global-colors-pagebackgroundcolor' => '',
        'global-colors-pagebackgroundgradient1' => '',
        'global-colors-pagebackgroundgradient2' => '',
        'global-colors-pagebackgroundimage' => '',
        'global-colors-pagebackgroundimageattachment' => '',
        'global-typography-body-fontfamily' => '',
        'global-typography-body-fontsize' => '',
        'global-typography-body-fontsize-mobile' => '',
        'global-typography-body-fontsize-tablet' => '',
        'global-typography-body-fontweight' => '',
        'global-typography-body-letterspacing' => '',
        'global-typography-body-lineheight' => '',
        'global-typography-body-linkcolor' => '',
        'global-typography-body-linkhovercolor' => '',
        'global-typography-body-text-transform' => '',
        'global-typography-smallinfo-fontfamily' => '',
        'global-typography-smallinfo-text-transform' => '',
        'global-typography-smallpara-fontfamily' => '',
        'global-typography-smallpara-text-transform' => '',
        'hds-boxshadow-enable' => '',
        'hds-menu-font-family' => '',
        'hds-menu-fontsize' => '',
        'hds-menu-fontweight' => '',
        'hds-menu-letter-spacing' => '',
        'hds-menu-text-transform' => '',
        'header-menu-background-color' => '',
        'header-menu-text-active-color' => '',
        'header-menu-text-color' => '',
        'header-menu-text-hover-color' => '',
        'header-primary-border-bottom-blur' => '',
        'header-primary-border-bottom-color' => '',
        'header-primary-border-bottom-size' => '',
        'header-primary-layout-desktop' => '',
        'header-site-identity-fontsize' => '',
        'header-site-identity-fontsize-tablet' => '',
        'headeroverlayopacity' => '',
        'hideactivitysections' => '',
        'hideheadercontent' => '',
        'homepagetransparentheader' => '',
        'lessonsvisiblityoncoursecard' => '',
        'loaderimage'   => '',
        'loginpanelcontentcolor' => '',
        'loginpanellinkcolor' => '',
        'loginpanellinkhovercolor' => '',
        'loginpaneltextcolor' => '',
        'navbarinverse' => '',
        'privacypolicynewtab' => '',
        'secondarycolor' => '',
        'showenrolledtextinput' => '',
        'showenrolledtextlabel' => '',
        'sitecolorhex' => '',
        'slideimage3' => '',
        'slideimage4' => '',
        'slideimage5' => '',
        'sliderbuttontext1' => '',
        'sliderbuttontext2' => '',
        'sliderbuttontext3' => '',
        'sliderbuttontext4' => '',
        'sliderbuttontext5' => '',
        'slidertext1' => '',
        'slidertext2' => '',
        'slidertext3' => '',
        'slidertext4' => '',
        'slidertext5' => '',
        'sliderurl1' => '',
        'sliderurl2' => '',
        'sliderurl3' => '',
        'sliderurl4' => '',
        'sliderurl5' => '',
        'smallinfo-adv-setting' => '',
        'smallinfo-regular-fontweight' => '',
        'smallinfo-semibold-fontweight' => '',
        'smallpara-adv-setting' => '',
        'smallpara-regular-fontweight' => '',
        'smallpara-semibold-fontweight' => '',
        'socialmediaiconcol1' => '',
        'socialmediaiconcol2' => '',
        'socialmediaiconcol3' => '',
        'socialmediaiconcol4' => '',
        'staticimage' => '',
        'termsandconditionewtab' => '',
        'testimonialdesignation1' => '',
        'testimonialdesignation2' => '',
        'testimonialdesignation3' => '',
        'testimonialimage1' => '',
        'testimonialimage2' => '',
        'testimonialimage3' => '',
        'testimonialname1' => '',
        'testimonialname2' => '',
        'testimonialname3' => '',
        'testimonialtext1' => '',
        'testimonialtext2' => '',
        'testimonialtext3' => '',
        'themecolors-bordercolor' => '',
        'themecolors-lightbordercolor' => '',
        'themecolors-mediumbordercolor' => '',
        'themecolors-textcolor' => '',
        'typography-heading-all-fontfamily' => '',
        'typography-heading-all-text-transform' => '',
        'typography-heading-all-textcolor' => '',
        'typography-heading-h1-custom-color' => '',
        'typography-heading-h1-fontfamily' => '',
        'typography-heading-h1-fontsize' => '',
        'typography-heading-h1-fontsize-tablet' => '',
        'typography-heading-h1-lineheight' => '',
        'typography-heading-h1-text-transform' => '',
        'typography-heading-h1-textcolor' => '',
        'typography-heading-h2-custom-color' => '',
        'typography-heading-h2-fontfamily' => '',
        'typography-heading-h2-fontsize' => '',
        'typography-heading-h2-fontsize-tablet' => '',
        'typography-heading-h2-lineheight' => '',
        'typography-heading-h2-text-transform' => '',
        'typography-heading-h2-textcolor' => '',
        'typography-heading-h3-custom-color' => '',
        'typography-heading-h3-fontfamily' => '',
        'typography-heading-h3-fontsize' => '',
        'typography-heading-h3-fontsize-tablet' => '',
        'typography-heading-h3-lineheight' => '',
        'typography-heading-h3-text-transform' => '',
        'typography-heading-h3-textcolor' => '',
        'typography-heading-h4-custom-color' => '',
        'typography-heading-h4-fontfamily' => '',
        'typography-heading-h4-fontsize' => '',
        'typography-heading-h4-fontsize-tablet' => '',
        'typography-heading-h4-lineheight' => '',
        'typography-heading-h4-text-transform' => '',
        'typography-heading-h4-textcolor' => '',
        'typography-heading-h5-custom-color' => '',
        'typography-heading-h5-fontfamily' => '',
        'typography-heading-h5-fontsize' => '',
        'typography-heading-h5-fontsize-tablet' => '',
        'typography-heading-h5-lineheight' => '',
        'typography-heading-h5-text-transform' => '',
        'typography-heading-h5-textcolor' => '',
        'typography-heading-h6-custom-color' => '',
        'typography-heading-h6-fontfamily' => '',
        'typography-heading-h6-fontsize' => '',
        'typography-heading-h6-fontsize-tablet' => '',
        'typography-heading-h6-lineheight' => '',
        'typography-heading-h6-text-transform' => '',
        'typography-heading-h6-textcolor' => '',
    ];
}


function get_service_obj() {
    $service = new \stdClass();
    $service->name = 'Edwiser sitesync';
    $service->shortname = 'edwsitesync';
    $service->enabled = true;
    $service->restrictedusers = 0;
    $service->requiredcapability = '';
    $service->timecreated = time();
    $service->timemodified = time();

    return $service;
}

function local_sitesync_delete_web_service() {
    global $DB;

    $serviceobj = get_service_obj();

    $service  = [];
    $service['name'] = $serviceobj->name;
    $service['shortname'] = $serviceobj->shortname;

    $DB->delete_records('external_services', $service);
}
