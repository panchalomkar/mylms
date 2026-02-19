<?php


defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../config.php');
use core_course_category;

//use course_in_list;
use core_course_list_element;


class report_overviewstats {

    /** @var stdClass if this is course level report, holds the course record */
    public $courseid = null;

    /**
     * Constructor. 
     */
    public function __construct() {
        $courseid = optional_param('course', null, PARAM_INT);
    }

    /**
     * Get Per day data report
     */
    public function get_per_day_data() {
        global $DB, $CFG;
        $now = strtotime('today midnight');

        $lastmonth = array();
        for ($i = 30; $i >= 0; $i--) {
            $lastmonth[$now - $i * DAYSECS] = array();
        }

        $sql = "SELECT timecreated, userid
                  FROM {logstore_standard_log}
                 WHERE timecreated >= :timestart
                   AND userid <> :guestid
                   AND action = 'loggedin'";

        $params = array('timestart' => $now - 30 * DAYSECS, 'guestid' => $CFG->siteguest);

        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $record) {
            foreach (array_reverse($lastmonth, true) as $timestamp => $loggedin) {
                $date = usergetdate($timestamp);
                if ($record->timecreated >= $timestamp) {
                    $lastmonth[$timestamp][$record->userid] = true;
                    break;
                }
            }
        }
        $rs->close();

        $data = array(
            'perday' => array(),
        );

        $format = get_string('strftimedateshort', 'core_langconfig');

