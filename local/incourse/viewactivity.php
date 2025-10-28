<?php
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$moduleid = required_param('moduleid', PARAM_INT);

require_login($courseid);

$cm = get_coursemodule_from_id(null, $moduleid, $courseid, false, MUST_EXIST);
$modname = $cm->modname;

// Capture output
ob_start();

$modfile = $CFG->dirroot . "/mod/{$modname}/view.php";
if (file_exists($modfile)) {
    define('LOCAL_INCOURSE_EMBED', true); // optional flag to conditionally disable header/footer
    $_GET['id'] = $moduleid;
    include($modfile);
} else {
    echo "Activity not found.";
}

echo ob_get_clean();
