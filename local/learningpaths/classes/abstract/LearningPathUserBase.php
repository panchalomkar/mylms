<?php
defined('MOODLE_INTERNAL') || die;

/**
 * LearningPathUserBase
 *
 * @package local_learningpath
 * @author  Andres <andres.aguilar@paradisosolutions.com>
 */
class LearningPathUserBase extends LearningPathBase {
    protected $type;

    /**
     * Save a new learningpath user object.
     * @param (record) object to insert in the database
     */
    public function save($record) {
        global $CFG;
        require_once"{$CFG->dirroot}/local/learningpaths/lib.php";
        // Check if record exist.
        $relationfield = substr_replace($this->type , "", -1) . "id";
        $params = ['learningpathid' => $record->learningpathid, $relationfield => $record->userid];
        $exist = $this->db->get_record("learningpath_{$this->type}", $params);

        // If doesn't exist them insert it.
        if (!$exist) {
            $this->data = $this->db->insert_record("learningpath_{$this->type}", $record);
            /*Send reminder if activated*/
            send_notification_enroll($record);
            return $this->data;
        }
        return false;
    }

    /**
     * Remove user from learningpath. This will unenroll users and delete the relations records in the database
     */
    public function delete() {
        global $CFG, $PAGE;

        // These classes is being loaded because unenrol required it
        require_once "{$CFG->dirroot}/course/lib.php";
        require_once "{$CFG->dirroot}/enrol/locallib.php";

        // Let's to get the enrollments list

        // changed query to delete enteries from user_enrollment tb
        $sql = "
            SELECT le.id, lc.courseid as course, le.type
            FROM {learningpath_enrollments} le
            INNER JOIN {learningpath_courses} lc
            ON lc.id = le.learningpath_courseid
            WHERE le.user_relationid = ?
        ";

        $records = $this->db->get_records_sql($sql, [$this->id]);
        
        // Unenroll user of courses where was enrolled for the learning path
        foreach ($records as $record) {
            /*
            * @author VaibhavG
            * @since 6 Nov 2020
            * @desc #100 Learning path / User remove / Lp enrolment. 
            * Checks, that course is in multiple LP. If yes then removing only course group (not user unenrollment) from user's enrollment group section when user gets removed from that LP. If course is in not multiple LP then unenrolled the user from course.
            */
            //checks course is present in multi LP
            $checkMultiLP = $this->db->get_records('learningpath_courses', array('courseid'=>$record->course));            
            $MultiLP = count((array)$checkMultiLP);
            
            //gets multiple LP records of specific course
            $getMultiLP = $this->db->get_record('learningpath_courses', array('learningpathid' => $this->data->learningpathid, 'courseid'=>$record->course));

            //checks if multiple LP records has count more than 1
            if($MultiLP > 1){
                //gets LP course groups
                $getLPCourseGroups = $this->db->get_record('learningpath_course_groups', array('learningpath_courseid'=>$getMultiLP->id));
                //delete group members using LP course' groupid
                $this->db->delete_records('groups_members', ['groupid' => $getLPCourseGroups->groupid, 'userid' => $this->data->userid]);
            }else{
                //if course not present into multiple LP then unenroll the user frm course
                $this->unenroll_course($record->course, $this->data->userid);
            }

            // Delete record in enrollments
            $this->db->delete_records('learningpath_enrollments', ['id' => $record->id]);
        }

        return $this->db->delete_records("learningpath_{$this->type}", ['id' => $this->id]);
    }

    /**
    * Unenroll user of a course, using manual enrollment
    */
    public function unenroll_course($course, $userid) {
        global $PAGE,$DB;
        // Get course record, course enrollment manager object and enrol plugin object
        $course = $this->db->get_record('course', ['id' => $course]);
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
            
            /**
             * Changes for delete learningpath issue (checking if record exist in user_enrolments, then call unenrol_user() function)
             * @author Manisha M
             * @since 24-04-2019
             * @paradiso
            */
            $checkrecord = $DB->get_record('user_enrolments', array('enrolid'=>$instance->id, 'userid'=>$userid));
            if (isset($checkrecord) && !empty($checkrecord)) {
                $plugin->unenrol_user($instance, $userid);
            }
        }
    }
}