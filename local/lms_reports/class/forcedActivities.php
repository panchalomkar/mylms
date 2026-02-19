<?php

require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/conditionlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');

class ForcedActivities
{
	//Function of contruct
	function __construct($course, $section, $add, $info)
	{
		//Get the course
		global $DB;
		$course = $DB->get_record('course', array('id'=>$course), '*', MUST_EXIST);

		list($module, $context, $cw) = can_add_moduleinfo($course, $add, $section);

		$cm = null;

		//Set the datas
	    $data = new stdClass();
	    $data->section          = $section;  // The section number itself - relative!!! (section column in course_sections)
	    $data->visible          = $cw->visible;
	    $data->course           = $course->id;
	    $data->module           = $module->id;
	    $data->modulename       = $module->name;
	    $data->groupmode        = $course->groupmode;
	    $data->groupingid       = $course->defaultgroupingid;
	    $data->groupmembersonly = 0;
	    $data->id               = '';
	    $data->instance         = '';
	    $data->coursemodule     = '';
	    $data->add              = $add;
	    $data->return           = 0; //must be false if this is an add, go back to course view on cancel
	    $data->sr               = null;
	    $data->intro            = $info->intro;
	    $data->introformat      = $info->introformat;
	    $data->name 			= $info->name;

	    if (!empty($CFG->enableavailability)) {
	        $data->availabilityconditionsjson = $cm->availability;
	    }

	    if (plugin_supports('mod', $data->modulename, FEATURE_MOD_INTRO, true)) {
	        $draftid_editor = file_get_submitted_draft_itemid('introeditor');
	        $currentintro = file_prepare_draft_area($draftid_editor, $context->id, 'mod_'.$data->modulename, 'intro', 0, array('subdirs'=>true), $data->intro);
	        $data->introeditor = array('text'=>$currentintro, 'format'=>$data->introformat, 'itemid'=>$draftid_editor);
	    }

	    if (plugin_supports('mod', $data->modulename, FEATURE_ADVANCED_GRADING, false)
	            and has_capability('moodle/grade:managegradingforms', $context)) {
	        require_once($CFG->dirroot.'/grade/grading/lib.php');
	        $gradingman = get_grading_manager($context, 'mod_'.$data->modulename);
	        $data->_advancedgradingdata['methods'] = $gradingman->get_available_methods();
	        $areas = $gradingman->get_available_areas();

	        foreach ($areas as $areaname => $areatitle) {
	            $gradingman->set_area($areaname);
	            $method = $gradingman->get_active_method();
	            $data->_advancedgradingdata['areas'][$areaname] = array(
	                'title'  => $areatitle,
	                'method' => $method,
	            );
	            $formfield = 'advancedgradingmethod_'.$areaname;
	            $data->{$formfield} = $method;
	        }
	    }

	    if ($items = grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>$data->modulename,
                                         'iteminstance'=>$data->instance, 'courseid'=>$course->id))) {
	        // add existing outcomes
	        foreach ($items as $item) {
	            if (!empty($item->outcomeid)) {
	                $data->{'outcome_'.$item->outcomeid} = 1;
	            }
	        }

	        // set category if present
	        $gradecat = false;
	        foreach ($items as $item) {
	            if ($gradecat === false) {
	                $gradecat = $item->categoryid;
	                continue;
	            }
	            if ($gradecat != $item->categoryid) {
	                //mixed categories
	                $gradecat = false;
	                break;
	            }
	        }
	        if ($gradecat !== false) {
	            // do not set if mixed categories present
	            $data->gradecat = $gradecat;
	        }
	    }

	    $sectionname = get_section_name($course, $cw);
	    $fullmodulename = get_string('modulename', $module->name);

	    if ($data->section && $course->format != 'site') {
	        $heading = new stdClass();
	        $heading->what = $fullmodulename;
	        $heading->in   = $sectionname;
	        $pageheading = get_string('updatingain', 'moodle', $heading);
	    } else {
	        $pageheading = get_string('updatinga', 'moodle', $fullmodulename);
	    }
	    $navbaraddition = null;
            if($add=='hsuforum')
            {
             $data->gradetype = '1';
              $data->type = 'general';
              $data->cmidnumber = '';
              $data->scale = 0;
            }
            if($add == 'resource')
            {
              $data->display = 1;
              $data->files = 2;
              
            }
            if($add=='feedback')
            {
                 $data->page_after_submit = '<p><span>Thank you for your feedback!</span></p>';
                            $data->site_after_submit =1;
                            $data->page_after_submit_editor = '';
                            $data->page_after_submit = '<p><span>Thank you for your feedback!</span></p>';
                            $data->timeclose = '';
                            $data->timeopen  ='';
            }
            
       
	    $modinfo = add_moduleinfo($data, $course, $info);
            
             if($add=='feedback')
            {
                            
               $feedback_item = new stdClass();
               $feedback_item->required = 0;
               $feedback_item->name ='How would you rate this course?';
                       $feedback_item->label ='1'; 
                       $feedback_item->horizontal =0; 
                       $feedback_item->subtype ='r';
                       $feedback_item->ignoreempty = 0; 
                       $feedback_item->hidenoselect= 0; 
                       $feedback_item->dependitem =0; 
                       $feedback_item->dependvalue = '';
                       $feedback_item->position = '1';
                       $feedback_item->cmid = $modinfo->coursemodule;
                       $feedback_item->id = 0;
                       $feedback_item->feedback = $modinfo->id; 
                       $feedback_item->template =0;   
                       $feedback_item->typ = 'multichoice';
                       $feedback_item->hasvalue = 1;
                       $feedback_item->options = ''; 
                       $feedback_item->clone_item = 0; 
                       $feedback_item->save_item ='';
                       $feedback_item->presentation ='r>>>>>Not Helpful
|Neutral
|Helpful';
                  $item->id = $DB->insert_record('feedback_item', $feedback_item);               
                       
            }

 

            
	}
}