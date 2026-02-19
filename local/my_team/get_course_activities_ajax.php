<?php
require_once(__DIR__ . '/../../config.php');
require_login();

header('Content-Type: application/json');

$userid   = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

require_once($CFG->libdir . '/completionlib.php');

$modinfo    = get_fast_modinfo($courseid, $userid);
$cms        = $modinfo->get_cms();
$completion = new completion_info(get_course($courseid));

$result = [];
$index  = 1;
function normalize_module_name(string $modname, string $label): string {

    $map = [
        // Exact plugin names
        'videotime'        => 'Video',
        'pdfjsloader'     => 'PDF',
        'pdf'             => 'PDF',
        'iomadcertificate'=> 'Certificate',
        'customcert'     => 'Certificate',
        'googlemeet'      => 'GoogleMeet',
        'h5pactivity'     => 'H5P',
        'scorm'           => 'SCORM',
        'quiz'            => 'Quiz',
        'assign'          => 'Assignment',
        'forum'           => 'Forum',
        'page'            => 'Page',
        'url'             => 'URL',
        'ilt'             => 'ILT',
    ];

    // 1️⃣ Exact modname match
    if (isset($map[$modname])) {
        return $map[$modname];
    }

    // 2️⃣ Fallback: clean label → single word
    $label = preg_replace('/[^a-zA-Z0-9 ]/', '', $label); // remove symbols
    $label = trim($label);

    // Take first word only
    $parts = explode(' ', $label);
    return ucfirst($parts[0]);
}

foreach ($cms as $cm) {
    if (!$cm->uservisible || !$completion->is_enabled($cm)) {
        continue;
    }

    $cmcompletion = $completion->get_data($cm, true, $userid);

    if (
        $cmcompletion->completionstate == COMPLETION_COMPLETE ||
        $cmcompletion->completionstate == COMPLETION_COMPLETE_PASS
    ) {
        $status     = 'Completed';
        $statusicon = '<span class="text-success"><i class="fa fa-check"></i></span>';
    } else {
        $status     = 'Not Completed';
        $statusicon = '<span class="text-danger"><i class="fa fa-times"></i></span>';
    }

    // ✅ Correct module name (human readable)
   $rawlabel   = get_string('modulename', $cm->modname);
$modulename = normalize_module_name($cm->modname, $rawlabel);


    $result[] = [
        'srno'         => $index++,
        'activityname'=> $cm->name,
        'moduletype'  => $modulename,
        'status'      => $statusicon . ' ' . $status
    ];
}

echo json_encode($result);
