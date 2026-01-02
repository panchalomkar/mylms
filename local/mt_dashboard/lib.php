<?php
/**
* Return the active tenant logo url 
* 
* @author Sandeep B
* @since 26-02-2019
* @paradiso
*/
function get_tenant_logo_url( $company_id  ){
    global $SESSION, $CFG,$DB;
    if( 0 == $company_id || empty($company_id) )
        return $CFG->wwwroot.'/local/mt_dashboard/pix/company_logo.png';
    $tenant_logo = 'tenant_logo_'.$company_id; 
    if( $company_id != false ){
        $theme = \theme_config::load('remui');
       // $tenant_logo_url = $theme->setting_file_serve( $tenant_logo, $tenant_logo );
        $tenant_logo_url = \theme_remui\toolbox::setting_file_url($tenant_logo, $tenant_logo);
        if (!empty($tenant_logo_url)) {
            return $tenant_logo_url;
        }else{
            return $CFG->wwwroot.'/local/mt_dashboard/pix/company_logo.png';
        }
    }
    return false;
}
/**
* Return the tenant count 
* @author Bhagyavant S Panhalkar
* @since 17-july-2019
* @paradiso
*/
function get_tenant_count($showsuspended=false){
    global $SESSION , $CFG , $DB;
    if($showsuspended == 1){
            $total_tenants = $DB->count_records('company');
        }else{
            $total_tenants = $DB->count_records('company', array('suspended' => 0), 'name', '*');
        }
    if(iomad::is_company_admin()){
      $id = iomad::is_company_user();
      $total_tenants = $DB->count_records('company', array('suspended' => 0,'id'=>$id), 'name', '*');
    }
    return $total_tenants;
}

/**
* Return all compines with pagination and search  
* @author Bhagyavant S Panhalkar
* @since 17-july-2019
* @paradiso
*/
function get_all_companies($showsuspended = 0 , $startfrom=0, $record_per_page=0 , $search = '', $add_limit = 1 ){
    global $DB, $USER;

    $limit = $add_limit ? "ORDER BY name ASC LIMIT $startfrom, $record_per_page" : "ORDER BY name ASC";
    $where_clauses = [];
    $params = [];

    // Suspended filter
    if ($showsuspended === 0) {           // Active only
        $where_clauses[] = "suspended = 0";
    } elseif ($showsuspended === 1) {     // Suspended only
        $where_clauses[] = "suspended = 1";
    } // else 2 = All companies, no filter

    // Search
    if (!empty($search)) {
        $where_clauses[] = "name LIKE :search";
        $params['search'] = "%$search%";
    }

    // Company admin access
    $compantId = [];
    $sqlList = $DB->get_records('company_users', ['userid' => $USER->id, 'managertype' => 1]);
    foreach ($sqlList as $companyList) {
        $compantId[] = $companyList->companyid;
    }

    if (!iomad::has_capability('block/iomad_company_admin:company_add', context_system::instance()) && !empty($compantId)) {
        list($ids, $paramsIds) = $DB->get_in_or_equal($compantId, SQL_PARAMS_NAMED, 'cid');
        $where_clauses[] = "id $ids";
        $params = array_merge($params, $paramsIds);
    }

    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
    }

    $companies = $DB->get_records_sql("SELECT * FROM {company} $where_sql $limit", $params);

    $companyselect = [];
    foreach ($companies as $company) {
        $companyselect[$company->id] = $company->name;
        if ($company->suspended) {
            $companyselect[$company->id] .= ' (S)';
        }
    }

    return $companyselect;
}


function get_tenant_courses($companyid){
    global $DB;
    $coursearray = array();
    $companycourses = $DB->get_records_sql("SELECT c.id,c.fullname FROM {company_course} cc LEFT JOIN {course} c ON cc.courseid = c.id WHERE cc.companyid = $companyid");
    foreach($companycourses AS $course){
        $coursearray[$course->id] = $course->fullname;
    }
    return $coursearray;
}
// added by omkar
/**
 * Get company (tenant) course completion rate
 *
 * Completion rate =
 * (Completed user-course enrolments / Total user-course enrolments) * 100
 *
 * @param int $companyid
 * @return int Percentage (0–100)
 */
