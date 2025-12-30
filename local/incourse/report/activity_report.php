<?php
require_once("../../../config.php");
require_login();

global $DB, $OUTPUT, $CFG;

$courseid  = optional_param('courseid', 0, PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);

$course = null;
$sectionnum = 0;

if ($courseid) {
    $course = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);
}

if ($sectionid && $courseid) {
    $sectionrec = $DB->get_record('course_sections', ['id'=>$sectionid, 'course'=>$courseid], 'section', IGNORE_MISSING);
    if ($sectionrec) {
        $sectionnum = (int)$sectionrec->section;
    } else {
        $sectionid = 0;
        $sectionnum = 0;
    }
}

$PAGE->set_url(new moodle_url('/local/incourse/report/activity_report.php', ['courseid'=>$courseid,'sectionid'=>$sectionid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Activity Report');
echo $OUTPUT->header();

$courses = $DB->get_records_menu('course', null, 'fullname', 'id, fullname');
?>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>

<style>
#page-local-incourse-report-activity_report nav#mdb-navbar { display:none; }
#page-local-incourse-report-activity_report div[role="main"] { height:100vh; }
</style>

<div class="max-w-6xl mx-auto mt-4 mb-4">
    <h1 class="text-2xl font-bold flex gap-2">
        <span class="material-icons text-blue-600">insights</span>
        Activity Report
    </h1>
</div>

<div class="max-w-6xl mx-auto flex gap-4 mb-4">
<form method="get" class="flex gap-4">
    <select name="courseid" onchange="this.form.submit()" class="border px-3 py-2 rounded w-64">
        <option value="">-- Select Course --</option>
        <?php foreach ($courses as $id => $name): ?>
            <option value="<?= $id ?>" <?= $courseid==$id?'selected':'' ?>><?= format_string($name) ?></option>
        <?php endforeach; ?>
    </select>

<?php if ($courseid): ?>
    <select name="sectionid" onchange="this.form.submit()" class="border px-3 py-2 rounded w-64">
        <option value="0" <?= !$sectionid?'selected':'' ?>>All Sections</option>
        <?php
        $sections = $DB->get_records_sql("SELECT id, section FROM {course_sections} WHERE course = ? AND section > 0 ORDER BY section ASC", [$courseid]);
        foreach ($sections as $s):
        ?>
            <option value="<?= $s->id ?>" <?= $sectionid==$s->id?'selected':'' ?>><?= get_section_name($course, $s->section) ?></option>
        <?php endforeach; ?>
    </select>
<?php endif; ?>
</form>

<?php if ($courseid): ?>
<a href="export_all_section_csv.php?courseid=<?= $courseid ?>&sectionid=<?= $sectionid ?>" class="ml-auto bg-green-600 text-white px-4 py-2 rounded flex gap-2 d-none">
    <span class="material-icons text-sm">download</span> Export All
</a>
<?php endif; ?>
</div>

<?php
if (!$courseid) { echo $OUTPUT->footer(); exit; }

$context = context_course::instance($courseid);
require_capability('moodle/course:update', $context);

$users      = get_enrolled_users($context);
$completion = new completion_info($course);
$modinfo    = get_fast_modinfo($course);

function format_duration($s) {
    if ($s <= 0) return '-';
    return floor($s/3600).' hr '.floor(($s%3600)/60).' min';
}

function calculate_activity_time(array $logs, int $timeout = 1800): int {
    if (empty($logs)) return 0;
    $time = 0;
    $prev = $logs[0]->timecreated ?? 0;
    foreach ($logs as $l) {
        if ($prev && ($l->timecreated-$prev) <= $timeout) $time += ($l->timecreated-$prev);
        $prev = $l->timecreated;
    }
    return $time ?: 120;
}

function get_quiz_time_spent(int $quizid, int $userid): int {
    global $DB;
    $attempts = $DB->get_records('quiz_attempts', ['quiz'=>$quizid,'userid'=>$userid,'state'=>'finished']);
    $time = 0;
    foreach ($attempts as $a) if ($a->timestart && $a->timefinish) $time += ($a->timefinish - $a->timestart);
    return $time;
}

function scorm_time_to_seconds(string $time): int {
    if (strpos($time,'PT')===0) { preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $time,$m); return ($m[1]??0)*3600+($m[2]??0)*60+($m[3]??0);}
    if (preg_match('/(\d+):(\d+):(\d+)/',$time,$m)) return $m[1]*3600+$m[2]*60+$m[3];
    return 0;
}

function get_scorm_time_spent(int $scormid,int $userid): int {
    global $DB;
    $tracks = $DB->get_records_sql("SELECT value FROM {scorm_scoes_track} WHERE userid=:userid AND scormid=:scormid AND element IN ('cmi.core.total_time','cmi.session_time')", ['userid'=>$userid,'scormid'=>$scormid]);
    $time = 0; foreach($tracks as $t) $time += scorm_time_to_seconds($t->value);
    return $time;
}

function get_h5p_time_spent(int $cmid,int $userid): int {
    global $DB;
    return (int)$DB->get_field_sql("SELECT SUM(duration) FROM {h5pactivity_attempts} WHERE userid=:userid AND cmid=:cmid", ['userid'=>$userid,'cmid'=>$cmid]) ?: 0;
}

/* ================= TABLE ================= */
?>

<div class="max-w-6xl mx-auto border rounded overflow-x-auto">
<table class="w-full text-sm border-collapse">
<thead class="bg-gray-100">
<tr class="pt-2">
    <th class="pl-4">#</th><th>Pic</th><th>Name</th><th>Email</th>
    <th class="text-center">Total Time</th><th class="text-center">Activity</th>
</tr>
</thead>
<tbody>
<?php
$i=1;
foreach($users as $u):
    $total = $done = $time = 0;
    $cms = [];

    foreach($modinfo->get_cms() as $cm) {
        if ($sectionid && $cm->sectionnum != $sectionnum) continue;
        if (!$cm->is_visible_on_course_page()) continue;

        $total++; $cms[$cm->id] = true;
        if ($cm->completion) {
            $c = $completion->get_data($cm,false,$u->id);
            if ($c->completionstate != COMPLETION_INCOMPLETE) $done++;
        }
    }

    if ($cms) {
        list($insql,$params) = $DB->get_in_or_equal(array_keys($cms), SQL_PARAMS_NAMED);
        $params += ['userid'=>$u->id,'courseid'=>$courseid,'contextlevel'=>CONTEXT_MODULE];
        $logs = $DB->get_records_sql("SELECT timecreated FROM {logstore_standard_log} WHERE userid=:userid AND courseid=:courseid AND contextlevel=:contextlevel AND contextinstanceid $insql ORDER BY timecreated ASC",$params);

        $prev=0;
        foreach($modinfo->get_cms() as $cm) {
            if ($sectionid && $cm->sectionnum != $sectionnum) continue;
            if (!$cm->is_visible_on_course_page()) continue;
            try {
                switch($cm->modname) {
                    case 'quiz':
                        if ($DB->record_exists('quiz_attempts',['quiz'=>$cm->instance,'userid'=>$u->id])) {
                            $time += get_quiz_time_spent($cm->instance,$u->id);
                        }
                        break;
                    case 'scorm':
                        if ($DB->record_exists('scorm_scoes_track',['scormid'=>$cm->instance,'userid'=>$u->id])) {
                            $time += get_scorm_time_spent($cm->instance,$u->id);
                        }
                        break;
                    case 'h5pactivity':
                        if ($DB->get_manager()->table_exists('h5pactivity_attempts')) {
                            $time += get_h5p_time_spent($cm->id,$u->id);
                        }
                        break;
                }
            } catch(Exception $e) {}
        }

        // fallback to logs if nothing
        if ($time <= 0) {
            $prev=0;
            foreach ($logs as $l) {
                if ($prev && ($l->timecreated-$prev)<=1800) $time += ($l->timecreated-$prev);
                $prev=$l->timecreated;
            }
        }
    }
?>
<tr class="user-row hover:bg-gray-50">
    <td class="pl-4 pt-2"><?= $i++ ?></td>
    <td class="pt-2"><?= $OUTPUT->user_picture($u,['size'=>30]) ?></td>
    <td class="uname pt-2"><?= fullname($u) ?></td>
    <td class="uemail pt-2"><?= s($u->email) ?></td>
    <td class="text-center pt-2"><?= format_duration($time) ?></td>
    <td class="text-center pt-2">
        <button class="text-blue-600" onclick="openModal(<?= $u->id ?>,<?= (int)$sectionid ?>)">
            <?= $done ?>/<?= $total ?>
        </button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>


<!-- ================= MODAL (UNCHANGED) ================= -->
 <!-- ACTIVITY MODAL -->
<div id="activityModal"
     class="fixed inset-0 hidden bg-black/50 flex items-center justify-center z-50" style="z-index: 999;">

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
     

      <!-- META INFO -->
<div id="modalMeta"
     class="px-4 py-3 bg-gray-50 border-b text-sm font-semibold text-gray-700 hidden d-flex items-center gap-4">
    👤 <span id="metaUsername"></span>
    &nbsp; | &nbsp;
    📘 <span id="metaCourse"></span>
    &nbsp; | &nbsp;
    📂 <span id="metaSection"></span>
        <span>  <button id="exportCsvBtn"
                class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded flex items-center gap-2" style="    font-size: 10px;">
                <span class="material-icons text-sm" style="font-size: 12px;">download</span>
                Export CSV
            </button></span>
</div>

<!-- TABLE -->
<div class="max-h-[420px] overflow-y-auto border-t m-4">
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
<!-- (your existing modal + JS works perfectly) -->

<script>
function filterUsers() {
    const q = userSearch.value.toLowerCase();
    document.querySelectorAll('.user-row').forEach(r=>{
        const n=r.querySelector('.uname').innerText.toLowerCase();
        const e=r.querySelector('.uemail').innerText.toLowerCase();
        r.style.display=(n.includes(q)||e.includes(q))?'':'none';
    });
}

/* =========================
   TIME FORMATTER
========================= */
function formatTime(seconds) {
    if (!seconds || isNaN(seconds)) return '-';

    seconds = Number(seconds); // ensure numeric

    const hrs  = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    let out = [];
    if (hrs > 0) out.push(hrs + ' hr');
    if (mins > 0) out.push(mins + ' min');
    if (hrs === 0 && mins === 0) out.push(secs + ' sec'); // show seconds if < 1 min

    return out.join(' ');
}



/* =========================
   MODAL HANDLING
========================= */
let currentUserId    = null;
let currentSectionId = null;

function openModal(userid, sectionid) {

    const modal = document.getElementById('activityModal');
    const body  = document.getElementById('modalBody');

    currentUserId    = userid;
    currentSectionId = sectionid;

    modal.classList.remove('hidden');

    body.innerHTML = `
        <tr>
            <td colspan="5" class="text-center p-4 text-gray-500">
                Loading...
            </td>
        </tr>`;

fetch(`activity_report_ajax.php?userid=${userid}&sectionid=${sectionid}&courseid=<?= $courseid ?>`)
    .then(res => res.json())
    .then(resp => {
 if (!resp || !resp.activities) {
        throw 'Invalid response';
    }
        const meta = resp.meta;        // ✅ DEFINE FIRST
        const data = resp.activities;

        // ---- SHOW META HEADER ----
        document.getElementById('metaUsername').textContent = meta.username || '-';
        document.getElementById('metaCourse').textContent  = meta.course || '-';
        document.getElementById('metaSection').textContent = meta.section || '-';
        document.getElementById('modalMeta').classList.remove('hidden');

        modalData = data;

        let html = '';
        let totalSeconds = 0;

        if (!data.length) {
            body.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center p-4 text-gray-500">
                        No activities found
                    </td>
                </tr>`;
            return;
        }

        data.forEach(d => {
            totalSeconds += parseInt(d.timespent || 0);

            html += `
                <tr>
                    <td class="border px-3 py-2">${d.srno}</td>
                    <td class="border px-3 py-2">${d.activityname}</td>
                    <td class="border px-3 py-2 text-center">${d.moduletype}</td>
                    <td class="border px-3 py-2 text-center">${d.status_html}</td>
                    <td class="border px-3 py-2 text-center">${formatTime(d.timespent)}</td>
                </tr>
            `;
        });

        body.innerHTML = html;
    })
    .catch(err => {
        console.error(err);
        body.innerHTML = `
            <tr>
                <td colspan="5" class="text-center p-4 text-red-500">
                    Failed to load data
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
const exportBtn = document.getElementById('exportCsvBtn');
if (exportBtn) {
document.getElementById('exportCsvBtn').addEventListener('click', () => {

    if (!modalData.length) return;

    let csv = 'Sr No,Activity,Type,Status,Time Spent\n';

    modalData.forEach(d => {
        csv += `"${d.srno}","${d.activityname}","${d.moduletype}","${d.status_text}","${formatTime(d.timespent)}"\n`;
        
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `activity_report_${currentUserId}.csv`;
    link.click();
});

}
</script>

<?php echo $OUTPUT->footer(); ?>
