<?php
require_once(__DIR__ . '/../../config.php');
require_login();

header('Content-Type: application/json');

$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

require_once($CFG->libdir . '/completionlib.php');

$modinfo = get_fast_modinfo($courseid, $userid);
$cms = $modinfo->get_cms();
$completion = new completion_info(get_course($courseid));

$result = [];
$index = 1;

foreach ($cms as $cm) {
    if (!$cm->uservisible || !$completion->is_enabled($cm)) {
        continue;
    }

    $cmcompletion = $completion->get_data($cm, true, $userid);

    if ($cmcompletion->completionstate == COMPLETION_COMPLETE ||
        $cmcompletion->completionstate == COMPLETION_COMPLETE_PASS) {
        $status = 'Completed';
        $statusicon = '<span class="text-success"><i class="fa fa-check"></i></span>';
    } else {
        $status = ($cmcompletion->completionstate === COMPLETION_INCOMPLETE) ? 'Not Completed' : 'Not Completed';
        $statusicon = '<span class="text-danger"><i class="fa fa-times"></i></span>';
    }

    $result[] = [
        'srno' => $index++,
        'activityname' => $cm->name,
        'moduletype' => ucfirst($cm->modname),
        'status' => $statusicon . ' ' . $status
    ];
}

echo json_encode($result);