function get_company_completion_rate(int $companyid): int {
    global $DB;

    $sql = "
        SELECT
            COUNT(DISTINCT CONCAT(cu.userid, '-', cc.courseid)) AS total,

            COUNT(DISTINCT
                CASE
                    WHEN gg.finalgrade IS NOT NULL
                     AND (
                          gi.gradepass IS NULL
                          OR gi.gradepass = 0
                          OR gg.finalgrade >= gi.gradepass
                     )
                    THEN CONCAT(cu.userid, '-', cc.courseid)
                END
            ) AS completed

        FROM {company_users} cu
        JOIN {company_course} cc
             ON cc.companyid = cu.companyid

        JOIN {user_enrolments} ue
             ON ue.userid = cu.userid

        JOIN {enrol} e
             ON e.id = ue.enrolid
            AND e.courseid = cc.courseid

        JOIN {grade_items} gi
             ON gi.courseid = cc.courseid
            AND gi.itemtype = 'course'

        LEFT JOIN {grade_grades} gg
             ON gg.itemid = gi.id
            AND gg.userid = cu.userid

        WHERE cu.companyid = :companyid
    ";

    $record = $DB->get_record_sql($sql, ['companyid' => $companyid]);

    if (!$record || (int)$record->total === 0) {
        return 0;
    }

    return (int) round(($record->completed / $record->total) * 100);
}
function get_company_creator_name(int $companyid): string {
    global $DB;

    if (empty($companyid)) {
        return 'Unknown';
    }

    // 1️⃣ Try: company admin (ANY managertype, earliest record)
    $user = $DB->get_record_sql("
        SELECT u.firstname, u.lastname
        FROM {company_users} cu
        JOIN {user} u ON u.id = cu.userid
        WHERE cu.companyid = :companyid
          AND u.deleted = 0
        ORDER BY cu.id ASC
    ", ['companyid' => $companyid]);

    if ($user) {
        return fullname($user);
    }

    // 2️⃣ Try: createdby column (if exists)
    if ($DB->get_manager()->field_exists('company', 'createdby')) {
        $creatorid = $DB->get_field('company', 'createdby', ['id' => $companyid]);
        if ($creatorid) {
            $u = $DB->get_record('user', ['id' => $creatorid], 'firstname, lastname');
            if ($u) {
                return fullname($u);
            }
        }
    }

    return 'Unknown';
}
function get_company_user_stats(int $companyid): array {
    global $DB;

    if (!$companyid) {
        return [];
    }

    $total = $DB->count_records_sql("
        SELECT COUNT(DISTINCT cu.userid)
        FROM {company_users} cu
        JOIN {user} u ON u.id = cu.userid
        WHERE cu.companyid = :cid AND u.deleted = 0
    ", ['cid' => $companyid]);

    $active = $DB->count_records_sql("
        SELECT COUNT(DISTINCT cu.userid)
        FROM {company_users} cu
        JOIN {user} u ON u.id = cu.userid
        WHERE cu.companyid = :cid AND u.suspended = 0 AND u.deleted = 0
    ", ['cid' => $companyid]);

    $suspended = $DB->count_records_sql("
        SELECT COUNT(DISTINCT cu.userid)
        FROM {company_users} cu
        JOIN {user} u ON u.id = cu.userid
        WHERE cu.companyid = :cid AND u.suspended = 1
    ", ['cid' => $companyid]);

    $inactive = $DB->count_records_sql("
        SELECT COUNT(DISTINCT cu.userid)
        FROM {company_users} cu
        JOIN {user} u ON u.id = cu.userid
        WHERE cu.companyid = :cid AND u.lastaccess = 0
    ", ['cid' => $companyid]);

    return [
        ['icon'=>'groups',        'label'=>'Total Users',     'value'=>$total,     'bg'=>'bg-blue-card',   'iconbg'=>'icon-blue'],
        ['icon'=>'person_check',  'label'=>'Active Users',    'value'=>$active,    'bg'=>'bg-sky-card',  'iconbg'=>'icon-sky'],
        ['icon'=>'person_cancel',  'label'=>'Inactive Users',  'value'=>$inactive,  'bg'=>'bg-teal-card', 'iconbg'=>'icon-teal'],
        ['icon'=>'shield',         'label'=>'Suspended Users', 'value'=>$suspended, 'bg'=>'bg-indigo-card',    'iconbg'=>'icon-indigo'],
    ];
}

/**
 * Company overview dashboard stats (Tab 1)
 *
 * @param int $companyid
 * @return array
 */
function get_company_overview_stats(int $companyid): array {
    global $DB;

    if (empty($companyid)) {
        return [];
    }

    // Active users
    $userscount = $DB->count_records_sql("
        SELECT COUNT(DISTINCT cu.userid)
        FROM {company_users} cu
        JOIN {user} u ON u.id = cu.userid
        WHERE cu.companyid = :companyid
          AND u.deleted = 0
          AND u.suspended = 0
    ", ['companyid' => $companyid]);

    // Total courses
    $coursecount = $DB->count_records_sql("
        SELECT COUNT(DISTINCT cc.courseid)
        FROM {company_course} cc
        WHERE cc.companyid = :companyid
    ", ['companyid' => $companyid]);

    // Completion rate (your existing logic)
    $completionrate = get_company_completion_rate($companyid);

    // Certifications (optional / safe)
    $certcount = 0;
    if ($DB->get_manager()->table_exists('certificate_issues')) {
        $certcount = $DB->count_records_sql("
            SELECT COUNT(ci.id)
            FROM {certificate_issues} ci
            JOIN {company_users} cu ON cu.userid = ci.userid
            WHERE cu.companyid = :companyid
        ", ['companyid' => $companyid]);
    }

    return [
        [
            'icon'   => 'groups',
            'label'  => 'Active Users',
            'value'  => $userscount,
            'bg'     => 'bg-blue-card',
            'iconbg' => 'icon-blue'
        ],
        [
            'icon'   => 'menu_book',
            'label'  => 'Total courses',
            'value'  => $coursecount,
            'bg'     => 'bg-sky-card',
            'iconbg' => 'icon-sky'
        ],
        [
            'icon'   => 'bar_chart',
            'label'  => 'Completion rate',
            'value'  => $completionrate . '%',
            'bg'     => 'bg-teal-card',
            'iconbg' => 'icon-teal'
        ],
        [
            'icon'   => 'workspace_premium',
            'label'  => 'Certifications',
            'value'  => $certcount,
            'bg'     => 'bg-indigo-card',
            'iconbg' => 'icon-indigo'
        ]
    ];
}

/**
 * Get company (tenant) course stats
 *
 * @param int $companyid
 * @return array
 */
function get_company_course_stats(int $companyid): array {
    global $DB;

    if (empty($companyid)) {
        return [];
    }

    // 1. Total Courses
    $totalcourses = $DB->count_records_sql("
        SELECT COUNT(DISTINCT cc.courseid)
        FROM {company_course} cc
        WHERE cc.companyid = :companyid
    ", ['companyid' => $companyid]);

    // 2. Total Enrollments (unique users enrolled in company courses)
    $totalenrollments = $DB->count_records_sql("
        SELECT COUNT(DISTINCT ue.userid)
        FROM {user_enrolments} ue
        JOIN {enrol} e ON e.id = ue.enrolid
        JOIN {company_course} cc ON cc.courseid = e.courseid
        WHERE cc.companyid = :companyid
    ", ['companyid' => $companyid]);

    // 3. Active Groups (groups with members in company courses)
    $activegroups = $DB->count_records_sql("
        SELECT COUNT(DISTINCT g.id)
        FROM {groups} g
        JOIN {groups_members} gm ON gm.groupid = g.id
        JOIN {company_course} cc ON cc.courseid = g.courseid
        WHERE cc.companyid = :companyid
    ", ['companyid' => $companyid]);

    return [
        [
            'icon'   => 'menu_book',
            'label'  => 'Total Courses',
            'value'  => $totalcourses,
            'bg'     => 'bg-sky-card',
            'iconbg' => 'icon-sky'
        ],
        [
            'icon'   => 'how_to_reg',
            'label'  => 'Total Enrollments',
            'value'  => $totalenrollments,
            'bg'     => 'bg-teal-card',
            'iconbg' => 'icon-teal'
        ],
        [
            'icon'   => 'groups',
            'label'  => 'Active Groups',
            'value'  => $activegroups,
            'bg'     => 'bg-blue-card',
            'iconbg' => 'icon-blue'
            
        ]
    ];
}

