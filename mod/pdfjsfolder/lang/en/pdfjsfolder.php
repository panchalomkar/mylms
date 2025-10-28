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
 * English strings for pdfjsfolder.
 *
 * @package    mod_pdfjsfolder
 * @copyright  2013 Jonas Nockert <jonasnockert@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'PDF Uploader';
$string['modulenameplural'] = 'PDF Uploader';
$string['modulename_help'] = 'A folder plugin built on PDF Uploader with the goal of making sure that the PDFs in the folder always open in the browser (without the option of downloading).';

$string['pdfjsfolder:addinstance'] = 'Add a new PDF folder';
$string['pdfjsfolder:view'] = 'View PDF folder';

$string['pluginadministration'] = 'PDF Uploader administration';
$string['pluginname'] = 'PDF Uploader';

$string['pdfjsfolder_defaults_heading'] = 'Default values for PDF Uploader settings';
$string['pdfjsfolder_defaults_text'] = 'The values you set here define the default values that are used in the PDF Uploader settings form when you create a new PDF Uploader.';
$string['pdfjsfolder_options_heading'] = 'PDF Uploader options';
$string['pdfjsfolder_options_text'] = 'The values you set here change how PDF Uploader work or are displayed.';

$string['filearea_pdfs'] = 'PDFs';

$string['pdf_fieldset'] = 'PDF';

$string['pdfs'] = 'PDFs';
$string['pdfs_help'] = 'Add the PDF files here.';

$string['display'] = 'Display folder contents';
$string['display_help'] = "If you choose to display the folder contents on a course page, there will be no link to a separate page. The description will be displayed only if \"Display description on course page\" is checked.\n\nAlso note that participants view actions can not be logged in this case.";
$string['displaypage'] = 'On a separate page';
$string['displayinline'] = 'Inline on a course page';
$string['downloadlinktext'] = 'download';
$string['noautocompletioninline'] = 'Automatic completion on viewing of activity can not be selected together with "Display inline" option.';
$string['showexpanded'] = 'Show sub-folders expanded';
$string['showexpanded_help'] = 'If enabled, will display sub-folders expanded by default. Else, sub-folders will display collapsed.';
$string['openinnewtab'] = 'Open PDFs in new tabs/windows';
$string['openinnewtab_help'] = 'If enabled, PDFs will open in new tabs or windows rather than in the current tab or window.';
$string['showdownloadlinks'] = 'Show links for downloading PDFs';
$string['showdownloadlinks_help'] = "If enabled, each PDF.js-based link will be followed by a link to download the PDF.\n\nThis can be useful on mobile devices where PDF.js may use too much memory or be too slow to work satisfactorily.";
$string['pdflabel'] = 'File Format: PDF Only';