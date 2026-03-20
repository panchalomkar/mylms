<?php
defined('MOODLE_INTERNAL') || die;

/**
 * LearningPathBase
 *
 * @package local_learningpath
 * @author  Andres <andres.aguilar@paradisosolutions.com>
 */
class LearningPathBase {
    protected $db;
    protected $page;

    /*
     * Contruct learning Path Base object
     */
    public function __construct() {
        // Assign moodle objects like learning path object attributes for internal usage.
        global $DB, $PAGE;
        $this->db = $DB;
        $this->page = $PAGE;
    }

    /**
     * Get a list with company available courses in case user belong to a MT company
     */
    public function get_company_courses() {
        $companycourses = false;
        if ($company = lms_get_current_editing_company()) {
            if ($companycourses = $this->get_company_courses_array($company->id)) {
                $companycourses = array_keys($companycourses);
                $companycourses = implode(', ', $companycourses);
            }
        }
        return $companycourses;
    }

    /**
     * Return a company courses list of this learningpath
     * @param (companyid) companyid to get courses
     * @return (array) courses list related to the company
     */
    public function get_company_courses_array($companyid) {
        // Get company courses list.
        // Updated query due to performance issue. 
        $sql = "SELECT c.id 
                    FROM {course} as c 
                    JOIN {company_course} as cc 
                    ON c.id = cc.courseid 
                    JOIN {iomad_courses} as ic 
                    ON c.id = ic.courseid 
                AND cc.courseid = ic.courseid 
                WHERE cc.companyid = {$companyid} 
                    OR ic.shared = 1 
                    GROUP BY c.id";
        $companycourses = $this->db->get_records_sql($sql);
        return $companycourses;
    }
}