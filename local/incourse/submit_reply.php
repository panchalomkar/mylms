<?php
require_once('../../config.php');
require_login();
global $DB, $USER;

$postid  = required_param('postid', PARAM_INT);
$message = required_param('message', PARAM_RAW_TRIMMED);

// Get parent post
$parentPost = $DB->get_record('forum_posts', ['id' => $postid], '*', MUST_EXIST);
$discussion = $DB->get_record('forum_discussions', ['id' => $parentPost->discussion], '*', MUST_EXIST);
$forum      = $DB->get_record('forum', ['id' => $discussion->forum], '*', MUST_EXIST);

// Prepare new post
$newpost = new stdClass();
$newpost->discussion = $discussion->id;
$newpost->forum      = $forum->id;
$newpost->parent     = $postid;
$newpost->userid     = $USER->id;
$newpost->subject    = 'Re: ' . $parentPost->subject;
$newpost->message    = $message;
$newpost->messageformat = FORMAT_HTML;
$newpost->created    = time();
$newpost->modified   = time();
$newpost->mailed     = 0;

// Insert into DB
$newpostid = $DB->insert_record('forum_posts', $newpost);

// Return JSON
echo json_encode([
    'status' => 'success',
    'postid' => $newpostid
]);
exit;
