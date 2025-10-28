<?php
 /* 
 * @file observer to unenrol cohort user from lp when deleted from cohort
 * @author Manisha M
 * @since 23-07-2019
 */
defined('MOODLE_INTERNAL') || die();
require_once "{$CFG->dirroot}/enrol/locallib.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/abstract/LearningPathBase.php";

class observer {

    public static function unenrol_cohort_user(\core\event\cohort_member_removed $eventdata){
        global $DB, $CFG, $PAGE;
          
        $cohortid        =  $eventdata->objectid;
        $cohortmemberid  =  $eventdata->relateduserid;
        $isuserexist     =  $DB->get_record('cohort_members',array('cohortid'=>$cohortid,'userid'=>$cohortmemberid));
        $cohorts         =  $DB->get_records('learningpath_cohorts',array('cohortid'=>$cohortid));
        
        if(!$isuserexist){
        foreach($cohorts AS $cohort){

            $lpid   =   $cohort->learningpathid;
            if($cohort){
            

                $lp_courses = $DB->get_records('learningpath_courses',array('learningpathid'=>$lpid));
                foreach($lp_courses AS $val){
                    //unenrol cohort user from lp course
                    $course = $DB->get_record('course', ['id' => $val->courseid]);
                    if (isset($course) && !empty($course)) {
                        $manager = new course_enrolment_manager($PAGE, $course); 
                        $plugin = enrol_get_plugin('manual');
                
                        // Check if course manager has manual as available enrollment method
                        $instance = false;
                        $instances = $manager->get_enrolment_instances();
                        foreach ($instances as $i) { 
                            if ($i->enrol == 'manual') { 
                                $instance = $i;
                                break;
                            }
                        } 
        
                        // Validate enrollment method
                        if (!$instance || !$plugin || !$plugin->allow_enrol($instance)) {
                            return false;
                        }
                        $checkrecord = $DB->get_record('user_enrolments', array('enrolid'=>$instance->id, 'userid'=>$cohortmemberid));
                    
                        if (isset($checkrecord) && !empty($checkrecord)) {
                            $plugin->unenrol_user($instance, $cohortmemberid);
                        }
                    }
                }
            }
            }
        } 
    }
}