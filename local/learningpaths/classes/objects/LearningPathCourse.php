<?php
require_once "{$CFG->dirroot}/local/learningpaths/classes/abstract/LearningPathBase.php";

/**
 * LearningPathUser
 * Learning path Course class manage all process about learning path users including all operations in database
 * @package local_learningpath
 * @author  Andres <andres.aguilar@paradisosolutions.com>
 */

class LearningPathCourse extends LearningPathBase
{
    protected $id;
    public $data;

    public function __construct($id = 0)
    {
        parent::__construct();

        if ($id > 0) {
            $sql = "SELECT {learningpath_courses}.id, {learningpath_courses}.courseid, {learningpath_courses}.required, {learningpath_courses}.learningpathid, {course}.fullname as coursename,
                    {course}.category, {course}.sortorder, {course}.shortname, {course}.fullname as fullname, {course}.idnumber, {course}.startdate, {course}.visible, {course}.groupmode, {course}.groupmodeforce,
                    {course}.cacherev, {learningpath_courses}.course_active
                    FROM {learningpath_courses}
                    INNER JOIN {course} ON {learningpath_courses}.courseid = {course}.id
                    WHERE {learningpath_courses}.id = ?";
            $this->data = $this->db->get_record_sql($sql, [$id]);
            $this->id = $id;
        }
    }

    /**
    * Add course to learningpath
    */
    public function save($record) {
        // Check if the record exist in the database
        $exist = $this->db->get_record("learningpath_courses", ['learningpathid' => $record->learningpathid, 'courseid' => $record->courseid]);
        // If doesn't exist them insert it
        if (!$exist) {
            return $this->db->insert_record("learningpath_courses", $record);
        }
        return false;
    }