        foreach ($lastmonth as $timestamp => $loggedin) {
            $date = userdate($timestamp, $format);
            $data['perday'][] = array('date' => $date, 'loggedin' => count($loggedin));
        }

    }

    /**
     * Get countries data report.
     */
    public function get_countries_data() {
        global $DB;

        $sql = "SELECT country, COUNT(*)
                  FROM {user}
                 WHERE country IS NOT NULL AND country <> '' AND deleted = 0 AND confirmed = 1
              GROUP BY country
              ORDER BY COUNT(*) DESC, country ASC";

        $data = array();
        foreach ($DB->get_records_sql_menu($sql) as $country => $count) {
            if (get_string_manager()->string_exists($country, 'core_countries')) {
                $countryname = get_string($country, 'core_countries');
            } else {
                $countryname = $country;
            }
            $data[] = array(
                'country' => $countryname,
                'count' => $count
            );
        }

        return $data;
    }

    /**
     * Get user preferred languages data report
     */
    public function get_lang_data() {
        global $DB;

        $sql = "SELECT lang, COUNT(*)
                  FROM {user}
                 WHERE deleted = 0 AND confirmed = 1
              GROUP BY lang
              ORDER BY COUNT(*) DESC";

        $data = array();
        foreach ($DB->get_records_sql_menu($sql) as $lang => $count) {
            if (get_string_manager()->translation_exists($lang)) {
                $langname = get_string_manager()->get_string('thislanguageint', 'core_langconfig', null, $lang);
            } else {
                $langname = $lang;
            }
            $data[] = array(
                'language' => $langname,
                'count' => $count
            );
        }

        return $data;
    }

    /**
     * Get Number of courses per category
     */
    public function get_coursescategory_data() {
        global $DB;

        // Number of courses per category

        $cats = core_course_category::make_categories_list();
        $arr['percategory'] = array();
        $total = 0;

        foreach ($cats as $catid => $catname) {
            $cat = core_course_category::get($catid);
            $coursesown = $cat->get_courses_count();
            $total += $coursesown;
            $arr['percategory'][] = array(
                'categoryname' => $catname,
                'coursesrecursive' => $cat->get_courses_count(array('recursive' => true)),
                'coursesown' => $coursesown,
            );
        }

        $arr['percategory'][] = array(
            'categoryname' => html_writer::tag('strong', get_string('total')),
            'coursesrecursive' => '',
            'coursesown' => html_writer::tag('strong', $total),
        );

        // Distribution graph of number of activities per course

        $sql = "SELECT course, COUNT(id) AS modules
                  FROM {course_modules}
              GROUP BY course";

        $rs = $DB->get_recordset_sql($sql);

        $max = 0;
        $data = array();
        $arr['sizes'] = array();

        foreach ($rs as $record) {
            $distributiongroup = floor($record->modules / 5); // 0 for 0-4, 1 for 5-9, 2 for 10-14 etc.
            if (!isset($data[$distributiongroup])) {
                $data[$distributiongroup] = 1;
            } else {
                $data[$distributiongroup] ++;
            }
            if ($distributiongroup > $max) {
                $max = $distributiongroup;
            }
        }

        $rs->close();

        for ($i = 0; $i <= $max; $i++) {
            if (!isset($data[$i])) {
                $data[$i] = 0;
            }
        }
        ksort($data);

        foreach ($data as $distributiongroup => $courses) {
            $distributiongroupname = sprintf("%d-%d", $distributiongroup * 5, $distributiongroup * 5 + 4);
            $arr['sizes'][] = array(
                'course_size' => $distributiongroupname,
                'courses' => $courses,
            );
        }
        return $arr;
    }

    /**
     * Get Number of total users
     */
    public function get_total_cc() {
        global $DB, $CFG;

       $csql = "SELECT COUNT(cc.id) "
                . " FROM {course_categories} cc ";

        if (!$usercount = $DB->count_records_sql($csql)) {
            $usercount = get_string("none");
        }
       
        return $usercount;
    }
        public function get_total_users() {
        global $DB, $CFG;

        $company =  $this->get_companyid();
        // if company, show total users of company
        if(!empty($company)){
            $csql = "SELECT COUNT(cu.id) "
                . " FROM {company_users} cu "
                . " WHERE  cu.companyid = $company ";
            $usercount = $DB->count_records_sql($csql);
            return $usercount;
        }
        $csql = "SELECT COUNT(u.id) "
                . " FROM {user} u "
                . " WHERE  u.deleted = 0 ";

        if (!$usercount = $DB->count_records_sql($csql)) {
            $usercount = get_string("none");
        }
        $usercount = get_users(false);
        return $usercount;
    }

    /**
     * Get Number of online users
     */
    public function get_online_users() {
        global $DB, $CFG;
        $timetoshowusers = 300; //Seconds default
        $company =  $this->get_companyid();

        if (isset($CFG->block_online_users_timetosee)) {
            $timetoshowusers = $CFG->block_online_users_timetosee * 60;
        }

        $now = time();
        $timefrom = 100 * floor(($now - $timetoshowusers) / 100); // Round to nearest 100 seconds for better query cache
        $join = $whr = '';
        
        // if company, get online users of company
        if($company){
            $join = " LEFT JOIN {company_users} cu ON u.id = cu.userid";
            $whr = " AND cu.companyid = $company";
        }

        $params['now'] = $now;
        $params['timefrom'] = $timefrom;

        $csql = "SELECT COUNT(u.id) "
                . " FROM {user} u "
                .   $join
                . " WHERE u.lastaccess > :timefrom "
                . " AND u.lastaccess <= :now "
                . " AND u.deleted = 0 "
                .   $whr;
 
        if (!$usercount = $DB->count_records_sql($csql, $params)) {
            $usercount = 0;
        }
        return $usercount;
    }

    /**
     * Get Number of users registered today
     */
    public function get_registered_today() {
        global $DB, $CFG;
        $today = date('Y-m-d 00:00:00');
        $today = strtotime($today);

        $params['today'] = $today;
        $company =  $this->get_companyid();
        $join = $whr = '';
         if(!empty($company)){
             $join = " LEFT JOIN {company_users} cu ON u.id = cu.userid";
             $whr  = " AND cu.companyid = $company";
         }
          $csql = "SELECT COUNT(u.id) "
          ." FROM {user} u "
          .  $join
          ." WHERE u.timecreated >= :today "
          .  $whr
          ." AND u.deleted = 0 "; 
        // $csql = "SELECT COUNT(1) "
        //         . " FROM {log} l "
        //         . " WHERE l.time >= :today "
        //         . " AND l.module = 'user' "
        //         . " AND l.action = 'add' ";
        /* END */

        if (!$usercount = $DB->count_records_sql($csql, $params)) {
            $usercount = 0;
        }
        return $usercount;
    }

    /**
     * Get Top 5 courses per viewed
     */
    public function get_top_viewed() {
        global $DB, $CFG;


        $company =  $this->get_companyid();
        $join   = $whr = '';

        if(!empty($company)){
            $join   =  " , {company_course} cc , {company_users} cu ";
            $whr    =  " AND l.courseid = cc.courseid AND cu.userid = l.userid AND cc.companyid= $company AND cu.companyid= $company ";
        }
        
         $csql = "SELECT 
                    COUNT(l.id) AS `Views`,
                    c.shortname 'Course', 
                    c.id AS 'Courseid'
                FROM {logstore_standard_log} l,
                    {course} c,
                    {role_assignments} ra  $join
                WHERE
                    c.visible = 1 AND c.id<>1 
                    AND l.eventname = '\\\core\\\\event\\\course_viewed' 
                    AND l.action = 'viewed' 
                    AND l.target ='course'
                    AND l.courseid = c.id 
                    AND l.userid = ra.userid 
                    AND l.contextid = ra.contextid 
                    AND ra.roleid = 5 
                     $whr
                GROUP BY l.`courseid`
                ORDER BY Views DESC ";
 
        if (!$usercount = $DB->get_records_sql($csql, null, $limitfrom = 0, $limitnum = 5)) {
            $usercount = get_string("none");
        } else {
            $array = array();
            foreach ($usercount as $u) {
                $array[] = $u;
            }
            $usercount = $array;
        }
        return $usercount;
    }

    /**
     * Get Top 5 courses per enrolled
     */
    public function get_top_enrolled() {
        global $DB, $CFG;

        $company =  $this->get_companyid();
        $join   = $whr = '';

        // if company, get company's top enrolled courses
        if(!empty($company)){
            $join   =  " JOIN {company_course} cc ON (cc.courseid=c.id) JOIN {company_users} cu ON (cu.userid = u.id) ";
            $whr    =  " AND cc.companyid= $company AND cu.companyid= $company ";
        }

        $csql = "SELECT "
                . " c.shortname AS 'Course', "
                . " COUNT(ue.id) AS 'Enrolled' "
                . " FROM {course} c  "
                . " JOIN {enrol} en ON (en.courseid = c.id) "
                . " JOIN {user_enrolments} ue ON (ue.enrolid = en.id) "
                . " JOIN {user} u ON (u.id=ue.userid) " 
                .   $join
                . " WHERE "
                . " u.deleted=0 " 
                .   $whr
                . " GROUP BY c.id "
                . " ORDER BY   "
                . "  `Enrolled` DESC";

        if (!$usercount = $DB->get_records_sql($csql, null, $limitfrom = 0, $limitnum = 5)) {
            $usercount = get_string("none");
        } else {
            $array = array();
            foreach ($usercount as $u) {
                $array[] = $u;
            }
            $usercount = $array;
        }
        return $usercount;
    }

    /**
     * Get accordion menu - html
     */
    public function get_accordion_html($menu, $txt = null) {
        global $DB, $CFG, $USER;

        if (empty($txt)) {
            $collapse = '';
        } else {
            $collapse = ' in';
        }

        $sitecontext = context_system::instance();
        $lb_show_system = has_capability('local/lms_reports:show_system_report', $sitecontext);
        $lb_show_learning = has_capability('local/lms_reports:show_learning_report', $sitecontext);

        $html = '';

        foreach ($menu as $m) {

            if (( $m->catid == 5 || preg_match("/^system/", $m->catname) ) && $lb_show_system === false) {
                //ddd( 'hide ', has_capability('local/elisprogram:hide_system_report', $sitecontext) );
                continue;
            }
            if (( $m->catid == 7 || preg_match("/^performance\_management/", $m->catname) ) && $lb_show_learning === false) {
                //ddd( 'hide ', has_capability('local/elisprogram:hide_system_report', $sitecontext) );
                continue;
            }

            $html .= '<div class="panel reports-panel mt-2" id="' . $m->catname . '">'
                    . '<!--Accordion title-->'
                    . '<div class="panel-heading">'
                    . ' <h6 class="panel-title">'
                    . '     <a class="lms-reports-dropdown pl-2" href="#collapse' . $m->catid . '"  data-toggle="collapse" data-parent="#accordion" >'
                    . get_string($m->catname, 'local_lms_reports')
                    . '         <i class="reports-dropdown fa fa-angle-down collapsed"></i>'
                    . '     </a>'
                    . ' </h4>'
                    . '</div> '
                    . '<!--Accordion content-->'
                    .  '<div id="collapse' . $m->catid . '" class="panel-collapse collapse collapse-in' . $collapse . '">'
                    . ' <div class="panel-body">';

            foreach ($m->reports as $r) {
                //print_object($r);
                $reportname = $r->name;

                $reportname = str_replace('[[', '', $reportname);
                $reportname = str_replace(']]', '', $reportname);

                $url = $r->url;
                $editurl = '';
                $deleteurl = '';
                if (!empty($r->idcr)) {
                    $url .= $r->idcr;
                    $ini = '[[';
                    $pini = strpos($reportname, $ini);
                    $end = ']]';
                    $pend = strpos($reportname, $ini);
                    if (($pini !== false) && ($pend !== false)) {
                        $sql = "SELECT name FROM {block_configurable_reports} WHERE id=? ";
                        $exist = $DB->get_record_sql($sql, array($r->idcr));
                        $reportname = $exist->name;
                    }

                }
                //print_object($reportname);
                if ('/' == substr($url, 0, 1)) {
                    $url = "{$CFG->wwwroot}{$url}";
                }
                $context = context_system::instance();
                if ($reportname != '') {
                    $html .= '      <div class="row item-menu" >'
                            . '         <span class="item">'
                            . '             <a href="javascript:void(0)" class="star" alt="' . $r->id . '-' . $r->favorite . '" rel="' . $r->id . '" ><i class="fa fa-star' . ((empty($r->favorite)) ? '-o' : '') . '"></i></a>&nbsp;'
                            . '             <a href="' . $url . '" title="' . $reportname . '" search="' . strtolower($reportname) . '" class="reportname pl-2" >' . $reportname . '</a>';
                    /* if(has_capability('block/configurable_reports:managereports', $context) || (has_capability('block/configurable_reports:manageownreports', $context)) ){
                      $html.= $editurl . $deleteurl;
                      }/* */
                    $html .= '          </span>'
                            . '     </div> ';
                }
            }
            $html .= ' &nbsp'
                    . ' </div>'
                    . '</div>'
                    . '</div>'; /**/
        }
        return $html;
    }

    /**
     * Get menu reports
     */
    public function get_menu_reports($txt = null) {
        global $DB, $CFG, $USER, $SESSION;

        $company =  $this->get_companyid();
 
        $where = $companywhere = '';
        if (!empty($txt)) {
            $where .= ' AND (r.name LIKE "%' . $txt . '%" || rt.name LIKE "%' . $txt . '%")  ';
        }

        $join = '';

        // if company, get company's reports

        $join .= " left join {block_configurable_reports} cr on cr.id = r.idcr ";
            
     
        $sql = 'SELECT DISTINCT rt.`id`, rt.`name` FROM {local_lms_report_type} rt '
                . ' INNER JOIN {local_lms_reports} r ON (rt.id=r.idtype) '
                . ' WHERE rt.state=1 AND r.state=1 ' . $where . ' GROUP BY rt.id ORDER BY rt.`order`,rt.`id`';
        $records = $DB->get_records_sql($sql);


        $sql = 'SELECT r.id, r.name, r.summary, r.url, r.idtype, r.iduser, r.idcr, r.favorite, rt.name as categoria FROM {local_lms_reports} r '
                . ' INNER JOIN {local_lms_report_type} rt ON (r.idtype=rt.id) '
                .  $join 
                . ' WHERE r.state = ? '
                . $where
                . $companywhere
                . ' ORDER BY  r.name ASC, r.`order` ASC ';
        $params = array('1');
 
        $reports = $DB->get_records_sql($sql, $params);

        //exit;
        $options = array();


        $menu = array();
        $url = "";
        $report = array();
        foreach ($reports as $r) {
            if ($r->favorite == 1) {

                $reportname = get_string($r->name, 'local_lms_reports');
                if (!empty($r->idcr)) {
                    $url .= $r->idcr;
                    $ini = '[[';
                    $pini = strpos($reportname, $ini);
                    $end = ']]';
                    $pend = strpos($reportname, $end);
                    if (($pini !== false) && ($pend !== false)) {
                        $sql = "SELECT name FROM {block_configurable_reports} WHERE id=? ";
                        $exist = $DB->get_record_sql($sql, array($r->idcr));
//                        $reportname = $exist->name;
                        $reportname = ( gettype($exist) != 'boolean' ) ? $exist->name : "";
                    }
                }
                $r->name = $reportname;

                $report[] = $r;
            }
        }
        if (!empty($report)) {
            $row = new stdClass();
            $row->catname = 'my_favorites';
            $row->catid = 'f';
            usort($report, array('report_overviewstats', "cmp"));
            $row->reports = $report;
            $menu[] = $row;
        }

        $report = array();
        $url = "";
        foreach ($reports as $r) {
            if ($r->iduser == $USER->id) {

                if (get_string_manager()->string_exists($r->name, 'local_lms_reports')) {
                    $reportname = get_string($r->name, 'local_lms_reports');
                } else {
                    $reportname = $r->name;
                }
                if (!empty($r->idcr)) {
                    $url .= $r->idcr;
                    $ini = '[[';
                    $pini = strpos($reportname, $ini);
                    $end = ']]';
                    $pend = strpos($reportname, $end);
                    if (($pini !== false) && ($pend !== false)) {
                        $sql = "SELECT name FROM {block_configurable_reports} WHERE id=? ";
                        $exist = $DB->get_record_sql($sql, array($r->idcr));
                        $reportname = ( gettype($exist) != 'boolean' ) ? $exist->name : "";
                    }
                }
                $r->name = $reportname;

                $report[] = $r;
            }
        }
        if (!empty($report)) {
            $row = new stdClass();
            $row->catname = 'my_reports';
            $row->catid = 'my';
            usort($report, array('report_overviewstats', "cmp"));
            $row->reports = $report;
            $menu[] = $row;
        }

        foreach ($records as $record) {
            $row = new stdClass();
            $row->catname = $record->name;
            $row->catid = $record->id;
            $report = array();
            foreach ($reports as $r) {
                if ($r->idtype == $record->id && $r->name != "") {
                    try {
                        $reportname = get_string($r->name, 'local_lms_reports');
                    } catch (Exception $e) {
                        $reportname = $r->name;
                        //echo '<br>Caught exception: ['.$r->name.']',  $e->getMessage(), "\n";
                    }

                    if (!empty($r->idcr)) {
                        $url .= $r->idcr;
                        $ini = '[[';
                        $pini = strpos($reportname, $ini);
                        $end = ']]';
                        $pend = strpos($reportname, $end);
                        if (($pini !== false) && ($pend !== false)) {
                            $sql = "SELECT name FROM {block_configurable_reports} WHERE id=? ";
                            $exist = $DB->get_record_sql($sql, array($r->idcr));
                            $reportname = isset($exist->name) ? $exist->name : '';
                        }
                    }
                    $r->name = $reportname;
                    $report[] = $r;
                }
            }
            usort($report, array('report_overviewstats', "cmp"));
            $row->reports = $report;
            $menu[] = $row;
        }

        $row = new stdClass();
        $row->catname = 'all_reports';
        $row->catid = 'a';
        usort($reports, array('report_overviewstats', "cmp"));
        $row->reports = $reports;
        $menu[] = $row;
        return $menu;
    }

    public function cmp($a, $b) {
        return strcmp(trim(strtolower($a->name)), trim(strtolower($b->name)));
    }

    /**
     * Update favorites reports
     */
    public function update_fav($fav, $id) {
        global $DB, $CFG;
        $sql = "UPDATE {local_lms_reports} SET `favorite`=? WHERE `id`=? ";
        $params = array($fav, $id);
        $DB->execute($sql, $params);
        return (true);
    }

    /**
     * Delete report
     */
    public function delete_report($id) {
        global $DB, $CFG;
        $sql = "DELETE FROM {local_lms_reports} WHERE `id`=? ";
        $params = array($id);
        $DB->execute($sql, $params);
        return (true);
    }

    public function check_permissions($userid, $context) {
        global $DB, $CFG, $USER;

        if (has_capability('block/configurable_reports:manageownreports', $context, $userid)) {
            return true;
        } elseif (has_capability('block/configurable_reports:managereports', $context, $userid)) {
            return true;
        } elseif (has_capability('block/configurable_reports:viewreports', $context)) {
            return true;
        } else {
            return false;
        }
    }

    public function get_companyid(){
        global $DB, $CFG, $SESSION;

        $company = 0;
        if(!empty($SESSION->currenteditingcompany)){
            $company    = $SESSION->currenteditingcompany;
        }else if(\iomad::is_company_admin()){
            $company = \iomad::is_company_user();
        }

        return $company;
    }

    // Method to get current company data
    public function get_company(){
        global $DB, $CFG, $SESSION;

        if(!$companyid = $this->get_companyid()){
            $companyid = 0;
        }
        $company = '';
        if(!empty($companyid)){
            $company = $DB->get_record('company', array('id' => $companyid));
        }
         
        return $company;
    }
}
