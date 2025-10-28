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
 * English strings for videofile.
 *
 * @package    mod_videofile
 * @copyright  2013 Jonas Nockert <jonasnockert@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Video';
$string['modulenameplural'] = 'Videos';
$string['modulename_help'] = 'Use the video module for adding html5 videos with flash fallback (using video.js). This module also allows for multi-language captions.';

$string['videofile:addinstance'] = 'Add a new video';
$string['videofile:view'] = 'View video';

$string['pluginadministration'] = 'Video administration';
$string['pluginname'] = 'Video';

$string['videofile_defaults_heading'] = 'Default values for video settings';
$string['videofile_defaults_text'] = 'The values you set here define the default values that are used in the video settings form when you create a new video.';
$string['width_explain'] = 'Specifies the default width of the video player.';
$string['height_explain'] = 'Specifies the default height of the video player.';
$string['responsive_explain'] = 'Specifies if responsive mode should be set as default or not.';
$string['limitdimensions_explain'] = 'Specifies if width and height should be used as maximum size during responsive mode.';

$string['filearea_captions'] = 'Captions';
$string['filearea_posters'] = 'Posters';
$string['filearea_videos'] = 'Videos';

$string['video_fieldset'] = 'Video';

$string['width'] = 'Width';
$string['width_help'] = 'Enter the width of the video here (e.g. 800 for a width of 800 pixels max width 1280).';
$string['height'] = 'Height';
$string['height_help'] = 'Enter the height of the video here (e.g. 500 for a height of 500 pixels max height 600).';
$string['responsive'] = 'Responsive?';
$string['responsive_help'] = "Check to make the video automatically resize with the browser window size.\n\nUse the width and height fields to define the video proportions (e.g. 16/9 or 800/450).";
$string['responsive_label'] = '';
$string['limitdimensions'] = 'Limit size in responsive mode?';

$string['videos'] = 'Videos';
$string['videos_help'] = "Add the video file here.\n\nYou can add alternative formats in order to be sure it can play regardless of which browser is being used (usually .mp4, .ogv and .webm covers it.)";
$string['posters'] = 'Poster Image';
$string['posters_help'] = 'Only formats (ai .bmp .gdraw .gif .ico .jpe .jpeg .jpg .pct .pic .pict .png .svg .svgz .tif .tiff)';
$string['posterimage'] = 'Help Poster Image / Captions.';
$string['posterimage_help'] = 'Poster Image / Captions will only work with uploaded videos.';
//$string['posters_help'] = 'Add an image here that will be displayed before the video begins playing.';
$string['captions'] = 'Captions';
$string['captions_help'] = "Only formats (.vtt)";
//$string['captions_help'] = "Add transcriptions of the dialogue in WebVTT format here.\n\nYou can add several files in order to provide multilingual captions. The file names, without extensions, will be used for the video caption option titles. If the files are named according to ISO 6392 (e.g. eng.vtt and swe.vtt) the options will be shown as the corresponding full language names according to the user's language preferences (e.g. English and Swedish, assuming the user's preferred language is set to English).";

$string['err_positive'] = 'You must enter a positive number here.';

$string['video_not_playing'] = 'Video not playing? Try {$a}.';

/**
 * Add New strings
 * @author ♦ Andres Ag. ♦
 * @since 22/05/2015
 * @paradiso
 */
$string['video_url'] = 'Video URL';
$string['video_type'] = 'Type of video';
$string['upload_file'] = 'Upload File';
$string['error_video_url'] = 'Video url should be youtube or vimeo';


/**
 * Strings for the completion with progress
 * @author ♦ Andres Ag. ♦
 * @since April 19 of 2016
 * @paradiso
 */
$string['videoprogress'] = 'Enable Video Progress Completion';
$string['videoprogressgroup'] = 'Video Progress';

//strings for
$string['forward'] = 'Allowed to move forward';
$string['no_allow_forward'] = 'Not Allowed to Move Forward';
$string['allow_forward'] = 'Allowed to Move Forward';

$string['forward_help'] = 'Allowed to move forward: The student will be able to move forward throughout the entire video.
Do not allow: Users cannot move forward throughout the video, to any place beyond which they have already watched.
Note: The option "Not Allowed to Move Forward", doesn’t work with YouTube and Vimeo video links.';

/**
 * Strings for the completion with progress
 * @author ♦ Andres Ag. ♦
 * @since April 19 of 2016
 * @paradiso
 */
$string['upload_video'] = 'Upload Video';
$string['upload_video_placeholder'] = 'Upload Video file format: MP4';
$string['upload_video_or'] = '-or-';
$string['video_placeholder'] = 'URL Youtube or Vimeo';

$string['choose_file'] = 'Choose File';
$string['choose_file_placeholder'] = 'No File Choosen';


/**
 * Strings for the completion Require view
 * @author Diego P.
 * @since 2017-06-30
 * @paradiso
 */
$string['completion_conditions_are_met_with_require_view'] = "Completion Conditions Help";
$string['completion_conditions_are_met_with_require_view_help'] = "You should usually not turn on the 'view' condition if you have other requirements - this it's unlikely that a student could meet any other conditions without viewing the activity.";

$string['file_type_not_supported'] = "File type not supported. Only file types .mp4, .webm, .ogv are allowed.";
$string['please_upload_video'] = "Please upload video";