    /**
    * Get learning path course prerequisites
    */
    public function get_prerequisites() {
        // Get courses
        $company_courses_list = $this->get_company_courses();
        // query changed for prerequisite not working in tenant
        $company_courses_sql = ($company_courses_list) ? " AND {learningpath_course_prereq}.prerequisite IN ({$company_courses_list})" : "";

        $result = $this->db->get_records_sql("
            SELECT {learningpath_course_prereq}.id id, {learningpath_course_prereq}.learningpath_courseid learningpath_courseid, {learningpath_course_prereq}.prerequisite prerequisite, {course}.fullname as coursename
            FROM {learningpath_course_prereq}
            INNER JOIN {course} ON {course}.id = {learningpath_course_prereq}.prerequisite
            WHERE {learningpath_course_prereq}.learningpath_courseid = ? {$company_courses_sql} ORDER BY {learningpath_course_prereq}.prerequisite
        ", [$this->id]);
        return $result;
    }

    /**
    * Get courses list which aren't available to be used as prerequisite of this course
    */
    public function get_does_not_as_prerequisites() {
        // Get courses
        $company_courses_list = $this->get_company_courses();
        $company_courses_sql = ($company_courses_list) ? " AND {learningpath_course_prereq}.learningpath_courseid IN ({$company_courses_list})" : "";

        $result = $this->db->get_record_sql("
            SELECT {learningpath_course_prereq}.id id, {learningpath_course_prereq}.learningpath_courseid learningpath_courseid, {learningpath_course_prereq}.prerequisite prerequisite
            FROM {learningpath_course_prereq}
            WHERE {learningpath_course_prereq}.prerequisite = ? {$company_courses_sql} ORDER BY {learningpath_course_prereq}.learningpath_courseid
        ", [$this->data->courseid]);

        return $result;
    }

    /**
    * Update required value
    */
    public function update_required($required) {
        return $this->db->set_field("learningpath_courses", 'required', $required, ['id' => $this->id]);
    }

    /**
    * Get course list available to use as prerequisites
    * Return a list of learningpath objects in the same learningpath. This doesn't return himself
    */
    public function get_learningpath_courses() {
        // Get courses
        $company_courses_list = $this->get_company_courses();
        $company_courses_sql = ($company_courses_list) ? " AND {learningpath_courses}.courseid IN ({$company_courses_list})" : "";

        $courses = [];
        $sql = "SELECT {learningpath_courses}.id FROM {learningpath_courses} WHERE learningpathid = ? AND {learningpath_courses}.id != ? {$company_courses_sql}";
        $records = $this->db->get_records_sql($sql, [$this->data->learningpathid, $this->id]);
        foreach ($records as $record) {
            $courses[] = new LearningPathCourse($record->id);
        }

        return $courses;
    }

    /**
    * Insert a prerequisites list
    * @param (courses) add a list of prerequisites to this learning path course
    */
    public function add_prerequisites($courses) {   
        $records = [];
        $courses = array_map('intval', $courses);
        foreach ($courses as $courseid) {
            if (is_int($courseid)) {
                // Validate doesn't exist any record with same information
                $record = ['learningpath_courseid' => $this->id, 'prerequisite' => $courseid];
                $exist = $this->db->get_record('learningpath_course_prereq', $record);
                if (!$exist) {
                    $records[] = (object) $record;
                }
            }
        }
        $this->db->insert_records('learningpath_course_prereq', $records);
    }

    /**
    * Remove all prerequisites of this course
    */
    public function remove_all_prerequisites() {
        return $this->db->delete_records('learningpath_course_prereq', ['learningpath_courseid' => $this->id]);
    }

    /**
    * Update the course position on the learningpath
    * @param (position) course position in learningpath. Position is used to organize the learningpath
    */
    public function set_position($position) {
        return $this->db->set_field("learningpath_courses", 'position', $position, ['id' => $this->id]);
    }

    /**
    * Delete relation between course and learningpath
    */
    public function delete() {
        global $CFG,$PAGE,$DB;
        require_once "{$CFG->dirroot}/enrol/locallib.php";
        
        $sql = "SELECT userid FROM {learningpath_users} WHERE learningpathid = ?";
        $records = $this->db->get_records_sql($sql, [$this->data->learningpathid]);
     
        if(isset($records)){
          foreach($records AS $record){
            $course = $DB->get_record('course', ['id' => $this->data->courseid]);
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
                $checkrecord = $DB->get_record('user_enrolments', array('enrolid'=>$instance->id, 'userid'=>$record->userid));
                if (isset($checkrecord) && !empty($checkrecord)) {
                    $plugin->unenrol_user($instance, $record->userid);
                }
            }
          }
        }
        // unenrol cohort members from course
        $course = $DB->get_record('course', ['id' => $this->data->courseid]);
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
            
            $checkrecord = $DB->get_records('user_enrolments', array('enrolid'=>$instance->id));
            
            if (isset($checkrecord) && !empty($checkrecord)) {
                foreach($checkrecord AS $rec){   
                    $lpid =$this->data->learningpathid;
                    $exist = $DB->get_record_sql("SELECT {cohort_members}.userid as userid 
                                                FROM {learningpath_cohorts}  
                                                join  {cohort_members}   
                                                WHERE {learningpath_cohorts}.cohortid = {cohort_members}.cohortid 
                                                and {learningpath_cohorts}.learningpathid = $lpid 
                                                and {cohort_members}.userid = $rec->userid");
                    
                    if($exist){    $plugin->unenrol_user($instance, $exist->userid);  }
                }
            }
        }
        
       return  $DB->delete_records("learningpath_courses", ['id' => $this->id]);
    }
    /**
    * Delete relation between course and learningpath
    */
    public function deletePrerreq() {
        $sql = "DELETE lpcp.*
                FROM {learningpath_course_prereq} lpcp
                INNER JOIN {learningpath_courses} lpc ON lpc.id = lpcp.learningpath_courseid
                WHERE lpc.learningpathid = :learningpathid AND lpcp.prerequisite = :prerequisite";
        
        return $this->db->execute($sql, ['learningpathid' => $this->data->learningpathid, 'prerequisite' => $this->data->courseid]);
    }

    /**
    * Check if user was enrolled into this this learningpath course
    * @param (userid) learningpath user id to verify
    * @return Result of the query. This could be false if doesn't exist or std object with enrollment information
    */
    public function is_enrolled($userid, $type = 'learningpath_user') {
        return $this->db->get_record("learningpath_enrollments", ["user_relationid" => $userid, "learningpath_courseid" => $this->id, "type" => $type]);
    }

    /**
    * Check if user has this course completed
    * @param (userdata) learningpath user data
    * @return 1 if this is completed or 0 if doesn't
    */
    public function is_completed($userdata) {
        // Load completion library
        global $CFG;
        require_once "{$CFG->dirroot}/lib/completionlib.php";
        $course = $this->db->get_record('course', ['id' => $this->data->courseid]);
        $completion = new completion_info($course);
        return ($completion->is_course_complete($userdata->userid)) ? 1 : 0;
    }

    /**
    * Check if a user complete the lerningpath course prerequisites
    * @param (userdata) learningpath user data
    */
    public function validate_prerequisites($userdata) {
        // Get prerequisites, if learningpath course doesn't have it, return true
        $prerequisites = $this->get_prerequisites();
        if (count($prerequisites) == 0) {
            return true;
        }

        // Load completion library
        global $CFG;
        require_once "{$CFG->dirroot}/lib/completionlib.php";

        // It user doesn't have all prerequisites as complete return false
        foreach ($prerequisites as $prerequisite) {
            // Get course record
            $course = $this->db->get_record('course', ['id' => $prerequisite->prerequisite]);
            // Get course completion and check if current user has it completed
            $completion = new completion_info($course);
            $complete = $completion->is_course_complete($userdata->userid);
            if (!$complete)
                return false;
        }
        return true;
    }

    /**
    * Enrol an user in this courses by learningpath
    * @param (userdata) learningpath user data
    */
    public function enrol_user($userdata) {
        /**
        * Get Roll by shortname by default search for student roll
        * @param (db) moodle database connection object
        * @param (shortname) role shortname to seek
        * @return roll id or false
        */
        if (!function_exists("get_roll_by_shortname")) {
            function get_roll_by_shortname($db, $shortname = "student")
            {
                if ($record = $db->get_record('role', ['shortname' => $shortname])) {
                    return $record->id;
                } else {
                    return false;
                }
            }
        }

        // Global moodle variables
        global $PAGE, $CFG;
        
        // This class is being leaded here because enrol required it
        require_once "{$CFG->dirroot}/course/lib.php";
        require_once "{$CFG->dirroot}/enrol/locallib.php";
        /*
        * @author VaibhavG
        * @since 6 Nov 2020
        * @desc #100 Learning path / User remove / Lp enrolment. Included the enrollib.php because the enrollment doesn't happened into LP courses. 
        */
        require_once($CFG->libdir . '/enrollib.php');

        // Get student roll id or throw an exception
        $student = get_roll_by_shortname($this->db, "student");
        if (!$student) {
            throw new moodle_exception();
        }

        // Get course record, course enrollment manager object and enrol plugin object
        $course = $this->db->get_record('course', ['id' => $this->data->courseid]);
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

        // Enroll user and save record in learningpath_enrollments
        try {
            $date = new DateTime();
            $record = new stdClass();
            $record->type = $userdata->type;
            $record->user_relationid = $userdata->user_relationid;
            $record->learningpath_courseid = $this->id;
            $record->enrollment_date = $date->getTimestamp();
            $plugin->enrol_user($instance, $userdata->userid, $student);

            // Adding user to learningpath group
            $this->add_user_to_group($userdata);
            return $this->db->insert_record("learningpath_enrollments", $record);
        } catch (Exception $e) {
            $plugin->unenrol_user($instance, $userdata->userid);
            return $e;
        }
    }

    /**
    * This function will add an user to a learningpath course group
    * @param (user) learningpath user object
    */
    private function add_user_to_group($user) {
        // Load groups library
        global $CFG;
        require_once "{$CFG->dirroot}/group/lib.php";

        // Validate if course exist
        $sql = "SELECT {learningpath_course_groups}.groupid FROM {learningpath_course_groups} INNER JOIN {groups} ON {groups}.id = {learningpath_course_groups}.groupid WHERE {learningpath_course_groups}.learningpath_courseid = ?";
        if ($record = $this->db->get_record_sql($sql, [$this->id])) {
            $groupid = $record->groupid;
        } else {
            $learningpath_name = $this->get_learningpath_name();
            
            $group = new stdClass();
            $group->courseid = $this->data->courseid;
            $group->name = trim("{$learningpath_name}-{$this->data->coursename}");
            $groupid = groups_create_group($group);
            $this->db->insert_record('learningpath_course_groups', ['learningpath_courseid' => $this->id, 'groupid' => $groupid]);
        }
        
        return groups_add_member($groupid, $user->userid);
    }

    /**
    * Return the learningpath name of current learningpath course
    */
    private function get_learningpath_name() {
        $record = $this->db->get_record("learningpaths", ['id' => $this->data->learningpathid]);
        return $record->name;
    }

    /**
    * @author ShivkumarY
    * @desc Return learnigpath courses already used as prerequisite
    * @since 25 Jan 2021
    */
    public function learningpath_courses_added_as_prerequisite($lpid){
        global $DB;

        $courses = $DB->get_records_sql("select lc1.id from {learningpath_courses} lc1 where lc1.courseid IN (SELECT lcp.prerequisite FROM {learningpath_courses} lc, {learningpath_course_prereq} lcp WHERE lc.id=lcp.learningpath_courseid AND lc.learningpathid =lc1.learningpathid ) AND lc1.learningpathid = ?",[$lpid]);
        return $courses;
    }
}