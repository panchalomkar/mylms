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
 * Reports block external apis
 *
 * @package     local_corporate_api
 * @copyright   2019 wisdmlabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_corporate_api\external;

defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use context_module;
use context_course;
use completion_info;
use external_format_value;
use cm_info;
use core_completion\progress;
use core_course\external\course_summary_exporter;
use core_availability\info;


/**
 * Trait implementing the external function local_corporate_api_complete_edwiserreports_installation.
 */
trait get_course_content_detail {

    public static function get_course_content_detail_parameters() {
        return new external_function_parameters(
                array(
                    'courseid' => new external_value(PARAM_INT, 'course id'),
                    'userid' => new external_value(PARAM_INT, 'user id'),
                      'options' => new external_multiple_structure (
                              new external_single_structure(
                                array(
                                    'name' => new external_value(PARAM_ALPHANUM,
                                                'The expected keys (value format) are:
                                                excludemodules (bool) Do not return modules, return only the sections structure
                                                excludecontents (bool) Do not return module contents (i.e: files inside a resource)
                                                includestealthmodules (bool) Return stealth modules for students in a special
                                                    section (with id -1)
                                                sectionid (int) Return only this section
                                                sectionnumber (int) Return only this section with number (order)
                                                cmid (int) Return only this module information (among the whole sections structure)
                                                modname (string) Return only modules with this name "label, forum, etc..."
                                                modid (int) Return only the module with this id (to be used with modname'),
                                    'value' => new external_value(PARAM_RAW, 'the value of the option,
                                                                    this param is personaly validated in the external function.')
                              )
                      ), 'Options, used since Moodle 2.9', VALUE_DEFAULT, array())
                )
        );
    }

    /**
     * Get course contents
     *
     * @param int $courseid course id
     * @param array $options Options for filtering the results, used since Moodle 2.9
     * @return array
     * @since Moodle 2.9 Options available
     * @since Moodle 2.2
     */
    public static function get_course_content_detail($courseid, $userid, $options = array()) {
        global $CFG, $DB, $USER, $PAGE;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->libdir . '/completionlib.php');

        //validate parameter
        $params = self::validate_parameters(self::get_course_content_detail_parameters(),
                        array('courseid' => $courseid, 'userid' => $userid,  'options' => $options));

        $filters = array();
        if (!empty($params['options'])) {

            foreach ($params['options'] as $option) {
                $name = trim($option['name']);
                // Avoid duplicated options.
                if (!isset($filters[$name])) {
                    switch ($name) {
                        case 'excludemodules':
                        case 'excludecontents':
                        case 'includestealthmodules':
                            $value = clean_param($option['value'], PARAM_BOOL);
                            $filters[$name] = $value;
                            break;
                        case 'sectionid':
                        case 'sectionnumber':
                        case 'cmid':
                        case 'modid':
                            $value = clean_param($option['value'], PARAM_INT);
                            if (is_numeric($value)) {
                                $filters[$name] = $value;
                            } else {
                                throw new moodle_exception('errorinvalidparam', 'webservice', '', $name);
                            }
                            break;
                        case 'modname':
                            $value = clean_param($option['value'], PARAM_PLUGIN);
                            if ($value) {
                                $filters[$name] = $value;
                            } else {
                                throw new moodle_exception('errorinvalidparam', 'webservice', '', $name);
                            }
                            break;
                        default:
                            throw new moodle_exception('errorinvalidparam', 'webservice', '', $name);
                    }
                }
            }
        }

        //retrieve the course
        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);

        if ($course->id != SITEID) {
            // Check course format exist.
            if (!file_exists($CFG->dirroot . '/course/format/' . $course->format . '/lib.php')) {
                throw new moodle_exception('cannotgetcoursecontents', 'webservice', '', null,
                                            get_string('courseformatnotfound', 'error', $course->format));
            } else {
                require_once($CFG->dirroot . '/course/format/' . $course->format . '/lib.php');
            }
        }

        // now security checks
        $context = context_course::instance($course->id, IGNORE_MISSING);
        try {
            self::validate_context($context);
        } catch (Exception $e) {
            $exceptionparam = new stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->courseid = $course->id;
            throw new moodle_exception('errorcoursecontextnotvalid', 'webservice', '', $exceptionparam);
        }

        $canupdatecourse = has_capability('moodle/course:update', $context);

        //create return value
        $coursecontents = array();

        if ($canupdatecourse or $course->visible
                or has_capability('moodle/course:viewhiddencourses', $context)) {

            //retrieve sections
            $modinfo = get_fast_modinfo($course);
            $sections = $modinfo->get_section_info_all();
            $courseformat = course_get_format($course);
            $coursenumsections = $courseformat->get_last_section_number();
            $stealthmodules = array();   // Array to keep all the modules available but not visible in a course section/topic.

            $completioninfo = new completion_info($course);

            //for each sections (first displayed to last displayed)
            $modinfosections = $modinfo->get_sections();
            foreach ($sections as $key => $section) {

                // This becomes true when we are filtering and we found the value to filter with.
                $sectionfound = false;

                // Filter by section id.
                if (!empty($filters['sectionid'])) {
                    if ($section->id != $filters['sectionid']) {
                        continue;
                    } else {
                        $sectionfound = true;
                    }
                }

                // Filter by section number. Note that 0 is a valid section number.
                if (isset($filters['sectionnumber'])) {
                    if ($key != $filters['sectionnumber']) {
                        continue;
                    } else {
                        $sectionfound = true;
                    }
                }

                // reset $sectioncontents
                $sectionvalues = array();
                $sectionvalues['id'] = $section->id;
                $sectionvalues['name'] = get_section_name($course, $section);
                $sectionvalues['visible'] = $section->visible;

                $options = (object) array('noclean' => true);
                list($sectionvalues['summary'], $sectionvalues['summaryformat']) =
                        external_format_text($section->summary, $section->summaryformat,
                                $context->id, 'course', 'section', $section->id, $options);
                $sectionvalues['section'] = $section->section;
                $sectionvalues['hiddenbynumsections'] = $section->section > $coursenumsections ? 1 : 0;
                $sectionvalues['uservisible'] = $section->uservisible;
                if (!empty($section->availableinfo)) {
                    $sectionvalues['availabilityinfo'] = \core_availability\info::format_info($section->availableinfo, $course);
                }

                $sectioncontents = array();

                // For each module of the section.
                if (empty($filters['excludemodules']) and !empty($modinfosections[$section->section])) {
                    foreach ($modinfosections[$section->section] as $cmid) {
                        $cm = $modinfo->cms[$cmid];
                        $cminfo = cm_info::create($cm);
                        $activitydates = \core\activity_dates::get_dates_for_module($cminfo, $USER->id);

                        // Stop here if the module is not visible to the user on the course main page:
                        // The user can't access the module and the user can't view the module on the course page.
                        if (!$cm->uservisible && !$cm->is_visible_on_course_page()) {
                            continue;
                        }

                        // This becomes true when we are filtering and we found the value to filter with.
                        $modfound = false;

                        // Filter by cmid.
                        if (!empty($filters['cmid'])) {
                            if ($cmid != $filters['cmid']) {
                                continue;
                            } else {
                                $modfound = true;
                            }
                        }

                        // Filter by module name and id.
                        if (!empty($filters['modname'])) {
                            if ($cm->modname != $filters['modname']) {
                                continue;
                            } else if (!empty($filters['modid'])) {
                                if ($cm->instance != $filters['modid']) {
                                    continue;
                                } else {
                                    // Note that if we are only filtering by modname we don't break the loop.
                                    $modfound = true;
                                }
                            }
                        }

                        $module = array();

                        $modcontext = context_module::instance($cm->id);

                        //common info (for people being able to see the module or availability dates)
                        $module['id'] = $cm->id;
                        $module['name'] = external_format_string($cm->name, $modcontext->id);
                        $module['instance'] = $cm->instance;
                        $module['contextid'] = $modcontext->id;
                        $module['modname'] = (string) $cm->modname;
                        $module['modplural'] = (string) $cm->modplural;
                        $module['modicon'] = $cm->get_icon_url()->out(false);
                        $module['indent'] = $cm->indent;
                        $module['onclick'] = $cm->onclick;
                        $module['afterlink'] = $cm->afterlink;
                        $module['customdata'] = json_encode($cm->customdata);
                        $module['completion'] = $cm->completion;
                        $module['downloadcontent'] = $cm->downloadcontent;
                        $module['noviewlink'] = plugin_supports('mod', $cm->modname, FEATURE_NO_VIEW_LINK, false);
                        $module['dates'] = $activitydates;

                        // Check module completion.
                        $completion = $completioninfo->is_enabled($cm);
                        if ($completion != COMPLETION_DISABLED) {
                            $exporter = new \core_completion\external\completion_info_exporter($course, $cm, $USER->id);
                            $renderer = $PAGE->get_renderer('core');
                            $modulecompletiondata = (array)$exporter->export($renderer);
                            $module['completiondata'] = $modulecompletiondata;
                        }

                        if (!empty($cm->showdescription) or $module['noviewlink']) {
                            // We want to use the external format. However from reading get_formatted_content(), $cm->content format is always FORMAT_HTML.
                            $options = array('noclean' => true);
                            list($module['description'], $descriptionformat) = external_format_text($cm->content,
                                FORMAT_HTML, $modcontext->id, $cm->modname, 'intro', $cm->id, $options);
                        }

                        //url of the module
                        $url = $cm->url;
                        if ($url) { //labels don't have url
                            $module['url'] = $url->out(false);
                        }

                        $canviewhidden = has_capability('moodle/course:viewhiddenactivities',
                                            context_module::instance($cm->id));
                        //user that can view hidden module should know about the visibility
                        $module['visible'] = $cm->visible;
                        $module['visibleoncoursepage'] = $cm->visibleoncoursepage;
                        $module['uservisible'] = $cm->uservisible;
                        if (!empty($cm->availableinfo)) {
                            $module['availabilityinfo'] = \core_availability\info::format_info($cm->availableinfo, $course);
                        }

                        // Availability date (also send to user who can see hidden module).
                        if ($CFG->enableavailability && ($canviewhidden || $canupdatecourse)) {
                            $module['availability'] = $cm->availability;
                        }

                        // Return contents only if the user can access to the module.
                        if ($cm->uservisible) {
                            $baseurl = 'webservice/pluginfile.php';

                            // Call $modulename_export_contents (each module callback take care about checking the capabilities).
                            require_once($CFG->dirroot . '/mod/' . $cm->modname . '/lib.php');
                            $getcontentfunction = $cm->modname.'_export_contents';
                            if (function_exists($getcontentfunction)) {
                                $contents = $getcontentfunction($cm, $baseurl);
                                $module['contentsinfo'] = array(
                                    'filescount' => count($contents),
                                    'filessize' => 0,
                                    'lastmodified' => 0,
                                    'mimetypes' => array(),
                                );
                                foreach ($contents as $content) {
                                    // Check repository file (only main file).
                                    if (!isset($module['contentsinfo']['repositorytype'])) {
                                        $module['contentsinfo']['repositorytype'] =
                                            isset($content['repositorytype']) ? $content['repositorytype'] : '';
                                    }
                                    if (isset($content['filesize'])) {
                                        $module['contentsinfo']['filessize'] += $content['filesize'];
                                    }
                                    if (isset($content['timemodified']) &&
                                            ($content['timemodified'] > $module['contentsinfo']['lastmodified'])) {

                                        $module['contentsinfo']['lastmodified'] = $content['timemodified'];
                                    }
                                    if (isset($content['mimetype'])) {
                                        $module['contentsinfo']['mimetypes'][$content['mimetype']] = $content['mimetype'];
                                    }
                                }

                                if (empty($filters['excludecontents']) and !empty($contents)) {
                                    $module['contents'] = $contents;
                                } else {
                                    $module['contents'] = array();
                                }
                            }
                        }

                        // Assign result to $sectioncontents, there is an exception,
                        // stealth activities in non-visible sections for students go to a special section.
                        if (!empty($filters['includestealthmodules']) && !$section->uservisible && $cm->is_stealth()) {
                            $stealthmodules[] = $module;
                        } else {
                            $sectioncontents[] = $module;
                        }

                        // If we just did a filtering, break the loop.
                        if ($modfound) {
                            break;
                        }

                    }
                }
                $sectionvalues['modules'] = $sectioncontents;

                // assign result to $coursecontents
                $coursecontents[$key] = $sectionvalues;

                // Break the loop if we are filtering.
                if ($sectionfound) {
                    break;
                }
            }

            // Now that we have iterated over all the sections and activities, check the visibility.
            // We didn't this before to be able to retrieve stealth activities.
            foreach ($coursecontents as $sectionnumber => $sectioncontents) {
                $section = $sections[$sectionnumber];

                if (!$courseformat->is_section_visible($section)) {
                    unset($coursecontents[$sectionnumber]);
                    continue;
                }

                // Remove section and modules information if the section is not visible for the user.
                if (!$section->uservisible) {
                    $coursecontents[$sectionnumber]['modules'] = array();
                    // Remove summary information if the section is completely hidden only,
                    // even if the section is not user visible, the summary is always displayed among the availability information.
                    if (!$section->visible) {
                        $coursecontents[$sectionnumber]['summary'] = '';
                    }
                }
            }

            // Include stealth modules in special section (without any info).
            if (!empty($stealthmodules)) {
                $coursecontents[] = array(
                    'id' => -1,
                    'name' => '',
                    'summary' => '',
                    'summaryformat' => backup::FORMAT_MOODLE,
                    'modules' => $stealthmodules
                );
            }

        }

   
        $info = new completion_info($course);
        // $course_modules = $DB->get_records('course_modules', array('course' => $courseid));

        // $totalact = count($course_modules);
        // $completedtotalact = 0;
        // $inprogresstotalact = 0;

        // foreach ($course_modules as $keyalue) {
        // $modinfo = get_fast_modinfo($keyalue->course);
        // $cm = $modinfo->get_cm($keyalue->id);
        // $activity = $DB->get_record('course_modules', array('id' => $cm->id));
        // $userid = $DB->get_record('user', array('id' => 14));
        // $cdata = $info->get_data($activity, false, $userid->id);
        // $comvalue = $cdata->completionstate ? get_string('yes') : get_string('no');
        // if ($comvalue == 'yes') {
        //     $completedtotalact ++;
        // }else{
        //     $inprogresstotalact ++;
        // }
	// }
$course_mod = $DB->get_field_sql("SELECT COUNT(cm.id) AS activity_count
FROM {course_modules} cm
JOIN {course_sections} cs ON cm.section = cs.id
WHERE cs.course = $courseid
AND cs.section > 0");
       $course_modules = $DB->get_records('course_modules', array('course' => $courseid , 'deletioninprogress' => 0));
        //$getcmcom = $DB->get_records_sql("SELECT * FROM {course_modules} cm 
	//INNER JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid WHERE cmc.completionstate = 1 AND cmc.userid = $userid");
        $getcmcom = $DB->get_field_sql("SELECT COUNT(cmc.completionstate) as activitycompleted
                FROM {course_modules_completion} cmc
                JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
		WHERE cmc.completionstate = 1 AND cmc.userid = $userid AND cm.course = $courseid");
        $inprogressactivity = intval($course_mod) - intval($getcmcom);
        $datagetss = array(
            'totalactivity' => intval($course_mod),
            'completedactivity' => intval($getcmcom),
	    'inprogressactivity' => $inprogressactivity,
        );
       
        $response = array(
            "activitystatus" => $datagetss,
            "course_content" => $coursecontents,
	);
	        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8"); 
         echo json_encode($response);
        die;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_course_content_detail_returns() {
        $completiondefinition = \core_completion\external\completion_info_exporter::get_read_structure(VALUE_DEFAULT, []);

        return new external_single_structure(
            array(
                'activitystatus' => new external_value(PARAM_RAW, 'activity status', 0),
                'course_content' => new external_value(PARAM_RAW, 'course content Data', 0)
            )
        );
    }
}
