<?php 
require_once("../../../config.php");
require_login();

global $DB, $OUTPUT, $CFG;

$courseid = optional_param('courseid', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/incourse/report/activity_report.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Activity Report');
?>

<style>
#page-local-incourse-report-activity_report nav#mdb-navbar { display:none; }
#page-local-incourse-report-activity_report div[role="main"] {
    filter: none;
    height: 100vh;
}
</style>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>

<?php
echo $OUTPUT->header();
$courses = $DB->get_records_menu('course', null, 'fullname', 'id, fullname');
?>

<!-- TITLE -->
<div class="text-2xl font-bold mb-3 max-w-6xl mx-auto flex items-center gap-2 mt-2">
    <span class="material-icons text-blue-600">insights</span>
    Activity Report
</div>

<!-- COURSE SELECT + SEARCH -->
<div class="max-w-6xl mx-auto pb-4 flex flex-col md:flex-row md:items-center gap-4">

    <!-- Course Select -->
    <div class="flex items-center gap-3">
        <span class="material-icons text-blue-500 text-2xl">school</span>
        <form method="get" class="m-0">
            <select
                name="courseid"
                class="border px-3 py-2 rounded w-72 focus:outline-none focus:ring-2 focus:ring-blue-500"
                onchange="this.form.submit()"
            >
                <option value="">-- Select Course --</option>
                <?php foreach ($courses as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php echo ($id == $courseid ? 'selected' : ''); ?>>
                        <?php echo format_string($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Search -->
    <div class="relative w-full md:w-80">
        <span class="material-icons absolute left-3 mr-5 top-1/2 -translate-y-1/2 text-gray-400">
            search
        </span>
        <input
            id="userSearch"
            onkeyup="filterUsers()"
            type="text"
            class="border pl-10 pr-3 py-2 w-full pl-5 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Search by name or email"
        >
    </div>

</div>

<?php
if (!$courseid) {
    echo $OUTPUT->footer();
    exit;
}

$course  = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_capability('moodle/course:update', $context);
$PAGE->set_heading($course->fullname);

// Sections with REAL NAMES
$sections = $DB->get_records_sql("
    SELECT id, section, name
    FROM {course_sections}
    WHERE course = :courseid AND section > 0
    ORDER BY section ASC
", ['courseid'=>$courseid]);

$users = get_enrolled_users($context);

// Build activity summary
$activitydata = [];
foreach ($users as $u) {
    $completion = new \completion_info($course);

    foreach ($sections as $s) {
        $modinfo = get_fast_modinfo($course);
        $totalactivities = 0;
        $completedactivities = 0;

        foreach ($modinfo->get_cms() as $cm) {
            // Skip section 0, hidden or deleted modules
            if (!isset($cm->sectionnum) || $cm->sectionnum != $s->section) continue;
            if ($cm->modname === 'forum' && $cm->instance == $course->newsforum) continue;
            if (!empty($cm->deletioninprogress) || (method_exists($cm, 'is_deleted') && $cm->is_deleted())) continue;
            if (!$cm->is_visible_on_course_page()) continue;

            $totalactivities++;

            if (!empty($cm->completion) && $cm->completion > 0) {
                $modcompletion = $completion->get_data($cm, false, $u->id);
                if (!empty($modcompletion->completionstate) && $modcompletion->completionstate != COMPLETION_INCOMPLETE) {
                    $completedactivities++;
                }
            }
        }

        // Time spent (accurate using session gaps)
        $cms = $DB->get_records_sql("
            SELECT cm.id
            FROM {course_modules} cm
            WHERE cm.course = :courseid
              AND cm.section = :sectionid
        ", [
            'courseid' => $courseid,
            'sectionid' => $s->id
        ]);

        $cmids = array_keys($cms);
        $time = 0;
        if ($cmids) {
            list($insql, $params) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
            $params += [
                'userid' => $u->id,
                'courseid' => $courseid,
                'contextlevel' => CONTEXT_MODULE
            ];

            $logs = $DB->get_records_sql("
                SELECT timecreated
                FROM {logstore_standard_log}
                WHERE userid = :userid
                  AND courseid = :courseid
                  AND contextlevel = :contextlevel
                  AND contextinstanceid $insql
                ORDER BY timecreated ASC
            ", $params);

            $prev = 0;
            $timeout = 30 * 60; // 30 minutes idle
            foreach ($logs as $log) {
                if ($prev) {
                    $gap = $log->timecreated - $prev;
                    if ($gap > 0 && $gap <= $timeout) $time += $gap;
                }
                $prev = $log->timecreated;
            }
        }

        $activitydata[$u->id][$s->id] = [
            'count' => $completedactivities,
            'total' => $totalactivities,
            'time'  => $time
        ];
    }
}
?>

<!-- TABLE WRAPPER -->
<div class="max-w-6xl mx-auto border rounded-lg overflow-hidden">

    <!-- Horizontal Scroll -->
    <div class="overflow-x-auto relative">

        <table id="activityTable" class="min-w-[1200px] border-collapse w-full text-sm">

            <!-- HEADER -->
            <thead class="bg-gray-100 sticky top-0 z-30">
                <tr>
                    <th class="sticky left-0 z-40 bg-gray-100 border px-3 py-2 w-12">#</th>
                    <th class="sticky left-10 z-40 bg-gray-100 border px-3 py-2 w-16">Pic</th>
                    <th class="sticky z-40 bg-gray-100 border px-3 py-2 w-48" style="left:6rem;">Name</th>
                    <th class="border px-4 py-2 text-center whitespace-nowrap">Email</th>

                    <?php foreach ($sections as $s): ?>
                        <th colspan="2" class="border px-4 py-2 text-center whitespace-nowrap">
                            <?php echo format_string(get_section_name($course, $s->section)); ?>
                        </th>
                    <?php endforeach; ?>
                </tr>

                <tr class="bg-gray-50">
                    <th colspan="3"
                        class="sticky left-0 z-30 bg-gray-50 border"></th>

                    <?php foreach ($sections as $s): ?>
                        <th class="border px-3 py-1 text-xs text-center">Activity</th>
                        <th class="border px-3 py-1 text-xs text-center">Time</th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <!-- BODY -->
            <tbody>
            <?php $i=1; foreach ($users as $u): ?>
                <tr class="hover:bg-gray-50 user-row">

                    <!-- FIXED COLUMNS -->
                    <td class="sticky left-0 z-20 bg-white border px-3 py-2 text-center">
                        <?php echo $i++; ?>
                    </td>

                    <td class="sticky left-10 z-20 bg-white border px-3 py-2 text-center">
                        <?php echo $OUTPUT->user_picture($u, ['size'=>30]); ?>
                    </td>

                    <td class="sticky z-20 bg-white border px-3 py-2 uname whitespace-nowrap"style="left:6rem;">
                        <?php echo fullname($u); ?>
                    </td>

                    <td class="border px-3 py-2 text-center">
                        <?php echo s($u->email); ?>
                    </td>

                    <!-- SCROLLABLE SECTIONS -->
                    <?php foreach ($sections as $s):
                        $d = $activitydata[$u->id][$s->id] ?? ['count'=>0,'total'=>0,'time'=>0];
                    ?>
                        <td class="border px-3 py-2 text-center">
                            <button
                                class="text-blue-600 font-semibold hover:underline"
                                onclick="openModal(<?php echo $u->id; ?>,<?php echo $s->id; ?>)">
                                <?php echo $d['count'] . '/' . $d['total']; ?>
                            </button>
                        </td>

                        <td class="border px-3 py-2 text-center text-xs text-gray-600">
                            <?php echo gmdate("H:i", $d['time']); ?>
                        </td>
                    <?php endforeach; ?>

                </tr>
            <?php endforeach; ?>
            </tbody>

        </table>
    </div>
</div>
<!-- MODAL -->
<div id="activityModal"
     class="fixed inset-0 hidden bg-black bg-opacity-40 flex items-center justify-center z-50">

    <div class="bg-white p-6 rounded-lg w-11/12 max-w-4xl shadow-lg">

        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Activity Details</h3>
            <button onclick="closeModal()" class="material-icons text-gray-500 hover:text-black">
                close
            </button>
        </div>

        <div class="flex justify-end mb-3">
            <button id="exportCsvBtn"
                class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                Export CSV
            </button>
        </div>

        <div class="max-h-[400px] overflow-y-auto border rounded">
            <table class="min-w-full text-sm border-collapse">
                <thead class="bg-gray-100 sticky top-0">
                    <tr>
                        <th class="border px-3 py-2">#</th>
                        <th class="border px-3 py-2">Activity</th>
                        <th class="border px-3 py-2">Type</th>
                        <th class="border px-3 py-2">Status</th>
                        <th class="border px-3 py-2">Time Spent</th>
                    </tr>
                </thead>
                <tbody id="modalBody"></tbody>
            </table>
        </div>

    </div>
</div>

</div>
<script>
// USER SEARCH
function filterUsers(){
    let q = document.getElementById('userSearch').value.toLowerCase();
    document.querySelectorAll('.user-row').forEach(r => {
        let n = r.querySelector('.uname').innerText.toLowerCase();
        let e = r.querySelector('.uemail').innerText.toLowerCase();
        r.style.display = (n.includes(q) || e.includes(q)) ? '' : 'none';
    });
}

// Helper: format seconds to hr/min
function formatTime(seconds) {
    if (!seconds) return '';
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    let str = '';
    if (hrs > 0) str += `${hrs} hr `;
    if (mins > 0) str += `${mins} min`;
    return str.trim();
}

// Track current user & section for CSV export
let currentUserId = null;
let currentSectionId = null;

// MODAL OPEN
function openModal(userid, sectionid) {
    currentUserId = userid;
    currentSectionId = sectionid;

    document.getElementById('activityModal').classList.remove('hidden');

    fetch(`activity_report_ajax.php?userid=${userid}&sectionid=${sectionid}`)
        .then(response => response.json())
        .then(data => {
            let html = '';
            data.forEach(d => {
                html += `<tr>
                    <td>${d.srno}</td>
                    <td>${d.activityname}</td>
                    <td>${d.moduletype}</td>
                    <td>${d.status}</td>
                    <td>${formatTime(d.timespent)}</td>
                </tr>`;
            });
            document.getElementById('modalBody').innerHTML = html;
        });
}

// MODAL CLOSE
function closeModal() {
    document.getElementById('activityModal').classList.add('hidden');
}

// CSV EXPORT
document.getElementById('exportCsvBtn').addEventListener('click', () => {
    if (!currentUserId || !currentSectionId) return;

    let rows = document.querySelectorAll('#activityModal table tbody tr');
    let csv = 'Sr No,Activity,Type,Status,Time Spent\n';

    rows.forEach(row => {
        let cols = row.querySelectorAll('td');
        let rowData = [];
        cols.forEach(col => {
            let text = col.innerText.replace(/<\/?[^>]+(>|$)/g, "");
            rowData.push('"' + text + '"');
        });
        csv += rowData.join(',') + '\n';
    });

    let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    let link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `activities_user_${currentUserId}_section_${currentSectionId}.csv`;
    link.click();
});
</script>

<?php echo $OUTPUT->footer(); ?>
