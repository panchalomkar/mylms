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
 * The main videofile configuration form.
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_videofile
 * @copyright  2013 Jonas Nockert <jonasnockert@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/filelib.php');

class mod_videofile_mod_form extends moodleform_mod {
    /**
     * Defines the videofile instance configuration form.
     *
     * @return void
     */
    public function definition(){
        global $CFG, $PAGE, $OUTPUT;

        $config = get_config('videofile');
        
        $mform =& $this->_form;
        $PAGE->requires->js_call_amd('mod_videofile/attempts', 'init');

        // Name and description fields.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('html', html_writer::start_tag('div', array('id' => 'video-general-settings')));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name',
                        get_string('maximumchars', '', 255),
                        'maxlength',
                        255,
                        'client');
        $this->add_intro_editor(false);

        $mform->addElement('html', html_writer::end_tag('div'));

        // Video file manager.
        $mform->addElement('html', html_writer::start_tag('div', array('id' => 'video-general-settings-upload')));
        $mform->addElement('html', html_writer::start_tag('div', array('id' => 'video-upload-hidden')));
        $options = array('subdirs' => false,
                         'maxbytes' => 0,
                         'maxfiles' => -1,
                         'accepted_types' => array('.mp4', '.webm', '.ogv'));
        $mform->addElement(
            'filemanager',
            'videos',
            get_string('videos', 'videofile'),
            null,
            $options);
        $mform->addHelpButton('videos', 'videos', 'videofile');
        $mform->addRule('videos', null, null, 'client');
        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addElement('html', html_writer::tag('div', '', array('class' => 'clearfix')));
                
