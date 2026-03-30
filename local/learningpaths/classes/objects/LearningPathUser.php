<?php
defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->dirroot}/local/learningpaths/classes/abstract/LearningPathBase.php");

/**
 * LearningPathUser
 * Learning path User class to manage all process about learning path users including all operations in database
 * @package local_learningpath
 * @author  Andres <andres.aguilar@paradisosolutions.com>
 */

class LearningPathUser extends LearningPathUserBase
{
    public $data;
    protected $id;

    /**
     * Construct object with database data
     */
    public function __construct($id = 0) {
        parent::__construct();
        $this->type = "users";

        if ($id != 0) {
            $sql = "
                SELECT
                    {learningpath_{$this->type}}.id,
                    {learningpath_{$this->type}}.userid,
                    {learningpath_{$this->type}}.learningpathid,
                    {learningpath_{$this->type}}.enrollment_date,
                    {user}.firstname,
                    {user}.lastname,
                    {user}.username,
                    {user}.email
                FROM {learningpath_{$this->type}}
                INNER JOIN {user} ON {user}.id = {learningpath_{$this->type}}.userid
                WHERE {learningpath_{$this->type}}.id = ? AND {user}.deleted = 0 AND {user}.suspended = 0
                ";

            if ($this->data = $this->db->get_record_sql($sql, [$id])) {
                $this->id = $this->data->id;
            }
        }
    }

    /**
     * Get courses completed in learningpath by this user. This will be used to calculated the learningpath progress and completion
     */
    public function completed_courses() {
        // Get learningpath courses list.
        $learningpath = new LearningPath($this->data->learningpathid);

        // Load completion library.
        global $CFG;
        require_once("{$CFG->dirroot}/lib/completionlib.php");

        // Flags to count number of completed courses and courses required for learningpath completion.
        $completed = $required = 0;
        foreach ($learningpath->data->courses as $learningpathcourse) {
            //if (!$learningpathcourse->data->required) {
                //continue;
            //}
            $course = $this->db->get_record('course', ['id' => $learningpathcourse->data->courseid]);
            $completion = new completion_info($course);
            $completed += ($completion->is_course_complete($this->data->userid)) ? 1 : 0;
            $required += 1;
        }
        if ($required == 0) {
            return (object) [
                'complated' => $completed,
                'total_required' => $required,
                'percentage' => 0
            ];
        }

        return (object) [
            'complated' => $completed,
            'total_required' => is_array($required) ? count($required) : 0,
            'percentage' => round(($completed / $required) * 100)
        ];
    }
}