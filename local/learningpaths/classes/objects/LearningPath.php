<?php
require_once "{$CFG->dirroot}/local/learningpaths/classes/forms/ManageUsersForm.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/forms/ManageCohortsForm.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/abstract/LearningPathBase.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/abstract/LearningPathUserBase.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPathCourse.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPathUser.php";
require_once "{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPathCohort.php";

/**
 * LearningPath
 * Learning path class to manage all process about learning paths
 * @package local_learningpath
 * @author  Andres <andres.aguilar@paradisosolutions.com>
 */

class LearningPath extends LearningPathBase {
    // Learning Path Attributes
    private $renderer;
    protected $id;
    protected $courses;
    protected $users;
    protected $cohorts;
    public $data;

    /**
    * Contruct learning path object
    * @param (id) learning path unique id in the database
    */
    public function __construct($id = 0, $isAjax = false)
    {
        global $PAGE;
        // Call parent contructor
        parent::__construct();
    //    $PAGE->set_pagelayout('standard');
        // Assign object attribute values
        $this->id = ($id != 0) ? $id : false;
        $this->renderer = $this->page->get_renderer('local_learningpaths');
        // If id is highter 0, consult into database the learning path general data. Else data will be an empty object
        if ($this->id > 0) {
            $data = $this->db->get_record("learningpaths", ['id' => $id]);
            if ($data) {
                // load Learning path data
                $this->load_learningpath_data($data);
            } elseif($isAjax) {
                return $this->data = new stdClass();
            }else{
                throw new moodle_exception(get_string('learningpath_no_found', 'local_learningpaths'), 'core_plugin');
            }
        } else {
            $this->data = new stdClass();
        }
    }