        $mform->addElement('html', html_writer::start_tag('div', array('id' => 'video-upload-button', 'class' => 'col-sm-6')));
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'fitemtitle col-12 pad-no')));
        $mform->addElement('html', html_writer::tag('label', get_string('videos', 'mod_videofile')));
        $mform->addElement('html', html_writer::end_tag('div'));
        /**
        * the upload file button start with disabled class
        *
        * @author Hugo S.
        * 07-06-2018
        * @paradiso
        * @ticket 8
        */
        $mform->addElement('html', html_writer::tag('div', html_writer::tag('button', get_string('upload_video', 'mod_videofile'), array('id' => 'upload-video-button', 'class' => 'btn btn-primary btn-labeled fa fa-cloud-upload mar-no disabled', 'type' => 'button'))) . html_writer::tag('div', get_string('upload_video_placeholder', 'mod_videofile'), array('id' => 'video-upload-placeholder')) . html_writer::tag('div', get_string('upload_video_or', 'mod_videofile'), array('id' => 'video-upload-or', 'class' => 'text-rgt mar-no pad-no')));
        $mform->addElement('html', html_writer::end_tag('div'));
        
        //Video url
        $mform->addElement(
            'text', 'video_url', '',
            array('placeholder' => get_string('video_placeholder', 'mod_videofile'),'style' => 'width:100%;')
        );
        $mform->setType('video_url', PARAM_TEXT);
        $mform->setDefault('video_url', $config->video);

        /**
        * Set a input file vor validate in the video url is enable or not
        * @author ♦ Andrés Ag. ♦
        * @since August 31 of 2016
        * @paradiso
        */
        $mform->addElement('hidden', 'video_enabled');
        $mform->addElement('html', html_writer::end_tag('div'));

        // Video fields.
        $mform->addElement('header',
                           'video_fieldset',
                           get_string('video_fieldset', 'videofile'));

        // Width.
        $mform->addElement('text',
                           'width',
                           get_string('width', 'videofile'),
                           array('size' => 4));
        $mform->setType('width', PARAM_INT);
        $mform->addHelpButton('width', 'width', 'videofile');
        /**
        * For the responsive width and height are not required
        * @author Andres Ag.
        * @since September 06 of 2016
        * @paradiso
        */
        $mform->disabledIf('width', 'responsive', 'eq', 1);
        /**
         * Add extra validation to prevent error when trying to upload a
         * video without width
         * @author Esteban E.
         * @since 16/06/2015
         * @paradiso
         */
        if(($config->width) && ($config->width <> 0))
        {
            $mform->setDefault('width', $config->width);
        }else
        {
            $mform->setDefault('width', 0);
        }

        // Height.
        $mform->addElement('text',
                           'height',
                           get_string('height', 'videofile'),
                           array('size' => 4));
        $mform->setType('height', PARAM_INT);
        $mform->addHelpButton('height', 'height', 'videofile');
        /**
        * For the responsive width and height are not required
        * @author Andres Ag.
        * @since September 06 of 2016
        * @paradiso
        */
        $mform->disabledIf('height', 'responsive', 'eq', 1);
         /**
         * Add extra validation to prevent error when trying to upload a
         * video without height
         * @author Esteban E.
         * @since 16/06/2015
         * @paradiso
         */
        if(($config->height) && ($config->height <> 0) )
        {
            $mform->setDefault('height', $config->height);
        }else
        {
            $mform->setDefault('height', 0);
        }

        $mform->addElement('select', 'forward', get_string('forward', 'mod_videofile'), array(0 => get_string('no_allow_forward', 'mod_videofile'), 1 => get_string('allow_forward', 'mod_videofile')));
        $mform->addHelpButton('forward', 'forward', 'videofile');
        

        // Responsive.
        $mform->addElement('advcheckbox',
                           'responsive',
                           get_string('responsive', 'videofile'),
                           get_string('responsive_label', 'videofile'));
        $mform->setType('responsive', PARAM_INT);
        $mform->addHelpButton('responsive', 'responsive', 'videofile');
        $mform->setDefault('responsive', $config->responsive);

        /**
         * Add option of select the type of video
         * @author Andres
         * @since 22/05/2015
         * @paradiso
         */

        // Posters file manager.
        $mform->addElement('html', html_writer::start_tag('div', array('id' => 'posters-general-settings-upload')));
        $mform->addElement('html', html_writer::start_tag('div', array('id' => 'posters-upload-hidden')));

        $options = array('subdirs' => false,
                         'maxbytes' => 0,
                         'maxfiles' => 1,
                         'accepted_types' => array('image'));
        $mform->addElement(
            'filemanager',
            'posters',
            get_string('posters', 'videofile'),
            null,
            $options);
        $mform->addHelpButton('posters', 'posters', 'videofile');
        $mform->addElement('html', html_writer::end_tag('div'));


        $mform->addElement('html', html_writer::tag('div', '', array('class' => 'clearfix')));

        $mform->addElement('html', html_writer::start_tag('div', array('id' => 'posters-upload', 'class' => 'col-sm-6')));
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'fitemtitle col-xs-12 pad-no')));
        $mform->addElement('html', html_writer::tag('label', get_string('posters', 'mod_videofile')));
        /*HELP BUTTOM*/
            $help = get_string('posters_help', 'mod_videofile');
            $mform->addElement('html',  html_writer::start_tag('div', array('class' => 'btn btn-secondary p-a-0 buttonhelp','role' => 'button' ,'data-container' => 'body' ,'data-toggle' => 'popover', 'data-placement' => 'right', 'data-content'=>'<div class="no-overflow">'.$help.'</div>', 'data-html' => 'true' ,'tabindex' => '0' ,'data-trigger' => 'focus' ,'data-original-title' => '' ,'title' => '')));
                $mform->addElement('html', html_writer::tag('i', '', array('class' => 'wid wid-icon-helpbutton', 'aria-hidden' => 'true')));
            $mform->addElement('html',  html_writer::end_tag('div'));
        /*END HELP BUTTOM*/
        $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addElement('html', html_writer::tag('div', html_writer::tag('button', get_string('choose_file', 'mod_videofile'), array('id' => 'upload-posters-button', 'class' => 'btn btn-primary mar-no', 'type' => 'button')), array('id' => 'posters-upload-button')) . html_writer::tag('div', get_string('choose_file_placeholder', 'mod_videofile'), array('id' => 'posters-upload-placeholder')));
        $mform->addElement('html', html_writer::end_tag('div'));

        // Captions file manager.
        $mform->addElement('html', html_writer::start_tag('div', array('id' => 'captions-upload-hidden')));
        $options = array('subdirs' => false,
                         'maxbytes' => 0,
                         'maxfiles' => -1,
                         'accepted_types' => array('.vtt'));
        $mform->addElement(
            'filemanager',
            'captions',
            get_string('captions', 'videofile'),
            null,
            $options);
        $mform->addHelpButton('captions', 'captions', 'videofile');
        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addElement('html', html_writer::start_tag('div', array('id' => 'captions-upload', 'class' => 'col-sm-6')));
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'fitemtitle col-xs-12 pad-no')));
                $mform->addElement('html', html_writer::tag('label', get_string('captions', 'mod_videofile')));
        /*HELP BUTTOM*/
        $help = get_string('captions_help', 'mod_videofile');
            $mform->addElement('html',  html_writer::start_tag('div', array('class' => 'btn btn-secondary p-a-0 buttonhelp','role' => 'button' ,'data-container' => 'body' ,'data-toggle' => 'popover', 'data-placement' => 'right', 'data-content'=>'<div class="no-overflow">'.$help.'</div>', 'data-html' => 'true' ,'tabindex' => '0' ,'data-trigger' => 'focus' ,'data-original-title' => '' ,'title' => '')));
                $mform->addElement('html', html_writer::tag('i', '', array('class' => 'wid wid-icon-helpbutton', 'aria-hidden' => 'true')));
        $mform->addElement('html',  html_writer::end_tag('div'));
         /*END HELP BUTTOM*/               
            $mform->addElement('html', html_writer::end_tag('div'));
            $mform->addElement('html', html_writer::tag('div', html_writer::tag('button', get_string('choose_file', 'mod_videofile'), array('id' => 'upload-captions-button', 'class' => 'btn btn-primary mar-no', 'type' => 'button')), array('id' => 'captions-upload-button')) . html_writer::tag('div', get_string('choose_file_placeholder', 'mod_videofile'), array('id' => 'captions-upload-placeholder')));
        $mform->addElement('html', html_writer::end_tag('div'));

        /**
         * Add the option of video forward
         * @author ♦ Andrés Ag. ♦
         * @since April 19 of 2016
         * @paradiso
         */
        /**
         * This JS was a ready function deprecated, this was changed.
         * We add a condition to return the videofile button when disabled
         * @author Hugo S.
         * 07-06-2018
         * @paradiso
         * @ticket 8
         */
        $PAGE->requires->js_call_amd('mod_videofile/modform', 'init');
        // Standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Prepares the form before data are set.
     *
     * @param array $data to be set
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($default_values);
        if ($this->current->instance) {
            $options = array('subdirs' => false,
                             'maxbytes' => 0,
                             'maxfiles' => -1);
            $draftitemid = file_get_submitted_draft_itemid('videos');
            file_prepare_draft_area($draftitemid,
                                    $this->context->id,
                                    'mod_videofile',
                                    'videos',
                                    0,
                                    $options);
            $defaultvalues['videos'] = $draftitemid;

            $options = array('subdirs' => false,
                             'maxbytes' => 0,
                             'maxfiles' => 1);
            $draftitemid = file_get_submitted_draft_itemid('posters');
            file_prepare_draft_area($draftitemid,
                                    $this->context->id,
                                    'mod_videofile',
                                    'posters',
                                    0,
                                    $options);
            $defaultvalues['posters'] = $draftitemid;

            $options = array('subdirs' => false,
                             'maxbytes' => 0,
                             'maxfiles' => -1);
            $draftitemid = file_get_submitted_draft_itemid('captions');
            file_prepare_draft_area($draftitemid,
                                    $this->context->id,
                                    'mod_videofile',
                                    'captions',
                                    0,
                                    $options);
            $defaultvalues['captions'] = $draftitemid;

            if (empty($defaultvalues['width'])) {
                $defaultvalues['width'] = 800;
            }

            if (empty($defaultvalues['height'])) {
                $defaultvalues['height'] = 500;
            }

            if (empty($defaultvalues['video_url'])) {
                $defaultvalues['video_url'] = '';
            }

            if (empty($defaultvalues['video_type'])) {
                $defaultvalues['video_type'] = '0';
            }

            /**
             * Add videoprogress
             * @author ♦ Andres ♦
             * @since April 19 of 2016
             * @paradiso
             */
            if (empty($defaultvalues['videoprogress'])) {
                $defaultvalues['videoprogress'] = '0';
            } elseif($defaultvalues['videoprogress'] != 0) {
                $defaultvalues['videoprogressenabled'] = 1;
            }
        }
    }

    /**
     * Validates the form input
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array eventual errors indexed by the field name
     */
    public function validation($data, $files) {
        $errors = array();

        if ($data['width'] <= 0) {
            $errors['width'] = get_string('err_positive', 'videofile');
        }

        if ($data['height'] <= 0) {
            $errors['height'] = get_string('err_positive', 'videofile');
        }
        return $errors;
    }

    /**
     * Completions Rules For Videoprogress
     * @return array return videoprogressgroup in mform select options
     */            
    /**
     * Add the completions rules for videoprogress
     * @author ♦ Andres ♦
     * @since April 19 of 2016
     * @paradiso
     */
    function add_completion_rules() {
        $mform =& $this->_form;

        $group = array();

        /**
        * Activity video, I enable the video progress completion is not working when the completion tracking is the third option
        * it adds help box for user info with this completion rules and Require View
        *
        * @issue #1017
        * @author DPinzon
        * 2017-06-30
        */
        $mform->addHelpButton('completionview', 'completion_conditions_are_met_with_require_view', 'videofile');

        $options = array(0 => '0%', 10 => '10%', 20 => '20%', 30 => '30%', 40 => '40%', 50 => '50%', 60 => '60%', 70 => '70%', 80 => '80%', 90 => '90%', 100 => '100%');
        $group[] =& $mform->createElement('checkbox', 'videoprogressenabled', '', get_string('videoprogress','mod_videofile'));
        $group[] =& $mform->createElement('select', 'videoprogress', get_string('videoprogressgroup', 'mod_videofile'), $options);
        $mform->addGroup($group, 'videoprogressgroup', get_string('videoprogressgroup','mod_videofile'), array(' '), false);
        
        return array('videoprogressgroup');
    }

    /**
     * Videoprogress Rule Enabled
     *
     * @param array $data video progress or video progress enabled
     * @return return data if not empty 
     */    
    function videoprogress_rule_enabled($data) {
        return (!empty($data['videoprogressenabled']) || $data['videoprogress']!= 0);
    }

    /**
     * Get Data For The Forms
     *
     * @return return data 0 or false
     */
    function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked
            $autocompletion = !empty($data->completion) && $data->completion==COMPLETION_TRACKING_AUTOMATIC;

            if (empty($data->videoprogressenabled) || !$autocompletion) {
                $data->videoprogress = 0;
            }
        }
        return $data;
    }
}
