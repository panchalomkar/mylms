<?php
/**
 * @package     local_my_team
 * @author      Jayesh T
 * @copyright   rlms
*/

global $CFG;
require_once('../../../config.php');
require_once("{$CFG->dirroot}/local/my_team/lib.php");

define('AJAX_SCRIPT', true);

require_login();


$action = optional_param('action','', PARAM_TEXT);

switch($action){
    case 'showusers':
        $response = show_team_users();
        break;

    case 'assignusers':
        $response = assign_team_users();
        break;

    case 'removeusers':
        $response = remove_team_users();
        break;

    case 'searchusers':
        $response = search_team_users();
        break;

    case 'showall':
        $courses = show_all_courses_collection();
        $response = array(
            'status' => 'success',
            'courses' => $courses
        );
        break;

    case 'showcollections':
        $response = get_collection_courses_list(true);
        $response = array(
            'status' => 'success',
            'coursecollectioncourses' => $response
        );
        $response['filters'] = get_filter_tags(array());
        break;

    case 'seecollectioncourse':
        $collectionid = optional_param('collectionid', 0, PARAM_INT);
        $response = get_collection_courses_list(true, $collectionid);
        $response = array(
            'status' => 'success',
            'collection' => $response
        );
        break;
   
    default:
    # code...
    break;
}

echo json_encode($response);