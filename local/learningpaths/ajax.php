<?php
require('../../config.php');

global $CFG, $DB;

require_once ("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPath.php");
require_once ("{$CFG->dirroot}/local/learningpaths/lib.php");

$page  = optional_param('page', 0, PARAM_INT);
$search  = optional_param('search', 0, PARAM_TEXT);
$lpid  = optional_param('id', 0, PARAM_INT);
$action = optional_param('action','',PARAM_TEXT);
$dashboard_per_page = optional_param('perpage', 10, PARAM_INT);
//if($action)
if($search)
{
   
    // Get current users list
    $users = $DB->get_records_sql("
        SELECT {user}.id id
        FROM {user}
        INNER JOIN {learningpath_users} ON {learningpath_users}.userid = {user}.id
        WHERE {learningpath_users}.learningpathid = ?", [$lpid]
    );

    // Get users ids, add guest user to array and convert to string to use on sql query
    $users = array_keys($users);
    $users[] = 1;
    $users = implode(",", $users);

    // Execute the query
    $available_users = $DB->get_records_sql("
        SELECT {user}.id id, {user}.firstname firstname, {user}.lastname lastname, {user}.email email
        FROM {user}
        WHERE {user}.id NOT IN ({$users}) AND {user}.deleted = 0 AND {user}.suspended = 0
        AND ({user}.firstname like '%{$search}%'
        OR {user}.lastname like '%{$search}%'
        OR {user}.email like '%{$search}%')
        "
    );

    $users_form = new ManageUsersForm($CFG->wwwroot . '/local/learningpaths/view.php', ['users' => $available_users, 'learningpath' =>$lpid]);
    $html = $users_form->render();
    echo json_encode(array('msg'=>1,'html'=>$html));
}
else
{
    // Get current users list
    $users = $DB->get_records_sql("
        SELECT {user}.id id
        FROM {user}
        INNER JOIN {learningpath_users} ON {learningpath_users}.userid = {user}.id
        WHERE {learningpath_users}.learningpathid = ?", [$lpid]
    );

    // Get users ids, add guest user to array and convert to string to use on sql query
    $users = array_keys($users);
    $users[] = 1;
    $users = implode(",", $users);

    // Execute the query
    $available_users = $DB->get_records_sql("
        SELECT {user}.id id, {user}.firstname firstname, {user}.lastname lastname, {user}.email email
        FROM {user} 
        WHERE {user}.id NOT IN ({$users}) AND {user}.deleted = 0 AND {user}.suspended = 0
        AND {user}.firstname like '%{$search}%'"
    );

    $users_form = new ManageUsersForm($CFG->wwwroot . '/local/learningpaths/view.php', ['users' => $available_users, 'learningpath' =>$lpid]);
    $html = $users_form->render();
    echo json_encode(array('msg'=>1,'html'=>$html));
}