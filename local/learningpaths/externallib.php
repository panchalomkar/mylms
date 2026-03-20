<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External local_learningpath API
 *
 * @package    local_learningpath
 * @since      2021
 * @copyright  paradiso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/user/externallib.php");

/**
 * Assign functions
 * @copyright paradiso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_learningpath_external extends external_api {

    public static function publishlp($lpid) {
        global $CFG, $USER,$DB;

        // raju code here
        $recordsss = new stdClass();
        $recordsss->id = $lpid;
        $recordsss->publish = 1;
        $publish = $DB->update_record('learningpaths', $recordsss);
          // raju code here end

      $lpdata = $DB->get_records('learningpath_courses', array('learningpathid'=>$lpid)); 
      foreach ($lpdata as $row) {
        $record                   =   new stdClass();
        $record->id   =   $row->id;
        $record->course_active    =   1;
        $publish = $DB->update_record('learningpath_courses', $record);
      }
      $lpname = $DB->get_field('learningpaths','name',['id'=>$lpid]);

      $lpcourses = $DB->get_records_sql("select lc.id, lc.courseid, c.fullname as coursename from {learningpath_courses} lc, {course} c where lc.learningpathid =? AND lc.courseid=c.id AND c.visible=1",[$lpid]);

      foreach ($lpcourses as $key => $value) {
        $sql = "SELECT {learningpath_course_groups}.groupid FROM {learningpath_course_groups} INNER JOIN {groups} ON {groups}.id = {learningpath_course_groups}.groupid WHERE {learningpath_course_groups}.learningpath_courseid = ?";
              if (!$record = $DB->get_record_sql($sql, [$key])) {
                
                  $group = new stdClass();
                  $group->courseid = $value->courseid;
                  $group->name = trim("{$lpname}-{$value->coursename}");
                  $groupid = groups_create_group($group);
                  $DB->insert_record('learningpath_course_groups', ['learningpath_courseid' => $key, 'groupid' => $groupid]);
              }
      }

        $responsedata = array();
        if($publish) {
            $responsedata['message'] = json_encode("success");
        } else {
            $responsedata['message'] = '';
        }
        return $responsedata;
    }

    public static function publishlp_parameters() {
        return new external_function_parameters(
            array(
              'lpid' => new external_value(PARAM_TEXT, 'lp id'),
            )
        );
    }

    public static function publishlp_returns() {
      return new external_single_structure(
            array(
              'message' => new external_value(PARAM_TEXT, 'message: message string'),
            )
      );
    }


  public static function actions($action,$learningpathid,$courseid,$prerequisites,$pageno,$order,$required,$item,$cohorts,$users,$ajax,$sesskey) {
        global $CFG, $USER,$DB;
        require_once("../../config.php");
        
        $users = explode(',', $users);
        $order = explode(',', $order);
        $prerequisites = explode(',', $prerequisites);
        $cohorts = explode(',', $cohorts);

      // Security Validations: Login, capability and sesskey.
      require_login();
      if (!confirm_sesskey()) {
          throw new moodle_exception(get_string('invalidsesskey', 'error'), 'core_plugin');
      }

      // Validate capabilities.
      $learningpathsmanager = has_capability('local/learningpaths:managealllearningpaths', context_system::instance());
      $learningpathscompanymanager = has_capability('local/learningpaths:managecompanylearningpaths', context_system::instance());
      if (!$learningpathsmanager && !$learningpathscompanymanager) {
          throw new moodle_exception(get_string('access_denied'));
      }
      // Require learning paths objects.
      require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPath.php");
      require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPathUser.php");
      require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPathCourse.php");
      require_once("{$CFG->dirroot}/blocks/lpd/lib/lib.php");
      // Actions.
     // print_r($action); 

      //echo 'asdasdasdasd'; die;
      switch ($action) {
          // Adding new courses to lerningpath.
          case 'add-course':
              // Create a new learningpath object where course will be added.
              $learningpathid = $learningpathid;
              $learningpath = new LearningPath($learningpathid);
              // Build course learningpath record.
              $record = new stdClass();
              $record->learningpathid = $learningpathid;
              $record->courseid = $courseid;
              $record->position = count($learningpath->data->courses) + 1;
              // Creating new larningpath course and save.
              $learningpathcourse = new LearningPathCourse();
              if ($learningpathcourse->save($record)) {
                  // If is ajax build the response else redirect the user.
                  if ($ajax != "false") {
                      $response = [];
                      $response['code'] = '200';
                      $response['courses_list'] = $learningpath->render_courses_list();
                      $response['add_courses_form'] = $learningpath->render_courses_form();
                      $response['course_list_add'] = '';
                      $response['users'] = '';
                      $response['updated'] = '';
                      $response['required'] = '';
                      $response['response'] = '';
                      $response['msg'] = '';
                      return $response;
                  } else {
                      redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=courses");
                  }
              }
              break;

          // Updating a course as required for learningpath completion.
          case 'update-required':
              $learningpathcourse = new LearningPathCourse($courseid);
              if ($updated = $learningpathcourse->update_required($required)) {
                  // If it's ajax build the response else redirect the user.
                  if ($ajax != "false") {
                      $response = [];
                      $response['code'] = '200';
                      $response['updated'] = $updated;
                      $response['required'] = $required;
                      $response['courses_list'] = '';
                      $response['course_list_add'] = '';
                      $response['users'] = '';
                      $response['add_courses_form'] = '';
                      $response['response'] = '';
                      $response['msg'] = '';
                      return $response;
                  } else {
                      redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathcourse->data->learningpathid}&tab=courses");
                  }
              }
              break;
          // Assign prerequisites list to learningpath.
          case 'assign-prerequisites':
              $learningpathcourse = new LearningPathCourse($courseid);
              $learningpathcourse->remove_all_prerequisites();
              $learningpathcourse->add_prerequisites($prerequisites);
              // If it's ajax build the response else redirect the user.
                  $learningpathid = $learningpathid;
                  $learningpath = new LearningPath($learningpathid);
                  // Build response.
                  $response = [];
                  $response['code'] = '200';
                  $response['courses_list'] = $learningpath->render_courses_list();
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
              break;
          // Save courses position.
          case 'save-course-positions':
              // Create a new learningpath object where course will be added.
              $learningpathid = $learningpathid;
              $page = $pageno;
              $learningpath = new LearningPath($learningpathid);
              $learningpath->save_courses_positions($order,$page);
              // If it's ajax build the response else redirect the user.
              if ($ajax != "false") {
                  $response = [];
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
              } else {
                  $response = [];
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
                  redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=courses");
              }
              break;
          // Remove a list of users from a learningpath.
          case 'remove-users':
              foreach ($users as $user) {
                  $learningpathuser = new LearningPathUser($user);
                  $learningpathuser->delete();
              }
              // If it's ajax build the response else redirect the user.
              if ($ajax != "false") {
                  $response = [];
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
              } else {
                  $learningpathid = $learningpathid;
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
                  redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=users");
              }
              break;
          // Remove a list of cohorts from a learningpath.
          case 'remove-cohorts':
              foreach ($cohorts as $cohort) {
                  $learningpathcohort = new LearningPathCohort($cohort);
                  $users = $learningpathcohort->get_users();
                  foreach ($users as $user) {
                      $learningpathuser = new LearningPathUser($user->userid);
                      $learningpathuser->delete();
                  }
                  $learningpathcohort->delete();
              }
              // If it's ajax build the response else redirect the user.
              if ($ajax != "false") {
                  $response = [];
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                return $response;
              } else {
                  $learningpathid = $learningpathid;
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
                  redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=cohorts");
              }
              break;
          // Search learningpath users by name. Always will response with a json.
          case 'search-users':
              // Create a new learningpath object where course will be added.
              $learningpathid = $learningpathid;
              $name = required_param('name', PARAM_TEXT);
              $learningpath = new LearningPath($learningpathid);
              // Get users and response.
              $response = [];
              $response['code'] = '200';
              $response['users'] = $learningpath->search_users($name);
              $response['courses_list'] = '';
              $response['course_list_add'] = '';
              $response['updated'] = '';
              $response['required'] = '';
              $response['add_courses_form'] = '';
              $response['response'] = '';
              $response['msg'] = '';
              return $response;
              break;
          // Remove a user from a learningpath.
          case 'remove_user':
              // Delete learningpath user.
              $learningpathuser = new LearningPathUser($item);
              $learningpathuser->delete();
              // Redirection to learningpath users tab.
              $learningpathid = $learningpathid;
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
              redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=users");
              break;
          // Remove a course from a lerningpath.
          case 'remove_course':
              require_once "{$CFG->dirroot}/local/learningpaths/classes/forms/AddCoursesForm.php";
              $learningpathcourse = new LearningPathCourse($item);
              $deleted = $learningpathcourse->delete();
              if (!$deleted) {
                  throw new dml_write_exception('cannot_remove_the_course', 'local_learningpaths');
              }else{
                  /**
                   * Delete the course prerreq too
                   * @author Daniel Carmona
                   * @since 28-02-2018
                   * @paradiso
                  */
                  $learningpathcourse->deletePrerreq();
              }
              // Redirection to learning path on courses tab.
              $learningpathid = $learningpathid;
              $learningpath = new LearningPath($learningpathid,true);
              $courses_form = new AddCoursesForm("{$CFG->wwwroot}/local/learningpaths/view.php", ['courses' => $learningpath->data->available_courses, 'learningpath' => $learningpathid]);
              // Build response.
              $response = [];
              $response['code'] = '200';
              $response['courses_list'] = $learningpath->render_courses_list();
              $response['course_list_add'] = $courses_form->render();
              $response['users'] = '';
              $response['updated'] = '';
              $response['required'] = '';
              $response['add_courses_form'] = '';
              $response['response'] = '';
              $response['msg'] = '';
              return $response;
              break;

          // Delete learningpath.
          case 'delete_learningpath':
              $learningpath = new LearningPath($item);
              $learningpath->delete();
              redirect("{$CFG->wwwroot}/local/learningpaths/");
              break;
          case 'delete_learningpath_ajax':
              $learningpath = new LearningPath($item);
              $res = $learningpath->delete();
              $msg = ($res)?get_string('delete_success','local_learningpaths'):get_string('delete_error','local_learningpaths');
              $response = array();
              $response['code'] = '200';
              $response['courses_list'] = '';
              $response['course_list_add'] = '';
              $response['users'] = '';
              $response['updated'] = '';
              $response['required'] = '';
              $response['add_courses_form'] = '';
              $response['response'] = $res;
              $response['msg'] = $msg;
              return response;
              exit;
              break;
          case 'refresh_courses':
              $learningpathid = $learningpathid;
              $learningpath = new LearningPath($learningpathid,true);
              // Build response.
              $response = [];
              $response['code'] = '200';
              $response['courses_list'] = $learningpath->render_courses_list();
              $response['course_list_add'] = '';
              $response['users'] = '';
              $response['updated'] = '';
              $response['required'] = '';
              $response['add_courses_form'] = '';
              $response['response'] = '';
              $response['msg'] = '';
              return $response;
              exit();
              break;
      }
  }

  public static function actions_parameters() {
      return new external_function_parameters(
          array(
              'action' => new external_value(PARAM_RAW, 'Action'),
              'learningpathid' => new external_value(PARAM_RAW, 'LearningPath ID'),
              'courseid' => new external_value(PARAM_RAW, 'Course ID'),
              'prerequisites' => new external_value(PARAM_RAW, 'Prerequisites'),
              'pageno' => new external_value(PARAM_RAW, 'Page Number'),
              'order' => new external_value(PARAM_RAW, 'Order By'),
              'required' => new external_value(PARAM_RAW, 'Required Fields'),
              'item' => new external_value(PARAM_RAW, 'Item'),
              'cohorts' => new external_value(PARAM_RAW, 'Cohorts ID'),
              'users' => new external_value(PARAM_RAW, 'Users'),
              'ajax' => new external_value(PARAM_RAW, 'AJAX functionality'),
              'sesskey' => new external_value(PARAM_RAW, 'Session Key'),
          )
      );
  }

  public static function actions_returns() {
    return new external_single_structure(
      array(
        'code' => new external_value(PARAM_TEXT, 'Code'),
        'courses_list' => new external_value(PARAM_RAW, 'Course List'),
        'course_list_add' => new external_value(PARAM_RAW, 'Course List Add'),
        'users' => new external_value(PARAM_RAW, 'Users'),
        'updated' => new external_value(PARAM_RAW, 'Updated'),
        'required' => new external_value(PARAM_RAW, 'Required Fields'),
        'add_courses_form' => new external_value(PARAM_RAW, 'Course Form Data'),
        'response' => new external_value(PARAM_RAW, 'Response After Success'),
        'msg' => new external_value(PARAM_RAW, 'Response Message'),
              
      )
    );
  }

  public static function ajaxpage($action,$lpid,$search,$dashboard_per_page,$page,$selected) {
      global $CFG, $DB;

      require_once "{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPath.php";
      require_once "{$CFG->dirroot}/local/learningpaths/lib.php";
      /*
      * @author: VaibhavG
      * @desc: to get search result on the basis of last name, employee id and email
      * @since: 14 May 2019 
      */
      $company_sql_join = $company_sql_where = "";
      if ($company = lms_get_current_editing_company()) {
          $company_sql_join = "INNER JOIN {company_users} ON {company_users}.userid = {user}.id INNER JOIN {company} ON {company_users}.companyid={company}.id LEFT JOIN {department} ON {company_users}.departmentid={department}.id AND {company_users}.companyid={department}.company";
          $company_sql_where = "AND {company_users}.companyid = {$company->id}";
      } else {
          $company_sql_join = "LEFT JOIN {company_users} ON {company_users}.userid = {user}.id LEFT JOIN {company} ON {company_users}.companyid={company}.id LEFT JOIN {department} ON {company_users}.departmentid={department}.id AND {company_users}.companyid={department}.company";
          $company_sql_where = "";
      }
      if($search) {
        // Get current users list
        $users = $DB->get_records_sql("
            SELECT {user}.id id
            FROM {user}
            INNER JOIN {learningpath_users} ON {learningpath_users}.userid = {user}.id {$company_sql_join}
            WHERE {learningpath_users}.learningpathid = ? {$company_sql_where}", [$lpid]
        );
        // Get users ids, add guest user to array and convert to string to use on sql query
        $users = array_keys($users);
        $users[] = 1;
        $users = implode(",", $users);

        // Execute the query
        $available_users = $DB->get_records_sql("
            SELECT {user}.id id, {user}.firstname firstname, {user}.lastname lastname, {user}.email email,{company}.name as company_name,{department}.name as departname
            FROM {user} {$company_sql_join}
            WHERE {user}.id NOT IN ({$users}) AND {user}.deleted = 0 AND {user}.suspended = 0
            AND ({user}.firstname like '%{$search}%'
            OR {user}.lastname like '%{$search}%'
            OR {user}.email like '%{$search}%'
            OR {company}.name like '%{$search}%' OR {department}.name like '%{$search}%'){$company_sql_where}"
        );

        $users_form = new ManageUsersForm($CFG->wwwroot . '/local/learningpaths/view.php', ['users' => $available_users, 'learningpath' =>$lpid,'pageno'=>$page,'dashboard_per_page'=>$dashboard_per_page,'selected' => $selected]);
        $html = $users_form->render();
        return array('msg'=>1,'html'=>$html);
      } else {
        // Get current users list
        $users = $DB->get_records_sql("
            SELECT {user}.id id
            FROM {user}
            INNER JOIN {learningpath_users} ON {learningpath_users}.userid = {user}.id {$company_sql_join}
            WHERE {learningpath_users}.learningpathid = ? {$company_sql_where}", [$lpid]
        );

        // Get users ids, add guest user to array and convert to string to use on sql query
        $users = array_keys($users);
        $users[] = 1;
        $users = implode(",", $users);

        // Execute the query
        $available_users = $DB->get_records_sql("
            SELECT {user}.id id, {user}.firstname firstname, {user}.lastname lastname, {user}.email email,{company}.name as company_name,{department}.name as departname
            FROM {user} {$company_sql_join}
            WHERE {user}.id NOT IN ({$users}) AND {user}.deleted = 0 AND {user}.suspended = 0
            AND {user}.firstname like '%{$search}%'
            {$company_sql_where}"
        );

        $users_form = new ManageUsersForm($CFG->wwwroot . '/local/learningpaths/view.php', ['users' => $available_users, 'learningpath' =>$lpid,'pageno'=>$page,'dashboard_per_page'=>$dashboard_per_page,'selected' => $selected]);
        $html = $users_form->render();
        return array('msg'=>1,'html'=>$html);
      }

  }

  public static function ajaxpage_parameters() {
    return new external_function_parameters(
      array(
          'action' => new external_value(PARAM_RAW, 'Action'),
          'id' => new external_value(PARAM_RAW, 'Course Id'),
          'search' => new external_value(PARAM_RAW, 'Search Text'),
          'perpage' => new external_value(PARAM_INT, 'Per Page to Display the Data'),
          'page' => new external_value(PARAM_INT, 'Page Number'),
          'selected' => new external_value(PARAM_RAW, 'Page Number'),
      )
  );

  }

  public static function ajaxpage_returns() {
    return new external_single_structure(
      array(
        'html' => new external_value(PARAM_RAW, 'HTML Data Returns'),
        'msg' => new external_value(PARAM_RAW, 'Response Message'),
              
      )
    );

  }

      public static function ajaxnew_parameters() {
        return new external_function_parameters(
          array(
              'action' => new external_value(PARAM_RAW, 'Action'),
              'learningpathid' => new external_value(PARAM_RAW, 'LearningPath ID'),
              'courseid' => new external_value(PARAM_RAW, 'Course ID'),
              'prerequisites' => new external_value(PARAM_RAW, 'Prerequisites'),
              'pageno' => new external_value(PARAM_RAW, 'Page Number'),
              'order' => new external_value(PARAM_RAW, 'Order By'),
              'required' => new external_value(PARAM_RAW, 'Required Fields'),
              'item' => new external_value(PARAM_RAW, 'Item'),
              'cohorts' => new external_value(PARAM_RAW, 'Cohorts ID'),
              'users' => new external_value(PARAM_RAW, 'Users'),
              'ajax' => new external_value(PARAM_RAW, 'AJAX functionality'),
              'sesskey' => new external_value(PARAM_RAW, 'Session Key'),
          )
        );

    }

   // public static function ajaxnew($action,$id) {
    public static function ajaxnew($action,$learningpathid,$courseid,$prerequisites,$pageno,$order,$required,$item,$cohorts,$users,$ajax,$sesskey) {

        /* $html='hi';
         return array('msg'=>1,'html'=>$html);*/

          global $CFG, $USER,$DB;
        require_once("../../config.php");
        
        $users = explode(',', $users);
        $order = explode(',', $order);
        $prerequisites = explode(',', $prerequisites);
        $cohorts = explode(',', $cohorts);

      // Security Validations: Login, capability and sesskey.
      require_login();
      if (!confirm_sesskey()) {
          throw new moodle_exception(get_string('invalidsesskey', 'error'), 'core_plugin');
      }

      // Validate capabilities.
      $learningpathsmanager = has_capability('local/learningpaths:managealllearningpaths', context_system::instance());
      $learningpathscompanymanager = has_capability('local/learningpaths:managecompanylearningpaths', context_system::instance());
      if (!$learningpathsmanager && !$learningpathscompanymanager) {
          throw new moodle_exception(get_string('access_denied'));
      }
      // Require learning paths objects.
      require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPath.php");
      require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPathUser.php");
      require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPathCourse.php");
      require_once("{$CFG->dirroot}/blocks/lpd/lib/lib.php");
      // Actions.
     // print_r($action); 

      //echo 'asdasd'; die;
      switch ($action) {
          // Adding new courses to lerningpath.
          case 'add-course':
              // Create a new learningpath object where course will be added.
              $learningpathid = $learningpathid;
              $learningpath = new LearningPath($learningpathid);
              // Build course learningpath record.
              $record = new stdClass();
              $record->learningpathid = $learningpathid;
              $record->courseid = $courseid;
              $record->position = count($learningpath->data->courses) + 1;
              // Creating new larningpath course and save.
              $learningpathcourse = new LearningPathCourse();
              if ($learningpathcourse->save($record)) {
                  // If is ajax build the response else redirect the user.
                  if ($ajax != "false") {
                      $response = [];
                      $response['code'] = '200';
                      $response['courses_list'] = $learningpath->render_courses_list();
                      $response['add_courses_form'] = $learningpath->render_courses_form();
                      $response['course_list_add'] = '';
                      $response['users'] = '';
                      $response['updated'] = '';
                      $response['required'] = '';
                      $response['response'] = '';
                      $response['msg'] = '';
                      return $response;
                  } else {
                      redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=courses");
                  }
              }
              break;

          // Updating a course as required for learningpath completion.
          case 'update-required':
              $learningpathcourse = new LearningPathCourse($courseid);
              if ($updated = $learningpathcourse->update_required($required)) {
                  // If it's ajax build the response else redirect the user.
                  if ($ajax != "false") {
                      $response = [];
                      $response['code'] = '200';
                      $response['updated'] = $updated;
                      $response['required'] = $required;
                      $response['courses_list'] = '';
                      $response['course_list_add'] = '';
                      $response['users'] = '';
                      $response['add_courses_form'] = '';
                      $response['response'] = '';
                      $response['msg'] = '';
                      return $response;
                  } else {
                      redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathcourse->data->learningpathid}&tab=courses");
                  }
              }
              break;
          // Assign prerequisites list to learningpath.
          case 'assign-prerequisites':
              //echo 'assign-prerequisites111'; die;
              $learningpathcourse = new LearningPathCourse($courseid);
              $learningpathcourse->remove_all_prerequisites();
              $learningpathcourse->add_prerequisites($prerequisites);
              // If it's ajax build the response else redirect the user.
                  $learningpathid = $learningpathid;
                  $learningpath = new LearningPath($learningpathid);
                  // Build response.
                  $response = [];
                  $response['code'] = '200';
                  $response['courses_list'] = $learningpath->render_courses_list();
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
                  redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathcourse->data->learningpathid}&tab=courses");
              break;
          // Save courses position.
          case 'save-course-positions':
              // Create a new learningpath object where course will be added.
              $learningpathid = $learningpathid;
              $page = $pageno;
              $learningpath = new LearningPath($learningpathid);
              $learningpath->save_courses_positions($order,$page);
              // If it's ajax build the response else redirect the user.
              if ($ajax != "false") {
                  $response = [];
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
              } else {
                  $response = [];
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
                  redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=courses");
              }
              break;
          // Remove a list of users from a learningpath.
          case 'remove-users':
              foreach ($users as $user) {
                  $learningpathuser = new LearningPathUser($user);
                  $learningpathuser->delete();
              }
              // If it's ajax build the response else redirect the user.
              if ($ajax != "false") {
                  $response = [];
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
              } else {
                  $learningpathid = $learningpathid;
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
                  redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=users");
              }
              break;
          // Remove a list of cohorts from a learningpath.
          case 'remove-cohorts':
              foreach ($cohorts as $cohort) {
                  $learningpathcohort = new LearningPathCohort($cohort);
                  $users = $learningpathcohort->get_users();
                  foreach ($users as $user) {
                      $learningpathuser = new LearningPathUser($user->userid);
                      $learningpathuser->delete();
                  }
                  $learningpathcohort->delete();
              }
              // If it's ajax build the response else redirect the user.
              if ($ajax != "false") {
                  $response = [];
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                return $response;
              } else {
                  $learningpathid = $learningpathid;
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
                  redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=cohorts");
              }
              break;
          // Search learningpath users by name. Always will response with a json.
          case 'search-users':
              // Create a new learningpath object where course will be added.
              $learningpathid = $learningpathid;
              $name = required_param('name', PARAM_TEXT);
              $learningpath = new LearningPath($learningpathid);
              // Get users and response.
              $response = [];
              $response['code'] = '200';
              $response['users'] = $learningpath->search_users($name);
              $response['courses_list'] = '';
              $response['course_list_add'] = '';
              $response['updated'] = '';
              $response['required'] = '';
              $response['add_courses_form'] = '';
              $response['response'] = '';
              $response['msg'] = '';
              return $response;
              break;
          // Remove a user from a learningpath.
          case 'remove_user':
              // Delete learningpath user.
              $learningpathuser = new LearningPathUser($item);
              $learningpathuser->delete();
              // Redirection to learningpath users tab.
              $learningpathid = $learningpathid;
                  $response['code'] = '200';
                  $response['courses_list'] = '';
                  $response['course_list_add'] = '';
                  $response['users'] = '';
                  $response['updated'] = '';
                  $response['required'] = '';
                  $response['add_courses_form'] = '';
                  $response['response'] = '';
                  $response['msg'] = '';
                  return $response;
              redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=users");
              break;
          // Remove a course from a lerningpath.
          case 'remove_course':
              require_once "{$CFG->dirroot}/local/learningpaths/classes/forms/AddCoursesForm.php";
              $learningpathcourse = new LearningPathCourse($item);
              $deleted = $learningpathcourse->delete();
              if (!$deleted) {
                  throw new dml_write_exception('cannot_remove_the_course', 'local_learningpaths');
              }else{
                  /**
                   * Delete the course prerreq too
                   * @author Daniel Carmona
                   * @since 28-02-2018
                   * @paradiso
                  */
                  $learningpathcourse->deletePrerreq();
              }
              // Redirection to learning path on courses tab.
              $learningpathid = $learningpathid;
              $learningpath = new LearningPath($learningpathid,true);
              $courses_form = new AddCoursesForm("{$CFG->wwwroot}/local/learningpaths/view.php", ['courses' => $learningpath->data->available_courses, 'learningpath' => $learningpathid]);
              // Build response.
              $response = [];
              $response['code'] = '200';
              $response['courses_list'] = $learningpath->render_courses_list();
              $response['course_list_add'] = $courses_form->render();
              $response['users'] = '';
              $response['updated'] = '';
              $response['required'] = '';
              $response['add_courses_form'] = '';
              $response['response'] = '';
              $response['msg'] = '';
              return $response;
              break;

          // Delete learningpath.
          case 'delete_learningpath':
              $learningpath = new LearningPath($item);
              $learningpath->delete();
              redirect("{$CFG->wwwroot}/local/learningpaths/");
              break;
          case 'delete_learningpath_ajax':
              $learningpath = new LearningPath($item);
              $res = $learningpath->delete();
              $msg = ($res)?get_string('delete_success','local_learningpaths'):get_string('delete_error','local_learningpaths');
              $response = array();
              $response['code'] = '200';
              $response['courses_list'] = '';
              $response['course_list_add'] = '';
              $response['users'] = '';
              $response['updated'] = '';
              $response['required'] = '';
              $response['add_courses_form'] = '';
              $response['response'] = $res;
              $response['msg'] = $msg;
              return response;
              exit;
              break;
          case 'refresh_courses':
              $learningpathid = $learningpathid;
              $learningpath = new LearningPath($learningpathid,true);
                            $courses_form = new AddCoursesForm("{$CFG->wwwroot}/local/learningpaths/view.php", ['courses' => $learningpath->data->available_courses, 'learningpath' => $learningpathid]);
              // Build response.
              $response = [];
              $response['code'] = '200';
              $response['courses_list'] = $learningpath->render_courses_list();
              $response['course_list_add'] = $courses_form->render();
              $response['users'] = '';
              $response['updated'] = '';
              $response['required'] = '';
              $response['add_courses_form'] = '';
              $response['response'] = '';
              $response['msg'] = '';
              return $response;
              exit();
              break;
      }

    }

    public static function ajaxnew_returns() {
    return new external_single_structure(
      array(
        'code' => new external_value(PARAM_TEXT, 'Code'),
        'courses_list' => new external_value(PARAM_RAW, 'Course List'),
        'course_list_add' => new external_value(PARAM_RAW, 'Course List Add'),
        'users' => new external_value(PARAM_RAW, 'Users'),
        'updated' => new external_value(PARAM_RAW, 'Updated'),
        'required' => new external_value(PARAM_RAW, 'Required Fields'),
        'add_courses_form' => new external_value(PARAM_RAW, 'Course Form Data'),
        'response' => new external_value(PARAM_RAW, 'Response After Success'),
        'msg' => new external_value(PARAM_RAW, 'Response Message'),
              
      )
    );

    }
    /**
     * @ticket :- #1630 Create Learning Path API Mobile APP
     * @since :- 15 March 2022
     * @author :- Abhishek V
     */
    public function lp_mobile_api($userid) {
        global $DB;
        $params = self::validate_parameters(self::lp_mobile_api_parameters(),
                        array('userid' => $userid));

        $uid = $params['userid'];               
        $fields = "SELECT lp.id, lp.name as lpname, lp.description, lp.credits, lp.startdate, lp.enddate";
        $from =" FROM {learningpaths} as lp 
        LEFT JOIN {learningpath_users} lpu on lpu.learningpathid = lp.id
        WHERE lp.deleted = :deleted";

        if ($uid) {
            $from .= " AND lpu.userid = {$uid}";
        }
        $learningpaths = $DB->get_records_sql($fields . $from, array('deleted'=>0));

        $lpid = array();
        $not_lpid = '';
        if(count($learningpaths) > 0) {
            foreach ($learningpaths as $user_data) {
                $lpid[] = $user_data->id;
            }
        }
        $lpid_list = implode(",",$lpid);
        if($lpid_list) {
            $not_lpid = ' AND lp.id NOT IN ('.$lpid_list.')';
        }

        $from = "
            FROM {learningpaths} AS lp
            JOIN {learningpath_cohorts} lpu ON lpu.learningpathid = lp.id
            JOIN {cohort_members} members ON lpu.cohortid = members.cohortid
            WHERE lp.deleted = :deleted
            ";

        if ($userid) {
            $from .= " AND members.userid = $userid $not_lpid";
        }

        $learningpathcohorts = $DB->get_records_sql($fields . $from, array('deleted'=>0));

        $learningpaths = array_merge($learningpaths, $learningpathcohorts);

        $getdata = array();
        foreach ($learningpaths as $row) {
            $lpprogress = self::getLPProgressCourseCompleted($row->id,$userid) ;
            $description = json_decode($row->description);
            $desc = htmlspecialchars_decode($description->text);
            $getdata['lp'][$row->id]['lpid'] =  $row->id;
            $getdata['lp'][$row->id]['lpname'] =  $row->lpname;
            $getdata['lp'][$row->id]['description'] =  $desc;
            $getdata['lp'][$row->id]['credits'] =  $row->credits;
            $getdata['lp'][$row->id]['startdate'] =  $row->startdate;
            $getdata['lp'][$row->id]['enddate'] =  $row->enddate;
            $getdata['lp'][$row->id]['progress'] =  $lpprogress;
        }
        $responsedata = array();
        if($getdata) {
            $responsedata = $getdata;
            $responsedata['status'] = true;
        } else {
            $responsedata['lp'] = array(); 
            $responsedata['status'] = false;
        }
        return $responsedata;
    }

    public static function lp_mobile_api_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'userid id'),
            )
        );
    }

    public static function lp_mobile_api_returns() {
        return new external_single_structure(
            array(
                'lp' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'lpid' => new external_value(PARAM_RAW, 'The Learning Path id'),
                            'lpname' => new external_value(PARAM_RAW, 'The Learning Path Name'),
                            'description' => new external_value(PARAM_RAW, 'The Learning Path Description'),
                            'credits' => new external_value(PARAM_RAW, 'The Learning Path Credits'),
                            'startdate' => new external_value(PARAM_RAW, 'The Learning Path Startdate'),
                            'enddate' => new external_value(PARAM_RAW, 'The Learning Path Enddate'),
                            'progress' => new external_value(PARAM_RAW, 'The Learning Path Enddate'),
                        )
                    )
                ),
                'warnings' => new external_warnings(),
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
            )
        );
    }

    function getLPProgressCourseCompleted($learningpathid,$userid) {   
        global $DB;
        $courses = self::getCoursesInfo($learningpathid,true,$userid);
        $coursesCompleted = array();
        $countCourses = 0;
        if( count($courses) > 0 ){
            foreach ($courses as $key => $coure) {
                $courseprogress = self::getCourseProgress($coure->id,$userid) ;
                $required = $DB->get_record('learningpath_courses',array('learningpathid'=>$learningpathid,'courseid' => $coure->id))->required;
                if ($required) {
                    $countCourses ++;
                }
                if( (int)$courseprogress == 100 && $required) {
                    $coursesCompleted[$coure->id] = 1;    
                } 
            }
        }
        if( count($coursesCompleted) > 0 ) {
            $lpprogress =  ( ( count($coursesCompleted) * 100 ) / $countCourses ) ;
        } else {
            $lpprogress = 0 ;
        } 
        return round($lpprogress) ;
    }

    function getCoursesInfo($learningPath,$getcoursesonly = false,$userid, $limit = null, $offset = null) {
        global $DB;
        $arrayCD  = $courseObj   = array();
        $sqlLimit = "";
        $param = array();
        if(!is_null($limit) && !is_null($offset) && $limit > 0){
            $limit = (int)$limit;
            $offset = (int)$offset;
            $sqlLimit = " LIMIT {$limit} OFFSET {$offset}";
        }
        /* get course properties credits and enddate */
        $fieldcredits = $DB->get_record('course_info_field',array('shortname'=>'credits'));
        $sql = "
            SELECT  c.id,
                    c.fullname,
                    c.startdate ,
                    c.enddate ,
                    c.timecreated,
                    lpc.id as learningpath_course
            FROM 
                {learningpath_courses} as lpc , {course} as c 
            WHERE 
            lpc.courseid = c.id AND
            lpc.learningpathid = ".$learningPath."  AND lpc.course_active = 1 ORDER BY position ASC";

        /* get LP courses */
        $lpcourses = $DB->get_records_sql($sql,$param,$sqlLimit);
        if($getcoursesonly && $lpcourses )
        {
            return $lpcourses; 
        }
        if( count($lpcourses) > 0 ){
            foreach ($lpcourses as $key => $course) {
                $coursestd = new stdClass();
                $creditsvalue = '';
                $enddatevalue = '';
                /*if course properties credits and enddate exist and they have data then search specific course value */
                if($fieldcredits) {
                    $creditsvalue = $DB->get_record('course_info_data',array('courseid'=>$course->id ,'fieldid'=>$fieldcredits->id));
                }
                $coursestd->id = $course->id; 
                $coursestd->name = $course->fullname; 
                if(empty($course->startdate)){
                    $coursestd->startdate = $course->timecreated;
                } else {
                    $coursestd->startdate = $course->startdate;
                }
                $coursestd->learningpath_course = $course->learningpath_course;
                if($creditsvalue == null) {
                    $coursestd->credits = 0; 
                } else {
                    $coursestd->credits = $creditsvalue->data;
                }
                if($enddatevalue) {
                    $coursestd->enddate = $enddatevalue->data; 
                } else {
                    $coursestd->enddate = $course->enddate; 
                } 
                $progress = self::getCourseProgress($course->id,$userid);
                $coursestd->progress = $progress ;
                $courseObj[$course->id] = $coursestd ;
            }
        }   
        return $courseObj;
    }
    
    // Learning Path Course API

    public static function lp_mobile_course_api($userid,$learningPath) {
        global $DB,$CFG;
        $getdata  = $courseObj   = array();
        $sqlLimit = "";
        $param = array();
        $fieldcredits = $DB->get_record('course_info_field',array('shortname'=>'credits'));
        $sql = "
            SELECT  c.id,
                    c.fullname,
                    c.startdate ,
                    c.enddate ,
                    c.timecreated,
                    lpc.id as learningpath_course
            FROM 
                {learningpath_courses} as lpc , {course} as c 
            WHERE 
            lpc.courseid = c.id AND
            lpc.learningpathid = ".$learningPath."  AND lpc.course_active = 1 ORDER BY position ASC";
        $lpcourses = $DB->get_records_sql($sql,$param,$sqlLimit);
        if( count($lpcourses) > 0 ){
            foreach ($lpcourses as $key => $course) {
                $creditsvalue = '';
                $enddatevalue = '';
                if($fieldcredits) {
                    $creditsvalue = $DB->get_record('course_info_data',array('courseid'=>$course->id ,'fieldid'=>$fieldcredits->id));
                }
                $getdata['lpcourse'][$course->id]['courseid'] =  $course->id;
                $getdata['lpcourse'][$course->id]['courselink'] =  $CFG->wwwroot.'/course/view.php?id='.$course->id;
                $getdata['lpcourse'][$course->id]['coursename'] = $course->fullname;
                if(empty($course->startdate)){
                    $getdata['lpcourse'][$course->id]['startdate'] = ucfirst(userdate($course->timecreated,'%b %d, %Y'));
                } else {
                    $getdata['lpcourse'][$course->id]['startdate'] = ucfirst(userdate($course->startdate,'%b %d, %Y'));
                }
                if($creditsvalue == null) {
                    $getdata['lpcourse'][$course->id]['credits'] = 0;
                } else {
                    $getdata['lpcourse'][$course->id]['credits'] = $creditsvalue->data;
                }
                if($enddatevalue) {
                    $getdata['lpcourse'][$course->id]['enddate'] = ucfirst(userdate($enddatevalue->data,'%b %d, %Y'));
                } else {
                    if($course->enddate) {
                        $getdata['lpcourse'][$course->id]['enddate'] = ucfirst(userdate($course->enddate,'%b %d, %Y'));
                    } else {
                        $getdata['lpcourse'][$course->id]['enddate'] = "Not Set";
                    }
                } 
                $progress = self::getCourseProgress($course->id,$userid);
                $getdata['lpcourse'][$course->id]['progress'] = $progress;
            }
        }
        $responsedata = array();
        if($getdata) {
            $responsedata = $getdata;
            $responsedata['status'] = true;
        } else {
            $responsedata['lpcourse'] = array(); 
            $responsedata['status'] = false;
        }
        return $responsedata;

    }

    public static function lp_mobile_course_api_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'userid id'),
                'lpid' => new external_value(PARAM_INT, 'Learning Path id'),
            )
        );
    }

    public static function lp_mobile_course_api_returns() {
        return new external_single_structure(
            array(
                'lpcourse' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'courseid' => new external_value(PARAM_RAW, 'The Learning Path Name'),
                            'courselink' => new external_value(PARAM_RAW, 'The Learning Path Name'),
                            'coursename' => new external_value(PARAM_RAW, 'The Learning Path Description'),
                            'startdate' => new external_value(PARAM_RAW, 'The Learning Path Startdate'),
                            'enddate' => new external_value(PARAM_RAW, 'The Learning Path Enddate'),
                            'credits' => new external_value(PARAM_RAW, 'The Learning Path Credits'),
                            'progress' => new external_value(PARAM_RAW, 'The Learning Path Credits'),
                        )
                    )
                ),
                'warnings' => new external_warnings(),
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
            )
        );

    }
    public static function getCourseProgress($courseid,$userid) {
        global $DB, $USER;
        $total_progress = 0 ;
        $course = $DB->get_record('course',array('id'=>$courseid));
        //Get mod info
        $modinfo = get_fast_modinfo($course);
        //Get the completion info of the course
        $info = new completion_info($course);
        $complete = $info->is_course_complete($userid);
        if($complete){
            $total_progress = 100;
        } else{
            //check if the current user is enrolled in the current course
            $my_courses = enrol_get_my_courses();
            $is_enrollled = false;
            if (isset($my_courses[$course->id]->id))
                $is_enrollled = true;
            //If eht completion info is enabled for the site and for the course
            if (completion_info::is_enabled_for_site() && $info->is_enabled()) {
                //Get the completions for current user
                $completions = $info->get_completions($userid);
                // For aggregating activity completion.
                $activities = array();
                $activities_complete = 0;
                // Loop through course criteria.
                foreach ($completions as $completion) {
                    //If is a videofile get the progress of the user in the video
                    $criteria = $completion->get_criteria();
                    $complete = $completion->is_complete();
                    // Activities are a special case, so cache them and leave them till last.
                    if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                        $activities[$criteria->moduleinstance] = $complete;
                        if ($complete) {
                            $activities_complete++;
                        } else if($completion->get_criteria()->module == 'videofile' ){
                        }
                    }
                    else if($complete)
                    {
                        $activities_complete++;
                    }
                }
                if ( count($activities) > 0 ) {
                    $total_progress = ($activities_complete / count($activities));
                    $total_progress = $total_progress * 100;
                } else {
                $total_progress = 0 ; 
                }
            }
        }
        return $total_progress ;
    }
    // END #1630

    public static function duplicatecheck($inputname,$editid) {
      global $CFG, $USER,$DB;
      require_once("../../config.php");
      $response = [];
        if($DB->record_exists('learningpaths', ['name' => htmlspecialchars($inputname),'id' =>$editid])){
            $response['result'] = false;
          }else{
            if($DB->record_exists('learningpaths', ['name' => htmlspecialchars($inputname)])){
              $response['result'] = true;
            }else{
              $response['result'] = false;
            }
          }
      
      return $response;
    }

    public static function duplicatecheck_returns() {
      return new external_single_structure(
        array(
        'result' => new external_value(PARAM_BOOL, 'result'),
        )
      );
    }

    public static function duplicatecheck_parameters() {
      return new external_function_parameters(
        array(
              'inputname' => new external_value(PARAM_TEXT, 'input name'),
              'editid' => new external_value(PARAM_INT, 'edit id'),
          )
        );
    }

    public static function lp_mobile_course_finish_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'userid id'),
                'courseid' => new external_value(PARAM_INT, 'Course id'),
            )
        );
    }
    public static function lp_mobile_course_finish($userid,$courseid) {
       
        global $CFG, $DB;
        require_once($CFG->libdir. '/coursecatlib.php');
        require_once($CFG->libdir. '/completionlib.php');
        require_once('../../completion/cron.php');
        require_once($CFG->dirroot.'/theme/paradiso/lib.php');

        $responseData = array();
        $course = get_course($courseid);
        $coursename = $course->fullname;
        $info = new completion_info($course);
        
        $criteria_comp = self::get_criteria_completion($userid, $course->id);
        
        if(!$criteria_comp){
            $responseData['status'] = "failed";
            $responseData['course'] = '';
        }
        // Is course complete?
        $coursecomplete = $info -> is_course_complete($userid);
        // Has this user completed any criteria?
        $criteriacomplete = $info -> count_course_user_data($userid);
        // Load course completion.
        $params = array('userid' => $userid, 'course' => $course -> id);
        if ($criteria_comp) {
            self::process_course_completions($userid, $course->id);
            $enrolmethod = "manual";
            $roleid = $DB->get_field('role','id',['shortname'=>'student']);
            $enroll = false;
                    $coursePrereqs = $DB->get_records_sql("SELECT lc.courseid, lcp.learningpath_courseid FROM {learningpath_course_prereq} lcp JOIN {learningpath_courses} lc ON lcp.learningpath_courseid=lc.id WHERE lcp.prerequisite = ? AND lc.course_active = 1",[$courseid]);
                    foreach($coursePrereqs as $coursePrereq){
                        $user = $DB->get_record('user', array('id' => $userid, 'deleted' => 0), '*', MUST_EXIST);
                        $course = $DB->get_record('course', array('id' => $coursePrereq->courseid), '*', MUST_EXIST);
                        $context = context_course::instance($course->id);
                        if (!is_enrolled($context, $user)) {
                            $enrol = enrol_get_plugin($enrolmethod);
                            if ($enrol === null) {
                                return false;
                            }
                            $instance = $DB->get_record('enrol', array('courseid'=>$course->id,'enrol' => $enrolmethod));
                            $enrol->enrol_user($instance, $userid, $roleid);
                            $enroll = true;
                            if($enroll){
                                $responseData['status'] = "success";
                                $responseData['course'] = "";
                                $courses[] = $course;
                                // assign learningpath groups
                                $groups = get_learningpath_user_group(null, null, $course->id); // params are LP,userid and courseid
                                foreach ($groups as $key => $value) {
                                    groups_add_member($key, $user->id);
                                }
                            }
                        }
                    } 
                    if($coursePrereqs) {
                        $html .=  '<ul>';  
                        $html .= "<strong>".get_string('enrolled_user_text', 'format_paradiso');"</strong>";
                        foreach($courses as $course){
                            $url = new moodle_url("/course/view.php?id=$course->id");
                            $html .=  "<li><a href=".$url.">".$course->fullname."</a></li>";
                        }
                        $html .=  '</ul>';
                        $responseData['course'] = $html;
                        $responseData['status'] = "success";
                    } else {
                        $responseData['status'] = "success";
                        $responseData['course'] = '';
                    }
        } else {
            $responseData['status'] = "failed";
            $responseData['course'] = "";
        }
        return $responseData;
    }
    public static function lp_mobile_course_finish_returns() {
        return new external_single_structure(
            array(
            'course' => new external_value(PARAM_RAW, 'result'),
            'status' => new external_value(PARAM_RAW, 'result'),
            )
          );
    }
    function get_criteria_completion($uid, $cid){
        global $DB;
        $total_criteria = $DB->get_record_sql("SELECT count(*) as total FROM {course_completion_criteria} WHERE course = ? AND criteriatype=4",[$cid]);
        $total_criteria_completed = $DB->get_record_sql("SELECT count(*) as count FROM {course_completion_criteria} ccc, {course_modules_completion} cmc WHERE ccc.course = ? AND ccc.criteriatype=4 AND cmc.userid=? AND ccc.moduleinstance = cmc.coursemoduleid AND cmc.completionstate =1",[$cid, $uid]);
        
        if($total_criteria->total == $total_criteria_completed->count){
            return true;
        }else{
            return false;
        }
    
    }
    
    function process_course_completions($uid, $cid) {
        global $DB;
     
        $timecompleted = time();
        $ccompletion = new completion_completion(array('course' => $cid, 'userid' => $uid));
        $ccompletion->mark_complete($timecompleted);
        // Mark all users as aggregated
        $sql = "
            UPDATE
                {course_completions}
            SET
                reaggregate = 0
            WHERE
                reaggregate < :timestarted
            AND reaggregate > 0
        ";
    
        $DB->execute($sql, array('timestarted' => $timestarted));
    
        return true;
    }
}
