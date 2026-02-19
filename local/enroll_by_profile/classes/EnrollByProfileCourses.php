<?php
defined('MOODLE_INTERNAL') || die;

/**
 * EnrollByProfileCourses
 *
 * @package local_enroll_by_profile
 * @author  Ajinkya D
 */
class EnrollByProfileCourses {

    protected $db;
    protected $page;

    /*
     * Contruct Enroll by profile course Base object
     */
    public function __construct() {
        global $DB, $PAGE;
        $this->db = $DB;
        $this->page = $PAGE;
    }

    /** 
    * This function will return all available courses with name and checkbox. 
    * @return $output html content with course name and checkbox <label> <span> course name </span> <input value="courseid" type="checkbox" /> </label>.  
    */  
    public function GetCourses($elements){ 
        global $DB,$CFG;
        require_once($CFG->dirroot . '/local/enroll_by_profile/lib.php'); 
        /** 
         * Show only visible courses  
         * @author Yesid V. 
         * @since April 17, 2018  
         * @paradiso  
        */  
        $coursesSql = "select c.* from {course} c where c.visible = ? and c.id > 1 order by c.fullname";  
        $courses = $DB->get_records_sql($coursesSql, array(1)); 
        
        $la_course = array(); 
        foreach ($courses as $key => $course) { 
           $la_course[$key] = $course->fullname ; 
        } 
          
            /*  
            * @author VaibhavG  
            * @since 19th Feb 2021  
            * @desc 509 Rules Engine issues fixes. Applied Searchbar  
            */  
              
            $result = GetCheckBoxHTML($la_course,$elements);  
        
        return $result ;  
    } 

    /** 
    * This function will enroll/unenroll given user from given list of courses. 
    * @param $userid User id to be assigned 
    * @param $couresid array of course id to be used to enroll the user 
    * @param $method action to be executed add or remove  
    */  
    public function CourseAssignment($userid,$couresid,$method,$removeuser=null)  
    { 
        global $DB, $CFG; 
        require_once($CFG->dirroot.'/lib/enrollib.php');  
        require_once($CFG->dirroot.'/lib/accesslib.php'); 
        require_once ($CFG->dirroot . '/enrol/manual/locallib.php');  
        require_once($CFG->dirroot . '/local/enroll_by_profile/lib.php');
          
        // We use only manual enrol plugin here, if it is disabled no enrollment is made. 
        if (enrol_is_enabled('manual')) { 
            $manplugin = enrol_get_plugin('manual');  
        } else {  
            $manplugin = null;  
        } 
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));  
        if($method=='add' && $manplugin ){  
              
          foreach ($couresid as $cid => $course)  
          { 
              //check the previous enrollment done by any kind of enrollment method 
              $chksql = "SELECT * FROM {enrol} WHERE courseid = $course AND (enrol = 'manual' OR enrol = 'guest' OR enrol = 'self' OR enrol = 'cohort') ";  
              $chkinstance = $DB->get_records_sql($chksql); 
              if ($chkinstance)   
              { 
                foreach($chkinstance as $mani)  
                {   
                  if (!$DB->get_record('user_enrolments', array('enrolid'=>$mani->id, 'userid'=>$userid)))  
                  { 
                    $fields = array('courseid' => $course,'enrol' => 'manual'); 
                    $maninstance = $DB->get_record('enrol', $fields, '*');  
                    $manplugin->enrol_user($maninstance, $userid, $studentrole->id);                            
                  } 
                } 
                  
              } 
          } 
        } 
        elseif($method == 'remove' && $manplugin){  
            $params = []; 
            $params['userid'] = $userid;  
            foreach ($couresid as $cid => $course) {  
            //continue; 
            /** 
            * Requested by client THIS CANT BE UN COMMENTED LEAVE IT LIKE THIS. 
            * 11/26/2019 Esteban E. 
            */  
                $params['courseid'] = $course;  
                $sql = " SELECT ue.*, e.courseid, c.id AS contextid 
                    FROM {user_enrolments} ue 
                    JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'manual')  
                    JOIN {context} c ON (c.instanceid = e.courseid AND c.contextlevel = " . CONTEXT_COURSE . ") 
                      WHERE e.courseid = :courseid AND ue.userid = :userid "; 
                $enrolments = $DB->get_records_sql($sql, $params);  
                foreach ($enrolments as $enrolment) { 
                    $instance = $DB->get_record('enrol', ['id' => $enrolment->enrolid], '*', MUST_EXIST); 
                    $manplugin->unenrol_user($instance, $enrolment->userid);  
                } 
            } 
        } 
    }
}