    /**
    * This function will load (or reload) all learning path data like general information, courses, users, etc
    * @param (data) new learning path general data
    */
    private function load_learningpath_data($data)
    {
        // Assign basic data property
        $this->data = $data;

        // Call some methods to load important data like users and courses
        $this->load_courses();
        $this->load_users();
        $this->load_cohorts();
        $this->load_available_course_list();
        $this->load_available_users_list();
        $this->load_available_cohorts_list($this->data->companyid);

        // Get some internal properties
        $this->data->total_courses = count($this->courses);
        $this->data->image = LearningPath::get_learningpath_image_object($this->id);
        $this->data->total_courses_required = $this->db->count_records_sql('
            SELECT count(*) FROM {course}
            INNER JOIN {learningpath_courses}
            ON {learningpath_courses}.courseid = {course}.id
            WHERE {learningpath_courses}.learningpathid = ? AND {learningpath_courses}.required = 1
        ', [$this->id]);
    }

    /*
    * Load learning path courses in object
    */
    private function load_courses() {
        // Get courses
        $company_courses_list = $this->get_company_courses();
        $company_courses_sql = ($company_courses_list) ? " AND {learningpath_courses}.courseid IN ({$company_courses_list})" : "";

        // Is searching a course by name?
        $coursename = optional_param('coursename', '', PARAM_TEXT);
        $coursename_sql = ($coursename != '') ? " AND {course}.fullname LIKE '%{$coursename}%'" : "";

        $sql = "SELECT {learningpath_courses}.id id
                FROM {course}
                INNER JOIN {learningpath_courses} ON {learningpath_courses}.courseid = {course}.id
                WHERE {learningpath_courses}.learningpathid = ? {$company_courses_sql} {$coursename_sql}
                ORDER BY {learningpath_courses}.position";
        $courses = $this->db->get_records_sql($sql, [$this->id]);

        // Add course objects to learningpath object
        $this->data->courses = $this->courses = [];
        foreach ($courses as $course) {
            $this->courses[] = $this->data->courses[] = new LearningPathCourse($course->id);
        }
    }

    /*
    * Load learning path users in learningpath object
    * @return true if is a normal load or array with learning path users, if have to load all users
    */
    private function load_users() {
        // Get users
        $users = (Object) $this->get_users_db(false, true, true);

        // Add users to learningpath object
        $this->users = $this->data->users = [];
        foreach ($users->users as $user) {
            $this->users[] = $this->data->users[] = new LearningPathUser($user->id);
        }
        
        //Assign total users
        $this->data->total_users = $users->total;
        return true;
    }

    /**
     * Get users from database
     */
    private function get_users_db($all = false, $completed = true, $total = false)
    {
        // For multitenant, join with multitenant relationship tables
        $join = $where = "";
        if ($company = lms_get_current_editing_company()) {
            $join .= "INNER JOIN {company_users} ON {company_users}.userid = {learningpath_users}.userid";
            $where .= " AND {company_users}.companyid = {$company->id}";
        }
        // Completed?
        if ($completed == false) {
            $where .= " AND {learningpath_users}.userid NOT IN (SELECT userid FROM {learningpath_completion} WHERE {learningpath_completion}.learningpathid = {$this->id})";
        }

        // Is searching an user by name?
        $user = optional_param('user', '', PARAM_TEXT);
        $where .= ($user != '') ? " AND ({user}.firstname LIKE '%{$user}%' OR {user}.lastname LIKE '%{$user}%')" : "";
        
        // Build sql
        $sql = "FROM {learningpath_users} {$join}
            INNER JOIN {user} ON {user}.id = {learningpath_users}.userid
            WHERE {learningpath_users}.learningpathid = ?
            AND {user}.deleted = 0 AND {user}.suspended = 0
            {$where}";

        // Return all users or using pagination?
        if ($all) {
            $users = $this->db->get_records_sql("SELECT {learningpath_users}.id id {$sql}", [$this->id]);
        } else {
            $this->data->users_page = optional_param('page', 0, PARAM_INT);
            $items = optional_param('userperpage', 10, PARAM_INT);
            $offset = $items * ($this->data->users_page);
            $users = $this->db->get_records_sql("SELECT {learningpath_users}.id AS id {$sql} LIMIT {$offset}, {$items}", [$this->id]);
        }

        // Needs the total of users?
        if ($total) {
            $total = $this->db->get_record_sql("SELECT count(*) as total_users {$sql}", [$this->id]);
            return ['users' => $users, 'total' => intval($total->total_users)];
        }

        return $users;
    }

    /**
    * Load all users in the learningpath
    * @return array with all learningpath users
    */
    public function get_all_users() {
        $return = [];
        $users = $this->get_users_db(true);
        foreach ($users as $user) {
            $return[] = new LearningPathUser($user->id);
        }
        return $return;
    }

    /**
    * Load all users in the learningpath that don't have completed the learningpath
    * @return array with all learningpath users
    */
    public function get_all_users_not_completed() {
        $return = [];
        $users = $this->get_users_db(true, false);
        foreach ($users as $user) {
            $return[] = new LearningPathUser($user->id);
        }
        return $return;
    }

    /**
    * Load learningpath cohorts
    */
    private function load_cohorts() {
        // Is searching a cohort by name?
        $cohort = optional_param('cohort', '', PARAM_TEXT);
        $page = optional_param('page', 0, PARAM_INT);
        $dashboard_per_page = optional_param('cohortsperpage', 10, PARAM_INT);
        if ($page !== false) {
            $offset = $page * $dashboard_per_page;
            $limit = $dashboard_per_page;
        }
        $cohort_where = ($cohort != '') ? " AND {cohort}.name LIKE '%{$cohort}%'" : "";
        $cohort_join = "INNER JOIN {cohort} ON {cohort}.id = {learningpath_cohorts}.cohortid";

        $cohorts = $this->db->get_records_sql("
            SELECT {learningpath_cohorts}.id id FROM {learningpath_cohorts} {$cohort_join} 
            WHERE {learningpath_cohorts}.learningpathid = ? {$cohort_where}",
            [$this->id], $offset, $limit);
        $cohorts_total= $this->db->get_records_sql("
            SELECT {learningpath_cohorts}.id id FROM {learningpath_cohorts} {$cohort_join} 
            WHERE {learningpath_cohorts}.learningpathid = ? {$cohort_where}",
            [$this->id]);
        // Add cohorts to learningpath object
        $this->cohorts = $this->data->cohorts = [];
        foreach ($cohorts as $cohort) {
            $this->cohorts[] = $this->data->cohorts[] = new LearningPathCohort($cohort->id);
        }
        $this->data->total_cohorts = count($cohorts_total);
    }

    /*
    * load available courses list
    */
    private function load_available_course_list() {
        // Get courses
        $company_courses_list = $this->get_company_courses();
        $company_courses_sql = ($company_courses_list) ? " AND {learningpath_courses}.courseid IN ({$company_courses_list})" : "";
        $courses = $this->db->get_records_sql("
            SELECT {course}.id id
            FROM {course}
            INNER JOIN {learningpath_courses} ON {learningpath_courses}.courseid = {course}.id
            WHERE {learningpath_courses}.learningpathid = ? {$company_courses_sql}", [$this->id]
        );

        // Get courses ids, add guest user to array and convert to string to use on sql query
        $courses = array_keys($courses);
        $courses[] = 1;
        $courses = implode(',', $courses);

        $company_courses_sql = ($company_courses_list) ? " AND {course}.id IN ({$company_courses_list})" : "";
        $this->data->available_courses = $this->db->get_records_sql("
            SELECT {course}.id id, {course}.fullname coursename
            FROM {course} WHERE visible = 1 AND {course}.id NOT IN ($courses) {$company_courses_sql}
        ");
    }

    /*
    * load available users list to add in the learning path
    */
    private function load_available_users_list() {
        $company_sql_join = $company_sql_where = "";
        if ($company = lms_get_current_editing_company()) {
            $company_sql_join = "INNER JOIN {company_users} ON {company_users}.userid = {user}.id";
            $company_sql_where = "AND {company_users}.companyid = {$company->id}";
        }

        // Get current users list
        $users = $this->db->get_records_sql("
            SELECT {user}.id id
            FROM {user}
            INNER JOIN {learningpath_users} ON {learningpath_users}.userid = {user}.id {$company_sql_join}
            WHERE {learningpath_users}.learningpathid = ? {$company_sql_where}", [$this->id]
        );

        // Get users ids, add guest user to array and convert to string to use on sql query
        $users = array_keys($users);
        $users[] = 1;
        $users = implode(",", $users);

        // Execute the query
        $this->data->available_users = $this->db->get_records_sql("
            SELECT {user}.id id, {user}.firstname firstname, {user}.lastname lastname, {user}.email email
            FROM {user} {$company_sql_join}
            WHERE {user}.id NOT IN ({$users}) AND {user}.deleted = 0 AND {user}.suspended = 0 {$company_sql_where}"
        );
    }

    /**
    * Return a list of learningpath courses with a name that matches the search
    * In this case will return the result of sql query
    * @param (name) fullname
    */
    public function search_courses($name) {
        // Get courses
        $company_courses_list = $this->get_company_courses();
        $company_courses_sql = ($company_courses_list) ? " AND {learningpath_courses}.courseid IN ({$company_courses_list})" : "";

        $sql = "SELECT {learningpath_courses}.id id
                FROM {course}
                INNER JOIN {learningpath_courses} ON {learningpath_courses}.courseid = {course}.id
                WHERE {learningpath_courses}.learningpathid = ? {$company_courses_sql}
                AND {course}.fullname LIKE '%?%'
                ORDER BY {learningpath_courses}.position";
        $courses = $this->db->get_records_sql($sql, [$this->id, $name]);

        // Add course objects to learningpath object
        $return = [];
        foreach ($courses as $course) {
            $return = new LearningPathCourse($course->id);
        }
    }

    /**
    * load available cohorts list to add in the learning path
    */
   private function load_available_cohorts_list($companyid = null) {
    global $DB;

    $cohorts = [];

    // Get already assigned cohorts
    foreach ($this->cohorts as $cohort) {
        if (!empty($cohort->data->cohortid)) {
            $cohorts[] = $cohort->data->cohortid;
        }
    }

    $params = [];
    $where = "WHERE c.visible = 1";

    // Exclude already added cohorts
    if (!empty($cohorts)) {
        list($in_sql, $in_params) = $DB->get_in_or_equal($cohorts, SQL_PARAMS_NAMED, 'coh');
        $where .= " AND c.id NOT $in_sql";
        $params = array_merge($params, $in_params);
    }

    // ✅ REMOVE company dependency safely
    // Only apply if table exists (optional advanced fix)
    if ($companyid && $DB->get_manager()->table_exists('company_cohorts')) {
        $where .= " AND c.id IN (
            SELECT cohortid FROM {company_cohorts} WHERE companyid = :companyid
        )";
        $params['companyid'] = $companyid;
    }

    $sql = "SELECT c.id, c.name FROM {cohort} c $where";

    $this->data->available_cohorts = $DB->get_records_sql($sql, $params);
}

    /**
    * This function must be used as way to save all forms data
    * @param (form) form name that must be checked and saved
    */
    public function check_forms_submit($form) {
        global $CFG;
        // Create form object for saving. This depend of $form value
        switch ($form) {
            // Adding new courses
            case 'AddCoursesForm':
                $mform = new AddCoursesForm(null, ['courses' => $this->data->available_courses, 'learningpath' => $this->id]);
                $activetab = "&tab=courses";
                
                // If get post data
                if ($data = $mform->get_data()) {
                    $courses = optional_param('courses', [], PARAM_RAW);
                    $saved = [];

                    // Save relations between course and learning path
                    $i=1;
                    foreach ($courses as $course) {
                        if ((int)$course > 0) {
                            // Create learningpath record
                            $record = new stdClass();
                            $record->learningpathid = $data->learningpathid;
                            $record->courseid = $course;
                            $record->position = count($this->data->courses) + $i;

                            // Create new learningpath and save
                            $learningpathCourse = new LearningPathCourse();
                            $learningpathCourse->save($record);
                        }
                        $i++;
                    }
                    redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$this->id}{$activetab}", get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
                }
                break;

            // Manage courses order
            case 'ManageCoursesPositionForm':
                $mform = new ManageCoursesPositionForm(null, ['learningpath' => $this->id]);
                $activetab = "&tab=courses";
                
                // If moodle form get post data
                if ($data = $mform->get_data()) {
                    $this->save_courses_positions(explode(",", $data->coursesposition));
                }
                redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$this->id}{$activetab}", get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
                break;

            // Manage course settings like pre-requisites and completion
            case 'ManageCoursesForm':
                $mform = new ManageCoursesForm();
                $activetab = "&tab=courses";

                // If data is found, it will remove all pre-requisites and add new
                if ($data = $mform->get_data()) {
                    $courses = optional_param('courses', [], PARAM_RAW);
                    $course = new LearningPathCourse($data->courseid);
                    $course->remove_all_prerequisites();
                    $course->add_prerequisites($courses);

                    // Update required field
                    $required = (optional_param('completion', '', PARAM_RAW) == "1") ? "1" : "0";
                    $course->update_required($required);

                    redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$this->id}{$activetab}", get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
                }
                break;

