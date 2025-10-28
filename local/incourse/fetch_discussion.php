<?php
require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
require_login();

echo local_incourse_render_forum_discussion($id);
