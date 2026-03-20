<?php
defined('MOODLE_INTERNAL') || die;
require_once "{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPath.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPathUser.php";

class LearningPathCohort extends LearningPathUserBase
{
    public $data;
    protected $id;
    protected $cohortid;

    public function __construct($id = 0) {
        parent::__construct();

        // Define database table.
        $this->type = 'cohorts';

        if ($id != 0) {
            // Get cohort with number of users.
            $sql = "
                SELECT
                    {learningpath_{$this->type}}.id id,
                    {learningpath_{$this->type}}.cohortid cohortid,
                    {learningpath_{$this->type}}.enrollment_date enrollment_date,
                    {learningpath_{$this->type}}.learningpathid learningpathid,
                    {cohort}.name name,
                    (
                        SELECT COUNT(*) FROM {cohort_members}
                        WHERE {cohort_members}.cohortid = {learningpath_{$this->type}}.cohortid
                    ) total_users
                    FROM {learningpath_{$this->type}}
                    INNER JOIN {cohort} ON {cohort}.id = {learningpath_{$this->type}}.cohortid
                    WHERE {learningpath_{$this->type}}.id = ?
                ";
            $data = $this->data = $this->db->get_record_sql($sql, [$id]);
            if ($data) {
                $this->id = $id;
                $this->cohortid = $this->data->cohortid;
            }
        }
    }

    /**
     * Save a new learningpath user object.
     * @param (record) object to insert in the database
     */
    public function save($record) {
        global $CFG, $DB;
        require_once"{$CFG->dirroot}/local/learningpaths/lib.php";
        // Check if record exist.
        $relationfield = substr_replace($this->type , "", -1) . "id";
        $params = ['learningpathid' => $record->learningpathid, $relationfield => $record->userid];
        $exist = $this->db->get_record("learningpath_{$this->type}", $params);

        // If doesn't exist them insert it.
        if (!$exist) {
            $this->data = $this->db->insert_record("learningpath_{$this->type}", $record);

            //Users enrolment
            /** get cohort users */
            $sql = "SELECT u.*
                    FROM {cohort_members} cm 
                    INNER JOIN {user} u ON cm.userid = u.id
                    WHERE cm.cohortid = ?";
            $cohort_users = $DB->get_records_sql($sql, [$record->cohortid]);
            /*Send reminder if activated*/
            send_notification_enroll($record);
            return $this->data;
        }
        return false;
    }

    // Get learningpath cohort users.
    public function get_users($cohortid = '') {
        if(!empty($cohortid)){
            $id = $cohortid;
        }else{
            $id = $this->cohortid;
        }
        return $this->db->get_records_sql('
            SELECT {cohort_members}.id user_relationid,
            {cohort_members}.userid FROM {cohort_members}
            WHERE {cohort_members}.cohortid = ?',
            [$id]
        );
    }

    /**
     * Remove cohort from learningpath.
     */
    public function delete($cohortid = '')
    {
        global $CFG;
        $users = $this->get_users($cohortid);

        // These classes is being loaded because unenrol required it
        require_once "{$CFG->dirroot}/course/lib.php";
        require_once "{$CFG->dirroot}/enrol/locallib.php";

        // Let's to get the enrollments list
        foreach ($users as $user) {
            $sql = "
                SELECT {learningpath_enrollments}.id, {learningpath_courses}.courseid as course, {learningpath_enrollments}.type, {learningpath_courses}.id
                FROM {learningpath_enrollments}
                INNER JOIN {learningpath_courses}
                ON {learningpath_courses}.id = {learningpath_enrollments}.learningpath_courseid
                WHERE {learningpath_enrollments}.user_relationid = ?
            ";
            $records = $this->db->get_records_sql($sql, [$user->user_relationid]);

            // Unenroll user of courses where was enrolled for the learning path
            foreach ($records as $record) {
                $this->unenroll_course($record->course, $user->userid);

                // Delete record in enrollments
                $this->db->delete_records('learningpath_enrollments', ['id' => $record->id]);
            }
        }
        return $this->db->delete_records("learningpath_{$this->type}", ['id' => $this->id]);
    }
}

