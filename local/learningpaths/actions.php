<?php
require_once("../../config.php");

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

// Global variables.
global $CFG;

// Require learning paths objects.
require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPath.php");
require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPathUser.php");
require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPathCourse.php");
require_once("{$CFG->dirroot}/blocks/lpd/lib/lib.php");

// Actions.
switch (required_param('action', PARAM_TEXT)) {
    // Adding new courses to lerningpath.
    case 'add-course':
        // Create a new learningpath object where course will be added.
        $learningpathid = required_param('learningpathid', PARAM_INT);
        $learningpath = new LearningPath($learningpathid);

        // Build course learningpath record.
        $record = new stdClass();
        $record->learningpathid = $learningpathid;
        $record->courseid = required_param('course', PARAM_INT);
        $record->position = count($learningpath->data->courses) + 1;

        // Creating new larningpath course and save.
        $learningpathcourse = new LearningPathCourse();
        if ($learningpathcourse->save($record)) {
            // If is ajax build the response else redirect the user.
            if (optional_param("ajax", "false", PARAM_TEXT) != "false") {
                $response = [];
                $response['code'] = '200';
                $response['courses_list'] = $learningpath->render_courses_list();
                $response['add_courses_form'] = $learningpath->render_courses_form();
                echo json_encode($response);
            } else {
                redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=courses");
            }
        }
        break;

    // Updating a course as required for learningpath completion.
    case 'update-required':
        $learningpathcourse = new LearningPathCourse(required_param('courseid', PARAM_INT));
        if ($updated = $learningpathcourse->update_required(required_param('required', PARAM_INT))) {
            // If it's ajax build the response else redirect the user.
            if (optional_param("ajax", "false", PARAM_TEXT) != "false") {
                $response = [];
                $response['code'] = '200';
                $response['updated'] = $updated;
                $response['required'] = required_param('required', PARAM_INT);
                echo json_encode($response);
            } else {
                redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathcourse->data->learningpathid}&tab=courses");
            }
        }
        break;

    // Assign prerequisites list to learningpath.
    case 'assign-prerequisites':
        $learningpathcourse = new LearningPathCourse(required_param('courseid', PARAM_INT));
        $learningpathcourse->remove_all_prerequisites();
        $learningpathcourse->add_prerequisites(optional_param('prerequisites', [], PARAM_RAW));

        // If it's ajax build the response else redirect the user.
        if (optional_param("ajax", "false", PARAM_TEXT) != "false") {
            $learningpathid = required_param('learningpathid', PARAM_INT);
            $learningpath = new LearningPath($learningpathid);

            // Build response.
            $response = [];
            $response['code'] = '200';
            $response['courses_list'] = $learningpath->render_courses_list();

            echo json_encode($response);
        } else {
            redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathcourse->data->learningpathid}&tab=courses");
        }
        break;

    // Save courses position.
    case 'save-course-positions':
        // Create a new learningpath object where course will be added.
        $learningpathid = required_param('learningpathid', PARAM_INT);
        $page = required_param('pageno',  PARAM_INT);
        $learningpath = new LearningPath($learningpathid);
        $learningpath->save_courses_positions(required_param('order', PARAM_RAW),$page);

        // If it's ajax build the response else redirect the user.
        if (optional_param("ajax", "false", PARAM_TEXT) != "false") {
            $response = [];
            $response['code'] = '200';
            echo json_encode($response);
        } else {
            redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=courses");
        }
        break;

    // Remove a list of users from a learningpath.
    case 'remove-users':
        foreach (optional_param('users', [], PARAM_RAW) as $user) {
            $learningpathuser = new LearningPathUser($user);
            $learningpathuser->delete();
        }

        // If it's ajax build the response else redirect the user.
        if (optional_param("ajax", "false", PARAM_TEXT) != "false") {
            $response = [];
            $response['code'] = '200';
            echo json_encode($response);
        } else {
            $learningpathid = required_param('learningpathid', PARAM_INT);
            redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=users");
        }
        break;


    // Remove a list of cohorts from a learningpath.
    case 'remove-cohorts':
        foreach (optional_param('cohorts', [], PARAM_RAW) as $cohort) {
            $learningpathcohort = new LearningPathCohort($cohort);
            $users = $learningpathcohort->get_users();
            foreach ($users as $user) {
                $learningpathuser = new LearningPathUser($user->userid);
                $learningpathuser->delete();
            }
            $learningpathcohort->delete();
        }

        // If it's ajax build the response else redirect the user.
        if (optional_param("ajax", "false", PARAM_TEXT) != "false") {
            $response = [];
            $response['code'] = '200';
            echo json_encode($response);
        } else {
            $learningpathid = required_param('learningpathid', PARAM_INT);
            redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=cohorts");
        }
        break;

    // Search learningpath users by name. Always will response with a json.
    case 'search-users':
        // Create a new learningpath object where course will be added.
        $learningpathid = required_param('learningpathid', PARAM_INT);
        $name = required_param('name', PARAM_TEXT);
        $learningpath = new LearningPath($learningpathid);

        // Get users and response.
        $response = [];
        $response['code'] = '200';
        $response['users'] = $learningpath->search_users($name);

        echo json_encode($response);
        break;

    // Remove a user from a learningpath.
    case 'remove_user':
        // Delete learningpath user.
        $learningpathuser = new LearningPathUser(required_param('item', PARAM_INT));
        $learningpathuser->delete();

        // Redirection to learningpath users tab.
        $learningpathid = required_param('learningpath', PARAM_INT);
        redirect("{$CFG->wwwroot}/local/learningpaths/view.php?id={$learningpathid}&tab=users");
        break;

    // Remove a course from a lerningpath.
    case 'remove_course':
        require_once "{$CFG->dirroot}/local/learningpaths/classes/forms/AddCoursesForm.php";
        $learningpathcourse = new LearningPathCourse(required_param('item', PARAM_INT));
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
        $learningpathid = required_param('learningpathid', PARAM_INT);
        $learningpath = new LearningPath($learningpathid,true);
        $courses_form = new AddCoursesForm("{$CFG->wwwroot}/local/learningpaths/view.php", ['courses' => $learningpath->data->available_courses, 'learningpath' => $learningpathid]);
        // Build response.
        $response = [];
        $response['code'] = '200';
        $response['courses_list'] = $learningpath->render_courses_list();
        $response['course_list_add'] = $courses_form->render();
        echo json_encode($response);
        exit();
    break;

    // Delete learningpath.
    case 'delete_learningpath':
        $learningpath = new LearningPath(required_param('item', PARAM_INT));
        $learningpath->delete();
        redirect("{$CFG->wwwroot}/local/learningpaths/");
        break;
    case 'delete_learningpath_ajax':
        $learningpath = new LearningPath(required_param('item', PARAM_INT));
        $res = $learningpath->delete();
        $msg = ($res)?get_string('delete_success','local_learningpaths'):get_string('delete_error','local_learningpaths');
        echo json_encode(['response' => $res,'msg' => $msg]);exit();
        break;
    case 'refresh_courses':
        $learningpathid = required_param('learningpathid', PARAM_INT);
        $learningpath = new LearningPath($learningpathid,true);
        // Build response.
        $response = [];
        $response['code'] = '200';
        $response['courses_list'] = $learningpath->render_courses_list();
        echo json_encode($response);
        exit();
        
        break;
}
