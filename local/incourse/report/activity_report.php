<?php
require_once("../../../config.php");
require_login();

global $DB, $OUTPUT, $CFG;

$courseid  = optional_param('courseid', 0, PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/incourse/report/activity_report.php', [
    'courseid' => $courseid,
    'sectionid' => $sectionid
]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Activity Report');

echo $OUTPUT->header();

$courses = $DB->get_records_menu('course', null, 'fullname', 'id, fullname');
?>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
#page-local-incourse-report-activity_report nav#mdb-navbar { display:none; }
#page-local-incourse-report-activity_report div[role="main"] {
    filter: none;
    height: 100vh;
}
</style>
<div class="max-w-6xl mx-auto mb-4">
    <h1 class="text-2xl font-bold flex items-center gap-2">
        <span class="material-icons text-blue-600">insights</span>
        Activity Report
    </h1>
</div>

<!-- FILTER BAR -->
<div class="max-w-6xl mx-auto flex flex-wrap gap-4 mb-4">

    <!-- COURSE -->
    <form method="get" class="flex gap-4">
        <select name="courseid"
            onchange="this.form.submit()"
            class="border px-3 py-2 rounded w-64">
            <option value="">-- Select Course --</option>
            <?php foreach ($courses as $id => $name): ?>
                <option value="<?= $id ?>" <?= $courseid == $id ? 'selected' : '' ?>>
                    <?= format_string($name) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($courseid): ?>
        <select name="sectionid"
            onchange="this.form.submit()"
            class="border px-3 py-2 rounded w-64">
            <option value="0">All Sections</option>
            <?php
            $sections = $DB->get_records_sql("
                SELECT id, section
                FROM {course_sections}
                WHERE course = ? AND section > 0
                ORDER BY section ASC
            ", [$courseid]);

            foreach ($sections as $s):
            ?>
                <option value="<?= $s->id ?>" <?= $sectionid == $s->id ? 'selected' : '' ?>>
                    <?= get_section_name($DB->get_record('course', ['id'=>$courseid]), $s->section) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </form>

    <?php if ($courseid && $sectionid): ?>
    <a href="export_all_section_csv.php?courseid=<?= $courseid ?>&sectionid=<?= $sectionid ?>"
       class="ml-auto bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
        Export All Data (CSV)
    </a>
    <?php endif; ?>

</div>

<?php
if (!$courseid) {
    echo $OUTPUT->footer();
    exit;
}

$course = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_capability('moodle/course:update', $context);

$users = get_enrolled_users($context);
$completion = new completion_info($course);
$modinfo = get_fast_modinfo($course);
?>
 <!-- Search -->
  <div class="max-w-6xl mx-auto flex flex-wrap gap-4 mb-4">
    <div class="col-md-4">
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
<!-- TABLE -->
<div class="max-w-6xl mx-auto border rounded overflow-x-auto">
<table class="w-full text-sm border-collapse">

<thead class="bg-gray-100">
<tr>
    <th class="border px-3 py-2">#</th>
    <th class="border px-3 py-2">Pic</th>
    <th class="border px-3 py-2">Name</th>
    <th class="border px-3 py-2">Email</th>
    <th class="border px-3 py-2">Total Dedicated Time</th>
    <th class="border px-3 py-2">Activity</th>
</tr>
</thead>

<tbody>
<?php
$i = 1;
foreach ($users as $u):
    $total = 0;
    $done  = 0;
    $time  = 0;

    foreach ($modinfo->get_cms() as $cm) {
        if ($sectionid && $cm->section != $sectionid) continue;
        if (!$cm->is_visible_on_course_page()) continue;

        $total++;

        if ($cm->completion) {
            $c = $completion->get_data($cm, false, $u->id);
            if ($c->completionstate != COMPLETION_INCOMPLETE) {
                $done++;
            }
        }
    }

    $logs = $DB->get_records_sql("
        SELECT timecreated
        FROM {logstore_standard_log}
        WHERE userid = ?
          AND courseid = ?
        ORDER BY timecreated ASC
    ", [$u->id, $courseid]);

    $prev = 0;
    foreach ($logs as $l) {
        if ($prev && ($l->timecreated - $prev) < 1800) {
            $time += ($l->timecreated - $prev);
        }
        $prev = $l->timecreated;
    }
?>
<tr class="hover:bg-gray-50 user-row">
    <td class="border px-3 py-2"><?= $i++ ?></td>
    <td class="border px-3 py-2 text-center"><?= $OUTPUT->user_picture($u, ['size'=>30]) ?></td>
    <td class="border px-3 py-2 uname"><?= fullname($u) ?></td>
    <td class="border px-3 py-2 uemail"><?= s($u->email) ?></td>
    <td class="border px-3 py-2 text-center"><?= gmdate("H:i", $time) ?></td>
    <td class="border px-3 py-2 text-center">
       <button class="text-blue-600 hover:underline"
    onclick="openModal(<?= $u->id ?>,<?= (int)$sectionid ?>)">
    <?= $done ?>/<?= $total ?>
</button>

    </td>
</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>
<!-- ACTIVITY MODAL -->
<div id="activityModal"
     class="fixed inset-0 hidden bg-black/50 flex items-center justify-center z-50">

    <div class="bg-white rounded-lg shadow-xl w-11/12 max-w-5xl">

        <!-- HEADER -->
        <div class="flex items-center justify-between px-5 py-3 border-b">
            <h3 class="text-lg font-semibold flex items-center gap-2">
                <span class="material-icons text-blue-600">list_alt</span>
                Activity Details
            </h3>
            <button onclick="closeModal()"
                class="material-icons text-gray-500 hover:text-black">
                close
            </button>
        </div>

        <!-- ACTION BAR -->
        <div class="flex justify-end px-5 py-3">
            <button id="exportCsvBtn"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm flex items-center gap-2">
                <span class="material-icons text-sm">download</span>
                Export CSV
            </button>
        </div>

        <!-- TABLE -->
        <div class="max-h-[420px] overflow-y-auto border-t">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 sticky top-0">
                    <tr>
                        <th class="border px-3 py-2 w-12">#</th>
                        <th class="border px-3 py-2">Activity</th>
                        <th class="border px-3 py-2 text-center">Type</th>
                        <th class="border px-3 py-2 text-center">Status</th>
                        <th class="border px-3 py-2 text-center">Time Spent</th>
                    </tr>
                </thead>
                <tbody id="modalBody">
                    <tr>
                        <td colspan="5" class="text-center p-4 text-gray-500">
                            No data
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
/* =========================
   USER SEARCH
========================= */
function filterUsers() {
    const q = document.getElementById('userSearch').value.toLowerCase();

    document.querySelectorAll('.user-row').forEach(row => {
        const name  = row.querySelector('.uname').innerText.toLowerCase();
        const email = row.querySelector('.uemail').innerText.toLowerCase();
        row.style.display = (name.includes(q) || email.includes(q)) ? '' : 'none';
    });
}

/* =========================
   TIME FORMATTER
========================= */
function formatTime(seconds) {
    if (!seconds) return '-';
    const hrs  = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    return `${hrs ? hrs + ' hr ' : ''}${mins ? mins + ' min' : ''}`.trim();
}

/* =========================
   MODAL HANDLING
========================= */
let currentUserId    = null;
let currentSectionId = null;

function openModal(userid, sectionid) {

    const modal = document.getElementById('activityModal');
    if (!modal) {
        console.error('activityModal not found in DOM');
        return;
    }

    currentUserId    = userid;
    currentSectionId = sectionid;

    modal.classList.remove('hidden');

    const body = document.getElementById('modalBody');
    body.innerHTML =
        '<tr><td colspan="5" class="text-center p-4">Loading...</td></tr>';

    fetch(`activity_report_ajax.php?userid=${userid}&sectionid=${sectionid}`)
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(d => {
                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="border px-3 py-2">${d.srno}</td>
                        <td class="border px-3 py-2">${d.activityname}</td>
                        <td class="border px-3 py-2 text-center">${d.moduletype}</td>
                        <td class="border px-3 py-2 text-center">${d.status}</td>
                        <td class="border px-3 py-2 text-center">${formatTime(d.timespent)}</td>
                    </tr>`;
            });
            body.innerHTML = html || `
                <tr>
                    <td colspan="5" class="text-center p-4 text-gray-500">
                        No activities found
                    </td>
                </tr>`;
        });
}


function closeModal() {
    document.getElementById('activityModal').classList.add('hidden');
}

/* =========================
   MODAL CSV EXPORT
========================= */
document.getElementById('exportCsvBtn').addEventListener('click', () => {

    if (!currentUserId || !currentSectionId) return;

    let csv = 'Sr No,Activity,Type,Status,Time Spent\n';

    document.querySelectorAll('#modalBody tr').forEach(row => {
        let cols = row.querySelectorAll('td');
        let rowData = [];
        cols.forEach(col => rowData.push(`"${col.innerText}"`));
        csv += rowData.join(',') + '\n';
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `user_${currentUserId}_section_${currentSectionId}.csv`;
    link.click();
});
</script>

<?php echo $OUTPUT->footer(); ?>
