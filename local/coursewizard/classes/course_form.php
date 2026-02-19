<?php
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');
//require_once($CFG->libdir. '/coursecatlib.php');
require_once($CFG->dirroot . '/local/coursewizard/lib.php');
require_once($CFG->dirroot."/theme/remui/lib.php");
/**
 * The form for handling editing a course.
 */
class course_edit_form_cw extends moodleform {
    protected $course;
    protected $context;
    /**
     * Form definition.
     */
    public function __construct( $editoroptions) {
        global $CFG, $DB;

        $this->editoroptions = $editoroptions;

        parent::__construct($actionurl);
    }

    function definition() {
        global $USER, $CFG, $DB, $OUTPUT, $SESSION;
        // if (!empty($SESSION->currenteditingcompany)) {
        //     $company_id = $SESSION->currenteditingcompany;
        // } else if (!empty($USER->profile->company)) {
        //     $usercompany = company::by_userid($USER->id);
        //     $company_id = $usercompany->id;
        // } else {
        //     $company_id = "";
        // }
        $cid = required_param('cid', PARAM_INT);  // Origin course id.
        $mform    = $this->_form;
        $company_id = rap_is_company_user();
        /*$PAGE->requires->yui_module('moodle-course-formatchooser', 'M.course.init_formatchooser',
                array(array('formid' => $mform->getAttribute('id'))));*/
        $course        = isset($this->_customdata['course'])?$this->_customdata['course']:''; // this contains the data of this form
        $category      = isset($this->_customdata['category'])?$this->_customdata['category']:'';
        $editoroptions = isset($this->_customdata['editoroptions'])?$this->_customdata['editoroptions']:'';
        $returnto = isset($this->_customdata['returnto'])?$this->_customdata['returnto']:'';
        $returnurl = isset($this->_customdata['returnurl'])?$this->_customdata['returnurl']:'';
        $systemcontext   = context_system::instance();
        if (!empty($course->id)) {
            $coursecontext = context_course::instance($course->id);
            $context = $coursecontext;
        } else {
            $coursecontext = null;
            $context = $systemcontext;
        }
        $courseconfig = get_config('moodlecourse');
        $this->course  = $course;
        $this->context = $context;
        $mform->addElement('hidden', 'cid', null);
        $mform->addElement('hidden', 'saveanddisplay', 'saveanddisplay');
        $mform->addElement('hidden', 'image_selected', null);
        $mform->setType('cid', PARAM_ALPHANUM);
        $mform->setConstant('cid', $cid);
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'tab-content'))); 
        $mform->addElement('hidden', 'startdate',time());
        $choices = array();
        $choices['0'] = get_string('hide');
        $choices['1'] = get_string('show');
        $mform->addElement('hidden', 'visible', get_string('coursevisibility'), $choices);
        $mform->setDefault('visible', $courseconfig->visible);
        $courseformats = get_sorted_course_formats(true);
        $formcourseformats = array();
        foreach ($courseformats as $courseformat) {
            $formcourseformats[$courseformat] = get_string('pluginname', "format_$courseformat");
        }
        if (isset($course->format)) {
            $course->format = course_get_format($course)->get_format(); // replace with default if not found
            if (!in_array($course->format, $courseformats)) {
                // this format is disabled. Still display it in the dropdown
                $formcourseformats[$course->format] = get_string('withdisablednote', 'moodle',
                        get_string('pluginname', 'format_'.$course->format));
            }
        }
        $mform->addElement('hidden', 'format', get_string('format'), $formcourseformats);
        $mform->setDefault('format', $courseconfig->format);
        // Appearance.
        if (!empty($CFG->allowcoursethemes)) {
            $themeobjects = get_list_of_themes();
            $themes=array();
            $themes[''] = get_string('forceno');
            foreach ($themeobjects as $key=>$theme) {
                if (empty($theme->hidefromselector)) {
                    $themes[$key] = get_string('pluginname', 'theme_'.$theme->name);
                }
            }
            $mform->addElement('hidden', 'theme', get_string('forcetheme'), $themes);
        }
        $languages=array();
        $languages[''] = get_string('forceno');
        $languages += get_string_manager()->get_list_of_translations();
        $mform->addElement('hidden', 'lang', get_string('forcelanguage'), $languages);
        $mform->setDefault('lang', $courseconfig->lang);
        // Files and uploads.
        if (!empty($course->legacyfiles) or !empty($CFG->legacyfilesinnewcourses)) {
            if (empty($course->legacyfiles)) {
                //0 or missing means no legacy files ever used in this course - new course or nobody turned on legacy files yet
                $choices = array('0'=>get_string('no'), '2'=>get_string('yes'));
            } else {
                $choices = array('1'=>get_string('no'), '2'=>get_string('yes'));
            }
            $mform->addElement('hidden', 'legacyfiles', get_string('courselegacyfiles'), $choices);
            if (!isset($courseconfig->legacyfiles)) {
                // in case this was not initialised properly due to switching of $CFG->legacyfilesinnewcourses
                $courseconfig->legacyfiles = 0;
            }
            $mform->setDefault('legacyfiles', $courseconfig->legacyfiles);
        }
        if (completion_info::is_enabled_for_site()) {
            $mform->addElement('hidden', 'enablecompletion', get_string('enablecompletion', 'completion'));
            $mform->setDefault('enablecompletion', $courseconfig->enablecompletion);
        } else {
            $mform->addElement('hidden', 'enablecompletion');
            $mform->setType('enablecompletion', PARAM_INT);
            $mform->setDefault('enablecompletion', 0);
        }
        enrol_course_edit_form($mform, $course, $context);
        $choices = array();
        $choices[NOGROUPS] = get_string('groupsnone', 'group');
        $choices[SEPARATEGROUPS] = get_string('groupsseparate', 'group');
        $choices[VISIBLEGROUPS] = get_string('groupsvisible', 'group');
        $mform->addElement('hidden', 'groupmode', get_string('groupmode', 'group'), $choices);
        $mform->setDefault('groupmode', $courseconfig->groupmode);
        $mform->addElement('hidden', 'groupmodeforce', get_string('groupmodeforce', 'group'));
        $mform->setDefault('groupmodeforce', $courseconfig->groupmodeforce);
        // Form definition with new course defaults.
        // Start pane 1
        $start_pane1 = html_writer::start_tag('div', array('id'=>'step1', 'class' => 'tab-pane active'));
        $mform->addElement('html',$start_pane1);
        $mform->addElement('html','<div class="course_wizard_padd1">');
        $placeholdertext = get_string('name_example','local_coursewizard');
        $mform->addElement('text','fullname', get_string('fullnamecourse'),'maxlength="254" size="50" placeholder="'.$placeholdertext.'"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);
        if (!empty($course->id) and !has_capability('moodle/course:changefullname', $coursecontext)) {
            $mform->hardFreeze('fullname');
            $mform->setConstant('fullname', $course->fullname);
        }
        // Verify permissions to change course category or keep current.
        if ($company_id) {
            $companycategory = $DB->get_record('company', array('id' => $company_id));
            $displaylist = $this->make_categories_list_company('moodle/course:create', $companycategory->category);
        } else {
            $displaylist = \core_course_category::make_categories_list();
        }
        $mform->addElement('select', 'category', get_string('coursecategory'), $displaylist);
        $mform->addRule('category', get_string('missingcoursecategory','local_coursewizard'), 'required', null, 'client');
        $mform->addHelpButton('category', 'coursecategory');
        $mform->setDefault('category', $category->id);
        // Just a placeholder for the course format options.
        $mform->addElement('hidden', 'addcourseformatoptionshere');
        $mform->setType('addcourseformatoptionshere', PARAM_BOOL);
        if (isset($course->format)) {
            $course->format = course_get_format($course)->get_format(); // replace with default if not found
            if (!in_array($course->format, $courseformats)) {
                // this format is disabled. Still display it in the dropdown
                $formcourseformats[$course->format] = get_string('withdisablednote', 'moodle',
                        get_string('pluginname', 'format_'.$course->format));
            }
        }
        $mform->addElement('editor', 'summary_editor',
        get_string('coursesummary'), null, $this->editoroptions);
$mform->addHelpButton('summary_editor', 'coursesummary');
$mform->setType('summary_editor', PARAM_RAW);
        // $mform->addElement('textarea','summary_editor[text]', get_string('coursesummary'));
        // $mform->addHelpButton('summary_editor', 'coursesummary');
        // $mform->setType('summary_editor', PARAM_RAW);
        // $summaryfields = 'summary_editor';
        $mform->addElement('html', html_writer::start_tag('ul', ['class' => 'list-inline pull-right']));
        $url = $CFG->wwwroot."/course/";
      
        //$mform->addElement('html', html_writer::tag('li', get_string('continue'),['class' => 'btn btn-primary btn-round next-step']));
        $mform->addElement('html', html_writer::end_tag('ul'));
        $mform->addElement('html', html_writer::end_tag('div'));
        // End step1
        $mform->addElement('html', html_writer::end_div());
        // Start pane 2
        $start_pane2 = html_writer::start_tag('div', array('id'=>'step2', 'class' => 'tab-pane'));
        $mform->addElement('html',$start_pane2);
        $mform->addElement('html','<div class="course_wizard_padd">');
        if ($overviewfilesoptions = course_overviewfiles_options($course)) {
            $mform->addElement('filemanager', 'overviewfiles_filemanager', get_string('add_image','local_coursewizard'), ['class' =>'addcourse_image'], $overviewfilesoptions);
            $mform->addHelpButton('overviewfiles_filemanager', 'courseoverviewfiles');
            $summaryfields .= ',overviewfiles_filemanager';            
        }
        $mform->addElement('html',get_recent_courses_images());
        // When two elements we need a group.
        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = $mform->addElement('html', html_writer::tag('a', 'Back to course', array('href' => $url , 'class' => 'cancel btn btn-secondary')));
        $buttonarray[] = $mform->addElement('html', html_writer::tag('a', get_string('cancel'), array('href' => $url , 'class' => 'cancel btn btn-secondary')));
        $buttonarray[] = &$mform->createElement('submit', 'saveanddisplay',  'Save & add content', ['class' => 'float-right'], $classarray);
        $mform->addGroup($buttonarray, 'buttonar', '', array(''), false);
        $mform->closeHeaderBefore('buttonar');
        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);
        // End step2
        $mform->addElement('html', html_writer::end_div());
        $mform->addElement('html', html_writer::end_div());
        $mform->addElement('html', html_writer::tag('div', '', array('class' => 'clearfix')));
        $mform->addElement('html',  html_writer::end_tag('div'));
        $mform->addElement('html', html_writer::tag('div', '', array('class' => 'clearfix')));
        $mform->addElement('html', html_writer::end_tag('div'));
        // Finally set the current form data
        $this->set_data($course);
    }
    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        // Add field validation check for duplicate shortname.
        if ($course = $DB->get_record('course', array('shortname' => $data['shortname']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $course->id != $data['id']) {
                $errors['shortname'] = get_string('shortnametaken', '', $course->fullname);
            }
        }
        // Add field validation check for duplicate idnumber.
        if (!empty($data['idnumber']) && (empty($data['id']) || $this->course->idnumber != $data['idnumber'])) {
            if ($course = $DB->get_record('course', array('idnumber' => $data['idnumber']), '*', IGNORE_MULTIPLE)) {
                if (empty($data['id']) || $course->id != $data['id']) {
                    $errors['idnumber'] = get_string('courseidnumbertaken', 'error', $course->fullname);
                }
            }
        }
        if ($errorcode = course_validate_dates($data)) {
            $errors['enddate'] = get_string($errorcode, 'error');
        }
        $errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));
        $courseformat = course_get_format((object)array('format' => $data['format']));
        $formaterrors = $courseformat->edit_form_validation($data, $files, $errors);
        if (!empty($formaterrors) && is_array($formaterrors)) {
            $errors = array_merge($errors, $formaterrors);
        }
        return $errors;
    }
    
    public function make_categories_list_company($requiredcapability = '', $excludeid = 0, $separator = ' / ') {
        global $DB;
        global $CFG;
        $coursecatcache = cache::make('core', 'coursecat');
        // Check if we cached the complete list of user-accessible category names ($baselist) or list of ids
        // with requried cap ($thislist).
        $currentlang = current_language();
        $basecachekey = $currentlang . '_catlist';
        $baselist = $coursecatcache->get($basecachekey);
        $thislist = false;
        $thiscachekey = null;
        if (!empty($requiredcapability)) {
            $requiredcapability = (array)$requiredcapability;
            $thiscachekey = 'catlist:'. serialize($requiredcapability);
            if ($baselist !== false && ($thislist = $coursecatcache->get($thiscachekey)) !== false) {
                $thislist = preg_split('|,|', $thislist, -1, PREG_SPLIT_NO_EMPTY);
            }
        } else if ($baselist !== false) {
            $thislist = array_keys($baselist);
        }
        if ($baselist === false) {
            // We don't have $baselist cached, retrieve it. Retrieve $thislist again in any case.
            $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
            $sql = "SELECT cc.id, cc.sortorder, cc.name, cc.visible, cc.parent, cc.path, $ctxselect
                    FROM {course_categories} cc
                    JOIN {context} ctx ON cc.id = ctx.instanceid AND ctx.contextlevel = :contextcoursecat
                    ORDER BY cc.sortorder";
            $rs = $DB->get_recordset_sql($sql, array('contextcoursecat' => CONTEXT_COURSECAT));
           
            $baselist = array();
            $thislist = array();
            foreach ($rs as $record) {
                // If the category's parent is not visible to the user, it is not visible as well.
                if (!$record->parent || isset($baselist[$record->parent])) {
                    context_helper::preload_from_record($record);
                    $context = context_coursecat::instance($record->id);
                    if (!$record->visible && !has_capability('moodle/category:viewhiddencategories', $context)) {
                        // No cap to view category, added to neither $baselist nor $thislist.
                        continue;
                    }
                    $baselist[$record->id] = array(
                        'name' => format_string($record->name, true, array('context' => $context)),
                        'path' => $record->path
                    );
                    if (!empty($requiredcapability) && !has_all_capabilities($requiredcapability, $context)) {
                        // No required capability, added to $baselist but not to $thislist.
                        continue;
                    }
                    $thislist[] = $record->id;
                }
            }
            $rs->close();
            $coursecatcache->set($basecachekey, $baselist);
            if (!empty($requiredcapability)) {
                $coursecatcache->set($thiscachekey, join(',', $thislist));
            }
        } else if ($thislist === false) {
            // We have $baselist cached but not $thislist. Simplier query is used to retrieve.
            $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
            $sql = "SELECT ctx.instanceid AS id, $ctxselect
                    FROM {context} ctx WHERE ctx.contextlevel = :contextcoursecat";
            $contexts = $DB->get_records_sql($sql, array('contextcoursecat' => CONTEXT_COURSECAT));
            $thislist = array();
            foreach (array_keys($baselist) as $id) {
                context_helper::preload_from_record($contexts[$id]);
                if (has_all_capabilities($requiredcapability, context_coursecat::instance($id))) {
                    $thislist[] = $id;
                }
            }
            $coursecatcache->set($thiscachekey, join(',', $thislist));
        }

        // Now build the array of strings to return, mind $separator and $excludeid.
        $names = array();
        foreach ($thislist as $id) {
            $path = preg_split('|/|', $baselist[$id]['path'], -1, PREG_SPLIT_NO_EMPTY);
            if (in_array($excludeid, $path)) {
                $namechunks = array();
                foreach ($path as $parentid) {
                    $namechunks[] = $baselist[$parentid]['name'];
                }
                $names[$id] = join($separator, $namechunks);
            }
        }
        // IOMAD :  Filter the list of categories.
        if (!is_siteadmin() and !during_initial_install()) {
            $names = iomad::iomad_filter_categories($names);
        }
        return $names;
    }
}