            // Adding new users.
            case 'ManageUsersForm':
                $mform = new ManageUsersForm(null, ['users' => $this->available_users, 'learningpath' => $this->id]);
                $activetab = "&tab=users";
                if ($data = $mform->get_data()) {
                    // Get learningpath enrollment date
                    $date = date('Y-m-d');
                    $enrollment_date = strtotime($date);
                    
                    foreach (optional_param('users', [], PARAM_RAW) as $userid) {
                        if ((int) $userid > 0) {
                            // Create user record object
                            $this->setUserLearningPath(['learningpathid' => $this->id, 'userid' => $userid, 'enrollment_date' => $enrollment_date]);
                        }
                    }
                    if(empty(optional_param('users', [], PARAM_RAW))) {
                        redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$this->id}{$activetab}", '', null, '');
                    }
                    redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$this->id}{$activetab}", get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
                }
                break;

            // Adding new cohorts
            case 'ManageCohortsForm':
                $mform = new ManageCohortsForm(null, ['cohorts' => $this->available_cohorts, 'learningpath' => $this->id]);
                $activetab = "&tab=cohorts";
                if ($data = $mform->get_data()) {
                    $date = date('Y-m-d');
                    $enrollment_date = strtotime($date);

                    foreach (optional_param('cohorts', [], PARAM_RAW) as $cohortid) {
                        if ((int) $cohortid > 0) {
                            $record = new stdClass();
                            $record->learningpathid = $this->id;
                            $record->cohortid = $cohortid;
                            $record->enrollment_date = $enrollment_date;

                            // Create Learningpath cohort object and save it
                            $learningpathCohort = new LearningPathCohort();
                            $learningpathCohort->save($record);
                        }
                    }
                    redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$this->id}{$activetab}", get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
                }
                break;

            // Creating or editing a learning path
            case 'LearningPathForm':
                // Learning Path Object definition
                if (isset($this->id)) {
                    $mform = new LearningPathForm(null, ['id' => $this->id]);
                } else {
                    $mform = new LearningPathForm();
                }

                //action cancel
                if ($mform->is_cancelled()) {
                    redirect(new moodle_url("$CFG->wwwroot/local/learningpaths/"));
                }
                 
                // Save learning path. This include creating and editing
                if ($data = $mform->get_data()) {

                    $data->name = htmlspecialchars($data->name);
                    if ($this->save($mform)) {
                        redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$this->id}", get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
                    } else {
                        throw new Exception("Error Processing Request", 1);
                    }

                } else {
                    // In case form can't be saves, because has any required field missing, then open the popup
                    $CFG->additionalhtmlfooter .= "
                        <script>
                            $(document).on('ready', function(){
                                $('.add-learning-path').click();
                            })
                        </script>
                    ";
                }

                break;
        }
    }

    /**
    * Save learning path general data like name, description and dates
    * @param (mform) learning path form instance
    */
    private function save($mform) {

        // Get form data
        $data = $mform->get_data();
        //print_r($data);
        //die;
            
        // Get credits
        $credits = intval($data->credits);
       $self_enrollment = isset($data->self_enrollment) ? intval($data->self_enrollment) : 0;
        // Build object with data to save. This is necessary because $data could has more fields that which are required for a learning path.
        $record = new stdClass();
        /*
        * @author VaibhavG.
        * @since 22 Dec 2020
        * @desc VAPT Prevention. Applied funstion s()
        */
        $record->name = $data->name;
        /*
        * @author VaibhavG.
        * @since 29th Jan 2021
        * @desc #492 VAPT Fixes. 
        * VAPT Prevention. Applied funstion s() to $data->description's text
        */
        $record->description = json_encode($data->description);
        $record->startdate = (isset($data->lp_startdate) && $data->enable_startdate == 1) ? strtotime($data->lp_startdate) : null;
        $record->enddate = (isset($data->lp_enddate) && $data->enable_enddate == 1) ? strtotime($data->lp_enddate) : null;
        $record->credits = (isset($data->credits) && is_integer($credits)) ? $credits : 0;
        $record->self_enrollment = (isset($data->self_enrollment) && is_integer($self_enrollment)) ? $self_enrollment : 0;
            
        // If exist id them update record, if doesn't exist insert it
        if (isset($data->id) && $data->id > 0) {
            $record->id = $data->id;
            $result = $this->db->update_record('learningpaths', $record, false);
        } else {
            if ($company = lms_get_current_editing_company()) {
                $record->companyid = $company->id;
            }
            
            $record->id = $this->id = $data->id = $this->db->insert_record('learningpaths', $record);
            $result = (isset($this->id) && !is_null($this->id)) ? true : false;
        }

        // Save learningpath image if exist
        if (isset($data->learningpath_image) && !empty($data->learningpath_image)) {
            // Deleting old images relation
            $this->db->delete_records('learningpath_image', ['learningpathid' => $this->id]);

            // Creating image record
            $image = new stdClass();
            $image->learningpathid = $record->id;
            $image->id = $this->db->insert_record('learningpath_image', $image);

            // Save learning path image
            $file = $mform->save_stored_file('learningpath_image', 1, 'local_learningpaths', 'image', $image->id, '/', null, true);
        }

        
        //save lp properties
        lp_profile_save_data($data);

        // Load learning path data using database record
        $this->load_learningpath_data($record);
        return $result;
    }

    /**
    * Save courses positions using the positions array
    * @param (positions) array with new courses order
    */
    public function save_courses_positions($positions,$page) {
        $page_no = $page.intval(0);
        $i=$page_no;
        $reposition = array();
        foreach ($positions as $row) {
            $reposition[$i]=$row;
            $i++;
        }
        
        foreach ($this->data->courses as $key => $course) {
            $index = array_search($course->data->id, $reposition);
            if ($index !== false)
                $course->set_position($index + 1);
        }
        $this->load_courses();
    }

    /**
    * This function will execute all pending course enrollments and learningpath completions
    * For that will check the users list and courses prerequisites
    */
    public function process_users($users = false) {
        global $DB;
        // Getting users and courses in separate variables, just to can use these objects easily
        $users = (!$users) ? $this->get_all_users() : $users;
        $courses = $this->data->courses;

        // Get the date in case needs for learning path completion
        $date = new DateTime();
        $date = $date->getTimestamp();
        $fieldcredits = $DB->get_record('course_info_field',array('shortname'=>'credits'));
        foreach ($users as $user) {
            $required_completed = 0;
            $credits = 0;
            $lpcreditval = '';
            
            // Iterate through users and enroll user in courses where he isn't enrolled and complete the prerequisites
            foreach ($courses as $course) {
                //continue if course is not active added by ShivkumarY
                if(empty($course->data->course_active)){continue;}
                // Enrol user in course if he has completed the requirements
                if (get_class($user) == 'LearningPathUser') {
                    if (!$course->is_enrolled($user->data->id) && $course->validate_prerequisites($user->data)) {
                        $user->data->type = "learningpath_user";
                        $user->data->user_relationid = $user->data->id;
                        $result = $course->enrol_user($user->data);
                    }
                } else {
                    if (!$course->is_enrolled($user->user_relationid, 'learningpath_cohort') && $course->validate_prerequisites($user)) {
                        $user->type = "learningpath_cohort";
                        $result = $course->enrol_user($user);
                    }
                }

                // If current user is required for learningpath completion
                if ($course->data->required) {
                    $required_completed += $course->is_completed($user->data);
                }
                if($course->is_completed($user->data)) {
                    $creditsvalue = $DB->get_record('course_info_data',array('courseid'=>$course->data->courseid ,'fieldid'=>$fieldcredits->id));
                 }
                 if($creditsvalue == null || empty($creditsvalue->data)) {
                    $credits = 0; 
                } else {
                    $credits += $creditsvalue->data;
                }
            }
            $lpcredit = $DB->get_record('learningpaths', ['id' => $this->id]);
            //Get user id, to get the completion
            $userid = (get_class($user) == 'LearningPathUser') ? $user->data->userid : $user->userid;

            // Has user the learningpath completed?
            $completed = $DB->get_record('learningpath_completion', ['userid' => $userid,'learningpathid'=>$this->id]);
            if($completed){
                $total_courses = $DB->count_records_sql('select count(*) as total from {learningpath_courses} where learningpathid=?',[$this->id]); 
                $active_courses = $DB->count_records_sql('select count(*) as total_active from {learningpath_courses} where learningpathid=? AND course_active = 1',[$this->id]); 
                if($total_courses->total != $active_courses->total_active){
                    $DB->delete_records('learningpath_completion',['id'=>$completed->id]);
                }
            }
            else if ($lpcredit->credits) {
                $lpcreditval = $lpcredit->credits;
                if ($credits >= $lpcreditval) {
                    $record = new stdClass();
                    $record->learningpathid = $this->id;
                    $record->userid = $userid;
                    $record->completion_date = $date;
                    $completion = $DB->insert_record('learningpath_completion', $record);
                    mtrace("user {$user->data->id} has completed the learning path");
                }
            }
            else if (!$completed) {
                // If all required courses are completed, then complete the learning path
                if ($required_completed == $this->data->total_courses_required) {
                    $record = new stdClass();
                    $record->learningpathid = $this->id;
                    $record->userid = $userid;
                    $record->completion_date = $date;
                    $completion = $DB->insert_record('learningpath_completion', $record);
                    mtrace("user {$user->data->id} has completed the learning path");
                }
            }
        }
    }

    /**
    * This function will execute all pending course enrollments.
    * For that will check the cohorts list and call enroll users with each cohort user
    */
    public function enroll_cohorts() {
        foreach ($this->cohorts as $cohort) {
            $this->process_users($cohort->get_users());
        }
    }

    /**
    * Delete learning path.
    * In this case it's a logical deletion to can recover the learning path in case it be done by a human error
    * Recovery just can be done directly from database for a developer
    * Learning path doesn't work when deleted = 1
    */
    public function delete() {
        global $DB;
        foreach ($this->get_all_users() as $user) {
            $user->delete();
        }

         /**
         * check if LP has cohorts, unenrol cohort's users too from LP
         * @author Manisha M
         * @since 09-12-2019
         * @paradiso
        */
        $lp_cohorts = $DB->get_records('learningpath_cohorts', ['learningpathid' => $this->id]);
        if(isset($lp_cohorts) && !empty($lp_cohorts)){
            foreach($lp_cohorts AS $cohort){
                $cohorts = new LearningPathCohort();
                $cohorts->delete($cohort->cohortid);
            }
        }
       
        $this->proccess_learningpaths();
        return $this->db->set_field("learningpaths", 'deleted', 1, ['id' => $this->id]);
    }

   public function proccess_learningpaths() {
    global $DB;

    $learningpaths = $DB->get_records_sql(
        'SELECT id FROM {learningpaths} WHERE deleted = ?', 
        [0]
    );

    if (!is_array($learningpaths)) {
        $learningpaths = [];
    }

    foreach ($learningpaths as $learningpathdb) {
        $_instance = new self($learningpathdb->id);

        // ✅ SAFE: ensure courses is array
        $courses = $_instance->data->courses ?? [];
        $users   = $_instance->data->users ?? [];
        $cohorts = $_instance->data->cohorts ?? [];

        if (is_countable($courses) && count($courses) > 0) {

            // ✅ users
            if (is_countable($users) && count($users) > 0) {
                $_instance->process_users($_instance->get_all_users_not_completed());
            }

            // ✅ cohorts
            if (is_countable($cohorts) && count($cohorts) > 0) {
                $_instance->enroll_cohorts();
            }
        }
    }
}

    /*
    * Call form to new/edit a learning path
    * This function print form, doesn't return the html
    */
    public function render_form() {
        $data = (isset($this->data)) ? (array) $this->data : [];

        //load LP properties
        $lpobject = (object)$data ;

        $data = (array)$lpobject ;

        $mform = new LearningPathForm(null, $data);
        $form_data = [];
        $form_data['learningpath_image'] = load_image_to_draft(isset($data['image'])?$data['image']:'');
       
        $mform->set_data($form_data);
        
        return $mform->render();
    }

    /*
    * Call renderer to get learning path general info html
    */
    public function render_general_info() {
        return $this->renderer->general_info($this->data);
    }

    /*
    * Call renderer to get learning path courses list html
    */
    public function render_courses_list() {
        $this->load_courses();
        return $this->renderer->courses_list($this->courses, $this->id);
    }

    /*
    * Call renderer to get learning path courses form with available courses to add
    */
    public function render_courses_form() {
        $this->load_available_course_list();
        return $this->renderer->add_courses_form($this->data);
    }

    /*
    * Call renderer to get learning path courses html
    */
    public function render_users_list() {
        $this->load_users();
        return $this->renderer->users_list($this->users, $this->id);
    }

    /*
    * Call renderer to get navigation tabs html
    */
    public function render_navigation_tabs() {
        return ($this->id) ? $this->renderer->navigation_tabs() : "";
    }

    /*
    * Call renderer to generate tabs html
    */
    public function render_tabs() {
        if ($this->id) {
            return $this->renderer->tabs($this->data);
        } else {
            $mform = new LearningPathForm();
            return  $mform->render($this->data);
        }
    }

    /**
    * Get learningpath image object
    */
    static function get_learningpath_image_object($id) {
        // Get learningpath image itemid
        $itemid = LearningPath::get_image_itemid($id);
        if (!$itemid)
            return false;

        // Load file library
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        // Get file storage service and files
        $fs = get_file_storage();
        $files = $fs->get_area_files(1, 'local_learningpaths', 'image', $itemid, 'id DESC', 0);
        $final_file = false;
   
        // Return file if it's a valid image else return false
        foreach ($files as $file) {
            if ($file->is_valid_image())
                return $file;
        }
        return false;
    }

    /**
    * Get image itemid
    */
    static function get_image_itemid($id) {
        global $DB;
        $itemid = $DB->get_record('learningpath_image', ['learningpathid' => $id]);
        if ($itemid) {
            return $itemid->id;
        }
        return false;
    }
    /**
     * Enroll an user to a specific Learning Path and it's courses
     * @author Daniel Carmona <daniel.carmona@paradisosolutions.com>
     * @param array $data with the following required keys: learningpathid, userid. Optional key: 'enrollment_date' with the default value of the current timestamp
     * @return bool Response of the proccess
     */
    public function setUserLearningPath(array $data = []) {
        global $DB, $CFG;
        if(array_key_exists('learningpathid', $data) && (int)$data['learningpathid'] > 0 && array_key_exists('userid', $data) && (int)$data['userid'] > 0){
            $record = new stdClass();
            $record->learningpathid = (int)$data['learningpathid'];
            $record->userid = (int)$data['userid'];
            if(!empty($data['enrollment_date'])){
                $date = $data['enrollment_date'];
            }else{
                $date = strtotime(date('Y-m-d'));
            }
            $record->enrollment_date = $date;

            // Create Learningpath user object and save it
            $learningpathUser = new LearningPathUser();
            if($res = $learningpathUser->save($record)){
                /*Enroll the lp courses*/
                $user = new LearningPathUser($res);
                $objLP = new LearningPath($record->learningpathid);
                $courses = $objLP->data->courses;
                $flag = true;
                if (count($courses) > 0){
                    foreach ($courses as $course) {
                        //continue if course is not active added by ShivkumarY
                        if(empty($course->data->course_active)){continue;}
                        if ($user && !$course->is_enrolled($user->data->id) && $course->validate_prerequisites($user->data)) {
                            $user->data->type = "learningpath_user";
                            $user->data->user_relationid = $user->data->id;
                            $flag = $course->enrol_user($user->data);
                            if(!$flag){
                                $res = 0;
                                return $res;
                            }
                        }
                    }
                }
            }
            return $res;
        }
        return false;
    }
    
    /*
     * Function to know if a specific user is enrolled to a learning path by a cohort
     * @author: Daniel Carmona <daniel.carmona@paradisosolutions.com
     * @date: 12-03-2018
     * @paradiso
     */
    public static function isUserInCohort($uid = null, $learningpathid = null) {
        global $DB;
        if (!is_null($uid) && !empty($uid) && (int)$uid > 0 && !is_null($learningpathid) && !empty($learningpathid) && (int)$learningpathid > 0) {
            $sql = "SELECT lpc.id id
                FROM {learningpath_cohorts} lpc
                INNER JOIN {cohort_members} cm ON cm.cohortid = lpc.cohortid
                WHERE lpc.learningpathid = ? AND cm.userid = ?";
                $limit = "LIMIT 1";
            $learningpath = $DB->get_records_sql($sql, [$learningpathid,$uid],$limit);
            if(empty($learningpath)){
                return false;
            }else{
                return true;
            }
        }
        return false;
    }
}